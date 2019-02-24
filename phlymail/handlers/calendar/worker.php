<?php
/**
 * worker - Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Calendar
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.1 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$error = false;
$output = '';
$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
session_write_close();

header('Content-Type: text/javascript; charset=UTF-8');
$mytask = (isset($_REQUEST['what']) && $_REQUEST['what']) ? $_REQUEST['what'] : false;
switch ($mytask) {
case 'rename_folder':
case 'folder_delete':
case 'folder_create':
case 'folder_empty':
case 'folder_resync':
    // Tell the setup module to return right after doing the operation without
    // generating output on its own
    $_PM_['tmp']['setup']['no_output'] = true;
    // Use groups manager here
    require_once(__DIR__.'/setup.folders.php');
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'")'.LF;
    } else { // No errors - force reload of the folder list to reflect changes done
        echo 'flist_refresh("calendar");'.LF.'if (parent.CurrentHandler == "calendar") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
case 'event_repeat':
    if (isset($_REQUEST['eid'])) {
        $state = $cDB->repeat_event_alert(intval($_REQUEST['eid']), 300);
        if (!$state) $error = $cDB->get_errors();
    }
    if ($error) $output .= 'alert("'.$error.'");'.LF;
    break;
case 'event_discard':
    if (isset($_REQUEST['eid'])) {
        $state = $cDB->discard_event_alert(intval($_REQUEST['eid']));
        if (!$state) $error = $cDB->get_errors();
    }
    if ($error) $output .= 'alert("'.$error.'");'.LF;
    break;
case 'event_delete':
    if (isset($_REQUEST['eid'])) {
        $ids = $_REQUEST['eid'];
        if (!is_array($ids)) $ids = array($ids);
        foreach ($ids as $id) {
            $cDB->delete_event($id);
        }
    }
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'")'.LF;
    } else { // No errors - force reload of the folder list to reflect changes done
        echo 'parent.frames.PHM_tl.flist_refresh("calendar");'.LF.'if (CurrentHandler == "calendar") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
case 'killalloldevents':
    $state = $cDB->killoldevents(0);
    if ($error) $output .= 'alert("'.$error.'");'.LF;
    break;
}

if (!$mytask) {
    if (!isset($_SESSION['phM_calendar_oldevtskilled'])) {
        $_SESSION['phM_calendar_oldevtskilled'] = true;
        if (isset($_PM_['calendar']) && isset($_PM_['calendar']['killoldevents']) && $_PM_['calendar']['killoldevents']) {
            $span = isset($_PM_['calendar']['killoldevents_span']) ? $_PM_['calendar']['killoldevents_span'] : 0;
            $cDB->killoldevents($span);
        }
    }
    // Check for events to be alerted before the next worker reload
    foreach ($cDB->get_alertable_events(20, false, false) as $event) {
        $output .= 'calendar_schedule_alert("'.$event['reminder_id'].'", "'.$event['warn_time'].'")'.LF;
    }
    // Check for TASKS to be alerted before the next worker reload
    foreach ($cDB->get_alertable_tasks(20, false, false) as $event) {
        $output .= 'calendar_schedule_alert("'.$event['reminder_id'].'", "'.$event['warn_time'].'")'.LF;
    }
}
if ($output) {
    echo $output;
}
exit;
