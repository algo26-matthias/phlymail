<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Core
 * @copyright 2001-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.5 2016-11-06
 */
class handler_core_topbuttonbar
{
    protected $smsactive = false;
    protected $faxactive = false;

    public function __construct(&$_PM_)
    {
        global $WP_msg;
        $this->WP_msg = $WP_msg;
        $this->_PM_ = $_PM_;
        if (!isset($_SESSION['phM_uniqe_handlers']['core']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['core']['i18n'] = 'Core';
        }
        $this->smsactive = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
        if ($this->smsactive) {
            $this->smsactive = (isset($_PM_['core']['sms_active']) && $_PM_['core']['sms_active']);
            $this->faxactive = ((isset($_PM_['core']['fax_default_active']) && $_PM_['core']['fax_default_active'])
                    || (isset($_PM_['core']['fax_active']) && $_PM_['core']['fax_active']));
            $gwoptions = parse_ini_file($_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'].'/settings.ini.php');
            if (!isset($gwoptions['has_fax']) || !$gwoptions['has_fax']) {
                $this->faxactive = false;
            }
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $passthru = give_passthrough(1);

        $tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.core.tpl');

        // Permissions
        if ($_SESSION['phM_privs']['all'] || ($_SESSION['phM_privs']['core_new_email'] && $_SESSION['phM_privs']['email_see_emails'])) {
            $tpl->assign_block('has_new_email');
        }
        if ($this->smsactive
                && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_sms'])) {
            $tpl->assign_block('smsactive');
        }
        if ($this->faxactive
                && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_fax'])) {
            $tpl->assign_block('faxactive');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_setup_settings']) {
            $tpl->assign_block('usersetup');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_add_profile'] || $_SESSION['phM_privs']['email_edit_profile']) {
            $tpl->assign_block('profiles');
        }
        if (!empty($_PM_['core']['showlinkconfig'])) {
            $tpl->assign_block('showlinkconfig');
        }
        if (!empty($_PM_['core']['logincheckupdates'])) {
            $tpl->fill_block('logincheckupdates', array
                    ('url_logincheckupdates' => PHP_SELF.'?l=worker&h=core&'.$passthru.'&what=logincheckupdates'
                    ,'head_update' => $WP_msg['CoreChkUpdHead']
                    ,'title_update' => $WP_msg['CoreChkUpdTitle']
                    ,'about_update' => $WP_msg['CoreChkUpdBody']
                    ));
        }

        // Handle MOTD here
        if (!empty($_PM_['core']['show_motd'])
                && (file_exists($_PM_['path']['conf'].'/global.MOTD.wpop') || !empty($_PM_['core']['MOTD']))) {
            if (!isset($_SESSION['phM_motd_shown'])) {
                $MOTD = !empty($_PM_['core']['MOTD']) ? $_PM_['core']['MOTD'] : file_get_contents($_PM_['path']['conf'].'/global.MOTD.wpop');
                $_SESSION['phM_motd_shown'] = true;
                $tpl->assign('loginmessage', phm_addcslashes(nl2br(phm_stripslashes(str_replace('$1', $_SESSION['phM_username'], $MOTD)))));
            }
        }

        $tpl->assign(array
                ('handler' => $_PM_['handler']['name']
                ,'msg_about' => $WP_msg['About']
                ,'msg_newemail' => $WP_msg['NewEmail']
                ,'msg_newsms' => $WP_msg['NewSMS']
                ,'msg_newfax' => $WP_msg['NewFax']
                ,'msg_logout' => $WP_msg['alt_logout']
                ,'msg_gotoconfig' => $WP_msg['CoreGoToConfig']
                ,'msg_setup_programme' => $WP_msg['SetupProgramme']
                ,'msg_setup_pop3_accounts' => $WP_msg['accounts']
                ,'msg_showfavfolderss' => $WP_msg['CoreShowFavourites']
                ,'msg_showfolderlist' => $WP_msg['CoreShowFolderlist']
                ,'msg_shownamepane' => $WP_msg['CoreShowNamePane']
                ,'checkquota_url' => PHP_SELF.'?l=worker&h=core&'.$passthru.'&what=get_quota_state'
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
        $return = array();
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_setup_settings']) {
            $return[] = array
                    ('icon'      => 'setup_men.png'
                    ,'name'      => $WP_msg['SetupProgramme']
                    ,'localpath' => 'l=setup&mode=general'
                    );
        }
        /* Disabled for now, mobile interface not completed
        if ($_SESSION['phM_privs']['all']
                || $_SESSION['phM_privs']['email_add_profile']
                || $_SESSION['phM_privs']['email_edit_profile']) {
            $return[] = array
                    ('icon'      => 'email.png'
                    ,'name'      => $WP_msg['accounts']
                    ,'localpath' => 'l=setup&mode=profiles'
                    );
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
        $WP_msg = &$this->WP_msg;
        $return = array();
        if ($_SESSION['phM_privs']['all']
                || ($_SESSION['phM_privs']['core_new_email'] && $_SESSION['phM_privs']['email_see_emails'])) {
            $return[] = array
                    ('icon'      => 'email.png'
                    ,'name'      => $WP_msg['NewEmail']
                    ,'localpath' => 'l=compose_email'
                    );
        }
        if ($this->smsactive
                && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_sms'])) {
            $return[] = array
                    ('icon'      => 'sms.png'
                    ,'name'      => $WP_msg['NewSMS']
                    ,'localpath' => 'l=compose_sms'
                    );
        }
        if ($this->faxactive
                && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_fax'])
                && 1 == 2) { # FIXME not yet ready for mobile
            $return[] = array
                    ('icon'      => 'fax.png'
                    ,'name'      => $WP_msg['NewFax']
                    ,'localpath' => 'l=compose_fax'
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
        return array();
    }
}
