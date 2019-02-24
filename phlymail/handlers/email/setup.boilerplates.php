<?php
/**
 * Boilerplates setup
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @subpackage Boilerplates
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.2 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$global = 0;
if (!empty($_REQUEST['global']) && (!empty($_SESSION['phM_privs']['all']) || !empty($_SESSION['phM_privs']['email_edit_global_boilerplates']))) {
    $global = 1;
}

$link_base = PHP_SELF.'?l=setup&h=email&mod=boilerplates&'.give_passthrough(1).($global ? '&global=1' : '').'&mode=';
$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : false;

$EBP = new handler_email_boilerplates($global ? 0 : $_SESSION['phM_uid']);
$enabled = (isset($EBP->enabled) && $EBP->enabled) ? true : false;

if ($mode) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!$enabled) {
        die('{"error":"'.addcslashes($WP_msg['BPlateNotAvail'], '"').'"}');
    }
}

if ($mode == 'list') {
    echo json_encode(array('platelist' => $EBP->get_list(false, $_REQUEST['gid'], false)));
    exit;
}

if ($mode == 'edit') {
    $item = $EBP->get_item(intval($_REQUEST['id']));
    if (!empty($item)) {
        $item['body'] = str_replace(array(LF, CRLF), LF, phm_stripslashes($item['body']));
        echo json_encode(array('plate' => $item));
    }
    exit;
}

if ($mode == 'dele') {
    $EBP->drop_item(intval($_REQUEST['id']));
    die('{"platesaved":"1"}');
}

if ($mode == 'savenew' || $mode == 'saveold') {
    $ret = false;
    $payload = array('name' => $_REQUEST['name'], 'type' => $_REQUEST['type'], 'gid' => $_REQUEST['gid']
            ,'body' => str_replace(CRLF, LF, $_REQUEST['body']));
    if ('savenew' == $mode) {
        $ret = $EBP->add_item($payload);
    } elseif ('saveold' == $mode) {
        $ret = $EBP->update_item(intval($_REQUEST['id']), $payload);
    }
    die(!$ret ? '{"error":"Saving failed"}' : '{"platesaved":"1"}');
}

if ($mode == 'newgroup' || $mode == 'oldgroup') {
    $ret = false;
    $payload = array('name' => $_REQUEST['name'], 'childof' => $_REQUEST['childof']);
    if ('newgroup' == $mode) {
        $ret = $EBP->add_group($payload);
    } elseif ('oldgroup' == $mode) {
        $ret = $EBP->update_group(intval($_REQUEST['id']), $payload);
    }
    die(!$ret ? '{"error":"Saving failed"}' : '{"groupsaved":"1"}');
}

if ($mode == 'delgroup') {
    $EBP->drop_group(intval($_REQUEST['id']));
    die('{"groupsaved":"1"}');
}

if (!$mode) {
    if (!$enabled) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['BPlateNotAvail']);
        return;
    }
    $tpl = new phlyTemplate($_PM_['path']['templates'].'email.boilerplates.tpl');

    $groups = $EBP->get_group_list(false);
    if (false === $groups) $groups = array();
    // Do we have unsorted item?
    if ($EBP->get_list(false, 0, false, true)) {
        $groups = array_merge
                (array(0 => array('id' => 0, 'childof' => 0, 'name' => $WP_msg['BPlateUnsorted'], 'level' => 0, 'subdirs' => 0, 'owner' => 0))
                ,$groups
                );
    }
    $t_lf = $tpl->get_block('listfolder');
    $t_sf = $tpl->get_block('selectfolder');
    foreach ($groups as $v) {
        if ($v['id'] != 0)  {
            $t_lf->fill_block('edit', array('msg_ren' => $WP_msg['LegRenameFolder'], 'msg_del' => $WP_msg['LegDeleteFolder']));
        }
        $t_lf->assign(array
                ('id' => $v['id']
                ,'name' => phm_entities($v['name'])
                ,'icon' => empty($v['owner']) ? $_PM_['path']['theme'].'/icons/folder_global.png' : $_PM_['path']['theme'].'/icons/folder_def.png'
                ,'spacer' => $v['level']*16
                ));
        $tpl->assign('listfolder', $t_lf);
        $t_lf->clear();
        $t_sf->assign(array
                ('id' => $v['id']
                ,'name' => str_repeat('&nbsp;', $v['level']*2).phm_entities($v['name'])
                ));
        $tpl->assign('selectfolder', $t_sf);
        $t_sf->clear();
    }
    $tpl->assign(array
            ('kill_request' => $WP_msg['BPlateReallyDel']
            ,'editlink' => $link_base.'edit&id='
            ,'savelink' => $link_base
            ,'delelink' => $link_base.'dele&id='
            ,'getplatelisturl' => $link_base.'list&gid='
            ,'msg_platename' => $WP_msg['BPlateName']
            ,'msg_platebody' => $WP_msg['BPlateBody']
            ,'msg_add_text' => $WP_msg['BPlateAddText']
            ,'msg_add_html' => $WP_msg['BPlateAddHTML']
            ,'msg_add_mgroup' => $WP_msg['BPlateAddMainGroup']
            ,'msg_add_sgroup' => $WP_msg['BPlateAddSubGroup']
            ,'msg_foldername' => $WP_msg['FolderName']
            ,'qdelfolder' => $WP_msg['ReallyDeleFolder']
            ,'msg_save' => $WP_msg['save']
            ,'msg_dele' => $WP_msg['del']
            ,'user_lang' => $WP_msg['language']
            ));
    return;
}
// Prevent bogus output on failing to match any of the above
exit;
