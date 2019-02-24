<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Contacts Handler
 * @copyright 2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2015-04-14 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$link_base = PHP_SELF.'?l=setup&h=contacts&'.give_passthrough(1);

$cDB = new handler_contacts_driver($_SESSION['phM_uid']);

if (!empty($_REQUEST['save'])) {
    $status = $cDB->update_freefield_types($_REQUEST['free']);
    if ($status === true) {
        $_REQUEST['WP_return'] = $WP_msg['optssaved'];
    } else {
        $_REQUEST['WP_return'] = $WP_msg[$status];
    }
}
if (!empty($_REQUEST['deletefield'])) {
    $cDB->delete_freefield_type($_REQUEST['deletefield']);
    unset($_REQUEST['free'][$_REQUEST['deletefield']]);
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'setup.contacts.tpl');
if (isset($_REQUEST['WP_return'])) {
    $tpl->assign('WP_return', phm_entities($_REQUEST['WP_return']));
}

$definedFields = $cDB->get_freefield_types();
if (!empty($_REQUEST['addfield'])) {
    $definedFields[] = array('name' => '', 'token' => '', 'type' => 'text');
}
foreach ($_REQUEST['free'] as $k => $v) {
    if (!isset($definedFields[$k])) {
        continue;
    }
    $definedFields[$k] = array('name' => $v['name'], 'token' => $v['token'], 'type' => $v['type']);
}

if (!empty($definedFields)) {
    $t_fl = $tpl->get_block('freefieldline');
    foreach ($definedFields as $id => $field) {
        $t_fl->assign(array(
                'id' => $id,
                'name' => phm_entities($field['name']),
                'token' => phm_entities($field['token'])
        ));
        if ($field['type'] == 'text' || $field['type'] == 'textarea') {
            $t_fl->assign('selected_'.$field['type'], ' selected');
        }
        $tpl->assign('freefieldline', $t_fl);
        $t_fl->clear();
    }
}
$tpl->assign('target_link', phm_entities($link_base.'&save=1'));