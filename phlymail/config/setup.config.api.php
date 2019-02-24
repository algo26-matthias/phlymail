<?php
/**
 * Management of API functions
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['config.api_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = isset($_REQUEST['whattodo']) ? $_REQUEST['whattodo'] : false;

if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['config.api_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $cfg['api_user'] = array
            ('newuser_runscript' => isset($_REQUEST['newuser_runscript']) ? $_REQUEST['newuser_runscript'] : false
            ,'newuser_writefile' => isset($_REQUEST['newuser_writefile']) ? $_REQUEST['newuser_writefile'] : false
            ,'newuser_createprofile' => isset($_REQUEST['newuser_createprofile']) ? $_REQUEST['newuser_createprofile'] : false
            ,'edituser_runscript' => isset($_REQUEST['edituser_runscript']) ? $_REQUEST['edituser_runscript'] : false
            ,'edituser_writefile' => isset($_REQUEST['edituser_writefile']) ? $_REQUEST['edituser_writefile'] : false
            ,'deleteuser_runscript' => isset($_REQUEST['deleteuser_runscript']) ? $_REQUEST['deleteuser_runscript'] : false
            ,'deleteuser_writefile' => isset($_REQUEST['deleteuser_writefile']) ? $_REQUEST['deleteuser_writefile'] : false
            ,'accname' => isset($_REQUEST['accname']) ? $_REQUEST['accname'] : ''
            ,'acctype' => isset($_REQUEST['acctype']) ? $_REQUEST['acctype'] : 'pop3'
            ,'smtpserver' => isset($_REQUEST['smtpserver']) ? $_REQUEST['smtpserver'] : ''
            ,'smtpport' => isset($_REQUEST['smtpport']) ? $_REQUEST['smtpport'] : 587 // 25
            ,'smtpuser' => isset($_REQUEST['smtpuser']) ? $_REQUEST['smtpuser'] : ''
            ,'smtppass' => isset($_REQUEST['smtppass']) ? $_REQUEST['smtppass'] : ''
            ,'smtpsecurity' => isset($_REQUEST['smtpsecurity']) ? $_REQUEST['smtpsecurity'] : 'SSL'
            ,'popserver' => isset($_REQUEST['popserver']) ? $_REQUEST['popserver'] : ''
            ,'popport' => isset($_REQUEST['popport']) ? $_REQUEST['popport'] : 110
            ,'popuser' => isset($_REQUEST['popuser']) ? $_REQUEST['popuser'] : ''
            ,'poppass' => isset($_REQUEST['poppass']) ? $_REQUEST['poppass'] : ''
            ,'popsecurity' => isset($_REQUEST['popsecurity']) ? $_REQUEST['popsecurity'] : 'SSL'
            ,'address' => isset($_REQUEST['address']) ? $_REQUEST['address'] : ''
            ,'real_name' => isset($_REQUEST['real_name']) ? $_REQUEST['real_name'] : ''
            ,'popnoapop' => isset($_REQUEST['popnoapop']) ? $_REQUEST['popnoapop'] : 0
            ,'smtpafterpop' => isset($_REQUEST['smtpafterpop']) ? $_REQUEST['smtpafterpop'] : 0
            ,'checkevery' => isset($_REQUEST['checkevery']) ? $_REQUEST['checkevery'] : ''
            ,'leaveonserver' => isset($_REQUEST['leaveonserver']) ? $_REQUEST['leaveonserver'] : 0
            ,'localkillserver' => isset($_REQUEST['localkillserver']) ? $_REQUEST['localkillserver'] : 0
            ,'onlysubscribed' => isset($_REQUEST['onlysubscribed']) ? $_REQUEST['onlysubscribed'] : 0
            ,'checkspam' => isset($_REQUEST['checkspam']) ? $_REQUEST['checkspam'] : 0
            ,'newuser_scriptpath' => isset($_REQUEST['newuser_scriptpath']) ? $_REQUEST['newuser_scriptpath'] : ''
            ,'newuser_fileformat' => isset($_REQUEST['newuser_fileformat']) ? phm_stripslashes($_REQUEST['newuser_fileformat']) : ''
            ,'newuser_filepath' => isset($_REQUEST['newuser_filepath']) ? $_REQUEST['newuser_filepath'] : ''
            ,'edituser_scriptpath' => isset($_REQUEST['edituser_scriptpath']) ? $_REQUEST['edituser_scriptpath'] : ''
            ,'edituser_fileformat' => isset($_REQUEST['edituser_fileformat']) ? phm_stripslashes($_REQUEST['edituser_fileformat']) : ''
            ,'edituser_filepath' => isset($_REQUEST['edituser_filepath']) ? $_REQUEST['edituser_filepath'] : ''
            ,'deleteuser_scriptpath' => isset($_REQUEST['deleteuser_scriptpath']) ? $_REQUEST['deleteuser_scriptpath'] : ''
            ,'deleteuser_fileformat' => isset($_REQUEST['deleteuser_fileformat']) ? phm_stripslashes($_REQUEST['deleteuser_fileformat']) : ''
            ,'deleteuser_filepath' => isset($_REQUEST['deleteuser_filepath']) ? $_REQUEST['deleteuser_filepath'] : ''
            );
    $ret = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $cfg) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'config.api&error='.$ret);
    exit;
}
if (!$whattodo) {
    $PHM = isset($_PM_['api_user']) ? $_PM_['api_user'] : array();
    // We ported the frontend's mail account editor to the config. To avoid duplicating all messages we simply import them here
    require_once($_PM_['path']['message'].'/'.$WP_conf['language'].'.php');
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/config.api.tpl');
    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'config.api&whattodo=save')
            ,'confpath' => CONFIGPATH
            ,'msg_save' => $WP_msg['save']
            ,'msg_no' => $WP_msg['no']
            ,'msg_yes' => $WP_msg['yes']
            ,'msg_profname' => $WP_msg['prflnm']
            ,'msg_auto' => $WP_msg['auto']
            ,'msg_popserver' => $WP_msg['popserver']
            ,'msg_popport' => $WP_msg['popport']
            ,'msg_popuser' => $WP_msg['popuser']
            ,'msg_poppass' => $WP_msg['poppass']
            ,'msg_email' => $WP_msg['email']
            ,'msg_realname' => $WP_msg['realname']
            ,'msg_popsec' => $WP_msg['ConnectionSecurity']
            ,'msg_smtphost' => $WP_msg['optsmtphost']
            ,'msg_smtpport' => $WP_msg['optsmtpport']
            ,'msg_smtpuser' => $WP_msg['optsmtpuser']
            ,'msg_smtppass' => $WP_msg['optsmtppass']
            ,'msg_leaveonserver' => $WP_msg['optleaveonserver']
            ,'msg_onlysubscribed' => $WP_msg['ImapOnlySubscribed']
            ,'msg_localkillserver' => $WP_msg['poplocalkillserver']
            ,'msg_checkspam' => $WP_msg['optcheckspam']
            ,'msg_checkevery' => $WP_msg['optcheckevery']
            ,'msg_smtpsec' => $WP_msg['ConnectionSecurity']
            ,'headtext' => $WP_msg['APIUserHeadText']
            ,'leg_create' => $WP_msg['APIUserLegCreate']
            ,'leg_edit' => $WP_msg['APIUserLegEdit']
            ,'leg_delete' => $WP_msg['APIUserLegDelete']
            ,'about_create' => $WP_msg['APIUserAboutCreate']
            ,'about_edit' => $WP_msg['APIUserAboutEdit']
            ,'about_delete' => $WP_msg['APIUserAboutDelete']
            ,'msg_runscript' => $WP_msg['APIUserRunScript']
            ,'msg_scriptpath' => $WP_msg['APIUserScriptPath']
            ,'msg_writefile' => $WP_msg['APIUserWriteFile']
            ,'msg_fileformat' => $WP_msg['APIUserFileFormat']
            ,'msg_filepath' => $WP_msg['APIUserFilePath']
            ,'msg_createprofile' => $WP_msg['APIUserCreateProfile']
            ,'msg_aboutprofile' => $WP_msg['APIUserAboutProfile']
            ,'accname' => isset($PHM['accname']) ? phm_entities($PHM['accname']) : ''
            ,'smtpserver' => isset($PHM['smtpserver']) ? phm_entities($PHM['smtpserver']) : ''
            ,'smtpport' => isset($PHM['smtpport']) ? phm_entities($PHM['smtpport']) : 587 // 25
            ,'smtpuser' => isset($PHM['smtpuser']) ? phm_entities($PHM['smtpuser']) : ''
            ,'smtppass' => isset($PHM['smtppass']) ? phm_entities($PHM['smtppass']) : ''
            ,'popserver' => isset($PHM['popserver']) ? phm_entities($PHM['popserver']) : ''
            ,'popport' => isset($PHM['popport']) ? phm_entities($PHM['popport']) : 110
            ,'popuser' => isset($PHM['popuser']) ? phm_entities($PHM['popuser']) : ''
            ,'poppass' => isset($PHM['poppass']) ? phm_entities($PHM['poppass']) : ''
            ,'address' => isset($PHM['address']) ? phm_entities($PHM['address']) : ''
            ,'checkevery' => isset($PHM['checkevery']) ? phm_entities($PHM['checkevery']) : 0
            ,'real_name' => isset($PHM['real_name']) ? phm_entities($PHM['real_name']) : ''
            ,'newuser_scriptpath' => isset($PHM['newuser_scriptpath']) ? phm_entities($PHM['newuser_scriptpath']) : ''
            ,'newuser_fileformat' => isset($PHM['newuser_fileformat']) ? phm_entities($PHM['newuser_fileformat']) : ''
            ,'newuser_filepath' => isset($PHM['newuser_filepath']) ? phm_entities($PHM['newuser_filepath']) : ''
            ,'edituser_scriptpath' => isset($PHM['edituser_scriptpath']) ? phm_entities($PHM['edituser_scriptpath']) : ''
            ,'edituser_fileformat' => isset($PHM['edituser_fileformat']) ? phm_entities($PHM['edituser_fileformat']) : ''
            ,'edituser_filepath' => isset($PHM['edituser_filepath']) ? phm_entities($PHM['edituser_filepath']) : ''
            ,'deleteuser_scriptpath' => isset($PHM['deleteuser_scriptpath']) ? phm_entities($PHM['deleteuser_scriptpath']) : ''
            ,'deleteuser_fileformat' => isset($PHM['deleteuser_fileformat']) ? phm_entities($PHM['deleteuser_fileformat']) : ''
            ,'deleteuser_filepath' => isset($PHM['deleteuser_filepath']) ? phm_entities($PHM['deleteuser_filepath']) : ''
            ));
    if (isset($PHM['newuser_runscript']) && $PHM['newuser_runscript']) $tpl->assign_block('nu_rs');
    if (isset($PHM['newuser_writefile']) && $PHM['newuser_writefile']) $tpl->assign_block('nu_wf');
    if (isset($PHM['newuser_createprofile']) && $PHM['newuser_createprofile']) $tpl->assign_block('nu_cp');
    if (isset($PHM['edituser_runscript']) && $PHM['edituser_runscript']) $tpl->assign_block('eu_rs');
    if (isset($PHM['edituser_writefile']) && $PHM['edituser_writefile']) $tpl->assign_block('eu_wf');
    if (isset($PHM['deleteuser_runscript']) && $PHM['deleteuser_runscript']) $tpl->assign_block('du_rs');
    if (isset($PHM['deleteuser_writefile']) && $PHM['deleteuser_writefile']) $tpl->assign_block('du_wf');
    if (isset($PHM['popnoapop']) && $PHM['popnoapop']) $tpl->assign_block('popnoapop');
    if (isset($PHM['smtpafterpop']) && $PHM['smtpafterpop']) $tpl->assign_block('smtpafterpop');
    if (isset($PHM['checkspam']) && $PHM['checkspam']) $tpl->assign_block('checkspam');
    if (!isset($PHM['leaveonserver']) || $PHM['leaveonserver']) $tpl->assign_block('leaveonserver');
    if (isset($PHM['onlysubscribed']) && $PHM['onlysubscribed']) $tpl->assign_block('onlysubscribed');
    if (isset($PHM['localkillserver']) && $PHM['localkillserver']) $tpl->assign_block('localkillserver');
    if (isset($_REQUEST['error']) && $_REQUEST['error']) $tpl->fill_block('error', 'error', $_REQUEST['error']);
    $tpl->assign_block(isset($PHM['acctype']) && $PHM['acctype'] == 'imap' ? 'acctype_imap' : 'acctype_pop3');

    $t_ss = $tpl->get_block('smtpsec');
    $t_ps = $tpl->get_block('popsec');
    foreach (array('SSL' => 'SSL', 'STARTTLS' => 'STARTTLS', 'AUTO' => $WP_msg['ConnectionSecurityAuto'], 'none' => $WP_msg['ConnectionSecurityNone']) as $k => $v) {
        $t_ss->assign(array('key' => $k, 'val' => $v));
        if (isset($PHM['smtpsecurity']) && $PHM['smtpsecurity'] == $k) {
            $t_ss->assign_block('sel');
        }
        $tpl->assign('smtpsec', $t_ss);
        $t_ss->clear();

        $t_ps->assign(array('key' => $k, 'val' => $v));
        if (isset($PHM['popsecurity']) && $PHM['popsecurity'] == $k) {
            $t_ps->assign_block('sel');
        }
        $tpl->assign('popsec', $t_ps);
        $t_ps->clear();
    }
}
