<?php
/**
 * Return a list of available folders
 *
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @copyright 2001-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.5 2013-08-09 
 */
class handler_email_folderlist extends handler_email_driver
{
    public function __construct(&$_PM_, $mode)
    {
        parent::__construct($_SESSION['phM_uid']);
        $this->init_folders(false); // ('browse' == $mode) ? false : true);
    }
    public function get()
    {
        return $this->read_folders(0);
    }
}