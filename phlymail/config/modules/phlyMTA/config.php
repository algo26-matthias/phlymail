<?php
/**
* Configuration frontend for the phlyMTA, making setup of it easy
* @package phlyMail
* @subpackage Addon module phlyMTA
* @copyright 2001-2013 phlyLabs Berlin, http://phlylabs.de/
* @version 0.0.5 2013-01-22 
*/
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($screen)) $screen = 'pop3';
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

if ('save' == $whattodo) {
    // Schreibberechtigung
    if (!isset($_SESSION['phM_perm_write']['modules_phlymta_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    if ($screen == 'pop3') {
        $tokvar['phlymta'] = array
                ('pop3d_maxchilds' => $_REQUEST['max_childs']
                ,'pop3d_port' => $_REQUEST['listening_port']
                ,'pop3d_timeoutauth' =>  $_REQUEST['timeout_auth']
                ,'pop3d_timeouttrans' =>  $_REQUEST['timeout_trans']
                ,'pop3d_runas' =>  $_REQUEST['runas']
                ,'pop3d_domain' => isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : 'phlymail.com'
                ,'pop3d_servicename' => 'phlyMTA POP3 service'
                );
        $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
        $WP_return = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
        header('Location: '.$link_base.'view&screen=pop3&module=phlyMTA&WP_return='.urlencode($WP_return));
        exit();
    }
}
if ($screen == 'pop3') {
    if ('start' == $whattodo) {
        system($_PM_['path']['base'].'/backend/pop3.server.php > /dev/null');
        header('Location: '.$link_base.'view&screen=pop3&module=phlyMTA');
        exit;
    }
    if ('stop' == $whattodo) {
        if (file_exists($_PM_['path']['conf'].'/services.pop3d.pid') && is_readable($_PM_['path']['conf'].'/services.pop3d.pid')) {
            $pid = file_get_contents($_PM_['path']['conf'].'/services.pop3d.pid');
            unlink($_PM_['path']['conf'].'/services.pop3d.pid');
        }
        header('Location: '.$link_base.'view&screen=pop3&module=phlyMTA');
        exit;
    }

    if (!isset($_PM_['phlymta']['pop3d_runas'])) {
        $user = posix_getpwuid(posix_getuid());
        $_PM_['phlymta']['pop3d_runas'] = $user['name'];
    }
    $tpl = new phlyTemplate(CONFIGPATH.'/modules/phlyMTA/setup.pop3server.tpl');
    $tpl->assign(array
            ('form_action' => htmlspecialchars($link_base.'view&whattodo=save&screen=pop3&module=phlyMTA')
            ,'about_pop3server' => $modmsg['AboutPOP3d']
            ,'leg_settings' => $modmsg['LegSettings']
            ,'leg_state' => $modmsg['LegState']
            ,'about_settings' => $modmsg['AboutSettings']
            ,'msg_max_childs' => $modmsg['MaxChilds']
            ,'about_machilds' => $modmsg['AboutMaxChilds']
            ,'msg_listeningport' => $modmsg['Port']
            ,'about_port' => $modmsg['AboutPort']
            ,'msg_timeo_auth' => $modmsg['TimeoutAuth']
            ,'msg_timeo_trans' => $modmsg['TimeoutTrans']
            ,'about_timeouts' => $modmsg['AboutTimeouts']
            ,'msg_runas' => $modmsg['RunAs']
            ,'about_runas' => $modmsg['AboutRunAs']

            ,'max_childs' => (isset($_PM_['phlymta']['pop3d_maxchilds']) ? phm_entities($_PM_['phlymta']['pop3d_maxchilds']) : '5')
            ,'listening_port' => (isset($_PM_['phlymta']['pop3d_port']) ? phm_entities($_PM_['phlymta']['pop3d_port']) : '110')
            ,'timeout_auth' => (isset($_PM_['phlymta']['pop3d_timeoutauth']) ? phm_entities($_PM_['phlymta']['pop3d_timeoutauth']) : '30')
            ,'timeout_trans' => (isset($_PM_['phlymta']['pop3d_timeouttrans']) ? phm_entities($_PM_['phlymta']['pop3d_timeouttrans']) : '60')
            ,'runas' => phm_entities($_PM_['phlymta']['pop3d_runas'])

            ,'msg_save' => $WP_msg['save']
            ));

    if (file_exists($_PM_['path']['conf'].'/services.pop3d.pid')) {
        $t_s = $tpl->get_block('state_stop');
        $t_s->assign(array
                ('stop_url' => htmlspecialchars($link_base.'view&whattodo=stop&screen=pop3&module=phlyMTA')
                ,'msg_is_running' => $modmsg['DaemonRunning']
                ,'msg_stop' => $modmsg['StopDaemon']
                ));
        $tpl->assign('state_stop', $t_s);
    } else {
        $t_s = $tpl->get_block('state_start');
        $t_s->assign(array
                ('start_url' => htmlspecialchars($link_base.'view&whattodo=start&screen=pop3&module=phlyMTA')
                ,'msg_not_running' => $modmsg['DaemonNotRunning']
                ,'msg_start' => $modmsg['StartDaemon']
                ));
        $tpl->assign('state_start', $t_s);
    }
}
