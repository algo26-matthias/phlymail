<?php
/**
 * Offering API calls for the Config interface
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: RSS
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2015-03-30 $Id: configapi.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_rss_configapi
{
    private $DB = false;
    private $errortext = false;
    public $perm_handler_available = 'rss_see_feeds';

    /**
     * Constructor method, this special constructor also attempts to create the required
     * docroot of the email storage for the given user
     *
     * @param  array reference  public settings structure
     * @param  int  ID of the user to perform the operation for
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid)
    {
        $this->_PM_ = $_PM_;
        $this->uid = $uid;
        $this->DB = new handler_rss_driver($uid);
    }

    /**
     * Returns errors which happened
     * @param void
     * @return string error message(s)
     * @since 0.0.1
     */
    public function get_errors() { return $this->errortext; }

    /**
     * This encapsulates the necessary operations on creating a new user from the Config interface.
     * In case of the Email handler this includes creating the system folders in the file system and
     * the index and internal folders in the file system.
     *
     * @param  void
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function create_user() { return true; }

    /**
     * This encapsulates the necessary operations on removing a user from the Config interface.
     * In case of the Email handler this includes removing the system folders from the file system and
     * the index and internal folders from the file system.
     *
     * @param  void
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function remove_user() { return $this->DB->remove_user(); }

    /**
     * This method allows the Config to query the available actions of this handlers
     * for managing access permissions to them. This allows for user level access permissions
     * to anything functional phlyMail offers - even complete readonly access and disabling
     * of single functions in the frontend (sending emails, adding profiles and stuff).
     * @param void
     * @return array Key => Translated action name
     * @since 1.0.0
     */
    public function get_perm_actions($lang = 'en')
    {
        // For a correct translation we unfortunately have to read in a messages file
        require(__DIR__.DIRECTORY_SEPARATOR.'lang.en.php');
        $d = opendir($this->_PM_['path']['handler'].'/rss');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$lang.'(.*)\.php$!', $f)) {
                include($this->_PM_['path']['handler'].'/rss/'.$f);
                break;
            }
        }
        return array
                ('see_feeds' => $WP_msg['PermSeeFeeds']
                ,'add_feed' => $WP_msg['PermAddFeed']
                ,'update_feed' => $WP_msg['PermUpdateFeed']
                ,'delete_feed' => $WP_msg['PermDeleteFeed']
                ,'import_feeds' => $WP_msg['PermImportFeeds']
                ,'export_feeds' => $WP_msg['PermExportFeeds']
                ,'add_folder' => $WP_msg['PermAddGroup']
                ,'edit_folder' => $WP_msg['PermEditGroup']
                ,'delete_folder' => $WP_msg['PermDeleteGroup']
                ,'see_global_feeds' => $WP_msg['PermSeeGlobalFeeds']
                ,'make_feed_global' => $WP_msg['PermMakeFeedGlobal']
                );
    }

    /**
     * This method delivers a list of quota settings, this handler defines. The list contains the
     * internal identifier for this definition, the human readable name of it and a few helpful bits
     * of information, so that the Config knows, which types of values are allowed.
     *
     * This method queries global values!
     *
     * @param string $lang The language of the Config interface for the display name of the setting
     * @return array
     * @since 0.0.8
     */
    public function get_quota_definitions($lang = 'en')
    {
        // For a correct translation we unfortunately have to read in a messages file
        require(__DIR__.DIRECTORY_SEPARATOR.'lang.en.php');
        $d = opendir($this->_PM_['path']['handler'].'/rss');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$lang.'(.*)\.php$!', $f)) {
                require_once($this->_PM_['path']['handler'].'/rss/'.$f);
                break;
            }
        }
        // Give definitions
        return array
                ('number_feeds' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaNumberFeeds']
                        ,'query' => true // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                ,'number_folders' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaNumberGroups']
                        ,'query' => true // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                );
    }

    /**
     * This method allows Config to query the current usage for a specific definition and
     * a specific user
     *
     * @param string $what The definition to query for the current user
     * @return mixed The current usage for that quota definition
     * @since 0.0.8
     */
    public function get_quota_usage($what, $stats = false)
    {
        switch ($what) {
            case 'number_feeds':
                return $this->DB->quota_feedsnum($stats);
                break;
            case 'number_folders':
                return $this->DB->quota_foldersnum($stats);
                break;
            default: return false;
        }
    }
}