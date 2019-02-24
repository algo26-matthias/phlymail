<?php
/**
 * collection of a few functions for the calendar
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @copyright 2006-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.2 2015-02-25 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!defined('DATE_ICAL_UTC')) {
    define('DATE_ICAL_UTC', 'Ymd\THis\Z');
}
if (!defined('DATE_ICAL_LOCAL')) {
    define('DATE_ICAL_LOCAL', 'Ymd\THis');
}

/**
 * Tries to parse a record from an iCal file
 *
 * @param string $data  Raw entry of type VEVENT / VTODO, may contain VALARM, too
 * @param string $type  Specify the type of the raw data for correct parsing (VEVENT|VTODO)
 * @param array $eventTypes  event types list from DB
 * @param array $eventStatus  event status list from DB
 * @return array $return  Record data matching format of calendar driver's add_event()
 * @since 4.0.2
 */
function parse_icaldata($data, $type = 'VEVENT', $eventTypes = array(), $eventStatus = array())
{
    // fix lousy Sunbird files.
    $data = preg_replace('!\W+(:|;)!m', '\1', $data);
    $return = array();
    preg_match('!BEGIN:'.$type.'(.+)END:'.$type.'!s', $data, $event);
    preg_match_all('!BEGIN:VALARM(.+)END:VALARM!Us', $data, $alarm, PREG_PATTERN_ORDER);
    if (!isset($event[1])) {
        return false; // Could not parse data
    }

    // Parse VEVENT data

    // Unfolding of calendar data
    $event[1] = preg_replace('/(\r\n|\n)([\ \t]+)/', '', $event[1]);
    foreach (array
                    (array('!^DTSTART(;.+)?:(.+)$!mi', 'start', 2, 1)
                    ,array('!^DTEND(;.+)?:(.+)$!mi', 'end', 2, 1)
                    ,array('!^DUE(;.+)?:(.+)$!mi', 'due', 2, 1)
                    ,array('!^COMPLETED(;.+)?:(.+)$!mi', 'completed_on', 2, 0)
                    ,array('!^LOCATION(;.+)?:(.+)$!mi', 'location', 2, 0)
                    ,array('!^DESCRIPTION(;.+)?:(.+)$!mi', 'description', 2, 0)
                    ,array('!^SUMMARY(;.+)?:(.+)$!mi', 'title', 2, 0)
                    ,array('!^STATUS(;.+)?:(.+)$!mi', 'status', 2, 0)
                    ,array('!^CATEGORIES(;.+)?:(.+)$!mi', 'type', 2, 0)
                    ,array('!^PERCENT-COMPLETE(;.+)?:(.+)$!mi', 'completion', 2, 0)
                    ,array('!^PRIORITY(;.+)?:(.+)$!mi', 'importance', 2, 0)
                    ,array('!^TRANSP(;.+)?:(.+)$!mi', 'opaque', 2, 0)
                    ,array('!^UID(;.+)?:(.+)$!mi', 'uuid', 2, 0)
            ) as $needle) {
        if (preg_match($needle[0], $event[1], $found)) {
            $return[$needle[1]] = trim($found[$needle[2]]);
            if ($needle[3] == 1 && preg_match('!;tzid\=([^;]+)!i', $found[1], $tzsearch)) {
                try {
                    // This is a seriously dangerous operation, since every bogus timezone will fail
                    $origTZ = new DateTimeZone($tzsearch[1]);
                    $myTZ =   new DateTimeZone(PHM_TIMEZONE);
                    $origDate = new DateTime($return[$needle[1]], $origTZ);
                    $origDate->setTimezone($myTZ);
                    $return[$needle[1]] = $origDate->format(DATE_ICAL_LOCAL);
                } catch (Exception $ex) {
                    // void
                }
            }
        } else {
            $return[$needle[1]] = false;
        }
    }
    // Unescape
    foreach ($return as $k => $v) {
        $return[$k] = str_replace(array('\N', '\n', '\,', '\:', '\;', '\"', '\\\\'), array(LF, LF, ',', ':', ';', '"', '\\'), $v);
    }
    if (!empty($return['due']) && empty($return['end'])) {
        $return['end'] = $return['due'];
    }
    unset($return['due']);

    if ($type == 'VEVENT' && empty($return['end'])) {
        $return['end'] = $return['start'];
    }

    $starts_unixtime = strtotime($return['start']);
    $ends_unixtime = strtotime($return['end']);
    $return['start'] = ($return['start']) ? date('Y-m-d H:i:s' , $starts_unixtime) : 0;
    $return['end'] = ($return['end']) ? date('Y-m-d H:i:s' , $ends_unixtime) : 0;
    // Recognize all-day events, which are stored kinda funny in iCal and Google Calendar.
    // phlyMail stores these as 2011-03-14 00:00 ... 2011-03-14 23:59 whereas the above
    // use 2011-03-14 ... 2011-03-15 (without stating time)
    if (date('Ymd', $starts_unixtime) < date('Ymd', $ends_unixtime)
            && date('His', $starts_unixtime) == '000000'
            && date('His', $ends_unixtime) == '000000') {
        $ends_unixtime -= 60;
        $return['end'] = date('Y-m-d H:i:s' , $ends_unixtime);
    }
    $return['starts'] = $starts_unixtime;
    $return['ends'] = $ends_unixtime;
    // TRANSPARENT / OPAQUE
    if (isset($return['opaque']) && strtoupper($return['opaque']) == 'TRANSPARENT') {
        $return['opaque'] = 0;
    } else {
        $return['opaque'] = 1;
    }

    // For VTODO
    $return['completed_on'] = ($return['completed_on']) ? date('Y-m-d H:i:s' , strtotime($return['completed_on'])) : 0;
    if ($return['status']) {
        $return['status'] = strtoupper($return['status']);
        if ($return['status'] == 'TENTATIVE') {
            $return['status'] = 10;
        } elseif ($return['status'] == 'CONFIRMED') {
            $return['status'] = 2;
        } elseif ($return['status'] == 'CANCELLED') {
            $return['status'] = 3;
        } elseif ($return['status'] == 'NEEDS-ACTION') {
            $return['status'] = 11;
        } elseif ($return['status'] == 'COMPLETED') {
            $return['status'] = 6;
        } elseif ($return['status'] == 'IN-PROCESS') {
            $return['status'] = 5;
        } else {
            $return['status'] = 0;
        }
    } else {
        $return['status'] = 0;
    }
    // iCal allows more than one category (hence the tag name), but phlyMail currently only allows exactly one
    // This won't change in the near future, since it would totally break the UI and data structure
    if ($return['type']) {
        $found = 0;
        foreach ($eventTypes as $k => $v) {
            if (stripos($return['type'], $v) !== false) {
                $found = 1;
                $return['type'] = $k;
                break;
            }
        }
        if (!$found) {
            $return['type'] = 0;
        }
    }

    //
    // Examination of RRULE: properties, which hold repetition rules
    //
    preg_match_all('!^RRULE:(.+)$!mi', $event[1], $rules, PREG_PATTERN_ORDER);
    if (isset($rules[1]) && !empty($rules[1])) {
        $return['repetitions'] = array();
        foreach ($rules[1] as $raw_rule) {
            $rule = array('type' => '-', 'until' => null, 'until_unix' => 0, 'extra' => '', 'repeat' => 0);
            if (preg_match('!\;COUNT\=([^;]+)!', $raw_rule, $found)) {
                $rule['count'] = intval($found[1]);
                $raw_rule = str_replace($found[0], '', $raw_rule);
            }
            if (preg_match('!\;UNTIL\=([^;]+)!', $raw_rule, $found)) {
                $rule['until'] = date('Y-m-d H:i:s' , strtotime($found[1]));
                $raw_rule = str_replace($found[0], '', $raw_rule);
            }
            if (preg_match('!^FREQ\=YEARLY!', $raw_rule)) {
                $rule['type'] = 'year';
            } elseif (preg_match('!^FREQ\=MONTHLY!', $raw_rule)) {
                $rule['type'] = 'month';
                $day = (preg_match('!\;BYMONTHDAY\=(.+)!', $raw_rule, $found))
                        ? intval($found[1])
                        : date('j', $return['starts']);
                $rule['repeat'] = $day;
                if (preg_match('!\;BYMONTH\=(.+)!', $raw_rule, $found)) {
                    $rule['extra'] = preg_replace('!\;BYMONTHDAY\=(.+)!', '', $found[1]);
                }
            } elseif (preg_match('!^FREQ\=WEEKLY!', $raw_rule)) {
                $rule['type'] = 'week';
                $rule['repeat'] = date('w', $return['starts']);
                if (preg_match('!\;BYDAY\=(.+)!', $raw_rule, $found)) {
                    $rule['type'] = 'day';
                    $rule['repeat'] = ical_parseByDay($found[1]);
                }
            } elseif (preg_match('!^FREQ\=DAILY!', $raw_rule)) {
                $rule['type'] = 'day';
                $rule['repeat'] = 0;
                if (preg_match('!\;BYDAY\=(.+)!', $raw_rule, $found)) {
                    $rule['repeat'] = ical_parseByDay($found[1]);
                }
                if ($rule['repeat'] == 0) {
                    $rule['repeat'] = 127;
                }
            }
            if (!empty($rule['count']) && empty($rule['until']) && $rule['count'] < 200) {
                $rule['until'] = ical_parseCountToUntil($starts_unixtime, $rule['count'], $rule['type'], $rule['repeat'], $rule['extra']);
            }

            $return['repetitions'][] = $rule;
        }
    }
    // END:RRULE

    //
    // Examination of ATTENDEE: properties
    //
    preg_match_all('!^ATTENDEE(.+)$!mi', $event[1], $attendees, PREG_PATTERN_ORDER);
    if (isset($attendees[1]) && !empty($attendees[1])) {
        $return['attendees'] = array();
        foreach ($attendees[1] as $raw_attendee) {
            $attendee = array('name' => '', 'email' => '', 'role' => 'opt', 'type' => 'person', 'status' => 0, 'invited' => null);
            if (preg_match('!\;CN\=(.+)(\;|\:|$)!U', $raw_attendee, $found)) {
                $attendee['name'] = $found[1];
            }
            if (preg_match('!(\;|\:)MAILTO\:(.+)$!', $raw_attendee, $found)) {
                $attendee['email'] = $found[2];
            }
            if (preg_match('!\;PARTSTAT=(ACCEPTED|DECLINED|TENTATIVE)!i', $raw_attendee, $found)) {
                $found[1] = strtolower($found[1]);
                if ($found[1] == 'accepted') {
                    $attendee['status'] = 1;
                }
                if ($found[1] == 'declined') {
                    $attendee['status'] = 2;
                }
                if ($found[1] == 'tentative') {
                    $attendee['status'] = 3;
                }
            }
            if (preg_match('!\;ROLE=(CHAIR|REQ-PARTICIPANT|OPT-PARTICIPANT|NON-PARTICIPANT)!i', $raw_attendee, $found)) {
                $found[1] = strtolower($found[1]);
                if ($found[1] == 'chair') {
                    $attendee['role'] = 'chair';
                }
                if ($found[1] == 'req-participant') {
                    $attendee['role'] = 'req';
                }
                if ($found[1] == 'opt-participant') {
                    $attendee['role'] = 'opt';
                }
                if ($found[1] == 'non-participant') {
                    $attendee['role'] = 'non';
                }
            }
            if (preg_match('!\;CUTYPE=(INDIVIDUAL|GROUP|RESOURCE|ROOM|UNKNOWN)!i', $raw_attendee, $found)) {
                $found[1] = strtolower($found[1]);
                if ($found[1] == 'individual') {
                    $attendee['type'] = 'person';
                }
                if ($found[1] == 'group') {
                    $attendee['type'] = 'group';
                }
                if ($found[1] == 'resource') {
                    $attendee['type'] = 'resource';
                }
                if ($found[1] == 'room') {
                    $attendee['type'] = 'room';
                }
                if ($found[1] == 'unknown') {
                    $attendee['type'] = 'unknown';
                }
            }
            $return['attendees'][] = $attendee;
        }
    }

    // END:ATTENDEE
    // END:VEVENT

    // Parse VALARM
    if (!isset($alarm[1])) {
        return $return; // No alarm info found
    }
    $return['reminders'] = array();
    foreach ($alarm[1] as $raw_reminder) {
        $reminder = array('trigger_value' => '', 'mode' => '-', 'time' => 0);
        foreach (array(
                array('!^ACTION:(.+)$!mi', 'action', 1),
                array('!^SUMMARY:(.+)$!mi', 'text', 1),
                array('!^TRIGGER(;VALUE\=DURATION|VALUE\=DATE\-TIME)?:(.+)$!mi', 'trigger', 2),
                array('!^TRIGGER;RELATED\=END:(.+)$!mi', 'trigger_end', 1),
                array('!^TRIGGER;RELATED\=START:(.+)$!mi', 'trigger_start', 1),
                array('!^ATTENDEE(;.+)?:MAILTO:(.+)$!mi', 'email', 2)
                ) as $needle) {
            if (preg_match($needle[0], $raw_reminder, $found)) {
                $reminder[$needle[1]] = trim($found[$needle[2]]);
                if ($needle[1] == 'trigger') {
                    $reminder['trigger_value'] = $found[1];
                }
            } else {
                $reminder[$needle[1]] = false;
            }
        }
        // We don't care much about the action types, since only the "EMAIL" one is useful to us
        // The rest of the action types collide somewhat with the logic of phlyMail's calendar
        if ($reminder['email'] && strtolower($reminder['action']) == 'email') {
            $reminder['mailto'] = $reminder['email'];
        }
        if ($reminder['trigger'] && strtoupper($reminder['trigger_value']) == ';VALUE=DURATION') {
            $reminder['trigger_start'] = $reminder['trigger'];
            $reminder['trigger'] = false;
        }

        // Look more closely at the trigger thingy
        if ($reminder['trigger'] && strtoupper($reminder['trigger_value']) == ';VALUE=DATE-TIME') {
            $reminder['time'] = strtotime($reminder['trigger']);
            if ($reminder['time'] <= $starts_unixtime) {
                $reminder['mode'] = 's';
                $reminder['time'] = $starts_unixtime - $reminder['time'];
            } else {
                $reminder['mode'] = 'e';
                $reminder['time'] = $ends_unixtime - $reminder['time'];
            }
        } elseif ($reminder['trigger_end'] || $reminder['trigger_start']) {
            $offset = 0;
            $examine = ($reminder['trigger_end']) ? $reminder['trigger_end'] : $reminder['trigger_start'];
            if (preg_match('!(\d+)W!', $examine, $found)) {
                $offset += $found[1] * 604800;
            }
            if (preg_match('!(\d+)D!', $examine, $found)) {
                $offset += $found[1] * 86400;
            }
            if (preg_match('!(\d+)H!', $examine, $found)) {
                $offset += $found[1] * 3600;
            }
            if (preg_match('!(\d+)M!', $examine, $found)) {
                $offset += $found[1] * 60;
            }
            if (preg_match('!(\d+)S!', $examine, $found)) {
                $offset += $found[1] * 1;
            }
            $offset = (substr($examine, 0, 1) == '-') ? $offset*-1 : $offset;
            $reminder['mode'] = ($reminder['trigger_end']) ? 'e' : 's';
            $reminder['time'] = $offset;
        }
        $return['reminders'][] = $reminder;
    }
    // END:VALARM
    return $return;
}

/**
 * Escapes UTF-8 textual content like descriptions (summaries), which might contain
 * newlines and stuff into an escaped form according to RFC 2445 so it can be safely
 * put into an ICS file.
 *
 * @param string $text
 * @return string
 * @since 4.0.1
 */
function ical_escapetext($text)
{
    return addcslashes($text, ',;\\');
}

function ical_foldline($text)
{
    return basics::chunkSplitUnicode(str_replace(array(CRLF, LF), array('\n', '\n'), rtrim($text, "\r\n")), 74, CRLF." ");
}

/**
 * Since PHP's date function cannot handle timestamps before 1970, we have to use
 * MySQL's native DATETIME format and convert it to the format used by RFC 2445.
 *
 * @param string $date
 * @return string
 * @since 4.0.1
 */
function icalDT_fr_mysqlDT($date)
{
    return date(DATE_ICAL_LOCAL, strtotime($date));
}

function icalConvertLocalToUTC($local, $tz)
{
    $date = new DateTime($local, new DateTimeZone($tz));
    $date->setTimezone(new DateTimeZone('UTC'));
    return $date->format(DATE_ICAL_UTC);
}

/**
 * Takes a comma separated list of week day abbr. and returns a bit field
 *
 * @param string $str
 * @return int
 * @since 4.0.3
 */
function ical_parseByDay($str)
{
    $return = 0;
    $days = array_flip(explode(',', strtoupper(trim($str))));

    if (isset($days['SU'])) {
        $return +=  1;
    }
    if (isset($days['SA'])) {
        $return +=  2;
    }
    if (isset($days['FR'])) {
        $return +=  4;
    }
    if (isset($days['TH'])) {
        $return +=  8;
    }
    if (isset($days['WE'])) {
        $return += 16;
    }
    if (isset($days['TU'])) {
        $return += 32;
    }
    if (isset($days['MO'])) {
        $return += 64;
    }

    return $return;
}

function ical_parseCountToUntil($start, $count, $type, $repeat, $extra)
{
    $ref_date = $start;
    if ($type == 'day' && $repeat == 127) {
        $repeat = 0;
    }
    if (empty($repeat) && empty($extra)) {
        $ref_date = strtotime('+'.$count.' '.$type, $ref_date);
        return date('Y-m-d H:i:s', $ref_date);
    }
    if ($type == 'month') {
        $i = 1; // Starting with 1 instead of 0 because the original event at starting date is one occurence
        $occurences = explode(',', $extra);
        while (true) {
            foreach ($occurences as $mday) {
                $i++;
                // Get the ref. date's month and year for calculations
                $year = date('Y', $ref_date);
                $month = date('n', $ref_date);
                // Reentered the loop, next month reached
                if ($mday < date('j', $ref_date)) {
                    $month++;
                    if ($month == 13) { // Even wrapped around a year
                        $year++;
                        $month = 1;
                    }
                }
                $ref_date = strtotime($year.'-'.$month.'-'.$mday);
                if ($i >= $count) {
                    break 2; // Break out of both loops, we are done
                }
            }
        }
    }
    if ($type == 'day') {
        $wdayToIcal = array(0 => 'Monday', 1 => 'Tuesday', 2 => 'Wedndesday', 3 => 'Thursday', 4 => 'Friday', 5 => 'Saturday', 6 => 'Sunday');
        $days = array();
        if ($repeat['repeat'] & 64) {
            $days[] = $wdayToIcal[0];
        }
        if ($repeat['repeat'] & 32) {
            $days[] = $wdayToIcal[1];
        }
        if ($repeat['repeat'] & 16) {
            $days[] = $wdayToIcal[2];
        }
        if ($repeat['repeat'] &  8) {
            $days[] = $wdayToIcal[3];
        }
        if ($repeat['repeat'] &  4) {
            $days[] = $wdayToIcal[4];
        }
        if ($repeat['repeat'] &  2) {
            $days[] = $wdayToIcal[5];
        }
        if ($repeat['repeat'] &  1) {
            $days[] = $wdayToIcal[6];
        }
        $i = 1; // Starting with 1 instead of 0 because the original event at starting date is one occurence
        while (true) {
            foreach ($days as $day) {
                $i++;
                $ref_date = strtotime('next '.$day, $ref_date);
                if ($i >= $count) {
                    break 2; // Break out of both loops, we are done
                }
            }
        }
    }
    return date('Y-m-d H:i:s', $ref_date);
}

/**
 * Output a given event / todo
 * This function uses echo; to capture the output, use ob_start()
 *
 * @param array $data  Event or task data from phlyMail's calendar DB
 * @param string $type  Specify the type of the raw data for correct parsing (VEVENT|VTODO)
 * @param array $eventTypes  event types list from DB
 * @param array $eventStatus  event status list from DB
 * @return void
 * @since 4.0.6
 */
function ical_echoEvent($data, $type = 'VEVENT', $eventTypes = array(), $eventStatus = array(), $tzid = 'UTC')
{
    if ($type != 'VEVENT' && $type != 'VTODO') {
        return false;
    }
    // $serverID = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'phlymail.local');
    $wdayToIcal = array(0 => 'MO', 1 => 'TU', 2 => 'WE', 3 => 'TH', 4 => 'FR', 5 => 'SA', 6 => 'SU');

    // These might confuse aggressive iCal parsers, so skip them in VEVENTs
    if ($type == 'VEVENT') {
        if ($data['starts'] == '0000-00-00 00:00:00') {
            return;
        }
        if ($data['ends'] == '0000-00-00 00:00:00') {
            $data['ends'] = $data['starts'];
        }
    }
    if ($type == 'VTODO') {
        if ($data['starts'] == '0000-00-00 00:00:00') {
            $data['starts'] = false;
        }
        if ($data['ends'] == '0000-00-00 00:00:00') {
            $data['ends'] = false;
        }
    }

    echo 'BEGIN:'.$type.CRLF;
    // Event payload
    echo 'UID:'.$data['uuid'].CRLF;
    echo rtrim(ical_foldline('LOCATION:'.ical_escapetext($data['location']))).CRLF;
    echo rtrim(ical_foldline('SUMMARY:'.ical_escapetext($data['title']))).CRLF;
    echo rtrim(ical_foldline('DESCRIPTION:'.ical_escapetext($data['description']))).CRLF;
    if ($data['starts']) {
        echo 'DTSTART:'.icalConvertLocalToUTC($data['starts'], $tzid).CRLF;
    }
    if ($data['ends']) {
        if ($type == 'VEVENT') {
            echo 'DTEND:'.icalConvertLocalToUTC($data['ends'], $tzid).CRLF;
        } elseif ($type == 'VTODO') {
            echo 'DUE:'.icalConvertLocalToUTC($data['ends'], $tzid).CRLF;
        }
    }
    if ($data['type']) {
        if (!isset($eventTypes[$data['type']])) {
            $data['type'] = 0;
        }
        echo 'CATEGORIES:'.strtoupper($eventTypes[$data['type']]).CRLF;
    }
    if ($data['status']) {
        if (!isset($eventStatus[$data['status']])) {
            $data['status'] = 0;
        }
        echo 'STATUS:'.strtoupper($eventStatus[$data['status']]).CRLF;
    }
    if (isset($data['opaque'])) {
        echo 'TRANSP:'.($data['opaque'] == 0 ? 'TRANSPARENT' : 'OPAQUE').CRLF;
    }
    if ($type == 'VTODO' && isset($data['completion'])) {
        echo 'PERCENT-COMPLETE:'.intval($data['completion']).CRLF;
    }
    if ($type == 'VTODO' && isset($data['completed_on'])) {
        echo 'COMPLETE:'.icalConvertLocalToUTC($data['completed_on'], $tzid).CRLF;
    }
    if ($type == 'VTODO' && isset($data['importance'])) {
        echo 'PRIORITY:'.intval($data['importance']).CRLF;
    }
    if (isset($GLOBALS['PHM_CAL_EX_ORGANIZER']) && $GLOBALS['PHM_CAL_EX_ORGANIZER']) {
        echo 'ORGANIZER:MAILTO:'.$GLOBALS['PHM_CAL_EX_ORGANIZER'].CRLF;
    }
    if (!empty($data['attendees']) && !isset($GLOBALS['PHM_CAL_EX_NOATTENDEES'])) {
        foreach ($data['attendees'] as $atte) {
            echo 'ATTENDEE';
            if ($atte['status'] == 1) {
                echo ';PARTSTAT=ACCEPTED';
            } elseif ($atte['status'] == 2) {
                echo ';PARTSTAT=DECLINED';
            } elseif ($atte['status'] == 3) {
                echo ';PARTSTAT=TENTATIVE';
            } else {
                echo ';PARTSTAT=NEEDS-ACTION';
            }
            if ($atte['role'] == 'chair') {
                echo ';ROLE=CHAIR';
            } elseif ($atte['role'] == 'req') {
                echo ';ROLE=REQ-PARTICIPANT';
            } elseif ($atte['role'] == 'opt') {
                echo ';ROLE=OPT-PARTICIPANT';
            } elseif ($atte['role'] == 'non') {
                echo ';ROLE=NON-PARTICIPANT';
            }
            if ($atte['type'] == 'person') {
                echo ';CUTYPE=INDIVIDUAL';
            } elseif ($atte['type'] == 'group') {
                echo ';CUTYPE=GROUP';
            } elseif ($atte['type'] == 'resource') {
                echo ';CUTYPE=RESOURCE';
            } elseif ($atte['type'] == 'room') {
                echo ';CUTYPE=ROOM';
            } elseif ($atte['type'] == 'unknown') {
                echo ';CUTYPE=UNKNOWN';
            }
            echo ';RSVP='.(!is_null($atte['invited']) ? 'TRUE' : 'FALSE');
            if ($atte['name']) {
                echo ';CN='.ical_escapetext($atte['name']);
            }
            echo ':MAILTO:'.($atte['email'] ? $atte['email'] : 'none').CRLF;
        }
    }

    if (!empty($data['repetitions'])) {
        foreach ($data['repetitions'] as $rep) {
            if ($rep['type'] == '-') {
                continue;
            }
            $repUntil = !empty($rep['until_unix']) ? ';UNTIL='.date(DATE_ICAL_UTC, $rep['until_unix']) : '';
            if ($rep['type'] == 'year') {
                echo 'RRULE:FREQ=YEARLY'.$repUntil.CRLF;
            }
            if ($rep['type'] == 'month') {
                echo 'RRULE:FREQ=MONTHLY';
                if (strlen($rep['extra'])) {
                    echo ';BYMONTH='.rtrim($rep['extra']); // Is comma separated list anyway
                }
                echo ';BYMONTHDAY='.$rep['repeat'].$repUntil.CRLF;
            }
            if ($rep['type'] == 'week') {
                echo 'RRULE:FREQ=WEEKLY;BYDAY='.$wdayToIcal[$rep['repeat']].$repUntil.CRLF;
            }
            if ($rep['type'] == 'day') {
                $days = array();
                if (empty($rep['repeat']) || $rep['repeat'] & 64) {
                    $days[] = $wdayToIcal[0];
                }
                if (empty($rep['repeat']) || $rep['repeat'] & 32) {
                    $days[] = $wdayToIcal[1];
                }
                if (empty($rep['repeat']) || $rep['repeat'] & 16) {
                    $days[] = $wdayToIcal[2];
                }
                if (empty($rep['repeat']) || $rep['repeat'] &  8) {
                    $days[] = $wdayToIcal[3];
                }
                if (empty($rep['repeat']) || $rep['repeat'] &  4) {
                    $days[] = $wdayToIcal[4];
                }
                if (empty($rep['repeat']) || $rep['repeat'] &  2) {
                    $days[] = $wdayToIcal[5];
                }
                if (empty($rep['repeat']) || $rep['repeat'] &  1) {
                    $days[] = $wdayToIcal[6];
                }
                echo 'RRULE:FREQ=DAILY;BYDAY='.implode(',', $days).$repUntil.CRLF;
            }
        }
    }
    if (!empty($data['reminders'])) {
        foreach ($data['reminders'] as $rem) {
            if ($rem['mode'] == '-') {
                continue;
            }
            echo 'BEGIN:VALARM'.CRLF;
            echo 'TRIGGER;RELATED='.($rem['mode'] == 'e' ? 'END' : 'START').':';
            if ($rem['time'] >= 604800 && (intval($rem['time'] / 604800) == $rem['time'] / 604800)) {
                echo '-P'.($rem['time'] / 604800).'W';
            } elseif ($rem['time'] >= 86400 && (intval($rem['time'] / 86400) == $rem['time'] / 86400)) {
                echo '-P'.($rem['time'] / 86400).'D';
            } elseif ($rem['time'] >= 3600 && (intval($rem['time'] / 3600) == $rem['time'] / 3600)) {
                echo '-PT'.($rem['time'] / 3600).'H';
            } elseif ($rem['time'] >= 60 && (intval($rem['time'] / 60) == $rem['time'] / 60)) {
                echo '-PT'.($rem['time'] / 60).'M';
            } else {
                echo 'PT'.intval($rem['time']).'S';
            }
            echo CRLF;
            if ($rem['mailto'] && !isset($GLOBALS['PHM_CAL_EX_NOATTENDEES'])) {
                echo 'ACTION:EMAIL'.CRLF.'ATTENDEE:MAILTO:'.$rem['mailto'].CRLF;
            } else {
                echo 'ACTION:DISPLAY'.CRLF;
            }
            if ($rem['text']) {
                echo ical_foldline('SUMMARY:'.ical_escapetext($rem['text'])).CRLF;
            } elseif ($data['title']) {
                echo ical_foldline('SUMMARY:'.ical_escapetext($data['title'])).CRLF;
            } elseif ($data['description']) {
                echo ical_foldline('DESCRIPTION:'.ical_escapetext($data['description'])).CRLF;
            } else {
                echo 'DESCRIPTION:Reminder'.CRLF;
            }
            echo 'END:VALARM'.CRLF;
        }
    }
    // Mandatory appendix
    echo 'END:'.$type.CRLF;
}

function ical_echoOffset($offset)
{
    $return = ($offset < 0) ? '-' : '+';
    $offset = abs($offset/3600); // Convert seconds to hours;, ignore negativity
    $return .= sprintf('%02d', intval($offset));  // First the hour part
    $return .= sprintf('%02d', intval(($offset-intval($offset))*60));  // and the minutes;
    return $return;
}

function externalCalendarRead($calendar, &$errno, &$errstr)
{
    // First we let PHP try it
    // works only for plain URIs without AUTH or local files
    if (substr($calendar['uri'], 0, 7) == 'file://') {
        $fh = fopen($calendar['uri'], 'r');
        if (false === $fh) {
            // File not found / accessible; Cannot parse it
            $errno = 1404;
            $errstr = 'Reading given path failed';
            return false;
        }
        $file = stream_get_contents($fh);
        fclose($fh);
    } else { // HTTP requests
        $req = parse_url($calendar['uri']);
        if (strtolower(substr($calendar['uri'], 0, 5)) == 'https') {
            $req['ssl'] = true;
        }
        if (!empty($calendar['ext_un'])) {
            $req['auth_user'] = deconfuse($calendar['ext_un'], md5($calendar['uri']));
        }
        if (!empty($calendar['ext_pw'])) {
            $req['auth_pass'] = deconfuse($calendar['ext_pw'], md5($calendar['uri']));
        }
        $httpClient = new Protocol_Client_HTTP();
        $file = $httpClient->send_request($req);
        if (false === $file) {
            $errno = $httpClient->getErrorNo();
            $errstr = $httpClient->getErrorString();
            return false;
        }
    }
    return $file;
}

function externalCalendarWrite($calendar, $data, &$errno, &$errstr)
{
    if (substr($calendar['uri'], 0, 7) == 'file://') {
        $fh = fopen($calendar['uri'], 'w');
        if (false === $fh) {
            // File not found / accessible; Cannot parse it
            $errno = 1404;
            $errstr = 'Writing given path failed';
            return false;
        }
        fwrite($fh, $data);
        fclose($fh);
        return true;
    } else {
        $req = parse_url($calendar['uri']);
        if (strtolower(substr($calendar['uri'], 0, 5)) == 'https') {
            $req['ssl'] = true;
        }
        if (!empty($calendar['ext_un'])) {
            $req['auth_user'] = deconfuse($calendar['ext_un'], md5($calendar['uri']));
        }
        if (!empty($calendar['ext_pw'])) {
            $req['auth_pass'] = deconfuse($calendar['ext_pw'], md5($calendar['uri']));
        }
        $req['method'] = 'PUT';
        $req['query'] = !empty($req['query']) ? $req['query'].'&'.$data : $data;
        $httpClient = new Protocol_Client_HTTP();
        $response = $httpClient->send_request($req);
        if (false === $response) {
            $errno = $httpClient->getErrorNo();
            $errstr = $httpClient->getErrorString();
            return false;
        }
        return true;
    }
}
