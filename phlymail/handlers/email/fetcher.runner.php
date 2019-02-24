<?php
/**
 * fetcher.runner.php - Actually fetching the emails, using JSON output
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler email
 * @author Matthias Sommerfeld
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.4.3 2015-12-15
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
/*
Step 1: Find out about profiles to check now, save in session
  1.1: Called by user: fetch all
  1.2: Called via worker: Only these, where checktime is reached
Step 2: Take profile from $_REQUEST, check inbox against UDIL cache
Step 3: Fetch a mail
*/
$session_start = time().'.'.getmygid();
$path = $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'];
$mail_path = 'email';
$inbox_name = 'inbox';
$spamassassin = !empty($_PM_['antijunk']['cmd_check']) ? $_PM_['antijunk']['cmd_check'] : false;
$spamcheck_maxsize = '51200';
$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 1;
$error = false;
if (!isset($_SESSION['phM_email_fetcher_nextcall'])) {
    $_SESSION['phM_email_fetcher_nextcall'] = time()+60;
}
$FS = new handler_email_driver($_SESSION['phM_uid']);
$Acnt = new DB_Controller_Account();
// Used to tell the frontend, that we deleted mails from the local cache
$JSON = array();

// Step 1
if ($step == 1) {
    //
    // Querying and retrieving mails for an IMAP folder; gets called when opening an IMAP folder
    //
    if (isset($_REQUEST['folder']) && $_REQUEST['folder'] != '') {
        $uid = $_SESSION['phM_uid'];
        // Session is no longer needed from this point on
        session_write_close();
        $f = $FS->get_folder_info(intval($_REQUEST['folder']));
        list ($pid, $folder) = explode(':', $f['folder_path'], 2);
        $accdata = $Acnt->getAccount(false, false, $pid);
        $CONN = new Protocol_Client_IMAP($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        // Connection failed
        if ($CONN->check_connected() !== true) {
            $JSON['done'] = 1;
            $JSON['error'] = 'Connection to '.$accdata['popserver'].':'.intval($accdata['popport']).' failed';
            sendJS($JSON, 1, 1);
            exit;
        }
        $status = $CONN->login($accdata['popuser'], $accdata['poppass'], $folder, false);
        // Login failed
        if (!$status['login']) {
            $error = $CONN->get_last_error();
            $CONN->close();
            $JSON['done'] = 1;
            $JSON['error'] = 'Login to '.$accdata['popserver'].' as '.$accdata['popuser'].' failed'.($error ? ' ('.$error.')' : '');
            sendJS($JSON, 1, 1);
            exit;
        }
        $accdata['leaveonserver'] = 1;
        $accdata['cachetype'] = 'struct';
        $dbcache = $dbsizes = $attlist = $maillist = array();
        $dbuidls = $FS->get_folder_uidllist(intval($_REQUEST['folder']), false, false, array('ouidl', 'hsize', 'read', 'forwarded', 'answered', 'bounced', 'colour'));
        foreach ($dbuidls as $k => $v) {
            $dbsizes[$v['ouidl']] = array
                    ('size' => $v['hsize'], 'idx' => $k, 'rd' => $v['read'], 'wg' => $v['forwarded']
                    ,'aw' => $v['answered'], 'bn' => $v['bounced'], 'cl' => $v['colour']
                    );
            $dbcache[$k] = $v['ouidl'];
        }
        foreach ($CONN->get_list() as $num => $flags) {
            $maillist[$num] = $flags['uidl'];
            $attlist[$num] = $flags;
        }
        $CONN->close();

        list ($maillist, $deletelist) = $FS->uidlcache_match(false, $maillist, $dbcache);
        if (!empty($deletelist)) {
            $JSON['deleted'] = count($deletelist);
            foreach ($deletelist as $idx => $ouidl) {
                $FS->IDX->mail_delete($uid, $idx, false);
            }
        }
        // Now update status flags of IMAP mails if necessary
        $upd = 0;
        // Rearrange some arrays for much quicker searching
        $dbLabels = array_flip($FS->label2colour);
        $deletelist = array_flip($deletelist);
        $dbcache = array_flip($dbcache);
        foreach ($attlist as $num => $flags) {
            $row_upd = false;
            if (isset($maillist[$num])) {
                continue; // Got that already
            }
            if (isset($deletelist[$flags['uidl']])) {
                continue; // Got deleted
            }
            if (!isset($dbsizes[$flags['uidl']])) {
                continue; // Not found in array
            }
            // Important change: Only update flags in DB, which really changed. Saves large amount of time on huge folders
            if ($dbsizes[$flags['uidl']]['rd'] != ($flags['seen'])
                    || $dbsizes[$flags['uidl']]['aw'] != $flags['answered']
                    || $dbsizes[$flags['uidl']]['wg'] != $flags['forwarded']
                    || $dbsizes[$flags['uidl']]['bn'] != $flags['bounced']) {
                $FS->mail_set_status($dbcache[$flags['uidl']], ($flags['seen']) ? 1 : 0
                        ,$flags['answered'] ? 1 : 0, $flags['forwarded'] ? 1 : 0
                        ,$flags['bounced'] ? 1 : 0, true);
                $row_upd = true;
            }
            // If server does not allow to set colour mark we won't get those back, thus destroying internally stored ones
            // But if so, we can consider non set marks as deleted by another instance
            if ($status['customflags'] == 1
                    && ((isset($dbLabels[$dbsizes[$flags['uidl']]['cl']]) && $dbLabels[$dbsizes[$flags['uidl']]['cl']] != '$label'.$flags['label'])
                            || (!isset($dbLabels[$dbsizes[$flags['uidl']]['cl']]) && $flags['label'] != 0))) {
                $FS->mail_set_colour($dbcache[$flags['uidl']], 0 == $flags['label'] ? false : $FS->label2colour['$label'.$flags['label']], true);
                $row_upd = true;
            }
            if ($row_upd) {
                $upd++;
            }
        }
        if ($upd) {
            $JSON['updated'] = $upd;
        }
        if (empty($maillist)) {
            $JSON['done'] = 1;
            sendJS($JSON, 1, 1);
        }
        $JSON['items'] = array();
        foreach ($maillist as $uidl) {
            $JSON['items'][] = $uidl;
        }
        sendJS($JSON, 1, 1);
    }
    //
    // Normal procedure of checking accounts for new mails
    //
    $profiles = $Acnt->getAccountIndex($_SESSION['phM_uid'], true);
    // Quotas: Check the space left and how many messages this user might store
    $quota_size_storage = $DB->quota_get($_SESSION['phM_uid'], 'email', 'size_storage');
    if (false !== $quota_size_storage) {
        $quota_spaceleft = $FS->quota_getmailsize(false);
        $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
    } else {
        $quota_spaceleft = false;
    }
    $quota_number_mails = $DB->quota_get($_SESSION['phM_uid'], 'email', 'number_mails');
    if (false !== $quota_number_mails) {
        $quota_mailsleft = $FS->quota_getmailnum(false);
        $quota_mailsleft = $quota_number_mails - $quota_mailsleft;
    } else {
        $quota_mailsleft = false;
    }
    // This would fail on all systems without provisioning
    try {
        $systemQuota = SystemProvisioning::get('storage');
        $systemUsage = SystemProvisioning::getUsage('total_rounded');
        if ($systemQuota - $systemUsage <= 0) {
            $quota_spaceleft = 0;
        }
    } catch (Exception $ex) {
        // void
    }


    // No more mails allowed to save
    $quota_reached = ((false !== $quota_mailsleft && $quota_mailsleft < 1) || (false !== $quota_spaceleft && $quota_spaceleft < 1));
    // End Quotas
    if (isset($_REQUEST['single'])) {
        $profiles = (isset($profiles[$_REQUEST['single']])) ? array($_REQUEST['single'] => $profiles[$_REQUEST['single']]) : array();
    }
    // No profiles to check
    if (empty($profiles)) {
        sendJS(array('done' => 1), 1, 1);
    }

    // Step 1.2 (explicit and implicit)
    foreach ($profiles as $pid => $accdet) {
        $accdata = $Acnt->getAccount(false, false, $pid);
        // Quota exceeded, no longer check POP3 accounts for new mails during this period
        if ($accdata['acctype'] == 'pop3' && $quota_reached) {
            unset($profiles[$pid]);
            continue;
        }
        // Only active accounts with recheck time != 0
        if ($accdata['checkevery'] < 1 && (!isset($_REQUEST['issuer']) || $_REQUEST['issuer'] != 'user')) {
            unset($profiles[$pid]);
            continue;
        }
        if ($accdata['checkevery'] > 0) {
            $nextcall = $accdata['logintime'] + ($accdata['checkevery']*60);
            if ((!$accdata['logintime'] || $nextcall > time()) && $_SESSION['phM_email_fetcher_nextcall'] > $nextcall) {
                $_SESSION['phM_email_fetcher_nextcall'] = $nextcall;
            }
            if (!isset($_REQUEST['issuer']) || $_REQUEST['issuer'] != 'user') {
                // Recheck time not reached yet
                if ($nextcall > time()) {
                    unset($profiles[$pid]);
                    continue;
                }
            }
        }
    }
    // No profiles to check
    if (empty($profiles)) {
        sendJS(array('done' => 1), 1, 1);
    }
    foreach ($profiles as $k => $v) {
        $items[] = $k;
    }
    sendJS(array('profiles' => $items), 1, 1);
}
// Step 2
if (2 == $step) {
    $pid = intval($_REQUEST['pid']);
    // Get the data
    $localkills = array();
    $accdata = $Acnt->getAccount(false, false, $pid);
    if ($accdata['acctype'] == 'pop3') {
        $CONN = new Protocol_Client_POP3($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        if ($CONN->check_connected() !== true) { // Connection failed
            $JSON['done'] = 1;
            $JSON['error'] = 'Connection to '.$accdata['popserver'].':'.intval($accdata['popport']).' failed';
            sendJS($JSON, 1, 1);
            exit;
        }
        $status = $CONN->login($accdata['popuser'], $accdata['poppass']);
        if (!$status['login']) { // Login failed
            $error = $CONN->get_last_error();
            $CONN->close();
            $JSON['done'] = 1;
            $JSON['error'] = 'Login to '.$accdata['popserver'].' as '.$accdata['popuser'].' failed'.($error ? ' ('.$error.')' : '');
            sendJS($JSON, 1, 1);
            exit;
        }
        // Appropriate settings for this account: Check for mails deleted locally which now get killed on the POP3 server, too.
        if ($accdata['leaveonserver'] && $accdata['localkillserver']) {
            $localkills = $FS->uidlcache_getdeleted($pid);
            if (!empty($localkills)) {
                $CONN->delete_by_uidl($localkills);
            }
        }
    } elseif ($accdata['acctype'] == 'imap') {
        $CONN = new Protocol_Client_IMAP($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        if ($CONN->check_connected() !== true) { // Connection failed
            $JSON['done'] = 1;
            $JSON['error'] = 'Connection to '.$accdata['popserver'].':'.intval($accdata['popport']).' failed';
            sendJS($JSON, 1, 1);
            exit;
        }

        $status = $CONN->login($accdata['popuser'], $accdata['poppass'], 'INBOX', false);
        if (!$status['login']) { // Login failed
            $error = $CONN->get_last_error();
            $CONN->close();
            $JSON['done'] = 1;
            $JSON['error'] = 'Login to '.$accdata['popserver'].' as '.$accdata['popuser'].' failed'.($error ? ' ('.$error.')' : '');
            sendJS($JSON, 1, 1);
            exit;
        }
        $accdata['leaveonserver'] = 1;
    }
    $Acnt->setLoginTime($_SESSION['phM_uid'], false, $pid);
    $nextcall = time() + ($accdata['checkevery']*60);
    if ($_SESSION['phM_email_fetcher_nextcall'] > $nextcall) {
        $_SESSION['phM_email_fetcher_nextcall'] = $nextcall;
    }
    $maillist = $CONN->get_list();

    if (empty($maillist)) {
        $JSON = array('done' => 1);
        $CONN->close();
        if ($error) {
            $JSON['error'] = $error;
        } elseif (isset($accdata) && $accdata['leaveonserver']) {
            $FS->uidlcache_remove($pid);
        }
        sendJS($JSON, 1, 1);
    }
    $attlist = array();
    foreach ($maillist as $num => $flags) {
        $attlist[$num] = $flags;
        $maillist[$num] = $flags['uidl'];
    }
    $CONN->close();
    if (isset($accdata) && ($accdata['leaveonserver'] || $accdata['acctype'] == 'imap')) {
        $fileto = $FS->get_folder_id_from_path($pid.':INBOX');
        if ($accdata['acctype'] == 'imap') {
            $dbcache = $FS->get_folder_uidllist($fileto);
            list ($maillist, $deletelist) = $FS->uidlcache_match(false, $maillist, $dbcache);
            if (!empty($deletelist)) {
                foreach ($deletelist as $idx => $ouidl) {
                    $FS->IDX->mail_delete($_SESSION['phM_uid'], $idx, false);
                }
            }
        } else {
            list ($maillist, $deletelist) = $FS->uidlcache_match($pid, $maillist);
            if (!empty($deletelist)) {
                foreach ($deletelist as $ouidl) {
                    $FS->delete_mail(false, $fileto, $ouidl);
                }
            }
        }
    }
    $_SESSION['phM_email_fetcher']['UIDLs'][$pid] = $maillist;
    sendJS(array('items' => array_keys($maillist)), 1, 1);
}
if (3 == $step) {
    require_once($_PM_['path']['lib'].'/functions.php');
    // These variables might get populated by the filtering mechanism
    $deferred_archive = $deferred_dele = $deferred_copy = $deferred_move = $deferred_junk = $deferred_status = $deferred_color = false;
    if (isset($_REQUEST['folder']) && $_REQUEST['folder'] != '') {
        $num = false;
        $uidl = (isset($_REQUEST['mail'])) ? $_SESSION['phM_email_fetcher']['UIDLs'][0][$_REQUEST['mail']] : phm_stripslashes($_REQUEST['uidl']);
        $f = $FS->get_folder_info(intval($_REQUEST['folder']));
        list ($pid, $folder) = explode(':', $f['folder_path'], 2);
    } else {
        $pid = intval($_REQUEST['pid']);
        $num = intval($_REQUEST['mail']);
        $uidl = false;
        $folder = 'INBOX';
    }
    $accdata = $Acnt->getAccount(false, false, $pid);
    if ($accdata['acctype'] == 'pop3') {
        // Prevent doublette downloads of mails probably already downloaded by another process
        if ($accdata['leaveonserver']) {
            $result = $FS->uidlcache_checkitem($pid, $_SESSION['phM_email_fetcher']['UIDLs'][$pid][$num]);
            if ($result) {
                sendJS(array('done' => 1), 1, 1);
            }
        }
        $CONN = new Protocol_Client_POP3($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        if ($CONN->check_connected() !== true) { // Connection failed
            sendJS(array('done' => 1, 'error' => 'Connection to '.$accdata['popserver'].' failed'), 1, 0);
            $error = true;
        }
        $status = $CONN->login($accdata['popuser'], $accdata['poppass']);
        if (!$status['login']) { // Login failed
            $CONN->close();
            sendJS(array('done' => 1, 'error' => 'Login to '.$accdata['popserver'].' as '.$accdata['popuser'].' failed'), 1, 0);
            $error = true;
        }
        $uidl = $CONN->uidl($num);
        $accdata['cachetype'] = 'full';
        // Quotas: Check the space left and how many messages this user might store
        $quota_size_storage = $DB->quota_get($_SESSION['phM_uid'], 'email', 'size_storage');
        if (false !== $quota_size_storage) {
            $quota_spaceleft = $FS->quota_getmailsize(false);
            $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
        } else {
            $quota_spaceleft = false;
        }
        $quota_number_mails = $DB->quota_get($_SESSION['phM_uid'], 'email', 'number_mails');
        if (false !== $quota_number_mails) {
            $quota_mailsleft = $FS->quota_getmailnum(false);
            $quota_mailsleft = $quota_number_mails - $quota_mailsleft;
        } else {
            $quota_mailsleft = false;
        }
        // This would fail on all systems without provisioning
        try {
            $systemQuota = SystemProvisioning::get('storage');
            $systemUsage = SystemProvisioning::getUsage('total_rounded');
            if ($systemQuota - $systemUsage <= 0) {
                $quota_spaceleft = 0;
            }
        } catch (Exception $ex) {
            // void
        }

        // No more mails allowed to save
        if ((false !== $quota_mailsleft && $quota_mailsleft < 1) || (false !== $quota_spaceleft && $quota_spaceleft < 1)) {
            sendJS(array('done' => 1), 1, 1);
        }
        // End Quotas
    } elseif ($accdata['acctype'] == 'imap') {
        $CONN = new Protocol_Client_IMAP($accdata['popserver'], $accdata['popport'], 0, $accdata['popsec'], $accdata['popallowselfsigned']);
        if ($CONN->check_connected() !== true) { // Connection failed
            sendJS(array('done' => 1, 'error' => 'Connection to '.$accdata['popserver'].' failed'), 1, 0);
            $error = true;
        }
        $status = $CONN->login($accdata['popuser'], $accdata['poppass'], $folder, false);
        if (!$status['login']) { // Login failed
            $CONN->close();
            sendJS(array('done' => 1, 'error' => 'Login to '.$accdata['popserver'].' as '.$accdata['popuser'].' failed'), 1, 0);
            $error = true;
        }
        if (!$num && $uidl) {
            $num = $CONN->msgno($uidl);
        } elseif ($num && !$uidl) {
            $uidl = $CONN->uidl($num);
        }
        $accdata['leaveonserver'] = 1;
        /*
        if (isset($accdata['cachetype'])) {
            $this->imap_cached_headers = ($accdata['cachetype'] == 'struct' || $accdata['cachetype'] == 'full');
            $this->imap_cached_mail = ($accdata['cachetype'] == 'full');
        } else {
            $this->imap_cached_headers = $this->imap_cached_mail = false;
        }*/
        $accdata['cachetype'] = 'struct';
    }
    // Prevent donwloading large mails blocks the frontend for too long
    session_write_close();

    if (!$num) {
        $error = true;
    }
    if (!$error) {
        // Init folder to store mail in
        if ($accdata['acctype'] == 'imap') {
            $fileto = $FS->get_folder_id_from_path($pid.':'.$folder);
        } else {
            $fileto = $inbox_name;
        }
        $mailfile = uniqid(time().'.', true);
        $tempmailfile = ($accdata['cachetype'] != 'full' ? $_PM_['path']['temp'] : $path.'/'.$mail_path.'/'.$fileto).'/'.$mailfile;

        if (!file_exists(dirname($tempmailfile))) {
            basics::create_dirtree(dirname($tempmailfile));
        }

        // If we shall download and delete the messages, the number of messages on the server is decreasing with
        // every call, so we simply fetch the first in the maildrop each time. That should do it, hopefully
        if (!$accdata['leaveonserver']) {
            $num = 1;
        }
        $mailprops = $CONN->get_list($num);
        $mail_size = $mailprops['size'];

        if ($folder == 'INBOX' && !empty($_PM_['antijunk']['use_feature']) && $accdata['checkspam'] && $mail_size < $spamcheck_maxsize) {
            $success = $CONN->retrieve_to_file($num, $tempmailfile.'.in');
            if (!$success) {
                sendJS(array('error' => phm_addcslashes($CONN->get_last_error())), 1, 0);
                $error = true;
            }
            if (!$error) {
                $spamcomd = str_replace('$1', $tempmailfile.'.in', $spamassassin);
                $spamcomd = str_replace('$2', $tempmailfile.'.out', $spamcomd);
                exec($spamcomd, $void, $deferred_junk);
                // Make sure, SA could be called and produced a tagged mail
                if (file_exists($tempmailfile.'.out')
                        && is_readable($tempmailfile.'.out')
                        && sprintf("%u", filesize($tempmailfile.'.out')) != 0) {
                    // Read the mail structure
                    $mh = fopen($tempmailfile.'.out', 'r');
                    list ($header, $struct) = Format_Parse_Email::parse($mh);
                    fclose($mh);
                    unset($struct['last_line']);
                    $header['struct'] = serialize($struct);
                    $mail_size = filesize($tempmailfile.'.out');
                    $header['folder_path'] = $fileto;
                    rename($tempmailfile.'.out', $tempmailfile);
                    unlink($tempmailfile.'.in');
                } else {
                    $deferred_junk = false;
                    // Read the mail structure
                    $mh = fopen($tempmailfile.'.in', 'r');
                    list ($header, $struct) = Format_Parse_Email::parse($mh);
                    fclose($mh);
                    unset($struct['last_line']);
                    $header['struct'] = serialize($struct);
                    $mail_size = filesize($tempmailfile.'.in');
                    $header['folder_path'] = $fileto;
                    rename($tempmailfile.'.in', $tempmailfile);
                }
            }
        } else {
            $success = $CONN->retrieve_to_file($num, $tempmailfile);
            if (!$success) {
                sendJS(array('error' => phm_addcslashes($CONN->get_last_error())), 1, 0);
                $error = true;
            }
            $mail_size = filesize($tempmailfile);
            $mh = fopen($tempmailfile, 'r');
            list ($header, $struct) = Format_Parse_Email::parse($mh);
            fclose($mh);
            unset($struct['last_line']);
            $header['struct'] = serialize($struct);
        }
        if ($folder == 'INBOX' && $header['spam_status'] && !empty($accdata['trustspamfilter'])) {
            $deferred_junk = true;
            $mailprops['recent'] = 0;
            $mailprops['seen'] = 1;
        }
        // Apply filters on the mail, but only, if it is not already tagged as SPAM
        // Additionally we don't apply filters on subfolders of IMAP servers. This would be evil!
        if (!$deferred_junk && $folder == 'INBOX') {
            foreach ($FS->filters_getlist('incoming') as $filter) {
                // Inactive?
                if (!$filter['active']) {
                    continue;
                }
                // Get filter information, run method to check against the rules
                $filter = $FS->filters_getfilter($filter['id']);
                $hit = Format_Parse_Email::apply_filter($header['complete'], $filter['match'], $filter['rules']);
                // Rules did not hit
                if (!$hit) {
                    continue;
                }
                //
                // Obey the actions defined for the filter
                //

                if (!empty($filter['archive'])) { // Archive
                    $deferred_archive = true;
                    $deferred_status = true;
                    break;
                } elseif (!empty($filter['delete'])) { // Delete the mail
                    $deferred_dele = true;
                    $deferred_status = true;
                    break;
                }
                // Mark as junk
                if (!empty($filter['mark_junk'])) {
                    $deferred_junk = true;
                }
                // Switch read status
                if (!empty($filter['mark_read'])) {
                    $deferred_status = ('read' == $filter['markread_status']);
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
                    if (is_array($is_there) && $is_there['foldername'] !== false && $is_there['folder_path'] != $inbox_name) {
                        $deferred_copy = $filter['copy_to'];
                    }
                }
                // Non-documented feature: Allow running arbitrary scripts.
                // This poses a serious security risk for the installation and thus is not made available
                // to the users through the frontend.
                // It's intended for admins only.
                if (!empty($filter['run_script']) && !empty($filter['script_name'])
                        && file_exists($_PM_['path']['storage'].'/filter_scripts/'.basename($filter['script_name']).'.php')) {
                    require $_PM_['path']['storage'].'/filter_scripts/'.basename($filter['script_name']).'.php';
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
        $header['ouidl'] = $uidl;
        $header['profile'] = $pid;
        if ($accdata['cachetype'] != 'full') {
            $header['folder_id'] = $fileto;
        } else {
            $header['folder_path'] = $fileto;
        }
        $header['size'] = $mail_size;
        if (!isset($header['priority'])) {
            $header['priority'] = $header['importance'];
        }
        $header['date_received'] = date('Y-m-d H:i:s');
        if (!empty($header['date'])) {
            $header['date_sent'] = date('Y-m-d H:i:s', ($header['date']) ? $header['date'] : time());
        } else {
            $header['date_sent'] = $header['date_received'];
        }
        $header['filed'] = ($accdata['cachetype'] == 'full');
        $header['uidl'] = $mailfile;
        $header['status'] = ($mailprops['seen']) ? 1 : 0;
        $header['answered'] = $mailprops['answered'] ? 1 : 0;
        $header['forwarded'] = isset($mailprops['forwarded']) && $mailprops['forwarded'] ? 1 : 0;
        $header['bounced'] = isset($mailprops['bounced']) && $mailprops['bounced'] ? 1 : 0;
        $header['unseen'] = $mailprops['recent'] || !$mailprops['seen'];

        // For fullsearch we'll need the body as well
        if (!empty($_PM_['fulltextsearch']['enabled'])
                && empty($deferred_dele) && empty($deferred_junk)) {
            $preferredType = !empty($_PM_['core']['email_preferred_part']) ? $_PM_['core']['email_preferred_part'] : 'html';
            $mh = fopen($tempmailfile, 'r');
            list ($header['search_body_type'], $header['search_body']) = Format_Parse_Email::extractSearchBody($mh, $struct, $preferredType);
            fclose($mh);
        }

        if (!empty($header['x_phm_msgtype'])) {
            switch ($header['x_phm_msgtype']) {
                case 'SMS': $header['type'] = 'sms'; break;
                case 'EMS': $header['type'] = 'ems'; break;
                case 'MMS': $header['type'] = 'mms'; break;
                case 'Fax': $header['type'] = 'fax'; break;
                case 'SystemMail': $header['type'] = 'sysmail'; break;
            }
        }
        if (isset($header['content_type']) && isset($header['mime'])
                && !preg_match('!^text/(plain|html)!i', $header['content_type'])
                && '1.0' == trim($header['mime'])) {
            if ('multipart/alternative' == $header['content_type']) {
                $header['attachments'] = 0;
                foreach ($struct['body']['part_type'] as $k => $v) {
                    $v = strtolower($v);
                    if (isset($struct['body']['dispo'][$k]) && $struct['body']['dispo'][$k] == 'attachment') {
                        $header['attachments'] = 1;
                        break;
                    }
                }
            } elseif ('multipart/report' == $header['content_type']) { // A message delivery notification / status report
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
        if ($error || $mail_size == 0) {
            // An error occured or the mail has 0 bytes, so adding to index is senseless.
            // Thus the temp file gets removed and the processing terminates here.
            unlink($tempmailfile);
            sendJS(array('done' => 1), 1, 1);
        } elseif ($accdata['cachetype'] == 'full') {
            $header['cached'] = '1';
            $newmail_id = $FS->file_mail($header);
            if ($accdata['leaveonserver']) {
                $FS->uidlcache_additem($pid, $uidl);
            } else {
                $stat = $CONN->delete($num);
            }
            $CONN->close();
        } else {
            $header['cached'] = '0';
            if ($deferred_dele) {
                $CONN->removeMessage($num);
                $CONN->close();
                unlink($tempmailfile);
                $deferred_dele = false;
                $deferred_status = false;
            } else {
                $CONN->close();
                unlink($tempmailfile);
                $newmail_id = $FS->file_mail($header);
                if ($folder != 'INBOX') {
                    $FS->mail_set_dsnsent($newmail_id, 1);
                }
            }
        }
    }
    $JSON = array('done' => 1);
    if ($deferred_archive) {
        $JSON['archive'] = $newmail_id;
    } elseif ($deferred_dele) {
        $JSON['delete'] = $newmail_id;
    } elseif ($deferred_junk) {
        $JSON['markjunk'] = $newmail_id;
    } else {
        if ($deferred_copy) {
            $JSON['copy_mail'] = $newmail_id;
            $JSON['copy_to'] = $deferred_copy;
        }
        if ($deferred_move) {
            $JSON['move_mail'] = $newmail_id;
            $JSON['move_to'] = $deferred_move;
        }
    }
    sendJS($JSON, 1, 0);
    if ($deferred_status) {
        $FS->mail_set_status($newmail_id, $deferred_status);
    }
    if (false !== $deferred_color) {
        $FS->mail_set_colour($newmail_id, $deferred_color);
    }
    exit;
}
