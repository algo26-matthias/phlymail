<?php
/**
 * phlyMail mobile front controller
 *
 * @package phlyMail Nahariya 4.0+ default branch
 * @copyright 2011-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.7 2015-08-10
 */
// Try to disable any execution time limits imposed - no effect under SAFE_MODE!
@set_time_limit(0);
// This constant is required for all modules to run
define('_IN_PHM_', true);
define('PHM_MOBILE', true);
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

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : false;
$pure = isset($_REQUEST['p']) ? $_REQUEST['p'] : false;
$load = isset($_REQUEST['l']) ? basename(trim($_REQUEST['l'])) : false;
$HDL = isset($_REQUEST['h']) ? basename(trim($_REQUEST['h'])) : false;
$folder = isset($_REQUEST['f']) ? basename(trim($_REQUEST['f'])) : false;
$item = isset($_REQUEST['i']) ? basename(trim($_REQUEST['i'])) : false;
$outer_mobile = 'outer.main.tpl';

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
    require($_PM_['path']['lib'].'/compat.5.x.php');
}
// Init session
session_start();

require($_PM_['path']['lib'].'/init.mobile.php');
if (!empty($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] != 'system') {
    Debug::off();
    if (isset($_PM_['core']['debugging_level']) && 'disabled' != $_PM_['core']['debugging_level']) {
        Debug::on();
    }
}

if ('logout' == $action) {
    require($_PM_['path']['lib'].'/logout.php');
    header('Location: '.PHP_SELF);
    exit;
}

if (!isset($_SESSION['phM_uid']) || !isset($_SESSION['phM_username'])) {
    $action = $load = $HDL = false;
    $outer_mobile = 'outer.auth.tpl';
    require($_PM_['path']['frontend'].'/mod.auth.php');
}

if ('flist' == $action) {
    $specific = $_PM_['path']['handler'].'/'.$HDL.'/mobile.folderlist.php';
    require(file_exists($specific) ? $specific : __DIR__.'/mobile/folderlist.php');
} elseif ('ilist' == $action) {
    $specific = $_PM_['path']['handler'].'/'.$HDL.'/mobile.itemlist.php';
    require(file_exists($specific) ? $specific : __DIR__.'/mobile/itemlist.php');
} elseif ('setup' == $action) {
    $specific = $_PM_['path']['handler'].'/'.$HDL.'/mobile.setup.php';
    require(file_exists($specific) ? $specific : __DIR__.'/mobile/setup.php');
} elseif ('new' == $action) {
    $specific = $_PM_['path']['handler'].'/'.$HDL.'/mobile.new.php';
    require(file_exists($specific) ? $specific : __DIR__.'/mobile/new.php');
} elseif ($load) {
    if ($HDL !== false && strlen($HDL)) {
        // Let the requested handler take care of the call
        $loader = $_PM_['path']['handler'].'/'.$HDL.'/loader.php';
        if (file_exists($loader) && is_readable($loader)) {
            require($loader);
        }
    }
} elseif ('plugged' == $action) {
    $tpl = &$_PM_['temp']['plug_output'];
} elseif (!isset($_PM_['temp']['load_tpl_auth']) && !isset($_REQUEST['load'])) {
    $outer_mobile = 'outer.home.tpl';
    require(__DIR__.'/mobile/home.php');
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
