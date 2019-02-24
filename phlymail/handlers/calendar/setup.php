<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Calendar Handler
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.2 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$link_base = PHP_SELF.'?l=setup&h=calendar&'.give_passthrough(1);
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday'])) {
	$_PM_['calendar']['wday'] = array(0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 0, 6 => 0);
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_start'])){
    $_PM_['calendar']['wday_start'] = 16;
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['wday_end'])) {
    $_PM_['calendar']['wday_end'] = 33;
}
if (!isset($_PM_['calendar']) || !isset($_PM_['calendar']['viewmode'])) {
    $_PM_['calendar']['viewmode'] = 'daily';
}
if (!isset($_PM_['core']['sms_sender'])) {
    $_PM_['core']['sms_sender'] = '';
}


if (isset($_REQUEST['whattodo']) && 'save' == $_REQUEST['whattodo']) {
    if (isset($_REQUEST['warn']) && $_REQUEST['warn']) {
        if (!isset($_REQUEST['warn_time']) || !$_REQUEST['warn_time'] || $_REQUEST['warn_time'] < 0) {
            $pre_warn = 0;
        } else {
            $pre_warn = $_REQUEST['warn_time'];
        }
        $factors = array('m' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800);
        $pre_warn *= $factors[$_REQUEST['warn_range']];
        $warnmode = ($_REQUEST['warn_mode'] == 's') ? 's' : 'e';
    } else {
        $pre_warn = 0;
        $warnmode = '-';
    }
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    $tokens = array('wday', 'wday_start', 'wday_end', 'viewmode', 'warn_time', 'warn_mode', 'mailto', 'smsto');
    $tokval = array
            (isset($_REQUEST['wd']) ? $_REQUEST['wd'] : array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0)
            ,isset($_REQUEST['wd_start']) ? $_REQUEST['wd_start'] : -1
            ,isset($_REQUEST['wd_end']) ? $_REQUEST['wd_end'] : -1
            ,isset($_REQUEST['viewmode']) ? $_REQUEST['viewmode'] : 'daily'
            ,$pre_warn
            ,$warnmode
            ,isset($_REQUEST['warn_mail']) ? $_REQUEST['warn_mail'] : ''
            ,isset($_REQUEST['warn_sms']) ? $_REQUEST['warn_sms'] : ''
            );
    // Checkbox settings may be unset.
    $tokcheck = array (1, 0, 0, 1, 0, 1, 0, 0, 0, 0);
    foreach ($tokens as $k => $v) {
        if ($tokval[$k] == -1) {
            if (!$tokcheck[$k]) {
                continue;
            }
            $tokval[$k] = '';
        }
        $GlChFile['calendar'][$tokens[$k]] = $tokval[$k];
    }
    $WP_return = ($DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile)) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'&WP_return='.urlencode($WP_return));
    exit();
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'setup.calendar.tpl');
if (isset($_REQUEST['WP_return'])) {
    $tpl->assign('WP_return', phm_entities($_REQUEST['WP_return']));
}
if ($tpl->block_exists('wd_checkbox')) {
    $t_wcb = $tpl->get_block('wd_checkbox');
    for ($i = 0; $i < 7; ++$i) {
        $t_wcb->assign(array
                ('day' => $WP_msg['wday'][$i]
                ,'daytitle' => $WP_msg['weekday'][$i]
                ,'id' => $i
                ));
        if (isset($_PM_['calendar']['wday'][$i]) && $_PM_['calendar']['wday'][$i]) {
            $t_wcb->assign_block('sel');
        }
        $tpl->assign('wd_checkbox', $t_wcb);
        $t_wcb->clear();
    }
} else {
    $t_wh = $tpl->get_block('wd_head');
    $t_wb = $tpl->get_block('wd_box');
    for ($i = 0; $i < 7; ++$i) {
        $t_wh->assign(array('day' => $WP_msg['wday'][$i], 'daytitle' => $WP_msg['weekday'][$i]));
        $tpl->assign('wd_head', $t_wh);
        $t_wh->clear();
        $t_wb->assign('id', $i);
        if (isset($_PM_['calendar']['wday'][$i]) && $_PM_['calendar']['wday'][$i]) {
            $t_wb->assign_block('sel');
        }
        $tpl->assign('wd_box', $t_wb);
        $t_wb->clear();
    }
}

$tpl->assign(array('wd_start' => $_PM_['calendar']['wday_start']*50, 'wd_end' => $_PM_['calendar']['wday_end']*50));

$t_vm = $tpl->get_block('viewmode');
foreach (array
        ('daily' => $WP_msg['CalDayView']
        ,'weekly' => $WP_msg['CalWeekView']
        ,'monthly' => $WP_msg['CalMonthView']
        ,'yearly' => $WP_msg['CalYearView']
        ,'list' => $WP_msg['CalListView']) as $k => $v) {
    if ($k == $_PM_['calendar']['viewmode']) {
        $t_vm->assign_block('sel');
    }
    $t_vm->assign(array('mode' => $k, 'name' => $v));
    $tpl->assign('viewmode', $t_vm);
    $t_vm->clear();
}
// Warn me before ...
if (isset($_PM_['calendar']['warn_mode']) && $_PM_['calendar']['warn_mode'] != '-') {
    $tpl->assign_block('warn');
    if ('s' == $_PM_['calendar']['warn_mode']) {
        $tpl->assign_block('s_w_s');
    }
    if ('e' == $_PM_['calendar']['warn_mode']) {
        $tpl->assign_block('s_w_e');
    }
    if ($_PM_['calendar']['warn_time'] >= 604800) {
        $tpl->assign_block('s_w_w');
        $tpl->assign('warn_time', $_PM_['calendar']['warn_time'] / 604800);
    } elseif ($_PM_['calendar']['warn_time'] >= 86400) {
        $tpl->assign_block('s_w_d');
        $tpl->assign('warn_time', $_PM_['calendar']['warn_time'] / 86400);
    } elseif ($_PM_['calendar']['warn_time'] >= 3600) {
        $tpl->assign_block('s_w_h');
        $tpl->assign('warn_time', $_PM_['calendar']['warn_time'] / 3600);
    } elseif ($_PM_['calendar']['warn_time'] >= 60) {
        $tpl->assign_block('s_w_m');
        $tpl->assign('warn_time', $_PM_['calendar']['warn_time'] / 60);
    } else {
        $tpl->assign_block('s_w_m');
        $tpl->assign('warn_time', 0);
    }
}
// Userdaten für externe Emailadresse
$userdata = $DB->get_usrdata($_SESSION['phM_uid']);
// Block für externe Benachrichtigung via SMS
$smsactive = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
if ($smsactive) {
    $smsactive = ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_sms']);
}
if ($smsactive) {
    $t_ea = $tpl->get_block('external_alerting');
    if (isset($_PM_['core']['sms_sender']) && $_PM_['core']['sms_sender']) {
        $t_ea->fill_block('warnsms_profiles', array('sms' => $_PM_['core']['sms_sender']));
    }
    $tpl->assign('external_alerting', $t_ea);
}
// Fill the warnmail combobox
$available_emails = array();
if ($userdata['externalemail']) {
    $available_emails[$userdata['externalemail']] = 1;
}
if ($userdata['email']) {
    $available_emails[$userdata['email']] = 1;
}
$Acnt = new DB_Controller_Account();
foreach ($Acnt->getAccountIndex($_SESSION['phM_uid'], true, false) as $k => $v) {
    $accdata = $Acnt->getAccount($_SESSION['phM_uid'], false, $k);
    if ($accdata['address']) {
        $available_emails[$accdata['address']] = 1;
    }
}
$t_wmp = $tpl->get_block('warnmail_profiles');
foreach ($available_emails as $k => $v) {
    $t_wmp->assign('email', $k);
    $tpl->assign('warnmail_profiles', $t_wmp);
    $t_wmp->clear();
}
$tpl->assign(array(
        'target_link' => htmlspecialchars($link_base.'&whattodo=save'),
        'warn_mail' => isset($_PM_['calendar']['mailto']) ? $_PM_['calendar']['mailto'] : $userdata['email'],
        'warn_sms' => isset($_PM_['calendar']['smsto']) ? $_PM_['calendar']['smsto'] : $_PM_['core']['sms_sender']
        ));
