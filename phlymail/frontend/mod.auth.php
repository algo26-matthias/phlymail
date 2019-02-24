<?php
/**
 * mod.auth.php -> phlyMail 4.5.0+ authentication module
 * @package phlyMail Nahariya 4.5.0+ Branch MessageCenter
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.7mod1 2015-04-20 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Which PHP version do we use?
if (!version_compare(phpversion(), '5.3.0', '>=')) {
    header('Content-Type: text/plain; charset=utf-8');
    die('phlyMail requires PHP 5.3.0 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
}

// This installation needs a salt
if (empty($_PM_['auth']['system_salt'])) {
    $config = parse_ini_file('./choices.ini.php', true);
    $config['auth']['system_salt'] = uniqid();
    basics::save_config('./choices.ini.php', $config, true);
}

if (isset($_REQUEST['notmobile'])) {
    // Have the session know it
    $_SESSION['notmobile'] = 1;
    // Make it permanent, don't bother user with mobile on that device any more
    setcookie('notmobile', 1, time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}

// Init vars
$WPloggedin = 0;
$still_blocked = 0;
// Is the system offline?
$maintained = (!isset($_PM_['core']['online_status']) || !$_PM_['core']['online_status']) ? 1 : 0;
$unusable = 0;
$special = isset($_REQUEST['special']) ? $_REQUEST['special'] : false;
if (!empty($_SESSION['phM_2fa_uid'])) {
    $special = 'handle_2fa';
}
$countonfail = (isset($_PM_['auth']['countonfail']) && $_PM_['auth']['countonfail']) ? $_PM_['auth']['countonfail'] : false;
$waitonfail = (isset($_PM_['auth']['waitonfail']) && $_PM_['auth']['waitonfail']) ? $_PM_['auth']['waitonfail'] : 5;
$lockonfail = (isset($_PM_['auth']['lockonfail']) && $_PM_['auth']['lockonfail']) ? $_PM_['auth']['lockonfail'] : 10;
$show_register = (isset($_PM_['auth']['show_register']) && $_PM_['auth']['show_register']) ? $_PM_['auth']['show_register'] : false;
$use_extauth = (isset($_PM_['extauth']) && isset($_PM_['extauth']['module'])) ? $_PM_['extauth']['module'] : false;
// Support on demand OTP
if (isset($_REQUEST['give_otp'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (1 == $maintained) {
        echo '{"error":"'.addcslashes($WP_msg['currentlyoffline'], '"').'"}';
        exit;
    }
    $method = 'plain';
    if (!$use_extauth && isset($_REQUEST['un'])) {
        $method = $DB->getuserpwtype($_REQUEST['un']);
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
// Single Sign On support. Circumvents the regular login procedure
if (isset($_SESSION['phM_SSO']) && $_SESSION['phM_SSO']) {
    exit; // Branch not done yet
    // Session cookie
    if (!isset($_PM_['auth']['session_cookie']) || $_PM_['auth']['session_cookie']) {
        $_SESSION['phM_cookie'] = md5(uniqid());
        setcookie('phlyMail_Session', $_SESSION['phM_cookie'], null, null, null, PHM_FORCE_SSL);
    }
    header('Location: '.PHP_SELF.'?'.$urladd.give_passthrough(1));
    exit();
}
if (isset($_REQUEST['user']) && isset($_REQUEST['pass'])) {
    list ($uid, $authSuccess) = (!empty($_REQUEST['digest']))
            ? $DB->authenticate($_REQUEST['user'], null, null, $_REQUEST['digest'], $_PM_['auth']['system_salt'])
            : $DB->authenticate($_REQUEST['user'], $_REQUEST['pass'], null, null, $_PM_['auth']['system_salt']);
    // External authentication is enabled
    if ($use_extauth) {
        $userpass = isset($_REQUEST['secure']) && $_REQUEST['secure'] ? $_REQUEST['secure'] : $_REQUEST['pass'];
        require_once($_PM_['path']['extauth'].'/'.basename($use_extauth).'.php');
        list ($extauthed, $extauth_err) = extauth($_REQUEST['user'], $userpass, $_PM_);
        if ($extauthed == 1) {
            // User does not exist in phlyMail
            if (!$uid && $_PM_['extauth']['create_user'] == 1) {
                $_PM_['handlers'] = parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php');
                // Reduce optional specific languages (like de_Du) to the base language (e.g. de)
                if (strstr($_PM_['core']['language'], '_')) {
                    $_PM_['core']['language'] = substr($_PM_['core']['language'], 0, strpos($_PM_['core']['language'], '_'));
                }
                // Create user in DB
                $uid = $DB->add_user(array('username' => $_REQUEST['user'], 'password' => $userpass, 'email' => '', 'active' => '1', 'salt' => $_PM_['auth']['system_salt']));
                // Tell handlers about it
                foreach ($_PM_['handlers'] as $handler => $active) {
                    // Only look for active handlers
                    if (!$active) {
                        continue;
                    }
                    // Look for an installation API call available
                    if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                        continue;
                    }
                    require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
                    $call = 'handler_'.$handler.'_configapi';
                    if (!in_array('create_user', get_class_methods($call))) {
                        continue;
                    }
                    $API = new $call($_PM_, $uid);
                    $state = $API->create_user();
                    if (!$state) {
                        $error = $API->get_errors();
                        $DB->delete_user($PHM['username']);
                        $uid = false;
                        break;
                    }
                    unset($API);
                }
                // Tell backend API about it
                if ($uid) {
                    $acctype = ($use_extauth == 'imap' || $use_extauth == 'pop3') ? $use_extauth : false;
                    require_once($_PM_['path']['admin'].'/lib/configapi.class.php');
                    $cAPI = new configapi($_PM_, $DB);
                    $res = $cAPI->create_user($uid, $_REQUEST['user'], $userpass, '', $acctype ? $acctype : null);
                    unset($cAPI);
                    if (!$res) {
                        echo $DB->error();
                    }
                }
            } elseif ($uid) {
                // Update just in case
                $orig_userdata = $DB->get_usrdata($uid);
                $orig_userdata['password'] = $userpass;
                $orig_userdata['salt'] = $_PM_['auth']['system_salt'];
                $DB->upd_user($orig_userdata);
            }
        } elseif ($extauthed == 0) {
            $DB->set_usrfail($uid);
            $uid = false;
        } else {
            $uid = false;
            $error = $extauth_err;
        }
    }
    $failure = $DB->get_usrfail($uid);
    // Automatisches Verblassen von Fehleingaben
    if ($failure['fail_count'] < $countonfail) {
        if ($failure['fail_time'] < (date('U') - 600)) {
            $DB->reset_usrfail($uid);
        }
    } else {
        if ($failure['fail_time'] < (date('U') - ($lockonfail * 60))) {
            $DB->reset_usrfail($uid);
        } else {
            $still_blocked = 1;
        }
    }
    if (!$use_extauth) {
        if (!$authSuccess) {
            if ($still_blocked != 1) {
                $DB->set_usrfail($uid);
            }
            $uid = false;
        }
    }
    // --- Custom Logging of logins and login attempts
    if (isset($_PM_['logging']['log_sysauth']) && $_PM_['logging']['log_sysauth']) {
        $logpath = $GLOBALS['_PM_']['path']['logging'].'/sysauth/'.preg_replace_callback('!\%(\w)!', create_function ('$s', 'return date($s[1]);'), $_PM_['logging']['basename']);
        if (basics::create_dirtree(dirname($logpath))) {
	        $logstring = date('Y-m-d H:i:s').' ';
	        if ($maintained == 1) {
	            $logstring .= '9 '.$_REQUEST['user'];
	        } elseif ($unusable == 1) {
	            $logstring .= '2 '.$_REQUEST['user'];
	        } elseif ($still_blocked == 1) {
	            $logstring .= '3 '.$_REQUEST['user'];
	        } elseif ($uid != false) {
	            $logstring .= '1 '.$_REQUEST['user'];
	        } else {
	            $logstring .= '0 "'.$_REQUEST['user'].'" '.getenv('REMOTE_ADDR');
	        }
	        file_put_contents($logpath, $logstring.LF, FILE_APPEND);
	    }
    }
    // ---
    if (1 == $maintained) {
        $error = $WP_msg['currentlyoffline'];
    } elseif (1 == $unusable) {
        $error = $WP_msg['stilldisabled'];
    } elseif ($still_blocked == 1) {
        $error = $WP_msg['stillblocked'];
    } elseif ($uid != false) {
        $_SESSION['phM_uid'] = $uid;
        $_SESSION['phM_username'] = $_REQUEST['user'];
        $_SESSION['phM_shares'] = (!empty($DB->features['shares']));
        // Has groups managemnt, so read in assigend groups
        if (isset($DB->features['groups']) && $DB->features['groups']) {
        	$_SESSION['phM_groups'] = $DB->get_usergrouplist($uid);
        } else {
        	$_SESSION['phM_groups'] = array(0);
        }
        // Has privileges for users, read in available privileges
        if (isset($DB->features['permissions']) && $DB->features['permissions']) {
            $_SESSION['phM_privs'] = $DB->get_user_permissions($uid);
            $_SESSION['phM_privs']['all'] = false;
        } else {
            $_SESSION['phM_privs']['all'] = true;
        }
        $WPloggedin = 1;
        $DB->set_logintime($_SESSION['phM_uid']);
        $urladd = '';
        if (isset($_REQUEST['orig_url'])) {
            $urladd = parse_url(urldecode($_REQUEST['orig_url']));
            $urladd = (isset($urladd['query'])) ? $urladd['query'].'&' : '';
        }
        // Session cookie
        if (!isset($_PM_['auth']['session_cookie']) || $_PM_['auth']['session_cookie']) {
            $_SESSION['phM_cookie'] = md5(uniqid());
            setcookie('phlyMail_Session', $_SESSION['phM_cookie'], null, null, null, PHM_FORCE_SSL);
        }
        // Check, whether there's two-factor auth configured for that user — if so, take him/her to the authentication screen
        $userChoices = $DB->get_usr_choices($_SESSION['phM_uid']);
        if (!empty($userChoices['2fa']['mode'])
                && $userChoices['2fa']['mode'] != 'none') {
            $_SESSION['phM_2fa_uid'] = $_SESSION['phM_uid'];
            unset($_SESSION['phM_uid']);
            $_SESSION['phM_orig_url'] = $urladd;
            header('Location: '.PHP_SELF.'?'.give_passthrough(1));
        } else {
            header('Location: '.PHP_SELF.'?'.$urladd.give_passthrough(1));
        }
        exit();
    } else {
        if (!isset($error) || !$error) {
            $error = $WP_msg['wrongauth'];
        }
        sleep($waitonfail);
    }
}

if ($WPloggedin != 1) {
    $action = 'auth';
    $_PM_['temp']['load_tpl_auth'] = 'do.it!';

    if ('handle_2fa' == $special && !empty($_SESSION['phM_2fa_uid'])) {
        $loggedIn = false;
        $userChoices = $DB->get_usr_choices($_SESSION['phM_2fa_uid']);

        $fetaureSmsActive = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
        if ($fetaureSmsActive) {
            $fetaureSmsActive = (isset($_PM_['core']['sms_active']) && $_PM_['core']['sms_active']);
        }
        if ($fetaureSmsActive) {
            $fetaureSmsActive = ($userChoices['core']['sms_active'] && $userChoices['core']['sms_active']);
        }

        if (!empty($userChoices['2fa']['mode'])
                && $userChoices['2fa']['mode'] != 'none') {
            if ($userChoices['2fa']['mode'] == 'u2f'
                    && !empty($userChoices['2fa']['u2f_serial'])) {
                if (!empty($_REQUEST['2fa_otp'])) {
                    @include_once 'Auth/Yubico.php';
                    if (class_exists('Auth_Yubico')) {
                        $yubi = new Auth_Yubico($_PM_core['2fa']['yubi_client_id'], $_PM_core['2fa']['yubi_client_key']);
                        $yubi->verify($_REQUEST['2fa_otp']);
                        if (PEAR::isError($auth)) {
                            $WP_return = $auth->getMessage();
                        } else {
                            if ($userChoices['2fa']['u2f_serial'] == substr($_REQUEST['2fa_otp'], 0, 12)) {
                                $loggedIn = true;
                            } else {
                                $WP_return = $WP_msg['2FaModeYubikeyUnregisteredToken'];
                            }
                        }
                    }
                }
                if (!$loggedIn) {
                    $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.2fa.u2f.tpl');
                }
            } elseif ($userChoices['2fa']['mode'] == 'sms'
                    && !empty($userChoices['2fa']['sms_to'])
                    && $fetaureSmsActive) {
                if (!empty($_REQUEST['2fa_otp'])) {
                    if ($_REQUEST['2fa_otp'] == $_SESSION['phM_login_code']) {
                        unset($_SESSION['phM_login_code']);
                        $loggedIn = true;
                    } else {
                        $WP_return = $WP_msg['2FaModeSmsELoginCodeInvalid'];
                    }
                }
                if (!$loggedIn) {
                    $usegwpath = $_PM_['path']['msggw'] . '/' . $_PM_['core']['sms_use_gw'];
                    $gwcredentials = $_PM_['path']['conf'] . '/msggw.' . $_PM_['core']['sms_use_gw'] . '.ini.php';
                    require_once($usegwpath . '/phm_shortmessage.php');
                    $GW = new phm_shortmessage($usegwpath, $gwcredentials);

                    $_SESSION['phM_login_code'] = SecurePassword::generate(6, false, STRONGPASS_DECIMALS);
                    $status = $GW->send_sms(
                            array(
                                    'from' => $userChoices['2fa']['sms_to'],
                                    'to' => $userChoices['2fa']['sms_to'],
                                    'text' => decode_utf8(str_replace('$token$', $_SESSION['phM_login_code'], $WP_msg['2FaModeSmsLoginCodeText']))
                            ),
                            'sms'
                            );

                    $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.2fa.sms.tpl');
                }
            } else {
                $loggedIn = true; // Not configured yet, let though without 2FA
            }
        }

        if ($loggedIn === true) {
            $_SESSION['phM_uid'] = $_SESSION['phM_2fa_uid'];
            $urladd = $_SESSION['phM_orig_url'];
            unset($_SESSION['phM_2fa_uid']);
            unset($_SESSION['phM_orig_url']);

            header('Location: '.PHP_SELF.'?'.$urladd.give_passthrough(1));
            exit;
        } elseif (isset($tpl)) {
            $tpl->assign(array(
                        'PHP_SELF' => htmlspecialchars(PHP_SELF.'?special=handle_2fa&'.give_passthrough())
                        ));
        }
    }

    if ('lost_pw' == $special || !empty($_REQUEST['setpwtok'])) {
        if (!empty($_REQUEST['setpwtok'])) {
            $userinfo = $DB->getuserbytoken($_REQUEST['setpwtok']);
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
                    $DB->upd_user(array
                            ('uid' => $userinfo['uid']
                            ,'username' => $userinfo['username']
                            ,'salt' => $_PM_['auth']['system_salt']
                            ,'password' => $_REQUEST['newpw1']
                            ));
                    $DB->removeusertoken($userinfo['uid']);
                    $DB->reset_usrfail($userinfo['uid']);

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
            $userinfo = $DB->getuserauthinfo($_REQUEST['user']);
            if (!$userinfo) {
                $error = $WP_msg['notknown'];
            } else {
                $usrdata = $DB->get_usrdata($userinfo['uid']);
                if ((!$usrdata['externalemail'] || !strstr($usrdata['externalemail'], '@'))
                        && (!$usrdata['email'] || !strstr($usrdata['email'], '@'))) {
                    $userinfo = false;
                    $error = $WP_msg['AuthLostNoEmail'];
                }
            }
            if (!empty($userinfo)) {
                $optin_token = $DB->setusertoken($userinfo['uid'], 172800);

                $email = ($usrdata['externalemail'] && strstr($usrdata['externalemail'], '@')) ? $usrdata['externalemail'] : $usrdata['email'];
                auth_mail_password(array
                        ('subject' => $WP_msg['AuthLostMailSubj']
                        ,'body' => $WP_msg['AuthLostMailBody']
                        ,'link' => PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/?setpwtok='.$optin_token
                        ,'email' => $email)
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
        if (isset($error) && $error) {
            $t_q->fill_block('error', 'error', $error);
        }
        $tpl->assign('query', $t_q);
        return;
    }
    if ('activate' == $special) {
    	$username = (isset($_REQUEST['username']) && $_REQUEST['username']) ? $_REQUEST['username'] : false;
    	$password = (isset($_REQUEST['password']) && $_REQUEST['password']) ? $_REQUEST['password'] : false;
        if ($username && $password && isset($_REQUEST['uid'])) {
            if ($DB->activate($_REQUEST['uid'], $username, $password, $_PM_['auth']['system_salt'])) {
                header ('Location: '.PHP_SELF.'?user='.$username.'&pass='.$password.'&'.give_passthrough(1));
                exit();
            } else {
                $error = $WP_msg['RegActFail'];
                unset($password);
            }
        }
        if (isset($_REQUEST['uid'])) {
            $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.actnow.tpl');
            $tpl->assign(array(
                    'PHP_SELF' => PHP_SELF,
                    'passthrough' => give_passthrough(1),
                    'msg_activate' => $WP_msg['AuthActivate'],
                    'msg_popuser' => $WP_msg['popuser'],
                    'msg_syspass' => $WP_msg['syspass'],
                    'msg_cancel' => $WP_msg['cancel'],
                    'username' => $username,
                    'uid' => phm_entities($_REQUEST['uid']),
                    'msg_actnow' => $WP_msg['RegActNow']
                    ));
            if (isset($error) && $error) {
                $tpl->fill_block('error', 'error', $error);
            }
        }
    }
    // Invoke registration module
    if ('register_me' == $special) {
        require_once($_PM_['path']['frontend'].'/register.php');
    }
    //
    if (!$special) {
        //
        // Admin has defined a failed login URI
        // Warning: Right now this is not used in mobile context
        //
        if (!empty($_PM_['core']['failed_redir_uri']) && !empty($error) && !defined('PHM_MOBILE')) {
            $url = preg_replace('!\r|\n|\t!', '', $_PM_['core']['failed_redir_uri']);
            if (!preg_match('!^http(s)?\://!', $url)) {
                $url = 'http://'.$url;
            }
            $url .= (false !== strstr($url, '?') ? '&' : '?').'error='.urlencode($error);
            header('Location: '.$url);
            exit;
        }

        // - Try to detect a mobile handset
        // - Do not try to redirect to it, when we already are there or the user deliberately redirected to the desktop client
        // - Thank You! to http://detectmobilebrowsers.com/
        // - Incorporating tablet recognition, for now we want the mobile interface on those, too
        if (!defined('PHM_MOBILE') && empty($_SESSION['notmobile'])
                && (!empty($_PM_['core']['mobile_autodetect']))) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/android|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(ad|hone|od)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino|playbook|silk/i', $useragent)
                    || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))
                    ) {
                $url = preg_replace('!(index)\.([a-zA-Z0-9]+)$!', 'm.$2', PHP_SELF);
                header('Location: '.$url);
                exit;
            }
        }

        $tpl = new phlyTemplate($_PM_['path']['templates'].'auth.login.tpl');
        $tpl->assign(array
                ('PHP_SELF' => htmlspecialchars(PHP_SELF.'?'.give_passthrough())
                ,'msg_authenticate' => $WP_msg['authenticate']
                ,'msg_popuser' => $WP_msg['popuser']
                ,'msg_poppass' => $WP_msg['poppass']
                ,'msg_login' => $WP_msg['login']
                ,'msg_lost_pw' => $WP_msg['AuthLostPW']
                ,'user' => isset($_REQUEST['user']) ? phm_entities($_REQUEST['user']) : ''
                ));
        if (!empty($error)) {
            $tpl->fill_block('error', 'error', $error);
        } elseif (!empty($_REQUEST['error'])) {
            $tpl->fill_block('error', 'error', phm_entities($_REQUEST['error']));
        }
        if ($show_register && !$maintained) {
            $tpl->fill_block('register', array('PHP_SELF' => htmlspecialchars(PHP_SELF.'?'.give_passthrough()), 'msg_reg_now' => $WP_msg['RegNow']));
        }
        // On external auth we should transfer the plain password instead of a MD5 hash
        if ($use_extauth) {
            $tpl->assign_block('extauth_on');
        }
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
        foreach ($bodylines as $value) {
            $body .= phm_quoted_printable_encode($value.CRLF);
        }
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
        $moep = $sm->get_last_error();
        if ($moep) {
            return;
        }
    }
    if ($_PM_['core']['send_method'] == 'smtp') {
        if (!isset($_PM_['core']['fix_smtp_host']) || !$_PM_['core']['fix_smtp_host']) {
            return;
        }
        $LE = CRLF;
        $from = Format_Parse_Email::parse_email_address($from);
        $smtp_host = $_PM_['core']['fix_smtp_host'];
        $smtp_port = ($_PM_['core']['fix_smtp_port']) ? $_PM_['core']['fix_smtp_port'] : 587; //25;
        $smtp_user = (isset($_PM_['core']['fix_smtp_user'])) ? $_PM_['core']['fix_smtp_user'] : false;
        $smtp_pass = (isset($_PM_['core']['fix_smtp_pass'])) ? $_PM_['core']['fix_smtp_pass'] : false;
        $smtp_sec  = (isset($_PM_['core']['fix_smtp_security'])) ? $_PM_['core']['fix_smtp_security'] : false;
        $smtp_self = (isset($_PM_['core']['fix_smtp_allowselfsigned'])) ? $_PM_['core']['fix_smtp_allowselfsigned'] : false;
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
