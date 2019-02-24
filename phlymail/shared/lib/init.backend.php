<?php
/**
 * Cousin of ./init.frontend.php for backend purposes
 * @package phlyMail Nahariya 4.0+
 * @subpackage Core system
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.5 2015-04-06 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
define('SESS_NAME', session_name());
define('SESS_ID', preg_replace('![^-,a-zA-Z0-9]!', '', session_id())); // Allow up to 6bits per character session IDs
define('CRLF', "\r\n");
define('LF', "\n");
require_once($_PM_['path']['lib'].'/autoload.php');
require_once($_PM_['path']['lib'].'/functions.php');
if (empty($_PM_['path']['userbase'])) {
    $_PM_['path']['userbase'] = $_PM_['path']['storage'];
}

// Global Choices, overloading core settings
if (file_exists($_PM_['path']['conf'].'/global.choices.ini.php')) {
    $_PM_ = merge_PM($_PM_, parse_ini_file($_PM_['path']['conf'].'/global.choices.ini.php', true));
    // Merge the files and throw away the superfluous file
    $newPM = array();
    if (file_exists($_PM_['path']['conf'].'/choices.ini.php')) {
        $newPM = merge_PM($newPM, parse_ini_file($_PM_['path']['conf'].'/choices.ini.php', true));
    }
    $newPM = merge_PM($newPM, parse_ini_file($_PM_['path']['conf'].'/global.choices.ini.php', true));
    basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $newPM);
    unlink($_PM_['path']['conf'].'/global.choices.ini.php');
}
if (file_exists($_PM_['path']['conf'].'/choices.ini.php')) {
    $_PM_ = merge_PM($_PM_, parse_ini_file($_PM_['path']['conf'].'/choices.ini.php', true));
}
// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

$DB = new DB_Base();

define('PHP_SELF', (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF']);
require_once($_PM_['path']['message'].'/'.$_PM_['core']['language'].'.php');
// Timezone
if (isset($_PM_['core']['timezone'])) {
    define('PHM_TIMEZONE', $_PM_['core']['timezone']);
    date_default_timezone_set($_PM_['core']['timezone']);
} elseif (isset($WP_msg['tz'])) {
    define('PHM_TIMEZONE', $WP_msg['tz']);
    date_default_timezone_set($WP_msg['tz']);
} else {
    define('PHM_TIMEZONE', date_default_timezone_get());
}
define('PHM_UTCOFFSET', utc_offset());
$DB->settimezone(PHM_TIMEZONE, PHM_UTCOFFSET);

// SMS active?
if (isset($_PM_['core']['sms_active'])) {
    if ($_PM_['core']['sms_active']) {
        $_PM_['core']['sms_active'] = 1;
    }
} elseif (isset($_PM_['core']['sms_default_active'])) {
    if ($_PM_['core']['sms_default_active']) {
        $_PM_['core']['sms_active'] = 1;
    }
} else {
    $_PM_['core']['sms_active'] = 0;
}
