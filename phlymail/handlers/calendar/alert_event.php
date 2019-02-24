<?php
/**
 * Display the alert window
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Calendar handler
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.event.alert.tpl');
if (isset($_REQUEST['eid']) && $_REQUEST['eid']) {
    $cDB = new handler_calendar_driver($_SESSION['phM_uid']);
    list ($eid, $ref) = $cDB->get_item_by_reminder($_REQUEST['eid']);
    $event = ($ref == 'evt') ? $cDB->get_event($eid) : $cDB->get_task($eid);
} else {
    $event = array('start_d' => date('d'), 'start_m' => date('m'), 'start_y' => date('Y')
    		,'start_mi' => date('i'), 'start_h' => date('H')
    		,'end_d' => date('d'), 'end_m' => date('m'), 'end_y' => date('Y')
    		,'end_mi' => date('i'), 'end_h' => date('H')
    		);
    $eid = '';
}
if (!$event['start']) {
    $start_end = date($WP_msg['dateformat_new'], $event['end']);
} else {
    $start_end = ($event['start'] == $event['end'])
            ? date($WP_msg['dateformat_new'], $event['start'])
            : date($WP_msg['dateformat_new'], $event['start']).' - '.date($WP_msg['dateformat_new'], $event['end']);
}
$tpl->assign(array
        ('edit_url' => ($ref == 'evt')
                ? PHP_SELF.'?l=edit_event&h=calendar&eid='.$eid.'&'.give_passthrough(1)
                : PHP_SELF.'?l=edit_task&h=calendar&tid='.$eid.'&'.give_passthrough(1)
        ,'window_title' => ($ref == 'evt') ? $WP_msg['CalEvtReminder'] : $WP_msg['CalTskReminder']
        ,'title' => isset($event['title']) ? htmlspecialchars($event['title'], ENT_COMPAT, 'utf-8') : ''
        ,'location' => isset($event['location']) ? htmlspecialchars($event['location'], ENT_COMPAT, 'utf-8') : ''
        ,'start_end' => $start_end
        ,'description' => isset($event['description']) ? nl2br(htmlspecialchars($event['description'], ENT_COMPAT, 'utf-8')) : ''
        ,'event_id' => $eid
        ,'event_type' => $ref
        ,'reminder_id' => intval($_REQUEST['eid'])
        ,'reminder_text' => isset($event['reminder_text']) ? nl2br(htmlspecialchars($event['reminder_text'], ENT_COMPAT, 'utf-8')) : ''
        ,'msg_close' => $WP_msg['CalEvtDiscard']
        ,'msg_edit' => $WP_msg['CalEvtEdit']
        ,'msg_reschedule' => $WP_msg['CalEvtReschedule']
        ));
