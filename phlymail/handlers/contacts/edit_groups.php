<?php
/**
 * edit_groups.php -> Manage groups (add, edit, delete)
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Calendar handler
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2012-05-14 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_add_group']
        && !$_SESSION['phM_privs']['contacts_edit_group'] && !$_SESSION['phM_privs']['contacts_delete_group']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$do   = (isset($_REQUEST['do'])) ? $_REQUEST['do'] : false;
$done = (isset($_REQUEST['done'])) ? $_REQUEST['done'] : false;
$error = false;
$base_link = PHP_SELF.'?h=contacts&l=edit_groups&'.give_passthrough(1);
$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
// Check quotas
$quota_num_groups = $DB->quota_get($_SESSION['phM_uid'], 'contacts', 'number_groups');
if (false !== $quota_num_groups) {
    $quota_groupsleft = $cDB->quota_groupsnum(false);
    $quota_groupsleft = $quota_num_groups - $quota_groupsleft;
} else {
    $quota_groupsleft = false;
}
// End quota check
if ('kill' == $do) {
    if (!$_SESSION['phM_privs']['all'] || !$_SESSION['phM_privs']['contacts_delete_group']) {
        $error .= $WP_msg['PrivNoAccess'].LF;
    } elseif (isset($_REQUEST['id']) && $_REQUEST['id']) {
        $cDB->dele_group($_REQUEST['id']);
    }
    $do = false;
}
if (('edit' == $do || 'add' == $do) && isset($_REQUEST['name']) && $_REQUEST['name']) {
    if ('edit' == $do && ($_SESSION['phM_privs']['all'] || !$_SESSION['phM_privs']['contacts_edit_group'])) {
        $error .= $WP_msg['PrivNoAccess'].LF;
    } elseif ('add' == $do && ($_SESSION['phM_privs']['all'] || !$_SESSION['phM_privs']['contacts_add_group'])) {
        $error .= $WP_msg['PrivNoAccess'].LF;
    } elseif (strlen($_REQUEST['name']) < 1 || strlen($_REQUEST['name']) > 32) {
        $error = $WP_msg['ELenGrpName'].LF;
    }
    if ('edit' == $do && !$error) {
        $exists = $cDB->checkfor_groupname($_REQUEST['name']);
        if ($exists && $exists != $_REQUEST['id']) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $cDB->update_group($_REQUEST['id'], $_REQUEST['name']);
    }
    if ('add' == $do && !$error) {
        // Check quotas
        $quota_num_contacts = $DB->quota_get($_SESSION['phM_uid'], 'contacts', 'num_groups');
        if (false !== $quota_num_groups) {
            $quota_groupsleft = $cDB->quota_groupsnum(false);
            $quota_groupsleft = $quota_num_groups - $quota_groupsleft;
        } else {
            $quota_groupsleft = false;
        }
        // No more groups allowed to save (Quotas)
        if (false !== $quota_groupsleft && $quota_groupsleft < 1) {
            $error .= $WP_msg['QuotaExceeded'].'<br />'.LF;
        } else {
            $exists = $cDB->checkfor_groupname($_REQUEST['name']);
            if ($exists) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        }
        if (!$error) $cDB->add_group($_REQUEST['name']);
    }
    $do = false;
}
if (!$do) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.editgroups.tpl');
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
            ,'handler' => 'contacts'
            ));
    if ($error) $tpl->fill_block('errors', 'error', $error);
    $i = 0;
    $tpl_l = $tpl->get_block('groupline');
    foreach ($cDB->get_grouplist(0) as $v) {
        $tpl_l->assign(array('group' => $v['name'], 'num' => '('.$v['adrcount'].')'
                ,'id' => $v['gid'], 'msg_edit' => $WP_msg['edit'], 'msg_dele' => $WP_msg['dele']
                ));
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_edit_group']) $tpl_l->assign_block('noedit');
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_delete_group']) $tpl_l->assign_block('nodele');
        $tpl->assign('groupline', $tpl_l);
        $tpl_l->clear();
        $i++;
    }
    if (!$i) $tpl->fill_block('none', 'nogroups', $WP_msg['none']);
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_add_group']) {
        $tpl->assign_block('nomoreadd'); // Privileges don't allow adding a group
    } elseif (false !== $quota_groupsleft && $quota_groupsleft < 1) {
        $tpl->assign_block('nomoreadd'); // No more groups allowed to save (Quotas)
    }
}
