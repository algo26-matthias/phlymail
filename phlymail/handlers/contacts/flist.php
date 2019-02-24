<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Contacts Handler
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.8 2013-10-25 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_see_contacts']) {
    sendJS(array('handler' => 'contacts', 'childof' => array(), 'folders' => array()), 1, 1);
}
if (!defined('CONTACTS_PUBLIC_CONTACTS')) {
    if (isset($_PM_['core']['contacts_nopublics']) && $_PM_['core']['contacts_nopublics']) {
        define('CONTACTS_PUBLIC_CONTACTS', false);
    } elseif (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_see_global_contacts']) {
        define('CONTACTS_PUBLIC_CONTACTS', false);
    } else {
        define('CONTACTS_PUBLIC_CONTACTS', true);
    }
}
$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
session_write_close();
if (file_exists($_PM_['path']['handler'].'/contacts/lang.'.$GLOBALS['WP_msg']['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/contacts/lang.'.$GLOBALS['WP_msg']['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/contacts/lang.de.php');
}
$childof = array(0 => array('root'));
$return = array('root' => false);
foreach ($cDB->get_grouplist(CONTACTS_PUBLIC_CONTACTS) as $k => $v) {
    $return[$v['gid']] = array(
            'path' => $v['gid'],
            'foldername' => $v['name'],
            'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'_big.gif',
            'colour' => '',
            'subdirs' => 0,
            'childof' => 'root',
            'has_folders' => 0,
            'has_items' => 1,
            'level' => 1,
            'is_shared' => !empty($v['is_shared']) ? 1 : 0,
            'ctx' => 1,
            'ctx_props' => 1,
            'ctx_resync' => 0,
            'ctx_subfolder' => 0,
            //'ctx_rename' => ($v['owner'] == 0 ? 0 : 1), 'ctx_dele' => ($v['owner'] == 0 ? 0 : 1), # Sort global folders into own root folder, visible on public folders
            'ctx_share' => $_SESSION['phM_shares'] ? 1 : 0,
            'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['contacts_'.$k]) && $_PM_['foldercollapses']['contacts_'.$k]) ? 1 : 0,
            'ctx_move' => 0,
            'ctx_rename' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_edit_group'] ? 1 : 0),
            'ctx_dele' => ($v['owner'] == 0) ? 0 : ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_delete_group'] ? 1 : 0)
            );
    $childof['root'][] = $v['gid'];
}
$return['root'] = array(
        'path' => 0,
        'type' => 2,
        'icon' => $_PM_['path']['theme'].'/icons/contacts.png',
        'big_icon' => $_PM_['path']['theme'].'/icons/contacts_big.gif',
        'colour' => '',
        'foldername' => $WP_msg['MyContacts'],
        'subdirs' => (!empty($return)) ? 1 : 0,
        'has_folders' => (!empty($return)) ? 1 : 0,
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
        'is_collapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['contacts_root']) && $_PM_['foldercollapses']['contacts_root']) ? 1 : 0
);

// Are there shared folders?
$myGroups = $myShares = array();
if (!empty($DB->features['groups'])) {
    $myGroups = $DB->get_usergrouplist($_SESSION['phM_uid'], true);
}
try {
    $DCS = new DB_Controller_Share();
    $myShares = $DCS->getMySharedFolders($_SESSION['phM_uid'], 'contacts', $myGroups);
} catch (Exception $e) {
    // void
}

if (!empty($myShares['contacts'])) {
    $childof[0][] = 'shareroot';
    $return['shareroot'] = array(
            'path' => 0,
            'foldername' => $WP_msg['SharedContacts'],
            'type' => 2,
            'icon' => $_PM_['path']['theme'].'/icons/sharedbox.png',
            'big_icon' => $_PM_['path']['theme'].'/icons/sharedbox_big.gif',
            'colour' => '',
            'subdirs' => 0, 'has_folders' => 1, 'has_items' => 0, 'childof' => 0, 'level' => 0
            ,'ctx' => 0, 'ctx_props' => 0, 'ctx_resync' => 0, 'ctx_subfolder' => 0
            ,'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0
            ,'is_collapsed' => !empty($_PM_['foldercollapses']['contacts_shareroot']) ? 1 : 0
            );
    foreach (array_keys($myShares['contacts']) as $sharedFolder) {
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
                    'is_collapsed' => !empty($_PM_['foldercollapses']['contacts_user'.$v['owner']]) ? 1 : 0
            );
            $childof['shareroot'][] = 'user'.$v['owner'];
        }

        $return[$sharedFolder] = array(
                'path' => $sharedFolder,
                'foldername' => $v['name'],
                'type' => 2,
                'icon' => $_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'.png',
                'big_icon' => $_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'_big.gif',
                'colour' => '',
                'subdirs' => 0, 'childof' => 'user'.$v['owner'], 'has_folders' => 0, 'has_items' => 1, 'level' => 2
                ,'ctx' => 1, 'ctx_props' => 1, 'ctx_resync' => 0, 'ctx_subfolder' => 0
                ,'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0
                ,'is_collapsed' => !empty($_PM_['foldercollapses']['contacts_'.$sharedFolder]) ? 1 : 0
                );
        $childof['user'.$v['owner']][] = $sharedFolder;
    }
}
sendJS(array('handler' => 'contacts', 'childof' => $childof, 'folders' => $return), 1, 1);
