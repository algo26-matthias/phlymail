<?php
/**
 * worker.php - Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Email
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.7 2015-03-30
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

switch ($_REQUEST['what']) {
case 'rename_folder':
case 'folder_move':
case 'folder_delete':
case 'folder_create':
case 'folder_empty':
case 'folder_resync':
    header('Content-Type: text/javascript; charset=UTF-8');
    // Tell the setup module to return right after doing the operation without
    // generating output on its own
    $_PM_['tmp']['setup']['no_output'] = true;
    // Include the setup module and let it hanlde the operation
    require_once(__DIR__.'/setup.folders.php');
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'")'.LF;
    } else { // No errors - force reload of the folder list to reflect changes done
        echo 'flist_refresh("email");'.LF.'if (parent.CurrentHandler == "email") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
case 'mail_move':
case 'mail_copy':
case 'mail_delete':
case 'mail_archive':
case 'mail_mark':
case 'mail_unmark':
case 'mail_spam':
case 'mail_unspam':
case 'mail_colourmark':
    header('Content-Type: '.(!isset($_REQUEST['no_json']) ? 'application/json' : 'text/javascript').'; charset=UTF-8');
    // Tell the setup module to return right after doing the operation without
    // generating output on its own
    $_PM_['tmp']['setup']['no_output'] = true;
    // Include the setup module and let it hanlde the operation
    require_once(__DIR__.'/setup.mails.php');
    if ($error) { // React on errors
        echo (!isset($_REQUEST['no_json']))
                ? '{"error":"'.addcslashes($error, '"').'","done":"1"}'
                : 'alert("'.addcslashes($error, '"').'")'.LF;;
    } else { // No errors - force reload of the inbox to reflect changes done
        echo (!isset($_REQUEST['no_json']))
                ? '{"done":"1"}'
                : 'flist_refresh("email");'.LF.'if (parent.CurrentHandler == "email") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
case 'recheck':
    header('Content-Type: text/javascript; charset=UTF-8');
    $output = '';
    $reload = false;
    $unseen = false;
    $numUnread = 0;

    // Check for new mails. Output is only generated, if not done already
    if (!isset($STOR)) {
        $STOR = new handler_email_driver($_SESSION['phM_uid']);
    }
    $output .= 'frames.PHM_tl.flist_reset_unseen("email");'.LF;
    // Get current folder structure
    $folderstates = $STOR->folders_get_unread();
    foreach ($folderstates as $folder => $state) {
        if ($state['icon'] == ':inbox') {
            $numUnread += $state['unread'];
        }
        $output .= 'frames.PHM_tl.flist_set_unread_items("email",'.$folder.', '.$state['unread'].');'.LF;
        if ($state['unseen']) {
            $output .= 'frames.PHM_tl.flist_mark_unseen("email","'.$folder.'");'.LF;
            $unseen = true;
        }
        if (!isset($_SESSION['email_folders_new_items'][$folder])) {
            $reload = true;
            continue;
        }
        if ($_SESSION['email_folders_new_items'][$folder]['unread'] != $state['unread']
                || $_SESSION['email_folders_new_items'][$folder]['unseen'] != $state['unseen']
                || $_SESSION['email_folders_new_items'][$folder]['total'] != $state['total']) {
            $reload = true;
        }
    }
    $_SESSION['email_folders_new_items'] = $folderstates;

    // Make the number of unread items appear in the page's title
    if ($numUnread == 0) {
        $numUnread = '';
    } else {
        $numUnread = (string)$numUnread;
    }
    if ($unseen) {
        $numUnread = '"'.$numUnread.'*"';
    }
    $output .= 'pageTitleNotification(' . $numUnread .');'.LF;

    if ($unseen) {
        if (isset($_PM_['core']['newmail_playsound']) && $_PM_['core']['newmail_playsound']) {
            $soundpath = $_PM_['path']['frontend'].'/sounds/';
            $soundfile = (isset($_PM_['core']['newmail_soundfile']) && $_PM_['core']['newmail_soundfile']
                    && file_exists($soundpath.$_PM_['core']['newmail_soundfile'])
                    && is_readable($soundpath.$_PM_['core']['newmail_soundfile']))
                ? $_PM_['core']['newmail_soundfile']
                : '';
            $output .= 'newmail_playsound("'.$soundfile.'");'.LF;
        }
        if (isset($_PM_['core']['newmail_showalert']) && $_PM_['core']['newmail_showalert']) {
            $output .= 'newmail_showalert();'.LF;
        }
        $STOR->folders_set_seen();
        $reload = true;
    }
    if ($reload) {
        $output .= 'if (CurrentHandler == "email") frames.PHM_tr.refreshlist();'.LF;
    }

    $fetcher_called = 0;
    if (!isset($_SESSION['phM_email_loginfetch'])) {
        if (isset($_PM_['core']['pop3fetch_login']) && $_PM_['core']['pop3fetch_login']) {
            $_SESSION['phM_email_loginfetch'] = true;
            $output .= 'emailfetch_init();'.LF;
            $fetcher_called = 1;
        }
    }
    // Open fetcher window
    if (!$fetcher_called) {
        if (!isset($_SESSION['phM_email_fetcher_nextcall']) || $_SESSION['phM_email_fetcher_nextcall'] <= time()+20) {
            $output .= 'emailfetch_init();'.LF;
            $_SESSION['phM_email_fetcher_nextcall'] = time()+60;
            session_write_close();
        }
    }
    if ($output) {
        echo $output;
    }
    exit;
    break;
case 'folder_export':
    require_once __DIR__.'/folderexport.php';
    break;
}
