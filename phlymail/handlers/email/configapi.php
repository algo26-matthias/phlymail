<?php
/**
 * Offering API calls for the Config interface
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: Email
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0 2013-07-03 
 */
class handler_email_configapi
{
    private $STOR = false;
    private $errortext = false;
    public $sysfolders = array
            ('mailbox' => array('root' => 1, 'de' => 'Postfach', 'en' => 'Mailbox', 'icon' => ':mailbox')
            ,'inbox' => array('root' => 0, 'de' => 'Posteingang', 'en' => 'Inbox', 'icon' => ':inbox')
            ,'sent' => array('root' => 0, 'de' => 'Gesendete', 'en' => 'Sent', 'icon' => ':sent')
            ,'drafts' => array('root' => 0, 'de' => 'Entwürfe', 'en' => 'Drafts', 'icon' => ':drafts')
            ,'templates' => array('root' => 0, 'de' => 'Vorlagen', 'en' => 'Templates', 'icon' => ':templates')
            ,'waste' => array('root' => 0, 'de' => 'Papierkorb', 'en' => 'Trash', 'icon' => ':waste')
            ,'junk' => array('root' => 0, 'de' => 'Unerwünscht', 'en' => 'Junk Mail', 'icon' => ':junk')
            ,'archive' => array('root' => 0, 'de' => 'Archiv', 'en' => 'Archive', 'icon' => ':archive')
            );
    public $perm_handler_available = 'email_see_emails';

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
        $this->STOR = new handler_email_driver($uid, '', true);
    }

    /**
     * Returns errors which happened
     * @param void
     * @return string error message(s)
     * @since 0.0.3
     */
    public function get_errors() { return $this->errortext; }

    public function check_user_installed()
    {
        $exists = $this->STOR->get_folder_id_from_path('mailbox', true);
        if (empty($exists)) {
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
    	// No local folders get created
    	if (isset($this->_PM_['core']['email_nolocals']) && $this->_PM_['core']['email_nolocals']) return true;

        $root = 0;
        foreach ($this->sysfolders as $path => $data) {
            $dispname = ($this->_PM_['core']['language'] == 'de') ? $data['de'] : $data['en'];
            if ($data['root']) {
                $root = $this->STOR->create_mailbox($dispname, 0, $data['icon'], true, false);
                if (!$root) {
                	$this->errortext = 'Could not create user\'s mailbox:'.LF.$this->STOR->get_errors('<br />');
                	return false;
                }
            } else {
                $state = $this->STOR->create_folder($dispname, $root, 0, $data['icon'], true, true, $path);
                if (!$state) {
                	$this->errortext = 'Could not create folder'.$path.':'.LF.$this->STOR->get_errors('<br />');
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
     * @param  void
     * @return  boolean  true on success, false otherwise
     * @since 0.0.2
     */
    public function remove_user() { return $this->STOR->remove_user(); }

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
        $d = opendir($this->_PM_['path']['message']);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^'.$lang.'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['message'].'/'.$f);
                break;
            }
        }
        return array
                ('see_emails' => $WP_msg['PermSeeEmails']
                ,'see_localfolders' => $WP_msg['PermSeeLocalFolders']
                ,'copy_email' => $WP_msg['PermCopyEmail']
                ,'move_email' => $WP_msg['PermMoveEmail']
                ,'delete_email' => $WP_msg['PermDeleteEmail']
                ,'import_emails' => $WP_msg['PermImportEmail']
                ,'export_emails' => $WP_msg['PermExportEmail']
                ,'add_folder' => $WP_msg['PermAddFolder']
                ,'edit_folder' => $WP_msg['PermEditFolder']
                ,'delete_folder' => $WP_msg['PermDeleteFolder']
                ,'add_profile' => $WP_msg['PermAddProfile']
                ,'edit_profile' => $WP_msg['PermEditProfile']
                ,'delete_profile' => $WP_msg['PermDeleteProfile']
                ,'edit_global_boilerplates' => $WP_msg['PermEditGlobalBoilerPlates']
                // ,'edit_global_filters' => $WP_msg['PermEditGlobalFilters']
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
        // Give definitions
        return array
                ('size_mail_in' => array
                        ('type' => 'filesize'
                        ,'min_value' => 0 // Beware: 0 means unlimited ...
                        ,'on_zero' => 'drop' // How to behave on zero values (drop or keep)
                        ,'name' => $WP_msg['ConfigQuotaMailSizeIn']
                        ,'query' => false // Whether this feature can be set at the moment (false: not yet implemented)
                        )
                ,'size_mail_out' => array
                        ('type' => 'filesize'
                        ,'min_value' => 0
                        ,'on_zero' => 'drop'
                        ,'name' => $WP_msg['ConfigQuotaMailSizeOut']
                        ,'query' => false
                        )
                ,'size_storage' => array
                        ('type' => 'filesize'
                        ,'min_value' => 0
                        ,'on_zero' => 'drop'
                        ,'name' => $WP_msg['ConfigQuotaStorageSize']
                        ,'query' => true
                        )
                ,'number_mails' => array
                        ('type' => 'int'
                        ,'min_value' => 0
                        ,'on_zero' => 'drop'
                        ,'name' => $WP_msg['ConfigQuotaNumMails']
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
            return $this->STOR->quota_getmailsize($stats);
            break;
        case 'number_mails':
            return $this->STOR->quota_getmailnum($stats);
            break;
        case 'number_folders':
            return $this->STOR->quota_getfoldernum($stats);
            break;
        default: return false;
        }
    }
}
