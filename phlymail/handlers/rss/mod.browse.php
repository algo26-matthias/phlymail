<?php
/**
 * Browser for moving / copying items around
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler RSS
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.6 2013-08-09 $Id: mod.browse.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();
$listmode = 'browse';
$only_handler = 'rss';
require_once($_PM_['path']['frontend'].'/folderlist.php');
$tpl->assign(array('mode' => $_REQUEST['mode'], 'handler' => 'rss'));
$content = $tpl;
$tpl = new phlyTemplate($_PM_['path']['templates'].'folderbrowser.container.tpl');
$tpl->assign(array('folderlist' => $content, 'head_select' => $WP_msg['FldrBrwsSelect'], 'msg_select' => $WP_msg['Select']));
