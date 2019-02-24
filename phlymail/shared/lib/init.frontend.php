<?php
/**
 * shared/lib/init.frontend.php -> Initialise all bells and whistles to ring
 * @package phlyMail Nahariya 4.0+
 * @subpackage Core system
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.0mod2 2015-04-06 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
define('SESS_NAME', session_name());
define('SESS_ID', preg_replace('![^-,a-zA-Z0-9]!', '', session_id())); // Allow up to 6bits per character session IDs
define('CRLF', "\r\n");
define('LF', "\n");
require_once $_PM_['path']['lib'].'/autoload.php';
require_once $_PM_['path']['lib'].'/functions.php';
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
// System is configured to enforce use of HTTPS
if (!empty($_PM_['auth']['force_ssl']) && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')) {
    header('HTTP/1.1 301 Moved Permanently - Please update any bookmarks or links');
    header('Location: https://'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']) . $_SERVER['REQUEST_URI']);
    exit();
}
define('PHM_FORCE_SSL', !empty($_PM_['auth']['force_ssl']));


// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

$DB = new DB_Base();
// Handling special proxy calls here. Very often used for SSL calls thorugh an SSL proxy used for all instances of a hoster
if (!empty($_PM_['proxy']['prepend_path'])
        && (isset($_SERVER[$_PM_['proxy']['server_param']]) && $_SERVER[$_PM_['proxy']['server_param']] == $_PM_['proxy']['server_value'])) {
    define('PHP_SELF', (!empty($_SERVER['SCRIPT_NAME']))
            ? $_PM_['proxy']['prepend_path'].'/'.$_SERVER['SCRIPT_NAME']
            : $_PM_['proxy']['prepend_path'].'/'.$_SERVER['PHP_SELF']);
    if (!empty($_PM_['proxy']['proxy_hostname'])) {
        define('PHM_SERVERNAME', $_PM_['proxy']['proxy_hostname']);
    }
    $_SERVER['REQUEST_URI'] = $_PM_['proxy']['prepend_path'].$_SERVER['REQUEST_URI'];
} else {
    define('PHP_SELF', (!empty($_SERVER['SCRIPT_NAME'])) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF']);
}
if (!defined('PHM_SERVERNAME')) {
    $protocol = 'http://';
    if (!empty($_SERVER['HTTPS'])) {
        $protocol = 'https://';
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        define('PHM_SERVERNAME', $protocol.$_SERVER['HTTP_HOST']);
    } elseif (!empty($_SERVER['SERVER_NAME'])) {
        define('PHM_SERVERNAME', $protocol.$_SERVER['SERVER_NAME']);
    }
}

if (isset($_SESSION['phM_uid'])) {
    require_once($_PM_['path']['lib'].'/user.choices.php');
} else {
    // Some minor things stored in cookies
    if (isset($_COOKIE['phlyMail_Language'])) {
        $_PM_['core']['language'] = basename($_COOKIE['phlyMail_Language']);
    }
    if (isset($_COOKIE['phlyMail_Theme'])) {
        $_PM_['core']['theme_name'] = basename($_COOKIE['phlyMail_Theme']);
    }
}
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

// Theme related
$fallbackTheme = 'Yokohama';
if (!isset($_PM_['core']['theme_name'])
        || !@file_exists($_PM_['path']['theme'].'/'.$_PM_['core']['theme_name'].'/choices.ini.php')) {
    $_PM_['core']['theme_name'] = $fallbackTheme;
}
$WP_theme = parse_ini_file($_PM_['path']['theme'].'/'.$_PM_['core']['theme_name'].'/choices.ini.php');
if ($WP_theme['engine'] != trim(file_get_contents($_PM_['path']['conf'].'/theme.engine'))) {
    $_PM_['core']['theme_name'] = $fallbackTheme;
    $WP_theme = parse_ini_file($_PM_['path']['theme'].'/'.$_PM_['core']['theme_name'].'/choices.ini.php');
}
$_PM_['path']['theme_dir'] = $_PM_['path']['theme'];
$_PM_['path']['theme'] .= '/'.$_PM_['core']['theme_name'];
$_PM_['path']['templates'] = $_PM_['path']['frontend'].'/templates/';
$_PM_['path']['themecache'] = $_PM_['path']['tplcache'].$_PM_['core']['theme_name'].'_';
// End Theme handling

// Tie session to IP, if told so
if (!empty($_PM_['auth']['tie_session_ip']) && !defined('PHM_NO_SESSION')) {
    if (isset($_SESSION['phM_remote_ip'])) {
        if ($_SESSION['phM_remote_ip'] != getenv('REMOTE_ADDR')) {
            // Redirect the visitor with the wrong IP to the login screen
            header('Location: '.PHP_SELF);
            exit;
        }
    } else {
        $_SESSION['phM_remote_ip'] = getenv('REMOTE_ADDR');
    }
}
// Session cookie check
if (!empty($_PM_['auth']['session_cookie']) && !defined('PHM_NO_SESSION')) {
    if (isset($_SESSION['phM_cookie'])
            && (!isset($_COOKIE['phlyMail_Session']) || $_SESSION['phM_cookie'] != $_COOKIE['phlyMail_Session'])) {
        // If no session cookie found or session cookie invalid
        header('Location: '.PHP_SELF);
        exit;
    }
}
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
// Use gzip
if (!empty($_PM_['core']['gzip_frontend']) && !(ini_get('zlib.output_compression'))) {
    ob_start('ob_gzhandler');
}
