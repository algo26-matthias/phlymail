<?php
/**
 * edit_task.php - Edit new / existing task
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Calendar handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.7 2015-02-18 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$passthru = give_passthrough(1);
if (!isset($_PM_['core']['sms_sender'])) {
    $_PM_['core']['sms_sender'] = '';
}

$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
if (isset($_REQUEST['delete_task']) && $_REQUEST['delete_task']) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_delete_task']) {
        sendJS(array('error' => $WP_msg['PrivNoAccess']), 1, 1);
    }
    sendJS($cDB->delete_task($_REQUEST['tid']) ? array('done' => 1) : array('error' => $WP_msg['TskNoDelTsk']), 1, 1);
}

if (isset($_REQUEST['save_task']) && $_REQUEST['save_task']) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_task'] && !$_SESSION['phM_privs']['calendar_update_task']) {
        echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
        exit;
    }
    // Convert entered dates and times into something database friendly
    foreach (array('start', 'end') as $k) {
    	${$k} = (isset($_REQUEST[$k])) ? $_REQUEST[$k] : false;
    }
    $start = !empty($_REQUEST['has_start']) ? basics::format_date($start, 'Y-m-d H:i:s') : 'NULL';
    $end = !empty($_REQUEST['has_end']) ? basics::format_date($end, 'Y-m-d H:i:s') : 'NULL';
    if (!$start) {
        echo '{"error":"'.$WP_msg['CalEvtInvalidStart'].'"}';
        exit;
    }
    if (!$end) {
        echo '{"error":"'.$WP_msg['CalEvtInvalidEnd'].'"}';
        exit;
    }

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
    $payload = array
            ('title' => $_REQUEST['title']
            ,'type' => $_REQUEST['type']
            ,'status' => $_REQUEST['status']
            ,'location' => $_REQUEST['location']
            ,'importance' => $_REQUEST['importance']
            ,'completion' => $_REQUEST['completion']
            ,'start' => $start
            ,'end' => $end
            ,'reminders' => $reminders
            ,'description' => $_REQUEST['description']
            ,'gid' => isset($_REQUEST['gid']) ? intval($_REQUEST['gid']) : 0
            );
    if (isset($_REQUEST['tid']) && $_REQUEST['tid'] && (!isset($_REQUEST['copytask']) || !$_REQUEST['copytask'])) {
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_update_task']) {
            echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
            exit;
        }
        $payload['id'] = $_REQUEST['tid'];
        $res = $cDB->update_task($payload);
        $tid = intval($_REQUEST['tid']);
    } else {
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_task']) {
            echo '{"error":"'.$WP_msg['PrivNoAccess'].'"}';
            exit;
        }
        $tid = $res = $cDB->add_task($payload);
    }
    if ($res) {
        echo '{"done":"1"}';
    } else {
        echo '{"error":"'.$DB->error().'"}';
    }
    exit;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.task.edit.tpl');
// Userdaten für externe Emailadresse
$userdata = $DB->get_usrdata($_SESSION['phM_uid']);

if (isset($_REQUEST['tid']) && $_REQUEST['tid']) {
    $tpl->assign('editmode', 'edit');

    $tid = intval($_REQUEST['tid']);
    $task = $cDB->get_task($tid);

    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_update_task']) {
        $tpl->assign_block('save_button');
    }
    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_delete_task']) {
        $tpl->assign_block('delete_button');
    }
    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_task']) {
        $tpl->fill_block('saveascopy', 'msg_copytask', $WP_msg['CalSaveAsCopy']);
    }
} else {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_add_task']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    // Check quotas
    $quota_num_tasks = $DB->quota_get($_SESSION['phM_uid'], 'calendar', 'number_tasks');
    if (false !== $quota_num_tasks) {
        $quota_tasksleft = $cDB->quota_getnumberofrecords(false);
        $quota_tasksleft = $quota_num_tasks - $quota_tasksleft;
    } else {
        $quota_tasksleft = false;
    }
    // This would fail on all systems without provisioning
    try {
        $systemQuota = SystemProvisioning::get('storage');
        $systemUsage = SystemProvisioning::getUsage('total_rounded');
        if ($systemQuota - $systemUsage <= 0) {
            $quota_tasksleft = false;
        }
    } catch (Exception $ex) {
        // void
    }

    // No more tasks allowed to save
    if (false !== $quota_tasksleft && $quota_tasksleft < 1) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['QuotaExceeded']);
        return;
    }
    // End Quota

    $tpl->assign('editmode', 'add');
    $tpl->assign_block('save_button');

    if (defined('FROM_SENDTO')) {
        // void
    } elseif (isset($_REQUEST['ref_date']) && $_REQUEST['ref_date']) { // Invoked by double click on empty calendar area
        $task = array('starts' => $_REQUEST['ref_date'], 'ends' => $_REQUEST['ref_date'], 'type' => 1, 'status' => 2
                ,'importance' => 5, 'gid' => (isset($_REQUEST['gid'])) ? intval($_REQUEST['gid']) : 0
                ,'reminders' => array(0 => array
                        ('mode' => '-', 'time' => 0, 'mailto' => $userdata['email'], 'smsto' => $_PM_['core']['sms_sender'], 'text' => ''))
        		);
        $tid = '';
    } else {
        $task = array('starts' => date('Y-m-d H:i'), 'ends' => date('Y-m-d H:i') ,'type' => 1, 'status' => 2, 'start' => time(), 'end' => time()
                ,'importance' => 5, 'gid' => (isset($_REQUEST['gid'])) ? intval($_REQUEST['gid']) : 0
                ,'reminders' => array(0 => array
                        ('mode' => '-', 'time' => 0, 'mailto' => $userdata['email'], 'smsto' => $_PM_['core']['sms_sender'], 'text' => ''))
    	        );
        $tid = '';
        if (!empty($_REQUEST['start'])) {
            $task['starts'] = $task['ends'] = $_REQUEST['start'];
        }
        if (!empty($_REQUEST['end'])) {
            $task['ends'] = $_REQUEST['end'];
        }
        if (isset($_REQUEST['location']) && $_REQUEST['location']) {
            $task['location'] = $_REQUEST['location'];
        }
        if (isset($_REQUEST['description']) && $_REQUEST['description']) {
            $task['description'] = $_REQUEST['description'];
        }
        if (isset($_REQUEST['title']) && $_REQUEST['title']) {
            $task['title'] = $_REQUEST['title'];
        }
    }
    // Obey default task alerting from setup
    if (isset($_PM_['calendar']) && isset($_PM_['calendar']['warn_mode']) && $_PM_['calendar']['warn_mode'] != '-') {
        foreach (array('mode' => 'warn_mode', 'time' => 'warn_time', 'sms' => 'smsto', 'mailto' => 'mailto', 'text' => 'text') as $k => $v) {
            $task['reminders'][0][$k] = isset($_PM_['calendar'][$v]) ? $_PM_['calendar'][$v] : '';
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
if ($userdata['email']) {
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
    $t_wmp->assign('email', $k);
    $tpl->assign('warnmail_profiles', $t_wmp);
    $t_wmp->clear();
}
$tpl->assign(array
        ('form_target' => PHP_SELF.'?l=edit_task&h=calendar&tid='.$tid.'&save_task=1&'.$passthru
        ,'delete_link' => PHP_SELF.'?l=edit_task&h=calendar&tid='.$tid.'&delete_task=1&'.$passthru
        ,'invite_link' => PHP_SELF.'?l=invitation&h=calendar&'.$passthru.'&send_invitation='
        ,'adb_search_uri' => PHP_SELF.'?l=apiselect&h=contacts&what=email&jqui=1&'.$passthru
        ,'cal_search_uri' => PHP_SELF.'?l=apiselect&h=calendar&jqui=1&'.$passthru.'&what='
        ,'title' => isset($task['title']) ? phm_entities($task['title']) : ''
        ,'location' => isset($task['location']) ? phm_entities($task['location']) : ''
        ,'description' => isset($task['description']) ? phm_entities($task['description']) : ''
        ,'completion' => isset($task['completion']) ? intval($task['completion']) : '0'
        ,'start' => isset($task['starts']) ? phm_entities(substr($task['starts'], 0, 16)) : ''
        ,'end' => isset($task['ends']) ? phm_entities(substr($task['ends'], 0, 16)) : ''
        ,'warn_mail' => isset($task['reminders'][0]['mailto']) ? phm_entities($task['reminders'][0]['mailto']) : ''
        ,'warn_sms' => isset($task['reminders'][0]['smsto']) ? phm_entities($task['reminders'][0]['smsto']) : ''
        ,'warn_text' => isset($task['reminders'][0]['text']) ? phm_entities($task['reminders'][0]['text']) : ''
        ,'msg_reallydelete' => $WP_msg['TskTskReallyDelete']
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
        ,'msg_end' => $WP_msg['TskDue']
        ,'msg_completion' => $WP_msg['TskCompletion']
        ,'msg_prio' => $WP_msg['TskImportance']
        ,'msg_desc' => $WP_msg['CalDescription']
        ,'msg_save' => $WP_msg['save']
        ,'head_warn' => $WP_msg['CalWarnMe']
        ,'msg_warnbeforestart' => $WP_msg['TskWarnBeforeStart']
        ,'msg_warnbeforeend' => $WP_msg['TskWarnBeforeEnd']
        ,'msg_additionalalerts' => $WP_msg['CalAdditionalAlert']
        ,'msg_endlaterbegin' => $WP_msg['CalEndLaterBegin']
        ,'msg_mailto' => $WP_msg['CalViaMailTo']
        ,'msg_smsto' => $WP_msg['CalViaSMSTo']
        ,'msg_del' => $WP_msg['CalDelEvt']
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
        ,'msg_dele' => $WP_msg['del']
        ,'msg_general' => $WP_msg['General']
        ,'msg_reminder' => $WP_msg['CalReminder']
        ,'msg_attachments' => $WP_msg['attachs']
        ,'msg_name' => $WP_msg['Name']
        ,'msg_role' => $WP_msg['Role']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'msg_edit' => $WP_msg['edit']
        ,'msg_email' => $WP_msg['email']
        ,'msg_duration' => $WP_msg['CalDuration']
        ));
if (!is_null($task['start']) && $task['start']) {
    $tpl->assign_block('has_start');
}
if (!is_null($task['end']) && $task['end']) {
    $tpl->assign_block('has_end');
}

// Warn me before ...
$t_mrm = $tpl->get_block('multi_reminders');
foreach ($task['reminders'] as $k => $v) {
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
            ,'sms' =>  phm_addcslashes($v['smsto'], "'")
            ,'text' => phm_addcslashes($v['text'], "'")));
    $tpl->assign('multi_reminders', $t_mrm);
    $t_mrm->clear();
}
$t_stat = $tpl->get_block('statusline');
foreach (array(0 => '-'
        ,10 => $WP_msg['CalStatTentative']
        ,11 => $WP_msg['CalStatNeedsAction']
        //,1 => $WP_msg['CalStatDueForApp']
        ,2 => $WP_msg['CalStatApproved']
        ,3 => $WP_msg['CalStatCancelled']
        //,4 => $WP_msg['CalStatDelegated']
        ,5 => $WP_msg['TskStatInProcess']
        ,6 => $WP_msg['TskStatCompleted']) as $k => $v) {
    $t_stat->assign(array('id' => $k, 'name' => $v));
    if (isset($task['status']) && $task['status'] == $k) {
        $t_stat->assign_block('sel');
    }
    $tpl->assign('statusline', $t_stat);
    $t_stat->clear();
}
$t_type = $tpl->get_block('typeline');
foreach (array(0 => '-'
        ,1 => $WP_msg['TskTyTask']
        ,2 => $WP_msg['CalTyHoliday']
        ,3 => $WP_msg['CalTyBirthday']
        ,4 => $WP_msg['CalTyPersonal']
        ,5 => $WP_msg['CalTyEducation']
        ,6 => $WP_msg['CalTyTravel']
        ,7 => $WP_msg['CalTyAnniversary']
        ,8 => $WP_msg['CalTyNotInOffice']
        ,9 => $WP_msg['CalTySickDay']
        ,10 => $WP_msg['CalTyMeeting']
        ,11 => $WP_msg['CalTyVacation']
        ,12 => $WP_msg['CalTyPhoneCall']
        ,13 => $WP_msg['CalTyBusiness']
        ,14 => $WP_msg['CalTyNonWorkingHours']
        ,50 => $WP_msg['CalTySpecialOccasion']) as $k => $v) {
    $t_type->assign(array('id' => $k, 'name' => $v));
    if (isset($task['type']) && $task['type'] == $k) {
        $t_type->assign_block('sel');
    }
    $tpl->assign('typeline', $t_type);
    $t_type->clear();
}
if (!isset($task['importance'])) {
    $task['importance'] = 5;
}
$t_prio = $tpl->get_block('prioline');
foreach (array(0 => '-'
        ,1 => '1 / A1 - '.$WP_msg['TskImpVHigh']
        ,2 => '2 / A2'
        ,3 => '3 / A3 - '.$WP_msg['TskImpHigh']
        ,4 => '4 / B1'
        ,5 => '5 / B2 - '.$WP_msg['TskImpNormal']
        ,6 => '6 / B3'
        ,7 => '7 / C1 - '.$WP_msg['TskImpLow']
        ,8 => '8 / C2'
        ,9 => '9 / C3 - '.$WP_msg['TskImpVLow']) as $k => $v) {
    $t_prio->assign(array('id' => $k, 'name' => $v));
    if ($task['importance'] == $k) {
        $t_prio->assign_block('sel');
    }
    $tpl->assign('prioline', $t_prio);
    $t_prio->clear();
}
$t_l = $tpl->get_block('groupline');
foreach ($cDB->get_grouplist(0) as $v) {
    $t_l->assign(array('id' => $v['gid'], 'name' => phm_entities($v['name'])));
    if (isset($task['gid']) && $v['gid'] == $task['gid']) {
        $t_l->assign_block('selected');
    }
    $tpl->assign('groupline', $t_l);
    $t_l->clear();
}
$t_pl = $tpl->get_block('projectline');
foreach ($cDB->get_projectlist(0) as $v) {
    $t_pl->assign(array('id' => $v['id'], 'name' => phm_entities($v['title'])));
    if (isset($event['pid']) && $v['id'] == $event['gid']) {
        $t_pl->assign_block('selected');
    }
    $tpl->assign('projectline', $t_pl);
    $t_pl->clear();
}