<?php
/**
 * Called by core on logout
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Hanlder Files
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if ((!isset($_PM_['core']['logout_emptytrash']) || !$_PM_['core']['logout_emptytrash'])) {
    return;
}
$FS = new handler_files_driver($_SESSION['phM_uid']);
$FS->init_folders(false);
if (isset($_PM_['core']['logout_emptytrash']) && $_PM_['core']['logout_emptytrash']) {
    $FS->delete_item(false, $FS->get_folder_id_from_path('waste', true), false);
}
unset($FS);
