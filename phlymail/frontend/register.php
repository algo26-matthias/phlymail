<?php
/**
 * register.php -> Sign up functions for new users
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.5 2015-02-04 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$error = false;
if ($show_register
        && (empty($provisionedExpiration) || $provisionedExpiration > time())
		&& $DB->get_usercount() <= $provisionedUsers) {
	if (isset($_REQUEST['username']) && $_REQUEST['username']) {
		if ($DB->checkfor_username($_REQUEST['username'])) $error .= $WP_msg['SuUserExists'];
		if ('' == $_REQUEST['password']) $error .= $WP_msg['SuDefinePW'];
		if ('' == $_REQUEST['email']) $error .= $WP_msg['SuDefineEmail'];
		if ($_REQUEST['password'] != $_REQUEST['password2']) $error .= $WP_msg['SuPW1notPW2'];
		if (!$error) {
            // Reduce optional specific languages (like de_Du) to the base language (e.g. de)
            if (strstr($_PM_['core']['language'], '_')) {
                $_PM_['core']['language'] = substr($_PM_['core']['language'], 0, strpos($_PM_['core']['language'], '_'));
            }
			$uid = $DB->add_user(array
			     ('username' => $_REQUEST['username']
			     ,'password' => $_REQUEST['password']
			     ,'salt' => $_PM_['auth']['system_salt']
			     ,'email' => $_REQUEST['email']
			     ,'firstname' => $_REQUEST['firstname']
			     ,'lastname' => $_REQUEST['lastname']
			     ,'tel_private' => $_REQUEST['tel_private']
			     ,'tel_business' => $_REQUEST['tel_business']
			     ,'cellular' => $_REQUEST['cellular']
			     ,'fax' => $_REQUEST['fax']
			     ,'www' => $_REQUEST['www']
			     ,'active' => '0'
			     ));
			// Keep track of activated handlers
			$_PM_['handlers'] = parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php');
            foreach ($_PM_['handlers'] as $handler => $active) {
                // Only look for active handlers
                if (!$active) continue;
                // Look for an installation API call available
                if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) continue;
                require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
                $call = 'handler_'.$handler.'_configapi';
                if (!in_array('create_user', get_class_methods($call))) continue;
                $API = new $call($_PM_, $uid);
                $state = $API->create_user();
                if (!$state) {
                	$error = $API->get_errors();
                	$DB->delete_user($PHM['username']);
                	break;
                }
                unset($API);
            }
            if ($state) {
                // Run ConfigAPI script for backend interoperation
                require_once($_PM_['path']['admin'].'/lib/configapi.class.php');
                $API = new configapi($_PM_, $DB);
                $API->create_user($uid, $_REQUEST['username'], $_REQUEST['password'], $_REQUEST['email']);
                unset($API);
            }
            // Groups may only be set, if the driver supports it - and they were defined by the admin
            if (isset($DB->features['groups']) && $DB->features['groups'] && isset($_PM_['core']['reg_defaultgroups'])) {
                $DB->set_usergrouplist($uid, explode(',', $_PM_['core']['reg_defaultgroups']));
            }
			// Finally mail the user and admin according to the settings chosen by the admin(s)
			auth_mail_newuser(array
					('username' => $_REQUEST['username'], 'IP' => getenv('REMOTE_ADDR')
					,'email' => $_REQUEST['email'], 'systememail' => $_PM_['core']['systememail']
					,'activationlink' => 'http://'.$_SERVER['HTTP_HOST'].PHP_SELF.'?special=activate&uid='.$uid
					));
		}
	}
	if (isset($uid) && $uid) {
        // Rückmeldetext abhängig davon, ob die Mail, wenn sie gesendet wird, einen Freischaltlink enthält
        $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.regdone.tpl');
        $tpl->assign(array
                ('message' => 'mail' == $Activate ? $WP_msg['AuthRegDoneMail'] : $WP_msg['AuthRegDone']
                ,'PHP_SELF' => PHP_SELF
                ,'passthrough' => give_passthrough(1)
                ));
	} else {
	    $baseLang = $_PM_['core']['language'];
        if (strstr($_PM_['core']['language'], '_')) {
            $baseLang = substr($_PM_['core']['language'], 0, strpos($_PM_['core']['language'], '_'));
        }
        if (file_exists($_PM_['path']['admin'].'/messages/'.$baseLang.'.php')) {
            require($_PM_['path']['admin'].'/messages/'.$baseLang.'.php');
        } else {
            require($_PM_['path']['admin'].'/messages/en.php');
        }
        $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.register.tpl');
        $tpl->assign(array
                ('PHP_SELF' => PHP_SELF
                ,'passthrough' => give_passthrough(1)
                ,'msg_register' => $WP_msg['AuthRegister']
                ,'msg_popuser' => $WP_msg['popuser']
                ,'msg_syspass' => $WP_msg['syspass']
                ,'msg_syspass2' => $WP_msg['syspass2']
                ,'msg_email' => $WP_msg['email']
                ,'msg_www' => $WP_msg['WWW']
                ,'msg_firstname' => $WP_msg['Firstname']
                ,'msg_lastname' => $WP_msg['Surname']
                ,'msg_tel_private' => $WP_msg['TelPersonal']
                ,'msg_tel_business' => $WP_msg['TelBusiness']
                ,'msg_fax' => $WP_msg['Fax']
                ,'msg_cellular' => $WP_msg['Cellular']
                ,'msg_cancel' => $WP_msg['cancel']
                ,'username' => (isset($_REQUEST['username']) && $_REQUEST['username']) ? htmlspecialchars($_REQUEST['username']) : ''
                ,'email' => (isset($_REQUEST['email']) && $_REQUEST['email']) ? htmlspecialchars($_REQUEST['email']) : ''
                ,'firstname' => (isset($_REQUEST['firstname']) && $_REQUEST['firstname']) ? htmlspecialchars($_REQUEST['firstname']) : ''
                ,'lastname' => (isset($_REQUEST['lastname']) && $_REQUEST['lastname']) ? htmlspecialchars($_REQUEST['lastname']) : ''
                ,'tel_private' => (isset($_REQUEST['tel_private']) && $_REQUEST['tel_private']) ? htmlspecialchars($_REQUEST['tel_private']) : ''
                ,'tel_business' => (isset($_REQUEST['tel_business']) && $_REQUEST['tel_business']) ? htmlspecialchars($_REQUEST['tel_business']) : ''
                ,'cellular' => (isset($_REQUEST['cellular']) && $_REQUEST['cellular']) ? htmlspecialchars($_REQUEST['cellular']) : ''
                ,'fax' => (isset($_REQUEST['fax']) && $_REQUEST['fax']) ? htmlspecialchars($_REQUEST['fax']) : ''
                ,'www' => (isset($_REQUEST['www']) && $_REQUEST['www']) ? htmlspecialchars($_REQUEST['www']) : ''
                ));
        if ($error) $tpl->fill_block('error', 'error', $error);
    }
} else {
    $special = false;
}

function auth_mail_newuser($input)
{
    global $_PM_; // Since we need it virtuallay everywhere in this function
    require_once($_PM_['path']['lib'].'/message.encode.php');

    $GLOBALS['Activate'] = 'admin'; // Wie wird die Freischaltung vorgenommen? Default: Admin
    if (strlen($input['systememail']) > 0) {
        // Step 1 - inform user
        if (isset($_PM_['core']['reg_mailuser']) && $_PM_['core']['reg_mailuser']) {
            $suf = fopen($_PM_['path']['conf'].'/regmail.usertext.wpop', 'r');
            $body = '';
            while ($line = fgets($suf, 1024)) $body .= str_replace(CRLF, LF, $line);
            fclose($suf);
            if (preg_match('!\$3!', $body)) $GLOBALS['Activate'] = 'mail'; // Freischaltung per Link
            $body = str_replace(array('$3', '$2', '$1'), array($input['activationlink'], $input['username'], $input['IP']), phm_stripslashes($body));
            $from = $input['systememail'];
            $to = $input['email'];
            $subject = phm_stripslashes($_PM_['core']['reg_mailuser_subj']);

            if (preg_match('![\x80-\xff]!', $body)) {
                $mime_encoding = 1;
                $bodylines = explode(LF, $body);
                $body = '';
                foreach ($bodylines as $key => $value) $body .= phm_quoted_printable_encode($value.CRLF);
                $body_qp = 'true';
            }
            $header = create_messageheader(array('from' => $from, 'to' => $to, 'subject' => $subject));
            $to = Format_Parse_Email::parse_email_address($to);
            if ($_PM_['core']['send_method'] == 'sendmail') {
                $header = str_replace(CRLF, LF, $header);
                $body = str_replace(CRLF, LF, $body);
                $LE = LF;
                $sendmail = preg_replace('!\ \-t!', '', $_PM_['core']['sendmail']).' -t';
                $sm = new Protocol_Client_Sendmail($sendmail);
                if ($moep = $sm->get_last_error() && $moep) return;
            }
            if ($_PM_['core']['send_method'] == 'smtp') {
                $LE = CRLF;
                $from = Format_Parse_Email::parse_email_address($from);
                if (isset($_PM_['core']['fix_smtp_host']) && $_PM_['core']['fix_smtp_host']) {
                    $smtp_host = $_PM_['core']['fix_smtp_host'];
                    $smtp_port = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587; //25;
                    $smtp_user = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
                    $smtp_pass = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
                    $smtp_sec  = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : false;
                    $smtp_self  = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : false;
                } else {
                    return;
                }
                $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_sec, $smtp_self);
                $sm->open_server($from[0], $to[0]);
            }
            if ($sm) {
                $sm->put_data_to_stream($header);
                $sm->put_data_to_stream('MIME-Version: 1.0'.$LE);
                $sm->put_data_to_stream('Content-Type: text/plain; charset=utf-8'.$LE);
                if (isset($body_qp) && 'true' == $body_qp) {
                    $sm->put_data_to_stream('Content-Transfer-Encoding: quoted-printable'.$LE);
                }
                $sm->put_data_to_stream($LE);
                $sm->put_data_to_stream($body);
                $sm->finish_transfer();
                $sm->close();
            }
        }
        // Step 2 - inform admin
        if (isset($_PM_['core']['reg_mailadm']) && $_PM_['core']['reg_mailadm']) {
            $body = '';
            $suf = fopen($_PM_['path']['conf'].'/regmail.admtext.wpop', 'r');
            while ($line = fgets($suf, 1024)) $body .= str_replace(CRLF, LF, $line);
            fclose($suf);
            $body = str_replace('$3', $input['email'], str_replace('$2', $input['username'], str_replace('$1', $input['IP'], phm_stripslashes($body))));
            $from = $input['email'];
            $to = $input['systememail'];
            $subject = phm_stripslashes($_PM_['core']['reg_mailadm_subj']);
            if (preg_match('/[\x80-\xff]/', $body))  {
                $bodylines = explode(LF, $body);
                $body = '';
                foreach ($bodylines as $value) $body .= phm_quoted_printable_encode($value.CRLF);
                $body_qp = 'true';
            }
            $header = create_messageheader(array('from' => $from, 'to' => $to, 'subject' => $subject));
            $to = Format_Parse_Email::parse_email_address($to);
            if ($_PM_['core']['send_method'] == 'sendmail') {
                $header = str_replace(CRLF, LF, $header);
                $body = str_replace(CRLF, LF, $body);
                $LE = LF;
                $sendmail = preg_replace('!\ \-t!', '', $_PM_['core']['sendmail']).' -t';
                $sm = new Protocol_Client_Sendmail($sendmail);
                if ($moep = $sm->get_last_error() && $moep) return;
            }
            if ($_PM_['core']['send_method'] == 'smtp') {
                $LE = CRLF;
                $from = Format_Parse_Email::parse_email_address($from);
                if (isset($_PM_['core']['fix_smtp_host']) && $_PM_['core']['fix_smtp_host']) {
                    $smtp_host = $_PM_['core']['fix_smtp_host'];
                    $smtp_port = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587; //25;
                    $smtp_user = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
                    $smtp_pass = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
                    $smtp_sec  = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : false;
                    $smtp_self  = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : false;
                } else {
                    return;
                }
                $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_sec, $smtp_self);
                $sm->open_server($from[0], $to[0]);
            }
            if ($sm) {
                $sm->put_data_to_stream($header);
                $sm->put_data_to_stream('MIME-Version: 1.0'.$LE);
                $sm->put_data_to_stream('Content-Type: text/plain; charset=utf-8'.$LE);
                if (isset($body_qp) && 'true' == $body_qp) {
                    $sm->put_data_to_stream('Content-Transfer-Encoding: quoted-printable'.$LE);
                }
                $sm->put_data_to_stream($LE);
                $sm->put_data_to_stream($body);
                $sm->finish_transfer();
                $sm->close();
            }
        }
    }
}
