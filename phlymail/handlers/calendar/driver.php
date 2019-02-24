<?php
/**
 * Database driver for the calendar handler
 * Provides storage functions for use with a mySQL database
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Handler Calendar
 * @author  Matthias Sommerfeld
 * @copyright 2004-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 4.5.3 2015-04-01
 */
class handler_calendar_driver extends DB_Controller
{
    protected $error, $uid, $principalId;
    protected $queryType = 'default'; // @see $this->setQueryType()
    protected $allShares = array();

    // Constructor
    // Read the config and open the DB
    public function __construct($uid = 0, $principalId = 0)
    {
        $this->uid = intval($uid);
        $this->principalId = intval($principalId);

        parent::__construct();

        $this->Tbl['cal_event'] = $this->DB['db_pref'].'calendar_events';
        $this->Tbl['cal_task']  = $this->DB['db_pref'].'calendar_tasks';
        $this->Tbl['cal_holiday'] = $this->DB['db_pref'].'calendar_holidays';
        $this->Tbl['cal_group'] = $this->DB['db_pref'].'calendar_groups';
        $this->Tbl['cal_project'] = $this->DB['db_pref'].'calendar_projects';
        $this->Tbl['cal_attach'] = $this->DB['db_pref'].'calendar_event_attachments';
        $this->Tbl['cal_attendee'] = $this->DB['db_pref'].'calendar_event_attendees';
        $this->Tbl['cal_reminder'] = $this->DB['db_pref'].'calendar_event_reminders';
        $this->Tbl['cal_repetition'] = $this->DB['db_pref'].'calendar_event_repetitions';
        $this->Tbl['user'] = $this->DB['db_pref'].'user';
        $this->Tbl['user_foldersettings'] = $this->DB['db_pref'].'user_foldersettings';

        $this->DB['ServerVersionString'] = $this->serverinfo();
        $this->DB['ServerVersionNum'] = preg_replace('![^0-9\.]!', '', $this->DB['ServerVersionString']);

        $this->query('SET SESSION sql_mode="ALLOW_INVALID_DATES"');

        try {
            $dbSh = new DB_Controller_Share();
            $allShares = $dbSh->getFolderList($this->uid, 'calendar');
            $this->allShares = (!empty($allShares[$this->uid]['calendar'])) ? $allShares[$this->uid]['calendar'] : array();
        } catch (Exception $e) {
            $this->allShares = array();
        }
    }

    public function changeUser($uid = 0)
    {
        $this->uid = doubleval($uid);
    }

    public function setQueryType($type)
    {
        if (!in_array($type, array('default', 'sync', 'root'), true)) {
            $this->set_error('Illegal query type');
            return false;
        }
        $this->queryType = $type;
        return true;
    }

    private function set_error($error)
    {
        if (isset($this->append_errors) && $this->append_errors) {
            $this->error[] = $error;
        } else {
            $this->error[0] = $error;
        }
    }

    public function get_errors($nl = "\n")
    {
        $error = implode($nl, $this->error);
        if (!isset($this->retain_errors) || !$this->retain_errors) {
            $this->error = array();
        }
        return $error;
    }

    /**
     * Install the required table(s) for the handler
     * @param  void
     * @return  boolean  return value of the MySQL query
     * @since 0.1.3
     */
    public function handler_install()
    {
        return true; // Handler always there
    }

    /**
     * Uninstall the required table(s) of the handler
     * @param  void
     * @return  boolean  return value of the MySQL query
     * @since 0.1.3
     */
    public function handler_uninstall()
    {
        return true; // Handler always there
    }

    /**
     * Return a list of event types
     *
     * @return array
     */
    public function get_event_types()
    {
        return array(0 => 'miscellaneous', 1 => 'appointment', 2 => 'holiday'
                ,3 => 'birthday', 4 => 'personal', 5 => 'education'
                ,6 => 'travel', 7 => 'anniversary', 8 => 'not in office'
                ,9 => 'sick day', 10 => 'meeting', 11 => 'vacation'
                ,12 => 'phone call', 13 => 'business'
                ,14 => 'non-working hours', 50 => 'special occasion'
                );
    }

    /**
     * Return a list of event status types
     *
     * @return array
     */
    public function get_event_status()
    {
        return array(0 => 'undefined'//, 1 => 'due for approval'
                ,2 => 'confirmed', 3 => 'cancelled'//, 4 => 'delegated'
                ,10 => 'tentative', 11 => 'needs-action'
                );
    }

    public function get_task_status()
    {
        return array(0 => 'undefined'//, 1 => 'due for approval'
                ,2 => 'confirmed', 3 => 'cancelled'//, 4 => 'delegated'
                ,5 => 'in-process', 6 => 'completed'
                ,10 => 'tentative', 11 => 'needs-action'
                );
    }

    /**
     * Return a list of task importances
     *
     * @return array
     */
    public function get_task_importance()
    {
        return array(0 => 'undefined', 1 => 'A1 / very high', 2 => 'A2'
                ,3 => 'A3 / high', 4 => 'B1', 5 => 'B2 / normal'
                ,6 => 'B3', 7 => 'C1 / low', 8 => 'C2', 9 => 'C3 / very low'
                );
    }

    public function rootIsShared()
    {
        return !empty($this->allShares[0]);
    }

    /**
     * Shorthand for optionally including the event list filter depending on query type property
     * @param int $gid Group ID (a.k.a. calendar / folder ID)
     * @return string
     */
    protected function getQueryTypeFilter($gid = 0, $pref = 'e')
    {
        $pref = $this->esc($pref);
        if ($gid == 0 && $this->queryType != 'default') {
            $field = $this->queryType == 'sync' ? 'not_in_sync' : 'not_in_root';
            return array
                    (' LEFT JOIN '.$this->Tbl['user_foldersettings'].' uf ON '.$pref.'.gid!=0'
                            .' AND '.$pref.'.gid=uf.fid AND uf.uid='.$this->uid.' AND uf.`handler`="calendar" AND uf.`key`="'.$field.'"'
                    ,' AND ('.$pref.'.gid=0 OR uf.`val`="0" OR uf.`val` IS NULL)'.($this->queryType == 'sync' ? '`archived`="0"' : '')
                    );
        }
        return array('', '');
    }

    protected function getGroupAndShareFilter($gid = 0, $pref = 'e', $withUser = true)
    {
        global $DB;

        if (empty($this->uid)) {
            return '1';
        }
        if (!empty($gid)) {
            if ($this->getGroupOwner($gid) == $this->uid) {
                $sharePerms['read'] = true;
            } elseif (empty($DB->features['shares'])) {
                $sharePerms['read'] = false;
            } else {
                $sharePerms = $DB->getUserSharedFolderPermissions($this->uid, 'calendar', $gid);
            }
            if (empty($sharePerms) || empty($sharePerms['read'])) {
                return '1=2';
            }
            return $pref.'.gid='.intval($gid);
        } else {
            $return = array();
            if ($withUser) {
                $return[] = $pref.'.`uid`='.$this->uid;
            }
            $qid = $this->query('SELECT `gid` FROM '.$this->Tbl['cal_group'].' WHERE `owner`='.(!empty($this->principalId) ? $this->principalId : $this->uid));
            if ($this->numrows($qid)) {
                $gidList = array();
                while ($line = $this->assoc($qid)) {
                    $gidList[] = $line['gid'];
                }
                $return[] = $pref.'.gid IN('.implode(',', $gidList).')';
            }

            if (!empty($DB->features['groups'])
                    && !empty($DB->features['shares'])) {
                $myGroups = $DB->get_usergrouplist($this->uid, true);
                $DCS = new DB_Controller_Share();
                $groups = $DCS->getMySharedFolders($this->uid, 'calendar', $myGroups);
                if (!empty($groups['calendar'])) {
                    $return[] = $pref.'.gid IN('.implode(',', basics::intify(array_keys($groups['calendar']))).')';
                }
            }
            return implode(' OR ', $return);
        }
    }

    public function getGroupOwner($gid)
    {
        $qid = $this->query('SELECT `owner`FROM '.$this->Tbl['cal_group'].' WHERE gid='.intval($gid));
        if (false === $qid) {
            return false;
        }
        list ($gid) = $this->fetchrow($qid);
        return $gid;
    }

    /**
     * Helps the auto completion for titles / locations when editing events / tasks
     *
     * @param string $term  Search term to look for
     * @param string $what  Which field to scan (title|location)
     * @return array
     */
    public function autoCompleteHelper($term, $what)
    {
        $term = $this->esc($term);
        $what = $this->esc($what);
        $return = array();
        foreach (array('tasks', 'events') as $where) {
            $qid = $this->query('SELECT `'.$what.'` FROM '
                    .($where == 'tasks' ? $this->Tbl['cal_task'] : $this->Tbl['cal_event'])
                    .' WHERE uid='.$this->uid.' AND `'.$what.'` LIKE "%'.$term.'%" GROUP BY `'.$what.'`');
            while ($line = $this->assoc($qid)) {
                $return[] = $line[$what];
            }
        }
        return array_values(array_unique($return, SORT_STRING));
    }

    /**
     * Takes a reminder ID and returns the item associated with it.
     * Right now this can be either an event or a task.
     *
     * @param int $rem  ID of the reminder
     * @return false|array  ID and type of the found item; FALSE on fialre
     */
    public function get_item_by_reminder($rem)
    {
        $qid = $this->query('SELECT `eid`,`ref` FROM '.$this->Tbl['cal_reminder'].' WHERE id='.doubleval($rem));
        list ($id, $ref) = $this->fetchrow($qid);
        if (!$id) {
            return false; // Not found
        }
        return array($id, $ref);
    }

    /**
     * Returns true, if a given date has events, false, if not
     * @param string|array $date MySQL date(s); Pass a string with a given date, an array for a date range
     *[@param int  $gid  ID of the group the events shall be in]
     * @return boolean TRUE if events are scheduled for that date, FALSE if not
     * @since 0.0.1
     */
    public function date_has_events($date, $gid = 0)
    {
        if (is_array($date)) {
            $from = $this->esc($date[0]);
            $to = $this->esc($date[1]);
        } else {
            $date = $from = $to = $this->esc($date);
        }

        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = $this->getQueryTypeFilter($gid);
        $gidFilter = $this->getGroupAndShareFilter($gid);

        $query = 'SELECT 1 FROM '.$this->Tbl['cal_repetition'].' rp, '.$this->Tbl['cal_event'].' e'
                .$eventListFilter[0]
                .' WHERE rp.`eid`=e.`id` AND rp.`ref`="evt" AND ('.$gidFilter.')'.$eventListFilter[1]
                .' AND IF (rp.`type`!="-", DATE_FORMAT(e.`starts`, "%Y%m%d") <= DATE_FORMAT("'.$date.'", "%Y%m%d"), 1)'
                .' AND IF (rp.`type`!="-" AND rp.`until` IS NOT NULL AND rp.`until` != "0-0-0 0:0:0", rp.`until`>"'.$date.'", 1) AND ('
                // Begins or ends today
                .'DATE_FORMAT(starts, "%Y%m%d") = DATE_FORMAT("'.$date.'", "%Y%m%d") OR DATE_FORMAT(e.ends, "%Y%m%d") = DATE_FORMAT("'.$date.'", "%Y%m%d") OR '
                // Begins in the past AND ends in the future
                .'(DATE_FORMAT(starts, "%Y%m%d") <= DATE_FORMAT("'.$date.'", "%Y%m%d") AND DATE_FORMAT(e.ends, "%Y%m%d") >= DATE_FORMAT("'.$date.'", "%Y%m%d")) OR '
                // Is an event occuring yearly. Todays date matches the repetition date
                .'(rp.`type`="year" AND (DATE_FORMAT(e.starts, "%m%d")=DATE_FORMAT("'.$date.'", "%m%d") OR DATE_FORMAT(e.ends, "%m%d")=DATE_FORMAT("'.$date.'", "%m%d") ) ) OR '
                // A monthly event, repetition day is today
                .'(rp.`type`="month" AND rp.`repeat` = DATE_FORMAT("'.$date.'", "%e") AND (rp.`extra`="" OR FIND_IN_SET(DATE_FORMAT("'.$date.'", "%c"), rp.`extra`)>0) ) OR '
                // Monthly event on e.g. the 31st of month with months shorter than 31 days, this is only supported from MySQL 4.1.1 onward
                .'(rp.`type`="month" AND rp.`repeat`=31 AND (rp.`extra`="" OR FIND_IN_SET(DATE_FORMAT("'.$date.'", "%c"), rp.`extra`)>0) AND LAST_DAY("'.$date.'")=DATE_FORMAT("'.$date.'", "%Y-%m-%d") ) OR '
                // A weekly event, repetition weekday is today
                .'(rp.`type`="week" AND rp.`repeat`=DATE_FORMAT("'.$date.'", "%w") AND IF(rp.`extra` IN("", "1"), 1, ABS(MOD(DATEDIFF(e.`starts`, "'.$date.'")/7, rp.`extra`))=0) ) OR '
                // A "daily" event, where the bit pattern should match today's weekday
                .'(rp.`type`="day" AND (rp.`repeat`="0" OR SUBSTRING(LPAD(BIN(rp.`repeat`),8,0), IF(DATE_FORMAT("'.$date.'", "%w")=0,8,DATE_FORMAT("'.$date.'", "%w")+1), 1) = 1 ) )'
                .') LIMIT 1';
        list ($true) = $this->fetchrow($this->query($query));
        return (bool) $true;
    }

    /**
     * Return a list of scheduled events for a given date
     *
     * Although advertised here, the method indeed does not use an array of dates.
     *
     * @param string|array $date MySQL date(s); Pass a string with a given date, an array for a date range
     *[@param int  $gid  ID of the group the events shall be in]
     * @return array Events with start time, end time, description
     * @since 0.0.1
     */
    public function date_get_eventlist($date, $gid = 0)
    {
        if (is_array($date)) {
            $from = $this->esc($date[0]);
            $to = $this->esc($date[1]);
        } else {
            $date = $from = $to = $this->esc($date);
        }
        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = $this->getQueryTypeFilter($gid);
        $gidFilter = $this->getGroupAndShareFilter($gid);

        $return = array();
        $query = 'SELECT DISTINCT e.`id` '
                .', IF(rp.`type`!="-", UNIX_TIMESTAMP(CONCAT(DATE_FORMAT("'.$date.'", "%Y-%m-%d"), " ",DATE_FORMAT(e.`starts`, "%T")) ), UNIX_TIMESTAMP(e.`starts`) ) as start'
                .', IF(rp.`type`!="-", UNIX_TIMESTAMP(CONCAT(DATE_FORMAT("'.$date.'", "%Y-%m-%d"), " ",DATE_FORMAT(e.`ends`, "%T")) ), UNIX_TIMESTAMP(e.`ends`) ) as end'
                .',e.`starts`, e.`ends`, e.`location`, e.`title`, e.`description`, e.`type`, e.`status`, e.`opaque`, rp.`type` `repeat_type`, rp.`until` `repeat_until`'
                .',IF(fs.`val` IS NULL, "", fs.`val`) `colour`'
                .', (SELECT `mode` FROM '.$this->Tbl['cal_reminder'].' WHERE `uid`='.$this->uid.' AND `eid`=e.`id` AND `ref`="evt" LIMIT 1) `warn_mode`'
                .' FROM '.$this->Tbl['cal_repetition'].' rp, '.$this->Tbl['cal_event'].' e'
                .' LEFT JOIN '.$this->Tbl['cal_group'].' eg ON eg.`gid`=e.`gid`'
                .' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=e.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid
                .$eventListFilter[0]
                .' WHERE rp.`eid`=e.`id` AND rp.`ref`="evt" AND ('.$gidFilter.')'.$eventListFilter[1]
                .' AND IF (rp.`type`!="-", DATE_FORMAT(e.`starts`, "%Y%m%d") <= DATE_FORMAT("'.$from.'", "%Y%m%d"), 1)'
                .' AND IF (rp.`type`!="-" AND rp.`until` IS NOT NULL AND rp.`until` != "0-0-0 0:0:0", rp.`until`>"'.$to.'",1) AND ('
                // Begins or ends today
                .'DATE_FORMAT(e.`starts`, "%Y%m%d")=DATE_FORMAT("'.$date.'", "%Y%m%d") OR DATE_FORMAT(e.`ends`, "%Y%m%d")=DATE_FORMAT("'.$date.'", "%Y%m%d") OR '
                // Begins in the past AND ends in the future
                .'( DATE_FORMAT(e.`starts`, "%Y%m%d")<=DATE_FORMAT("'.$date.'", "%Y%m%d") AND DATE_FORMAT(e.`ends`, "%Y%m%d")>=DATE_FORMAT("'.$date.'", "%Y%m%d") ) OR '
                // Is an event occuring yearly. Todays date matches the repetition date
                .'(rp.`type`="year" AND (DATE_FORMAT(e.`starts`,"%m%d")=DATE_FORMAT("'.$date.'", "%m%d") OR DATE_FORMAT(e.`ends`,"%m%d")=DATE_FORMAT("'.$date.'","%m%d"))) OR '
                // A monthly event, repetition day is today, repetition month is empty or matches
                .'(rp.`type`="month" AND rp.`repeat`=DATE_FORMAT("'.$date.'", "%e") AND (rp.`extra`="" OR FIND_IN_SET(DATE_FORMAT("'.$date.'", "%c"), rp.`extra`)>0) ) OR '
                // Monthly event on e.g. the 31st of month with months shorter than 31 days, this is only supported from MySQL 4.1.1 onward
                .'(rp.`type`="month" AND rp.`repeat`=31 AND (rp.`extra`="" OR FIND_IN_SET(DATE_FORMAT("'.$date.'", "%c"), rp.`extra`)>0) AND LAST_DAY("'.$date.'")=DATE_FORMAT("'.$date.'", "%Y-%m-%d"))'
                // A weekly event, repetition weekday is today
                .' OR (rp.`type`="week" AND rp.`repeat`=DATE_FORMAT("'.$date.'", "%w") AND IF(rp.`extra` IN("", "1"), 1, ABS(MOD(DATEDIFF(e.`starts`, "'.$date.'")/7, rp.`extra`))=0) ) OR '
                // A "daily" event, where the bit pattern should match today's weekday
                .'(rp.`type`="day" AND (rp.`repeat`="0" OR SUBSTRING(LPAD(BIN(rp.`repeat`), 8, 0), IF(DATE_FORMAT("'.$date.'", "%w")=0, 8, DATE_FORMAT("'.$date.'", "%w")+1), 1) = 1 ) )'
                .') ORDER BY `start` ASC';
        $qh = $this->query($query);
        while ($line = $this->assoc($qh)) {
            if ($line['warn_mode'] == '?') {
                $qid2 = $this->query('SELECT `mode` FROM '.$this->Tbl['cal_reminder']
                        .' WHERE `uid`='.$this->uid.' AND `eid`='.$line['id'].' AND `ref`="evt" AND `mode` != "-" LIMIT 1');
                list ($rem) = $this->fetchrow($qid2);
                $line['warn_mode'] = $rem ? $rem : '-';
            } elseif ($line['warn_mode'] == '' || is_null($line['warn_mode'])) {
                $line['warn_mode'] = '-';
            }
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Gives the unix timestamp of the next date with an event based on the start timestamp passed
     * @param  int  UNIX timestamp as starting offset
     * @return  int  UNIX timestamp of the next date which has an event defined
     * @since 0.1.1
     */
    public function get_nextday_withevents($basedate, $gid = 0)
    {
        $maxcount = 365; // Prevents too high load on DB
        $mydate = $basedate;
        while ($maxcount) {
            --$maxcount;
            $basedate = strtotime('+1 day', $basedate);
            if ($this->date_has_events(date('Y-m-d', $basedate), $gid)) {
                return $basedate;
            }
        }
        return $mydate;
    }

    /**
     * Gives the unix timestamp of the previous date with an event based on the start timestamp passed
     * @param  int  UNIX timestamp as starting offset
     * @return  int  UNIX timestamp of the previous date which has an event defined
     * @since 0.1.1
     */
    public function get_prevday_withevents($basedate, $gid = 0)
    {
        $maxcount = 365; // Prevents too high load on DB
        $mydate = $basedate;
        while ($maxcount) {
            --$maxcount;
            $basedate = strtotime('-1 day', $basedate);
            if ($this->date_has_events(date('Y-m-d', $basedate), $gid)) {
                return $basedate;
            }
        }
        return $mydate;
    }

    /**
     * Retrieve detailed data about an event
     * @param int ID of the event
     *[@param int ID of a reminder, which identifies an event]
     * @return array Specific data, false on failure / non-existant ID
     * @since 0.0.3
     */
    public function get_event($id, $reminder = null)
    {
        if (!is_null($reminder)) {
            $qid = $this->query('SELECT `eid` FROM '.$this->Tbl['cal_reminder'].' WHERE `ref`="evt" AND id='.doubleval($reminder));
            list($id) = $this->fetchrow($qid);
            if (!$id) {
                return array();
            }
        }
        $id = doubleval($id);

        // Allows to retrieve events from calendars, which are shared with us
        $gidFilter = $this->getGroupAndShareFilter(0);

        $query = 'SELECT e.`id`,e.`id` `eid`,e.uid ,UNIX_TIMESTAMP(e.starts) `start`, UNIX_TIMESTAMP(e.ends) `end`, e.`starts`, e.`ends`'
                .',e.`title`,e.`description`,e.`location`,e.`type`,e.`status`,e.`opaque`,e.`gid`,e.`uuid`, fs.`val` `colour`, eg.`type` `calendar_type`'
                .' FROM '.$this->Tbl['cal_event'].' e'
                .' LEFT JOIN '.$this->Tbl['cal_group'].' eg ON eg.`gid`=e.`gid`'
                .' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=e.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid
                .' WHERE ('.$gidFilter.') AND e.id='.$id;
        $event = $this->assoc($this->query($query));
        $event['reminders'] = array();
        $qid = $this->query('SELECT `id`,`time`,`mode`,`text`,`mailto`,`smsto` FROM '.$this->Tbl['cal_reminder']
                .' WHERE `uid`='.$this->uid.' AND `eid`='.$id.' AND `ref`="evt" ORDER BY `mode` DESC, `time` DESC');
        while ($line = $this->assoc($qid)) {
            $event['reminders'][] = $line;
            if (!is_null($reminder) && $line['id'] == $reminder) {
                $event['reminder_text'] = $line['text'];
            }
        }
        $event['repetitions'] = array();
        $qid = $this->query('SELECT `id`,`type`,`repeat`,`extra`,`until`,IF (`until` IS NOT NULL, unix_timestamp(`until`), 0) `until_unix`'
                .' FROM '.$this->Tbl['cal_repetition'].' WHERE `eid`='.$id.' AND `ref`="evt" ORDER BY `id` ASC');
        while ($line = $this->assoc($qid)) {
            $event['repetitions'][] = $line;
        }
        return $event;
    }

    /**
     * Enter description here...
     *
     *[@param int $gid GID to filter against, 0 for all groups]
     *[@param string $pattern Search pattern to search events for; Default: null]
     * @return int
     * @since 4.2.2
     */
    public function get_eventcount($gid = 0, $pattern = null)
    {
        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = $this->getQueryTypeFilter($gid);
        $gidFilter = $this->getGroupAndShareFilter($gid);

        $query = 'SELECT COUNT(*) anzahl FROM '.$this->Tbl['cal_event'].' e'.$eventListFilter[0].' WHERE ('.$gidFilter.')'.$eventListFilter[1];
        // Do we have a search pattern set?
        if ($pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            // Flatten the field list
            $v = array();
            foreach (array('title', 'location', 'description') as $k) {
                $v[] = 'e.`'.$k.'` LIKE "'.$pattern.'"';
            }
            $query .=' AND ('.implode(' OR ', $v).')';
        }
        $qid = $this->query($query);
        try {
            $line = $this->assoc($qid);
            return ($line['anzahl']) ? $line['anzahl'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Retrieves a list of all events a given user has defined
     *
     *[@param  int  $gid  GID to filter against, 0 for all groups]
     *[@param  bool  $digest   TRUE to only return flags for having repetition / reminder; Default: false]
     *[@param  string  $pattern  Search pattern to search events for; Default: null]
     *[@param  integer $num  Number of entries to return]
     *[@param  integer $start  Start entry]
     *[@param  string  $orderby  Field to order by; Default: starts]
     *[@param  string  ASC|DESC $orderdir  Order direction; Default: ASC]
     *[@param  bool  TRUE|FALSE $archived Is in archive; Default: FALSE]
     * @return  array
     * @since  4.0.3
     */
    public function get_eventlist($gid = 0, $digest = false, $pattern = null, $num = 0, $start = 0, $orderby = 'starts', $orderdir = 'ASC', $archived = false)
    {
        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = ($gid == 'NULL') ? array('', '') : $this->getQueryTypeFilter($gid);
        $gidFilter = $this->getGroupAndShareFilter($gid);
        $return = array();
        $query = 'SELECT e.`id`,UNIX_TIMESTAMP(e.starts) `start`,UNIX_TIMESTAMP(e.ends) `end`,e.`starts`,e.`ends`'
                .',e.`title`,e.`description`,e.`location`,e.`type`,e.`status`,e.`opaque`,e.`gid`,e.`uuid`,UNIX_TIMESTAMP(e.`lastmod`) mtime,fs.`val` `colour`'
                .($digest ? ',COUNT(rep.id) `repetitions`,COUNT(rem.id) `reminders`,COUNT(rem2.id) `reminders_sms`,COUNT(rem3.id) `reminders_email`' : '')
                .' FROM '.$this->Tbl['cal_event'].' e'
                .' LEFT JOIN '.$this->Tbl['cal_group'].' eg ON eg.`gid`=e.`gid`'
                .' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=e.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid
                .$eventListFilter[0]
                .($digest ? ' LEFT JOIN '.$this->Tbl['cal_repetition'].' rep ON rep.`type`!="-" AND rep.`eid`=e.`id` AND rep.`ref`="evt"' : '')
                .($digest ? ' LEFT JOIN '.$this->Tbl['cal_reminder'].' rem ON rem.`eid`=e.`id` AND rem.`ref`="evt"' : '')
                .($digest ? ' LEFT JOIN '.$this->Tbl['cal_reminder'].' rem2 ON rem2.`eid`=e.`id` AND rem2.`ref`="evt" AND rem2.`smsto`!=""' : '')
                .($digest ? ' LEFT JOIN '.$this->Tbl['cal_reminder'].' rem3 ON rem3.`eid`=e.`id` AND rem3.`ref`="evt" AND rem3.`mailto`!=""' : '')
                .' WHERE ('.$gidFilter.')'.$eventListFilter[1].' AND e.`archived`="'.($archived ? 1 : 0).'"';
        if ($gid === 'NULL') {
            $query .= ' AND e.gid=0';
        } elseif ($gid > 0) {
            $query .= ' AND e.gid='.doubleval($gid);
        }

        // Do we have a search pattern set?
        if ($pattern == '@@upcoming@@') { // Special filter for upcoming events (pinboard)
            $query .= ' AND (`starts`>=NOW() OR (`starts`<=NOW() AND `ends`>=NOW()))';
        } elseif ($pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            // Flatten the field list
            $v = array();
            foreach (array('title', 'location', 'description') as $k) { $v[] = 'e.`'.$k.'` LIKE "'.$pattern.'"'; }
            $query .=' AND ('.implode(' OR ', $v).')';
        }
        if ($digest) {
            $query .= ' GROUP BY e.`id`';
        }
        $query .= ' ORDER BY `'.$this->esc($orderby).'` '.($orderdir == 'ASC' ? 'ASC' : 'DESC');
        if ($num > 0) {
            $query .= ' LIMIT '.doubleval($start).', '.doubleval($num);
        }
        $qid = $this->query($query);
        while ($line = $this->assoc($qid)) {
            if (!$digest) {
                $line['reminders'] = array();
                $qid2 = $this->query('SELECT `id`,`time`,`mode`,`text`,`mailto`,`smsto` FROM '.$this->Tbl['cal_reminder']
                        .' WHERE `uid`='.$this->uid.' AND `eid`='.$line['id'].' AND `ref`="evt" ORDER BY `mode` DESC, `time` DESC');
                while ($line2 = $this->assoc($qid2)) { $line['reminders'][] = $line2; }
                $line['repetitions'] = array();
                $qid2 = $this->query('SELECT `id`,`type`,`repeat`,`extra`,`until`, IF (`until` IS NOT NULL, unix_timestamp(`until`), 0) `until_unix`'
                        .' FROM '.$this->Tbl['cal_repetition'].' WHERE `eid`='.$line['id'].' AND `ref`="evt" ORDER BY `id` ASC');
                while ($line2 = $this->assoc($qid2)) { $line['repetitions'][] = $line2; }

                $line['attendees'] = $this->get_event_attendees($line['id']);
            }
            $return[$line['id']] = $line;
        }
        return $return;
    }

    public function getEventUUIDs($gid = 0, $archived = false)
    {
        $return = array();
        $query = 'SELECT `id`, `uuid` FROM '.$this->Tbl['cal_event'].' WHERE `archived`="'.($archived ? 1 : 0).'"';
        if ($gid > 0) {
            $query .= ' AND gid='.doubleval($gid);
        }
        $res = $this->query($query);
        while ($line = $this->assoc($res)) {
            $return[$line['uuid']] = $line[ 'id'];
        }
        return $return;
    }

    /**
     * Add an event to the database
     * @param array Specification for that event
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.2
     */
    public function add_event($data)
    {
        $datafields = array
               ('start' => array('req' => true)
               ,'end' => array('req' => true)
               ,'gid' => array('req' => true)
               ,'title' => array('req' => false, 'def' => '')
               ,'description' => array('req' => false, 'def' => '')
               ,'location' => array('req' => false, 'def' => '')
               ,'type' => array('req' => false, 'def' => 0)
               ,'status' => array('req' => false, 'def' => 0)
               ,'opaque' => array('req' => false, 'def' => 1)
               ,'uuid' => array('req' => false, 'def' => basics::uuid())
               );
        foreach ($datafields as $k => $v) {
            if (!isset($data[$k])) {
                if ($v['req'] === true) {
                    return false;
                }
                $data[$k] = $v['def'];
            } else {
                $data[$k] = $this->esc($data[$k]);
            }
        }
        if (empty($data['gid'])) {
            $data['gid'] = 0;
        }

        // Am I the owner?
        if (!empty($data['gid']) && $this->getGroupOwner($data['gid']) != $this->uid) {
            // If not, I should have write permissions through a share
            if (!empty($GLOBALS['DB']->features['groups'])) {
                $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $data['gid']);
            }
            if (empty($perms) || empty($perms['write'])) {
                // No, I haven't
                return false;
            }
        }

        $query = 'INSERT '.$this->Tbl['cal_event']
                .' (`uid`,`gid`,`starts`,`ends`,`title`,`description`,`location`,`type`,`status`,`opaque`,`uuid`,`lastmod`) VALUES ('
                .$this->uid.', "'.$data['gid'].'" ,"'.$data['start'].'","'.$data['end'].'","'.$data['title'].'","'.$data['description'].'"'
                .',"'.$data['location'].'",'.doubleval($data['type']).','.doubleval($data['status']).',"'.doubleval($data['opaque']).'", "'.$data['uuid'].'",NOW())';
        if (!$this->query($query)) {
            return false;
        }
        $newId = $this->insertid();
        // Make sure, the end of an event is NOT before its beginning
        $this->query('UPDATE '.$this->Tbl['cal_event'].' SET `ends`=`starts` WHERE `ends`<`starts` AND id='.$newId);

        if (isset($data['attendees']) && !empty($data['attendees'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_attendee'].' (`eid`,`ref`,`name`,`email`,`role`,`type`,`status`,`mailhash`) VALUES ';
            $k = 0;
            foreach ($data['attendees'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                $query .= '('.doubleval($newId).',"evt","'.$this->esc($v['name']).'","'.$this->esc($v['email']).'"'
                        .',"'.$this->esc($v['role']).'","'.$this->esc($v['type']).'",'.doubleval($v['status'])
                        .',"'.$this->esc(basics::uuid()).'")';
                $k++;
            }
            $this->query($query);
        }

        if (isset($data['reminders']) && !empty($data['reminders'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_reminder'].' (`eid`,`ref`,`uid`,`mode`,`time`,`text`,`smsto`,`mailto`) VALUES ';
            $k = 0;
            foreach ($data['reminders'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                if ($v['mode'] == '-') {
                    continue;
                }
                $query .= '('.doubleval($newId).',"evt",'.$this->uid.',"'.$this->esc($v['mode']).'",'.doubleval($v['time'])
                        .',"'.$this->esc($v['text']).'"'
                        .',"'.(!empty($v['smsto']) ? $this->esc($v['smsto']) : '').'"'
                        .',"'.(!empty($v['mailto']) ? $this->esc($v['mailto']) : '').'")';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        }
        if (isset($data['repetitions']) && !empty($data['repetitions'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_repetition'].' (`eid`,`ref`,`type`,`repeat`,`extra`,`until`) VALUES ';
            $k = 0;
            foreach ($data['repetitions'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                $query .= '('.doubleval($newId).',"evt","'.$this->esc($v['type']).'",'.doubleval($v['repeat'])
                        .','.(isset($v['extra']) && !is_null($v['extra']) ? '"'.$this->esc($v['extra']).'"' : '""')
                        .','.(isset($v['until']) && !is_null($v['until']) ? '"'.$this->esc($v['until']).'"' : 'NULL').')';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        } else {
            $query = 'INSERT INTO '.$this->Tbl['cal_repetition'].' (`eid`,`ref`,`type`,`repeat`,`extra`,`until`) VALUES '
                    .'('.doubleval($newId).',"evt","-",0,"",NULL)';
            $this->query($query);
        }
        return $newId;
    }

    /**
     * Update an event in the database
     * @param array Specification for that event
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.4
     */
    public function update_event($data)
    {
        if (!isset($data['id']) || !$data['id']) {
            return false;
        }
        //
        // Permissions
        //
        if (!empty($data['gid'])) {
            $targetGroupId = $data['gid'];
        } else {
            $oldEvent = $this->get_event($data['id']);
            $targetGroupId = $oldEvent['gid'];
        }
        // Am I the owner?
        if ($this->getGroupOwner($targetGroupId) != $this->uid) {
            // If not, I should have write permissions through a share
            $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $targetGroupId);
            if (empty($perms) || empty($perms['write'])) {
                // No, I haven't
                return false;
            }
        }
        //
        //

        $query = 'UPDATE '.$this->Tbl['cal_event'].' SET lastmod=NOW()';
        $datafields = array('start' => 'starts', 'end' => 'ends',
                'title' => 'title', 'description' => 'description', 'location' => 'location',
                'type' => 'type', 'status' => 'status', 'opaque' => 'opaque', 'gid' => 'gid'
                );
        foreach ($datafields as $k => $v) {
            if (!isset($data[$k])) {
                continue;
            }
            $query .= ',`'.$v.'`="'.$this->esc($data[$k]).'"';
        }
        $query .= ' WHERE id='.$data['id'];

        $this->query($query);

        $this->query('DELETE FROM '.$this->Tbl['cal_reminder'].' WHERE `uid`='.$this->uid.' AND `ref`="evt" AND `eid`='.$data['id']);
        $this->query('DELETE FROM '.$this->Tbl['cal_repetition'].' WHERE `ref`="evt" AND `eid`='.$data['id']);
        if (isset($data['reminders']) && !empty($data['reminders'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_reminder'].' (`eid`,`ref`,`uid`,`mode`,`time`,`text`,`smsto`,`mailto`) VALUES ';
            $k = 0;
            foreach ($data['reminders'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                if ($v['mode'] == '-') {
                    continue;
                }

                if (!isset($v['smsto'])) {
                    $v['smsto'] = '';
                }
                if (!isset($v['mailto'])) {
                    $v['mailto'] = '';
                }

                $query .= '('.doubleval($data['id']).',"evt",'.$this->uid.',"'.$this->esc($v['mode']).'"'
                        .','.doubleval($v['time']).',"'.$this->esc($v['text']).'"'
                        .',"'.$this->esc($v['smsto']).'","'.$this->esc($v['mailto']).'")';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        }
        if (isset($data['repetitions']) && !empty($data['repetitions'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_repetition'].' (`eid`,`ref`,`type`,`repeat`,`extra`,`until`) VALUES ';
            $k = 0;
            foreach ($data['repetitions'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                $query .= '('.doubleval($data['id']).',"evt","'.$this->esc($v['type']).'",'.doubleval($v['repeat'])
                        .','.(!is_null($v['extra']) ? '"'.$this->esc($v['extra']).'"' : '""')
                        .','.(!is_null($v['until']) ? '"'.$this->esc($v['until']).'"' : 'NULL').')';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        } else {
            $query = 'INSERT INTO '.$this->Tbl['cal_repetition'].' (`eid`,`ref`,`type`,`repeat`,`extra`,`until`) VALUES '
                    .'('.doubleval($data['id']).',"evt","-",0,"",NULL)';
            $this->query($query);
        }
        return true;
    }

    /**
     * Delete an event from the database
     *
     * Watch out for potential problems with UUID based deletion. Despite their name, UUIDs in the calendar are not necessarily unique. This applies to all cases, where
     * multiple users share the same external calendar, where the same UUID might refer to mulitple instances of the same event. So deleting by UUID will always be pinned
     * to the user ID as well to prevent unwanted deletion of the wrong instance.
     *
     * @param int|array|null  ID of the event (or array of IDs)
     * @param int|array|null  UUID of the event (or array of UUIDs)
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.6
     */
    public function delete_event($id = null, $uuid = null)
    {
        if (is_null($id) && is_null($uuid)) {
            return false;
        }
        if (!is_null($uuid)) {
            if (!is_array($uuid)) {
                $uuid = array($uuid);
            }
        } else {
            if (!is_array($id)) {
                $id = array($id);
            }
        }
        //
        // Permissions
        //
        if (!is_null($uuid)) {
            $qid = $this->query('SELECT gid FROM '.$this->Tbl['cal_event'].' WHERE uid='.$this->uid.' AND `uuid`="'.$this->esc(array_values($uuid)[0]).'"');
            list ($targetGroupId) = $this->fetchrow($qid);
        } else {
            $qid = $this->query('SELECT gid FROM '.$this->Tbl['cal_event'].' WHERE `id`="'.$this->esc(array_values($id)[0]).'"');
            list ($targetGroupId) = $this->fetchrow($qid);
        }
        // Am I the owner?
        if (empty($GLOBALS['DB']->features['shares']) && empty($targetGroupId)) {
            $perms = array('delete' => 1);
        } elseif ($this->getGroupOwner($targetGroupId) != $this->uid) {
            // If not, I should have write permissions through a share
            if (!empty($GLOBALS['DB']->features['shares'])) {
                $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $targetGroupId);
            }
            if (empty($perms) || empty($perms['delete'])) {
                // No, I haven't
                return false;
            }
        }
        //
        //

        if (!is_null($uuid)) {
            foreach ($uuid as $k => $v) {
                $uuid[$k] = '"'.$this->esc($v).'"';
            }
            $uuid = implode(',', $uuid);

            $id = array();
            $res = $this->query('SELECT `id` FROM '.$this->Tbl['cal_event'].' WHERE uid='.$this->uid.' AND `uuid` IN('.$uuid.')');
            while ($line = $this->assoc($res)) {
                $id[] = $line['id'];
            }
        } else {
            foreach ($id as $k => $v) {
                $id[$k] = intval($v);
            }
        }
        $id = implode(',', $id);
        return ($this->query('DELETE FROM '.$this->Tbl['cal_event'].' WHERE uid='.$this->uid.' AND id IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_reminder'].' WHERE `ref`="evt" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_repetition'].' WHERE `ref`="evt" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_attendee'].' WHERE `ref`="evt" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_attach'].' WHERE `ref`="evt" AND `eid` IN('.$id.')'));
    }

    /**
     * Empties a given list of calendars - or everything of that user
     * Entries already archived are ignored!
     *
     * @param array $groups List of calendars to empty;
     * @return bool
     * @since 4.3.7
     */
    public function empty_calendar($groups = array())
    {
        $sqladd = '';
        if (!empty($groups)) {
            if (is_numeric($groups)) {
                $groups = array($groups);
            }
            foreach ($groups as $k => $groupId) {
                // Am I the owner?
                if ($this->getGroupOwner($groupId) != $this->uid) {
                    // If not, I should have write permissions through a share
                    $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $groupId);
                    if (empty($perms) || empty($perms['delete'])) {
                        // No, I haven't
                        return false;
                    }
                }
                //
                $groups[$k] = doubleval($groupId);
            }
            $sqladd = ' AND {TABLE}.`gid` IN('.join(',', $groups).')';
        } else {
            return false;
        }
        // Empty both the events and the tasks table with all associated secondary table items
        foreach (array('evt' => 'cal_event', 'tsk' => 'cal_task') as $token => $table) {
            $sec_sqladd = str_replace('{TABLE}', $this->Tbl[$table], $sqladd);
            foreach (array('cal_reminder', 'cal_repetition', 'cal_attendee', 'cal_attach') as $sec_table) {
                $query = 'DELETE '.$this->Tbl[$sec_table].'.* FROM '.$this->Tbl[$sec_table].', '.$this->Tbl[$table].
                        ' WHERE '.$this->Tbl[$sec_table].'.`ref`="'.$token.'" AND '.$this->Tbl[$sec_table].'.eid='.$this->Tbl[$table].'.id'.
                        $sec_sqladd;
                $this->query($query);
            }
            $query = 'DELETE FROM '.$this->Tbl[$table].' WHERE `archived`="0"'.str_replace('{TABLE}.', '', $sqladd);
            $this->query($query);
        }
        return;
    }

    /**
     * Return a list of events, where the alert time is overdue or will be reached within the next n minutes
     * @param  int Number of minutes to look into the future
     *[@param  bool  Query for all users' events; Default false]
     *[@param  bool  Only return events, where external alerts are set; Default false]
     * @return array  keys: event IDs, values: UNIX timestamp of alarm time; if onlyexternal is true, the format changes:
     *  keys: event IDs, values: array('warntime' => timestamp, 'mailto' => string, 'smsto' => string)
     * @since 0.0.9
     */
    public function get_alertable_events($min = 5, $allusers = false, $onlyexternal = false)
    {
        // $date = date('Y-m-d');
        $return = array();
        $userfilter = ($allusers != false) ? '' : ' AND ('.$this->getGroupAndShareFilter().')';
        $alertfilter = ($onlyexternal) ? ' AND (rm.`mailto` != "" OR rm.`smsto` != "")' : '';

        $query = 'SELECT
    DISTINCT e.`id`, e.`uuid`, rm.`id` `reminder_id`, rm.`text` `reminder`, rm.mailto, rm.smsto, e.uid, e.title, e.description, e.location
    ,IF (rp.`type`!="-", UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ", DATE_FORMAT(e.starts, "%T")) ), UNIX_TIMESTAMP(e.starts) ) `start`
    ,IF (rp.`type`!="-", UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ", DATE_FORMAT(e.ends, "%T")) ), UNIX_TIMESTAMP(e.ends) ) `end`
    ,IF
        (UNIX_TIMESTAMP(rm.snooze) != 0
        ,UNIX_TIMESTAMP(rm.snooze)
        ,IF
            (rm.mode = "s"
            ,UNIX_TIMESTAMP(DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ", DATE_FORMAT(e.starts, "%T")), INTERVAL rm.`time` SECOND))
            ,UNIX_TIMESTAMP(DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ", DATE_FORMAT(e.ends, "%T")), INTERVAL rm.`time` SECOND))
            )
        ) `warntime`
FROM
    '.$this->Tbl['cal_event'].' e
    ,'.$this->Tbl['cal_reminder'].' rm
    ,'.$this->Tbl['cal_repetition'].' rp
WHERE e.`archived`="0" AND e.`id` = rm.`eid` AND rm.`ref` = "evt" AND e.id = rp.eid AND rp.`ref` = "evt" AND rm.mode != "-"'.$userfilter.$alertfilter.'
    /* Dont alert cancelled events */
    AND `e`.`status` != 3
    /* Nonrepeated events get selected when they were not alerted yet or their warn_snooze is later than now */
    AND IF (rp.`type` = "-" AND rm.lastinfo != 0 AND rm.`snooze` < NOW(), 0, 1)
    AND IF (rp.`type` != "-" AND rp.`until` IS NOT NULL AND rp.`until` != "0-0-0 0:0:0", rp.`until` > NOW(), 1)
    AND
    (
        /* A rescheduled alert */
        IF (UNIX_TIMESTAMP(rm.`snooze`) > 0 AND UNIX_TIMESTAMP(rm.`snooze`)-'.($min * 60).' < UNIX_TIMESTAMP(NOW()), 1, 0)
        OR
        (
            rp.`type` = "-"
            AND
            IF
                (rm.`mode` = "s"
                , e.starts > NOW() AND rm.lastinfo != e.starts AND DATE_SUB(e.starts, INTERVAL rm.`time` + '.($min * 60).' SECOND) < NOW()
                , e.ends > NOW() AND rm.lastinfo != e.ends AND DATE_SUB(e.ends, INTERVAL rm.`time` + '.($min * 60).' SECOND) < NOW()
                )
        )
        OR
        /* Yearly event */
        (
            rp.`type` = "year"
            AND
            DATE_FORMAT(rm.`lastinfo`, "%Y") != DATE_FORMAT(NOW(), "%Y")
            AND
            IF
                (rm.`mode` = "s"
                ,CONCAT(DATE_FORMAT(NOW(), "%Y"), "-", DATE_FORMAT(e.`starts`, "%m-%d %T")) > NOW()
                    AND DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y"), "-", DATE_FORMAT(e.`starts`, "%m-%d %T")), INTERVAL (rm.`time` + '.($min * 60).') SECOND) < NOW()
                ,CONCAT(DATE_FORMAT(NOW(), "%Y"), "-", DATE_FORMAT(e.`ends`, "%m-%d %T")) > NOW()
                    AND DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y"), "-", DATE_FORMAT(e.`ends`, "%m-%d %T")), INTERVAL (rm.`time` + '.($min * 60).') SECOND) < NOW()
                )
        )
        OR
        /* Monthly event */
        (
            rp.`type` = "month"
            AND
            DATE_FORMAT(rm.`lastinfo`, "%Y%m") != DATE_FORMAT(NOW(), "%Y%m")
            AND
            (
                rp.`extra` = ""
                OR
                FIND_IN_SET(DATE_FORMAT(NOW(), "%c"), rp.`extra`) > 0
            )
            AND
            IF
                (rm.`mode` = "s"
                ,CONCAT(DATE_FORMAT(NOW(),"%Y-%m"), "-", rp.`repeat`, " ", DATE_FORMAT(e.`starts`, "%T")) > NOW()
                    AND DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m"), "-", rp.`repeat`, " ", DATE_FORMAT(e.`starts`, "%T")), INTERVAL (rm.`time` + '.($min * 60).') SECOND) < NOW()
                ,CONCAT(DATE_FORMAT(NOW(),"%Y-%m"), "-", rp.`repeat`, " ", DATE_FORMAT(e.`ends`, "%T")) > NOW()
                    AND DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m"), "-", rp.`repeat`, " ", DATE_FORMAT(e.`ends`, "%T")), INTERVAL (rm.`time` + '.($min * 60).') SECOND) < NOW()
                )
        )
        OR
        /* Monthly event on e.g. the 31st of month with months shorter than 31 days, will only work with alerts set for the same day */
        (
            LAST_DAY(NOW()) = CURDATE()
            AND
            rp.`type` = "month"
            AND
            rp.`repeat` = 31
            AND
            DATE_FORMAT(rm.`lastinfo`, "%Y%m") != DATE_FORMAT(NOW(), "%Y%m")
            AND
            (
                rp.`extra` = ""
                OR
                FIND_IN_SET(DATE_FORMAT(NOW(), "%c"), rp.`extra`) > 0
            )
            AND
            IF
                (rm.`mode` = "s"
                ,DATE_FORMAT(e.`starts`, "%d%H%i%s") > DATE_FORMAT(LAST_DAY(NOW()), "%d%H%i%s")
                    AND DATE_FORMAT(UNIX_TIMESTAMP(e.`starts`) - (rm.`time` + '.($min * 60).'), "%H%i%s") < DATE_FORMAT(NOW(), "%H%i%s")
                ,DATE_FORMAT(e.`ends`, "%d%H%i%s") > DATE_FORMAT(LAST_DAY(NOW()), "%d%H%i%s")
                    AND DATE_FORMAT(UNIX_TIMESTAMP(e.`ends`) - (rm.`time` + '.($min * 60).'), "%H%i%s") < DATE_FORMAT(NOW(), "%H%i%s")
                )
        )
        OR
        /* Weekly event */
        (
            rp.`type` = "week"
            AND
            (UNIX_TIMESTAMP(rm.lastinfo) = 0 OR DATE_FORMAT(rm.`lastinfo`, "%Y%m%d") != DATE_FORMAT(NOW(), "%Y%m%d"))
            AND
            IF
                (rm.`mode`="s"
                ,DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%w%H%i") >= DATE_FORMAT(e.`starts`, "%w%H%i")
                    AND DATE_FORMAT(CAST(NOW() + INTERVAL rm.`time` SECOND AS DATETIME), "%w%H%i") <= DATE_FORMAT(e.`starts`, "%w%H%i")
                    AND IF(rp.`extra` IN("", "1"), 1, ABS(MOD(DATEDIFF(e.`starts`, NOW()) / 7, rp.`extra`)) = 0)
                ,DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%w%H%i") >= DATE_FORMAT(e.`ends`, "%w%H%i")
                    AND DATE_FORMAT(CAST(NOW() + INTERVAL rm.`time` SECOND AS DATETIME), "%w%H%i") <= DATE_FORMAT(e.`ends`, "%w%H%i")
                    AND IF(rp.`extra` IN("", "1"), 1, ABS(MOD(DATEDIFF(e.`ends`, NOW()) / 7, rp.`extra`)) = 0)
                )
        )
        OR
        /* "Daily" event, where the bit pattern should match today\'s weekday */
        (
            rp.`type` = "day"
            AND
            (UNIX_TIMESTAMP(rm.lastinfo) = 0 OR DATE_FORMAT(rm.`lastinfo`, "%Y%m%d") != DATE_FORMAT(NOW(), "%Y%m%d"))
            AND
                (rp.`repeat`="0"
                OR
                SUBSTRING(LPAD(BIN(rp.`repeat`), 8, 0), IF(DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%w") = 0, 8, DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%w")), 1) = 1
                )
            AND
            IF
                (rm.`mode`="s"
                ,DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%H%i") >= DATE_FORMAT(e.`starts`, "%H%i")
                    AND DATE_FORMAT(CAST(NOW() + INTERVAL rm.`time` SECOND AS DATETIME), "%H%i") <= DATE_FORMAT(e.`starts`, "%H%i")
                ,DATE_FORMAT(CAST(NOW() + INTERVAL (rm.`time` + '.($min * 60).') SECOND AS DATETIME), "%H%i") >= DATE_FORMAT(e.`ends`, "%H%i")
                    AND DATE_FORMAT(CAST(NOW() + INTERVAL rm.`time` SECOND AS DATETIME), "%H%i") <= DATE_FORMAT(e.`ends`, "%H%i")
                )
        )
    )
ORDER BY `warntime` ASC';

        // echo $query.LF;

        $qid = $this->query($query);
        if (false === $qid) {
            $error = $this->error();
            if ($error && function_exists('vecho')) {
                error_log($error);
                vecho($error);

            }
            return array();
        }
        while ($line = $this->assoc($qid)) {
            $return[$line['id']] = array
                  ('warn_time' => $line['warntime'], 'mailto' => $line['mailto']
                  ,'smsto' => $line['smsto'], 'uid' => $line['uid']
                  ,'title' => $line['title'], 'description' => $line['description']
                  ,'location' => $line['location'], 'starts' => $line['start'], 'ends' => $line['end']
                  ,'reminder' => $line['reminder'], 'reminder_id' => $line['reminder_id']
                  );
        }
        // print_r($return);
        return $return;
    }

    /**
     * Switches off a scheduled warn_time of given event
     * @param int ID of the event reminder (API break between phlyMail 3.6 and 3.7)
     * @return bool  MySQL return value of the SQL statement
     * @since 0.1.0
     */
    public function discard_event_alert($rid)
    {
        $query = 'UPDATE '.$this->Tbl['cal_reminder'].' SET `lastinfo`=NOW(), `snooze`="0000-00-00 00:00:00" WHERE `id`='.doubleval($rid);
        $return = $this->query($query);
        if (!$return) {
            $this->set_error($this->error());
        }
        return $return;
    }

    /**
     * Reschedules the alert for a given event, by default 5mins into the future
     * @param  int  ID of the event reminder to reschedule
     * @param  int  Number of seconds to set the delay to (starting with NOW())
     * @return  bool  MySQL return value of the SQL statement issued
     * @since 0.1.0
     */
    public function repeat_event_alert($rid, $timespan = 300)
    {
        if (!$timespan) {
            $timespan = 300;
        }
        $query = 'UPDATE '.$this->Tbl['cal_reminder'].' SET `lastinfo`="0000-00-00 00:00:00",`snooze`=(UNIX_TIMESTAMP(NOW())+ '.doubleval($timespan).')'
               .' WHERE `id`='.doubleval($rid);
        $return = $this->query($query);
        if (!$return) {
            $this->set_error($this->error());
        }
        return $return;
    }

    /**
     * Archive events according to their folder and their age
     *
     * @param  int  $gid ID of the folder (group)
     * @param  string  $age  <int> <interval>, e.g. 1 MONTH
     * @return boolean  MySQL return value of the performed SQL query
     */
    public function archive_events($gid, $age)
    {
        if (!preg_match('!^(\d+)\s([a-z]+)$!i', $age)) {
            return false;
        }
        $query = 'UPDATE '.$this->Tbl['cal_event'].' e, '.$this->Tbl['cal_repetition'].' rp'.
                ' SET e.`archived`="1"'.
                ' WHERE e.uid='.$this->uid.' AND e.gid='.intval($gid).' AND rp.`eid`=e.`id` AND rp.`ref`="evt"'.
                ' AND (rp.`type`="-" OR (rp.`type`!="-" AND rp.`until` IS NOT NULL AND rp.`until`<NOW()))'.
                ' AND DATE_FORMAT(DATE_ADD(e.ends, INTERVAL '.$age.'), "%Y%m%d") < DATE_FORMAT(NOW(), "%Y%m%d")';
        return $this->query($query);
    }


    /**
     * Removes all old events from database identified by folder (group) and age
     *
     * @param  int  $gid ID of the folder (group)
     * @param  string  $age  <int> <interval>, e.g. 1 MONTH
     * @return boolean  MySQL return value of the performed SQL query
     * @since 0.1.2
     */
    public function expire_events($gid, $age)
    {
        if (!preg_match('!^(\d+)\s([a-z]+)$!i', $age)) {
            return false;
        }
        $query = 'DELETE FROM '.$this->Tbl['cal_event'].' e, '.$this->Tbl['cal_reminder'].' rm,'.$this->Tbl['cal_repetition'].' rp'.
                ' WHERE e.uid='.$this->uid.' AND e.gid='.intval($gid).' AND rm.`eid`=e.`id` AND rm.`ref`="evt" AND rp.`eid`=e.`id` AND rp.`ref`="evt"'.
                ' AND (rp.`type`="-" OR (rp.`type`!="-" AND rp.`until` IS NOT NULL AND rp.`until`<NOW()))'.
                ' AND DATE_FORMAT(DATE_ADD(e.ends, INTERVAL '.$age.'), "%Y%m%d") < DATE_FORMAT(NOW(), "%Y%m%d")';
        return $this->query($query);
    }

    /**
     * Adds an attende to an event. Generates the hash for invitation mails, too.
     *
     * @param int $eid The event to attach the attendee to
     * @param string $name  Firstname, might be empty
     * @param string $email   Email address, might be empty
     *[@param int $status  Attendance status; Default: 0]
     *[@param string $role  One of chair, req, opt, non; Default: opt]
     *[@param string $type  One of person, group, resource, room, unknown; Default: person]
     *[@param string evt|tsk|jou  Specify, what kind of entity the given $eid refers to (event, task, yournal); Default: evt]
     * @return array(int, string) The ID of the new entry and the unique hash to identify the attendee
     */
    public function add_event_attendee($eid, $name, $email, $status = 0, $role = 'opt', $type = 'person', $ref = 'evt')
    {
        if (!$eid) {
            return false; // We NEED the event's ID
        }
        $hash = basics::uuid();
        $query = 'INSERT INTO '.$this->Tbl['cal_attendee'].' SET '
                .'`eid`='.doubleval($eid).',`name`="'.$this->esc($name).'",`ref`="'.$this->esc($ref).'"'
                .',`email`="'.$this->esc($email).'",`role`="'.$this->esc($role).'"'
                .',`type`="'.$this->esc($type).'",`status`="'.$this->esc($status).'"'
                .',`mailhash`="'.$this->esc($hash).'"';
        if ($this->query($query)) {
            $newId = $this->insertid();
            return array($newId, $hash);
        }
        return false;
    }

    /**
     * Update event attendee's data
     *
     * @param int $id  ID of the attendee
     *[@param string $name  Name of the attendee]
     *[@param string $email  Email address of the attendee]
     *[@param int $status  Attendance status]
     *[@param string $role  One of chair, req, opt, non]
     *[@param string $type  One of person, group, resource, room, unknown]
     * @return bool
     */
    public function update_event_attendee($id, $name = null, $email = null, $status = 0, $role = null, $type = null)
    {
        if (is_null($name) && is_null($email)) {
            return true; // Nothing to do
        }
        $query = 'UPDATE '.$this->Tbl['cal_attendee'].' SET eid=eid'
                .(!is_null($name) ? ',`name`="'.$this->esc($name).'"' : '')
                .(!is_null($email) ? ',`email`="'.$this->esc($email).'"' : '')
                .(!is_null($status) ? ',`status`="'.$this->esc($status).'"' : '')
                .(!is_null($role) ? ',`role`="'.$this->esc($role).'"' : '')
                .(!is_null($type) ? ',`type`="'.$this->esc($type).'"' : '')
                .' WHERE `id`='.doubleval($id);
        return $this->query($query);
    }

    /**
     * Delete an event's attendee. For your convenience a registered XNA for
     * the given attendee is unregistered, too.
     *
     * @param int $id  ID of the attendee
     * @return bool
     */
    public function delete_event_attendee($id)
    {
        $attendee = $this->get_event_attendees(null, $id, null);
        if (empty($attendee)) {
            return true;
        }
        $XNA = new DB_Controller_XNA();
        $XNA->unregister($attendee['mailhash']);
        return $this->query('DELETE FROM '.$this->Tbl['cal_attendee'].' WHERE `id`='.doubleval($id));
    }

    /**
     * Get event attendee list
     *
     *[@param int $eid  event ID]
     *[@param int $aid  attendee ID]
     *[@param string $hash  attendee mail hash]
     *[@param evt|tsk|jou  Specify, what kind of entity the $eid refers to; Default: evt]
     * @return array
     */
    public function get_event_attendees($eid = null, $aid = null, $hash = null, $ref = 'evt')
    {
        if (is_null($eid) && is_null($aid) && is_null($hash)) {
            return false;
        }

        $return = array();
        $q_r = '`eid`='.doubleval($eid).' AND `ref`="'.$this->esc($ref).'"';
        $order = ' ORDER BY `name` ASC';
        if (!is_null($hash)) {
            $q_r = '`mailhash`="'.$this->esc($hash).'"';
            $order = '';
        } elseif (!is_null($aid)) {
            $q_r = '`id`='.doubleval($aid);
            $order = '';
        }
        $query = 'SELECT `id`,`eid`,`ref`,`name`,`email`,`role`,`type`,`mailhash`,`invited`,`rsvp`,`status`'
                .' FROM '.$this->Tbl['cal_attendee'].' WHERE '.$q_r.$order;
        $qid = $this->query($query);
        if ($order != '') {
            while ($line = $this->assoc($qid)) {
                $return[] = $line;
            }
        } else {
            $return = $this->assoc($qid);
        }
        return $return;
    }

    /**
     * Marks an event attendee as invited
     *
     * @param string $hash  The mail hash of the attendee (supposed to be unique)
     * @return bool
     */
    public function set_event_attendee_invited($hash)
    {
        return $this->query('UPDATE '.$this->Tbl['cal_attendee'].' SET `invited`=NOW() WHERE `mailhash`="'.$this->esc($hash).'"');
    }

    /**
     * Marks the RSVP (invitation reply) status of an event attendee
     *
     * @param string $hash  The mail hash of the attendee
     * @param int $status  1 = accepted, 2 = denied, 3 = maybe
     * @return bool
     */
    public function set_event_attendee_rsvp($hash, $status)
    {
        $query = 'UPDATE '.$this->Tbl['cal_attendee'].' SET `rsvp`=NOW(), `status`='.doubleval($status)
                .' WHERE `mailhash`="'.$this->esc($hash).'"';
        return $this->query($query);
    }


    public function get_task_count($gid = 0, $pattern = '', $criteria = '')
    {
        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = $this->getQueryTypeFilter($gid, 't');
        $gidFilter = $this->getGroupAndShareFilter($gid, 't');

        $q_l = 'SELECT COUNT(*) FROM '.$this->Tbl['cal_task'].' t'.$eventListFilter[0].' WHERE ('.$gidFilter.')'.$eventListFilter[1];

        if ($gid) {
            $q_l .= ' AND t.`gid`='.doubleval($gid);
        }
        // Do we have a search criteria and a pattern set?
        if ($criteria && $pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            if (isset($this->criteria_list[$criteria])) {
                // Flatten the field list
                foreach ($this->criteria_list[$criteria] as $k) {
                    $v[] = 't.'.$k.' LIKE "'.$pattern.'"';
                }
                $q_l.=' AND ('.implode(' OR ', $v).')';
            }
        }
        list ($count) = $this->fetchrow($this->query($q_l));
        return $count;
    }

    public function get_tasklist($gid = 0, $pattern = '', $criteria = '', $num = 0, $start = 0, $order_by = false, $order_dir = 'ASC')
    {
        $return = array();
        // Support for filtering out events from groups not included in result set according to query type
        $eventListFilter = ($gid == 'NULL') ? array('', '') : $this->getQueryTypeFilter($gid, 't');
        $gidFilter = $this->getGroupAndShareFilter($gid, 't');
        $q_r = '';
        $q_l = 'SELECT t.`id`,t.`gid`,t.`title`,t.`location`,t.`description`,t.`type`,t.`status`,t.`importance`,t.`completion`,t.`uuid`, t.`starts`, t.`ends`,fs.`val` `colour`'
                .', IF(t.`starts`=0, NULL, UNIX_TIMESTAMP(t.`starts`)) `start`, IF(t.`ends`=0, NULL, UNIX_TIMESTAMP(t.`ends`)) `end`'
                .((version_compare($this->DB['ServerVersionString'], '4.1.1', 'ge'))
                        ? ', (SELECT `mode` FROM '.$this->Tbl['cal_reminder'].' WHERE `uid`='.$this->uid.' AND `eid`=t.`id` AND `ref`="tsk" LIMIT 1) `warn_mode`'
                        : ', "?" `warn_mode`')
                .' FROM '.$this->Tbl['cal_task'].' t'
                .' LEFT JOIN '.$this->Tbl['cal_group'].' eg ON eg.`gid`=t.`gid`'
                .' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=t.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid
                .$eventListFilter[0]
                .' WHERE ('.$gidFilter.')'.$eventListFilter[1];
        if ($gid === 'NULL') {
            $q_l .= ' AND t.`gid`=0';
        } elseif ($gid > 0) {
            $q_l .= ' AND t.`gid`='.doubleval($gid);
        }
        $order_dir = ('ASC' == $order_dir) ? 'ASC' : 'DESC';
        // Order by / direction given?
        $orderstring = ($order_by) ? 't.`'.$this->esc($order_by).'` '.$order_dir : 't.`completion` DESC';
        // Do we have a search criteria and a pattern set?
        if ($pattern == '@@upcoming@@') { // Special filter for upcoming events (pinboard)
            $q_l .= ' AND (t.`starts` IS NULL OR t.`ends` IS NULL OR t.`starts`=0 OR t.`ends`=0 OR t.`starts`>=NOW() OR (t.`starts`<=NOW() AND t.`ends`>=NOW())) AND  t.`completion`<100';
        } elseif ($criteria && $pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            if (isset($this->criteria_list[$criteria])) {
                // Flatten the field list
                foreach ($this->criteria_list[$criteria] as $k) {
                    $v[] = 't.'.$k.' LIKE "'.$pattern.'"';
                }
                $q_l .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        if ($num != 0) {
            $q_r .= ' LIMIT '.($start).','.($num);
        }
        $qid = $this->query($q_l . ' ORDER BY ' . $orderstring.$q_r);
        while ($line = $this->assoc($qid)) {
            if ($line['warn_mode'] == '?') {
                $qid2 = $this->query('SELECT `mode` FROM '.$this->Tbl['cal_reminder']
                        .' WHERE `uid`='.$this->uid.' AND `eid`='.$line['id'].' AND `ref`="tsk" AND `mode` IS NOT NULL LIMIT 1');
                list ($rem) = $this->fetchrow($qid2);
                $line['warn_mode'] = $rem ? $rem : '-';
            } elseif ($line['warn_mode'] == '' || is_null($line['warn_mode'])) {
                $line['warn_mode'] = '-';
            }
            $return[] = $line;
        }
        return $return;
    }

    public function getTaskUUIDs($gid = 0, $archived = false)
    {
        $return = array();
        $query = 'SELECT `id`, `uuid` FROM '.$this->Tbl['cal_task'].' WHERE `archived`="'.($archived ? 1 : 0).'"';
        if ($gid > 0) {
            $query .= ' AND gid='.doubleval($gid);
        }
        $res = $this->query($query);
        while ($line = $this->assoc($res)) {
            $return[$line['uuid']] = $line[ 'id'];
        }
        return $return;
    }

    /**
     * Retrieve detailed data about an task
     * @param int ID of the task
     * @return array Specific data, false on failure / non-existant ID
     * @since 0.0.3
     */
    public function get_task($id, $reminder = null)
    {
        if (!is_null($reminder)) {
            $qid = $this->query('SELECT `eid` FROM '.$this->Tbl['cal_reminder'].' WHERE `ref`="tsk" AND id='.doubleval($reminder));
            list($id) = $this->fetchrow($qid);
            if (!$id) {
                return array();
            }
        }
        $id = doubleval($id);

        // Allows to retrieve events from calendars, which are shared with us
        $gidFilter = $this->getGroupAndShareFilter(0, 't');

        $query = 'SELECT t.`gid`,t.`title`,t.`location`,t.`description`,t.`starts`,t.`ends`,t.`type`,t.`status`,t.`importance`,t.`completion`,t.`uuid`,fs.`val` `colour`'
                .', IF(t.`starts` IS NULL, NULL, UNIX_TIMESTAMP(t.`starts`)) `start`, IF(t.`ends` IS NULL, NULL, UNIX_TIMESTAMP(t.`ends`)) `end`'
                .' FROM '.$this->Tbl['cal_task'].' t'
                .' LEFT JOIN '.$this->Tbl['cal_group'].' eg ON eg.`gid`=t.`gid`'
                .' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=t.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid
                .' WHERE ('.$gidFilter.') AND t.id='.doubleval($id);
        $task = $this->assoc($this->query($query));
        $task['reminders'] = array();
        $qid = $this->query('SELECT `id`,`time`,`mode`,`text`,`mailto`,`smsto` FROM '.$this->Tbl['cal_reminder']
                .' WHERE `uid`='.$this->uid.' AND `eid`='.$id.' AND `ref`="tsk" ORDER BY `mode` DESC, `time` DESC');
        while ($line = $this->assoc($qid)) {
            $task['reminders'][] = $line;
            if (!is_null($reminder) && $line['id'] == $reminder) {
                $task['reminder_text'] = $line['text'];
            }
        }
        return $task;
    }

    /**
     * Add an task to the database
     * @param array Specification for that task
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.2
     */
    public function add_task($data)
    {
        $datafields = array
               ('start' => array('req' => false, 'def' => 'NULL')
               ,'end' => array('req' => false, 'def' => 'NULL')
               ,'gid' => array('req' => true)
               ,'title' => array('req' => false, 'def' => '')
               ,'location' => array('req' => false, 'def' => '')
               ,'description' => array('req' => false, 'def' => '')
               ,'importance' => array('req' => false, 'def' => '1')
               ,'completion' => array('req' => false, 'def' => '0')
               ,'type' => array('req' => false, 'def' => '0')
               ,'status' => array('req' => false, 'def' => '0')
               ,'uuid' => array('req' => false, 'def' => basics::uuid())
               );
        foreach ($datafields as $k => $v) {
            if (!isset($data[$k])) {
                if ($v['req'] === true) {
                    return false;
                }
                $data[$k] = $v['def'];
            } else {
                $data[$k] = $this->esc($data[$k]);
            }
        }
        // Am I the owner?
        if ($this->getGroupOwner($data['gid']) != $this->uid) {
            // If not, I should have write permissions through a share
            $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $data['gid']);
            if (empty($perms) || empty($perms['write'])) {
                // No, I haven't
                return false;
            }
        }
        $query = 'INSERT '.$this->Tbl['cal_task'].' SET `uid`='.$this->uid.',`gid`='.$data['gid']
                .',`starts`='.($data['start'] == 'NULL' ? 'NULL' : '"'.$data['start'].'"')
                .',`ends`='.($data['end'] == 'NULL' ? 'NULL' : '"'.$data['end'].'"')
                .',`title`="'.$data['title'].'",`location`="'.$data['location'].'"'
                .',`description`="'.$data['description'].'",`uuid`="'.$data['uuid'].'"'
                .',`importance`='.doubleval($data['importance']).',`completion`='.doubleval($data['completion'])
                .',`type`='.doubleval($data['type']).',`status`='.doubleval($data['status']);
        if (!$this->query($query)) {
            return false;
        }
        $newId = $this->insertid();
        // Make sure, the end of an event is NOT before its beginning
        $this->query('UPDATE '.$this->Tbl['cal_task'].' SET `ends`=`starts` WHERE `ends`<`starts` AND id='.$newId);

        if (isset($data['reminders']) && !empty($data['reminders'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_reminder'].' (`eid`,`ref`,`uid`,`mode`,`time`,`text`,`smsto`,`mailto`) VALUES ';
            $k = 0;
            foreach ($data['reminders'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                if ($v['mode'] == '-') {
                    continue;
                }
                $query .= '('.doubleval($newId).',"tsk",'.$this->uid.',"'.$this->esc($v['mode']).'",'.doubleval($v['time'])
                        .',"'.$this->esc($v['text']).'","'.$this->esc($v['smsto']).'","'.$this->esc($v['mailto']).'")';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        }
        return $newId;
    }

    /**
     * Update an task in the database
     * @param array Specification for that task
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.4
     */
    public function update_task($data)
    {
        if (!isset($data['id']) || !$data['id']) {
            return false;
        }

        //
        // Permissions
        //
        if (!empty($data['gid'])) {
            $targetGroupId = $data['gid'];
        } else {
            $oldEvent = $this->get_task($data['id']);
            $targetGroupId = $oldEvent['gid'];
        }
        // Am I the owner?
        if ($this->getGroupOwner($targetGroupId) != $this->uid) {
            // If not, I should have write permissions through a share
            $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $targetGroupId);
            if (empty($perms) || empty($perms['write'])) {
                // No, I haven't
                return false;
            }
        }
        //
        //

        $query = 'UPDATE '.$this->Tbl['cal_task'].' SET lastmod=NOW()';
        foreach (array('start' => 'starts', 'end' => 'ends', 'title' => 'title', 'location' => 'location'
               ,'description' => 'description', 'importance' => 'importance', 'gid' => 'gid'
               ,'completion' => 'completion', 'type' => 'type', 'status' => 'status'
               ) as $k => $v) {
            if (!isset($data[$k])) {
                continue;
            }
            $query .= ',`'.$v.'`='.(('NULL' == $data[$k] || is_null($data[$k])) ? 'NULL' : '"'.$this->esc($data[$k]).'"');
        }
        $this->query($query.' WHERE uid='.$this->uid.' AND id='.$data['id']);
        // Reminders set
        $this->query('DELETE FROM '.$this->Tbl['cal_reminder'].' WHERE `uid`='.$this->uid.' AND `ref`="tsk" AND `eid`='.$data['id']);
        if (isset($data['reminders']) && !empty($data['reminders'])) {
            $query = 'INSERT INTO '.$this->Tbl['cal_reminder'].' (`eid`,`ref`,`uid`,`mode`,`time`,`text`,`smsto`,`mailto`) VALUES ';
            $k = 0;
            foreach ($data['reminders'] as $v) {
                if ($k) {
                    $query .= ',';
                }
                if ($v['mode'] == '-') {
                    continue;
                }
                $query .= '('.doubleval($data['id']).',"tsk",'.$this->uid.',"'.$this->esc($v['mode']).'"'
                        .','.doubleval($v['time']).',"'.$this->esc($v['text']).'"'
                        .',"'.$this->esc($v['smsto']).'","'.$this->esc($v['mailto']).'")';
                $k++;
            }
            if ($k > 0) {
                $this->query($query);
            }
        }
        return true;
    }

    /**
     * Delete an task from the database
     *
     * Watch out for potential problems with UUID based deletion. Despite their name, UUIDs in the calendar are not necessarily unique. This applies to all cases, where
     * multiple users share the same external calendar, where the same UUID might refer to mulitple instances of the same event. So deleting by UUID will always be pinned
     * to the user ID as well to prevent unwanted deletion of the wrong instance.
     *
     * @param int|array|null  ID of the event (or array of IDs)
     * @param int|array|null  UUID of the event (or array of UUIDs)
     * @return boolean TRUE on success, FALSE otherwise
     * @since 0.0.6
     */
    public function delete_task($id = null, $uuid = null)
    {
        if (is_null($id) && is_null($uuid)) {
            return false;
        }

        if (!is_null($uuid)) {
            if (!is_array($uuid)) {
                $uuid = array($uuid);
            }
        } else {
            if (!is_array($id)) {
                $id = array($id);
            }
        }

        //
        // Permissions
        //
        if (!is_null($uuid)) {
            $qid = $this->query('SELECT gid FROM '.$this->Tbl['cal_task'].' WHERE uid='.$this->uid.' AND `uuid`="'.$this->esc(array_values($uuid)[0]).'"');
            list ($targetGroupId) = $this->fetchrow($qid);
        } else {
            $qid = $this->query('SELECT gid FROM '.$this->Tbl['cal_task'].' WHERE `id`="'.$this->esc(array_values($id)[0]).'"');
            list ($targetGroupId) = $this->fetchrow($qid);
        }

        // Am I the owner?
        if ($this->getGroupOwner($targetGroupId) != $this->uid) {
            // If not, I should have write permissions through a share
            $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $targetGroupId);
            if (empty($perms) || empty($perms['delete'])) {
                // No, I haven't
                return false;
            }
        }
        //
        //

        if (!is_null($uuid)) {
            foreach ($uuid as $k => $v) {
                $uuid[$k] = '"'.$this->esc($v).'"';
            }
            $uuid = implode(',', $uuid);

            $id = array();
            $res = $this->query('SELECT `id` FROM '.$this->Tbl['cal_task'].' WHERE uid='.$this->uid.' AND `uuid` IN('.$uuid.')');
            while ($line = $this->assoc($res)) {
                $id[] = $line['id'];
            }
        } else {
            foreach ($id as $k => $v) {
                $id[$k] = intval($v);
            }
        }
        $id = implode(',', $id);

        return ($this->query('DELETE FROM '.$this->Tbl['cal_task'].' WHERE uid='.$this->uid.' AND id IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_reminder'].' WHERE `ref`="tsk" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_repetition'].' WHERE `ref`="tsk" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_attendee'].' WHERE `ref`="tsk" AND `eid` IN('.$id.')')
                && $this->query('DELETE FROM '.$this->Tbl['cal_attach'].' WHERE `ref`="tsk" AND `eid` IN('.$id.')'));
    }

    /**
     * Return a list of events, where the alert time is overdue or will be reached within the next n minutes
     * @param  int Number of minutes to look into the future
     *[@param  bool  Query for all users' events; Default false]
     *[@param  bool  Only return events, where external alerts are set; Default false]
     * @return array  keys: event IDs, values: UNIX timestamp of alarm time; if onlyexternal is true, the format changes:
     *  keys: event IDs, values: array('warntime' => timestamp, 'mailto' => string, 'smsto' => string)
     * @since 0.0.9
     */
    public function get_alertable_tasks($min = 5, $allusers = false, $onlyexternal = false)
    {
        $return = array();
        $userfilter = ($allusers != false) ? '' : ' AND ('.$this->getGroupAndShareFilter(null, 't').')';
        $alertfilter = ($onlyexternal) ? ' AND (rm.`mailto` != "" OR rm.`smsto` != "")' : '';

        $query = 'SELECT DISTINCT t.`id`,t.`uuid`, rm.`id` `reminder_id`,rm.`text` `reminder`,rm.mailto,rm.smsto, t.uid, t.title, t.description, t.location'
                .', UNIX_TIMESTAMP(t.starts)`start`, UNIX_TIMESTAMP(t.ends) `end`'
                .', IF (UNIX_TIMESTAMP(rm.snooze) != 0, UNIX_TIMESTAMP(rm.snooze), IF (rm.mode="s", UNIX_TIMESTAMP(DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ",DATE_FORMAT(t.starts, "%T")), INTERVAL rm.time SECOND)), UNIX_TIMESTAMP(DATE_SUB(CONCAT(DATE_FORMAT(NOW(), "%Y-%m-%d"), " ",DATE_FORMAT(t.ends, "%T")), INTERVAL rm.`time` SECOND)))) `warntime`'
                .' FROM '.$this->Tbl['cal_task'].' t,'.$this->Tbl['cal_reminder'].' rm'
                .' WHERE t.id=rm.eid AND rm.`ref`="tsk" AND rm.mode!="-"'.$userfilter.$alertfilter
                .' AND IF(rm.lastinfo!=0 AND rm.`snooze` < NOW(), 0, 1)'
                // Don't alert cancelled events
                .' AND t.`status` != 3 AND ('
                // Nonrepeated events get selected when they were not alerted yet or their warn_snooze is later than now
                .'IF (rm.`mode`="s"'
                .', t.starts > NOW() AND rm.lastinfo != t.starts AND DATE_SUB(t.starts, INTERVAL rm.`time`+'.($min * 60).' SECOND) < NOW()'
                .', t.ends > NOW() AND rm.lastinfo != t.ends AND DATE_SUB(t.ends, INTERVAL rm.`time`+'.($min * 60).' SECOND) < NOW()'
                .') OR '
                // A rescheduled alert
                .'IF(UNIX_TIMESTAMP(rm.`snooze`) > 0 AND UNIX_TIMESTAMP(rm.`snooze`)-'.($min * 60).' < UNIX_TIMESTAMP(NOW()), 1, 0)'
                .') ORDER BY `warntime` ASC';
        // if (function_exists('vecho')) vecho($query);
        $qid = $this->query($query);
        // if (function_exists('vecho')) vecho $this->error();
        while ($line = $this->assoc($qid)) {
            $return[$line['id']] = array
                  ('warn_time' => $line['warntime'], 'mailto' => $line['mailto']
                  ,'smsto' => $line['smsto'], 'uid' => $line['uid']
                  ,'title' => $line['title'], 'description' => $line['description']
                  ,'location' => $line['location'], 'starts' => $line['start'], 'ends' => $line['end']
                  ,'reminder' => $line['reminder'], 'reminder_id' => $line['reminder_id']
                  );
        }
        return $return;
    }

    /**
     * Archive tasks according to their folder and their age
     *
     * @param  int  $gid ID of the folder (group)
     * @param  string  $age  <int> <interval>, e.g. 1 MONTH
     * @return boolean  MySQL return value of the performed SQL query
     */
    public function archive_tasks($gid, $age)
    {
        if (!preg_match('!^(\d+)\s([a-z]+)$!i', $age)) {
            return false;
        }
        $query = 'UPDATE '.$this->Tbl['cal_task'].' t SET t.`archived`="1"'.
                ' WHERE t.uid='.$this->uid.' AND t.gid='.intval($gid).' AND t.completion>=100'.
                ' AND DATE_FORMAT(DATE_ADD(t.ends, INTERVAL '.$age.'), "%Y%m%d") < DATE_FORMAT(NOW(), "%Y%m%d")';
        return $this->query($query);
    }


    /**
     * Expire tasks according to their folder and their age
     *[@param  int  Minimum age in minutes of tasks considered to be old, default is 0]
     * @return boolean  MySQL return value of the performed SQL query
     * @since 0.1.2
     */
    public function expire_tasks($gid, $age)
    {
        if (!preg_match('!^(\d+)\s([a-z]+)$!i', $age)) {
            return false;
        }
        $query = 'DELETE FROM '.$this->Tbl['cal_task'].' t'.
                ' WHERE t.uid='.$this->uid.' AND t.gid='.intval($gid).' AND t.completion>=100'.
                ' AND DATE_FORMAT(DATE_ADD(t.ends, INTERVAL '.$age.'), "%Y%m%d") < DATE_FORMAT(NOW(), "%Y%m%d")';
        return $this->query($query);
    }

    /**
     * Return list of groups associated with a certain user. Includes the shared groups
     *
     * @param integer user id
     * @param boolean with global groups?
     * [@param string pattern
     * [@param integer num
     * [@param integer start]]])
     * @return $return array data on success, FALSE otherwise
     * @since 0.3.4
     */
    public function get_grouplist($inc_global = 0, $pattern = '', $num = 0, $start = 0)
    {
        $return = array();

        $groupsUser = !empty($this->principalId) ? $this->principalId : $this->uid;

        $sqlGroupFilter = $this->getGroupAndShareFilter(null, 'g', false);
        if (!empty($sqlGroupFilter)) {
            $sqlGroupFilter = ' OR '.$sqlGroupFilter;
        }
        $query = 'SELECT g.`gid`, g.`name`, g.`type`, g.`owner`, fs.`val` `colour`, g.`rw`, u.username FROM '.$this->Tbl['cal_group'].' g'.
                ' LEFT JOIN '.$this->Tbl['user'].' u ON u.uid=g.owner'.
                ' LEFT JOIN '.$this->Tbl['user_foldersettings'].' fs ON fs.`fid`=g.`gid` AND fs.`handler`="calendar" AND fs.`key`="foldercolour" AND fs.uid='.$this->uid.
                ' WHERE (g.owner'.($inc_global ? ' IN('.$groupsUser.',0)' : '='.$groupsUser).
                $sqlGroupFilter.')';
        if (!empty($pattern)) {
            $query .= ' AND `name` LIKE "%'.$this->esc($pattern).'%"';
        }
        $query .= ' GROUP BY g.gid ORDER BY g.name, g.owner';
        if ($num != 0) {
            $query .= ' LIMIT ' . doubleval($start) . ',' . doubleval($num);
        }
        $qid = $this->query($query);
        while ($line = $this->assoc($qid)) {
            $line['is_shared'] = !empty($this->allShares[$line['gid']]) ? '1' : '0';
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Retrieve information about a folder (right now still called groups due to historical reasons)
     *
     * @param int $gid  ID of the folder (group) to get info about
     *[@param bool $nameOnly Set to true, to get the name of the folder as a string; Default: FALSE]
     * @return string|array group name on $nameOnly == TRUE, array data otherwise; FALSE on error
     * @since 0.3.4
     */
    public function get_group($gid = 0, $nameOnly = false, $shared = false)
    {
        $ownerSql = (defined('_IN_PHM_CRON') || $shared) ? '' : ' AND g.owner='.$this->uid;

        if (!$gid) {
            return false;
        }
        $query = 'SELECT g.*, u.username FROM '.$this->Tbl['cal_group'].' g'.
                ' LEFT JOIN '.$this->Tbl['user'].' u ON u.uid=g.owner '.
                ' WHERE g.gid='.doubleval($gid).$ownerSql;
        $qh = $this->query($query);
        if (false === $qh || !$this->numrows($qh)) {
            return false;
        }
        if ($nameOnly) {
            $result = $this->assoc($qh);
            return $result['name'];
        }
        $group = $this->assoc($qh);
        $fSet = new DB_Controller_Foldersetting();
        $sync = $fSet->foldersetting_get('calendar', $gid, $this->uid, 'not_in_sync');
        $root = $fSet->foldersetting_get('calendar', $gid, $this->uid, 'not_in_root');
        $colour = $fSet->foldersetting_get('calendar', $gid, $this->uid, 'foldercolour');
        $group['show_in_sync'] = (is_null($sync) || !$sync) ? 1 : 0;
        $group['show_in_root'] = (is_null($root) || !$root) ? 1 : 0;
        $group['colour'] = empty($colour) ? '' : $colour;
        $group['is_shared'] = !empty($this->allShares[$gid]) ? '1' : '0';
        if ($group['type'] == 1) {
            $Cron = new DB_Controller_Cron();
            $job = $Cron->getJobs('calendar', 'remotefolders', $gid);
            $job = array_shift($job);
            $group['lastcheck'] = $job['laststart'];
            $group['checkevery'] = $job['interval'] * 60;
        }
        return $group;
    }

    /**
     * Create a new sub calendar (group / folder)
     *
     * @param string $name  Group name
     *[@param string $colour  Colour associated with the folder]
     *[@param bool  $sync  Include in syncs; Default: TRUE]
     *[@param bool  $root  Show in main calendar; Default: TRUE]
     *[@param int  $type  0 for local, 1 for URI; Default: 0]
     *[@param string $uri  Pass URI for external calendar; Deafult: NULL]
     *[@param string  $mime  MIME type of external calendar; Deafult: NULL]
     *[@param int  $check  Check interval of external calendar in minute; Default: 0]
     * @return TRUE on success, FALSE otherwise
     * @since 0.3.4
     */
    public function add_group($name = '', $colour = '', $sync = 1, $root = 1, $type = 0, $uri = null, $ext_un = null, $ext_pw = null, $mime = null, $check = 0)
    {
        $name   = $this->esc($name);
        $colour = $this->esc($colour);
        $query = 'INSERT '.$this->Tbl['cal_group'].' SET owner='.$this->uid.', `name`="'.$name.'"';
        if ($type == 1) {
            $query .= ',`type`=1'
                    .',`uri`="'.(is_null($uri) ? '' : $this->esc($uri)).'"'
                    .',`ext_un`="'.(is_null($ext_un) ? '' : $this->esc($ext_un)).'"'
                    .',`ext_pw`="'.(is_null($ext_pw) ? '' : $this->esc($ext_pw)).'"'
                    .',`mime`="'.(is_null($mime) ? '' : $this->esc($mime)).'"';
        } else {
            $query .= ',`type`=0';
        }
        $this->query($query);
        $gid = $this->insertid();

        if ($type == 1) {
            $Cron = new DB_Controller_Cron();
            $Cron->setJob('calendar', 'remotefolders', $gid, $check / 60, 0);
        }
        $fSet = new DB_Controller_Foldersetting();
        if ($sync == 0) {
            $fSet->foldersetting_set('calendar', $gid, $this->uid, 'not_in_sync', 1);
        }
        if ($root == 0) {
            $fSet->foldersetting_set('calendar', $gid, $this->uid, 'not_in_root', 1);
        }
        if ($colour != '') {
            $fSet->foldersetting_set('calendar', $gid, $this->uid, 'foldercolour', $colour);
        }
        return $gid;
    }

    /**
     * Update a given group (folder). Any parameter not to be updated might be NULL
     *
     * @param int $gid  ID of the folder (group) to update
     *[@param string $name New name of the folder; Default NULL]
     *[@param string $colour New colour of the folder; Default NULL]
     *[@param bool $sync Whether to include this folder in syncs; Default NULL]
     *[@param bool $root Whether to show this folder in the root calendar; Default NULL]
     *[@param string $uri New URI of the folder; Default NULL]
     *[@param string $ext_un External username; Default NULL]
     *[@param string $ext_pw External password; Default NULL]
     *[@param int $check New check interval of the folder; Default NULL]
     * @return TRUE on success, FALSE otherwise
     * @since 0.3.4
     */
    public function update_group($gid = 0, $name = null, $colour = null, $sync = null, $root = null, $type = null,
            $uri = null, $ext_un = null, $ext_pw = null, $mime = null, $check = null)
    {
        if (!$gid) {
            return false;
        }
        $gid = (int) $gid;

        // Am I the owner?
        if ($this->getGroupOwner($gid) != $this->uid) {
            // If not, I should have write permissions through a share
            $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $gid);
            if (empty($perms) || empty($perms['write'])) {
                // No, I haven't
                return false;
            }
        }

        $fSet = new DB_Controller_Foldersetting();
        if (!is_null($sync)) {
            if ($sync) {
                $fSet->foldersetting_del('calendar', $gid, $this->uid, 'not_in_sync');
            } else {
                $fSet->foldersetting_set('calendar', $gid, $this->uid, 'not_in_sync', 1);
            }
        }
        if (!is_null($root)) {
            if ($root) {
                $fSet->foldersetting_del('calendar', $gid, $this->uid, 'not_in_root');
            } else {
                $fSet->foldersetting_set('calendar', $gid, $this->uid, 'not_in_root', 1);
            }
        }
        if (!is_null($colour)) {
            if (!strlen($colour) ) {
                $fSet->foldersetting_del('calendar', $gid, $this->uid, 'foldercolour');
            } else {
                $fSet->foldersetting_set('calendar', $gid, $this->uid, 'foldercolour', $colour);
            }
        }
        $Cron = new DB_Controller_Cron();
        if ($type == 1) {
            $Cron->setJob('calendar', 'remotefolders', $gid, $check / 60, 0);
        } else {
            $Cron->removeJob('calendar', 'remotefolder', $gid);
        }

        $sqladd = array();
        if (!is_null($name)) {
            $sqladd[] = '`name`="'.$this->esc($name).'"';
        }
        if (!is_null($type)) {
            $sqladd[] = '`type`='.doubleval($type);
        }
        if (!is_null($uri)) {
            $sqladd[] = '`uri`="'.$this->esc($uri).'"';
        }
        if (!is_null($ext_un)) {
            $sqladd[] = '`ext_un`="'.$this->esc($ext_un).'"';
        }
        if (!is_null($ext_pw)) {
            $sqladd[] = '`ext_pw`="'.$this->esc($ext_pw).'"';
        }
        if (!is_null($mime)) {
            $sqladd[] = '`mime`="'.$this->esc($mime).'"';
        }
        if (empty($sqladd)) {
            return true;
        }

        $query = 'UPDATE '.$this->Tbl['cal_group'].' SET '.implode(',', $sqladd).' WHERE gid='.$gid.' AND owner='.$this->uid;
        return $this->query($query);
    }

    /**
     * Check, whether a group name for a ceratin user already exists
     * Input  : adb_checkfor_groupname(integer owner, string groupname)
     * @return group id if yes, FALSE otherwise
     * @since 0.3.4
     */
    public function checkfor_groupname($name = '')
    {
        $query = 'SELECT gid FROM '.$this->Tbl['cal_group'].' WHERE owner='.$this->uid.' AND name="'.$this->esc($name).'"';
        list ($result) = $this->fetchrow($this->query($query));
        return ($result) ? $result : false;
    }

    /**
     * Delete a given group from address book
     * Input  : adb_dele_group(integer group id)
     * @return TRUE on success or FALSE on failure
     * @since 0.3.4
     */
    public function dele_group($gid = 0)
    {
        // Am I the owner?
        if ($this->getGroupOwner($gid) != $this->uid) {
            // // If not, I should have write permissions through a share
            // $perms = $GLOBALS['DB']->getUserSharedFolderPermissions($this->uid, 'calendar', $gid);
            // if (empty($perms) || empty($perms['delete'])) {
               //  // No, I haven't
                return false;
            // }
        }
        // Cron austragen, wenn vorhanden
        $Cron = new DB_Controller_Cron();
        $Cron->removeJob('calendar', 'remotefolders', $gid);
        // Alle folder settings
        $fSet = new DB_Controller_Foldersetting();
        $fSet->foldersetting_del('calendar', $gid);
        // Löschen
        return ($this->query('DELETE FROM '.$this->Tbl['cal_group'].' WHERE gid='.doubleval($gid))
             && $this->query('ALTER TABLE '.$this->Tbl['cal_group'].' ORDER BY gid'));
    }

    public function createDefaultGroup()
    {
        $gid = $this->add_group($GLOBALS['WP_msg']['Standard'], 'FFA500', true, true);
        $this->query('UPDATE '.$this->Tbl['cal_event'].' SET gid='.intval($gid).' WHERE uid='.$this->uid);
        $this->query('UPDATE '.$this->Tbl['cal_task'].' SET gid='.intval($gid).' WHERE uid='.$this->uid);
    }

    /**
     * Remember the last status code and message for remote calendars
     * @param int $gid
     * @param int $status
     * @param string $message
     * @return TRUE on success or FALSE on failure
     * @since 4.4.7
     */
    public function set_remote_calendar_checked($gid, $status = 0, $message = '')
    {
        $query = 'UPDATE '.$this->Tbl['cal_group'].' SET `laststatus`='.intval($status).', `lasterror`="'.$this->esc($message).'" WHERE gid='.doubleval($gid);
        return $this->query($query);
    }

    /**
     * Return list of projects associated with a certain user
     * @param integer user id
     * @param boolean with global groups?
     * [@param string pattern
     * [@param integer num
     * [@param integer start]]])
     * @return $return array data on success, FALSE otherwise
     * @since 0.3.4
     */
    public function get_projectlist($inc_global = 0, $gid = null, $num = 0, $start = 0)
    {
        $return = array();
        $q_r = '';
        $q_l = 'SELECT `id`, `title`, `uid` FROM '.$this->Tbl['cal_project'].' WHERE 1=1';
        if (!empty($gid)) {
            $q_l .= ' AND `gid`='.doubleval($gid);
        }
        $q_l .= ' AND `uid` '.($inc_global ? ' IN('.$this->uid.',0)' : '='.$this->uid);
        if ($num > 0) {
            $q_r .= ' LIMIT ' . doubleval($start) . ',' . doubleval($num);
        }
        $qid = $this->query($q_l . ' GROUP BY id ORDER BY `uid`, `title`' . $q_r);
        while ($line = $this->assoc($qid)) {
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Retrieve information about a project
     *
     * @param int $id  ID of the folder (group) to get info about
     * @return string|array group name on $nameOnly == TRUE, array data otherwise; FALSE on error
     * @since 4.4.2
     */
    public function get_project($id = 0)
    {
        if (!$id) {
            return false;
        }
        $query = 'SELECT * FROM '.$this->Tbl['cal_project'].' WHERE uid='.$this->uid.' AND id='.doubleval($id);
        $qh = $this->query($query);
        if (false === $qh || !is_object($qh)) {
            return false;
        }
        return $this->assoc($qh);
    }

    /**
     * Create a new project
     *
     * @param array $data  Project data
     * @return TRUE on success, FALSE otherwise
     * @since 4.4.2
     */
    public function add_project($data)
    {
        $datafields = array
               ('start' => array('req' => false, 'def' => 'NULL')
               ,'end' => array('req' => false, 'def' => 'NULL')
               ,'gid' => array('req' => true)
               ,'title' => array('req' => false, 'def' => '')
               ,'location' => array('req' => false, 'def' => '')
               ,'description' => array('req' => false, 'def' => '')
               ,'importance' => array('req' => false, 'def' => '1')
               ,'completion' => array('req' => false, 'def' => '0')
               ,'status' => array('req' => false, 'def' => '0')
               ,'uuid' => array('req' => false, 'def' => basics::uuid())
               );
        foreach ($datafields as $k => $v) {
            if (!isset($data[$k])) {
                if ($v['req'] === true) {
                    return false;
                }
                $data[$k] = $v['def'];
            } else {
                $data[$k] = $this->esc($data[$k]);
            }
        }
        $query = 'INSERT '.$this->Tbl['cal_project'].' SET `uid`='.$this->uid.',`gid`='.$data['gid']
                .',`starts`='.($data['start'] == 'NULL' ? 'NULL' : '"'.$data['start'].'"')
                .',`ends`='.($data['end'] == 'NULL' ? 'NULL' : '"'.$data['end'].'"')
                .',`title`="'.$data['title'].'",`location`="'.$data['location'].'"'
                .',`description`="'.$data['description'].'",`uuid`="'.$data['uuid'].'"'
                .',`importance`='.doubleval($data['importance']).',`completion`='.doubleval($data['completion'])
                .',`status`='.doubleval($data['status']);
        if (!$this->query($query)) {
            return false;
        }
        $newId = $this->insertid();
        // Make sure, the end of an event is NOT before its beginning
        $this->query('UPDATE '.$this->Tbl['cal_task'].' SET `ends`=`starts` WHERE `ends`<`starts` AND id='.$newId);
        return $newId;
    }

    /**
     * Update a project
     * @param array $data Data to update
     * @return boolean TRUE on success, FALSE otherwise
     * @since 4.4.2
     */
    public function update_project($data)
    {
        if (!isset($data['id']) || !$data['id']) {
            return false;
        }
        $query = 'UPDATE '.$this->Tbl['cal_project'].' SET lastmod=NOW()';
        foreach (array('start' => 'starts', 'end' => 'ends', 'title' => 'title', 'location' => 'location'
               ,'description' => 'description', 'importance' => 'importance', 'gid' => 'gid'
               ,'completion' => 'completion', 'status' => 'status'
               ) as $k => $v) {
            if (!isset($data[$k])) {
                continue;
            }
            $query .= ',`'.$v.'`='.(('NULL' == $data[$k] || is_null($data[$k])) ? 'NULL' : '"'.$this->esc($data[$k]).'"');
        }
        $this->query($query.' WHERE uid='.$this->uid.' AND id='.$data['id']);
        return true;
    }

    /**
     * Delete a given project
     * @param  int $id  ID of the record to delete
     * @return TRUE on success or FALSE on failure
     * @since 0.3.4
     */
    public function delete_project($id = 0)
    {
        $query = 'SELECT 1 FROM '.$this->Tbl['cal_project'].' WHERE `id`='.doubleval($id).' AND `uid`='.$this->uid.' LIMIT 1';
        list ($result) = $this->fetchrow($this->query($query));
        if (!$result) {
            return false;
        }
        return ($this->query('DELETE FROM '.$this->Tbl['cal_project'].' WHERE id='.doubleval($id))
             && $this->query('ALTER TABLE '.$this->Tbl['cal_project'].' ORDER BY id ASC'));
    }

    /**
     * Retrieves a list of holidays within a given date range.
     * If neither start nor end are given, all stored holidays are retrieved, which can be
     * useful for exporting. If only start is given, you'll get returned, whether this day
     * is a holiday, and if so the name of it. If both arguments are given, all holidays
     * with their name in this date range are retrieved.
     *
     * The third parameter is only obeyed, when parameters one and two are NOT specified!
     *
     *[@param string $start MySQL date of the first day of the range]
     *[@param string $end MySQL date of the last day of the range]
     *[@param int|array $limit Either an intege for "LIMIT x" or an array for "LIMIT x,y"]
     * @return array dates, which are holidays
     * @since 4.0.9
     * @todo Add support for recurring holidays. These need to be selected in the form mm-dd, but
     *   be returned with the correct year stamped in front. This might easily be done when querying
     *   a single date or a date range less than one year, but when spanning over more than one year ...
     */
    public function daterange_getholidays($start = false, $end = false, $limit = null)
    {
        $return = array();
        $mode = 0;
        if (!$start && !$end) {
            $query = 'SELECT `hid`, `hname`,`hdate` FROM '.$this->Tbl['cal_holiday'].' WHERE `uid` IN (0,'.$this->uid.') ORDER BY `hdate`ASC';
            if (is_array($limit)) {
                $query .= ' LIMIT '.doubleval($limit[0]).','.doubleval($limit[1]);
            } elseif (!is_null($limit) && 0 < $limit) {
                $query .= ' LIMIT '.doubleval($limit);
            }
            $mode = 1;
        } elseif (!$end) {
            $query = 'SELECT `hname`, `hdate` FROM '.$this->Tbl['cal_holiday'].' WHERE `uid` IN (0,'.$this->uid.') AND `hdate`="'.$this->esc($start).'"';
        } else {
            $query = 'SELECT `hname`, `hdate` FROM '.$this->Tbl['cal_holiday']
                    .' WHERE `uid` IN (0,'.$this->uid.') AND `hdate`>="'.$this->esc($start).'" AND `hdate`<="'.$this->esc($end).'"';
        }
        $res = $this->query($query);
        while ($line = $this->assoc($res)) {
            if (1 == $mode) {
                $return[$line['hid']] = array($line['hdate'], $line['hname']);
            } else {
                $return[$line['hdate']] = $line['hname'];
            }
        }
        return $return;
    }

    /**
     * Add a holiday to the database
     *
     * @param string $date MySQL date of the holiday
     * @param string $name Descriptive name of it (may be localized)
     * @param bool $recurring Whether this is a holiday always celebrated at the same date of year
     * @return bool TRUE, if adding succeeded
     * @since 4.0.9
     */
    public function add_holiday($date, $name, $recurring = false, $is_global = false)
    {
        $query = 'INSERT INTO '.$this->Tbl['cal_holiday'].' SET `hname`="'.$this->esc($name).'"'
                .', `hdate`="'.$this->esc($date).'", `recurring`="'.($recurring ? 1 : 0).'"'
                .($is_global === true ? ',uid=0' : ',uid='.doubleval($this->uid));
        return $this->query($query);
    }

    /**
     * Update a holiday
     *
     * @param int $id  Index of the entry in the database (primary key)
     * @param string $date MySQL date of the holiday
     * @param string $name Descriptive name of it (may be localized)
     * @param bool $recurring Whether this is a holiday always celebrated at the same date of year
     * @return bool TRUE, if adding succeeded
     * @since 4.0.9
     */
    public function update_holiday($id, $date, $name, $recurring)
    {
        $query = 'UPDATE '.$this->Tbl['cal_holiday'].' SET `hname`="'.$this->esc($name).'"'
                .', `hdate`="'.$this->esc($date).'", `recurring`="'.($recurring ? 1 : 0).'"'
                .' WHERE `hid`='.doubleval($id).' AND uid='.doubleval($this->uid);
        return $this->query($query);
    }

    public function delete_holiday($id)
    {
        return $this->query('DELETE FROM '.$this->Tbl['cal_holiday'].' WHERE `hid`='.doubleval($id).' AND uid='.doubleval($this->uid));
    }

    public function empty_holidays($group = null)
    {
        return $this->query('DELETE FROM '.$this->Tbl['cal_holiday'].' WHERE uid='.doubleval($this->uid));
    }

    public function quota_getnumberofrecords($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['cal_event'].' WHERE uid='.$this->uid;
            list ($records) = $this->fetchrow($this->query($query));
            return $records;
        }
        $query = 'SELECT count(distinct uid), count(*) FROM '.$this->Tbl['cal_event'].' WHERE uid>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, count(uid) moep FROM '.$this->Tbl['cal_event'].' WHERE uid>0 GROUP BY uid ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    public function quota_getnumberoftasks($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['cal_task'].' WHERE uid='.$this->uid;
            list ($records) = $this->fetchrow($this->query($query));
            return $records;
        }
        $query = 'SELECT count(distinct uid), count(*) FROM '.$this->Tbl['cal_task'].' WHERE uid>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, count(uid) moep FROM '.$this->Tbl['cal_task'].' WHERE uid>0 GROUP BY uid ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    public function quota_groupsnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['cal_group'].' WHERE owner='.doubleval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct owner), count(*) FROM '.$this->Tbl['cal_group'].' WHERE owner>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT owner, count(owner) moep FROM '.$this->Tbl['cal_group'].' WHERE owner>0 GROUP BY owner ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }
}
