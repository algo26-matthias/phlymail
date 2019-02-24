<?php
/**
 * Setup SMS / MMS / EMS module(s)
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2015-04-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['sms_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;
$uid = (isset($_REQUEST['uid']) && $_REQUEST['uid']) ? $_REQUEST['uid'] : false;
if ($uid) {
    if ('saveuser' == $whattodo) {
        if (isset($_SESSION['phM_perm_write']['sms_']) || $_SESSION['phM_superroot']) {
            $GlChFile = $DB->get_usr_choices($uid);
            $sms_active = (isset($_REQUEST['sms_active']) && $_REQUEST['sms_active']) ? 1 : 0;
            $fax_active = (isset($_REQUEST['fax_active']) && $_REQUEST['fax_active']) ? 1 : 0;
            $fax_0180_active = (isset($_REQUEST['fax_0180_active']) && $_REQUEST['fax_0180_active']) ? 1 : 0;
            $freemonthly = (isset($_REQUEST['freemonthly']) && $_REQUEST['freemonthly']) ? 1 : 0;
            $tokens = array('sms_active', 'sms_freesms', 'sms_freemonthly', 'sms_maxmonthly', 'fax_active', 'fax_0180_active');
            $tokval = array($sms_active, $_REQUEST['freesms'], $freemonthly, $_REQUEST['maxlimit'], $fax_active, $fax_0180_active);
            foreach ($tokens as $k => $v) {
                $GlChFile['core'][$tokens[$k]] = $tokval[$k];
            }
            $error = ($DB->set_usr_choices($uid, $GlChFile)) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
            header('Location: '.$link_base.'sms&whattodo=edituser&uid='.$uid.'&error='.urlencode($error));
            exit;
        } else {
            $error = $WP_msg['no_access'];
        }
    }
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.sms.edituser.tpl');
    // Get user's data and choices
    $userdata = $DB->get_usrdata($uid);
    $choices = $DB->get_usr_choices($uid);
    $choices = $choices['core'];
    // Read SMS stats for user
    list ($curr_sum) = $DB->get_sms_stats(date('Ym'), $uid);
    $last = strtotime('last month');
    list ($last_sum) = $DB->get_sms_stats(date('Ym', $last), $uid);
    $curr_approx = ceil($curr_sum * date('t') / date('j'));
    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'sms&whattodo=saveuser&uid='.$uid)
            ,'where_um' => $WP_msg['UMLinkUM']
            ,'link_um' => htmlspecialchars($link_base.'users')
            ,'where_user' => str_replace('$1', $userdata['username'], $WP_msg['UMLinkUser'])
            ,'link_user' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$uid)
            ,'where_setsms' => $WP_msg['UMSetSMS']
            ,'freesms' => isset($choices['sms_freesms']) ? (int) $choices['sms_freesms'] : 0
            ,'maxlimit' => isset($choices['sms_maxmonthly']) ? (int) $choices['sms_maxmonthly'] : 0
            ,'curr_use' => ($curr_sum) ? number_format($curr_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'last_use' => ($last_sum) ? number_format($last_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'curr_approx' => number_format($curr_approx, 0, $WP_msg['dec'], $WP_msg['tho'])
            ,'about_sms' => $WP_msg['SMSAboutUserSet']
            ,'leg_sms' => $WP_msg['UMSetSMS']
            ,'msg_maysendsms' => $WP_msg['SMSUserMay']
            ,'msg_maysendfax' => $WP_msg['FaxUserMay']
            ,'msg_maysendfax0180' => $WP_msg['FaxUserMay0180']
            ,'msg_freesms' => $WP_msg['SMSFree']
            ,'msg_monthly' => $WP_msg['SMSMonthly']
            ,'msg_maxmonthly' => $WP_msg['SMSMaxMonthlyU']
            ,'msg_save' => $WP_msg['save']
            ,'leg_smsstat' => $WP_msg['SMSLegStatU']
            ,'msg_curruse' => $WP_msg['SMSCurrUse']
            ,'msg_lastuse' => $WP_msg['SMSLastUseU']
            ,'msg_month' => $WP_msg['Month']
            ,'msg_sms' => 'SMS'
            ,'msg_approx' => $WP_msg['Approx']
            ));
    if (isset($_REQUEST['error']) && $_REQUEST['error']) {
        $tpl->fill_block('error', 'error', $_REQUEST['error']);
    }
    if ((isset($_PM_['core']['sms_freemonthly']) && $_PM_['core']['sms_freemonthly'])
            || (isset($choices['sms_freemonthly']) && $choices['sms_freemonthly'])) {
        $tpl->assign_block('freemon');
    }
    if ((isset($_PM_['core']['sms_default_active']) && $_PM_['core']['sms_default_active'])
            || (isset($choices['sms_active']) && $choices['sms_active'])) {
        $tpl->assign_block('smsact');
    }
    if ((isset($_PM_['core']['fax_default_active']) && $_PM_['core']['fax_default_active'])
            || (isset($choices['fax_active']) && $choices['fax_active'])) {
        $tpl->assign_block('faxact');
    }
    if (isset($choices['fax_0180_active']) && $choices['fax_0180_active']) {
        $tpl->assign_block('fax0180act');
    }
    return;
}

if ('save' == $whattodo) {
    if (isset($_SESSION['phM_perm_write']['sms_']) || $_SESSION['phM_superroot']) {
        if (!isset($_PM_['core']['sms_use_gw'])) {
            $_PM_['core']['sms_use_gw'] = 'phlymail.de';
        }
        $truth = false;
        if (isset($_REQUEST['username'])) {
            $stamp = base64_encode(time());
            $fid = fopen($_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php', 'w');
            if ($fid) {
                fputs($fid, ';<?php die(); '.LF);
                fputs($fid, 'sms_user = "'.confuse($_REQUEST['username'], $stamp).'"'.LF);
                if (isset($_REQUEST['password'])) {
                    fputs($fid, 'sms_pass = "'.confuse($_REQUEST['password'], confuse($_REQUEST['username'], $stamp)).'"'.LF);
                }
                fputs($fid, 'sms_stamp = "'.$stamp.'"'.LF);
                fclose($fid);
                $truth = true;
            }
        }
        if (isset($_REQUEST['use_gw'])) {
            $tokvar['core'] = array
                    ('sms_feature_active' => isset($_REQUEST['use_sms']) ? 1 : 0
                    ,'sms_use_gw' => $_REQUEST['use_gw']
                    ,'sms_global_prefix' => (isset($_REQUEST['global_prefix']) && $_REQUEST['global_prefix']) ? $_REQUEST['global_prefix'] : ''
                    );
            $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
        }
        if (isset($_REQUEST['deposit'])) {
            $tokvar['core'] = array
                    ('sms_allowover' => isset($_REQUEST['allowover']) ? 1 : 0
                    ,'sms_is_monthly' => isset($_REQUEST['is_monthly']) ? 1 : 0
                    );
            $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
            $deposit = preg_replace('![^0-9]!', '', $_REQUEST['deposit']);
            $DB->set_sms_global_deposit($deposit+0);
        }
        if (isset($_REQUEST['default_active'])) {
            $tokvar['core'] = array(
                    'fax_default_active' => $_REQUEST['fax_default_active'],
                    'sms_default_active' => $_REQUEST['default_active'],
                    'sms_is_monthly' => isset($_REQUEST['is_monthly']) ? 1 : 0,
                    'sms_allowover' => isset($_REQUEST['allowover']) ? 1 : 0,
                    'sms_freesms' => $_REQUEST['freesms'],
                    'sms_freemonthly' => isset($_REQUEST['freemonthly']) ? 1 : 0,
                    'sms_maxmonthly' => $_REQUEST['maxlimit']
                    );
            $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
        }
        $error = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
        header('Location: '.$link_base.'sms&error='.urlencode($error));
        exit;
    } else {
        $error = $WP_msg['no_access'];
        $whattodo = false;
    }
}
if (isset($_REQUEST['test'])) {
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gwcredentials = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
    require_once($usegwpath.'/phm_shortmessage.php');
    $GW = new phm_shortmessage($usegwpath, $gwcredentials);
    switch (trim($GW->test())) {
        case '0': $error = $WP_msg['SMSTestWUoP']; break;
        case '1': $error = $WP_msg['SMSTestOkay']; break;
        case '-': $error = $WP_msg['SMSTestDB']; break;
        default: $error = $WP_msg['SMSTestUnknown'];
    }
}
if (isset($_REQUEST['synchro'])) {
    $usegwpath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
    $gwcredentials = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
    require_once($usegwpath.'/phm_shortmessage.php');
    $GW = new phm_shortmessage($usegwpath, $gwcredentials);
    $deposit = trim($GW->synchro());
    switch ($deposit) {
    case 'no': $error = $WP_msg['SMSTestWUoP']; break;
    case '-': $error = $WP_msg['SMSTestDB']; break;
    default:
        $error = $WP_msg['SMSSynchroOK'];
        $DB->set_sms_global_deposit($deposit+0);
    }
}

if (!$whattodo) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.sms.main.tpl');
    if (!empty($_REQUEST['error'])) {
        $error = $_REQUEST['error'];
    }
    if (!empty($error)) {
        $tpl->fill_block('error', 'error', $error);
    }
    if (!isset($_PM_['core']['sms_use_gw'])) {
        $_PM_['core']['sms_use_gw'] = 'phlymail.de';
    }
    $gw_sett = (file_exists($_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'].'/settings.ini.php'))
            ? parse_ini_file($_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'].'/settings.ini.php')
            : array();
    $gw_user = (file_exists($_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php'))
            ? parse_ini_file($_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php')
            : array();
    $tpl->assign_block(isset($gw_sett['query_pass']) && $gw_sett['query_pass'] ? 'gw_has_pw' : 'gw_has_key');
    $d_ = opendir($_PM_['path']['msggw']);
    while (false !== ($gwname = readdir($d_))) {
        if ($gwname == '.' || $gwname == '..') {
            continue;
        }
        if (!file_exists($_PM_['path']['msggw'].'/'.trim($gwname).'/settings.ini.php')) {
            continue;
        }
        $gwsett = parse_ini_file($_PM_['path']['msggw'].'/'.trim($gwname).'/settings.ini.php');
        $gws[] = array('name' => $gwname, 'server' => $gwsett['display_server']);
    }
    closedir($d_);
    sort($gws);
    $t_s = $tpl->get_block('used_gw_line');
    foreach ($gws as $v) {
        $t_s->assign(array('name' => $v['name'], 'Server' => $v['server']));
        if ($v['name'] == $_PM_['core']['sms_use_gw']) {
            $t_s->assign_block('sel');
        }
        $tpl->assign('used_gw_line', $t_s);
        $t_s->clear();
    }
    list ($curr_sum, $curr_min, $curr_max, $curr_cnt, $curr_usrdata) = $DB->get_sms_stats(date('Ym'));
    $last = strtotime('last month');
    list ($last_sum, $last_min, $last_max, $last_cnt, $last_usrdata) = $DB->get_sms_stats(date('Ym', $last));
    $curr_avg = ($curr_cnt) ? number_format($curr_sum/$curr_cnt, 1, $WP_msg['dec'], $WP_msg['tho']) : 0;
    $last_avg = ($last_cnt) ? number_format($last_sum/$last_cnt, 1, $WP_msg['dec'], $WP_msg['tho']) : 0;
    $curr_approx = ceil($curr_sum * date('t') / date('j'));
    $deposit = $DB->get_sms_global_deposit();
    $tpl->assign(array
            ('target' => htmlspecialchars($link_base.'sms&whattodo=save')
            ,'order_deposit_uri' => 'https://phlymail.com/'.($WP_msg['language'] == 'de' ? 'de' : 'en').'/phlymail/msggw/'
            ,'username' => isset($gw_user['sms_user']) ? phm_entities(deconfuse($gw_user['sms_user'], $gw_user['sms_stamp'])) : ''
            ,'password' => isset($gw_user['sms_pass']) ? phm_entities(deconfuse($gw_user['sms_pass'], $gw_user['sms_user'])) : ''
            ,'freesms' => isset($_PM_['core']['sms_freesms']) ? (int) $_PM_['core']['sms_freesms'] : 0
            ,'maxlimit' => isset($_PM_['core']['sms_maxmonthly']) ? (int) $_PM_['core']['sms_maxmonthly'] : 0
            ,'global_prefix' => isset($_PM_['core']['sms_global_prefix']) ? phm_entities($_PM_['core']['sms_global_prefix']) : ''
            ,'curr_use' => ($curr_sum) ? number_format($curr_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'curr_max' => ($curr_max) ? number_format($curr_max, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'curr_min' => ($curr_min) ? number_format($curr_min, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'last_use' => ($last_sum) ? number_format($last_sum, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'last_max' => ($last_max) ? number_format($last_max, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'last_min' => ($last_min) ? number_format($last_min, 0, $WP_msg['dec'], $WP_msg['tho']) : 0
            ,'curr_avg' => $curr_avg
            ,'last_avg' => $last_avg
            ,'curr_approx' => number_format($curr_approx, 0, $WP_msg['dec'], $WP_msg['tho'])
            ,'msg_gw_to_use' => $WP_msg['SMSUseGW']
            ,'leg_gwdep' => $WP_msg['SMSLegGWSet']
            ,'server' => $gw_sett['display_server']
            ,'msg_use' => $WP_msg['SMSUseFeat']
            ,'about_use' => $WP_msg['SMSAboutUse']
            ,'msg_global_prefix' => $WP_msg['SMSGlobalPrefix']
            ,'about_global_prefix' => $WP_msg['SMSAboutGlobPrefix']
            ,'msg_username' => $WP_msg['sysuser']
            ,'msg_password' => $WP_msg['syspass']
            ,'msg_userkey' => $WP_msg['SMSUserKey']
            ,'msg_ismonthly' => $WP_msg['SMSIsMonthly']
            ,'msg_over' => $WP_msg['SMSAllowOver']
            ,'leg_userdep' => $WP_msg['SMSLegUserDep']
            ,'about_userdep' => $WP_msg['SMSAboutUserDep']
            ,'msg_allmay' => $WP_msg['SMSAllMay']
            ,'msg_nomay' => $WP_msg['SMSNoMay']
            ,'msg_freesms' => $WP_msg['SMSFree']
            ,'msg_monthly' => $WP_msg['SMSMonthly']
            ,'msg_maxmonthly' => $WP_msg['SMSMaxMonthly']
            ,'msg_save' => $WP_msg['save']
            ,'leg_global_settings' => $WP_msg['SMSLegGlobSet']
            ,'leg_currstat' => $WP_msg['SMSLegCurrStat']
            ,'leg_laststat' => $WP_msg['SMSLegLastStat']
            ,'msg_curruse' => $WP_msg['SMSCurrUse']
            ,'msg_lastuse' => $WP_msg['SMSLastUse']
            ,'msg_month' => $WP_msg['Month']
            ,'msg_sms' => 'SMS'
            ,'msg_approx' => $WP_msg['Approx']
            ,'msg_avgperuser' => $WP_msg['SMSAvgUser']
            ,'msg_maxuse' => $WP_msg['SMSMaxUse']
            ,'msg_leastuse' => $WP_msg['SMSLeastUse']
            ,'msg_allmayfax' => $WP_msg['FaxAllMay']
            ,'msg_nomayfax' => $WP_msg['FaxNoMay']
            ));
    if (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']) {
        $tpl->assign_block('useit');
    }
    if (isset($_PM_['core']['sms_freemonthly']) && $_PM_['core']['sms_freemonthly']) {
        $tpl->assign_block('freemon');
    }
    if (isset($_PM_['core']['sms_default_active']) && $_PM_['core']['sms_default_active']) {
        $tpl->assign_block('defact1');
    } else {
        $tpl->assign_block('defact0');
    }
    if (isset($_PM_['core']['fax_default_active']) && $_PM_['core']['fax_default_active']) {
        $tpl->assign_block('faxdefact1');
    } else {
        $tpl->assign_block('faxdefact0');
    }
    $gw_may_test = (isset($gw_sett['has_test']) && $gw_sett['has_test']);
    $gw_may_synchro = (isset($gw_sett['has_synchro']) && $gw_sett['has_synchro']);
    if (isset($gw_user['sms_user'])) {
        if ($gw_may_test || $gw_may_synchro) {
            $t_i = $tpl->get_block('accountsaved');
            if ($gw_may_test) {
                $t_i->assign_block('gw_has_test');
            }
            if ($gw_may_synchro) {
                $t_i->assign_block('gw_has_synchro');
            }
            $t_i->assign(array
                    ('link_test' => htmlspecialchars($link_base.'sms&test=1')
                    ,'link_synchro' => htmlspecialchars($link_base.'sms&synchro=1')
                    ,'leg_gateway' => $WP_msg['SMSLegGateway']
                    ,'about_gateway' => $WP_msg['SMSAboutGateway']
                    ,'about_test' => $WP_msg['SMSAboutTest']
                    ,'msg_test' => $WP_msg['SMSTest']
                    ,'about_deposit' => $WP_msg['SMSAboutDeposit']
                    ,'msg_deposit' => $WP_msg['SMSDeposit']
                    ,'msg_synchro' => $WP_msg['SMSSyncDeposit']
                    ,'deposit' => isset($deposit) ? (int) $deposit : 0
                    ));
            $tpl->assign('accountsaved', $t_i);
        } else {
            $t_f = $tpl->get_block('setfreely');
            $t_f->assign(array
                    ('msg_deposit' => $WP_msg['SMSDeposit']
                    ,'deposit' => isset($deposit) ? (int) $deposit : 0
                    ,'msg_ismonthly' => $WP_msg['SMSIsMonthly']
                    ,'msg_over' => $WP_msg['SMSAllowOver']
                    ,'msg_save' => $WP_msg['save']
                    ,'target' => htmlspecialchars($link_base.'sms&whattodo=save')
                    ));
            if (isset($_PM_['core']['sms_is_monthly']) && $_PM_['core']['sms_is_monthly']) {
                $t_f->assign_block('ismonth');
            }
            if (isset($_PM_['core']['sms_allowover']) && $_PM_['core']['sms_allowover']) {
                $t_f->assign_block('over');
            }
            $tpl->assign('setfreely', $t_f);
        }
    }
    if (is_array($curr_usrdata)) {
        $tpl->fill_block('showcurrmin', array
                ('user' => $curr_usrdata['min_usr']
                ,'link_show' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$curr_usrdata['min_uid'])
                ,'msg_showuser' => $WP_msg['SMSShowUser']
                ));
        $tpl->fill_block('showcurrmax', array
                ('user' => $curr_usrdata['max_usr']
                ,'link_show' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$curr_usrdata['max_uid'])
                ,'msg_showuser' => $WP_msg['SMSShowUser']
                ));
    }
    if (is_array($last_usrdata)) {
        $tpl->fill_block('showlastmin', array
                ('user' => $last_usrdata['min_usr']
                ,'link_show' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$last_usrdata['min_uid'])
                ,'msg_showuser' => $WP_msg['SMSShowUser']
                ));
        $tpl->fill_block('showlastmax', array
                ('user' => $last_usrdata['max_usr']
                ,'link_show' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$last_usrdata['max_uid'])
                ,'msg_showuser' => $WP_msg['SMSShowUser']
                ));
    }
}
