<?php
/**
 * Offers JS API for selecting emails / phone numbers
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Contatcs handler
 * @copyright 2001-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.4 2016-06-10
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_PM_['core']['sms_global_prefix'])) $_PM_['core']['sms_global_prefix'] = false;

$what      = !empty($_REQUEST['what']) ? $_REQUEST['what'] : 'email';
$gfilter   = !empty($_REQUEST['gfilter']) ? intval($_REQUEST['gfilter']) : 0;
$ord_by    = !empty($_REQUEST['ord_by']) ? $_REQUEST['ord_by']  : false;
$ord_dir   = !empty($_REQUEST['ord_dir']) ? $_REQUEST['ord_dir'] : false;

$ADB = new handler_contacts_driver($_SESSION['phM_uid']);

if (!empty($_REQUEST['json'])) {
    $prefix = true;
    if ($what == 'fax') {
        $filterFields = array('fax', 'comp_fax');
    } elseif ($what == 'phone') {
        $filterFields = array('cellular', 'comp_cellular', 'tel_private', 'tel_business');
    } else {
        $filterFields = array('email1', 'email2');
        $prefix = false;
    }
    $return = array();
    foreach ($ADB->get_adridx(CONTACTS_VISIBILITY_MODE, $gfilter, '', '', 0, 0, $ord_by, $ord_dir) as $line) {
        $record = array('name' => $line['displayname'], 'fname' => $line['firstname'], 'lname' => $line['lastname']);
        $hit = false;
        foreach ($filterFields as $field) {
            if (!$line[$field]) continue; // Nix drin
            $hit = true;
            if ($prefix) {
                $test = $line[$field];
                // Automatically add country code, if needed
                if (!preg_match('!^(\+|00)!', $test) && $_PM_['core']['sms_global_prefix']) {
                    $test = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $test);
                }
                $test = preg_replace('!^\+!', '00', $test);
                $test = preg_replace('![^0-9]!', '', $test);
                if (!preg_match('!^00!', $test)) continue; // Sieht nicht nach Rufnummer aus
                $record[$field] = $test;
            } else {
                $record[$field] = $line[$field];
            }
        }
        if (!$hit) {
            continue; // No number / email address for that record
        }
        $return[] = $record;
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($return);
    exit;
}
// For jQuery UI autocomplete
if (!empty($_REQUEST['jqui'])) {
    $term = phm_stripslashes($_REQUEST['term']);
    $result = $ADB->search_contact($term, $what, false, CONTACTS_PUBLIC_CONTACTS);
    $out = array();
    foreach ($result as $k => $gefunden) {
        $record = array
                ('nick' => $gefunden['nick']
                ,'fname' => $gefunden['firstname']
                ,'lname' => $gefunden['lastname']
                );
        if ('email' == $what) {
            if ($gefunden['email1']) {
                $out[] = array_merge($record, array('email' => $gefunden['email1']));
            }
            if ($gefunden['email2']) {
                $out[] = array_merge($record, array('email' => $gefunden['email2']));
            }
        } elseif ('fax' == $what) {
            if ($gefunden['comp_fax']) {
                $out[] = array_merge($record, array('fax' => $gefunden['comp_fax']));
            }
            if ($gefunden['fax']) {
                $out[] = array_merge($record, array('fax' => $gefunden['fax']));
            }
        } else {
            if ($gefunden['comp_cellular']) {
                $out[] = array_merge($record, array('cell' => $gefunden['comp_cellular']));
            }
            if ($gefunden['cellular']) {
                $out[] = array_merge($record, array('cell' => $gefunden['cellular']));
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($out);
    exit;
}
// For jQuery mobile autocomplete
if (!empty($_REQUEST['jqm'])) {
    $term = phm_stripslashes($_REQUEST['term']);
    $result = $ADB->search_contact($term, $what, false, CONTACTS_PUBLIC_CONTACTS);
    $out = array();
    foreach ($result as $k => $gefunden) {
        $record = !empty($gefunden['nick']) ? $gefunden['nick'] : trim($gefunden['firstname'].' '.$gefunden['lastname']);
        if ('email' == $what) {
            if ($gefunden['email1']) {
                $out[] = $gefunden['email1'].' ('.$record.')';
            }
            if ($gefunden['email2']) {
                $out[] = $gefunden['email2'].' ('.$record.')';
            }
        } elseif ('fax' == $what) {
            if ($gefunden['comp_fax']) {
                $out[] = $gefunden['comp_fax'].' ('.$record.')';
            }
            if ($gefunden['fax']) {
                $out[] = $gefunden['fax'].' ('.$record.')';
            }
        } else {
            if ($gefunden['comp_cellular']) {
                $out[] = $gefunden['comp_cellular'].' ('.$record.')';
            }
            if ($gefunden['cellular']) {
                $out[] = $gefunden['cellular'].' ('.$record.')';
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($out);
    exit;
}

if (isset($_REQUEST['find'])) {
    $find = phm_stripslashes($_REQUEST['find']);
    $result = $ADB->search_contact($find, $what, false, CONTACTS_PUBLIC_CONTACTS);
    $out = array();
    foreach ($result as $k => $gefunden) {
        $prefix = '{"nick": "'.addcslashes($gefunden['nick'], '"').'","fname": "'.addcslashes($gefunden['firstname'], '"')
                .'","lname": "'.addcslashes($gefunden['lastname'], '"').'"';
        if ('email' == $what) {
            $out[] = $prefix.',"email1": "'.addcslashes($gefunden['email1'], '"').'","email2": "'.addcslashes($gefunden['email2'], '"').'"}';
        } elseif ('fax' == $what) {
            if ($gefunden['comp_fax']) {
                $out[] = $prefix.',"fax" :"'.addcslashes($gefunden['comp_fax'], '"').'"}';
            }
            if ($gefunden['fax']) {
                $out[] = $prefix.',"fax": "'.addcslashes($gefunden['fax'], '"').'"}';
            }
        } else {
            if ($gefunden['comp_cellular']) {
                $out[] = $prefix.',"cell": "'.addcslashes($gefunden['comp_cellular'], '"').'"}';
            }
            if ($gefunden['cellular']) {
                $out[] = $prefix.',"cell": "'.addcslashes($gefunden['cellular'], '"').'"}';
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"adb_found" : ['.implode(',', $out).']}';
    exit;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.apiselect.tpl');
$tpl->assign(array
        ('gtarget' => htmlspecialchars(PHP_SELF.'?l=apiselect&h=contacts&what='.$what.'&'.give_passthrough().'&ord_by='.$ord_by.'&ord_dir='.$ord_dir)
        ,'insert' => $WP_msg['Insert']
        ,'desc1' => $WP_msg['APIdesc1']
        ,'desc2' => $WP_msg['APIdesc2']
        ,'msg_onlygroup' => $WP_msg['APIonlyGroup']
        ,'msg_all' => $WP_msg['all']
        ));
// Allow limiting list ot certain groups
$t_gl = $tpl->get_block('groupsel');
foreach ($ADB->get_grouplist(1) as $k => $line) {
    $cnt = $ADB->get_adrcount(CONTACTS_VISIBILITY_MODE, $line['gid']);
    $t_gl->assign(array('gid' => $line['gid'], 'gname' => $line['name'] . '(' . $cnt . ')'));
    if ($gfilter == $line['gid']) $t_gl->assign_block('sel');
    $tpl->assign('groupsel', $t_gl);
    $t_gl->clear();
}

if ($ADB->get_adrcount(1, $gfilter) > 0) {
    $selblk = ($what == 'phone' || $what == 'fax') ? 'sel_phone' : 'sel_mail';

    $key = 0;
    $t_ent = $tpl->get_block('entry');
    $tpl_name = $t_ent->get_block('name');
    $tpl_sel  = $t_ent->get_block($selblk);
    if (false !== $gfilter) {
        $tpl->fill_block('sendtogroup', 'msg_sendtogroup', $WP_msg['SendToGroup']);
        $t_sendgroup = $tpl->get_block('addgroupmember');
    }
    if ('phone' == $what || 'fax' == $what) {
        $tpl->assign_block('isphone');
        $filterFields = ('phone' == $what)
                ? array('cellular', 'comp_cellular', 'tel_private', 'tel_business')
                : array('fax', 'comp_fax');

        foreach ($ADB->get_adridx(1, $gfilter, '', '', 0, 0, $ord_by, $ord_dir) as $line) {
            $fetched = array();
            // Find valid phone numbers
            foreach ($filterFields as $field) {
                if (!$line[$field]) continue;
                $test = $line[$field];
                // Automatically add country code, if needed
                if (!preg_match('!^(\+|00)!', $test) && $_PM_['core']['sms_global_prefix']) {
                    $test = preg_replace('!^0(?=[1-9]+)!', $_PM_['core']['sms_global_prefix'], $test);
                }
                $test = preg_replace('!^\+!', '00', $test);
                $test = preg_replace('![^0-9]!', '', $test);
                if (!preg_match('!^00!', $test)) continue;
                $fetched[$test] = $line[$field];
                if ('cellular' == $field && false !== $gfilter) {
                    $t_sendgroup->assign('id', $key);
                    $tpl->assign('addgroupmember', $t_sendgroup);
                    $t_sendgroup->clear();
                }
            }
            if (empty($fetched)) continue;
            //
            // Only attach Groupname, if one is given
            $groupstring = ($line['group']) ? $line['group'] : '';
            $tpl_name->assign('group', $groupstring);
            $tpl_name->assign('nickname', $line['displayname']);
            $t_ent->assign('name', $tpl_name);
            $tpl_name->clear();
            foreach ($fetched as $nice => $raw) {
                $tpl_sel->assign(array('key' => $key, 'value' => $raw, 'mobile' => $raw, 'msg_sel' => $WP_msg['Insert'], 'msg_to' => $WP_msg['To']));
                $t_ent->assign($selblk, $tpl_sel);
                $tpl_sel->clear();
                ++$key;
            }
            $tpl->assign('entry', $t_ent);
            $t_ent->clear();
        }
    } else {
        $tpl->assign_block('ismail');
        foreach ($ADB->get_adridx(1, $gfilter, '', '', 0, 0, $ord_by, $ord_dir) as $line) {
            if ($line['email1'] == '' && $line['email2'] == '') continue;
            // Only attach Groupname, if one is given
            $groupstring = ($line['group']) ? $line['group'] : '';

            $tpl_name->assign('nickname', $line['displayname']);
            $tpl_name->assign('group', $groupstring);
            $t_ent->assign('name', $tpl_name);
            $tpl_name->clear();
            if ($line['email1']) {
                $tpl_sel->assign(array('key' => $key, 'email' => $line['email1'], 'msg_to' => $WP_msg['To'], 'msg_cc' => $WP_msg['Cc'], 'msg_bcc' => $WP_msg['Bcc']));
                $t_ent->assign($selblk, $tpl_sel);
                $tpl_sel->clear();
                // Allow to send to group
                if (false !== $gfilter) {
                    $t_sendgroup->assign('id', $key);
                    $tpl->assign('addgroupmember', $t_sendgroup);
                    $t_sendgroup->clear();
                }
            }
            if ($line['email2']) {
                ++$key;
                $tpl_sel->assign(array('key' => $key, 'email' => $line['email2'], 'msg_to' => $WP_msg['To'], 'msg_cc' => $WP_msg['Cc'], 'msg_bcc' => $WP_msg['Bcc']));
                $t_ent->assign($selblk, $tpl_sel);
                $tpl_sel->clear();
            }
            $tpl->assign('entry', $t_ent);
            $t_ent->clear();
            ++$key;
        }
    }
} else {
    $t_no = $tpl->get_block('nothing');
    $t_no->assign('msg_none', $WP_msg['APInone']);
    $tpl->assign('nothing', $t_no);
}
