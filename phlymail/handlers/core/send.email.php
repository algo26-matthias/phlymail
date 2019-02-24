<?php
/**
 * send.emailphp -> the actual script, which handles sending
 * @package phlyMail Nahariya 4.0+
 * @subpackage Core handler
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de
 * @version 4.4.6 2015-04-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
/**
 * Sending an email is divided into three steps, each of them run in an own
 * pass of this script.
 * Step 1 builds the email file based on the request parameters.
 * Step 2 actually sends the mail through either SMTP or sendmail
 * Step 3 finally saves the sent email in the configured default mail storage.
 * This three step flow ensures, that even larger mails can safely be sent
 * without failing with a script timeout (max_execution_time).
 * For safely passing the required header information (SMTP needs at least
 * the sender and the receiver, Sendmail might also need them), these are saved
 * as a separate serialized file alongside the mail.
 */

$save_handler = 'email'; // This might become configurable in the future
$tmp_path = $_PM_['path']['temp'].'/';
$Acnt = new DB_Controller_Account();

if (!isset($_REQUEST['WP_do'])) {
    require_once($_PM_['path']['lib'].'/message.encode.php');
    $MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');

    // Enforced override of language setting as of 2010-04-07
    $WP_msg['iso_encoding'] = 'utf-8';

    $msg_id = uniqid(time().'.');
    if (!isset($WP_send)) {
        $WP_send = array();
    }

    if (!empty($_REQUEST['WP_send'])) {
        // Can come in array form (mobile interface)
        foreach (array('to', 'cc', 'bcc', 'recipients') as $tkn) {
            if (!empty($_REQUEST['WP_send'][$tkn]) && is_array($_REQUEST['WP_send'][$tkn])) {
                $_REQUEST['WP_send'][$tkn] = implode(', ', $_REQUEST['WP_send'][$tkn]);
            }
        }
        // "Recipients" is the source field in Mobile view, which usually gets transferred
        // over to the specific to/cc/bcc via autosuggest. If the latter does not find
        // anything, the address stays there
        if (!empty($_REQUEST['WP_send']['recipients'])) {
            $_REQUEST['WP_send']['to'] .= (!empty($_REQUEST['WP_send']['to']) ? ', ' : '').$_REQUEST['WP_send']['recipients'];
        }
    }

    // Clean given strings
    foreach (array('from', 'to', 'cc', 'bcc', 'subj', 'body', 'bodytype',
            'return_receipt', 'prio', 'inreply', 'from_profile', 'replymode',
            'orig', 'references', 'sendvcf') as $k) {
       if (!isset($WP_send[$k])) {
           $WP_send[$k] = null;
       }
       if (!isset($_REQUEST['WP_send'][$k])) {
           continue;
       }
       $WP_send[$k] = phm_stripslashes(un_html($_REQUEST['WP_send'][$k]));
    }
    if (isset($_REQUEST['WP_send']['attach'])) {
        $WP_send['attach'] = $_REQUEST['WP_send']['attach'];
        // Process deleted item
        foreach ($WP_send['attach'] as $k => $v) {
            // Item got marked deleted before sending
            if (isset($v['is_deleted']) && $v['is_deleted']) {
                // An actually uploaded file -> remove from filesystem
                if ($v['mode'] == 'user') {
                    @unlink($tmp_path.$v['filename']);
                }
                unset($WP_send['attach'][$k]);
                continue;
             }
             // URLdecode is important
             $WP_send['attach'][$k]['name'] = phm_stripslashes(urldecode($v['name']));
        }
    }
    // Attach VCF, if told so
    if (isset($WP_send['sendvcf']) && $WP_send['sendvcf'] != 'none') {
        $vcfTmpName = uniqid(time().'.');
        $userdata = $DB->get_usrdata($_SESSION['phM_uid'], false);
        $vcfid = $userdata['contactid'];
        $PHM_ADB_EX_DO = 'export';
        $PHM_ADB_EX_ENTRY = $vcfid;
        $PHM_ADB_EX_FORMAT = 'VCF';
        $PHM_ADB_EX_TYPE = $WP_send['sendvcf'];
        $PHM_ADB_EX_PUTTOFILE = $_PM_['path']['temp'].'/'.$vcfTmpName;
        require_once($_PM_['path']['handler'].'/contacts/exchange.php');
        if (!isset($WP_send['attach'])) {
            $WP_send['attach'] = array();
        }
        $WP_send['attach'][] = array
                    ('mode' => 'user'
                    ,'filename' => $vcfTmpName
                    ,'mimetype' => 'text/vcard'
                    ,'name' => $userdata['firstname'].' '.$userdata['lastname'].'.vcf'
                    );
    }

    // Maybe some external handler called this module and did not specify the sending profile
    if (!isset($WP_send['from_profile'])) {
        $WP_send['from'] = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_);
        $WP_send['from_profile'] = implode('.', $Acnt->getProfileFromEmail($_SESSION['phM_uid'], $WP_send['from']));
    }
    // Split, if needed, the send profile into profile ID and alias ID
    $WP_send['alias'] = false;
    if (false !== strpos($WP_send['from_profile'], '.')) {
        list ($WP_send['from_profile'], $WP_send['alias']) = explode('.', $WP_send['from_profile']);
    }
    $connect = $Acnt->getAccount($_SESSION['phM_uid'], $WP_send['from_profile']);
    if ($WP_send['alias']) {
        $receipt = $WP_send['from'] = $connect['aliases'][$WP_send['alias']]['email'];
        $real_name = $connect['aliases'][$WP_send['alias']]['real_name'];
    } else {
        $receipt = $WP_send['from'] = $connect['address'];
        $real_name = $connect['real_name'];
    }
	if ($real_name) {
        $WP_send['from'] .= ' ('.$real_name.')';
    }
    // Saving the gathered information to a temp file
    $state = file_put_contents($tmp_path.$msg_id.'.ser', serialize($WP_send));
    // At least one byte written -> build mail file
    if ($state) {
        $WP_send['additional'] = isset($WP_send['additional']) ? trim($WP_send['additional']).CRLF : '';
        // Find out about attachments and whether this mail will be MIMEd...
        if (isset($WP_send['bodytype']) && 'text/html' == $WP_send['bodytype']) {
            // Are there some inline items? Fix their URL
            $WP_send['body'] = preg_replace('!(\<img.+)data-original-cid\="(.+)" (.+src\=").+"!uUism', '$1$3cid:$2"', $WP_send['body']);

            if (!empty($_PM_['core']['use_provsig']) && file_exists($_PM_['path']['conf'].'/forced.signature.wpop')
                    && is_readable($_PM_['path']['conf'].'/forced.signature.wpop')) {
                $WP_send['body'] .= '<br />'.CRLF.'<br />'.CRLF
                        .nl2br(file_get_contents($_PM_['path']['conf'].'/forced.signature.wpop'));
            }
        } else {
            if (!empty($_PM_['core']['use_provsig']) && file_exists($_PM_['path']['conf'].'/forced.signature.wpop')
                    && is_readable($_PM_['path']['conf'].'/forced.signature.wpop')) {
                $WP_send['body'] .= ' '.CRLF.file_get_contents($_PM_['path']['conf'].'/forced.signature.wpop');
            }
            $WP_send['bodytype'] = 'text/plain';
        }
        // Ensure, there's just LF in the body
        $WP_send['body'] = str_replace(CRLF, LF, $WP_send['body']);
        // Obey outgoing encoding
        $WP_send['body'] = decode_utf8($WP_send['body'], $WP_msg['iso_encoding']);

        if (!empty($_PM_['core']['send_wordwrap']) && function_exists('wordwrap')) {
            $WP_send['body'] = wordwrap($WP_send['body'], 72, LF);
        }
        // On answering mails, refer to the original message ID
        if (isset($WP_send['inreply']) && $WP_send['inreply']) {
            $WP_send['inreply'] = trim($WP_send['inreply']);
            $WP_send['inreply'] .= CRLF.'References: '
                    .((isset($WP_send['references']) && $WP_send['references']) ? trim($WP_send['references']).' ' : '')
                    .'<'.$WP_send['inreply'].'>';
            $WP_send['additional'] .= 'In-Reply-To: <'.$WP_send['inreply'].'>'.CRLF;
        }
        $WP_send['additional'] .= set_prio_headers($WP_send['prio']);

        $mime_boundary = '_---_phlyMail_--_'.time().'==_';
        if (isset($WP_send['attach']) && is_array($WP_send['attach']) && !empty($WP_send['attach'])) {
            $mime_encoding = true;
            $attachments = true;
        }
        $WP_send['body_orig'] = $WP_send['body'];
        if (preg_match('/[\x80-\xff]/', $WP_send['body'])) {
            $mime_encoding = true;
            $bodylines = explode(LF, $WP_send['body']);
            $WP_send['body'] = '';
            foreach ($bodylines as $value) {
                $WP_send['body'] .= phm_quoted_printable_encode($value);
            }
            unset($bodylines);
            $body_qp = true;
        }
        if (isset($WP_send['return_receipt']) && $WP_send['return_receipt']) {
            $WP_send['additional'] .= 'Return-Receipt-To: '.$receipt.CRLF.'Disposition-Notification-To: '.$receipt.CRLF;
        }
        // Create Message ID
        if (isset($WP_send['from']) && $WP_send['from']) {
            $addi = Format_Parse_Email::parse_email_address($WP_send['from'], 0, false);
            $dom = strstr($addi[0], '@');
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $dom = '@'.$_SERVER['SERVER_NAME'];
        } else { // This is failsafe only
            $dom = '@phlymail.local';
        }
        $WP_send['additional'] .= 'Message-ID: <'.$msg_id.$dom.'>'.CRLF;
        // Create the message header
        $header = create_messageheader
                (array('from' => $WP_send['from'], 'to' => $WP_send['to'], 'cc' => $WP_send['cc'], 'bcc' => $WP_send['bcc'], 'subject' => $WP_send['subj'])
                ,$WP_send['additional']
                ,$WP_msg['iso_encoding']
                ,$connect['userheaders']
                ,(isset($uh_before)) ? $uh_before : true
                );
        // Start composing the file
        $tmpout = fopen($tmp_path.$msg_id, 'w');
        fwrite($tmpout, $header);
        if (isset($attachments) && $attachments)  {
            fwrite($tmpout, 'MIME-Version: 1.0'.CRLF);
            fwrite($tmpout, 'Content-Type: multipart/mixed; boundary="'.$mime_boundary.'"'.CRLF);
            fwrite($tmpout, CRLF);
            fwrite($tmpout, 'This is a multipart message in MIME format.'.CRLF);
            fwrite($tmpout, CRLF.'--'.$mime_boundary.CRLF);

            // When sending HTML, also write out a plain text part derived from the final HTML
            if ('text/html' == $WP_send['bodytype']) {
                $mime_boundary2 = '_---_phlyMail_Alt_--_'.time().'==_';
                fwrite($tmpout, 'Content-Type: multipart/alternative; boundary="'.$mime_boundary2.'"'.CRLF.CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary2.CRLF);
                try {
                    $WP_send['body_text'] = \Format\Convert\Html2Text::convert($WP_send['body_orig']);
                } catch (\Format\Convert\Html2TextException $e) {
                    // void - don't know, what to do
                }
                $text_qp = false;
                if (preg_match('/[\x80-\xff]/', $WP_send['body_text'])) {
                    $bodylines = explode(CRLF, $WP_send['body_text']);
                    $WP_send['body_text'] = '';
                    foreach ($bodylines as $value) {
                        $WP_send['body_text'] .= phm_quoted_printable_encode($value);
                    }
                    unset($bodylines);
                    $text_qp = true;
                }
                if (isset($text_qp) && true == $text_qp) {
                    fwrite($tmpout, 'Content-Type: text/plain; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF);
                } else {
                    fwrite($tmpout, 'Content-Type: text/plain; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: 8bit'.CRLF.CRLF);
                }
                fwrite($tmpout, $WP_send['body_text'].CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary2.CRLF);
                if (isset($body_qp) && true == $body_qp) {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF);
                } else {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: 8bit'.CRLF.CRLF);
                }
                fwrite($tmpout, $WP_send['body'].CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary2.'--'.CRLF);
            } else {
                if (isset($body_qp) && true == $body_qp) {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF);
                } else {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: 8bit'.CRLF.CRLF);
                }
                fwrite($tmpout, $WP_send['body'].CRLF);
            }

            foreach ($WP_send['attach'] as $k => $v) {
                if ($v['mode'] == 'user') {
                    fwrite($tmpout, CRLF.'--'.$mime_boundary.CRLF);
                    put_attach_file($tmpout, $tmp_path.$v['filename'], $v['mimetype'], $v['name'], CRLF);
                    @unlink($tmp_path.$v['filename']);
                } else {
                    // Instantiate from handler's API class
                    if (!isset($API) || !is_object($API)) {
                        $from_class = 'handler_'.basename($_REQUEST['from_h']).'_api';
                        $API = new $from_class($_PM_, $_SESSION['phM_uid']);
                    }
                    $mimehead = $API->give_mail_part($_REQUEST['mail'], $v['filename'], true);
                    // MIME header
                    fwrite($tmpout, CRLF.'--'.$mime_boundary.CRLF);
                    fwrite($tmpout, 'Content-Type: '.$mimehead['content_type']);
                    if ($mimehead['filename']) {
                        fwrite($tmpout, '; name='.$mimehead['filename'].CRLF);
                        if (!empty($mimehead['content_id'])) {
                            fwrite($tmpout, 'Content-ID: '.$mimehead['content_id'].CRLF);
                        } else {
                            fwrite($tmpout, 'Content-Disposition: attachment; filename="'.$mimehead['filename'].'"'.CRLF);
                        }
                    } elseif ($mimehead['charset']) {
                        fwrite($tmpout, '; charset="'.$mimehead['charset'].'"'.CRLF);
                    }
                    fwrite($tmpout, 'Content-Transfer-Encoding: '.$mimehead['encoding'].CRLF.CRLF);
                    // Pipe MIME part from original mail to the temp file
                    if ($mimehead['is_imap'] !== false) {
                        fwrite($tmpout, $API->mailpart_giveall($_REQUEST['mail'], $mimehead['is_imap']));
                    } else {
                        $length = $mimehead['length'];
                        while (($line = $API->mailpart_giveline()) && $line && $length > 0) {
                            $length -= strlen($line);
                            fwrite($tmpout, $line);
                        }
                    }
                }
            }
            fwrite($tmpout, CRLF.'--'.$mime_boundary.'--'.CRLF);
        } else {
            if (isset($WP_send['bodytype']) && 'text/html' == $WP_send['bodytype']) {
                fwrite($tmpout, 'MIME-Version: 1.0'.CRLF);
                fwrite($tmpout, 'Content-Type: multipart/alternative; boundary="'.$mime_boundary.'"'.CRLF);
                fwrite($tmpout, CRLF);
                fwrite($tmpout, 'This is a multipart message in MIME format.'.CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary.CRLF);
                try {
                    $WP_send['body_text'] = \Format\Convert\Html2Text::convert($WP_send['body_orig']);
                } catch (\Format\Convert\Html2TextException $e) {
                    // void - don't know, what to do
                }
                $text_qp = false;
                if (preg_match('/[\x80-\xff]/', $WP_send['body_text'])) {
                    $bodylines = explode(LF, $WP_send['body_text']);
                    $WP_send['body_text'] = '';
                    foreach ($bodylines as $value) {
                        $WP_send['body_text'] .= phm_quoted_printable_encode(decode_utf8($value, $WP_msg['iso_encoding']));
                    }
                    unset($bodylines);
                    $text_qp = true;
                }
                if (isset($text_qp) && true == $text_qp) {
                    fwrite($tmpout, 'Content-Type: text/plain; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF);
                } else {
                    fwrite($tmpout, 'Content-Type: text/plain; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: 8bit'.CRLF.CRLF);
                }
                fwrite($tmpout, $WP_send['body_text'].CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary.CRLF);
                if (isset($body_qp) && true == $body_qp) {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF);
                } else {
                    fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                    fwrite($tmpout, 'Content-Transfer-Encoding: 8bit'.CRLF.CRLF);
                }
                fwrite($tmpout, $WP_send['body'].CRLF);
                fwrite($tmpout, CRLF.'--'.$mime_boundary.'--'.CRLF);
            } elseif (isset($body_qp) && true == $body_qp) {
                fwrite($tmpout, 'MIME-Version: 1.0'.CRLF);
                fwrite($tmpout, 'Content-Type: '.$WP_send['bodytype'].'; charset="'.$WP_msg['iso_encoding'].'"'.CRLF);
                fwrite($tmpout, 'Content-Transfer-Encoding: quoted-printable'.CRLF);
                fwrite($tmpout, CRLF);
                fwrite($tmpout, $WP_send['body'].CRLF);
            } else {
                fwrite($tmpout, CRLF.$WP_send['body'].CRLF);
            }
        }
        fclose($tmpout);

        if (isset($_REQUEST['draft']) && $_REQUEST['draft']) {
            $do = 'save&draft=1';
            $statmsg = $WP_msg['EmailSavingMail'];
        } elseif (isset($_REQUEST['template']) && $_REQUEST['template']) {
            $do = 'save&template=1';
            $statmsg = $WP_msg['EmailSavingMail'];
        } else {
            $do = 'send';
            $statmsg = $WP_msg['EmailSendingMail'];
        }
        sendJS(array('statusmessage' => $statmsg, 'url' => PHP_SELF.'?WP_do='.$do.'&'.give_passthrough(1).'&l=send_email&h=core&msg_id='.$msg_id), 1, 1);
    }
}
// Step I b: Prepare bouncing an email
if (isset($_REQUEST['WP_do']) && 'bounce' == $_REQUEST['WP_do']) {
    if (isset($_REQUEST['mail']) && isset($_REQUEST['to'])) {
        $mailhead = '';
        $msg_id = uniqid(time().'.');
        $do = 'send';
        $statmsg = $WP_msg['EmailSendingMail'];

        // Maybe some external handler called this module and did not specify the sending profile
        if (!isset($WP_send['from_profile'])) {
            $WP_send['from'] = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_);
            $WP_send['from_profile'] = implode('.', $Acnt->getProfileFromEmail($_SESSION['phM_uid'], $WP_send['from']));
        }
        // Split, if needed, the send profile into profile ID and alias ID
        $WP_send['alias'] = false;
        if (false !== strpos($WP_send['from_profile'], '.')) {
            list ($WP_send['from_profile'], $WP_send['alias']) = explode('.', $WP_send['from_profile']);
        }
        $connect = $Acnt->getAccount($_SESSION['phM_uid'], $WP_send['from_profile']);
        if ($WP_send['alias']) {
            $receipt = $WP_send['from'] = $connect['aliases'][$WP_send['alias']]['email'];
            $real_name = $connect['aliases'][$WP_send['alias']]['real_name'];
        } else {
            $receipt = $WP_send['from'] = $connect['address'];
            $real_name = $connect['real_name'];
        }
        $WP_send['to'] = $_REQUEST['to'];
        // Save state information for the subsequent steps
        file_put_contents($tmp_path.$msg_id.'.ser', serialize($WP_send));

        // Instantiate from handler's API class
        if (!isset($API) || !is_object($API)) {
            if (!empty($_REQUEST['from_h'])) {
                $from_handler = $_REQUEST['from_h'];
            } else {
                $from_handler = 'email';
            }
            $from_class = 'handler_'.basename($from_handler).'_api';
            $API = new $from_class($_PM_, $_SESSION['phM_uid']);
        }
        session_write_close();
        $valid = $API->give_mail($_REQUEST['mail'], true);
        if ($valid) {
            while (false !== ($line = $API->mailpart_giveline()) && substr($line, 0, 2) != CRLF) {
                $mailhead .= $line;
            }
            $mailhead = Format_Parse_Email::parse_mail_header($mailhead, 0, 1);
            $to = Format_Parse_Email::parse_email_address($_REQUEST['to'], 0, true, true);
            // Drop some redundant or unwanted headers, add Envelope-To to denote recipient
            $mailhead = 'Envelope-To: '.$to.CRLF
                    .preg_replace('/\r\n(Envelope-To|Return-Receipt-To): ([^\r^\n]+)/i', '', $mailhead['prepared']);
            // Start composing the file
            $tmpout = fopen($tmp_path.$msg_id, 'w');
            fwrite($tmpout, trim($mailhead).CRLF.CRLF);
            while (false !== ($line = $API->mailpart_giveline())) {
                fwrite($tmpout, $line);
            }
            fclose($tmpout);
            // Done
            sendJS(array('statusmessage' => $statmsg, 'url' => PHP_SELF.'?WP_do='.$do.'&'.give_passthrough(1).'&l=send_email&h=core&msg_id='.$msg_id), 1, 1);
        } else {
            sendJS(array('error' => 'Mail unreadable'), 1, 1);
        }
    }
}
// Step II: Actually send the email
if (isset($_REQUEST['WP_do']) && 'send' == $_REQUEST['WP_do']) {
    require_once($_PM_['path']['lib'].'/message.encode.php');
    $WP_return = false;
    $msg_id = basename($_REQUEST['msg_id']); // Prevent "../" attacks on this script
    // Read the cached mail structure
    $WP_send = unserialize(file_get_contents($tmp_path.$msg_id.'.ser'));

    if (!empty($_PM_['core']['fix_smtp_host'])) {
        $smtp_host    = $_PM_['core']['fix_smtp_host'];
        $smtp_port    = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587;
        $smtp_user    = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
        $smtp_pass    = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
        $smtpsecurity = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : 'AUTO';
        $smtpselfsign = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : 'false';
    }
    if (!isset($WP_send['from_profile']) && isset($_SESSION['phM_profileID'])) {
        $WP_send['from_profile'] = $_SESSION['phM_profileID'];
    }
    if (isset($WP_send['from_profile'])) {
        $connect = $Acnt->getAccount($_SESSION['phM_uid'], $WP_send['from_profile']);
        // If we have SMTP connection data for this profile, use it, else try to use the default
        // connection data
        if (!empty($connect['smtpserver'])) {
            $smtp_host = $connect['smtpserver'];
            $smtp_port = ($connect['smtpport']) ? $connect['smtpport'] : 587;
            $smtp_user = $connect['smtpuser'];
            $smtp_pass = $connect['smtppass'];
            $smtpsecurity = $connect['smtpsec'];
            $smtpselfsign = $connect['smtpallowselfsigned'];
        }
    }
    if (!isset($WP_send['from'])) {
        $error = $WP_msg['nofrom'];
    }

    if ($_PM_['core']['send_method'] == 'sendmail') {
        $from = Format_Parse_Email::parse_email_address($WP_send['from']);
        $sendmail = str_replace('$1', $from[0], trim($_PM_['core']['sendmail']));
        $sm = new Protocol_Client_Sendmail($sendmail);
        $moep = $sm->get_last_error();
        if ($moep) {
            $sm = false;
            $WP_return .= $moep.'\n';
        }
    } elseif ($_PM_['core']['send_method'] == 'smtp') {
        if (empty($WP_send['cc'])) {
            $WP_send['cc'] = '';
        }
        if (empty($WP_send['bcc'])) {
            $WP_send['bcc'] = '';
        }

        $to = explode(', ', gather_addresses(array(trim($WP_send['to']), trim($WP_send['cc']), trim($WP_send['bcc']))));
        $from = Format_Parse_Email::parse_email_address($WP_send['from'], 0, true);
        $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtpsecurity, $smtpselfsign);
        $server_open = $sm->open_server($from[0], $to, filesize($tmp_path.$msg_id));
        if (!$server_open) {
            $WP_return .= str_replace('<br />', '\n', str_replace(LF, '', $sm->get_last_error())).'\n';
            $sm = false;
        }
    }
    if ($sm) {
        $tmpout = fopen($tmp_path.$msg_id, 'r');
        //
        // Dropping BCC from outgoing mails
        //
        if ($_PM_['core']['send_method'] == 'smtp') {
            // This methodology is only supported with SMTP right now, since Sendmail parses the mail header on its own
            // We practically read in the whole mail header at once, parse out the BCC lines and stuff the $sm handle with it
            foreach (explode(CRLF, Format_Parse_Email::sanitize_mailheader($tmpout, 'BCC')) as $line) {
                $line = trim($line, CRLF);
                $sm->put_data_to_stream($line.CRLF);
            }
        }
        // This will catch up with the body (or start at the beginning in case of Sendmail)
        while (!feof($tmpout) && false !== ($line = fgets($tmpout))) {
            $sm->put_data_to_stream($line);
        }
        // Make sure, there's a finalising CRLF.CRLF
        $sm->finish_transfer();
        if ($_PM_['core']['send_method'] == 'sendmail') {
            if (!$sm->close()) {
                $WP_return .= $WP_msg['nomailsent'].' ('.$sm->get_last_error().')\n';
                $success = false;
            } else {
                $success = true;
            }
        }
        if ($_PM_['core']['send_method'] == 'smtp') {
            if ($sm->check_success()) {
                $success = true;
            } else {
                $WP_return .= $WP_msg['nomailsent'].' ('.$sm->get_last_error().')\n';
                $success = false;
            }
            $sm->close();
        }
        if ($success) {
            $DB->set_user_accounting('email', date('Ym'), $_SESSION['phM_uid'], 1);
        	sendJS(array('statusmessage' => $WP_msg['EmailSavingMail']
        	       ,'url' => PHP_SELF.'?WP_do=save&'.give_passthrough(1).'&l=send_email&h=core&msg_id='.$msg_id), 1, 1);
        } else {
        	sendJS(array('error' => $WP_return), 1, 1);
        }
    } else {
    	sendJS(array('error' => $WP_msg['nomailsent'].': '.$WP_return), 1, 1);
    }
}
// Step III: Place a copy into sent objects of the current mail storgae handler
if (isset($_REQUEST['WP_do']) && 'save' == $_REQUEST['WP_do']) {
    $is_unread = false;
    $msg_id = basename($_REQUEST['msg_id']); // Prevent "../" attacks on this script
    $mytmp_ser = $tmp_path.$msg_id.'.ser';
    $mytmpfile = $tmp_path.$msg_id;
    $WP_send = unserialize(file_get_contents($mytmp_ser));

    $save_handler_path = $_PM_['path']['handler'].'/'.$save_handler.'/api.php';
    require_once($save_handler_path);
    $save_class = 'handler_'.$save_handler.'_api';
    $SAVE = new $save_class($_PM_, $_SESSION['phM_uid']);
    $profile = $Acnt->getProfileFromAccountId($_SESSION['phM_uid'], $WP_send['from_profile']);
    $profinfo = $Acnt->getAccount($_SESSION['phM_uid'], false, $profile);
    if (isset($_REQUEST['draft']) && $_REQUEST['draft']) {
        $fallback = $profFolder = $defaultFolder = 'drafts';
    } elseif (isset($_REQUEST['template']) && $_REQUEST['template']) {
        $fallback = $profFolder = $defaultFolder = 'templates';
    } else {
        $fallback = $profFolder = $defaultFolder = 'sent';
        if (isset($WP_send['orig']) && $WP_send['orig'] && !empty($_PM_['core']['replysamefolder'])
                && (!isset($WP_send['replymode']) || !in_array($WP_send['replymode'], array('draft', 'template')))) {
            $profFolder = $SAVE->get_folder_from_item($WP_send['orig']);
        } else {
            $profFolder = $profinfo[$profFolder];
        }
    }
    if (0 != $profFolder) {
        $folderInfo = $SAVE->get_folder_info($profFolder);
        if (false === $folderInfo || empty($folderInfo)) {
            $profFolder = false;
        }
    } else {
        if ($profinfo['acctype'] == 'pop3') {
            $profile = 0;
        }
        $profFolder = $SAVE->get_system_folder($fallback, $profile);
        $folderInfo = $SAVE->get_folder_info($profFolder);
        if (false === $folderInfo || empty($folderInfo)) {
            $profFolder = false;
        }
    }
    if ($profFolder && (intval($folderInfo['type']/10)*10 == 10)) {
        $status = $is_unread ? 0 : 1;
        $SAVE->save_item(array('folder_id' => $profFolder, 'status' => $status), false, $mytmpfile);
    } else {
        $whereToSave = ($profFolder) ? false : $defaultFolder;
        $SAVE->parse_and_save_mail($mytmpfile, $whereToSave, $profFolder, false, 'mail', $is_unread);
    }
    // Mark original mail as answred / forwarded
    if (isset($WP_send['replymode']) && $WP_send['replymode'] && isset($WP_send['orig']) && $WP_send['orig']) {
        $SAVE->mail_set_status($WP_send['orig'], 1, ('re' == $WP_send['replymode']), ('re' != $WP_send['replymode']));
    }
    unlink($mytmpfile);
    unlink($mytmp_ser);
    // Drafts get removed after sending them, only templates get kept
    if (isset($WP_send['orig']) && $WP_send['orig'] && isset($WP_send['replymode']) && $WP_send['replymode'] == 'draft') {
        $SAVE->mail_delete($WP_send['orig'], false, false, true);
    }
    // Customize this
    sendJS(array('done' => 1), 1, 1);
}

// Special case: Send out a return receipt (either automatic or after asking the user)
if (isset($_REQUEST['WP_do']) && 'send_dsn' == $_REQUEST['WP_do']) {
    // Allow us to fulfill the request even if the user decides to select another mail inbetween
    if (function_exists('ignore_user_abort')) {
        ignore_user_abort(true);
    }
    require_once($_PM_['path']['lib'].'/message.encode.php');
    $mime_boundary = '_---_next_part_--_'.time().'==_';
    $msg_id = uniqid(time().'.');
    $WP_send = array();
    $WP_return = '';
    // Wash given strings
    foreach (array('to', 'osubj', 'omsgid', 'odate', 'dispo', 'prof') as $k) {
        if (!isset($_REQUEST[$k])) {
            continue;
        }
        $WP_send[$k] = trim(phm_stripslashes(un_html($_REQUEST[$k])));
    }
    if (!empty($_PM_['core']['fix_smtp_host'])) {
        $smtp_host    = $_PM_['core']['fix_smtp_host'];
        $smtp_port    = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587;
        $smtp_user    = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
        $smtp_pass    = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
        $smtpsecurity = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : 'AUTO';
        $smtpselfsign = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : 'false';
    }

    if (!empty($WP_send['prof'])) {
        $connect = $Acnt->getAccount($_SESSION['phM_uid'], false, $WP_send['prof']);
        $WP_send['from'] = $connect['address'];
        // If we have SMTP connection data for this profile, put this into session, else try to use the default
        // connection data
        if (isset($connect['smtpserver']) && $connect['smtpserver']) {
            $smtp_host = $connect['smtpserver'];
            $smtp_port = ($connect['smtpport']) ? $connect['smtpport'] : 25;
            $smtp_user = $connect['smtpuser'];
            $smtp_pass = $connect['smtppass'];
            $smtpsecurity = $connect['smtpsec'];
            $smtpselfsign = $connect['smtpallowselfsigned'];
        }
    }
    // Create Message ID
    if (isset($WP_send['from']) && $WP_send['from']) {
        $addi = Format_Parse_Email::parse_email_address($WP_send['from'], 0, false);
        $dom = strstr($addi[0], '@');
    } elseif(!empty($_SERVER['SERVER_NAME'])) {
        $dom = '@'.$_SERVER['SERVER_NAME'];
    } else { // This is failsafe only
        $dom = '@phlymail.local';
    }
    $tpl = new phlyTemplate($_PM_['path']['conf'].'/dsn_success.phml');
    $tpl->assign(array
            ('from' => $WP_send['from']
            ,'to' => $WP_send['to']
            ,'orig_subject' => encode_1522_line_q($WP_send['osubj'], 'g', 'UTF-8')
            ,'orig_msgid' => $WP_send['omsgid']
            ,'orig_date' => $WP_send['odate']
            ,'msgid' => $msg_id.$dom
            ,'date' => date('r')
            ,'boundary' => $mime_boundary
            ,'mailer' => 'phlyMail (http://phlymail.com)'
            ));
    if (isset($WP_send['dispo']) && 'automatic' == $WP_send['dispo']) {
        $tpl->assign_block('automatic');
    } else {
        $tpl->assign_block('manual');
    }

    if ($_PM_['core']['send_method'] == 'sendmail') {
        $from = Format_Parse_Email::parse_email_address($WP_send['from']);
        $sendmail = str_replace('$1', $from[0], trim($_PM_['core']['sendmail']));
        $sm = new Protocol_Client_Sendmail($sendmail);
        $moep = $sm->get_last_error();
        if ($moep) {
            $sm = false;
            $WP_return .= $moep.LF;
        }
    } elseif($_PM_['core']['send_method'] == 'smtp') {
        $to = explode(', ', gather_addresses(array(trim($WP_send['to']), trim($WP_send['cc']), trim($WP_send['bcc']))));
        $from = Format_Parse_Email::parse_email_address($WP_send['from'], 0, true);
        $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtpsecurity, $smtpselfsign);
        $server_open = $sm->open_server($from[0], $to);
        if (!$server_open) {
            $WP_return .= str_replace('<br />', LF, $sm->get_last_error()).LF;
            $sm = false;
        }
    }
    if ($sm) {
        foreach (explode(LF, $tpl->get_content()) as $line) {
            $sm->put_data_to_stream(trim($line).CRLF);
        }
        // Make sure, there's a finishing CRLF.CRLF
        $sm->finish_transfer();
        if ($_PM_['core']['send_method'] == 'sendmail') {
            if (!$sm->close()) {
                $WP_return .= $WP_msg['nomailsent'].' ('.$sm->get_last_error().')'.LF;
                $success = false;
            } else {
                $success = true;
            }
        }
        if ($_PM_['core']['send_method'] == 'smtp') {
            if ($sm->check_success()) {
                $success = true;
            } else {
                $WP_return .= $WP_msg['nomailsent'].' ('.$sm->get_last_error().')'.LF;
                $success = false;
            }
            $sm->close();
        }
    }
    sendJS((!$success) ? array('error' => $WP_return) : array('done' => 1), 1, 1);
}
