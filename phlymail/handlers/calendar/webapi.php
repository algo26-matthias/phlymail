<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @subpackage Import / Export
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// This is an XNA request
if (!empty($XNA)) {
    if ($load == 'export') { // Security measure, since other things are not supported right now
        $PHM_CAL_EX_DO = 'export';
    } else {
        $PHM_CAL_EX_DO = null;
    }
    $action = json_decode($action, true);
    define('PHM_API_UID', $action['uid']);
    // Apply permission checks, read settings for user
    if (isset($DB->features['permissions']) && $DB->features['permissions']) {
        $_phM_privs = $DB->get_user_permissions(PHM_API_UID);
        $_phM_privs['all'] = false;
    } else {
        $_phM_privs['all'] = true;
    }
    $PHM_CAL_EX_GROUP = $action['g'];
    $PHM_CAL_EX_FORMAT = $action['f'];

} else { // Normal invocation through HTTP AUTH
    $PHM_CAL_EX_DO = 'export';
    $PHM_CAL_EX_GROUP = isset($_REQUEST['g']) ? intval($_REQUEST['g']) : 0;
    $PHM_CAL_EX_FORMAT = isset($_REQUEST['f']) ? basename($_REQUEST['f']) : 'ICS';

}
if ($PHM_CAL_EX_GROUP == 0) { // Obey exclusion of groups marked accordingly
    $PHM_CAL_EX_QUERYTYPE = 'sync';
}
require_once(__DIR__.'/functions.php');
require_once(__DIR__.'/exchange.php');
