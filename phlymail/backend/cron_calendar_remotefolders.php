<?php
/**
 * Read and parse remote calendars
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2011-2015 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.7 2015-03-16 
 */

require_once $GLOBALS['_PM_']['path']['handler'] . '/calendar/functions.php';

class cron_calendar_remotefolders
{
    public function __construct($cronjob)
    {
        $this->job = $cronjob;
    }

    public function Run()
    {
        $job = &$this->job;

        $cDB = new handler_calendar_driver(0);
        $group = $cDB->get_group($job['item']);
        if (empty($group)) {
            $Cron = new DB_Controller_Cron();
            $Cron->removeJob('calendar', 'remotefolders', $job['item']);
            return;
        }

        $file = externalCalendarRead($group, $errno, $errstr);
        if (false === $file) {
            $cDB->set_remote_calendar_checked($group['gid'], $errno, $errstr);
            return;
        }

        $PHM_CAL_IM_DO = 'import';
        $PHM_CAL_IM_UID = $group['owner'];
        $PHM_CAL_IM_GROUP = $job['item'];
        $PHM_CAL_IM_FORMAT = 'ICS';
        $PHM_CAL_IM_SYNC = true;
        $PHM_CAL_IM_FILE = $file;
        $PHM_CAL_NO_OUTPUT = true;

        // whoa, hackish
        $_phM_privs = array('all' => 0, 'calendar_export_events' => 0, 'calendar_import_events' => 1);

        require $GLOBALS['_PM_']['path']['handler'] . '/calendar/exchange.php';

        vecho('Imported ' . $imported . ' events for group #' . $group['gid']);
    }
}
