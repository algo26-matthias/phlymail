<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.0 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (file_exists($_PM_['path']['handler'].'/files/lang.'.$WP_msg['language'].'.php')) {
    require $_PM_['path']['handler'].'/files/lang.'.$WP_msg['language'].'.php';
} else {
    require $_PM_['path']['handler'].'/files/lang.de.php';
}
$outer_template = '3dframed.tpl';
switch ($load) {
    case 'ilist': $modname = 'listitems.php'; $outer_template = 'maillist.tpl'; break;
    case 'flist': $modname = 'flist.php'; break;
    case 'setup': $modname = 'mod.setup.php'; break;
    case 'getfromurl': // Fall through
    case 'upload': $modname = 'upload.php'; break;
    case 'browse': $modname = 'mod.browse.php'; break;
    case 'folderprops': $modname = 'folderprops.php'; break;
    case 'output': // Fall through
    case 'item': $modname = 'mod.output.php'; $outer_template = 'mailview.tpl'; break;
    case 'sendto': $modname = 'sendto.php'; break;
    case 'worker':
        $modname = 'worker.php';
        $outer_template = (isset($_REQUEST['what']) && $_REQUEST['what'] == 'folder_export') ? '3dframed.tpl' : 'framed.tpl';
        break;
}
require __DIR__.'/'.$modname;
