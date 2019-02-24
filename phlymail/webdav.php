<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.5mod1 2015-08-10
 */
// Try to disable any execution time limits imposed - no effect under SAFE_MODE!
@set_time_limit(0);

define('_IN_PHM_', true);
define('PHM_NO_SESSION', 1); // This advises init.frontend to not use any session stuff
// Setup session related directives
@ini_set('url_rewriter.tags', '');
@ini_set('arg_separator.output', '&amp;');
@set_include_path(get_include_path().PATH_SEPARATOR.__DIR__);
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

$authSuccess = false;
$uid = false;
$Digest = new Sabre_HTTP_DigestAuth();
$Digest->setRealm($_PM_['auth']['system_salt']);
$Digest->init();
$username = $Digest->getUserName();
if (empty($username)) {
    $Digest->requireLogin();
    die('Please login to use this service');
} else {
    $userinfo = $DB->getuserauthinfo($username);
    if (!empty($userinfo)) {
        $authSuccess = $Digest->validateA1($userinfo['pw_digesta1']);
        if (false !== $authSuccess) {
            $uid = $userinfo['uid'];
        }
    }
}

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
//

$webDavServer = new Sabre_DAV_Server(new phlyDAV_Tree());

// Allow simple viewing via Browser
$webDavServer->addPlugin(new Sabre_DAV_Browser_Plugin()); # FIXME build nicer templates or disable on release
// Try to guess, what content type we are dealing with
$webDavServer->addPlugin(new Sabre_DAV_Browser_GuessContentType()); # FIXME extend that!
// Allow mounting information to be passed to the client
$webDavServer->addPlugin(new Sabre_DAV_Mount_Plugin());
// Strip out unneccessary temp files
$webDavServer->addPlugin(new phlyDAV_TempFileFilter($_PM_['path']['tmp']));
// Allow locking
$lockBackend = new phlyDAV_Locks();
$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
$webDavServer->addPlugin($lockPlugin);
// And go ...
$webDavServer->exec();
