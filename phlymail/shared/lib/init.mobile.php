<?php
/**
 * derived from shared/lib/init.frontend.php; Initialise the mobile frontend
 * @package phlyMail Nahariya 4.0+
 * @subpackage Core system
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.0 2015-03-30 
 */
require_once __DIR__.'/init.frontend.php';

// Theme related
$fallbackTheme = 'Basic';
$clientType = 'mobile';

if (isset($_COOKIE['phlyMail_Mobile_Theme'])) {
    $_PM_['core']['mobile_theme_name'] = basename($_COOKIE['phlyMail_Mobile_Theme']);
}

if (!isset($_PM_['core']['mobile_theme_name'])
        || !@file_exists($_PM_['path']['theme_dir'].'/'.$_PM_['core']['mobile_theme_name'].'/choices.ini.php')) {
    $_PM_['core']['mobile_theme_name'] = $fallbackTheme;
}
$WP_theme = parse_ini_file($_PM_['path']['theme_dir'].'/'.$_PM_['core']['mobile_theme_name'].'/choices.ini.php');
if ($WP_theme['engine'] != trim(file_get_contents($_PM_['path']['conf'].'/theme.engine'))) {
    $_PM_['core']['mobile_theme_name'] = $fallbackTheme;
    $WP_theme = parse_ini_file($_PM_['path']['theme_dir'].'/'.$_PM_['core']['mobile_theme_name'].'/choices.ini.php');
}

$_PM_['path']['theme'] = $_PM_['path']['theme_dir'].'/'.$_PM_['core']['mobile_theme_name'];
$_PM_['path']['templates'] = $_PM_['path']['frontend'].'/mobile.templates/';
$_PM_['path']['themecache'] = $_PM_['path']['tplcache'].$_PM_['core']['mobile_theme_name'].'_';
$_PM_['path']['tplcache'] .= 'mobile_';
// End Theme handling

// HTML sending is not enabled in mobile environments (yet?)
if (!empty($_PM_['core']['send_html'])) {
    $_PM_['core']['send_html'] = false;
}
