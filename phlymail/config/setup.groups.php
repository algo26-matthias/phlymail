<?php
/**
 * Manage system groups (add, edit, dele, permissions)
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Config interface
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.9 2013-01-22 
 * @todo - Group quotas
 * - Nice description on top of the page to explain everyhting
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['groups_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$do   = (isset($_REQUEST['do'])) ? $_REQUEST['do'] : false;
$done = (isset($_REQUEST['done'])) ? $_REQUEST['done'] : false;
$error = false;
$base_link = PHP_SELF.'?action=groups&'.give_passthrough(1);

if ('kill' == $do) {
    if (isset($_REQUEST['id']) && $_REQUEST['id']) $DB->dele_group($_REQUEST['id']);
    $do = false;
}
if (('edit' == $do || 'add' == $do) && isset($_REQUEST['name']) && $_REQUEST['name']) {
    if (strlen($_REQUEST['name']) < 1 || strlen($_REQUEST['name']) > 32) $error = $WP_msg['ELenGrpName'].'<br />'.LF;
    if ('edit' == $do && !$error) {
        $exists = $DB->checkfor_groupname($_REQUEST['name']);
        if ($exists && $exists != $_REQUEST['id']) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $DB->update_group($_REQUEST['id'], $_REQUEST['name']);
    }
    if ('add' == $do && !$error) {
        $exists = $DB->checkfor_groupname($_REQUEST['name']);
        if ($exists) $error .= $WP_msg['EGrpNameExists'].'<br />'.LF;
        if (!$error) $DB->add_group($_REQUEST['name'], $_REQUEST['childof'], '');
    }
    $do = false;
}
if ('get_gperm' == $do) {
    $ginfo = $DB->get_groupinfo($_REQUEST['gid']);
    $gperm = array();
    foreach ($DB->get_group_permissions($_REQUEST['gid']) as $k => $v) {
        $gperm[] = '"'.$k.'":'.intval($v);
    }
    header('Content-Type:application/json; charset=utf-8');
    echo '{"got_gperm":{'.implode(',', $gperm).'}, "gid":'.intval($_REQUEST['gid']).',"gname":"'.phm_addcslashes($ginfo['friendly_name']).'"}';
    exit;
}
if ('set_gperm' == $do) {
    $perms = array();
    foreach ($_REQUEST['p'] as $k => $v) {
        list ($hdl, $act) = explode('_', $k, 2);
        $perms[] = array('handler' => $hdl, 'action' => $act, 'perm' => $v);
    }
    $DB->set_group_permissions($_REQUEST['gid'], $perms);
    header('Content-Type:application/json; charset=utf-8');
    echo '{"set_gperm":1}';
    exit;
}
if ('get_gulist' == $do) {
    $ulist = array();
    foreach ($DB->get_groupuserlist($_REQUEST['gid'], true) as $uid => $uname) {
        $ulist[] = $uid.':"'.phm_addcslashes($uname).'"';
    }
    header('Content-Type:application/json; charset=utf-8');
    echo '{"got_gulist":{'.implode(',', $ulist).'}, "gid":'.intval($_REQUEST['gid']).'}';
    exit;
}
if ('get_uperm' == $do) {
    $uinfo = $DB->get_usrdata($_REQUEST['uid']);
    $uperm = array();
    foreach ($DB->get_user_permissions($_REQUEST['uid']) as $k => $v) {
        $uperm[] = '"'.$k.'":'.intval($v);
    }
    header('Content-Type:application/json; charset=utf-8');
    echo '{"got_uperm":{'.implode(',', $uperm).'}, "uid":'.intval($_REQUEST['uid']).',"uname":"'.phm_addcslashes($uinfo['username']).'"}';
    exit;
}
if ('set_uperm' == $do) {
    $perms = array();
    foreach ($_REQUEST['p'] as $k => $v) {
        list ($hdl, $act) = explode('_', $k, 2);
        $perms[] = array('handler' => $hdl, 'action' => $act, 'perm' => $v);
    }
    $DB->set_user_permissions($_REQUEST['uid'], $perms);
    header('Content-Type:application/json; charset=utf-8');
    echo '{"set_uperm":1}';
    exit;
}

if (!$do) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.groups.tpl');
    if ($error) {
        $t_e = $tpl->get_block('errors');
        $t_e->assign('error', $error);
        $tpl->assign('errors', $t_e);
    }
    $groups = $DB->get_grouplist(false);
    if (!empty($groups)) {
        $tpl_l = $tpl->get_block('groupline');
        // The structure allows hierarchic groups, so a helper is needed
        cfg_out_groups($groups['childs'], 0, 0);
    } else {
        $tpl_n = $tpl->get_block('none');
        $tpl_n->assign('nogroups', $WP_msg['none']);
        $tpl->assign('none', $tpl_n);
    }
    $t_ph = $tpl->get_block('priv_handler');
    $t_pp = $t_ph->get_block('priv_priv');
    // Read all handlers' available privileges
    foreach ($_PM_['handlers'] as $handler => $active) {
        // Look for an installation API call available
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) continue;
        require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
        $call = 'handler_'.$handler.'_configapi';
        if (!in_array('get_perm_actions', get_class_methods($call))) continue;
        $API = new $call($_PM_, 0);
        $perms = $API->get_perm_actions($WP_conf['language']);
        if (empty($perms)) {
            unset($API);
            continue;
        }
        $t_ph->assign(array('handlername' => ucfirst($handler), 'handler' => $handler));
        foreach ($perms as $k => $v) {
            $t_pp->assign(array('handler' => $handler, 'priv' => $k, 'privname' => $v));
            $t_ph->assign('priv_priv', $t_pp);
            $t_pp->clear();
        }
        $tpl->assign('priv_handler', $t_ph);
        $t_ph->clear();
        unset($API);
    }
    $tpl->assign(array
            ('addlink' => $base_link.'&do=add'
            ,'delelink' => $base_link.'&do=kill'
            ,'editlink' => $base_link.'&do=edit'
            ,'grouppriv_geturl' => $base_link.'&do=get_gperm'
            ,'grouppriv_seturl' => $base_link.'&do=set_gperm'
            ,'userlist_geturl' => $base_link.'&do=get_gulist'
            ,'userpriv_geturl' => $base_link.'&do=get_uperm'
            ,'userpriv_seturl' => $base_link.'&do=set_uperm'
            ,'frontend_path' => $_PM_['path']['frontend']
            ,'about_groups' => $WP_msg['AboutSysGrps']
            ,'msg_conf_dele' => $WP_msg['DelGrp']
            ,'msg_newgroupname' => $WP_msg['NewGrpName']
            ,'msg_newnamegroup' => $WP_msg['NewNameGrp']
            ,'msg_name_error' => $WP_msg['ELenGrpName']
            ,'msg_gname' => $WP_msg['group']
            ,'msg_save' => $WP_msg['save']
            ,'msg_cancel' => $WP_msg['cancel']
            ,'msg_add' => $WP_msg['addGrp']
            ,'msg_all' => $WP_msg['all']
            ,'msg_none' => $WP_msg['none']
            ,'msg_simple' => $WP_msg['simple']
            ,'head_privs_user' => $WP_msg['PrivilegesOfUser']
            ,'head_privs_group' => $WP_msg['PrivilegesOfGroup']
            ,'poptitle_privileges' => $WP_msg['PrivilegesOfTheGroup']
            ));
}

function cfg_out_groups(&$groups, $child = 0, $level = 0)
{
    foreach ($groups[$child] as $k => $v) {
        $GLOBALS['tpl_l']->assign(array
                ('group' => phm_entities($v['friendly_name'])
                ,'num' => ($v['num_users'] == 0) ? '' : '('.$v['num_users'].')'
                ,'levelspacer' => 8*$level
                ,'id' => $v['gid']
                ,'msg_edit' => $GLOBALS['WP_msg']['edit']
                ,'msg_dele' => $GLOBALS['WP_msg']['del']
                ,'msg_add' => $GLOBALS['WP_msg']['addGrp']
                ,'msg_privileges' => $GLOBALS['WP_msg']['Privileges']
                ,'msg_showusers' => $GLOBALS['WP_msg']['ShowUsers']
                ));
        $GLOBALS['tpl']->assign('groupline', $GLOBALS['tpl_l']);
        $GLOBALS['tpl_l']->clear();
        if (isset($groups[$v['gid']])) cfg_out_groups($groups, $v['gid'], $level+1);
    }
}
