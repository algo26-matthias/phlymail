<?php
/**
 * Setup AntiJunk and AntiVirus options
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2005-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.4 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['junk_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;

if ('save' == $whattodo) {
    if (!isset($_SESSION['phM_perm_write']['junk_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $tokvar['antijunk'] = array
            ('use_feature' => isset($_REQUEST['use_feature']) ? $_REQUEST['use_feature'] : 0
            ,'cmd_check' => isset($_REQUEST['pathSA']) ? $_REQUEST['pathSA'] : ''
            ,'cmd_learnspam' => isset($_REQUEST['markSPAM']) ? $_REQUEST['markSPAM'] : ''
            ,'cmd_learn_ham' => isset($_REQUEST['unmarkSPAM']) ? $_REQUEST['unmarkSPAM'] : ''
            ,'check_maxsize' => isset($_REQUEST['maxsize']) ? $_REQUEST['maxsize'] : false
            );
    if (!isset($WP_newsessionip)) $WP_newsessionip = 0;
    $truth = basics::save_config($_PM_['path']['conf'].'/choices.ini.php', $tokvar);
    $WP_return = ($truth) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'junk&WP_return='.urlencode($WP_return));
    exit();
}
$tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.antijunk.tpl');
$tpl->assign(array('target_link' => htmlspecialchars($link_base.'junk&whattodo=save')
        ,'link_base' => htmlspecialchars($link_base)
        ,'WP_return' => $WP_return
        ,'head_text' => $WP_msg['HeadJunkprotection']
        ,'leg_junkprotect' => $WP_msg['JunkLegUseThis']
        ,'msg_junkprotect' => $WP_msg['JunkUseThis']
        ,'about_junkprotect' => $WP_msg['JunkAboutUseThis']
        ,'leg_pathSA' => $WP_msg['JunkLegFilter']
        ,'msg_pathSA' => $WP_msg['JunkPathSA']
        ,'about_pathSA' => $WP_msg['JunkAboutPathSA']
        ,'leg_userland' => $WP_msg['JunkLegUserland']
        ,'msg_markSPAM' => $WP_msg['JunkCmdSPAM']
        ,'msg_unmarkSPAM' => $WP_msg['JunkCmdNoSPAM']
        ,'about_markSPAM' => $WP_msg['JunkAboutCmdSPAM']
        ,'msg_save' => $WP_msg['save']
        ,'pathSA' => isset($_PM_['antijunk']['cmd_check']) ? phm_entities($_PM_['antijunk']['cmd_check']) : ''
        ,'markSPAM' => isset($_PM_['antijunk']['cmd_learnspam']) ? phm_entities($_PM_['antijunk']['cmd_learnspam']) : ''
        ,'unmarkSPAM' => isset($_PM_['antijunk']['cmd_learn_ham']) ? phm_entities($_PM_['antijunk']['cmd_learn_ham']) : ''
        ,'maxsize' => isset($_PM_['antijunk']['check_maxsize']) ? phm_entities($_PM_['antijunk']['check_maxsize']) : ''
        ));
if (isset($_PM_['antijunk']['use_feature']) && $_PM_['antijunk']['use_feature']) {
    $tpl->assign_block('junkprotect');
}
