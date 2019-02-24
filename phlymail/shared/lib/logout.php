<?php
/**
 * Destroying a phlyMail session
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Core functionality
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.5 2012-01-29
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// Check all handlers for logout jobs, currently reading in flat files, no classes
if (isset($_SESSION['phM_uniqe_handlers'])) {
    $xbase = $_PM_['path']['handler'];
    foreach ($_SESSION['phM_uniqe_handlers'] as $handler => $name) {
        if (is_readable($xbase.'/'.$handler.'/api_logout.php')) {
            require_once($xbase.'/'.$handler.'/api_logout.php');
        }
    }
}

$url = PHP_SELF;
if (isset($_REQUEST['redir']) && $_REQUEST['redir'] == 'config'
        && isset($_PM_['core']['showlinkconfig']) && $_PM_['core']['showlinkconfig']) { // Only allow this with correct setting
    // Try to find out the file extension of the config.php - allows changing
    // the extension to something else like .php5 and this change reflected in
    // the goto link
    preg_match('!\.([a-z0-9]+)$!', basename(PHP_SELF), $sufx);
    $sufx = (isset($sufx[1]) && $sufx[1]) ? $sufx[1] : 'php';
    $url = str_replace('index.'.$sufx, 'config.'.$sufx, $url);
} elseif (defined('PHM_MOBILE')) {
    // No fancy reirection stuff, this breaks
} elseif (isset($_PM_['core']['logout_redir_uri']) && $_PM_['core']['logout_redir_uri']) {
    $url = preg_replace('!\r|\n|\t!', '', $_PM_['core']['logout_redir_uri']);
    if (!preg_match('!^http(s)?\://!', $url)) $url = 'http://'.$url;
}
if (isset($_SESSION['phM_uid'])) {
    $DB->set_logouttime($_SESSION['phM_uid']);
}
$_SESSION = array();
header('Location: '.$url);
exit;
