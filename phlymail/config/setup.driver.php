<?php
/**
 * Setup Database Driver(s)
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2013-02-08 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['driver_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.driver.tpl');
if ('chgdrv' == $whattodo) {
    if (isset($_SESSION['phM_perm_write']['driver_']) || $_SESSION['phM_superroot']) {
        $truth = save_config($_PM_['path']['conf'].'/choices.ini.php',array(
                'core' => array('database' => basename($_REQUEST['new_driver']))
                ));
        if ($truth) {
            $error = '%h%optssaved%';
            $_PM_['core']['database'] = basename($_REQUEST['new_driver']);
        } else {
            $error = '%h%optsnosave%';
        }
    } else {
        $error = '%h%no_access%';
    }
}

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.driver.tpl');
$tpl->assign(array(
        'target_link' => htmlspecialchars($link_base.'driver&whattodo=chgdrv'),
        'link_base' => htmlspecialchars($link_base),
));
if (!empty($error)) {
    $tpl->fill_block('error', 'error', $error);
}
$d_ = opendir($_PM_['path']['driver']);
while (false !== ($drivername = readdir($d_))) {
    if ($drivername == '.' || $drivername == '..') {
        continue;
    }
    if (!file_exists($_PM_['path']['driver'].'/'.$drivername.'/setup.php')) {
        continue;
    }
    $friendlyName = $drivername;
    if (file_exists($_PM_['path']['driver'] . '/' . $drivername . '/driver.name')) {
        $friendlyName = file_get_contents($_PM_['path']['driver'] . '/' . $drivername . '/driver.name');
    }
    $drivers[$drivername] = $friendlyName;
}
closedir($d_);
sort($drivers);
switch (sizeof($drivers)) {
    case 0:
        $go_on = 0;
        $tpl->fill_block('one_no_driver', 'output', '-');
        break;
    case 1:
        $go_on = 1;
        $tpl->fill_block('one_no_driver', 'output', $drivers[0]);
        break;
    default:
        $go_on = 1;
        $tpl_d = $tpl->get_block('drivermenu');
        $tpl_l = $tpl_d->get_block('menuline');
        foreach ($drivers as $drivername => $friendlyName) {
            $tpl_l->assign(array('key' => $drivername, 'drivername' => $friendlyName));
            if ($drivername == $_PM_['core']['database']) {
                $tpl_l->assign_block('selected');
            }
            $tpl_d->assign('menuline', $tpl_l);
            $tpl_l->clear();
        }
        $tpl_d->assign('msg_save', $WP_msg['save']);
        $tpl->assign('drivermenu', $tpl_d);
        break;
}
if ($go_on == 0) {
    $tpl->assign('conf_output', '%h%SuDBnoDriver%');
} else {
    if (!file_exists($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.ini.php')) {
        $tpl->assign('conf_output', '%h%SuDBnotConfd%');
    }
    if (file_exists($_PM_['path']['driver'].'/'.$_PM_['core']['database'].'/setup.php')) {
        $_PM_['tmp']['driver_dir'] = $_PM_['path']['driver'].'/'.$_PM_['core']['database'];
        if (!empty($_REQUEST['save'])) {
            $WP_DBset_action = "do";
        }
        require_once($_PM_['tmp']['driver_dir'].'/setup.php');
        $tpl->assign('conf_output', $conf_output);
    } else {
        $tpl->assign('conf_output', str_replace('$1', $_PM_['core']['database'], $WP_msg['SuDBnoMod']));
    }
}
