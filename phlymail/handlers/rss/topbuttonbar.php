<?php
/**
 * Topbuttonbar
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler RSS
 * @copyright 2009-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2013-08-05 $Id: topbuttonbar.php 2731 2013-03-25 13:24:16Z mso $
 */
class handler_rss_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        if (file_exists($_PM_['path']['handler'] . '/rss/lang.' . $WP_msg['language'] . '.php')) {
            require($_PM_['path']['handler'] . '/rss/lang.' . $WP_msg['language'] . '.php');
        } else {
            require($_PM_['path']['handler'] . '/rss/lang.en.php');
        }
        $this->WP_msg = $WP_msg;
        $this->_PM_ = $_PM_;
        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (!isset($_SESSION['phM_uniqe_handlers']['rss']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['rss']['i18n'] = $WP_msg['MainFoldername'];
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $this->tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.rss.tpl');
        if (isset($_PM_['customsize']['rss_previewheight']) && $_PM_['customsize']['rss_previewheight']
                && (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows'])) {
            $this->tpl->fill_block('customheight', 'height', $_PM_['customsize']['rss_previewheight']);
        }
        // Permissions
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_add_feed']) {
            $this->tpl->assign_block('has_new_feed');
        }
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_export_feeds'] || $_SESSION['phM_privs']['rss_import_feeds']) {
            $this->tpl->assign_block('has_exchange');
        }
        $this->tpl->assign(array('handler' => $_PM_['handler']['name']));
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
                && !$_SESSION['phM_privs']['rss_add_feed']) {
            return array();
        }
        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'rss.png'
                        ,'name'      => $WP_msg['RSSAddNewFeed']
                        ,'localpath' => 'l=edit_feed'
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
                && !$_SESSION['phM_privs']['rss_export_feeds']
                && !$_SESSION['phM_privs']['rss_import_feeds']) {
            return array();
        }

        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'rss.png'
                        ,'name'      => $WP_msg['RSSExchangeFeeds']
                        ,'localpath' => 'l=exchange'
                        )
                );
    }
}