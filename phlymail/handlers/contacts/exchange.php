<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @subpackage Import / Export
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.0 2015-04-27
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Might exist in both the sessin and a variable form the API
$myPrivs = isset($_phM_privs) ? $_phM_privs : $_SESSION['phM_privs'];

if (!$myPrivs['all'] &&
        ($myPrivs['contacts_export_contacts'] == 0 && $myPrivs['contacts_import_contacts'] == 0)) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
/**
 *  MicroSoft makes up for a lot of noise again. They do change the format of their
 *  address book file from version to version and these do even differ between
 *  Outlook and Outlook Express.
 *  That makes it almost impossible to support a useful im/export feature for the
 *  Outlook family of products.
 */
$myurl = PHP_SELF.'?l=exchange&h=contacts';
$cDB = new handler_contacts_driver(defined('PHM_API_UID') ? PHM_API_UID : $_SESSION['phM_uid']);
if (isset($PHM_ADB_EX_QUERYTYPE)) { // Obey exclusion of groups marked accordingly
    $cDB->setQueryType($PHM_ADB_EX_QUERYTYPE);
}

$passthrough2 = give_passthrough(2);

$do = false;
if (isset($PHM_ADB_EX_DO)) {
    $do = $PHM_ADB_EX_DO;
} elseif (isset($_REQUEST['do']) && $_REQUEST['do']) {
    $do = $_REQUEST['do'];
}
$return = false;
if ('export' == $do) {
    if (!$myPrivs['all'] && $myPrivs['contacts_export_contacts'] == 0) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $exgroup = 0;
    if (isset($PHM_ADB_EX_GROUP)) {
        $exgroup = $PHM_ADB_EX_GROUP;
    } elseif (isset($_REQUEST['exgroup'])) {
        $exgroup = intval($_REQUEST['exgroup']);
    }
    $exentry = 0;
    if (isset($PHM_ADB_EX_ENTRY)) {
        $exentry = $PHM_ADB_EX_ENTRY;
    } elseif (isset($_REQUEST['exentry'])) {
        $exentry = intval($_REQUEST['exentry']);
    }
    $export_format = false;
    if (isset($PHM_ADB_EX_FORMAT)) {
        $export_format = $PHM_ADB_EX_FORMAT;
    } elseif (isset($_REQUEST['exform'])) {
        $export_format = $_REQUEST['exform'];
    }

    switch ($export_format) {
    case 'VCF':
        // Export single contact or group
        if (!empty($exentry)) {
            $entries = array($cDB->get_contact($exentry, 3));
        } else {
            $entries = $cDB->get_adridx(0, $exgroup);
        }
        // Found nothing ...
        if (empty($entries)) {
            return;
        }

        if (isset($PHM_ADB_EX_PUTTOFILE)) {
            ob_start(); // Catch all output generated to put to file later
        } else {
            header('Content-Type: application/octet-stream');
            // Choose filename, full name if single contact VCF or general if multiple contacts
            if (!empty($exentry)) {
                header('Content-Disposition: attachment; filename="'.$entries[0]['firstname'].' '.$entries[0]['lastname'].'.vcf"');
            } else {
                header('Content-Disposition: attachment; filename="phlyMailAddresses.vcf"');
            }
        }
        $myversion = version_format(file_get_contents($_PM_['path']['conf'].'/current.build'));
        $serverID = ((isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'phlymail.local');

        require_once(__DIR__.'/../calendar/functions.php'); // Holds functions we need, too

        // Generate the output
        $cnt = 0;
        foreach ($entries as $entry) {
            $entry = $cDB->get_contact($entry['aid']);
            if ($cnt > 0) {
                echo CRLF; // Divide entries from each other, only, when more than one
            }
            echo 'BEGIN:VCARD'.CRLF.'VERSION:3.0'.CRLF;
            echo 'PRODID:-//phlyLabs//NONSGML phlyMail '.$myversion.'//EN'.CRLF;
            echo 'UID:'.$exentry.'-'.$entry['uuid'].'@'.$serverID.CRLF;
            $fullname = '';
            if ($entry['title']) {
                $fullname .= $entry['title'].' ';
            }
            if ($entry['firstname']) {
                $fullname .= $entry['firstname'].' ';
            }
            if ($entry['thirdname']) {
                $fullname .= $entry['thirdname'].' ';
            }
            if ($entry['lastname']) {
                $fullname .= $entry['lastname'];
            }
            echo ical_foldline('FN:'.ical_escapetext(rtrim($fullname))).CRLF;
            $n = ical_escapetext($entry['lastname']).';'.ical_escapetext($entry['firstname'])
                    .';'.str_replace(' ', ',', ical_escapetext($entry['thirdname']))
                    .';'.str_replace(' ', ',', ical_escapetext($entry['title']))
                    .';'; // The specs want "honoric suffixes" here, we don't have such a thing right now
            echo ical_foldline('N:'.str_replace(array(',,', '  '), array(',', ' '), $n)).CRLF;
            if (!isset($PHM_ADB_EX_TYPE) || $PHM_ADB_EX_TYPE == 'all' || $PHM_ADB_EX_TYPE == 'busi') {
                echo ical_foldline('EMAIL;TYPE=INTERNET,WORK:'.ical_escapetext($entry['email2'])).CRLF;
                echo ical_foldline('ORG:'.ical_escapetext($entry['company'])).CRLF;
                echo ical_foldline('TITLE:'.ical_escapetext($entry['comp_dep'])).CRLF;
                echo ical_foldline('ROLE:'.ical_escapetext($entry['comp_role'])).CRLF;
                $adr = ';';
                if ($entry['comp_address'] && $entry['comp_address2']) {
                    $adr .= ical_escapetext($entry['comp_address'].','.$entry['comp_address2']);
                } elseif ($entry['comp_address']) {
                    $adr .= ical_escapetext($entry['comp_address']);
                } elseif ($entry['comp_address2']) {
                    $adr .= ical_escapetext($entry['comp_address2']);
                }
                $adr .= ';'.ical_escapetext($entry['comp_street']).';'.ical_escapetext($entry['comp_location'])
                        .';'.ical_escapetext($entry['comp_region']).';'.ical_escapetext($entry['comp_zip'])
                        .';'.ical_escapetext($entry['comp_country']);
                echo ical_foldline('ADR;TYPE=WORK:'.$adr).CRLF;
                echo ical_foldline('TEL;TYPE=WORK:'.ical_escapetext($entry['tel_business'])).CRLF;
                echo ical_foldline('TEL;TYPE=WORK,FAX:'.ical_escapetext($entry['comp_fax'])).CRLF;
                echo ical_foldline('TEL;TYPE=WORK,CELL:'.ical_escapetext($entry['comp_cellular'])).CRLF;
                echo ical_foldline('URL;TYPE=WORK:'.ical_escapetext($entry['comp_www'])).CRLF;
            }
            if (!isset($PHM_ADB_EX_TYPE) || $PHM_ADB_EX_TYPE == 'all' || $PHM_ADB_EX_TYPE == 'priv') {
                echo ical_foldline('NICKNAME:'.ical_escapetext($entry['nick'])).CRLF;
                echo ical_foldline('EMAIL;TYPE=INTERNET,HOME:'.ical_escapetext($entry['email1'])).CRLF;
                echo ical_foldline('BDAY:'.ical_escapetext($entry['birthday'])).CRLF;
                $adr = ';';
                if ($entry['address'] && $entry['address2']) {
                    $adr .= ical_escapetext($entry['address'].','.$entry['address2']);
                } elseif ($entry['address']) {
                    $adr .= ical_escapetext($entry['address']);
                } elseif ($entry['address2']) {
                    $adr .= ical_escapetext($entry['address2']);
                }
                $adr .= ';'.ical_escapetext($entry['street']).';'.ical_escapetext($entry['location'])
                        .';'.ical_escapetext($entry['region']).';'.ical_escapetext($entry['zip'])
                        .';'.ical_escapetext($entry['country']);
                echo ical_foldline('ADR;TYPE=HOME:'.$adr).CRLF;
                echo ical_foldline('TEL;TYPE=HOME:'.ical_escapetext($entry['tel_private'])).CRLF;
                echo ical_foldline('TEL;TYPE=HOME,FAX:'.ical_escapetext($entry['fax'])).CRLF;
                echo ical_foldline('TEL;TYPE=HOME,CELL:'.ical_escapetext($entry['cellular'])).CRLF;
                echo ical_foldline('URL;TYPE=HOME:'.ical_escapetext($entry['www'])).CRLF;
            }
            if (!empty($entry['free'])) {
                foreach ($entry['free'] as $id => $free) {
                    echo 'X-'.phm_strtoupper($free['token']).':'.ical_escapetext($free['value']).CRLF;
                }
            }
            echo 'END:VCARD'.CRLF;
            $cnt++;
        }
        // Should we have set a path to a file to write the output to:
        if (isset($PHM_ADB_EX_PUTTOFILE)) {
            file_put_contents($PHM_ADB_EX_PUTTOFILE, ob_get_clean());
            return;
        }
        break;
    case 'LDIF':
        if (isset($PHM_ADB_EX_PUTTOFILE)) {
            ob_start(); // Catch all output generated to put to file later
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=phlyMailAddresses.ldif');
        }
        foreach ($exentry ? array($cDB->get_contact($exentry, 3)) : $cDB->get_adridx(0, $exgroup) as $line) {
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
            echo 'homeurl: '.$line['www'].LF;
            echo 'mozillaSecondEmail: '.$line['email2'].LF.LF;
        }
        echo LF;
        // Should we have set a path to a file to write the output to:
        if (isset($PHM_ADB_EX_PUTTOFILE)) {
            file_put_contents($PHM_ADB_EX_PUTTOFILE, ob_get_clean());
            return;
        }
        break;
    case 'MSOutl':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.csv');
        foreach ($cDB->get_adridx(0, $exgroup) as $line) {
            foreach ($line as $k => $v) {
            	$line[$k] = decode_utf8(str_replace('"', "'", $v), 'iso-8859-1', true);
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
        foreach ($cDB->get_adridx(0, $exgroup) as $line) {
            foreach ($line as $k => $v) {
            	$line[$k] = decode_utf8(str_replace('"', "'", $v), 'iso-8859-1', true);
                $line[$k] = preg_replace('!('.CRLF.'|'.LF.')!', ' ', $line[$k]);
            }
            echo '"'.$line['nick'].'";"'.$line['firstname'].'";"'.$line['lastname'].'";"'
                    .$line['address'].'";"";"";"";"'.$line['birthday'].'";"'.$line['comments'].'";"'
                    .$line['www'].'";"'.$line['email1'].'";"'.$line['email2'].'";"'
                    .$line['tel_private'].'";"'.$line['tel_business'].'";"'.$line['cellular'].'";"'
                    .$line['fax'].'"'.LF;
        }
        break;
    case 'phlyMailADB3':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=phlyMailAddresses.ldif');
        foreach ($cDB->get_adridx(0, $exgroup, '', '', 0, 0, false, false, true) as $line) {
            $line = $cDB->get_contact($line['aid']);
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
            echo 'xphlymailgroup: '.$line['gid'].LF;
            echo 'homeurl: '.$line['www'].LF;
            if (!empty($line['free'])) {
                foreach ($line['free'] as $id => $free) {
                    echo 'x'.$free['token'].':: '.base64_encode($free['value']).LF;
                }
            }
            echo LF;
        }
        echo LF;
        break;
    case 'CSV':
        $db_fieldlist = array(
                0 => 'nick', 1 => 'firstname', 2 => 'lastname', 3 => 'company',
                4 => 'comp_dep', 5 => 'comp_address', 6 => 'comp_address2',
                7 => 'comp_street', 8 => 'comp_zip', 9 => 'comp_location', 10 => 'comp_region',
                11 => 'comp_country', 12 => 'address', 13 => 'address2', 14 => 'street',
                15 => 'zip', 16 => 'location', 17 => 'region', 18 => 'country',
                19 => 'email1', 20 => 'email2', 21 => 'tel_private', 22 => 'tel_business',
                23 => 'cellular', 24 => 'fax', 25 => 'www', 26 => 'birthday', 27 => 'comments',
                28 => 'customer_number'
                );
        $db_fieldnames = array(
                0 => $WP_msg['nick'], 1 => $WP_msg['fnam'], 2 => $WP_msg['snam'],
                3 => $WP_msg['company'], 4 => $WP_msg['comp_dep'], 5 => $WP_msg['comp_address'],
                6 => $WP_msg['comp_address2'], 7 => $WP_msg['comp_street'],
                8 => $WP_msg['comp_zip'], 9 => $WP_msg['comp_location'], 10 => $WP_msg['comp_region'],
                11 => $WP_msg['comp_country'], 12 => $WP_msg['address'], 13 => $WP_msg['address2'],
                14 => $WP_msg['street'], 15 => $WP_msg['zip'], 16 => $WP_msg['location'],
                17 => $WP_msg['state'], 18 => $WP_msg['country'], 19 => $WP_msg['emai1'],
                20 => $WP_msg['emai2'], 21 => $WP_msg['fon'], 22 => $WP_msg['fon2'],
                23 => $WP_msg['cell'], 24 => $WP_msg['fax'], 25 => $WP_msg['www'],
                26 => $WP_msg['bday'], 27 => $WP_msg['cmnt'], 28 => $WP_msg['CustomerNumber']
                );
        $definedFreeFields = $cDB->get_freefield_types();
        foreach ($definedFreeFields as $freeField) {
            $db_fieldlist[] = 'free_'.$freeField['id'];
            $db_fieldnames[] = $freeField['name'];
        }

        if (isset($_REQUEST['selected_fields'])) {
        	// Make sure just to use a single char delimiter
            $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? $_REQUEST['delimiter']{0} : ';';
            header('Content-Type: application/octet-stream;charset=utf-8');
            header('Content-Disposition: attachment; filename=phlyMailAddresses.csv');
            // If requested, output a descriptive line containing the field names
            // These depend on the selected frontend language
            if (isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                $out = false;
                foreach ($_REQUEST['selected_fields'] as $dbfield) {
                	$fieldname = $db_fieldnames[$dbfield];
                    if ($out) {
                        echo $delimiter;
                    }
                    echo isset($_REQUEST['is_quoted']) ? '"'.$fieldname.'"' : $fieldname;
                    $out = true;
                }
                echo LF;
            }
            foreach ($cDB->get_adridx(0, $exgroup) as $line) {
                $line = $cDB->get_contact($line['aid']);
                $out = false;
                foreach ($_REQUEST['selected_fields'] as $dbfield) {
                    if ($out) {
                        echo $delimiter;
                    }
                    $field = '';
                    if ($dbfield == -1) {
                        $field = '';
                    } elseif (isset($db_fieldlist[$dbfield])) {
                        if (substr($db_fieldlist[$dbfield], 0, 5) == 'free_') {
                            list($type, $index) = explode('_', $db_fieldlist[$dbfield]);
                            $field = isset($line['free'][$index]) ? $line['free'][$index]['value'] : '';
                        } else {
                            $field = $line[$db_fieldlist[$dbfield]];
                        }
                    }
                    $field = str_replace(array("\r", "\n"), array('', '\n'), $field);

                    echo isset($_REQUEST['is_quoted']) ? '"'.$field.'"' : $field;
                    $out = true;
                }
                echo LF;
            }
        } else {
        	// Make sure just to use a single char delimiter
            $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? substr($_REQUEST['delimiter'], 0, 1) : ';';
            $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.exportcsv.tpl');
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
                    ,'form_action' => htmlspecialchars($myurl.'&'.give_passthrough(1).'&do=export&exform=CSV'.($exgroup ? '&exgroup='.$exgroup : ''))
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
    if (!$return) {
        exit;
    }
}
if ('import' == $do) {

    if (!$myPrivs['all'] && $myPrivs['contacts_import_contacts'] == 0) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $imported = 0;
    $imgroup = isset($_REQUEST['imgroup']) ? intval($_REQUEST['imgroup']) : 0;

    if (!empty($_REQUEST['imurl']) || isset($_FILES['imfile']) || !empty($_SESSION['WP_impfile'])) {
        $temp_name = $_PM_['path']['temp'].'/'.SecurePassword::generate(16, false, STRONGPASS_LOWERCASE | STRONGPASS_DECIMALS);

        // URL given, try to download and process it
        if (!empty($_REQUEST['imurl'])) {
            $dlinfo = basics::download
                    ($_REQUEST['imurl']
                    ,$temp_name
                    ,$temp_name.'.dnl'
                    ,1073741824
                    );
            $file = file($temp_name);
            @unlink($temp_name);
            @unlink($temp_name.'.dnl');

        // CSV preprocessed
        } elseif (!isset($_FILES['imfile']) && isset($_SESSION['WP_impfile'])) {
            $file = $_SESSION['WP_impfile'];
            unset($_SESSION['WP_impfile']);
        } elseif (is_uploaded_file($_FILES['imfile']['tmp_name'])) {
            ini_set('auto_detect_line_endings', 'true');
            move_uploaded_file($_FILES['imfile']['tmp_name'], $temp_name);
            $file = file($temp_name);
            @unlink($temp_name);
        }
        // Allow the importer to also add the birthday as an event to the calendar

        try {
            $API = new handler_calendar_api($_PM_, $_SESSION['phM_uid']);
        } catch (Exception $e) {
            $API = false;
        }
        switch ($_REQUEST['imform']) {
        case 'LDIF':
        case 'phlyMailADB':
        case 'phlyMailADB3':
            // Empty selected calendar before importing from the file
            // Inspired by idea of E. Turcan
            if (!empty($_REQUEST['truncate'])) {
				$cDB->empty_group($imgroup);
            }
            $tokenBucket = array('xmozillanickname' => 'nick', 'givenname' => 'firstname', 'thirdname' => 'thirdname'
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
                        ,'description' => 'comments', 'xphlymailimagemeta' => 'imagemeta'
                        ,'xphlymailimage' => 'image', 'xphlymailgroup' => 'gid'
                        ,'xphlymailtitle' => 'title', 'xphlymailthirdname' => 'thirdname'
                        ,'xphlymailhomeGeoLat' => 'personal_geo_lat', 'xphlymailhomeGeoLong' => 'personal_geo_long'
                        ,'xphlymailcompanyrole' => 'comp_role', 'xphlymailcompanyfax' => 'comp_fax'
                        ,'xphlymailcompanymobile' => 'comp_cellular', 'xphlymailcompanywww' => 'comp_www'
                        ,'xphlymailcompanygeolat' => 'comp_geo_lat', 'xphlymailcompanygeolong' => 'comp_geo_long'
                        ,'xphlymailcustomernumber' => 'customer_number', 'mozillaSecondEmail' => 'email2'
                        );
            $definedFreeFields = $cDB->get_freefield_types();
            foreach ($definedFreeFields as $freeField) {
                $tokenBucket['x'.$freeField['token']] = array('free', $freeField['id']);
            }
            $file = explode(LF.LF, preg_replace('!(\r\n|\r|\n)!', LF, implode($file)));
            foreach ($file as $key => $value) {
                $save = false;
                foreach ($tokenBucket as $needle => $field) {
                    if ($needle && preg_match('!^'.$needle.':(:?)\ ?(.+)$!im', $value, $found)) {
                        // double colon denotes base64 data
                        if ($found[1]) {
                            $found[2] = base64_decode($found[2]);
                        }
                        // there actually is sth. stored in the line
                        if ($found[2]) {
                            // free fields are arrays
                            if (is_array($field)) {
                                $save[$field[0]][$field[1]] = $found[2];
                            } else {
                                $save[$field] = $found[2];
                            }
                        }
                    }
                }
                if (!empty($save)) {
                    $save['owner'] = $_SESSION['phM_uid'];
                    $save['group'] = array($imgroup);
                    if (!isset($save['nick'])) {
                        $save['nick'] = false;
                    }
                    if (!isset($save['firstname'])) {
                        $save['firstname'] = false;
                    }
                    if (!isset($save['lastname'])) {
                        $save['lastname'] = false;
                    }
                    if (!isset($save['birthday'])) {
                        $save['birthday'] = '0000';
                    }
                    // Try to add the birthday to the calendar
                    $displayname = false;
                    if ($save['nick']) {
                        $displayname = $save['nick'];
                    } elseif ($save['lastname'] && $save['firstname']) {
                        $displayname = $save['firstname'].' '.$save['lastname'];
                    } elseif ($save['firstname']) {
                        $displayname = $save['firstname'];
                    } elseif ($save['lastname']) {
                        $displayname = $save['lastname'];
                    }
                    if (false !== $API && $displayname && substr($save['birthday'], 0, 4) != '0000') {
                        $cal_evt_id = $API->add_event(array
                                ('start' => $save['birthday'].' 0:0:0'
                                ,'end' => $save['birthday'].' 0:0:0'
                                ,'title' => $WP_msg['bday'].' '.$displayname
                                ,'repeat_type' => 'year'
                                ,'type' => 3
                                ,'status' => 2
                                ));
                        if ($cal_evt_id) {
                            $save['bday_cal_evt_id'] = $cal_evt_id;
                        }
                    }
                    // END birthday
                    $res = $cDB->add_contact($save);
                    if ($res) {
                        ++$imported;
                    }
                }
            }
            break;
        case 'MSOutl':
        case 'MSOutlEx':
            break;
        case 'MSOutlEx6':
            // Empty selected calendar before importing from the file
            // Inspired by idea of E. Turcan
            if (!empty($_REQUEST['truncate'])) {
				$cDB->empty_group($imgroup);
            }
            foreach ($file as $key => $value) {
                $line = explode(';', str_replace('"', '', trim(encode_utf8($value, 'iso-8859-1', true))));
                $save = array();
                foreach (array('nick' => 3, 'firstname' => array(0, 2), 'lastname' => 1, 'company' => 25
                        ,'street' => 6, 'location' => 7, 'zip' => 8, 'region' => 9, 'country' => 10
                        ,'email1' => 5, 'email2' => false, 'tel_private' => 12
                        ,'tel_business' => 22, 'cellular' => 14, 'fax' => 23, 'www' => 15
                        ,'birthday' => false, 'comments' => 29, 'free1' => false, 'free2' => false
                        ,'free3' => false, 'free4' => false, 'free5' => false, 'free6' => false
                        ,'free7' => false, 'free8' => false, 'free9' => false, 'free10' => false
                        ,'gid' => false
                        ) as $field => $needle) {
                    if (false === $needle) {
                        // Not mapped
                        continue;
                    } elseif (is_array($needle)) {
                        // Collected fields: Stored in one field within phlyMailADB, but in
                        // various fields of the source
                        $save[$field] = false;
                        foreach ($needle as $part) { if ($line[$part]) { $save[$field][] = $line[$part]; } }
                        if (!empty($save[$field])) {
                            $save[$field] = implode(' ', $save[$field]);
                        } else {
                            unset($save[$field]);
                        }
                    } else {
                        if ($line[$needle]) {
                            $save[$field] = $line[$needle]; // 1:1 translation
                        }
                    }
                }
                if (!empty($save)) {
                    $save['owner'] = $_SESSION['phM_uid'];
                    $save['group'] = array($imgroup);
                    $res = $cDB->add_contact($save);
                    if ($res) {
                        ++$imported;
                    }
                }
            }
            break;
        case 'CSV':
            // Empty selected calendar before importing from the file
            // Inspired by idea of E. Turcan
            if (!empty($_REQUEST['truncate'])) {
				$cDB->empty_group($imgroup);
            }
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
            $definedFreeFields = $cDB->get_freefield_types();
            foreach ($definedFreeFields as $freeField) {
                $db_fieldlist[] = 'free_'.$freeField['id'];
                $db_fieldnames[] = $freeField['name'];
            }
            if (isset($_REQUEST['selected_fields'])) {
                foreach ($file as $k => $line) {
                	// $line = encode_utf8($line, 'iso-8859-1', true);
                    if (0 == $k && isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                        continue;
                    }
                    if (isset($_REQUEST['is_quoted']) && $_REQUEST['is_quoted']) {
                        $line = str_replace('"', '', $line);
                    }
                    // Make sure just to use a single char delimiter
                    $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? substr($_REQUEST['delimiter'], 0, 1) : ';';
                    $line = explode($delimiter, trim($line));
                    $save = array();
                    foreach ($_REQUEST['selected_fields'] as $dbfield => $csvfield) {
                         if (substr($db_fieldlist[$dbfield], 0, 5) == 'free_') {
                            list($type, $index) = explode('_', $db_fieldlist[$dbfield]);
                            $save['free'][$index] = !empty($line[$csvfield]) ? $line[$csvfield] : '';
                        } else {
                            $save[$db_fieldlist[$dbfield]] = !empty($line[$csvfield]) ? $line[$csvfield] : '';
                        }
                    }
                    if (!empty($save)) {
                        $save['owner'] = $_SESSION['phM_uid'];
                        $save['group'] = array($imgroup);
                        $res = $cDB->add_contact($save);
                        if ($res) {
                            ++$imported;
                        }
                    }
                }
            } else {
                $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.importcsv.tpl');
                $_SESSION['WP_impfile'] = $file;
                $file = isset($file[0]) ? trim($file[0]) : false;
                if (!$file) {
                    break; // File not readable / non existant -> return to input mask
                }
                if (isset($_REQUEST['fieldnames']) && $_REQUEST['fieldnames']) {
                    $tpl->assign_block('if_fieldnames');
                }
                if (isset($_REQUEST['is_quoted']) && $_REQUEST['is_quoted']) {
                    $tpl->assign_block('if_quoted');
                    $file = str_replace('"', '', $file);
                }
                // Make sure just to use a single char delimiter
                $delimiter = (isset($_REQUEST['delimiter']) && $_REQUEST['delimiter']) ? substr($_REQUEST['delimiter'], 0, 1) : ';';
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
                        ,'form_action' => htmlspecialchars($myurl.'&'.give_passthrough(1).'&do=import&imform=CSV'.($imgroup ? '&imgroup='.$imgroup : ''))
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
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.exchmenu.tpl');
    $passthru = give_passthrough();
    $def_groups = $cDB->get_grouplist(0);
    if ($myPrivs['all'] || $myPrivs['contacts_import_contacts']) {
        $tpl_imp = $tpl->get_block('import');
        $tpl_imp->assign(array
                ('target' => $myurl . '&amp;do=import&amp;' . $passthru
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
                ,'msg_truncate' => $WP_msg['ImpTruncate']
                ,'msg_none' => $WP_msg['none']
                ,'msg_group' => $WP_msg['group']
                ,'msg_go' => $WP_msg['Import']
                ));
        if ($return) {
            $tpl->fill_block('return', 'return', $return);
        }
        $imop = $tpl_imp->get_block('imoption');
        foreach (array('LDIF' => 'LDIF', 'MSOutlEx6' => 'Microsoft Outlook Express 6'
                ,'CSV' => $WP_msg['csvMenuOption'], 'phlyMailADB' => 'phlyMailADB (phlyMail 2.1+)'
                ,'phlyMailADB3' => 'phlyMailADB3 (phlyMail 3+)') as $val => $name) {
            $imop->assign(array('value' => $val, 'name' => $name));
            $tpl_imp->assign('imoption', $imop);
            $imop->clear();
        }
        $imgr = $tpl_imp->get_block('imgroup');
        foreach ($def_groups as $v) {
            $imgr->assign(array('id' => $v['gid'], 'name' => $v['name']));
            $tpl_imp->assign('imgroup', $imgr);
            $imgr->clear();
        }
        $tpl->assign('import', $tpl_imp);
    }
    if ($cDB->get_adrcount($_SESSION['phM_uid'], 0) && ($myPrivs['all'] || $myPrivs['contacts_export_contacts'])) {
        $tpl_exp = $tpl->get_block('export');
        $tpl_exp->assign(array
                ('target' => $myurl . '&amp;do=export&amp;' . $passthru
                ,'msg_select' => $WP_msg['plsSel']
                ,'passthrough' => $passthrough2
                ,'about_export' => $WP_msg['AboutExport']
                ,'leg_export' => $WP_msg['Export']
                ,'msg_csv_only' => $WP_msg['LegendCSV']
                ,'msg_fieldnames' => $WP_msg['csvFirstLine']
                ,'msg_csv_quoted' => $WP_msg['csvIsQuoted']
                ,'msg_field_delimiter' => $WP_msg['csvFieldDelimiter']
                ,'msg_format' => $WP_msg['format']
                ,'msg_none' => $WP_msg['none']
                ,'msg_group' => $WP_msg['group']
                ,'msg_go' => $WP_msg['Export']
                ));
        $exop = $tpl_exp->get_block('exoption');
        foreach (array('LDIF' => 'LDIF',
                'VCF' => 'vCard (VCF)',
                'MSOutl' => 'Micosoft Outlook', 'MSOutlEx' => 'Microsoft Outlook Express', /*'MSOutlEx6' => 'Microsoft Outlook Express 6',*/
                'phlyMailADB3' => 'phlyMailADB3 (phlyMail 3+)',
                'CSV' => $WP_msg['csvMenuOption']) as $val => $name) {
            $exop->assign(array('value' => $val, 'name' => $name));
            $tpl_exp->assign('exoption', $exop);
            $exop->clear();
        }
        $exgr = $tpl_exp->get_block('exgroup');
        foreach ($def_groups as $v) {
            $exgr->assign(array('id' => $v['gid'], 'name' => $v['name']));
            $tpl_exp->assign('exgroup', $exgr);
            $exgr->clear();
        }
        $tpl->assign('export', $tpl_exp);
    }
}
