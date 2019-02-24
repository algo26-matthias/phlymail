<?php
/**
 * compose.sms.php -> Sending SMS, EMS, MMS
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Core
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.3 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Sent SMS are autmatically saved in the "Sent objects" folder of the
// currently selected mail storgae handler. By the time of writing this,
// there's only Email available, but IMAP already in mind. The mechanism
// of how Core gets to know, which handler to use is not yet invented, so
// the following setting is to be considered as intermediate:
$save_folder = 'sent';
$save_handler = 'email';
$SMSLOGALL = (isset($_PM_core['sms_log_full']) && $_PM_core['sms_log_full']);
$passthru = give_passthrough(1);
// Is the user allowed to send out SMS?
$nochfrei = basics::SmsDepositAvailable($_SESSION['phM_uid'], $_PM_['core']['sms_maxmonthly'], !empty($_PM_['core']['sms_allowover']));

$active = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
if ($active) {
    $active = (isset($_PM_['core']['sms_active']) && $_PM_['core']['sms_active']);
}
$send_action = (isset($_REQUEST['send_action']) && $_REQUEST['send_action']) ? $_REQUEST['send_action'] : false;
$base_link = PHP_SELF.'?l=compose_sms&h=core&';

// If the global SMS country code is not set, we init it as false
if (!isset($_PM_['core']['sms_global_prefix'])) {
    $_PM_['core']['sms_global_prefix'] = false;
}
if (!$active || !$nochfrei) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', (!$nochfrei && $active) ? $WP_msg['SMSQuotaExceeded'] : $WP_msg['SMSNotActive']);
    foreach (array('sms_sendlist', 'sms_sentlist', 'sms_listsize', 'sms_text', 'sms_text_decoded', 'sms_sender') as $k) {
        if (isset($_SESSION[$k])) {
            unset($_SESSION[$k]);
        }
    }
    return;
}
if (!isset($_PM_['core']['sms_sender']) || !$_PM_['core']['sms_sender']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'send.sms.tpl');
    $tpl->fill_block('nosender', array
            ('msg_nosender' => $WP_msg['SMSDefineSender']
            ,'link_setup' => PHP_SELF.'?l=setup&amp;h=core&amp;'.$passthru.'&amp;mode=general'
            ,'msg_setup' => $WP_msg['setgen']
            ));
    return;
}

$Acnt = new DB_Controller_Account();

if ($send_action) {
    if (!isset($_SESSION['sms_sendlist'])) {
        // Automatically add country code, if needed
        if (!preg_match('!^(\+|00)!', $_PM_['core']['sms_sender']) && $_PM_['core']['sms_global_prefix']) {
            $_PM_['core']['sms_sender'] = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $_PM_['core']['sms_sender']);
        }
        $_SESSION['sms_sendlist'] = explode(',', $_REQUEST['to']);
        $_SESSION['sms_sendpause'] = isset($_REQUEST['sendpause']) ? intval($_REQUEST['sendpause']) : 0;
        $_SESSION['sms_sentlist'] = array();
        $_SESSION['sms_listsize'] = sizeof($_SESSION['sms_sendlist']);
        $_SESSION['sms_sender'] = $_PM_['core']['sms_sender'];
        $_SESSION['sms_text'] = phm_stripslashes($_REQUEST['body']);
        $_SESSION['sms_savefolder'] = isset($_REQUEST['savefolder']) ? $_REQUEST['savefolder'] : false;
        // Check for probably different answer mode
        $_SESSION['sms_answer'] = (isset($_REQUEST['answer'])) ? ('email' == $_REQUEST['answer'] ? 'email' : 'sms') : false;
        // Use the appropriate charset for sending
        $send_enc = (isset($_PM_['core']['sms_send_encoding'])) ? $_PM_['core']['sms_send_encoding'] : null;
        $text_dec = decode_utf8($_SESSION['sms_text'], $send_enc, false);
        $_SESSION['sms_text_decoded'] = ($text_dec) ? $text_dec : $_SESSION['sms_text'];
        // Make sure the user does not bypass length restrictions due to low prepaid limit
        if (strlen($_SESSION['sms_text_decoded']) > $_SESSION['sms_maxlen']) {
        	 $_SESSION['sms_text_decoded'] = substr($_SESSION['sms_text_decoded'], 0, $_SESSION['sms_maxlen']);
        }
    }
    // Taking one receiver from the list, trim it to avoid unnecessary spaces around it
    $to = trim(array_shift($_SESSION['sms_sendlist']));
    // Automatically add country code, if needed
    if (!preg_match('!^(\+|00)!', $to) && $_PM_['core']['sms_global_prefix']) {
        $to = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $to);
    }
    $curr_num = $_SESSION['sms_listsize'] - sizeof($_SESSION['sms_sendlist']);
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gwcredentials = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
    require_once($usegwpath.'/phm_shortmessage.php');
    $GW = new phm_shortmessage($usegwpath, $gwcredentials);
    // Receiver and sender - numbers, text, type get "washed"
    $Washed = $GW->wash_input(array('from' => $_SESSION['sms_sender'], 'to' => $to, 'text' => $_SESSION['sms_text_decoded']));
    if (!is_array($Washed)) {
        // Fehler beim Waschen des Inputs...
        array_unshift($_SESSION['sms_sendlist'], $to);
        sendJS(array('error' => $WP_msg['noSMSsent'].' ('.$GW->get_last_error().')', 'to' => implode (',', $_SESSION['sms_sendlist'])), true, false);
        unset($_SESSION['sms_sendlist']);
        exit;
    } else {
        // If alternative answering way given
        if ($_SESSION['sms_answer'] == 'email') {
            $Washed['email'] = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_);
            $Washed['answermail'] = true;
            $Washed['user'] = $_SESSION['phM_username'];
        }
        // Und weg damit
        $return = $GW->send_sms($Washed);
        if ($return[0] == 101 || $return[0] == 100) {
            $error = false;
            $_SESSION['sms_sentlist'][] = $to; // $Washed['to'];
            $sms_sent = (isset($return[2]) && $return[2]) ? $return[2] : 1;
            $DB->decrease_sms_global_deposit($sms_sent);
            $DB->set_user_accounting('sms', date('Ym'), $_SESSION['phM_uid'], $sms_sent);
            $DB->log_sms_sent(array
                    ('uid' => $_SESSION['phM_uid']
                    ,'when' => time()
                    ,'receiver' => ($SMSLOGALL) ? $Washed['to'] : substr($Washed['to'], 0, -3) . 'xxx'
                    ,'size' => strlen($Washed['text'])
                    ,'type' => 0
                    ,'text' => ($SMSLOGALL) ? $Washed['text'] : ''
                    ));

        } else {
            sendJS(array('error' => $WP_msg['noSMSsent'].' ('.$return[1].')'), 1, 1);
        }
        if (!empty($_SESSION['sms_sendlist'])) {
            $link = $base_link.'send_action=1&'.$passthru;
            $status = $to;
            if ($_SESSION['sms_listsize'] != 1) {
                $status = $curr_num.'/'.$_SESSION['sms_listsize'];
            }
            if ($_SESSION['sms_sendpause'] > 0) {
                sleep($_SESSION['sms_sendpause']);
            }
            sendJS(array('url' => $link, 'statusmessage' => $WP_msg['SMSSending'].' '.$status), 1, 1);
        } else {
            // Place a copy into sent objects of the current mail storgae handler
            if (!empty($_SESSION['sms_sentlist'])) {
                require_once($_PM_['path']['lib'].'/message.encode.php');
                $mytmpfile = $_PM_['path']['temp'].'/'.SecurePassword::generate(12, false, STRONGPASS_LOWERCASE | STRONGPASS_DECIMALS);

                $subject = str_replace(array(CRLF, LF), array(' ', ' '),  $_SESSION['sms_text']);
                if (strlen($subject) > 70) {
                    $subject = substr($subject, 0, strpos(wordwrap($subject, 70, LF, true), LF)).' ...';
                }
                $mailheader = create_messageheader
                        (array
                                ('from' => $_SESSION['sms_sender']
                                ,'to' => implode(', ', $_SESSION['sms_sentlist'])
                                ,'subject' => $subject
                                )
                        ,'X-phlyMail-Message-Type: SMS'.CRLF
                                .'Content-Transfer-Encoding: quoted-printable'.CRLF
                                .'Content-Type: text/plain; charset='.((isset($send_enc) && $send_enc) ? $send_enc : 'ISO-8859-1').CRLF
                                .'MIME-Version: 1.0'
                        );
                $tmp = fopen($mytmpfile, 'w');
                fputs($tmp, $mailheader.CRLF.phm_quoted_printable_encode($_SESSION['sms_text_decoded']));
                fclose($tmp);
                $save_class = 'handler_'.$save_handler.'_api';
                $SAVE = new $save_class($_PM_, $_SESSION['phM_uid']);
                $save_fid = false;
                if (isset($_SESSION['sms_savefolder']) && $_SESSION['sms_savefolder']) {
                    $save_fid = intval($_SESSION['sms_savefolder']);
                    $saveinfo = $SAVE->get_folder_info($save_fid);
                    if (!empty($saveinfo)) {
                        $save_folder = false;
                    } else {
                        $save_fid = false;
                    }
                }
                $state = $SAVE->parse_and_save_mail($mytmpfile, $save_folder, $save_fid, false, 'sms');
            }
            // Query current deposit, force correct update this way
            $gwsett = @parse_ini_file($usegwpath.'/settings.ini.php');
            if (is_array($gwsett) && isset($gwsett['has_synchro']) && $gwsett['has_synchro']) {
                $DB->set_sms_global_deposit($GW->synchro());
            }
            // Empty session data
            unset($_SESSION['sms_savefolder'], $_SESSION['sms_sendlist'], $_SESSION['sms_sentlist']
                    ,$_SESSION['sms_listsize'], $_SESSION['sms_sender'], $_SESSION['sms_text']
                    ,$_SESSION['sms_answer'], $_SESSION['sms_text_decoded'], $_SESSION['sms_sendlist']);
            // That's it, bye
            sendJS(array('done' => 1), 1, 1);
        }
    }
}

if (!$send_action) {
    foreach (array('sms_sendlist', 'sms_listsize', 'sms_text', 'sms_sender', 'sms_answer', 'sms_sentlist') as $k) {
        if (isset($_SESSION[$k])) {
            unset($_SESSION[$k]);
        }
    }
    if (isset($_REQUEST['to'])) {
        $WP_send['to'] = $_REQUEST['to'];
    }
    if (isset($_REQUEST['body'])) {
        $WP_send['body'] = $_REQUEST['body'];
    }

    if (isset($_REQUEST['reload']) && $_REQUEST['reload']) {
        foreach (array('body', 'to') as $k) {
            if (!isset($WP_save[$k])) {
                continue;
            }
            $WP_send[$k] = $WP_save[$k];
            unset($WP_save[$k]);
        }
    }
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gw_props = parse_ini_file($usegwpath.'/settings.ini.php');
    $max_len = 160;
    $max_sms = 1;
    if (isset($gw_props['max_len']) && $gw_props['max_len']) {
    	$max_len = $gw_props['max_len'];
    	if ($max_len > 160) {
            $max_sms = $max_len / 153;
        }
    }
    $max_len = ($nochfrei === true || $nochfrei > 1)
            ? (($nochfrei === true || $nochfrei > $max_sms) ? $max_len : ($nochfrei * 153) )
            : 160;
    $_SESSION['sms_maxlen'] = $max_len;
    $tpl = new phlyTemplate($_PM_['path']['templates'].'send.sms.tpl');
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
            ,'contacts_link' => PHP_SELF.'?l=apiselect&h=contacts&what=phone&json=1&'.$passthru
            ,'search_adb_url' => PHP_SELF.'?l=apiselect&h=contacts&what=cellular&'.$passthru
            ,'msg_contacts' => $WP_msg['APIContacts']
            ,'msg_send' => $WP_msg['send']
            ,'msg_to' => $WP_msg['to']
            ,'msg_from' => $WP_msg['from']
            ,'msg_copytobox' => $WP_msg['copytobox']
            ,'from' => $_PM_['core']['sms_sender']
            ,'to' => isset($WP_send['to']) ? htmlspecialchars($WP_send['to']) : ''
            ,'input_sendto' => isset($_PM_['core']['input_sendto']) ? $_PM_['core']['input_sendto'] : ''
            ,'msg_charsleft' => $WP_msg['SMSCharsLeft']
            ,'max_len' => $max_len
            ,'body' => isset($WP_send['body']) ? htmlspecialchars($WP_send['body']) : ''
            ,'msg_del' => $WP_msg['del']
            ,'msg_attach' => $WP_msg['attach']
            ,'oldaction' => isset($oldaction) ? $oldaction : ''
            ,'err_norcpt' => $WP_msg['noto']
            ,'err_notxt' => $WP_msg['SMSNoText']
            ,'err_toolong' => $WP_msg['SMSTooLong']
            ,'msg_sendmail' => $WP_msg['SMSSending']
            ,'msg_savecopy' => $WP_msg['SaveCopyIn']
            ,'msg_sendpause' => $WP_msg['SMSSendPauseSec']
            ,'msg_sendtogroup' => $WP_msg['SendToGroup']
            ,'path_contactsbarget' => PHP_SELF.'?l=compose_email&h=core&'.$passthru.'&get_contactsbar=1'
            ,'path_contactsbarsetopen' => PHP_SELF.'?l=worker&h=core&what=customsize&'.$passthru.'&token=core_contactsbar_open&value='
            ));
    if (!empty($gw_props['answer_via_email'])
            && !empty($gw_props['answer_via_sms'])
            && $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_)) {
        $t_n->fill_block('answerchoice', array('msg_answervia' => $WP_msg['SMSAnswerVia'], 'msg_sms' => 'SMS', 'msg_email' => 'EMail'));
    }
    if (!empty($_PM_['customsize']['core_contactsbar_open']) && $t_n->block_exists('contacts_are_open')) {
        $t_n->assign_block('contacts_are_open');
    }
    $save_class = 'handler_'.$save_handler.'_api';
    $API = new $save_class($_PM_, $_SESSION['phM_uid']);
    $defaultFolder = $API->get_system_folder($save_folder, 0);
    if (isset($_PM_['core']['sentfolder_sms']) && 0 != $_PM_['core']['sentfolder_sms']
            && is_array($API->get_folder_info($_PM_['core']['sentfolder_sms']))) {
        $defaultFolder = intval($_PM_['core']['sentfolder_sms']);
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
    // Allow to select smileys
    if ($t_n->block_exists('smileyselector')) {
        $t_ss = $t_n->get_block('smileyselector');
        foreach (Smiley::map() as $k => $v) {
            $t_ss->assign(array('icon' => $k, 'emoticon' => $v));
            $t_n->assign('smileyselector', $t_ss);
            $t_ss->clear();
        }
    }

    $tpl->assign('normal', $t_n);
    // Read SMS stats for user
    list ($curr_sum) = $DB->get_sms_stats(date('Ym'), $_SESSION['phM_uid']);
    $last = strtotime('last month');
    list ($last_sum) = $DB->get_sms_stats(date('Ym', $last), $_SESSION['phM_uid']);
    $curr_approx = ceil($curr_sum * date('t') / date('j'));
    // Output STATS
    if ($tpl->block_exists('stats')) {
        $t_s = $tpl->get_block('stats');
        $t_s->assign(array
                ('maxlimit' => isset($choices['sms_maxmonthly']) ? $choices['sms_maxmonthly'] : 0
                ,'curr_use' => ($curr_sum) ? number_format($curr_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
                ,'last_use' => ($last_sum) ? number_format($last_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
                ,'curr_approx' => number_format($curr_approx, 0, $WP_msg['dec'], $WP_msg['tho'])
                ,'leg_smsstat' => $WP_msg['SMSLegStatU']
                ,'msg_curruse' => $WP_msg['SMSCurrUse']
                ,'msg_lastuse' => $WP_msg['SMSLastUseU']
                ,'msg_month' => $WP_msg['Month']
                ,'msg_sms' => 'SMS'
                ,'msg_approx' => $WP_msg['Approx']
                ));
        if ($_PM_['core']['sms_freesms'] != 0) {
            $t_s->fill_block('iffree', array
                    ('free_used' => ($curr_sum <= $_PM_['core']['sms_freesms'])
                            ? number_format($curr_sum, 0, $WP_msg['dec'], $WP_msg['tho'])
                            : number_format($_PM_['core']['sms_freesms'], 0, $WP_msg['dec'], $WP_msg['tho'])
                    ,'free_given' => number_format($_PM_['core']['sms_freesms'], 0, $WP_msg['dec'], $WP_msg['tho'])
                    ,'msg_freesms' => $WP_msg['SMSFree']
                    ));
        }
        $tpl->assign('stats', $t_s);
    }
}