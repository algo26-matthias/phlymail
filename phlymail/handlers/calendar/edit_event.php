<?php
/**
 * edit_event.php - Edit new / existing event
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Calendar handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.9 2015-05-15
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$passthru = give_passthrough(1);
if (!isset($_PM_['core']['sms_sender'])) {
    $_PM_['core']['sms_sender'] = '';
}
$perms = 'all';

$eid = !empty($_REQUEST['eid']) ? intval($_REQUEST['eid']) : null;

$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
if (!empty($_REQUEST['delete_event'])) {
    $mode = 'delete';
} elseif (!empty($_REQUEST['save_event'])) {
    $mode = 'store';
    if (!empty($_REQUEST['copyevent'])) {
        $mode = 'copy';
    } elseif (empty($eid)) {
        $mode = 'add';
    }
} elseif (!empty($eid)) {
    $mode = 'load';
} else {
    $mode = 'create';
}
if (!defined('FROM_SENDTO')) {
    $event = null;
}
if ($mode == 'delete' || $mode == 'load') {
    $event = $cDB->get_event($eid);
    if (empty($event)) {
        $perms = array('read' => 0, 'write' => 0, 'delete' => 0);
    } else {
        if (empty($event['gid'])) {
            $perms = array('read' => 1, 'write' => 1, 'delete' => 1);
        } elseif ($cDB->getGroupOwner($event['gid']) != $_SESSION['phM_uid']
                && $event['owner'] != $_SESSION['phM_uid']) {
            if (!empty($DB->features['shares'])) {
                $perms = $DB->getUserSharedFolderPermissions($_SESSION['phM_uid'], 'calendar', $event['gid']);
            }
        }/* else {
            $groupInfo = $cDB->get_group($event['gid']);
            if ($groupInfo['rw'] != 'rw') {
                $perms = array('read' => 1, 'write' => 0, 'delete' => 0);
            }
        }*/
    }
}
if ($mode == 'store' || $mode == 'copy') {
    if (!empty($_REQUEST['gid'])) {
        $newGid = intval($_REQUEST['gid']);
    }
    $event = $cDB->get_event($eid);
    // When changing the group ID of an event, that effectively means deleting the event
    // from the old calendar and putting it into the new one. Needs permissions to do so
    // This off ocurse does not apply when copying the event, which leaves the original untouched
    if ($event['gid'] != $newGid && $mode != 'copy') {
        if ($cDB->getGroupOwner($event['gid']) != $_SESSION['phM_uid']) {
            $perms = $DB->getUserSharedFolderPermissions($_SESSION['phM_uid'], 'calendar', $event['gid']);
        } else {
            $groupInfo = $cDB->get_group($event['gid']);
            if ($groupInfo['rw'] != 'rw') {
                $perms = array('read' => 1, 'write' => 0, 'delete' => 0);
            }
        }
    }
    // What about the new group?
    if ($newGid && ($perms == 'all' || !empty($perms['write']))) {
        if ($cDB->getGroupOwner($newGid) != $_SESSION['phM_uid']) {
            $perms = $DB->getUserSharedFolderPermissions($_SESSION['phM_uid'], 'calendar', $newGid);
        } else {
            $groupInfo = $cDB->get_group($newGid);
            if ($groupInfo['rw'] != 'rw') {
                $perms = array('read' => 1, 'write' => 0, 'delete' => 0);
            }
        }
    }
}

if ($mode == 'add') {
    if (!empty($_REQUEST['gid'])) {
        $targetGroupOwner = $cDB->getGroupOwner($_REQUEST['gid']);
        if (empty($targetGroupOwner)) {
            $perms = '';
        } elseif ($targetGroupOwner != $_SESSION['phM_uid']) {
            $perms = $DB->getUserSharedFolderPermissions($_SESSION['phM_uid'], 'calendar', $_REQUEST['gid']);
        }
    } else {
        $perms = array('read' => 1, 'write' => 1);
    }
}

// Hier nun ALLE Rechte checken gemäß oben ermitteltem $mode
if ($mode == 'delete') {
    if (($perms != 'all' && empty($perms['delete'])) || (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_delete_event'])) {
        echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
        exit;
    }
}
if ($mode == 'store') {
    if (($perms != 'all' && empty($perms['write'])) || (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_update_event'])) {
        echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
        exit;
    }
}
if ($mode == 'copy') {
    if (($perms != 'all' && empty($perms['write'])) || (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_event'])) {
        echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
        exit;
    }
}
if ($mode == 'add') {
    if (($perms != 'all' && empty($perms['write'])) || (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_event'])) {
        echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
        exit;
    }
}
if ($mode == 'create') {
    if (($perms != 'all' && empty($perms['write'])) || (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_event'])) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
}

if ($mode == 'delete') {
    // External calendar in read/write mode? Trigger local update of it's data
    if ($groupInfo['type'] == 1 && $groupInfo['rw'] == 'rw'
            && $groupInfo['uri']) {
        $PHM_CAL_IM_FILE = externalCalendarRead($groupInfo, $errno, $errstr);
        if (false === $PHM_CAL_IM_FILE) {
            $cDB->set_remote_calendar_checked($groupInfo['gid'], $errno, $errstr);
            echo '{"error":"'.$WP_msg['CalNoDelEvt'].' - ExtRead"}';
            exit;
        }
        $PHM_CAL_IM_DO = 'import';
        $PHM_CAL_IM_UID = $groupInfo['owner'];
        $PHM_CAL_IM_GROUP = $groupInfo['gid'];
        $PHM_CAL_IM_FORMAT = 'ICS';
        $PHM_CAL_IM_SYNC = true;
        require $_PM_['path']['handler'] . '/calendar/exchange.php';
    }
    //

    // Actual deletion
    $done = $cDB->delete_event($eid);
    //

    // Trigger external update of calendar's data
    if ($groupInfo['type'] == 1 && $groupInfo['rw'] == 'rw'
            && $groupInfo['uri']) {
        if (substr($groupInfo['uri'], 0, 7) == 'file://') {
            $PHM_CAL_EX_PUTTOFILE = substr($groupInfo['uri'], 7);
            $isRemote = false;
        } else {
            $PHM_CAL_EX_PUTTOFILE = $_PM_['path']['temp'].'/'.SecurePassword::generate(16, false, STRONGPASS_DECIMALS | STRONGPASS_LOWERCASE);
            $isRemote = true;
        }

        $PHM_CAL_EX_DO = 'export';
        $PHM_CAL_EX_UID = $groupInfo['owner'];
        $PHM_CAL_EX_GROUP = $groupInfo['gid'];
        $PHM_CAL_EX_FORMAT = 'ICS';
        require $_PM_['path']['handler'] . '/calendar/exchange.php';
        if ($isRemote) {
            $res = externalCalendarWrite($groupInfo, file_get_contents($PHM_CAL_EX_PUTTOFILE), $errno, $errstr);
            if (false === $res) {
                $cDB->set_remote_calendar_checked($groupInfo['gid'], $errno, $errstr);
                echo '{"error":"' . $WP_msg['CalNoDelEvt'] . ' - ExtWrite"}';
                exit;
            }
        }
    }
    //

    echo ($done) ? '{"done":"1"}' : '{"error":"'.$WP_msg['CalNoDelEvt'].'"}';
    exit;
}

if ($mode == 'add' || $mode == 'copy' || $mode == 'store') {
    // Convert entered dates and times into something database friendly
    foreach (array('start', 'end') as $k) {
    	${$k} = (isset($_REQUEST[$k])) ? $_REQUEST[$k] : false;
    }
    if (!$start) {
        echo '{"error":"'.$WP_msg['CalEvtInvalidStart'].'"}';
        exit;
    }
    if (!$end) {
        echo '{"error":"'.$WP_msg['CalEvtInvalidEnd'].'"}';
        exit;
    }
    $start = basics::format_date($start, 'Y-m-d H:i:s');
    $end = basics::format_date($end, 'Y-m-d H:i:s');

    $reminders = array();
    if (isset($_REQUEST['warn']) && $_REQUEST['warn']) {
        $factors = array('m' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800);
        foreach ($_REQUEST['reminders']['time'] as $k => $v) {
            $reminders[$k] = array
                    ('time' => ((!$v || $v < 0) ? 0 : intval($v)) * $factors[$_REQUEST['reminders']['range'][$k]]
                    ,'mode' => ($_REQUEST['reminders']['mode'][$k] == 's') ? 's' : 'e'
                    ,'mailto' => isset($_REQUEST['reminders']['mail'][$k]) ? $_REQUEST['reminders']['mail'][$k] : ''
                    ,'smsto' => isset($_REQUEST['reminders']['sms'][$k]) ? $_REQUEST['reminders']['sms'][$k] : ''
                    ,'text' => isset($_REQUEST['reminders']['text'][$k]) ? $_REQUEST['reminders']['text'][$k] : ''
                    );
        }
    }
    $repetitions = array();
    if (!empty($_REQUEST['repetitions'])) {
        foreach ($_REQUEST['repetitions']['type'] as $k => $repeat_type) {
            if ($repeat_type == '-') {
                continue; // Will not repeat
            }
            if (!in_array($repeat_type, array('year', 'month', 'week', 'day'))) {
                continue; // Invalid repetition type
            }

            if (isset($_REQUEST['repetitions']['has_repunt'][$k]) && $_REQUEST['repetitions']['has_repunt'][$k]) {
                $repeat_until = $_REQUEST['repetitions']['repunt'][$k];
                $repeat_until = basics::format_date($repeat_until, 'Y-m-d H:i:s');
                if (!$repeat_until) {
                    echo '{"error":"'.$WP_msg['CalEvtInvalidRepUnt'].'"}';
                    $repeat_until = null;
                }
            } else {
                $repeat_until = null;
            }
            $repeat = 0;
            $repeat_extra = '';
            if ($repeat_type == 'week') {
                $repeat = $_REQUEST['repetitions']['week'][$k];
                if ($repeat < 0 || $repeat > 6) {
                    $repeat = 0;
                }
            }
            if ($repeat_type == 'month') {
                $repeat = $_REQUEST['repetitions']['month'][$k];
                if ($repeat < 1 || $repeat > 31) {
                    $repeat = 1;
                }
                $repeat_extra = array();
                foreach (range(1, 12, 1) as $month) {
                    if (isset($_REQUEST['repetitions']['repmon_'.$month][$k]) && $_REQUEST['repetitions']['repmon_'.$month][$k]) {
                        $repeat_extra[] = $month;
                    }
                }
                $repeat_extra = implode(',', $repeat_extra);
            }
            if ($repeat_type == 'day') {
                if (!empty($_REQUEST['repetitions']['on_sunday'][$k])) {
                    $repeat += 1;
                }
                if (!empty($_REQUEST['repetitions']['on_saturday'][$k])) {
                    $repeat += 2;
                }
                if (!empty($_REQUEST['repetitions']['on_friday'][$k])) {
                    $repeat += 4;
                }
                if (!empty($_REQUEST['repetitions']['on_thursday'][$k])) {
                    $repeat += 8;
                }
                if (!empty($_REQUEST['repetitions']['on_wednesday'][$k])) {
                    $repeat += 16;
                }
                if (!empty($_REQUEST['repetitions']['on_tuesday'][$k])) {
                    $repeat += 32;
                }
                if (!empty($_REQUEST['repetitions']['on_monday'][$k])) {
                    $repeat += 64;
                }
            }
            $repetitions[$k] = array('type' => $repeat_type, 'repeat' => $repeat, 'extra' => $repeat_extra, 'until' => $repeat_until);
        }
    }

    $payload = array('title' => $_REQUEST['title'], 'location' => $_REQUEST['location'],
            'description' => $_REQUEST['description'], 'type' => $_REQUEST['type'],
            'status' => $_REQUEST['status'], 'opaque' => $_REQUEST['opaque'],
            'start' => $start, 'end' => $end, 'reminders' => $reminders,
            'repetitions' => $repetitions,
            'gid' => isset($_REQUEST['gid']) ? intval($_REQUEST['gid']) : 0
            );

    // External calendar in read/write mode? Trigger local update of it's data
    $groupInfo = $cDB->get_group($payload['gid']);
    if ($groupInfo['type'] == 1 && $groupInfo['rw'] == 'rw'
            && $groupInfo['uri']) {
        $PHM_CAL_IM_FILE = externalCalendarRead($groupInfo, $errno, $errstr);
        if (false === $PHM_CAL_IM_FILE) {
            $cDB->set_remote_calendar_checked($groupInfo['gid'], $errno, $errstr);

            echo '{"error":"'.$errstr.'"}';
            exit;
        }
        $PHM_CAL_IM_DO = 'import';
        $PHM_CAL_IM_UID = $groupInfo['owner'];
        $PHM_CAL_IM_GROUP = $groupInfo['gid'];
        $PHM_CAL_IM_FORMAT = 'ICS';
        $PHM_CAL_IM_SYNC = true;
        require $_PM_['path']['handler'] . '/calendar/exchange.php';
    }
    //

    // Store record
    if ($mode == 'store') {
        $payload['id'] = $eid;
        $res = $cDB->update_event($payload);
    } else {
        $eid = $res = $cDB->add_event($payload);
    }
    // Trigger external update of calendar's data
    if ($groupInfo['type'] == 1 && $groupInfo['rw'] == 'rw'
            && $groupInfo['uri']) {
        if (substr($groupInfo['uri'], 0, 7) == 'file://') {
            $PHM_CAL_EX_PUTTOFILE = substr($groupInfo['uri'], 7);
            $isRemote = false;
        } else {
            $PHM_CAL_EX_PUTTOFILE = $_PM_['path']['temp'].'/'.SecurePassword::generate(16, false, STRONGPASS_DECIMALS | STRONGPASS_LOWERCASE);
            $isRemote = true;
        }

        $PHM_CAL_EX_DO = 'export';
        $PHM_CAL_EX_UID = $groupInfo['owner'];
        $PHM_CAL_EX_GROUP = $groupInfo['gid'];
        $PHM_CAL_EX_FORMAT = 'ICS';
        require $_PM_['path']['handler'] . '/calendar/exchange.php';

        if ($isRemote) {
            $res = externalCalendarWrite($groupInfo, file_get_contents($PHM_CAL_EX_PUTTOFILE), $errno, $errstr);
            if (false === $res) {
                $cDB->set_remote_calendar_checked($groupInfo['gid'], $errno, $errstr);
                echo '{"error":"' . $errstr . '"}';
                exit;
            }
        }
    }
    //

    if ($res) {
        // Handle event attendess
        $extraOutput = array();
        require_once __DIR__.'/invitations.php';
        if (!empty($extraOutput)) {
            $extraOutput['invite_eid'] = $eid;
            $extraOutput['done'] = 1;
            sendJS($extraOutput);
        }
        echo '{"done":"1"}';
    } else {
        echo '{"error":"'.$DB->error().'"}';
    }
    exit;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.event.edit.tpl');
// Userdaten für externe Emailadresse
$userdata = $DB->get_usrdata($_SESSION['phM_uid']);

if ($mode == 'load') {
    $tpl->assign('editmode', 'edit');

    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_update_event']) {
        $tpl->assign_block('save_button');
    }
    if (($perms == 'all' || !empty($perms['delete']))
            && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_delete_event'])) {
        $tpl->assign_block('delete_button');
    }
    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_event']) {
        $tpl->fill_block('saveascopy', 'msg_copyevent', $WP_msg['CalSaveAsCopy']);
    }

    if (substr($event['starts'], 11, 5) == '00:00' && substr($event['ends'], 11, 5) == '23:59') {
        $tpl->assign_block('is_allday');
    }
    // Event attendees
    $t_atl = $tpl->get_block('attendee_line');
    foreach ($cDB->get_event_attendees($eid) as $line) {
        $t_atl->assign(array
                ('id' => $line['id'], 'name' => $line['name'], 'email' => $line['email']
                ,'type' => $line['type'], 'role' => $line['role']
                ,'uuid' => $line['mailhash'], 'status' => $line['status']
                ));
        if (!is_null($line['invited'])) {
            $t_atl->assign_block('is_invited');
            $t_atl->assign('msg_invited_on', str_replace('$1', date($WP_msg['dateformat'], strtotime($line['invited'])), $WP_msg['EvtAttendeeInvitedOn']));
        }
        if (!$line['email'] || !strstr($line['email'], '@')) {
            $t_atl->assign_block('disable_invite_email');
        }
        switch ($line['status']) {
            case 1: $t_atl->assign('msg_rsvp_status', $WP_msg['EvtRSVPyes']);   $t_atl->assign_block('rsvp_yes');   break;
            case 2: $t_atl->assign('msg_rsvp_status', $WP_msg['EvtRSVPno']);    $t_atl->assign_block('rsvp_no');    break;
            case 3: $t_atl->assign('msg_rsvp_status', $WP_msg['EvtRSVPmaybe']); $t_atl->assign_block('rsvp_maybe'); break;
            default: $t_atl->assign('msg_rsvp_status', $WP_msg['EvtRSVPnone']); $t_atl->assign_block('rsvp_none');  break;
        }
        switch ($line['role']) {
        	case 'chair': $t_atl->fill_block('role_chair', 'msg_role', $WP_msg['EvtRoleChair']);  break;
        	case 'req':   $t_atl->fill_block('role_req', 'msg_role', $WP_msg['EvtRoleRequired']); break;
        	case 'opt':   $t_atl->fill_block('role_opt', 'msg_role', $WP_msg['EvtRoleOptional']); break;
        	case 'non':   $t_atl->fill_block('role_none', 'msg_role', $WP_msg['EvtRoleNone']);    break;
        }
        switch ($line['type']) {
        	case 'person':   $t_atl->fill_block('type_person', 'msg_type', $WP_msg['EvtCuPerson']);     break;
        	case 'group':    $t_atl->fill_block('type_group', 'msg_type', $WP_msg['EvtCuGroup']);       break;
        	case 'resource': $t_atl->fill_block('type_resource', 'msg_type', $WP_msg['EvtCuResource']); break;
        	case 'room':     $t_atl->fill_block('type_room', 'msg_type', $WP_msg['EvtCuRoom']);         break;
        	case 'unknown':  $t_atl->fill_block('type_unknown', 'msg_type', $WP_msg['EvtCuUnknown']);   break;
        }
        $tpl->assign('attendee_line', $t_atl);
        $t_atl->clear();
    }
} else {
    // Check quotas
    $quota_num_appointments = $DB->quota_get($_SESSION['phM_uid'], 'calendar', 'number_appointments');
    if (false !== $quota_num_appointments) {
        $quota_appointmentsleft = $cDB->quota_getnumberofrecords(false);
        $quota_appointmentsleft = $quota_num_appointments - $quota_appointmentsleft;
    } else {
        $quota_appointmentsleft = false;
    }
    // This would fail on all systems without provisioning
    try {
        $systemQuota = SystemProvisioning::get('storage');
        $systemUsage = SystemProvisioning::getUsage('total_rounded');
        if ($systemQuota - $systemUsage <= 0) {
            $quota_appointmentsleft = false;
        }
    } catch (Exception $ex) {
        // void
    }

    // No more appointments allowed to save
    if (false !== $quota_appointmentsleft && $quota_appointmentsleft < 1) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['QuotaExceeded']);
        return;
    }
    // End Quota

    $tpl->assign('editmode', 'add');
    $tpl->assign_block('save_button');

    $defReminder = array('mode' => '-', 'time' => 0, 'mailto' => $userdata['email'], 'smsto' => $_PM_['core']['sms_sender'], 'text' => '');
    // Obey default event alerting from setup
    if (!empty($_PM_['calendar']) && !empty($_PM_['calendar']['warn_mode']) && $_PM_['calendar']['warn_mode'] != '-') {
        foreach (array('mode' => 'warn_mode', 'time' => 'warn_time', 'sms' => 'smsto', 'mailto' => 'mailto', 'text' => 'text') as $k => $v) {
            $defReminder[$k] = isset($_PM_['calendar'][$v]) ? $_PM_['calendar'][$v] : '';
        }
    }

    if (defined('FROM_SENDTO')) {
        $eid = ''; // void
        if (empty($event['repetitions'])) {
            $event['repetitions'] = array(0 => array('repunt' => date('Y-m-d H:i'), 'type' => '-', 'repeat' => '', 'extra' => '', 'until_unix' => 0));
        }
        if (empty($event['reminders'])) {
            $event['reminders'] = array(0 => $defReminder);
        }
    } elseif (!empty($_REQUEST['ref_date'])) { // Invoked by double click on empty calendar area
        $event = array('starts' => $_REQUEST['ref_date'], 'ends' => $_REQUEST['ref_date'], 'type' => 1, 'status' => 2, 'gid' => (isset($_REQUEST['gid'])) ? intval($_REQUEST['gid']) : 0
                ,'repetitions' => array(0 => array
                        ('repunt' => $_REQUEST['ref_date'], 'type' => '-', 'repeat' => '', 'extra' => '', 'until_unix' => 0
                        ))
                ,'reminders' => array(0 => $defReminder)
        		);
        $eid = '';
    } else {
        $event = array('starts' => date('Y-m-d H:i'), 'ends' => date('Y-m-d H:i'), 'type' => 1, 'status' => 2
                ,'gid' => (isset($_REQUEST['gid'])) ? intval($_REQUEST['gid']) : 0
    	        ,'repetitions' => array(0 => array
                        ('repunt' => date('Y-m-d H:i'), 'type' => '-', 'repeat' => '', 'extra' => '', 'until_unix' => 0))
                ,'reminders' => array(0 => $defReminder)
    	        );
        $eid = '';
        if (!empty($_REQUEST['start'])) {
            $event['starts'] = $event['ends'] = $_REQUEST['start'];
        }
        if (!empty($_REQUEST['end'])) {
            $event['ends'] = $_REQUEST['end'];
        }
        if (!empty($_REQUEST['location'])) {
            $event['location'] = $_REQUEST['location'];
        }
        if (!empty($_REQUEST['description'])) {
            $event['description'] = $_REQUEST['description'];
        }
        if (!empty($_REQUEST['title'])) {
            $event['title'] = $_REQUEST['title'];
        }
    }
}
// Block für externe Benachrichtigung via SMS
$smsactive = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
if ($smsactive) {
    $smsactive = ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_sms']);
}
if ($smsactive) {
    $t_ea = $tpl->get_block('external_alerting');
    if (isset($_PM_['core']['sms_sender']) && $_PM_['core']['sms_sender']) {
        $t_ea->fill_block('warnsms_profiles', array('sms' => phm_entities($_PM_['core']['sms_sender'])));
    }
    $tpl->assign('external_alerting', $t_ea);
}
// Fill the warnmail combobox
$available_eamils = array();
if (!empty($userdata['email'])) {
    $available_eamils[$userdata['email']] = 1;
}
$Acnt = new DB_Controller_Account();
foreach ($Acnt->getAccountIndex($_SESSION['phM_uid'], true, false) as $k => $v) {
    $accdata = $Acnt->getAccount($_SESSION['phM_uid'], false, $k);
    if ($accdata['address']) {
        $available_eamils[$accdata['address']] = 1;
    }
}
$t_wmp = $tpl->get_block('warnmail_profiles');
foreach ($available_eamils as $k => $v) {
    $t_wmp->assign('email', phm_entities($k));
    $tpl->assign('warnmail_profiles', $t_wmp);
    $t_wmp->clear();
}
$tpl->assign(array
        ('form_target' => PHP_SELF.'?l=edit_event&h=calendar&eid='.$eid.'&save_event=1&'.$passthru
        ,'delete_link' => PHP_SELF.'?l=edit_event&h=calendar&eid='.$eid.'&delete_event=1&'.$passthru
        ,'invite_link' => PHP_SELF.'?l=invitation&h=calendar&'.$passthru.'&send_invitation='
        ,'adb_search_uri' => PHP_SELF.'?l=apiselect&h=contacts&what=email&jqui=1&'.$passthru
        ,'cal_search_uri' => PHP_SELF.'?l=apiselect&h=calendar&jqui=1&'.$passthru.'&what='
        ,'title' => isset($event['title']) ? phm_entities($event['title']) : ''
        ,'location' => isset($event['location']) ? phm_entities($event['location']) : ''
        ,'start' => isset($event['starts']) ? phm_entities(substr($event['starts'], 0, 16)) : ''
        ,'end' => isset($event['ends']) ? phm_entities(substr($event['ends'], 0, 16)) : ''
        ,'repunt' => isset($event['repetitions'][0]['until_unix']) ? phm_entities(date('Y-m-d H:i', $event['repetitions'][0]['until_unix'])) : ''
        ,'description' => isset($event['description']) ? phm_entities($event['description']) : ''
        ,'warn_mail' => isset($event['reminders'][0]['mailto']) ? phm_entities($event['reminders'][0]['mailto']) : ''
        ,'warn_sms' => isset($event['reminders'][0]['smsto']) ? phm_entities($event['reminders'][0]['smsto']) : ''
        ,'warn_text' => isset($event['reminders'][0]['text']) ? phm_entities($event['reminders'][0]['text']) : ''
        ,'msg_reallydelete' => $WP_msg['CalEvtReallyDelete']
        ,'msg_invalidcorrected' => $WP_msg['CalEvtInvalidCorrected']
        ,'head_edit' => $WP_msg['CalHeadEdit']
        ,'msg_title' => $WP_msg['CalTitle']
        ,'msg_loc' => $WP_msg['CalLocation']
        ,'msg_day' => $WP_msg['CalDay']
        ,'msg_days' => $WP_msg['CalDays']
        ,'msg_weeks' => $WP_msg['CalWeeks']
        ,'msg_month' => $WP_msg['CalMonth']
        ,'msg_year' => $WP_msg['CalYear']
        ,'msg_hour' => $WP_msg['CalHour']
        ,'msg_hours' => $WP_msg['CalHours']
        ,'msg_minute' => $WP_msg['CalMinute']
        ,'msg_minutes' => $WP_msg['CalMinutes']
        ,'msg_start' => $WP_msg['CalStart']
        ,'msg_end' => $WP_msg['CalEnd']
        ,'msg_desc' => $WP_msg['CalDescription']
        ,'msg_save' => $WP_msg['save']
        ,'head_warn' => $WP_msg['CalWarnMe']
        ,'msg_warnbeforestart' => $WP_msg['CalWarnBeforeStart']
        ,'msg_warnbeforeend' => $WP_msg['CalWarnBeforeEnd']
        ,'msg_additionalalerts' => $WP_msg['CalAdditionalAlert']
        ,'msg_endlaterbegin' => $WP_msg['CalEndLaterBegin']
        ,'msg_mailto' => $WP_msg['CalViaMailTo']
        ,'msg_smsto' => $WP_msg['CalViaSMSTo']
        ,'msg_del' => $WP_msg['CalDelEvt']
        ,'msg_repaet' => $WP_msg['CalRepeatEvent']
        ,'msg_none' => $WP_msg['CalNever']
        ,'msg_yearly' => $WP_msg['CalYearly']
        ,'msg_monthly' => $WP_msg['CalMonthly']
        ,'msg_weekly' => $WP_msg['CalWeekly']
        ,'msg_daily' => $WP_msg['CalDaily']
        ,'msg_on' => $WP_msg['CalRepOn']
        ,'msg_monday' => $WP_msg['wday'][0]
        ,'msg_tuesday' => $WP_msg['wday'][1]
        ,'msg_wednesday' => $WP_msg['wday'][2]
        ,'msg_thursday' => $WP_msg['wday'][3]
        ,'msg_friday' => $WP_msg['wday'][4]
        ,'msg_saturday' => $WP_msg['wday'][5]
        ,'msg_sunday' => $WP_msg['wday'][6]
        ,'title_monday' => $WP_msg['weekday'][0]
        ,'title_tuesday' => $WP_msg['weekday'][1]
        ,'title_wednesday' => $WP_msg['weekday'][2]
        ,'title_thursday' => $WP_msg['weekday'][3]
        ,'title_friday' => $WP_msg['weekday'][4]
        ,'title_saturday' => $WP_msg['weekday'][5]
        ,'title_sunday' => $WP_msg['weekday'][6]
        ,'msg_jan' => $WP_msg['mnth'][1]
        ,'msg_feb' => $WP_msg['mnth'][2]
        ,'msg_mar' => $WP_msg['mnth'][3]
        ,'msg_apr' => $WP_msg['mnth'][4]
        ,'msg_may' => $WP_msg['mnth'][5]
        ,'msg_jun' => $WP_msg['mnth'][6]
        ,'msg_jul' => $WP_msg['mnth'][7]
        ,'msg_aug' => $WP_msg['mnth'][8]
        ,'msg_sep' => $WP_msg['mnth'][9]
        ,'msg_oct' => $WP_msg['mnth'][10]
        ,'msg_nov' => $WP_msg['mnth'][11]
        ,'msg_dec' => $WP_msg['mnth'][12]
        ,'title_jan' => $WP_msg['month'][1]
        ,'title_feb' => $WP_msg['month'][2]
        ,'title_mar' => $WP_msg['month'][3]
        ,'title_apr' => $WP_msg['month'][4]
        ,'title_may' => $WP_msg['month'][5]
        ,'title_jun' => $WP_msg['month'][6]
        ,'title_jul' => $WP_msg['month'][7]
        ,'title_aug' => $WP_msg['month'][8]
        ,'title_sep' => $WP_msg['month'][9]
        ,'title_oct' => $WP_msg['month'][10]
        ,'title_nov' => $WP_msg['month'][11]
        ,'title_dec' => $WP_msg['month'][12]
        ,'week_firstday' => $WP_msg['week_firstday']
        ,'msg_status' => $WP_msg['CalStatus']
        ,'msg_type' => $WP_msg['CalType']
        ,'msg_none' => $WP_msg['none']
        ,'msg_group' => $WP_msg['group']
        ,'msg_repunt' => $WP_msg['CalRepeatUntil']
        ,'msg_dele' => $WP_msg['del']
        ,'msg_general' => $WP_msg['General']
        ,'msg_reminder' => $WP_msg['CalReminder']
        ,'msg_repetition' => $WP_msg['CalRepetition']
        ,'msg_attendees' => $WP_msg['CalAttendees']
        ,'msg_attachments' => $WP_msg['attachs']
        ,'msg_allday' => $WP_msg['allday']
        ,'msg_name' => $WP_msg['Name']
        ,'msg_role' => $WP_msg['Role']
        ,'msg_CuType' => $WP_msg['CuType']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'msg_attendance' => $WP_msg['CalAttendance']
        ,'msg_attendance_yes' => $WP_msg['EvtRSVPyes']
        ,'msg_attendance_no' => $WP_msg['EvtRSVPno']
        ,'msg_attendance_maybe' => $WP_msg['EvtRSVPmaybe']
        ,'msg_attendance_none' => $WP_msg['EvtRSVPnone']
        ,'msg_edit' => $WP_msg['edit']
        ,'msg_invite_email' => $WP_msg['EvtAttendeInviteEmail']
        ,'msg_attendee' => $WP_msg['CalAttendee']
        ,'msg_email' => $WP_msg['email']
        ,'msg_role_optional' => $WP_msg['EvtRoleOptional']
        ,'msg_role_required' => $WP_msg['EvtRoleRequired']
        ,'msg_role_chair' => $WP_msg['EvtRoleChair']
        ,'msg_role_none' => $WP_msg['EvtRoleNone']
        ,'msg_type_person' => $WP_msg['EvtCuPerson']
        ,'msg_type_group' => $WP_msg['EvtCuGroup']
        ,'msg_type_resource' => $WP_msg['EvtCuResource']
        ,'msg_type_room' => $WP_msg['EvtCuRoom']
        ,'msg_type_unknown' => $WP_msg['EvtCuUnknown']
        ,'msg_delattendee' => $WP_msg['QDelEvtAttendee']
        ,'msg_sending_invitation' => $WP_msg['SendingInvitation']
        ,'msg_duration' => $WP_msg['CalDuration']
        ));
// Repeat Monthly selector
$t_md = $tpl->get_block('repmonlin');
foreach (range(1, 31) as $day) {
    $t_md->assign(array('day' => $day, 'msg_day' => sprintf('%02d', $day).'.'));
    if (isset($event['repetitions'][0]['type']) && $event['repetitions'][0]['type'] == 'month' && $event['repetitions'][0]['repeat'] == $day) {
        $t_md->assign_block('sel');
    }
    $tpl->assign('repmonlin', $t_md);
    $t_md->clear();
}
// Repeat weekly selector
$t_we = $tpl->get_block('repweelin');
foreach (range(0, 6) as $day) {
    $t_we->assign(array('day' => (6 == $day) ? 0 : $day+1, 'msg_day' => $WP_msg['weekday'][$day]));
    if (isset($event['repetitions'][0]['type']) && $event['repetitions'][0]['type'] == 'week'
            && $event['repetitions'][0]['repeat'] == ((6 == $day) ? 0 : $day + 1)) {
        $t_we->assign_block('sel');
    }
    $tpl->assign('repweelin', $t_we);
    $t_we->clear();
}
// Repeat daily selectors
if (isset($event['repetitions'][0]['type']) && $event['repetitions'][0]['type'] == 'day') {
    if ($event['repetitions'][0]['repeat'] &  1) {
        $tpl->assign('sel_sunday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] &  2) {
        $tpl->assign('sel_saturday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] &  4) {
        $tpl->assign('sel_friday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] &  8) {
        $tpl->assign('sel_thursday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] & 16) {
        $tpl->assign('sel_wednesday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] & 32) {
        $tpl->assign('sel_tuesday', ' checked="checked"');
    }
    if ($event['repetitions'][0]['repeat'] & 64) {
        $tpl->assign('sel_monday', ' checked="checked"');
    }
}

if (isset($event['repetitions'][0]['type']) && $event['repetitions'][0]['type'] == 'month') {
    $myEventExtra = array();
    if (isset($event['repetitions'][0]['extra']) && $event['repetitions'][0]['extra']) {
        $myEventExtra = explode(',', $event['repetitions'][0]['extra']);
    }
    foreach ($myEventExtra as $month) { // Check boxes on month in set
        $tpl->assign('sel_repmon_'.$month, ' checked="checked"');
    }

}
// Preselect repetition type
if (!isset($event['repetitions'][0]['type']) || $event['repetitions'][0]['type'] == '-') {
    $tpl->assign('selrepeatnone', ' checked="checked"');
} elseif ($event['repetitions'][0]['type'] == 'year') {
    $tpl->assign('selrepeatyear', ' checked="checked"');
} elseif ($event['repetitions'][0]['type'] == 'month') {
    $tpl->assign('selrepeatmonth', ' checked="checked"');
} elseif ($event['repetitions'][0]['type'] == 'week') {
    $tpl->assign('selrepeatweek', ' checked="checked"');
} elseif ($event['repetitions'][0]['type'] == 'day') {
    $tpl->assign('selrepeatday', ' checked="checked"');
}
// In case there's a repetition UNTIL defined, assign the right block in the template
if (isset($event['repetitions'][0]['until_unix']) && $event['repetitions'][0]['until_unix'] > 0) {
    $tpl->assign_block('has_repunt');
}
// Warn me before ...
$t_mrm = $tpl->get_block('multi_reminders');
foreach ($event['reminders'] as $k => $v) {
    if ($k == 0) {
        $tpl->assign_block('warn');
        if ('s' == $v['mode']) {
            $tpl->assign_block('s_w_s');
        }
        if ('e' == $v['mode']) {
            $tpl->assign_block('s_w_e');
        }
        if ($v['time'] >= 604800 && (intval($v['time'] / 604800) == $v['time'] / 604800)) {
            $tpl->assign_block('s_w_w');
            $tpl->assign('warn_time', $v['time'] / 604800);
        } elseif ($v['time'] >= 86400 && (intval($v['time'] / 86400) == $v['time'] / 86400)) {
            $tpl->assign_block('s_w_d');
            $tpl->assign('warn_time', $v['time'] / 86400);
        } elseif ($v['time'] >= 3600 && (intval($v['time'] / 3600) == $v['time'] / 3600)) {
            $tpl->assign_block('s_w_h');
            $tpl->assign('warn_time', $v['time'] / 3600);
        } elseif ($v['time'] >= 60 && (intval($v['time'] / 60) == $v['time'] / 60)) {
            $tpl->assign_block('s_w_m');
            $tpl->assign('warn_time', $v['time'] / 60);
        } else {
            $tpl->assign_block('s_w_m');
            $tpl->assign('warn_time', 0);
        }
    }
    // Hidden JS array holding all entries
    if ($v['time'] >= 604800 && (intval($v['time'] / 604800) == $v['time'] / 604800)) {
        $range = 'w';
        $time = $v['time'] / 604800;
    } elseif ($v['time'] >= 86400 && (intval($v['time'] / 86400) == $v['time'] / 86400)) {
        $range = 'd';
        $time = $v['time'] / 86400;
    } elseif ($v['time'] >= 3600 && (intval($v['time'] / 3600) == $v['time'] / 3600)) {
        $range = 'h';
        $time = $v['time'] / 3600;
    } elseif ($v['time'] >= 60 && (intval($v['time'] / 60) == $v['time'] / 60)) {
        $range = 'm';
        $time = $v['time'] / 60;
    } else {
        $range = 'm';
        $time = 0;
    }
    $t_mrm->assign(array('time' => $time, 'range' => $range, 'mode' => $v['mode']
            ,'mail' =>  phm_addcslashes($v['mailto'], "'")
            ,'sms' =>  !empty($v['smsto']) ? phm_addcslashes($v['smsto'], "'") : ''
            ,'text' => phm_addcslashes($v['text'], "'")));
    $tpl->assign('multi_reminders', $t_mrm);
    $t_mrm->clear();
}
// Multiple repetitions
$t_mrp = $tpl->get_block('multi_repetitions');
foreach ($event['repetitions'] as $k => $v) {
    $t_mrp->assign(array
            ('type' => $v['type']
            ,'repeat' => trim($v['repeat'])
            ,'extra' => trim($v['extra'])
            ,'until' => $v['until_unix'] > 0 ? date('Y-m-d H:i', $v['until_unix']) : ''
            ,'has_until' => $v['until_unix'] > 0 ? 1 : 0
            ));
    $tpl->assign('multi_repetitions', $t_mrp);
    $t_mrp->clear();
}

$t_stat = $tpl->get_block('statusline');
foreach (array(0 => '-', 10 => $WP_msg['CalStatTentative'], 11 => $WP_msg['CalStatNeedsAction']
        //,1 => $WP_msg['CalStatDueForApp']
        ,2 => $WP_msg['CalStatApproved'], 3 => $WP_msg['CalStatCancelled']
        //,4 => $WP_msg['CalStatDelegated']
        ) as $k => $v) {
    $t_stat->assign(array('id' => $k, 'name' => $v));
    if (isset($event['status']) && $event['status'] == $k) {
        $t_stat->assign_block('sel');
    }
    $tpl->assign('statusline', $t_stat);
    $t_stat->clear();
}
$t_type = $tpl->get_block('typeline');
foreach (array(0 => '-', 1 => $WP_msg['CalTyAppointment'], 2 => $WP_msg['CalTyHoliday']
        ,3 => $WP_msg['CalTyBirthday'], 4 => $WP_msg['CalTyPersonal'], 5 => $WP_msg['CalTyEducation']
        ,6 => $WP_msg['CalTyTravel'], 7 => $WP_msg['CalTyAnniversary'], 8 => $WP_msg['CalTyNotInOffice']
        ,9 => $WP_msg['CalTySickDay'], 10 => $WP_msg['CalTyMeeting'], 11 => $WP_msg['CalTyVacation']
        ,12 => $WP_msg['CalTyPhoneCall'], 13 => $WP_msg['CalTyBusiness'], 14 => $WP_msg['CalTyNonWorkingHours']
        ,50 => $WP_msg['CalTySpecialOccasion']) as $k => $v) {
    $t_type->assign(array('id' => $k, 'name' => $v));
    if (isset($event['type']) && $event['type'] == $k) {
        $t_type->assign_block('sel');
    }
    $tpl->assign('typeline', $t_type);
    $t_type->clear();
}
$t_l = $tpl->get_block('groupline');
foreach ($cDB->get_grouplist(0) as $v) {
    if ($v['type'] == 1 && $v['rw'] != 'rw') {
        if ($mode == 'create') {
            continue;
        } else {
            $t_l->assign_block('readonly');
        }
    }
    if ($v['owner'] != $_SESSION['phM_uid']) {
        $perms = $DB->getUserSharedFolderPermissions($_SESSION['phM_uid'], 'calendar', $v['gid']);
        if (empty($perms['write'])) {
            if ($mode == 'create') {
                continue;
            } else {
                $t_l->assign_block('readonly');
            }
        }
    }
    $t_l->assign(array(
            'id' => $v['gid'],
            'name' => phm_entities($v['name'].($v['owner'] != $_SESSION['phM_uid'] ? ' — '.$v['username'] : ''))
            ));
    if (isset($event['gid']) && $v['gid'] == $event['gid']) {
        $t_l->assign_block('selected');
    }
    $tpl->assign('groupline', $t_l);
    $t_l->clear();
}
$t_pl = $tpl->get_block('projectline');
foreach ($cDB->get_projectlist(0) as $v) {
    $t_pl->assign(array('id' => $v['id'], 'name' => $v['title']));
    if (isset($event['pid']) && $v['id'] == $event['gid']) {
        $t_pl->assign_block('selected');
    }
    $tpl->assign('projectline', $t_pl);
    $t_pl->clear();
}
$t_opaq = $tpl->get_block('opacityline');
foreach (array(1 => $WP_msg['CalOpaqOpaque'], 0 => $WP_msg['CalOpaqTransparent']) as $k => $v) {
    $t_opaq->assign(array('id' => $k, 'name' => $v));
    if (isset($event['opaque']) && $event['opaque'] == $k) {
        $t_opaq->assign_block('sel');
    }
    $tpl->assign('opacityline', $t_opaq);
    $t_opaq->clear();
}