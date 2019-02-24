<?php
/**
 * loader.php - central loader for modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2015-04-14 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (file_exists($_PM_['path']['handler'].'/contacts/lang.'.$WP_msg['language'].'.php')) {
    require_once $_PM_['path']['handler'].'/contacts/lang.'.$WP_msg['language'].'.php';
} else {
    require_once $_PM_['path']['handler'].'/contacts/lang.de.php';
}
if (isset($_PM_['core']['contacts_nopublics']) && $_PM_['core']['contacts_nopublics']) {
    define('CONTACTS_PUBLIC_CONTACTS', false);
} elseif (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_see_global_contacts']) {
    define('CONTACTS_PUBLIC_CONTACTS', false);
} else {
    define('CONTACTS_PUBLIC_CONTACTS', true);
}
define('CONTACTS_VISIBILITY_MODE', CONTACTS_PUBLIC_CONTACTS ? 2 : 0);

$modname = false;
if (!defined('PHM_MOBILE')) {
    $outer_template = '3dframed.tpl';
    if ($load == 'preview') {
        $outer_template = 'framed.tpl';
    }
    if ($load == 'ilist') {
        $outer_template = 'maillist.tpl';
    }
}
switch($load) {
    case 'ilist':        $modname = 'main.php'; break;
    case 'flist':        $modname = 'flist.php'; break;
    case 'edit_vcf':
    case 'edit_contact': $modname = 'edit_contact.php'; break;
    case 'item':
    case 'preview':      $modname = 'preview.php'; break;
    case 'edit_groups':  $modname = 'edit_groups.php'; break;
    case 'exchange':     $modname = 'exchange.php'; break;
    case 'apiselect':    $modname = 'apiselect.php'; break;
    case 'worker':       $modname = 'worker.php'; break;
    case 'folderprops':  $modname = 'folderprops.php'; break;
    case 'sendto':       $modname = 'sendto.php'; break;
    case 'setup':        $modname = 'setup.php'; break;
}
if ($modname) {
    require __DIR__.'/'.$modname;
}
