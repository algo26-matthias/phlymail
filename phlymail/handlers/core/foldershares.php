<?php
/**
 * Share a folder
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler Calendar
 * @author  Matthias Sommerfeld
 * @copyright 2013-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.1.0 2015-03-10 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$DCS = new DB_Controller_Share();
if (!empty($_REQUEST['save'])) {
    $DCS->setFolder(
            $_REQUEST['hdl'],
            $_REQUEST['fid'] == 'root' ? 0 : $_REQUEST['fid'],
            $_SESSION['phM_uid'],
            isset($_REQUEST['gshare']) ? $_REQUEST['gshare'] : array(),
            isset($_REQUEST['ushare']) ? $_REQUEST['ushare'] : array()
            );
}

$myGroups = $DB->get_usergrouplist($_SESSION['phM_uid'], true, true);
$myColleagues = $DB->get_groupuserlist(array_keys($myGroups), true);

$thisShares = $DCS->getFolder($_REQUEST['hdl'], $_REQUEST['fid'] == 'root' ? 0 : $_REQUEST['fid'], $_SESSION['phM_uid']);

$tpl = new phlyTemplate($_PM_['path']['templates'].'core.edit_share.tpl');
$t_gl = $tpl->get_block('groupline');
$t_ul = $tpl->get_block('userline');
foreach ($myGroups as $gid => $gname) {
    $t_gl->assign(array(
            'gid' => $gid,
            'groupname' => phm_entities($gname)
    ));
    if (!empty($thisShares['gid'][$gid])) {
        foreach (array(
                'read' => 'chk_read',
                'write' => 'chk_write',
                'delete' => 'chk_delete',
                // 'newfolder' => 'chk_addchild',
                // 'delitems' => 'chk_delchild'
                ) as $dbtok => $tpltok) {
            if (!empty($thisShares['gid'][$gid][$dbtok])) {
                $t_gl->assign_block($tpltok);
            }
        }
    }
    $tpl->assign('groupline', $t_gl);
    $t_gl->clear();
}
foreach ($myColleagues as $uid => $uname) {
    if ($uid == $_SESSION['phM_uid']) {
        continue; // Do not list myself
    }

    $t_ul->assign(array(
            'uid' => $uid,
            'username' => phm_entities($uname)
    ));
    if (!empty($thisShares['uid'][$uid])) {
        foreach (array(
                'read' => 'chk_read',
                'write' => 'chk_write',
                'delete' => 'chk_delete',
                // 'newfolder' => 'chk_addchild',
                // 'delitems' => 'chk_delchild'
                ) as $dbtok => $tpltok) {
            if (!empty($thisShares['uid'][$uid][$dbtok])) {
                $t_ul->assign_block($tpltok);
            }
        }
    }
    $tpl->assign('userline', $t_ul);
    $t_ul->clear();
}
$tpl->assign('form_url', phm_entities(PHP_SELF.'?'.give_passthrough(1).'&l=foldershares&h=core&save=1&hdl='.basename($_REQUEST['hdl']).'&fid='.basename($_REQUEST['fid'])));