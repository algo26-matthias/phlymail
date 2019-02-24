<?php
/**
 * compose.fax.php -> Sending Faxes
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Core
 * @copyright 2010-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.9 2015-04-17 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Sent faxes are autmatically saved in the "Sent objects" folder of the
// currently selected mail storgae handler. By the time of writing this,
// there's only Email available, but IMAP already in mind. The mechanism
// of how Core gets to know, which handler to use is not yet invented, so
// the following setting is to be considered as intermediate:
$save_folder = 'sent';
$save_handler = 'email';
$FaxLOGALL = (isset($_PM_core['sms_log_full']) && $_PM_core['sms_log_full']);
$passthru = give_passthrough(1);
// Is the user allowed to send out Faxes?
$nochfrei = basics::SmsDepositAvailable($_SESSION['phM_uid'], $_PM_['core']['sms_maxmonthly'], !empty($_PM_['core']['sms_allowover']));
$active = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
if ($active) {
    $active = ((isset($_PM_['core']['fax_default_active']) && $_PM_['core']['fax_default_active'])
            || (isset($_PM_['core']['fax_active']) && $_PM_['core']['fax_active']));
}
$send_action = (isset($_REQUEST['send_action']) && $_REQUEST['send_action']) ? $_REQUEST['send_action'] : false;
$base_link = PHP_SELF.'?l=compose_fax&h=core&';

// If the global Fax country code is not set, we init it as false
if (!isset($_PM_['core']['sms_global_prefix'])) {
    $_PM_['core']['sms_global_prefix'] = false;
}
if (!$active || !$nochfrei) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', (!$nochfrei && $active) ? $WP_msg['FaxQuotaExceeded'] : $WP_msg['FaxNotActive']);
    foreach (array('fax_sendlist', 'fax_sentlist', 'fax_listsize', 'fax_text', 'fax_text_decoded', 'fax_sender') as $k) {
        if (isset($_SESSION[$k])) {
            unset($_SESSION[$k]);
        }
    }
    return;
}
if (!isset($_PM_['core']['fax_sender']) || !$_PM_['core']['fax_sender']
        || !isset($_PM_['core']['fax_sender_name']) || !$_PM_['core']['fax_sender_name']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'send.fax.tpl');
    $tpl->fill_block('nosender', array
            ('msg_nosender' => $WP_msg['FaxDefineSender']
            ,'link_setup' => PHP_SELF.'?l=setup&amp;h=core&amp;'.$passthru.'&amp;mode=general'
            ,'msg_setup' => $WP_msg['setgen']
            ));
    return;
}

$Acnt = new DB_Controller_Account();

if ($send_action) {
    if (!isset($_SESSION['fax_sendlist'])) {
        // Automatically add country code, if needed
        if (!preg_match('!^(\+|00)!', $_PM_['core']['fax_sender']) && $_PM_['core']['sms_global_prefix']) {
            $_PM_['core']['fax_sender'] = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $_PM_['core']['fax_sender']);
        }
        $_SESSION['fax_text'] = str_replace('$1', $_REQUEST['to'], $WP_msg['FaxMailSubject']);
        $_SESSION['fax_sendlist'] = explode(',', $_REQUEST['to']);
        $_SESSION['fax_sendpause'] = isset($_REQUEST['sendpause']) ? intval($_REQUEST['sendpause']) : 0;
        $_SESSION['fax_sentlist'] = array();
        $_SESSION['fax_listsize'] = sizeof($_SESSION['fax_sendlist']);
        $_SESSION['fax_sender'] = $_PM_['core']['fax_sender'];
        $_SESSION['fax_sender_name'] = $_PM_['core']['fax_sender_name'];
        $_SESSION['fax_status_email'] = $_PM_['core']['fax_status_email'];
        $_SESSION['fax_savefolder'] = isset($_REQUEST['savefolder']) ? $_REQUEST['savefolder'] : false;
        // Check for probably different answer mode
        $_SESSION['fax_answer'] = (isset($_REQUEST['answer'])) ? ('email' == $_REQUEST['answer'] ? 'email' : 'fax') : false;
        // Process deleted item
        foreach ($_REQUEST['attach'] as $k => $v) {
            // Item got marked deleted before sending
            if (isset($v['is_deleted']) && $v['is_deleted']) {
                // An actually uploaded file -> remove from filesystem
                if ($v['mode'] == 'user') {
                    @unlink($_PM_['path']['temp'].'/'.$v['filename']);
                }
                continue;
            }
            $_SESSION['fax_file'] = base64_encode(file_get_contents($_PM_['path']['temp'].'/'.$v['filename']));
            $_SESSION['fax_uploads'][] = $v['filename']; // Delete after successfull sending
        }
        // Do not send files larger than the gateway allows
        if (false !== $_SESSION['fax_maxlen'] && strlen($_SESSION['fax_file']) > $_SESSION['fax_maxlen']*1.25) {
            $error = size_format($_SESSION['fax_maxlen'], 1, 0, 0);
            $error = str_replace('$1', $error, $WP_msg['FaxPDFTooLarge']);
            sendJS(array('error' => $error, 'to' => implode (',', $_SESSION['fax_sendlist'])), true, false);
            unset($_SESSION['fax_sendlist']);
            exit;
        }
    }
    // Taking one receiver from the list, trim it to avoid unnecessary spaces around it
    $to = trim(array_shift($_SESSION['fax_sendlist']));
    // Automatically add country code, if needed
    if (!preg_match('!^(\+|00)!', $to) && $_PM_['core']['sms_global_prefix']) {
        $to = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $to);
    }
    if (preg_match('!^(\+49|0049|0)(900|700|1[1-79]|18[1-9]|32|118)!', $to)) {
        sendJS(array('error' => $WP_msg['FaxNotToThisRecipient'], 'to' => implode (',', $_SESSION['fax_sendlist'])), true, false);
        unset($_SESSION['fax_sendlist']);
        exit;
    }
    if (preg_match('!^(\+49|0049|0)(180)!', $to)
            && (!isset($_PM_['core']['fax_0180_active']) || !$_PM_['core']['fax_0180_active'])) {
        sendJS(array('error' => $WP_msg['FaxNotToThisRecipient'], 'to' => implode (',', $_SESSION['fax_sendlist'])), true, false);
        unset($_SESSION['fax_sendlist']);
        exit;
    }

    $curr_num = $_SESSION['fax_listsize'] - sizeof($_SESSION['fax_sendlist']);
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gwcredentials = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
    require_once($usegwpath.'/phm_shortmessage.php');
    $GW = new phm_shortmessage($usegwpath, $gwcredentials);
    // Receiver and sender - numbers, text, type get "washed"
    $Washed = $GW->wash_input(array
            ('from' => $_SESSION['fax_sender']
            ,'from_name' => $_SESSION['fax_sender_name']
            ,'status_to' => $_SESSION['fax_status_email']
            ,'to' => $to));
    if (!is_array($Washed)) {
        // Fehler beim Waschen des Inputs...
        array_unshift($_SESSION['fax_sendlist'], $to);
        sendJS(array('error' => $WP_msg['noFaxsent'].' ('.$GW->get_last_error().')', 'to' => implode (',', $_SESSION['fax_sendlist'])), true, false);
        unset($_SESSION['fax_sendlist']);
        exit;
    } else {
        // If alternative answering way given
        if ($_SESSION['fax_answer'] == 'email') {
            $Washed['email'] = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_);
            $Washed['answermail'] = true;
            $Washed['user'] = $_SESSION['phM_username'];
        }
        $Washed['file'] = $_SESSION['fax_file'];

        // Und weg damit
        $return = $GW->send_fax($Washed);
        if ($return[0] == 101 || $return[0] == 100) {
            $error = false;
            $_SESSION['fax_sentlist'][] = $to;
            $DB->set_user_accounting('fax', date('Ym'), $_SESSION['phM_uid'], 1);
            $DB->log_sms_sent(array
                    ('uid' => $_SESSION['phM_uid']
                    ,'when' => time()
                    ,'receiver' => ($FaxLOGALL) ? $Washed['to'] : substr($Washed['to'], 0, -3) . 'xxx'
                    ,'size' => strlen($Washed['file'])*.75
                    ,'type' => 1
                    ,'text' => ''
                    ));

        } else {
            sendJS(array('error' => $WP_msg['noFaxsent'].' ('.$return[1].')'), 1, 1);
        }
        if (!empty($_SESSION['fax_sendlist'])) {
            $link = $base_link.'send_action=1&'.$passthru;
            $status = $to;
            if ($_SESSION['fax_listsize'] != 1) {
                $status = $curr_num.'/'.$_SESSION['fax_listsize'];
            }
            if ($_SESSION['fax_sendpause'] > 0) {
                sleep($_SESSION['fax_sendpause']);
            }
            sendJS(array('url' => $link, 'statusmessage' => $WP_msg['FaxSending'].' '.$status), 1, 1);
        } else {
            if (!empty($_SESSION['fax_sentlist'])) {
                require_once($_PM_['path']['lib'].'/message.encode.php');
                $mytmpfile = $_PM_['path']['temp'].'/'.SecurePassword::generate(12, false, STRONGPASS_LOWERCASE | STRONGPASS_DECIMALS);

                $subject = str_replace(array(CRLF, LF), array(' ', ' '),  $_SESSION['fax_text']);
                if (strlen($subject) > 70) {
                    $subject = substr($subject, 0, strpos(wordwrap($subject, 70, LF, true), LF)).' ...';
                }
                $mailheader = create_messageheader
                        (array
                                ('from' => $_SESSION['fax_sender'].' ('.$_SESSION['fax_sender_name'].')'
                                ,'to' => implode(', ', $_SESSION['fax_sentlist'])
                                ,'subject' => $subject
                                )
                        ,'X-phlyMail-Message-Type: Fax'.CRLF
                                .'MIME-Version: 1.0'.CRLF
                                .'Content-Type: application/pdf; name="phlyMail-Fax.pdf"'.CRLF
                                .'Content-Transfer-Encoding: base64'.CRLF
                                .'Content-Disposition: attachment; filename="phlyMail-Fax.pdf"'.CRLF
                        );
                $tmp = fopen($mytmpfile, 'w');
                fputs($tmp, $mailheader.CRLF.chunk_split($_SESSION['fax_file']));
                fclose($tmp);
                $save_class = 'handler_'.$save_handler.'_api';
                $SAVE = new $save_class($_PM_, $_SESSION['phM_uid']);
                $save_fid = false;
                if (isset($_SESSION['fax_savefolder']) && $_SESSION['fax_savefolder']) {
                    $save_fid = intval($_SESSION['fax_savefolder']);
                    $saveinfo = $SAVE->get_folder_info($save_fid);
                    if (!empty($saveinfo)) {
                        $save_folder = false;
                    } else {
                        $save_fid = false;
                    }
                }
                $state = $SAVE->parse_and_save_mail($mytmpfile, $save_folder, $save_fid, false, 'fax');
            }
            // Query current deposit, force correct update this way
            $gwsett = @parse_ini_file($usegwpath.'/settings.ini.php');
            if (!empty($gwsett['has_synchro'])) {
                $DB->set_sms_global_deposit($GW->synchro());
            }
            if (!empty($_SESSION['fax_uploads'])) {
                foreach ($_SESSION['fax_uploads'] as $filename) {
                    @unlink($_PM_['path']['temp'].'/'.$filename);
                }
            }
            modfax_reset_session();

            // That's it, bye
            sendJS(array('done' => 1), 1, 1);
        }
    }
}

if (!$send_action) {
    // Remove anything probably left over from an earlier, cancelled sending attempt
    modfax_reset_session();

    if (isset($_REQUEST['to'])) {
        $WP_send['to'] = $_REQUEST['to'];
    }
    if (isset($_REQUEST['body'])) {
        $WP_send['body'] = $_REQUEST['body'];
    }

    if (isset($_REQUEST['reload']) && $_REQUEST['reload']) {
        foreach (array('body', 'to') as $k) {
            if (!isset($WP_save[$k])) continue;
            $WP_send[$k] = $WP_save[$k];
            unset($WP_save[$k]);
        }
    }
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gw_props = parse_ini_file($usegwpath.'/settings.ini.php');
    $max_len = false; // No limit
    if (isset($gw_props['fax_maxsize']) && $gw_props['fax_maxsize']) {
    	$max_len = $gw_props['fax_maxsize'];
    }
    $_SESSION['fax_maxlen'] = $max_len;
    $tpl = new phlyTemplate($_PM_['path']['templates'].'send.fax.tpl');
    $t_n = $tpl->get_block('normal');
    if (isset($WP_return)) {
        $t_n->fill_block('error', 'error', base64_decode($WP_return));
    }
    if (isset($WP_ext['send_attach'])) {
        eval($WP_ext['send_attach']);
        $t_n->assign('ext_send_attach', $WP_ext['send_attach']);
    }
    if (isset($parts_attach) && $parts_attach == 'true') {
        $tpl_a = $t_n->get_block('attachblock');
        $return = Format_Parse_Email::get_visible_attachments($mimebody, $attach, 'boxes');
        $tpl_al = $tpl_a->get_block('attachline');
        foreach ($return['img'] as $key => $value) {
            if ($WP_send['attach'][$key]) {
                $tpl_al->assign_block('attsel');
            }
            $tpl_al->assign(array
                    ('att_icon' => $value
                    ,'att_num' => $key
                    ,'att_icon_alt' => $return['img_alt'][$key]
                    ,'att_name' => $return['name'][$key]
                    ,'att_size' => $return['size'][$key]
                    ,'msg_att_type' => $WP_msg['filetype']
                    ,'att_type' => $return['filetype'][$key]
                    ));
            $tpl_a->assign('attachline', $tpl_al);
            $tpl_al->clear();
        }
        $tpl_a->assign(array
                ('msg_attachs' => $WP_msg['attachs']
                ,'msg_selection' => $WP_msg['selection']
                ,'msg_all' => $WP_msg['all']
                ,'msg_none' => $WP_msg['none']
                ));
        $t_n->assign('attachblock', $tpl_a);
    }
    $t_n->assign(array
            ('form_action' => htmlspecialchars($base_link.'send_action=1&'.$passthru)
            ,'att_link' => htmlspecialchars(PHP_SELF.'?l=compose_email_upload&h=core&'.$passthru)
            ,'contacts_link' => htmlspecialchars(PHP_SELF.'?l=apiselect&h=contacts&what=fax&'.$passthru)
            // ,'contacts_link' => PHP_SELF.'?l=apiselect&h=contacts&what=fax&json=1&'.$passthru # FIXME Use this URI once the contacts sidebar is in place
            ,'search_adb_url' => PHP_SELF.'?l=apiselect&h=contacts&what=fax&'.$passthru
            ,'path_attachbrowse' => PHP_SELF.'?h=core&l=selectfile&'.$passthru
            ,'receive_files_url' => PHP_SELF.'?h=core&l=compose_email&ajax=1&receive_file=&'.$passthru
            ,'msg_contacts' => $WP_msg['APIContacts']
            ,'msg_send' => $WP_msg['send']
            ,'msg_to' => $WP_msg['to']
            ,'msg_from' => $WP_msg['from']
            ,'msg_copytobox' => $WP_msg['copytobox']
            ,'from' => $_PM_['core']['fax_sender'].' &bull; '.$_PM_['core']['fax_sender_name']
            ,'to' => isset($WP_send['to']) ? htmlspecialchars($WP_send['to']) : ''
            ,'input_sendto' => isset($_PM_['core']['input_sendto']) ? $_PM_['core']['input_sendto'] : ''
            ,'msg_attachs' => $WP_msg['EmailAttachFile']
            ,'msg_upload' => $WP_msg['Upload']
            ,'msg_del' => $WP_msg['del']
            ,'msg_attach' => $WP_msg['attach']
            ,'oldaction' => isset($oldaction) ? $oldaction : ''
            ,'err_norcpt' => $WP_msg['noto']
            ,'err_notxt' => str_replace('$1', size_format($_SESSION['fax_maxlen'], 1, 0, 0), $WP_msg['FaxNoText'])
            ,'err_notothisnumber' => $WP_msg['FaxNotToThisRecipient']
            ,'msg_sendmail' => $WP_msg['FaxSending']
            ,'msg_savecopy' => $WP_msg['SaveCopyIn']
            ));
    if (isset($gw_props['answer_via_email']) && $gw_props['answer_via_email']
            && isset($gw_props['answer_via_fax']) && $gw_props['answer_via_fax']
            && $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_)) {
        $t_n->fill_block('answerchoice', array
                ('msg_answervia' => $WP_msg['FaxAnswerVia']
                ,'msg_fax' => 'Fax'
                ,'msg_email' => 'EMail'
                ));
    }
    $save_class = 'handler_'.$save_handler.'_api';
    $API = new $save_class($_PM_, $_SESSION['phM_uid']);
    $defaultFolder = $API->get_system_folder($save_folder, 0);
    if (isset($_PM_['core']['sentfolder_fax']) && 0 != $_PM_['core']['sentfolder_fax']
            && is_array($API->get_folder_info($_PM_['core']['sentfolder_fax']))) {
        $defaultFolder = intval($_PM_['core']['sentfolder_fax']);
    }
    $t_inb = $t_n->get_block('savefolder');
    foreach ($API->give_folderlist() as $id => $data) {
        $lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level'] * 2) : '';
        $t_inb->assign(array
                ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id.($id == $defaultFolder ? '" selected="selected' : '')
                ,'name' => $lvl_space . phm_entities($data['foldername'])
                ));
        $t_n->assign('savefolder', $t_inb);
        $t_inb->clear();
    }
    // Allow selection of arbitrary local attachments from other handlers
    foreach ($_SESSION['phM_uniqe_handlers'] as $type => $data) {
        if ($type == 'core') {
            continue; // Core got nothing useful right now
        }
        if ($type == 'email') {
            continue; // Well... maybe attaching a complete mail makes sense??
        }
        if (!file_exists($_PM_['path']['handler'].'/'.basename($type).'/api.php')) {
            continue;
        }
        require_once($_PM_['path']['handler'].'/'.basename($type).'/api.php');
        if (!in_array('sendto_fileinfo', get_class_methods('handler_'.$type.'_api'))) {
            continue;
        }
        $t_n->fill_block('attachreceiver', 'msg_name', $WP_msg['AttRcvOtherModule']);
        break;
    }
    $tpl->assign('normal', $t_n);
}

function modfax_reset_session()
{
    // Empty session data
    foreach (array('fax_savefolder', 'fax_sendlist', 'fax_sentlist', 'fax_listsize', 'fax_sender'
            ,'fax_sender_name', 'fax_answer', 'fax_file', 'fax_sendlist', 'fax_uploads') as $k) {
        unset($_SESSION[$k]);
    }
}
