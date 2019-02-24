<?php
/**
 * phlyMail Config Skin handler
 * @package phlyMail Nahariya 4.0+
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 3.5.5 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$WP_mode['content_type'] = 'text/html';

// Use Font Encoding from language file
if (!isset($WP_skin['metainfo'])) $WP_skin['metainfo'] = false;
$WP_skin['metainfo'] .= '<meta http-equiv="content-type" content="'.$WP_mode['content_type'].'; charset=utf-8">'.LF;
$WP_skin['version'] = 'phlyMail';
if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
    $WP_skin['version'] = $_PM_['core']['provider_name'];
} elseif (file_exists($_PM_['path']['conf'].'/build.name')) {
    $WP_skin['version'] = file_get_contents($_PM_['path']['conf'].'/build.name');
}
$WP_skin['version'] .= ' Config';
$WP_skin['bidi-dir'] = isset($WP_msg['html_bidi']) ? $WP_msg['html_bidi'] : 'ltr';
$WP_skin['currbuild'] = file_get_contents($_PM_['path']['conf'].'/current.build');

// Decide, which main template to process
if (isset($WP_once['load_tpl_auth'])) {
    $t_skin = new phlyTemplate(CONFIGPATH.'/templates/auth.tpl');
} else {
    if (isset($outer_template) && $outer_template) {
        $t_skin = new phlyTemplate(CONFIGPATH.'/templates/'.basename($outer_template));
    } else {
        $t_skin = new phlyTemplate(CONFIGPATH.'/templates/main.tpl');
    }
    $t_skin->assign(array
            ('link_logout' => PHP_SELF.'?'.htmlspecialchars(give_passthrough(1).'&action=logout')
            ,'msg_logout' => $WP_msg['logout']
            ,'menu' => $Menu
            ));
}
$t_skin->assign(array
        ('version' => $WP_skin['version']
        ,'metainfo' => $WP_skin['metainfo']
        ,'confpath' => CONFIGPATH
        ,'phlymail_content' => $tpl
        ,'scheme' => (isset($WP_conf['scheme']) && file_exists(CONFIGPATH.'/schemes/'.$WP_conf['scheme'].'.css')) ? $WP_conf['scheme'] : 'default'
        ,'link_frontend' => PHP_SELF.'?'.htmlspecialchars(give_passthrough(1).'&action=logout&redir=index')
        ,'msg_frontend' => $WP_msg['go_frontend']
        ,'skin_path' => CONFIGPATH
        ,'provider_name' => $WP_skin['version']
        ,'frontend_path' => $_PM_['path']['frontend']
        ,'bidi-direction' => $WP_skin['bidi-dir']
        ,'iso_language' => $WP_msg['language']
        ,'current_build' => $WP_skin['currbuild']
        ));
header('Content-Type: text/html; charset="UTF-8"');
$t_skin->display();
