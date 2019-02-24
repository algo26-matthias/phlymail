<?php
/**
 * Setup Security of phlyMail FrontEnd
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['security_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;
if ('unconfuse' == $whattodo || 'confuse' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['security_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $Acnt = new DB_Controller_Account();
    if ('confuse' == $whattodo) {
        $Acnt->cleartext_confused();
        $tokvar['core']['accountpass_security'] = 'confused';
    } else {
        $Acnt->confused_cleartext();
        $tokvar['core']['accountpass_security'] = 'cleartext';
    }
    $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
    $WP_return = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'security&WP_return='.urlencode($WP_return));
    exit();
}
if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['security_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $tokvar['auth'] = array
            ('tie_session_ip' => (isset($_REQUEST['WP_newsessionip'])) ? $_REQUEST['WP_newsessionip'] : 0
            ,'session_cookie' => (isset($_REQUEST['WP_newsessioncookie'])) ? $_REQUEST['WP_newsessioncookie'] : 0
            ,'waitonfail' => (int) $_REQUEST['WP_newwaitfail']
            ,'countonfail' => (int) $_REQUEST['WP_newcountfail']
            ,'lockonfail' => (int) $_REQUEST['WP_newlockfail']
            ,'force_ssl' => (int) $_REQUEST['WP_newforcessl']
            );
    $tokvar['proxy'] = array
            ('server_param' => preg_replace('![^a-zA-Z_]!', '', $_REQUEST['WP_serverparam'])
            ,'server_value' => $_REQUEST['WP_servervalue']
            ,'prepend_path' => ($_REQUEST['WP_prependpath']) ? '/'.preg_replace('!^(/|)(.+)(/|)$!', '$2', $_REQUEST['WP_prependpath']) : ''
            ,'proxy_hostname' => ($_REQUEST['WP_proxyhost'])
                    ? (preg_match('!^http(s)?\://!', $_REQUEST['WP_proxyhost']) ? $_REQUEST['WP_proxyhost'] : 'http://'.$_REQUEST['WP_proxyhost'])
                    : ''
            );
    $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
    $WP_return = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'security&WP_return='.urlencode($WP_return));
    exit();
}
$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.security.tpl');
$tpl->assign(array
        ('target_link' => htmlspecialchars($link_base.'security&whattodo=save')
        ,'link_base' => htmlspecialchars($link_base)
        ,'WP_return' => $WP_return
        ,'head_text' => $WP_msg['SuHeadSec']
        ,'msg_sessionip' => $WP_msg['SuTieSessionIp']
        ,'msg_sessioncookie' => $WP_msg['SuTieSessionCookie']
        ,'msg_waitonfail' => $WP_msg['SuOptWaitOnFail']
        ,'msg_lockonfail' => $WP_msg['SuOptLockOnFail']
        ,'msg_countonfail' => $WP_msg['SuOptCountOnFail']
        ,'waitonfail' => (int) $_PM_['auth']['waitonfail']
        ,'lockonfail' => (int) $_PM_['auth']['lockonfail']
        ,'countonfail' => (int) $_PM_['auth']['countonfail']
        ,'msg_save' => $WP_msg['save']
        ,'leg_wronglogin' => $WP_msg['LegWrongLogin']
        ,'about_wronglogin' => $WP_msg['AboutWrongLogin']
        ,'leg_sessionsec' => $WP_msg['LegSessSec']
        ,'about_sessionsec' => $WP_msg['AboutSessSec']
        ,'leg_accpass' => $WP_msg['LegAccPass']
        ,'about_accpass' => $WP_msg['AboutAccPass']
        ,'leg_proxy' => $WP_msg['LegSecProxy']
        ,'about_proxy' => $WP_msg['AboutSecProxy']
        ,'msg_server_param' => $WP_msg['SecProxyServerParam']
        ,'msg_server_value' => $WP_msg['SecProxyServerValue']
        ,'msg_prepend' => $WP_msg['SecProxyPrependPath']
        ,'msg_proxyhost' => $WP_msg['SecProxyProxyHost']
        ,'proxy_serverparam' => isset($_PM_['proxy']['server_param']) ? phm_entities($_PM_['proxy']['server_param']) : ''
        ,'proxy_servervalue' => isset($_PM_['proxy']['server_value']) ? phm_entities($_PM_['proxy']['server_value']) : ''
        ,'prox_prepend_path' => isset($_PM_['proxy']['prepend_path']) ? phm_entities($_PM_['proxy']['prepend_path']) : ''
        ,'prox_proxyhost' => isset($_PM_['proxy']['proxy_hostname']) ? phm_entities($_PM_['proxy']['proxy_hostname']) : ''
        ));
if (isset($_PM_['auth']['tie_session_ip']) && $_PM_['auth']['tie_session_ip']) {
    $tpl->assign_block('sessionip');
}
if (isset($_PM_['auth']['session_cookie']) && $_PM_['auth']['session_cookie']) {
    $tpl->assign_block('sessioncookie');
}
if (isset($_PM_['auth']['force_ssl']) && $_PM_['auth']['force_ssl']) {
    $tpl->assign_block('forcessl');
}
if (isset($_PM_['core']['accountpass_security']) && $_PM_['core']['accountpass_security'] == 'cleartext') {
    $tpl->assign(array
            ('msg_switchnow' => $WP_msg['AccPassSecure']
            ,'switchaccpasslink' => htmlspecialchars($link_base.'security&whattodo=confuse')
            ));
} else {
    $tpl->assign(array
            ('msg_switchnow' => $WP_msg['AccPassInsecure']
            ,'switchaccpasslink' => htmlspecialchars($link_base.'security&whattodo=unconfuse')
            ));
}
