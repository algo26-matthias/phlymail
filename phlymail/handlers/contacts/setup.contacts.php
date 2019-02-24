<?php
/**
 * Setup Module contact operations
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.6 2015-04-14 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
$error = false;
$update_contactlist = false;

if (!empty($_REQUEST['what']) && !empty($_REQUEST['contact'])) {
    $contacts = $_REQUEST['contact'];
    if (!is_array($contacts)) {
        $contacts = array(0 => $contacts);
    }
    switch ($_REQUEST['what']) {
    case 'contact_delete':
        if (empty($_SESSION['phM_privs']['all']) && empty($_SESSION['phM_privs']['contacts_delete_contact'])) {
            return;
        }
        foreach ($contacts as $contact) {
            $ret = $cDB->delete_contact($contact);
        }
        $update_contactlist = true;
        break;
    case 'contact_visibility':
        if (empty($_SESSION['phM_privs']['all'])
                && (empty($_SESSION['phM_privs']['contacts_update_contact'])
                        || empty($_SESSION['phM_privs']['contacts_make_contact_global']))) {
            return;
        }
        foreach ($contacts as $contact) {
            $ret = $cDB->set_contact_visibility($contact, $_REQUEST['visible']);
        }
        $update_contactlist = true;
        break;
    }
}
// This module might be called from a background task, thus generating no output
if (isset($_PM_['tmp']['setup']['no_output'])) {
    return;
}
