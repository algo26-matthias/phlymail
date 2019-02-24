<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Files Handler
 * @copyright 2001-2010 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0 2010-01-20
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$icon_path = $_PM_['path']['theme'].'/icons/';
$mode = (isset($_REQUEST['mode']) && $_REQUEST['mode']) ? $_REQUEST['mode'] : false;

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['files_see_files']) {
    sendJS(array('handler' => 'files', 'childof' => array(), 'folders' => array()), 1, 1);
}
$FS = new handler_files_driver($_SESSION['phM_uid']);
session_write_close(); // Don't block other processes
$FS->init_folders(('browse' == $mode) ? false : true);
// Hint: all other handlers put their translated name into session RIGHT HERE, but
// this handlers does so in the below method.
$folders = array();
$childof = array();
foreach ($FS->read_folders_flat(0) as $k => $v) {
    $v['is_trash'] = 0;
    // Find special icons for folders
    switch ($v['icon']) {
    case ':waste':
        $v['big_icon'] = $icon_path.'waste_big.gif';
        $v['icon'] = $icon_path.'waste.png';
        $v['is_trash'] = 1;
        break;
    case ':files':
        $v['big_icon'] = $icon_path.'files_big.gif';
        $v['icon'] = $icon_path.'files.png';
        break;
    case ':virtual':
        $v['big_icon'] = $icon_path.'virtualfolder_big.gif';
        $v['icon'] = $icon_path.'virtualfolder.png';
        break;
    }
    if (!file_exists($v['icon'])) {
        $v['icon'] = $icon_path.'folder_def.png';
    }
    if (!isset($v['big_icon']) || !file_exists($v['big_icon'])) {
        $v['big_icon'] = $icon_path.'folder_def_big.gif';
    }
    $v = array_merge($v, array(
            'subdirs' => (int) (isset($v['subdirs']) && $v['subdirs']),
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0,
            'ctx_resync' => 0,
            'colour' => '',
            'ctx_subfolder' => (int) ((isset($v['has_folders']) && $v['has_folders']) && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_add_folder'])),
            'is_collapsed' => (int) (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['files_'.$k]) && $_PM_['foldercollapses']['files_'.$k]),
            'ctx_move' => (($v['type'] == 1 || $v['type'] == 11) && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_edit_folder'])) ? 1 : 0,
            'ctx_rename' => (($v['type'] == 1 || $v['type'] == 11) && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_edit_folder'])) ? 1 : 0,
            'ctx_dele' => (($v['type'] == 1 || $v['type'] == 11) && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_delete_folder'])) ? 1 : 0
            ));
    $folders[$k] = $v;
    if (!isset($childof[$v['childof']])) {
        $childof[$v['childof']] = array();
    }
    $childof[$v['childof']][] = $k;
}
sendJS(array('handler' => 'files', 'childof' => $childof, 'folders' => $folders), 1, 1);
