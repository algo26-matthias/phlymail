<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Email Handler
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.2 2015-02-18 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$FS = new handler_email_driver($_SESSION['phM_uid']);
$error = false;
$update_maillist = false;

$alternate = (!empty($_REQUEST['alternate']));
$myPrivs = $_SESSION['phM_privs'];
$myUid = $_SESSION['phM_uid'];
$Acnt = new DB_Controller_Account();

session_write_close();

$nl = (isset($_PM_['tmp']['setup']['no_output'])) ? LF : '<br />';
if (!empty($_REQUEST['what']) && isset($_REQUEST['mail'])) {
    $mails = $_REQUEST['mail'];
    if (!is_array($mails)) {
        $mails = array(0 => $mails);
    }

    // Allow to override delete action with archive action
    $what = $_REQUEST['what'];
    if (!empty($_PM_['archive']['override_delete']) && $what == 'mail_delete' && !$alternate) {
        $what = 'mail_archive';
    }

    switch ($what) {
    case 'mail_unmark':
    case 'mail_mark':
        $status = ($_REQUEST['what'] == 'mail_mark') ? 1 : 0;
        foreach ($mails as $mail) {
            $ret = $FS->mail_set_status($mail, $status, null, null, null);
            if (!$ret) {
                $error = $WP_msg['SetMailEnostatus'].': '.$FS->get_errors($nl);
            }
        }
        $update_maillist = true;
        break;
    case 'mail_copy':
        // Quotas: Check the space left and how many messages this user might store
        $quota_size_storage = $DB->quota_get($myUid, 'email', 'size_storage');
        if (false !== $quota_size_storage) {
            $quota_spaceleft = $FS->quota_getmailsize(false);
            $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
        } else {
            $quota_spaceleft = false;
        }
        $quota_number_mails = $DB->quota_get($myUid, 'email', 'number_mails');
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

        if (!$myPrivs['all'] && !$myPrivs['email_copy_email']) {
            $error .= $WP_msg['PrivNoAccess'];
            break;
        } elseif ((false !== $quota_mailsleft && $quota_mailsleft < 1) // No more mails allowed to save
                || (false !== $quota_spaceleft && $quota_spaceleft < 1)) {
            $error .= $WP_msg['QuotaExceeded'];
            break; // Break out of switch statement, since the quota has been reached already
        }
        // End Quotas

        // Fall through, if everything was okay
    case 'mail_move':
        if (!$myPrivs['all'] && !$myPrivs['email_move_email']) {
            $error .= $WP_msg['PrivNoAccess'];
            break;
        } elseif (!isset($_REQUEST['folder'])) {
            $error = $WP_msg['SetMailEnotarget'];
            break;
        } else {
            $folder = $_REQUEST['folder'];
        }
        foreach ($mails as $mail) {
            $ret = ($_REQUEST['what'] == 'mail_copy') ? $FS->copy_mail($mail, $folder) : $FS->move_mail($mail, $folder);
            if (!$ret) {
                $error = $WP_msg['SetMailEnomove'].': '.$FS->get_errors($nl);
            }
        }
        break;
    case 'mail_spam':
    case 'mail_unspam':
        if (!$myPrivs['all'] && !$myPrivs['email_move_email']) {
            $error .= $WP_msg['PrivNoAccess'];
            break;
        }
        $cmd = ($_REQUEST['what'] == 'mail_spam') ? 'cmd_learnspam' : 'cmd_learn_ham';
        $folder = ($_REQUEST['what'] == 'mail_spam') ? 'junk' : 'inbox';
        $profFolder = false;
        // Make sure, the antispam settings are sufficient
        if (!isset($_PM_['antijunk'][$cmd]) || !$_PM_['antijunk'][$cmd] || !strstr($_PM_['antijunk'][$cmd], '$1')) {
            break;
        }
        // We'll have a look at the first mail in the list to find out about the folder it currently is in.
        // In case of an IMAP folder we look deeper at that folder and the profile:
        // - Has that profile a user defined system folder?
        // - If not, does the system folder exist at all?
        // - Otherwise use the local copy
        $xmpl = $mails[0];
        $xmplInfo = $FS->get_mail_info($xmpl, true, false);
        $xmplFld = $FS->get_folder_info($xmplInfo['folder_id']);
        if (preg_match('!^(\d+)\:!', $xmplFld['folder_path'], $found)) { // This is an IMAP folder
            $accdata = $Acnt->getAccount($myUid, null, $found[1]);
            $profFolder = $accdata[$folder];
            if (0 != $profFolder) { // The user defined a Junk folder for that profile -> try to use it
                $folderInfo = $FS->get_folder_info($profFolder);
                if (false === $folderInfo || empty($folderInfo)) {
                    $profFolder = false;
                }
            } else { // Otherwise try using the system folder for that account, this will only work with IMAP
                // No, maybe as fallback later on -> $profFolder = $FS->folder_exists($folder); // Patch provided by Florian Däumling
                $profFolder = $FS->get_system_folder($folder, ($accdata['acctype'] == 'pop3') ? 0 : $found[1]);
                $folderInfo = $FS->get_folder_info($profFolder);
                if (false === $folderInfo || empty($folderInfo)) {
                    $profFolder = false;
                }
            }
        }
        $newfolder = ($profFolder) ? $profFolder : $FS->get_folder_id_from_path($folder);
        foreach ($mails as $mail) {
            $ret = ($newfolder) ? $FS->move_mail($mail, $newfolder) : false;
            if (!$ret) {
                $error = $WP_msg['SetMailEnomove'].': '.$FS->get_errors($nl);
            }
            $mailpath = $FS->mail_get_real_location($mail);
            $mailpath = $FS->userroot.'/'.$mailpath[1].'/'.$mailpath[2]; // API changed w/ 4.0
            shell_exec(str_replace('$1', $mailpath, $_PM_['antijunk'][$cmd], $count));
        }
        $update_maillist = true;
        break;
    case 'mail_delete':
        $error = '';
        if (!$myPrivs['all'] && !$myPrivs['email_delete_email']) {
            $error .= $WP_msg['PrivNoAccess'];
            break;
        }
        foreach ($mails as $mail) {
            $ret = $FS->delete_mail($mail, false, false, $alternate);
            if (!$ret) {
                $error .= $WP_msg['SetMailEnodelete'].': '.$FS->get_errors($nl);
            }
        }
        $update_maillist = true;
        break;
    case 'mail_archive':
        $error = '';
        if (!$myPrivs['all'] && !$myPrivs['email_move_email']) {
            $error .= $WP_msg['PrivNoAccess'];
            break;
        }
        foreach ($mails as $mail) {
            $ret = $FS->archive_mail($mail);
            if (!$ret) {
                $error .= $WP_msg['SetMailEnomove'].': '.$FS->get_errors($nl);
            }
        }
        $update_maillist = true;
        break;
    case 'mail_colourmark':
        $colour = (empty($_REQUEST['colour'])|| 'none' == $_REQUEST['colour']) ? false : $_REQUEST['colour'];
        foreach ($mails as $mail) {
            $ret = $FS->mail_set_colour($mail, $colour);
            if (!$ret) {
                $error .= $WP_msg['SetMailEnocolour'].': '.$FS->get_errors($nl);
            }
        }
        break;
    }
}