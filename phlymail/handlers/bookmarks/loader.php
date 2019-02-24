<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage  Handler Bookmarks
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (file_exists($_PM_['path']['handler'].'/bookmarks/lang.'.$WP_msg['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/bookmarks/lang.'.$WP_msg['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/bookmarks/lang.de.php');
}
if (isset($_PM_['core']['bookmarks_nopublics']) && $_PM_['core']['bookmarks_nopublics']) {
    define('BOOKMARKS_PUBLIC_BOOKMARKS', false);
} else {
    define('BOOKMARKS_PUBLIC_BOOKMARKS', true);
}

$modname = false;
$outer_template = '3dframed.tpl';
switch($load) {
    case 'ilist': $modname = 'main.php'; $outer_template = 'maillist.tpl'; break;
    case 'flist': $modname = 'flist.php'; break;
    case 'edit_bookmark': $modname = 'edit_bookmark.php'; $outer_template = '3dframed.tpl';  break;
    case 'exchange': $modname = 'exchange.php'; break;
    case 'worker': $modname = 'worker.php'; break;
    case 'folderprops': $modname = 'folderprops.php'; break;
    case 'sendto': $modname = 'sendto.php'; break;
    case 'item':
    case 'preview': $modname = 'preview.php'; break;
    case 'browse': $modname = 'mod.browse.php'; break;
    case 'worker': $modname = 'worker.php';
        $outer_template = (isset($_REQUEST['what']) && $_REQUEST['what'] == 'folder_export') ? '3dframed.tpl' : 'framed.tpl';
        break;
}
if ($modname) {
    require __DIR__.'/'.$modname;
}
