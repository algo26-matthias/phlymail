<?php
/**
 * Exporting a given folder as mbox file
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Email
 * @copyright 2001-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.3 2013-07-07 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_export_emails']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
if (!isset($STOR)) {
    $STOR = new handler_email_driver($_SESSION['phM_uid']);
}
$folderID = (!empty($_REQUEST['fid'])) ? doubleval($_REQUEST['fid']) : false;

// The ID of a mail has been given, this gets appended to the mbox file
if (isset($_REQUEST['mail']) && false !== $_REQUEST['mail']) {
    $minfo = $STOR->get_mail_info(intval($_REQUEST['mail']), true, false);
    $minfo['from'] = Format_Parse_Email::parse_email_address($minfo['from'], 0, false, true);
    if ($minfo['cached']) {
        $mpath = $STOR->mail_get_real_location(intval($_REQUEST['mail']));
        $mpath = $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/email/'.$mpath[1].'/'.$mpath[2]; // API changed w/ 4.0
    } else {
        list($mbox, $length) = $STOR->get_imap_part(intval($_REQUEST['mail']));
    }
    $finfo = $STOR->get_folder_info($folderID);
    $MBX = new Format_Write_Mbox();
    $mboxname = $MBX->simplify_mbox_name($finfo['foldername'], 'mbx');
    if ($MBX->append_init($_PM_['path']['temp'].'/'.$_SESSION['mboxname_fid_'.$folderID], $minfo['from'], $minfo['date_received'])) {
        // Allow other processes to continue working. They do not need to wait for us finishing our task!
        session_write_close();
        // Go on appending data to the mbox file
        if ($minfo['cached']) {
            $mfh = fopen($mpath, 'r');
            while (!feof($mfh) && false !== ($line = fgets($mfh, 8192))) $MBX->append_line($line);
            fclose($mfh);
        } else {

            $read = 0;
            while (true) {
                $line = $mbox->talk_ml();
                if (!$line) break;
                $read += strlen($line);
                $MBX->append_line($line);
                if ($read >= $length) {
                    while (false !== $mbox->talk_ml()) { /* void */ }
                    $mbox->close();
                }
            }
        }
        $MBX->append_finalize();
        echo '{"got_mail":'.$_REQUEST['mail'].'}';
        exit;
    }
    echo '{"error":"Could not append to the mail file. Check file permissions"}';
    exit;

// Done, file gets downloaded now
} elseif (isset($_REQUEST['finish'])) {
    $finfo = $STOR->get_folder_info($folderID);
    $MBX = new Format_Write_Mbox();
    $mboxname = $MBX->simplify_mbox_name($finfo['foldername'], 'mbx');
    header('Content-Type: application/mbox; format=mboxo');
    header('Content-Disposition: attachment; filename="'.$mboxname.'"');
    header('Content-Transfer-Encoding: 7bit');
    header('Cache-Control: post-check=0, pre-check=0');
    $filename = $_PM_['path']['temp'].'/'.$_SESSION['mboxname_fid_'.$folderID];
    session_write_close();
    $mfh = fopen($filename, 'r');
    fpassthru($mfh);
    fclose($mfh);
    unlink($filename);
    exit;

// Init the action
} else {
    $passthru = give_passthrough(1);
    $_SESSION['mboxname_fid_'.$folderID] = SecurePassword::generate(12, false, STRONGPASS_DECIMALS | STRONGPASS_LOWERCASE);
    $tpl = new phlyTemplate($_PM_['path']['templates'].'folderexport.tpl');
    $tpl->assign(array
            ('exporturl' => PHP_SELF.'?'.$passthru.'&l=worker&what=folder_export&h=email&fid='.$folderID
            ,'downloadurl' => PHP_SELF.'?'.$passthru.'&l=worker&what=folder_export&h=email&fid='.$folderID.'&finish=1'
            ,'about_export' => $WP_msg['AboutExportEmail']
            ,'msg_close' => $WP_msg['CloseWindow']
            // Get the list of Mail IDs affected (all from this folder)
            ,'id_list' => implode(',', array_keys($STOR->get_folder_uidllist($folderID, 'hdate_recv', 'ASC')))
            ));
}
