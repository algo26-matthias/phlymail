<?php
/**
 * Setup Module item operations
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.6 2015-02-18 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$FS = new handler_files_driver($_SESSION['phM_uid']);
// Get current folder structure
$FS->init_folders(false);
$error = false;
$update_itemlist = false;
if (isset($_REQUEST['what']) && $_REQUEST['what'] && isset($_REQUEST['item'])) {
    $items = $_REQUEST['item'];
    if (!is_array($items)) {
        $items = array(0 => $items);
    }
    switch ($_REQUEST['what']) {
    case 'item_copy':
        // Quotas: Check the space left and how many messages this user might store
        $quota_size_storage = $DB->quota_get($_SESSION['phM_uid'], 'files', 'size_storage');
        if (false !== $quota_size_storage) {
            $quota_spaceleft = $FS->quota_getitemsize(false);
            $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
        } else {
            $quota_spaceleft = false;
        }
        $quota_number_items = $DB->quota_get($_SESSION['phM_uid'], 'files', 'number_items');
        if (false !== $quota_number_items) {
            $quota_itemsleft = $FS->quota_getitemnum(false);
            $quota_itemsleft = $quota_number_items - $quota_itemsleft;
        } else {
            $quota_itemsleft = false;
        }
        // This would fail on all systems without provisioning
        try {
            $systemQuota = SystemProvisioning::get('storage');
            $systemUsage = SystemProvisioning::getUsage('total_rounded');
            if ($systemQuota - $systemUsage <= 0) {
                $quota_spaceleft = 0;
            }
        } catch (Exception $ex) {
            // void
        }

        // No more items allowed to save
        if ((false !== $quota_itemsleft && $quota_itemsleft < 1)
                || (false !== $quota_spaceleft && $quota_spaceleft < 1)) {
            $error .= $WP_msg['QuotaExceeded'];
            break; // Break out of switch statement, since the quota has been reached alreay
        }
        // End Quotas

        // Fall through, if everything was okay
    case 'item_move':
        if (!isset($_REQUEST['folder'])) {
            $error = $WP_msg['SetMailEnotarget'];
            break;
        } else {
            $folder = $_REQUEST['folder'];
        }
        foreach ($items as $item) {
            $ret = ($_REQUEST['what'] == 'item_copy')
                    ? $FS->copy_item($item, $folder)
                    : $FS->move_item($item, $folder);
            if (true !== $ret) {
                if (-2 === $ret) {
                    $error .= $WP_msg['SetItemEsamefile'].' '.$FS->get_errors(LF);
                } else {
                    $error .= $WP_msg['SetItemEnorename'].': '.$FS->get_errors(LF);
                }
            }
        }
        break;
    case 'item_delete':
        $error = '';
        $alternate = (isset($_REQUEST['alternate']) && $_REQUEST['alternate']);
        foreach ($items as $item) {
            $ret = $FS->delete_item($item, false, $alternate);
            if (!$ret) {
                $error .= $WP_msg['SetMailEnodelete'].': '.$FS->get_errors(LF);
            }
        }
        $update_itemlist = true;
        break;
    case 'item_rename':
        $error = '';
        $MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
        list ($mtype) = $MIME->get_type_from_name(basename($_REQUEST['newname']), false);
        $ret = $FS->rename_item($items[0], $_REQUEST['newname'], ($mtype) ? $mtype : null);
        if (true !== $ret) {
            if (-2 === $ret) {
                $error .= $WP_msg['SetItemEsamefile'].' '.$FS->get_errors(LF);
            } else {
                $error .= $WP_msg['SetItemEnorename'].': '.intval($ret).' '.$FS->get_errors(LF);
            }
        }
        $update_itemlist = true;
        break;
    }
}
// This module might be called from a background task, thus generating no output
if (isset($_PM_['tmp']['setup']['no_output'])) {
    return;
}
