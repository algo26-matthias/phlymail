<?php
/**
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.8 2012-12-14
 */

class handler_files_topbuttonbar
{
    public function __construct(&$_PM_)
    {
        global $WP_msg;
        if (file_exists($_PM_['path']['handler'] . '/files/lang.' . $WP_msg['language'] . '.php')) {
            require($_PM_['path']['handler'] . '/files/lang.' . $WP_msg['language'] . '.php');
        } else {
            require($_PM_['path']['handler'] . '/files/lang.de.php');
        }
        $this->WP_msg = $WP_msg;
        $this->_PM_ = $_PM_;
        // Helper assignment. Saves huge API for just having a nice translation of the root node everywhere necessary
        if (!isset($_SESSION['phM_uniqe_handlers']['files']['i18n'])) {
            $_SESSION['phM_uniqe_handlers']['files']['i18n'] = $WP_msg['FilesMyName'];
        }
    }

    public function get()
    {
        $WP_msg = &$this->WP_msg;
        $_PM_ = &$this->_PM_;
        $passthru = give_passthrough(1);
        $tpl = new phlyTemplate($_PM_['path']['templates'].'topbuttonbar.files.tpl');
        // Permissions
        if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_add_file']) {
            $tpl->assign_block('has_new_file');
        }
        $tpl->assign(array
                ('handler' => 'files'
                ,'handlername' => $WP_msg['FilesMyName']
                ,'but_upload' => $WP_msg['FileUpload']
                // ,'rootcollapsed' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses']['files_']) && $_PM_['foldercollapses']['files_']) ? 0 : 1
                // ,'iframesrcurl' => PHP_SELF.'?h=files&l=ilist&'.$passthru.'&workfolder='
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
                && !$_SESSION['phM_privs']['files_add_file']) {
            return array();
        }
        $WP_msg = &$this->WP_msg;
        return array
                (array
                        ('icon'      => 'files.upload_men.png'
                        ,'name'      => $WP_msg['FileUpload']
                        ,'localpath' => 'l=upload'
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
        return array();
    }
}
