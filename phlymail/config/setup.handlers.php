<?php
/**
 * Manage existant handlers - (un)install, setup, ...
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2005-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['handlers_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

$base_link = htmlspecialchars(PHP_SELF.'?action=handlers&'.give_passthrough());
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;
$mode = (isset($_REQUEST['mode']) && $_REQUEST['mode']) ? $_REQUEST['mode'] : false;
$handler = (isset($_REQUEST['handler']) && $_REQUEST['handler']) ? $_REQUEST['handler'] : false;
$uid = (isset($_REQUEST['uid']) && $_REQUEST['uid']) ? $_REQUEST['uid'] : false;

if ('install' == $mode && $handler) {
    $call = 'handler_'.$handler.'_configapi';
    if (!isset($_PM_['handlers'][$handler]) || !$_PM_['handlers'][$handler]) {
        // Look for an installation API call available
        if (file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
            require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
            if (in_array('handler_install', get_class_methods($call))) {
                $API = new $call($_PM_, 0);
                $state = $API->handler_install();
                unset($API);
            }
            if (in_array('create_user', get_class_methods($call))) {
                foreach ($DB->get_usridx() as $uid => $username) {
                    $API = new $call($_PM_, $uid);
                    $state = $API->create_user();
                    unset($API);
                }
            }
        }
        $_PM_['handlers'][$handler] = 1;
        basics::save_config($_PM_['path']['conf'].'/active_handlers.ini.php', $_PM_['handlers']);
    }
}

if ('uninstall' == $mode && $handler) {
    if (isset($_PM_['handlers'][$handler]) && $_PM_['handlers'][$handler]) {
        $call = 'handler_'.$handler.'_configapi';
        // Look for an uninstallation API call available
        if (file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
            require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
            if (in_array('remove_user', get_class_methods($call))) {
                foreach ($DB->get_usridx() as $uid => $username) {
                    $API = new $call($_PM_, $uid);
                    $state = $API->remove_user();
                    unset($API);
                }
            }
            if (in_array('handler_uninstall', get_class_methods($call))) {
                $API = new $call($_PM_, 0);
                $state = $API->handler_uninstall();
                unset($API);
            }
        }
        $_PM_['handlers'][$handler] = 0;
        basics::save_config($_PM_['path']['conf'].'/active_handlers.ini.php', $_PM_['handlers']);
    }
}

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.handlers.tpl');
$handlers = array();
$d = opendir($_PM_['path']['handler']);
while (false !== ($handler = readdir($d))) {
    if ($handler == '.' or $handler == '..') continue;
    if (!is_dir($_PM_['path']['handler'].'/'.$handler)) continue;
    $handlers[] = $handler;
}
closedir($d);
sort($handlers);
$tpl->assign(array
        ('msg_optactive' => $WP_msg['HDLStatus']
        ,'msg_opthandler' => $WP_msg['HDLHandler']
        ,'msg_optdescr' => $WP_msg['optdescr']
        ,'about' => $WP_msg['AboutConfHDL']
        ));
$t_ml = $tpl->get_block('modline');
foreach ($handlers as $handler) {
    if (file_exists($_PM_['path']['handler'].'/'.$handler.'/description.ini')) {
        $properties = parse_ini_file($_PM_['path']['handler'].'/'.$handler.'/description.ini', true);
    } else {
        $properties = array
                ('properties' => array('name' => $handler, 'is_core' => true, 'icon' => false, 'config_setup' => false, 'version' => '')
                ,'description' => array('de' => '-', 'en' => '-')
                );
    }
    if (!isset($_PM_['handlers'][$handler]) || !$_PM_['handlers'][$handler]) {
        $t_ml->fill_block('notactive', array('confpath' => CONFIGPATH, 'title' => $WP_msg['optinactive']));
        // Allow to install handlers added to the system after its installation
        $t_ml->fill_block('install', array
                ('link' => $base_link.'&amp;mode=install&amp;handler='.$handler
                ,'msg_install' => $WP_msg['HDLInstall']
                ));
    } else {
        $t_ml->fill_block('isactive', array('confpath' => CONFIGPATH, 'title' => $WP_msg['optactive']));
        // Only certain handlers, marked as "not core" might get uninstalled
        if (isset($properties['properties']['is_core']) && !$properties['properties']['is_core']) {
            $t_ml->fill_block('uninstall', array
                    ('link' => $base_link.'&amp;mode=uninstall&amp;handler='.$handler
                    ,'msg_uninstall' => $WP_msg['HDLUninstall']
                    ));
        // All others stay, where they are :)
        } else {
            $t_ml->assign_block('noinstall');
        }
        // In case the handler allows a detailed setup within the module manager
        if (isset($properties['properties']['config_setup']) && $properties['properties']['config_setup']) {
            $t_ml->fill_block('configure', array
                    ('link' => $base_link.'&amp;mode=config&amp;handler='.$handler
                    ,'msg_configure' => $WP_msg['HDLConfigure']
                    ));
        } else {
            $t_ml->assign_block('noconfig');
        }
    }
    $t_ml->assign(array
            ('plugname' => $properties['properties']['name']
            ,'version' => (isset($properties['properties']['version']) && $properties['properties']['version'])
            		? 'v'.plugversionformat($properties['properties']['version']).'&nbsp;'
            		: ''
            ,'description' => (isset($properties['description'][$WP_conf['language']]))
                    ? $properties['description'][$WP_conf['language']]
                    : (isset($properties['description']['de']) ? $properties['description']['de'] : $properties['description']['en'])
            ));
    $tpl->assign('modline', $t_ml);
    $t_ml->clear();
}

function plugversionformat($raw)
{
	if (strlen($raw) == 3) {
		return substr($raw, 0, 1).'.'.substr($raw, 1, 2);
	} elseif (strlen($raw) == 5) {
		return substr($raw, 0, 1).'.'.substr($raw, 1, 2).'.'.substr($raw, 3, 2);
	} elseif (strlen($raw) == 4) {
		return substr($raw, 0, 2).'.'.substr($raw, 2, 2);
	} elseif (strlen($raw) == 6) {
		return substr($raw, 0, 2).'.'.substr($raw, 2, 2).'.'.substr($raw, 4, 2);
	} else {
		return $raw;
	}
}
