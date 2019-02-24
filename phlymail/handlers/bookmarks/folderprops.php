<?php
/**
 * Edit the properties of a given folder
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler Bookmarks
 * @author  Matthias Sommerfeld
 * @copyright 2001-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 4.1.2 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$cDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
$fSet = new DB_Controller_Foldersetting();
$foldertypes = array
        (0 => $WP_msg['SystemFolder']
        ,1 => $WP_msg['UserFolder']
        ,-1 => $WP_msg['notdef']
        );
$error = false;
$update_folderlist = false;
$fid = (isset($_REQUEST['fid']) && $_REQUEST['fid']) ? $_REQUEST['fid'] : 0;
if (0 == $fid) {
    $fname = $WP_msg['MainFoldername'];
    $ftype = 0;
} else {
    $folderInfo = $cDB->get_folder($fid);
    $fname = phm_entities($folderInfo['name']);
    $ftype = 1;
}
$choices = (isset($_PM_['bookmarks']) && $_PM_['bookmarks']) ? $_PM_['bookmarks'] : array();

if (isset($_REQUEST['save']) && $_REQUEST['save']) {/*
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
    $GlChFile['bookmarks']['show_fields'] = $showfields;
    $GlChFile['bookmarks']['use_preview'] = (isset($_REQUEST['show_preview']) && $_REQUEST['show_preview']);
    $GlChFile['bookmarks']['use_default_fields'] = (isset($_REQUEST['view_default']) && $_REQUEST['view_default']);
    if (0 == $fieldcount) $GlChFile['bookmarks']['use_default_fields'] = true;
    $GlChFile['bookmarks']['orderby'] = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'lastname';
    $GlChFile['bookmarks']['orderdir'] = isset($_REQUEST['orderdir']) ? $_REQUEST['orderdir'] : 'ASC';
    $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
    $choices = $GlChFile['bookmarks'];
    */
    if (isset($_REQUEST['show_in_sync']) && $_REQUEST['show_in_sync']) {
        $fSet->foldersetting_del('bookmarks', $fid, $_SESSION['phM_uid'], 'not_in_sync');
    } else {
        $fSet->foldersetting_set('bookmarks', $fid, $_SESSION['phM_uid'], 'not_in_sync', 1);
    }
    $global_message = $WP_msg['optssaved'];
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'folderproperties.tpl');
if (!empty($global_message)) {
    $tpl->fill_block('has_global_message', 'message', $global_message);
}

$icon_path = $_PM_['path']['theme'].'/icons/';
$data['big_icon'] = $icon_path.'bookmarks_big.gif';
$data['icon'] = $icon_path.'bookmarks.png';
// System folder: No rename, no other icon
$tpl->assign_block('html_norename');
$tpl->assign_block('js_norename');
$tpl->assign_block('html_noicon');
$tpl->assign_block('js_noicon');
if (isset($choices['use_preview']) && $choices['use_preview']) {
    $tpl->assign_block('show_preview');
}

$choices['not_in_sync'] = $fSet->foldersetting_get('bookmarks', $fid, $_SESSION['phM_uid'], 'not_in_sync');
// Flag for not in sync
$t_hss = $tpl->get_block('has_show_in_sync');
$t_hss->assign('msg_show_in_sync', $WP_msg['ShowInSync']);
if (!isset($choices['not_in_sync']) || !$choices['not_in_sync']) {
    $t_hss->assign_block('show_in_sync');
}
$tpl->assign('has_show_in_sync', $t_hss);
$tpl->assign_block('has_store_basic_settings');

/** Code below in an intermediate state, just basics supported right now, rest follows later **/
$t_d = $tpl->get_block('display');
$t_d->assign_block('view_default');
$t_d->assign_block('show_preview');
$t_d->assign_block('nopreview');
$t_d->assign_block('noviewdefault');
$tpl->assign('display', $t_d);

// Quotas
$t_qu = $tpl->get_block('quotas');
$t_ql = $t_qu->get_block('quotaline');
$num_quotas = 0;
foreach (array
        ('number_bookmarks' => array
                ('type' => 'int'
                ,'method' => 'quota_bookmarksnum'
                ,'name' => $WP_msg['QuotaNumberBookmarks']
                )
        ,'number_groups' => array
                ('type' => 'int'
                ,'method' => 'quota_foldersnum'
                ,'name' => $WP_msg['QuotaNumberGroups']
                )
        ) as $k => $v) {
    $v['limit'] = $DB->quota_get($_SESSION['phM_uid'], 'bookmarks', $k);
    if (false === $v['limit']) continue;
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
    $t_ql->assign(array
            ('crit_id' => $k
            ,'msg_crit' => $v['name']
            ,'msg_use' => $v['use']
            ,'msg_limit' => $v['limit']
            ,'use' => $use
            ,'limit' => $limit
            ));
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
        ,'has_folders' => /*$ftype == 0 ? */$WP_msg['yes']/* : $WP_msg['no']*/
        ,'has_items' => $WP_msg['yes']
        ,'type' => $foldertypes[$ftype]
        ,'msg_save' => $WP_msg['save']
        ,'form_target' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=bookmarks&save=1')
        ));
