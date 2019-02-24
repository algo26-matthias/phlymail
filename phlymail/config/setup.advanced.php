<?php
/**
 * Setup Of Advanced Choices
 * @package phlyMail Nahariya 4.0+
 * @copyright 2003-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.4 2016-01-25
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['advanced_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

// We ported the frontend's mail account editor to the config. To avoid duplicating all messages we simply import them here
require_once $_PM_['path']['message'].'/'.$WP_conf['language'].'.php';

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.advanced.tpl');
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

if ('save' == $whattodo) {
    // Schreibberechtigung
    if (!isset($_SESSION['phM_perm_write']['advanced_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    // Add the mandatory -t flag to the sendmail path
    if ($_REQUEST['WP_newsendmail'] && !preg_match('!\s-t([\s\Z\w]|$)!', $_REQUEST['WP_newsendmail'])) {
        $_REQUEST['WP_newsendmail'] = preg_replace('!^(.+)(?=\s|\Z)!U', '\1 -t', $_REQUEST['WP_newsendmail']);
    }
    $tokvar['core'] = array
            ('send_method' => $_REQUEST['WP_newsendmethod']
            ,'sendmail' => $_REQUEST['WP_newsendmail']
            ,'debugging_level' => (isset($_REQUEST['debugging_level'])) ? $_REQUEST['debugging_level'] : 'system'
            ,'pagesize' => wash_size_field($_REQUEST['WP_newpagesize'])
            ,'use_provsig' => (isset($_REQUEST['WP_useprovsig'])) ? $_REQUEST['WP_useprovsig'] : false
            ,'online_status' => $_REQUEST['WP_isonline']
            ,'show_motd' => (isset($_REQUEST['WP_newshowmotd'])) ? $_REQUEST['WP_newshowmotd'] : 0
            ,'fix_smtp_host' => $_REQUEST['WP_newsmtphost']
            ,'fix_smtp_port' => ($_REQUEST['WP_newsmtpport']) ? $_REQUEST['WP_newsmtpport'] : ''
            ,'fix_smtp_user' => $_REQUEST['WP_newsmtpuser']
            ,'fix_smtp_pass' => $_REQUEST['WP_newsmtppass']
            ,'fix_smtp_security' => $_REQUEST['WP_newsmtpsec']
            ,'fix_smtp_allowselfsigned' => $_REQUEST['WP_newallowselfsigned']
            ,'provider_name' => phm_stripslashes($_REQUEST['WP_newprovidername'])
            ,'gzip_frontend' => (isset($_REQUEST['WP_usegzip'])) ? $_REQUEST['WP_usegzip'] : 0
            );
    $WP_return = $WP_msg['optsnosave'];
    $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
    if ($truth) {
        if ($_REQUEST['WP_provsig']) {
            file_put_contents($_PM_['path']['conf'].'/forced.signature.wpop', phm_stripslashes($_REQUEST['WP_provsig']));
        } else {
            if (file_exists($_PM_['path']['conf'].'/forced.signature.wpop')) {
                unlink($_PM_['path']['conf'].'/forced.signature.wpop');
            }
        }
        if ($_REQUEST['WP_MOTD']) {
            file_put_contents($_PM_['path']['conf'].'/global.MOTD.wpop', phm_stripslashes($_REQUEST['WP_MOTD']));
        } else {
            if (file_exists($_PM_['path']['conf'].'/global.MOTD.wpop')) {
                unlink($_PM_['path']['conf'].'/global.MOTD.wpop');
            }
        }
        $WP_return = $WP_msg['optssaved'];
    }
    header('Location: '.$link_base.'advanced&WP_return='.urlencode($WP_return));
    exit();
}

$_PM_['core']['provsig'] = (file_exists($_PM_['path']['conf'].'/forced.signature.wpop'))
        ? file_get_contents($_PM_['path']['conf'].'/forced.signature.wpop')
        : '';
$_PM_['core']['MOTD'] = (file_exists($_PM_['path']['conf'].'/global.MOTD.wpop'))
        ? file_get_contents($_PM_['path']['conf'].'/global.MOTD.wpop')
        : '';
$tpl->assign(array
        ('head_text' => $WP_msg['SuHeadAdv']
        ,'link_base' => htmlspecialchars($link_base)
        ,'WP_return' => $WP_return
        ,'target_link' => htmlspecialchars($link_base.'advanced&whattodo=save')
        ,'msg_optsendmethod' => $WP_msg['optsendmethod']
        ,'msg_path' => $WP_msg['optsendmail']
        ,'msg_fillin_sm' => $WP_msg['FillinSendMail']
        ,'msg_fillin_smtp' => $WP_msg['FillinSMTP']
        ,'msg_smtphost' => $WP_msg['optsmtphost']
        ,'msg_smtpport' => $WP_msg['optsmtpport']
        ,'msg_smtpuser' => $WP_msg['optsmtpuser']
        ,'msg_smtppass' => $WP_msg['optsmtppass']
        ,'msg_smtpsec' => $WP_msg['ConnectionSecurity']
        ,'size_limit' => $WP_msg['optsizelimit']
        ,'msg_forcedsig' => $WP_msg['SuOptForcedSig']
        ,'sizeexample' => $WP_msg['optsizeexample']
        ,'msg_pagesize' => $WP_msg['SuOptPagesize']
        ,'msg_save' => $WP_msg['save']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'msg_onlineyes' => $WP_msg['SuOnlineYes']
        ,'msg_onlineno' => $WP_msg['SuOnlineNo']
        ,'msg_online' => $WP_msg['SuOptOnline']
        ,'msg_showmotd' => $WP_msg['SuShowMOTD']
        ,'msg_usegzip' => $WP_msg['SuUseGZipFE']
        ,'msg_providername' => $WP_msg['SuNameOfService']
        ,'about_online' => $WP_msg['AboutOnline']
        ,'about_providername' => $WP_msg['AboutProvName']
        ,'about_sendmethod' => $WP_msg['AboutSendMethod']
        ,'about_sizelimit' => $WP_msg['AboutSizeLimit']
        ,'about_pagesize' => $WP_msg['AboutPagesize']
        ,'leg_online' => $WP_msg['LegOnline']
        ,'leg_misc' => $WP_msg['LegMisc']
        ,'leg_fsig' => $WP_msg['LegFSig']
        ,'leg_motd' => $WP_msg['LegMOTD']
        ,'leg_providername' => $WP_msg['LegName']
        ,'leg_debugging' => $WP_msg['LegDebug']
        ,'msg_debugging' => $WP_msg['DebReportWhat']
        ,'about_debugging' => $WP_msg['AboutDebug']
        ,'sendmail' => phm_entities($_PM_['core']['sendmail'])
        ,'smtphost' => isset($_PM_['core']['fix_smtp_host']) ? phm_entities($_PM_['core']['fix_smtp_host']) : ''
        ,'smtpport' => isset($_PM_['core']['fix_smtp_port']) ? phm_entities($_PM_['core']['fix_smtp_port']) : ''
        ,'smtpuser' => isset($_PM_['core']['fix_smtp_user']) ? phm_entities($_PM_['core']['fix_smtp_user']) : ''
        ,'smtppass' => isset($_PM_['core']['fix_smtp_pass']) ? phm_entities($_PM_['core']['fix_smtp_pass']) : ''
        ,'provsig' => htmlspecialchars($_PM_['core']['provsig'])
        ,'pagesize' => phm_entities($_PM_['core']['pagesize'])
        ,'MOTD' => htmlspecialchars($_PM_['core']['MOTD'])
        ,'providername' => isset($_PM_['core']['provider_name']) ? htmlspecialchars($_PM_['core']['provider_name']) : ''
        ));
// Debugging?
$t_deb = $tpl->get_block('debug_level');
foreach (array(
        'disabled' => 'DebReportNone',
        'enabled' => 'DebReportAll',
        'system' => 'DebReportSystem') as $k => $v) {
    $t_deb->assign(array('level' => $k, 'msg_level' => $WP_msg[$v]));
    if (isset($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] == $k) {
        $t_deb->assign_block('sel');
    }
    $tpl->assign('debug_level', $t_deb);
    $t_deb->clear();
}
// Fix SMTP - security
$t_ss = $tpl->get_block('smtpsec');
foreach (array('SSL' => 'SSL', 'STARTTLS' => 'STARTTLS', 'AUTO' => $WP_msg['ConnectionSecurityAuto'], 'none' => $WP_msg['ConnectionSecurityNone']) as $k => $v) {
    $t_ss->assign(array('key' => $k, 'val' => $v));
    if (isset($_PM_['core']['fix_smtp_security']) && $_PM_['core']['fix_smtp_security'] == $k) {
        $t_ss->assign_block('sel');
    }
    $tpl->assign('smtpsec', $t_ss);
    $t_ss->clear();
}

switch ($_PM_['core']['send_method']) {
    case 'sendmail': $tpl->assign_block('methsmsel');   break;
    case 'smtp':     $tpl->assign_block('methsmtpsel'); break;
}
switch ($_PM_['core']['online_status']) {
    case true:  $tpl->assign_block('online_yes'); break;
    case false: $tpl->assign_block('online_no');  break;
}
if (!empty($_PM_['core']['show_motd'])) {
    $tpl->assign_block('showmotd');
}
if (!empty($_PM_['core']['use_provsig'])) {
    $tpl->assign_block('use_provsig');
}
if (!empty($_PM_['core']['gzip_frontend'])) {
    $tpl->assign_block('usegzip');
}
if (!empty($_PM_['core']['fix_smtp_allowselfsigned'])) {
    $tpl->assign_block('smtpallowselfsigned');
}