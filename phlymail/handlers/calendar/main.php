<?php
/**
 * Main Calendar display (right frame)
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Calendar handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.0 2015-04-02
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_see_calendar']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$passthrough = give_passthrough(1);
$workfolder = 0;
if (isset($_REQUEST['workfolder'])) {
    $_SESSION['phM_calendar_workfolder'] = $workfolder = intval($_REQUEST['workfolder']);
} elseif (isset($_SESSION['phM_calendar_workfolder'])) {
    $workfolder = $_SESSION['phM_calendar_workfolder'];
}
$base_link = PHP_SELF.'?h=calendar&l=ilist&'.$passthrough;
$edit_evt_link = PHP_SELF.'?h=calendar&l=edit_event&'.$passthrough;
$edit_tsk_link = PHP_SELF.'?h=calendar&l=edit_task&'.$passthrough;
$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
if ($workfolder == 0) {
    $cDB->setQueryType('root');
}
$folder = $cDB->get_group($workfolder, false);
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday'])) {
    $_PM_['calendar']['wday'] = array(0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 0, 6 => 0);
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_start'])) {
    $_PM_['calendar']['wday_start'] = 16;
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_end'])) {
    $_PM_['calendar']['wday_end'] = 33;
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['viewmode'])) {
    $_PM_['calendar']['viewmode'] = 'monthly';
}

if (empty($_PM_['core']['timezone'])) {
    $_PM_['core']['timezone'] = date_default_timezone_get();
    if (empty($_PM_['core']['timezone'])) {
        $_PM_['core']['timezone'] = 'UTC';
    }
}

// This might be configured later on
$weeks_show_next = 4;
$weeks_show_last = 1;
$today = getdate();
if (isset($_REQUEST['gototoday']) && $_REQUEST['gototoday']) {
    $show_day = $_SESSION['calendar_show_day'] = $today;
    $reference_date = $_SESSION['calendar_ref_day'] = time();
} elseif (isset($_REQUEST['jumpto']) && $_REQUEST['jumpto']) {
    if ('nextevent' == $_REQUEST['jumpto']) {
        $new = $cDB->get_nextday_withevents(isset($_SESSION['calendar_ref_day']) ? $_SESSION['calendar_ref_day'] : time(), $workfolder);
        if ($new) {
            $reference_date = $_SESSION['calendar_ref_day'] = $new;
            $show_day = $_SESSION['calendar_show_day'] = getdate($new);
        } else {
            $reference_date = $_SESSION['calendar_ref_day'];
            $show_day = $_SESSION['calendar_show_day'];
        }
    } elseif ('prevevent' == $_REQUEST['jumpto']) {
        $new = $cDB->get_prevday_withevents(isset($_SESSION['calendar_ref_day']) ? $_SESSION['calendar_ref_day'] : time(), $workfolder);
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
if (isset($_REQUEST['pattern']) && $_REQUEST['pattern']) { // Force view mode if search is active
    $_PM_['calendar']['viewmode'] = $_SESSION['calendar_viewmode'] = 'list';
} elseif (isset($_REQUEST['viewmode']) && $_REQUEST['viewmode']) {
    $_PM_['calendar']['viewmode'] = $_SESSION['calendar_viewmode'] = $_REQUEST['viewmode'];
} else {
    if (isset($_SESSION['calendar_viewmode'])) {
        $_PM_['calendar']['viewmode'] = $_SESSION['calendar_viewmode'];
    }
}

switch ($_PM_['calendar']['viewmode']) {
    case 'weekly':  $mytpl = 'weekview'; break;
    case 'monthly': $mytpl = 'monthview'; break;
    case 'yearly':  $mytpl = 'yearview'; break;
    case 'list':    $mytpl = 'listview';
        if (isset($_REQUEST['jsreq'])) {
            $mytpl = 'listview.json';
        }
        break;
    default:        $mytpl = 'general'; break; // Default view, if nothing else given or requested by user
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.'.$mytpl.'.tpl');

if ($mytpl != 'listview' && $mytpl != 'listview.json') {
    // General overview (a week or so in the past, 8 weeks or so in the future)
    $t_cur = $tpl->get_block('ov_current');
    $t_tod = $tpl->get_block('ov_today');
    $t_oth = $tpl->get_block('ov_other');
    $t_end = $tpl->get_block('ov_weekend');
    $t_spa = $tpl->get_block('ov_space');
    $t_nex = $tpl->get_block('ov_nextmonth');
    $t_has = $tpl->get_block('hasevents');

    $lastmon = strtotime('-'.$weeks_show_last .' week', strtotime('last Monday', $reference_date));
    $nextsun = strtotime('+'.$weeks_show_next.' week', strtotime('next Sunday', $reference_date));

    $year = date('y', $lastmon);
    $month = date('n', $lastmon);
    $tpl->assign('month', $WP_msg['month'][$month].' \''.$year);
    $oneback = strtotime('-1 month', $reference_date);
    $oneforward = strtotime('+1 month', $reference_date);

    $calendarhead = str_replace
            (array('$1', '$2')
            ,array(date($WP_msg['dateformat_old'], $show_day[0]), date('W', $show_day[0]))
            ,$WP_msg['dateformat_calendarhead']
            );
    $calendarhead = (($workfolder != 0) ? $cDB->get_group($workfolder, true, true) : $WP_msg['CalMyEvents']).' — '.$calendarhead;
    $holidays = $cDB->daterange_getholidays(date('Y-m-d', $lastmon), date('Y-m-d', $nextsun));
    $t_week = $tpl->get_block('weekline');
    $curr_i = $lastmon;
    $yesterday = getdate($curr_i);
    $weekcount = 1;
    $mode = 'write';
    while ($curr_i < $nextsun) {
        if ('write' == $mode) {
            $curr_date = getdate($curr_i);
            // Find out, whether that day has scheduled events
            $has_event = $cDB->date_has_events(date('Ymd', $curr_i), $workfolder);
            $is_holiday = (isset($holidays[date('Y-m-d', $curr_i)]));
            // Get day of week, transform it to the German base, where monday is day 0, sunday is day 6
            $weekday = date('w', $curr_i) -1 ;
            // barrelshifting the sunday from the beginning of the list to the end
            if (-1 == $weekday) {
                $weekday = 6;
            }
            $is_wday = (isset($_PM_['calendar']['wday'][$weekday]) && $_PM_['calendar']['wday'][$weekday]);

            if ($yesterday['mon'] != $curr_date['mon']) {
                $mode = 'fill';
                $filled = 0;
                continue;
            }
            if ($curr_date['yday'].$curr_date['year'] == $show_day['yday'].$show_day['year']) {
                $t_tod->assign(array
                        ('date' => $curr_date['mday']
                        ,'has_events' => ($has_event) ? $t_has : ''
                        ));
                $t_week->assign('weekday', $t_tod);
                $t_tod->clear();
            } elseif (!$is_wday || $is_holiday) {
                $t_end->assign(array
                        ('date' => $curr_date['mday']
                        ,'goto' => htmlspecialchars($base_link.'&show_day='.$curr_i)
                        ,'has_events' => ($has_event) ? $t_has : ''
                        ,'title' => ($is_holiday) ? $holidays[date('Y-m-d', $curr_i)] : ''
                        ));
                $t_week->assign('weekday', $t_end);
                $t_end->clear();
            } elseif ($curr_date['mon'].$curr_date['year'] == $today['mon'].$today['year']) {
                $t_cur->assign(array
                        ('date' => $curr_date['mday']
                        ,'goto' => htmlspecialchars($base_link.'&show_day='.$curr_i)
                        ,'has_events' => ($has_event) ? $t_has : ''
                        ));
                $t_week->assign('weekday', $t_cur);
                $t_cur->clear();
            } else {
                $t_oth->assign(array
                        ('date' => $curr_date['mday']
                        ,'goto' => htmlspecialchars($base_link.'&show_day='.$curr_i)
                        ,'has_events' => ($has_event) ? $t_has : ''
                        ));
                $t_week->assign('weekday', $t_oth);
                $t_oth->clear();
            }
            if ($weekcount % 7 == 0) {
                $tpl->assign('weekline', $t_week);
                $t_week->clear();
            }
            $yesterday = $curr_date;
            $curr_i = strtotime('+1 day', $curr_i);
        } elseif ('fill' == $mode) {
            if (7 == $filled) {
                $yesterday = $curr_date;
                $mode = 'write';
                continue;
            }
            $t_week->assign('weekday', $t_spa);
            if ($weekcount % 7 == 0) {
                $tpl->assign('weekline', $t_week);
                $t_week->clear();
                $year = date('y', $curr_i);
                $month = date('n', $curr_i);
                $t_nex->assign('month', $WP_msg['month'][$month].' \''.$year);
                $t_week->assign('weekday', $t_nex);
                $t_nex->clear();
                $tpl->assign('weekline', $t_week);
                $t_week->clear();
            }
            ++$filled;
        }
        ++$weekcount;
    }
}
// Specific view modes

if (!$_PM_['calendar']['viewmode'] || $_PM_['calendar']['viewmode'] == 'daily') {
    // Building the time table for currently shown day
    $t_tl = $tpl->get_block('timeline');
    $t_fh = $t_tl->get_block('fullhour');
    $t_hh = $t_tl->get_block('halfhour');
    $workstart = $_PM_['calendar']['wday_start'];
    $workend = $_PM_['calendar']['wday_end'];
    // Get day of week, transform it to the German base, where monday is day 0, sunday is day 6
    $weekday = date('w', $show_day[0]) -1 ;
    // barrelshifting the sunday from the beginning of the list to the end
    if (-1 == $weekday) {
        $weekday = 6;
    }
    $is_wday = (isset($_PM_['calendar']['wday'][$weekday]) && $_PM_['calendar']['wday'][$weekday]);
    foreach (range(0, 47, 1) as $halfhour) {
        if ($halfhour % 2) {
            if ($is_wday && $halfhour < ($workend) && $halfhour >= $workstart) {
                $t_hh->assign_block('work');
            } else {
                $t_hh->assign_block('spare');
            }
            $t_hh->assign(array('m' => '30', 'h' => $hour));
            $t_tl->assign('halfhour', $t_hh);
            $tpl->assign('timeline', $t_tl);
            $t_tl->clear();
            $t_hh->clear();
        } else {
            $t_fh->assign_block(($is_wday && $halfhour < ($workend) && $halfhour >= $workstart) ? 'work' : 'spare');
            $hour = $halfhour / 2;
            if (strlen($hour) < 2) {
                $hour = '0'.$hour;
            }
            $t_fh->assign(array('m' => '00', 'h' => $hour));
            $t_tl->assign('fullhour', $t_fh);
            $tpl->assign('timeline', $t_tl);
            $t_tl->clear();
            $t_fh->clear();
        }
    }
    // Place the event data in the template for JS to display them
    $num = 0;
    $t_evt = $tpl->get_block('eventline');
    foreach ($cDB->date_get_eventlist($show_day['year'].'-'.$show_day['mon'].'-'.$show_day['mday'], $workfolder) as $line) {
        $startJS = $start = new DateTime($line['starts'], new DateTimeZone($_PM_['core']['timezone']));
        $endJS = $end = new DateTime($line['ends'], new DateTimeZone($_PM_['core']['timezone']));
        if ($startJS->format('nj') != $show_day['mon'].$show_day['mday']) {
            $startJS->setTime(0, 0, 0);
        }
        if ($endJS->format('nj') != $show_day['mon'].$show_day['mday']) {
            $endJS->setTime(23, 59, 0);
        }
        $t_evt->assign(array
                ('id' => $num
                ,'eid' => $line['id']
                ,'day' => $curr_date['mday']
                ,'json' => json_encode(array(
                        'title' => $line['title'],
                        'desc' => basics::softbreak($line['description']),
                        'loc' => $line['location'],
                        'eid' => $line['id'],
                        'status' => $line['status'],
                        'type' => $line['type'],
                        'starth' => $startJS->format('G'),
                        'startm' => $startJS->format('i')*1,
                        'endh' => $endJS->format('G'),
                        'endm' => $endJS->format('i')*1,
                        'starts' => $start->format($WP_msg['dateformat_new']),
                        'ends' => $end->format($WP_msg['dateformat_new']),
                        'start' => $start->format('r'),
                        'end' => $end->format('r'),
                        'colour' => $line['colour'],
                        'alarm' => ($line['warn_mode'] != '-') ? 1 : 0,
                        'repeats' => ($line['repeat_type'] != '-') ? 1 : 0,
                        'refstamp' => date('U', $line['end'])
                        ))));
        $tpl->assign('eventline', $t_evt);
        $t_evt->clear();
        ++$num;
    }
}

if ($_PM_['calendar']['viewmode'] == 'monthly') {
    foreach (array(0 => 'monday', 1 => 'tuesday', 2 => 'wednesday', 3 => 'thursday', 4 => 'friday', 5 => 'saturday', 6 => 'sunday') as $k => $v) {
        if (!isset($_PM_['calendar']['wday'][$k]) || !$_PM_['calendar']['wday'][$k]) {
            $tpl->assign('label_'.$v, ' sunday');
        }
    }
    $tm_dc = $tpl->get_block('mnth_daycell');
    $tm_kw  = $tpl->get_block('mnth_kw');
    $tm_hol = $tpl->get_block('li_holiday');

    $tm_tod = $tpl->get_block('mnth_today');
    $tm_cur = $tpl->get_block('mnth_current');
    $tm_oth = $tpl->get_block('mnth_other');
    $tm_spa = $tpl->get_block('mnth_space');

    $lastmon = strtotime('1 '.date('M Y', $reference_date).' 00:00:00');
    $nextsun = strtotime('-1 second', strtotime('+1 month', $lastmon));

    $year = date('y', $lastmon);
    $month = date('n', $lastmon);
    $oneback    = strtotime('-1 month', $lastmon);
    $oneforward = strtotime('+1 month', $lastmon);
    $tm_week = $tpl->get_block('mnth_weekline');
    $tpl->assign('month_l', $WP_msg['month'][$month].' \''.$year);
    $curr_i = $lastmon;
    $yesterday = getdate($curr_i);
    $start_wday = date('w', $curr_i);
    if ($start_wday == 1) {
        $weekcount = 1;
    } else {
        $weekcount = 1;
        $filled = ($start_wday == 0) ? 1 : 8 - $start_wday;
        $curr_i = strtotime('-'.(7-$filled).'day', $curr_i);
    }
    $end_wday = date('w', $nextsun);
    if ($end_wday != 0) { // Ain't a sunday, bro
        $nextsun = strtotime('next sunday +23 hour +59 minute', $nextsun);
    }
    $holidays = $cDB->daterange_getholidays(date('Y-m-d', $curr_i), date('Y-m-d', $nextsun));

    $num = 0;
    $t_evt = $tpl->get_block('eventline');
    while ($curr_i < $nextsun) {
        $curr_date = getdate($curr_i);
        $curr_date['mth_day'] = ($curr_date['mon']*100) + $curr_date['mday'];
        // Get day of week, transform it to the German base, where monday is day 0, sunday is day 6
        $weekday = date('w', $curr_i) -1 ;
        // barrelshifting the sunday from the beginning of the list to the end
        if (-1 == $weekday) {
            $weekday = 6;
        }
        $is_wday = (isset($_PM_['calendar']['wday'][$weekday]) && $_PM_['calendar']['wday'][$weekday]);
        $is_holiday = (isset($holidays[date('Y-m-d', $curr_i)]));

        $tm_dc->assign(array
                ('date' => $curr_date['mday']
                ,'datelong' => date('Y-m-d', $curr_date[0])
                ,'mday' => $curr_date['mday']
                ,'day' => $curr_date['mth_day']
                ));

        if ($curr_date['mon'] != $month) {
            $tm_dc->assign('dayclass', 'cal_mnth_space');
        } elseif ($curr_date['yday'].$curr_date['year'] == $show_day['yday'].$show_day['year']) {
            $tm_dc->assign('dayclass', 'cal_mnth_showday');

        } elseif ($curr_date['mon'].$curr_date['year'] == $today['mon'].$today['year']) {
            $tm_dc->assign('dayclass', 'cal_mnth_curr');

        } else {
            $tm_dc->assign('dayclass', 'cal_mnth_other');
        }
        if ($weekday == 0) {
            $tm_kw->assign('kw', str_replace('$1', date('W', $curr_i), $WP_msg['dateformat_calendarmhead']));
            $tm_dc->assign('date', $tm_kw);
            $tm_kw->clear();
        }

        if (!$is_wday) {
            $tm_dc->assign('sunday', ' sunday');
        }
        if ($is_holiday) {
            $tm_dc->assign(array('sunday' => ' holiday', 'title' => $holidays[date('Y-m-d', $curr_i)]));
            $tm_hol->assign('holiday', $holidays[date('Y-m-d', $curr_i)]);
            $tm_dc->assign('holiday', $tm_hol);
            $tm_hol->clear();
        }
        $tm_week->assign('mnth_weekday', $tm_dc);
        $tm_dc->clear();

        if ($weekcount % 7 == 0) {
            $tpl->assign('mnth_weekline', $tm_week);
            $tm_week->clear();
        }

        // Place the event data in the template for JS to display them
        foreach ($cDB->date_get_eventlist($curr_date['year'].'-'.$curr_date['mon'].'-'.$curr_date['mday'], $workfolder) as $line) {
            
            $startJS = $start = DateTime::createFromFormat('U', intval($line['start']), new DateTimeZone('UTC'));
            $endJS = $end = DateTime::createFromFormat('U', intval($line['end']), new DateTimeZone('UTC'));
            $startJS->setTimezone(new DateTimeZone($_PM_['core']['timezone']));
            $endJS->setTimezone(new DateTimeZone($_PM_['core']['timezone']));

            if ($startJS->format('nj') != $curr_date['mon'].$curr_date['mday']) {
                $startJS->setTime(0, 0, 0);
            }
            if ($endJS->format('nj') != $curr_date['mon'].$curr_date['mday']) {
                $endJS->setTime(23, 59, 0);
            }
            $t_evt->assign(array(
                    'id' => $num+1,
                    'eid' => $line['id'],
                    'day' => $curr_date['mth_day'],
                    'json' => json_encode(array('title' => $line['title'],
                            'desc' => basics::softbreak($line['description']),
                            'loc' => $line['location'], 'eid' => $line['id'],
                            'status' => $line['status'], 'type' => $line['type'],
                            'starth' => $startJS->format('G'),
                            'startm' => $startJS->format('i')*1,
                            'endh' => $endJS->format('G'),
                            'endm' => $endJS->format('i')*1,
                            'starts' => $start->format($WP_msg['dateformat_new']),
                            'ends' => $end->format($WP_msg['dateformat_new']),
                            'start' => $start->format('r'),
                            'end' => $end->format('r'),
                            'colour' => $line['colour'],
                            'alarm' => ($line['warn_mode'] != '-') ? 1 : 0,
                            'repeats' => ($line['repeat_type'] != '-') ? 1 : 0,
                            'day' => $curr_date['mday'],
                            'refstamp' => date('U', $line['end']),
                            'id' => $num+1
                    ))
            ));
            $tpl->assign('eventline', $t_evt);
            $t_evt->clear();
            ++$num;
        }

        $curr_i = strtotime('+1 day', $curr_i);
        $yesterday = $curr_date;

        ++$weekcount;
    }
    if (($weekcount-1) % 7 != 0) {
        while (($weekcount-1) % 7 != 0) {
            $tm_week->assign('mnth_weekday', $tm_spa);
            ++$weekcount;
        }
        $tpl->assign('mnth_weekline', $tm_week);
    }
}

if ($_PM_['calendar']['viewmode'] == 'weekly') {
    $tm_hol = $tpl->get_block('li_holiday');
    $lastmon = (date('w', $reference_date) == 1)
            ? strtotime(date('Y-m-d', $reference_date).' 12:00:00')
            : strtotime('last Monday 12:00', strtotime(date('Y-m-d', $reference_date).' 12:00:00'));
    $nextsun = (date('w', $reference_date) == 0)
            ? strtotime(date('Y-m-d', $reference_date).' 12:00:00')
            : strtotime('+6 days 12:00', strtotime(date('Y-m-d', $lastmon).' 12:00:00'));
    $year = date('y', $lastmon);
    $month = date('n', $lastmon);
    $oneweekback = strtotime('-1 week', $lastmon);
    $oneweekforward = strtotime('+1 week', $lastmon);
    $curr_i = $lastmon;
    $num = 0;
    $t_evt = $tpl->get_block('eventline');
    while ($curr_i <= $nextsun) {
        $curr_date = getdate($curr_i);
        // Get day of week, transform it to the German base, where monday is day 0, sunday is day 6
        $weekday = date('w', $curr_i)-1;
        // barrelshifting the sunday from the beginning of the list to the end
        if (-1 == $weekday) {
            $weekday = 6;
        }
        $is_wday = (isset($_PM_['calendar']['wday'][$weekday]) && $_PM_['calendar']['wday'][$weekday]);
        $is_today = ($curr_date['yday'].$curr_date['year'] == $show_day['yday'].$show_day['year']);
        $is_holiday = (isset($holidays[date('Y-m-d', $curr_i)]));
        $tpl->assign(array
                ('date_'.$weekday => date($WP_msg['dateformat_daymonth'], $curr_i)
                ,'free_'.$weekday => (($is_today) ? ' cal_mnth_showday' : '').(($is_wday) ? '' : ' sunday').(($is_holiday) ? ' holiday' : '')
                ,'day_'.$weekday => date('d', $curr_i)
                ,'title_'.$weekday => $is_holiday ? $holidays[date('Y-m-d', $curr_i)] : ''
                ));
        if ($is_holiday) {
            $tm_hol->assign('holiday', $holidays[date('Y-m-d', $curr_i)]);
            $tpl->assign('holiday_'.$weekday, $tm_hol);
            $tm_hol->clear();
        }
        // Place the event data in the template for JS to display them
        foreach ($cDB->date_get_eventlist($curr_date['year'].'-'.$curr_date['mon'].'-'.$curr_date['mday'], $workfolder) as $line) {
            $startJS = $start = new DateTime($line['starts'], new DateTimeZone($_PM_['core']['timezone']));
            $endJS = $end = new DateTime($line['ends'], new DateTimeZone($_PM_['core']['timezone']));
            if ($startJS->format('nj') != $curr_date['mon'].$curr_date['mday']) {
                $startJS->setTime(0, 0, 0);
            }
            if ($endJS->format('nj') != $curr_date['mon'].$curr_date['mday']) {
                $endJS->setTime(23, 59, 0);
            }
            $t_evt->assign(array(
                    'id' => $num+1,
                    'eid' => $line['id'],
                    'day' => $weekday,
                    'json' => json_encode(array(
                            'title' => $line['title'],
                            'desc' => basics::softbreak($line['description']),
                            'loc' => $line['location'], 'eid' => $line['id'],
                            'status' => $line['status'], 'type' => $line['type'],
                            'starth' => $startJS->format('G'),
                            'startm' => $startJS->format('i')*1,
                            'endh' => $endJS->format('G'),
                            'endm' => $endJS->format('i')*1,
                            'starts' => $start->format($WP_msg['dateformat_new']),
                            'ends' => $end->format($WP_msg['dateformat_new']),
                            'start' => $start->format('r'),
                            'end' => $end->format('r'),
                            'colour' => $line['colour'],
                            'alarm' => ($line['warn_mode'] != '-') ? 1 : 0,
                            'repeats' => ($line['repeat_type'] != '-') ? 1 : 0,
                            'day' => $curr_date['mday'],
                            'refstamp' => date('U', $line['end']),
                            'id' => $num+1
                    ))
            ));
            $tpl->assign('eventline', $t_evt);
            $t_evt->clear();
            ++$num;
        }
        $curr_i = strtotime('+1 day', $curr_i);
    }
    $tpl->assign(array
            ('oneweekback' => htmlspecialchars($base_link.'&skim='.$oneweekback)
            ,'oneweekforward' => htmlspecialchars($base_link.'&skim='.$oneweekforward)
            ,'month_l' => str_replace('$1', date('W', $lastmon), $WP_msg['dateformat_calendarmhead'])
                    .' &mdash; '.date($WP_msg['dateformat_daymonth'], $lastmon)
                    .'-'.date($WP_msg['dateformat_daymonth'], $nextsun)
            ));
}

if ($_PM_['calendar']['viewmode'] == 'yearly') {
    $oneyearback    = strtotime('-1 year', $reference_date);
    $oneyearforward = strtotime('+1 year', $reference_date);
    $tpl->assign(array
            ('oneyearback' => htmlspecialchars($base_link.'&skim='.$oneyearback)
            ,'oneyearforward' => htmlspecialchars($base_link.'&skim='.$oneyearforward)
            ,'month_l' => date('Y', $reference_date)
            ,'detaillink' => $base_link.'&viewmode=monthly&goto_day='.date('Y', $reference_date)
            ));
}

if ($_PM_['calendar']['viewmode'] == 'list') {
    $fieldnames = array
            ('starts' => array('n' => $WP_msg['CalStart'], 't' => '', 'i' => '', 'db' => 'start')
            ,'ends' => array('n' => $WP_msg['CalEnd'], 't' => '', 'i' => '', 'db' => 'end')
            ,'title' => array('n' => $WP_msg['CalTitle'], 't' => '', 'i' => '', 'db' => 'title')
            ,'location' => array('n' => $WP_msg['CalLocation'], 't' => '', 'i' => '', 'db' => 'location')
            ,'description' => array('n' => $WP_msg['CalDescription'], 't' => '', 'i' => '', 'db' => 'description')
            ,'repetitions' => array('n' => '', 't' => $WP_msg['CalListRep'], 'i' => 'cal_head_repetition.png', 'db' => 'repeetitions')
            ,'reminders' => array('n' => '', 't' => $WP_msg['CalListRem'], 'i' => 'cal_head_reminder.png', 'db' => 'reminders')
            ,'reminders_sms' => array('n' => '', 't' => $WP_msg['CalListRemSMS'], 'i' => 'cal_head_remindsms.png', 'db' => 'reminders_sms')
            ,'reminders_email' => array('n' => '', 't' => $WP_msg['CalListRemEmail'], 'i' => 'cal_head_remindemail.png', 'db' => 'reminders_email')
            );
    $showfields  = (isset($_PM_['calendar']['show_fields']) && !empty($_PM_['calendar']['show_fields']) && !$_PM_['calendar']['use_default_fields'])
            ? $_PM_['calendar']['show_fields']
            : array('starts' => 1, 'ends' => 1, 'title' => 1, 'location' => 1, 'repetitions' => 1, 'reminders' => 1, 'reminders_sms' => 1, 'reminders_email' => 1);

    if (isset($_REQUEST['pagenum'])) {
        $_SESSION['calendar_pagenum'] = intval($_REQUEST['pagenum']);
    }
    if (isset($_REQUEST['jumppage'])) {
        $_SESSION['calendar_pagenum'] = intval($_REQUEST['jumppage']) - 1;
    }
    if (!isset($_SESSION['calendar_pagenum'])) {
        $_SESSION['calendar_pagenum'] = 0;
    }

    if (isset($_REQUEST['orderby']) && isset($fieldnames[$_REQUEST['orderby']])) {
        $orderby = $_REQUEST['orderby'];
        $orderdir = (isset($_REQUEST['orderdir']) && ('ASC' == $_REQUEST['orderdir'] || 'DESC' == $_REQUEST['orderdir'])) ? $_REQUEST['orderdir'] : 'ASC';
        $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
        $GlChFile['calendar']['orderby'] = $orderby;
        $GlChFile['calendar']['orderdir'] = $orderdir;
        $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
    } else {
        // Try to find a field to order the whole list by
        $orderby  = 'starts';
        foreach (array('starts', 'ends', 'title', 'location') as $field) {
            if (isset($showfields[$field]) && $showfields[$field]) {
                $orderby = $field;
                break;
            }
        }
        $orderdir = 'ASC';
    }
    $ordlink = '&orderby='.$orderby.'&orderdir='.$orderdir;
    $pattern = isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : null;
    if ($pattern) {
        $ordlink .= '&pattern='.$pattern;
    }
    $eingang = $cDB->get_eventcount($workfolder, $pattern);

    if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize']) {
        $displayend = $i = $eingang;
        $displaystart = 1;
        $i2 = 0;
    } else {
        if ($_SESSION['calendar_pagenum'] < 0) {
            $_SESSION['calendar_pagenum'] = 0;
        }
        if ($_PM_['core']['pagesize'] * $_SESSION['calendar_pagenum'] > $eingang) {
            $_SESSION['calendar_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
        }
        $i = $eingang - ($_PM_['core']['pagesize'] * $_SESSION['calendar_pagenum']);
        $i2 = $i - $_PM_['core']['pagesize'];
        if ($i2 < 0) {
            $i2 = 0;
        }
        $displaystart = $_PM_['core']['pagesize'] * $_SESSION['calendar_pagenum'] +1;
        $displayend = $_PM_['core']['pagesize'] * ($_SESSION['calendar_pagenum'] + 1);
        if ($displayend > $eingang) {
            $displayend = $eingang;
        }
    }
    $myPageNum = $_SESSION['calendar_pagenum'];
    // That's it with the session
    session_write_close();

    // Initialise the ShowFields array passed to JavaScript with the icon field always displayed in front
    $sf_js = array();
    foreach ($showfields as $f => $a) {
        if (!$a) {
            continue;
        }
        $sf_js[] = '"'.$f.'":{"n":"'.$fieldnames[$f]['n'].'","i":"'.$fieldnames[$f]['i'].'","t":"'.$fieldnames[$f]['t'].'"}';
    }

    $plural = ($eingang == 1) ? $WP_msg['entry'] : $WP_msg['entries'];
    // Handle Jump to Page Form
    if ($_PM_['core']['pagesize']) {
        $max_page = ceil($eingang / $_PM_['core']['pagesize']);
    } else {
        $max_page = 0;
    }
    $jumpsize = strlen($max_page);

    $tpl_lines = $tpl->get_block('eventlines');
    $i = $displaystart;
    foreach ($cDB->get_eventlist($workfolder, true, $pattern, ($displayend-$displaystart+1), $displaystart-1, $orderby, $orderdir) as $line) {
        $line['start'] = strtotime($line['starts']);
        $line['end'] = strtotime($line['ends']);
        $line['starts'] = date(date('Y') == date('Y', $line['start']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $line['start']);
        $line['ends'] = date(date('Y') == date('Y', $line['end']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $line['end']);

        $tpl_lines->assign(array
                ('num' => $i
                ,'data' => '{"uidl": "'.$line['id'].'"'
                        .(isset($showfields['starts']) ? ', "starts": "'.phm_addcslashes($line['starts']).'"' : '')
                        .(isset($showfields['starts']) ? ', "starts_title": "'.phm_addcslashes(date($WP_msg['dateformat'], $line['start'])).'"' : '')
                        .(isset($showfields['ends']) ? ', "ends": "'.phm_addcslashes($line['ends']).'"' : '')
                        .(isset($showfields['ends']) ? ', "ends_title": "'.phm_addcslashes(date($WP_msg['dateformat'], $line['end'])).'"' : '')
                        .(isset($showfields['title']) ? ', "title": "'.phm_addcslashes($line['title']).'"' : '')
                        .(isset($showfields['location']) ? ', "location": "'.phm_addcslashes($line['location']).'"' : '')
                        .(isset($showfields['description']) ? ', "description": "'.phm_addcslashes(basics::softbreak($line['description']), '"/\\').'"' : '')
                        .(isset($showfields['colour']) ? ', "colour": "'.phm_addcslashes($line['colour'], '"/\\').'"' : '')
                        .(isset($showfields['repetitions']) ? ', "repetitions": "'.phm_addcslashes($line['repetitions']).'"' : '')
                        .(isset($showfields['reminders']) ? ', "reminders": "'.phm_addcslashes($line['reminders']).'"' : '')
                        .(isset($showfields['reminders_sms']) ? ', "reminders_sms": "'.phm_addcslashes($line['reminders_sms']).'"' : '')
                        .(isset($showfields['reminders_email']) ? ', "reminders_email": "'.phm_addcslashes($line['reminders_email']).'"' : '')
                        .'}'
                ,'notfirst' => $i == $displaystart ? '' : ','
                ));
        $tpl->assign('eventlines', $tpl_lines);
        $tpl_lines->clear();
        $i++;
    }
    // Handle Jump to Page Form
    if (isset($_PM_['core']['pagesize']) && $_PM_['core']['pagesize']) {
        $max_page = ceil($eingang / $_PM_['core']['pagesize']);
    } else {
        $max_page = 0;
    }
    $jumpsize = strlen($max_page);
    // Assign things, both template modes (HTML and JSON) will need
    $tpl->assign(array
            ('size' => $jumpsize
            ,'maxlen' => $jumpsize
            ,'page' => $myPageNum + ($eingang == 0 ? 0 : 1)
            ,'boxsize' => $max_page
            ,'plural' => $plural
            ,'size' => $jumpsize
            ,'maxlen' => $jumpsize
            ,'contacts' => $WP_msg['entries']
            ,'neueingang' => number_format($eingang, 0, $WP_msg['dec'], $WP_msg['tho'])
            ,'displaystart' => ($eingang == 0) ? 0 : $displaystart
            ,'displayend' => $displayend
            ,'showfields' => '{'.implode(', ', $sf_js).'}'
            ,'orderby' => $orderby
            ,'orderdir' => $orderdir
            ,'pagenum' => $myPageNum
            ,'pagesize' => $_PM_['core']['pagesize']
            ,'jsrequrl' => $base_link.$ordlink.'&jsreq=1'
            ));
    // This is a JSON request, which just needs the maillist and a few info bits 'bout that folder
    if (isset($_REQUEST['jsreq'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $tpl->display();
        exit;
    }
}

if ($_PM_['calendar']['viewmode'] != 'list') {
    $tpl->assign(array
            ('calendarhead' => $calendarhead
            ,'oneback' => htmlspecialchars($base_link.'&skim='.$oneback)
            ,'oneforward' => htmlspecialchars($base_link.'&skim='.$oneforward)
            ));
    // Output tasks for the currently selected group (or all groups, if selected)
    $num = 0;
    $t_tl = $tpl->get_block('taskline');
    foreach ($cDB->get_tasklist($workfolder) as $line) {
        $start = getdate($line['start']);
        $end = getdate($line['end']);
        switch ($line['importance']) {
            case 1: case 2: $priotext = $WP_msg['TskImpVHigh']; break;
            case 3: case 4: $priotext = $WP_msg['TskImpHigh']; break;
            case 5: $priotext = $WP_msg['TskImpNormal']; break;
            case 6: case 7: $priotext = $WP_msg['TskImpLow']; break;
            case 8: case 9: $priotext = $WP_msg['TskImpVLow']; break;
            default: $priotext = '';
        }
        $t_tl->assign(array
                ('id' => $num+1
                ,'eid' => $line['id']
                ,'day' => $weekday
                ,'json' => json_encode(array('title' => $line['title']
                        ,'desc' => basics::softbreak($line['description'])
                        ,'loc' => $line['location']
                        ,'eid' => $line['id']
                        ,'status' => $line['status']
                        ,'type' => $line['type']
                        ,'starth' => $start['hours'], 'startm' => $start['minutes']
                        ,'endh' => $end['hours'], 'endm' => $end['minutes']
                        ,'starts' => date($WP_msg['dateformat_new'], $line['start'])
                        ,'ends' => date($WP_msg['dateformat_new'], $line['end'])
                        ,'colour' => empty($line['colour']) ? '' : $line['colour']
                        ,'alarm' => ($line['warn_mode'] != '-') ? 1 : 0
                        ,'completion' => $line['completion']
                        ,'importance' => $line['importance']
                        ,'importance_title' => $priotext
                        ,'refstamp' => date('U', $line['end'])
                        ,'has_start' => (!is_null($line['start'])) ? 1 : 0
                        ,'has_end' => (!is_null($line['end'])) ? 1 : 0
                        ,'id' => $num+1
                        ))
                ));
        $tpl->assign('taskline', $t_tl);
        $t_tl->clear();
        ++$num;
    }
}

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
        ,'gid' => $workfolder
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
        ,'msg_dayview' => $WP_msg['CalDayView']
        ,'msg_weekview' => $WP_msg['CalWeekView']
        ,'msg_monthview' => $WP_msg['CalMonthView']
        ,'msg_yearview' => $WP_msg['CalYearView']
        ,'msg_listview' => $WP_msg['CalListView']
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
        ,'folder_writable' => (int) ($folder['owner'] == $_SESSION['phM_uid'])
        ,'head_tasks' => $WP_msg['TskPlural']
        ));
foreach (array(1 => 'january_l', 2 => 'february_l', 3 => 'march_l'
        ,4 => 'april_l', 5 => 'may_l', 6 => 'june_l'
        ,7 => 'july_l', 8 => 'august_l', 9 => 'september_l'
        ,10 => 'october_l', 11 => 'november_l', 12 => 'december_l') as $k => $v) {
    $tpl->assign($v, $WP_msg['month'][$k]);
}
