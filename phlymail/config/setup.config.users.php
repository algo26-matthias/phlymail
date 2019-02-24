<?php
/**
 * Usermanagement Config Area
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.6 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Leseberechtigung
if (!isset($_SESSION['phM_perm_read']['config.users_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

// Schreibberechtigung
if (!isset($_SESSION['phM_perm_write']['config.users_']) && isset($whattodo) && !$_SESSION['phM_superroot']) {
    if (in_array($whattodo, array('savenew', 'saveold', 'resetfail', 'inactive', 'active', 'delete', 'add'))) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;
$uid = (isset($_REQUEST['uid']) && $_REQUEST['uid']) ? $_REQUEST['uid'] : false;

if ('savenew' == $whattodo || 'saveold' == $whattodo) {
    $error = '';
    $PHM = ($_REQUEST['PHM']) ? $_REQUEST['PHM'] : array();
    $read = (isset($_REQUEST['read'])) ? $_REQUEST['read'] : array();
    $write = (isset($_REQUEST['write'])) ? $_REQUEST['write'] : array();

    if ('savenew' == $whattodo) {
        if ($DB->checkfor_admname($PHM['username'])) $error .= $WP_msg['SuUserExists'];
        if ('' == $PHM['password']) $error.= $WP_msg['SuDefinePW'];
        if ('' == $PHM['username']) $error.= $WP_msg['SuDefineUN'];
        // Prevent normal admins from setting permissions of any admin, thus preventing,
        // that an admin could create a new superadmin with known password, hereby
        // breaking into the system
        if (!$_SESSION['phM_superroot']) {
            $permissions = '';
            $superadmin = 'no';
        } else {
            $superadmin = (isset($_REQUEST['WPnewsuperadmin'])) ? 'yes' : 'no';
        }
    } else {
        $PHM2 = $DB->get_admdata($uid);
        // Prevent normal admins from hacking SA accounts
        if ($PHM2['is_root'] == 'yes' && !$_SESSION['phM_superroot']) {
            $WP_return = base64_encode($WP_msg['CUMOnlySAMay']);
            header('Location: '.$link_base.'config.users&WP_return='.$WP_return);
            exit;
        }
        // Prevent normal admins from setting permissions of any admin (including themselves)
        if (!$_SESSION['phM_superroot']) {
            $permissions = $PHM2['permissions'];
            $superadmin = $PHM2['is_root'];
        } else {
            $superadmin = (isset($_REQUEST['WPnewsuperadmin'])) ? 'yes' : 'no';
            $permissions = base64_encode(serialize(array($read, $write)));
        }
    }
    if ($PHM['password'] != $PHM['password2']) $error.= $WP_msg['SuPW1notPW2'];
    if (!$error) {
        if ('savenew' == $whattodo) {
            $uid = $DB->add_admin(array
                ('username' => $PHM['username'], 'active' => $PHM['active']
                ,'password' => (isset($PHM['password'])) ? $PHM['password'] : ''
                ,'email' => $PHM['email']
                ,'salt' => $_PM_['auth']['system_salt']
                ,'permissions' => base64_encode(serialize(array($read, $write)))
                ,'is_root' => $superadmin
                ));
            $WP_return = ($uid) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
        } else {
            $truth = $DB->upd_admin(array
                ('uid' => $uid
                ,'username' => $PHM2['username'], 'active' => $PHM['active']
                ,'password' => (isset($PHM['password'])) ? $PHM['password'] : ''
                ,'email' => $PHM['email']
                ,'salt' => $_PM_['auth']['system_salt']
                ,'permissions' => $permissions
                ,'is_root' => $superadmin
                ));
            $WP_return = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
        }
        header('Location: '.$link_base.'config.users&WP_return='.base64_encode($WP_return));
        exit();
    } else {
        $whattodo = ($whattodo == 'savenew') ? 'add' : 'edit';
    }
}

if ('resetfail' == $whattodo) {
    $DB->reset_admfail($uid);
    $whattodo = 'edit';
}
if ('active' == $whattodo) {
    $PHM = $DB->get_admdata($uid);
    // Only SuperAdmins may change settings of SuperAdmins
    if ($PHM['is_root'] == 'yes' && !$_SESSION['phM_superroot']) {
        $WP_return = base64_encode($WP_msg['CUMOnlySAMay']);
    } else {
        $DB->onoff_admin($PHM['username'], 1);
    }
    unset($uid);
    $whattodo = false;
}
if ('inactive' == $whattodo) {
    $PHM = $DB->get_admdata($uid);
    // Only SuperAdmins may change settings of SuperAdmins
    if ($PHM['is_root'] == 'yes' && !$_SESSION['phM_superroot']) {
        $WP_return = base64_encode($WP_msg['CUMOnlySAMay']);
    } else {
        $DB->onoff_admin($PHM['username'], 0);
    }
    unset($uid);
    $whattodo = false;
}
if ('delete' == $whattodo) {
    $PHM = $DB->get_admdata($uid);
    // Only SuperAdmins may delete SuperAdmins
    if ($PHM['is_root'] == 'yes' && !$_SESSION['phM_superroot']) {
        $WP_return = base64_encode($WP_msg['CUMOnlySAMay']);
    } else {
        if (isset($_REQUEST['really']) && 'yeahyeah' == $_REQUEST['really']) {
            $DB->delete_admin($PHM['username']);
            $uid = false;
            $whattodo = false;
        } else {
            $tpl = new phlyTemplate(CONFIGPATH.'/templates/um.deleuser.tpl');
            $tpl->assign(array(
                    'msg_real' => $WP_msg['SuDelUserReal'],
                    'link_yes' => htmlspecialchars($link_base.'config.users&whattodo='.$whattodo.'&really=yeahyeah&uid='.$uid),
                    'link_no' => htmlspecialchars($link_base.'config.users'),
                    'msg_yes' => $WP_msg['yes'],
                    'msg_no' => $WP_msg['no']
                    ));
            return;
        }
    }
}

if ('edit' == $whattodo || 'add' == $whattodo) {
    // Edit Admin
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.config.users.tpl');
    $tpl->assign(array('link_base' => htmlspecialchars($link_base)));

    $PHM = isset($_REQUEST['PHM']) ? $_REQUEST['PHM'] : array();

    if ('add' == $whattodo) {
        $nwhatto = 'savenew';
        if (empty($PHM)) $PHM = array('username' => '', 'active' => 1, 'password' => '', 'password2' => '', 'email' => '', 'is_root' => 'no');
    }
    if ('edit' == $whattodo) {
        $nwhatto = 'saveold';
        if ($uid && !isset($PHM['username'])) {
            $PHM = $DB->get_admdata($uid);
            // Only SuperAdmins may edit SuperAdmins
            if ($PHM['is_root'] == 'yes' && !$_SESSION['phM_superroot']) {
                $WP_return = base64_encode($WP_msg['CUMOnlySAMay']);
                header('Location: '.$link_base.'config.users&WP_return='.$WP_return);
                exit;
            }
            unset($PHM['password']);
            list ($read, $write) = unserialize(base64_decode($PHM['permissions']));
            $read  = (isset($read) && is_array($read))   ? array_flip($read)  : array();
            $write = (isset($write) && is_array($write)) ? array_flip($write) : array();
        }
    }
    $tpl_e = $tpl->get_block('editarea');
    $tpl_e->assign(array(
            'edit_target' => htmlspecialchars($link_base).'config.users&amp;whattodo='.$nwhatto,
            'msg_save' => $WP_msg['save'],
            'msg_cancel' => $WP_msg['cancel'],
            'link_base' => htmlspecialchars($link_base).'config.users',
            'msg_active' => $WP_msg['optactive'],
            'msg_yes' => $WP_msg['yes'],
            'msg_no' => $WP_msg['no'],
            'password' => isset($PHM['password']) ? phm_entities($PHM['password']) : '',
            'password2' => isset($PHM['password2']) ? phm_entities($PHM['password2']) : '',
            'msg_syspass2' => $WP_msg['syspass2'],
            'msg_email' => $WP_msg['sysextemail'],
            'email' => phm_entities($PHM['email']),
            'leg_basic' => $WP_msg['UMLegBasic']
            ));
    if (isset($error) && $error) $tpl_e->fill_block('error', 'error', $error);
    $tpl_e->assign('msg_sysuser', $WP_msg['sysuser']);
    if ('add' == $whattodo) {
        $tpl_e->assign('msg_syspass', $WP_msg['sysnewpass']);
        $tpl_e->assign('head_text', $WP_msg['SuEnterBD']);
        $tpl_a = $tpl_e->get_block('adduser');
        $tpl_a->assign('name', $PHM['username']);
        $tpl_e->assign('adduser', $tpl_a);
    } else {
        $tpl_e->assign('msg_syspass', $WP_msg['syspass']);
        $tpl_e->assign('head_text', $WP_msg['SuEditBD']);
        $tpl_a = $tpl_e->get_block('edituser');
        $tpl_a->assign(array('name' => $PHM['username'], 'uid' => $uid));
        $tpl_e->assign('edituser', $tpl_a);

        $t_lf = $tpl_e->get_block('loginfail');
        $failure = $DB->get_admfail($uid);
        $failedlogin = ($failure['fail_count']+0).' / '.$_PM_['auth']['countonfail'];
        if ($failure['fail_count'] > 0) {
            $t_lf->fill_block('resetfail', array(
                    'msg_resetfail' => $WP_msg['SuReset'],
                    'link_resetfail' => htmlspecialchars($link_base.'config.users&whattodo=resetfail&uid='.$uid)
                    ));
            $failedlogin .= ' ('.date($WP_msg['dateformat'], $failure['fail_time']).')';
        }
        $t_lf->assign(array
                ('loginfail' => $failedlogin
                ,'leg_stat' => $WP_msg['CUMLegStat']
                ,'lastlogin' => date($WP_msg['dateformat'], $PHM['login_time'])
                ,'lastlogout' => date($WP_msg['dateformat'], $PHM['logout_time'])
                ,'msg_lastlogin' => $WP_msg['SuLastLogin']
                ,'msg_lastlogout' => $WP_msg['SuLastLogout']
                ,'msg_loginfail' => $WP_msg['SuLoginFail']
                ));
        $tpl_e->assign('loginfail', $t_lf);
    }
    $tpl_e->assign_block($PHM['active'] == 0 ? 'selno' : 'selyes');
    if ($_SESSION['phM_superroot']) {
        // Only SuperAdmins may set permissions of other admins
        $tpl_sa = $tpl_e->get_block('forSA');
        if (isset($PHM['is_root']) && $PHM['is_root'] == 'yes') $tpl_sa->assign_block('selsupadm');
        $perm_t = $tpl_sa->get_block('permline');
        foreach ($global_menu_itms as $k) {
            if ($k['action'] == '') continue;
            if ($k['type'] == 'm') {
                $perm_t->assign_block('heading');
                $perm_t->assign('menu', $k['name']);
            } else {
                $perm_tl = $perm_t->get_block('perm');
                $action = $k['action'];
                $screen = (isset($k['screen'])) ? $k['screen'] : '';
                $perm_tl->assign(array
                    ('menu' => ($k['type'] == 's') ? '&nbsp;&nbsp;'.$k['name'] : $k['name']
                    ,'action' => $action, 'screen' => $screen
                    ));
                $curr_line = $action.'_'.$screen;
                if (isset($read[$curr_line])) $perm_tl->assign_block('optread');
                if (isset($write[$curr_line])) $perm_tl->assign_block('optwrite');
                $perm_t->assign('perm', $perm_tl);
            }
            $tpl_sa->assign('permline', $perm_t);
            $perm_t->clear();
        }
        $tpl_sa->assign(array
                ('leg_permissions' => $WP_msg['CUMLegPerm']
                ,'msg_perm' => $WP_msg['CUMPerm']
                ,'msg_none' => $WP_msg['none'], 'msg_all' => $WP_msg['all']
                ,'msg_superadmin' => $WP_msg['SuperAdmin']
                ,'about_superadmin' => $WP_msg['CUMAboutSA']
                ,'msg_modperms' => $WP_msg['CUMModPerm'], 'msg_module' => $WP_msg['CUMModule']
                ,'msg_read' => $WP_msg['CUMRead'], 'msg_write' => $WP_msg['CUMWrite']
                ));
        $tpl_e->assign('forSA', $tpl_sa);
    }
    $tpl->assign('editarea', $tpl_e);

} else {
    $criteria = (isset($_REQUEST['criteria']) && $_REQUEST['criteria']) ? $_REQUEST['criteria'] : 'all';
    $search = (isset($_REQUEST['search']) && $_REQUEST['search']) ? $_REQUEST['search'] : '';
    // Overview
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.config.users.tpl');
    $tpl->assign(array('link_base' => htmlspecialchars($link_base)));
    $tpl_ow = $tpl->get_block('overview');
    if ($WP_return) $tpl_ow->fill_block('return', 'WP_return', base64_decode($WP_return));
    $overview = $DB->get_admoverview($_PM_['auth']['countonfail']);
    foreach (array('all', 'inactive', 'locked', 'active') as $v) {
        if ($overview[$v] > 0) {
            $tpl_ow->assign_block('search_'.$v);
            $tpl_ow->assign('link_search_'.$v, htmlspecialchars($link_base.'config.users&search=&criteria='.$v));
            $tpl_ow->assign('users_'.$v, $overview[$v]);
        } else {
            $tpl_ow->assign('users_'.$v, 0);
        }
    }
    $tpl_ow->assign('search', $search);
    $tpl_ow->assign_block('sel_crit_'.$criteria);
    if(!$search) $search = '*';
    $users = $DB->get_admidx($_SESSION['phM_uid'], $_SESSION['phM_superroot'], $search, $criteria);
    if (is_array($users)) {
        $tpl_m = $tpl_ow->get_block('menu_ow');
        $tpl_m->assign('username', $WP_msg['sysuser']);
        $tpl_m->assign('active', $WP_msg['optactive']);
        $tpl_ml = $tpl_m->get_block('menuline');
        foreach($users as $k => $v) {
            $usrdata = $DB->get_admdata($k);
            if ($usrdata['active'] == 0) {
                $tpl_ml->assign('active', $WP_msg['no']);
                $tpl_ml->assign('link_active', htmlspecialchars($link_base.'config.users&whattodo=active&uid='.$k));
            } else {
                $tpl_ml->assign('active', $WP_msg['yes']);
                $tpl_ml->assign('link_active', htmlspecialchars($link_base.'config.users&whattodo=inactive&uid='.$k));
            }
            $tpl_ml->assign(array
                    ('uid' => $k
                    ,'username' => $v
                    ,'link_dele' => htmlspecialchars($link_base.'config.users&whattodo=delete&uid='.$k)
                    ,'msg_dele' => $WP_msg['del']
                    ,'link_edit' => htmlspecialchars($link_base.'config.users&whattodo=edit&uid='.$k)
                    ,'msg_edit' =>$WP_msg['edit']
                    ));
            $tpl_m->assign('menuline',$tpl_ml);
            $tpl_ml->clear();
        }
        $tpl_ow->assign('menu_ow', $tpl_m);
    } else $tpl_ow->assign_block('nomenu');

    if (isset($_SESSION['phM_perm_write']['config.users_']) || $_SESSION['phM_superroot']) {
        $tpl_ow->fill_block('adduser', array
                ('link_adduser' => htmlspecialchars($link_base.'config.users&whattodo=add')
                ,'msg_adduser' => $WP_msg['SuAddAdmin']
                ));
    }
    $tpl_ow->assign(array
            ('head_text' => $WP_msg['SuHeadConfUser']
            ,'regadmins' => $WP_msg['CUMregusers']
            ,'msg_all' => $WP_msg['all']
            ,'msg_active' => $WP_msg['optactive']
            ,'msg_inactive' => $WP_msg['optinactive']
            ,'msg_locked' => $WP_msg['optlocked']
            ,'searchcrit' => $WP_msg['UMsearchcrit']
            ,'msg_finduser' => $WP_msg['CUMfinduser']
            ,'msg_title' => $WP_msg['UMtitinpfind']
            ,'msg_find' => $WP_msg['UMfind']
            ,'msg_nomatch' => $WP_msg['UMnomatch']
            ,'confpath' => CONFIGPATH
            ,'search_target' => htmlspecialchars($link_base.'config.users')
            ));
    $tpl->assign('overview', $tpl_ow);
}
