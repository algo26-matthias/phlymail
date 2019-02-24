<?php
/**
 * folderprops.php - Edit the properties of a given folder
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Email handler
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.9 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$foldertypes = array(
        0 => $WP_msg['SystemFolder'],
        1 => $WP_msg['UserFolder'],
        -1 => $WP_msg['notdef'],
        10 => $WP_msg['SystemFolder'].' (IMAP)',
        11 => $WP_msg['UserFolder'].' (IMAP)',
        20 => $WP_msg['SystemFolder'].' ('.$WP_msg['Virtual'].')',
        21 => $WP_msg['UserFolder'].' ('.$WP_msg['Virtual'].')'
);

$validfields = array
        ('status' => $WP_msg['MailStatus']
        ,'attachments' => $WP_msg['attach']
        ,'hpriority' => $WP_msg['prio']
        ,'hsubject' => $WP_msg['subject']
        ,'hfrom' => $WP_msg['from']
        ,'hto' => $WP_msg['to']
        ,'hcc' => 'CC'
        ,'hbcc' => 'BCC'
        ,'hdate_sent' => $WP_msg['date']
        ,'hsize' => $WP_msg['size']
        );
$error = false;
$update_folderlist = false;
$FS = new handler_email_driver($_SESSION['phM_uid']);
$fid = (isset($_REQUEST['fid']) && $_REQUEST['fid']) ? $_REQUEST['fid'] : 0;
$data = $FS->get_folder_info($fid);
$choices = (isset($data['settings']) && $data['settings']) ? $data['settings'] : array();

foreach ($_SESSION['WPs_Plugin'] as $handler) {
    require_once($handler['path']);
    $t = new $handler['class']($_PM_);
    $t->pluginhandler('set_default_folder_props');
}

if (isset($_REQUEST['save']) && $_REQUEST['save']) {
    foreach ($_SESSION['WPs_Plugin'] as $handler) {
        require_once($handler['path']);
        $t = new $handler['class']($_PM_);
        $t->pluginhandler('save_folder_props');
    }
    if (!empty($_REQUEST['formname']) && $_REQUEST['formname'] == 'basic_settings') {
        if (isset($_REQUEST['show_in_sync']) && $_REQUEST['show_in_sync']) {
            unset($choices['not_in_sync']);
        } else {
            $choices['not_in_sync'] = 1;
        }
        if (isset($_REQUEST['show_in_pinboard']) && $_REQUEST['show_in_pinboard']) {
            unset($choices['not_in_pinboard']);
        } else {
            $choices['not_in_pinboard'] = 1;
        }
        if (!empty($_REQUEST['autoarchive']) && !empty($_REQUEST['autoarchive_age_inp'])) {
            $choices['autoarchive'] = 1;
            $choices['autoarchive_age'] = intval($_REQUEST['autoarchive_age_inp']).' '.$_REQUEST['autoarchive_age_drop'];
        } else {
            $choices['autoarchive'] = 0;
            $choices['autoarchive_age'] = 0;
        }
        if (!empty($_REQUEST['autodelete']) && !empty($_REQUEST['autodelete_age_inp'])) {
            $choices['autodelete'] = 1;
            $choices['autodelete_age'] = intval($_REQUEST['autodelete_age_inp']).' '.$_REQUEST['autodelete_age_drop'];
        } else {
            $choices['autodelete'] = 0;
            $choices['autodelete_age'] = 0;
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
        if (isset($_REQUEST['set_as_default']) && $_REQUEST['set_as_default']) {
            $user_choices = $DB->get_usr_choices($_SESSION['phM_uid']);
            $user_choices['email']['folder_default_fields'] = $showfields;
            $DB->set_usr_choices($_SESSION['phM_uid'], $user_choices);
        }
        $choices['use_preview'] = (isset($_REQUEST['show_preview']) && $_REQUEST['show_preview']) ? 1 : 0;
        $choices['use_default_fields'] = (isset($_REQUEST['view_default']) && $_REQUEST['view_default']) ? 1 : 0;
        if (0 == $fieldcount) {
            $choices['use_default_fields'] = 1;
        }
        $choices['groupby'] = isset($_REQUEST['groupby']) && $_REQUEST['groupby'] ? $_REQUEST['groupby'] : '';
        $choices['orderby'] = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'hdate_sent';
        $choices['orderdir'] = isset($_REQUEST['orderdir']) ? $_REQUEST['orderdir'] : 'DESC';
    }
    $FS->set_folder_settings($fid, $choices);

    $global_message = $WP_msg['optssaved'];
}


$tpl = new phlyTemplate($_PM_['path']['templates'].'folderproperties.tpl');
if (!empty($global_message)) {
    $tpl->fill_block('has_global_message', 'message', $global_message);
}

$icon_path = $_PM_['path']['theme'].'/icons/';
// Find special icons
switch ($data['icon']) {
    case ':inbox':    $data['big_icon'] = $icon_path.'inbox_big.gif';    break;
    case ':outbox':   $data['big_icon'] = $icon_path.'outbox_big.gif';   break;
    case ':archive':  $data['big_icon'] = $icon_path.'archive_big.gif';  break;
    case ':sent':     $data['big_icon'] = $icon_path.'sent_big.gif';     break;
    case ':mailbox':  $data['big_icon'] = $icon_path.'mailbox_big.gif';  break;
    case ':calendar': $data['big_icon'] = $icon_path.'calendar_big.gif'; break;
    case ':contacts': $data['big_icon'] = $icon_path.'contacts_big.gif'; break;
    case ':notes':    $data['big_icon'] = $icon_path.'notes_big.gif';    break;
    case ':files':    $data['big_icon'] = $icon_path.'files_big.gif';    break;
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
$msg_items = (isset($data['has_items']) && $data['has_items']) ? $WP_msg['yes'] : $WP_msg['no'];
$msg_folders = (isset($data['has_folders']) && $data['has_folders']) ? $WP_msg['yes'] : $WP_msg['no'];
$msg_type = $foldertypes[$data['type']];

if (isset($data['has_items']) && $data['has_items']) {
    if (!isset($choices['show_fields']) || empty($choices['show_fields'])) {
        $choices['show_fields'] = ($data['icon'] == ':sent')
                ? array('status' => 1, 'hpriority' => 1, 'colour' => 1, 'attachments' => 1, 'hsubject' => 1, 'hto' => 1, 'hdate_sent' => 1, 'hsize' => 1)
                : array('status' => 1, 'hpriority' => 1, 'colour' => 1, 'attachments' => 1, 'hsubject' => 1, 'hfrom' => 1, 'hdate_sent' => 1, 'hsize' => 1);
    }
    $t_d = $tpl->get_block('display');
    $td_f = $t_d->get_block('dbline');
    // Show currently selected fields first, in order of appearance
    foreach ($choices['show_fields'] as $k => $v) {
        if (!$v) {
            continue;
        }
        $k = str_replace('meta_', '', $k); // Auto translate old meta_* to *
        if (!isset($validfields[$k])) {
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
    // Alow to store the selected shown fields as default for all matching folders
    $t_d->fill_block('has_set_as_default', 'msg_set_as_default', $WP_msg['SaveAsDefault']);

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
        $choices['orderby'] = 'hdate_sent';
        $choices['orderdir'] = 'DESC';
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
    // Define groupby
    $t_gb = $t_d->get_block('has_groupby');
    $t_gl = $t_gb->get_block('groupline');
    // Allow to deselect the grouping
    $t_gl->assign(array('val' => '', 'name' => $WP_msg['none']));
    $t_gb->assign('groupline', $t_gl);
    $t_gl->clear();
    foreach ($validfields as $name => $text) {
        $t_gl->assign(array('val' => $name, 'name' => $text));
        if (isset($choices['groupby']) && $choices['groupby'] == $name) {
            $t_gl->assign_block('sel');
        }
        $t_gb->assign('groupline', $t_gl);
        $t_gl->clear();
    }
    $t_gb->assign(array('msg_groupby' => $WP_msg['GroupBy']));
    $t_d->assign('has_groupby', $t_gb);
    // End groupby

    $tpl->assign('display', $t_d);

    // Flag for not in sync
    $t_hss = $tpl->get_block('has_show_in_sync');
    $t_hss->assign('msg_show_in_sync', $WP_msg['ShowInSync']);
    if (!isset($choices['not_in_sync']) || !$choices['not_in_sync']) {
        $t_hss->assign_block('show_in_sync');
    }
    $tpl->assign('has_show_in_sync', $t_hss);

    // Flag for show in pinboard
    $t_hss = $tpl->get_block('has_show_in_pinboard');
    $t_hss->assign('msg_show_in_pinboard', $WP_msg['ShowInPinboard']);
    if (!isset($choices['not_in_pinboard']) || !$choices['not_in_pinboard']) {
        $t_hss->assign_block('show_in_pinboard');
    }
    $tpl->assign('has_show_in_pinboard', $t_hss);

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

    // There's basic settings, one can save
    $tpl->assign_block('has_store_basic_settings');

    // Allow exporting as MBOX
    if (($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_export_emails']) && $data['mailnum'] > 0) {
        $t_ex = $tpl->get_block('has_export');
        $t_ex->assign('msg_export', $WP_msg['Export']);
        $tpl->assign('has_export', $t_ex);
        $tpl->assign('exportwinurl', PHP_SELF.'?'.give_passthrough(1).'&l=worker&what=folder_export&h=email&fid='.$fid);
    }
} else {
    // Quotas
    $t_qu = $tpl->get_block('quotas');
    $t_ql = $t_qu->get_block('quotaline');
    $num_quotas = 0;
    foreach (array
            ('size_storage' => array
                    ('type' => 'filesize'
                    ,'method' => 'quota_getmailsize'
                    ,'name' => $WP_msg['QuotaStorageSize']
                    )
            ,'number_mails' => array
                    ('type' => 'int'
                    ,'method' => 'quota_getmailnum'
                    ,'name' => $WP_msg['QuotaNumMails']
                    )
            ,'number_folders' => array
                    ('type' => 'int'
                    ,'method' => 'quota_getfoldernum'
                    ,'name' => $WP_msg['QuotaNumFolders']
                    )
            ) as $k => $v) {
        $v['limit'] = $DB->quota_get($_SESSION['phM_uid'], 'email', $k);
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
        $t_ql->assign(array('crit_id' => $k, 'msg_crit' => $v['name'], 'msg_use' => $v['use'], 'msg_limit' => $v['limit'], 'use' => $use, 'limit' => $limit));
        $t_qu->assign('quotaline', $t_ql);
        $t_ql->clear();
    }
    if ($num_quotas) {
        $tpl->assign('quotas', $t_qu);
        $tpl->assign('leg_quotas', $WP_msg['QuotaLegend']);
    }
    // Ende Qutoas
    if ($data['icon'] == ':imapbox') {
        $t_hs = $tpl->get_block('has_subscribe');
        $t_hs->assign('msg_subscribe', $WP_msg['Subscribe'].'...');
        $tpl->assign('has_subscribe', $t_hs);
        $tpl->assign('subscribewinurl', PHP_SELF.'?'.give_passthrough(1).'&l=setup&mod=folders&subscribe=init&h=email&fid='.$fid);
    }
    if ($data['icon'] == ':imapbox' || $data['icon'] == ':mailbox') {
        $t_hs = $tpl->get_block('has_hidefolders');
        $t_hs->assign('msg_hidefolders', $WP_msg['HideFolders'].'...');
        $tpl->assign('has_hidefolders', $t_hs);
        $tpl->assign('hidefolderswinurl', PHP_SELF.'?'.give_passthrough(1).'&l=setup&mod=folders&hidefolders=init&h=email&fid='.$fid);
    }
}
foreach ($_SESSION['WPs_Plugin'] as $handler) {
    require_once($handler['path']);
    $t = new $handler['class']($_PM_);
    $t->pluginhandler('show_folder_props');
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
        ,'form_target' => htmlspecialchars(PHP_SELF.'?'.give_passthrough(1).'&l=folderprops&h=email&save=1&fid='.$fid)
        ));
