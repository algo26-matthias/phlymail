<?php
/**
 * Initialise all bells and whistles to ring
 * @package phlyMail Nahariya 4.0+
 * @subpackage Config application
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5mod2 2015-04-06 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

define('SESS_NAME', session_name());
define('SESS_ID', preg_replace('![^-,a-zA-Z0-9]!', '', session_id())); // Allow up to 6bits per character session IDs
define('CRLF', "\r\n");
define('LF', "\n");
require_once $_PM_['path']['lib'].'/autoload.php';
require_once $_PM_['path']['lib'].'/functions.php';
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
    $_PM_ = init_merge_PM($_PM_, parse_ini_file($_PM_['path']['conf'].'/choices.ini.php', true));
}
$_PM_['path']['templates'] = $_PM_['path']['frontend'].'/templates/';

// Override ForceSSL option in case of misconfiguration
if (!empty($_REQUEST['nossl'])) {
    $nossl = 1;
    $_PM_['core']['pass_through'][] = 'nossl';
    $_PM_['auth']['force_ssl'] = false;
}
define('PHM_FORCE_SSL', !empty($_PM_['auth']['force_ssl']));

// System is configured to enforce use of HTTPS
if (!empty($_PM_['auth']['force_ssl']) && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')) {
    header('HTTP/1.1 301 Moved Permanently - Please update any bookmarks or links');
    header('Location: https://'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']) . $_SERVER['REQUEST_URI']);
    exit();
}

// Config Choices
$WP_conf = (file_exists($_PM_['path']['conf'].'/config.choices.ini.php'))
        ? parse_ini_file($_PM_['path']['conf'].'/config.choices.ini.php')
        : array('scheme' => 'default', 'language' => 'de', 'allow_ip' => 0)
        ;
// Handling special proxy calls here. Very often used for SSL calls thorugh an SSL proxy used for all instances of a hoster
if (!empty($_PM_['proxy']['prepend_path'])
        && (isset($_SERVER[$_PM_['proxy']['server_param']]) && $_SERVER[$_PM_['proxy']['server_param']] == $_PM_['proxy']['server_value'])) {
    define('PHP_SELF', (!empty($_SERVER['SCRIPT_NAME']))
            ? $_PM_['proxy']['prepend_path'].'/'.$_SERVER['SCRIPT_NAME']
            : $_PM_['proxy']['prepend_path'].'/'.$_SERVER['PHP_SELF']);
    if (!empty($_PM_['proxy']['proxy_hostname'])) {
        define('PHM_SERVERNAME', $_PM_['proxy']['proxy_hostname']);
    }
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
// Session cookie check
if (!defined('PHM_NO_SESSION')) {
    if (isset($_SESSION['phM_cookie'])
            && (!isset($_COOKIE['phlyMail_Session']) || $_SESSION['phM_cookie'] != $_COOKIE['phlyMail_Session'])) {
        // If no session cookie found or session cookie invalid
        header('Location: '.PHP_SELF);
        exit;
    }
}
// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());
$DB = new DB_Admin();
require(CONFIGPATH.'/messages/'.$WP_conf['language'].'.php');
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

// Rise security of the config interface by blocking everything but allowed IPs
if (isset($WP_conf['allow_ip']) && $WP_conf['allow_ip']) {
    if (!isset($_SESSION['allowed_ips'])) {
        if (file_exists($_PM_['path']['conf'].'/config.allowed_ips.php')
                && is_readable($_PM_['path']['conf'].'/config.allowed_ips.php')) {
            $allowed_ips = file_get_contents($_PM_['path']['conf'].'/config.allowed_ips.php');
            $_SESSION['allowed_ips'] = explode(LF, trim(str_replace('<?php die(); ?>', '', $allowed_ips)));
        } else {
            $_SESSION['allowed_ips'] = array(getenv('REMOTE_ADDR'));
        }
    }
    if (isset($_SESSION['allowed_ips']) && is_array($_SESSION['allowed_ips'])) {
        $treffer = 0;
        $zeilen = 0;
        $client_ip = getenv('REMOTE_ADDR');
        foreach ($_SESSION['allowed_ips'] as $test) {
            ++$zeilen;
            if (!$test) continue;
            if (substr($client_ip, 0, strlen($test)) == $test) {
                $treffer = 1;
                break;
            }
        }
        if (!$treffer && $zeilen) $_SESSION = array('blocked' => 'IP');
    }
}
// Keep track of activated handlers
$_PM_['handlers'] = parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php');

// Since array_merge canonly merge flat arrays and array_merge_recursive appends doublettes
// to the father element we have to do the merge "manually"
function init_merge_PM($_PM_, $import)
{
    foreach ($import as $k => $v) {
        if (is_array($v)) { foreach ($v as $k2 => $v2) { $_PM_[$k][$k2] = $v2; } } else { $_PM_[$k] = $v; }
    }
    return $_PM_;
}