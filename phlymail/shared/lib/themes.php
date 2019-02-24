<?php
/**
 * phlyMail 4.x Theme handler
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.2 2015-02-25 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$WP_theme['version'] = 'phlyMail';
if (!isset($WP_theme['content_type'])) {
    $WP_theme['content_type'] = 'text/html';
}
if (!isset($WP_theme['metainfo'])) {
    $WP_theme['metainfo'] = '';
}
if (!isset($WP_theme['base_colour'])) {
    $WP_theme['base_colour'] = '';
}
if (!isset($WP_theme['icon_set'])) {
    $WP_theme['icon_set'] = 'black';
}
if (!isset($WP_theme['charset'])) {
    $WP_theme['charset'] = 'UTF-8';
}
$WP_theme['bidi-dir'] = isset($WP_msg['html_bidi']) ? $WP_msg['html_bidi'] : 'ltr';
if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
    $WP_theme['version'] = $_PM_['core']['provider_name'];
} elseif (file_exists($_PM_['path']['conf'].'/build.name')) {
    $WP_theme['version'] = file_get_contents($_PM_['path']['conf'].'/build.name');
}
$WP_theme['currbuild'] = file_get_contents($_PM_['path']['conf'].'/current.build');
// Load and fill template
if (defined('PHM_MOBILE')) {
    $t_theme = new phlyTemplate($_PM_['path']['templates'].$outer_mobile, $_PM_['path']['tplcache'].str_replace('/', '_', $outer_mobile));
    $t_theme->assign('phlymail_content', $tpl);
} elseif (isset($_PM_['temp']['load_tpl_auth'])) {
    $t_theme = new phlyTemplate($_PM_['path']['theme'].'/auth.tpl', $_PM_['path']['themecache'].'auth.tpl');
    $t_theme->assign('phlymail_content', $tpl);
    // Session cookie
    if (!isset($_PM_['auth']['session_cookie']) || $_PM_['auth']['session_cookie']) {
        $t_theme->fill_block('sessioncookie_on', 'msg_cookie_warning', $WP_msg['SessionCookieInfo']);
    }
    if (!empty($_PM_['core']['mobile_advertise']) && $t_theme->block_exists('mobile_advertise')) {
        $t_theme->assign_block('mobile_advertise');
    }

} elseif ($load || in_array ($action, array('flist', 'setup', 'new', 'exchange'))) {
    $t_theme = new phlyTemplate($_PM_['path']['theme'].'/'.$outer_template, $_PM_['path']['themecache'].str_replace('/', '_', $outer_template));
    $t_theme->assign('phlymail_content', $tpl);
} else {
    $t_theme = &$tpl;
}
$tpl_defaults = new phlyTemplate($_PM_['path']['templates'].'core.defaults.tpl');
$tpl_defaults->assign(array(
        'charset' => $WP_theme['charset'],
        'content_type' => $WP_theme['content_type']
        ));
$t_theme->assign('metainfo', $tpl_defaults->get());

$t_theme->assign(array
        ('version' => $WP_theme['version']
        ,'metainfo' => $WP_theme['metainfo']
        ,'PHP_SELF' => PHP_SELF
        ,'FULL_PHP_SELF' => PHM_SERVERNAME.PHP_SELF
        ,'root_path' => PHM_SERVERNAME.rtrim(dirname(PHP_SELF), DIRECTORY_SEPARATOR)
        ,'theme_path' => PHM_SERVERNAME.rtrim(dirname(PHP_SELF), DIRECTORY_SEPARATOR).'/'.$_PM_['path']['theme']
        ,'frontend_path' => PHM_SERVERNAME.rtrim(dirname(PHP_SELF), DIRECTORY_SEPARATOR).'/'.$_PM_['path']['frontend']
        ,'bidi-direction' => $WP_theme['bidi-dir']
        ,'iso_language' => $WP_msg['iso_language']
        ,'current_build' => $WP_theme['currbuild']
        ,'theme_base_colour' => $WP_theme['base_colour']
        ,'icon-set' => 'ui-iconset-'.$WP_theme['icon_set']
        ,'microtime' => microtime(true)
        ,'SID' => strlen(SESS_ID) ? SESS_NAME.'='.SESS_ID : ''
        ,'passthru' => give_passthrough(1)
        ));
header('ETag: "'.uniqid().'"');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()-10) . ' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', time()-10) . ' GMT');
header('Pragma: no-cache');
header('Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate');
header('Content-Type: '.$WP_theme['content_type'].'; charset="'.$WP_theme['charset'].'"');
$t_theme->display();
