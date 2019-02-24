<?php
/**
 * Gathers info from other handlers to display a digest view of user's data.
 * For instance: Latest 5 mails, upcoming 5 events, ...
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2010-2012 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.5 2012-05-02
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// Refresh a single box
if (isset($_REQUEST['refresh'])) {
    if (strlen($_REQUEST['refresh'])) {
        list ($handler, $box) = explode('_', basename($_REQUEST['refresh']));
        $clsnam = 'handler_'.$handler.'_api';
        $API = new $clsnam($_PM_, $_SESSION['phM_uid']);
        sendJS(array('box' => $handler.'_'.$box, 'rows' => $API->pinboard_boxes($box)), 1, 1);
    }
    exit;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'core.pinboard.tpl');
$t_ab = $tpl->get_block('addbox');
foreach ($_SESSION['phM_uniqe_handlers'] as $handler => $v) {
    if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/api.php')) {
        continue;
    }
    require_once($_PM_['path']['handler'].'/'.$handler.'/api.php');
    if (!in_array('pinboard_boxes', get_class_methods('handler_'.$handler.'_api'))) {
        continue;
    }
    $clsnam = 'handler_'.$handler.'_api';
    $API = new $clsnam($_PM_, $_SESSION['phM_uid']);
    foreach ($API->pinboard_boxes() as $box => $v) {
        $t_ab->assign(array('handler' => $handler, 'boxname' => $box
                ,'headline' => $v['headline'], 'icon' => $v['icon'], 'action' => $v['action']
                ,'cols' => json_encode($v['cols']), 'rows' => json_encode($v['rows'])
                ));
        $tpl->assign('addbox', $t_ab);
        $t_ab->clear();
    }
    unset($API);
}
$tpl->assign(array
        ('msg_refresh' => $WP_msg['refresh']
        ,'url_refresh' => PHP_SELF.'?h=core&l=ilist&'.give_passthrough(1).'&refresh='
        ));
