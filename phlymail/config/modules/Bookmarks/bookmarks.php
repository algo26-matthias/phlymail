<?php
/**
 * Configuration frontend for global bookmarks
 * @package phlyMail
 * @subpackage Addon module Bookmarks
 * @copyright 2001-2015 phlyLabs Berlin, http://phlylabs.de/
 * @version 0.0.8 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$do   = (isset($_REQUEST['do'])) ? $_REQUEST['do'] : false;
$done = (isset($_REQUEST['done'])) ? $_REQUEST['done'] : false;
$error = false;
$base_link = PHP_SELF.'?action=view&screen=bookmarks&module=Bookmarks&'.give_passthrough(1);

if ('kill' == $do) {
    if (isset($_REQUEST['id']) && $_REQUEST['id']) $bDB->dele_folder($_REQUEST['id']);
    $do = false;
}
if ('killitem' == $do) {
    if (isset($_REQUEST['id']) && $_REQUEST['id']) $bDB->delete_item($_REQUEST['id']);
    $do = false;
}
if (('edit' == $do || 'add' == $do) && isset($_REQUEST['name']) && $_REQUEST['name']) {
    if (strlen($_REQUEST['name']) < 1 || strlen($_REQUEST['name']) > 64) {
        $error = $modmsg['ELenGrpName'].'<br />'.LF;
    }
    if ('edit' == $do && !$error) {
        $exists = $bDB->checkfor_foldername($_REQUEST['name']);
        if ($exists && $exists != $_REQUEST['id']) $error .= $modmsg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $bDB->update_folder($_REQUEST['id'], $_REQUEST['name']);
    }
    if ('add' == $do && !$error) {
        $exists = $bDB->checkfor_foldername($_REQUEST['name']);
        if ($exists) $error .= $modmsg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $bDB->add_folder($_REQUEST['name'], '', $_REQUEST['childof']);
    }
    $do = false;
}
if ('getitemlist' == $do) {
    $ulist = array();
    foreach ($bDB->get_index(true, $_REQUEST['gid']) as $v) {
        $ulist[] = $v;
    }
    header('Content-Type:application/json; charset=utf-8');
    echo json_encode(array('gid' => intval($_REQUEST['gid']), 'items' => $ulist));
    exit;
}

if ('edititem' == $do) {
    $id = isset($_REQUEST['id']) && $_REQUEST['id'] ? $_REQUEST['id'] : false;

    if (isset($_REQUEST['save'])) {
        $payload = array
                ('name' => $_REQUEST['name']
                ,'url' => $_REQUEST['url']
                ,'description' => $_REQUEST['desc']
                ,'favourite' => isset($_REQUEST['is_favourite']) && $_REQUEST['is_favourite'] ? 1 : 0
                ,'fid' => $_REQUEST['group']
                );
        if ($id) {
            $payload['id'] = intval($id);
            $res = $bDB->update_item($payload);
        } else {
            $res = $bDB->add_item($payload);
        }
    }

    $tpl = new phlyTemplate(__DIR__.DIRECTORY_SEPARATOR.'bookmarks.edit.tpl');
    $outer_template = 'main.pure.tpl';
    if (isset($res) && $res) $tpl->assign_block('success');

    if ($id) {
        $bm = $bDB->get_item($id, 0);
        $tpl->assign(array
                ('url' => phm_entities($bm['url'])
                ,'name' => phm_entities($bm['name'])
                ,'desc' => phm_entities($bm['description'])
                ,'save_url' => $base_link.'&amp;do=edititem&amp;save=1&amp;id='.$bm['id']
                ));
        if ($bm['favourite']) $tpl->assign_block('is_favourite');
    } else {
        $bm = array();
        $tpl->assign('save_url', $base_link.'&amp;do=edititem&amp;save=1');
        if (isset($_REQUEST['childof'])) $bm['fid'] = intval($_REQUEST['childof']);
    }
    $tpl->assign(array
            ('msg_url' => $modmsg['BMURL']
            ,'msg_desc' => $modmsg['BMDescription']
            ,'msg_name' => $modmsg['BMName']
            ,'msg_group' => $modmsg['HGrp']
            ,'msg_save' => $WP_msg['save']
            ,'msg_is_favourite' => $modmsg['BMFavouriteBookmark']
            ));
    $t_gs = $tpl->get_block('group_sel');
    foreach ($bDB->get_folderlist(0) as $id => $grp) {
        $lvl_space = ($grp['level'] > 0) ? str_repeat('&nbsp;', $grp['level'] * 2) : '';
        $t_gs->assign(array('id' => $id, 'name' => $lvl_space.phm_entities($grp['name'])));
        if (isset($bm['fid']) && $bm['fid'] == $id) $t_gs->assign_block('sel');
        $tpl->assign('group_sel', $t_gs);
        $t_gs->clear();
    }
    return;
}

$tpl = new phlyTemplate(__DIR__.DIRECTORY_SEPARATOR.'bookmarks.general.tpl');
$groups = $bDB->get_folderlist(1);
if (!empty($groups)) {
    $tpl_l = $tpl->get_block('groupline');
    foreach ($groups as $k => $v) {
        $tpl_l->assign(array
                ('id' => $k
                ,'group' => phm_entities($v['name'])
                ,'levelspacer' => 8*$v['level']
                ,'msg_edit' => $WP_msg['edit']
                ,'msg_dele' => $WP_msg['del']
                ,'msg_add' => $modmsg['addGrp']
                ,'msg_additem' => $modmsg['addItm']
                ,'msg_showusers' => $modmsg['ShowItems']
                ));
        $tpl->assign('groupline', $tpl_l);
        $tpl_l->clear();
    }
} else {
    $tpl->fill_block('none', 'nogroups', $WP_msg['none']);
}
$tpl->assign(array
        ('addgrouplink' => $base_link.'&do=add'
        ,'delegrouplink' => $base_link.'&do=kill'
        ,'editgrouplink' => $base_link.'&do=edit'
        ,'additemlink' => $base_link.'&do=additem'
        ,'deleitemlink' => $base_link.'&do=killitem'
        ,'edititemlink' => $base_link.'&do=edititem'
        ,'itemlist_geturl' => $base_link.'&do=getitemlist'
        ,'about_groups' => $modmsg['AboutSysGrps']
        ,'msg_conf_dele' => $modmsg['DelGrp']
        ,'msg_conf_deleitem' => $modmsg['DelItm']
        ,'msg_newgroupname' => $modmsg['NewGrpName']
        ,'msg_newnamegroup' => $modmsg['NewNameGrp']
        ,'msg_name_error' => $modmsg['ELenGrpName']
        ,'msg_gname' => $modmsg['group']
        ,'msg_save' => $WP_msg['save']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'msg_add' => $modmsg['addGrp']
        ));
