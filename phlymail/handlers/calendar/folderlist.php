<?php
/**
 * Returning the list of folders (calendars)
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Calendar
 * @copyright 2004-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.5 2016-01-25
 */
class handler_calendar_folderlist
{
    public function __construct(&$_PM_, $mode)
    {
    	if (file_exists($_PM_['path']['handler'].'/calendar/lang.'.$GLOBALS['WP_msg']['language'].'.php')) {
    		require $_PM_['path']['handler'].'/calendar/lang.'.$GLOBALS['WP_msg']['language'].'.php';
    	} else {
    		require $_PM_['path']['handler'].'/calendar/lang.de.php';
    	}
        $this->cDB = new handler_calendar_driver($_SESSION['phM_uid']);
        $this->_PM_ = $_PM_;
    	$this->WP_msg = $WP_msg;
    }

    public function get()
    {
        $myGroups = $this->cDB->get_grouplist(false);
        if (empty($myGroups)) {
            // As of 4.5 we need a default group for the permissions to work
            // If there's no default group, it is created here
            $this->cDB->createDefaultGroup();
        }

        $return = array();
        foreach ($this->cDB->get_grouplist(true) as $k => $v) {
            $return[] = array(
                    'path' => $v['gid'],
                    'icon' => ':calendar',
                    'foldername' => $v['name'],
                    'type' => 2,
                    'subdirs' => false
                );
        }
        return array(0 => array(
                'path' => 0,
                'icon' => ':calendar',
                'foldername' => $this->WP_msg['CalCalendar'],
                'type' => 2,
                'subdirs' => (!empty($return)) ? $return : false
            ));
    }
}
