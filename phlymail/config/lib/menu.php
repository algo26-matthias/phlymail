<?php
/**
 * @package phlyMail Nahariya 4.0+
 * @subpackage Config
 * @copyright 2003-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2012-05-02 
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

$global_menu_itms = array
        (0  => array('name' => $WP_msg['MenuHome'],     'action' => 'home',                   'type' => 'i')
        ,1  => array('name' => $WP_msg['setgen'],       'action' => 'general',                'type' => 'i')
        ,2  => array('name' => $WP_msg['setadv'],       'action' => 'advanced',               'type' => 'i')
        ,3  => array('name' => $WP_msg['setplugs'],     'action' => 'handlers',               'type' => 'i')
        ,4  => array('name' => $WP_msg['setquota'],     'action' => 'quotas',                 'type' => 'i')
        ,5  => array('name' => $WP_msg['setuser'],      'action' => 'users',                  'type' => 'i')
        ,6  => array('name' => $WP_msg['groups'],       'action' => 'groups',                 'type' => 'i')
        ,7  => array('name' => $WP_msg['setSec'],       'action' => 'security',               'type' => 'i')
        ,8  => array('name' => $WP_msg['setJunk'],      'action' => 'junk',                   'type' => 'i')
        ,9  => array('name' => $WP_msg['setDB'],        'action' => 'driver',                 'type' => 'i')
        ,10 => array('name' => $WP_msg['setAU'],        'action' => 'AU',                     'type' => 'i')
        ,11 => array('name' => $WP_msg['setregnow'],    'action' => 'regnow',                 'type' => 'i')
        ,12 => array('name' => $WP_msg['diagnosis'],    'action' => 'diag',                   'type' => 'i')
        ,13 => array('name' => $WP_msg['MenuConfig'],   'action' => '-',                      'type' => 'm')
        ,14 => array('name' => $WP_msg['MenuSettings'], 'action' => 'config',                 'type' => 's')
        ,15 => array('name' => $WP_msg['MenuAdmins'],   'action' => 'config.users',           'type' => 's')
        ,16 => array('name' => 'SMS + Fax',             'action' => 'sms',                    'type' => 'i')
        ,17 => array('name' => $WP_msg['MenuGlobAdr'],  'action' => '-',                      'type' => 'm')
        ,18 => array('name' => $WP_msg['contacts'],     'action' => 'gcontacts',              'type' => 's')
        ,19 => array('name' => $WP_msg['groups'],       'action' => 'ggroups',                'type' => 's')
        ,20 => array('name' => $WP_msg['exchange'],     'action' => 'gconexchange',           'type' => 's')
        ,21 => array('name' => $WP_msg['MenuAPI'],      'action' => '-',                      'type' => 'm')
        ,22 => array('name' => $WP_msg['setuser'],      'action' => 'config.api',             'type' => 's')
        );
$types = array('i' => 'item', 's' => 'subitem', 'm' => 'menu');
// Support feature to disable global contacts completely, which is interesting for hosters
if (isset($_PM_['core']['contacts_nopublics']) && $_PM_['core']['contacts_nopublics']) {
    unset($global_menu_itms[21], $global_menu_itms[22], $global_menu_itms[23], $global_menu_itms[24]);
}

$Menu = new phlyTemplate(CONFIGPATH.'/templates/menu.tpl');
$L = $Menu->get_block('line');
foreach ($global_menu_itms as $k) {
    if (!isset($k['action'])) $k['action'] = '';
	if (!isset($k['screen'])) $k['screen'] = '';
    $C = $L->get_block($types[$k['type']]);
    $C->assign(array('link_target' => htmlspecialchars($link_base.$k['action'].($k['screen'] != '' ? '&screen='.$k['screen'] : '')), 'msg_line' => $k['name']));
    if ($action == $k['action'] && $screen == $k['screen']) {
        $C->assign_block('is_active');
    }
    $L->assign($types[$k['type']], $C);
    $L->assign('confpath', CONFIGPATH);
    $Menu->assign('line', $L);
    $L->clear();
}

$moddir = CONFIGPATH.'/modules';
// Nothing more to do - no additional modules here
if (!file_exists($moddir) && !is_dir($moddir)) return;

$M = opendir($moddir);
while (false !== ($sub = readdir($M))) {
    if ('.' == $sub) continue;
    if ('..' == $sub) continue;
	if (!is_dir($moddir.'/'.$sub)) continue;
	if (file_exists($moddir.'/'.$sub.'/menu.ini.php')
	       && is_readable($moddir.'/'.$sub.'/menu.ini.php')) {
	    $submen = parse_ini_file($moddir.'/'.$sub.'/menu.ini.php', true);
	    if (isset($submen['main']['active']) && $submen['main']['active'] != 1) continue;
	    if (file_exists($moddir.'/'.$sub.'/lang.'.$WP_conf['language'].'.php')) {
	        require_once($moddir.'/'.$sub.'/lang.'.$WP_conf['language'].'.php');
	    } else {
	        require_once($moddir.'/'.$sub.'/lang.en.php');
	    }
	    foreach ($submen as $k) {
            $k['name'] = $modmsg[$k['name']];
	        if (!isset($k['action'])) $k['action'] = '';
	        if (!isset($k['screen'])) $k['screen'] = '';
	        $C = $L->get_block($types[$k['type']]);
	        $C->assign(array
	               ('link_target' => htmlspecialchars($link_base.$k['action'].($k['screen'] != '' ? '&screen='.$k['screen'] : '').'&module='.$sub)
	               ,'msg_line' => $k['name']
	               ));
	        if (isset($_REQUEST['module']) && $_REQUEST['module'] == $sub && $screen == $k['screen']) {
	            $C->assign_block('is_active');
	        }
	        $L->assign($types[$k['type']], $C);
	        $L->assign('confpath', CONFIGPATH);
	        $Menu->assign('line', $L);
	        $L->clear();
	        if (isset($k['usermod'])) {
	            $_PM_['useredit'][] = array($modmsg[$k['usermod']], $link_base.'view&module='.$sub.'&whattodo=edituser');
	        }
	    }
	}
}
closedir($M);
