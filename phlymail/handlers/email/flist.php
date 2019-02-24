<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Email Handler
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.6 2015-03-11 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$icon_path = $_PM_['path']['theme'].'/icons/';
$mode = (isset($_REQUEST['mode']) && $_REQUEST['mode']) ? $_REQUEST['mode'] : 'default';

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_see_emails']) {
    sendJS(array('handler' => 'email', 'childof' => array(), 'folders' => array()), 1, 1);
}
// Local copy of some session vars
$myUid = $_SESSION['phM_uid'];
$myUsername = basename($_SESSION['phM_username']);
$myPrivs = $_SESSION['phM_privs'];
session_write_close(); // closing the session so other process don't get blocked

$FS = new handler_email_driver($myUid);
$FS->init_folders(false); // ('browse' == $mode) ? false : true);
$folders = array();
$childof = array();
foreach ($FS->read_folders_flat(0, 0, false) as $k => $v) {
    $v['is_junk'] = $v['is_trash'] = 0;
    $stale = (isset($v['stale']) && $v['stale']) ? 1 : 0;
    $secure = (isset($v['secure']) && $v['secure']) ? 1 : 0;
    $v['ctx_share'] = $v['ctx'] = $v['ctx_props'] = 1;
    $v['ctx_subfolder'] = (int) ((isset($v['has_folders']) && $v['has_folders']) && ($myPrivs['all'] || $myPrivs['email_add_folder']));
    // Find special icons for folders
    switch ($v['icon']) {
        case ':inbox':     $v['big_icon'] = $icon_path.'inbox_big.gif';     $v['icon'] = $icon_path.'inbox.png'; break;
        case ':archive':   $v['big_icon'] = $icon_path.'archive_big.gif';   $v['icon'] = $icon_path.'archive.png';  break;
        case ':outbox':    $v['big_icon'] = $icon_path.'outbox_big.gif';    $v['icon'] = $icon_path.'outbox.png'; break;
        case ':sent':      $v['big_icon'] = $icon_path.'sent_big.gif';      $v['icon'] = $icon_path.'sent.png'; break;
        case ':waste':     $v['big_icon'] = $icon_path.'waste_big.gif';     $v['icon'] = $icon_path.'waste.png'; $v['is_trash'] = 1; break;
        case ':junk':      $v['big_icon'] = $icon_path.'junk_big.gif';      $v['icon'] = $icon_path.'junk.png'; $v['is_junk'] = 1; break;
        case ':drafts':    $v['big_icon'] = $icon_path.'drafts_big.gif';    $v['icon'] = $icon_path.'drafts.png'; break;
        case ':templates': $v['big_icon'] = $icon_path.'templates_big.gif'; $v['icon'] = $icon_path.'templates.png'; break;
        case ':calendar':  $v['big_icon'] = $icon_path.'calendar_big.gif';  $v['icon'] = $icon_path.'calendar.png'; break;
        case ':contacts':  $v['big_icon'] = $icon_path.'contacts_big.gif';  $v['icon'] = $icon_path.'contacts.png'; break;
        case ':notes':     $v['big_icon'] = $icon_path.'notes_big.gif';     $v['icon'] = $icon_path.'notes.png'; break;
        case ':tasks':     $v['big_icon'] = $icon_path.'tasks_big.gif';     $v['icon'] = $icon_path.'tasks.png'; break;
        case ':files':     $v['big_icon'] = $icon_path.'files_big.gif';     $v['icon'] = $icon_path.'files.png'; break;
        case ':rss':       $v['big_icon'] = $icon_path.'rss_big.gif';       $v['icon'] = $icon_path.'rss.png'; break;
        case ':mailbox':   $v['big_icon'] = $icon_path.'mailbox_big.gif';   $v['icon'] = $icon_path.'mailbox.png';
            $v['foldername'] = $WP_msg['mailbox'].' '.$myUsername;
            $v['ctx_share'] = 0;
            break;
        case ':imapbox':   $v['big_icon'] = $icon_path.'imapbox'.($stale ? '_stale' : ($secure ? '_secure' : '')).'_big.gif';
            $v['icon'] = $icon_path.'imapbox'.($stale ? '_stale' : ($secure ? '_secure' : '')).'.png';
            $v['ctx_share'] = 0;
            break;
        case ':virtual':   $v['big_icon'] = $icon_path.'virtualfolder_big.gif'; $v['icon'] = $icon_path.'virtualfolder.png';
            $v['ctx_share'] = 0;
            break;
        case ':sharedbox': $v['big_icon'] = $icon_path.'sharedbox_big.gif'; $v['icon'] = $icon_path.'sharedbox.png';
            $v['foldername'] = $WP_msg['SharedFolders'];
            $v['ctx_share'] = $v['ctx'] = $v['ctx_props'] = $v['ctx_subfolder'] = 0;
            break;
    }
    // Do we have shares at all?
    if (empty($_SESSION['phM_shares'])) {
        $v['ctx_share'] = 0;
    }

    // Shared folders
    if ($v['type'] == 2) {
        $v['ctx_share'] = $v['ctx_subfolder'] = 0;
    }
    if (!file_exists($v['icon'])) {
        $v['icon'] = $icon_path.'folder_def.png';
    }
    if (!isset($v['big_icon']) || !file_exists($v['big_icon'])) {
        $v['big_icon'] = $icon_path.'folder_def_big.gif';
    }
    // These lines tell the frontend, which CTXMen items are available.
    $v = array_merge($v, array(
            'subdirs' => (int) (isset($v['subdirs']) && $v['subdirs']),
            'ctx_resync' => (int) (isset($v['has_items']) && $v['has_items']),
            'colour' => '',
            'is_collapsed' => (int) (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['email_'.$k]) && $_PM_['foldercollapses']['email_'.$k]),
            'ctx_move' => (($v['type'] == 1 || $v['type'] == 11) && ($myPrivs['all'] || $myPrivs['email_edit_folder'])) ? 1 : 0,
            'ctx_rename' => (($v['type'] == 1 || $v['type'] == 11) && ($myPrivs['all'] || $myPrivs['email_edit_folder'])) ? 1 : 0,
            'ctx_dele' => (($v['type'] == 1 || $v['type'] == 11) && ($myPrivs['all'] || $myPrivs['email_delete_folder'])) ? 1 : 0
            ));
    $folders[$k] = $v;
    if (!isset($childof[$v['childof']])) {
        $childof[$v['childof']] = array();
    }
    $childof[$v['childof']][] = $k;
}
sendJS(array('handler' => 'email', 'childof' => $childof, 'folders' => $folders), 1, 1);
