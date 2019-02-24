<?php
/**
 * preview.php - Used in the preview window
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Contacts
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0 2015-04-14 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$error = false;
$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
$dbTN = new DB_Controller_Thumb();
$passthru = give_passthrough(1);
$bday = '';
$contact = array();
// That's it with the session
session_write_close();

$id = false;
if (!empty($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
} elseif (!empty($_REQUEST['i'])) {
    $id = intval($_REQUEST['i']);
}

if (!empty($id)) {
    if (isset($_REQUEST['getimage']) && $_REQUEST['getimage']) {
        $thumb = $dbTN->get('contacts', $_REQUEST['id'], $_REQUEST['getimage'] == 2 ? 'large' : 'small');
        if (false !== $thumb) {
            header('Content-Type: '.$thumb['mime']);
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.$thumb['size']);
            echo $thumb['stream'];
            exit;
        }
        $contact = $cDB->get_contactimage($id, CONTACTS_VISIBILITY_MODE);
        if ($contact['image'] && $contact['imagemeta']) {
            $contact['imagemeta'] = unserialize($contact['imagemeta']);
            switch ($contact['imagemeta'][2]) {
                case 1: header('Content-Type: image/gif');  break;
                case 2: header('Content-Type: image/jpeg'); break;
                case 3: header('Content-Type: image/png');  break;
                default: exit;
            }
            echo phm_stripslashes($contact['image']);
        }
        exit;
    }
    $contact = $cDB->get_contact($id, CONTACTS_VISIBILITY_MODE);

    // Handle Birthday
    if ($contact['birthday'] && $contact['birthday'] != '0000-00-00') {
        list ($byear, $bmonth, $bday) = explode('-', $contact['birthday']);
        if ($byear == '0000') {
            $byear = '';
        }
        $bday = str_replace(array('%y', '%m' , '%d'), array($byear, $bmonth, $bday), $WP_msg['date_formatstring']);
    }
}
if (isset($_REQUEST['print'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.print.tpl');
    $tpl->assign_block('printhead');
    $tpl->assign_block('printfoot');
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.preview.tpl');
    $mayedit = false;
    if ($contact['global']) {
        // Just rule it out
    } elseif ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_update_contact']) {
        $mayedit = true;
    }
    if ($mayedit) {
        $tpl->assign_block('may_edit');
    }
}
if (isset($contact['group'])) {
    if (sizeof($contact['group']) > 5) {
        $contact['group'] = array_merge(array_slice($contact['group'], 0, 5), array('&hellipse;'));
    }
    $contact['group'] = implode(', ', $contact['group']); // Stringify
}
// Have special chars and plain text line breaks covered
foreach ($contact as $k => $v) {
    if (is_array($v)) {
        if (!isset($contact[$k])) {
            $contact[$k] = array();
        }
        foreach ($v as $k2 => $v2) {
            if (is_array($v2)) {
                if (!isset($contact[$k][$k2])) {
                    $contact[$k][$k2] = array();
                }
                foreach ($v2 as $k3 => $v3) {
                    $contact[$k][$k2][$k3] = nl2br(phm_entities($v3));
                }
                continue;
            }
            $contact[$k][$k2] = nl2br(phm_entities($v2));
        }
        continue;
    }
    $contact[$k] = nl2br(phm_entities($v));
}

$tpl->assign(array
        ('displayname' => $contact['displayname']
        ,'nick' => !empty($contact['nick']) ? $contact['nick'] : ''
        ,'group' => !empty($contact['group']) ? $contact['group'] : ''
        ,'fname' => !empty($contact['firstname']) ? $contact['firstname'] : ''
        ,'lname' => !empty($contact['lastname']) ? $contact['lastname'] : ''
        ,'email1' => !empty($contact['email1']) ? $contact['email1'] : ''
        ,'email2' => !empty($contact['email2']) ? $contact['email2'] : ''
        ,'www' => !empty($contact['www']) ? $contact['www'] : ''
        ,'thirdname' => !empty($contact['thirdname']) ? $contact['thirdname'] : ''
        ,'title' => !empty($contact['title']) ? $contact['title'] : ''
        ,'address' => !empty($contact['address']) ? $contact['address'] : ''
        ,'fon_private' => !empty($contact['tel_private']) ? $contact['tel_private'] : ''
        ,'fon_business' => !empty($contact['tel_business']) ? $contact['tel_business'] : ''
        ,'cellular' => !empty($contact['cellular']) ? $contact['cellular'] : ''
        ,'fax' => !empty($contact['fax']) ? $contact['fax'] : ''
        ,'comment' => !empty($contact['comments']) ? $contact['comments'] : ''
        ,'company' => !empty($contact['company']) ? $contact['company'] : ''
        ,'department' => !empty($contact['comp_dep']) ? $contact['comp_dep'] : ''
        ,'addr' => (!empty($contact['address']) ? $contact['address'] : '')
                .(!empty($contact['address']) && !empty($contact['address2']) ? '<br />' : '')
                .(!empty($contact['address2']) ? $contact['address2'] : '')
        ,'street' => !empty($contact['street']) ? $contact['street'] : ''
        ,'zip_location' => ((!empty($contact['zip']) ? $contact['zip'] : ''))
                .((!empty($contact['zip']) && !empty($contact['location'])) ? ' / ' : '')
                .(!empty($contact['location']) ? $contact['location'] : '')
        ,'region_country' => (!empty($contact['region']) ? $contact['region'] : '')
                .((!empty($contact['region']) && !empty($contact['country'])) ? ' / ' : '')
                .(!empty($contact['country']) ? $contact['country'] : '')
        ,'comp_role' => !empty($contact['comp_role']) ? $contact['comp_role'] : ''
        ,'comp_addr' => (!empty($contact['comp_address']) ? $contact['comp_address'] : '')
                .(!empty($contact['comp_address2']) ? $contact['comp_address2'] : '')
        ,'comp_street' => !empty($contact['comp_street']) ? $contact['comp_street'] : ''
        ,'comp_zip_location' => ((!empty($contact['comp_zip']) ? $contact['comp_zip'] : ''))
                .((!empty($contact['comp_zip']) && !empty($contact['comp_location'])) ? ' / ' : '')
                .(!empty($contact['comp_location']) ? $contact['comp_location'] : '')
        ,'comp_region_country' => (!empty($contact['comp_region']) ? $contact['comp_region'] : '')
                .((!empty($contact['comp_region']) && !empty($contact['comp_country'])) ? ' / ' : '')
                .(!empty($contact['comp_country']) ? $contact['comp_country'] : '')
        ,'comp_www' => !empty($contact['comp_www']) ? $contact['comp_www'] : ''
        ,'comp_cellular' => !empty($contact['comp_cellular']) ? $contact['comp_cellular'] : ''
        ,'comp_fax' => !empty($contact['comp_fax']) ? $contact['comp_fax'] : ''
        ,'customer_number' => !empty($contact['customer_number']) ? $contact['customer_number'] : ''
        ,'passthrough' => give_passthrough(2)
        ,'action' => $action
        ,'id' => $id
        ,'bday' => $bday
        ,'leg_details' => $WP_msg['LegDetails']
        ,'edit_url' => PHP_SELF.'?l=edit_contact&h=contacts&id='.$id.'&'.$passthru
        ,'edit_url_h' => PHP_SELF.'?'.phm_entities('l=edit_contact&h=contacts&id='.$id.'&'.$passthru)
        ,'print_url' => PHP_SELF.'?l=preview&h=contacts&id='.$id.'&print=1&'.$passthru
        ,'composemail_url' => PHP_SELF.'?h=core&l=compose_email&'.$passthru.'&to='
        ,'composesms_url' => PHP_SELF.'?h=core&l=compose_sms&'.$passthru.'&to='
        ,'composefax_url' => PHP_SELF.'?h=core&l=compose_fax&'.$passthru.'&to='
        ));

if (!empty($contact['free'])) {
    $t_hf = $tpl->get_block('has_freefields');
    $t_ff = $t_hf->get_block('freefield');
    foreach ($contact['free'] as $id => $free) {
        $t_ff->assign(array(
                'name' => $free['name'],
                'value' => $free['value']
                ));
        $t_hf->assign('freefield', $t_ff);
        $t_ff->clear();
    }
    $tpl->assign('has_freefields', $t_hf);
}

$thumb = $dbTN->get('contacts', $id, 'small');
if (false !== $thumb) {
    $tpl->fill_block('ifimage', array
            ('imgurl' => htmlspecialchars(PHP_SELF.'?l=preview&h=contacts&id='.$id.'&getimage=1&'.$passthru)
            ,'imgw' => $thumb['width']
            ,'imgh' => $thumb['height']
            ));
} elseif ($contact['imagemeta']) {
    $contact['imagemeta'] = unserialize($contact['imagemeta']);
    $tpl->fill_block('ifimage', array
            ('imgurl' => htmlspecialchars(PHP_SELF.'?l=preview&h=contacts&id='.$id.'&getimage=1&'.$passthru)
            ,'imgw' => $contact['imagemeta'][0]
            ,'imgh' => $contact['imagemeta'][1]
            ));
}
