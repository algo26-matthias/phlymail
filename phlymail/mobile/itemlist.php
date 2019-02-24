<?php
/**
 * Handle display of item lists in a mobile phlyMail
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2012-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.6 2015-02-12 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_REQUEST['f'])) {
    die('Wrong call');
}
if (!file_exists($_PM_['path']['handler'].'/'.$HDL.'/api.php')) {
    die('Wrong call');
}
require_once($_PM_['path']['handler'].'/'.$HDL.'/api.php');
$call = 'handler_'.$HDL.'_api';
if (!in_array('selectfile_itemlist', get_class_methods($call))) {
    die('Wrong call either');
}
$API = new $call($_PM_, $_SESSION['phM_uid']);
// Build up links
$link_base = PHP_SELF.'?'.give_passthrough(1);
$icon_path = $_PM_['path']['theme'].'/icons/';
$passthru1 = give_passthrough(1);
$fold_link = PHP_SELF.'?h='.$HDL.'&amp;l=item&amp;'.$passthru1.'&amp;i=';

$tpl = new phlyTemplate($_PM_['path']['templates'].'listitem.general.tpl');
$t_l = $tpl->get_block('line');

try {
    $info = $API->get_folder_info($_REQUEST['f']);
    $tpl->assign('pageTitle', phm_entities($info['foldername']));
} catch (Exception $e) {
    // void
}

$itemCount = 0;
foreach ($API->selectfile_itemlist($_REQUEST['f']) as $k => $v) {
    $t_l->assign(array(
            'id' => $HDL.'_'.$k,
            'link' => $fold_link.$v['id'],
            'primary' => !empty($v['l1']) ? $v['l1'] : '&nbsp;'
            ));
    if (!empty($v['l1_ico'])) {
        $t_l->fill_block('primary_icon', array('src' => $v['l1_ico'], 'alt' => !empty($v['l1_ico_alt']) ? $v['l1_ico_alt'] : ''));
    }
    if (!empty($v['prioicon'])) {
        $t_l->fill_block('priority_icon', array('src' => $v['prioicon'], 'alt' => !empty($v['priotext']) ? $v['priotext'] : ''));
    }
    if (!empty($v['att'])) {
        $t_l->assign_block('has_attach');
    }
    if (!empty($v['colour'])) {
        $t_l->fill_block('has_colour', 'colour', $v['colour']);
    }
    if (!empty($v['l2'])) {
        $t_l->fill_block('secondary', 'secondary', $v['l2']);
    }
    if (!empty($v['l3'])) {
        $t_l->fill_block('tertiary', 'tertiary', $v['l3']);
    }
    if (!empty($v['aside'])) {
        $t_l->fill_block('aside', 'aside', $v['aside']);
    }
    if (!empty($v['thumb'])) {
        $t_l->fill_block('thumb', array('src' => $v['thumb'], 'alt' => !empty($v['mime']) ? $v['mime'] : ''));
    } elseif (!empty($v['i32'])) {
        $t_l->fill_block('icon', array('src' => $v['i32'], 'alt' => !empty($v['mime']) ? $v['mime'] : ''));
    }
    $tpl->assign('line', $t_l);
    $t_l->clear();
    $itemCount++;
}
if (!$itemCount) {
    $tpl->assign_block('folderisempty');
}
