<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage  Handler RSS
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.9 2015-03-30 $Id: loader.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

if (file_exists($_PM_['path']['handler'].'/rss/lang.'.$WP_msg['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/rss/lang.'.$WP_msg['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/rss/lang.de.php');
}
if (isset($_PM_['core']['rss_nopublics']) && $_PM_['core']['rss_nopublics']) {
    define('RSS_PUBLIC_FEEDS', false);
} else {
    define('RSS_PUBLIC_FEEDS', true);
}

$modname = false;
$outer_template = '3dframed.tpl';
switch($load) {
    case 'ilist': $modname = 'main.php'; $outer_template = 'maillist.tpl'; break;
    case 'flist': $modname = 'flist.php'; break;
    case 'edit_feed': $modname = 'edit_feed.php'; $outer_template = '3dframed.tpl';  break;
    case 'exchange': $modname = 'exchange.php'; break;
    case 'worker': $modname = 'worker.php'; break;
    case 'folderprops': $modname = 'folderprops.php'; break;
    case 'sendto': $modname = 'sendto.php'; break;
    case 'item':
    case 'preview': $modname = 'preview.php'; $outer_template = 'maillist.tpl'; break;
    case 'browse': $modname = 'mod.browse.php'; break;
    case 'worker': $modname = 'worker.php';
        $outer_template = (isset($_REQUEST['what']) && $_REQUEST['what'] == 'folder_export') ? '3dframed.tpl' : 'framed.tpl';
        break;
}
if ($modname) require(__DIR__.'/'.$modname);