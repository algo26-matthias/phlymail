<?php
/**
 * Cronjob Controller
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.7 2015-12-09
 */
class DB_Controller_Cron extends DB_Controller
{
    /**
     * Constructor
     * Read the config and get an instance of the DB singleton
     */
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['cron'] = $this->DB['db_pref'] . 'core_crontab';
    }

    /**
     * Returns a list of jobs, which are due right now.
     * Conditions in a nutshell:
     * a) Process never ran
     * b) Process is due
     * c) Process is marked as at-once
     * c) But it is not running right now
     *
     * @return array
     */
    public function getDueJobs($limit = null)
    {
        $return = array();
        // Try to reset crashed processes
        $this->query('UPDATE '.$this->Tbl['cron'].' SET pid=NULL, laststop=NOW() WHERE pid IS NOT NULL AND laststart IS NOT NULL AND laststop IS NOT NULL AND laststart > laststop AND DATE_ADD(`laststart`, INTERVAL 8 HOUR) <= NOW()');

        // Take the number of already running processes into account
        if (!is_null($limit)) {
            $qid = $this->query('SELECT COUNT(*) FROM '.$this->Tbl['cron'].' WHERE pid IS NOT NULL AND DATE_ADD(`laststart`, INTERVAL 8 HOUR) >= NOW()');
            if (false !== $qid && $this->numrows($qid)) {
                list ($dbLimit) = $this->fetchrow($qid);
                if ($dbLimit >= $limit) {
                    return $return;
                } elseif ($dbLimit > 0) {
                    $limit -= $dbLimit;
                }
            }
        }
        // Now fetch the due processes
         $qid = $this->query('SELECT * FROM '.$this->Tbl['cron']
                .' WHERE `pid` IS NULL AND (`runonce`="0" OR (`runonce`="1" AND `laststart` IS NULL)) AND (`laststart` IS NULL'
                .' OR ( (`at_once`="1" OR DATE_ADD(`laststart`, INTERVAL `interval` MINUTE) <= NOW())'
                .' AND 
                    ( 
                        (`laststart` IS NULL AND `laststop` IS NULL) 
                        OR 
                        (`laststop` IS NOT NULL AND  `laststart`<=`laststop`) 
                        OR 
                        (`laststart` IS NOT NULL AND `laststop` IS NOT NULL AND  `laststart`>`laststop` AND DATE_ADD(`laststart`, INTERVAL 8 HOUR) <= NOW())))
                     )'
                .' ORDER BY `at_once` DESC, `prio` DESC, `laststop` ASC, `laststart` ASC'
        		.(!is_null($limit) && $limit > 0 ? ' LIMIT '.intval($limit) : '')
        		);
        if ($qid) {
            while ($line = $this->assoc($qid)) {
                $return[] = $line;
            }
        }
        return $return;
    }

    /**
     * Adds (or updates) a job in the DB
     *
     * The scheduling parameters are similar to those of the UNIX cron tab, as such, that "*", "1,2,3,4" and "/5" are supported
     *
     * @param string $handler  Handler name
     * @param string $job   Job name
     * @param int $item  Item ID
     * @param string $interval  Running interval, min. 1min, pass strings like "14h", "5d" for shorthands
     * @param int $prio  Priority, 100 > 0; Default: 0 (completely unrelated)
     * @param mixed $reference  Reference time. This value is not in use right now
     * @param bool $runonce  Set TRUE for jobs to be executed exactly once
     * @return bool
     */
    public function setJob($handler, $job, $item = null, $interval = '1', $prio = 0, $reference = null, $runonce = false)
    {
        $sql = 'REPLACE INTO '.$this->Tbl['cron']
                .' SET `handler`="'.$this->esc($handler).'",`job`="'.$this->esc($job).'"'
                .',`item`='.(is_null($item) ? 'NULL' : intval($item))
                .',`interval`="'.$this->esc($this->parseScheduleFormat($interval)).'"'
                .',`prio`="'.abs(intval($prio)).'"'
                .',`reference_time`='.(is_null($reference) ? 'NOW()' : '"'.$this->esc($reference).'"')
                .',`runonce`="'.(empty($runonce) ? 0 : 1).'"';
        return $this->query($sql);
    }

    /**
     * Checks for the existance of one (or more) job(s).
     *
     * @param string $handler
     *[@param string $job]
     *[@param int $item]
     * @return boolean
     */
    public function jobExists($handler, $job = null, $item = null)
    {
        $sql = 'SELECT 1 FROM '.$this->Tbl['cron'].' WHERE `handler`="'.$this->esc($handler).'"'
                .(!is_null($job) ? ' AND `job`="'.$this->esc($job).'"' : '')
                .(!is_null($job) && !is_null($item) ? ' AND `item`='.intval($item) : '');
        $qid = $this->query($sql);
        if ($qid) {
            return ($this->numrows($qid)) ? true : false;
        }
        return false;
    }

    /**
     * Retrieves info for one (or more) job(s).
     *
     * @param string $handler
     *[@param string $job]
     *[@param int $item]
     * @return boolean
     */
    public function getJobs($handler = null, $job = null, $item = null)
    {
        $sql = 'SELECT * FROM '.$this->Tbl['cron'].' WHERE 1=1'
                .(!is_null($handler) ? ' AND `handler`="'.$this->esc($handler).'"' : '')
                .(!is_null($job) ? ' AND `job`="'.$this->esc($job).'"' : '')
                .(!is_null($job) && !is_null($item) ? ' AND `item`='.intval($item) : '');
        $qid = $this->query($sql);
        if ($qid) {
            $return = array();
            while ($line = $this->assoc($qid)) {
                $return[] = $line;
            }
            return $return;
        }
        return false;
    }

    public function markJobAtOnce($handler = null, $job = null, $item = null)
    {
        return $this->markJobX($handler, $job, $item, '`at_once`="1"');
    }

    public function markJobRunning($handler = null, $job = null, $item = null, $pid = null)
    {
        $sql = '`at_once`="0",`laststart`=NOW()';
        if (!is_null($pid) && $pid > 0) {
            $sql .= ',pid='.intval($pid);
        }
        return $this->markJobX($handler, $job, $item, $sql);
    }

    public function markJobDone($handler = null, $job = null, $item = null)
    {
        return $this->markJobX($handler, $job, $item, '`laststop`=NOW(),pid=NULL');
    }

    public function markJobRunOnce($handler = null, $job = null, $item = null)
    {
        return $this->markJobX($handler, $job, $item, '`runonce`="1"');
    }

    protected function markJobX($handler, $job, $item, $x)
    {
        $sql = 'UPDATE '.$this->Tbl['cron'].' SET '.$x.' WHERE 1=1'
                .(!is_null($handler) ? ' AND `handler`="'.$this->esc($handler).'"' : '')
                .(!is_null($job) ? ' AND `job`="'.$this->esc($job).'"' : '')
                .(!is_null($job) && !is_null($item) ? ' AND `item`='.intval($item) : '');
        return $this->query($sql);
    }

    /**
     * Removes either an item for a job, a whole job or all jobs for a handler form the cron table.
     *
     * @param string $handler  Handler name; Mandatory
     * @param string $job   Job name; optional, left empty: remove all the jobs for that handler
     * @param int $item  Item ID; optional, job given and ID blank: all IDs for the job are removed
     * @return bool
     */
    public function removeJob($handler, $job = null, $item = null)
    {
    	if (is_array($handler) && isset($handler['handler'])) {
    		if (isset($handler['item']) && !is_null($handler['item'])) {
    			$item = $handler['item'];
    		}
    		if (isset($handler['job']) && !is_null($handler['job'])) {
    			$job = $handler['job'];
    		}
    		$handler = $handler['handler'];
    	}
        $sql = 'DELETE FROM '.$this->Tbl['cron'].' WHERE `handler`="'.$this->esc($handler).'"'
                .(!is_null($job) ? ' AND `job`="'.$this->esc($job).'"' : '')
                .(!is_null($job) && !is_null($item) ? ' AND `item`='.intval($item) : '');
        return $this->query($sql);
    }

    /**
     * Reads the timestamp of the latest run job, if any. This allows the Config to check,
     * whether the cron system is running at all and to inform the user, if not.
     *
     * @return int|null|false; Timestamp of last run, if any; NULL if none, FALSE on DB failure
     */
    public function getHeartbeat()
    {
        $sql = 'SELECT CONVERT_TZ(MAX(`laststart`), SUBSTRING(REPLACE(CONCAT("+", SEC_TO_TIME(TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP(), NOW()))), "+-", "-"), 1, 6), "+00:00") `lastrun` FROM '.$this->Tbl['cron'];
        $qid = $this->query($sql);
        if ($qid && $this->numrows($qid)) {
            $line = $this->assoc($qid);
            return $line['lastrun'];
        }
        return false;
    }

    /**
     * Internal method to centralize parsing of the permitted and understood values of schedules for the crontab.
     * Right now we read:
     * - a single asterisk as meaning "for alle legal values", i.e. every minute
     * - a comma spearated list of values, e.g. 1,2,15,25,55
     * - an asterisk followed by slash followed by an integer, i.e. every nth value (* / 5 [without spaces] means every fifth e.g. minute
     *
     * @param string $field  The fields value to parse
     * @return string  The cleaned up (and translated) value
     */
    protected function parseScheduleFormat($field)
    {
        if ($field == intval($field)) {
            return $field;
        }
        if (preg_match('/^[0-9]+(m|min|h|d|w|mon|q|y)$/i', $field, $found)) {
            switch (strtolower($found[1])) {
                case 'm':  // Minutes; the default anyway
                case 'min':
                    $field *= 1; break;
                case 'h': // Hours
                    $field *= 60; break;
                case 'd': // Days
                    $field *= 24*60; break;
                case 'w': // Weeks
                    $field *= 7*24*60; break;
                case 'mon': // Months
                    $field *= 30.4375*24*60; break; // Well... this is not perfect
                case 'q': // Quarter, a.k.a. 3 months
                    $field *= 91.3125*24*60; break; // This isn't, either
                case 'y': // Years
                    $field *= 365.25*24*60; break; // And this...
            }
            return floor($field);
        }
        return 1;
    }
}

