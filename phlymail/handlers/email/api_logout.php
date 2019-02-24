<?php
/**
 * Called by core on logout
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2012-05-04 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
if ((!isset($_PM_['core']['logout_emptytrash']) || !$_PM_['core']['logout_emptytrash'])
        && (!isset($_PM_['core']['logout_emptyjunk']) || !$_PM_['core']['logout_emptyjunk'])) {
    return;
}
$STOR = new handler_email_driver($_SESSION['phM_uid']);
$STOR->init_folders(false);
if (isset($_PM_['core']['logout_emptytrash']) && $_PM_['core']['logout_emptytrash']) {
    foreach ($STOR->get_folder_id_from_path('waste', true, true) as $idx) {
        $STOR->delete_mail(false, $idx, false);
    }
}
if (isset($_PM_['core']['logout_emptyjunk']) && $_PM_['core']['logout_emptyjunk']) {
    foreach ($STOR->get_folder_id_from_path('junk', true, true) as $idx) {
        $STOR->delete_mail(false, $idx, false);
    }
}
unset($STOR);
