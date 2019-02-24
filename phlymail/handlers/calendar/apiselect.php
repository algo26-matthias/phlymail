<?php
/**
 * Offers JS API for selecting stuff from calendar
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Calendar handler
 * @copyright 2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$what  = !empty($_REQUEST['what']) ? $_REQUEST['what'] : 'title';
$cDB   = new handler_calendar_driver($_SESSION['phM_uid']);

// For jQuery UI autocomplete
if (!empty($_REQUEST['jqui'])) {
    $term = phm_stripslashes($_REQUEST['term']);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($cDB->autoCompleteHelper($term, $what));
    exit;
}
