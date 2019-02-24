<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Calendar
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version
 */
class handler_calendar_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        if (file_exists($_PM_['path']['handler'] . '/calendar/lang.' . $WP_msg['language'] . '.php')) {
            require($_PM_['path']['handler'] . '/calendar/lang.' . $WP_msg['language'] . '.php');
        } else {
            require($_PM_['path']['handler'] . '/calendar/lang.de.php');
        }
        $this->WP_msg = $WP_msg;
        $this->_PM_ = $_PM_;
        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (!isset($_SESSION['phM_uniqe_handlers']['calendar']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['calendar']['i18n'] = $WP_msg['CalCalendar'];
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.calendar.tpl');
        // Permissions
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_event']) {
            $tpl->assign_block('has_new_event');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_task']) {
            $tpl->assign_block('has_new_task');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_export_events'] || $_SESSION['phM_privs']['calendar_import_events']) {
            $tpl->assign_block('has_exchange');
        }
        $tpl->assign(array
                ('alert_url' => PHP_SELF.'?l=alert_event&h=calendar&'.give_passthrough().'&eid='
                ,'year' => date('Y')
                ,'msg_newevent' => $WP_msg['CalNewEvt']
                ,'msg_newtask' => $WP_msg['TskNewTask']
                ,'msg_setup_calendar' => $WP_msg['CalCalendar']
                ,'msg_killconfirm' => $WP_msg['killJSconfirm']
                ,'head_reminder' => $WP_msg['CalEvtReminder']
                ));
        return $tpl;
    }

    /**
     * Retrieves the items to show in the setup menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_setup_menu()
    {
        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'calendar.png'
                        ,'name'      => $WP_msg['CalCalendar']
                        ,'localpath' => 'l=setup'
                        )
                );
    }

    /**
     * Retrieves the items to show in the "new" menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_new_menu()
    {
        $WP_msg = &$this->WP_msg;
        $return = array();
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_event']) {
            $return[] = array
                    ('icon'      => 'calendar.png'
                    ,'name'      => $WP_msg['CalNewEvt']
                    ,'localpath' => 'l=edit_event'
                    );
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['calendar_add_task']) {
            $return[] = array
                    ('icon'      => 'tasks.png'
                    ,'name'      => $WP_msg['TskNewTask']
                    ,'localpath' => 'l=edit_task'
                    );
        }
        return $return;
    }

    /**
     * Retrieves the items to show in the exhcange menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_exchange_menu()
    {
        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['calendar_export_events']
                && !$_SESSION['phM_privs']['calendar_import_events']) {
            return array();
        }

        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'calendar.png'
                        ,'name'      => $WP_msg['CalCalendar']
                        ,'localpath' => 'l=exchange'
                        )
                );
    }
}
