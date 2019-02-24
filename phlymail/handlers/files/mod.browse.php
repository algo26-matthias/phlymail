<?php
/**
 * Allow brwosing folders for copying/moving files
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.5 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$listmode = 'browse';
$only_handler = 'files';
require_once($_PM_['path']['frontend'].'/folderlist.php');
$tpl->assign(array('mode' => $_REQUEST['mode'], 'handler' => 'files'));
$content = $tpl;
$tpl = new phlyTemplate($_PM_['path']['templates'].'folderbrowser.container.tpl');
$tpl->assign(array('folderlist' => $content, 'head_select' => $WP_msg['FldrBrwsSelect'], 'msg_select' => $WP_msg['Select']));
