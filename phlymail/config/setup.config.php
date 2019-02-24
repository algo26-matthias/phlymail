<?php
/**
 * Setup Config Area
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.4 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['config_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['config_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $tokvar = array
            ('allow_ip' => (isset($_REQUEST['WPnewallowip'])) ? (bool) $_REQUEST['WPnewallowip'] : false
            ,'language' => $_REQUEST['WPnewlanguage']
            ,'scheme' => $_REQUEST['WPnewcolscheme']
            );
    $WP_return = $WP_msg['optsnosave'];
    $truth = basics::save_config($_PM_['path']['conf'].'/config.choices.ini.php', $tokvar);
    if ($truth) {
        $WP_return = $WP_msg['optssaved'];
        $WPnewallowedips = '';
        if (isset($_REQUEST['WPnewallowedips']) && $_REQUEST['WPnewallowedips']) {
            $WPnewallowedips = preg_replace
                    (array('!,!', '!\ !', '!('.LF.'|'.CRLF.')+!m', '!^'.LF.'!', '!'.LF.'$!')
                    ,array(LF, LF, LF, '', '')
                    ,$_REQUEST['WPnewallowedips']
                    );
        }
        file_put_contents($_PM_['path']['conf'].'/config.allowed_ips.php', '<?php die(); ?>'.LF.$WPnewallowedips);
    }
    header('Location: '.$link_base.'config&WP_return='.urlencode($WP_return));
    exit();
}
$allowed_ips = '';
if (file_exists($_PM_['path']['conf'].'/config.allowed_ips.php')
        && is_readable($_PM_['path']['conf'].'/config.allowed_ips.php')) {
    $allowed_ips = str_replace('<?php die(); ?>', '', file_get_contents($_PM_['path']['conf'].'/config.allowed_ips.php'));
}

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.config.tpl');
if (isset($WP_return)) $tpl->fill_block('return', 'WP_return', $WP_return);
$tpl->assign(array
      ('target_link' => htmlspecialchars($link_base.'config&whattodo=save')
      ,'head_text' => $WP_msg['SuHeadConf']
      ,'leg_misc' => $WP_msg['LegMisc']
      ,'msg_scheme' => $WP_msg['ColScheme']
      ,'msg_language' => $WP_msg['optlang']
      ,'leg_allow_ip' => $WP_msg['LegConfAllowIPs']
      ,'about_allow_ip' => $WP_msg['AboutConfAllowIPs']
      ,'msg_allow_ip' => $WP_msg['ConfAllowIPs']
      ,'allowedips' => $allowed_ips
      ,'msg_save' => $WP_msg['save']
      ));
if (isset($WP_conf['allow_ip']) && $WP_conf['allow_ip']) $tpl->assign_block('allowip');
// Read Schemes available
$d_ = opendir(CONFIGPATH.'/schemes/');
while (false !== ($file = readdir($d_))) {
    if ($file == '.' || $file == '..') continue;
    if (!preg_match('/\.css$/i', trim($file))) continue;
    $file = preg_replace('/\.css$/i', '', trim($file));
    $files[] = $file;
}
closedir($d_);
sort($files);
$t_s = $tpl->get_block('colschmopt');
foreach ($files as $file) {
    $t_s->assign('key', $file);
    $t_s->assign('val', $file);
    if (isset($WP_conf['scheme']) && $file == $WP_conf['scheme']) $t_s->assign_block('sel');
    $tpl->assign('colschmopt', $t_s);
    $t_s->clear();
}
unset($files);

// Read Languages available
$d_ = opendir(CONFIGPATH.'/messages/');
while (false !== ($file = readdir($d_))) {
    if ($file == '.' || $file == '..') continue;
    if (!preg_match('/\.php$/i', trim($file))) continue;
    $file = preg_replace('/\.php$/i', '', trim($file));
    $files[] = $file;
}
closedir($d_);
sort($files);
$t_s = $tpl->get_block('langopt');
foreach ($files as $file) {
    $t_s->assign('key', $file);
    $t_s->assign('val', $file);
    if ($file == $WP_conf['language']) $t_s->assign_block('sel');
    $tpl->assign('langopt', $t_s);
    $t_s->clear();
}
unset($files);
