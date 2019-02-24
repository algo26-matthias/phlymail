<?php
/**
 * Specific view for celndar items
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Mobile interface
 * @subpackage Calendar handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
defined('PHM_MOBILE') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_see_calendar']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

////// Den Quatsch vielleicht doch wieder über den Loader abfeuern?
$myDir = __DIR__;
if (file_exists($myDir.'/lang.'.$WP_msg['language'].'.php')) {
    require_once($myDir.'/lang.'.$WP_msg['language'].'.php');
} else {
    require_once($myDir.'/lang.de.php');
}
require_once($myDir.'/functions.php');
///////

$folder = intval($folder);

$passthrough = give_passthrough(1);
$base_link = PHP_SELF.'?h=calendar&a=ilist&f='.$folder.'&'.$passthrough;
$edit_evt_link = PHP_SELF.'?h=calendar&l=edit_event&'.$passthrough;
$edit_tsk_link = PHP_SELF.'?h=calendar&l=edit_task&'.$passthrough;
$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
if ($folder == 0) {
    $cDB->setQueryType('root');
}
$group = $cDB->get_group($folder, false);
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday'])) {
    $_PM_['calendar']['wday'] = array(0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 0, 6 => 0);
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_start'])) $_PM_['calendar']['wday_start'] = 16;
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_end'])) $_PM_['calendar']['wday_end'] = 33;

// This might be configured later on
$weeks_show_next = 4;
$weeks_show_last = 1;
$today = getdate();
if (isset($_REQUEST['gototoday']) && $_REQUEST['gototoday']) {
    $show_day = $_SESSION['calendar_show_day'] = $today;
    $reference_date = $_SESSION['calendar_ref_day'] = time();
} elseif (isset($_REQUEST['jumpto']) && $_REQUEST['jumpto']) {
    if ('nextevent' == $_REQUEST['jumpto']) {
        $new = $cDB->get_nextday_withevents(isset($_SESSION['calendar_ref_day']) ? $_SESSION['calendar_ref_day'] : time(), $folder);
        if ($new) {
            $reference_date = $_SESSION['calendar_ref_day'] = $new;
            $show_day = $_SESSION['calendar_show_day'] = getdate($new);
        } else {
            $reference_date = $_SESSION['calendar_ref_day'];
            $show_day = $_SESSION['calendar_show_day'];
        }
    } elseif ('prevevent' == $_REQUEST['jumpto']) {
        $new = $cDB->get_prevday_withevents(isset($_SESSION['calendar_ref_day']) ? $_SESSION['calendar_ref_day'] : time(), $folder);
        if ($new) {
            $reference_date = $_SESSION['calendar_ref_day'] = $new;
            $show_day = $_SESSION['calendar_show_day'] = getdate($new);
        } else {
            $reference_date = $_SESSION['calendar_ref_day'];
            $show_day = $_SESSION['calendar_show_day'];
        }
    }
} elseif (isset($_REQUEST['goto_day']) && $_REQUEST['goto_day']
        && preg_match('!^(\d{4})-(\d{1,2})-(\d{1,2})$!', $_REQUEST['goto_day'], $found)) {
    $new = mktime(0, 0, 0, $found[2], $found[3], $found[1]);
    if ($new) {
        $reference_date = $_SESSION['calendar_ref_day'] = $new;
        $show_day = $_SESSION['calendar_show_day'] = getdate($new);
    } else {
        $reference_date = $_SESSION['calendar_ref_day'];
        $show_day = $_SESSION['calendar_show_day'];
    }
} else {
    if (isset($_REQUEST['show_day']) && $_REQUEST['show_day']) {
        $show_day = $_SESSION['calendar_show_day'] = getdate($_REQUEST['show_day']);
        $reference_date = $_SESSION['calendar_ref_day'] = $_REQUEST['show_day'];
    } elseif (isset($_SESSION['calendar_show_day']) && $_SESSION['calendar_show_day']) {
        $show_day = $_SESSION['calendar_show_day'];
    } else {
        $show_day = $_SESSION['calendar_show_day'] = $today;
    }
    if (isset($_REQUEST['skim']) && $_REQUEST['skim']) {
        $reference_date = $_SESSION['calendar_ref_day'] = $_REQUEST['skim'];
    } elseif (isset($_SESSION['calendar_ref_day']) && $_SESSION['calendar_ref_day']) {
        $reference_date = $_SESSION['calendar_ref_day'];
    } else {
        $reference_date = $_SESSION['calendar_ref_day'] = time();
    }
}
if (!empty($_REQUEST['pattern'])) { // Force view mode if search is active
    $_PM_['calendar']['viewmode'] = 'list';
} elseif (isset($_REQUEST['viewmode']) && $_REQUEST['viewmode']) {
    $_PM_['calendar']['viewmode'] = $_REQUEST['viewmode'];
} else {
    $_PM_['calendar']['viewmode'] = 'month';
}

if ($_PM_['calendar']['viewmode'] == 'day') {

    $oneback    = strtotime('-1 day', $show_day[0]);
    $oneforward = strtotime('+1 day', $show_day[0]);

    $tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.dayview.tpl');
    $tpl->assign(array(
            'pageTitle' => $folder == 0 ? $WP_msg['CalMyEvents'] : $group['name'],
            'subTitle' => date($WP_msg['dateformat_old'], $show_day[0]),
            'oneback' => htmlspecialchars($base_link.'&viewmode=day&show_day='.$oneback),
            'oneforward' => htmlspecialchars($base_link.'&viewmode=day&show_day='.$oneforward)
            ));

    $num = 0;
    $t_evt = $tpl->get_block('eventline');
    foreach ($cDB->date_get_eventlist($show_day['year'].'-'.$show_day['mon'].'-'.$show_day['mday'], $folder) as $line) {
        $start = getdate($line['start']);
        $end   = getdate($line['end']);
        if ($start['mon'].$start['mday'] != $show_day['mon'].$show_day['mday']) {
            $start['hours'] = $start['minutes'] = '00';
        }
        if ($end['mon'].$end['mday'] != $show_day['mon'].$show_day['mday']) {
            $end['hours'] = '23';
            $end['minutes'] = '59';
        }
        $t_evt->assign(array(
                'id' => $num,
                'eid' => $line['id'],
                'link' => htmlspecialchars($edit_evt_link.'&eid='.$line['id']),
                'primary' => $line['title'],
                'alarm' => ($line['warn_mode'] != '-') ? 1 : 0
                ));
        if (!empty($line['location'])) {
            $t_evt->fill_block('tertiary', 'tertiary', $line['location']);
        }
        $t_evt->fill_block('secondary', 'secondary', date($WP_msg['dateformat'], $line['start']).' &rarr; '.date($WP_msg['dateformat'], $line['end']));
        if (!empty($line['colour'])) {
            $t_evt->fill_block('has_colour', 'colour', $line['colour']);
        }
        $tpl->assign('eventline', $t_evt);
        $t_evt->clear();
        ++$num;
    }

} else {
    $lastmon = strtotime('1 '.date('M Y', $reference_date).' 00:00:00');
    $nextsun = strtotime('-1 second', strtotime('+1 month', $lastmon));

    $year = date('y', $lastmon);
    $month = date('n', $lastmon);
    $oneback    = strtotime('-1 month', $lastmon);
    $oneforward = strtotime('+1 month', $lastmon);
    $curr_i = $lastmon;
    $yesterday = getdate($curr_i);
    $start_wday = date('w', $curr_i);
    if ($start_wday == 1) {
        $weekcount = 1;
    } else {
        $weekcount = 1;
        $filled = ($start_wday == 0) ? 1 : 8 - $start_wday;
        $curr_i = strtotime('-' . (7 - $filled) . 'day', $curr_i);
    }
    $end_wday = date('w', $nextsun);
    if ($end_wday != 0) { // Ain't a sunday, bro
        $nextsun = strtotime('next sunday +23 hour +59 minute', $nextsun);
    }
    $holidays = $cDB->daterange_getholidays(date('Y-m-d', $curr_i), date('Y-m-d', $nextsun));

    $tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.monthview.tpl');
    $tpl->assign(array(
            'pageTitle' => $folder == 0 ? $WP_msg['CalMyEvents'] : $group['name'],
            'subTitle' => $WP_msg['month'][$month].' \''.$year
            ));
    foreach (array(0 => 'monday', 1 => 'tuesday', 2 => 'wednesday', 3 => 'thursday', 4 => 'friday', 5 => 'saturday', 6 => 'sunday') as $k => $v) {
        if (!isset($_PM_['calendar']['wday'][$k]) || !$_PM_['calendar']['wday'][$k]) $tpl->assign('label_'.$v, ' sunday');
    }

    $tm_week = $tpl->get_block('mnth_weekline');
    $tm_dc = $tm_week->get_block('mnth_daycell');
    $tm_kw  = $tm_dc->get_block('mnth_kw');

    $num = 0;
    while ($curr_i < $nextsun) {
        $curr_date = getdate($curr_i);
        $curr_date['mth_day'] = ($curr_date['mon']*100) + $curr_date['mday'];
        // Get day of week, transform it to the German base, where monday is day 0, sunday is day 6
        $weekday = date('w', $curr_i)-1;
        // barrelshifting the sunday from the beginning of the list to the end
        if (-1 == $weekday) $weekday = 6;
        $is_wday = !empty($_PM_['calendar']['wday'][$weekday]);

        $is_holiday = (isset($holidays[date('Y-m-d', $curr_i)]));
        // Find out, whether that day has scheduled events

        $year = date('y', $curr_i);

        $dayclass = '';
        if (date('d', $curr_i) == date('d', time())) {
            $dayclass .= ' today';
        }
        if (date('d', $curr_i) == date('d', $reference_date)) {
            $dayclass .= ' selected';
        }
        if ($is_holiday) {
            $dayclass .= ' holiday';
            $tm_dc->fill_block('li_holiday', 'holiday', $holidays[date('Y-m-d', $curr_i)]);
        }
        if (!$is_wday) {
            $dayclass .= ' weekend';
        }
        if (date('n', $curr_i) == date('n', time())) {
            $dayclass .= ' current';
        }

        $day_group = array();

        // Place the event data in the template for JS to display them
        foreach ($cDB->date_get_eventlist($curr_date['year'].'-'.$curr_date['mon'].'-'.$curr_date['mday'], $folder) as $line) {
            $start = getdate($line['start']);
            $end = getdate($line['end']);
            if ($start['mon'].$start['mday'] != $curr_date['mon'].$curr_date['mday']) {
                $start['hours'] = $start['minutes'] = '00';
            }
            if ($end['mon'].$end['mday'] != $curr_date['mon'].$curr_date['mday']) {
                $end['hours'] = '23';
                $end['minutes'] = '59';
            }
            // Used to draw dots for each group with events for that day
            if (empty($line['colour'])) {
                $day_group['FFFFFF'] = 1;
            } else {
                $day_group[$line['colour']] = 1;
            }
        }
        if (!empty($day_group)) {
            $t_ec = $tm_dc->get_block('evt_colour');
            foreach ($day_group as $col => $egal) {
                $t_ec->assign('colour', $col);
                $tm_dc->assign('evt_colour', $t_ec);
                $t_ec->clear();
            }
        }

        $tm_dc->assign(array(
                'date' => $curr_date['mday'],
                'datelong' => date('Y-m-d', $curr_date[0]),
                'mday' => $curr_date['mday'],
                'day' => $curr_date['mth_day'],
                'daylink' => htmlspecialchars($base_link.'&viewmode=day&show_day='.$curr_date[0]),
                'dayclass' => $dayclass
                ));
        $tm_week->assign('mnth_daycell', $tm_dc);
        $tm_dc->clear();

        if ($weekcount % 7 == 0) {
            $tm_week->assign('kw', date('W', $curr_i));
            $tpl->assign('mnth_weekline', $tm_week);
            $tm_week->clear();
        }

        // Next day please
        $curr_i = strtotime('+1 day', $curr_i);
        $yesterday = $curr_date;
        ++ $weekcount;
    }
    if (($weekcount - 1) % 7 != 0) {
        while (($weekcount - 1) % 7 != 0) {
            $tm_week->assign('mnth_weekday', $tm_spa);
            ++$weekcount;
        }
        $tm_week->assign('kw', date('W', $curr_i));
        $tpl->assign('mnth_weekline', $tm_week);
    }

    // Output tasks for the currently selected group (or all groups, if selected)
    $num = 0;
    $t_tl = $tpl->get_block('taskline');
    foreach ($cDB->get_tasklist($folder) as $line) {
        $start = getdate($line['start']);
        $end = getdate($line['end']);
        $priotext = '';
        $prioicon = '';
        switch ($line['importance']) {
            case 1: case 2: $priotext = $WP_msg['TskImpVHigh'];  $prioicon = 'veryhigh';  break;
            case 3: case 4: $priotext = $WP_msg['TskImpHigh'];   $prioicon = 'high';      break;
            case 5:         $priotext = $WP_msg['TskImpNormal']; $prioicon = 'middle';    break;
            case 6: case 7: $priotext = $WP_msg['TskImpLow'];    $prioicon = 'low';       break;
            case 8: case 9: $priotext = $WP_msg['TskImpVLow'];   $prioicon = 'verylow';   break;
        }
        $t_tl->assign(array(
                'id' => $num+1,
                'eid' => $line['id'],
                'link' => htmlspecialchars($edit_tsk_link.'&tid='.$line['id']),
                'primary' => $line['title'],
                'alarm' => ($line['warn_mode'] != '-') ? 1 : 0,
                'completion' => $line['completion']
                ));
        if (!empty($line['location'])) {
            $t_tl->fill_block('tertiary', 'tertiary', $line['location']);
        }
        if ($line['start'] || $line['end']) {
            $t_tl->fill_block('secondary', 'secondary', date($WP_msg['dateformat_new'], $line['start']).' &rarr; '.date($WP_msg['dateformat_new'], $line['end']));
        }
        if ($priotext) {
            $t_tl->fill_block('priority_icon', array(
                    'src' => $_PM_['path']['theme'].'/icons/task_imp_'.$prioicon.'.png',
                    'alt' => $priotext
                    ));
        }
        if (!empty($line['colour'])) {
            $t_tl->fill_block('has_colour', 'colour', $line['colour']);
        }

        $tpl->assign('taskline', $t_tl);
        $t_tl->clear();
        ++$num;
    }
    if (0 == $num) {
        $tpl->assign_block('notasks');
    }

    $tpl->assign(array(
            'oneback' => htmlspecialchars($base_link.'&skim='.$oneback),
            'oneforward' => htmlspecialchars($base_link.'&skim='.$oneforward)
            ));
}
/*
// Permissions reflected in context menu items
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_event']) {
    $tpl->assign_block('ctx_new');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_delete_event']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('msg_jumptoday' => $WP_msg['CalJumpToToday']
        ,'msg_jumptoprev' => $WP_msg['CalJumpToPrev']
        ,'msg_jumptonext' => $WP_msg['CalJumpToNext']
        ,'msg_jumptodate' => $WP_msg['CalJumpToDate']
        ,'msg_deleoldevt' => $WP_msg['CalDelOldEvts']
        ,'msg_newevent' => $WP_msg['CalNewEvt']
        ,'gid' => $folder
        ,'monday_l' => $WP_msg['weekday'][0]
        ,'tuesday_l' => $WP_msg['weekday'][1]
        ,'wednesday_l' => $WP_msg['weekday'][2]
        ,'thursday_l' => $WP_msg['weekday'][3]
        ,'friday_l' => $WP_msg['weekday'][4]
        ,'saturday_l' => $WP_msg['weekday'][5]
        ,'sunday_l' => $WP_msg['weekday'][6]
        ,'monday_s' => $WP_msg['wday'][0]
        ,'tuesday_s' => $WP_msg['wday'][1]
        ,'wednesday_s' => $WP_msg['wday'][2]
        ,'thursday_s' => $WP_msg['wday'][3]
        ,'friday_s' => $WP_msg['wday'][4]
        ,'saturday_s' => $WP_msg['wday'][5]
        ,'sunday_s' => $WP_msg['wday'][6]
        ,'ref_day' => $show_day[0]
        ,'msg_title' => $WP_msg['CalTitle']
        ,'msg_starts' => $WP_msg['CalStart']
        ,'msg_ends' => $WP_msg['CalEnd']
        ,'msg_completion' => $WP_msg['TskCompletion']
        ,'msg_location' => $WP_msg['CalLocation']
        ,'msg_description' => $WP_msg['CalDescription']
        ,'curry' => date('Y', $reference_date)
        ,'currm' => date('m', $reference_date)
        ,'currd' => date('d', $reference_date)
        ,'go' => $WP_msg['goto']
        ,'but_search' => $WP_msg['ButSearch']
        ,'msg_page' => $WP_msg['page']
        ,'selection' => $WP_msg['selection']
        ,'allpage' => $WP_msg['allpage']
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_dele' => $WP_msg['del']
        ,'but_last' => '&lt;&lt;'
        ,'but_next' => '&gt;&gt;'
        ,'search' => $WP_msg['ButSearch']
        ,'msg_day' => $WP_msg['CalDay']
        ,'msg_month' => $WP_msg['CalMonth']
        ,'msg_year' => $WP_msg['CalYear']
        ,'msg_jumptoday' => $WP_msg['CalJumpToToday']
        ,'msg_jumptoprev' => $WP_msg['CalJumpToPrev']
        ,'msg_jumptonext' => $WP_msg['CalJumpToNext']
        ,'msg_jumptodate' => $WP_msg['CalJumpToDate']
        ,'msg_importance' => $WP_msg['TskImportance']
        ,'handler' => 'calendar'
        ,'PHP_SELF' => PHP_SELF
        ,'passthrough' => $passthrough
        ,'passthrough_2' => give_passthrough(2)
        ,'goto_today' => htmlspecialchars($base_link.'&gototoday=1')
        ,'edit_evt_link' => $edit_evt_link
        ,'edit_tsk_link' => $edit_tsk_link
        ,'fetcher_url' => PHP_SELF.'?h=calendar&l=fetcher.run&issuer=user&'.$passthrough.'&folder='.$_SESSION['phM_calendar_workfolder']
        ,'viewlink' => PHP_SELF.'?h=calendar&l=edit_event&'.$passthrough.'&eid='
        ,'eventops_url' => PHP_SELF.'?l=worker&h=calendar&'.$passthrough.'&what=event_'
        ,'lnk_switchview' => $base_link.'&viewmode='
        ,'search_url' => htmlspecialchars($base_link, ENT_COMPAT, 'utf-8')
        ,'jumpto' => date('Y-m-d')
        ,'folder_writable' => (int) ($group['owner'] == $_SESSION['phM_uid'])
        ,'head_tasks' => $WP_msg['TskPlural']
        ));
foreach (array(1 => 'january_l', 2 => 'february_l', 3 => 'march_l'
        ,4 => 'april_l', 5 => 'may_l', 6 => 'june_l'
        ,7 => 'july_l', 8 => 'august_l', 9 => 'september_l'
        ,10 => 'october_l', 11 => 'november_l', 12 => 'december_l') as $k => $v) {
    $tpl->assign($v, $WP_msg['month'][$k]);
}
*/
