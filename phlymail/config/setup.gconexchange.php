<?php
/**
 * Import/Export of global addresses
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @subpackage Global Contacts (due to be removed soon!)
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2012-05-02
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

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
$myurl = PHP_SELF.'?action=gconexchange';
$cDB = new handler_contacts_driver(0);
$do = (isset($_REQUEST['do']) && $_REQUEST['do']) ? $_REQUEST['do'] : false;
$return = false;

if ('export' == $do) {
    switch ($_REQUEST['exform']) {
    case 'LDIF':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.ldif');
        foreach ($cDB->get_adridx(0) as $line) {
            echo 'dn:: '.base64_encode('cn='.$line['firstname'].' '.$line['lastname'].',mail='.$line['email1']).LF;
            echo 'objectclass: top'.LF.'objectclass: person'.LF;
            echo 'objectclass: organizationalPerson'.LF.'objectclass: inetOrgPerson'.LF;
            echo 'cn:: '.base64_encode($line['firstname'].' '.$line['lastname']).LF;
            echo 'xmozillanickname: '.$line['nick'].LF;
            echo 'mail: '.$line['email1'].LF;
            echo 'givenname: '.$line['firstname'].LF;
            echo 'sn: '.$line['lastname'].LF;
            echo 'description:: '.base64_encode($line['comments']).LF;
            echo 'homePostalAddress:: '.base64_encode($line['address']).LF;
            echo 'mozillaHomePostalAddress2:: '.base64_encode($line['address2']).LF;
            echo 'mozillaHomeLocalityName:: '.base64_encode($line['location']).LF;
            echo 'mozillaHomeState:: '.base64_encode($line['region']).LF;
            echo 'mozillaHomePostalCode:: '.base64_encode($line['zip']).LF;
            echo 'mozillaHomeCountryName:: '.base64_encode($line['country']).LF;
            echo 'o:: '.base64_encode($line['company']).LF;
            echo 'ou:: '.base64_encode($line['comp_dep']).LF;
            echo 'postaladdress:: '.base64_encode($line['comp_address']).LF;
            echo 'xmozillapostaladdress2:: '.base64_encode($line['comp_address2']).LF;
            echo 'postalCode:: '.base64_encode($line['comp_zip']).LF;
            echo 'l:: '.base64_encode($line['comp_location']).LF;
            echo 'st:: '.base64_encode($line['comp_region']).LF;
            echo 'c:: '.base64_encode($line['comp_country']).LF;
            echo 'telephonenumber: '.$line['tel_business'].LF;
            echo 'homephone: '.$line['tel_private'].LF;
            echo 'facsimiletelephonenumber: '.$line['fax'].LF;
            echo 'cellphone: '.$line['cellular'].LF;
            echo 'homeurl: '.$line['www'].LF.LF;
        }
        echo LF;
        break;
    case 'MSOutl':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.csv');
        foreach ($cDB->get_adridx(0) as $line) {
            foreach ($line as $k => $v) {
            	$line[$k] = decode_utf8($v, 'iso-8859-1', true);
                $line[$k] = str_replace('"', "'", $v);
                $line[$k] = preg_replace('!('.CRLF.'|'.LF.')!', ' ', $line[$k]);
            }
            echo '"'.$line['nick'].'","'.$line['firstname'].'","'.$line['lastname'].'","'
                    .$line['address'].'","","","","'.$line['birthday'].'","'.$line['comments'].'","'
                    .$line['www'].'","'.$line['email1'].'","'.$line['email2'].'","'
                    .$line['tel_private'].'","'.$line['tel_business'].'","'.$line['cellular'].'","'
                    .$line['fax'].'"'.LF;
        }
        break;
    case 'MSOutlEx':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.csv');
        foreach ($cDB->get_adridx(0) as $line) {
            foreach ($line as $k => $v) {
            	$line[$k] = decode_utf8($v, 'iso-8859-1', true);
                $line[$k] = str_replace('"', "'", $v);
                $line[$k] = preg_replace('!('.CRLF.'|'.LF.')!', ' ', $line[$k]);
            }
            echo '"'.$line['nick'].'";"'.$line['firstname'].'";"'.$line['lastname'].'";"'
                    .$line['address'].'";"";"";"";"'.$line['birthday'].'";"'.$line['comments'].'";"'
                    .$line['www'].'";"'.$line['email1'].'";"'.$line['email2'].'";"'
                    .$line['tel_private'].'";"'.$line['tel_business'].'";"'.$line['cellular'].'";"'
                    .$line['fax'].'"'.LF;
        }
        break;
    case 'PHlyADB3':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.ldif');
        foreach ($cDB->get_adridx(0, '', '', 0, 0, false, false, true) as $line) {
            echo 'dn:: '.base64_encode('cn='.$line['firstname'].' '.$line['lastname'].',mail='.$line['email1']).LF;
            echo 'objectclass: top'.LF.'objectclass: person'.LF;
            echo 'objectclass: organizationalPerson'.LF.'objectclass: inetOrgPerson'.LF;
            echo 'cn:: '.base64_encode($line['firstname'].' '.$line['lastname']).LF;
            echo 'xmozillanickname:: '.base64_encode($line['nick']).LF;
            echo 'xphlymailtitle:: '.base64_encode($line['title']).LF;
            echo 'xphlymailthirdname:: '.base64_encode($line['thirdname']).LF;
            echo 'mail: '.$line['email1'].LF;
            echo 'xphlymailemail2: '.$line['email2'].LF;
            echo 'givenname:: '.base64_encode($line['firstname']).LF;
            echo 'sn:: '.base64_encode($line['lastname']).LF;
            echo 'description:: '.base64_encode($line['comments']).LF;
            echo 'homePostalAddress:: '.base64_encode($line['address']).LF;
            echo 'xphlymailhomePostalAddress2:: '.base64_encode($line['address2']).LF;
            echo 'xphlymailhomeStreet:: '.base64_encode($line['street']).LF;
            echo 'xphlymailhomeZIP:: '.base64_encode($line['zip']).LF;
            echo 'xphlymailhomeCity:: '.base64_encode($line['location']).LF;
            echo 'xphlymailhomeRegion:: '.base64_encode($line['region']).LF;
            echo 'xphlymailhomeCountry:: '.base64_encode($line['country']).LF;
            echo 'xphlymailhomeGeoLat: '.$line['personal_geo_lat'].LF;
            echo 'xphlymailhomeGeoLong: '.$line['personal_geo_long'].LF;
            echo 'homephone: '.$line['tel_private'].LF;
            echo 'facsimiletelephonenumber: '.$line['fax'].LF;
            echo 'mobile: '.$line['cellular'].LF;
            echo 'xphlymailbirthday: '.$line['birthday'].LF;
            echo 'o:: '.base64_encode($line['company']).LF;
            echo 'ou:: '.base64_encode($line['comp_dep']).LF;
            echo 'postaladdress:: '.base64_encode($line['comp_address']).LF;
            echo 'xphlymailpostaladdress2:: '.base64_encode($line['comp_address2']).LF;
            echo 'postalCode:: '.base64_encode($line['comp_zip']).LF;
            echo 'l:: '.base64_encode($line['comp_location']).LF;
            echo 'st:: '.base64_encode($line['comp_region']).LF;
            echo 'c:: '.base64_encode($line['comp_country']).LF;
            echo 'xphlymailcompanyrole:: '.base64_encode($line['comp_role']).LF;
            echo 'telephonenumber: '.$line['tel_business'].LF;
            echo 'xphlymailcompanyfax: '.$line['comp_fax'].LF;
            echo 'xphlymailcompanymobile: '.$line['comp_cellular'].LF;
            echo 'xphlymailcompanywww: '.$line['comp_www'].LF;
            echo 'xphlymailcompanygeolat: '.$line['comp_geo_lat'].LF;
            echo 'xphlymailcompanygeolong: '.$line['comp_geo_long'].LF;
            echo 'xphlymailimagemeta:: '.base64_encode($line['imagemeta']).LF;
            echo 'xphlymailcustomernumber: '.$line['customer_number'].LF;
            echo 'xphlymailimage:: '.base64_encode($line['image']).LF;
            echo 'xphlymailfree1:: '.base64_encode($line['free1']).LF;
            echo 'xphlymailfree2:: '.base64_encode($line['free2']).LF;
            echo 'xphlymailfree3:: '.base64_encode($line['free3']).LF;
            echo 'xphlymailfree4:: '.base64_encode($line['free4']).LF;
            echo 'xphlymailfree5:: '.base64_encode($line['free5']).LF;
            echo 'xphlymailfree6:: '.base64_encode($line['free6']).LF;
            echo 'xphlymailfree7:: '.base64_encode($line['free7']).LF;
            echo 'xphlymailfree8:: '.base64_encode($line['free8']).LF;
            echo 'xphlymailfree9:: '.base64_encode($line['free9']).LF;
            echo 'xphlymailfree10:: '.base64_encode($line['free10']).LF;
            echo 'xphlymailgroup: '.$line['gid'].LF;
            echo 'homeurl: '.$line['www'].LF.LF;
        }
        echo LF;
        break;
    case 'CSV':
        $db_fieldlist = array(0 => 'nick', 1 => 'firstname', 2 => 'lastname'
                ,3 => 'company', 4 => 'comp_dep', 5 => 'comp_address', 6 => 'comp_address2'
                ,7 => 'comp_street', 8 => 'comp_zip', 9 => 'comp_location', 10 => 'comp_region'
                ,11 => 'comp_country', 12 => 'address', 13 => 'address2', 14 => 'street'
                ,15 => 'zip', 16 => 'location', 17 => 'region', 18 => 'country'
                ,19 => 'email1', 20 => 'email2', 21 => 'tel_private', 22 => 'tel_business'
                ,23 => 'cellular', 24 => 'fax', 25 => 'www', 26 => 'birthday', 27 => 'comments'
                ,28 => 'customer_number'
                );
        $db_fieldnames = array(0 => $WP_msg['nick'], 1 => $WP_msg['fnam']
                ,2 => $WP_msg['snam'], 3 => $WP_msg['company'], 4 => $WP_msg['comp_dep']
                ,5 => $WP_msg['comp_address'], 6 => $WP_msg['comp_address2'], 7 => $WP_msg['comp_street']
                ,8 => $WP_msg['comp_zip'], 9 => $WP_msg['comp_location'], 10 => $WP_msg['comp_region']
                ,11 => $WP_msg['comp_country'], 12 => $WP_msg['address'], 13 => $WP_msg['address2']
                ,14 => $WP_msg['street'], 15 => $WP_msg['zip'], 16 => $WP_msg['location']
                ,17 => $WP_msg['state'], 18 => $WP_msg['country'], 19 => $WP_msg['emai1']
                ,20 => $WP_msg['emai2'], 21 => $WP_msg['fon'], 22 => $WP_msg['fon2']
                ,23 => $WP_msg['cell'], 24 => $WP_msg['fax'], 25 => $WP_msg['www']
                ,26 => $WP_msg['bday'], 27 => $WP_msg['cmnt'], 28 => $WP_msg['CustomerNumber']
                );
        if (isset($_REQUEST['selected_fields'])) {
        	// Make sure just to use a single char delimiter
            $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? $_REQUEST['delimiter']{0} : ';';

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=phlyMailAddresses.csv');

            // If requested, output a descriptive line containing the field names
            // These depend on the selected frontend language
            if (isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                $out = false;
                foreach ($_REQUEST['selected_fields'] as $dbfield) {
                	$fieldname = decode_utf8($db_fieldnames[$dbfield], 'iso-8859-1', true);
                    if ($out) echo $delimiter;
                    echo isset($_REQUEST['is_quoted']) ? '"'.$fieldname.'"' : $fieldname;
                    $out = true;
                }
                echo LF;
            }

            foreach ($cDB->get_adridx(0) as $line) {
            	foreach ($line as $k => $v) {
            		$line[$k] = decode_utf8($v, 'iso-8859-1', true);
            	}
                $out = false;
                foreach ($_REQUEST['selected_fields'] as $dbfield) {
                    if ($out) echo $delimiter;
                    if ($dbfield == -1) {
                        $field = '';
                    } elseif (isset($db_fieldlist[$dbfield])) {
                        $field = $line[$db_fieldlist[$dbfield]];
                    } else {
                        $field = '';
                    }
                    echo isset($_REQUEST['is_quoted']) ? '"'.$field.'"' : $field;
                    $out = true;
                }
                echo LF;
            }
        } else {
        	// Make sure just to use a single char delimiter
            $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? $_REQUEST['delimiter']{0} : ';';
            $tpl = new phlyTemplate(CONFIGPATH.'/templates/gcontacts.exportcsv.tpl');
            if (isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                $tpl->assign_block('if_fieldnames');
            }
            if (isset($_REQUEST['is_quoted']) && $_REQUEST['is_quoted']) {
                $tpl->assign_block('if_quoted');
            }
            $tpl->assign(array
                    ('about_selection' => $WP_msg['csvImAboutSelection']
                    ,'sel_size' => count($db_fieldlist)
                    ,'msg_select' => $WP_msg['select']
                    ,'legend_selection' => $WP_msg['csvImLegendSelection']
                    ,'msg_in_csv' => $WP_msg['csvExInCSV']
                    ,'msg_from_db' => $WP_msg['csvExFromDB']
                    ,'msg_space' => $WP_msg['csvExSpace']
                    ,'msg_add_space' => $WP_msg['csvExAddSpace']
                    ,'delimiter' => $delimiter
                    ,'msg_save' => $WP_msg['Export']
                    ,'form_action' => htmlspecialchars($myurl.'&'.give_passthrough(1).'&do=export&exform=CSV')
                    ,'link_back' => htmlspecialchars($myurl.'&'.give_passthrough(1))
                    ,'msg_back' => $WP_msg['cancel']
                    ));
            $t_csv = $tpl->get_block('dbline');
            foreach ($db_fieldlist as $k => $v) {
                $t_csv->assign(array('id' => $k, 'value' => $db_fieldnames[$k]));
                $tpl->assign('dbline', $t_csv);
                $t_csv->clear();
            }
            return;
        }
        break;
    default:
        $return .= $WP_msg['unkExpFrmt'].'<br />'.LF;
        $do = false;
        break;
    }
    if (!$return) exit;
}
if ('import' == $do) {
    $imported = 0;
    if (isset($_FILES['imfile']) || isset($_SESSION['WP_impfile'])) {
        if (!isset($_FILES['imfile']) && isset($_SESSION['WP_impfile'])) {
            $file = $_SESSION['WP_impfile'];
            unset($_SESSION['WP_impfile']);
        } elseif (is_uploaded_file($_FILES['imfile']['tmp_name'])) {
            $file = file($_FILES['imfile']['tmp_name']);
            @unlink($_FILES['imfile']['tmp_name']);
        }
        switch ($_REQUEST['imform']) {
        case 'LDIF':
        case 'PHlyADB':
        case 'PHlyADB3':
            $file = explode(LF.LF, preg_replace('!(\r\n|\r|\n)!', LF, implode($file)));
            foreach ($file as $key => $value) {
                $save = false;
                foreach (array('xmozillanickname' => 'nick', 'givenname' => 'firstname', 'thirdname' => 'thirdname'
                        ,'sn' => 'lastname', 'o:: ' => 'company', 'ou:: ' => 'comp_dep'
                        ,'postaladdress:: ' => 'comp_address', 'xphlymailpostaladdress2:: ' => 'comp_address2'
                        ,'postalCode:: ' => 'comp_zip', 'l:: ' => 'comp_location'
                        ,'st:: ' => 'comp_region', 'c:: ' => 'comp_country'
                        ,'xphlymailcompany' => 'company', 'homePostalAddress' => 'address'
                        ,'xphlymailhomePostalAddress2' => 'address2'
                        ,'xphlymailhomeStreet' => 'street', 'xphlymailhomeZIP' => 'zip'
                        ,'xphlymailhomeCity' => 'location', 'xphlymailhomeRegion' => 'region'
                        ,'xphlymailhomeCountry' => 'country', 'mozillaHomePostalAddress2:: ' => 'address2'
                        ,'mozillaHomeLocalityName:: ' => 'location'
                        ,'mozillaHomeState:: ' => 'region', 'mozillaHomePostalCode:: ' => 'zip'
                        ,'mozillaHomeCountryName:: ' => 'country', 'mail' => 'email1'
                        ,'xphlymailemail2' => 'email2', 'homephone' => 'tel_private'
                        ,'telephonenumber' => 'tel_business', 'cellphone' => 'cellular'
                        ,'mobile' => 'cellular', 'facsimiletelephonenumber' => 'fax'
                        ,'homeurl' => 'www', 'xphlymailbirthday' => 'birthday'
                        ,'description' => 'comments', 'xphlymailfree1' => 'free1'
                        ,'xphlymailfree2' => 'free2', 'xphlymailfree3' => 'free3'
                        ,'xphlymailfree4' => 'free4', 'xphlymailfree5' => 'free5'
                        ,'xphlymailfree6' => 'free6', 'xphlymailfree7' => 'free7'
                        ,'xphlymailfree8' => 'free8', 'xphlymailfree9' => 'free9'
                        ,'xphlymailfree10' => 'free10', 'xphlymailimagemeta' => 'imagemeta'
                        ,'xphlymailimage' => 'image', 'xphlymailgroup' => 'gid'
                        ,'xphlymailtitle' => 'title', 'xphlymailthirdname' => 'thirdname'
                        ,'xphlymailhomeGeoLat' => 'personal_geo_lat', 'xphlymailhomeGeoLong' => 'personal_geo_long'
                        ,'xphlymailcompanyrole' => 'comp_role', 'xphlymailcompanyfax' => 'comp_fax'
                        ,'xphlymailcompanymobile' => 'comp_cellular', 'xphlymailcompanywww' => 'comp_www'
                        ,'xphlymailcompanygeolat' => 'comp_geo_lat', 'xphlymailcompanygeolong' => 'comp_geo_long'
                        ,'xphlymailcustomernumber' => 'customer_number'
                        ) as $needle => $field) {
                    if ($needle && preg_match('!^'.$needle.':(:?)\ ?(.+)$!im', $value, $found)) {
                        if ($found[1]) $found[2] = base64_decode($found[2]);
                        if ($found[2]) $save[$field] = $found[2];
                    }
                }
                if (!empty($save)) {
                    $save['owner'] = 0;
                    $cDB->add_contact($save);
                    ++$imported;
                }
            }
            break;
        case 'MSOutl':
            break;
        case 'MSOutlEx':
            break;
        case 'MSOutlEx6':
            foreach ($file as $key => $value) {
                $line = explode(';', str_replace('"', '', trim(encode_utf8($value, 'iso-8859-1', true))));
                $save = array();
                foreach (array
                        ('nick' => 3
                        ,'firstname' => array(0, 2)
                        ,'lastname' => 1
                        ,'company' => 25
                        ,'street' => 6
                        ,'location' => 7
                        ,'zip' => 8
                        ,'region' => 9
                        ,'country' => 10
                        ,'email1' => 5
                        ,'email2' => false
                        ,'tel_private' => 12
                        ,'tel_business' => 22
                        ,'cellular' => 14
                        ,'fax' => 23
                        ,'www' => 15
                        ,'birthday' => false
                        ,'comments' => 29
                        ,'free1' => false
                        ,'free2' => false
                        ,'group' => false
                        ) as $field => $needle) {
                    if (false === $needle) {
                        // Not mapped
                        continue;
                    } elseif (is_array($needle)) {
                        // Collected fields: Stored in one field within PHlyADB, but in
                        // various fields of the source
                        $save[$field] = false;
                        foreach ($needle as $part) {
                            if ($line[$part]) $save[$field][] = $line[$part];
                        }
                        if (!empty($save[$field])) {
                            $save[$field] = implode(' ', $save[$field]);
                        } else {
                            unset($save[$field]);
                        }
                    } else {
                        // 1:1 translation
                        if ($line[$needle]) $save[$field] = $line[$needle];
                    }
                }
                if (!empty($save)) {
                    $save['owner'] = 0;
                    $cDB->add_contact($save);
                    ++$imported;
                }
            }
            break;
        case 'CSV':
            $db_fieldlist = array(0 => 'nick', 1 => 'firstname', 2 => 'lastname'
                    ,3 => 'company', 4 => 'comp_dep', 5 => 'comp_address'
                    ,6 => 'comp_address2', 7 => 'comp_street', 8 => 'comp_zip'
                    ,9 => 'comp_location', 10 => 'comp_region', 11 => 'comp_country'
                    ,12 => 'address', 13 => 'address2', 14 => 'street'
                    ,15 => 'zip', 16 => 'location', 17 => 'region'
                    ,18 => 'country', 19 => 'email1', 20 => 'email2'
                    ,21 => 'tel_private', 22 => 'tel_business', 23 => 'cellular'
                    ,24 => 'fax', 25 => 'www', 26 => 'birthday', 27 => 'comments'
                    ,28 => 'customer_number'
                    );
            $db_fieldnames = array(0 => $WP_msg['nick'], 1 => $WP_msg['fnam']
                    ,2 => $WP_msg['snam'], 3 => $WP_msg['company']
                    ,4 => $WP_msg['comp_dep'], 5 => $WP_msg['comp_address']
                    ,6 => $WP_msg['comp_address2'], 7 => $WP_msg['comp_street']
                    ,8 => $WP_msg['comp_zip'], 9 => $WP_msg['comp_location']
                    ,10 => $WP_msg['comp_region'], 11 => $WP_msg['comp_country']
                    ,12 => $WP_msg['address'], 13 => $WP_msg['address2']
                    ,14 => $WP_msg['street'], 15 => $WP_msg['zip']
                    ,16 => $WP_msg['location'], 17 => $WP_msg['state']
                    ,18 => $WP_msg['country'], 19 => $WP_msg['emai1']
                    ,20 => $WP_msg['emai2'], 21 => $WP_msg['fon']
                    ,22 => $WP_msg['fon2'], 23 => $WP_msg['cell']
                    ,24 => $WP_msg['fax'], 25 => $WP_msg['www'], 26 => $WP_msg['bday']
                    ,27 => $WP_msg['cmnt'], 28 => $WP_msg['CustomerNumber']
                    );
            if (isset($_REQUEST['selected_fields'])) {
                foreach ($file as $k => $line) {
                	$line = encode_utf8($line, 'iso-8859-1', true);
                    if (0 == $k && isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                        continue;
                    }
                    if (isset($_REQUEST['is_quoted']) && $_REQUEST['is_quoted']) {
                        $line = str_replace('"', '', $line);
                    }
                    // Make sure just to use a single char delimiter
                    $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? $_REQUEST['delimiter']{0} : ';';
                    $line = explode($delimiter, trim($line));
                    $save = array();
                    foreach ($_REQUEST['selected_fields'] as $dbfield => $csvfield) {
                        $save[$db_fieldlist[$dbfield]] = $line[$csvfield];
                    }
                    if (!empty($save)) {
                        $save['owner'] = 0;
                        $cDB->add_contact($save);
                        ++$imported;
                    }
                }
            } else {
                $tpl = new phlyTemplate(CONFIGPATH.'/templates/gcontacts.importcsv.tpl');
                $_SESSION['WP_impfile'] = $file;
                $file = isset($file[0]) ? trim($file[0]) : false;
                if (!$file) {
                    // File not readable / non exstant -> return to input mask
                    break;
                }
                if (isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                    $tpl->assign_block('if_fieldnames');
                }
                if (isset($_REQUEST['is_quoted']) && $_REQUEST['is_quoted']) {
                    $tpl->assign_block('if_quoted');
                    $file = str_replace('"', '', $file);
                }
                // Make sure just to use a single char delimiter
                $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? $_REQUEST['delimiter']{0} : ';';
                $file = explode($delimiter, $file);
                $tpl->assign(array
                        ('about_selection' => $WP_msg['csvImAboutSelection']
                        ,'legend_source' => $WP_msg['csvImLegendSource']
                        ,'msg_select' => $WP_msg['select']
                        ,'legend_selection' => $WP_msg['csvImLegendSelection']
                        ,'msg_from_csv' => $WP_msg['csvImFromCSV']
                        ,'msg_in_db' => $WP_msg['csvImInDB']
                        ,'delimiter' => $delimiter
                        ,'msg_save' => $WP_msg['save']
                        ,'form_action' => htmlspecialchars($myurl.'&'.give_passthrough(1).'&do=import&imform=CSV')
                    	,'link_back' => htmlspecialchars($myurl.'&'.give_passthrough(1))
                    	,'msg_back' => $WP_msg['cancel']
                        ));

                $t_csv = $tpl->get_block('csvline');
                foreach ($file as $k => $v) {
                    $t_csv->assign(array('id' => $k, 'value' => encode_utf8($v)));
                    $tpl->assign('csvline', $t_csv);
                    $t_csv->clear();
                }
                $t_csv = $tpl->get_block('dbline');
                foreach ($db_fieldlist as $k => $v) {
                    $t_csv->assign(array('id' => $k, 'value' => $db_fieldnames[$k]));
                    $tpl->assign('dbline', $t_csv);
                    $t_csv->clear();
                }
                return;
            }
            break;
        default:
            $return .= $WP_msg['unkImpFrmt'].'<br />'.LF;
            break;
        }
    }
    $do = false;
}
if (!$do) {
    if (isset($imported) && $imported) {
        $return .= str_replace('$1', $imported, $WP_msg['ImpNum']).'<br />'.LF;
    }

    $tpl = new phlyTemplate(CONFIGPATH.'/templates/gcontacts.exchmenu.tpl');
    $passthrough2 = give_passthrough(2);
    $tpl->assign(array
            ('target' => $myurl
            ,'msg_select' => $WP_msg['plsSel']
            ,'passthrough' => $passthrough2
            ,'about_import' => $WP_msg['AboutImport']
            ,'leg_import' => $WP_msg['Import']
            ,'msg_file' => $WP_msg['filename']
            ,'msg_format' => $WP_msg['format']
            ,'msg_csv_only' => $WP_msg['LegendCSV']
            ,'msg_fieldnames' => $WP_msg['csvFirstLine']
            ,'msg_csv_quoted' => $WP_msg['csvIsQuoted']
            ,'msg_field_delimiter' => $WP_msg['csvFieldDelimiter']
            ));
    if ($return) $tpl->fill_block('return', 'return', $return);
    $imop = $tpl->get_block('imoption');
    foreach (array
            ('LDIF' => 'LDIF' /*
            ,'MSOutl' => 'Micosoft Outlook'
            ,'MSOutlEx' => 'Microsoft Outlook Express' */
            ,'MSOutlEx6' => 'Microsoft Outlook Express 6'
            ,'CSV' => $WP_msg['csvMenuOption']
            ,'PHlyADB' => 'PHlyADB (phlyMail 2.1+)'
            ,'PHlyADB3' => 'PHlyADB3 (phlyMail 3+)'
            ) as $val => $name) {
        $imop->assign(array('value' => $val, 'name' => $name));
        $tpl->assign('imoption', $imop);
        $imop->clear();
    }
    if ($cDB->get_adrcount(0)) {
        $tpl_exp = $tpl->get_block('export');
        $tpl_exp->assign(array
                ('target' => $myurl
                ,'msg_select' => $WP_msg['plsSel']
                ,'passthrough' => $passthrough2
                ,'about_export' => $WP_msg['AboutExport']
                ,'leg_export' => $WP_msg['Export']
                ,'msg_csv_only' => $WP_msg['LegendCSV']
                ,'msg_fieldnames' => $WP_msg['csvFirstLine']
                ,'msg_csv_quoted' => $WP_msg['csvIsQuoted']
                ,'msg_field_delimiter' => $WP_msg['csvFieldDelimiter']
                ,'msg_format' => $WP_msg['format']
                ));
        $exop = $tpl_exp->get_block('exoption');
        foreach (array
                ('LDIF' => 'LDIF' /*
                ,'MSOutl' => 'Micosoft Outlook'
                ,'MSOutlEx' => 'Microsoft Outlook Express'
                ,'MSOutlEx6' => 'Microsoft Outlook Express 6' */
                ,'CSV' => $WP_msg['csvMenuOption']
                ,'PHlyADB3' => 'PHlyADB3 (phlyMail 3+)'
                ) as $val => $name) {
            $exop->assign(array('value' => $val, 'name' => $name));
            $tpl_exp->assign('exoption', $exop);
            $exop->clear();
        }
        $tpl->assign('export', $tpl_exp);
    }
}
