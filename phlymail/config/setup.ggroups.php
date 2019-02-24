<?php
/**
 * Manage groups (add, edit, delete
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2002-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.6 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['gcontacts_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

if (file_exists($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/contacts/lang.de.php');
}
$do   = (isset($_REQUEST['do'])) ? $_REQUEST['do'] : false;
$done = (isset($_REQUEST['done'])) ? $_REQUEST['done'] : false;
$error = false;
$base_link = PHP_SELF.'?action=ggroups&'.give_passthrough(1);
$cDB = new handler_contacts_driver(0);

if ('kill' == $do) {
    if (isset($_REQUEST['id']) && $_REQUEST['id']) $cDB->dele_group($_REQUEST['id']);
    $do = false;
}
if (('edit' == $do || 'add' == $do) && isset($_REQUEST['name']) && $_REQUEST['name']) {
    if (strlen($_REQUEST['name']) < 1 || strlen($_REQUEST['name']) > 32) $error = $WP_msg['ELenGrpName'].'<br />'.LF;
    if ('edit' == $do && !$error) {
        $exists = $cDB->checkfor_groupname($_REQUEST['name']);
        if ($exists && $exists != $_REQUEST['id']) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $cDB->update_group($_REQUEST['id'], $_REQUEST['name']);
    }
    if ('add' == $do && !$error) {
        $exists = $cDB->checkfor_groupname($_REQUEST['name']);
        if ($exists) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $cDB->add_group($_REQUEST['name']);
    }
    $do = false;
}
if (!$do) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/ggroups.tpl');
    $tpl->assign(array
            ('addlink' => $base_link.'&do=add'
            ,'delelink' => $base_link.'&do=kill'
            ,'editlink' => $base_link.'&do=edit'
            ,'msg_add' => $WP_msg['addGrp']
            ,'about_groups' => $WP_msg['AboutGrps']
            ,'msg_conf_dele' => $WP_msg['DelGrp']
            ,'msg_newgroupname' => $WP_msg['NewGrpName']
            ,'msg_newnamegroup' => $WP_msg['NewNameGrp']
            ,'msg_name_error' => $WP_msg['ELenGrpName']
            ));
    if ($error) $tpl->fill_block('errors', 'error', $error);
    $i = 0;
    $tpl_l = $tpl->get_block('groupline');
    foreach ($cDB->get_grouplist(0) as $v) {
        $tpl_l->assign(array
                ('group' => phm_entities($v['name'])
                ,'num' => '('.$v['adrcount'].')'
                ,'id' => $v['gid']
                ,'msg_edit' => $WP_msg['edit']
                ,'msg_dele' => $WP_msg['dele']
                ));
        $tpl->assign('groupline', $tpl_l);
        $tpl_l->clear();
        $i++;
    }
    if (!$i) $tpl->fill_block('none', 'nogroups', $WP_msg['none']);
}
