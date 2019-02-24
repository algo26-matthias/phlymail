<?php
/**
 * Selecting a signature to append to the mail body
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Core
 * @copyright 2005-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$tpl = new phlyTemplate($_PM_['path']['templates'].'send.selectsig.tpl');
$Acnt = new DB_Controller_Account();
// Getting all accounts
$activecnt = 0;
$signs = array();
foreach ($Acnt->get_signature_list($_SESSION['phM_uid']) as $k => $v) {
    if (!$v['signature']) continue;
    phm_stripslashes($v);
    $signs[$k] = array('sign' => $v['signature'], 'prof' => $v['title']);
    ++$activecnt;
}
if ($activecnt) {
    $t_s = $tpl->get_block('sigs');
    foreach ($signs as $k => $v) {
        $t_s->assign(array('profile' => $v['prof'], 'profid' => $k, 'sig' => $v['sign'], 'htmlsig' => text2html($v['sign'])));
        $tpl->assign('sigs', $t_s);
        $t_s->clear();
    }
} else {
    $tpl->assign_block('no_sigs');
}
$tpl->assign(array
        ('msg_select' => $WP_msg['Select']
        ,'about_select' => $WP_msg['AboutSigSel']
        ,'msg_no_sigs' => $WP_msg['NoSigAvail']
        ));

