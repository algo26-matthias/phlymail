<?php
/**
 * Receive something from another handler
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @copyright 2006-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$srchdl = preg_replace('![^a-zA-Z_]!', '', $_REQUEST['source']);
$toload = 'handler_'.$srchdl.'_api';
$API = new $toload($_PM_, $_SESSION['phM_uid']);
$srcinfo = $API->sendto_fileinfo($_REQUEST['resid']);
$raw = $API->sendto_sendinit($_REQUEST['resid']);

$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
// Parse the event data
foreach (array('VEVENT', 'VTODO') as $type) {
    $event = parse_icaldata($raw, $type, $cDB->get_event_types(), $cDB->get_event_status());
    if (false !== $event) {
        break;
    }
}
if (false === $event) {
    $error = 'No parsable event data';
} else {
    define('FROM_SENDTO', 1);
    if ($event['starts']) {
        $event['starts'] = date('Y-m-d H:i', $event['starts']);
    }
    if (empty($event['ends'])) {
        $event['ends'] = $event['starts'];
    }
    if ($event['ends']) {
        $event['ends'] = date('Y-m-d H:i', $event['ends']);
    }
    require(__DIR__.'/edit_event.php');
}
