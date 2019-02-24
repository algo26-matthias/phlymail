<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @subpackage Import / Export
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.0 2015-08-10
 */

// Try to disable any execution time limits imposed - no effect under SAFE_MODE!
@set_time_limit(0);

define('_IN_PHM_', true);
define('PHM_NO_SESSION', 1); // This advises init.frontend to not use any session stuff
// Setup session related directives
@ini_set('url_rewriter.tags', '');
@ini_set('arg_separator.output', '&amp;');
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
require($_PM_['path']['lib'].'/init.frontend.php');

$still_blocked = 0;
$maintained = (!isset($_PM_['core']['online_status']) || !$_PM_['core']['online_status']) ? 1 : 0;
$countonfail = (isset($_PM_['auth']['countonfail']) && $_PM_['auth']['countonfail']) ? $_PM_['auth']['countonfail'] : false;
$waitonfail = (isset($_PM_['auth']['waitonfail']) && $_PM_['auth']['waitonfail']) ? $_PM_['auth']['waitonfail'] : 5;
$lockonfail = (isset($_PM_['auth']['lockonfail']) && $_PM_['auth']['lockonfail']) ? $_PM_['auth']['lockonfail'] : 10;

if ($maintained) {
    header('HTTP/1.0 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    die('System offline');
}

//
// Handle eXternal No Auth requests, which allow to pipe certain external requests
// to internal modules without authentication. Serious caution must be taken, that
// this does not open any security holes, since it could easily lead to exposal
// of private information.
//
if (isset($_REQUEST['XNA'])) {
    $dbXNA = new DB_Controller_XNA();
    $xnaInfo = $dbXNA->getUuid($_REQUEST['XNA']);
    if (false === $xnaInfo || !isset($xnaInfo['handler']) || !isset($xnaInfo['load'])) {
        header('HTTP/1.0 400 Bad Request');
        header('Status: 400 Bad Request');
        die('Missing or wrong XNA parameter');
    }
    $HDL = $xnaInfo['handler'];
    $load = $xnaInfo['load'];
    $action = $xnaInfo['action'];
    $XNA = $xnaInfo['uuid'];

} else { // Normal case of authenticated access to the system
    $HDL = !empty($_REQUEST['handler'])
            ? basename($_REQUEST['handler'])
            : (!empty($_REQUEST['h']) ? basename($_REQUEST['h']) : false);

    //
    // Check for HTTP AUTH BASIC
    //
    if (isset($_SERVER['PHP_AUTH_USER'])) { // PHP as Apache module, AUTH environment variables populated
        $phpAuthUser = $_SERVER['PHP_AUTH_USER'];
        $phpAuthPass = $_SERVER['PHP_AUTH_PW'];
    } elseif (isset($_GET['RewriteFakeAuth'])) { // Fallback via mod_rewrite
        // Check for the HTTP authentication string in $_GET
        if (preg_match('/Basic\s+(.*)$/i', $_GET['RewriteFakeAuth'], $auth)) {
            $auth = explode(':', base64_decode($auth[1])); // Auth info is base64 encoded
            $phpAuthUser = isset($auth[0]) ? $auth[0] : false;
            $phpAuthPass = isset($auth[1]) ? $auth[1] : false;
        }
    } else {
        header('WWW-Authenticate: Basic realm="phlyMail Web API"');
        header('HTTP/1.0 401 Unauthorized');
        die('Please login to use this service');
    }

    list ($uid, $authSuccess) = $DB->authenticate($phpAuthUser, $phpAuthPass, null, null, $_PM_['auth']['system_salt']);
    if (!$uid) {
        header('HTTP/1.0 403 Forbidden');
        header('Status: 403 Forbidden');
        die('Unknown user');
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
    if (!$authSuccess) {
        if ($still_blocked != 1) {
            $DB->set_usrfail($uid);
        }
        $uid = false;
    }
    if ($still_blocked) {
        header('HTTP/1.0 403 Forbidden');
        header('Status: 403 Forbidden');
        die('Too many failed logins');
    }
    if (!$uid) {
        header('HTTP/1.0 403 Forbidden');
        header('Status: 403 Forbidden');
        die('Username or password wrong');
    }
    define('PHM_API_UID', $uid); // Read by the handlers

    // Apply permission checks, read settings for user
    if (isset($DB->features['permissions']) && $DB->features['permissions']) {
        $_phM_privs = $DB->get_user_permissions($uid);
        $_phM_privs['all'] = false;
    } else {
        $_phM_privs['all'] = true;
    }
}

if (!empty($HDL)) {
    if (file_exists($_PM_['path']['handler'].'/'.$HDL.'/webapi.php')) {
        require_once($_PM_['path']['handler'].'/'.$HDL.'/webapi.php');
    } else {
        header('HTTP/1.0 415 Unsupported Media Type');
        header('Status: 415 Unsupported Media Type');
        die('Wrong type of service');
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    header('Status: 400 Bad Request');
    die('Missing parameter');
}
exit;