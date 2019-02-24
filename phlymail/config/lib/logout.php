<?php
/**
 * phlyMail Config logout
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2002-2010 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.3 2010-08-16
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
if (isset($_SESSION['phM_uid'])) $DB->set_admlogouttime($_SESSION['phM_uid']);
$_SESSION = array();
if (isset($_REQUEST['redir']) && $_REQUEST['redir'] == 'index') {
    // Try to find out the file extension of the config.php - allows changing
    // the extension to something else like .php5 and this change reflected in
    // the goto frontend link
    preg_match('!\.([a-z0-9]+)$!', basename(PHP_SELF), $sufx);
    $sufx = (isset($sufx[1]) && $sufx[1]) ? $sufx[1] : 'php';
    header('Location: index.'.$sufx.'?action=login');
    exit;
}
header('Location: '.PHP_SELF);
exit;
