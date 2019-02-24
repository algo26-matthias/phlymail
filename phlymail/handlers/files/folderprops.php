<?php
/**
 * Edit the properties of a given folder
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$foldertypes = array(0 => $WP_msg['SystemFolder'], 1 => $WP_msg['UserFolder'], -1 => $WP_msg['notdef']);
$validfields = array();
$error = false;
$update_folderlist = false;

$FS = new handler_files_driver($_SESSION['phM_uid']);
$fSet = new DB_Controller_Foldersetting();
$fid = (isset($_REQUEST['fid']) && $_REQUEST['fid']) ? $_REQUEST['fid'] : 0;
$data = $FS->get_folder_info($fid);
$choices = (isset($data['settings']) && $data['settings']) ? unserialize($data['settings']) : array();

if (isset($_REQUEST['save']) && $_REQUEST['save']) {
    if (isset($_REQUEST['show_in_sync']) && $_REQUEST['show_in_sync']) {
        $fSet->foldersetting_del('files', $fid, $_SESSION['phM_uid'], 'not_in_sync');
    } else {
        $fSet->foldersetting_set('files', $fid, $_SESSION['phM_uid'], 'not_in_sync', 1);
    }

    $global_message = $WP_msg['optssaved'];
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'folderproperties.tpl');
if (!empty($global_message)) {
    $tpl->fill_block('has_global_message', 'message', $global_message);
}

$icon_path = $_PM_['path']['theme'].'/icons/';
// Find special icons
switch ($data['icon']) {
    case ':trash': $data['big_icon'] = $icon_path.'waste_big.gif'; break;
    case ':calendar': $data['big_icon'] = $icon_path.'calendar_big.gif'; break;
    case ':contacts': $data['big_icon'] = $icon_path.'contacts_big.gif'; break;
    case ':files': $data['big_icon'] = $icon_path.'files_big.gif'; break;
}
if (!isset($data['big_icon']) || !file_exists($data['big_icon'])) {
    $data['big_icon'] = $icon_path.'folder_def_big.gif';
}
// System folder: No rename, no other icon
if (0 == $data['type']) {
    $tpl->assign_block('html_norename');
    $tpl->assign_block('js_norename');
    $tpl->assign_block('html_noicon');
    $tpl->assign_block('js_noicon');
}
if (isset($choices['use_preview']) && $choices['use_preview']) {
    $tpl->assign_block('show_preview');
}
if (!isset($data['type']) || !isset($foldertypes[$data['type']])) {
    $data['type'] = -1;
}
$msg_items   = (isset($data['has_items'])   && $data['has_items'])   ? $WP_msg['yes'] : $WP_msg['no'];
$msg_folders = (isset($data['has_folders']) && $data['has_folders']) ? $WP_msg['yes'] : $WP_msg['no'];
$msg_type    = $foldertypes[$data['type']];

if (isset($data['has_items']) && $data['has_items']) {
    $choices['not_in_sync'] = $fSet->foldersetting_get('files', $fid, $_SESSION['phM_uid'], 'not_in_sync');
    // Flag for not in sync
    $t_hss = $tpl->get_block('has_show_in_sync');
    $t_hss->assign('msg_show_in_sync', $WP_msg['ShowInSync']);
    if (!isset($choices['not_in_sync']) || !$choices['not_in_sync']) {
        $t_hss->assign_block('show_in_sync');
    }
    $tpl->assign('has_show_in_sync', $t_hss);
    $tpl->assign_block('has_store_basic_settings');

    $t_d = $tpl->get_block('display');
    $t_d->assign_block('view_default');
    $t_d->assign_block('show_preview');
    $t_d->assign_block('nopreview');
    $t_d->assign_block('noviewdefault');
    $tpl->assign('display', $t_d);
} else {
    // Quotas
    $t_qu = $tpl->get_block('quotas');
    $t_ql = $t_qu->get_block('quotaline');
    $num_quotas = 0;
    foreach (array
            ('size_storage' => array
                    ('type' => 'filesize'
                    ,'method' => 'quota_getitemsize'
                    ,'name' => $WP_msg['QuotaStorageSize']
                    )
            ,'number_items' => array
                    ('type' => 'int'
                    ,'method' => 'quota_getitemnum'
                    ,'name' => $WP_msg['QuotaNumMails']
                    )
            ,'number_folders' => array
                    ('type' => 'int'
                    ,'method' => 'quota_getfoldernum'
                    ,'name' => $WP_msg['QuotaNumFolders']
                    )
            ) as $k => $v) {
        $v['limit'] = $DB->quota_get($_SESSION['phM_uid'], 'files', $k);
        if (false === $v['limit']) {
            continue;
        }
        $num_quotas++;
        $v['use'] = $FS->{$v['method']}();
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
}
$tpl->assign(array
        ('big_icon' => $data['big_icon']
        ,'foldername' => $data['foldername']
        ,'msg_name'  => $WP_msg['FolderName']
        ,'msg_type' => $WP_msg['FolderType']
        ,'msg_properties' => $WP_msg['properties']
        ,'msg_has_folders' => $WP_msg['FolderHasFolders']
        ,'msg_has_items' => $WP_msg['FolderHasItems']
        ,'leg_display' => $WP_msg['LegDisplayAndFields']
        ,'msg_use_preview' => $WP_msg['FolderUsePrevie']
        ,'msg_showfields' => $WP_msg['FolderShowFields']
        ,'msg_use_default' => $WP_msg['FolderUseDefFields']
        ,'has_folders' => $msg_folders
        ,'has_items' => $msg_items
        ,'type' => $msg_type
        ,'msg_save' => $WP_msg['save']
        ,'form_target' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=files&save=1&fid='.$fid)
        ));
