<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @subpackage Invitations and attendees
 * @copyright 2010-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.6 2015-03-31 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
//
// Branch handling incoming XNA request
//
if (isset($XNA)) {
    if (!strlen($_REQUEST['rsvp'])) {
        header('HTTP/1.0 400 Bad Request');
        header('Status: 400 Bad Request');
        die('Missing or wrong parameter');
    }
    $cDB = new handler_calendar_driver(0); // We don't have a user id here

    $attendee = $cDB->get_event_attendees(null, null, $XNA);
    if (empty($attendee)) {
        header('HTTP/1.0 400 Bad Request');
        header('Status: 400 Bad Request');
        die('Missing or wrong parameter');
    }
    $rsvp = intval($_REQUEST['rsvp']);
    $cDB->set_event_attendee_rsvp($XNA, $rsvp);

    $_PM_['temp']['load_tpl_auth'] = 1;
    $tpl = $WP_msg['XNARSVPhead'];
    if ($rsvp == 1) {
        $tpl .= $WP_msg['XNARSVPyes'];
    } elseif ($rsvp == 2) {
        $tpl .= $WP_msg['XNARSVPno'];
    } elseif ($rsvp == 3) {
        $tpl .= $WP_msg['XNARSVPmaybe'];
    }
    return;
    //
    // Don't fall through!!!
    //
}
//
// Included on storing event; check, whether we need to store attendees
//
if (isset($_REQUEST['save_event']) && $_REQUEST['save_event']) {
    $createdMap = array();
    if (isset($_REQUEST['delattendee'])) {
        foreach ($_REQUEST['delattendee'] as $k => $v) {
            $cDB->delete_event_attendee($k);
        }
    }
    if (isset($_REQUEST['editattendee'])) {
        foreach ($_REQUEST['editattendee'] as $k => $v) {
            $cDB->update_event_attendee(
                    $k,
                    (isset($v['name'])) ? $v['name'] : null,
                    (isset($v['email'])) ? $v['email'] : null,
                    (isset($v['status'])) ? $v['status'] : null,
                    (isset($v['role'])) ? $v['role'] : null,
                    (isset($v['type'])) ? $v['type'] : null
                    );
        }
    }
    if (isset($_REQUEST['addattendee'])) {
        foreach ($_REQUEST['addattendee'] as $k => $v) {
            list($id, $hash) = $cDB->add_event_attendee(
                    $eid,
                    (isset($v['name'])) ? $v['name'] : null,
                    (isset($v['email'])) ? $v['email'] : null,
                    (isset($v['status'])) ? $v['status'] : null,
                    (isset($v['role'])) ? $v['role'] : null,
                    (isset($v['type'])) ? $v['type'] : null
                    );
            $createdMap[$k] = $id;
        }
    }
    if (isset($_REQUEST['invite_email'])) {
        $invites = array();
        foreach ($_REQUEST['invite_email'] as $k => $v) {
            if (substr($k, 0, 2) == 'x_') {
                if (isset($createdMap[$v])) {
                    $invites[] = $createdMap[$v];
                }
            } else {
                $invites[] = $k;
            }
        }
        if (!empty($invites)) {
            $extraOutput = array('send_invites' => $invites);
        }
    }
    return;
    //
    // Don't fall through!!!
    //
}
//
// Send invitation email
//
if (isset($_REQUEST['send_invitation']) && $_REQUEST['send_invitation']) {
    $evtStatus = array
            (0 => '-'
            ,1 => $WP_msg['CalStatDueForApp']
            ,2 => $WP_msg['CalStatApproved']
            ,3 => $WP_msg['CalStatCancelled']
            ,10 => $WP_msg['CalStatTentative']
            ,11 => $WP_msg['CalStatNeedsAction']
            );
    $evtTypes = array
            (0 => '-'
            ,1 => $WP_msg['CalTyAppointment']
            ,2 => $WP_msg['CalTyHoliday']
            ,3 => $WP_msg['CalTyBirthday']
            ,4 => $WP_msg['CalTyPersonal']
            ,5 => $WP_msg['CalTyEducation']
            ,6 => $WP_msg['CalTyTravel']
            ,7 => $WP_msg['CalTyAnniversary']
            ,8 => $WP_msg['CalTyNotInOffice']
            ,9 => $WP_msg['CalTySickDay']
            ,10 => $WP_msg['CalTyMeeting']
            ,11 => $WP_msg['CalTyVacation']
            ,12 => $WP_msg['CalTyPhoneCall']
            ,13 => $WP_msg['CalTyBusiness']
            ,14 => $WP_msg['CalTyNonWorkingHours']
            ,50 => $WP_msg['CalTySpecialOccasion']
            );
    //
    // Build email
    //
    $tmpName = uniqid(time().'.');
    $subject = 'phlyMail';
    if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
        $subject = $_PM_['core']['provider_name'];
    } elseif (file_exists($_PM_['path']['conf'].'/build.name')) {
        $subject = file_get_contents($_PM_['path']['conf'].'/build.name');
    }
    $Acnt = new DB_Controller_Account();
    $PHM_CAL_EX_DO = 'export';
    $PHM_CAL_EX_NOATTENDEES = true;
    $PHM_CAL_EX_PUTTOFILE = $_PM_['path']['temp'].'/'.$tmpName; // Will put ICS file as attachment into FS
    $PHM_CAL_EX_FORMAT = 'ICS';
    $PHM_CAL_EX_ORGANIZER = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $_PM_);
    $PHM_CAL_EX_EVENT = intval($_REQUEST['send_invitation']);
    require_once __DIR__.'/exchange.php';
    // Details about attendee
    $att = $cDB->get_event_attendees(null, $_REQUEST['att'], null);
    $evt = $cDB->get_event($_REQUEST['send_invitation']);
    // Set attendee invited
    $cDB->set_event_attendee_invited($att['mailhash']);
    // register XNA request for the sent invitation
    $XNA = new DB_Controller_XNA();
    $XNA->register('calendar', 'invitation', null, $att['mailhash']);
    $WP_send = array
            ('to' => $att['email']
            ,'subj' => $subject.' '.$WP_msg['MailRSVPhead']
            ,'bodytype' => 'text/html'
            ,'attach' => array(array('mode' => 'user', 'filename' => $tmpName, 'mimetype' => 'text/calendar', 'name' => 'invitation.ics'))
            );
    $rsvp_link = PHM_SERVERNAME.(dirname(PHP_SELF) == '/' ? '' : dirname(PHP_SELF)).'/?XNA='.$att['mailhash'];
    $tpl = new phlyTemplate($_PM_['path']['conf'].'/calendar.invitemail.tpl');
    $tpl->assign(array
            ('head_invite' => $subject.' '.$WP_msg['MailRSVPhead']
            ,'msg_prologue' => str_replace('$name$', $att['name'], $WP_msg['MailRSVPprologue'])
            ,'msg_title' => $WP_msg['CalTitle']
            ,'msg_location' => $WP_msg['CalLocation']
            ,'msg_type' => $WP_msg['CalType']
            ,'msg_when' => $WP_msg['CalWhen']
            ,'msg_status' => $WP_msg['CalStatus']
            ,'msg_desc' => $WP_msg['CalDescription']
            ,'about_rsvp' => $WP_msg['MailRSVPabout']
            ,'msg_rsvp_yes' => $WP_msg['MailRSVPyes']
            ,'msg_rsvp_no' => $WP_msg['MailRSVPno']
            ,'msg_rsvp_maybe' => $WP_msg['MailRSVPmaybe']
            ,'html_bidi' => $WP_msg['html_bidi']
            ,'charset' => 'utf-8'
            ,'title' => $evt['title']
            ,'location' => $evt['location']
            ,'type' => isset($evtTypes[$evt['type']]) ? $evtTypes[$evt['type']] : '-'
            ,'when' => $WP_msg['html_bidi'] == 'ltr'
                    ? date($WP_msg['dateformat'], $evt['start']).' - '.date($WP_msg['dateformat'], $evt['end'])
                    : date($WP_msg['dateformat'], $evt['end']).' - '.date($WP_msg['dateformat'], $evt['start'])
            ,'status' => isset($evtStatus[$evt['status']]) ? $evtStatus[$evt['status']] : '-'
            ,'description' => text2html($evt['description'])
            ,'link_rsvp_yes' => htmlspecialchars($rsvp_link.'&rsvp=1', ENT_QUOTES, 'utf-8')
            ,'link_rsvp_no' => htmlspecialchars($rsvp_link.'&rsvp=2', ENT_QUOTES, 'utf-8')
            ,'link_rsvp_maybe' => htmlspecialchars($rsvp_link.'&rsvp=3', ENT_QUOTES, 'utf-8')
            ));
    $WP_send['body'] = $tpl->get_content();
    // Forward to Core's send email module
    require __DIR__.'/../core/send.email.php';
    //
    // Make sure, we don't fall through!
    //
    exit;
}
