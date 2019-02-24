<?php
/**
 * Configure tool for phlyMail 4.0.0+
 *
 * @package phlyMail Nahariya 4.0+ default branch
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.4mod1 2015-08-10
 */
// Which PHP version do we use?
if (!version_compare(phpversion(), '5.4.0', '>=')) {
    header('Content-Type: text/plain; charset=utf-8');
    die('phlyMail requires PHP 5.4.0 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
}
define('_IN_PHM_', true);
// Do not use cookies for session management
@ini_set('session.use_cookies', 'Off');
@ini_set('session.use_only_cookies', 'Off');
@ini_set('session.use_trans_sid','Off');
@ini_set('url_rewriter.tags', '');
@ini_set('arg_separator.output','&amp;');
@set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);
@session_cache_limiter('nocache');
// Load required files
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

// Allow config/ to be renamed or moved to another place
if (!isset($_PM_['path']['admin'])) {
    $_PM_['path']['admin'] = 'config';
}
$_PM_['path']['admin'] = preg_replace('!/$!', '', $_PM_['path']['admin']);
define('CONFIGPATH', $_PM_['path']['admin']);

// Comaptibility layer
if (!version_compare(phpversion(), '6.0.0', '>=')) {
    require_once($_PM_['path']['lib'].'/compat.5.x.php');
}
session_start();
require_once(CONFIGPATH.'/lib/init.script.php');
if (!empty($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] != 'system') {
    Debug::off();
    if (isset($_PM_['core']['debugging_level']) && 'disabled' != $_PM_['core']['debugging_level']) {
        Debug::on();
    }
}
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$screen = isset($_REQUEST['screen']) ? $_REQUEST['screen'] : '';
if ('logout' == $action) {
    require_once(CONFIGPATH.'/lib/logout.php');
}

// Greifen alle Setup-Module drauf zu
$link_base = PHP_SELF.'?'.give_passthrough(1).'&action=';
if (!isset($_SESSION['phM_uid']) || !isset($_SESSION['phM_username']) || !isset($_SESSION['phM_adminsession'])) {
    $screen = $action = false;
    require_once(CONFIGPATH.'/mod.auth.php');
} else {
    require_once(CONFIGPATH.'/lib/menu.php');
}
if (isset($_REQUEST['module']) && file_exists(CONFIGPATH.'/modules/'.basename($_REQUEST['module']).'/config.php')) {
    require_once(CONFIGPATH.'/modules/'.basename($_REQUEST['module']).'/config.php');
} else {
    switch ($action) {
        case '':
        case 'home':
        case 'menu':         require_once(CONFIGPATH.'/setup.home.php');         break;
        case 'diag':         require_once(CONFIGPATH.'/setup.diag.php');         break;
        case 'advanced':     require_once(CONFIGPATH.'/setup.advanced.php');     break;
        case 'general':      require_once(CONFIGPATH.'/setup.general.php');      break;
        case 'security':     require_once(CONFIGPATH.'/setup.security.php');     break;
        case 'AU':           require_once(CONFIGPATH.'/setup.au.php');           break;
        case 'users':        require_once(CONFIGPATH.'/setup.users.php');        break;
        case 'junk':         require_once(CONFIGPATH.'/setup.junk.php');         break;
        case 'handlers':     require_once(CONFIGPATH.'/setup.handlers.php');     break;
        case 'regnow':       require_once(CONFIGPATH.'/setup.regnow.php');       break;
        case 'driver':       require_once(CONFIGPATH.'/setup.driver.php');       break;
        case 'config':       require_once(CONFIGPATH.'/setup.config.php');       break;
        case 'config.users': require_once(CONFIGPATH.'/setup.config.users.php'); break;
        case 'config.api':   require_once(CONFIGPATH.'/setup.config.api.php');   break; // MC
        case 'gcontacts':    require_once(CONFIGPATH.'/setup.gcontacts.php');    break; // MC
        case 'gconedit':     require_once(CONFIGPATH.'/setup.gconedit.php');     break; // MC
        case 'gconexchange': require_once(CONFIGPATH.'/setup.gconexchange.php'); break; // MC
        case 'ggroups':      require_once(CONFIGPATH.'/setup.ggroups.php');      break; // MC
        case 'quotas':       require_once(CONFIGPATH.'/setup.quotas.php');       break;
        case 'groups':       require_once(CONFIGPATH.'/setup.groups.php');       break; // MC
        case 'sms':          require_once(CONFIGPATH.'/setup.sms.php');          break;
    }
}

// Use gzip
if (!empty($_PM_['core']['gzip_config'])) {
    ob_start('ob_gzhandler');
}
if ((!isset($pure) || $pure != 'true') && $action != 'saug') {
    if (!isset($tpl)) {
        $tpl = null;
    }
    require_once(CONFIGPATH.'/lib/skins.php');
} elseif (isset($tpl)) {
    if (is_object($tpl)) {
        $tpl->display();
    } else {
        echo $tpl;
    }
}
