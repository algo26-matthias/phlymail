<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @subpackage Import / Export
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.3 2015-03-30
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

# FIXME Find a better place for that definition
if (empty($_PM_['core']['timezone'])) {
    $_PM_['core']['timezone'] = date_default_timezone_get();
    if (empty($_PM_['core']['timezone'])) {
        $_PM_['core']['timezone'] = 'UTC';
    }
}

// Might exist in both the sessin and a variable form the API
$myPrivs = isset($_phM_privs) ? $_phM_privs : (!empty($_SESSION['phM_privs']) ? $_SESSION['phM_privs'] : false);

$myurl = PHP_SELF.'?l=exchange&h=calendar';
if (!$myPrivs['all']
        && ($myPrivs['calendar_export_events'] == 0 && $myPrivs['calendar_import_events'] == 0)
        && empty($PHM_CAL_IM_UID) && empty($PHM_CAL_EX_UID)) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
require_once __DIR__.'/functions.php'; // Might be not included

$effectiveUid = defined('PHM_API_UID') ? PHM_API_UID : (isset($PHM_CAL_IM_UID) ? $PHM_CAL_IM_UID : (isset($PHM_CAL_EX_UID) ? $PHM_CAL_EX_UID : $_SESSION['phM_uid']));

if (empty($cDB) || !is_a($cDB, 'handler_calendar_driver')) {
    $cDB = new handler_calendar_driver($effectiveUid);
} else {
    $cDB->changeUser($effectiveUid);
}

if (isset($PHM_CAL_EX_QUERYTYPE)) { // Obey exclusion of groups marked accordingly
    $cDB->setQueryType($PHM_CAL_EX_QUERYTYPE);
}
$do = false;
if (isset($PHM_CAL_EX_DO)) {
    $do = $PHM_CAL_EX_DO;
} elseif (isset($PHM_CAL_IM_DO)) {
    $do = $PHM_CAL_IM_DO;
} elseif (isset($_REQUEST['do']) && $_REQUEST['do']) {
    $do = $_REQUEST['do'];
}
$return = false;

if ('export' == $do) {
    if (!$myPrivs['all'] && !$myPrivs['calendar_export_events']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $exgroup = 0;
    if (isset($PHM_CAL_EX_GROUP)) {
        $exgroup = $PHM_CAL_EX_GROUP;
    } elseif (isset($_REQUEST['exgroup'])) {
        $exgroup = intval($_REQUEST['exgroup']);
    }
    $exevent = 0;
    if (isset($PHM_CAL_EX_EVENT)) {
        $exevent = $PHM_CAL_EX_EVENT;
    } elseif (isset($_REQUEST['exevent'])) {
        $exevent = intval($_REQUEST['exevent']);
    }
    $extodo = 0;
    if (isset($PHM_CAL_EX_TODO)) {
        $extodo = $PHM_CAL_EX_TODO;
    } elseif (isset($_REQUEST['exiodo'])) {
        $extodo = intval($_REQUEST['extodo']);
    }
    $export_format = false;
    if (isset($PHM_CAL_EX_FORMAT)) {
        $export_format = $PHM_CAL_EX_FORMAT;
    } elseif (isset($_REQUEST['exform'])) {
        $export_format = $_REQUEST['exform'];
    }

    switch ($export_format) {
    case 'ICS':
    case 'VCS':
        if (isset($PHM_CAL_EX_PUTTOFILE)) {
            ob_start(); // Catch all output generated to put to file later
        } elseif (isset($PHM_CAL_EX_FORMAT)) {
            header('Content-Type: text/calendar; charset=UTF-8');
            if ('ICS' == $export_format) {
                header('Content-Disposition: inline; filename=phlyMailEvents.ics');
            } else {
                header('Content-Disposition: inline; filename=phlyMailEvents.vcs');
            }
        } else {
            header('Content-Type: application/octet-stream');
            if ('ICS' == $export_format) {
                header('Content-Disposition: attachment; filename=phlyMailEvents.ics');
            } else {
                header('Content-Disposition: attachment; filename=phlyMailEvents.vcs');
            }
        }

        $eventTypes = $cDB->get_event_types();
        $eventStatus = $cDB->get_event_status();
        $myversion = version_format(file_get_contents($_PM_['path']['conf'].'/current.build'));
        // Mandatory preamble
        echo 'BEGIN:VCALENDAR'.CRLF.'VERSION:2.0'.CRLF.'METHOD:PUBLISH'.CRLF;
        echo 'PRODID:-//phlyLabs//NONSGML phlyMail '.$myversion.'//EN'.CRLF;

        $timezone = $_PM_['core']['timezone'];

        if ($extodo) {
            // We have a specific task (VTODO) to export
            ical_echoEvent($cDB->get_task($extodo), 'VTODO', $eventTypes, $eventStatus, $timezone);
        } elseif ($exevent) {
            // We have a specific event (VEVENT) to export
            ical_echoEvent($cDB->get_event($exevent), 'VEVENT', $eventTypes, $eventStatus, $timezone);
        } else {
            // Otherwise: Output all VEVENTs and VTODOs of the given folder
            foreach ($cDB->get_eventlist($exgroup) as $line) {
                ical_echoEvent($line, 'VEVENT', $eventTypes, $eventStatus, $timezone);
            }
            foreach ($cDB->get_tasklist($exgroup) as $line) {
                ical_echoEvent($line, 'VTODO', $eventTypes, $eventStatus, $timezone);
            }
        }
        echo 'END:VCALENDAR'.CRLF;
        // Should we have set a path to a file to write the output to:
        if (isset($PHM_CAL_EX_PUTTOFILE)) {
            file_put_contents($PHM_CAL_EX_PUTTOFILE, ob_get_clean());
            return;
        }
        break;
    case 'dotHol':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailHolidays.hol');
        $records = $cDB->daterange_getholidays(false, false, null);
        echo '[phlyMail] '.intval(count($records)).LF;
        foreach ($records as $record) {
            echo decode_utf8($record[1], 'windows-1252', true).', '.preg_replace('!^(\d+)\-(\d+)\-(\d+)!', '\1/\2/\3', $record[0]).LF;
        }
        break;
    default:
        $return .= $WP_msg['unkExpFrmt'].'<br />'.LF;
        $do = false;
        break;
    }
    if (!$return) {
        exit;
    }
}
if ('import' == $do) {
    if (!$myPrivs['all'] && !$myPrivs['calendar_import_events']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }

    $imgroup = 0;
    if (isset($PHM_CAL_IM_GROUP)) {
        $imgroup = $PHM_CAL_IM_GROUP;
    } elseif (isset($_REQUEST['imgroup'])) {
        $imgroup = intval($_REQUEST['imgroup']);
    }
    $imevent = 0;
    if (isset($PHM_CAL_IM_EVENT)) {
        $imevent = $PHM_CAL_IM_EVENT;
    } elseif (isset($_REQUEST['imevent'])) {
        $imevent = intval($_REQUEST['imevent']);
    }
    $imtodo = 0;
    if (isset($PHM_CAL_IM_TODO)) {
        $imtodo = $PHM_CAL_IM_TODO;
    } elseif (isset($_REQUEST['imiodo'])) {
        $imtodo = intval($_REQUEST['imtodo']);
    }
    $import_format = false;
    if (isset($PHM_CAL_IM_FORMAT)) {
        $import_format = $PHM_CAL_IM_FORMAT;
    } elseif (isset($_REQUEST['imform'])) {
        $import_format = $_REQUEST['imform'];
    }
    $import_truncate = false;
    if (!empty($PHM_CAL_IM_TRUNC)) {
        $import_truncate = true;
    } elseif (!empty($_REQUEST['truncate'])) {
        $import_truncate = true;
    }
    $import_deepsync = false;
    if (!empty($PHM_CAL_IM_SYNC)) {
        $import_deepsync = true;
    } elseif (!empty($_REQUEST['deepsync'])) {
        $import_deepsync = true;
    }

    $imported = 0;

    if (isset($PHM_CAL_IM_FILERES)
            || isset($PHM_CAL_IM_FILE)
            || !empty($_REQUEST['imurl'])
            || isset($_FILES['imfile'])
            || !empty($_SESSION['WP_impfile'])) {
        $tempName = SecurePassword::generate(16, false, STRONGPASS_DECIMALS | STRONGPASS_LOWERCASE);
        if (isset($PHM_CAL_IM_FILE)) {
            $file = explode(LF, $PHM_CAL_IM_FILE);
        } elseif (isset($PHM_CAL_IM_FILERES)) {
            $file = explode(LF, stream_get_contents($PHM_CAL_IM_FILERES));
        // URL given, try to download and process it
        } elseif (!empty($_REQUEST['imurl'])) {
            $dlinfo = basics::download($_REQUEST['imurl'], $_PM_['path']['temp'].'/'.$tempName, $_PM_['path']['temp'].'/'.$tempName.'.dnl', 1073741824);
            $file = file($_PM_['path']['temp'].'/'.$tempName);
            @unlink($_PM_['path']['temp'].'/'.$tempName);
            @unlink($_PM_['path']['temp'].'/'.$tempName.'.dnl');

        // CSV preprocessed
        } elseif (!isset($_FILES['imfile']) && !empty($_SESSION['WP_impfile'])) {
            $file = $_SESSION['WP_impfile'];
            unset($_SESSION['WP_impfile']);

        // local file uploaded
        } elseif (is_uploaded_file($_FILES['imfile']['tmp_name'])) {
            $file = file($_FILES['imfile']['tmp_name']);
            @unlink($_FILES['imfile']['tmp_name']);
        } else {
            $file = array();
        }

        switch ($import_format) {
        case 'ICS':
        case 'VCS':
            // Empty selected calendar before importing from the file
            // Inspired by idea of E. Turcan
            // Don't truncate the whole folder when handling individual items, which we jsut update
            if ($import_truncate && empty($imtodo) && empty($imevent)) {
                $cDB->empty_calendar($imgroup);
            }

            // In sync. mode we fetch all UUIDs (of the folder) first, then
            // update / insert what we got from the import file and last
            // drop no longer existant items from DB
            $evtUUIDs = null;
            $tskUUIDs = null;
            if ($import_deepsync) {
                $evtUUIDs = $cDB->getEventUUIDs($imgroup);
                $tskUUIDs = $cDB->getTaskUUIDs($imgroup);
            }

            // Make sure, we can always parse the final calendar entry even if it is broken at the end
            $file[] = CRLF.'END:VEVENT'.CRLF.'END:VTODO'.CRLF;

            $raw = '';
            $mode = '';
            $type = 'VEVENT';
            $eventTypes = $cDB->get_event_types();
            $eventStatus = $cDB->get_event_status();
            foreach ($file as $line) {
                $line = rtrim($line, CRLF).CRLF;
                if ($mode == '' && preg_match('!BEGIN:(VEVENT|VTODO)!', $line, $found)) {
                    $mode = 'r';
                    $type = $found[1];
                }
                if ($mode == 'r') {
                    $raw .= $line;
                    if (preg_match('!END:'.$type.'!', $line)) {
                        $mode = 'p';
                    }
                }
                if ($mode == 'p') {
                    $save = parse_icaldata($raw, $type, $eventTypes, $eventStatus);
                    // If an event or task ID is given, but the resulting record type differs, we do not update the record
                    if (($type == 'VTODO' && !empty($imevent)) || ($type == 'VEVENT' && !empty($imtodo))) {
                        $save = array();
                    }
                    if (!empty($save)) {
                        $save['gid'] = $imgroup;
                        $res = false;
                        if ($type == 'VEVENT') {
                            if (!empty($imevent)) {
                                $save['id'] = $imevent;
                                $res = $cDB->update_event($save);
                            } elseif (!empty($evtUUIDs) && !empty($save['uuid']) && isset($evtUUIDs[$save['uuid']])) {
                                $save['id'] = $evtUUIDs[$save['uuid']];
                                $res = $cDB->update_event($save);
                                unset($evtUUIDs[$save['uuid']]);
                            } else {
                                $res = $cDB->add_event($save);
                            }
                        } elseif ($type == 'VTODO') {
                            if (!empty($imtodo)) {
                                $save['id'] = $imtodo;
                                $res = $cDB->update_task($save);
                            } elseif (!empty($tskUUIDs) && !empty($save['uuid']) && isset($tskUUIDs[$save['uuid']])) {
                                $save['id'] = $tskUUIDs[$save['uuid']];
                                $res = $cDB->update_task($save);
                                unset($tskUUIDs[$save['uuid']]);
                            } else {
                                $res = $cDB->add_task($save);
                            }
                        }
                        if ($res) {
                            ++$imported;
                        } else {
                            // print_r($cDB->get_errors());
                        }
                    }
                    $raw = '';
                    $mode = '';
                }
            }
            // These "leftovers" do not exist in the import file and thus get dropped from DB
            if ($import_deepsync && !empty($evtUUIDs)) {
                $cDB->delete_event($evtUUIDs);
            }
            if ($import_deepsync && !empty($tskUUIDs)) {
                $cDB->delete_task($tskUUIDs);
            }

            break;
        case 'dotHol':
            // Empty selected calendar before importing from the file
            // Inspired by idea of E. Turcan
            if ($import_truncate) {
				$cDB->empty_holidays($imgroup);
            }
            foreach ($file as $line) {
                // Specfically looking for a matching entry
                if (!preg_match('!(.+),\ (\d+)/(\d+)/(\d+)$!', trim($line), $found)) {
                    continue;
                }
                $date = $found[2].'-'.$found[3].'-'.$found[4];
                $has_date = $cDB->daterange_getholidays($date, false, null);
                if (!empty($has_date)) {
                    continue; // Don't import doublette entries
                }
                $cDB->add_holiday($date, $found[1], false, false);
                ++$imported;
            }
            break;
        default:
            $return .= $WP_msg['unkImpFrmt'].'<br />'.LF;
            break;
        }
    }
    $do = false;
}

if (!empty($PHM_CAL_NO_OUTPUT)) {
    return;
}

if (!$do) {
    if (!empty($imported)) {
        $return .= str_replace('$1', $imported, $WP_msg['ImpNum']).'<br />'.LF;
    }
    $tpl = new phlyTemplate($_PM_['path']['templates'].'calendar.exchmenu.tpl');
    $passthru = give_passthrough();
    if ($return) {
        $tpl->fill_block('return', 'return', $return);
    }
    if ($myPrivs['all'] || $myPrivs['calendar_import_events']) {
        $tpl_imp = $tpl->get_block('import');
        $tpl_imp->assign(array
                ('target' => htmlspecialchars($myurl.'&'.$passthru)
                ,'msg_select' => $WP_msg['plsSel']
                ,'about_import' => $WP_msg['AboutImport']
                ,'leg_import' => $WP_msg['Import']
                ,'msg_file' => $WP_msg['filename']
                ,'msg_format' => $WP_msg['format']
                ,'msg_csv_only' => $WP_msg['LegendCSV']
                ,'msg_fieldnames' => $WP_msg['csvFirstLine']
                ,'msg_csv_quoted' => $WP_msg['csvIsQuoted']
                ,'msg_field_delimiter' => $WP_msg['csvFieldDelimiter']
                ,'msg_truncate' => $WP_msg['ImpTruncate']
                ,'msg_none' => $WP_msg['none']
                ,'msg_group' => $WP_msg['group']
                ));
        $imop = $tpl_imp->get_block('imoption');
        foreach (array('ICS' => 'ICS (iCal)', 'VCS' => 'VCS (vCal)'
                ,'dotHol' => $WP_msg['dotHolMenuOption']) as $val => $name) {
            $imop->assign(array('value' => $val, 'name' => $name));
            $tpl_imp->assign('imoption', $imop);
            $imop->clear();
        }
        $imgr = $tpl_imp->get_block('imgroup');
        foreach ($cDB->get_grouplist(0) as $v) {
            $imgr->assign(array('id' => $v['gid'], 'name' => $v['name']));
            $tpl_imp->assign('imgroup', $imgr);
            $imgr->clear();
        }
        $tpl->assign('import', $tpl_imp);
    }
    if ($cDB->quota_getnumberofrecords(false) && ($myPrivs['all'] || $myPrivs['calendar_export_events'])) {
        $tpl_exp = $tpl->get_block('export');
        $tpl_exp->assign(array
                ('target' => htmlspecialchars($myurl.'&'.$passthru)
                ,'msg_select' => $WP_msg['plsSel']
                ,'about_export' => $WP_msg['AboutExport']
                ,'leg_export' => $WP_msg['Export']
                ,'msg_csv_only' => $WP_msg['LegendCSV']
                ,'msg_fieldnames' => $WP_msg['csvFirstLine']
                ,'msg_csv_quoted' => $WP_msg['csvIsQuoted']
                ,'msg_field_delimiter' => $WP_msg['csvFieldDelimiter']
                ,'msg_format' => $WP_msg['format']
                ,'msg_none' => $WP_msg['none']
                ,'msg_group' => $WP_msg['group']
                ));
        $exop = $tpl_exp->get_block('exoption');
        foreach (array('ICS' => 'ICS (iCal)', 'VCS' => 'VCS (vCal)', 'dotHol' => $WP_msg['dotHolMenuOption']) as $val => $name) {
            $exop->assign(array('value' => $val, 'name' => $name));
            $tpl_exp->assign('exoption', $exop);
            $exop->clear();
        }
        $exgr = $tpl_exp->get_block('exgroup');
        foreach ($cDB->get_grouplist(0) as $v) {
            $exgr->assign(array('id' => $v['gid'], 'name' => $v['name']));
            $tpl_exp->assign('exgroup', $exgr);
            $exgr->clear();
        }
        $tpl->assign('export', $tpl_exp);
    }
}