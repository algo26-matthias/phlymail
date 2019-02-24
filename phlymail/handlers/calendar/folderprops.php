<?php
/**
 * Edit the properties of a given folder
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler Calendar
 * @author  Matthias Sommerfeld
 * @copyright 2004-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.3.7 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
$error = false;
$update_folderlist = false;
$foldertypes = array
        (0 => $WP_msg['SystemFolder']
        ,1 => $WP_msg['UserFolder']
        ,2 => $WP_msg['CalExternalCalendar']
        ,-1 => $WP_msg['notdef']
        );
$fid = (isset($_REQUEST['fid']) && $_REQUEST['fid']) ? $_REQUEST['fid'] : 0;
if (0 == $fid) {
    $fname = $WP_msg['CalMyEvents'];
    $ftype = 0;
} else {
    $myGrp = $cDB->get_group($fid, false, true);
    $fname = phm_entities($myGrp['name']);
    $ftype = 1;
    if ($myGrp['type'] == 1) {
        $ftype = 2;
    }
}
$validfields = array
        ('starts' => $WP_msg['CalStart']
        ,'ends' => $WP_msg['CalEnd']
        ,'title' => $WP_msg['CalTitle']
        ,'location' => $WP_msg['CalLocation']
        ,'description' => $WP_msg['CalDescription']
        ,'repetitions' => $WP_msg['CalListRep']
        ,'reminders' => $WP_msg['CalListRem']
        ,'reminders_sms' => $WP_msg['CalListRemSMS']
        ,'reminders_email' => $WP_msg['CalListRemEmail']
        );
$choices = (isset($_PM_['calendar']) && $_PM_['calendar']) ? $_PM_['calendar'] : array();

if (!empty($_REQUEST['save'])) {
    if (isset($_REQUEST['foldercolour'])) {
        $cDB->update_group($fid, null, $_REQUEST['foldercolour']);
        $myGrp = $cDB->get_group($fid, false, true);
    } elseif (isset($_REQUEST['uri']) && $ftype == 2) {
        $secUN = $secPW = $check = null;
        if (!empty($_REQUEST['username'])) {
            $secUN = confuse($_REQUEST['username'], md5($_REQUEST['uri']));
        } else {
            $secUN = '';
        }
        if (!empty($_REQUEST['password'])) {
            $secPW = confuse($_REQUEST['password'], md5($_REQUEST['uri']));
        } else {
            $secPW = '';
        }
        if (!empty($_REQUEST['checkevery_value'])) {
            $v = $_REQUEST['checkevery_value'];
            $factors = array('m' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800);
            $check = ((!$v || $v < 0) ? 0 : intval($v)) * $factors[$_REQUEST['checkevery_range']];
        }
        $cDB->update_group($fid, null, null, null, null, 1, $_REQUEST['uri'], $secUN, $secPW, 'text/calendar', $check);
        $myGrp = $cDB->get_group($fid, false, true);
    } elseif (!empty($_REQUEST['formname']) && $_REQUEST['formname'] == 'basic_settings') {
        $fSet = new DB_Controller_Foldersetting();
        if (!empty($_REQUEST['autoarchive']) && !empty($_REQUEST['autoarchive_age_inp'])) {
            $fSet->foldersetting_set('calendar', $fid, $_SESSION['phM_uid'], 'autoarchive', 1);
            $fSet->foldersetting_set('calendar', $fid, $_SESSION['phM_uid'], 'autoarchive_age', intval($_REQUEST['autoarchive_age_inp']).' '.$_REQUEST['autoarchive_age_drop']);
        } else {
            $fSet->foldersetting_del('calendar', $fid, $_SESSION['phM_uid'], 'autoarchive');
            $fSet->foldersetting_del('calendar', $fid, $_SESSION['phM_uid'], 'autoarchive_age');
        }
        if (!empty($_REQUEST['autodelete']) && !empty($_REQUEST['autodelete_age_inp'])) {
            $fSet->foldersetting_set('calendar', $fid, $_SESSION['phM_uid'], 'autodelete', 1);
            $fSet->foldersetting_set('calendar', $fid, $_SESSION['phM_uid'], 'autodelete_age', intval($_REQUEST['autodelete_age_inp']).' '.$_REQUEST['autodelete_age_drop']);
        } else {
            $fSet->foldersetting_del('calendar', $fid, $_SESSION['phM_uid'], 'autodelete');
            $fSet->foldersetting_del('calendar', $fid, $_SESSION['phM_uid'], 'autodelete_age');
        }
        if ($ftype > 0) {
            $new_ftype = null;
            if (!empty($_REQUEST['type'])) {
                $new_ftype = (2 == $_REQUEST['type']) ? 1 : 0;
                $ftype = $new_ftype+1;
            }
            $cDB->update_group($fid, null, null, isset($_REQUEST['show_in_sync']) ? 1 : 0, isset($_REQUEST['show_in_root']) ? 1 : 0, $new_ftype);
            $myGrp = $cDB->get_group($fid, false, true);
        }
    } else {
        $fieldcount = 0;
        $showfields = array();
        if (isset($_REQUEST['show_field'])) {
            foreach ($_REQUEST['show_field'] as $k => $v) {
                $showfields[$k] = true;
                ++$fieldcount;
            }
        }
        foreach ($validfields as $k => $v) {
            if (isset($_REQUEST['show_field'][$k]) && $_REQUEST['show_field'][$k]) {
                continue;
            }
            $showfields[$k] = false;
        }
        $choices['show_fields'] = $showfields;
        $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
        $GlChFile['calendar']['show_fields'] = $showfields;
        $GlChFile['calendar']['use_default_fields'] = (isset($_REQUEST['view_default']) && $_REQUEST['view_default']);
        if (0 == $fieldcount) {
            $GlChFile['calendar']['use_default_fields'] = true;
        }
        $GlChFile['calendar']['orderby'] = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'starts';
        $GlChFile['calendar']['orderdir'] = isset($_REQUEST['orderdir']) ? $_REQUEST['orderdir'] : 'ASC';
        $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
        $choices = $GlChFile['calendar'];
    }
    $global_message = $WP_msg['optssaved'];
}


$tpl = new phlyTemplate($_PM_['path']['templates'].'folderproperties.tpl');
if (!empty($global_message)) {
    $tpl->fill_block('has_global_message', 'message', $global_message);
}

$icon_path = $_PM_['path']['theme'].'/icons/';
$data['big_icon'] = $icon_path.'calendar_big.gif';
$data['icon'] = $icon_path.'calendar.png';
// System folder: No rename, no other icon
$tpl->assign_block('html_norename');
$tpl->assign_block('js_norename');
$tpl->assign_block('html_noicon');
$tpl->assign_block('js_noicon');

// Allow to set, whether the folder should appear in main folder and be included in syncs
if ($ftype != 0) {
    $t_hss = $tpl->get_block('has_show_in_sync');
    $t_hss->assign('msg_show_in_sync', $WP_msg['ShowInSync']);
    if (isset($myGrp['show_in_sync']) && $myGrp['show_in_sync']) {
        $t_hss->assign_block('show_in_sync');
    }
    $tpl->assign('has_show_in_sync', $t_hss);
    $t_hsr = $tpl->get_block('has_show_in_root');
    $t_hsr->assign('msg_show_in_root', $WP_msg['ShowInRoot']);
    if (isset($myGrp['show_in_root']) && $myGrp['show_in_root']) {
        $t_hsr->assign_block('show_in_root');
    }
    $tpl->assign('has_show_in_root', $t_hsr);

    if ($myGrp['owner'] == $_SESSION['phM_uid']) {
        $t_hts = $tpl->get_block('has_type_select');
        $t_htsl = $t_hts->get_block('line');
        foreach (array(1 => $WP_msg['UserFolder'], 2 => $WP_msg['CalExternalCalendar']) as $k => $v) {
            $t_htsl->assign(array('id' => $k, 'name' => $v));
            if ($ftype == $k) {
                $t_htsl->assign_block('sel');
            }
            $t_hts->assign('line', $t_htsl);
            $t_htsl->clear();
        }
        $tpl->assign('has_type_select', $t_hts);

        // Allowing to archive / delete events in external (read-only) calendars is not quite sensible, is it?
        if ($ftype == 1) {
            // Can override global autoarchive setting
            $t_haa = $tpl->get_block('has_autoarchive');
            $t_haa->assign('msg_autoarchive_olderthan', $WP_msg['AutoArchiveElementsOlderThan']);
            if (!empty($choices['autoarchive'])) {
                $t_haa->assign_block('autoarchive');
            }
            $unit = null;
            if (!empty($choices['autoarchive_age']) && preg_match('!^(\d+)\s([a-zA-Z]+)$!', $choices['autoarchive_age'], $found)) {
                $t_haa->assign('autoarchive_age', $found[1]);
                $unit = $found[2];
            }
            $t_blk = $t_haa->get_block('autoarchive_age_drop');
            foreach (array('day' => $WP_msg['Days'], 'week' => $WP_msg['Weeks'], 'month' => $WP_msg['Months'], 'year' => $WP_msg['Years']) as $k => $v) {
                $t_blk->assign(array('unit' => $k, 'name' => $v));
                if ($unit == $k) {
                    $t_blk->assign_block('sel');
                }
                $t_haa->assign('autoarchive_age_drop', $t_blk);
                $t_blk->clear();
            }
            $tpl->assign('has_autoarchive', $t_haa);

            // Can override global autodelete setting
            $t_haa = $tpl->get_block('has_autodelete');
            $t_haa->assign('msg_autodelete_olderthan', $WP_msg['AutoDeleteElementsOlderThan']);
            if (!empty($choices['autodelete'])) {
                $t_haa->assign_block('autodelete');
            }
            $unit = null;
            if (!empty($choices['autodelete_age']) && preg_match('!^(\d+)\s([a-zA-Z]+)$!', $choices['autodelete_age'], $found)) {
                $t_haa->assign('autodelete_age', $found[1]);
                $unit = $found[2];
            }
            $t_blk = $t_haa->get_block('autodelete_age_drop');
            foreach (array('day' => $WP_msg['Days'], 'week' => $WP_msg['Weeks'], 'month' => $WP_msg['Months'], 'year' => $WP_msg['Years']) as $k => $v) {
                $t_blk->assign(array('unit' => $k, 'name' => $v));
                if ($unit == $k) {
                    $t_blk->assign_block('sel');
                }
                $t_haa->assign('autodelete_age_drop', $t_blk);
                $t_blk->clear();
            }
            $tpl->assign('has_autodelete', $t_haa);
        }
    }

    // Show the save button
    $tpl->assign_block('has_store_basic_settings');
}

if (!isset($choices['show_fields']) || empty($choices['show_fields'])) {
    $choices['show_fields'] = array('starts' => 1, 'ends' => 1, 'title' => 1, 'location' => 1, 'repetitions' => 1, 'reminders' => 1);
}
$t_d = $tpl->get_block('display');
$td_f = $t_d->get_block('dbline');
// Show currently selected fields first, in order of appearance
foreach ($choices['show_fields'] as $k => $v) {
    if (!$v || !isset($validfields[$k])) {
        continue;
    }
    $td_f->assign(array('id' => $k, 'value' => $validfields[$k]));
    $td_f->assign_block('checked');
    $t_d->assign('dbline', $td_f);
    $td_f->clear();
}
// Now the rest of the fields currently not selected
foreach ($validfields as $name => $text) {
    if (isset($choices['show_fields'][$name]) && $choices['show_fields'][$name]) {
        continue;
    }
    if (isset($choices['show_fields']['meta_'.$name]) && $choices['show_fields']['meta_'.$name]) {
        continue;
    }
    $td_f->assign(array('id' => $name, 'value' => $text));
    $t_d->assign('dbline', $td_f);
    $td_f->clear();
}
$t_d->assign('sel_size', sizeof($validfields));

if (!isset($choices['show_fields']) || (isset($choices['use_default_fields']) && $choices['use_default_fields'])) {
    $t_d->assign_block('view_default');
}
// Define orderby / orderdir
$t_ob = $t_d->get_block('has_orderby');
$t_ol = $t_ob->get_block('orderline');
// Preset should be matching the default behaviour of phlyMail
if (!isset($choices['orderby'])) {
    $choices['orderby'] = 'lastname';
    $choices['orderdir'] = 'ASC';
}
foreach ($validfields as $name => $text) {
    $t_ol->assign(array('val' => $name, 'name' => $text));
    if ($choices['orderby'] == $name) {
        $t_ol->assign_block('sel');
    }
    $t_ob->assign('orderline', $t_ol);
    $t_ol->clear();
}
$t_ob->assign_block((isset($choices['orderdir']) && $choices['orderdir'] == 'DESC') ? 'seldesc' : 'selasc');
$t_ob->assign(array
        ('msg_orderby' => $WP_msg['OrderBy']
        ,'msg_asc' => $WP_msg['OrderDirAsc']
        ,'msg_desc' => $WP_msg['OrderDirDesc']
        ));
$t_d->assign('has_orderby', $t_ob);
$t_d->assign_block('nopreview');
// End orderby
$tpl->assign('display', $t_d);
// Quotas
$t_qu = $tpl->get_block('quotas');
$t_ql = $t_qu->get_block('quotaline');
$num_quotas = 0;
foreach (array(
        'number_appointments' => array( 'type' => 'int', 'method' => 'quota_getnumberofrecords', 'name' => $WP_msg['QuotaNumberAppointments']),
        'number_tasks' => array('type' => 'int', 'method' => 'quota_getnumberoftasks', 'name' => $WP_msg['ConfigQuotaNumberTasks']),
        'number_groups' => array('type' => 'int', 'method' => 'quota_groupsnum', 'name' => $WP_msg['QuotaNumberGroups'])
        ) as $k => $v) {
    $v['limit'] = $DB->quota_get($_SESSION['phM_uid'], 'calendar', $k);
    if (false === $v['limit']) {
        continue;
    }
    $num_quotas++;
    $v['use'] = $cDB->{$v['method']}();
    if ($v['type'] == 'filesize') {
        $use = $v['use'];
        $limit = $v['limit'];
        $v['use'] = size_format($v['use']);
        $v['limit'] = size_format($v['limit']);
    } else {
        $use = $v['use'];
        $limit = $v['limit'];
    }
    $t_ql->assign(array('crit_id' => $k, 'msg_crit' => $v['name'], 'msg_use' => $v['use'], 'msg_limit' => $v['limit'], 'use' => $use, 'limit' => $limit));
    $t_qu->assign('quotaline', $t_ql);
    $t_ql->clear();
}
if ($num_quotas) {
    $tpl->assign('quotas', $t_qu);
    $tpl->assign('leg_quotas', $WP_msg['QuotaLegend']);
}
// Ende Qutoas
if ($ftype < 3) { // Prepared for later inclusion of external calendars
    $xna_action = json_encode(array('f' => 'ICS', 'g' => $fid, 'uid' => $_SESSION['phM_uid']));

    $XNA = new DB_Controller_XNA();
    $xna_uuid = $XNA->registered('calendar', 'export', $xna_action);
    if (isset($_REQUEST['xna_register']) && empty($xna_uuid)) {
        $xna_uuid = $XNA->register('calendar', 'export', $xna_action);
    } elseif (isset($_REQUEST['xna_unregister']) && !empty($xna_uuid)) {
        $XNA->unregister($xna_uuid);
        $xna_uuid = null;
    }
    if (!empty($xna_uuid)) {
        $xna_uuid = PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/api.php?XNA='.$xna_uuid;
        $xna_submit = $WP_msg['APIDeleteXNA'];
        $xna_gen_url = 'xna_unregister=1';
    } else {
        $xna_uuid = '';
        $xna_submit = $WP_msg['APICreateXNA'];
        $xna_gen_url = 'xna_register=1';
    }

    $tpl->fill_block('webapi', array
            ('leg_api' => $WP_msg ['APILegend']
            ,'about_webapi' => $WP_msg['APIAbout']
            ,'url' => PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/api.php?h=calendar&amp;f=ICS'.($fid != 0 ? '&amp;g='.intval($fid) : '')
            ,'url_xna' => $xna_uuid
            ,'xna_submit_value' => $xna_submit
            ,'generate_xna_url' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=calendar&fid='.$fid.'&'.$xna_gen_url)
            ,'about_webapi_xna' => $WP_msg['APIPrefixCreateXNA']
            ,'title_http' => $WP_msg['APITitleHttpAuth']
            ,'title_xna' => $WP_msg['APITitleXNA']
            ));
}
// Set the calendar's colour (for identifying the subcalendars in the main calendar)
if ($ftype != 0) {
    $t_c = $tpl->get_block('colour');
    $t_cs = $t_c->get_block('sel_foldercolour');
    $d = opendir($_PM_['path']['frontend'].'/img');
    while ($d && ($f = readdir($d)) !== false) {
        if (substr($f, 0, 12) != 'colour_flag_') {
            continue;
        }
        $hex = substr($f, 12, 6);
        $t_cs->assign('hex', $hex);
        if ($myGrp['colour'] == $hex) {
            $t_cs->assign_block('sel');
        }
        $t_c->assign('sel_foldercolour', $t_cs);
        $t_cs->clear();
    }
    $t_c->assign('leg_colour', $WP_msg['LegFolderColour']);
    $tpl->assign('colour', $t_c);
    closedir($d);
}

if ($ftype == 2) {
    $t_es = $tpl->get_block('externalsource');
    $t_es->assign(array
            ('leg_externalsource' => $WP_msg['LegFolderExternalSource']
            ,'msg_username' => $WP_msg['sysuser']
            ,'msg_passwod' => $WP_msg['syspass']
            ,'msg_checkevery' => $WP_msg['FolderCheckEvery']
            ,'uri' => !(empty($myGrp['uri'])) ? $myGrp['uri'] : ''
            ,'username' => !(empty($myGrp['ext_un'])) ? deconfuse($myGrp['ext_un'], md5($myGrp['uri'])) : ''
            ,'password' => !(empty($myGrp['ext_pw'])) ? deconfuse($myGrp['ext_pw'], md5($myGrp['uri'])) : ''
            ));
    if ($myGrp['checkevery'] >= 604800 && (intval($myGrp['checkevery'] / 604800) == $myGrp['checkevery'] / 604800)) {
        $t_es->assign_block('s_w_w');
        $t_es->assign('checkevery_value', $myGrp['checkevery'] / 604800);
    } elseif ($myGrp['checkevery'] >= 86400 && (intval($myGrp['checkevery'] / 86400) == $myGrp['checkevery'] / 86400)) {
        $t_es->assign_block('s_w_d');
        $t_es->assign('checkevery_value', $myGrp['checkevery'] / 86400);
    } elseif ($myGrp['checkevery'] >= 3600 && (intval($myGrp['checkevery'] / 3600) == $myGrp['checkevery'] / 3600)) {
        $t_es->assign_block('s_w_h');
        $t_es->assign('checkevery_value', $myGrp['checkevery'] / 3600);
    } elseif ($myGrp['checkevery'] >= 60 && (intval($myGrp['checkevery'] / 60) == $myGrp['checkevery'] / 60)) {
        $t_es->assign_block('s_w_m');
        $t_es->assign('checkevery_value', $myGrp['checkevery'] / 60);
    } else {
        $t_es->assign_block('s_w_m');
        $t_es->assign('checkevery_value', 0);
    }
    $tpl->assign('externalsource', $t_es);
    // Letzter Check
    if (strtotime($myGrp['lastcheck']) > 0) {
        $tpl->fill_block('has_last_update', array
                ('msg_last_update'  => $WP_msg['FolderLastCheck']
                ,'last_update' => date($WP_msg['dateformat'], strtotime($myGrp['lastcheck']))
                ));
    }
}
$tpl->assign(array
        ('big_icon' => $data['big_icon']
        ,'foldername' => $fname
        ,'msg_name'  => $WP_msg['FolderName']
        ,'msg_type' => $WP_msg['FolderType']
        ,'msg_properties' => $WP_msg['properties']
        ,'msg_has_folders' => $WP_msg['FolderHasFolders']
        ,'msg_has_items' => $WP_msg['FolderHasItems']
        ,'leg_display' => $WP_msg['LegDisplayAndFields']
        ,'msg_use_preview' => $WP_msg['FolderUsePrevie']
        ,'msg_showfields' => $WP_msg['FolderShowFields']
        ,'msg_use_default' => $WP_msg['FolderUseDefFields']
        ,'msg_day' => $WP_msg['CalDay']
        ,'msg_days' => $WP_msg['CalDays']
        ,'msg_weeks' => $WP_msg['CalWeeks']
        ,'msg_month' => $WP_msg['CalMonth']
        ,'msg_year' => $WP_msg['CalYear']
        ,'msg_hour' => $WP_msg['CalHour']
        ,'msg_hours' => $WP_msg['CalHours']
        ,'msg_minute' => $WP_msg['CalMinute']
        ,'msg_minutes' => $WP_msg['CalMinutes']
        ,'has_folders' => $ftype == 0 ? $WP_msg['yes'] : $WP_msg['no']
        ,'has_items' => $WP_msg['yes']
        ,'type' => $foldertypes[$ftype]
        ,'msg_save' => $WP_msg['save']
        ,'form_target' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=calendar&save=1&fid='.$fid)
        ));
