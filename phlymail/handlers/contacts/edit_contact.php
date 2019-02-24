<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.5 2015-04-23
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$passthru = give_passthrough(1);
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_add_contact']
        && !$_SESSION['phM_privs']['contacts_update_contact'] && !$_SESSION['phM_privs']['contacts_delete_contact']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$error = false;
$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
$dbTN = new DB_Controller_Thumb();

if (isset($_REQUEST['delete_contact']) && $_REQUEST['delete_contact']) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_delete_contact']) {
        send_response('{"error":"'.$WP_msg['PrivNoAccess'].'"}');
    }
    $contact = $cDB->get_contact($_REQUEST['id'], CONTACTS_PUBLIC_CONTACTS);
    if (!empty($contact)) {
        if ($contact['bday_cal_evt_id'] && is_readable($_PM_['path']['handler'].'/calendar/api.php')) {
            $API = new handler_calendar_api($_PM_, $_SESSION['phM_uid']);
            $API->delete_event($contact['bday_cal_evt_id']);
        }
        $dbTN->drop('contacts', $_REQUEST['id']);
        $done = $cDB->delete_contact($_REQUEST['id']);
        @unlink($_PM_['path']['storage'].'/'.$_SESSION['phM_uid'].'/contacts/'.intval($_REQUEST['id']));
    }
    if ($done) {
        send_response('{"done":"1"}');
    } else {
        send_response('{"error":"Error deleting contact!"}');
    }
}
// Special branch for editting the own VCF record
if (isset($_REQUEST['save_vcf']) && $_REQUEST['save_vcf']) {
    $success = $failure = 0;

    $fieldcount = 0;
    foreach (array('nick', 'firstname', 'lastname', 'company') as $k) {
        if (isset($_REQUEST[$k]) && $_REQUEST[$k]) {
            ++$fieldcount;
        }
    }
    if (!$fieldcount) {
        $error = $WP_msg['ENoEmptyRecord'];
    }
    if (!empty($_REQUEST['birthday_fulldate'])) {
        $birthday = $_REQUEST['birthday_fulldate'];
    } else {
        $birthday = ((isset($_REQUEST['birthday_year'])) ? sprintf('%04d', $_REQUEST['birthday_year'] + 0) : '0000')
                .'-'.((isset($_REQUEST['birthday_month']) ? $_REQUEST['birthday_month'] + 0 : '0'))
                .'-'.((isset($_REQUEST['birthday_day']) ? $_REQUEST['birthday_day'] + 0 : '0'));
    }
    if (!$error) {
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
                ,'own_vcf' => 1
                );
        $id = $DB->get_usrdata($_SESSION['phM_uid'], true);
        $id = $id['contactid'];
        $payload['aid'] = $id;

        $res = $cDB->update_contact($payload);

        if (isset($_REQUEST['delimage']) && $_REQUEST['delimage']) {
            $dbTN->drop('contacts', $id);
        }
        if (isset($_FILES['image']) && $_FILES['image']['tmp_name'] && is_uploaded_file($_FILES['image']['tmp_name'])) {
            if ($res) {
                $dbTN->drop('contacts', $id);
                $thumb = thumbnail::create($_FILES['image']['tmp_name'], 120, 120);
                if (false !== $thumb) {
                   $dbTN->add('contacts', $id, 'small', $thumb['mime'], $thumb['size'], $thumb['width'], $thumb['height'], $thumb['stream']);
                }
                $thumb = thumbnail::create($_FILES['image']['tmp_name'], 430, 320);
                if (false !== $thumb) {
                    $dbTN->add('contacts', $id, 'large', $thumb['mime'], $thumb['size'], $thumb['width'], $thumb['height'], $thumb['stream']);
                }
                basics::create_dirtree($_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/contacts/');
                move_uploaded_file($_FILES['image']['tmp_name'], $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/contacts/'.$id);
            }
            @unlink($_FILES['image']['tmp_name']);
        }
        if ($res) {
        	$success = true;
        } else {
        	$error = $DB->error();
        }
    }
    if (!empty($_REQUEST['noajax'])) {
        if ($success === true) {
            if (defined('PHM_MOBILE')) {
                header('Location: '.PHP_SELF.'?a=setup&'.$passthru);
            } else {
                header('Location: '.PHP_SELF.'?l=edit_vcf&h=contacts&'.$passthru.'&id='.$id);
            }
            exit;
        } else {
            die('We forgot to implement proper error handling here, sorry');
        }
    } else {
        send_response($success === true ? '{"done":"1"}' : '{"error":"'.$error.'"}');
    }
}

if (isset($_REQUEST['save_contact']) && $_REQUEST['save_contact']) {
    // Check quotas
    $quota_num_contacts = $DB->quota_get($_SESSION['phM_uid'], 'contacts', 'number_contacts');
    if (false !== $quota_num_contacts) {
        $quota_contactsleft = $cDB->quota_contactsnum(false);
        $quota_contactsleft = $quota_num_contacts - $quota_contactsleft;
    } else {
        $quota_contactsleft = false;
    }
    // This would fail on all systems without provisioning
    try {
        $systemQuota = SystemProvisioning::get('storage');
        $systemUsage = SystemProvisioning::getUsage('total_rounded');
        if ($systemQuota - $systemUsage <= 0) {
            $quota_contactsleft = false;
        }
    } catch (Exception $ex) {
        // void
    }

    // No more contacts allowed to save
    if (false !== $quota_contactsleft && $quota_contactsleft < 1) {
        send_response('{"error":"'.$WP_msg['QuotaExceeded'].'"}');
    }
    // End Quota

    $fieldcount = 0;
    foreach (array('nick', 'firstname', 'lastname', 'company') as $k) {
        if (isset($_REQUEST[$k]) && $_REQUEST[$k]) {
            ++$fieldcount;
        }
    }
    if (!$fieldcount) {
        $error = $WP_msg['ENoEmptyRecord'];
    }

    if (!empty($_REQUEST['birthday_fulldate'])) {
        $birthday = $_REQUEST['birthday_fulldate'];
    } else {
        $birthday = ((isset($_REQUEST['birthday_year'])) ? sprintf('%04d', $_REQUEST['birthday_year'] + 0) : '0000')
                .'-'.((isset($_REQUEST['birthday_month']) ? $_REQUEST['birthday_month'] + 0 : '0'))
                .'-'.((isset($_REQUEST['birthday_day']) ? $_REQUEST['birthday_day'] + 0 : '0'));
    }

    if (!$error) {
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
                ,'group' => isset($_REQUEST['gid']) ? $_REQUEST['gid'] : array()
                );
        if (isset($_REQUEST['customer_number'])) {
            $payload['customer_number'] = $_REQUEST['customer_number'];
        }
        if (!empty($_REQUEST['free'])) {
            $payload['free'] = $_REQUEST['free'];
        }
        if (isset($_REQUEST['id']) && $_REQUEST['id']) {
            if (isset($_REQUEST['delimage']) && $_REQUEST['delimage']) {
                $dbTN->drop('contacts', $_REQUEST['id']);
            }
            $id = $payload['aid'] = $_REQUEST['id'];
            $contact = $cDB->get_contact($_REQUEST['id'], 1);
            $error = true;
            if ($load == 'edit_vcf') {
                $error = false;
            } elseif ($contact['global']) {
                // Just rule it out
            } elseif ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_update_contact']) {
                $error = false;
            }
            // Don't allow action if permissions do not fit
            if ($error) {
                die();
            }

            if ($birthday != '0000-0-0' && is_readable($_PM_['path']['handler'].'/calendar/api.php')) {
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
                                ,'opaque' => 0
                                ,'gid' => 0
                                ));
                        if ($cal_evt_id) {
                            $payload['bday_cal_evt_id'] = $cal_evt_id;
                        }
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
                    if ($cal_evt_id) {
                        $payload['bday_cal_evt_id'] = $cal_evt_id;
                    }
                }
            } // END API function Calendar Interop
            $id = $res = $cDB->add_contact($payload);
        }
        if (isset($_FILES['image']) && $_FILES['image']['tmp_name'] && is_uploaded_file($_FILES['image']['tmp_name'])) {
            if ($id && $res) {
                $dbTN->drop('contacts', $id);
                $thumb = thumbnail::create($_FILES['image']['tmp_name'], 120, 120);
                if (false !== $thumb) {
                   $dbTN->add('contacts', $id, 'small', $thumb['mime'], $thumb['size'], $thumb['width'], $thumb['height'], $thumb['stream']);
                }
                $thumb = thumbnail::create($_FILES['image']['tmp_name'], 430, 320);
                if (false !== $thumb) {
                    $dbTN->add('contacts', $id, 'large', $thumb['mime'], $thumb['size'], $thumb['width'], $thumb['height'], $thumb['stream']);
                }
                basics::create_dirtree($_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/contacts/');
                move_uploaded_file($_FILES['image']['tmp_name'], $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/contacts/'.$id);
            } else {
                @unlink($_FILES['image']['tmp_name']);
            }
        }
        if ($res) {
            $success = true;
        } else {
            $error = $DB->error();
        }
    }
    if (!empty($_REQUEST['noajax'])) {
        if ($success === true) {
            header('Location: '.PHP_SELF.'?h=contacts&l=edit_contact&'.$passthru.'&id='.$id);
            exit;
        } else {
            die('We forgot to implement proper error handling here, sorry');
        }
    } else {
        send_response($success === true ? '{"done":"1"}' : '{"error":"'.$error.'"}');
    }
}

if ((isset($_REQUEST['id']) && $_REQUEST['id']) || $load == 'edit_vcf') {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.edit.tpl');
    if ($load == 'edit_vcf') {
        $id = $DB->get_usrdata($_SESSION['phM_uid'], true);
        $id = $id['contactid'];
    } else {
        $id = $_REQUEST['id'];
        $tpl->assign_block('has_customer_number');
    }
    $contact = $cDB->get_contact($id, CONTACTS_PUBLIC_CONTACTS);
    $mayedit = $maydelete = false;
    if ($load == 'edit_vcf') {
        $mayedit = true;
    } elseif ($contact['global']) {
        // Just rule it out
    } elseif ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_update_contact']) {
        $mayedit = true;
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_delete_contact']) {
            $maydelete = true;
        }
    }
    if ($mayedit) {
        if ($maydelete && $tpl->block_exists('delete_button')) {
            $tpl->assign_block('delete_button');
            $tpl->assign('delete_link', PHP_SELF.'?l='.$load.'&h=contacts&id='.$id.'&delete_contact=1&'.$passthru);
        }
        $tpl->assign_block('save_button');
        $tpl->assign_block('may_edit');
        $tpl->assign('form_target', $load == 'edit_vcf'
                ? PHP_SELF.phm_entities('?l='.$load.'&h=contacts&save_vcf=1&'.$passthru)
                : PHP_SELF.phm_entities('?l='.$load.'&h=contacts&id='.$id.'&save_contact=1&'.$passthru)
                );
    } else {
        $tpl->assign_block('no_edit');
    }
    if ($tpl->block_exists('print_button')) {
        $tpl->assign_block('print_button');
    }
    // Handle Birthday
    $byear = $bmonth = $bday = false;
    if ($contact['birthday']) {
        list ($byear, $bmonth, $bday) = explode('-', $contact['birthday']);
        $byear = (int) $byear;
        $bmonth = (int) $bmonth;
        $bday = (int) $bday;
    }
    $thumb = $dbTN->get('contacts', $id, 'large');
    if (false !== $thumb) {
        $tpl->fill_block('ifimage', array
                ('imgurl' => htmlspecialchars(PHP_SELF.'?l=preview&h=contacts&id='.$id.'&getimage=2&'.$passthru)
                ,'imgw' => $thumb['width']
                ,'imgh' => $thumb['height']
                ));
        $tpl->assign_block('delimage');
    } elseif ($contact['imagemeta']) {
        $contact['imagemeta'] = unserialize($contact['imagemeta']);
        $tpl->fill_block('ifimage', array
                ('imgurl' => htmlspecialchars(PHP_SELF.'?l=preview&h=contacts&id='.$id.'&getimage=1&'.$passthru)
                ,'imgw' => $contact['imagemeta'][0]
                ,'imgh' => $contact['imagemeta'][1]
                ));
        $tpl->assign_block('delimage');
    }
    $contact['gid'] = array_keys($contact['group']); // They are held this way ...
} else {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_add_contact']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    // Check quotas
    $quota_num_contacts = $DB->quota_get($_SESSION['phM_uid'], 'contacts', 'number_contacts');
    $quota_contactsleft = false;
    if (false !== $quota_num_contacts) {
        $quota_contactsleft = $cDB->quota_contactsnum(false);
        $quota_contactsleft = $quota_num_contacts - $quota_contactsleft;
    }
    // No more contacts allowed to save
    if (false !== $quota_contactsleft && $quota_contactsleft < 1) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['QuotaExceeded']);
        return;
    }
    // End Quota
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.edit.tpl');
    $id = '';
    if (!isset($contact)) {
        $contact = array();
        $byear = $bmonth = $bday = false;
    }
    $tpl->assign_block('save_button');
    $tpl->assign_block('may_edit');
    $tpl->assign('form_target', PHP_SELF.'?l='.$load.'&h=contacts&id='.$id.'&save_contact=1&'.$passthru);
}
// Overload whatever we got from the DB with request data
foreach (array
        ('nick', 'firstname', 'lastname', 'thirdname', 'title', 'company', 'comp_role', 'comp_dep', 'comp_address', 'comp_address2'
        ,'comp_street', 'comp_zip', 'comp_location', 'comp_region', 'comp_country', 'comp_fax', 'comp_www', 'comp_cellular'
        ,'address', 'address2', 'street', 'zip', 'location', 'region', 'country', 'email1', 'email2', 'tel_private', 'tel_business'
        ,'cellular', 'fax', 'www', 'birthday', 'comments', 'customer_number', 'gid') as $k) {
    if (isset($_REQUEST[$k])) {
        if (is_array($_REQUEST[$k])) {
            if (!isset($contact[$k])) {
                $contact[$k] = array();
            }
            foreach ($_REQUEST[$k] as $k2 => $v2) {
                $contact[$k][$k2] = phm_stripslashes($v2);
            }
            continue;
        }
        $contact[$k] = phm_stripslashes($_REQUEST[$k]);
    }
}

if ($load == 'edit_contact') {
    // No group selection when editting own VCF record
    $t_hg = $tpl->get_block('has_groupsel');
    $t_l = $t_hg->get_block('groupline');
    foreach ($cDB->get_grouplist(1) as $v) {
        $t_l->assign(array('id' => $v['gid'], 'name' => $v['name']));
        if (isset($contact['gid']) && in_array($v['gid'], $contact['gid'])) {
            $t_l->assign_block('selected');
        }
        $t_hg->assign('groupline', $t_l);
        $t_l->clear();
    }
    $tpl->assign('has_groupsel', $t_hg);
    // Custom fields are not available, when editing your own VCF
    $definedFields = $cDB->get_freefield_types();
    if (!empty($definedFields)) {
        $tpl->assign_block('has_freefields');
        $t_ff = $tpl->get_block('freefield');
        foreach ($definedFields as $field) {
            $value = !empty($contact['free'][$field['id']]) ? phm_entities($contact['free'][$field['id']]['value']) : '';
            if ($field['type'] == 'text') {
                $t_ff->fill_block('type_text', 'value', $value);
            } elseif ($field['type'] == 'textarea') {
                $t_ff->fill_block('type_textarea', 'value', $value);
            }
            $t_ff->assign(array('id' => $field['id'], 'name' => phm_entities($field['name'])));
            $tpl->assign('freefield', $t_ff);
            $t_ff->clear();
        }
    }
}
$tpl->assign(array
        ('nick' => isset($contact['nick']) ? $contact['nick'] : ''
        ,'firstname' => isset($contact['firstname']) ? $contact['firstname'] : ''
        ,'lastname' => isset($contact['lastname']) ? $contact['lastname'] : ''
        ,'thirdname' => isset($contact['thirdname']) ? $contact['thirdname'] : ''
        ,'title' => isset($contact['title']) ? $contact['title'] : ''
        ,'email1' => isset($contact['email1']) ? $contact['email1'] : ''
        ,'email2' => isset($contact['email2']) ? $contact['email2'] : ''
        ,'www' => isset($contact['www']) ? $contact['www'] : ''
        ,'address' => isset($contact['address']) ? $contact['address'] : ''
        ,'tel_private' => isset($contact['tel_private']) ? $contact['tel_private'] : ''
        ,'tel_business' => isset($contact['tel_business']) ? $contact['tel_business'] : ''
        ,'cellular' => isset($contact['cellular']) ? $contact['cellular'] : ''
        ,'fax' => isset($contact['fax']) ? $contact['fax'] : ''
        ,'comments' => isset($contact['comments']) ? $contact['comments'] : ''
        ,'address2' => isset($contact['address2']) ? $contact['address2'] : ''
        ,'street' => isset($contact['street']) ? $contact['street'] : ''
        ,'zip' => isset($contact['zip']) ? $contact['zip'] : ''
        ,'location' => isset($contact['location']) ? $contact['location'] : ''
        ,'region' => isset($contact['region']) ? $contact['region'] : ''
        ,'country' => isset($contact['country']) ? $contact['country'] : ''
        ,'company' => isset($contact['company']) ? $contact['company'] : ''
        ,'comp_dep' => isset($contact['comp_dep']) ? $contact['comp_dep'] : ''
        ,'comp_role' => isset($contact['comp_role']) ? $contact['comp_role'] : ''
        ,'comp_address' => isset($contact['comp_address']) ? $contact['comp_address'] : ''
        ,'comp_address2' => isset($contact['comp_address2']) ? $contact['comp_address2'] : ''
        ,'comp_street' => isset($contact['comp_street']) ? $contact['comp_street'] : ''
        ,'comp_zip' => isset($contact['comp_zip']) ? $contact['comp_zip'] : ''
        ,'comp_location' => isset($contact['comp_location']) ? $contact['comp_location'] : ''
        ,'comp_region' => isset($contact['comp_region']) ? $contact['comp_region'] : ''
        ,'comp_country' => isset($contact['comp_country']) ? $contact['comp_country'] : ''
        ,'comp_fax' => isset($contact['comp_fax']) ? $contact['comp_fax'] : ''
        ,'comp_www' => isset($contact['comp_www']) ? $contact['comp_www'] : ''
        ,'comp_cellular' => isset($contact['comp_cellular']) ? $contact['comp_cellular'] : ''
        ,'customer_number' => isset($contact['customer_number']) ? $contact['customer_number'] : ''
        ,'passthrough' => give_passthrough(2)
        ,'action' => $action
        ,'id' => $id
        ,'print_url' => PHP_SELF.'?h=contacts&l=preview&print=1&'.$passthru.'&id='.$id
        ,'cancel_url' => PHP_SELF.'?'.phm_entities('h=contacts&l=preview&'.$passthru.'&id='.$id)
        ,'birthday_year' => ($byear && $byear != '0000') ? $byear : ''
        ,'birthday_fulldate' => isset($contact['birthday']) ? $contact['birthday'] : ''
        ));

if ($tpl->block_exists('bday_dayline')) {
    // Output Days of month
    $out_bd = $tpl->get_block('bday_dayline');
    foreach (range(0, 31) as $day) {
        $out_bd->assign('day', $day);
        if ($bday && $bday == $day) {
            $out_bd->assign_block('selected');
        }
        $tpl->assign('bday_dayline', $out_bd);
        $out_bd->clear();
    }
}
if ($tpl->block_exists('bday_monthline')) {
    // Output Months of year
    $out_bm = $tpl->get_block('bday_monthline');
    foreach (range(0, 12) as $month) {
        $out_bm->assign('month', $month);
        if ($bmonth && $bmonth == $month) {
            $out_bm->assign_block('selected');
        }
        $tpl->assign('bday_monthline', $out_bm);
        $out_bm->clear();
    }
}

function send_response($text = '')
{
    header('Content-Type: text/html; charset=UTF-8');
    echo '<html><head><title></title></head><body onload="parent.process(document.getElementById(\'response\').innerHTML)"><div id="response">'
            .$text.'</div></body></html>';
    exit;
}
