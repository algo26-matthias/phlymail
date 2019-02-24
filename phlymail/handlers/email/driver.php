<?php
/**
 * FS Storage Driver
 * Provides mail storage functions for use within the filing system and IMAP
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler email
 * @author Matthias Sommerfeld
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.7.4 2015-06-05
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_email_driver {
    public $pagesize = false;
    private $error = array();
    public $docroot = false;
    public $userroot = false;
    public $IDX = false;

    protected $archive_nested = true;
    protected $archive_years  = true;
    protected $allShares = array();

    private $IMAP_Searched = array();
    protected $streamType = false;
    public $label2colour = array('$label1' => 'FF0000', '$label2' => 'FFFF00'
            ,'$label3' => '00FF00', '$label4' => '0000FF', '$label5' => 'FF00FF'
            /*,'$label6' => '000000'*/, '$label7' => '800000', '$label8' => '008000'
            ,'$label9' => '000080', '$label10' => '808000', '$label11' => '008080'
            ,'$label12' => '800080', '$label13' => '808080', '$label14' => '00FFFF'
            );
    public $sysfolders = array
            ('mailbox' => array('root' => 1, 'msg' => 'EmailLocalsFolder', 'icon' => ':mailbox', 'imap' => false)
            ,'virtual' => array('root' => 1, 'msg' => 'EmailVirtualsFolder', 'icon' => ':virtual', 'imap' => false)
            ,'inbox' => array('root' => 0, 'msg' => 'EmailInboxFolder', 'icon' => ':inbox', 'imap' => false)
            ,'outbox' => array('root' => 0, 'msg' => 'EmailOutboxFolder', 'icon' => ':outbox', 'imap' => false)
            ,'sent' => array('root' => 0, 'msg' => 'EmailSentObjectsFolder', 'icon' => ':sent', 'imap' => 'Sent')
            ,'drafts' => array('root' => 0, 'msg' => 'EmailDraftsFolder', 'icon' => ':drafts', 'imap' => 'Drafts')
            ,'templates' => array('root' => 0, 'msg' => 'EmailTemplatesFolder', 'icon' => ':templates', 'imap' => 'Templates')
            ,'archive' => array('root' => 0, 'msg' => 'EmailArchiveFolder', 'icon' => ':archive', 'imap' => 'Archive')
            ,'waste' => array('root' => 0, 'msg' => 'EmailWasteFolder', 'icon' => ':waste', 'imap' => 'Waste')
            ,'junk' => array('root' => 0, 'msg' => 'EmailJunkFolder', 'icon' => ':junk', 'imap' => 'Junk')
            );
    // This holds all known names of system folders on IMAP servers, 'ÄÄ' serves as the placeholder for the delimiter
    public $imaptranslate = array
            ('inbox' => array('fn' => 'Inbox', 'i' => ':inbox')
            ,'outbox' => array('fn' => 'Outbox', 'i' => ':outbox') // Special feature of Courier
            ,'inboxÄÄoutbox' => array('fn' => 'Outbox', 'i' => ':outbox') // Special feature of Courier
            ,'inboxÄÄsent' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄsentmail' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄsent items' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄgesendet' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄpostausgang' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄgesendete emails' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄgesendete objekte' => array('fn' => 'Sent', 'i' => ':sent')
            ,'[google mail]ÄÄgesendet' => array('fn' => 'Sent', 'i' => ':sent')
            ,'[google mail]ÄÄsent mail' => array('fn' => 'Sent', 'i' => ':sent')
            ,'[gmail]ÄÄgesendet' => array('fn' => 'Sent', 'i' => ':sent')
            ,'[gmail]ÄÄsent mail' => array('fn' => 'Sent', 'i' => ':sent')
            ,'sent' => array('fn' => 'Sent', 'i' => ':sent')
            ,'sent items' => array('fn' => 'Sent', 'i' => ':sent')
            ,'gesendet' => array('fn' => 'Sent', 'i' => ':sent')
            ,'postausgang' => array('fn' => 'Sent', 'i' => ':sent')
            ,'gesendete emails' => array('fn' => 'Sent', 'i' => ':sent')
            ,'gesendete objekte' => array('fn' => 'Sent', 'i' => ':sent')
            ,'inboxÄÄdrafts' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'inboxÄÄentw&apw-rfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'inboxÄÄentwuerfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'[google mail]ÄÄentw&apw-rfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'[google mail]ÄÄdrafts' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'[gmail]ÄÄentw&apw-rfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'[gmail]ÄÄdrafts' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'drafts' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'entw&apw-rfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'entwuerfe' => array('fn' => 'Drafts', 'i' => ':drafts')
            ,'inboxÄÄtemplates' => array('fn' => 'Templates', 'i' => ':templates')
            ,'inboxÄÄvorlagen' => array('fn' => 'Templates', 'i' => ':templates')
            ,'[google mail]ÄÄvorlagen' => array('fn' => 'Templates', 'i' => ':templates')
            ,'[google mail]ÄÄtemplates' => array('fn' => 'Templates', 'i' => ':templates')
            ,'[gmail]ÄÄvorlagen' => array('fn' => 'Templates', 'i' => ':templates')
            ,'[gmail]ÄÄtemplates' => array('fn' => 'Templates', 'i' => ':templates')
            ,'templates' => array('fn' => 'Templates', 'i' => ':templates')
            ,'vorlagen' => array('fn' => 'Templates', 'i' => ':templates')
            ,'inboxÄÄtrash' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄdeleted items' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄdeleted messages' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄdeleted' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄgel&apy-scht' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄmuelleimer' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄpapierkorb' => array('fn' => 'Trash', 'i' => ':waste')
            ,'[google mail]ÄÄpapierkorb' => array('fn' => 'Trash', 'i' => ':waste')
            ,'[google mail]ÄÄtrash' => array('fn' => 'Trash', 'i' => ':waste')
            ,'[gmail]ÄÄpapierkorb' => array('fn' => 'Trash', 'i' => ':waste')
            ,'[gmail]ÄÄtrash' => array('fn' => 'Trash', 'i' => ':waste')
            ,'trash' => array('fn' => 'Trash', 'i' => ':waste')
            ,'deleted items' => array('fn' => 'Trash', 'i' => ':waste')
            ,'deleted messages' => array('fn' => 'Trash', 'i' => ':waste')
            ,'deleted' => array('fn' => 'Trash', 'i' => ':waste')
            ,'gel&apy-scht' => array('fn' => 'Trash', 'i' => ':waste')
            ,'muelleimer' => array('fn' => 'Trash', 'i' => ':waste')
            ,'papierkorb' => array('fn' => 'Trash', 'i' => ':waste')
            ,'inboxÄÄspam' => array('fn' => 'Junk', 'i' => ':junk')
            ,'inboxÄÄjunk' => array('fn' => 'Junk', 'i' => ':junk')
            ,'inboxÄÄspamverdacht' => array('fn' => 'Junk', 'i' => ':junk')
            ,'inboxÄÄjunk e-mail' => array('fn' => 'Junk', 'i' => ':junk')
            ,'[google mail]ÄÄspam' => array('fn' => 'Junk', 'i' => ':junk')
            ,'[gmail]ÄÄspam' => array('fn' => 'Junk', 'i' => ':junk')
            ,'spam' => array('fn' => 'Junk', 'i' => ':junk')
            ,'junk' => array('fn' => 'Junk', 'i' => ':junk')
            ,'spamverdacht' => array('fn' => 'Junk', 'i' => ':junk')
            ,'junk e-mail' => array('fn' => 'Junk', 'i' => ':junk')
            ,'junk-e-mail' => array('fn' => 'Junk', 'i' => ':junk')
            ,'inboxÄÄarchiv' => array('fn' => 'Archive', 'i' => ':archive')
            ,'inboxÄÄarchive' => array('fn' => 'Archive', 'i' => ':archive')
            ,'archiv' => array('fn' => 'Archive', 'i' => ':archive')
            ,'archive' => array('fn' => 'Archive', 'i' => ':archive')
            ,'[google mail]ÄÄalle nachrichten' => array('fn' => 'Archive', 'i' => ':archive')
            ,'[gmail]ÄÄalle nachrichten' => array('fn' => 'Archive', 'i' => ':archive')
            );
    /**
     * Constructor, expects to be given 2 parameters
     * @param  int  ID of the affected user
     *[@param  string  Actual working dir within doc root]
     *[@param  boolean  TRUE to create the user's doc root, FALSE otherwise; Default: FALSE]
     */
    public function __construct($uid = 0, $dirname = '', $create = false)
    {
        if (false === $uid) {
            return false;
        }
        // Load indexer driver, instantiate it
        $this->IDX = new handler_email_indexer();
        $this->uid = intval($uid);
        $this->place = false;
        $this->docroot = $GLOBALS['_PM_']['path']['userbase'];
        $this->userroot = $this->docroot.'/'.$this->uid.'/email';

        $this->archive_nested = (!empty($GLOBALS['_PM_']['archive']['mimic_foldertree']));
        $this->archive_years  = (!empty($GLOBALS['_PM_']['archive']['partition_by_year']));

        if ($this->uid > 0
                && $create
                && !basics::create_dirtree($this->userroot)) {
            return false;
        }
        if (file_exists($this->docroot) && !empty($dirname)) {
            $this->place = $dirname;
        }
        try {
            $dbSh = new DB_Controller_Share();
            $allShares = $dbSh->getFolderList($this->uid, 'email');
            $this->allShares = (!empty($allShares[$this->uid]['email'])) ? $allShares[$this->uid]['email'] : array();
        } catch (Exception $e) {
            unset($e);
            $this->allShares = array();
        }
    }

    public function set_parameter($option, $value = false)
    {
        if (!is_array($option)) {
            $option = array($option => $value);
        }
        foreach ($option as $k => $v) {
            switch ($k) {
            case 'pagesize':
                $this->pagesize = ($v > 0) ? $v : false;
                break;
            default:
                $this->set_error('Set Parameter: Unknown option '.$k);
                return false;
            }
        }
        return true;
    }

    private function set_error($error)
    {
        if (isset($this->append_errors) && $this->append_errors) {
            $this->error[] = $error;
        } else {
            $this->error[0] = $error;
        }
    }

    public function get_errors($nl = "\n")
    {
        $error = implode($nl, $this->error);
        if (!isset($this->retain_erros) || !$this->retain_erros) {
            $this->error = array();
        }
        return $error;
    }

    public function affected()
    {
    	return $this->IDX->affected();
    }

    public function changeUID($uid)
    {
    	$this->uid = abs(intval($uid));
    	return true;
    }

    /**
     * Removes all saved information of a certain user from the
     * index and optionally the file system
     *[@param  boolean  TRUE for also deleting all data from the file system; Default: TRUE]
     * @return  boolean  State of the operation
     * @since 0.2.4
     */
    public function remove_user($fstoo = true)
    {
        $state = $this->IDX->remove_user($this->uid);
        if (!$state) return false;
        if (!$fstoo) return true;
        // Deleting the dir depends on the OS we are running on
        basics::emptyDir($this->userroot, true);
    }

    public function init_folders($live = true, $imapBox = null)
    {
        if ($live != false) {
            foreach ($this->IDX->get_imapboxes($this->uid) as $box) {
                if (!is_null($imapBox) && $box['folder_path'] != $imapBox.':') {
                    continue;
                }
                $this->init_imapbox($box);
            }
        }
        $this->fidx = $this->IDX->get_folder_structure($this->uid);
        return true;
    }

    /**
     * Read all available folders below doc root and return as multidim array,
     * this method is probably only useful for the initial display of all folders
     * within the frontend's folder screen
     * @param int $parent_id  Starting point in the structure; Default 0
     * @param bool $ignore_imap Whether to ignore IMAP boxes; Default false
     * @param bool $ignore_hidden Whether to skip invisible folders; Default true
     * @return array multi dim folder structure and meta data
     */
    public function read_folders($parent_id = 0, $ignore_imap = false, $ignore_hidden = true)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) return false;
        foreach ($this->fidx[$parent_id] as $k => $v) {
            if ($ignore_imap && intval($v['type']/10) == 1) continue;
            if ($ignore_hidden && $v['visible'] == 0) continue;
            $return[$k] = $v;
            $return[$k]['path'] = $k;
            $return[$k]['foldername'] = $v['friendly_name'];
            $return[$k]['is_shared'] = !empty($this->allShares[$k]) ? '1' : '0';
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = $this->read_folders($k, $ignore_imap, $ignore_hidden);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
    }

    /**
     * Read all available folders below doc root and return as array,
     * opposed to read_folders() the returned array does not reflect the real
     * structure of the folders, just the order and a 'level' attribute will tell
     * you about it.
     * @param int $parent_id Starting point in the structure; Default 0
     * @param int $level Starting level (leave as zero in most cases!)
     * @param bool $ignore_imap Whether to ignore IMAP boxes; Default false
     * @return array of folders and their meta data
     */
    public function read_folders_flat($parent_id = 0, $level = 0, $ignore_imap = false)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) return false;
        foreach ($this->fidx[$parent_id] as $k => $v) {
            if ($ignore_imap && intval($v['type']/10) == 1) continue;
            $return[$k] = $v;
            $return[$k]['path'] = $k;
            $return[$k]['foldername'] = $v['friendly_name'];
            $return[$k]['level'] = $level;
            $return[$k]['is_shared'] = !empty($this->allShares[$k]) ? '1' : '0';
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = true;
                $return = $return + $this->read_folders_flat($k, $level+1, $ignore_imap);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
    }

    /**
     * Try to determine, how many unread items there are for all folders
     * @param void
     * @return array containing the folder IDs as keys and the quantities as values
     * @since 0.1.4
     * @see indexer::folders_get_unread()
     */
    public function folders_get_unread() { return $this->IDX->folders_get_unread($this->uid); }

    /**
     * Try to determine, how many unread items there are for all folders
     *
     * @since 4.4.4
     * @see indexer::folders_set_seen()
     */
    public function folders_set_seen() { return $this->IDX->folders_set_seen($this->uid); }

    /**
     * Marks a folder as being secure (or not). This only applies to IMAP root nodes,
     * although other folders have that flag, too.
     *
     * @param int $profile  Profile ID (NOT folder ID!)
     *[@param bool $secure  true for secure, false otherwise; Default: false]
     */
    public function folder_mark_secure($profile, $secure = false)
    {
        return $this->IDX->folder_mark_secure($this->uid, $profile, $secure ? 1 : 0);
    }

    /**
     * Check for the existance of a certain foldername; by specifying the parent
     * folder this method will check for the existance within the given parent
     * only, thus allowing to prevent duplicate folder names
     *
     * @param  string  $folder  Display name of the folder to search for
     * [@param  int  $childof  ID of the folder to limit the search to]
     * @return  bool  TRUE, if found, FALSE otherwise
     * @since v0.0.6
     * @see  indexer::folder_exists()
     */
    public function folder_exists($folder, $childof = false)
    {
        if (!$folder) return false;
        return $this->IDX->folder_exists($this->uid, $folder, $childof);
    }

    /**
     * Create a new folder within a given one
     *
     * @param  string  $folder  Display name of the folder to create
     * @param  int  $childof  ID of the parent folder
     *[@param  int  $type  The type of the folder; default is 1 (user created folder)]
     *[@param  string  $icon  Path to an icon to assign this folder]
     *[@param  bool  $has_folders  Whether this folder can contain subfolders]
     *[@param  bool  $has_items  Whether this folder can contain any items]
     *[@param  string  $path  Optional fs path for the new folder, only interesting for system folders]
     * @return  int  New folder's ID in index if successful; FALSE otherwise
     * @since v0.0.6
     * @see  indexer::create_folder()
     * @see  indexer::update_folder()
     */
    public function create_folder($folder, $childof, $type = 1, $icon = '', $has_folders = true, $has_items = true, $path = '')
    {
        if (!$folder || false === $childof || is_null($childof)) {
            return false;
        }
        // Check, whether we are allowed to create the new folder there
        if ($type < 10 && !is_writable($this->userroot)) {
            $this->set_error('Insufficient permissions to create folder');
            return false;
        }
        // Handle both sinlge fragments and whole paths the same way
        if (!is_array($folder)) {
            $folder = array($folder);
        }

        // Would lead to unexpected results
        if (sizeof($folder) > 1 && !empty($path)) {
            return false;
        }

        $papa = $this->get_folder_info($childof);

        // IMAP
        if (10 == $papa['type'] || 11 == $papa['type']) {
            try {
                $mbox = $this->connect_imap($papa['folder_path'], false, true);
                $newName = $mbox->mboxpath.(($papa['childof'] != 0) ? $mbox->delimiter : '');
                foreach ($folder as $k => $fragment) {
                    if ($k != 0) {
                        $newName .= $mbox->delimiter;
                    }
                    $newName .= uctc::convert($fragment, 'utf8', 'utf7imap');
                    $mbox->createFolder($newName);
                    $mbox->subscribeFolder($newName);
                }
                $mbox->close();
            } catch (Exception $e) {
                $this->set_error($e->getMessage());
                return false;
            }
            return true;
        }
        // Local folders

        $myChildOf = $childof;
        $myPath = $this->userroot;

        foreach ($folder as $k => $fragment) {
            // Create folder's index and retrieve its ID
            $new_folder = $this->IDX->create_folder(array(
                    'uid' => $this->uid,
                    'friendly_name' => $fragment,
                    'folder_path' => ($path != '') ? $path : '',
                    'childof' => $myChildOf,
                    'type' => $type,
                    'icon' => $icon,
                    'has_folders' => $has_folders,
                    'has_items' => $has_items
                    ));
            ob_start();
            $fspath = ($path != '') ? $path /* named folder */ : $new_folder /* standard user folder */;

            $myPath = $myPath.'/'.$fspath;
            $myChildOf = $new_folder;

            if (!file_exists($myPath) && !is_dir($myPath)) {
                // Try to create the physical folder
                $state = basics::create_dirtree($myPath);
                if (!$state) {
                    $this->set_error('Error in mkdir "'.$myPath.'": '.ob_get_clean());
                    $this->IDX->remove_folder($this->uid, $new_folder, false); // Remove index information again
                    return false;
                }
            }
            ob_end_clean();
            // In case of a named folder path, we are done here (it cannot be a path as well
            if ($path != '') {
                return $new_folder;
            }
            // Update folder path in index accordingly (if no path name was given)
            $this->IDX->update_folder(array('uid' => $this->uid, 'id' => $new_folder, 'folder_path' => $fspath));
        }
        // The last created folder is the one this variable points to
        return $new_folder;
    }

    /**
     * This method is used to automatically create a system folder within a given
     * profile (IMAP) or the local folders structure
     * @param int profile
     * @param string $type One of sent, waste, junk, drafts, templates
     */
    private function create_system_folder($profile, $type)
    {
        if (!isset($this->sysfolders[$type])) {
            return false;
        }
        // Find out about the profile. In case of POP3, the system folder is a local one
        $Acnt = new DB_Controller_Account();
        $accdata = $Acnt->getAccount($this->uid, false, $profile);
        if (!$profile || !$accdata || $accdata['acctype'] == 'pop3') {
            $profile = 0;
        }
        if ($profile == 0) {
            return $this->create_folder
                    ($GLOBALS['WP_msg'][$this->sysfolders[$type]['msg']]
                    ,$this->IDX->get_system_folder($this->uid, 'mailbox', 0)
                    ,0
                    ,$this->sysfolders[$type]['icon']
                    ,true
                    ,true
                    ,$type
                    );
        } else {
            if (!$this->sysfolders[$type]['imap']) return; // Not possible in IMAP
            // First try the INBOX folder
            $state = $this->create_folder
                    ($this->sysfolders[$type]['imap']
                    ,$this->IDX->get_folder_id_from_path($this->uid, $profile.':INBOX')
                    ,10
                    ,$this->sysfolders[$type]['icon']
                    ,true
                    ,true
                    );
            if ($state) return true;
            // Didn't do, try the root folder
            $this->create_folder
                    ($this->sysfolders[$type]['imap']
                    ,$this->IDX->get_folder_id_from_path($this->uid, $profile.':')
                    ,10
                    ,$this->sysfolders[$type]['icon']
                    ,true
                    ,true
                    );
            return true;
        }
    }

    /**
     * Creates an internal folder, which will not appear in the index
     * @param  string  $path  Path of the folder within the docroot
     * @return   boolean  TRUE on success; FALSE otherwise
     * @since 0.2.3
     */
    public function create_internal_folder($path)
    {
        if (!$path) {
        	$this->set_error('Please give me a path to create');
        	return false;
        }
        $path = $this->userroot.'/'.$path;
        // Try to create the physical folder
        $state = basics::create_dirtree($path);
        if (!$state) {
            $this->set_error('Error in mkdir "'.$path.'": '.ob_get_clean());
            return false;
        }
        return true;
    }

    /**
     * Creates a mailbox root folder in the indexer. The mailbox folder is NOT created in the file system
     *
     * @param  string  $folder  Display name of the folder to create
     *[@param  int  $type  The type of the mailbox; default is 0 (system folder)]
     *[@param  string  $icon  Path to an icon to assign this folder]
     *[@param  bool  $has_folders  Whether this folder can contain subfolders]
     *[@param  bool  $has_items  Whether this folder can contain any items]
     * @return  int  New mailbox' ID in index if successful; FALSE otherwise
     * @since v0.2.3
     * @see  indexer::create_folder()
     */
    public function create_mailbox($folder, $type = 0, $icon = '', $has_folders = true, $has_items = false)
    {
        if (!$folder) {
            return false;
        }
        // Create folder's index and retrieve its ID
        $new_folder = $this->IDX->create_folder(array
                ('uid' => $this->uid
                ,'friendly_name' => $folder
                ,'folder_path' => ''
                ,'childof' => 0
                ,'type' => $type
                ,'icon' => $icon
                ,'has_folders' => $has_folders
                ,'has_items' => $has_items
                ));
        $this->set_error($this->IDX->get_errors());
        return $new_folder;
    }

    /**
     * Rename a folder (change its friendly name)
     *
     * @param int $id  The ID of the folder to give a new name to
     * @param string $name  New name of the folder
     * @return  bool
     * @see  indexer::update_folder()
     */
    public function rename_folder($id, $name)
    {
        $name = phm_stripslashes($name);
        // Clean implementatins should not fail here
        if (!$id || !$name) {
            return false;
        }
        // Look for the type of the folder - if it is an IMAP one, operate on the IMAP box directly
        $info = $this->get_folder_info($id);
        if (11 == $info['type']) {
            list ($srcprof, $srcpath) = explode(':', $info['folder_path'], 2);
            $mbox = $this->connect_imap($info['folder_path'], false, true);
            $srpos = strrpos($srcpath, $mbox->delimiter);
            $basename = (false === $srpos) ? '' : substr($mbox->mboxstring, 0, $srpos);
            $newfpath = (($basename) ? $basename.$mbox->delimiter : '').uctc::convert($name, 'utf8', 'utf7imap');
            if ($mbox->mboxstring == $newfpath) return true;
            $mbox->renameFolder($srcpath, $newfpath);
            $mbox->unsubscribeFolder($srcpath);
            $mbox->subscribeFolder($newfpath);
            return $this->IDX->update_folder(array
                    ('uid' => $this->uid
                    ,'id' => $id
                    ,'friendly_name' => $name
                    ,'folder_path' => $srcprof.':'.$newfpath
                   ));
        } elseif (10 == $info['type']) {
            return true;
        }
        return $this->IDX->update_folder(array('uid' => $this->uid, 'id' => $id, 'friendly_name' => $name));
    }

    /**
     * Remove a folder again (from Indexer and FileSystem)
     * This is done recursively, so watch out for larger structures, which could
     * need too long for PHPs max_execution_time!
     *
     * @param int $id The unique ID of the folder to remove
     * @param bool  TRUE, if the folder should not be moved to the waste bin
     * @param bool TRUE to allow deleting system folders, FALSE not to; Default: true
     * @return bool  TRUE on success, FALSE on failure
     * @see  indexer::remove_folder()
     */
    public function remove_folder($id, $forced = false, $system = true)
    {
        $info = $this->get_folder_info($id);
        if (empty($info) || empty($id)) return false;

        // System folders should not be deleted through the frontend
        if (!$system && $info['type']%10 == 0) return false;
        // Folders are only really deleted, when the correct flag is given or they are
        // a *direct* child of the waste bin
        if (!$forced) {
            $move = false;
            if (11 == $info['type']) {
                // IMAP folder -> Find the wastebin of this respective IMAP mailbox
                $mbox = $this->connect_imap($info['folder_path'], false, true);
                $profile = explode(':', $info['folder_path']);
                $waste = $this->get_folder_id_from_path($profile[0].':INBOX'.$mbox->delimiter.'Trash');
                // Support one-level IMAP boxes with a standard folder naming
                if (!$waste) {
                    $waste = $this->get_folder_id_from_path($profile[0].':Trash');
                }
                if (!$waste) {
                    $waste = $this->get_system_folder('waste', $profile[0], false);
                }
            } else {
                $waste = $this->get_folder_id_from_path('waste', true);
            }
            if (0 == $waste) {
                $move = false;
            } else {
                $move = !($this->folder_is_in_waste($info));
            }
            if ($move) return $this->move_folder($id, $waste);
        }
        // Retrieve affected subfolders
        $list = $this->read_folders_flat($id, 1);
        // Add current folder to the list and adapt the information
        $list[0] = $info;
        $list[0]['level'] = 0;
        $list[0]['path'] = $list[0]['folder_path'];
        // Apply sorting by level in descending order - this causes
        // the folders to be deleted from deepest to highest, thus making sure,
        // that even if the script runs for too long, no orphans without parent
        // are left over
        if (count($list) > 1) {
            $sort = array();
            foreach ($list as $k => $v) { $sort[$k] = $v['level']; }
            array_multisort($sort, SORT_DESC, $list);
        }
        // Look for the type of the folder - if it is an IMAP one, operate on the IMAP box directly
        if (11 == $info['type']) {
            $mbox = $this->connect_imap($info['folder_path'], false, true);
            if (!is_object($mbox)) {
                return false;
            }
        }
        // Go ahead, deleting everything in sight
        foreach ($list as $k => $v) {
            // Try to remove index entry
            $success = $this->IDX->remove_folder($this->uid, $this->get_folder_id_from_path($v['path']), false, true);
            if (!$success) {
                $this->set_error('Could not remove folder: '.$this->IDX->get_errors());
                return false;
            }
            if (11 == $info['type']) {
                list ($p, $v['path']) = explode(':', $v['path'], 2);
                $mbox->removeFolder($v['path']);
                $mbox->unsubscribeFolder($v['path']);
                $mbox->expunge(true);
            } else {
                basics::emptyDir($this->userroot.'/'.$v['path'], true);
            }
        }
        return true;
    }

    /**
     * This is just a helper method for remove_folder()
     * It tries to find out, whether the folder to delete is a child of a waste folder
     *
     * @param array $info Folder Info array of the folder
     * @return bool TRUE for this is a child of a waste folder, FALSE if not
     * @since 0.9.7
     */
    private function folder_is_in_waste($info)
    {
        while ($info['childof'] != 0) {
            $info = $this->get_folder_info($info['childof']);
            if ($info['icon'] == ':waste') {
                return true;
            }
        }
        return false;
    }

    /**
     * Resyncs index fields with real amount of messages, unread and unseen states
     *
     * @param int $id   Unique ID of the folder to resync
     * @return bool  TRUE on succes, FALSE on failure
     * @since 0.4.2
     */
    public function resync_folder($id) { return $this->IDX->resync_folder($this->uid, $id); }

    /**
     * Moving a folder within the folder hierarchy
     * By moving we mean changing the parent folder info only, since all folders
     * of a certain mailbox are stored wihtin the same physical folder
     *
     * @param  int  $id  The unqiue ID of the folder to move
     * @param  int  $childof  The new parent folder ID
     * @return  bool  TRUE on success, FALSE on failure
     */
    public function move_folder($id, $childof)
    {
        $info = $this->get_folder_info($id);
        $parentinfo = $this->get_folder_info($childof);
        if (11 == $info['type']) {
            // Prevent impossible operations
            if ($parentinfo['type'] != 11 && $parentinfo['type'] != 10) {
                $this->set_error('Cannot move folder to different server');
                return false;
            }
            list ($srcprof, $srcpath) = explode(':', $info['folder_path'], 2);
            list ($tgtprof, $tgtpath) = explode(':', $parentinfo['folder_path'], 2);
            if ($srcprof != $tgtprof) {
                $this->set_error('Cannot move folder to different server');
                return false;
            }
            $mbox = $this->connect_imap($info['folder_path'], false, true);
            $srpos = strrpos($srcpath, $mbox->delimiter);
            $leafname = substr($srcpath, ($srpos === false) ? 0 : $srpos+1);
            $newfpath = ($tgtpath) ? $tgtpath.$mbox->delimiter.$leafname : $leafname;

            $mbox->renameFolder($srcpath, $newfpath);
            $mbox->unsubscribeFolder($srcpath);
            $mbox->subscribeFolder($newfpath);
            $mbox->close();
            return $this->IDX->update_folder(array('uid' => $this->uid, 'id' => $id, 'folder_path' => $srcprof.':'.$newfpath));
        }
        // Update folder path in index accordingly
        return $this->IDX->update_folder(array('uid' => $this->uid, 'id' => $id, 'childof' => $childof));
    }

    public function get_folder_info($folder)
    {
        $info = $this->IDX->get_folder_info($this->uid, $folder);
        if (false === $info || empty($info)) {
            return false;
        }
        $Trans = $this->translate(array($info), $GLOBALS['WP_msg']);
        $info['foldername'] = $Trans[0]['foldername'];
        return $info;
    }

    /**
     * Returns the ID of the folder, matching path
     *
     * @param  string  Path of the folder
     *[@param  bool  Check for roles and return these (like WASTE, ...); default: false]
     * @param  bool  Return the IDs of the matching folder in all accounts
     * @return  int|array  ID (Array of IDs when $allOfThem is true) of the folder on success, false in failure
     * @since 0.1.8
     */
    public function get_folder_id_from_path($path, $roles = false, $allOfThem = false)
    {
        return $this->IDX->get_folder_id_from_path($this->uid, $path, $roles, (bool) $allOfThem);
    }

    /**
     * Returns ID or list of IDs for a certain type of system folder. Valid types in the moment
     * are: waste, sent, junk, drafts, inbox, imapbox and mailbox. If the optional profile is given,
     * then only the relevant system folder for that IMAP profile is returned. To query the local
     * system folder, specify profile 0.
     *
     * @param  int  ID of the affected user
     * @param string $type  Type of the folder, see description
     *[@param int $profile  Id of the IMAP profile, 0 for a local folder]
     *[@param bool $autocreate  Allow the system to create system folders not found; Default: true]
     * @return array  An array consisting of arrays, which hold folder ID, folder path and profile ID
     * @since 0.7.2
     */
    public function get_system_folder($type, $profile = false, $autocreate = true)
    {
        $folder = $this->IDX->get_system_folder($this->uid, $type, $profile);
        if (!$folder && $profile !== false && $autocreate) {
            // The system folder was not found, we try to create the folder, since this method usually
            // gets called by operations which try to use this folder
            $this->create_system_folder($profile, $type);
            $folder = $this->IDX->get_system_folder($this->uid, $type, $profile);
        }
        return $folder;
    }

    /**
     * Marks all mails in a folder as "seen". This does not affect the (un)read flag.
     *
     * @param integer $id  folder id
     * @return bool
     * @since 4.1.9
     */
    public function folder_setseen($id) { return $this->IDX->folder_setseen($this->uid, $id); }

    /**
     * Save the settings for an individual folder (preview, which fields to show ...)
     *
     * @param int $id   Unique ID of the folder to save the settings for
     * @param string Serialized folder settings, take care for updates by yourself please
     * @since 0.4.5
     */
    public function set_folder_settings($id, $settings)
    {
        return $this->IDX->set_folder_settings($this->uid, $id, $settings);
    }

    /**
     * Retrieves a list of all UIDLs of a specific folder, mainly interesting for IMAP
     *
     * @param int $id
     *[@param string $orderby  Order by this DB field]
     *[@param ASC|DESC $orderdir Order direction]
     * @return array
     * @since 0.5.3
     */
    public function get_folder_uidllist($id, $orderby = false, $orderdir = false, $retfield = 'ouidl')
    {
        return $this->IDX->get_folder_uidllist($this->uid, $id, $orderby, $orderdir, $retfield);
    }

    public function get_imapkids($profile)
    {
        return $this->IDX->get_imapkids($this->uid, $profile);
    }

    /**
     * Read in mail index for specific folder, if folder not already specified
     * on construct, it must be given here
     *[@param   string    Dir to read, not necessary if given on construct]
     *[@param  int  $offset  Where to start getting mail list]
     *[@param  int  $pagesize  How many mails are on one page]
     *[@param  string  $ordby  DB field to order the list by]
     *[@param  ASC|DESC  $orddir  order direction]
     *[@param  string  Search criteria to match mails against, also pass pattern then]
     *[@param  string  Search pattern to match mails against, also pass criteria]
     * @return    mixed    false, if dir not given; empty array, if no content;
     *                     array('mails' => number of mails, 'size' => raw size of mails
     */
    public function init_mails($folder = false, $offset = false, $pagesize = 0, $ordby = false, $orddir = 'ASC', $crit = false, $ptrn = false, $flags = null)
    {
        // Got a previously searched IMAP result
        if (empty($GLOBALS['_PM_']['fulltextsearch']['enabled'])
                && in_array($crit, array('body', 'complete'))) {
            if (empty($this->IMAP_Searched)) {
                $this->IMAP_Searched = '';
            }
            $this->midx = $this->IDX->get_mail_list($this->uid, $this->place, $offset-1, $pagesize, $ordby, $orddir, false, 'ouidl', $this->IMAP_Searched);
            $mails = count($this->IMAP_Searched);
            $this->IMAP_Searched = array();
            return array('mails' => $mails, 'size' => 0);
        }
        // If no dir to work on specified, give up
        if (!$this->place && false === $folder) {
            return false;
        }
        if (!isset($this->place) || !$this->place) {
            $this->place = $folder;
        }
        $folderdata = $this->IDX->get_folder_info($this->uid, $this->place);
        if (!empty($folderdata['mailnum'])) {
            $this->midx = $this->IDX->get_mail_list($this->uid, $this->place, $offset-1, $pagesize, $ordby, $orddir, false, $crit, $ptrn, $flags);
            if (($crit !== false && $ptrn !== false) || !empty($flags)) {
                return $this->IDX->mail_aggregate_search($this->uid, $this->place, false, $crit, $ptrn, $flags);
            }
            return array('mails' => $folderdata['mailnum'], 'size' => $folderdata['mailsize']);
        }
        $this->midx = array();
        return array('mails' => 0, 'size' => 0);
    }

    /**
     * Used to see, whether a search would return any results. Make sure, to only pass
     * criteria == 'body' for IMAP folders!
     * The found message UIDs for IMAP searches are cached for faster access later on
     *
     * @param unknown_type $folder
     * @param unknown_type $criteria
     * @param unknown_type $pattern
     * @return unknown
     */
    public function mail_test_search($fid = false, $criteria = false, $pattern = false, $flags = null)
    {
        // If no dir to work on specified, give up
        if (!$this->place && false === $fid) {
            return false;
        }
        if (!isset($this->place) || !$this->place) {
            $this->place = $fid;
        }
        $folder = $this->IDX->get_folder_info($this->uid, $this->place);
        // Search in IMAP directly (only, if fulltext search is NOT enabled)
        if (empty($GLOBALS['_PM_']['fulltextsearch']['enabled'])
                && in_array($criteria, array('body', 'complete'))) {
            $mbox = $this->connect_imap($folder['folder_path'], true, false);
            $this->IMAP_Searched = $mbox->searchMessages(array('UNDELETED', ($criteria == 'body' ? 'BODY' : 'TEXT'), $pattern));
            return array('mails' => count($this->IMAP_Searched), 'size' => 0);
        }
        if (!empty($folder['mailnum'])) {
            return $this->IDX->mail_aggregate_search($this->uid, $this->place, false, $criteria, $pattern, $flags);
        }
        return array('mails' => 0, 'size' => 0);
    }

    public function mail_pinboard_digest()
    {
        return $this->IDX->mail_pinboard_digest($this->uid);
    }

    /**
     * Retrieves the list of items in a given folder, which exceed a given age
     * @param unknown_type $fid
     * @param unknown_type $age
     */
    public function get_archivable_items($fid, $age)
    {
        return $this->IDX->get_archivable_items($this->uid, $fid, $age);
    }

    /**
     * Returns header fields of a given mail ID. This is fetched from the index data created by
     * $this->init_mails()
     * @param  int  ID of mail to fetch data of
     *[@param  bool  Set to true, if info should be fetched from the indexer directly
     *[@param int  Set this to a specific folder id to retrieve the complete list for that folder]]
     * @return  mixed  false on error; empty array if impossible to retrieve data; array with header fields on success
     */
    public function get_mail_info($id, $direct = false, $folder = false)
    {
        if ($direct) {
            $return = $this->IDX->get_mail_list($this->uid, $folder, false, false, false, false, $id);
            if (empty($return)) {
                return false;
            }
            if (!$folder) {
                $flags = $this->IDX->whitelist_search($this->uid, Format_Parse_Email::parse_email_address($return[0]['from'], null, false, true));
                if (!empty($flags)) {
                    $return[0]['htmlunblocked'] = !empty($flags['htmlunblocked']) ? $flags['htmlunblocked'] : false;
                    $return[0]['process_cal'] = !empty($flags['process_cal']) ? $flags['process_cal'] : false;
                    $return[0]['process_vcf'] = !empty($flags['process_vcf']) ? $flags['process_vcf'] : false;
                }
                return $return[0];
            }
            return $return;
        }
        if (!isset($this->midx) && !$this->init_mails()) {
            return false;
        }
        return isset($this->midx[$id]) ? $this->midx[$id] : array();
    }

    /**
     * Allows to check for the type of a given mail
     *
     * @param int $id  IDX of the mail
     * @return string  One of 'mail','sms','ems','mms','fax','appointment','away','receipt','sysmail'
     * @since 4.4.7
     */
    public function get_mail_type($id)
    {
        return $this->IDX->mail_get_type($this->uid ? $this->uid : null, $id);
    }

    /**
     * Find the previous and next mails in list relative to given mail ID.
     * Right now there's no other sorting available than by date, DESC.
     *
     * @param int $id
     */
    public function mail_prevnext($id)
    {
        return $this->IDX->mail_prevnext($this->uid, $id);
    }

    /**
     * Set the status of a mail (Read, Unread, Answered, ...) and combinations of them
     * @param  int  ID of the mail to set the status
     * @param  boolean This mail has been read 1 => yes, 0 => no
     * @param  bool This mail has been answered
     * @param  bool This mail has been forwarded, some IMAP servers might not support settings this flag
     * @param  bool This mail has been bounced, most IMAP servers might not support settings this flag
     *[@param  boolean  IMAP only: Whether to also set this status in the IMAP box or in the index DB only]
     */
    public function mail_set_status($mail = 0, $rd = null, $aw = null, $fw = null, $bn = null, $onlydb = false)
    {
        if (!$mail) {
            return false;
        }
        if (is_null($rd) && is_null($aw) && is_null($fw) && is_null($bn)) {
            return true;
        }
        $sqlstat = $this->IDX->mail_set_status($this->uid, $mail, $rd, $aw, $fw, $bn);
        if ($onlydb) {
            return $sqlstat;
        }

        $mail = $this->get_mail_info($mail, true);
        $info = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
        if (11 == $info['type'] || 10 == $info['type']) {
            $mbox = $this->connect_imap($info['folder_path'], false, false, 0);
            $mailID = $mbox->getNumberByUniqueId($mail['ouidl']);
            $flags = array();
            if (!is_null($rd)) {
                if ($rd) {
                    $flags[] = '\Seen';
                    $mbox->setFlags(array('\Recent'), $mailID, null, '-');
                } else {
                    $mbox->setFlags(array('\Seen'), $mailID, null, '-');
                }
            }
            if (!is_null($aw) && $aw) {
                $flags[] = '\Answered';
            }
            if (!is_null($fw) && $fw) {
                $flags[] = '\Forwarded';
            }
            if (!is_null($bn) && $bn) {
                $flags[] = '\Bounced';
            }
            if (!empty($flags)) {
                $mbox->setFlags($flags, $mailID, null, '+');
            }
            $mbox->close();
        }
        return $sqlstat;
    }

    /**
     * Set (or delete) the colour mark for a mail
     * @param  int  ID of the mail to set the colour mark for
     * @param  string  HTML hex value to set the colour, FALSE to unset it
     *[@param  boolean  IMAP only: Whether to also set this status in the IMAP box or in the index DB only]
     * @since  0.6.4
     */
    public function mail_set_colour($mail = 0, $colour = 'FFFFFF', $onlydb = false)
    {
        if (!$mail) {
            return false;
        }
        $sqlstat = $this->IDX->mail_set_colour($this->uid, $mail, $colour);
        if ($onlydb) {
            return $sqlstat;
        }

        $mail = $this->get_mail_info($mail, true);
        $info = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
        if (11 == $info['type'] || 10 == $info['type']) {
            $mbox = $this->connect_imap($info['folder_path'], false, false, 0);
            $mailID = $mbox->getNumberByUniqueId($mail['ouidl']);
            $mbox->setFlags(array_keys($this->label2colour), $mailID, null, '-');
            $labels = array_flip($this->label2colour);
            if (isset($labels[$colour])) {
                $mbox->setFlags(array($labels[$colour]), $mailID, null, '+');
            }
            $mbox->close();
        }
        return $sqlstat;
    }

    /**
     * Set the "DSN Sent" status of a mail
     * @param  int  ID of the mail to set the DSN_Sent status for
     * @param  bool  TRUE for sent, FALSE for not sent
     * @since  0.4.8
     */
    public function mail_set_dsnsent($mail, $status)
    {
        if (!$mail) {
            return false;
        }
        return $this->IDX->mail_set_dsnsent($this->uid, $mail, $status);
    }

    /**
     * Sets the "Show blocked items" status of a mail in the DB. Defaults to
     * "yes". Mails with this status set to yes load external elements on display
     *
     * @param int $mail ID of the affected mail
     * @param bool $yes Whether to unblock remote HTML elements; Default: true (yes)
     * @return bool
     * @since 0.8.7
     */
    public function mail_set_htmlunblocked($mail = 0, $yes = true)
    {
        if (!$mail) {
            return false;
        }
        return $this->IDX->mail_set_htmlunblocked($this->uid, $mail, $yes);
    }

    /**
     * Get the complete path to a given mail
     *
     * @param  int  ID of the mail
     * @return  string  path to the mail, false on failure
     * @since 0.1.8
     */
    public function mail_get_real_location($mail)
    {
        $return = $this->IDX->mail_get_real_location($this->uid, $mail);
        if (!is_array($return) || !isset($return[2]) || !$return[2]) {
            return array(false, false);
        }
        $path = $this->docroot.'/'.$return[0].'/email/'.$return[1].'/'.$return[2];
        return (!file_exists($path)) ? array(false, false) : $return;
    }


    /**
     * Open a stream to given mail for file system operations
     * @param  int  ID of the mail
     * @param  string  Mode flag, see PHP function fopen() for possible values
     * @return  resource  Resource ID of the opened stream, false on failure
     */
    public function mail_open_stream($id = 0, $flag = 'r')
    {
        $this->streamType = false;
        $mail = $this->get_mail_info($id, true);
        $info = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
        if (11 == $info['type'] || 10 == $info['type']) {
            $mbox = $this->connect_imap($info['folder_path'], true);
            if (false !== $mbox) {
                $this->bytes = $mbox->getRawContent($mbox->msgno($mail['ouidl']));
                $this->streamType = 'imap';
                $this->mbox = $mbox;
                return true;
            }
            return false;
        }
        $return = $this->IDX->mail_get_real_location($this->uid, $id);
        $path = $this->docroot.'/'.$return[0].'/email/'.$return[1].'/'.$return[2];
        if (!file_exists($path)) {
            return false;
        }
        $this->fh = fopen($path, $flag);
        if (!is_resource($this->fh) || !$this->fh) {
            return false;
        } else {
            $this->streamType = 'fs';
            return $this;
        }
    }

    /**
     * Set the file pointer of the current stream to a certain position to continue reading
     * from there
     * @param  int  Offset in bytes to point to afterwards
     * @return  bool  TRUE on success, FALSE otherwise
     * @since 0.1.6
     */
    public function mail_seek_stream($offset = 0)
    {
        if (false == $this->streamType || 'imap' == $this->streamType) {
            return false;
        }
        if (!isset($this->fh) || !is_resource($this->fh)) {
            return false;
        }
        $ret = fseek($this->fh, $offset);
        return (bool) $ret;
    }

    /**
     * Reads data from a mail file from the current position up to the number of bytes specified
     * Use mail_seek_stream() to set the file pointer to your desired start position
     * @param  int  Number of bytes to read, if set to 0, one line from the stream is returned
     * @return  mixed  String data read from mail stream on success, FALSE on EOF or failure
     * @since 0.1.6
     */
    public function mail_read_stream($length = 0)
    {
        if (false == $this->streamType) {
            return false; // No stream open
        }
        if ($this->streamType == 'imap') {
            $line = $this->mbox->talk_ml();
            $this->bytes -= strlen($line);
            if (false === $line || $this->bytes <= 0) {
                $this->streamType = false;
                return false;
                // while (false !== $this->talk_ml()) { /* void */ }
            }
            return $line;
        }
        if (!isset($this->fh) || !is_resource($this->fh)) {
            return false;
        }
        if (!$length) {
            if (false !== feof($this->fh)) {
                fclose($this->fh);
                unset($this->fh);
                return false;
            }
            $line = fgets($this->fh, 1024);
            return $line;
        }
        return fread($this->fh, $length);
    }

    /**
     * Closes a previously opened stream again
     * @param  void
     * @return  void
     * @since 0.2.2
     */
    public function mail_close_stream()
    {
        if (isset($this->fh) && is_resource($this->fh)) {
            fclose($this->fh);
            unset($this->fh);
        }
    }

    /**
     * Returns the header data read from an actual mail. Available either as
     * formatted key => value pairs, unformatted key => value pairs or raw data
     * @param    int    ID of mail to work on
     * [@param    string    'raw' for raw string data; 'unformatted' for unformatted
     *                      key => value pairs; 'formatted' for formatted key => value
     *                      pairs (Default)]
     * @return    mixed     Depends on second parameter. 'raw' returns the unformatted
     *                      mail header as string, the other two options force array data
     */
    public function get_mail_header($id, $type = 'formatted', $imap_part = null)
    {
        $header = '';
        try {
            $mail = $this->get_mail_info($id, true);
            if (!$mail['cached']) {
                $folderdata = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
                $mbox = $this->connect_imap($folderdata['folder_path'], true);
                $header = $mbox->getRawHeader($mbox->getNumberByUniqueId($mail['ouidl']), $imap_part);
            } else {
                $this->mail_open_stream($id);
                while (true) {
                    $line = $this->mail_read_stream(0);
                    $header .= $line;
                    if (trim($line) == '') {
                        $this->mail_close_stream();
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            // void
        }
        if ($type == 'raw') {
            return $header;
        }
        return Format_Parse_Email::parse_mail_header($header);
    }

    /**
     * Returns the structure of the mail
     * @param int  ID of the mail
     * @return array  structure of the mail
     * @since 0.1.7
     */
    public function get_mail_structure($id)
    {
        return unserialize($this->IDX->mail_get_structure($this->uid, $id));
    }

    /**
     * Initialises the transfer of a (given part form a) given IMAP mail
     *
     * @param int $id
     *[@param null|int $part If specified, refers to the part, if not, the whole mail is assumed]
     * @return array  The mbox handle and the number of bytes we gonna read
     */
    public function get_imap_part($id, $part = null)
    {
        $mail = $this->get_mail_info($id, true);
        $folderdata = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
        $mbox = $this->connect_imap($folderdata['folder_path'], true);
        if (empty($mbox)) {
            return false;
        }
        $bytes = $mbox->getRawContent($mbox->getNumberByUniqueId($mail['ouidl']), $part);
        return array($mbox, $bytes);
    }

    /**
     * Move a mail in the file system and the indexer as well
     * @param  int  ID of the mail to move
     * @param  int  folder ID to move the mail to
     * @return  bool  TRUE on success, false otherwise
     * @since 0.0.9
     */
    public function move_mail($id, $folder)
    {
        return $this->copy_mail($id, $folder, true);
    }

    /**
     * Copy a mail in the file system and the indexer as well
     * @param  int  ID of the mail to move
     * @param  int  folder ID to move the mail to
     *[@param bool Actually move the mail, not copy it; Default: false]
     * @return  bool  TRUE on success, false otherwise
     * @since 0.1.4
     */
    public function copy_mail($id, $folder, $move = false)
    {
        $cached = true;
        $filename = null;
        $new_ouidl = null;
        // Make sure we don't try to copy a mail onto itself and delete it afterwards
        $mail = $this->get_mail_info($id, true);
        $imap_flags = ($mail['status'] == 1 || !$move) ? array('\Seen') : array();
        if ($move && $mail['folder_id'] == $folder) {
            return true;
        }
        $src = $this->get_folder_info($mail['folder_id']);
        $targ = $this->get_folder_info($folder);
        if (!$targ || empty($targ)) {
            $this->set_error('I don\'t know destination folder ID "'.$folder.'"');
            return false;
        }
        // Source and Destination are IMAP
        if ((11 == $src['type'] || 10 == $src['type']) && (11 == $targ['type'] || 10 == $targ['type'])) {
            list ($srcpr, $srcfld) = explode(':', $src['folder_path'], 2);
            list ($tgtpr, $tgtfld) = explode(':', $targ['folder_path'], 2);
            if ($srcpr == $tgtpr) { // Same server account
                $mbox = $this->connect_imap($src['folder_path'], false, false, 0);
                try {
                    $oldID = $mbox->getNumberByUniqueId($mail['ouidl']);
                    $state = $mbox->copyMessage($tgtfld, $oldID);
                    if ($move && $state) {
                        $mbox->removeMessage($oldID);
                        $mbox->expunge();
                    }
                    $mbox->close();
                    $new_ouidl = 'phlyMail-Temp';
                    $cached = false;
                } catch (Exception $e) {
                    return false;
                }

            } else { // different accounts
                $mbx1 = $this->connect_imap($src['folder_path'], !$move, false, 0);
                $mbx2 = $this->connect_imap($targ['folder_path'], false, false, 1);
                $oldID = $mbx1->getNumberByUniqueId($mail['ouidl']);
                $bytes = $mbx1->getRawContent($oldID);
                $mbx2->appendMessage($mbx2->mboxstring, $bytes, $imap_flags);
                while ($bytes > 0) {
                    $line = $mbx1->talk_ml();
                    $bytes -= strlen($line);
                    $mbx2->append_ml($line);
                }
                $state = $mbx2->finishAppend();
                if (!$state) {
                    $this->set_error($move ? 'Moving messages failed' : 'Copying message failed');
                    $mbx1->close();
                    $mbx2->close();
                    return false;
                }
                $new_ouidl = 'phlyMail-Temp';
                if ($move) {
                    $mbx1->removeMessage($oldID);
                    $mbx1->expunge();
                }
                $mbx1->close();
                $mbx2->close();
                $cached = false;
            }
        // Source is IMAP, destination local
        } elseif (11 == $src['type'] || 10 == $src['type']) {
            $to_path = $this->docroot.'/'.$targ['uid'].'/email/'.$targ['folder_path'];
            if (!file_exists($to_path) || !is_dir($to_path)) {
                basics::create_dirtree($to_path);
            }
            if (!is_writable($to_path)) {
                $this->set_error('Cannot write to '.$to_path);
                return false;
            }
            $filename = uniqid(time().'.', true);
            $mbox = $this->connect_imap($src['folder_path'], !$move, false, 0);
            $oldID = $mbox->getNumberByUniqueId($mail['ouidl']);
            $mbox->retrieve_to_file($oldID, $to_path.'/'.$filename);
            if ($move) {
                $mbox->removeMessage($oldID);
                $mbox->expunge();
            }
            // Make sure, the structure still represents the offests in the newly created file
            $fh = fopen($to_path.'/'.$filename, 'r');
            list ($mailhead, $struct) = Format_Parse_Email::parse($fh);
            unset($struct['last_line']);
            $this->IDX->mail_set_structure($this->uid, $id, serialize($struct));
            $cached = true;
        // Source is local , destination IMAP
        } elseif (11 == $targ['type'] || 10 == $targ['type']) {
            $return = $this->IDX->mail_get_real_location($this->uid, $id);
            $from_path = $this->docroot.'/'.$return[0].'/email/'.$return[1].'/'.$return[2];
            if (!file_exists($from_path)) {
                $this->set_error($from_path.' does not exist');
                return false;
            }
            if (!is_readable($from_path)) {
                $this->set_error('Cannot read from '.$from_path);
                return false;
            }
            $bytes = filesize($from_path);
            $srcFp = fopen($from_path, 'r');
            $mbox = $this->connect_imap($targ['folder_path'], false, false);
            $mbox->appendMessage($mbox->mboxstring, $bytes, $imap_flags);
            while ($bytes > 0) {
                $line = fgets($srcFp);
                $bytes -= strlen($line);
                $mbox->append_ml($line);
            }
            $state = $mbox->finishAppend();
            if (!$state) {
                $this->set_error('Moving messages failed');
                $mbox->close();
                fclose($srcFp);
                return false;
            }
            if ($move) {
                unlink($from_path);
            }
            $cached = false;
        // Source and destination are local
        } else {
            list ($orgUid, $path, $filename) = $this->IDX->mail_get_real_location($this->uid, $id);
            $from_path = $this->docroot.'/'.$orgUid.'/email/'.$path.'/'.$filename;
            $to_path = $this->docroot.'/'.$targ['uid'].'/email/'.$targ['folder_path'];
            if (!file_exists($from_path)) {
                $this->set_error($from_path.' does not exist');
                return false;
            }
            if (!file_exists($to_path) || !is_dir($to_path)) {
                basics::create_dirtree($to_path);
            }
            if (!is_readable($from_path) || !is_writable($to_path)) {
                $this->set_error('Reading from '.$from_path.' or writing to '.$to_path.' denied');
                return false;
            }
            if (!$move) {
                $filename = uniqid(time().'.', true);
            }
            $res = copy($from_path, $to_path.'/'.$filename);
            if (!$res) {
                $this->set_error('Copying in the filesystem failed ('.$from_path.' -> '.$to_path.'/'.$filename.')');
                return false;
            }
            if ($move) {
                unlink($from_path);
            }
            $cached = true;
        }
        $state = ($move)
                ? $this->IDX->mail_move($this->uid, $id, $folder, $filename, $cached, $new_ouidl)
                : $this->IDX->mail_copy($this->uid, $id, $folder, $filename, $cached, $new_ouidl);
        if (!$state) {
            $this->set_error($this->IDX->get_errors());
        }
        return $state;
    }

    /**
     * Delete a mail from the file system and the indexer as well
     *
     * [@param  int  ID of the mail to remove]
     * [@param  int  folder ID to empty, only supported with WASTE / JUNK in the moment]
     * [@param  string  Delete mail by UIDL; Currently used by the folder syncer on opening an IMAP folder]
     * [@param  bool  Set to true to immediately delete the mail, not moving to the wastebin; Only used for deleting a mail]
     * @return  bool  TRUE on success, false otherwise
     * @since 0.1.1
     */
    public function delete_mail($id = false, $folder = false, $ouidl = false, $forced = false)
    {
    	if (false !== $folder) {
    		$info = $this->IDX->get_folder_info($this->uid, $folder);
   		    if (':waste' == $info['icon'] || ':junk' == $info['icon']) {
   		        if (11 == $info['type'] || 10 == $info['type']) {
   		            $drop = $this->IDX->get_folder_uidllist($this->uid, $folder);
   		            if (empty($drop)) return true; // Nothing to delete

   		            $mbox = $this->connect_imap($info['folder_path'], false, false);
                    if (is_object($mbox)) {
                        $mbox->removeMessage(1, INF);
                        $mbox->expunge(true);
                        $mbox->close();
                        $this->IDX->mail_delete($this->uid, false, $folder);
                        return true;
                    }
                    return false;
   		        } else {
    		        $this->IDX->mail_delete($this->uid, false, $folder);
  		            $d = opendir($this->userroot.'/'.$info['folder_path']);
   		            while (false != ($file = readdir($d))) {
   		                if ('.' == $file) continue;
   		                if ('..' == $file) continue;
   		                @unlink($this->userroot.'/'.$info['folder_path'].'/'.$file);
   		            }
   		            closedir($d);
    		        return true;
   		        }
    	    } else {
    		    // $this->set_error('Kann Ordner *'.$info['folder_path'].'/'.$folder.' nicht leeren');
    		    // Currently we don't even try to empty folders other than junk or waste
    		    return true;
    		}
    	} elseif (false !== $ouidl) {
    		$ret = $this->IDX->mail_delete($this->uid, false, $folder, $ouidl);
    		if (!$ret) {
                $this->set_error($this->IDX->get_errors());
            }
    		return $ret;
    	} else {
    	    if (!empty($GLOBALS['_PM_']['email']['delete_markread'])) {
                $this->mail_set_status($id, 1);
    	    }
    	    $mail = $this->get_mail_info($id, true);
    		$info = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);
   		    if (11 == $info['type'] || 10 == $info['type']) {
   		        // User held shift on deleting the mail, just kill it
   		        if ($forced) {
                    $mbox = $this->connect_imap($info['folder_path'], false, false);
                    $mailID = $mbox->getNumberByUniqueId($mail['ouidl']);
                    $mbox->removeMessage($mailID);
                    $mbox->expunge(true);
                    $mbox->close();
                    return $this->IDX->mail_delete($this->uid, $id, false);
   		        }
                // IMAP folder -> Find the wastebin of this respective IMAP mailbox
                $profile = explode(':', $info['folder_path'], 2);
                $Acnt = new DB_Controller_Account();
                $accdata = $Acnt->getAccount($this->uid, false, $profile[0]);
                $waste = $accdata['waste'];
                if (0 != $waste) { // The user defined a Junk folder for that profiel -> try to use it
                    $folderInfo = $this->get_folder_info($waste);
                    if (false === $folderInfo || empty($folderInfo)) {
                        $waste = false;
                    }
                } else { // Otherwise try using the system folder for that account, this will only work with IMAP
                    $waste = $this->IDX->get_system_folder($this->uid, 'waste', $profile[0]);
                    $folderInfo = $this->get_folder_info($waste);
                    if (false === $folderInfo || empty($folderInfo)) {
                        $waste = false;
                    }
                }
                if ($waste && $waste != $mail['folder_id']) {
                    return $this->move_mail($id, $waste);
                } elseif ($waste && $waste == $mail['folder_id']) {
                    $mbox = $this->connect_imap($info['folder_path'], false, false);
                    $mailID = $mbox->getNumberByUniqueId($mail['ouidl']);
                    $mbox->setFlags(array('\Deleted'), $mailID);
                    $mbox->expunge(true);
                    $mbox->close();
                    return $this->IDX->mail_delete($this->uid, $id, false);
                } else {
                    return $this->move_mail($id, $this->IDX->get_system_folder($this->uid, 'waste', 0));
                }

   		    }
    		list (, $path, $filename) = $this->IDX->mail_get_real_location($this->uid, $id);
    		if (!$path || !$filename) return true;
    		$from_path = $this->userroot.'/'.$path.'/'.$filename;
    		// File might be missing -> just remove it from the index
    		if (!file_exists($from_path) || !is_readable($from_path) || !is_writable($from_path)) {
    			return $this->IDX->mail_delete($this->uid, $id, false);
    		}
    		// Mails are not deleted directly, instead they get moved to the trash
    		// Exception: User held shift, meaning: drop that mail, don't trash it first
    		if ($path != 'waste' && !$forced) {
    			return $this->move_mail($id, $this->get_folder_id_from_path('waste'));
    		}
    		$ret = $this->IDX->mail_delete($this->uid, $id, false);
    		if (!$ret) {
                $this->set_error($this->IDX->get_errors());
            }
    		unlink($from_path);
    		return $ret;
    	}
    }

    /**
     * Archive a mail. Right now rather simple, since we just handle one single folder for archiving purposes
     *
     * [@param  int  $id  ID of the mail to archive]
     * [@param  int  $folder  folder ID to archive, not supported in the moment]
     * [@param  array|null  $_PM_  Deviant settings for the _PM_ array, e.g. in cronjob runs]
     * @return  bool  TRUE on success, false otherwise
     * @since 4.6.0
     * @todo Obey the rules for a structured archive for local folders (create folders as necessary, too)
     */
    public function archive_mail($id = false, $folder = false, $_PM_ = null)
    {
        if (empty($_PM_)) {
            $_PM_ = $GLOBALS['_PM_'];
        }

    	if (false !== $folder) {
    	    return false;
    	}
        $mail = $this->get_mail_info($id, true);
        $info = $this->IDX->get_folder_info($this->uid, $mail['folder_id']);

        //
        // IMAP folder -> Find the archive folder of this respective IMAP
        // mailbox
        //
        if (11 == $info['type'] || 10 == $info['type']) {

            $newFoldersType = 10;

            $profile = explode(':', $info['folder_path'], 2);
            $Acnt = new DB_Controller_Account();
            $accdata = $Acnt->getAccount($this->uid, false, $profile[0]);
            $archive = $accdata['archive'];
            if (0 != $archive) {
                // The user defined an archive folder for that profile -> try to use it
                $folderInfo = $this->get_folder_info($archive);
                if (false === $folderInfo || empty($folderInfo)) {
                    $archive = false;
                }
            } else {
                // Otherwise try using the system folder for that account
                $archive = $this->IDX->get_system_folder($this->uid, 'archive', $profile[0]);
                $folderInfo = $this->get_folder_info($archive);
                if (false === $folderInfo || empty($folderInfo)) {
                    $archive = false;
                    // Try to create the archive folder
                    if ($this->create_system_folder($profile[0], 'archive')) {
                        $archive = $this->IDX->get_system_folder($this->uid, 'archive', $profile[0]);
                        $folderInfo = $this->get_folder_info($archive);
                        if (false === $folderInfo || empty($folderInfo)) {
                            $archive = false;
                        }
                    }
                }
            }
        } else {

            //
            // Local folders
            //

            $newFoldersType = 0;

            list (, $path, $filename) = $this->IDX->mail_get_real_location($this->uid, $id);
            if (! $path || ! $filename) {
                return false;
            }
            $from_path = $this->userroot . '/' . $path . '/' . $filename;
            // File might be missing -> just remove it from the index
            if (! file_exists($from_path) || ! is_readable($from_path) || ! is_writable($from_path)) {
                return $this->IDX->mail_delete($this->uid, $id, false);
            }
            // in case the mail is not archived already, it gets moved to the
            // archive here
            if ($path != 'archive' && substr($path, 0, 8) !== 'archive/') {
                $archive = $this->get_folder_id_from_path('archive', false);
                $folderInfo = $this->get_folder_info($archive);
                if (false === $folderInfo || empty($folderInfo)) {
                    $archive = false;
                    // Try to create the archive folder
                    if ($this->create_system_folder(0, 'archive')) {
                        $archive = $this->get_folder_id_from_path('archive', false);
                        $folderInfo = $this->get_folder_info($archive);
                        if (false === $folderInfo || empty($folderInfo)) {
                            $archive = false;
                        }
                    }
                }
            }
        }
        if ($archive) {
            // Rules for structurizing the archive are set
            if (!empty($_PM_['archive']['partition_by_year'])
                    || !empty($_PM_['archive']['mimic_foldertree'])) {
                $newPath = array();
                if (!empty($_PM_['archive']['mimic_foldertree'])) {
                    // Walking up the path until we reach the root
                    while (true) {
                        // absolute Abbruchbedingung: wir befinden uns im/ Archivbaum
                        // dies führt beim nochmaligen Archivieren zur
                        // Vervielfältigung der Ordnerstruktur
                        if ($info['icon'] == ':archive') {
                            return true; // TRUE ist ok: Mail soll ja archiviert werden und das ist sie bereits
                        }
                        // Never include the root node for an IMAP box. Just
                        // don't!
                        if ($info['icon'] == ':mailbox' || $info['icon'] == ':imapbox') {
                            break;
                        }
                        // translate system folders
                        if (!empty($info['icon'])
                                && !empty($this->sysfolders[str_replace(':', '', $info['icon'])]['msg'])) {
                            $info['foldername'] = $GLOBALS['WP_msg'][$this->sysfolders[str_replace(':', '', $info['icon'])]['msg']];
                        }
                        array_unshift($newPath, $info['foldername']);
                        // reached the top?
                        if ($info['childof'] == 0 || $info['icon'] == ':inbox') {
                            break;
                        }
                        $info = $this->IDX->get_folder_info($this->uid, $info['childof']);
                        if (empty($info)) {
                            break; // Cannot read folder info -> exit the loop
                        }
                    }
                }

                if (!empty($_PM_['archive']['partition_by_year'])) {
                    $year = strtotime($mail['date_sent']);
                    if (false === $year) {
                        $year = strtotime($mail['date_recv']);
                    }
                    $year = date('Y', $year);
                    if (preg_match('!^[12]\d\d\d$!', $year)) {
                        array_unshift($newPath, $year);
                    }
                }
                $this->create_folder($newPath, $archive, $newFoldersType);
                $this->init_folders(true);
                foreach ($newPath as $searchFragment) {
                    $sub_archive = $this->folder_exists($searchFragment, $archive);
                    if ($sub_archive) {
                        $archive = $sub_archive;
                    }
                }
            }
            //
            // Now go archive the mail
            //
            if ($archive != $mail['folder_id']) {
                // Check, that the mail is not moved onto itself before
                // actually archiving it
                return $this->move_mail($id, $archive);
            } else {
                // Cannot archive twice, just ignore
                return true;
            }
        }
        return false;
    }

    /**
     * Add a mail to the storage and indexer
     * @param  array Mail data
     * [- folder_path string  Path of the folder within docroot to save the mail to]
     * [- folder_id int ID of the folder, either this or the folder_path MUST be given; if both are given, the ID takes precedence]
     * - uidl string Filename of the mail
     * - subject string Subject: header field
     * - from  string  From: header field
     * - to  string  To: header field
     * - cc  string  CC: header field
     * - bcc  string  Bcc: header field
     * - date_recveived  string  Date of reception of that mail
     * - date_sent  string  Date: header field of the mail
     * - size  string  Size of the mail in octets (bytes)
     * - prioritiy  int  Priority setting of the mail (1...5)
     * - attachments  boolean  Does this mail have attachments?
     * [- type string  Type of the item to file ('mail', 'sms', 'ems', 'mms', 'appointment', 'receipt', 'away'); Default: mail]
     * - filed boolean TRUE, if the mail is already saved in the specified location, FALSE if not;
     *          if FALSE is specified, one should pass an open file handle as the second parameter,  where the mail will be read from
     * - struct  serialized mail structure data
     *[@param  resource  Open stream to read the mail data from; necessary, if $data['filed'] == false]
     *[@param  string  Path to the source file, ONLY valid for IMAP folders as the destination!; Default: false]
     * @return int  Mail ID in the index
     * @since 0.1.0
     */
    public function file_mail($data, $res = false, $from_path = false)
    {
        if (!isset($data['folder_path']) && !isset($data['folder_id'])) {
            $this->set_error('Neither folder path nor ID given');
            return false;
        }
        if (isset($data['folder_path']) && !isset($data['folder_id'])) {
            $data['folder_id'] = $this->IDX->get_folder_id_from_path($this->uid, $data['folder_path']);
        }
        // Check, whether the destination folder is an IMAP folder, if so, append the mail file to it.
        $info = $this->IDX->get_folder_info($this->uid, $data['folder_id']);
        if ((11 == $info['type'] || 10 == $info['type']) && $from_path) {
            if ($res && is_resource($res)) {
                $fstats = fstat($res);
                $bytes = $fstats[7];
            } else {
                $res = fopen($from_path, 'r');
                $bytes = filesize($from_path);
            }
            $mbox = $this->connect_imap($info['folder_path'], false, false);
            $tgtFp = $mbox->appendMessage($mbox->mboxstring, $bytes, (isset($data['status']) && $data['status']) ? array('\Seen') : null);
            while ($bytes > 0) {
                $line = fgets($res);
                $bytes -= strlen($line);
                $mbox->append_ml($line);
            }
            $state = $mbox->finishAppend();
            $mbox->close();
            if (!$state) {
                $this->set_error('Could not append mail to IMAP mailbox');
                return false;
            }
            return true;
        }
        $ID = $this->IDX->mail_add($this->uid, $data['folder_id'], $data);
        if (empty($data['delay_resnyc_folder'])) {
            $this->IDX->resync_folder($this->uid, $data['folder_id']);
        }
        if (!isset($data['filed']) || !$data['filed']) {
            if ($res && is_resource($res)) {
                list (, $folderpath, $filename) = $this->IDX->mail_get_real_location($this->uid, $ID);
                $path = $this->userroot.'/'.$folderpath.'/'.$filename;
                if (!file_exists(dirname($path)) || !is_dir(dirname($path))) {
                    basics::create_dirtree(dirname($path));
                }
                $fh = fopen($path, 'w');
                while (!feof($res) && false !== ($line = fgets($res, 1024))) {
                    fputs($fh, $line);
                }
                fclose($fh);
            }
        }
        return $ID;
    }

    /**
     * Retrieve the list of filters defined for the current user
     * @param  string $type  One of incoming, outgoing, system
     *[@param  bool  $global  Whether to fetch the global ones, too; Default: true]
     * @return  array
     * - id  int  databse ID of the filter
     * - name  string  user defined name of that filter
     * - active bool  whether this filter is currently active
     * - layered_id  number of that filter to allow sorting
     * @since 0.3.9
     */
    public function filters_getlist($type = 'incoming', $global = true)
    {
    	return $this->IDX->filters_getlist($this->uid, $type, $global);
    }

    /**
     * Retrieve basic data and all rules for a specific filter
     *
     * @param  int  id of the filter
     * @return  array
     * - id  int  databse ID of the filter
     * - name  string  user defined name of that filter
     * - active  bool  whether this filter is currently active
     * - layered_id  number of that filter to allow sorting
     * - match  string  any|all
     * - move  bool  Whether to move mails matching
     * - move_to  string  Identifier of the target folder (app dependent)
     * - copy  bool  Whether to copy mails matching
     * - copy_to  string  Identifier of the target folder (app dependent)
     * - set_prio  bool  Whether to set a priority for matching mails
     * - new_prio  int  Numeric representation of the priority to set
     * - mark_junk  bool  Whether to mark a matching mail as junk
     * - delete  bool  Whether to delete a matching mail
     * - rules  array  A list of all defined rules with their properties:
     *   - id  int  database ID of the filter rule
     *   - field  string  identifier of the mail header field to match
     *   - operator  string  identifier of the comparison operation to perform
     *   - search  string  String to match
     * @since 0.3.9
     */
    public function filters_getfilter($filter = 0)
    {
    	return $this->IDX->filters_getfilter($this->uid, $filter);
    }

    /**
     * Sets the last use timestamp for a given filter
     * @param int  ID of the filter to set the timestamp for
     * @param  email|sms  You can either set the time for the last SMS sent or the last email
     * @param  int  UNIX timestamp
     * @return bool
     * @since 0.5.0
     */
    public function filters_set_lastuse($filter = 0, $mode = null, $time = 0)
    {
        return $this->IDX->filters_set_lastuse($this->uid, $filter, $mode, $time);
    }

    /**
     * Add a filter to the user's filter list
     *
     * @param  int  user id affected
     * @param  array  Payload to add to the database (@see filters_getfilters for the desired structure, excepting the ID
     *    fields for the filter itself and the rules)
     * @return  bool  TRUE on success, FALSE on any failure
     * @since 0.3.9
     */
    public function filters_addfilter($filter)
    {
    	return $this->IDX->filters_addfilter($this->uid, $filter);
    }

    /**
     * Update a filter
     *
     * @param  int  user id affected
     * @param  array  Payload to add to the database (@see filters_getfilters for the desired structure)
     * @return  bool  TRUE on success, FALSE on any failure
     * @since 0.3.9
     */
    public function filters_updatefilter($filter)
    {
    	return $this->IDX->filters_updatefilter($this->uid, $filter);
    }

    /**
     * Remove a certain filter
     *
     * @param  int  user id affected
     * @param  int  id of the filter
	 * @return  bool  TRUE on success, FALSE on failure
     * @since 0.3.9
     */
    public function filters_removefilter($filter)
    {
    	return $this->IDX->filters_removefilter($this->uid, $filter);
    }

    /**
     * Switch activation state of a filter
     *
     * @param  int  user id affected
     * @param  int  id of the filter
	 * @return  bool  TRUE on success, FALSE on failure
     * @since 0.4.1
     */
    public function filters_activatefilter($filter)
    {
    	return $this->IDX->filters_activatefilter($this->uid, $filter);
    }

    /**
     * Apply a new ordering to the list of filtes
     *
     * @param  int  user id affected
     * @param  int  Filter to get moved around
     * @param  'up'|'down'  Direction to move the filter to
     * @return  bool  TRUE on succes, FALSE on any failure
     * @since 0.3.9
     */
    public function filters_reorder($filters, $dir)
    {
    	return $this->IDX->filters_reorder($this->uid, $filters, $dir);
    }

    /**
     * Checks a given IMAP box for new or deleted folders. While checking this,
     * the folders are updated accordingly in the indexer and the updated structure
     * is returned afterwards
     *
     * @param array  Structure of the box as read from DB
     * @return array  Structure as currently found on the server
     */
    public function init_imapbox($stored)
    {
        $mbox = $this->connect_imap($stored['folder_path']);
        if (!is_object($mbox)) {
            $this->IDX->update_folder(array('id' => $stored['id'], 'uid' => $this->uid, 'stale' => '1'));
            return false;
        }
        $folders = $mbox->listMailbox('', '*', $mbox->onlysubscribed);
        if (!is_array($folders)) {
            $this->IDX->update_folder(array('id' => $stored['id'], 'uid' => $this->uid, 'stale' => '1'));
            return false;
        }
        // Might have been marked as stale - remove mark
        $this->IDX->update_folder(array('id' => $stored['id'], 'uid' => $this->uid, 'stale' => '0'));
        $deli = false;
        $index = array();
        $list = array();
        foreach ($folders as $k => $v) {
            $status = $mbox->status($k);
            $list[strtolower($k)] = array
                    ('real_name' => $k
                    ,'has_folders' => in_array('\Noinferiors', $v['flags']) ? 0 : 1
                    ,'has_items' => in_array('\Noselect', $v['flags']) ? 0 : 1
                    ,'mailnum' => isset($status['messages']) ? $status['messages'] : 0
                    ,'srv_unseen' => isset($status['recent']) ? $status['recent'] : 0
                    ,'unread' => isset($status['unseen']) ? $status['unseen'] : 0
                    ,'uidnext' => isset($status['uidnext']) ? $status['uidnext'] : 0
                    ,'uidvalidity' => isset($status['uidvalidity']) ? $status['uidvalidity'] : 0
                    );
            if ($deli === false) {
                $deli = $v['delim'];
            }
        }
        $mbox->close();


        if (function_exists('vecho')) {
            vecho(print_r($list, true).LF.LF);
        }

        ksort($list);

        if (function_exists('vecho')) {
            vecho(print_r($list, true).LF.LF);
        }

        // Init mailbox node
        $index[1] = array('layered_id' => 1, 'childof' => 0, 'folder_path' => ''
                ,'friendly_name' => $mbox->mboxname, 'type' => 10
                ,'icon' => ':imapbox', 'has_folders' => 1, 'has_items' => 0
                ,'mailnum' => 0, 'mailsize' => 0, 'unread' => 0
                ,'unseen' => 0, 'stale' => 0, 'mailnum' => 0
                ,'srv_unseen' => 0, 'unread' => 0
                ,'uidnext' => 0, 'uidvalidity' => 0
                );
        // Retrieve current list of folders stored for this IMAP proifle (used later)
        $current_folders = $this->IDX->get_imapkids($this->uid, $mbox->profnum, true);
        $currFolderPaths = array();
        foreach ($current_folders as $folder) {
        	$currFolderPaths[$folder['idx']] = $folder['folder_path'];
        }

        // Add the found folders to the cron tab, remove old jobs
        $Cron = new DB_Controller_Cron();

        //
        // Get the system folders
        //
        $i = 2;
        $sysfound = array(':sent' => false, ':drafts' => false, ':templates' => false, ':waste' => false, ':junk' => false, ':archive' => false);
        // Process is two-fold: First look for user-defined folders in the profile,
        // then search for a few common names in the folder list.

        // User defined
        foreach ($mbox->usersysfolders as $k => $v) {
            if (empty($v)) {
                continue; // Not defined
            }
            if (!isset($current_folders[$v])) {
                continue; // Not found in list
            }
            $sysfound[':'.$k] = $v;
        }

        // Iterate the folders
        foreach ($this->imaptranslate as $sysfold => $props) {
            $sysfold = str_replace('ÄÄ', $deli, $sysfold);
            if (isset($list[$sysfold])) {
                if (isset($sysfound[$props['i']])) {
                    if ($sysfound[$props['i']]) {
                        continue;
                    }
                    $sysfound[$props['i']] = 'x';
                }
                $rpos = strrpos($list[$sysfold]['real_name'], $deli);
                $parent = substr($list[$sysfold]['real_name'], 0, $rpos);
                $index[$i] = array
                        ('layered_id' => $i
                        ,'childof' => ('inbox' == $sysfold) ? 1 : false
                        ,'folder_path' => $list[$sysfold]['real_name']
                        ,'friendly_name' => $props['fn']
                        ,'type' => 10
                        ,'icon' => $props['i']
                        ,'has_folders' => $list[$sysfold]['has_folders']
                        ,'has_items' => $list[$sysfold]['has_items']
                    	,'mailnum' => isset($list[$sysfold]['mailnum']) ? $list[$sysfold]['mailnum'] : 0
                    	,'unread' => isset($list[$sysfold]['unread']) ? $list[$sysfold]['unread'] : 0
                    	,'srv_unseen' => isset($list[$sysfold]['srv_unseen']) ? $list[$sysfold]['srv_unseen'] : 0
                    	,'uidnext' => isset($list[$sysfold]['uidnext']) ? $list[$sysfold]['uidnext'] : 0
                    	,'uidvalidity' => isset($list[$sysfold]['uidvalidity']) ? $list[$sysfold]['uidvalidity'] : 0
                        );
                if (!$index[$i]['childof']) {
                    foreach ($index as $k => $v) {
                        if ($v['folder_path'] == $parent) {
                            $index[$i]['childof'] = $k;
                            break;
                        }
                    }
                    if (!$index[$i]['childof']) {
                        $index[$i]['childof'] = 1;
                    }
                }
                unset($list[$sysfold]);
                ++$i;
            }
        }
        foreach ($list as $attr) {
            $rpos = strrpos($attr['real_name'], $deli);
            if ($rpos) {
                $parent = substr($attr['real_name'], 0, $rpos);
                $friendlyname = substr($attr['real_name'], $rpos+1);
            } else {
                $rpos = -1;
                $parent = '';
                $friendlyname = $attr['real_name'];
            }
            $index[$i] = array
                    ('layered_id' => $i
                    ,'childof' => false
                    ,'folder_path' => $attr['real_name']
                    ,'friendly_name' => $friendlyname
                    ,'type' => 11
                    ,'icon' => ''
                    ,'has_folders' => $attr['has_folders']
                    ,'has_items' => $attr['has_items']
                    ,'mailnum' => isset($attr['mailnum']) ? $attr['mailnum'] : 0
					,'srv_unseen' => isset($attr['srv_unseen'] ) ? $attr['srv_unseen'] : 0
					,'unread' => isset($attr['unread'] ) ? $attr['unread'] : 0
					,'uidnext' => isset($attr['uidnext'] ) ? $attr['uidnext'] : 0
                 	,'uidvalidity' => isset($attr['uidvalidity']) ? $attr['uidvalidity'] : 0
                    );
            if (!$index[$i]['childof']) {
                foreach ($index as $k => $v) {
                    if ($v['folder_path'] == $parent) {
                        $index[$i]['childof'] = $k;
                        break;
                    }
                }
                if (!$index[$i]['childof']) {
                    $index[$i]['childof'] = 1;
                }
            }
            ++$i;
        }
        // Handle the special case, where the inbox itself has no children and all folders are on the root level
        if (empty($index[2]['has_folders'])) {
            $index[1]['has_folders'] = true;
        }

        // Add new folders to the index, update settings for existing ones
        $idx_list = array();
        foreach ($index as $num => $data) {
            $exists = array_search($mbox->profnum.':'.$data['folder_path'], $currFolderPaths);
            if (is_null($exists) || !$exists) {
                $id = $this->IDX->create_folder(array
                        ('uid' => $this->uid
                        ,'friendly_name' => ($data['icon'] == ':imapbox') ? $data['friendly_name'] : uctc::convert($data['friendly_name'], 'utf7imap', 'utf8')
                        ,'folder_path' => $mbox->profnum.':'.$data['folder_path']
                        ,'childof' => $index[$data['childof']]['real_id']
                        ,'type' => $data['type']
                        ,'icon' => $data['icon']
                        ,'has_folders' => $data['has_folders']
                        ,'has_items' => $data['has_items']
                        ,'mailnum' => $data['mailnum']
						,'unread' => $data['unread']
                        ,'srv_unseen' => $data['srv_unseen']
                        ,'uidnext' => $data['uidnext']
                        ,'uidvalidity' => $data['uidvalidity']
                        ));
                if ($mbox->cached_mail && !$this->create_internal_folder($id)) {
                    die('Creating internal folder '.$id.' failed: '.print_r($this->error, true));
                }
                $index[$num]['real_id'] = $id;
                // Add a cron job
                if ($data['icon'] != ':imapbox' && $data['has_items']) {
                	$Cron->setJob('email', 'fetchfolder', $id, 120, 5);
                }
            } else {
                // Look for sysfolders set by user, but don't overwrite system icons if already set
                if (substr($data['icon'], 0, 1) != ':') {
                    $userSysFolder = array_search($exists, $sysfound);
                    if (false !== $userSysFolder) {
                        $data['icon'] = $userSysFolder;
                        $data['type'] = 10;
                    }
                }
                $index[$num]['real_id'] = $exists;
                $this->IDX->update_folder(array
                        ('uid' => $this->uid
                        ,'id' => $exists
                        ,'friendly_name' => ($data['icon'] == ':imapbox') ? $data['friendly_name'] : uctc::convert($data['friendly_name'], 'utf7imap', 'utf8')
                        ,'folder_path' => $mbox->profnum.':'.$data['folder_path']
                        ,'childof' => ($data['childof']) ? $index[$data['childof']]['real_id'] : 0
                        ,'type' => $data['type']
                        ,'icon' => $data['icon']
                        ,'has_folders' => $data['has_folders']
                        ,'has_items' => $data['has_items']
                        ,'mailnum' => $data['mailnum']
						,'unread' => $data['unread']
                        ,'srv_unseen' => $data['srv_unseen']
                        ,'uidnext' => $data['uidnext']
                        ,'uidvalidity' => $data['uidvalidity']
                        ));
                $dbFolder = $current_folders[$exists];
                // Cron job for that folder
                if ($data['icon'] == ':imapbox'
                		|| $data['has_items'] == 0) {
                	// void
                } elseif (!$Cron->jobExists('email', 'fetchfolder', $exists)) {
                    // Does not exist yet, so create it
                    $Cron->setJob('email', 'fetchfolder', $exists, 120, 5);
                } elseif ($dbFolder['mailnum'].'-'.$dbFolder['unread'].'-'.$dbFolder['srv_unseen'].'-'.$dbFolder['uidnext'].'-'.$dbFolder['uidvalidity']
                        != $data['mailnum'].'-'.$data['unread'].'-'.$data['srv_unseen'].'-'.$data['uidnext'].'-'.$data['uidvalidity']) {
                    // DB statistics and live statistics differ, so refetch
                    $Cron->markJobAtOnce('email', 'fetchfolder', $exists);
                }
            }
            $idx_list[$index[$num]['real_id']] = $num;
        }
        // Drop folders from the index we no longer have on the server -> this includes cached mail headers!
        foreach ($current_folders as $idx => $path) {
            if (!isset($idx_list[$idx])) {
                $Cron->removeJob('email', 'fetchfolder', $idx);
                $this->IDX->remove_folder($this->uid, $idx);
            }
        }
        return true;
    }

    /**
     * Returns a list of all folders on the server and the subscription status alongside
     *
     * @param int $fid folder ID of the IMAP Box node
     * @return array $return An array containting the folder structure
     * @since 0.8.2
     */
    public function get_imapsubscriptions($fid)
    {
        $folder = $this->get_folder_info($fid);
        $mbox = $this->connect_imap($folder['folder_path'], true, true);
        $list = $subbed = array();
        foreach($mbox->listMailbox('', '*', true) as $k => $v) { $subbed[$k] = 1; }
        foreach ($mbox->listMailbox('', '*', false) as $k => $v) {
            $list[strtolower($k)] = array('real_name' => $k
                    ,'has_folders' => in_array('\Noinferiors', $v['flags'])
                    ,'has_items' => !in_array('\Noselect', $v['flags'])
                    ,'subscribed' => (isset($subbed[$k])), 'delim' => $v['delim']
                    );
        }
        $mbox->close();
        ksort($list);
        // Init mailbox node
        $index[1] = array('layered_id' => 1, 'childof' => 0, 'folder_path' => ''
                ,'friendly_name' => $mbox->mboxname, 'type' => 10, 'icon' => ':imapbox'
                ,'has_folders' => 1, 'has_items' => 0, 'subscribed' => true
                );
        // Get the system folders
        $i = 2;
        $sysfound = array(':sent' => false, ':outbox' => false, ':drafts' => false, ':templates' => false, ':waste' => false, ':junk' => false, ':archive' => false);
        foreach ($this->imaptranslate as $sysfold => $props) {
            $sysfold = str_replace('ÄÄ', $mbox->delimiter, $sysfold);
            if (isset($list[$sysfold])) {
                if (isset($sysfound[$props['i']])) {
                    if ($sysfound[$props['i']]) {
                        continue;
                    }
                    $sysfound[$props['i']] = true;
                }
                $rpos = strrpos($list[$sysfold]['real_name'], $mbox->delimiter);
                $parent = substr($list[$sysfold]['real_name'], 0, $rpos);
                $index[$i] = array
                        ('layered_id' => $i
                        ,'childof' => ('inbox' == $sysfold) ? 1 : false
                        ,'folder_path' => $list[$sysfold]['real_name']
                        ,'friendly_name' => $props['fn']
                        ,'type' => 10
                        ,'icon' => $props['i']
                        ,'has_folders' => $list[$sysfold]['has_folders']
                        ,'has_items' => $list[$sysfold]['has_items']
                        ,'subscribed' => $list[$sysfold]['subscribed']
                        );
                if (!$index[$i]['childof']) {
                    foreach ($index as $k => $v) {
                        if ($v['folder_path'] == $parent) {
                            $index[$i]['childof'] = $k;
                            break;
                        }
                    }
                    if (!$index[$i]['childof']) {
                        $index[$i]['childof'] = 1;
                    }
                }
                unset($list[$sysfold]);
                ++$i;
            }
        }
        foreach ($list as $attr) {
            if ($rpos = strrpos($attr['real_name'], $attr['delim'])) {
                $parent = substr($attr['real_name'], 0, $rpos);
            } else {
                $rpos = -1;
                $parent = '';
            }
            $index[$i] = array('layered_id' => $i, 'childof' => false
                    ,'folder_path' => $attr['real_name']
                    ,'friendly_name' => substr($attr['real_name'], ($rpos+1))
                    ,'type' => 11, 'icon' => '', 'has_folders' => $attr['has_folders']
                    ,'has_items' => $attr['has_items'], 'subscribed' => $attr['subscribed']
                    );
            if (!$index[$i]['childof']) {
                foreach ($index as $k => $v) {
                    if ($v['folder_path'] == $parent) {
                        $index[$i]['childof'] = $k;
                        break;
                    }
                }
                if (!$index[$i]['childof']) {
                    $index[$i]['childof'] = 1;
                }
            }
            ++$i;
        }
        $list = array();
        foreach ($index as $k => $v) {
            $list[$v['childof']][$k] = array
                    ('layered_id' => $v['layered_id']
                    ,'childof' => $v['childof']
                    ,'folder_path' => $folder['folder_path'].$v['folder_path']
                    ,'friendly_name' => ($v['icon'] == ':imapbox') ? $v['friendly_name'] : uctc::convert($v['friendly_name'], 'utf7imap', 'utf8')
                    ,'type' => $v['type']
                    ,'icon' => $v['icon']
                    ,'has_folders' => $v['has_folders']
                    ,'has_items' => $v['has_items']
                    ,'subscribed' => $v['subscribed']
                    );
        }
        return $this->structurize_folders($list, 0);
    }

    /**
     * (Un)Subscribes IMAP folders in an IMAP profile. Pass an array containing
     * the folders. Only folders of a single profile can be processed at once.
     *
     * @param array  List of dolers to (un)subscribe
     * @param bool $mode TRUE for subscribe, FALSE for unsubscribe
     * @return bool TRUE or FALSE, depending on the return status of the IMAP operation
     * @since 0.8.2
     */
    public function subscribe_folders($folders)
    {
        list ($profile) = explode(':', $folders[0]['path'], 2);
        $mbox = $this->connect_imap($profile.':', false, false);
        foreach ($folders as $finfo) {
            list ($profile, $path) = explode(':', $finfo['path'], 2);
            if ($finfo['sub']) {
                $mbox->subscribeFolder($path);
            } else {
                $mbox->unsubscribeFolder($path);
            }
        }
        $mbox->close();
        return true;
    }

    /**
     * This method allows to set certain folders as being "hidden", so they no longer
     * get displayed in the folder listing
     *
     * @param array $folders
     * @return true
     * @since 1.0.2
     */
    public function hide_folders($folders)
    {
        foreach ($folders as $finfo) {
            $this->IDX->hide_folder($this->uid, $finfo['id'], $finfo['visible']);
        }
        return true;
    }

    /**
     * Quota related: Returns the number of folders, the given user has created.
     * @return int $num Number of user defined local folders created
     * @since 0.7.5
     */
    public function quota_getfoldernum($stats = false)
    {
        return $this->IDX->quota_getfoldernum($this->uid, $stats);
    }

    /**
     * Qutoa related: Returns the overall size of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @return int $size Size of all mails in bytes
     * @since 0.7.5
     */
    public function quota_getmailsize($stats = false)
    {
        return $this->IDX->quota_getmailsize($this->uid, $stats);
    }

    /**
     * Qutoa related: Returns the number of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @return int $size Number of all mails
     * @since 0.7.5
     */
    public function quota_getmailnum($stats = false)
    {
        return $this->IDX->quota_getmailnum($this->uid, $stats);
    }

    /**
     * Does a matching of the currently saved cache with the passed list
     *
     * @param int $profile  Profile ID
     * @param array List of mails to match
     *[@param array List from DB to match against, so nothing gets updated in the DB]
     * @return array Lists after matching:
     * [0] -> new elements
     * [1] -> no longer existant elements
     * @since 0.7.6
     */
    public function uidlcache_match($profile, $maillist, $dblist = false)
    {
        return $this->IDX->uidlcache_match($profile, $maillist, $dblist);
    }

    /**
     * Adds an item to the UIDL cache
     *
     * @param int $profile
     * @param string $item
     * @return bool
     * @since 0.7.6
     */
    public function uidlcache_additem($profile, $item)
    {
        return $this->IDX->uidlcache_additem($profile, $item);
    }

    /**
     * Enter description here...
     *
     * @param int $profile
     * @param string $item
     * @return bool  TRUE, if UIDL already in DB, FALSE otherwise
     * @since 0.7.6
     */
    public function uidlcache_checkitem($profile, $item)
    {
        return $this->IDX->uidlcache_checkitem($profile, $item);
    }

    /**
     * Drops all information about already downloaded UIDLs for a certain profile.
     * This method is used by the fetchers when a profile is switched from "Keep on server" to not doing so.
     *
     * @param int $profile
     * @return bool
     * @since 0.7.6
     */
    public function uidlcache_remove($profile)
    {
        return $this->IDX->uidlcache_remove($profile);
    }

    /**
     * Used for profile, where mails deleted locally shall get deleted on the POP3 server, too.
     *
     * @param int $profile
     * @param string $item
     * @return true
     * @since 0.8.1
     */
    public function uidlcache_markdeleted($profile, $item)
    {
        return $this->IDX->uidlcache_markdeleted($profile, $item);
    }

    /**
     * Delivers a list of all UIDLs in a given profile, which were deleted locally, thus can get deleted from the server, too.
     * This applies to all POP3 profile which have leaveonserver On and localkillserver On.
     *
     * @param int $profile
     * @return array
     * @since 0.8.1
     */
    public function uidlcache_getdeleted($profile)
    {
        return $this->IDX->uidlcache_getdeleted($profile);
    }

    public function whitelist_addfilter($filter, $html = null, $ical = null, $vcf = null)
    {
        return $this->IDX->whitelist_addfilter($this->uid, $filter, $html, $ical, $vcf);
    }

    public function whitelist_getfilter($id)
    {
        return $this->IDX->whitelist_getfilter($this->uid, $id);
    }

    public function whitelist_getlist()
    {
        return $this->IDX->whitelist_getlist($this->uid);
    }

    public function whitelist_search($email)
    {
        return $this->IDX->whitelist_search($this->uid, $email);
    }

    //
    // Private methods
    //

    /**
     * Connect to the IMAP box depending on the profile stored in the folder path
     *
     * @param string $folder_path This holds the profile ID and the path within the mailbox
     *[@param unknown_type $ro  TRUE for readonly access (prevents setting of Recent flags); Default: false]
     * @param unknown_type $to_root  TRUE to open the root mailbox; Default: FALSE]
     * @return bool
     */
    private function connect_imap($folder_path, $ro = false, $to_root = false)
    {
        $profile = explode(':', $folder_path);
        if (!isset($profile[1]) || !$profile[0]) {
            return false;
        }
        $Acnt = new DB_Controller_Account();
        $account = $Acnt->getAccount(0, null, $profile[0]);
        // The profile no longer exists, so we cannot connect to it at all - remove it from the index!
        if (!isset($account['popserver']) && !isset($account['popport'])) {
            foreach ($this->get_imapkids($profile[0]) as $k => $v) {
                $this->IDX->remove_folder($this->uid, $k, false, true);
            }
            return true;
        }
        $account['popport'] = ($account['popport']) ? $account['popport'] : 143;
        $mbox = new Protocol_Client_IMAP($account['popserver'], $account['popport'], 0, $account['popsec'], $account['popallowselfsigned']);
        // Not connected
        if ($mbox->check_connected() !== true) {
            return false;
        }
        $mbox->mboxstring = ($to_root || !$profile[1]) ? 'INBOX' : $profile[1];
        $mbox->mboxpath   = $profile[1];

        $status = $mbox->login($account['popuser'], $account['poppass'], $mbox->mboxstring);
        if (!$mbox->check_connected()) {
            return false;
        }
        $mbox->cached_headers = $mbox->cached_mail = false;
        if (isset($account['cachetype'])) {
            $mbox->cached_headers = ($account['cachetype'] == 'struct' || $account['cachetype'] == 'full');
            $mbox->cached_mail = ($account['cachetype'] == 'full');
        }
        $mbox->mboxname = $account['accname'];
        $mbox->profnum = $profile[0];
        $mbox->onlysubscribed = (isset($account['onlysubscribed']) && $account['onlysubscribed']);
        $mbox->secure = ($status['type'] == 'secure') ? 1 : 0;
        $mbox->usersysfolders = array('inbox' => $account['inbox'], 'drafts' => $account['drafts'], 'templates' => $account['templates']
                ,'sent' => $account['sent'], 'waste' => $account['waste'], 'junk' => $account['junk'], 'archive' => $account['archive']);
        if ($to_root || !$profile[1]) {
            $this->folder_mark_secure($profile[0], $mbox->secure);
        }
        if ($to_root) { // Query hierarchy delimiter form the server
            $folders = $mbox->listMailbox('', '%', $mbox->onlysubscribed);
            foreach ($folders as $k => $v) {
                $mbox->delimiter = $v['delim'];
            }
        }
        return $mbox;
    }

    public function translate($return, $language)
    {
        foreach ($return as $k => $v) {
            if (in_array($v['type'], array(0, 10, 20))) {
                foreach ($this->sysfolders as $data) {
                    if ($v['icon'] == $data['icon']) {
                        $return[$k]['foldername'] = (isset($language[$data['msg']])) ? $language[$data['msg']] : $v['foldername'];
                        break;
                    }
                }
            }
            if (isset($v['subdirs']) && is_array($v['subdirs'])) {
                $return[$k]['subdirs'] = $this->translate($v['subdirs'], $language);
            }
        }
        return $return;
    }

    private function structurize_folders($data, $parent_id = 0, $path = '')
    {
        // Not valid parent ID
        if (!isset($data[$parent_id])) {
            return false;
        }
        $return = array();
        foreach ($data[$parent_id] as $k => $v) {
            $return[$k] = array('path' => $k, 'folder_path' => $v['folder_path']
                    ,'icon' => $v['icon'], 'foldername' => $v['friendly_name']
                    ,'type' => $v['type'], 'has_folders' => $v['has_folders']
                    ,'has_items' => $v['has_items'], 'subscribed' => $v['subscribed']
                    ,'subdirs' => (isset($data[$k])) ? $this->structurize_folders($data, $k) : false
                    );
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
    }
}
