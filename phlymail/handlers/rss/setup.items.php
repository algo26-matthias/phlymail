<?php
/**
 * Setup Module item management
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler RSS
 * @copyright 2009-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.5 2013-11-03 $Id: setup.items.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

$cDB = new handler_rss_driver($_SESSION['phM_uid']);
$error = false;
$update_itemlist = false;

if (isset($_REQUEST['what']) && $_REQUEST['what'] && isset($_REQUEST['item'])) {
    $items = &$_REQUEST['item'];
    if (!is_array($items)) $items = array(0 => $items);

    switch ($_REQUEST['what']) {
    case 'item_unmark':
    case 'item_mark':
        $data = array('read' => $_REQUEST['what'] == 'item_mark' ? '1' : '0', 'id' => array());
        foreach ($items as $item) {
            $data['id'][] = $item;
        }
        $ret = $cDB->update_item($data);
        $update_itemlist = true;
        break;
    case 'item_delete':
        if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_delete_bookmark']) {
            $error .= $WP_msg['PrivNoAccess'];
        } else {
            $error = '';
            $ret = $cDB->delete_item($items);
            $update_itemlist = true;
        }
        break;
    }
}
// This module might be called from a background task, thus generating no output
if (isset($_PM_['tmp']['setup']['no_output'])) {
    return;
}