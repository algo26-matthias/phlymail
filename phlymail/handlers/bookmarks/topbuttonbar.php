<?php
/**
 * Topbuttonbar
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Bookmarks
 * @copyright 2009-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.7 2012-05-02
 */
class handler_bookmarks_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        if (file_exists($_PM_['path']['handler'] . '/bookmarks/lang.' . $WP_msg['language'] . '.php')) {
            require($_PM_['path']['handler'] . '/bookmarks/lang.' . $WP_msg['language'] . '.php');
        } else {
            require($_PM_['path']['handler'] . '/bookmarks/lang.en.php');
        }
        $this->WP_msg = $WP_msg;
        $this->_PM_ = $_PM_;
        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (!isset($_SESSION['phM_uniqe_handlers']['bookmarks']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['bookmarks']['i18n'] = $WP_msg['MainFoldername'];
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $this->tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.bookmarks.tpl');
        if (isset($_PM_['customsize']['bookmarks_previewheight']) && $_PM_['customsize']['bookmarks_previewheight']
                && (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows'])) {
            $this->tpl->fill_block('customheight', 'height', $_PM_['customsize']['bookmarks_previewheight']);
        }
        // Permissions
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_add_bookmark']) {
            $this->tpl->assign_block('has_new_bookmark');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_export_bookmarks'] || $_SESSION['phM_privs']['bookmarks_import_bookmarks']) {
            $this->tpl->assign_block('has_exchange');
        }
        $this->tpl->assign(array
                ('handler' => $_PM_['handler']['name']
                ,'msg_setup_bookmarks' => $WP_msg['MainFoldername']
                ,'msg_newbookmark' => $WP_msg['NewBookmark']
                ,'msg_groupsmanager' => $WP_msg['GroupManager']
                ));
        return $this->tpl;
    }

    /**
     * Retrieves the items to show in the setup menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_setup_menu()
    {
        return array();
    }

    /**
     * Retrieves the items to show in the "new" menu for this handler
     * @return Array holding items, if available
     * @since 2012-05-15
     */
    public function get_new_menu()
    {
        // #FIXME Disable for now, view not prepared for mobile
        if (1 == 1) {
            return array();
        }
        // End FIXME

        if (!$_SESSION['phM_privs']['all']
                && !$_SESSION['phM_privs']['bookmarks_add_bookmark']) {
            return array();
        }
        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'bookmarks.png'
                        ,'name'      => $WP_msg['NewBookmark']
                        ,'localpath' => 'l=edit_bookmark'
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
                && !$_SESSION['phM_privs']['bookmarks_export_bookmarks']
                && !$_SESSION['phM_privs']['bookmarks_import_bookmarks']) {
            return array();
        }

        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'bookmarks.png'
                        ,'name'      => $WP_msg['MainFoldername']
                        ,'localpath' => 'l=exchange'
                        )
                );
    }
}
