<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler RSS
 * @copyright 2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.3 2013-10-25 $Id: preview.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

$id = false;
if (!empty($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
} elseif (!empty($_REQUEST['i'])) {
    $id = intval($_REQUEST['i']);
}
if (!$id) {
    die();
}

$fDB = new handler_rss_driver($_SESSION['phM_uid']);
$item = $fDB->get_item($id, RSS_PUBLIC_FEEDS);
if (!$item || empty($item)) {
    die();
}

$feed = $fDB->get_feed($item['feed_id']);

if (!empty($item['content'])) {
    header('Content-Type: text/html; charset="utf-8"');
    echo links($item['content'], 'html', false);
    exit;
} elseif ($item['url']) {
    header('Location: '.$item['url']);
    exit;
}