<?php
/**
 * Offering API calls for interoperating with other handlers
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Files
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.1 2012-05-23 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_files_api
{
    protected $STOR;
    protected $uid;
    protected $MIME;
    protected $foldersInit;

    /**
     * Constructor method
     *
     * @param  array reference  public settings structure
     * @param  int  ID of the user to perform the operation for
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid)
    {
        $this->uid  = $uid;
        $this->STOR = new handler_files_driver($uid, '');
        $this->MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
    }

    /**
     * Allows other handlers to save an item in a desired folder within given
     * user context. Please be aware, that you will have to pass all necessary
     * meta fields by yourself.
     *
     * @see handler_files_driver::file_item()
     * @sinxe 0.0.1
     */
    public function save_item($data, $res = false)
    {
        global $DB, $WP_msg;

        if (!empty($data['path_canon'])) {
            $info = $this->resolve_path($data['path_canon'], true);
            if (!$info || $info['type'] != 'd') {
                return false;
            }
            $data['folder_id'] = $info['item']['path'];
        }
        $data['friendlyname'] = basename(phm_stripslashes($data['friendlyname']));
        $data['filename'] = uniqid(time().'.', true);
        if (empty($data['type']) || strtolower($data['type']) == 'application/octet-stream') {
            list ($type) = $this->MIME->get_type_from_name($data['friendlyname'], false);
            $data['type'] = ($type) ? $type : 'application/octet-stream';
        }

        // Quotas: Check the space left and how many messages this user might store
        $quota_size_storage = $DB->quota_get($this->uid, 'files', 'size_storage');
        if (false !== $quota_size_storage) {
            $quota_spaceleft = $this->STOR->quota_getitemsize(false);
            $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
        } else {
            $quota_spaceleft = false;
        }
        $quota_number_items = $DB->quota_get($this->uid, 'files', 'number_items');
        if (false !== $quota_number_items) {
            $quota_itemsleft = $this->STOR->quota_getitemnum(false);
            $quota_itemsleft = $quota_number_items - $quota_itemsleft;
        } else {
            $quota_itemsleft = false;
        }
        // No more items allowed to save
        if ((false !== $quota_itemsleft && $quota_itemsleft < 1)
                || (false !== $quota_spaceleft && $quota_spaceleft < 1)) {
            throw new Sabre_DAV_Exception_InsufficientStorage($WP_msg['QuotaExceeded']);
        }
        // End Quotas

        return $this->STOR->file_item($data, $res);
    }


    /**
     * Query some info about a given folder
     *
     * @param int $fid  ID of the folder you are interested in
     * @return array  Detailed info about the folder
     * @see indexer::get_folder_info()
     * @since 0.2.3
     */
    public function get_folder_info($fid)
    {
        $info = $this->STOR->IDX->get_folder_info($this->uid, $fid);
        $trans = $this->STOR->translate(array(0 => $info), $GLOBALS['WP_msg']);
        return $trans[0];
    }

    /**
     * Returns a list of existing folders for a given user
     * @param  void
     * @return  array  Folder list with various meta data
     * @since 0.0.9
     */
    public function give_folderlist()
    {
        if (!$this->foldersInit) {
           $this->foldersInit = $this->STOR->init_folders(false);
        }
        $icon_path = $GLOBALS['_PM_']['path']['theme'].'/icons/';
        $return = array();
        foreach ($this->STOR->read_folders_flat(0) as $k => $v) {
            $v['is_trash'] = 0;
            // Find special icons for folders
            switch ($v['icon']) {
            case ':waste':
                $v['big_icon'] = $icon_path.'waste_big.gif';
                $v['icon'] = $icon_path.'waste.png';
                $v['is_trash'] = 1;
                break;
            case ':files':
                $v['big_icon'] = $icon_path.'files_big.gif';
                $v['icon'] = $icon_path.'files.png';
                break;
            case ':virtual':
                $v['big_icon'] = $icon_path.'virtualfolder_big.gif';
                $v['icon'] = $icon_path.'virtualfolder.png';
                break;
            }
            if (!file_exists($v['icon'])) $v['icon'] = $icon_path.'folder_def.png';
            if (!isset($v['big_icon']) || !file_exists($v['big_icon'])) $v['big_icon'] = $icon_path.'folder_def_big.gif';
            $return[$k] = $v;
        }
        return $return;
    }

    /**
     * Takes a path and tries to find out, whether the referenced item is a dir
     * or a file.
     *
     * @param string $path  Path to parse
     * @param bool  $ext  Extended mode, which returns the item, not just the type
     * @return 'f'|'d'|false  F for a file, d for a dir, false otherwise
     */
    public function resolve_path($path, $ext = false)
    {
        if (!$this->foldersInit) {
           $this->foldersInit = $this->STOR->init_folders(false);
        }
        $parent = dirname($path);
        $me     = basename($path);
        $hit    = false;
        foreach($this->STOR->read_folders_flat() as $folder) {
            if ($folder['path_canon'] == $path) {
                if (!$ext) return 'd';
                return array('type' => 'd', 'item' => $folder);
            }
            if ($folder['path_canon'] == $parent) {
                $hit = $folder;
            }
        }
        if ($hit) {
            if (!$ext) return 'f';
            foreach ($this->give_itemlist($hit['path']) as $file) {
                if ($me == $file['friendly_name']) {
                    return array('type' => 'f', 'item' => $file);
                }
            }
        }
        return false;
    }

    /**
     * Returns all items stored in a folder
     *
     *[@param int $id  ID of the folder]
     *[@param string $path  Canonical path of the folder]
     */
    public function give_itemlist($fid = null, $path = null)
    {
        if (!is_null($path)) {
            if (!$this->foldersInit) {
               $this->foldersInit = $this->STOR->init_folders(false);
            }
            foreach ($this->STOR->read_folders_flat() as $folder) {
                if ($folder['path_canon'] == $path) {
                    $fid = $folder['path'];
                    break;
                }
                if ($folder['path_canon'].'/' == $path) {
                    $fid = $folder['path'];
                    break;
                }
            }
        }
        if (is_null($fid)) return false;
        $return = array();
        $groesse = $this->STOR->init_items($fid);
        $anzahl = isset($groesse['items']) ? $groesse['items'] : 0;
        for ($i = 0; $i < $anzahl; ++$i) {
            $return[] = $this->STOR->get_item_info($i);
        }
        return $return;
    }

    /**
     * Used for getting a list of items in a given folder in a quite generic format
     *
     * @param int $fid  ID of the folder
     * @return array
     */
    public function selectfile_itemlist($fid, $offset = 0, $amount = 100, $orderby = 'friendly_name', $orderdir = 'ASC')
    {
        global $DB, $_PM_;

        $passthrough = give_passthrough(1);
        $return = array();
        $groesse = $this->STOR->init_items($fid, $offset+1, $amount, $orderby, $orderdir);
        $anzahl = isset($groesse['items']) ? $groesse['items'] : 0;
        if ($anzahl == 0) {
            return $return;
        }
        $forEnd = $offset + $anzahl;
        $TN = new DB_Controller_Thumb();

        for ($i = $offset; $i <= $forEnd; $i++) {
            $item = $this->STOR->get_item_info($i);
            if (empty($item)) continue;
            $mimeicon = '';
            $item['l2'] = size_format($item['size']);
            if (substr($item['type'], 0, 6) == 'image/') {
                $thumb = $TN->get('files', $item['id'], 'fselect');
                if (is_array($thumb) && $thumb['mime'] && 0 != $thumb['size']) {
                    $mimeicon = htmlspecialchars(PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&getthumb='.$item['id'].'&type=fselect');
                }
                if ($item['img_w'] && $item['img_h']) {
                    $item['l2'] .= '; '.$item['img_w'].' x '.$item['img_h'];
                }
            }
            if (!$mimeicon) {
                $mimeicon = $this->MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/32', $item['type'], array('png', 'gif', 'jpg'));
                if (!$mimeicon) {
                    $mimeinfo = $this->MIME->get_type_from_name($item['friendly_name'], true);
                    $mimeicon = $this->MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/32', $mimeinfo[0], array('png', 'gif', 'jpg'));
                    $item['type'] = $mimeinfo[0];
                }
                $mimeicon = $_PM_['path']['frontend'].'/filetypes/32/'.$mimeicon;
            }
            $return[] = array
                    ('id' => $item['id']
                    ,'i32' => $mimeicon
                    ,'mime' => $item['type']
                    ,'l1' => $item['friendly_name']
                    ,'l2' => $item['l2'].'; ' . $this->MIME->get_typename_from_type($item['type'], true)
                    );
        }
        return $return;
    }

    /**
     * Inits a SendTo handshake as the initiator of a SendTo. This method is called
     * by the receiving handler to get some info about the mail part it will receive.
     * This info usually is displayed to the user to allow some dedicated action by him.
     *
     * @param int $item  ID of the item you wish to address
     * @since 0.1.1
     */
    public function sendto_fileinfo($item)
    {
        $info = $this->STOR->get_item_info($item, true);
        if (false === $info || empty($info)) return false;
        return array('content_type' => $info['type'], 'encoding' => '8bit'
                ,'charset' => 'UTF-8', 'filename' => $info['friendly_name']
                ,'length' => $info['size']);
    }

    /**
     * SendTo handshake part 2: The receiver now tells us to initialise the sending process.
     *
     * @param int $item ID of the item we wish to read
     * @return bool TRUE on success, FALSE on failure to open the stream for reading from
     * @since 0.1.1
     */
    public function sendto_sendinit($item)
    {
        return $this->STOR->item_open_stream($item, 'r');
    }

    /**
     * Extending the inital SendTo protocol by methods for line or block wise reading.
     *
     *[@param int $len Number of bytes to read at once; Default: 0, which will return max. 1024B]
     * @return string
     * @since 0.1.1
     */
    public function sendto_sendline($len = 0)
    {
        return $this->STOR->item_read_stream($len);
    }

    /**
     * Closes the stream to the sent file again
     *
     * @param void
     * @return void
     * @since 0.1.1
     */
    public function sendto_finish()
    {
        $this->STOR->item_close_stream();
    }

    // Following for WebDAV

    public function remove_dir($path)
    {
        $info = $this->resolve_path($path, true);
        if ($info['type'] != 'd') return false;
        if (in_array($info['item']['icon'], array(':waste', ':files', ':virtual'))) {
            throw new Sabre_DAV_Exception_Forbidden('Cannot delete system folder!');
        }
        return $this->STOR->remove_folder($info['item']['path']);
    }

    public function rename_dir($path, $name)
    {
        $info = $this->resolve_path($path, true);
        if ($info['type'] != 'd') return false;
        if (in_array($info['item']['icon'], array(':waste', ':files', ':virtual'))) {
            throw new Sabre_DAV_Exception_Forbidden('Cannot rename system folder!');
        }
        return $this->STOR->rename_folder($info['item']['path'], $name);
    }

    public function create_dir($path, $name)
    {
        global $DB, $WP_msg;

        // Quotas: Check the space left and how many messages this user might store
        $quota_number_folder = $DB->quota_get($this->uid, 'files', 'number_folders');
        if (false !== $quota_number_folder) {
            $quota_folderleft = $DB->quota_getfoldernum(false);
            $quota_folderleft = $quota_number_folder - $quota_folderleft;
        } else {
            $quota_folderleft = false;
        }
        // No more folders allowed
        if (false !== $quota_folderleft && $quota_folderleft < 1) {
            throw new Sabre_DAV_Exception_InsufficientStorage($WP_msg['QuotaExceeded']);
        }
        // End Quota definitions

        $info = $this->resolve_path($path, true);
        if ($info['type'] != 'd') return false;
        $childof = $info['item']['path'];
        return $this->STOR->create_folder($name, $childof);
    }

    public function remove_item($id)
    {
        return $this->STOR->delete_item($id);
    }

    public function rename_item($id, $name)
    {
        // Should the extension change, the MIME type will probably, too
        list ($type) = $this->MIME->get_type_from_name($name, false);
        $type = ($type) ? $type : null;
        return $this->STOR->rename_item($id, $name, $type);
    }

    public function read_item_content($item)
    {
        $path = $this->STOR->item_get_real_location($item, true);
        if (!file_exists($path)) return false;
        return fopen($path, 'r');
    }

    public function update_item_content($id, $res)
    {
        return $this->STOR->update_item_content($id, $res);
    }
}
