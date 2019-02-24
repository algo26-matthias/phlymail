<?php
/**
 * Proivdes item storage functions for use within the filing system
 * @package phlyMail Nahariya 4.0+
 * @subpackage Files handler
 * @subpackage FS Storage Driver
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.5 2015-02-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_files_driver {

    // Default setting for pagesize: false (unlimited);
    public $pagesize = false;
    private $error = array();
    public $docroot = false;
    public $userroot = false;
    public $sysfolders = array
            ('root' => array('root' => 1, 'msg' => 'MyFilesRootFolder', 'de' => 'Dateien', 'en' => 'Files', 'icon' => ':files')
            ,'waste' => array('root' => 0, 'msg' => 'EmailWasteFolder', 'de' => 'Papierkorb', 'en' => 'Trash', 'icon' => ':waste')
            );

    /**
     * Constructor, expects to be given 2 parameters
     * @param  string  Path to INI file
     * @param  int  ID of the affected user
     *[@param  string  Actual working dir within doc root]
     *[@param  boolean  TRUE to create the user's doc root, FALSE otherwise; Default: FALSE]
     * @return  boolean  false if either dir does not exist or UID not given, true otherwise
     */
    public function __construct($uid = '', $dirname = '', $create = false)
    {
        if (false === $uid) return false;
        // Load indexer driver, instantiate it
        $this->IDX = new handler_files_indexer();
        $this->uid = intval($uid);
        $this->place = false;
        $this->docroot = $GLOBALS['_PM_']['path']['userbase'];
        $this->userroot = $this->docroot.'/'.$this->uid.'/files';
        if ($this->uid > 0 && $create && !basics::create_dirtree($this->userroot)) return false;
        if (file_exists($this->docroot)) {
            if ($dirname) $this->place = $dirname;
            return;
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


    /**
     * Called on installing the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.7
     */
    public function handler_install()
    {
    	return $this->IDX->handler_install();
    }

    /**
     * Called on uninstalling the handler from the Config interface
     * @param void
     * @return boolean
     * @since 0.0.7
     */
    public function handler_uninstall()
    {
    	return $this->IDX->handler_uninstall();
    }


    public function init_folders()
    {
        $this->fidx = $this->IDX->get_folder_structure($this->uid);
        if (empty($this->fidx)) {
            $HFCA = new handler_files_configapi($GLOBALS['_PM_'], $this->uid);
            $status = $HFCA->create_user();
            if ($status) {
                $this->fidx = $this->IDX->get_folder_structure($this->uid);
            } else {
                print_r($HFCA->get_errors());
            }
        }
        return true;
    }

    /**
     * Read all available folders below doc root and return as multidim array,
     * this method is probably only useful for the initial display of all folders
     * within the frontend's folder screen
     * @param    void
     * @return  multi dim array folder structure and meta data
     */
    public function read_folders($parent_id = 0, $path = '')
    {
        $return = false;
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) return false;

        foreach ($this->fidx[$parent_id] as $k => $v) {
            $return[$k] = array
                    ('path' => $k
                    ,'path_canon' => (isset($this->fidx[$parent_id]['path_canon']) ? $this->fidx[$parent_id]['path_canon'] : '')
                            .'/'.($v['icon'] == ':files' ? '' : $v['friendly_name'])
                    ,'icon' => $v['icon']
                    ,'foldername' => $v['friendly_name']
                    ,'type' => $v['type']
                    ,'mtime' => $v['mtime']
                    ,'has_folders' => $v['has_folders']
                    ,'has_items' => ($v['icon'] == ':files' || $v['icon'] == ':waste') ? 1 : $v['has_items']
                    ,'subdirs' => (isset($this->fidx[$k])) ? $this->read_folders($k) : false
                    );
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
    }

    /**
     * Read all available folders below doc root and return as array,
     * opposed to read_folders() the returned array does not reflect the real
     * structure of the folders, just the order and a 'level' attribute will tell
     * you about it.
     * @param    void
     * @return  array of folders and their meta data
     */
    public function read_folders_flat($parent_id = 0, $level = 0, $path_fragment = '')
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) return false;

        foreach ($this->fidx[$parent_id] as $k => $v) {
            $return[$k] = array
                    ('path' => $k
                    ,'path_canon' => $path_fragment.($level > 1 ? '/' : '').($v['icon'] == ':files' ? '/' : $v['friendly_name'])
                    ,'icon' => $v['icon']
                    ,'foldername' => $v['friendly_name']
                    ,'type' => $v['type']
                    ,'mtime' => $v['mtime']
                    ,'has_folders' => $v['has_folders']
                    ,'has_items' => ($v['icon'] == ':files' || $v['icon'] == ':waste') ? 1 : $v['has_items']
                    ,'level' => $level
                    ,'childof' => $v['childof']
                    );
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = true;
                $return = $return + $this->read_folders_flat($k, $level+1, $return[$k]['path_canon']);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
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
        if (!is_writable($this->userroot)) {
            $this->set_error('Insufficient permissions to create folder "'.$this->userroot.'"');
            return false;
        }

        // Create folder's index and retrieve its ID
        $new_folder = $this->IDX->create_folder(array
                ('uid' => $this->uid
                ,'friendly_name' => $folder
                ,'folder_path' => ($path != '') ? $path : ''
                ,'childof' => $childof
                ,'type' => $type
                ,'icon' => $icon
                ,'has_folders' => $has_folders
                ,'has_items' => $has_items
                ));
        ob_start();
        // This is a named folder
        if ($path != '') {
            $new_folder = $path;
        }
        // Try to create the physical folder
        $state = basics::create_dirtree($this->userroot.'/'.$new_folder);
        if (!$state) {
            $this->set_error('Error in mkdir "'.$this->userroot.'/'.$new_folder.'": '.ob_get_clean());
            // Remove index information again
            $this->IDX->remove_folder($this->uid, $new_folder, false);
            return false;
        }
        ob_end_clean();
        // In case of a named folder path, we are done here
        if ($path != '') return $new_folder;

        // Update folder path in index accordingly (if no path name was given)
        $this->IDX->update_folder(array
                ('uid' => $this->uid
                ,'id' => $new_folder
                ,'folder_path' => $new_folder
                ));
        return $new_folder;
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
        if (file_exists($this->userroot.'/'.$path) && is_dir($this->userroot.'/'.$path)) return true;
        ob_start();
        // Try to create the physical folder
        $state = basics::create_dirtree($this->userroot.'/'.$path);
        if (!$state) {
            $this->set_error('Error in mkdir "'.$this->userroot.'/'.$path.'": '.ob_get_clean());
            return false;
        }
        ob_end_clean();
        return true;
    }

    /**
     * Creates a itembox root folder in the indexer. The itembox folder is NOT created in the file system
     *
     * @param  string  $folder  Display name of the folder to create
     *[@param  int  $type  The type of the itembox; default is 0 (system folder)]
     *[@param  string  $icon  Path to an icon to assign this folder]
     *[@param  bool  $has_folders  Whether this folder can contain subfolders]
     *[@param  bool  $has_items  Whether this folder can contain any items]
     * @return  int  New itembox' ID in index if successful; FALSE otherwise
     * @since v0.2.3
     * @see  indexer::create_folder()
     */
    public function create_mailbox($folder, $type = 0, $icon = '', $has_folders = true, $has_items = false)
    {
        if (!$folder) return false;

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
        $this->error = $this->IDX->get_errors();
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
        // Clean implementatins should not fail here
        if (!$id || !$name) return false;

        return $this->IDX->update_folder(array
                ('uid' => $this->uid
                ,'id' => $id
                ,'friendly_name' => $name
                ));
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
        // Folders are only really deleted, when the correct flag is given or they are
        // a *direct* child of the waste bin
        $info = $this->get_folder_info($id);
        // System folders should not be deleted through the frontend
        if (!$system && $info['type'] % 10 == 0) return false;
        if (!$forced) {
            $move = false;
            $waste = $this->get_folder_id_from_path('waste', true);
            if (0 != $waste) {
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
        // Go ahead, deleting everything in sight
        foreach ($list as $k => $v) {
            // Try to remove index entry
            $success = $this->IDX->remove_folder($this->uid, $this->get_folder_id_from_path($v['path']), false, true);
            if (!$success) {
                $this->set_error('Could not remove folder: '.$this->IDX->get_errors());
                return false;
            }
            basics::emptyDir($this->userroot.'/'.$v['path'], true);
        }
        return true;
    }

    /**
     * This is just a helper method for remove_folder()
     * It tries to find out, whether the folder to delete is a child of a waste folder
     *
     * @param array $info Folder Info array of the folder
     * @return bool TRUE for this is a child of a waste folder, FALSE if not
     * @since 0.5.1
     */
    private function folder_is_in_waste($info)
    {
        while ($info['childof'] != 0) {
            $info = $this->get_folder_info($info['childof']);
            if ($info['icon'] == ':waste') return true;
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
     * of a certain itembox are stored wihtin the same physical folder
     *
     * @param  int  $id  The unqiue ID of the folder to move
     * @param  int  $childof  The new parent folder ID
     * @return  bool  TRUE on success, FALSE on failure
     */
    public function move_folder($id, $childof)
    {
        // Update folder path in index accordingly
        return $this->IDX->update_folder(array
                ('uid' => $this->uid
                ,'id' => $id
                ,'childof' => $childof
                ));
    }

    public function get_folder_info($folder)
    {
        return $this->IDX->get_folder_info($this->uid, $folder);
    }

    /**
     * Returns the ID of the folder, matching path
     *
     * @param  string  Path of the folder
     * @return  int  ID of the folder on success, false in failure
     * @since 0.1.8
     */
    public function get_folder_id_from_path($path)
    {
        return $this->IDX->get_folder_id_from_path($this->uid, $path);
    }

    /**
     * Read in item index for specific folder, if folder not already specified
     * on construct, it must be given here
     *[@param   string    Dir to read, not necessary if given on construct]
     *[@param  int  $offset  Where to start getting item list]
     *[@param  int  $pagesize  How many items are on one page]
     *[@param  string  $ordby  DB field to order the list by]
     *[@param  ASC|DESC  $orddir  order direction]
     * @return    mixed    false, if dir not given; empty array, if no content;
     *                     array('items' => number of items, 'size' => raw size of items
     */
    public function init_items($folder = false, $offset = false, $pagesize = 0, $ordby = false, $orddir = 'ASC')
    {
        // If no dir to work on specified, give up
        if (!$this->place && !$folder) return false;

        if (!isset($this->place) || !$this->place) $this->place = $folder;

        $folderdata = $this->IDX->get_folder_info($this->uid, $this->place);
        if (isset($folderdata['itemnum']) && $folderdata['itemnum']) {
            $this->midx = $this->IDX->get_item_list($this->uid, $this->place, $offset, $pagesize, $ordby, $orddir);
            return array('items' => $folderdata['itemnum'], 'size' => $folderdata['itemsize']);
        } else {
            $this->midx = array();
            return array('items' => 0, 'size' => 0);
        }
    }

    /**
     * Returns header fields of a given item ID. This is fetched from the index data created by
     * $this->init_items()
     * @param  int  ID of item to fetch data of
     * [@param  bool  Set to true, if info should be fetched from the indexer directly]
     * @return  mixed  false on error; empty array if impossible to retrieve data;
     *                array with header fields on success
     */
    public function get_item_info($id, $direct = false)
    {
        if ($direct) {
            $return = $this->IDX->get_item_list($this->uid, 0, null, null, null, null, $id);
            return $return[0];
        }
        if (!isset($this->midx)) {
            if (!$this->init_items()) return false;
        }
        $return = isset($this->midx[$id]) ? $this->midx[$id] : array();
        return $return;
    }

    /**
     * Get the complete path to a given item
     *
     * @param  int  ID of the item
     * @return  array|string  path to the item, false on failure
     * @since 0.1.8
     */
    public function item_get_real_location($item, $as_string = false, $nocheck = false)
    {
        $return = $this->IDX->item_get_real_location($this->uid, $item);
        if (!is_array($return) || !isset($return[1]) || !$return[1]) {
            return array(false, false);
        }
        $path = str_replace('//', '/', $this->userroot.'/'.$return[0].'/'.$return[1]);
        if ($nocheck || file_exists($path)) {
            return $as_string ? $path : $return;
        }
        return $as_string ? false : array(false, false);
    }

    public function item_exists($name, $folder)
    {
        return $this->IDX->item_exists($this->uid, $name, $folder);
    }

    /**
     * Open a stream to given item for file system operations
     * @param  int  ID of the item
     * @param  string  Mode flag, see PHP function fopen() for possible values
     * @return  resource  Resource ID of the opened stream, false on failure
     */
    public function item_open_stream($item = 0, $flag = 'r')
    {
        list ($folderpath, $filename) = $this->IDX->item_get_real_location($this->uid, $item);
        $path = $this->userroot.'/'.$folderpath.'/'.$filename;
        if (!file_exists($path)) return false;
        $this->fh = fopen($path, $flag);
        return (!is_resource($this->fh) || !$this->fh) ? false : $this;
    }

    /**
     * Set the file pointer of the current stream to a certain position to continue reading
     * from there
     * @param  int  Offset in bytes to point to afterwards
     * @return  bool  TRUE on success, FALSE otherwise
     * @since 0.1.6
     */
    public function item_seek_stream($offset = 0)
    {
        if (!isset($this->fh) || !is_resource($this->fh)) return false;
        $ret = fseek($this->fh, $offset);
        return ($ret);
    }

    /**
     * Reads data from a item file from the current position up to the number of bytes specified
     * Use item_seek_stream() to set the file pointer to your desired start position
     * @param  int  Number of bytes to read, if set to 0, one line from the stream is returned
     * @return  mixed  String data read from item stream on success, FALSE on EOF or failure
     * @since 0.1.6
     */
    public function item_read_stream($length = 0)
    {
        if (!isset($this->fh) || !is_resource($this->fh)) return false;
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
    public function item_close_stream()
    {
        if (isset($this->fh) && is_resource($this->fh)) {
            fclose($this->fh);
            unset($this->fh);
        }
    }

    /**
     * Move a item in the file system and the indexer as well
     * @param  int  ID of the item to move
     * @param  int  folder ID to move the item to
     * @return  bool  TRUE on success, false otherwise
     * @since 0.0.9
     */
    public function move_item($id, $folder, $forced = false)
    {
        // Make sure we don't try to copy a item onto itself and delete it afterwards
        $info = $this->get_item_info($id, true);
        if ($info['folder_id'] == $folder) return true;

        list ($path, $filename) = $this->IDX->item_get_real_location($this->uid, $id);
        if ($path) $path .= '/';
        $info = $this->IDX->get_folder_info($this->uid, $folder);
        if (!$info || empty($info)) {
            $this->set_error('I don\'t know the folder '.$folder);
            return false;
        }
        $from_path = $this->userroot.'/'.$path.$filename;
        $to_path   = $this->userroot.'/'.$info['folder_path'];
        if (!file_exists($from_path)) {
            $this->set_error($from_path.' does not exist');
            return false;
        }
        if (!file_exists($to_path) || !is_dir($to_path)) basics::create_dirtree($to_path);
        if (!is_readable($from_path) || !is_writable($to_path)) {
            $this->set_error('Read from '.$from_path.' or write to '.$to_path.' not possible');
            return false;
        }
        $res = copy($from_path, $to_path.'/'.$filename);
        if (!$res) {
            $this->set_error('Copying in the file system failed ('.$from_path.' -> '.$to_path.'/'.$filename.')');
            return false;
        }
        $this->IDX->item_move($this->uid, $id, $folder, $forced);
        return unlink($from_path);
    }

    /**
     * Copy a item in the file system and the indexer as well
     * @param  int  ID of the item to move
     * @param  int  folder ID to move the item to
     * @return  bool  TRUE on success, false otherwise
     * @since 0.1.4
     */
    public function copy_item($id, $folder, $forced = false)
    {
        list ($path, $filename) = $this->IDX->item_get_real_location($this->uid, $id);
        if ($path) $path .= '/';
        $info = $this->IDX->get_folder_info($this->uid, $folder);
        if (!$info || empty($info)) {
            $this->set_error('I don\'t know the folder '.$folder);
            return false;
        }
        $from_path = $this->userroot.'/'.$path.$filename;
        $to_path   = $this->userroot.'/'.$info['folder_path'];
        if (!file_exists($from_path)) {
            $this->set_error($from_path.' does not exist');
            return false;
        }
        if (!file_exists($to_path) || !is_dir($to_path)) basics::create_dirtree($to_path);
        if (!is_readable($from_path) || !is_writable($to_path)) {
            $this->set_error('Read from '.$from_path.' or write to '.$to_path.' not possible');
            return false;
        }
        // Give the item a new ID
        $filename = uniqid(time().'.', true);
        $res = copy($from_path, $to_path.'/'.$filename);
        if (!$res) {
            $this->set_error('Copying in the file system failed ('.$from_path.' -> '.$to_path.'/'.$filename.')');
            return false;
        }
        return $this->IDX->item_copy($this->uid, $id, $folder, $filename, $forced);
    }

    /**
     * Delete a item from the file system and the indexer as well
     *
     * [@param  int  ID of the item to remove]
     * [@param  int  folder ID to empty, only supported with WASTE in the moment]
     * @return  bool  TRUE on success, false otherwise
     * @since 0.1.1
     */
    public function delete_item($id = false, $folder = false, $forced = false)
    {
    	if (false !== $folder) {
    		$info = $this->IDX->get_folder_info($this->uid, $folder);
    		if ('waste' == $info['folder_path']) {
    		    $this->IDX->item_delete($this->uid, false, $folder);
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
    	    $TN = new DB_Controller_Thumb();
    	    $TN->drop('files', $id);
    		list ($path, $filename) = $this->IDX->item_get_real_location($this->uid, $id);
    		if (!$filename) return true;
    		$from_path = $this->userroot.'/'.($path ? $path.'/' : '').$filename;
    		// File might be missing -> just remove it from the index
    		if (!file_exists($from_path) || !is_readable($from_path) || !is_writable($from_path)) {
    			return $this->IDX->item_delete($this->uid, $id, false);
    		}
    		// Mails are not deleted directly, instead they get moved to the trash
    		if ($path != 'waste' && !$forced) {
    			return $this->move_item($id, $this->get_folder_id_from_path('waste'));
    		}
    		$ret = $this->IDX->item_delete($this->uid, $id, false);
    		if (!$ret) $this->set_error($this->IDX->get_errors());
    		unlink($from_path);
    		return $ret;
    	}
    }

    /**
     * Rename an item (change its friendly name)
     *
     * @param int $id  The ID of the item to give a new name to
     * @param string $name  New name of the item
     *[@param string $mime  New MIME type of the item
     * @return  bool
     * @see  indexer::update_item()
     */
    public function rename_item($id, $name, $mime = null)
    {
        // Clean implementatins should not fail here
        if (!$id || !$name) return -3;
        return $this->IDX->item_rename($this->uid, $id, $name, $mime);
    }

    /**
     * Add a item to the storage and indexer
     * @param  array item data
     * [- folder_path  string  Path of the folder within docroot to save the item to]
     * [- folder_id  int  ID of the folder, either this or the folder_path MUST be given;
     *          if both are given, the ID takes precedence]
     * - filename  string  Filename of the item
     * - friendlyname  string  Human readable name of the item
     * - type  string  MIME type of the item
     * - size  int  Size in bytes fo the whole file
     * - img_w  int  Width of the item, if an image (JPEG, PNG, ...)
     * - img_h  int  Height of the item, if an image (JPEG, PNG, ...)
     * - filed  boolean TRUE, if the item is already saved in the specified location, FALSE if not;
     *          if FALSE is specified, one should pass an open file handle as the second parameter,
     *          where the item will be read from
     *[@param  resource  Open stream to read the item data from]
     * @return int $ID ID of the newly created DB item
     * @since 0.1.0
     */
    public function file_item($data, $res = false)
    {
        if (!isset($data['folder_path']) && !isset($data['folder_id'])) {
            $this->set_error('Neither folder path nor ID given');
            return false;
        }
        if (isset($data['folder_path']) && !isset($data['folder_id'])) {
            $data['folder_id'] = $this->IDX->get_folder_id_from_path($this->uid, $data['folder_path']);
        }
        $ID = $this->IDX->item_add($this->uid, $data['folder_id'], $data);
        if (!isset($data['filed']) || !$data['filed']) {
            $this->update_item_content($ID, $res);
        } else {
            $TN = new DB_Controller_Thumb();
    	    $TN->drop('files', $ID);
        }
        return $ID;
    }

    public function update_item($item, $params)
    {
        return $this->IDX->item_update($this->uid, $item, $params);
    }

    public function update_item_content($ID, $res)
    {
        if ($res && is_resource($res)) {
            $path = $this->item_get_real_location($ID, true, true);
            if (!file_exists(dirname($path)) || !is_dir(dirname($path))) basics::create_dirtree(dirname($path));
            $written = file_put_contents($path, $res);
            $data = array('size' => filesize($path));
            $ii = getimagesize($path);
            $data['img_w'] = $ii[0];
            $data['img_h'] = $ii[1];
            if (!empty($ii['mime']) && strpos($ii['mime'], 'image/') === 0) {
                $data['type'] = $ii['mime'];
            }
            $this->IDX->item_update($this->uid, $ID, $data);
            $TN = new DB_Controller_Thumb();
    	    $TN->drop('files', $ID);
        }
    }

    /**
     * Internal method to ensure every added or updated item has up-to-date thumbnails
     *
     * @param int $id   ID of the item
     */
    public function create_item_thumbs($id)
    {
        $path = $this->item_get_real_location($id, true);
        if (empty($path)) return false;
        $thumbtypes = array('fdetail' => array('x' => 190, 'y' => 190), 'ftile' => array('x' => 96, 'y' => 64), 'fselect' => array('x' => 32, 'y' => 32));
        $TN = new DB_Controller_Thumb();
  	    $TN->drop('files', $id); // Force recreation of ALL thumbnails for images
        foreach ($thumbtypes as $thumbname => $thumbsize) {
            $thumb = thumbnail::create($path, $thumbsize['x'], $thumbsize['y']);
            if (false !== $thumb) {
                $TN->add('files', $id, $thumbname, $thumb['mime'], $thumb['size'], $thumb['width'], $thumb['height'], $thumb['stream']);
            }
        }
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
    public function quota_getitemsize($stats = false)
    {
        return $this->IDX->quota_getitemsize($this->uid, $stats);
    }

    /**
     * Qutoa related: Returns the number of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @return int $size Number of all mails
     * @since 0.7.5
     */
    public function quota_getitemnum($stats = false)
    {
        return $this->IDX->quota_getitemnum($this->uid, $stats);
    }

    /**
     * Takes care of system folders, whose name in the DB does not necessarily match the
     * langauge, the user has currently chosen
     *
     * @param array $folders  Structurized folder list
     * @param array $language  The languae array (usually $WP_msg)
     * @return array  The folder strcutre, translated
     */
    public function translate($folders, $language)
    {
        foreach ($folders as $k => $v) {
            if (in_array($v['type'], array(0, 10, 20))) {
                foreach ($this->sysfolders as $data) {
                    if ($v['icon'] == $data['icon']) {
                        $folders[$k]['foldername'] = (isset($language[$data['msg']])) ? $language[$data['msg']] : $v['foldername'];
                        break;
                    }
                }
            }
            if (isset($v['subdirs']) && is_array($v['subdirs'])) {
                $folders[$k]['subdirs'] = $this->translate($v['subdirs'], $language);
            }
        }
        return $folders;
    }

    private function structurize_folders($data, $parent_id = 0, $path = '')
    {
        // Not valid parent ID
        if (!isset($data[$parent_id])) return false;

        foreach ($data[$parent_id] as $k => $v) {
            $return[$k] = $v;
            $return[$k]['path']       = $k;
            $return[$k]['foldername'] = $v['friendly_name'];
            if (isset($data[$k])) {
                $return[$k]['subdirs'] = $this->structurize_folders($data, $k);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $this->translate($return, $GLOBALS['WP_msg']);
    }
}

