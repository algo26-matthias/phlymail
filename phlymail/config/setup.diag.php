<?php
/**
 * Short diagnosis of the system and the installation
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['diag_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$pure = (isset($_REQUEST['pure']) && $_REQUEST['pure']);
if (isset($_REQUEST['phpinfo']) && $_REQUEST['phpinfo']) {
    phpinfo();
    exit;
} elseif ($pure) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="diagnosis.txt"');
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/diagnosis.txt.tpl');
} else {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/diagnosis.general.tpl');
}

$t_o = $tpl->get_block('outline');
$t_o->assign('head_text', $WP_msg['DiagHeadBasic']);
$t_l = $t_o->get_block('content');
$t_n = $t_l->get_block('normal');
$t_s = $t_l->get_block('special');

$t_n->assign('key', $WP_msg['DiagKversion']);
if (file_exists($_PM_['path']['conf'].'/current.build') && function_exists('version_format')) {
    $buildname = (file_exists($_PM_['path']['conf'].'/build.name')) ? file_get_contents($_PM_['path']['conf'].'/build.name') : 'phlyMail';
    $t_n->assign('value', $buildname.' '.version_format(file_get_contents($_PM_['path']['conf'].'/current.build')));
} else {
    $t_n->assign('value', $WP_msg['DiagUnkVers']);
}
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_s->assign('text', $WP_msg['DiagKwhere']); $t_l->assign('special', $t_s);
$t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_s->assign('text', __DIR__); $t_l->assign('special', $t_s);
$t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagFSize']);
$size = mod_diagnosis_recurse_size($_PM_['path']['base']);
$t_n->assign('value', size_format($size, $pure));
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagConfRel']);
if (isset($_PM_['path']['conf'])) {
    $t_n->assign('value', $_PM_['path']['conf']);
} else {
    $t_n->assign('value', $WP_msg['DiagNoLoc']);
}
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagSkinRel']);
if (isset($_PM_['path']['theme'])) {
    $t_n->assign('value', $_PM_['path']['theme']);
} else {
    $t_n->assign('value', $WP_msg['DiagNoLoc']);
}
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagPHPVer']); $t_n->assign('value', phpversion());
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagPHPMem']);
$size = @ini_get('memory_limit');
if ($size != '') $t_n->assign('value', $size);
else $t_n->assign('value', 'unknown');
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', 'Register globals:');
$size = @ini_get('register_globals');
$t_n->assign('value', ($size == '1') ? 'On' : 'Off');
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', 'Safe Mode:');
$size = @ini_get('safe_mode');
$t_n->assign('value', ($size == '1') ? 'On' : 'Off');
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', 'IP:');
$t_n->assign('value', isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $WP_msg['nofiletype']);
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagServSoft']);
$t_n->assign('value', (isset($_ENV['SERVER_SOFTWARE'])) ? $_ENV['SERVER_SOFTWARE'] : $WP_msg['nofiletype']);
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagOS']);
$t_n->assign('value', @php_uname());
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagSAPI']);
$t_n->assign('value', @php_sapi_name());
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

$t_n->assign('key', $WP_msg['DiagMTM']);
if ($_PM_['core']['send_method'] == 'smtp') {
    $t_n->assign('value', 'SMTP');
} elseif ($_PM_['core']['send_method'] == 'sendmail') {
    $t_n->assign('value', 'Sendmail');
} else {
    $t_n->assign('value', $WP_msg['DiagUnkSet']);
}
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

if (isset($_PM_['path']['conf'])) {
    $dirname = $scandir = $_PM_['path']['conf'];
} else {
   $scandir = false;
   $dirname = $WP_msg['DiagNoLoc'];
}
$t_n->assign('key', str_replace('$1', $dirname, $WP_msg['DiagWriteTest']));
if ($scandir && touch($scandir.'/diagnosistest')) {
    if (unlink($scandir.'/diagnosistest')) {
        $t_n->assign('value', $WP_msg['DiagSucc']);
    } else {
        $t_n->assign('value', $WP_msg['DiagNoDel']);
    }
} else {
    $t_n->assign('value', $WP_msg['DiagFail']);
}
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

try {
    $WP_theme = parse_ini_file($_PM_['path']['theme'].'/'.$_PM_['core']['theme_name'].'/choices.ini.php');
} catch (Exception $e) {
    $WP_theme['copyright'] = '';
}
$t_n->assign('key', $WP_msg['DiagCurrSkin']);
$t_n->assign('value', $_PM_['core']['theme_name'].' ('.links($WP_theme['copyright']).')');
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

if (!isset($WP_msg['msg_copyright'])) $WP_msg['msg_copyright'] = '';
$t_n->assign('key', $WP_msg['DiagCurrLang']);
$t_n->assign('value', $_PM_['core']['language'].' ('.links($WP_msg['msg_copyright']).')');
$t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
$t_l->clear(); $t_n->clear(); $t_s->clear();

if (!$pure) {
    $t_n->assign('key', 'PHP Info');
    $t_n->assign('value', '<a target="_blank" href="'.PHP_SELF.'?action=diag&amp;'.give_passthrough(1).'&amp;phpinfo=true'.'">&lt; click &gt;</a>');
    $t_l->assign('normal', $t_n); $t_o->assign('content', $t_l);
    $t_l->clear(); $t_n->clear(); $t_s->clear();
}

if (isset($_REQUEST['include_modversions'])) {
    $tpl->assign('outline', $t_o); $t_o->clear();
    $t_o->assign('head_text', $WP_msg['DiagHeadModVers']);
    foreach ($versions as $fn => $version) {
        $t_n->assign('key', $fn);
        $t_n->assign('value', $version);
        $t_l->assign('normal', $t_n);
        $t_o->assign('content', $t_l);
        $t_l->clear(); $t_n->clear(); $t_s->clear();
    }
}
if ($pure) {
    $tpl->assign('outline', $t_o);
    ob_start();
    $tpl->display();
    $tpl = un_html(strip_tags(ob_get_contents()));
    ob_end_clean();
    echo $tpl;
    exit();
}

$tpl->assign(array
      ('export' => $WP_msg['DiagExport']
      ,'target' => PHP_SELF.'?action=diag&amp;'.give_passthrough(1).'&amp;pure=true'
      ));
$tpl->assign('outline', $t_o);

function mod_diagnosis_recurse_size($curr_dir = '')
{
    $size = 0;
    if (!file_exists($curr_dir) || !is_readable($curr_dir)) return $size;
    $dh = opendir($curr_dir);
    while (false !== ($fn = readdir($dh))) {
        if ($fn == '.' or $fn == '..') continue;
        $effective = $curr_dir.'/'.$fn;
        $size += (is_dir($effective)
                ? mod_diagnosis_recurse_size($effective)
                : filesize($effective)
            );
    }
    closedir($dh);
    return $size;
}
