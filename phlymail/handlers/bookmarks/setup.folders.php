<?php
/**
 * Setup Module folder management
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Bookmarks Handler
 * @copyright 2009-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$cDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
$error = false;
$update_folderlist = false;

if (isset($_REQUEST['new_folder']) && isset($_REQUEST['childof'])) {
    // Quotas: Check the space left and how many messages this user might store
    $quota_number_folder = $DB->quota_get($_SESSION['phM_uid'], 'bookmarks', 'number_folders');
    if (false !== $quota_number_folder) {
        $quota_folderleft = $cDB->quota_foldersnum(false);
        $quota_folderleft = $quota_number_folder - $quota_folderleft;
    } else {
        $quota_folderleft = false;
    }
    // End Quota definitions
    // No more folders allowed
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_add_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif (false !== $quota_folderleft && $quota_folderleft < 1) {
        $error .= $WP_msg['QuotaExceeded'];
    } elseif ($_REQUEST['new_folder'] && $_REQUEST['childof']) {
        if (strlen($_REQUEST['new_folder']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$cDB->checkfor_foldername($_REQUEST['new_folder'], $_REQUEST['childof'])) {
            $res = $cDB->add_folder($_REQUEST['new_folder'], $_REQUEST['childof']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnocreate'].': '.$cDB->get_errors(LF);
            } else {
                $update_folderlist = true;
            }
        } else {
            $error .= str_replace('$1', $_REQUEST['new_folder'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['childof']) {
        $error .= $WP_msg['SetFldEwherecreate'];
    } else {
        $error .= $WP_msg['SetFldEnametooshort'];
    }
}

if (isset($_REQUEST['move_folder']) && isset($_REQUEST['move_to'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_edit_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['move_folder'] && isset($_REQUEST['move_to'])) {
        $folderinfo = $cDB->get_folder($_REQUEST['move_folder']);
        if (!$cDB->checkfor_foldername($folderinfo['name'], $_REQUEST['move_to'])) {
            $res = $cDB->move_folder($_REQUEST['move_folder'], $_REQUEST['move_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnomove'].': '.$cDB->get_errors(LF);
            } else {
                $update_folderlist = true;
            }
        } else {
            $error .= str_replace('$1', $folderinfo['name'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['move_to']) {
        $error .= $WP_msg['SetFldEwheremove'];
    }
}

if (isset($_REQUEST['rename_folder']) && isset($_REQUEST['rename_to'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_edit_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['rename_folder'] && $_REQUEST['rename_to']) {
        $info = $cDB->get_folder($_REQUEST['rename_folder']);
        $if_exists = $cDB->checkfor_foldername($_REQUEST['rename_to'], $info['childof']);
        if (strlen($_REQUEST['rename_to']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$if_exists || $if_exists == $_REQUEST['rename_folder']) {
            $res = $cDB->update_folder($_REQUEST['rename_folder'], $_REQUEST['rename_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnorename'].': '.$cDB->get_errors(LF);
            } else {
                $update_folderlist = true;
            }
        } else {
            $error .= str_replace('$1', $_REQUEST['rename_to'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['rename_folder']) {
        $error .= $WP_msg['SetFldEwhichrename'];
    } else {
        $error .= $WP_msg['SetFldEnametooshort'];
    }
}

if (isset($_REQUEST['remove_folder'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_delete_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['remove_folder']) {
        $res = $cDB->dele_folder($_REQUEST['remove_folder']);
        if (!$res) {
             $error .= $WP_msg['SetFldEnodelete'].': '.$cDB->get_errors(LF);
        } else {
            $update_folderlist = true;
        }
    } else {
        $error .= $WP_msg['SetFldEwhichdelete'];
    }
}
