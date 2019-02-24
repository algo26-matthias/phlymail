<?php
/**
 * phlyMail, an advanced and sophisticated PIM / Groupware solution
 *
 * This is the master page for frontend access. All module actions are handled
 * and dispatched from here. Besides that, this file builds the environment,
 * all modules rely upon.
 *
 * @package phlyMail Nahariya 4.0+ default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0mod1 2015-08-10
 */

// Try to disable any execution time limits imposed - no effect under SAFE_MODE!
@set_time_limit(0);
// This constant is required for all modules to run
define('_IN_PHM_', true);
// Setup session related directives
@ini_set('session.use_cookies', 'Off');
@ini_set('session.use_only_cookies', 'Off');
@ini_set('session.use_trans_sid', 'Off');
@ini_set('url_rewriter.tags', '');
@ini_set('arg_separator.output', '&amp;');
@set_include_path(get_include_path().PATH_SEPARATOR.__DIR__);
@session_cache_limiter('nocache');
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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
$pure = isset($_REQUEST['pure']) ? $_REQUEST['pure'] : false;

$load = isset($_REQUEST['l']) ? basename(trim($_REQUEST['l'])) : false;
$HDL = isset($_REQUEST['h']) ? basename(trim($_REQUEST['h'])) : false;
$folder = isset($_REQUEST['f']) ? basename(trim($_REQUEST['f'])) : false;
$item = isset($_REQUEST['i']) ? basename(trim($_REQUEST['i'])) : false;
// Allow classic URIs to be used
if (false === $load && !empty($_REQUEST['load'])) {
    $load = basename(trim($_REQUEST['load']));
}
if (false === $HDL && !empty($_REQUEST['handler'])) {
    $HDL = basename(trim($_REQUEST['handler']));
}
// Done

// Comaptibility layer
if (!version_compare(phpversion(), '6.0.0', '>=')) {
    require_once($_PM_['path']['lib'].'/compat.5.x.php');
}
//
// Handle eXternal No Auth requests, which allow to pipe certain external requests
// to internal modules without authentication. Serious caution must be taken, that
// this does not open any security holes, since it could easily lead to exposal
// of private information.
//
if (isset($_REQUEST['XNA'])) {
    define('PHM_NO_SESSION', 1); // This advises init.frontend to not use any session stuff
    require($_PM_['path']['lib'].'/init.frontend.php');
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

//
// Decoupling external URLs from the user's session, whose information might
// travel with the Referer: HHTP header, thus landing in the target server's
// log files and thus be isused by a malicious server admin.
//
} elseif (isset($_REQUEST['deref'])) {
    define('PHM_NO_SESSION', 1); // This advises init.frontend to not use any session stuff
    require($_PM_['path']['lib'].'/init.frontend.php');
    if (isset($_GET[session_name()]) || isset($_POST[session_name()])) {
        header('Location: '.PHP_SELF.'?deref='.preg_replace('[^-0-9a-fA-F]', '', $_REQUEST['deref']));
        exit;
    }
    if (empty($_REQUEST['deref'])) {
        exit;
    }
    $Deref = new DB_Controller_Derefer();
    $uri = $Deref->map($_REQUEST['deref']);

    // Looks neither like URL nor Email
    if (!basics::isURL($uri) && !basics::isEmail($uri)) {
        exit;
    }
    // Alright, should be fine now
    header('Location: '.$uri);
    exit;

//
// Normal case of authenticated access to the system
//
} else {
    // Init session
    session_start();
    require($_PM_['path']['lib'].'/init.frontend.php');
    if (!empty($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] != 'system') {
        Debug::off();
        if (isset($_PM_['core']['debugging_level']) && 'disabled' != $_PM_['core']['debugging_level']) {
            Debug::on();
        }
    }
    if ('logout' == $action) {
        require_once($_PM_['path']['lib'].'/logout.php');
        header('Location: '.PHP_SELF.'?'.give_passthrough(1).'&WP_return='.$WP_return);
        exit;
    }

    if (!isset($_SESSION['phM_uid']) || !isset($_SESSION['phM_username'])) {
        $action = $load = $HDL = false;
        require_once($_PM_['path']['frontend'].'/mod.auth.php');
    }
}
if ('flist' == $action) {
    $outer_template = 'folderlist.tpl';
    require_once($_PM_['path']['frontend'].'/folderlist.php');
} elseif ('worker' == $action) {
    require_once($_PM_['path']['frontend'].'/worker.php');
} elseif ($load) {
    if ($HDL !== false && strlen($HDL)) {
        // Keep track of folder changes, allows to reselect folder after page reload
        if ($load == 'ilist') {
            $_SESSION['phM_login_handler'] = $HDL;
            $_SESSION['phM_login_folder'] = isset($_REQUEST['workfolder']) ? basename($_REQUEST['workfolder']) : 0;
        }
        // Let the requested handler take care of the call
        $loader = $_PM_['path']['handler'].'/'.$HDL.'/loader.php';
        if (file_exists($loader) && is_readable($loader)) {
            require_once($loader);
        }
    }
} elseif ('plugged' == $action) {
    $tpl = &$_PM_['temp']['plug_output'];
} elseif (!isset($_PM_['temp']['load_tpl_auth']) && !isset($_REQUEST['load'])) {
    require_once($_PM_['path']['frontend'].'/mainsite.php');
}

// Output the theme
if (!$pure) {
    require($_PM_['path']['lib'].'/themes.php');
} elseif (isset($tpl)) {
    if (is_object($tpl)) {
        $tpl->display();
    } else {
        echo $tpl;
    }
}
