<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.1 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (file_exists(__DIR__.'/lang.'.$WP_msg['language'].'.php')) {
    require_once __DIR__.'/lang.'.$WP_msg['language'].'.php';
} else {
    require_once __DIR__.'/lang.de.php';
}
require_once __DIR__.'/functions.php';
$modname = false;
$outer_template = '3dframed.tpl';
switch($load) {
    case 'worker': $modname = 'worker.php'; break;
    case 'ilist': $modname = 'main.php'; $outer_template = 'maillist.tpl'; break;
    case 'flist': $modname = 'flist.php'; break;
    case 'apiselect': $modname = 'apiselect.php'; break;
    case 'edit_event': $modname = 'edit_event.php'; break;
    case 'edit_task': $modname = 'edit_task.php'; break;
    case 'edit_groups': $modname = 'edit_groups.php'; break;
    case 'alert_event': $modname = 'alert_event.php'; break;
    case 'setup': $modname = 'setup.php'; break;
    case 'folderprops': $modname = 'folderprops.php'; break;
    case 'sendto': $modname = 'sendto.php'; break;
    case 'exchange': $modname = 'exchange.php'; break;
    case 'invitation': $modname = 'invitations.php'; break;
}
if ($modname) {
    require __DIR__.'/'.$modname;
}
