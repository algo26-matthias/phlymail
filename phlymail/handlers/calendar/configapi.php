<?php
/**
 * Offering API calls for the Config interface
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: Calendar
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.3 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_calendar_configapi
{
    public $perm_handler_available = 'calendar_see_calendar';

    /**
     * Constructor method, this special constructor also attempts to create the required
     * docroot of the email storage for the given user
     *
     * @param  array reference  public settings structure
     * @param  int  ID of the user to perform the operation for
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function __construct($_PM_, $uid)
    {
        $this->_PM_ = $_PM_;
		$this->cDB = new handler_calendar_driver($uid);
    }

    /**
     * Returns errors which happened
     * @param void
     * @return string error message(s)
     * @since 0.0.1
     */
    public function get_errors()
    {
        return $this->errortext;
    }

    /**
     * Called on installing the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.1
     */
    public function handler_install()
    {
    	return $this->cDB->handler_install();
    }

    /**
     * Called on uninstalling the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.1
     */
    public function handler_uninstall()
    {
    	return $this->cDB->handler_uninstall();
    }

    /**
     * This method allows the Config to query the available actions of this handlers
     * for managing access permissions to them. This allows for user level access permissions
     * to anything functional phlyMail offers - even complete readonly access and disabling
     * of single functions in the frontend (sending emails, adding profiles and stuff).
     * @param void
     * @return array Key => Translated action name
     * @since 1.0.0
     */
    public function get_perm_actions($lang = 'en')
    {
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir($this->_PM_['path']['handler'].'/calendar');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$lang.'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['handler'].'/calendar/'.$f);
                break;
            }
        }
        return array
                ('see_calendar' => $WP_msg['PermSeeCalendar']
                ,'add_event' => $WP_msg['PermAddEvent']
                ,'update_event' => $WP_msg['PermUpdateEvent']
                ,'delete_event' => $WP_msg['PermDeleteEvent']
                ,'import_events' => $WP_msg['PermImportEvent']
                ,'export_events' => $WP_msg['PermExportEvent']
                ,'add_task' => $WP_msg['PermAddTask']
                ,'update_task' => $WP_msg['PermUpdateTask']
                ,'delete_task' => $WP_msg['PermDeleteTask']
                ,'import_tasks' => $WP_msg['PermImportTask']
                ,'export_tasks' => $WP_msg['PermExportTask']
                ,'add_group' => $WP_msg['PermAddGroup']
                ,'edit_group' => $WP_msg['PermEditGroup']
                ,'delete_group' => $WP_msg['PermDeleteGroup']
                );
    }

    /**
     * This method delivers a list of quota settings, this handler defines. The list contains the
     * internal identifier for this definition, the human readable name of it and a few helpful bits
     * of information, so that the Config knows, which types of values are allowed.
     *
     * This method queries global values!
     *
     * @param string $lang The language of the Config interface for the display name of the setting
     * @return array
     * @since 0.0.8
     */
    public function get_quota_definitions($lang = 'en')
    {
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir($this->_PM_['path']['handler'].'/calendar');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$lang.'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['handler'].'/calendar/'.$f);
                break;
            }
        }
        // Give definitions
        return array
                ('number_appointments' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaNumberAppointments']
                        ,'query' => true // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                ,'number_tasks' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaNumberTasks']
                        ,'query' => true // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                ,'number_groups' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaNumberGroups']
                        ,'query' => true // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                );
    }

    /**
     * This method allows Config to query the current usage for a specific definition and
     * a specific user
     *
     * @param string $what The definition to query for the current user
     * @return mixed The current usage for that quota definition
     * @since 0.0.8
     */
    public function get_quota_usage($what, $stats = false)
    {
        switch ($what) {
            case 'number_appointments':
                return $this->cDB->quota_getnumberofrecords($stats);
                break;
            case 'number_tasks':
                return $this->cDB->quota_getnumberoftasks($stats);
                break;
            case 'number_groups':
                return $this->cDB->quota_groupsnum($stats);
                break;
            default: return false;
        }
    }
}
