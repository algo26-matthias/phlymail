<?php
/**
 * setup.folders.php - Setup Module folder management
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Calendar
 * @copyright 2004-2009 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2009-07-04
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$error = false;
$update_folderlist = false;

if (isset($_REQUEST['new_folder'])) {
    // Quotas: Check the space left and how many messages this user might store
    $quota_number_folder = $DB->quota_get($_SESSION['phM_uid'], 'calendar', 'number_folders');
    if (false !== $quota_number_folder) {
        $quota_folderleft = $cDB->quota_groupsnum(false);
        $quota_folderleft = $quota_number_folder - $quota_folderleft;
    } else {
        $quota_folderleft = false;
    }
    // End Quota definitions
    // No more folders allowed
    if (false !== $quota_folderleft && $quota_folderleft < 1) {
        $error .= $WP_msg['QuotaExceeded'];
    } elseif ($_REQUEST['new_folder']) {
        if (strlen($_REQUEST['new_folder']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$cDB->checkfor_groupname($_REQUEST['new_folder'])) {
            $res = $cDB->add_group($_REQUEST['new_folder']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnocreate'].': '.$cDB->get_errors(LF);
            } else {
                $update_folderlist = true;
            }
        } else {
            $error .= str_replace('$1', $_REQUEST['new_folder'], $WP_msg['SetFldEalreadyexists']);
        }
    } else {
        $error .= $WP_msg['SetFldEnametooshort'];
    }
}

if (isset($_REQUEST['rename_folder']) && isset($_REQUEST['rename_to'])) {
    if ($_REQUEST['rename_folder'] && $_REQUEST['rename_to']) {
        $if_exists = $cDB->checkfor_groupname($_REQUEST['rename_to']);
        if (strlen($_REQUEST['rename_to']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$if_exists || $if_exists == $_REQUEST['rename_folder']) {
            $res = $cDB->update_group($_REQUEST['rename_folder'], $_REQUEST['rename_to']);;
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
    if ($_REQUEST['remove_folder']) {
        $res = $cDB->dele_group($_REQUEST['remove_folder']);
        if (!$res) {
             $error .= $WP_msg['SetFldEnodelete'].': '.$cDB->get_errors(LF);
        } else {
            $update_folderlist = true;
        }
    } else {
        $error .= $WP_msg['SetFldEwhichdelete'];
    }
}
