<?php
/**
 * Read user choices and populate $_PM_ with them
 * User choices take precedence
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.3 2012-09-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$choices = $DB->get_usr_choices($_SESSION['phM_uid']);
// Prevent setting choices, which are invalid (no longer existant themes and the like)
if (isset($choices['core'])) {
    if (isset($choices['core']['theme_name']) && !file_exists($_PM_['path']['theme'].'/'.$choices['core']['theme_name'])) {
        unset($choices['core']['theme_name']);
    }
    if (isset($choices['core']['mobile_theme_name']) && !file_exists($_PM_['path']['theme'].'/'.$choices['core']['mobile_theme_name'])) {
        unset($choices['core']['mobile_theme_name']);
    }
    if (isset($choices['core']['language']) && !file_exists($_PM_['path']['message'].'/'.$choices['core']['language'].'.php')) {
        unset($choices['core']['language']);
    }
}
// Storing the autoMarkRead value changed over time, first only the time value was stored,
// setting it to "0" meant "don't mark mails read automatically".
// Now there's both a switch AND a time value, effectively allowing to mark mails read instantly
if (isset($choices['core']['automarkread']) && !isset($choices['core']['automarkread_time'])) {
    $choices['core']['automarkread_time'] = $choices['core']['automarkread'];
    $choices['core']['automarkread'] = ($choices['core']['automarkread_time'] == 0) ? 0 : 1;
}
// Merge together
$_PM_ = merge_PM($_PM_, $choices);
