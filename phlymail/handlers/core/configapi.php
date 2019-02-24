<?php
/**
 * Offering API calls for the Config interface
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: Core
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.7 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_core_configapi
{
    private $storage = false;
    private $errortext = false;
    public $perm_handler_available = false;

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
    }

    /**
     * Returns errors which happened
     * @param void
     * @return string error message(s)
     * @since 0.0.5
     */
    public function get_errors() { return $this->errortext; }

    public function check_user_installed()
    {
        $root = $this->_PM_['path']['userbase'].'/'.$this->uid.'/core/';
        if (!file_exists($root)) {
            $this->create_user();
        }
    }

    /**
     * This encapsulates the necessary operations on creating a new user from the Config interface.
     * In case of the Email handler this includes creating the system folders in the file system and
     * the index and internal folders in the file system.
     *
     * @param  void
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function create_user()
    {
        $state = file_exists($this->_PM_['path']['userbase'].'/'.$this->uid.'/core/');
        if (!$state) {
            $state = basics::create_dirtree($this->_PM_['path']['userbase'].'/'.$this->uid.'/core/');
        }
        if (!$state) {
            $this->errortext = 'Could not create dir '.$this->_PM_['path']['userbase'].'/'.$this->uid.'/core/';
        }
        return $state;
    }

    /**
     * This encapsulates the necessary operations on removing a user from the Config interface.
     * In case of the Email handler this includes removing the system folders from the file system and
     * the index and internal folders from the file system.
     *
     * @param  void
     * @return  boolean  true on success, false otherwise
     * @since 0.0.2
     */
    public function remove_user() { return true; }

    /**
     * This method delivers the Config all available actions of this handler
     * for managing access permissions to them. This allows for user level access permissions
     * to anything functional phlyMail offers - even complete readonly access and disabling
     * of single functions in the frontend (sending emails, adding profiles and stuff).
     * @param void
     * @return array Key => Translated action name
     * @since 1.0.0
     */
    public function get_perm_actions($lang = 'en')
    {
        $d = opendir($this->_PM_['path']['message']);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f
                    || '..' == $f) {
                continue;
            }
            if (preg_match('!^'.$lang.'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['message'].'/'.$f);
                break;
            }
        }
        return array
                ('use_pinboard' => $WP_msg['PermUsePinboard']
                ,'new_email' => $WP_msg['PermNewEmail']
                ,'new_sms' => $WP_msg['PermNewSMS']
                ,'new_fax' => $WP_msg['PermNewFax']
                ,'setup_settings' => $WP_msg['PermSetupSettings']
                ,'change_theme' => $WP_msg['PermChangeTheme']
                );
    }
}
