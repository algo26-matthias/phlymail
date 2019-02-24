<?php
/**
 * Setup Module item management
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Bookmarks Handler
 * @copyright 2009-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$bDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
$error = false;
$update_itemlist = false;

if (isset($_REQUEST['what']) && $_REQUEST['what'] && isset($_REQUEST['item'])) {
    $items = $_REQUEST['item'];
    if (!is_array($items)) $items = array(0 => $items);
    switch ($_REQUEST['what']) {
    case 'item_copy':
        // Quotas: Check how many items this user might store
        $quota_number_items = $DB->quota_get($_SESSION['phM_uid'], 'bookmarks', 'number_items');
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_add_bookmark']) {
            $error .= $WP_msg['PrivNoAccess'];
        } elseif (false !== $quota_number_items) {
            $quota_itemsleft = $bDB->quota_bookmarksnum(false);
            $quota_itemsleft = $quota_number_items - $quota_itemsleft;
        } else {
            $quota_itemsleft = false;
        }
        // No more items allowed to save
        if (false !== $quota_itemsleft && $quota_itemsleft < 1) {
            $error .= $WP_msg['QuotaExceeded'];
            break; // Break out of switch statement, since the quota has been reached alreay
        }
        // End Quotas

        // Fall through, if everything was okay
    case 'item_move':
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_update_bookmark']) {
            $error .= $WP_msg['PrivNoAccess'];
        } elseif (!isset($_REQUEST['folder'])) {
            $error = $WP_msg['SetMailEnotarget'];
            break;
        } else {
            $folder = $_REQUEST['folder'];
        }
        foreach ($items as $item) {
            $ret = ($_REQUEST['what'] == 'item_copy')
                    ? $bDB->copy_item($item, $folder)
                    : $bDB->move_item($item, $folder);
            if (true !== $ret) {
                if (-2 === $ret) {
                    $error .= $WP_msg['SetItemEsamefile'].' '.$bDB->get_errors(LF);
                } else {
                    $error .= $WP_msg['SetItemEnorename'].': '.$bDB->get_errors(LF);
                }
            }
        }
        break;
    case 'item_delete':
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_delete_bookmark']) {
            $error .= $WP_msg['PrivNoAccess'];
        } else {
            $error = '';
            foreach ($items as $item) {
                $ret = $bDB->delete_item($item);
                if (-2 == $ret) {
                    continue;
                } elseif (!$ret) {
                    // $error .= $WP_msg['SetMailEnodelete'].': '.$bDB->get_errors(LF); # FIXME We have no error reporting at this point.
                }
            }
            $update_itemlist = true;
        }
        break;
    }
}
// This module might be called from a background task, thus generating no output
if (isset($_PM_['tmp']['setup']['no_output'])) return;
