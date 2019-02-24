<?php
/**
 * Snap in module for the folder browser shown on copy / move.
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler RSS
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.5 2015-03-30 $Id: folderlist.php 2731 2013-03-25 13:24:16Z mso $
 */
class handler_rss_folderlist extends handler_rss_driver
{
    public function __construct(&$_PM_, $mode)
    {
        parent::__construct($_SESSION['phM_uid']);
        $this->_PM_ = $_PM_;
    }

    public function get()
    {
        if (file_exists(__DIR__.'/lang.'.$GLOBALS['WP_msg']['language'].'.php')) {
            require(__DIR__.'/lang.'.$GLOBALS['WP_msg']['language'].'.php');
        } else {
            require(__DIR__.'/lang.de.php');
        }
        $this->get_folderlist();

        return array(0 => array(
                'path' => 0,
                'icon' => $this->_PM_['path']['theme'].'/icons/rss.png',
                'foldername' => $WP_msg['MyFeeds'],
                'type' => 1,
                'has_folders' => 1,
                'has_items' => 0,
                'subdirs' => $this->read_folders(0)
                ));
    }
}