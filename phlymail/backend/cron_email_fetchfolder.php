<?php
/**
 * Actual syncing task to update the database index of a given IMAP folder.
 *
 * After running this task the folder's DB index is up to date in terms
 * of mail status, mail flags and so on.
 *
* @author Matthias Sommerfeld, phlyLabs Berlin
* @copyright 2001-2015 phlyLabs Berlin, http://phlylabs.de
* @version 0.2.0 2015-03-31
*/
class cron_email_fetchfolder
{
    protected $smsactive         = false;
    protected $base_path         = '$0/$1';
    protected $inbox_name        = 'inbox';
    protected $temp_name         = '.tmp';
    protected $spamassassin;
    protected $spamcheck_maxsize = '51200';
    protected $AlertSMS          = array();
    protected $AlertEmail        = array();

    public function __construct($cronjob)
    {
        $this->job       = $cronjob;
        $this->_PM_      = $_PM_ = &$GLOBALS['_PM_'];
        $this->temp_name = $_PM_['path']['temp'];
        $this->base_path = str_replace('$0', $_PM_['path']['userbase'], $this->base_path);
        $this->DB        = new DB_Base();
        // Initialize SA
        if (empty($this->_PM_['antijunk']['use_feature'])) {
            $this->_PM_['antijunk']['use_feature'] = false;
        } else {
            $this->spamassassin = $_PM_['antijunk']['cmd_check'];
        }
        // Load SMS driver
        if (!empty($_PM_['core']['sms_feature_active'])) {
            $usegwpath     = $_PM_['path']['msggw'] . '/' . $_PM_['core']['sms_use_gw'];
            $gwcredentials = $_PM_['path']['conf'] . '/msggw.' . $_PM_['core']['sms_use_gw'] . '.ini.php';
            require_once($usegwpath . '/phm_shortmessage.php');
            $this->GW = new phm_shortmessage($usegwpath, $gwcredentials);
            $this->smsactive = true;
        }
        // Preparations for external alerts
        // Reading in all available messages files to get the relevant date formats
        $d = opendir($_PM_['path']['message']);
        while ($file = readdir($d)) {
            if ('.' == $file
                    || '..' == $file
                    || substr(trim($file), -4, 4) != '.php') {
                continue;
            }
            $lang = preg_replace('/\.php$/i', '', trim($file));
            require($_PM_['path']['message'] . '/' . $file);
            // Reading in the templates for that language, if not there, try using the English one
            if (file_exists('backend/pop3fetcher.alertemail.' . $lang . '.tpl')) {
                $this->mailtpl[$lang] = file_get_contents('backend/pop3fetcher.alertemail.' . $lang . '.tpl');
            } elseif (file_exists('backend/pop3fetcher.alertemail.en.tpl')) {
                $this->mailtpl[$lang] = file_get_contents('backend/pop3fetcher.alertemail.en.tpl');
            }
            if ($this->smsactive && file_exists('backend/pop3fetcher.alertsms.' . $lang . '.tpl')) {
                $this->smstpl[$lang] = file_get_contents('backend/pop3fetcher.alertsms.' . $lang . '.tpl');
            } elseif ($this->smsactive && file_exists('backend/pop3fetcher.alertsms.en.tpl')) {
                $this->smstpl[$lang] = file_get_contents('backend/pop3fetcher.alertsms.en.tpl');
            }
        }
        if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
            $this->subject = $_PM_['core']['provider_name'];
        }
    }

    public function Run()
    {
        $_PM_ = $this->_PM_;

        $FS = new handler_email_driver(0); // We don't know the UID yet
        $info = $FS->get_folder_info($this->job['item']);
        if (false === $info || empty($info)) {
            vecho('Could not retrieve info for folder #'.$this->job['item']);
            $Cron = new DB_Controller_Cron();
            $Cron->removeJob($this->job);
            return false;
        }
        if ($info['has_items'] == 0) {
            vecho('Folder #'.$this->job['item'].' has no items');
            $Cron = new DB_Controller_Cron();
            $Cron->removeJob($this->job);
            return true;
        }
        // Now we know
        $uid = $info['uid'];
        $FS->changeUID($uid);
        $path = str_replace('$1', $uid, $this->base_path);

        $userChoices = $this->DB->get_usr_choices($uid);
        $this->_PM_ = $_PM_ = merge_PM($_PM_, $userChoices, true);
        if (isset($_PM_['core']['timezone'])) {
            $this->DB->settimezone($_PM_['core']['timezone']);
        }

        list($acntID, $pathIMAP) = explode(':', $info['folder_path']);
        $Acnt = new DB_Controller_Account();
        $accdata = $Acnt->getAccount(null, null, $acntID);

        $CONN = new Protocol_Client_IMAP($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        if (true !== $CONN->check_connected()) { // Connection failed
            vecho('Connecting to '.$accdata['popserver'].':'.$accdata['popport'].' failed');
            unset($CONN);
            return;
        }
        $status = $CONN->login($accdata['popuser'], $accdata['poppass'], $pathIMAP, false);
        if (!$status['login']) { // Login failed
            $CONN->close();
            unset($CONN);
            vecho('Could not login as '.$accdata['popuser'].'@'.$accdata['popserver'].':'.$accdata['popport']);
            return;
        }
        $accdata['leaveonserver'] = 1;
        $accdata['cachetype'] = 'struct';

		$dbcache = $dbsizes = $attlist = $maillist = array ();

		foreach ($CONN->get_list() as $num => $flags ) {
			$maillist[$num] = $flags['uidl'];
			$attlist[$num] = $flags;
		}

        $dbuidls = $FS->get_folder_uidllist($this->job['item'], false, false, array('ouidl', 'hsize', 'read', 'forwarded', 'answered', 'bounced', 'colour'));
        if (empty($dbuidls)) {
            $dbuidls = array();
        }
        foreach ($dbuidls as $k => $v) {
            $dbsizes[$v['ouidl']] = array('size' => $v['hsize'], 'idx' => $k, 'rd' => $v['read'], 'wg' => $v['forwarded'], 'aw' => $v['answered'], 'bn' => $v['bounced'], 'cl' => $v['colour']);
            $dbcache[$k] = $v['ouidl'];
        }
        list ($maillist, $deletelist) = $FS->uidlcache_match(false, $maillist, $dbcache);
        // First get new mails
		foreach ($maillist as $num => $uidl) {
            // These variables might get populated by the filtering mechanisms
            $deferred_archive = $deferred_dele = $deferred_copy = $deferred_move = $deferred_junk = $deferred_status = $deferred_color = false;

            $mailfile = uniqid(time() . '.', true);
            $mail_size = $attlist[$num]['size'];
            $final_location = ($accdata['cachetype'] != 'full' ? $this->temp_name : $path.'/email/'.$this->job['item']).'/'.$mailfile;
            if (!file_exists(dirname($final_location))) {
                basics::create_dirtree(dirname($final_location));
            }

            $success = $CONN->retrieve_to_file($num, $final_location);
            if (!$success) {
                vecho($CONN->get_last_error());
                continue;
            }

            $mh = fopen($final_location, 'r');
            list ($header, $struct) = Format_Parse_Email::parse($mh);
            unset($struct['last_line']);

            // Optional SPAM checker and filtering only happens in the INBOX
            if (strtoupper($pathIMAP) == 'INBOX') {
                // Mail might've been tagged by an external SPAM filter
                if ($header['spam_status'] && !empty($accdata['trustspamfilter'])) {
                    $deferred_junk = true;
                }
                // SPAM filtering
                if (!$deferred_junk && $this->_PM_['antijunk']['use_feature'] && $accdata['checkspam'] && $mail_size < $this->spamcheck_maxsize) {
                    $spamcomd = str_replace('$1', $final_location, $this->spamassassin);
                    $spamcomd = str_replace('$2', $this->temp_name.'/' . $mailfile . '.out', $spamcomd);
                    exec($spamcomd, $void, $deferred_junk);
                    // Make sure, SA could be called and produced a tagged mail
                    if (file_exists($this->temp_name.'/' . $mailfile . '.out')
                            && is_readable($this->temp_name.'/' . $mailfile . '.out')) {
                        // Close existing handle (file will change)
                        fclose($mh);
                        // Replace original mail by tagged one
                        rename($this->temp_name.'/' . $mailfile . '.out', $final_location);
                        // Again, could've changed after SPAM filtering
                        $mail_size = filesize($final_location);
                        // Reopen, read & parse again
                        $mh = fopen($final_location, 'r');
                        list ($header, $struct) = Format_Parse_Email::parse($mh);
                        unset($struct['last_line']);
                    }
                }
                // Aplly filters on the mail, but only, if it is not already tagged as SPAM
                if (!$deferred_junk) {
                    $mh = fopen($final_location, 'r');
                    list ($header, $struct) = Format_Parse_Email::parse($mh);

                    foreach ($FS->filters_getlist() as $filter) {
                        // Inactive?
                        if (!$filter['active']) {
                            continue;
                        }
                        // Get filter information, run method to check against the rules
                        $filter = $FS->filters_getfilter($filter['id']);
                        $hit    = Format_Parse_Email::apply_filter($header['complete'], $filter['match'], $filter['rules']);
                        // Rules did not hit
                        if (!$hit) {
                            continue;
                        }
                        //
                        // Obey the actions defined for the filter
                        //
                        if (!empty($filter['archive'])) { // Archive
                            $deferred_archive = true;
                            break;
                        } elseif (!empty($filter['delete'])) { // Delete
                            $deferred_dele = true;
                            break;
                        }
                        // Mark as junk
                        if ($this->_PM_['antijunk']['use_feature'] && !empty($filter['mark_junk'])) {
                            $deferred_junk = true;
                        }
                        // Switch read status
                        if (!empty($filter['mark_read'])) {
                            $deferred_status = ('read' == $filter['markread_status']) ? 1 : 0;
                        }
                        // Switch priority
                        if (!empty($filter['set_prio'])) {
                            $header['priority'] = $filter['new_prio'];
                        }
                        // Set a colour mark
                        if (!empty($filter['set_colour'])) {
                            $deferred_color = $filter['new_colour'];
                        }
                        // Move somewhere else
                        if (!empty($filter['move'])) {
                            // Prevent moving the mail to a non-existent folder
                            $is_there = $FS->get_folder_info($filter['move_to']);
                            if (is_array($is_there) && $is_there['foldername'] !== false) {
                                $deferred_move = $filter['move_to'];
                            }
                        }
                        // Copy somewhere else
                        if (!empty($filter['copy'])) {
                            // Prevent copying the mail to a non-existent folder - also duplicating it in the inbox is prevented
                            $is_there = $FS->get_folder_info($filter['copy_to']);
                            if (is_array($is_there) && $is_there['foldername'] !== false && $is_there['folder_path'] != $this->inbox_name) {
                                $deferred_copy = $filter['copy_to'];
                            }
                        }

                        // Non-documented feature: Allow running arbitrary scripts.
                        // This poses a serious securityx risk for the installation and thus is not made available
                        // to the users through the frontend.
                        // It's intended for admins only.
                        if (!empty($filter['run_script']) && !empty($filter['script_name'])
                            && file_exists($_PM_['path']['storage'].'/filter_scripts/'.basename($filter['script_name']).'.php')) {
                            require $_PM_['path']['storage'].'/filter_scripts/'.basename($filter['script_name']).'.php';
                        }

                        // Send alert SMS (but only, if within defined timeframe and not before the min. pause is reached)
                        if (!empty($filter['alert_sms']) && $this->smsactive) {
                            vecho('Might send sms for ' . $filter['name']);
                            // Explode the timeframe saved
                            preg_match('!^(\d\d)\:(\d\d)\-(\d\d)\:(\d\d)$!', $filter['sms_timeframe'], $smstf);
                            $smsstart = $smstf[1] . $smstf[2];
                            $smsend   = $smstf[3] . $smstf[4];
                            $mytime   = date('Hi');
                            // This allows nighttime timeframes, which span from one day to the next
                            if ($smsend < $smsstart) {
                                $intimeframe = ($mytime <= $smsstart || $smsend <= $mytime);
                            } else {
                                $intimeframe = ($smsstart <= $mytime && $mytime <= $smsend);
                            }
                            vecho('Evaluating SMS alert; timeframe: ' . print_r($intimeframe, true).', filter`s SMS#: '.$filter['sms_to'].', Times: '.(time() - $filter['sms_minpause']).' / '.$filter['sms_lastuse']);
                            if ($filter['sms_to'] && (time() - $filter['sms_minpause']) > $filter['sms_lastuse']
                                    && $intimeframe && !isset($this->AlertSMS[$filter['id']])) {
                                $this->AlertSMS[$filter['id']] = array(
                                        'filter' => $filter['name'],
                                        'to' => $header['to'],
                                        'from' => $header['from'],
                                        'subject' => $header['subject'],
                                        'uid' => $uid,
                                        'smsto' => $filter['sms_to']
                                        );
                                $FS->filters_set_lastuse($filter['id'], 'sms', time());
                            }
                        }
                        // Send alert Email (but only, if within defined timeframe and not before the min. pause is reached)
                        if (!empty($filter['alert_email'])) {
                            vecho('Might send email for ' . $filter['name']);
                            // Explode the timeframe saved
                            preg_match('!^(\d\d)\:(\d\d)\-(\d\d)\:(\d\d)$!', $filter['email_timeframe'], $emailtf);
                            $emailstart = $emailtf[1] . $emailtf[2];
                            $emailend   = $emailtf[3] . $emailtf[4];
                            $mytime     = date('Hi');
                            // This allows nighttime timeframes, which span from one day to the next
                            if ($emailend < $emailstart) {
                                $intimeframe = ($mytime <= $emailstart || $emailend <= $mytime);
                            } else {
                                $intimeframe = ($emailstart <= $mytime && $mytime <= $emailend);
                            }
                            vecho('Evaluating Mail alert; timeframe: ' . print_r($intimeframe, true).', filter`s Mailto: '.$filter['email_to'].', Times: '.(time() - $filter['email_minpause']).' / '.$filter['email_lastuse']);
                            if ($filter['email_to'] && (time() - $filter['email_minpause']) > $filter['email_lastuse']
                                    && $intimeframe && !isset($this->AlertEmail[$filter['id']])) {
                                $this->AlertEmail[$filter['id']] = array(
                                        'filter' => $filter['name'],
                                        'to' => $header['to'],
                                        'from' => $header['from'],
                                        'subject' => $header['subject'],
                                        'uid' => $uid,
                                        'mailto' => $filter['email_to']
                                );
                                $FS->filters_set_lastuse($filter['id'], 'email', time());
                            }
                        }
                    }
                    // Apply the rule for moving incoming mails to another folder as set in the account
                    if (!$deferred_move && !empty($accdata['inbox'])) {
                        $inbox = $FS->get_folder_info($accdata['inbox']);
                        if (is_array($inbox) && $inbox['folder_path']) {
                            $deferred_move = $accdata['inbox'];
                        }
                    }
                }
            }
            // End SPAM / filtering

            // For fullsearch we'll need the body as well
            if (!empty($_PM_['fulltextsearch']['enabled'])
                    && empty($deferred_dele) && empty($deferred_junk)) {
                $preferredType = !empty($_PM_['core']['email_preferred_part']) ? $_PM_['core']['email_preferred_part'] : 'html';
                list ($header['search_body_type'], $header['search_body']) = Format_Parse_Email::extractSearchBody($mh, $struct, $preferredType);
            }

            $header['status'] = ($attlist[$num]['seen']) ? 1 : 0;
            $header['unseen'] = ($header['status'] == 1) ? 0 : 1;
            $header['answered'] = $attlist[$num]['answered'] ? 1 : 0;
            $header['forwarded'] = (!empty($attlist[$num]['forwarded'])) ? 1 : 0;
            $header['bounced'] = (!empty($attlist[$num]['bounced'])) ? 1 : 0;
            // Prevents to have these heavy operations invoked for each mail. Once in the end suffices
            $header['delay_thread_cleanup'] = true;
            $header['delay_resnyc_folder'] = true;
            $header['struct'] = serialize($struct);
            $header['ouidl'] = $uidl;
            $header['profile'] = $acntID;
            $header['folder_id'] = $this->job['item'];
            $header['size'] = $mail_size;
            if (!isset($header['priority'])) {
                $header['priority'] = $header['importance'];
            }
            $header['date_received'] = date('Y-m-d H:i:s');
            if (!empty($header['date'])) {
                $header['date_sent'] = date('Y-m-d H:i:s', ($header['date']) ? $header['date'] : time());
            } else {
                $header['date_sent'] = date('Y-m-d H:i:s');
            }
            $header['filed'] = true;
            $header['uidl'] = $mailfile;
            if (!empty($header['x_phm_msgtype'])) {
                switch ($header['x_phm_msgtype']) {
                    case 'SMS':        $header['type'] = 'sms'; break;
                    case 'EMS':        $header['type'] = 'ems'; break;
                    case 'MMS':        $header['type'] = 'mms'; break;
                    case 'Fax':        $header['type'] = 'fax'; break;
                    case 'SystemMail': $header['type'] = 'sysmail'; break;
                }
            }
            if (isset($header['content_type']) && isset($header['mime'])
                    && !preg_match('!^text/(plain|html)!i', $header['content_type'])
                    && '1.0' == trim($header['mime'])) {
                if ('multipart/alternative' == $header['content_type']) {
                    $header['attachments'] = 0;
                    if (!empty($struct['body']['part_type'])) {
                        foreach ($struct['body']['part_type'] as $k => $v) {
                            $v = strtolower($v);
                            if (isset($struct['body']['dispo'][$k]) && $struct['body']['dispo'][$k] == 'attachment') {
                                $header['attachments'] = 1;
                                break;
                            }
                        }
                    }
                // A message delivery notification / status report
                } elseif ('multipart/report' == $header['content_type']) {
                    $header['type'] = 'receipt';
                    $header['attachments'] = 1;
                // Any of the known MIME types for calendar mails
                } elseif (in_array($header['content_type'], array('text/calendar', 'text/vcalendar', 'text/icalendar', 'text/x-vcal', 'text/x-vcalendar'))) {
                    $header['type'] = 'appointment';
                    $header['attachments'] = 1;
                } else {
                    $header['attachments'] = 1;
                }
            } else {
                $header['attachments'] = 0;
            }

            if ($accdata['cachetype'] == 'full') {
                $header['cached'] = 1;
                $newmail_id = $FS->file_mail($header);
            } else {
                $header['cached'] = 0;
                unlink($this->temp_name.'/' . $mailfile);
                $newmail_id = $FS->file_mail($header);
            }
            $flags = $attlist[$num];
            $FS->mail_set_status($newmail_id, ($flags['seen']) ? 1 : 0, $flags['answered'] ? 1 : 0, $flags['forwarded'] ? 1 : 0, $flags['bounced'] ? 1 : 0, true);

            // Deferred filtering operations
            if ($deferred_dele) {
                $FS->delete_mail($newmail_id, false, false, true);
            } elseif ($deferred_junk) {
                $profFolder = $accdata['junk'];
                if (0 != $profFolder) { // The user defined a Junk folder for that account -> try to use it
                    $folderInfo = $FS->get_folder_info($profFolder);
                    if (false === $folderInfo || empty($folderInfo)) {
                        $profFolder = false;
                    }
                } else { // Otherwise try using the system folder for that account
                    $profFolder = $FS->get_system_folder('junk', ($accdata['acctype'] == 'pop3') ? 0 : $this->job['item']);
                    $folderInfo = $FS->get_folder_info($profFolder);
                    if (false === $folderInfo || empty($folderInfo)) {
                        $profFolder = false;
                    }
                }
                $newfolder  = ($profFolder) ? $profFolder : $FS->get_folder_id_from_path('junk'); // Last fallback: Use the locally defined Junk folder
                $ret        = ($newfolder) ? $FS->copy_mail($newmail_id, $newfolder, true) : false;
                // Make sure, the antispam settings are sufficient
                if ($this->_PM_['antijunk']['use_feature'] && isset($this->_PM_['antijunk']['cmd_learnspam'])
                        && $this->_PM_['antijunk']['cmd_learnspam']
                        && strstr($this->_PM_['antijunk']['cmd_learnspam'], '$1')) {
                    $mailpath = $FS->mail_get_real_location($newmail_id);
                    $mailpath = $FS->userroot . '/' . $mailpath[1] . '/' . $mailpath[2]; // API changed with 4.0
                    shell_exec(str_replace('$1', $mailpath, $this->_PM_['antijunk']['cmd_learnspam'], $count));
                }
            } else {
                if ($deferred_status) {
                    $FS->mail_set_status($newmail_id, $deferred_status);
                }
                if (false !== $deferred_color) {
                    $FS->mail_set_colour($newmail_id, $deferred_color);
                }
                if ($deferred_copy) {
                    $FS->copy_mail($newmail_id, $deferred_copy, false);
                }
                if ($deferred_move) {
                    $FS->copy_mail($newmail_id, $deferred_move, true);
                }
                if ($deferred_archive) {
                    $FS->archive_mail($newmail_id, false);
                }
            }

        }
        $CONN->close();
        // Then delete from index, what's no longer on the server
        if (!empty($deletelist)) {
            foreach ($deletelist as $idx => $ouidl) {
                $FS->IDX->mail_delete($uid, $idx, false);
            }
        }
        // Now update status flags of IMAP mails if necessary
        $dbLabels = array_flip($FS->label2colour);
        foreach ($attlist as $num => $flags) {
            if (isset($maillist[$num])) {
                continue; // Got that already
            }
            if (in_array($flags['uidl'], $deletelist)) {
                continue; // Got deleted
            }
            list ($idx) = array_keys($dbcache, $flags['uidl']);
            if (!$idx) {
                continue; // Not found in array
            }

            // Important change: Only update flags in DB, which really changed. Saves large amount of time on huge folders
            if ($dbsizes[$flags['uidl']]['rd'] != ($flags['seen'])
                    || $dbsizes[$flags['uidl']]['aw'] != $flags['answered']
                    || $dbsizes[$flags['uidl']]['wg'] != $flags['forwarded']
                    || $dbsizes[$flags['uidl']]['bn'] != $flags['bounced']) {
                $FS->mail_set_status($idx, ($flags['seen']) ? 1 : 0, $flags['answered'] ? 1 : 0, $flags['forwarded'] ? 1 : 0, $flags['bounced'] ? 1 : 0, true);
            }
            // If server does not allow to set colour mark we won't get those back, thus destroying internally stored ones
            // But if so, we can consider non set marks as deleted by another instance
            if (!empty($status['customflags']) && $status['customflags'] == 1
                    && ((isset($dbLabels[$dbsizes[$flags['uidl']]['cl']])
                            && $dbLabels[$dbsizes[$flags['uidl']]['cl']] != '$label' . $flags['label'])
                            || (! isset($dbLabels[$dbsizes[$flags['uidl']]['cl']]) && $flags['label'] != 0))) {
                $FS->mail_set_colour($idx, 0 == $flags['label'] ? false : $FS->label2colour['$label' . $flags['label']], true);
            }
        }
        // Finally clean up threads and resync folder
        $FS->IDX->thread_cleanup();
        $FS->IDX->resync_folder($uid, $this->job['item']);
        // Check, if there's SMS or Mails to send out for this user
        if (!empty($this->AlertSMS)) {
            $this->handle_alerts($this->AlertSMS);
        }
        if (!empty($this->AlertEmail)) {
            $this->handle_alerts($this->AlertEmail);
        }
    }

    /**
     * Takes an array of events to alert
     * @param  array  List of events to process
     * @return void
     */
    protected function handle_alerts($alerts)
    {
        $_PM_ = $this->_PM_;
        $userdata = array();
        foreach ($alerts as $data) {
            if (!isset($userdata[$data['uid']])) {
                $userdata[$data['uid']] = $this->DB->get_usr_choices($data['uid']);
            }
            $lang = $userdata[$data['uid']]['core']['language'];
            if (isset($data['mailto']) && $data['mailto']) {
                $this->send_email(array
                        ('uid'      => $data['uid']
                        ,'lang'     => $lang
                        ,'mailfrom' => (isset($_PM_['core']['systememail']) && $_PM_['core']['systememail']) ? $_PM_['core']['systememail'] : $data['mailto']
                        ,'mailto'   => $data['mailto']
                        ,'provider' => $this->subject
                        ,'from'     => $data['from']
                        ,'to'       => $data['to']
                        ,'subject'  => $data['subject']
                        ,'filter'   => $data['filter']
                        ));
            }
            if ($this->smsactive && isset($data['smsto']) && $data['smsto']) {
                // Is the user allowed to send out SMS?
                $nochfrei = basics::SmsDepositAvailable($data['uid'], $this->_PM_['core']['sms_maxmonthly'], !empty($this->_PM_['core']['sms_allowover']));
                $active = (isset($this->userdata[$data['uid']]['core']['sms_active']) && $this->userdata[$data['uid']]['core']['sms_active']) ? 1 : 0;
                if (!$nochfrei || !$active) {
                    continue;
                }
                // Use the appropriate charset for sending
                $send_enc     = (isset($_PM_['core']['sms_send_encoding'])) ? $_PM_['core']['sms_send_encoding'] : null;
                $provider_dec = decode_utf8($this->subject, $send_enc, false);
                $from_dec     = decode_utf8($data['from'], $send_enc, false);
                $to_dec       = decode_utf8($data['to'], $send_enc, false);
                $subject_dec  = decode_utf8($data['subject'], $send_enc, false);
                $filter_dec   = decode_utf8($data['filter'], $send_enc, false);
                //
                $this->send_sms(array
                        ('uid'      => $data['uid']
                        ,'lang'     => $lang
                        ,'smsfrom'  => (isset($userdata[$data['uid']]['core']['sms_sender']) && $userdata[$data['uid']]['core']['sms_sender']) ? $userdata[$data['uid']]['core']['sms_sender'] : $data['smsto']
                        ,'smsto'    => $data['smsto']
                        ,'provider' => ($provider_dec) ? $provider_dec : $this->subject
                        ,'from'     => ($from_dec) ? $from_dec : $data['from']
                        ,'to'       => ($to_dec) ? $to_dec : $data['to']
                        ,'subject'  => ($subject_dec) ? $subject_dec : $data['subject']
                        ,'filter'   => ($filter_dec) ? $filter_dec : $data['filter']
                        ));
            }
        }
    }

    /**
     * Used to send an email based on the data passed and depending on the system's settings
     * At the moment emails are sent in UTF-8, so no decoding is necessary
     * @param  array  All payload necessary
     * - lang  string  Language selected by the user (full name, like de_Du) for using the right template file
     * - from  string From address (usually the system's email address)
     * - to  string  Receiver address as stated in the event record
     * - subject  string  Subject addon (usually the provider name of the installation)
     * - title  string  Title of the event
     * - location  string  Location of the event
     * - start  datetime  Start of the event
     * - end  datetime  End of the event
     * - desc string  Descriptive text entered with tthe event
     * - uid  int  ID of the user
     *
     * @return  bool  Whether sending was successfully done
     * @since 0.3.0
     */
    protected function send_email($data)
    {
        $_PM_         = $this->_PM_;
        $tpl          = $this->mailtpl[$data['lang']];
        $data['to']   = Format_Parse_Email::parse_email_address($data['to'], 0, true, true);
        $data['from'] = Format_Parse_Email::parse_email_address($data['from'], 0, true, true);
        foreach (array('from', 'to', 'subject', 'mailfrom', 'mailto', 'provider', 'filter') as $token) {
            $tpl = str_replace('$' . $token . '$', (isset($data[$token]) ? $data[$token] : ''), $tpl);
        }
        foreach (array('from', 'to', 'subject', 'provider', 'filter') as $token) {
            $tpl = str_replace('$html_' . $token . '$', (isset($data[$token]) ? $data[$token] : ''), $tpl);
        }
        if ($_PM_['core']['send_method'] == 'sendmail') {
            $sendmail = str_replace('$1', $data['mailfrom'], trim($_PM_['core']['sendmail']));
            $sm       = new Protocol_Client_Sendmail($sendmail);
            $moep     = $sm->get_last_error();
            if ($moep) {
                vecho($moep);
                $sm = false;
            }
        } elseif ($_PM_['core']['send_method'] == 'smtp') {
            $sm = new Protocol_Client_SMTP(
                    $_PM_['core']['fix_smtp_host'],
                    $_PM_['core']['fix_smtp_port'],
                    $_PM_['core']['fix_smtp_user'],
                    $_PM_['core']['fix_smtp_pass'],
                    $_PM_['core']['fix_smtp_allowselfsigned']);
            $server_open = $sm->open_server($data['mailfrom'], array($data['mailto']));
            if (!$server_open) {
                vecho(str_replace('<br />', '\n', str_replace(LF, '', $sm->get_last_error())) . '\n');
                $sm = false;
            }
        }
        if ($sm) {
            $sm->put_data_to_stream($tpl);
            // Make sure, there's a finalising CRLF.CRLF
            $sm->finish_transfer();
            if ($_PM_['core']['send_method'] == 'sendmail') {
                $success = true;
                if (!$sm->close()) {
                    vecho('No mail sent (' . $sm->get_last_error() . ')\n');
                    $success = false;
                }
            }
            if ($_PM_['core']['send_method'] == 'smtp') {
                if ($sm->check_success()) {
                    $success = true;
                } else {
                    vecho('No mail sent (' . $sm->get_last_error() . ')\n');
                    $success = false;
                }
                $sm->close();
            }
            if ($success) {
                vecho('Sent mail... ' . print_r($data, true));
            }
            return $success;
        }
        return false;
    }

    /**
     * Used to send an SMS based on the data passed
     * @param  array  All payload necessary
     * - lang  string  Language selected by the user (full name, like de_Du)
     * - from  string From address (usually the system's email address)
     * - to  string  Receiver address as stated in the event record
     * - title  string  Title of the event
     * - location  string  Location of the event
     * - start  datetime  Start of the event
     * - end  datetime  End of the event
     * - uid  int  ID of the user
     *
     * @return  bool  Whether sending was successfully done
     * @since 0.3.0
     */
    protected function send_sms($data)
    {
        $tpl = $this->smstpl[$data['lang']];
        foreach (array('from', 'to', 'subject', 'filter', 'provider') as $token) {
            $tpl = str_replace('$'.$token.'$', (isset($data[$token]) ? $data[$token] : ''), $tpl);
        }
        // Receiver and sender - numbers, text, type get "washed"
        $Washed = $this->GW->wash_input(array('from' => $data['smsfrom'], 'to'   => $data['smsto'], 'text' => $tpl));
        if (!is_array($Washed)) {
            vecho('Could not send SMS (' . $this->GW->get_last_error() . ')');
            return false;
        } else {
            // Und weg damit
            $return = $this->GW->send_sms($Washed);
            switch ($return[0]) {
                case 101:
                case 100:
                    $sms_sent = (isset($return[2])) ? $return[2] : 1;
                    $this->DB->decrease_sms_global_deposit($sms_sent);
                    $this->DB->set_user_accounting('sms', date('Ym'), $data['uid'], $sms_sent);
                    $this->DB->log_sms_sent(array(
                            'uid'      => $data['uid'],
                            'when'     => time(),
                            'receiver' => substr($Washed['to'], 0, -3) . 'xxx',
                            'size'     => strlen($Washed['text']),
                            'type'     => 0
                    ));
                    vecho('Sent SMS... ' . print_r($data, true));
                    return true;
                    // break;
                default:
                    vecho('Could not send SMS (' . $return[0] . ': ' . $return[1] . ')');
                    return false;
            }
        }
    }
}
