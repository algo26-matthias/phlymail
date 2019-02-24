<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Contacts
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version
 */
class handler_contacts_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        if (file_exists($_PM_['path']['handler'] . '/contacts/lang.' . $WP_msg['language'] . '.php')) {
            require($_PM_['path']['handler'] . '/contacts/lang.' . $WP_msg['language'] . '.php');
        } else {
            require($_PM_['path']['handler'] . '/contacts/lang.de.php');
        }
        $this->_PM_ = $_PM_;
        $this->WP_msg = $WP_msg;

        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (!isset($_SESSION['phM_uniqe_handlers']['contacts']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['contacts']['i18n'] = $WP_msg['MainFoldername'];
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.contacts.tpl');
        if (isset($_PM_['customsize']['contacts_previewheight']) && $_PM_['customsize']['contacts_previewheight']
                && (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows'])) {
            $tpl->fill_block('customheight', 'height', $_PM_['customsize']['contacts_previewheight']);
        }
        // Permissions
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_add_contact']) {
            $tpl->assign_block('has_new_contact');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_export_contacts'] || $_SESSION['phM_privs']['contacts_import_contacts']) {
            $tpl->assign_block('has_exchange');
        }
        $tpl->assign(array
                ('msg_newcontact' => $WP_msg['NewContact']
                ,'msg_setup_contacts' => $WP_msg['MainFoldername']
                ,'msg_edit_vcf' => $WP_msg['MenuOwnVCF']
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
                        ('icon'      => 'contacts.png'
                        ,'name'      => $WP_msg['MenuOwnVCF']
                        ,'localpath' => 'l=edit_vcf'
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
        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['contacts_add_contact']) {
            return array();
        }

        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'contacts.png'
                        ,'name'      => $WP_msg['NewContact']
                        ,'localpath' => 'l=edit_contact'
                        )
                );
    }

    /**
     * Retrieves the items to show in the exhcange menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_exchange_menu()
    {
        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['contacts_export_contacts']
                && !$_SESSION['phM_privs']['contacts_import_contacts']) {
            return array();
        }

        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'contacts.png'
                        ,'name'      => $WP_msg['MainFoldername']
                        ,'localpath' => 'l=exchange'
                        )
                );
    }
}
