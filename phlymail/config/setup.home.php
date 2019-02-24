<?php
/**
 * Config start page and settings checker
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.1mod2 2015-04-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.home.tpl');
$error = false;

if (isset($_REQUEST['fixgroups']) && $_REQUEST['fixgroups']) {
    $grpId = $DB->add_group($WP_msg['DefaultGroup'], 0);
    $grpPerms = array();
    // Read all handlers' available privileges
    foreach ($_PM_['handlers'] as $handler => $active) {
        // Look for an installation API call available
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
            continue;
        }
        require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
        $call = 'handler_'.$handler.'_configapi';
        if (!in_array('get_perm_actions', get_class_methods($call))) {
            continue;
        }
        $API = new $call($_PM_, 0);
        $perms = $API->get_perm_actions($WP_conf['language']);
        if (empty($perms)) {
            unset($API);
            continue;
        }
        foreach ($perms as $k => $v) {
            $grpPerms[] = array('handler' => $handler, 'action' => $k, 'perm' => 1);
        }
        unset($API);
    }
    $DB->set_group_permissions($grpId, $grpPerms);
    foreach ($DB->get_usridx() as $k => $v) {
        $DB->add_usertogroup($k, $grpId);
    }
    if (!$error) {
        header('Location: '.$link_base);
        exit;
    }
}

// Check, whether there's settings causing problems
$problems = array();
// Send method and missing settings
switch ($_PM_['core']['send_method']) {
    case 'sendmail':
        if (!isset($_PM_['core']['sendmail']) || !$_PM_['core']['sendmail']) {
            $problems['sendmail'] = 1;
        }
        break;
    case 'smtp':
        if (!isset($_PM_['core']['fix_smtp_host']) || !$_PM_['core']['fix_smtp_host']) {
            $problems['smtp'] = 1;
        }
        break;
}
// ProvSig
if (isset($_PM_['core']['use_provsig']) && 'true' == $_PM_['core']['use_provsig']) {
    if (file_exists($_PM_['path']['conf'].'/forced.signature.wpop')) {
        $_PM_['core']['provsig'] = file_get_contents($_PM_['path']['conf'].'/forced.signature.wpop');
    }
    if (!isset($_PM_['core']['provsig']) || !$_PM_['core']['provsig']) {
        $problems['provsig'] = 1;
    }
}
// MOTD
if (isset($_PM_['core']['show_motd']) && $_PM_['core']['show_motd']) {
    if (file_exists($_PM_['path']['conf'].'/global.MOTD.wpop')) {
        $_PM_['core']['MOTD'] = file_get_contents($_PM_['path']['conf'].'/global.MOTD.wpop');
    }
    if (!isset($_PM_['core']['MOTD']) || !$_PM_['core']['MOTD']) {
        $problems['MOTD'] = 1;
}
}
// Register Now things
if (isset($_PM_['auth']['show_register']) && $_PM_['auth']['show_register']) {
    // No system email given
    if (!isset($_PM_['core']['systememail']) || !$_PM_['core']['systememail']) {
        $problems['regsysaddr'] = 1;
    }
    // No email notifications set
    if (!isset($_PM_['core']['reg_mailuser']) && !isset($_PM_['core']['reg_mailadm'])) {
        $problems['regnotice'] = 1;
    } elseif (!$_PM_['core']['reg_mailuser'] && !$_PM_['core']['reg_mailadm']) {
        $problems['regnotice'] = 1;
    }
    // User may be mailed, but either subject or text are not set
    if (isset($_PM_['core']['reg_mailuser']) && $_PM_['core']['reg_mailuser']) {
        if (!isset($_PM_['core']['reg_mailuser_subj']) || !$_PM_['core']['reg_mailuser_subj']) {
            $problems['regmailuser'] = 1;
        }
        if (file_exists($_PM_['path']['conf'].'/regmail.usertext.wpop')) {
            $_PM_['core']['regmailusertext'] = file_get_contents($_PM_['path']['conf'].'/regmail.usertext.wpop');
        }
        if (!isset($_PM_['core']['regmailusertext']) || !$_PM_['core']['regmailusertext']) {
            $problems['regmailuser'] = 1;
        }
    }
    // Admin may be mailed, but either subject or text are not set
    if (isset($_PM_['core']['reg_mailadm']) && $_PM_['core']['reg_mailadm']) {
        if (!isset($_PM_['core']['reg_mailadm_subj']) || !$_PM_['core']['reg_mailadm_subj']) {
            $problems['regmailadm'] = 1;
        }
        if (file_exists($_PM_['path']['conf'].'/regmail.admtext.wpop')) {
            $_PM_['core']['regmailadmtext'] = file_get_contents($_PM_['path']['conf'].'/regmail.admtext.wpop');
        }
        if (!isset($_PM_['core']['regmailadmtext']) || !$_PM_['core']['regmailadmtext']) {
            $problems['regmailadm'] = 1;
        }
    }

    // Groups available - new users should get at least one
    if ($DB->features['groups']
            && (!isset($_PM_['core']['reg_defaultgroups']) || empty($_PM_['core']['reg_defaultgroups']))) {
        $problems['regdefgroups'] = 1;
    }
}
// SMS settings
if (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']) {
    // User/Pass block removed, too much dependent on GW driver
    // Deposit exhausted
    if ($DB->get_sms_global_deposit() < 1) {
        $problems['smsdeposit'] = 1;
    }
    // higher setting of free sms than monthly allowed limit
    if (isset($_PM_['core']['sms_freesms']) && isset($_PM_['core']['sms_maxmonthly'])) {
        if ($_PM_['core']['sms_maxmonthly'] > 0
                && $_PM_['core']['sms_maxmonthly'] < $_PM_['core']['sms_freesms']) {
            $problems['smsfree'] = 1;
        }
    }
}
// Groups / permissions set up?
if ($DB->features['groups'] && $DB->features['permissions']) {
    if (!$DB->has_permissions_set()) {
        $problems['permissions'] = 1;
    }
}
// All themes up to date?
if (file_exists($_PM_['path']['conf'].'/theme.engine')) {
    $themeEngine = trim(file_get_contents($_PM_['path']['conf'].'/theme.engine'));
    $themes = array();
    $d = opendir($_PM_['path']['theme']);
    while (false !== ($f = readdir($d))) {
        if ('.' == $f) {
            continue;
        }
        if ('..' == $f) {
            continue;
        }
        if (file_exists($_PM_['path']['theme'].'/'.$f.'/choices.ini.php') // Choices file there?
                && is_readable($_PM_['path']['theme'].'/'.$f.'/choices.ini.php')) {
            $thChoi = parse_ini_file($_PM_['path']['theme'].'/'.$f.'/choices.ini.php'); // Parse it
            if (isset($thChoi['engine']) && $thChoi['engine'] == $themeEngine) { // Has engine setting and version matches?
                continue; // Alright, this theme ought to be compatible
            }
        }
        $themes[] = $f;
    }
    closedir($d);
    if (!empty($themes)) {
        $problems['themescompat'] = $themes;
    }
}

// Is there additional 2FA mechs available?
try {
    @include_once 'Auth/Yubico.php';
    if (!class_exists('Auth_Yubico')) {
        $problems['2fa'] = true;
    }
} catch (Exception $e) {
    // Requiring the PEAR module failed with an exception.
    // This is unrecoverable
    $problems['2fa'] = true;
}
if (empty($_PM_['core']['sms_feature_active'])
        || !empty($problems['smsdeposit'])
        || !empty($problems['smsfree'])
) {
    $problems['2fa'] = true;
}

// Are the cronjobs running?
$cron = new DB_Controller_Cron();
$cron_hb = $cron->getHeartbeat();
if (empty($cron_hb)) {
    $problems['cronjob'] = 'NULL';
} elseif (strtotime($cron_hb.'Z') < strtotime('-1 hour', time())) {
    $problems['cronjob'] = strtotime($cron_hb.'Z');
}

// Problems found -> output them
if (!empty($problems)) {
    $t_c = $tpl->get_block('checks');
    $t_c->assign(array
            ('msg_foundprob' => $WP_msg['CkSFoundProb']
            ,'leg_check' => $WP_msg['Leg_CkSet']
            ));
    $t_p = $t_c->get_block('probline');
    $plist = array
            ('smtp' => array('mod' => 'advanced', 'name' => 'setadv', 'msg' => 'CkSSMTP')
            ,'sendmail' => array('mod' => 'advanced', 'name' => 'setadv', 'msg' => 'CkSSndMl')
            ,'sizelimit' => array('mod' => 'advanced', 'name' => 'setadv', 'msg' => 'CkSSLimit')
            ,'provsig' => array('mod' => 'advanced', 'name' => 'setadv', 'msg' => 'CkSProvSig')
            ,'MOTD' => array('mod' => 'advanced', 'name' => 'setadv', 'msg' => 'CkSMOTD')
            ,'regsysaddr' => array('mod' => 'regnow', 'name' => 'setregnow', 'msg' => 'CkSRegSysAddr')
            ,'regnotice' => array('mod' => 'regnow', 'name' => 'setregnow', 'msg' => 'CkSRegNotice')
            ,'regmailuser' => array('mod' => 'regnow', 'name' => 'setregnow', 'msg' => 'CkSRegMailUser')
            ,'regmailadm' => array('mod' => 'regnow', 'name' => 'setregnow', 'msg' => 'CkSRegMailAdm')
            ,'regdefgroups' => array('mod' => 'regnow', 'name' => 'setregnow', 'msg' => 'CkSRegDefGrps')
            ,'smsdeposit' => array('mod' => 'sms', 'name' => 'SMS', 'msg' => 'CkSSMSDeposit')
            ,'smsfree' => array('mod' => 'sms', 'name' => 'SMS', 'msg' => 'CkSSMSFree')
            ,'permissions' => array('mod' => 'home&fixgroups=1', 'name' => 'FixGroupsNow', 'msg' => 'CkSFixPermissions')
            ,'2fa' => array('mod' => '', 'name' => 'Ck2FA', 'msg' => 'Ck2FADescr')
            );
    foreach ($plist as $k => $v) {
        if (isset($problems[$k])) {
            $t_p->assign(array
                   ('msg_module' => $WP_msg['CkSModule']
                   ,'module' => $WP_msg[$v['name']], 'msg_descr' => $WP_msg[$v['msg']]
                   ,'link_module' => htmlspecialchars($link_base.$v['mod'])
                   ));
            $t_c->assign('probline', $t_p);
            $t_p->clear();
        }
    }
    if (isset($problems['themescompat'])) {
        $t_p->assign(array
               ('msg_module' => 'Themes'
               ,'msg_descr' => str_replace('$themeslist$', implode('</li><li>', $problems['themescompat']), $WP_msg['CkThemesCompat'])
               ,'module' => $WP_msg['CkThemesCompatURLTitle']
               ,'link_module' => htmlspecialchars($WP_msg['CkThemesCompatURL']).'" target="_blank' // Force new window / tab
               ));
        $t_c->assign('probline', $t_p);
        $t_p->clear();
    }
    if (isset($problems['cronjob'])) {
        if ($problems['cronjob'] == 'NULL') {
            $t_p->assign('msg_descr', $WP_msg['CkCronJobsNeverRan']);
        } else {
            $t_p->assign('msg_descr', str_replace('$lastrun$', date($WP_msg['dateformat'], $problems['cronjob']), $WP_msg['CkCronJobsDelayed']));
        }
        $t_p->assign(array(
                'msg_module' => 'CronJobs',
                'module' => $WP_msg['CkHowToCronJobsTitle'],
                'link_module' => htmlspecialchars($WP_msg['CkHowToCronJobsURL']).'" target="_blank' // Force new window / tab
               ));
        $t_c->assign('probline', $t_p);
        $t_p->clear();
    }
    $tpl->assign('checks', $t_c);
}
//
$overview = $DB->get_usroverview($_PM_['auth']['countonfail']);
foreach (array('all', 'inactive', 'locked', 'active') as $v) {
    if ($overview[$v] > 0) {
        $tpl->assign_block('search_'.$v);
        $tpl->assign(array(
                'link_search_'.$v => htmlspecialchars($link_base.'users&search=&criteria='.$v),
                'users_'.$v => number_format($overview[$v], 0, $WP_msg['dec'], $WP_msg['tho']),
                'users_'.$v.'_raw' => intval($overview[$v])
                ));
    } else {
        $tpl->assign(array(
                'users_'.$v => 0,
                'users_'.$v.'_raw' => 0
        ));
    }
}

$tpl->assign(array(
        'curr_build' => trim(file_get_contents($_PM_['path']['conf'].'/build.name'))
                .' '.version_format(trim(file_get_contents($_PM_['path']['conf'].'/current.build'))),
        'head_text' => $WP_msg['SuHeadHome'],
        'link_AU' => htmlspecialchars($link_base.'AU'),
        'link_fe_onoff' => htmlspecialchars($link_base.'advanced'),
        'maxlicence' => $WP_msg['UMmaxlicence'],
        'regusers' => $WP_msg['UMregusers'],
        'msg_all' => $WP_msg['all'],
        'msg_active' => $WP_msg['optactive'],
        'msg_inactive' => $WP_msg['optinactive'],
        'msg_locked' => $WP_msg['optlocked'],
        'searchcrit' => $WP_msg['UMsearchcrit'],
        'msg_currbuild' => $WP_msg['YourCurrBuild'],
        'msg_frontendis' => $WP_msg['YourFEis'],
        'msg_checkupd' => $WP_msg['CheckForUpdates'],
        'msg_chgstatus' => $WP_msg['ChgStatus'],
        'msg_general' => $WP_msg['general'],
        'msg_users' => $WP_msg['MenuUsers'],
        'msg_validto' => $WP_msg['ValidTo'],
        'confpath' => CONFIGPATH,
        'users_max' => $WP_msg['Unlimited'],
        'users_max_raw' => -1,
        'valid_to' => $WP_msg['Perpetual'],
        'msg_fe_active' => !empty($_PM_['core']['online_status'])
                ? $WP_msg['SuOnlineYes']
                : $WP_msg['SuOnlineNo']
        ));
