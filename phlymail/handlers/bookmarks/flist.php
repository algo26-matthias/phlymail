<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Bookmarks Handler for phlyMail Nahariya 4.0+
 * @copyright 2009-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.1 2013-10-25 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_see_bookmarks']) {
    sendJS(array('handler' => 'bookmarks', 'childof' => array(), 'folders' => array()), 1, 1);
}
$cDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
session_write_close();

$childof = array(0 => array());
$return = array();
$favourites = $cDB->get_favourites(true, true);
if ($favourites > 0) {
    $childof[0][] = 'favourites';
    $return['favourites'] = array(
            'path' => 0,
            'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/bookmarks_favourites.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/bookmarks_favourites_big.gif',
            'colour' => '',
            'foldername' => $WP_msg['FavouriteBookmarks'],
            'subdirs' => 0,
            'has_folders' => 0,
            'has_items' => 1,
            'childof' => 0,
            'level' => 0,
            'ctx' => 0,
            'ctx_props' => 0,
            'ctx_dele' => 0,
            'ctx_share' => 0,
            'ctx_resync' => 0,
            'ctx_subfolder' => 0,
            'ctx_move' => 0,
            'ctx_rename' => 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['bookmarks_favourites']) && $_PM_['foldercollapses']['bookmarks_favourites']) ? 1 : 0
            );
}

$hasPersonalFolders = $hasSharedFolders = false;
$myFolders = array();
foreach ($cDB->get_folderlist(true) as $k => $v) {
    if ($v['owner'] == 0) {
        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['bookmarks_see_global_bookmarks']) {
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
            'icon' => $_PM_['path']['theme'].'/icons/bookmarks.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/bookmarks_big.gif',
            'foldername' => $WP_msg['MyBookmarks'],
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
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['bookmarks_root']) && $_PM_['foldercollapses']['bookmarks_root']) ? 1 : 0
            );
}
if ($hasSharedFolders || $numGlobEntr > 0) {
    $childof[0][] = 'shareroot';
    $return['shareroot'] = array
            ('path' => 0, 'type' => 2
            ,'icon' => $_PM_['path']['theme'].'/icons/sharedbox.png'
            ,'big_icon' => $_PM_['path']['theme'].'/icons/sharedbox_big.gif'
            ,'foldername' => $WP_msg['PublicBookmarks']
            ,'subdirs' => 1
            ,'has_folders' => 1
            ,'has_items' => 1
            ,'childof' => 0
            ,'level' => 0
            ,'ctx' => 1
            ,'ctx_props' => 1
            ,'ctx_resync' => 0
            ,'ctx_subfolder' => 0
            ,'ctx_move' => 0
            ,'ctx_rename' => 0
            ,'ctx_dele' => 0
            ,'ctx_share' => 0
            ,'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['bookmarks_shareroot']) && $_PM_['foldercollapses']['bookmarks_shareroot']) ? 1 : 0
            );
}

foreach ($myFolders as $k => $v) {
    $basefolder = 'folder_def';
    if ($v['childof'] == 0) {
        $v['childof'] = 'root';
    }
    if ($v['owner'] == 0) {
        $basefolder = 'contactsfolder_global';
        if ($v['childof'] == 0) {
            $v['childof'] = 'shareroot';
        }
    }
    $return[$k] = array(
            'path' => $v['path'],
            'foldername' => $v['name'],
            'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'_big.gif',
            'colour' => '',
            'subdirs' => (isset($v['subdirs']) && $v['subdirs']) ? 1 : 0,
            'childof' => $v['childof'],
            'has_folders' => 1,
            'has_items' => 1,
            'level' => $v['level']+1,
            'is_shared' => !empty($v['is_shared']) ? 1 : 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_resync' => 0,
            'ctx_subfolder' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_add_folder'] ? 1 : 0),
            'ctx_move' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_edit_folder'] ? 1 : 0),
            'ctx_rename' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_edit_folder'] ? 1 : 0),
            'ctx_dele' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_delete_folder'] ? 1 : 0),
             'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['bookmarks_'.$k]) && $_PM_['foldercollapses']['bookmarks_'.$k]) ? 1 : 0
            );
    if (!isset($childof[$v['childof']])) {
        $childof[$v['childof']] = array();
    }
    $childof[$v['childof']][] = $k;
}
sendJS(array('handler' => 'bookmarks', 'childof' => $childof, 'folders' => $return), 1, 1);
