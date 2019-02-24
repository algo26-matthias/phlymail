<?php
/**
 * Configuration frontend for global bookmarks
 * @package phlyMail
 * @subpackage Handler Bookmarks
 * @copyright 2001-2015 phlyLabs Berlin, http://phlylabs.de/
 * @version 0.0.6 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($screen)) {
    $screen = 'bookmarks';
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

$bDB = new handler_bookmarks_driver(0);

switch ($screen) {
    case 'bookmarks': require_once(__DIR__.DIRECTORY_SEPARATOR.'bookmarks.php'); break;
    case 'exchange':  require_once(__DIR__.DIRECTORY_SEPARATOR.'exchange.php');  break;
}
