<?php
/**
 * Receive something from another handler (vCard data right now)
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @subpackage Import / Export
 * @copyright 2006-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_add_contact']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

// See RFC2426 for details ...
$srchdl = preg_replace('![^a-zA-Z_]!', '', $_REQUEST['source']);
$toload = 'handler_'.$srchdl.'_api';
$API = new $toload($_PM_, $_SESSION['phM_uid']);
$srcinfo = $API->sendto_fileinfo($_REQUEST['resid']);
$raw = $API->sendto_sendinit($_REQUEST['resid']);
// Parse the event data
$return = array();
preg_match('!BEGIN:VCARD(.+)END:VCARD!s', $raw, $event);
if (!isset($event[1])) {
    $error = 'No parsable vCard data';
} else {
    // Unfolding of vcard data
    $event[1] = preg_replace('!\r\n[\s\t]!', '', $event[1]);
    // Unslice it all
    $contact = array();
    preg_match_all('!^([A-Z]+)(;.+)?:(.+)$!Umi', $event[1], $matches);

    foreach ($matches[1] as $k => $token) {
        if (preg_match('!encoding\=(.+)(;|$)!i', $matches[2][$k], $encoding)) {
            if (strtolower($encoding[1]) == 'quoted_printable') {
                $matches[3][$k] = quoted_printable_decode($matches[3][$k]);
            } elseif (strtolower($encoding[1]) == 'base64') {
                $matches[3][$k] = base64_decode($matches[3][$k]);
            }
        }
        if (preg_match('!charset\=(.+)(;|$)!i', $matches[2][$k], $charset)) {
            $matches[3][$k] = encode_utf8($matches[3][$k], $charset[1]);
        }
        switch ($token) {
        case 'TEL':
            if (preg_match('!WORK!i', $matches[2][$k])) {
                if (preg_match('!FAX!i', $matches[2][$k])) {
                    $contact['comp_fax'] = $matches[3][$k];
                } elseif (preg_match('!CELL!i', $matches[2][$k])) {
                    $contact['comp_cellular'] = $matches[3][$k];
                } elseif (preg_match('!VOICE!i', $matches[2][$k])) {
                    $contact['tel_business'] = $matches[3][$k];
                }
            } else {
                if (preg_match('!FAX!i', $matches[2][$k])) {
                    $contact['fax'] = $matches[3][$k];
                } elseif (preg_match('!CELL!i', $matches[2][$k])) {
                    $contact['cellular'] = $matches[3][$k];
                } elseif (preg_match('!VOICE!i', $matches[2][$k])) {
                    $contact['tel_private'] = $matches[3][$k];
                }
            }
            break;
        case 'EMAIL':
            if (preg_match('!WORK!i', $matches[2][$k])) {
                $contact['email2'] = $matches[3][$k];
            } else {
                $contact['email'] = $matches[3][$k];
            }
            break;
        case 'ADR':
            // RFC 2646 says: PO Box, Extended Address, Street, Locality, Region, Postal Code, Country Name
            $parts = preg_split('/(?<!\\\);/', $matches[3][$k]); // Don't match escaped chars!
            if (preg_match('!WORK!i', $matches[2][$k])) {
                $contact['comp_address'] = $parts[0];
                $contact['comp_address2'] = $parts[1];
                $contact['comp_street'] = $parts[2];
                $contact['comp_location'] = $parts[3];
                $contact['comp_region'] = $parts[4];
                $contact['comp_zip'] = $parts[5];
                $contact['comp_country'] = $parts[6];
            } else {
                $contact['address'] = $parts[0];
                $contact['address2'] = $parts[1];
                $contact['street'] = $parts[2];
                $contact['location'] = $parts[3];
                $contact['region'] = $parts[4];
                $contact['zip'] = $parts[5];
                $contact['country'] = $parts[6];
            }
            break;
        case 'N': $contact['n'] = $matches[3][$k]; break;
        case 'FN': $contact['fn'] = $matches[3][$k]; break;
        case 'NICKNAME': $contact['nick'] = $matches[3][$k]; break;
        case 'ORG': $contact['company'] = $matches[3][$k]; break;
        case 'NOTE': $contact['comments'] = $matches[3][$k]; break;
        case 'ROLE': $contact['comp_role'] = $matches[3][$k]; break;
        case 'TITLE': $contact['comp_dep'] = $matches[3][$k]; break;
        case 'BDAY':
            if (preg_match('!^(\d+)-(\d+)-(\d+)!', $matches[3][$k], $bday_parts)) {
                $byear = intval($bday_parts[1]);
                $bmonth = intval($bday_parts[2]);
                $bday = intval($bday_parts[3]);
            }
        }
    }
    // This makes sure, we try to get the more specific firts and only fallback, if we need to.
    if (isset($contact['n'])) {
        // RFC 2646 says: Family Name, Given Name, Additional Names, Honorific Prefixes, and Honorific Suffixes
        $parts = preg_split('/(?<!\\\);/', $contact['n']); // Don't match escaped chars!
        $contact['lastname'] = $parts[0];
        $contact['firstname'] = $parts[1];
        $contact['thirdname'] = str_replace(',', ' ', $parts[2]);
        $contact['title'] = implode(' ', array(str_replace(',', ', ', $parts[3]), str_replace(',', ', ', $parts[4])));
    } elseif (isset($contact['fn'])) {
        list($contact['firstname'], $contact['lastname']) = explode(' ', $contact['fn']);
    }

    // Unescape
    foreach ($contact as $k => $v) {
        $contact[$k] = str_replace(array('\N', '\n', '\,', '\:', '\;', '\"', '\\\\'), array(LF, LF, ',', ':', ';', '"', '\\'), $v);
    }
    require __DIR__.'/edit_contact.php';
}
