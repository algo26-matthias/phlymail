<?php
/**
 * Generic, standardized way of retrieving all folders a handler offers.
 * In the case of the Core handler this seems to be senseless, since it
 * has only one "folder" -> the pinboard, which actually is not a folder at all
 * but a collection of data from other handlers.
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Core Handler
 * @copyright 2010 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2010-06-24
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// No privleges, no folders
if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['core_use_pinboard']) {
    sendJS(array('handler' => 'files', 'childof' => array(), 'folders' => array()), 1, 1);
}
session_write_close(); // Don't block other processes
$folders = array('root' => array
        ('path' => 0
        ,'icon' => $_PM_['path']['theme'].'/icons/pinboard_men.png'
        ,'big_icon' => $_PM_['path']['theme'].'/icons/pinboard_big.png'
        ,'colour' => ''
        ,'foldername' => $WP_msg['CorePinboard']
        ,'type' => 0
        ,'has_folders' => 0
        ,'has_items' => 0
        ,'level' => 0
        ,'childof' => 0
        ));
sendJS(array('handler' => 'core', 'childof' => array(0 => array('root')), 'folders' => $folders), 1, 1);
