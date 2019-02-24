<?php
/**
 * DB setup module
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage phlyMail DB
 * @subpackage Driver MySQL
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.6 2015-02-03 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

require_once(
        file_exists($_PM_['tmp']['driver_dir'].'/lang.'.basename($WP_msg['language']).'.php')
            ? $_PM_['tmp']['driver_dir'].'/lang.'.basename($WP_msg['language']).'.php'
            : $_PM_['tmp']['driver_dir'].'/lang.en.php'
        );
if (!isset($_PM_['core']['file_umask'])) $_PM_['core']['file_umask'] = 0755;

if (!isset($WP_DBset_action)) {
    $WP_DBset_action = (isset($_REQUEST['WP_DBset_action'])) ? $_REQUEST['WP_DBset_action'] : false;
}
$WP_DB = (isset($_REQUEST['WP_DB'])) ? $_REQUEST['WP_DB'] : array();
$skeleton = file($_PM_['tmp']['driver_dir'].'/conf.skel');
$WPDB['conf_file'] = $_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.ini.php';
if (!isset($conf_output)) {
    $conf_output = '';
}

if ('do' == $WP_DBset_action) {
    $newDB = array();
    foreach ($skeleton as $v) {
        list ($v) = explode(';;', trim($v), 4);
        $newDB[$v] = $WP_DB[$v];
    }
    basics::save_config($WPDB['conf_file'], $newDB, true, $_PM_['core']['file_umask']);
    // Treat the installation process individually
    if (defined('_IN_INSTALLER_')) {
        return;
    }
    // Normal procedure
    $WP_DBset_action = false;
}
if (!$WP_DBset_action) {
    if (empty($WP_DB) && file_exists($WPDB['conf_file']) && is_readable($WPDB['conf_file'])) {
        $WP_DB = parse_ini_file($WPDB['conf_file']);
    }
    $conf_output .= '<p>'.$WP_drvmsg['HeadGen'].'</p><br /><table>'.LF;
    foreach ($skeleton as $v) {
        $line = explode(';;', trim($v), 5);
        if (!isset($WP_DB[$line[0]])) {
            if (isset($DB) && is_object($DB) && isset($DB->DB[$line[0]])) {
                $WP_DB[$line[0]] = $DB->DB[$line[0]];
            } else {
                $WP_DB[$line[0]] = isset($line[3]) ? $line[3] : '';
            }
        }
        $required = ($line[4] == 1 && !defined('DB_SETUP_NOT_REQUIRED')) ? ' required' : '';
        $conf_output .= '<tr><td class="l">'.$WP_drvmsg[$line[0]].'</td><td class="l">&nbsp;'
                .'<input type='.$line[1].$required.' name="WP_DB['.$line[0].']" value="'.$WP_DB[$line[0]].'" size='.$line[2].' />'
                .'</td></tr>';
    }
    $conf_output .= '</table>';
}
