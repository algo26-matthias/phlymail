<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2012-12-14 
 */
class handler_email_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        $this->_PM_ = $_PM_;
        $this->WP_msg = $WP_msg;
        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (empty($_SESSION['phM_uniqe_handlers']['email']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['email']['i18n'] = 'Emails';
        }
    }

    public function get()
    {
        $_PM_ = &$this->_PM_;
        $WP_msg = &$this->WP_msg;
        $tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.email.tpl');
        if (isset($_PM_['customsize']['email_previewheight']) && $_PM_['customsize']['email_previewheight']
                && (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows'])) {
            $tpl->fill_block('customheight', 'height', $_PM_['customsize']['email_previewheight']);
        }
        $passthru = give_passthrough(1);
        $tpl->assign(array
                ('msg_mailbox' => $WP_msg['all']
                ,'msg_profile' => $WP_msg['profile']
                ,'msg_mail' => $WP_msg['mail']
                ,'msg_filters' => $WP_msg['FilterTopFilterRules']
                ,'msg_getmessages' => $WP_msg['MainGetMsg']
                ,'msg_newmail' => phm_addcslashes($WP_msg['WorkerNewMail'], "'")
                ,'msg_getmessages' => phm_addcslashes($WP_msg['MainGetMsg'])
                ,'msg_dlingmessages' => phm_addcslashes($WP_msg['DownloadingMessages'])
                ,'msg_updatingindex' => phm_addcslashes($WP_msg['UpdatingIndex'])
                ,'checkmail_url' => PHP_SELF.'?l=worker&h=email&'.$passthru.'&what=recheck'
                ,'fetcher_url' => PHP_SELF.'?h=email&l=fetcher.run&'.$passthru
                ,'mailops_url' => PHP_SELF.'?l=worker&h=email&'.$passthru.'&what=mail_'
                ));
        // Allow direct fetching of individual profiles
        $Acnt = new DB_Controller_Account();
        $t_p = $tpl->get_block('fetchprof');
        foreach ($Acnt->getAccountIndex($_SESSION['phM_uid'], true) as $pid => $data) {
            $t_p->assign(array
                    ('msg_mailbox' => addcslashes($data['accname'], "'")
                    ,'handler' => $_PM_['handler']['name']
                    ,'passthrough' => $passthru
                    ,'pid' => $pid
                    ));
            $tpl->assign('fetchprof', $t_p);
            $t_p->clear();
        }
        $EBP = new handler_email_boilerplates($_SESSION['phM_uid']);
        if (isset($EBP->enabled) && $EBP->enabled) {
            $tpl->fill_block('boilerplates', array
                    ('msg_boilerplates' => addcslashes($WP_msg['BPlateMenu'], "'")
                    ,'handler' => $_PM_['handler']['name']
                    ,'passthrough' => $passthru
                    ));
            if (/* shall be explicitly set $_SESSION['phM_privs']['all'] || */ !empty($_SESSION['phM_privs']['email_edit_global_boilerplates'])) {
                $tpl->fill_block('global_boilerplates', array
                        ('msg_global_boilerplates' => addcslashes($WP_msg['BPlateMenuGlobal'], "'")
                        ,'handler' => $_PM_['handler']['name']
                        ,'passthrough' => $passthru
                        ));
            }
        }
        // Global filtering will be popped in a bit later, we are working on a failsafe methodology
        // if (/* shall be explicitly set $_SESSION['phM_privs']['all'] || */ $_SESSION['phM_privs']['email_edit_global_filters']) {
        //    $tpl->fill_block('global_filters', array
        //            ('msg_global_filters' => addcslashes($WP_msg['FilterGlobalFilterRules'], "'")
        //            ,'handler' => $_PM_['handler']['name']
        //            ,'passthrough' => $passthru
        //            ));
        // }
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
        $return = array();
        /* Disabled for now, mobile interface not completed
        $return = array(
                array(
                        'icon'      => 'emailfilters.png'
                        ,'name'      => $WP_msg['FilterTopFilterRules']
                        ,'localpath' => 'l=setup&mod=filters'
                        )
                );
        // Global filtering will be popped in a bit later, we are working on a failsafe methodology
        // if (/ * shall be explicitly set $_SESSION['phM_privs']['all'] || * / $_SESSION['phM_privs']['email_edit_global_filters']) {
        //     $return[] = array
        //             ('icon'      => 'emailfilters.png'
        //             ,'name'      => $WP_msg['FilterGlobalFilterRules']
        //             ,'localpath' => 'l=setup&mod=filters&global=1'
        //             );
        // }
        $EBP = new handler_email_boilerplates($_SESSION['phM_uid']);
        if (isset($EBP->enabled) && $EBP->enabled) {
            $return[] = array
                    ('icon'      => 'boilerplate.png'
                    ,'name'      => $WP_msg['BPlateMenu']
                    ,'localpath' => 'l=setup&mod=boilerplates'
                    );
            if (/ * shall be explicitly set $_SESSION['phM_privs']['all'] || * / $_SESSION['phM_privs']['email_edit_global_boilerplates']) {
                $return[] = array
                        ('icon'      => 'boilerplate.png'
                        ,'name'      => $WP_msg['BPlateMenuGlobal']
                        ,'localpath' => 'l=setup&mod=boilerplates&global=1'
                        );
            }
        } */
        return $return;
    }

    /**
     * Retrieves the items to show in the "new" menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_new_menu()
    {
        return array();
    }

    /**
     * Retrieves the items to show in the exhcange menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_exchange_menu()
    {
        return array();
    }
}
