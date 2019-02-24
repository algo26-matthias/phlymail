<?php
/**
 * External alerting service, sending alerts for events to external email addresses or SMS recipients
 *
 * @package  phlyMail MessageCenter 4.0.0+
 * @subpackage  Calendar handler
 * @author  Matthias Sommerfeld, <mso@phlylabs.de>
 * @copyright 2005-2015, phlyLabs Berlin, http://phlylabs.de
 * @version 4.1.3 2015-04-15
 */

// Not covered by autoload mechanism, no class (yet)
require_once $_PM_['path']['lib'].'/message.encode.php';

class cron_calendar_externalalerts
{
    protected $cDB;
    private $userdata   = array();
    private $smsactive  = false;
    private $dateformat = array();
    private $subject    = 'phlyMail';
    private $interval   = 1; // The cron runs every minute

    public function __construct($cronjob)
    {
        global $DB;

        $this->_PM_ = $_PM_ = $GLOBALS['_PM_'];
        if (empty($DB)) {
            $DB  = new DB_Base();
        }
        $cDB = new handler_calendar_driver(0);
        if ($cDB && is_object($cDB) && $DB && is_object($DB)) {
            $this->DB = $DB;
            $this->cDB = $cDB;
        } else {
            vecho('No DB connection');
            $this->__destruct();
        }
        // Be robust, allow to adopt to different cycles
        $this->interval = $cronjob['interval'];
        // Load SMS driver
        if (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']) {
            $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
            $gwcredentials = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
            require_once($usegwpath.'/phm_shortmessage.php');
            $this->GW = new phm_shortmessage($usegwpath, $gwcredentials);
            $this->smsactive = true;
        }
        // Reading in all available messages files to get the relevant date formats
        $d = opendir($_PM_['path']['message']);
        while ($file = readdir($d)) {
            if ('.' == $file) {
                continue;
            }
            if ('..' == $file) {
                continue;
            }
            if (!preg_match('/\.php$/i', trim($file))) {
                continue;
            }
            $lang = preg_replace('/\.php$/i', '', trim($file));
            require($_PM_['path']['message'].'/'.$file);
            $langISO = $WP_msg['language'];
            $this->dateformat[$lang] = $WP_msg['dateformat'];
            // Reading in the templates for that language, if not there, try using the English one
            if (file_exists('backend/calendar.alertemail.'.$lang.'.tpl')) {
                $this->mailtpl[$lang] = 'backend/calendar.alertemail.'.$lang.'.tpl';
            } elseif (file_exists('backend/calendar.alertemail.en.tpl')) {
                $this->mailtpl[$lang] = 'backend/calendar.alertemail.en.tpl';
            } else {
                vecho('No suitable mail template for '.$lang.' found');
                return;
            }
            if ($this->smsactive) {
                if (file_exists('backend/calendar.alertsms.'.$lang.'.tpl')) {
                    $this->smstpl[$lang] = 'backend/calendar.alertsms.'.$lang.'.tpl';
                } elseif ($this->smsactive && file_exists('backend/calendar.alertsms.en.tpl')) {
                    $this->smstpl[$lang] = 'backend/calendar.alertsms.en.tpl';
                } elseif ($this->smsactive) {
                    vecho('No suitable SMS template for '.$lang.' found');
                    return;
                }
            }
            if (file_exists($_PM_['path']['handler'].'/calendar/lang.'.$lang.'.php')) {
                require($_PM_['path']['handler'].'/calendar/lang.'.$lang.'.php');
                $this->msg[$lang] = $WP_msg;
            } elseif (file_exists($_PM_['path']['handler'].'/calendar/lang.'.$langISO.'.php')) {
                require($_PM_['path']['handler'].'/calendar/lang.'.$langISO.'.php');
                $this->msg[$lang] = $WP_msg;
            } else {
                require($_PM_['path']['handler'].'/calendar/lang.en.php');
                $this->msg[$lang] = $WP_msg;
            }
        }

        if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
            $this->subject = $_PM_['core']['provider_name'];
        }
    }

    public function Run()
    {
        global $DB;
        $userList = $DB->get_usridx(null, 'active');
        if (empty($userList)) {
            return true;
        }
        // And go
        foreach (array_keys($userList) as $userID) {
            // (Re)set to default timezone and UTC offset (the latter as fallback only)
            $timezone = PHM_TIMEZONE;
            $utc_offset = PHM_UTCOFFSET;
            $userSettings = $this->DB->get_usr_choices($userID);
            if (!empty($userSettings['core']['timezone'])) {
                $timezone = $userSettings['core']['timezone'];
            }
            date_default_timezone_set($timezone);
            $utc_offset = utc_offset();
            $this->cDB->settimezone($timezone, $utc_offset);

            // Tell DB which user we are currently working on
            $this->cDB->changeUser($userID);

            // Are there any events to alert?
            $alerts = $this->cDB->get_alertable_events($this->interval, false, true);
            vecho('Handling '.count($alerts).' events');
            if (!empty($alerts)) {
                $this->handle_alerts($alerts, 'evt');
            }

            // Are there any tasks to alert?
            $alerts = $this->cDB->get_alertable_tasks($this->interval, false, true);
            vecho('Handling '.count($alerts).' tasks');
            if (!empty($alerts)) {
                $this->handle_alerts($alerts, 'tsk');
            }
        }
    }

    public function __destruct()
    {
        unset($this->cDB);
        unset($this->DB);
        vecho('Done');
    }

    /**
    * Takes an array of events to alert
    * @param  array  List of events to process
    * @return void
    */
    private function handle_alerts($alerts, $type)
    {
        foreach ($alerts as $data) {
            if (!isset($this->userdata[$data['uid']])) {
                $this->userdata[$data['uid']] = $this->DB->get_usr_choices($data['uid']);
            }
            $this->cDB->discard_event_alert($data['reminder_id']);
            $lang = $this->userdata[$data['uid']]['core']['language'];
            if ($data['mailto']) {
                foreach (explode(',', multi_address($data['mailto'], 100, 'sort')) as $mailto) {
                    $from = (isset($this->_PM_['core']['systememail']) && $this->_PM_['core']['systememail'])
                            ? $this->_PM_['core']['systememail']
                            : $data['mailto'];
                    $subject = isset($this->userdata[$data['uid']]['core']['provider_name']) && $this->userdata[$data['uid']]['core']['provider_name']
                            ? $this->userdata[$data['uid']]['core']['provider_name']
                            : $this->subject;
                    $head_reminder = $type == 'evt' ? $this->msg[$lang]['CalEvtReminder'] : $this->msg[$lang]['CalTskReminder'];
                    $subject .= ' '.$head_reminder;
                    $this->send_email(array
                            ('uid' => $data['uid']
                            ,'lang' => $lang
                            ,'from' => $from
                            ,'to' => trim($mailto)
                            ,'msgid' => rtrim(create_msgid($from, true))
                            ,'subject' => rtrim(encode_1522_line_q($subject, 'g', 'UTF-8'))
                            ,'subject_html' => $subject
                            ,'title' => $data['title']
                            ,'location' => $data['location']
                            ,'reminder' => $data['reminder']
                            ,'reminder_html' => nl2br($data['reminder'])
                            ,'desc' => $data['description']
                            ,'desc_html' => nl2br($data['description'])
                            ,'start' => date($this->dateformat[$lang], $data['starts'])
                            ,'end' => date($this->dateformat[$lang], $data['ends'])
                            ,'time' => time()
                            ));
                }
            }
            if ($this->smsactive && $data['smsto']) {
                foreach (explode(',', $data['smsto']) as $smsto) {
                    // Is the user allowed to send out SMS?
                    $nochfrei = basics::SmsDepositAvailable($data['uid'], $this->_PM_['core']['sms_maxmonthly'], !empty($this->_PM_['core']['sms_allowover']));
                    $active = (isset($this->userdata[$data['uid']]['core']['sms_active']) && $this->userdata[$data['uid']]['core']['sms_active']) ? 1 : 0;
                    if (!$nochfrei || !$active) {
                        continue 2; // Two levels here ...
                    }

                    // Use the appropriate charset for sending
                    $send_enc = (isset($this->_PM_['core']['sms_send_encoding']))
                            ? $this->_PM_['core']['sms_send_encoding']
                            : null;
                    $subject_dec = decode_utf8($this->subject, $send_enc, false);
                    $title_dec = decode_utf8($data['title'], $send_enc, false);
                    $location_dec = decode_utf8($data['location'], $send_enc, false);
                    $reminder_dec = decode_utf8($data['reminder'], $send_enc, false);
                    $this->send_sms(array
                            ('uid' => $data['uid']
                            ,'lang' => $lang
                            ,'from' => (isset($this->userdata[$data['uid']]['core']['sms_sender']) && $this->userdata[$data['uid']]['core']['sms_sender'])
                                    ? $this->userdata[$data['uid']]['core']['sms_sender']
                                    : trim($smsto)
                            ,'to' => trim($smsto)
                            ,'subject' => ($subject_dec) ? $subject_dec : $this->subject
                            ,'title' => ($title_dec) ? $title_dec :$data['title']
                            ,'location' => ($location_dec) ? $location_dec : $data['location']
                            ,'reminder' => ($reminder_dec) ? $reminder_dec : $data['reminder']
                            ,'start' => date($this->dateformat[$lang], $data['starts'])
                            ,'end' => date($this->dateformat[$lang], $data['ends'])
                            ,'head_reminder' => $head_reminder
                            ));
                }
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
    * @return  bool  whether sending was successfully done
    * @since 0.0.1
    */
    private function send_email($data)
    {
        $_PM_ = &$this->_PM_;
        $tpl = new phlyTemplate($this->mailtpl[$data['lang']]);

        $data['to'] = Format_Parse_Email::parse_email_address($data['to'], 0, true, true);
        $data['from'] = Format_Parse_Email::parse_email_address($data['from'], 0, true, true);

        if ($data['reminder']) {
            $tpl->fill_block('reminder', 'reminder', $data['reminder']);
        }
        if ($data['reminder_html']) {
            $tpl->fill_block('reminder_html', 'reminder_html', $data['reminder_html']);
        }

        foreach (array('from', 'to', 'subject', 'subject_html', 'title', 'location', 'start', 'end'
                ,'desc', 'desc_html', 'msgid', 'time') as $token) {
            $tpl->assign($token, (isset($data[$token]) ? $data[$token] : ''));
        }

        if ($_PM_['core']['send_method'] == 'sendmail') {
            $sendmail = str_replace('$1', $data['from'], trim($_PM_['core']['sendmail']));
            $sm = new Protocol_Client_Sendmail($sendmail);
            $moep = $sm->get_last_error();
            if ($moep) {
                vecho($moep);
                $sm = false;
            }
        } elseif ($_PM_['core']['send_method'] == 'smtp') {
            if (!empty($_PM_['core']['fix_smtp_host'])) {
                $smtp_host      = $_PM_['core']['fix_smtp_host'];
                $smtp_port      = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587;
                $smtp_user      = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
                $smtp_pass      = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
                $smtpsecurity   = isset($_PM_['core']['fix_smtp_security']) ? $_PM_['core']['fix_smtp_security'] : 'AUTO';
                $smtpselfsigned = isset($_PM_['core']['fix_smtp_allowselfsigned']) ? $_PM_['core']['fix_smtp_allowselfsigned'] : false;
            }
            $settings = $this->DB->get_usr_choices($data['uid']);
            $Acnt = new DB_Controller_Account();
            $email = $Acnt->getDefaultEmail($data['uid'], $settings);
            if (!empty($email)) {
                list($account_id, $alias_id) = $Acnt->getProfileFromEmail($data['uid'], $email);
            }
            if (!empty($account_id)) {
                $connect = $Acnt->getAccount($data['uid'], $account_id);
                // If we have SMTP connection data for this account, use it, else try to use the default
                // connection data
                if (!empty($connect['smtpserver'])) {
                    $smtp_host      = $connect['smtpserver'];
                    $smtp_port      = ($connect['smtpport']) ? $connect['smtpport'] : 587;
                    $smtp_user      = $connect['smtpuser'];
                    $smtp_pass      = $connect['smtppass'];
                    $smtpsecurity   = $connect['smtpsec'];
                    $smtpselfsigned = $connect['smtpallowselfsigned'];
                }
            }
            // Pull data from system's default SMTP account
            $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtpsecurity, $smtpselfsigned);
            $server_open = $sm->open_server($data['from'], array($data['to']));
            if (!$server_open) {
                vecho(str_replace('<br />', '\n', str_replace(LF, '', $sm->get_last_error())).'\n');
                $sm = false;
            }
        }
        if (!$sm) {
            return false;
        }
        $sm->put_data_to_stream($tpl->get_content());
        $sm->finish_transfer();
        if ($_PM_['core']['send_method'] == 'sendmail') {
            if (!$sm->close()) {
                vecho('No mail sent ('.$sm->get_last_error().')\n');
                $success = false;
            } else {
                $success = true;
            }
        }
        if ($_PM_['core']['send_method'] == 'smtp') {
            if ($sm->check_success()) {
                $success = true;
            } else {
                vecho('No mail sent ('.$sm->get_last_error().')\n');
                $success = false;
            }
            $sm->close();
        }
        if ($success) {
            vecho('Sent mail... '.print_r($data, true));
        }
        return $success;
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
    * @return  bool  whether sending was successfully done
    * @since 0.0.1
    */
    private function send_sms($data)
    {
        $tpl = new phlyTemplate($this->smstpl[$data['lang']]);
        if ($data['reminder']) {
            $tpl->fill_block('reminder', 'reminder', $data['reminder']);
        }
        foreach (array('from', 'to', 'subject', 'title', 'location', 'desc', 'start', 'end', 'head_reminder') as $token) {
            $tpl->assign($token, (isset($data[$token]) ? $data[$token] : ''));
        }
        // Receiver and sender - numbers, text, type get "washed"
        $Washed = $this->GW->wash_input(array
                ('from' => $data['from']
                ,'to' => $data['to']
                ,'text' => $tpl->get_content()
                ));
        if (!is_array($Washed)) {
            vecho('Could not send SMS ('.$this->GW->get_last_error().')');
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
                $this->DB->log_sms_sent(array
                        ('uid' => $data['uid']
                        ,'when' => time()
                        ,'receiver' => substr($Washed['to'], 0, -3) . 'xxx'
                        ,'size' => strlen($Washed['text'])
                        ,'type' => 0
                        ));
                vecho('Sent SMS... '.print_r($data, true));
                return true;
                // break;
            default:
                vecho('Could not send SMS ('.$return[1].')');
                return false;
            }
        }
    }
}
