<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.9 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$modname = false;
switch ($load) {
    case 'ilist': $modname = 'mod.listmail.php'; $outer_template = 'maillist.tpl'; break;
    case 'flist': $modname = 'flist.php'; $outer_template = 'framed.tpl';break;
    case 'setup': $modname = 'mod.setup.php'; $outer_template = '3dframed.tpl'; break;
    case 'item': // mobile
        $modname = 'mod.read.php';
        break;
    case 'read': // desktop
        $modname = 'mod.read.php';
        $outer_template = (!empty($_REQUEST['preview'])) ? 'maillist.tpl'
                : (!empty($_REQUEST['print']) ? 'mailview.tpl' : '3dframed.tpl');
        break;
    case 'output': $modname = 'mod.output.php'; $outer_template = 'mailview.tpl'; break;
    case 'browse': $modname = 'mod.browse.php'; $outer_template = '3dframed.tpl'; break;
    case 'folderprops': $modname = 'folderprops.php'; $outer_template = '3dframed.tpl'; break;
    case 'fetcher.run': $modname = 'fetcher.runner.php'; break;
    case 'worker':
        $modname = 'worker.php';
        if (isset($_REQUEST['what']) && $_REQUEST['what'] == 'folder_export') {
            $outer_template = '3dframed.tpl';
        }
        break;
    case 'sendto': $modname = 'sendto.php'; $outer_template = '3dframed.tpl'; break;
}
if ($modname) {
    require dirname(__FILE__).'/'.$modname;
}
