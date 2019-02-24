<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage RSS Handler for phlyMail Nahariya 4.0+
 * @copyright 2009-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.6 2013-10-25 $Id: flist.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['rss_see_feeds']) {
    sendJS(array('handler' => 'rss', 'childof' => array(), 'folders' => array()), 1, 1);
}
$cDB = new handler_rss_driver($_SESSION['phM_uid']);
session_write_close();

$childof = array(0 => array());
$return = array();

$hasPersonalFolders = $hasSharedFolders = false;
$myFolders = array();
foreach ($cDB->get_hybridlist(true) as $k => $v) {
    if ($v['owner'] == 0) {
        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['rss_see_global_feeds']) {
            continue;
        }
        $hasSharedFolders = true;
    } else {
        $hasPersonalFolders = true;
    }
    $myFolders[$k] = $v;
}
// This tries to find out, whether there's items in the root folders of that user and the shared ones
$numPersEntr = $cDB->get_itemcount(0, 0);
$numGlobEntr = $cDB->get_itemcount(1, 0) - $numPersEntr;

if ($hasPersonalFolders || $numPersEntr > 0) {
    $childof[0][] = 'root';
    $return['root'] = array(
            'path' => 0,
            'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/rss.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/rss_big.gif',
            'colour' => '',
            'foldername' => $WP_msg['MyFeeds'],
            'subdirs' => 1,
            'has_folders' => 1,
            'has_items' => 1,
            'childof' => 0,
            'level' => 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_dele' => 0,
            'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0,
            'ctx_resync' => 0,
            'ctx_subfolder' => 1,
            'ctx_move' => 0,
            'ctx_rename' => 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['rss_root']) && $_PM_['foldercollapses']['rss_root']) ? 1 : 0
            );
}
if ($hasSharedFolders || $numGlobEntr > 0) {
    $childof[0][] = 'shareroot';
    $return['shareroot'] = array(
            'path' => 0, 'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/sharedbox.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/sharedbox_big.gif',
            'colour' => '',
            'foldername' => $WP_msg['PublicFeeds'],
            'subdirs' => 1,
            'has_folders' => 1,
            'has_items' => 1,
            'childof' => 0,
            'level' => 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_resync' => 0,
            'ctx_subfolder' => 0,
            'ctx_move' => 0,
            'ctx_rename' => 0,
            'ctx_dele' => 0,
            'ctx_share' => 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['rss_shareroot']) && $_PM_['foldercollapses']['rss_shareroot']) ? 1 : 0
            );
}

foreach ($myFolders as $k => $v) {
    $basefolder = 'folder_def';
    if ($v['childof'] == 0) {
        $v['childof'] = 'root';
    }
    if (!empty($v['type']) && $v['type'] == 2) {
        $basefolder = 'rss';
    } elseif ($v['owner'] == 0) {
        $basefolder = 'contactsfolder_global';
        if ($v['childof'] == 0) {
            $v['childof'] = 'shareroot';
        }
    }
    $return[$k] = array(
            'path' => $v['path'],
            'foldername' => $v['name'],
            'type' => !empty($v['type']) ? $v['type'] : 0,
            'icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'_big.gif',
            'colour' => '',
            'subdirs' => !empty($v['subdirs']) ? 1 : 0,
            'childof' => $v['childof'],
            'has_folders' => !empty($v['has_folders']) ? 1 : 0,
            'has_items' => !empty($v['has_items']) ? 1 : 0,
            'level' => $v['level']+1,
            'is_shared' => !empty($v['is_shared']) ? 1 : 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_resync' => 0,
            'ctx_subfolder' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_add_folder'] ? (!empty($v['has_folders']) ? 1 : 0) : 0),
            'ctx_move' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_edit_folder'] ? 1 : 0),
            'ctx_rename' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_edit_folder'] ? 1 : 0),
            'ctx_dele' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_delete_folder'] ? 1 : 0),
            'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['rss_'.$k]) && $_PM_['foldercollapses']['rss_'.$k]) ? 1 : 0
            );
    if (!isset($childof[$v['childof']])) {
        $childof[$v['childof']] = array();
    }
    $childof[$v['childof']][] = $k;
}
sendJS(array('handler' => 'rss', 'childof' => $childof, 'folders' => $return), 1, 1);
