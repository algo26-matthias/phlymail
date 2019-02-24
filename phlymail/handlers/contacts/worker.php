<?php
/**
 * Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.0 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

header('Content-Type: text/javascript; charset=UTF-8');
$mytask = (isset($_REQUEST['what']) && $_REQUEST['what']) ? $_REQUEST['what'] : false;
switch ($mytask) {
case 'rename_folder':
case 'folder_delete':
case 'folder_create':
case 'folder_empty':
case 'folder_resync':
    // Tell the setup module to return right after doing the operation without
    // generating output on its own
    $_PM_['tmp']['setup']['no_output'] = true;
    // Use groups manager here
    require_once __DIR__.'/setup.folders.php';
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'")'.LF;
    } else { // No errors - force reload of the folder list to reflect changes done
        echo 'flist_refresh("contacts");'.LF.'if (parent.CurrentHandler == "contacts") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
case 'contact_delete':
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_delete_contact']) {
        return;
    }
case 'contact_visibility':
    if ($_REQUEST['what'] == 'contact_visibility' && !$_SESSION['phM_privs']['all']
            && (!$_SESSION['phM_privs']['contacts_update_contact'] || !$_SESSION['phM_privs']['contacts_make_contact_global'])) {
        return;
    }
    // Tell the setup module to return right after doing the operation without
    // generating output on its own
    $_PM_['tmp']['setup']['no_output'] = true;
    // Include the setup module and let it hanlde the operation
    require_once __DIR__.'/setup.contacts.php';
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'");'.LF;
    } else { // No errors - force reload of the "inbox" to reflect changes done
        echo 'parent.frames.PHM_tl.flist_refresh("contacts");'.LF.'if (CurrentHandler == "contacts") parent.frames.PHM_tr.refreshlist();'.LF;
    }
    exit;
    break;
}
