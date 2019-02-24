<?php
/**
 * Setup "Register Now"
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['regnow_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo']) && $_REQUEST['whattodo']) ? $_REQUEST['whattodo'] : false;
$WP_return = '';
if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['regnow_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $tokvar = array
    		('auth' => array('show_register' => (isset($_REQUEST['WP_registershow']) && $_REQUEST['WP_registershow']))
    		,'core' => array('systememail' => $_REQUEST['WP_systememail']
                    ,'reg_mailadm_subj' => phm_stripslashes($_REQUEST['WP_mailadmsubj'])
                    ,'reg_mailuser_subj' => phm_stripslashes($_REQUEST['WP_mailusersubj'])
                    ,'reg_mailadm' => (isset($_REQUEST['WP_mailadm']) && $_REQUEST['WP_mailadm'])
                    ,'reg_mailuser' => (isset($_REQUEST['WP_mailuser']) && $_REQUEST['WP_mailuser'])
                    ,'reg_defaultgroups' => array()
                    )
    		);
    // Groups in request?
    if (isset($_REQUEST['groups'])) {
        $tokvar['core']['reg_defaultgroups'] = $_REQUEST['groups'];
    }
    // Flatten
    $tokvar['core']['reg_defaultgroups'] = implode(',', $tokvar['core']['reg_defaultgroups']);

    if (basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar)) {
        if (isset($_REQUEST['WP_regmailadmtext']) && $_REQUEST['WP_regmailadmtext']) {
            $suf = fopen($_PM_['path']['conf'].'/regmail.admtext.wpop', 'w');
            fwrite($suf, phm_stripslashes($_REQUEST['WP_regmailadmtext']));
            fclose($suf);
        } elseif (file_exists($WP_core['conf_files'].'/regmail.admtext.wpop')) {
        	unlink($WP_core['conf_files'].'/regmail.admtext.wpop');
        }
        if (isset($_REQUEST['WP_regmailusertext']) && $_REQUEST['WP_regmailusertext']) {
            $suf = fopen($_PM_['path']['conf'].'/regmail.usertext.wpop', 'w');
            fwrite($suf, phm_stripslashes($_REQUEST['WP_regmailusertext']));
            fclose($suf);
        } elseif (file_exists($WP_core['conf_files'].'/regmail.usertext.wpop')) {
        	unlink($WP_core['conf_files'].'/regmail.usertext.wpop');
        }
        $WP_return = $WP_msg['optssaved'];
    } else {
        $WP_return = $WP_msg['optsnosave'];
    }
    header('Location: '.$link_base.'regnow&WP_return='.urlencode($WP_return));
    exit();
}
if (file_exists($_PM_['path']['conf'].'/regmail.usertext.wpop')) {
    $regmailusertext = file_get_contents($_PM_['path']['conf'].'/regmail.usertext.wpop');
}
if (file_exists($_PM_['path']['conf'].'/regmail.admtext.wpop')) {
    $regmailadmtext = file_get_contents($_PM_['path']['conf'].'/regmail.admtext.wpop');
}
$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.regnow.tpl');
$tpl->assign(array
        ('target_link' => htmlspecialchars($link_base.'regnow&whattodo=save')
        ,'WP_return' => urldecode($WP_return)
        ,'head_text' => $WP_msg['SuHeadReg']
        ,'msg_registershow' => $WP_msg['SuOptRegisterShow']
        ,'msg_regsystmail' => $WP_msg['SuRegSystmail']
        ,'systememail' => isset($_PM_['core']['systememail']) ? $_PM_['core']['systememail'] : ''
        ,'msg_regmailuser' => $WP_msg['SuOptRegMailUser']
        ,'msg_subject' => $WP_msg['subject']
        ,'reg_mailuser_subj' => isset($_PM_['core']['reg_mailuser_subj']) ? phm_entities($_PM_['core']['reg_mailuser_subj']) : ''
        ,'msg_regmailusertext' => $WP_msg['SuRegMailUserText']
        ,'regmailusertext' => isset($regmailusertext) ? phm_entities($regmailusertext) : ''
        ,'msg_regmailadm' => $WP_msg['SuOptRegMailAdm']
        ,'reg_mailadm_subj' => isset($_PM_['core']['reg_mailadm_subj']) ? phm_entities($_PM_['core']['reg_mailadm_subj']) : ''
        ,'msg_mailadmtext' => $WP_msg['SuRegMailAdmText']
        ,'regmailadmtext' => isset($regmailadmtext) ? phm_entities($regmailadmtext) : ''
        ,'msg_save' => $WP_msg['save']
        ,'link_base' => htmlspecialchars($link_base)
        ,'LegRegNow' => $WP_msg['LegRegNow']
        ,'LegMailUser' => $WP_msg['LegMailUser']
        ,'LegMailAdm' => $WP_msg['LegMailAdmin']
        ,'msg_defaultgroups' => $WP_msg['RegNowDefaultGroups']
        ));
if (isset($_PM_['auth']['show_register']) && $_PM_['auth']['show_register']) $tpl->assign_block('regshow');
if (isset($_PM_['core']['reg_mailuser']) && $_PM_['core']['reg_mailuser']) $tpl->assign_block('mailuser');
if (isset($_PM_['core']['reg_mailadm']) && $_PM_['core']['reg_mailadm']) $tpl->assign_block('mailadm');
$groupsOut = false;
if (isset($DB->features['groups']) && $DB->features['groups']) {
    $groups = $DB->get_grouplist(false);
    $definedroups = isset($_PM_['core']['reg_defaultgroups']) ? explode(',', $_PM_['core']['reg_defaultgroups']) : array();
    if (!empty($groups)) {
        $t_grpl = $tpl->get_block('groupline');
        regnow_out_groups($groups['childs'], 0, 0, $definedroups); // The structure allows hierarchic groups, so a helper is needed
        $groupsOut = true;
    }
}
if (!$groupsOut) {
    $tpl->fill_block('groupline', array('gname' => '-', 'gid' => ''));
}

function regnow_out_groups(&$groups, $child = 0, $level = 0, $usergroups = array())
{
    foreach ($groups[$child] as $v) {
        $GLOBALS['t_grpl']->assign(array('gname' => str_repeat('&nbsp;&nbsp;', $level).$v['friendly_name'], 'gid' => $v['gid']));
        if (in_array($v['gid'], $usergroups)) $GLOBALS['t_grpl']->assign_block('sel');
        $GLOBALS['tpl']->assign('groupline', $GLOBALS['t_grpl']);
        $GLOBALS['t_grpl']->clear();
        if (isset($groups[$v['gid']])) regnow_out_groups($groups, $v['gid'], $level+1, $usergroups);
    }
}
