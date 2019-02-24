<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Bookmarks
 * @copyright 2002-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.3 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$id = false;
if (!empty($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
} elseif (!empty($_REQUEST['i'])) {
    $id = intval($_REQUEST['i']);
}
if (!$id) die();

$bDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
$item = $bDB->get_item($id, BOOKMARKS_PUBLIC_BOOKMARKS);
if (!$item || empty($item)) die();

if (defined('PHM_MOBILE') && !isset($_REQUEST['go'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'bookmarks.preview.item.tpl');

    if (empty($item['url'])) {
        /* so what then? */
    }

    if (!preg_match('!^(?:f|ht)tps?://!i', $item['url'])) {
        $item['url'] = 'http://'.$item['url'];
    }

    $tpl->assign(array
            ('url' => $item['url']
            ,'name' => $item['name']
            ,'desc' => $item['description']
            ,'edit_url_h' => PHP_SELF.'?l=edit_bookmark&amp;h=bookmarks&amp;'.give_passthrough(1).'&amp;id='.$item['id']
            ));
    if ($item['favourite']) $tpl->assign_block('is_favourite');
} else {
    header('Location: '.PHP_SELF.'?deref='.derefer($item['url']));
    exit;
}
