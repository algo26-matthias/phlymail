<?php
/**
 * mod.read.php -> Display a given mail
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Email
 * @copyright 2001-2014 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.8 2014-03-16 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// We need the mail id
if (!empty($item)) {
    $id = $item;
} elseif (!empty($_REQUEST['mail'])) {
    $id = $_REQUEST['mail'];
} else {
    die('No mail given');
}
$passthrough = give_passthrough(1);
if (isset($_REQUEST['setdsnsent'])) {
    $STOR = new handler_email_driver($_SESSION['phM_uid'], $_SESSION['workfolder']);
    $STOR->mail_set_dsnsent($_REQUEST['mail'], $_REQUEST['setdsnsent']);
    exit;
}

$save_as = (isset($_REQUEST['save_as'])) ? $_REQUEST['save_as'] : false;
$save_opt = (isset($_REQUEST['save_opt'])) ? $_REQUEST['save_opt'] : false;
$what = (isset($_REQUEST['what'])) ? $_REQUEST['what'] : false;
$preview = (isset($_REQUEST['preview']) && $_REQUEST['preview']);
$print = (isset($_REQUEST['print']) && $_REQUEST['print']);
$mobile = (defined('PHM_MOBILE'));
$viewsrc = (isset($_REQUEST['viewsrc']) && $_REQUEST['viewsrc']);
$sanitize = (isset($_REQUEST['sanitize']) && !$_REQUEST['sanitize']) ? false: true;
$attach = (isset($_REQUEST['attach'])) ? $_REQUEST['attach'] : '';
$is_inline = (isset($_REQUEST['inline'])) ? $_REQUEST['inline'] : false;
$linkbase = PHP_SELF.'?'.$passthrough.'&h=email';
$contactsbase = PHP_SELF.'?'.$passthrough.'&h=contacts';
$corebase = PHP_SELF.'?'.$passthrough.'&h=core&from_h=email';

// Wichtigkeiten
$WP_prio = array(1 => $WP_msg['high'], 3 => $WP_msg['normal'], 5 => $WP_msg['low']);
// Textauszeichnung / Headerausgabe / HTML-Mails
if (isset($_REQUEST['teletype'])) {
    $_SESSION['phM_tt'] = $_REQUEST['teletype'];
}
if (isset($_REQUEST['viewallheaders'])) {
    $_SESSION['phM_vheaders'] = $_REQUEST['viewallheaders'];
}
if (!isset($_SESSION['phM_tt'])) {
    $_SESSION['phM_tt'] = isset($_PM_['core']['teletype']) ? $_PM_['core']['teletype'] : false;
}
if (!isset($_SESSION['phM_vheaders'])) {
    $_SESSION['phM_vheaders'] = 0;
}
$teletype = $_SESSION['phM_tt'];
$viewheaders = $_SESSION['phM_vheaders'];
$uid = $_SESSION['phM_uid'];
session_write_close();

$STOR = new handler_email_driver($uid);
$mailinfo = $STOR->get_mail_info($id, true);
// not in print or source view, when saving
if (!$print && !$viewsrc && !$save_as && !$is_inline) {
    // Mark the mail as read (Not in preview mode!)
    if (!$preview) {
        $STOR->mail_set_status($id, 1);
    }
    // User decided to unblock external HTML elements
    if (!$sanitize) {
        // For a given email address / domain name
        if (isset($_REQUEST['unblockfilter']) && strlen($_REQUEST['unblockfilter'])) {
            $STOR->whitelist_addfilter(phm_stripslashes($_REQUEST['unblockfilter']), 1, null, null);
        } else { // Only for that mail
            $STOR->mail_set_htmlunblocked($id, true);
        }
    }
    // Check, whether the above setting already has been saved in the database
    if ($mailinfo['htmlunblocked']) {
        $sanitize = false;
    }
}
// View source or save as file
if ($viewsrc || 'raw' == $save_as) {
    if ($save_as) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlymail.eml');
    } else {
        header('Content-Type: text/plain');
    }
    if ($mailinfo['cached']) {
        $STOR->mail_open_stream($id, 'r');
        while ($chunk = $STOR->mail_read_stream(8192)) {
            echo $chunk;
        }
        $STOR->mail_close_stream();
        exit;
    } else {
        list($mbox, $length) = $STOR->get_imap_part($id);
        $read = 0;
        while (false !== ($line = $mbox->talk_ml())) {
            if ($read < $length) {
                echo $line;
            }
            $read += strlen($line);
        }
        exit;
    }
}
// Go get some mail
$struct = $STOR->get_mail_structure($id);
$mail_header = $STOR->get_mail_header($id);
$parent = 0;
if ($is_inline) {
    $is_inline = preg_replace('![^0-9\.]!', '', $is_inline);
    $parent = array_search($is_inline, $struct['body']['imap_part']); // Get index of starting part from original structure
    // Clean up structure to leave only the parts in, which belong to this inline email
    $copy = array();
    foreach ($struct['body']['imap_part'] as $k => $v) {
        if ($v == $is_inline) {
            continue; // This will hold the mailheader. We don't need it
        }
        if (substr($v, 0, strlen($is_inline)) == $is_inline) {
            $copy[$k] = $v;
        }
    }
    foreach ($struct['body'] as $k => $v) {
        foreach ($v as $k2 => $v2) {
            if (!isset($copy[$k2])) {
                unset($struct['body'][$k][$k2]);
            }
        }
    }
    unset($copy);
    // Read the mailheader directly from the file
    if ($mailinfo['cached']) {
        $STOR->mail_open_stream($id, 'r');
        $STOR->mail_seek_stream($struct['body']['offset'][$parent]);
        $mail_header = '';
        while (true) {
            $line = $STOR->mail_read_stream();
            if (!$line) {
                break;
            }
            $mail_header .= $line;
            if (trim($line) == '') {
                break;
            }
        }
        $mail_header = Format_Parse_Email::parse_mail_header($mail_header);
    } else { // Imap delivers the mail header more directly
        $mail_header = $STOR->get_mail_header($id, 'formatted', $is_inline);
    }
}
// Evade a weakness in the indexer, which forgets Cc for some reason
$mail_header['cc'] = '';
$mail_header['x_img_url'] = '';
foreach ($mail_header['complete'][1] as $k => $v) {
    if (strtolower($v) == 'cc') {
        $mail_header['cc'] = trim($mail_header['complete'][2][$k]);
    }
    if (strtolower($v) == 'x-image-url') {
        $mail_header['x_img_url'] = trim($mail_header['complete'][2][$k]);
    }
    if ($mail_header['x_img_url'] && $mail_header['cc']) {
        break;
    }
}
if ($print || $mobile) {
    $von = Format_Parse_Email::parse_email_address($mail_header['from']);
    $mail_header['from'] = htmlspecialchars($von[2]);
    $mail_header['to'] = multi_address($mail_header['to'], 0, 'print');
} elseif (isset($mailinfo['type']) && ('sms' == $mailinfo['type'] || 'fax' == $mailinfo['type'])) {
    // Avoid trying to parse mobile phone numbers as email addresses
} else {
    $mail_header['x_from'] = Format_Parse_Email::parse_email_address($mail_header['from'], 0, false, true);
    if (isset($mail_header['replyto'])) {
        $mail_header['replyto'] = Format_Parse_Email::parse_email_address($mail_header['replyto'], 0, false, true);
    }
    $mail_header['from'] = str_replace
            (array('$themes$', '$title$')
            ,array(PHM_SERVERNAME.rtrim(dirname(PHP_SELF), '/').'/'.$_PM_['path']['theme'], $WP_msg['AddToContacts'])
            ,multi_address($mail_header['from'], 5, 'read')
            );
    $mail_header['to'] = str_replace
            (array('$themes$', '$title$')
            ,array(PHM_SERVERNAME.rtrim(dirname(PHP_SELF), '/').'/'.$_PM_['path']['theme'], $WP_msg['AddToContacts'])
            ,multi_address($mail_header['to'], 5, 'read')
            );
    $mail_header['cc'] = str_replace
            (array('$themes$', '$title$')
            ,array(PHM_SERVERNAME.rtrim(dirname(PHP_SELF), '/').'/'.$_PM_['path']['theme'], $WP_msg['AddToContacts'])
            ,multi_address($mail_header['cc'], 5, 'read')
            );
}
$dsn_subject = $mail_header['subject'];
$dsn_date = $mail_header['date'];
$mail_header['date'] = @date($WP_msg['dateformat'], $mail_header['date']);
if (!$mail_header['date']) {
    $mail_header['date'] = '---';
}

if ($preview) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'read.preview.tpl');
} elseif ($print) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'read.print.tpl');
    define('_PM_OUTPUTTER_INCLUDED_', true);
} elseif ($mobile) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'read.general.tpl');
    define('_PM_OUTPUTTER_INCLUDED_', true);
    define('_PM_OUTPUTTER_HTML2TEXT_', true);
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'read.general.tpl');
    if (!empty($_PM_['core']['reply_samewin'])) {
        $tpl->assign_block('replysamewin');
    }
    if ('sys' == $teletype) {
        $tpl->fill_block('teletype_pro', array
                ('link_teletype' => $linkbase.'&l=read&mail='.$id.'&teletype=pro'
                ,'but_teletype' => $WP_msg['txt_prop']
                ));
    } else {
        $tpl->fill_block('teletype_sys', array
                ('link_teletype' => $linkbase.'&l=read&mail='.$id.'&teletype=sys'
                ,'but_teletype' => $WP_msg['txt_syst']
                ));
    }
    if ($viewheaders == 1 || 'complete' == $save_opt) {
        if (!$save_as) {
            $tpl->assign_block('fullheader');
        }
        $tpl->assign(array
                ('but_header' => $WP_msg['woheader']
                ,'link_header' => $linkbase.'&l=read&mail='.$id.'&viewallheaders=0'
                ));
    } else {
        if (!$save_as) {
            $tpl->assign_block('normalheader');
        }
        $tpl->assign(array
                ('but_header' => $WP_msg['wheader']
                ,'link_header' => $linkbase.'&l=read&mail='.$id.'&viewallheaders=1'
                ));
    }
}

list ($typeToDisplay, $partNum, $parts_attach) = Format_Parse_Email::determineVisibleBody(
        $struct,
        !empty($_PM_['core']['email_preferred_part']) ? $_PM_['core']['email_preferred_part'] : 'html'
        );
$add_sani = (!$sanitize) ? '&sanitize=0' : '';
$tpl->assign(array(
        'body_link' => phm_entities($linkbase.'&l=output&mail='.$id.$add_sani.'&part='.$partNum),
        'active_part' => ($sanitize) ? 'sanitizedhtml' : 'originalhtml'
        ));
if ($print || $mobile) {
    $num = $partNum;
    require_once $_PM_['path']['handler'].'/email/mod.output.php';
}
if ($typeToDisplay == 'html') {
    if ($sanitize && !$print && !$mobile) {
        $tpl->assign_block('preview_blocked');
    }
}

$MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
$Acnt = new DB_Controller_Account();
$return = Format_Parse_Email::get_visible_attachments(
        $struct['body'],
        'links',
        ($print) ? $_PM_['path']['frontend'].'/filetypes/16' : $_PM_['path']['frontend'].'/filetypes/32'
);

if (!empty($parts_attach) && !empty($struct['body']['part_attached']) && !empty($return) && isset($return['img'])) {
    if (!$print) {
        $t_ahdl = $tpl->get_block('availhdls');
        foreach ($_SESSION['phM_uniqe_handlers'] as $k => $v) {
            // Should only happen, when there's a deactivated handler with entries in the SendTo table
            if (!isset($v['i18n'])) {
                continue;
            }
            if ($k == 'core') {
                continue; // Makes no sense right now
            }
            $t_ahdl->assign(array
                    ('icon' => $k.'_sendto.gif'
                    ,'icon32' => $k.'_sendto.png'
                    ,'handler' => $k
                    ,'msg' => $WP_msg['SendTo'].' '.$v['i18n']
                    ));
            $tpl->assign('availhdls', $t_ahdl);
            $t_ahdl->clear();
        }
    }
    $mimecache = array();
    $tpl_a = $tpl->get_block('attachblock');
    $tpl_al = $tpl_a->get_block('attachline');
    $dbST = new DB_Controller_SendTo();
    foreach ($return['img'] as $key => $value) {
        $mimetype = $return['img_alt'][$key];
        if (!isset($mimecache[$mimetype])) {
            $mimecache[$mimetype] = array();
            foreach ($dbST->getMimeHandlers($mimetype) as $k => $v) {
                if ($v == 'email') {
                    continue;
                }
                $mimecache[$mimetype][$k] = "'".$v."'";
            }
        }
        if (isset($_PM_['core']['textnodownload']) && $_PM_['core']['textnodownload']
                && in_array($struct['body']['part_type'][$key], array('text/html', 'text/plain', 'message/delivery-status'))) {
            $tpl_al->assign('link_target', htmlspecialchars($linkbase.'&l=output&mail='.$id.'&part='.$key));
            $tpl_al->assign_block('inline');
        } elseif (preg_match('!^message/!', $struct['body']['part_type'][$key])
                && $struct['body']['part_type'][$key] != 'message/delivery-status') {
            $tpl_al->assign('link_target', htmlspecialchars($linkbase.'&inline='.$struct['body']['imap_part'][$key].'&l=read&mail='.$id));
            $tpl_al->assign_block('inline');
        } else {
            $tpl_al->assign('link_target', htmlspecialchars($linkbase.'&l=output&mail='.$id.'&save=1&part='.$key));
        }
        $tpl_al->assign(array
                ('att_icon' => $value, 'att_num' => $return['attid'][$key]
                ,'att_icon_alt' => $return['img_alt'][$key], 'att_name' => $return['name'][$key]
                ,'att_size' => $return['size'][$key], 'msg_att_type' => $WP_msg['filetype']
                ,'att_type' => ($return['filetype'][$key]) ? $return['filetype'][$key] : $WP_msg['nofiletype']
                ,'resid' => $id.'.'.$key
                ,'hdllist' => implode(',', $mimecache[$mimetype])
                ,'hdllist_js' => str_replace("'", '', implode(',', $mimecache[$mimetype]))
                ));
        $tpl_a->assign('attachline', $tpl_al);
        $tpl_al->clear();
        // Inline Attachments
        if (!$print && !empty($_PM_['core']['showattachmentinline'])) {
            if ($tpl->block_exists('showinline')) {
                if (preg_match('!^image/(gif|png|pjpeg|jpeg)$!', $struct['body']['part_type'][$key])) {
                    $tSI = $tpl->get_block('showinline');
                    $tSI->assign(array('id' => $key, 'type' => 'image', 'name' => $return['name'][$key]));
                    $tpl->assign('showinline', $tSI);
                    $tSI->clear();
                } elseif (in_array($struct['body']['part_type'][$key], array('text/plain', 'message/delivery-status'))) {
                    $tSI = $tpl->get_block('showinline');
                    $tSI->assign(array('id' => $key, 'type' => 'text', 'name' => $return['name'][$key]));
                    $tpl->assign('showinline', $tSI);
                    $tSI->clear();
                }
            }
        }
    }
    $tpl->assign('attachblock', $tpl_a);

    if ($tpl->block_exists('has_attach')) {
        $tpl->assign_block('has_attach');
    }
}
if ($preview) {
    $tpl->assign(array
            ('from' => phm_addcslashes($mail_header['from'], '\'\/[]{}')
            ,'to' => phm_addcslashes($mail_header['to'], '\'\/[]{}')
            ,'replyto' => phm_addcslashes($mail_header['replyto'], '\'\/[]{}')
            ,'subject' => phm_addcslashes($mail_header['subject'], '\'\/[]{}')
            ,'cc' => phm_addcslashes($mail_header['cc'], '\'\/[]{}')
            ,'date' => phm_addcslashes($mail_header['date'], '\'\/[]{}')
            ,'imgurl' => !empty($mail_header['x_img_url']) ? phm_addcslashes(PHP_SELF.'?deref='.derefer($mail_header['x_img_url']), '\'\/[]{}') : ''
            ,'x_from' => !empty($mail_header['x_from']) ? phm_addcslashes($mail_header['x_from'], '\'\/[]{}') : ''
            ));
} elseif ($viewheaders == 1) {
    $tpl_hl = $tpl->get_block('headerlines');
    foreach ($mail_header['complete'][1] as $key => $value) {
        $tpl_hl->assign(array
                ('hl_key' => $value
                ,'hl_val' => htmlspecialchars(phm_stripslashes($mail_header['complete'][2][$key]))
                ));
        $tpl->assign('headerlines', $tpl_hl);
        $tpl_hl->clear();
    }
} else {
    $tpl_hl = $tpl->get_block('headerlines');
    foreach (array('from' => 'from', 'to' => 'to', 'cc' => 'cc', 'date' => 'date'
            ,'prio' => 'importance', 'subject' => 'subject', 'comment' => 'comment') as $k => $v) {
        if (!empty($mail_header[$v])) {
            $tpl_hl->assign(array
                    ('hl_key' => isset($WP_msg[$k]) ? $WP_msg[$k] : ucfirst($k)
                    ,'hl_name' => $k
                    ));
            // Mail Importance setting
            if ($v == 'importance' && isset($mail_header[$v])) {
                if ($tpl->block_exists('priority_icon')) {
                    if (1 == $mail_header[$v]) {
                        $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['high'].' (1)');
                        $prioicon = 'prio_1';
                    } elseif (2 == $mail_header[$v]) {
                        $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['high'].' (2)');
                        $prioicon = 'prio_2';
                    } elseif (4 == $mail_header[$v]) {
                        $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['low'].' (4)');
                        $prioicon = 'prio_4';
                    } elseif (5 == $mail_header[$v]) {
                        $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['low'].' (5)');
                        $prioicon = 'prio_5';
                    }
                    if (!empty($prioicon)) {
                        // Prefer PNG over old fashioned gif
                        if (file_exists($_PM_['path']['theme'].'/icons/'.$prioicon.'.png')) {
                            $prioicon = $_PM_['path']['theme'].'/icons/'.$prioicon.'.png';
                        } else {
                            $prioicon = $_PM_['path']['theme'].'/icons/'.$prioicon.'.gif';
                        }
                        $tpl->fill_block('priority_icon', array('src' => $prioicon, 'alt' => $priotext));
                    }
                } elseif (1 == $mail_header[$v]) {
                    $tpl_hl->assign(array('hl_add' => 'prio_high', 'hl_val' => $WP_msg['high']));
                } elseif (5 == $mail_header[$v]) {
                    $tpl_hl->assign(array('hl_add' => 'prio_low', 'hl_val' => $WP_msg['low']));
                } elseif (3 == $mail_header[$v]) {
                    $tpl_hl->clear();
                    continue;
                }
            } else {
                $tpl_hl->assign('hl_val', $mail_header[$v]);
            }
            $tpl->assign('headerlines', $tpl_hl);
            $tpl_hl->clear();
            // Für das Bounce-Formular
            if ($v == 'from' || $v == 'subject') {
                $tpl->assign('hl_bounce_'.$v, $mail_header[$v]);
            }
        }
    }
}

if (!$preview && !$print) {
    if ($typeToDisplay == 'text' && $tpl->block_exists('but_txtvers')) {
        $tpl->fill_block('but_txtvers', array(
                'link' => $linkbase.'&l=output&mail='.$id.'&part='.$partNum,
                'msg_textversion' => $WP_msg['MailVerTxt']
                ));
    }
    if (($typeToDisplay == 'html' || $typeToDisplay == 'enriched') && $tpl->block_exists('but_htmlvers')) {
        $tpl->fill_block('but_htmlvers', array(
                'link' => $linkbase.'&l=output&mail='.$id.'&part='.$partNum,
                'msg_securehtml' => $WP_msg['MailVerSHTML'],
                'msg_originalhtml' => $WP_msg['MailVerOHTML']
                ));
    }
}
if (isset($_PM_['core']['mdn_behaviour']) && 'none' != $_PM_['core']['mdn_behaviour']
        && $mailinfo['dsn_sent'] == 0 && $mailinfo['profile'] != 0
        && isset($mail_header['send_mdn_to']) && $mail_header['send_mdn_to']) {
    if (isset($_PM_['core']['systememail'])) {
        $dsn_from = $_PM_['core']['systememail'];
    } else {
        $accdata = $Acnt->getAccount($uid, false, $mailinfo['profile']);
        $dsn_from = $accdata['address'];
    }
    $mdn_uri = $corebase.'&l=send_email&WP_do=send_dsn&mail='.$id
            .'&from='.urlencode($dsn_from)
            .'&to='.urlencode($mail_header['send_mdn_to']).'&osubj='.urlencode($dsn_subject)
            .'&omsgid='.urlencode($mail_header['message_id'])
            .'&odate='.urlencode($dsn_date)
            .'&prof='.urlencode($mailinfo['profile'])
            .'&dispo='.(('ask' == $_PM_['core']['mdn_behaviour']) ? 'manual' : 'automatic');
    if ($tpl->block_exists('mdn')) {
        $tpl->fill_block('mdn', array
                ('send_url' => $mdn_uri
                ,'status_url' => $linkbase.'&l=read&setdsnsent=1&mail='.$id
                ,'dispomode' => (('ask' == $_PM_['core']['mdn_behaviour']) ? 'manual' : 'automatic')
                ,'msg_confirm_mdn' => $WP_msg['SendMDNConfirm']
                ));
    } elseif ('ask' != $_PM_['core']['mdn_behaviour']) {
        $http = new Protocol_Client_HTTP();
        $http->send_request($mdn_uri);
    }
}
if ($tpl->block_exists('skim_next') || $tpl->block_exists('skim_prev')) {
    $skim = $STOR->mail_prevnext($id);
    if (!empty($skim['prev'])) {
        // Using the new "item" instead of old "mail"
        $tpl->fill_block('skim_prev', 'link_previous', htmlspecialchars($linkbase.'&l=read&i='.$skim['prev'], null, 'utf-8'));
    }
    if (!empty($skim['next'])) {
        // Using the new "item" instead of old "mail"
        $tpl->fill_block('skim_next', 'link_next', htmlspecialchars($linkbase.'&l=read&i='.$skim['next'], null, 'utf-8'));
    }
}
if ($tpl->block_exists('sel_colourmark')) {
    $t_scm = $tpl->get_block('sel_colourmark');
    foreach ($STOR->label2colour as $lbl => $col) {
        $t_scm->assign(array('colour' => $col));
        if ($mailinfo['colour'] == $col) {
            $t_scm->assign_block('sel');
        }
        $tpl->assign('sel_colourmark', $t_scm);
        $t_scm->clear();
    }
}
if ($tpl->block_exists('has_colour') && !empty($mailinfo['colour']) && $mailinfo['colour'] != 'NULL') {
    $tpl->fill_block('has_colour', 'colour', $mailinfo['colour']);
}

$tpl->assign(array
        ('msg_mail' => $WP_msg['mail']
        ,'but_answer' => $WP_msg['answer']
        ,'but_answerAll'=> $WP_msg['answerAll']
        ,'but_print' => $WP_msg['prnt']
        ,'but_forward' => $WP_msg['forward']
        ,'but_bounce' => $WP_msg['bounce']
        ,'but_save' => $WP_msg['savemail']
        ,'but_pure' => $WP_msg['source']
        ,'but_archive' => $WP_msg['EmailSendToArchive']
        ,'but_dele' => $WP_msg['del']
        ,'msg_view' => $WP_msg['EmailMenView']
        ,'msg_viewsrc' => $WP_msg['source']
        ,'msg_mail' => $WP_msg['mail']
        ,'msg_save' => $WP_msg['save']
        ,'msg_dele' => addcslashes($WP_msg['killone'], "'")
        ,'msg_printview' => (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'])
                ? $_PM_['core']['provider_name'].' '.$WP_msg['printview']
                : 'phlyMail '.$WP_msg['printview']
        ,'link_answer' => $corebase.'&l=compose_email&replymode=answer&mail='.$id
        ,'link_answerAll' => $corebase.'&l=compose_email&replymode=answerAll&mail='.$id
        ,'link_forward' => $corebase.'&l=compose_email&replymode=forward&mail='.$id
        ,'link_bounce' => $corebase.'&l=bounce_email&replymode=bounce&mail='.$id
        ,'link_editasnew' => $corebase.'&l=compose_email&replymode=template&mail='.$id
        ,'link_dele' => $linkbase.'&l=worker&what=mail_delete&mail[]='.$id
        ,'link_archive' => $linkbase.'&l=worker&what=mail_archive&mail[]='.$id
        ,'link_print' => $linkbase.'&l=read&print=1&mail='.$id
        ,'link_viewsrc' => $linkbase.'&l=read&viewsrc=1&mail='.$id
        ,'link_save' => $linkbase.'&l=read&save_as=raw&mail='.$id
        ,'link_sendtoadb' => $contactsbase.'&l=edit_contact'
        ,'showinlineurl' => $linkbase.'&l=output&mail='.$id.'&inline=1&save=1&part='
        ,'link_sendto' => PHP_SELF.'?'.$passthrough.'&l=sendto&source=email'
        ,'bounce_url' => PHP_SELF.'?'.$passthrough.'&l=send_email&h=core&WP_do=bounce&from_h=email&mail='.$id
        ,'bounce_del_url' => PHP_SELF.'?l=worker&h=email&'.$passthrough.'&what=mail_delete&alternate=1&mail[]='.$id
        ,'search_adb_url' => PHP_SELF.'?l=apiselect&h=contacts&what=email&'.$passthrough
        // HTML links (the above are JS links)
        ,'hlink_send' => phm_entities($corebase.'&l=compose_email&mail='.$id.'&replymode=', null, 'utf-8')
        ,'hlink_mailops' => phm_entities($linkbase.'&l=worker&mail='.$id.'&what=mail_', null, 'utf-8')
        ,'hlink_levelup' => phm_entities($linkbase.'&h=email&a=ilist&f='.$mailinfo['folder_id'], null, 'utf-8')
        ));
if ($is_inline && $tpl->block_exists('is_inline')) {
    $tpl->assign_block('is_inline');
}
