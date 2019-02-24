<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.1 2013-01-22 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// This is an escaped content page (not display within the normal application frame),
// so we have to tell the skin module about which outer template we need
$outer_template = 'framed.tpl';
$passthru = give_passthrough(1);
if (!isset($_SESSION['phM_perm_read']['gcontacts_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
if (file_exists($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/contacts/lang.de.php');
}
$tpl = new phlyTemplate(CONFIGPATH.'/templates/gcontacts.edit.tpl');
$cDB = new handler_contacts_driver(0);
if (isset($_REQUEST['delete_contact']) && $_REQUEST['delete_contact']) {
    $done = $cDB->delete_contact($_REQUEST['id']);
    if ($done) {
    	send_response('{"done":1}');
    } else {
    	send_response('{"error":"Error deleting contact!"}');
    }
}

if (isset($_REQUEST['save_contact']) && $_REQUEST['save_contact']) {
    $birthday = ((isset($_REQUEST['birthday_year'])) ? ($_REQUEST['birthday_year'] + 0) : '0000')
            .'-'.((isset($_REQUEST['birthday_month']) ? $_REQUEST['birthday_month'] + 0 : '0'))
            .'-'.((isset($_REQUEST['birthday_day']) ? $_REQUEST['birthday_day'] + 0 : '0'));
    $payload = array
            ('nick' => $_REQUEST['nick']
            ,'firstname' => $_REQUEST['firstname']
            ,'lastname' => $_REQUEST['lastname']
            ,'thirdname' => $_REQUEST['thirdname']
            ,'title' => $_REQUEST['title']
            ,'company' => isset($_REQUEST['company']) ? $_REQUEST['company'] : ''
            ,'comp_role' => isset($_REQUEST['comp_role']) ? $_REQUEST['comp_role'] : ''
            ,'comp_dep' => isset($_REQUEST['comp_dep']) ? $_REQUEST['comp_dep'] : ''
            ,'comp_address' => isset($_REQUEST['comp_address']) ? $_REQUEST['comp_address'] : ''
            ,'comp_address2' => isset($_REQUEST['comp_address2']) ? $_REQUEST['comp_address2'] : ''
            ,'comp_street' => isset($_REQUEST['comp_street']) ? $_REQUEST['comp_street'] : ''
            ,'comp_zip' => isset($_REQUEST['comp_zip']) ? $_REQUEST['comp_zip'] : ''
            ,'comp_location' => isset($_REQUEST['comp_location']) ? $_REQUEST['comp_location'] : ''
            ,'comp_region' => isset($_REQUEST['comp_region']) ? $_REQUEST['comp_region'] : ''
            ,'comp_country' => isset($_REQUEST['comp_country']) ? $_REQUEST['comp_country'] : ''
            ,'comp_www' => isset($_REQUEST['comp_www']) ? $_REQUEST['comp_www'] : ''
            ,'comp_cellular' => isset($_REQUEST['comp_cellular']) ? $_REQUEST['comp_cellular'] : ''
            ,'comp_fax' => isset($_REQUEST['comp_fax']) ? $_REQUEST['comp_fax'] : ''
            ,'tel_business' => $_REQUEST['tel_business']
            ,'address' => $_REQUEST['address']
            ,'address2' => $_REQUEST['address2']
            ,'street' => $_REQUEST['street']
            ,'zip' => $_REQUEST['zip']
            ,'location' => $_REQUEST['location']
            ,'region' => $_REQUEST['region']
            ,'country' => $_REQUEST['country']
            ,'email1' => $_REQUEST['email1']
            ,'email2' => $_REQUEST['email2']
            ,'tel_private' => $_REQUEST['tel_private']
            ,'cellular' => $_REQUEST['cellular']
            ,'fax' => $_REQUEST['fax']
            ,'www' => $_REQUEST['www']
            ,'birthday' => $birthday
            ,'comments' => $_REQUEST['comments']
            ,'customer_number' => $_REQUEST['customer_number']
            ,'free1' => isset($_REQUEST['free1']) ? $_REQUEST['free1'] : ''
            ,'free2' => isset($_REQUEST['free2']) ? $_REQUEST['free2'] : ''
            ,'free3' => isset($_REQUEST['free3']) ? $_REQUEST['free3'] : ''
            ,'free4' => isset($_REQUEST['free4']) ? $_REQUEST['free4'] : ''
            ,'free5' => isset($_REQUEST['free5']) ? $_REQUEST['free5'] : ''
            ,'free6' => isset($_REQUEST['free6']) ? $_REQUEST['free6'] : ''
            ,'free7' => isset($_REQUEST['free7']) ? $_REQUEST['free7'] : ''
            ,'free8' => isset($_REQUEST['free8']) ? $_REQUEST['free8'] : ''
            ,'free9' => isset($_REQUEST['free9']) ? $_REQUEST['free9'] : ''
            ,'free10' => isset($_REQUEST['free10']) ? $_REQUEST['free10'] : ''
            ,'group' => isset($_REQUEST['gid']) ? $_REQUEST['gid'] : array()
            );
    if (isset($_FILES['image']) && $_FILES['image']['tmp_name'] && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $imginf = getimagesize($_FILES['image']['tmp_name']);
        if (!$imginf || !$imginf[0] || !in_array($imginf[2], array(1,2,3))) {
            $error = $WP_msg['ImgErrWrongType'];
        } elseif ($imginf[0] > 120 || $imginf[1] > 120) {
            $error = $WP_msg['ImgErrTooLarge'];
        } else {
            $payload['image'] = file_get_contents($_FILES['image']['tmp_name']);
            $payload['imagemeta'] = serialize($imginf);
        }
        @unlink($_FILES['image']['tmp_name']);
        if (isset($error) && $error) {
            send_response('{"error":"'.$error.'"}');
            exit;
        }
    }
    if (isset($_REQUEST['delimage']) && $_REQUEST['delimage']) {
        $payload['image'] = '';
        $payload['imagemeta'] = '';
    }
    if (isset($_REQUEST['id']) && $_REQUEST['id']) {
        $payload['aid'] = $_REQUEST['id'];
        if ($birthday != '0000-0-0' && $birthday != '0-0-0' && is_readable($_PM_['path']['handler'].'/calendar/api.php')) {
            $displayname = false;
            if ($_REQUEST['nick']) {
                $displayname = $_REQUEST['nick'];
            } elseif ($_REQUEST['lastname'] && $_REQUEST['firstname']) {
                $displayname = $_REQUEST['firstname'].' '.$_REQUEST['lastname'];
            } elseif ($_REQUEST['firstname']) {
                $displayname = $_REQUEST['firstname'];
            } elseif ($_REQUEST['lastname']) {
                $displayname = $_REQUEST['lastname'];
            }
            if ($displayname) {
                $API = new handler_calendar_api($_PM_, $_SESSION['phM_uid']);
                if ($contact['bday_cal_evt_id']) {
                    $API->update_event(array
                            ('id' => $contact['bday_cal_evt_id']
                            ,'start' => $birthday.' 0:0:0'
                            ,'end' => $birthday.' 0:0:0'
                            ,'title' => $WP_msg['bday'].' '.$displayname
                            ,'type' => 3
                            ,'repetitions' => array(array('type' => 'year', 'repeat' => 0, 'until' => null))
                            ));
                } else {
                    $cal_evt_id = $API->add_event(array
                            ('start' => $birthday.' 0:0:0'
                            ,'end' => $birthday.' 0:0:0'
                            ,'title' => $WP_msg['bday'].' '.$displayname
                            ,'repetitions' => array(array('type' => 'year', 'repeat' => 0, 'until' => null))
                            ,'type' => 3
                            ,'status' => 2
                            ,'gid' => 0
                            ));
                    if ($cal_evt_id) $payload['bday_cal_evt_id'] = $cal_evt_id;
                }
            }
        } // END API function Calendar Interop
        $res = $cDB->update_contact($payload);
    } else {
        // API method to add the event's bday to the calendar, in case this is possible
        if ($birthday != '0000-0-0' && $birthday != '0-0-0' && is_readable($_PM_['path']['handler'].'/calendar/api.php')) {
            $displayname = false;
            if ($_REQUEST['nick']) {
                $displayname = $_REQUEST['nick'];
            } elseif ($_REQUEST['lastname'] && $_REQUEST['firstname']) {
                $displayname = $_REQUEST['firstname'].' '.$_REQUEST['lastname'];
            } elseif ($_REQUEST['firstname']) {
                $displayname = $_REQUEST['firstname'];
            } elseif ($_REQUEST['lastname']) {
                $displayname = $_REQUEST['lastname'];
            }
            if ($displayname) {
                $API = new handler_calendar_api($_PM_, $_SESSION['phM_uid']);
                $cal_evt_id = $API->add_event(array
                        ('start' => $birthday.' 0:0:0'
                        ,'end' => $birthday.' 0:0:0'
                        ,'title' => $WP_msg['bday'].' '.$displayname
                        ,'repetitions' => array(array('type' => 'year', 'repeat' => 0, 'until' => null))
                        ,'type' => 3
                        ,'status' => 2
                        ,'gid' => 0
                        ));
                if ($cal_evt_id) $payload['bday_cal_evt_id'] = $cal_evt_id;
            }
        } // END API function Calendar Interop
        $res = $cDB->add_contact($payload);
    }
    if ($res) {
        send_response('{"done":1}');
        exit;
    } else {
        send_response('{"error":"'.$DB->error().'"}');
        exit;
    }
}
if (isset($_REQUEST['id']) && $_REQUEST['id']) {
    if (isset($_REQUEST['getimage']) && $_REQUEST['getimage']) {
    	require_once($_PM_['path']['handler'].'/contacts/driver.mysql.php');
        $contact = $cDB->get_contactimage($_REQUEST['id'], 1);
        if ($contact['image'] && $contact['imagemeta']) {
            $contact['imagemeta'] = unserialize($contact['imagemeta']);
            switch ($contact['imagemeta'][2]) {
                case 1: header('Content-Type: image/gif');  break;
                case 2: header('Content-Type: image/jpeg'); break;
                case 3: header('Content-Type: image/png');  break;
                default: exit;
            }
            echo $contact['image'];
            exit;
        } else {
            exit;
        }
    }
    $contact = $cDB->get_contact($_REQUEST['id'], 1);
    $id = $_REQUEST['id'];
    $tpl->fill_block('delete_button', array('msg_dele' => $WP_msg['DelAdr']));
    $tpl->fill_block('save_button', array('msg_save' => $WP_msg['save']));
    $tpl->assign_block('may_edit');
    $tpl->assign(array
            ('form_target' => PHP_SELF.'?action=gconedit&id='.$id.'&save_contact=1&'.$passthru
            ,'delete_link' => PHP_SELF.'?action=gconedit&id='.$id.'&delete_contact=1&'.$passthru
            ));
    // Handle Birthday
    if ($contact['birthday']) {
        list ($byear, $bmonth, $bday) = explode('-', $contact['birthday']);
        $byear = (int) $byear;
        $bmonth = (int) $bmonth;
        $bday = (int) $bday;
    } else {
        $byear = $bmonth = $bday = false;
    }
    if ($contact['imagemeta']) {
        $contact['imagemeta'] = unserialize($contact['imagemeta']);
        $tpl->fill_block('ifimage', array
                ('imgurl' => PHP_SELF.'?action=gconedit&id='.$id.'&getimage=1&'.$passthru
                ,'imgw' => $contact['imagemeta'][0]
                ,'imgh' => $contact['imagemeta'][1]
                ));
        $tpl->fill_block('delimage', 'msg_delimage', $WP_msg['ImgDelImage']);
    }
    $contact['gid'] = array_keys($contact['group']); // They are held this way ...
} else {
    $contact = array();
    $id = '';
    $byear = $bmonth = $bday = false;
    $tpl->fill_block('save_button', array('msg_save' => $WP_msg['save']));
    $tpl->assign_block('may_edit');
    $tpl->assign(array
            ('form_target' => PHP_SELF.'?action=gconedit&id='.$id.'&save_contact=1&'.$passthru
            ,'delete_link' => PHP_SELF.'?action=gconedit&id='.$id.'&delete_contact=1&'.$passthru
            ));
}

// Overload whatever we got from the DB with request data
foreach (array
        ('nick', 'firstname', 'lastname', 'company', 'comp_dep', 'comp_address', 'comp_address2', 'comp_street', 'comp_zip'
        ,'comp_location', 'comp_region', 'comp_country', 'comp_fax', 'comp_www', 'comp_cellular', 'address', 'address2'
        ,'street', 'zip', 'location', 'region', 'country', 'email1', 'email2', 'tel_private', 'tel_business', 'cellular'
        ,'fax', 'www', 'birthday', 'comments', 'gid', 'customer_number'
        ,'free1', 'free2', 'free3', 'free4', 'free5', 'free6', 'free7', 'free8', 'free9', 'free10') as $k) {
    if (isset($_REQUEST[$k])) {
        $contact[$k] = $_REQUEST[$k];
    }
}

$tpl->assign(array
        ('msg_adbadd' => $WP_msg['adbAdd']
        ,'msg_group' => $WP_msg['group']
        ,'msg_none' => $WP_msg['none']
        ,'msg_nick' => $WP_msg['nick']
        ,'msg_fnam' => $WP_msg['fnam']
        ,'msg_lnam' => $WP_msg['snam']
        ,'msg_thirdname' => $WP_msg['ThirdNames']
        ,'msg_title' => $WP_msg['Title']
        ,'msg_role' => $WP_msg['Role']
        ,'msg_email1' => $WP_msg['emai1']
        ,'msg_email2' => $WP_msg['emai2']
        ,'msg_www' => $WP_msg['www']
        ,'msg_address' => $WP_msg['address']
        ,'msg_fon' => $WP_msg['fon']
        ,'msg_fon2' => $WP_msg['fon2']
        ,'msg_cell' => $WP_msg['cell']
        ,'msg_fax' => $WP_msg['fax']
        ,'msg_bday' => $WP_msg['bday']
        ,'msg_bday_format' => $WP_msg['bday_format']
        ,'msg_comment' => $WP_msg['cmnt']
        ,'msg_address' => $WP_msg['address']
        ,'leg_general' => $WP_msg['General']
        ,'leg_personal' => $WP_msg['Personal']
        ,'leg_business' => $WP_msg['Business']
        ,'leg_image' => $WP_msg['LegendImage']
        ,'msg_company' => $WP_msg['company']
        ,'msg_department' => $WP_msg['comp_dep']
        ,'msg_addr' => $WP_msg['address']
        ,'msg_street' => $WP_msg['street']
        ,'msg_zip' => $WP_msg['zip']
        ,'msg_location' => $WP_msg['location']
        ,'msg_region' => $WP_msg['state']
        ,'msg_country' => $WP_msg['country']
        ,'msg_uploadimage' => $WP_msg['ImgUpload']
        //,'msg_restrictions' => $WP_msg['ImgRestriction']
        ,'msg_CustomerNumber' => $WP_msg['CustomerNumber']
        ,'nick' => isset($contact['nick']) ? phm_entities($contact['nick']) : ''
        ,'firstname' => isset($contact['firstname']) ? phm_entities($contact['firstname']) : ''
        ,'lastname' => isset($contact['lastname']) ? phm_entities($contact['lastname']) : ''
        ,'thirdname' => isset($contact['thirdname']) ? phm_entities($contact['thirdname']) : ''
        ,'title' => isset($contact['title']) ? phm_entities($contact['title']) : ''
        ,'email1' => isset($contact['email1']) ? phm_entities($contact['email1']) : ''
        ,'email2' => isset($contact['email2']) ? phm_entities($contact['email2']) : ''
        ,'www' => isset($contact['www']) ? phm_entities($contact['www']) : ''
        ,'address' => isset($contact['address']) ? phm_entities($contact['address']) : ''
        ,'tel_private' => isset($contact['tel_private']) ? phm_entities($contact['tel_private']) : ''
        ,'tel_business' => isset($contact['tel_business']) ? phm_entities($contact['tel_business']) : ''
        ,'cellular' => isset($contact['cellular']) ? phm_entities($contact['cellular']) : ''
        ,'fax' => isset($contact['fax']) ? phm_entities($contact['fax']) : ''
        ,'comments' => isset($contact['comments']) ? phm_entities($contact['comments']) : ''
        ,'address2' => isset($contact['address2']) ? phm_entities($contact['address2']) : ''
        ,'street' => isset($contact['street']) ? phm_entities($contact['street']) : ''
        ,'zip' => isset($contact['zip']) ? phm_entities($contact['zip']) : ''
        ,'location' => isset($contact['location']) ? phm_entities($contact['location']) : ''
        ,'region' => isset($contact['region']) ? phm_entities($contact['region']) : ''
        ,'country' => isset($contact['country']) ? phm_entities($contact['country']) : ''
        ,'company' => isset($contact['company']) ? phm_entities($contact['company']) : ''
        ,'comp_dep' => isset($contact['comp_dep']) ? phm_entities($contact['comp_dep']) : ''
        ,'comp_role' => isset($contact['comp_role']) ? phm_entities($contact['comp_role']) : ''
        ,'comp_address' => isset($contact['comp_address']) ? phm_entities($contact['comp_address']) : ''
        ,'comp_address2' => isset($contact['comp_address2']) ? phm_entities($contact['comp_address2']) : ''
        ,'comp_street' => isset($contact['comp_street']) ? phm_entities($contact['comp_street']) : ''
        ,'comp_zip' => isset($contact['comp_zip']) ? phm_entities($contact['comp_zip']) : ''
        ,'comp_location' => isset($contact['comp_location']) ? phm_entities($contact['comp_location']) : ''
        ,'comp_region' => isset($contact['comp_region']) ? phm_entities($contact['comp_region']) : ''
        ,'comp_country' => isset($contact['comp_country']) ? phm_entities($contact['comp_country']) : ''
        ,'comp_fax' => isset($contact['comp_fax']) ? phm_entities($contact['comp_fax']) : ''
        ,'comp_www' => isset($contact['comp_www']) ? phm_entities($contact['comp_www']) : ''
        ,'comp_cellular' => isset($contact['comp_cellular']) ? phm_entities($contact['comp_cellular']) : ''
        ,'customer_number' => isset($contact['customer_number']) ? phm_entities($contact['customer_number']) : ''
        ,'passthrough' => give_passthrough(2)
        ,'action' => phm_entities($action)
        ,'id' => $id
        ,'print_url' => PHP_SELF.'?h=contacts&l=preview&print=1&'.$passthru.'&id='.$id
        ,'birthday_year' => ($byear) ? phm_entities($byear) : ''
        ,'msg_askdele' => $WP_msg['AskDelAdr']
        ,'leg_details' => $WP_msg['LegDetails']
        ));
$t_l = $tpl->get_block('groupline');
foreach ($cDB->get_grouplist(1) as $v) {
    $t_l->assign(array('id' => $v['gid'], 'name' => $v['name']));
    if (isset($contact['gid']) && in_array($v['gid'], $contact['gid'])) {
        $t_l->assign_block('selected');
    }
    $tpl->assign('groupline', $t_l);
    $t_l->clear();
}
// Output Days of month
$out_bd = $tpl->get_block('bday_dayline');
foreach (range(0, 31) as $day) {
    $out_bd->assign('day', $day);
    if ($bday && $bday == $day) $out_bd->assign_block('selected');
    $tpl->assign('bday_dayline', $out_bd);
    $out_bd->clear();
}
// Output Months of year
$out_bm = $tpl->get_block('bday_monthline');
foreach (range(0, 12) as $month) {
    $out_bm->assign('month', $month);
    if ($bmonth && $bmonth == $month) $out_bm->assign_block('selected');
    $tpl->assign('bday_monthline', $out_bm);
    $out_bm->clear();
}

function send_response($text = '')
{
    header('Content-Type: text/html; charset=UTF-8');
    echo '<html><head></head><body onload="parent.process(document.getElementById(\'response\').innerHTML)"><div id="response">'
            .$text.'</div></body></html>';
    exit;
}
