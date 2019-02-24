<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Calendar Handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.4 2015-03-11 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$icon_path = $_PM_['path']['theme'].'/icons/';

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['calendar_see_calendar']) {
    sendJS(array('handler' => 'calendar', 'childof' => array(), 'folders' => array()), 1, 1);
}
if (file_exists($_PM_['path']['handler'].'/calendar/lang.'.$WP_msg['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/calendar/lang.'.$WP_msg['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/calendar/lang.de.php');
}
$cDB = new handler_calendar_driver($_SESSION['phM_uid']);
session_write_close(); // Don't block other processes
$childof = array(0 => array('root'));
$return = array('root' => false);
foreach ($cDB->get_grouplist(true) as $k => $v) {
    if ($v['owner'] != $_SESSION['phM_uid']) {
        continue; // Handled separately
    }
    $remote = $v['type'] == 1 ? '_remote' : '';
    $return[$v['gid']] = array(
            'path' => $v['gid'],
            'foldername' => $v['name'],
            'icon' => $icon_path.'calendar'.$remote.'.png',
            'big_icon' => $icon_path.'calendar'.$remote.'_big.gif',
            'colour' => $v['colour'],
            'childof' => 'root',
            'type' => 2,
            'subdirs' => 0,
            'has_folders' => 0,
            'has_items' => 1,
            'level' => 1,
            'is_shared' => !empty($v['is_shared']) ? 1 : 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_resync' => 0,
            'ctx_subfolder' => 0,
            'is_collapsed' => !empty($_PM_['foldercollapses']['calendar_'.$k]) ? 1 : 0,
            'ctx_move' => 0,
            'ctx_rename' => ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_edit_group']) ? 1 : 0,
            'ctx_dele' => ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_delete_group']) ? 1 : 0,
            'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0
            );
    $childof['root'][] = $v['gid'];
}
$rootIsShared = $cDB->rootIsShared();
$return['root'] = array(
        'path' => 0, 'foldername' => $WP_msg['CalMyEvents'],
        'icon' => $icon_path.'calendar.png',
        'big_icon' => $icon_path.'calendar_big.gif',
        'colour' => '',
        'type' => 2,
        'subdirs' => (!empty($return)) ? 1 : 0,
        'has_folders' => (!empty($return)) ? 1 : 0,
        'has_items' => 1,
        'childof' => 0,
        'level' => 0,
        'is_shared' => !empty($rootIsShared) ? 1 : 0,
        'ctx' => 1,
        'ctx_props' => 1,
        'ctx_resync' => 0,
        'ctx_subfolder' => ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_group']) ? 1 : 0,
        'is_collapsed' => !empty($_PM_['foldercollapses']['calendar_root']) ? 1 : 0,
        'ctx_move' => 0,
        'ctx_rename' => 0,
        'ctx_dele' => 0,
        'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0
);
// Are there shared folders?
$myGroups = $myShares = array();
if (!empty($DB->features['groups'])) {
    $myGroups = $DB->get_usergrouplist($_SESSION['phM_uid'], true);
}
try {
    $DCS = new DB_Controller_Share();
    $myShares = $DCS->getMySharedFolders($_SESSION['phM_uid'], 'calendar', $myGroups);
} catch (Exception $e) {
    // void
}

if (!empty($myShares['calendar'])) {
    $childof[0][] = 'shareroot';
    $return['shareroot'] = array
            ('path' => 0, 'foldername' => $WP_msg['CalSharedCalendars'], 'type' => 2
            ,'icon' => $_PM_['path']['theme'].'/icons/sharedbox.png'
            ,'big_icon' => $_PM_['path']['theme'].'/icons/sharedbox_big.gif'
            ,'subdirs' => 0, 'has_folders' => 1, 'has_items' => 0, 'childof' => 0, 'level' => 0
            ,'ctx' => 0, 'ctx_props' => 0, 'ctx_resync' => 0, 'ctx_subfolder' => 0
            ,'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0
            ,'is_collapsed' => !empty($_PM_['foldercollapses']['calendar_shareroot']) ? 1 : 0
            );
    foreach (array_keys($myShares['calendar']) as $sharedFolder) {
        $v = $cDB->get_group($sharedFolder, false, true);
        if (!isset($return['user'.$v['owner']])) {
            $return['user'.$v['owner']] = array(
                    'path' => 'nil',
                    'foldername' => $v['username'],
                    'type' => 2,
                    'icon' => $_PM_['path']['theme'].'/icons/contacts.png',
                    'big_icon' => $_PM_['path']['theme'].'/icons/contacts_big.gif',
                    'colour' => '',
                    'has_folders' => 1,
                    'has_items' => 0,
                    'level' => 1,
                    'ctx' => 0, 'ctx_props' => 0, 'ctx_resync' => 0, 'ctx_subfolder' => 0,
                    'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0,
                    'is_collapsed' => !empty($_PM_['foldercollapses']['calendar_user'.$v['owner']]) ? 1 : 0
            );
            $childof['shareroot'][] = 'user'.$v['owner'];
        }

        $return[$sharedFolder] = array(
                'path' => $sharedFolder,
                'foldername' => $v['name'],
                'type' => 2,
                'icon' => $_PM_['path']['theme'].'/icons/calendar.png',
                'big_icon' => $_PM_['path']['theme'].'/icons/calendar_big.gif',
                'colour' => $v['colour'],
                'subdirs' => 0, 'childof' => 'user'.$v['owner'], 'has_folders' => 0, 'has_items' => 1, 'level' => 2
                ,'ctx' => 1, 'ctx_props' => 1, 'ctx_resync' => 0, 'ctx_subfolder' => 0
                ,'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0
                ,'is_collapsed' => !empty($_PM_['foldercollapses']['calendar_'.$sharedFolder]) ? 1 : 0
                );
        $childof['user'.$v['owner']][] = $sharedFolder;
    }
}
sendJS(array('handler' => 'calendar', 'childof' => $childof, 'folders' => $return), 1, 1);
