<?php
/**
 * Handle display of folder lists in a mobile phlyMail
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!empty($_SESSION['phM_folderCache'][$HDL])) {
    $folders = $_SESSION['phM_folderCache'][$HDL];
} else {
    if (!file_exists($_PM_['path']['handler'].'/'.$HDL.'/api.php')) {
        die('Wrong call');
    }
    try {
        $call = 'handler_'.$HDL.'_api';
        $API = new $call($_PM_, $_SESSION['phM_uid']);
        $folders = $API->give_folderlist();
        unset($API);
    } catch (Exception $e) {
        $folders = array();
    }
}

// Build up links
$link_base = PHP_SELF.'?'.give_passthrough(1);
$icon_path = $_PM_['path']['theme'].'/icons/';
$passthru1 = give_passthrough(1);
$fold_link = PHP_SELF.'?h='.$HDL.'&amp;a=ilist&amp;'.$passthru1.'&amp;f=';

$tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.general.tpl');

$t_l = $tpl->get_block('line');
$t_ld = $t_l->get_block('divider');
$t_lt = $t_l->get_block('target');
$t_ln = $t_l->get_block('notarget');

// Current level
$myLevel = (!empty($_REQUEST['lvl']) ? intval($_REQUEST['lvl']) : 0);
// parsing mode
$parsingMode = 1;

// Allow to selectively extract substructures
$noOutput = false;
if ($myLevel > 0) {
    $noOutput = true;
}
foreach ($folders as $k => $v) {
    // Level passed in? Skip everything above that
    if ($v['level'] < $myLevel) {
        continue;
    }
    if ($noOutput == false && $myLevel == $v['level'] && $v['childof'] != $folder) {
        $noOutput = true;
    }
    if ($noOutput == true && $myLevel == $v['level'] && $v['childof'] == $folder) {
        $noOutput = false;
    }

    $myLink = $fold_link.$k;
    if ($v['level'] < 1 && $v['has_items'] == 0) {
        $parsingMode = 2;
        $myLink = PHP_SELF.'?h='.$HDL.'&amp;a=flist&amp;'.$passthru1.'&amp;f='.$k.'&amp;lvl='.($v['level']+1);
    } elseif (2 == $parsingMode) {
        continue;
    }
    if ($noOutput == true) {
        continue;
    }

    // Properties and permissions
    $propList = array();
    // A renamable folder
    if ($v['type'] == 1 || $v['type'] == 11) {
        $propList[] = 'rename';
        $propList[] = 'is_user';
        $propList[] = 'move';
        $propList[] = 'dele';
    }
    if (!empty($v['has_folders'])) {
        $propList[] = 'has_folders';
    }
    if (!empty($v['is_trash'])) {
        $propList[] = 'is_trash';
    }
    if (!empty($v['is_junk'])) {
        $propList[] = 'is_junk';
    }
    if (!empty($v['has_items'])) {
        $propList[] = 'has_items';
    }
    $t_lt->assign(array
            ('proplist' => join(',', $propList)
            ,'id' => $HDL.'_'.$k
            ,'link' => $myLink
            ,'title' => phm_entities($v['foldername'])
            ,'level' => $v['level'] - $myLevel // align leftmost
            ));
    $t_lt->fill_block('ticon', array('src' => $v['icon']));
    if (!empty($v['unread'])) {
        $t_lt->fill_block('count', 'count', $v['unread']);
    }
    $t_l->assign('target', $t_lt);
    $t_lt->clear();

    $tpl->assign('line', $t_l);
    $t_l->clear();
}
