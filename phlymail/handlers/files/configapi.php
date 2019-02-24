<?php
/**
 * Offering API calls for the Config interface
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: Files
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2013-07-07 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_files_configapi
{
    private $STOR = false;
    private $uid = 0;
    private $errortext = false;
    public $sysfolders = array
            ('root' => array('root' => 1, 'de' => 'Dateien', 'en' => 'Files', 'icon' => ':files')
            ,'waste' => array('root' => 0, 'de' => 'Papierkorb', 'en' => 'Trash', 'icon' => ':waste')
            );
    public $perm_handler_available = 'files_see_files';

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
        $this->uid = intval($uid);
        $this->STOR = new handler_files_driver($uid, '', true);
    }

    /**
     * Returns errors which happened
     * @return string error message(s)
     * @since 0.0.3
     */
    public function get_errors() { return $this->errortext; }

    public function check_user_installed()
    {
        // Never try to install anything for uid 0!
        if ($this->uid == 0) {
            return true;
        }

        $exists = $this->STOR->get_folder_id_from_path('waste');
        if (empty($exists)) {
            $this->create_user();
        }
    }

    /**
     * This encapsulates the necessary operations on creating a new user from the Config interface.
     * In case of the Email handler this includes creating the system folders in the file system and
     * the index and internal folders in the file system.
     *
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function create_user()
    {
        $root = 0;
        foreach ($this->sysfolders as $path => $data) {
            $dispname = ($this->_PM_['core']['language'] == 'de') ? $data['de'] : $data['en'];
            if ($data['root']) {
                $root = $this->STOR->create_mailbox($dispname, 0, $data['icon'], true, true);
                if (!$root) {
                	$this->errortext = $this->STOR->get_errors('<br />');
                	return false;
                }
            } else {
                $state = $this->STOR->create_folder($dispname, $root, 0, $data['icon'], true, true, $path);
                if (!$state) {
                	$this->errortext = $this->STOR->get_errors('<br> /');
                	return false;
                }
            }
        }
        return true;
    }

    /**
     * This encapsulates the necessary operations on removing a user from the Config interface.
     * In case of the Email handler this includes removing the system folders from the file system and
     * the index and internal folders from the file system.
     *
     * @return  boolean  true on success, false otherwise
     * @since 0.0.2
     */
    public function remove_user() { return $this->STOR->remove_user(); }

    /**
     * Called on installing the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.7
     */
    public function handler_install() { return $this->STOR->handler_install(); }

    /**
     * Called on uninstalling the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.7
     */
    public function handler_uninstall() { return $this->STOR->handler_uninstall(); }

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
        $d = opendir($this->_PM_['path']['handler'].'/files');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$lang.'(.*)\.php$!', $f)) {
                require_once($this->_PM_['path']['handler'].'/files/'.$f);
                break;
            }
        }
        return array
                ('see_files' => $WP_msg['PermSeeFiles']
                ,'add_file' => $WP_msg['PermAddFile']
                ,'update_file' => $WP_msg['PermUpdateFile']
                ,'delete_file' => $WP_msg['PermDeleteFile']
                ,'import_file' => $WP_msg['PermImportFile']
                ,'export_file' => $WP_msg['PermExportFile']
                ,'add_folder' => $WP_msg['PermAddFolder']
                ,'edit_folder' => $WP_msg['PermEditFolder']
                ,'delete_folder' => $WP_msg['PermDeleteFolder']
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
        // For a correct translation we unfortunately have to read in a messages file, that fits, since
        // the frontend allows to have de_Du, de_Sie, while the Config jsut has de as language...
        $d = opendir($this->_PM_['path']['message']);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^'.$lang.'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['message'].'/'.$f);
                break;
            }
        }
        //require_once($this->_PM_['path']['handler'].'/files/lang.'.$lang.'.php');
        // Give definitions
        return array
                ('traffic_limit' => array
                        ('type' => 'filesize'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => '' // $WP_msg['ConfigQuotaTrafficLimit']
                        ,'query' => false // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                ,'size_storage' => array
                        ('type' => 'filesize'
                        ,'min_value' => 0
                        ,'on_zero' => 'drop'
                        ,'name' => $WP_msg['ConfigQuotaStorageSize']
                        ,'query' => true
                        )
                ,'number_items' => array
                        ('type' => 'int'
                        ,'min_value' => 0
                        ,'on_zero' => 'drop'
                        ,'name' => $WP_msg['ConfigQuotaNumItems']
                        ,'query' => true
                        )
                ,'number_folders' => array
                        ('type' => 'int'
                        ,'min_value' => 0 // Here 0 means to not allow to add any folders
                        ,'on_zero' => 'keep'
                        ,'name' => $WP_msg['ConfigQuotaNumFolders']
                        ,'query' => true
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
        case 'size_storage':
            return $this->STOR->quota_getitemsize($stats);
            break;
        case 'number_items':
            return $this->STOR->quota_getitemnum($stats);
            break;
        case 'number_folders':
            return $this->STOR->quota_getfoldernum($stats);
            break;
        default: return false;
        }
    }
}
