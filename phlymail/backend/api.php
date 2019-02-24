<?php
/**
 * phlyMail API - allows external access to internal functions
 * @package phlyMail Nahariya 4.0+ default branch
 * @subpackage Core system
 * @copyright 2008-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.0 2015-08-10
 */
// Try to disable any execution time limits imposed - no effect under SAFE_MODE!
@set_time_limit(0);
define('_IN_PHM_', true);
// Which PHP version do we use?
if (!version_compare(phpversion(), '5.2.1', '>=')) {
    header('Content-Type: text/plain; charset=utf-8');
    die('phlyMail requires PHP 5.2.1 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
}
chdir('../');
@set_include_path(get_include_path().PATH_SEPARATOR.realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR));
// Load necessary files
$_PM_ = [];
foreach (['defaults.ini.php', 'choices.ini.php'] as $choices) {
    if (!file_exists($choices) || !is_readable($choices)) {
        continue;
    }
    $_PM_ = array_replace_recursive($_PM_, parse_ini_file($choices, true));
}
if (empty($_PM_)) {
    die('Error initializing core, defaults.ini.php not found?');
}
// Comaptibility layer
if (!version_compare(phpversion(), '6.0.0', '>=')) {
    require_once($_PM_['path']['lib'].'/compat.5.x.php');
}
require($_PM_['path']['lib'].'/init.backend.php');
if (!empty($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] != 'system') {
    Debug::off();
    if (isset($_PM_['core']['debugging_level']) && 'disabled' != $_PM_['core']['debugging_level']) {
        Debug::on();
    }
}

class phlymail_api
{
    public $error = false;

    private $uid = false;
    private $user = false;
    private $groups = false;
    private $privs = false;
    private $handler = false;
    private $instance = false;

    public function __construct()
    {
        $this->DB = &$GLOBALS['DB'];
        $this->_PM_ = &$GLOBALS['_PM_'];
        $this->WP_msg = &$GLOBALS['WP_msg'];
    }

    public function auth($user, $pass)
    {
        $_PM_ = &$this->_PM_;
        $WP_msg = &$this->WP_msg;
        // Init vars
        $WPloggedin = 0;
        $still_blocked = 0;
        // Is the system offline?
        $maintained = (!isset($_PM_['core']['online_status']) || !$_PM_['core']['online_status']) ? 1 : 0;
        $unusable = 0;
        $countonfail = (isset($_PM_['auth']['countonfail']) && $_PM_['auth']['countonfail']) ? $_PM_['auth']['countonfail'] : false;
        $waitonfail = (isset($_PM_['auth']['waitonfail']) && $_PM_['auth']['waitonfail']) ? $_PM_['auth']['waitonfail'] : 5;
        $lockonfail = (isset($_PM_['auth']['lockonfail']) && $_PM_['auth']['lockonfail']) ? $_PM_['auth']['lockonfail'] : 10;
        $show_register = (isset($_PM_['auth']['show_register']) && $_PM_['auth']['show_register']) ? $_PM_['auth']['show_register'] : false;
        $use_extauth = (isset($_PM_['extauth']) && isset($_PM_['extauth']['module'])) ? $_PM_['extauth']['module'] : false;

        list ($uid, $realpass) = $this->DB->authenticate($user, $pass, null, null, $this->_PM_['auth']['system_salt']);
        // External authentication is enabled
        if ($use_extauth) {
            require_once($_PM_['path']['extauth'].'/'.basename($use_extauth).'.php');
            list ($extauthed, $extauth_err) = extauth($user, $pass, $_PM_);
            if ($extauthed == 1) {
                // User does not exist in phlyMail
                if (!$uid && $_PM_['extauth']['create_user'] == 1) {
                    $_PM_['handlers'] = parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php');
                    // Reduce optional specific languages (like de_Du) to the base language (e.g. de)
                    if (strstr($_PM_['core']['language'], '_')) {
                        $_PM_['core']['language'] = substr($_PM_['core']['language'], 0, strpos($_PM_['core']['language'], '_'));
                    }
                    // Create user in DB
                    $uid = $this->DB->add_user(array('username' => $user, 'password' => $pass, 'email' => '', 'active' => '1', 'salt' => $_PM_['auth']['system_salt']));
                    // Tell handlers about it
                    foreach ($_PM_['handlers'] as $handler => $active) {
                        // Only look for active handlers
                        if (!$active) {
                            continue;
                        }
                        // Look for an installation API call available
                        $call = 'handler_'.$handler.'_configapi';
                        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                            continue;
                        }
                        require_once $_PM_['path']['handler'].'/'.$handler.'/configapi.php';
                        if (!in_array('create_user', get_class_methods($call))) {
                            continue;
                        }
                        $API = new $call($_PM_, $uid);
                        $state = $API->create_user();
                        if (!$state) {
                            $this->error = $API->get_errors();
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
                        $res = $cAPI->create_user($uid, $user, $pass, '', $acctype ? $acctype : null);
                        unset($cAPI);
                        if (!$res) {
                            echo $DB->error();
                        }
                    }
                }
            } elseif ($extauthed == 0) {
                $DB->set_usrfail($uid);
                $uid = false;
            } else {
                $uid = false;
                $this->error = $extauth_err;
            }
        }
        if (!isset($GLOBALS['WP_l'])
                || ($GLOBALS['WP_l'][6] && $GLOBALS['WP_l'][6] < time())) {
            $uid = false;
        }
        if (!isset($GLOBALS['WP_l'])
                || ($GLOBALS['WP_l'][3] != chr(255).chr(255).chr(255) && $DB->get_usercount() > $GLOBALS['WP_l'][3])) {
            $uid = false;
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
            if ($pass) {
                if (md5($pass) != $realpass) {
                    if ($still_blocked != 1) {
                        $DB->set_usrfail($uid);
                    }
                    $uid = false;
                }
            } else {
                $uid = false;
            }
        }
        // --- Custom Logging of logins and login attempts
        if (isset($_PM_['logging']['log_sysauth']) && $_PM_['logging']['log_sysauth']) {
            $logpath = $_PM_['path']['logging'].'/sysauth/'.preg_replace_callback('!\%(\w)!', create_function ('$s', 'return date($s[1]);'), $_PM_['logging']['basename']);
            basics::create_dirtree(dirname($logpath));
            $logstring = date('Y-m-d H:i:s').' ';
            if ($maintained == 1) {
                $logstring .= '9 '.$user;
            } elseif ($unusable == 1) {
                $logstring .= '2 '.$user;
            } elseif ($still_blocked == 1) {
                $logstring .= '3 '.$user;
            } elseif ($uid != false) {
                $logstring .= '1 '.$user;
            } else {
                $logstring .= '0 "'.$user.'" '.getenv('REMOTE_ADDR');
            }
            file_put_contents($logpath, $logstring.LF, FILE_APPEND);

        }
        // ---
        if (1 == $maintained) {
            $this->error = $WP_msg['currentlyoffline'];
        } elseif (1 == $unusable) {
            $this->error = $WP_msg['stilldisabled'];
        } elseif ($still_blocked == 1) {
            $this->error = $WP_msg['stillblocked'];
        } elseif ($uid != false) {
            $this->uid = $uid;
            $this->username = $user;
            // Has groups managemnt, so read in assigend groups
            if (isset($DB->features['groups']) && $DB->features['groups']) {
                $this->groups = $DB->get_usergrouplist($uid);
            } else {
                $this->groups = array(0);
            }
            // Has privileges for users, read in available privileges
            if (isset($DB->features['permissions']) && $DB->features['permissions']) {
                $this->privs = $DB->get_user_permissions($uid);
                $this->privs['all'] = false;
            } else {
                $this->privs['all'] = true;
            }
            $WPloggedin = 1;
            $DB->set_logintime($uid);
            return true;
        }
        return false;
    }

    /**
     * Initiates an instance of the desired handler's API within this class.
     * The instance is returned to allow subsequent calls to methods of the API.
     *
     * @param string $hdl  One of the installed handlers of phlyMail
     */
    public function open_handler($handler)
    {
        if (false === $this->uid) {
            return false;
        }
        $handler = basename($handler);
        if (!file_exists($this->_PM_['path']['handler'].'/'.$handler.'/api.php')) {
            return false;
        }
        require_once $this->_PM_['path']['handler'].'/'.$handler.'/api.php';
        $call = 'handler_'.$handler.'_api';
        $this->instance = new $call($this->_PM_, $this->uid);
        if (is_object($this->instance)) {
            $this->handler = $handler;
            return $this->instance;
        }
        return false;
    }
}
