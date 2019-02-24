<?php
/**
 * Allow brwosing folders fro copying/moving mails
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.3 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$listmode = 'browse';
$only_handler = 'email';
require_once($_PM_['path']['frontend'].'/folderlist.php');
$tpl->assign(array('mode' => $_REQUEST['mode'], 'handler' => 'email'));
$content = $tpl;
$tpl = new phlyTemplate($_PM_['path']['templates'].'folderbrowser.container.tpl');
$tpl->assign(array('folderlist' => $content, 'head_select' => $WP_msg['FldrBrwsSelect'], 'msg_select' => $WP_msg['Select']));
