<?php
/**
 * Handle display of main phlyMail window
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.8.1 2013-02-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// Build up links
$link_base = PHP_SELF.'?'.give_passthrough(1);
$tpl = new phlyTemplate($_PM_['path']['templates'].'core.home.tpl');

// Store some settings in cookies so they are available before login
if (!empty($_PM_['core']['language'])) {
    setcookie('phlyMail_Language', $_PM_['core']['language'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}
if (!empty($_PM_['core']['mobile_theme_name'])) {
    setcookie('phlyMail_Mobile_Theme', $_PM_['core']['mobile_theme_name'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}

// Reading in all available handlers, puttings them into session for faster access later on
if (!isset($_SESSION['phM_configured_mailboxes'])) {
    $_SESSION['phM_configured_mailboxes'] = array();
    foreach (parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php') as $name => $active) {
        if (!$active) continue; // Handler not active
        $_SESSION['phM_configured_mailboxes'][] = array('type' => $name, 'name' => ucfirst($name));
    }
}
// Iterate over handlers
$handlerlist = array_merge(array(0 => array('type' => 'core', 'name' => 'Core')), $_SESSION['phM_configured_mailboxes']);
$scanned = $_SESSION['phM_uniqe_handlers'] = $_SESSION['WPs_Plugin'] = array();

$t_hdl = $tpl->get_block('handler');
foreach ($handlerlist as $handler) {
    if (in_array($handler['type'], $scanned, true)) continue;
    // Read all handlers' available privileges in case some are not set in the DB
    if (isset($DB->features['permissions']) && $DB->features['permissions']
            && file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/configapi.php')) {
        require_once($_PM_['path']['handler'].'/'.$handler['type'].'/configapi.php');
        $call = 'handler_'.$handler['type'].'_configapi';
        if (in_array('get_perm_actions', get_class_methods($call))) {
            $API = new $call($_PM_, $_SESSION['phM_uid']);
            $perms = $API->get_perm_actions($WP_msg['language']);
            if (!empty($perms)) {
                // Init the non set
                foreach ($perms as $k => $v) {
                    if (!isset($_SESSION['phM_privs'][$handler['type'].'_'.$k])) $_SESSION['phM_privs'][$handler['type'].'_'.$k] = 0;
                }
            }
            // If the basic permission to see this handler is given, set and zero this handler will not be further processed;
            if ($API->perm_handler_available && !$_SESSION['phM_privs'][$API->perm_handler_available]) {
                unset($API);
                continue;
            }
            unset($API);
        }
    }
    //
    // Fill session info about usable handlers
    $_SESSION['phM_uniqe_handlers'][$handler['type']] = array();
    $scanned[] = $handler['type'];

    // Read the regular top button bar script, this e.g. names the handler
    if (file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/topbuttonbar.php')) {
        $_PM_['handler']['path'] = $_PM_['path']['handler'].'/'.$handler['type'];
        $_PM_['handler']['name'] = $handler['type'];
        // It seems to be senseless to instantiate the class and right away destroy it again:
        // The constructor sets a session variable, which holds the i18n name of the handler
        $call = 'handler_'.$handler['type'].'_topbuttonbar';
        $API = new $call($_PM_);
        unset($API);
    }

    if (file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/api.php')) {
        $_PM_['handler']['path'] = $_PM_['path']['handler'].'/'.$handler['type'];
        $_PM_['handler']['name'] = $handler['type'];
        require_once($_PM_['path']['handler'].'/'.$handler['type'].'/api.php');
        $call = 'handler_'.$handler['type'].'_api';
        if (!in_array('give_folderlist', get_class_methods($call))
                && !in_array('give_menu_options', get_class_methods($call))) {
            continue;
        }
        $API = new $call($_PM_, $_SESSION['phM_uid']);
        if (in_array('give_folderlist', get_class_methods($call))) {
            $folders = $API->give_folderlist();
            if (!empty($folders)) {
                $_SESSION['phM_folderCache'][$handler['type']] = $folders;
                $t_hdl->assign(array
                        ('type' => $handler['type']
                        ,'name' => $_SESSION['phM_uniqe_handlers'][$handler['type']]['i18n']
                        ,'link' => PHP_SELF.'?a=flist&amp;h='.$handler['type'].'&amp;co=&amp;'.give_passthrough()
                        ));
                $tpl->assign('handler', $t_hdl);
                $t_hdl->clear();
            }
        }
        unset($API);
    }
}
unset($scanned);

$t_f = $tpl->get_block('favorite');
$FF = new DB_Controller_Favfolder();
foreach ($FF->getList($_SESSION['phM_uid']) as $k => $v) {
    $t_f->assign(array
            ('icon' => $_SESSION['phM_folderCache'][$v['handler']][$v['fid']]['icon']
            ,'name' => $_SESSION['phM_folderCache'][$v['handler']][$v['fid']]['foldername']
            ,'link' => PHP_SELF.'?a=ilist&amp;h='.$v['handler'].'&amp;f='.basename($v['fid']).'&amp;'.give_passthrough()
            ));
    $tpl->assign('favorite', $t_f);
    $t_f->clear();
}

$tpl->assign(array
        ('link_logout' => htmlspecialchars($link_base.'&a=logout')
        ,'link_setup' => htmlspecialchars($link_base.'&a=setup')
        ,'link_new' => htmlspecialchars($link_base.'&a=new')
        ,'PHP_SELF' => PHP_SELF
        ,'id' => time()
        ,'loggedin_user' => $_SESSION['phM_username']
        ,'core_prompt_logout' => intval(!isset($_PM_['core']['logout_showprompt']) || $_PM_['core']['logout_showprompt'])
        ));
