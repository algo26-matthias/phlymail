<?php
/**
 * Setup Module folder management
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$FS = new handler_files_driver($_SESSION['phM_uid']);
$error = false;
$update_folderlist = false;
// Get current folder structure
$FS->init_folders(false);

if (isset($_REQUEST['new_folder']) && isset($_REQUEST['childof'])) {
    // Quotas: Check the space left and how many messages this user might store
    $quota_number_folder = $DB->quota_get($_SESSION['phM_uid'], 'files', 'number_folders');
    if (false !== $quota_number_folder) {
        $quota_folderleft = $FS->quota_getfoldernum(false);
        $quota_folderleft = $quota_number_folder - $quota_folderleft;
    } else {
        $quota_folderleft = false;
    }
    // End Quota definitions
    // No more folders allowed
    if (false !== $quota_folderleft && $quota_folderleft < 1) {
        $error .= $WP_msg['QuotaExceeded'];
    } elseif ($_REQUEST['new_folder'] && $_REQUEST['childof']) {
        if (strlen($_REQUEST['new_folder']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$FS->folder_exists($_REQUEST['new_folder'], $_REQUEST['childof'])) {
            $res = $FS->create_folder($_REQUEST['new_folder'], $_REQUEST['childof']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnocreate'].': '.$FS->get_errors(LF);
            } else {
                // Update current folder structure
                $FS->init_folders(false);
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
    if ($_REQUEST['move_folder'] && $_REQUEST['move_to']) {
        $folderinfo = $FS->get_folder_info($_REQUEST['move_folder']);
        if (!$FS->folder_exists($folderinfo['foldername'], $_REQUEST['move_to'])) {
            $res = $FS->move_folder($_REQUEST['move_folder'], $_REQUEST['move_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnomove'].': '.$FS->get_errors(LF);
            } else {
                // Update current folder structure
                $FS->init_folders(false);
                $update_folderlist = true;
            }
        } else {
            $error .= str_replace('$1', $folderinfo['foldername'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['move_to']) {
        $error .= $WP_msg['SetFldEwheremove'];
    }
}

if (isset($_REQUEST['rename_folder']) && isset($_REQUEST['rename_to'])) {
    if ($_REQUEST['rename_folder'] && $_REQUEST['rename_to']) {
        $info = $FS->get_folder_info($_REQUEST['rename_folder']);
        $if_exists = $FS->folder_exists($_REQUEST['rename_to'], $info['childof']);
        if (strlen($_REQUEST['rename_to']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$if_exists || $if_exists == $_REQUEST['rename_folder']) {
            $res = $FS->rename_folder($_REQUEST['rename_folder'], $_REQUEST['rename_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnorename'].': '.$FS->get_errors(LF);
            } else {
                // Update current folder structure
                $FS->init_folders(false);
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
    if ($_REQUEST['remove_folder']) {
        $forced = (isset($_REQUEST['directly']) && $_REQUEST['directly']);
        $res = $FS->remove_folder($_REQUEST['remove_folder'], $forced);
        if (!$res) {
             $error .= $WP_msg['SetFldEnodelete'].': '.$FS->get_errors(LF);
        } else {
            // Update current folder structure
            $FS->init_folders(false);
            $update_folderlist = true;
        }
    } else {
        $error .= $WP_msg['SetFldEwhichdelete'];
    }
}

if (isset($_REQUEST['empty_folder']) && $_REQUEST['empty_folder']) {
	$res = $FS->delete_item(false, $_REQUEST['empty_folder']);
	if (!$res) {
		$error .= $WP_msg['SetFldEnoempty'].': '.$FS->get_errors(LF);
	} else {
		// Update current folder structure
		$FS->init_folders(false);
		$update_folderlist = true;
	}
}
