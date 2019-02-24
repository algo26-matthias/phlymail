<?php
/**
 * phlyMail Config authentication module
 * @package phlyMail Nahariya 4.1+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2002-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.7 2013-01-23 
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

// Which PHP version do we use?
if (!version_compare(phpversion(), '5.3.0', '>=')) {
    header('Content-Type: text/plain; charset=utf-8');
    die('phlyMail requires PHP 5.3.0 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
}

$WPloggedin = 0;
$still_blocked = 0;
$special = isset($_REQUEST['special']) ? $_REQUEST['special'] : false;
// Anchoring point for DB update script
if (!isset($_REQUEST['ignore_runonce']) && file_exists('runonce.php') && is_readable('runonce.php')) {
    require('runonce.php');
    $state = @unlink('runonce.php');
    if (!$state) {
        $error = ('de' == $WP_conf['language'])
                ? 'Update der Intallation erfolgreich. Bitte löschen Sie runonce.php aus dem Hautpverzeichnis von phlyMail.'
                : 'Installation update successfull. Please delete runonce.php from the main folder of phlyMail.';
        $ignore_runonce = 1;
        $_PM_['core']['pass_through'][] = 'ignore_runonce';
    }
}

// This installation needs a salt
if (empty($_PM_['auth']['system_salt'])) {
    $config = parse_ini_file('./choices.ini.php', true);
    $config['auth']['system_salt'] = uniqid();
    basics::save_config('./choices.ini.php', $config, true);
}

// Support on demand OTP
if (isset($_REQUEST['give_otp'])) {
    header('Content-Type: application/json; charset=utf-8');
    $method = 'plain';
    if (isset($_REQUEST['un'])) {
        $method = $DB->getadminpwtype($_REQUEST['un']);
    }
    $json = array('method' => empty($method) ? 'plain' : $method);
    if ($method == 'digest') {
        $json['otp'] = $_PM_['auth']['system_salt'];
    } elseif ($method == 'md5') {
        $json['otp'] = $_SESSION['otp'] = md5($_SERVER['REMOTE_ADDR']).time().getmypid();
    } else {
        $json['otp'] = 'otp';
    }
    echo json_encode($json);
    exit;
}
//
if (isset($_REQUEST['user']) && isset($_REQUEST['pass'])) {
    list ($uid, $authSuccess) = (!empty($_REQUEST['digest']))
            ? $DB->adm_auth($_REQUEST['user'], null, null, $_REQUEST['digest'], $_PM_['auth']['system_salt'])
            : $DB->adm_auth($_REQUEST['user'], $_REQUEST['pass'], null, null, $_PM_['auth']['system_salt']);
    $failure = $DB->get_admfail($uid);
    // Automatisches Verblassen von Fehleingaben
    if ($failure['fail_count'] < $_PM_['auth']['countonfail']) {
        if ($failure['fail_time'] < (date('U') - 600)) $DB->reset_admfail($uid);
    } else {
        if ($failure['fail_time'] < (date('U') - ($_PM_['auth']['lockonfail'] * 60))) {
            $DB->reset_admfail($uid);
        } else {
            $still_blocked = 1;
        }
    }
    if (!$authSuccess) {
        if ($still_blocked != 1) $DB->set_admfail($uid);
        $uid = false;
    }
    if ($still_blocked == 1) $error = $WP_msg['stillblocked'];
    elseif ($uid != false) {
        $_SESSION['phM_uid'] = $uid;
        $_SESSION['phM_username'] = $_REQUEST['user'];
        $_SESSION['phM_adminsession'] = 'true';
        $WPloggedin = 1;
        $DB->set_admlogintime($uid);
        $PHM = $DB->get_admdata($uid);
        unset($PHM['password']);
        list($read, $write) = unserialize(base64_decode($PHM['permissions']));
        // Permissions of that administrative user
        $_SESSION['phM_perm_read']  = (isset($read) && is_array($read))   ? array_flip($read)  : array();
        $_SESSION['phM_perm_write'] = (isset($write) && is_array($write)) ? array_flip($write) : array();
        // Might be a super admin
        $_SESSION['phM_superroot']  = (isset($PHM['is_root']) && $PHM['is_root'] == 'yes');

        $_SESSION['phM_cookie'] = md5(uniqid());
        setcookie('phlyMail_Session', $_SESSION['phM_cookie'], null, null, null, PHM_FORCE_SSL);

        header('Location: '.PHP_SELF.'?'.give_passthrough(1));
        exit();
    } else {
        $error = $WP_msg['wrongauth'];
        sleep($_PM_['auth']['waitonfail']);
    }
}

if ($WPloggedin != 1) {
    $action = 'auth';
    $WP_once['load_tpl_auth'] = 'do.it!';
    if ('lost_pw' == $special || !empty($_REQUEST['setpwtok'])) {
        if (!empty($_REQUEST['setpwtok'])) {
            $userinfo = $DB->getadminbytoken($_REQUEST['setpwtok']);
            if (empty($userinfo)) {
                $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.forgotten.tpl');
                $tpl->fill_block('okay', array
                        ('msg_okay' => $WP_msg['notknown']
                        ,'msg_back' => $WP_msg['backLI']
                        ,'login' => htmlspecialchars(PHP_SELF.'?'.give_passthrough())
                        ));
                return;
            } elseif (!empty($_REQUEST['newpw1'])) {
                if (empty($_REQUEST['newpw2'])) {
                    $error = $WP_msg['SuDefinePW'];
                } elseif (strlen($_REQUEST['newpw1']) < 5) {
                    $error = $WP_msg['AuthPWTooShort'];
                } elseif ($_REQUEST['newpw1'] != $_REQUEST['newpw2']) {
                    $error = $WP_msg['SuPW1notPW2'];
                } elseif ($_REQUEST['newpw1'] == $_REQUEST['newpw2']) {
                    $DB->upd_admin(array
                            ('username' => $userinfo['username']
                            ,'salt' => $_PM_['auth']['system_salt']
                            ,'password' => $_REQUEST['newpw1']
                            ,'uid' => $userinfo['uid']
                            ));
                    $DB->removeadmintoken($userinfo['uid']);
                    $DB->reset_admfail($userinfo['uid']);

                    $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.forgotten.tpl');
                    $tpl->fill_block('okay', array
                            ('msg_okay' => $WP_msg['AuthPWSet']
                            ,'msg_back' => $WP_msg['backLI']
                            ,'login' => htmlspecialchars(PHP_SELF.'?'.give_passthrough())
                            ));
                    return;
                }
            }
            $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.forgotten.tpl');
            $t_en = $tpl->get_block('enternew');
            $t_en->assign(array
                    ('PHP_SELF' => htmlspecialchars(PHP_SELF.'?setpwtok='.$_REQUEST['setpwtok'].'&'.give_passthrough())
                    ,'msg_pw1' => $WP_msg['sysnewpass']
                    ,'msg_pw2' => $WP_msg['syspass2']
                    ,'msg_lost_pw' => $WP_msg['AuthSetPW']
                    ,'msg_enter' => $WP_msg['AuthSetPWEnterPWs']
                    ,'msg_send' => $WP_msg['AuthLostSend']
                    ));
            if (!empty($error)) {
                $t_en->fill_block('error', 'error', $error);
            }
            $tpl->assign('enternew', $t_en);
            return;
        }
        if (!empty($_REQUEST['user'])) {
            $userinfo = $DB->getadminauthinfo($_REQUEST['user']);
            if (!$userinfo) {
                $error = $WP_msg['notknown'];
            } elseif (!$userinfo['externalemail'] || !strstr($userinfo['externalemail'], '@')) {
                $userinfo = false;
                $error = $WP_msg['AuthLostNoEmail'];
            }
            if (!empty($userinfo)) {
                $optin_token = $DB->setadmintoken($userinfo['uid'], 172800);
                auth_mail_password(array
                        ('subject' => $WP_msg['AuthLostMailSubj']
                        ,'body' => $WP_msg['AuthLostMailBody']
                        ,'link' => PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/'.basename(PHP_SELF).'?setpwtok='.$optin_token
                        ,'email' => $userinfo['externalemail'])
                        );
                $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.forgotten.tpl');
                $tpl->fill_block('okay', array
                        ('msg_okay' => $WP_msg['AuthPWSent']
                        ,'msg_back' => $WP_msg['backLI']
                        ,'login' => htmlspecialchars(PHP_SELF.'?'.give_passthrough())
                        ));
                return;
            }
        }
        $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.forgotten.tpl');
        $t_q = $tpl->get_block('query');
        $t_q->assign(array
                ('PHP_SELF' => htmlspecialchars(PHP_SELF.'?whattodo=check&special=lost_pw&'.give_passthrough())
                ,'msg_popuser' => $WP_msg['popuser']
                ,'msg_lost_pw' => $WP_msg['AuthLostPW']
                ,'msg_enter' => $WP_msg['AuthLostEnterName']
                ,'msg_send' => $WP_msg['AuthLostSend']
                ,'user' => isset($_REQUEST['user']) ? phm_entities($_REQUEST['user']) : ''
                ));
        if (!empty($error)) $t_q->fill_block('error', 'error', $error);
        $tpl->assign('query', $t_q);
        return;
    }
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/auth.login.tpl');
    $tpl->assign(array
            ('PHP_SELF' => PHP_SELF.'?'.give_passthrough()
            ,'msg_authenticate' => $WP_msg['authenticate']
            ,'msg_popuser' => $WP_msg['popuser']
            ,'msg_poppass' => $WP_msg['poppass']
            ,'msg_login' => $WP_msg['login']
            ,'msg_lost_pw' => $WP_msg['AuthLostPW']
            ));
    if (!empty($error)) {
        $tpl->fill_block('error', 'error', $error);
    }
}

function auth_mail_password($input)
{
    global $_PM_; // Since we need it virtuallay everywhere in this function
    require_once($_PM_['path']['lib'].'/message.encode.php');

    $providername = (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') ? $_PM_['core']['provider_name'] : 'phlyMail';
    $body = str_replace('$2', $input['link'], str_replace('$1', $providername, phm_stripslashes($input['body'])));
    $from = isset($_PM_['core']['systememail']) && $_PM_['core']['systememail'] ? $_PM_['core']['systememail'] : $input['email'];
    $to = $input['email'];
    $subject = str_replace('$1', $providername, phm_stripslashes($input['subject']));

    if (preg_match('![\x80-\xff]!', $body)) {
        $bodylines = explode(LF, $body);
        $body = '';
        foreach ($bodylines as $value) $body .= phm_quoted_printable_encode($value.CRLF);
        $body_qp = 'true';
    }
    $header = create_messageheader(array('from' => $from, 'to' => $to, 'subject' => $subject), null, 'utf-8');
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
        if (!isset($_PM_['core']['fix_smtp_host']) || !$_PM_['core']['fix_smtp_host']) return;
        $LE = CRLF;
        $from = Format_Parse_Email::parse_email_address($from);
        $smtp_host = $_PM_['core']['fix_smtp_host'];
        $smtp_port = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587; // 25;
        $smtp_user = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
        $smtp_pass = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
        $smtp_sec  = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : false;
        $smtp_self = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : false;
        $sm = new Protocol_Client_SMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_sec, $smtp_self);
        $sm->open_server($from[0], $to[0]);
    }
    if ($sm) {
        $sm->put_data_to_stream($header);
        if (isset($body_qp) && 'true' == $body_qp) {
            $sm->put_data_to_stream('MIME-Version: 1.0'.$LE);
            $sm->put_data_to_stream('Content-Type: text/plain; charset=utf-8'.$LE);
            $sm->put_data_to_stream('Content-Transfer-Encoding: quoted-printable'.$LE);
        }
        $sm->put_data_to_stream($LE);
        $sm->put_data_to_stream($body);
        $sm->finish_transfer();
        $sm->close();
    }
}
