<?php
/**
 * Edit (or add) a bookmark
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Bookmarks
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2015-02-18 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$error = false;
$bDB = new handler_bookmarks_driver($_SESSION['phM_uid']);

$id = isset($_REQUEST['id']) && $_REQUEST['id'] ? $_REQUEST['id'] : false;
$base_url = PHP_SELF.'?l=edit_bookmark&amp;h=bookmarks&amp;'.give_passthrough(1).'&amp;save=1';

if (isset($_REQUEST['save'])
        && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_add_bookmark'] || $_SESSION['phM_privs']['bookmarks_update_bookmark'])) {
    // Check quotas
    $quota_num_contacts = $DB->quota_get($_SESSION['phM_uid'], 'bookmarks', 'number_bookmarks');
    if (false !== $quota_num_contacts) {
        $quota_contactsleft = $bDB->quota_bookmarksnum(false);
        $quota_contactsleft = $quota_num_contacts - $quota_contactsleft;
    } else {
        $quota_contactsleft = false;
    }
    // This would fail on all systems without provisioning
    try {
        $systemQuota = SystemProvisioning::get('storage');
        $systemUsage = SystemProvisioning::getUsage('total_rounded');
        if ($systemQuota - $systemUsage <= 0) {
            $quota_contactsleft = 0;
        }
    } catch (Exception $ex) {
        // void
    }

    // No more bookmarks allowed to save
    if (false !== $quota_contactsleft && $quota_contactsleft < 1) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['QuotaExceeded']);
        return;
    }
    if (!$id && !$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_add_bookmark']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    } elseif ($id && !$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_update_bookmark']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    // End Quota
    $payload = array
            ('name' => $_REQUEST['name']
            ,'url' => $_REQUEST['url']
            ,'description' => $_REQUEST['desc']
            ,'fid' => $_REQUEST['group']
            ,'favourite' => isset($_REQUEST['is_favourite']) && $_REQUEST['is_favourite'] ? 1 : 0
            );
    if ($id) {
        $payload['id'] = intval($id);
        $res = $bDB->update_item($payload);
    } else {
        $res = $bDB->add_item($payload);
    }
    if (defined('PHM_MOBILE') && $res) {
        if ($id) {
            header('Location: '.PHP_SELF.'?h=bookmarks&a=ilist&'.give_passthrough(1).'&f='.(intval($_REQUEST['group']) > 0 ? intval($_REQUEST['group']) : 'root'));
        } else {
            header('Location: '.PHP_SELF.'?'.give_passthrough(1));
        }
        exit;
    }

}
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_add_bookmark'] && !$_SESSION['phM_privs']['bookmarks_edit_bookmark']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'bookmarks.edit.item.tpl');
if (isset($res) && $res) {
    $tpl->assign_block('success');
}
if ($id) {
    $bm = $bDB->get_item($id, 0);
    $tpl->assign(array
            ('url' => $bm['url']
            ,'name' => $bm['name']
            ,'desc' => $bm['description']
            ,'save_url' => $base_url.'&amp;id='.$bm['id']
            ));
    if ($bm['favourite']) {
        $tpl->assign_block('is_favourite');
    }
} else {
    $bm = array();
    $tpl->assign('save_url', $base_url);
}
$tpl->assign(array
        ('msg_url' => $WP_msg['BMURL']
        ,'msg_desc' => $WP_msg['BMDescription']
        ,'msg_name' => $WP_msg['BMName']
        ,'msg_group' => $WP_msg['HGrp']
        ,'msg_save' => $WP_msg['save']
        ,'msg_root' => $WP_msg['MyBookmarks']
        ,'msg_is_favourite' => $WP_msg['BMFavouriteBookmark']
        ));
$t_gs = $tpl->get_block('group_sel');
foreach ($bDB->get_folderlist(0) as $id => $grp) {
    $lvl_space = ($grp['level'] > 0) ? str_repeat('&nbsp;', $grp['level'] * 2) : '';
    $t_gs->assign(array('id' => $id, 'name' => $lvl_space . phm_entities($grp['name'])));
    if (isset($bm['fid']) && $bm['fid'] == $id) {
        $t_gs->assign_block('sel');
    }
    $tpl->assign('group_sel', $t_gs);
    $t_gs->clear();
}

function aquire_favicon($url)
{
    $url = null;
}
