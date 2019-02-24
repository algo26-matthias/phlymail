<?php
/**
 * compose.emailphp -> Send an email (+forward, answer, bounce)
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Core Handler, Email composition
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.5.2 2015-04-05 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Handle boilerplate management here
if (!empty($_REQUEST['get_boilerplate'])) {
    $EBP = new handler_email_boilerplates($_SESSION['phM_uid']);
    // No mess with me, senor!
    if (!isset($EBP->enabled) || !$EBP->enabled) {
        exit;
    }
    // Session no longer needed
    session_write_close();
    $item = $EBP->get_item(intval($_REQUEST['get_boilerplate']));
    header('Content-Type: application/json; charset=UTF-8');
    if (empty($item['body'])) {
        $item = array('type' => 'text', 'body' => '');
    }
    if (isset($_PM_['core']['send_html']) && $_PM_['core']['send_html'] && $item['type'] == 'text') {
        $item['body'] = preg_replace('!(\r\n|\r|\n)!', '<br />', $item['body']);
    }
    echo json_encode(array('boilerplate' => $item['body']));
    exit;
}

if (!empty($_REQUEST['get_contactsbar'])) {
    $API = new handler_contacts_api($_PM_, $_SESSION['phM_uid']);
    $groups = array();
    foreach ($API->give_folderlist() as $k => $v) {
        $groups[] = array('id' =>  $k, 'name' => $v['foldername'], 'level' => $v['level'], 'has_items' => $v['has_items']);
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($groups);
    exit;
}

// Retrieving data for mails to get bounced, answered and the like is bound
// to another handler, which issues the order to do so first.
require_once $_PM_['path']['lib'].'/message.encode.php';
$MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
$Acnt = new DB_Controller_Account();

$WP_return = false;
$may_send = (empty($_SESSION['phM_privs']['core_new_email']) && empty($_SESSION['phM_privs']['all'])) ? false : true;
$sendlinkadd = '';
$WP_send = array();
$WP_send['importance'] = 3;
$WP_send['receipt'] = (!empty($_PM_['core']['receipt_out'])) ? 1 : 0;
$body_prefix = '';
$aliases = array();
$from_handler = (isset($_REQUEST['from_h'])) ? $_REQUEST['from_h'] : false;
$sanitize = isset($_REQUEST['sanitize'])
        ? ($_REQUEST['sanitize'])
        : ((isset($_SESSION['phM_sanitize_html']) && !$_SESSION['phM_sanitize_html']) ? false : true);
$mail = (isset($_REQUEST['mail'])) ? $_REQUEST['mail'] : false;
if (isset($_REQUEST['to'])) {
    $WP_send['to'] = htmlspecialchars($_REQUEST['to']);
}
if (isset($_REQUEST['subj'])) {
    $WP_send['subject'] = htmlspecialchars($_REQUEST['subj']);
}

// Check, whether sending is possible at all
$useraccounts = $Acnt->getAccountIndex($_SESSION['phM_uid']);
if (!is_array($useraccounts) || empty($useraccounts)) {
    $may_send = false;
}
if (is_array($useraccounts)) {
    $activecnt = 0;
    foreach ($useraccounts as $k => $profilenm) {
        $profiledata = $Acnt->getAccount($_SESSION['phM_uid'], $k);
        ++$activecnt;
        if (!empty($profiledata['aliases'])) {
            $aliases[$k] = $profiledata['aliases'];
        }
    }
    if (!$activecnt) {
        $may_send = false;
    }
}
if (!$may_send) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    if (empty($_SESSION['phM_privs']['core_new_email']) && empty($_SESSION['phM_privs']['all'])) {
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
    } elseif (!$activecnt) {
        $tpl->assign('output', $WP_msg['CreateProfileFirst']);
    } else {
        $tpl->assign('output', $WP_msg['NoSrvrSelected']);
    }
    return;
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'send.email.tpl');
// Optional inclusion of boilerplates
$EBP = new handler_email_boilerplates($_SESSION['phM_uid']);
if (!empty($EBP->enabled) && $tpl->block_exists('boilerplates')) {
    $tpl->fill_block('boilerplates', 'msg_boilerplates', phm_addcslashes($WP_msg['BPlateMenu'], "'"));

    $t_bpl = $tpl->get_block('bplatelist');
    $t_bplf = $t_bpl->get_block('bplate_folder');
    $t_bplp = $t_bpl->get_block('bplate_plate');
    $allThePlates = $EBP->get_everything();

    if (!empty($allThePlates)) {
        foreach ($allThePlates as $k => $v) {
            if (substr($k, 0, 1) == 'f') {
                $t_bplf->assign(array(
                        'id' => $v['id'],
                        'name' => phm_entities($v['name']),
                        'icon' => empty($v['owner']) ? 'folder_global.png' : 'folder_def.png',
                        'level' => $v['level'],
                        'spacer' => $v['level'] * 16
                ));
                $t_bpl->assign('bplate_folder', $t_bplf);
                $t_bplf->clear();
            } else {
                $t_bplp->assign(array(
                        'id' => $v['id'],
                        'name' => phm_entities($v['name']),
                        'icon' => 'boilerplate' . ($v['type'] == 'html' ? '2' : '') . '_men.gif',
                        'level' => $v['level'],
                        'spacer' => $v['level'] * 16
                ));
                if (empty($_PM_['core']['send_html']) && $v['type'] == 'html') {
                    $t_bplp->assign_block('disable_html');
                }
                $t_bpl->assign('bplate_plate', $t_bplp);
                $t_bplp->clear();
            }
            $tpl->assign('bplatelist', $t_bpl);
            $t_bpl->clear();
        }
    }
}

// Allow selection of arbitrary local attachments from other handlers
foreach ($_SESSION['phM_uniqe_handlers'] as $type => $data) {
    if ($type == 'core') {
        continue; // Core got nothing useful right now
    }
    if ($type == 'email') {
        continue; // Well... maybe attaching a complete mail could make sense??
    }
    if (!file_exists($_PM_['path']['handler'].'/'.basename($type).'/api.php')) {
        continue;
    }
    require_once $_PM_['path']['handler'].'/'.basename($type).'/api.php';
    if (!in_array('sendto_fileinfo', get_class_methods('handler_'.$type.'_api'))) {
        continue;
    }
    $tpl->fill_block('attachreceiver', 'msg_name', $WP_msg['AttRcvOtherModule']);
    break;
}
//
// Init the compose process by receiving an attachment from another handler.
//
// Alternatively: If the AJAX flag is set in the request vars, this branch is
// called via the compose winodw itself after using the Core's generic file
// selector, which allows to select arbitrary files from any of the supporting handlers
if (isset($_REQUEST['receive_file'])) {
    if (!empty($from_handler)) {
        $src_class = 'handler_'.$from_handler.'_api';
        $SRC = new $src_class($_PM_, $_SESSION['phM_uid']);
        if (isset($_REQUEST['ajax'])) {
            $ajax = array();
        } else {
            $tpl_a = $tpl->get_block('origattachs');
        }
        foreach ($_REQUEST['item'] as $item) {
            $info = $SRC->sendto_fileinfo($item);
            $sml_icon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/16/', $info['content_type']);
            $filename = uniqid(time().getmypid(), true);
            $SRC->sendto_sendinit($item);
            while (($line = $SRC->sendto_sendline()) && false !== $line) {
                file_put_contents($_PM_['path']['temp'].'/'.$filename, $line, FILE_APPEND);
            }
            $SRC->sendto_finish();
            if (isset($_REQUEST['ajax'])) {
                $ajax[] = array
                        ('icon' => $_PM_['path']['frontend'].'/filetypes/16/'.$sml_icon
                        ,'name' => phm_addcslashes($info['filename'], '"')
                        ,'filename' => phm_addcslashes($filename, '"')
                        ,'mimetype' => $info['content_type']
                        );
            } else {
                $tpl_a->fill_block('hdlattline', array
                        ('small_icon' => $_PM_['path']['frontend'].'/filetypes/16/'.$sml_icon
                        ,'name' => phm_addcslashes($info['filename'], "'")
                        ,'filename' => phm_addcslashes($filename, "'")
                        ,'mimetype' => $info['content_type']
                        ));
                $tpl_a->assign('msg_attachs', $WP_msg['attachs']);
                $tpl->assign('origattachs', $tpl_a);
                $tpl_a->clear();
            }
        }
        $SRC = null;
        if (isset($_REQUEST['ajax'])) {
            sendJS($ajax, 1, 1);
        }
    }

} elseif (isset($_REQUEST['replymode'])) {
    // Job to do: Answer / AnswerAll / Forward
    // We need the original mail for extracting relevant structure and body data
    if (!empty($from_handler) && $mail) {
        $save_class = 'handler_'.$from_handler.'_api';
        $SAVE = new $save_class($_PM_, $_SESSION['phM_uid']);
        $orig = $SAVE->give_mail_struct($mail);
        $struct = $orig['structure'];
        if (!empty($orig['header']['importance'])) {
            $WP_send['importance'] = $orig['header']['importance'];
        }
        $orig['header']['date'] = date($WP_msg['dateformat'], $orig['header']['date']);
        // In case of a SMS we wish to allow to "Edit as new" too - by loading the SMS editor
        $type = $SAVE->give_mail_type($mail);
        if ('sms' == $type) {
            $WP_send['to'] = $orig['header']['to'];
            $WP_send['body'] = $SAVE->give_mail_part($mail, 0); // Assumes, that SMS are not multipart and plain text
            require_once __DIR__.'/compose.sms.php'; // switch to SMS editor
            return; // Stop further processing right here
        }

        // Prefix of the body to tell about the original mail's subject, the sender and so on
        // Depending on the chosen content type of the mail the header is either a nice HTML table or plain text
        if (!empty($_PM_['core']['send_html'])) {
            $htmlfrom = Format_Parse_Email::parse_email_address($orig['header']['from'], 0, false, false);
            $htmlfrom = ($htmlfrom[0] == $htmlfrom[1])
                    ? '<a href="mailto:'.$htmlfrom[0].'">'.$htmlfrom[0].'</a>'
                    : $htmlfrom[1].' (<a href="mailto:'.$htmlfrom[0].'">'.$htmlfrom[0].'</a>)';
            $htmlto = Format_Parse_Email::parse_email_address($orig['header']['to'], 0, false, false);
            $htmlto = ($htmlto[0] == $htmlto[1])
                    ? '<a href="mailto:'.$htmlto[0].'">'.$htmlto[0].'</a>'
                    : $htmlto[1].' (<a href="mailto:'.$htmlto[0].'">'.$htmlto[0].'</a>)';
            $body_prefix = '&nbsp;<strong>'.$WP_msg['headoldmsg'].'</strong><br />'.CRLF
                    .'<table border="0" cellpadding="2" cellspacing="0">'.CRLF
                    .'<tr><td align="left"><strong>'.$WP_msg['date'].':</strong></td><td align="left">'.text2html($orig['header']['date']).'</td></tr>'.CRLF
                    .'<tr><td align="left"><strong>'.$WP_msg['from'].':</strong></td><td align="left">'.$htmlfrom.'</td></tr>'.CRLF
                    .'<tr><td align="left"><strong>'.$WP_msg['to'].':</strong></td><td align="left">'.$htmlto.'</td></tr>'.CRLF
                    .'<tr><td align="left"><strong>'.$WP_msg['subject'].':</strong></td><td align="left">'.text2html($orig['header']['subject']).'</td></tr>'.CRLF
                    .'</table><br /><br />'.CRLF;
        } else {
            $body_prefix = $WP_msg['headoldmsg'].CRLF
                    .$WP_msg['date'].': '.$orig['header']['date'].CRLF
                    .$WP_msg['from'].': '.$orig['header']['from'].CRLF
                    .$WP_msg['to'].': '.$orig['header']['to'].CRLF
                    .$WP_msg['subject'].': '.$orig['header']['subject'].CRLF
                    .' '.CRLF;
        }
        //
        // Determine, which of the mail parts is the mail body
        //
        $part_text = $part_enriched = $part_html = -1;
        if (isset($struct['body']['part_type']) && is_array($struct['body']['part_type'])) {
            $mode = 'mixed';
            if (isset($struct['header']['content_type'])
                    && 'multipart/' == substr(strtolower($struct['header']['content_type']), 0, 10)) {
                preg_match('!multipart/(\w+)!', strtolower($struct['header']['content_type']), $found);
                $mode = (!empty($found) && isset($found[1])) ? $found[1] : 'mixed';
            }
            ksort($struct['body']['imap_part']); // Ensure the real structure is iterated upon
            foreach ($struct['body']['imap_part'] as $k => $v) {
                if (isset($old_mode) && substr($v, 0, strlen($parent)) != $parent) {
                    $mode = $old_mode;
                } elseif ($mode == 'inlinemail') {
                    continue;
                }
                if (!isset($struct['body']['part_type'][$k])) {
                    $struct['body']['part_type'][$k] = 'text/plain';
                }
                $pType = strtolower($struct['body']['part_type'][$k]);
                if ('multipart/' == substr($v, 0, 10)) {
                    preg_match('!multipart/(\w+)!', $pType, $found);
                    if (!empty($found) && isset($found[1])) {
                        $mode = $found[1];
                    }
                } elseif ('message/' == substr($pType, 0, 8)) {
                    $parent = $v;
                    $old_mode = $mode;
                    $mode = 'inlinemail';
                    $parts_attach = true;
                    $struct['body']['part_attached'][$k] = true;
                } elseif (isset($struct['body']['dispo'][$k]) && $struct['body']['dispo'][$k] == 'attachment') {
                    $parts_attach = true;
                    $struct['body']['part_attached'][$k] = true;
                    continue;
                } elseif ('text/plain' == $pType || 'text' == $pType) {
                    if (('mixed' == $mode || 'alternative' == $mode) && -1 != $part_text) {
                        $parts_attach = true;
                        $struct['body']['part_attached'][$k] = true;
                        continue;
                    }
                    $part_text = $k;
                } elseif ('text/enriched' == $pType) {
                    if (('mixed' == $mode || 'alternative' == $mode) &&  -1 != $part_enriched) {
                        $parts_attach = true;
                        $struct['body']['part_attached'][$k] = true;
                        continue;
                    }
                    $part_enriched = $k;
                } elseif ('text/html' == $pType) {
                    if (('mixed' == $mode || 'alternative' == $mode) &&  -1 != $part_html) {
                        $parts_attach = true;
                        $struct['body']['part_attached'][$k] = true;
                        continue;
                    }
                    $part_html = $k;
                } else {
                    if (-1 != $part_html && $struct['body']['childof'][$part_html] != 0 && $mode == 'related'
                            && $struct['body']['childof'][$k] == $struct['body']['childof'][$part_html]) {
                        continue;
                    }
                    $parts_attach = true;
                    $struct['body']['part_attached'][$k] = true;
                }
            }
        } elseif (isset($struct['header']['content_type'])) {
            $struct['header']['content_type'] = strtolower($struct['header']['content_type']);
            if ('text/plain' == $struct['header']['content_type'] || 'text' == $struct['header']['content_type']) {
                $part_text = 0;
            } elseif ('text/enriched' == $struct['header']['content_type']) {
                $part_enriched = 0;
            } elseif ('text/html' == $struct['header']['content_type']) {
                $part_html = 0;
            }
        } else {
            $part_text = 0;
        }
        if (-1 == $part_html && -1 !== $part_enriched) {
            $part_html = $part_enriched;
            $part_enriched = -1;
        }

        // Prepare the mail body to start off with depending on the chosen content type of the mail
        $WP_send['body'] = '';
        if (!empty($_PM_['core']['send_html'])) {
            if (-1 != $part_html) {
                $WP_send['body'] = $SAVE->give_mail_part($mail, $part_html);
                if (preg_match('!\<body.*?\>(.+)\</body\>!msi', $WP_send['body'], $found)) {
                    $WP_send['body'] = $found[1];
                }

                $ctype_pad = (isset($struct['body']['part_detail'][$part_html]) && $struct['body']['part_detail'][$part_html])
                        ? $struct['body']['part_detail'][$part_html]
                        : ((isset($struct['header']['content_type_pad'])) ? $struct['header']['content_type_pad'] : '' );
                if ($ctype_pad) {
                    preg_match('!charset(\s*)=(\s*)"?([^";]+)("|$|;)!i', $ctype_pad, $found);
                }
                $charset = (isset($found[3])) ? $found[3] : 'iso-8859-1';
                if (strtolower($charset) == 'us-ascii') { // htmlspecialchars does not know it ...
                    $charset = 'utf-8';
                }
                /* $WP_send['body'] = links(
                        $WP_send['body'], // ist doch schon UTF-8 ... encode_utf8($WP_send['body'], $charset, true),
                        'html',
                        $sanitize,
                        htmlspecialchars(PHP_SELF.'?l=output&h=email&mail='.$mail.'&'.give_passthrough(1).'&cid=')
                ); */
            } elseif (-1 != $part_text) {
                $WP_send['body'] = text2html($SAVE->give_mail_part($mail, $part_text));
            }
        } else {
            if (-1 != $part_text) {
                $WP_send['body'] = $SAVE->give_mail_part($mail, $part_text);
            } elseif (-1 != $part_html) {
                $WP_send['body'] = $SAVE->give_mail_part($mail, $part_html);
                if (preg_match('!\<body.*?\>(.+)\</body\>!msi', $WP_send['body'], $found)) {
                    $WP_send['body'] = $found[1];
                }
                // Passing the encoding to the html2text is rather complicated...
                $myCharset = (!empty($struct['body']['part_detail'][$part_html])) ? $struct['body']['part_detail'][$part_html] : 'charset=utf-8';
                $WP_send['body'] = '<html><head><meta http-equiv="content-type" content="text/html; '.$myCharset.'" /></head><body>'.$WP_send['body'].'</body></html>';
                // Go!
                try {
                    $WP_send['body'] = \Format\Convert\Html2Text::convert($WP_send['body']);
                } catch (\Format\Convert\Html2TextException $e) {
                    // void - don't know, what to do
                }
            }
        }

        if ('draft' != $_REQUEST['replymode'] && 'template' != $_REQUEST['replymode']) {
            // Separate body from original signature
            if (empty($_PM_['core']['reply_dontcutsignatures'])) {
                list($WP_send['body']) = explode(CRLF.'-- '.CRLF, $WP_send['body']);
            }
            $WP_send['body'] = (!empty($_PM_['core']['send_html']))
                    ? $body_prefix.'<div style="border-left:2px blue solid;padding-left:4px;">'.$WP_send['body'].'</div>'
                    : htmlspecialchars($body_prefix.'> '.str_replace(CRLF, CRLF.'> ', $WP_send['body']));
        }

        if ($_REQUEST['replymode'] == 'forward' || $_REQUEST['replymode'] == 'draft' || $_REQUEST['replymode'] == 'template') {
            if (preg_match('!^\[Fwd\:\ (.+)\]$!i', trim($orig['header']['subject']))) {
                $orig['header']['subject'] = preg_replace('!^\[Fwd\:\ (.+)\]$!i', '\1', trim($orig['header']['subject']));
            }
            $WP_send['subject'] = 'Fwd: '.preg_replace('!(Re|AW|WG|Fwd):( ){0,1}!i', '', $orig['header']['subject']);
            if ($_REQUEST['replymode'] == 'draft' || $_REQUEST['replymode'] == 'template') {
                $WP_send['subject'] = $orig['header']['subject'];
                $sendlinkadd = '&WP_send[orig]='.$mail.'&WP_send[replymode]='.$_REQUEST['replymode'];
                $WP_send['to'] = $orig['header']['to'];
                if (isset($orig['header']['cc'])) {
                    $WP_send['cc'] = $orig['header']['cc'];
                }
                if (isset($orig['header']['bcc'])) {
                    $WP_send['bcc'] = $orig['header']['bcc'];
                }
                $WP_send['from'] = Format_Parse_Email::parse_email_address($orig['header']['from']);
                $fromprofile = $Acnt->getProfileFromEmail($_SESSION['phM_uid'], $orig['header']['from']);
            } elseif ($_REQUEST['replymode'] == 'forward') {
            	$fromprofile = $Acnt->getProfileFromEmail
            	       ($_SESSION['phM_uid']
            	       , $orig['header']['to'].','.$orig['header']['cc'].(isset($orig['header']['cc']) ? trim($orig['header']['cc']) : '')
            	       );
                $sendlinkadd = '&WP_send[replymode]=fwd&WP_send[orig]='.$mail;
            }
            $WP_send['from'] = $orig['header']['from'];
            if (!empty($parts_attach) && !empty($struct['body']['part_attached'])) {
                $tpl_a = $tpl->get_block('origattachs');
                $return = Format_Parse_Email::get_visible_attachments($struct['body'], 'links', $_PM_['path']['frontend'].'/filetypes/16');
                $tpl_al = $tpl_a->get_block('attline');
                foreach ($return['img'] as $key => $value) {
                    $tpl_al->assign(array
                            ('small_icon' => $_PM_['path']['frontend'].'/filetypes/16/'.$value
                            ,'name' => phm_addcslashes($return['name'][$key], "'")
                            ,'filename' => doubleval($key)
                            ,'mimetype' => $struct['body']['part_type'][$key]
                            ));
                    $tpl_a->assign('attline', $tpl_al);
                    $tpl_al->clear();
                }
                $tpl_a->assign('msg_attachs', $WP_msg['attachs']);
                $tpl->assign('origattachs', $tpl_a);
            }

        } elseif ($_REQUEST['replymode'] == 'answer' || $_REQUEST['replymode'] == 'answerAll') {
            if (preg_match('!^\[Fwd\:\ (.+)\]$!i', trim($orig['header']['subject']))) {
                $orig['header']['subject'] = preg_replace('!^\[Fwd\:\ (.+)\]$!i', '\1', trim($orig['header']['subject']));
            }
            $WP_send['subject'] = 'Re: '.preg_replace('!(Re|AW|WG|Fwd):( ){0,1}!i', '', $orig['header']['subject']);
            $sendlinkadd = '&WP_send[replymode]=re&WP_send[orig]='.$mail;
            if ($_REQUEST['replymode'] == 'answerAll') {
                foreach ($orig['header']['complete'][1] as $k => $v) {
                    if (strtolower($v) == 'cc') {
                        $orig['header']['cc'] = $orig['header']['complete'][2][$k];
                        break;
                    }
                }
                $WP_send['to'] = gather_addresses(array
                        (trim($orig['header']['to'])
                        ,trim((!empty($orig['header']['replyto'])) ? $orig['header']['replyto'] : $orig['header']['from'])
                        ));
                if (!empty($orig['header']['cc'])) {
                    $WP_send['cc'] = gather_addresses(array(trim($orig['header']['cc'])));
                }
            } else {
                $WP_send['to'] = (!empty($orig['header']['replyto'])) ? $orig['header']['replyto'] : $orig['header']['from'];
            }
            $fromprofile = $Acnt->getProfileFromEmail($_SESSION['phM_uid'], $orig['header']['to'].(isset($orig['header']['cc']) ? ' '.trim($orig['header']['cc']) : ''));
        }
    }
}
$t_pmen = $tpl->get_block('priomen');
foreach (array(1 => $WP_msg['high'], 3 => $WP_msg['normal'], 5 => $WP_msg['low']) as $k => $v) {
    $t_pmen->assign(array('prioval' => $k, 'priotxt' => $v));
    if ($WP_send['importance'] == $k) {
        $t_pmen->assign_block('priosel');
    }
    $tpl->assign('priomen', $t_pmen);
    $t_pmen->clear();
}
if (!empty($WP_send['receipt'])) {
    $tpl->assign_block('receipt');
}
// Try to guess, whether the user might wish to send from a currently selected IMAP account
if (empty($fromprofile)) {
    if (isset($_SESSION['phM_login_handler']) && $_SESSION['phM_login_handler'] == 'email') {
        $API = new handler_email_api($_PM_, $_SESSION['phM_uid']);
        $fromprofile = $API->get_profile_from_folder($_SESSION['phM_login_folder']);
        if (false !== $fromprofile) {
            $fromprofile = array($Acnt->getAccountIdFromProfile($_SESSION['phM_uid'], $fromprofile), 0);
        } else {
            unset($fromprofile);
        }
        unset($API);
    }
}

if (is_array($useraccounts)) {
    $number_useraccounts = sizeof($useraccounts);

    if ($number_useraccounts > 1 || ($number_useraccounts == 1 && !empty($aliases))
            || ($number_useraccounts > 0 && !empty($_PM_['core']['allow_man']))) {
        $t_acc = $tpl->get_block('on_account');
        $t_men = $t_acc->get_block('accmenu');
        $fromselected = false;
        if (empty($fromprofile) || !$fromprofile[0]) {
            $fromprofile = isset($_PM_['core']['default_profile']) ? array($_PM_['core']['default_profile'], 0) : array(0, 0);
        }
        foreach ($useraccounts as $k => $profilenm) {
            $profiledata = $Acnt->getAccount($_SESSION['phM_uid'], $k);
            $showname = $profiledata['address'];
            if ($profiledata['real_name']) {
                $showname = $profiledata['real_name'].' <'.$showname.'>';
            }
            $t_men->assign(array
                    ('counter' => $k
                    ,'profilenm' => htmlspecialchars($showname.' - '.$profilenm)
                    ,'vcf' => $profiledata['sendvcf']
                    ));
            if (!$fromselected && $fromprofile[0] == $k && $fromprofile[1] == 0) {
                $t_men->assign_block('selected');
                $fromselected = true;
                if ($profiledata['sig_on'] && $profiledata['signature']
                        && (!isset($_REQUEST['replymode']) || ('draft' != $_REQUEST['replymode'] && 'template' != $_REQUEST['replymode']))) {
                    $sig = $Acnt->get_signature($_SESSION['phM_uid'], $profiledata['signature']);
                    $WP_send['sign'] = phm_stripslashes($sig['signature']);
                    $WP_send['sign_html'] = phm_stripslashes($sig['signature_html']);
                    $WP_send['sendvcf'] = $profiledata['sendvcf'];
                }
            }
            $t_acc->assign('accmenu', $t_men);
            $t_men->clear();
            // Drop my addresses from Reply all (should I?)
            if (isset($_REQUEST['replymode']) && 'answerAll' == $_REQUEST['replymode']) {
                foreach (array('to', 'cc') as $feld) {
                    $WP_send[$feld] = str_ireplace($profiledata['address'], '', $WP_send[$feld]);
                    $WP_send[$feld] = preg_replace('!^,\s|,\s$!', '', preg_replace('!,\s,!', ',' , $WP_send[$feld]));
                }
            }
            if (!isset($aliases[$k])) {
                continue;
            }
            // Incorporate aliases
            foreach ($aliases[$k] as $aid => $alias) {
                $showname = $alias['email'];
                if ($alias['real_name']) {
                    $showname = $alias['real_name'].' <'.$showname.'>';
                }
                $t_men->assign(array
                        ('counter' => $k.'.'.$aid
                        ,'profilenm' => htmlspecialchars(' -> '.$showname)
                        ,'vcf' => $alias['sendvcf'] == 'default' ? $profiledata['sendvcf'] : $alias['sendvcf']
                        ));
                if (!$fromselected && $fromprofile[0] == $k && $fromprofile[1] == $aid) {
                    $t_men->assign_block('selected');
                    $fromselected = true;
                    if ($alias['signature'] && $alias['signature']
                            && (!isset($_REQUEST['replymode']) || ('draft' != $_REQUEST['replymode'] && 'template' != $_REQUEST['replymode']))) {
                        $sig = $Acnt->get_signature($_SESSION['phM_uid'], $alias['signature']);
                        $WP_send['sign'] = phm_stripslashes($sig['signature']);
                        $WP_send['sign_html'] = phm_stripslashes($sig['signature_html']);
                        $WP_send['sendvcf'] = $alias['sendvcf'] == 'default' ? $profiledata['sendvcf'] : $alias['sendvcf'];
                    }
                }
                $t_acc->assign('accmenu', $t_men);
                $t_men->clear();

                // Drop my addresses from Reply all (should I?)
                if (isset($_REQUEST['replymode']) && 'answerAll' == $_REQUEST['replymode']) {
                    foreach (array('to', 'cc') as $feld) {
                        $WP_send[$feld] = str_ireplace($alias['email'], '', $WP_send[$feld]);
                        $WP_send[$feld] = preg_replace('!^,\s|,\s$!', '', preg_replace('!,\s,!', ',' , $WP_send[$feld]));
                    }
                }
            }
        }
        $t_acc->assign('msg_sigload', $WP_msg['sigload']);
        $tpl->assign('on_account', $t_acc);
    } else {
        $t_acc = $tpl->get_block('one_account');
        foreach ($useraccounts as $k => $profilenm) {
            break;
        }
        $profiledata = $Acnt->getAccount($_SESSION['phM_uid'], $k);
        $t_acc->assign('from', $profilenm);
        $t_acc->assign('address', $profiledata['address']);
        $t_acc->assign('profile', $k);
        $sig = $Acnt->get_signature($_SESSION['phM_uid'], $profiledata['signature']);
        $WP_send['sign'] = phm_stripslashes($sig['signature']);
        $WP_send['sign_html'] = phm_stripslashes($sig['signature_html']);
        $WP_send['sendvcf'] = $profiledata['sendvcf'];
        if (get_magic_quotes_gpc()) {
            $WP_send['sign'] = phm_stripslashes($WP_send['sign']);
        }
        $tpl->assign('one_account', $t_acc);
    }
}

// Glue body and signature together - if there's any of these
if (isset($_PM_['core']['answer_style']) && $_PM_['core']['answer_style'] == 'tofu') {
    $body = (!empty($WP_send['sign'])
                    ? (!empty($_PM_['core']['send_html'])
                            ? '<p /><p />-- <br />'.($WP_send['sign_html'] ? $WP_send['sign_html'] : text2html($WP_send['sign'])).'<p />'
                            : LF.'-- '.LF.$WP_send['sign']).LF.' '.LF
                    : ''
            )
            .(isset($WP_send['body']) ? $WP_send['body'] : '');
} else {
    $body = (isset($WP_send['body']) ? $WP_send['body'] : '')
            .(!empty($WP_send['sign'])
                    ? (!empty($_PM_['core']['send_html'])
                            ? '<br />-- <br />'.($WP_send['sign_html'] ? $WP_send['sign_html'] : text2html($WP_send['sign']))
                            : LF.'-- '.LF.$WP_send['sign'])
                    : ''
            );
}
if (!empty($_PM_['core']['send_html'])) {
    $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            .'<html xmlns="http://www.w3.org/1999/xhtml"><body>'.$body.'</body></html>';
}

if (!empty($_PM_['core']['send_html'])) {
	$tpl->fill_block('send_html', array('user_lang' => $WP_msg['language']));
}

$passthru = give_passthrough(1);

if (!empty($WP_send['sendvcf']) && $tpl->block_exists('vcf_'.$WP_send['sendvcf'])) {
    $tpl->assign_block('vcf_'.$WP_send['sendvcf']);
}
if (!empty($_PM_['customsize']['core_boilerplates_open']) && $tpl->block_exists('bplates_are_open')) {
    $tpl->assign_block('bplates_are_open');
}
if (!empty($_PM_['customsize']['core_contactsbar_open']) && $tpl->block_exists('contacts_are_open')) {
    $tpl->assign_block('contacts_are_open');
}
// Mobile needs them prepared differently
if ($tpl->block_exists('predef_recipients')) {
    $t_pr = $tpl->get_block('predef_recipients');
    foreach (array('to', 'cc', 'bcc') as $target) {
        if (empty($WP_send[$target])) {
            continue;
        }
        if (isset($WP_msg[$target])) {
            $target_txt = $WP_msg[$target];
        } else {
            $target_txt = strtoupper($target);
        }
        $addressList = multi_address($WP_send[$target], 20, 'maillist');
        foreach (explode(', ', $addressList[2]) as $address) {
            $t_pr->assign(array(
                    'target' => $target,
                    'target_txt' => $target_txt,
                    'uniqid' => uniqid(),
                    'address'  => phm_entities($address)
                    ));
            $tpl->assign('predef_recipients', $t_pr);
            $t_pr->clear();
        }
    }
}

$tpl->assign(array
        ('msg_priority' => $WP_msg['prio']
        ,'msg_options' => $WP_msg['alt_setup']
        ,'msg_send' => $WP_msg['send']
        ,'msg_from' => $WP_msg['from']
        ,'msg_to' => $WP_msg['to']
        ,'to' => !empty($WP_send['to']) ? htmlspecialchars($WP_send['to']) : ''
        ,'cc' => !empty($WP_send['cc']) ? htmlspecialchars($WP_send['cc']) : ''
        ,'bcc' => !empty($WP_send['bcc']) ? htmlspecialchars($WP_send['bcc']) : ''
        ,'msg_subject' => $WP_msg['subject']
        ,'user_lang' => $WP_msg['language']
        ,'subject' => !empty($WP_send['subject']) ? htmlspecialchars(str_replace('{', '{\\', $WP_send['subject'])) : ''
        ,'msg_copytobox' => $WP_msg['copytobox']
        ,'msg_receipt_out' => $WP_msg['receipt_out']
        ,'msg_confirm_no_subject' => $WP_msg['confirm_no_subject']
        ,'msg_confirm_no_receiver' => $WP_msg['confirm_no_receiver']
        ,'msg_notsent_save' => $WP_msg['nomailsent_savedraft']
        ,'msg_showbcc' => $WP_msg['EmailShowBCC']
        ,'msg_receipt' => $WP_msg['receipt_out']
        ,'msg_savedraft' => $WP_msg['EmailSaveAsDraft']
        ,'msg_savetemplate' => $WP_msg['EmailSaveAsTemplate']
        ,'msg_attachs' => $WP_msg['EmailAttachFile']
        ,'msg_contacts' => $WP_msg['APIContacts']
        ,'msg_signature' => $WP_msg['EmailSelectSig']
        ,'msg_sendmail' => $WP_msg['EmailCreatingMail']
        ,'msg_rewrap_text' => $WP_msg['RewrapText']
        ,'msg_bplate_fetching' => $WP_msg['BPlateFetching']
        ,'msg_sendvcf' => $WP_msg['VCFsend']
        ,'msg_vcf_none' => $WP_msg['VCFsendNone']
        ,'msg_vcf_default' => $WP_msg['VCFsendDefault']
        ,'msg_vcf_priv' => $WP_msg['VCFsendPriv']
        ,'msg_vcf_busi' => $WP_msg['VCFsendBusi']
        ,'msg_vcf_all' => $WP_msg['VCFsendAll']
        ,'msg_sendtogroup' => $WP_msg['SendToGroup']
        ,'msg_saveas' => $WP_msg['savemail']
        ,'att_link' => htmlspecialchars(PHP_SELF.'?l=compose_email_upload&h=core&'.$passthru)
        ,'sig_link' => htmlspecialchars(PHP_SELF.'?l=compose_email_sig&h=core&'.$passthru)
        ,'contacts_link' => PHP_SELF.'?l=apiselect&h=contacts&what=email&json=1&'.$passthru
        ,'search_adb_url' => PHP_SELF.'?l=apiselect&h=contacts&what=email&'.$passthru
        ,'sendtarget' => htmlspecialchars(PHP_SELF.'?l=send_email&h=core&'.$passthru.'&from_h='.$from_handler.'&mail='.$mail.$sendlinkadd)
        ,'path_bplateget' => PHP_SELF.'?l=compose_email&h=core&'.$passthru.'&get_boilerplate='
        ,'path_bplatesetopen' => PHP_SELF.'?l=worker&h=core&what=customsize&'.$passthru.'&token=core_boilerplates_open&value='
        ,'path_contactsbarget' => PHP_SELF.'?l=compose_email&h=core&'.$passthru.'&get_contactsbar=1'
        ,'path_contactsbarsetopen' => PHP_SELF.'?l=worker&h=core&what=customsize&'.$passthru.'&token=core_contactsbar_open&value='
        // Build the message thread, if possible
        ,'message_id' => isset($orig) ? htmlspecialchars($orig['header']['message_id']) : ''
        ,'head_references' => isset($orig) ? htmlspecialchars($orig['header']['references']) : ''
        ,'body' => str_replace('{', '{\\', $body) // The message's body
        ,'answer_style' => isset($_PM_['core']['answer_style']) && $_PM_['core']['answer_style'] == 'tofu' ? 'tofu' : 'netiqette'
        ,'msg_upload' => $WP_msg['Upload']
        ,'path_attachbrowse' => PHP_SELF.'?h=core&l=selectfile&'.$passthru
        ,'receive_files_url' => PHP_SELF.'?h=core&l=compose_email&ajax=1&receive_file=1&'.$passthru
        ,'upload_form_action' => htmlspecialchars(PHP_SELF.'?l=compose_email_upload&h=core&isjs=1&'.$passthru)
        ));
// Allow to select smileys
if ($tpl->block_exists('smileyselector')) {
    $t_ss = $tpl->get_block('smileyselector');
    foreach (Smiley::map() as $k => $v) {
        $t_ss->assign(array('icon' => $k, 'emoticon' => $v));
        $tpl->assign('smileyselector', $t_ss);
        $t_ss->clear();
    }
}

if (false !== ($maxfilesize = ini_get('upload_max_filesize')) && $maxfilesize) {
    if ($tpl->block_exists('maxfilesize')) {
        $tpl->fill_block('maxfilesize', 'maxfilesize', wash_size_field($maxfilesize));
    }
    $tpl->assign('msg_maxfilesize', $WP_msg['MaxFilesize'].': '.size_format(wash_size_field($maxfilesize), 0, 0, 0));
}