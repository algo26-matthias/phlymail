<?php
/**
 * Edit the properties of a given folder
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler Contacts
 * @author  Matthias Sommerfeld
 * @copyright 2001-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 4.1.4 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
$foldertypes = array
        (0 => $WP_msg['SystemFolder']
        ,1 => $WP_msg['UserFolder']
        // ,2 => $WP_msg['CalExternalCalendar']
        ,-1 => $WP_msg['notdef']
        );
$error = false;
$update_folderlist = false;
$fid = (isset($_REQUEST['fid']) && $_REQUEST['fid']) ? $_REQUEST['fid'] : 0;
if (0 == $fid) {
    $fname = $WP_msg['MainFoldername'];
    $ftype = 0;
} else {
    $myGrp = $cDB->get_group($fid, false);
    $fname = phm_entities($myGrp['name']);
    $ftype = 1;
    if ($myGrp['type'] == 1) $ftype = 2;
}

$validfields = array
        ('nick' => $WP_msg['nick']
        ,'firstname' => $WP_msg['fnam']
        ,'lastname' => $WP_msg['snam']
        ,'company' => $WP_msg['company']
        ,'email1' => $WP_msg['emai1']
        ,'email2' => $WP_msg['emai2']
        ,'tel_private' => $WP_msg['fon']
        ,'tel_business' => $WP_msg['fon2']
        ,'cellular' => $WP_msg['cell']
        ,'fax' => $WP_msg['fax']
        );
$choices = (isset($_PM_['contacts']) && $_PM_['contacts']) ? $_PM_['contacts'] : array();

if (isset($_REQUEST['save']) && $_REQUEST['save']) {
    if ($ftype > 0 && !empty($_REQUEST['formname']) && $_REQUEST['formname'] == 'basic_settings') {
        $cDB->update_group($fid, null, isset($_REQUEST['show_in_sync']) ? 1 : 0, isset($_REQUEST['show_in_root']) ? 1 : 0);
        $myGrp = $cDB->get_group($fid, false);
    } else {
        $fieldcount = 0;
        foreach ($validfields as $k => $v) {
            if (isset($_REQUEST['show_field'][$k]) && $_REQUEST['show_field'][$k]) {
                $showfields[$k] = true;
                ++$fieldcount;
            } else {
                $showfields[$k] = false;
            }
        }
        $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
        $GlChFile['contacts']['show_fields'] = $showfields;
        $GlChFile['contacts']['use_preview'] = (isset($_REQUEST['show_preview']) && $_REQUEST['show_preview']);
        $GlChFile['contacts']['use_default_fields'] = (isset($_REQUEST['view_default']) && $_REQUEST['view_default']);
        if (0 == $fieldcount) {
            $GlChFile['contacts']['use_default_fields'] = true;
        }
        $GlChFile['contacts']['orderby'] = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'lastname';
        $GlChFile['contacts']['orderdir'] = isset($_REQUEST['orderdir']) ? $_REQUEST['orderdir'] : 'ASC';
        $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
        $choices = $GlChFile['contacts'];
    }
    $global_message = $WP_msg['optssaved'];
}


$tpl = new phlyTemplate($_PM_['path']['templates'].'folderproperties.tpl');
if (!empty($global_message)) {
    $tpl->fill_block('has_global_message', 'message', $global_message);
}

$icon_path = $_PM_['path']['theme'].'/icons/';
$data['big_icon'] = $icon_path.'contacts_big.gif';
$data['icon'] = $icon_path.'contacts.png';
// System folder: No rename, no other icon
$tpl->assign_block('html_norename');
$tpl->assign_block('js_norename');
$tpl->assign_block('html_noicon');
$tpl->assign_block('js_noicon');
if (isset($choices['use_preview']) && $choices['use_preview']) {
    $tpl->assign_block('show_preview');
}

// Allow to set, whether the folder should appear in main folder and be included in syncs
if ($ftype != 0) {
    $t_hss = $tpl->get_block('has_show_in_sync');
    $t_hss->assign('msg_show_in_sync', $WP_msg['ShowInSync']);
    if (!isset($myGrp['show_in_sync']) || $myGrp['show_in_sync']) {
        $t_hss->assign_block('show_in_sync');
    }
    $tpl->assign('has_show_in_sync', $t_hss);
    $t_hsr = $tpl->get_block('has_show_in_root');
    $t_hsr->assign('msg_show_in_root', $WP_msg['ShowInRoot']);
    if (!isset($myGrp['show_in_root']) || $myGrp['show_in_root']) {
        $t_hsr->assign_block('show_in_root');
    }
    $tpl->assign('has_show_in_root', $t_hsr);
    $tpl->assign_block('has_store_basic_settings');
}

$t_d = $tpl->get_block('display');
$td_f = $t_d->get_block('dbline');
foreach ($validfields as $name => $text) {
    $td_f->assign(array('id' => $name, 'value' => $text));
    if (isset($choices['show_fields'][$name]) && $choices['show_fields'][$name]) {
        $td_f->assign_block('checked');
    }
    $t_d->assign('dbline', $td_f);
    $td_f->clear();
}
$t_d->assign('sel_size', sizeof($validfields));

if (!isset($choices['show_fields']) || (isset($choices['use_default_fields']) && $choices['use_default_fields'])) {
    $t_d->assign_block('view_default');
}
if (!isset($choices['use_preview'])) {
    $choices['use_preview'] = $_PM_['core']['folders_usepreview'];
}
if ($choices['use_preview']) {
    $t_d->assign_block('show_preview');
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
// End orderby
$tpl->assign('display', $t_d);

// Quotas
$t_qu = $tpl->get_block('quotas');
$t_ql = $t_qu->get_block('quotaline');
$num_quotas = 0;
foreach (array
        ('number_contacts' => array('type' => 'int', 'method' => 'quota_contactsnum', 'name' => $WP_msg['QuotaNumberContacts'])
        ,'number_groups' => array('type' => 'int', 'method' => 'quota_groupsnum', 'name' => $WP_msg['QuotaNumberGroups'])
        ) as $k => $v) {
    $v['limit'] = $DB->quota_get($_SESSION['phM_uid'], 'contacts', $k);
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
        ,'has_folders' => $ftype == 0 ? $WP_msg['yes'] : $WP_msg['no']
        ,'has_items' => $WP_msg['yes']
        ,'type' => $foldertypes[$ftype]
        ,'msg_save' => $WP_msg['save']
        ,'form_target' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=contacts&save=1&fid='.$fid)
        ));
if ($ftype < 2) { // Prepared for later inclusion of external calendars
    $xna_action = json_encode(array('f' => 'LDIF', 'g' => $fid, 'uid' => $_SESSION['phM_uid']));

    $XNA = new DB_Controller_XNA();

    $xna_uuid = $XNA->registered('contacts', 'export', $xna_action);
    if (isset($_REQUEST['xna_register']) && empty($xna_uuid)) {
        $xna_uuid = $XNA->register('contacts', 'export', $xna_action);
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
            ,'url' => PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/api.php?h=contacts&amp;f=LDIF'.($fid != 0 ? '&amp;g='.intval($fid) : '')
            ,'url_xna' => $xna_uuid
            ,'xna_submit_value' => $xna_submit
            ,'generate_xna_url' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=contacts&fid='.$fid.'&'.$xna_gen_url)
            ,'about_webapi_xna' => $WP_msg['APIPrefixCreateXNA']
            ,'title_http' => $WP_msg['APITitleHttpAuth']
            ,'title_xna' => $WP_msg['APITitleXNA']
            ));
}