<?php
/**
 * Handle display of main phlyMail window
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2003-2014 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.3 2014-11-18 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// Build up links
$link_base = PHP_SELF.'?'.give_passthrough(1);
$tpl = new phlyTemplate($_PM_['path']['theme'].'/main.tpl');
$t_core_js = new phlyTemplate($_PM_['path']['templates'].'core.main.tpl.js');

// Store some settings in cookies so they are available before login
if (!empty($_PM_['core']['language'])) {
    setcookie('phlyMail_Language', $_PM_['core']['language'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}
if (!empty($_PM_['core']['theme_name'])) {
    setcookie('phlyMail_Theme', $_PM_['core']['theme_name'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}
if (!empty($_PM_['core']['mobile_theme_name'])) {
    setcookie('phlyMail_Mobile_Theme', $_PM_['core']['mobile_theme_name'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
}

// Reading in all available handlers, puttings them into session for faster access later on
if (!isset($_SESSION['phM_configured_mailboxes'])) {
    $_SESSION['phM_configured_mailboxes'] = array();
    foreach (parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php') as $name => $active) {
        if (!$active) {
            continue; // Handler not active
        }
        $_SESSION['phM_configured_mailboxes'][] = array('type' => $name, 'name' => ucfirst($name));
    }
}
// Include the delivery script, this technique allows for handlers to output anything. So they are
// not tied to outputting any fixed, theme based template
$handlerlist = array_merge(array(0 => array('type' => 'core', 'name' => 'Core')), $_SESSION['phM_configured_mailboxes']);
$scanned = $_SESSION['phM_uniqe_handlers'] = $_SESSION['WPs_Plugin'] = array();
$folder_props_height = 500;

foreach ($handlerlist as $handler) {
    if (in_array($handler['type'], $scanned, true)) {
        continue;
    }
    // Read all handlers' available privileges in case some are not set in the DB
    if (file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/configapi.php')) {
        require_once($_PM_['path']['handler'].'/'.$handler['type'].'/configapi.php');
        $call = 'handler_'.$handler['type'].'_configapi';
        $API = new $call($_PM_, $_SESSION['phM_uid']);
        if (in_array('check_user_installed', get_class_methods($call))) {
            $API->check_user_installed();
        }
        if (isset($DB->features['permissions']) && $DB->features['permissions']
                && in_array('get_perm_actions', get_class_methods($call))) {
            $perms = $API->get_perm_actions($WP_msg['language']);
            if (!empty($perms)) {
                // Init the non set
                foreach ($perms as $k => $v) {
                    if (!isset($_SESSION['phM_privs'][$handler['type'].'_'.$k])) {
                        $_SESSION['phM_privs'][$handler['type'].'_'.$k] = 0;
                    }
                }
            }
            // If the basic permission to see this handler is given, set and zero, this handler will not be further processed;
            if ($API->perm_handler_available && !$_SESSION['phM_privs'][$API->perm_handler_available]) {
                unset($API);
                continue;
            }
        }
        unset($API);
    }
    //
    // Fill session info about usable handlers
    $_SESSION['phM_uniqe_handlers'][$handler['type']] = array();
    // Read and populate top button bar
    if (file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/topbuttonbar.php')) {
        $_PM_['handler']['path'] = $_PM_['path']['handler'].'/'.$handler['type'];
        $_PM_['handler']['name'] = $handler['type'];
        $topbuttonbar = 'handler_'.$handler['type'].'_topbuttonbar';
        $t = new $topbuttonbar($_PM_);
        $tpl->assign('contextmenus', $t->get());
        unset($t);
        $scanned[] = $handler['type'];
    }
    if (file_exists($_PM_['path']['handler'].'/'.$handler['type'].'/plugin.php')) {
        require_once($_PM_['path']['handler'].'/'.$handler['type'].'/plugin.php');
        $data = parse_ini_file($_PM_['path']['handler'].'/'.$handler['type'].'/description.ini');
        if (isset($data['folder_props_size'])) {
            $folder_props_height += $data['folder_props_size'];
        }
        $_SESSION['WPs_Plugin'][$handler['type']]['path'] = $_PM_['path']['handler'].'/'.$handler['type'].'/plugin.php';
        $_SESSION['WPs_Plugin'][$handler['type']]['class'] = 'plugin_'.$handler['type'];
    }
}
unset($scanned);

if (!isset($_SESSION['phM_login_handler'])) {
    $_SESSION['phM_login_handler'] = isset($_PM_['core']['login_handler']) && $_PM_['core']['login_handler'] ? $_PM_['core']['login_handler'] : 'core';
    $_SESSION['phM_login_folder'] = isset($_PM_['core']['login_folder']) && $_PM_['core']['login_folder'] ? basename($_PM_['core']['login_folder']) : 0;
}

// Which elements should be shown
if (!isset($_PM_['customsize']['core_vieww_favourites']) || $_PM_['customsize']['core_vieww_favourites']) {
    $t_core_js->assign_block('showfavourites');
}
if (!isset($_PM_['customsize']['core_vieww_folderlist']) || $_PM_['customsize']['core_vieww_folderlist']) {
    $t_core_js->assign_block('showfolderlist');
}
if (!isset($_PM_['customsize']['core_vieww_namepane']) || $_PM_['customsize']['core_vieww_namepane']) {
    $t_core_js->assign_block('shownamepane');
}
$tpl->assign('javascript', $t_core_js);
$tpl->assign(array
        ('left_target' => $link_base.'&action=flist'
        ,'right_target' => 'about:blank'
        ,'link_logout' => htmlspecialchars($link_base.'&action=logout')
        ,'worker_target' => $link_base.'&action=worker'
        ,'passthrough' => htmlspecialchars(give_passthrough())
        ,'id' => time()
        ,'top_new' => $WP_msg['MainNew']
        ,'top_setup' => $WP_msg['alt_setup']
        ,'top_exchange' => $WP_msg['MainExchange']
        ,'top_getmsg' => $WP_msg['MainGetMsg']
        ,'top_view' => $WP_msg['MainView']
        ,'top_system' => $WP_msg['MainSystem']
        ,'loggedin_user' => $_SESSION['phM_username']
        ,'main_folderoverview' => $WP_msg['MainFolderOverview']
        ,'msg_statusloading' => $WP_msg['StatusLoading']
        ,'msg_statusready' => $WP_msg['StatusReady']
        ,'favfolders_loadurl' => $link_base.'&l=worker&h=core&what=favfolders_get'
        ,'favfolders_seturl' => $link_base.'&l=worker&h=core&what=favfolders_set'
        ,'favfolders_reorderurl' => $link_base.'&l=worker&h=core&what=favfolders_reorder'
        ,'customsize_url' => PHP_SELF.'?l=worker&h=core&'.give_passthrough(1).'&what=customsize'
        ,'folderlist_width' => (isset($_PM_['customsize']['core_folderlistwidth']) && $_PM_['customsize']['core_folderlistwidth']
                        && (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']))
                ? $_PM_['customsize']['core_folderlistwidth']
                : '20%'
        ,'msg_quotaokay' => $WP_msg['QuotaOkay']
        ,'msg_quotamedium' => $WP_msg['QuotaMedium']
        ,'msg_quotabad' => $WP_msg['QuotaExceeded']
        ,'msg_showpinboard' => $WP_msg['CorePinboard']
        ,'msg_logout' => $WP_msg['alt_logout']
        ,'msg_reallylogut' => $WP_msg['QReallyLogout']
        ,'core_prompt_logout' => intval(!isset($_PM_['core']['logout_showprompt']) || $_PM_['core']['logout_showprompt'])
        ,'folder_props_height' => $folder_props_height
        ));
// Some people have trouble with the vertical resizability of the preview window, so we got to allow switching this off
if (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) {
    $tpl->assign_block('allowresize');
}
