<?php
/**
 * When called in initialisation mode, this module delivers a div container
 * holding a file selector, which allows to pick a file from any of the supporting
 * handlers.
 * What items are available, depends on the handlers.
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2003-2013 phlyLabs Berlin, http://phlylabs.de
 * @version 0.1.0 2013-07-07 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// AJAX request for listing items within a given folder or receiving info about file
if (isset($_REQUEST['h']) && isset($_REQUEST['f'])) {
    $h = basename($_REQUEST['hdl']);
    $f = basename($_REQUEST['f']);
    if (!file_exists($_PM_['path']['handler'].'/'.$h.'/api.php')) sendJS(array('nix'), 1, 1);

    $clsnam = 'handler_'.$h.'_api';
    $API = new $clsnam($_PM_, $_SESSION['phM_uid']);
    // Actual folder contents' output
    $items = $API->selectfile_itemlist($f);
    sendJS($items, 1, 1);
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'core.fileselector.tpl');
$tpl->assign(array
        ('msg_ok' => $WP_msg['ok']
        ,'ilist_url' => PHP_SELF.'?h=core&l=selectfile&'.give_passthrough(1)
        ));
$t_lifo = $tpl->get_block('listfolder');
foreach ($_SESSION['phM_uniqe_handlers'] as $type => $data) {
    if (!file_exists($_PM_['path']['handler'].'/'.basename($type).'/api.php')) {
        continue;
    }
    require_once($_PM_['path']['handler'].'/'.basename($type).'/api.php');
    // Check, whether the handler offers the necessary methods
    if (!in_array('give_folderlist', get_class_methods('handler_'.$type.'_api'))) {
        continue;
    }
    if (!in_array('selectfile_itemlist', get_class_methods('handler_'.$type.'_api'))) {
        continue;
    }
    if (!in_array('sendto_fileinfo', get_class_methods('handler_'.$type.'_api'))) {
        continue;
    }
    // Denote the handler the following structure belongs to
    $t_lifo->fill_block('fhead', array('handler_icon' => $type.'.png', 'handler' => phm_entities($data['i18n'])));
    $tpl->assign('listfolder', $t_lifo);
    $t_lifo->clear();
    // Actual folder structure output
    $clsnam = 'handler_'.$type.'_api';
    $API = new $clsnam($_PM_, $_SESSION['phM_uid']);
    foreach ($API->give_folderlist() as $k => $v) {
        $t_lifo->fill_block('folder', array
                ('id' =>  $k
                ,'handler' => $type
                ,'icon' => $v['icon']
                ,'name' => phm_entities($v['foldername'])
                ,'spacer' => $v['level']*16
                ));
        $tpl->assign('listfolder', $t_lifo);
        $t_lifo->clear();
    }
}
