<?php
/**
 * Setup General Settings
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2013-04-08 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['general_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;

if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['general_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $tokens = array(
            'theme_name' => 'WP_newskin',
            'mobile_theme_name' => 'mobile_theme',
            'language' => 'WP_newlang',
            'receipt_out' => 'WP_newreceiptout',
            'send_wordwrap' => 'WP_newsendwordwrap',
            'logout_redir_uri' => 'logout_uri',
            'failed_redir_uri' => 'failed_uri',
            'timezone' => 'timezone',
            'mobile_advertise' => 'mobile_advertise',
            'mobile_autodetect' => 'mobile_autodetect'
            );
    $tokvar = array();
    foreach ($tokens as $k => $v) {
        $tokvar['core'][$k] = (isset($_REQUEST[$v])) ? $_REQUEST[$v] : 0;
    }
    $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
    $_SESSION['WP_return'] = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'general');
    exit();
}
$WP_return = false;
if (!empty($_SESSION['WP_return'])) {
    $WP_return = $_SESSION['WP_return'];
    unset($_SESSION['WP_return']);
}

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.general.tpl');
if ($WP_return) $tpl->assign_block('return');
$tpl->assign(array
        ('target_link' => htmlspecialchars($link_base.'general&whattodo=save')
        ,'WP_return' => $WP_return
        ,'logout_uri' => isset($_PM_['core']['logout_redir_uri']) ? phm_entities($_PM_['core']['logout_redir_uri']) : ''
        ,'failed_uri' => isset($_PM_['core']['failed_redir_uri']) ? phm_entities($_PM_['core']['failed_redir_uri']) : ''
        ));
$themeEngine = trim(file_get_contents($_PM_['path']['conf'].'/theme.engine'));
$d_ = opendir($_PM_['path']['theme']);
$skins = $mobskins = array();
while (false !== ($skinname = readdir($d_))) {
    if ($skinname == '.' or $skinname == '..') continue;
    if (!is_dir($_PM_['path']['theme'].'/'.$skinname)) continue;
    if (!file_exists($_PM_['path']['theme'].'/'.$skinname.'/choices.ini.php')) continue;
    // Read theme's chocies
    $thChoi = parse_ini_file($_PM_['path']['theme'].'/'.$skinname.'/choices.ini.php'); // Parse
    if (!isset($thChoi['engine']) || $thChoi['engine'] != $themeEngine) { // Has engine setting and version matches?
        continue; // This theme ought to be imcompatible
    }
    // Must match any of the supported client types
    if (empty($thChoi['client_type'])) {
        continue;
    }
    if ($thChoi['client_type'] == 'desktop') {
        $skins[] = $skinname;
    }
    if ($thChoi['client_type'] == 'mobile') {
        $mobskins[] = $skinname;
    }
}
closedir($d_);
sort($skins);
sort($mobskins);
$t_s = $tpl->get_block('skinline');
foreach ($skins as $skinname) {
    $t_s->assign(array('key' => $skinname,  'skinname' => $skinname));
    if (!empty($_PM_['core']['theme_name']) && $skinname == $_PM_['core']['theme_name']) {
        $t_s->assign_block('sel');
    }
    $tpl->assign('skinline', $t_s);
    $t_s->clear();
}
$t_ms = $tpl->get_block('mobskinline');
foreach ($mobskins as $skinname) {
    $t_ms->assign(array('key' => $skinname,  'skinname' => $skinname));
    if (!empty($_PM_['core']['mobile_theme_name']) && $skinname == $_PM_['core']['mobile_theme_name']) {
        $t_ms->assign_block('sel');
    }
    $tpl->assign('mobskinline', $t_ms);
    $t_ms->clear();
}
$langs = $langnames = array();
$d_ = opendir($_PM_['path']['message']);
while (false !== ($langname = readdir($d_))) {
    if ($langname == '.' || $langname == '..') {
        continue;
    }
    if (!preg_match('/\.php$/i', trim($langname))) {
        continue;
    }
    preg_match(
            '!\$WP_msg\[\'language_name\'\]\ \=\ \'([^\']+)\'!',
            file_get_contents($_PM_['path']['message'].'/'.$langname),
            $found
            );
    $langname = preg_replace('/\.php$/i', '', trim($langname));
    $langs[] = $found[1];
    $langnames[] = $langname;
}
closedir($d_);
array_multisort($langs, SORT_ASC, $langnames);
$t_s = $tpl->get_block('langline');
foreach ($langs as $id => $langname) {
    $t_s->assign(array('key' => $langnames[$id], 'langname' => $langname));
    if ($langnames[$id] == $_PM_['core']['language']) $t_s->assign_block('sel');
    $tpl->assign('langline', $t_s);
    $t_s->clear();
}
$t_tz = $tpl->get_block('timezone');
foreach (DateTimeZone::listIdentifiers() as $timezone) {
    $t_tz->assign(array('key' => $timezone, 'val' => $timezone));
    if ($timezone == PHM_TIMEZONE) {
        $t_tz->assign_block('sel');
    }
    $tpl->assign('timezone', $t_tz);
    $t_tz->clear();
}

if (!empty($_PM_['core']['receipt_out'])) {
    $tpl->assign_block('receipt');
}
if (!empty($_PM_['core']['send_wordwrap'])) {
    $tpl->assign_block('wordwrap');
}
if (!empty($_PM_['core']['mobile_advertise'])) {
    $tpl->assign_block('mobile_advertise');
}
if (!empty($_PM_['core']['mobile_autodetect'])) {
    $tpl->assign_block('mobile_autodetect');
}
