<?php
/**
 * Proivdes indexing functions for use with a mySQL database
 * @package phlyMail Nahariya 4.0+
 * @subpackage Files handler
 * @subpackage DB abstraction
 * @copyright 2003-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.0 2013-01-24 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_files_indexer extends DB_Controller
{
    private $error = array();
    private $append_errors = false;
    private $retain_errors = false;
    // Constructor
    // Read the config and open the DB
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['core_thumbs'] = $this->DB['db_pref'].'core_thumbs';
        $this->Tbl['files_folders'] = $this->DB['db_pref'].'files_folders';
        $this->Tbl['files_files']   = $this->DB['db_pref'].'files_index';
    }

    private function set_error($error)
    {
        if ($this->append_errors) {
            $this->error[] = $error;
        } else {
            $this->error[0] = $error;
        }
    }

    public function get_errors($nl = "\n")
    {
        $error = implode($nl, $this->error);
        if (!$this->retain_errors) $this->error = array();
        return $error;
    }

    public function get_folder_structure($uid = 0)
    {
        if (false === $uid) return false;
        $return = false;
        $syssorter = 'CASE att_icon WHEN ":files" THEN 0 WHEN ":waste" THEN 1 ELSE 7 END';
        $qh = $this->query
                ('SELECT idx as id,layered_id, childof, folder_path, friendly_name, att_type as type, att_icon as icon'
                .',IF (att_type in(0,10,20), CONCAT('.$syssorter.', "_", IF(att_type IN(0,10,20), 1000+layered_id, friendly_name)), friendly_name) namesorter'
                .',att_has_folders as has_folders, att_has_items as has_items, filenum, filesize, mtime FROM '
                .$this->Tbl['files_folders'].' WHERE uid='.$uid.' ORDER BY childof ASC, namesorter ASC'
                );
        while ($line = $this->assoc($qh)) {
            $return[$line['childof']][$line['id']] = array
                    ('layered_id' => $line['layered_id']
                    ,'childof' => $line['childof']
                    ,'folder_path' => $line['folder_path']
                    ,'friendly_name' => $line['friendly_name']
                    ,'type' => $line['type']
                    ,'icon' => $line['icon']
                    ,'has_folders' => $line['has_folders']
                    ,'has_items' => $line['has_items']
                    ,'filenum' => $line['filenum']
                    ,'filesize' => $line['filesize']
                    ,'mtime' => $line['mtime']
                    );
        }
        return $return;
    }

    /**
    * Removes all information about an user from the index
    *
    * @param  int  User ID affected
    * @return  boolean  TRUE on success, FALSE otherwise
    * @since 0.2.6
    */
    public function remove_user($uid)
    {
        $uid = (int) $uid;
        if (!$uid) return false;
        return ($this->query('DELETE from '.$this->Tbl['files_folders'].' WHERE uid='.$uid)
                && $this->query('DELETE from '.$this->Tbl['files_files'].' where uid='.$uid));
    }

    public function get_folder_info($uid = 0, $folder = 0)
    {
        if (false === $uid) return false;
        if (false === $folder) return false;

        return $this->assoc($this->query
                ('SELECT friendly_name as foldername,`uuid`, childof, folder_path, att_type as `type`, att_icon as icon'
                .', att_big_icon as big_icon, att_has_folders as has_folders, att_has_items as has_items'
                .', filenum itemnum, filesize itemsize,mtime FROM '
                .$this->Tbl['files_folders'].' WHERE uid='.$uid.' AND idx='.$folder
                ));
    }

    /**
    * Try to find a folder by its display name. If the third parameter childof is specified,
    * the search is limited to that folder.
    * This method makes most sense in finding out, whether a folder name is unique, thus avoiding
    * the use of duplicate folder names within the same folder.
    *
    * @param  int  $uid  ID of the affected user, give 0 for global folders
    * @param  string  $folder  Display name of the folder to search for
    * [@param  int  $childof  ID of the folder to limit the search to]
    * @return  int  ID of the folder, if found; FALSE otherwise
    * @since v0.0.4
    */
    public function folder_exists($uid = 0, $folder, $childof = false)
    {
        $query = 'SELECT idx FROM '.$this->Tbl['files_folders'].' where uid='.$uid.' AND friendly_name="'.$folder.'"';
        if ($childof) $query .= ' AND childof='.$childof;
        list ($return) = $this->fetchrow($this->query($query));
        return ($return) ? $return : false;
    }

    /**
    * Try to find the ID of a folder by specifying its path within the docroot
    *
    * @param  int  ID of the affected user, 0 for lobal folders
    * @param  string  folder path to look for
    * @return  int  ID of the folder, if found, FALSE otherwise
    * @since 0.0.9
    */
    public function get_folder_id_from_path($uid = 0, $path)
    {
        $query = 'SELECT idx FROM '.$this->Tbl['files_folders'].' where uid='.$uid.' AND folder_path="'.$path.'"';
        list ($return) = $this->fetchrow($this->query($query));
        return ($return) ? $return : false;
    }

    /**
    * Create index information about a new folder
    *
    * @param array $pass Array holding all necessary information
    * @return int $id Unique ID fo the newly created index entry; false on failure
    */
    public function create_folder($pass)
    {
        // On clean implementation you should not fail here
        if (!isset($pass['uid']))           return false;
        if (!isset($pass['friendly_name'])) return false;
        if (!isset($pass['folder_path']))   return false;
        if (!isset($pass['childof']))       return false;
        if (!isset($pass['type']))          return false;
        // If not specified, set default values
        if (!isset($pass['icon']))        $pass['icon'] = '';
        if (!isset($pass['has_folders'])) $pass['has_folders'] = true;
        if (!isset($pass['has_items']))   $pass['has_items'] = true;
        // A bit lousy, should better be done by subquerying...
        list ($max_layered) = $this->fetchrow($this->query
                ('SELECT max(layered_id) FROM '.$this->Tbl['files_folders']
                .' WHERE childof='.$pass['childof']
                ));
        $query = 'INSERT '.$this->Tbl['files_folders'].' (uid,uuid,friendly_name,folder_path,childof,layered_id,att_type'
                .',att_icon,att_has_folders,att_has_items,filenum,filesize,ctime,mtime)'
                .' VALUES ('.$pass['uid'].',"'.basics::uuid().'","'.$pass['friendly_name'].'","'.$pass['folder_path'].'",'
                .$pass['childof'].','.($max_layered+1).','.$pass['type'].',"'.$pass['icon']
                .'","'.($pass['has_folders'] ? 1 : 0).'","'.($pass['has_items'] ? 1 : 0).'",0,0,NOW(),NOW())';
        if (!$this->query($query)) {
            $this->set_error($this->error());
            return false;
        }
        return $this->insertid();
    }

    /**
    * Update index information about a folder
    *
    * @param array $pass Array holding all necessary information
    * @return bool   TRUE on success, FALSE on failure
    * @since v0.0.6
    */
    public function update_folder($pass)
    {
        // On clean implementation you should not fail here
        if (!isset($pass['uid'])) return false;
        if (!isset($pass['id'])) return false;

        // Construct query string
        $out = array();
        foreach (array
                (array('pass' => 'friendly_name', 'key' => 'friendly_name', 'quote' => true)
                ,array('pass' => 'folder_path', 'key' => 'folder_path', 'quote' => true)
                ,array('pass' => 'childof', 'key' => 'childof', 'quote' => false)
                ,array('pass' => 'layered_id', 'key' => 'layered_id', 'quote' => false)
                ,array('pass' => 'type', 'key' => 'att_type', 'quote' => false)
                ,array('pass' => 'icon', 'key' => 'att_icon', 'quote' => false)
                ,array('pass' => 'has_folders', 'key' => 'att_has_folders', 'quote' => false)
                ,array('pass' => 'has_items', 'key' => 'att_has_items', 'quote' => false)
                ,array('pass' => 'filenum', 'key' => 'filenum', 'quote' => false)
                ,array('pass' => 'filesize', 'key' => 'filesize', 'quote' => false)
                ) as $v) {
            if (!isset($pass[$v['pass']])) continue;
            $out[] = ($v['quote']) ? $v['key'].'="'.$pass[$v['pass']].'"' : $v['key'].'='.$pass[$v['pass']];
        }
        $query = implode(',', $out);
        unset($out);
        // If nothing found to update, return as false
        if (!$query) return false;

        return $this->query('UPDATE '.$this->Tbl['files_folders']
                .' SET `mtime`=NOW(), `uuid`="'.basics::uuid().'",'.$query
                .' WHERE idx='.$pass['id'].' AND uid='.$pass['uid']
                );
    }

    /**
    * Delete index information about a given folder
    *
    * @param int $uid  User ID of the given folder
    * @param int $id   Unique ID of the folder to remove
    * [@param bool $with_childs  Whether to also delete the information about any subfolder - if set to
    *                           TRUE, subfolder information will also be removed; Default: FALSE]
    * [@param bool $with_items  Whether to also delete the items in this folder from the index database;
    *                           if set to TRUE, item information will also be removed; Default: TRUE]
    * @return bool  TRUE on succes, FALSE on failure
    */
    public function remove_folder($uid, $id, $with_childs = false, $with_items = true)
    {
        $id = doubleval($id);
        $affected = false;
        if ($with_childs) {
            $qid = $this->query('SELECT idx FROM '.$this->Tbl['files_folders'].' WHERE childof='.$id.' AND uid='.$uid);
            while (list($child) = $this->fetchrow($qid)) {
                $affected = $this->remove_folder($uid, $child, true);
                if (!$affected) return false;
            }
        }
        if ($with_items) $this->item_delete($uid, false, $id);
        return $this->query('DELETE FROM '.$this->Tbl['files_folders'].' WHERE idx='.$id.' AND uid='.$uid);
    }

    /**
    * Resyncs index fields with real amount of items and their size
    *
    * @param int $uid  User ID of the given folder
    * @param int $id   Unique ID of the folder to resync
    * @return bool  TRUE on succes, FALSE on failure
    * @since 0.1.3
    */
    public function resync_folder($uid, $id)
    {
    	$query = 'SELECT sum(size), count(*) FROM '.$this->Tbl['files_files'].' WHERE folder_id='.intval($id).' AND uid='.intval($uid);
        list ($size, $itemnum) = $this->fetchrow($this->query($query));
    	$query = 'UPDATE '.$this->Tbl['files_folders'].' SET `mtime`=NOW(),`uuid`="'.basics::uuid().'",filesize='.($size+0).', filenum='.($itemnum+0)
    	       .' WHERE idx='.intval($id).' AND uid='.intval($uid);
        return $this->query($query);
    }

    /**
    * Retrieve the item index of a given folder and return as array
    * @param    int    ID of the affected user, give 0 for global folders
    * @param    int    ID of the folder
    * [@param    mixed    Skimming option, pass a FALSE value for no skimming, nonngeative integer for an offset]
    * [@param    mixed    Skimming option, pass a FALSE value for no skimming, nonngeative integer for a pagesize]
    * [@param  string  name of the DB field for ordering by; Default: hdate_sent]
    * [@param  'ASC'|'DESC'  Direction to order; Default: ASC]
    * [@param  int  ID of a item for getting only this item's information, omit everything else, pass folder ID 0 then]
    */
    public function get_item_list($uid = 0, $folder = 0, $offset = false, $pagesize = false, $ordby = false, $orddir = 'ASC', $idx = false, $criteria = false, $pattern = false)
    {
        if (false === $uid) return false;
        if (false === $folder && false === $idx) return false;
        $return = array();
        $q_l = $q_r = '';
        $valid_criteria = array
                ('friendly_name' => 'friendly_name'
                ,'type' => 'type'
                ,'allheaders' => array('friendly_name', 'type')
                );
        if (!$ordby) $ordby = 'type';

        // Limit result set to mails matching search criteria and search pattern
        if ($criteria !== false && $pattern !== false && isset($valid_criteria[$criteria])) {
            $pattern = $this->esc($pattern);
            if (is_array($valid_criteria[$criteria])) {
                $searches = array();
                foreach ($valid_criteria[$criteria] as $crit) $searches[] = $crit.' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
                $q_r .= ' AND ('.implode(' OR ', $searches).')';
            } else {
                $q_r .= ' AND '.$valid_criteria[$criteria].' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
            }
        }

        // Either select data for a single mail or optionally ordered, skimmable data from a folder
        if ($idx) {
            $q_r .= ' AND idx='.intval($idx);
        } elseif ($folder == 0) {
            $q_r .= ' ORDER BY `'.$this->esc($ordby).'` '.($orddir == 'ASC' ? 'ASC' : 'DESC');
        } else {
            $q_r .= ' AND folder_id='.intval($folder).' ORDER BY `'.$this->esc($ordby).'` '.($orddir == 'DESC' ? 'DESC' : 'ASC').($ordby != 'friendly_name' ? ', `friendly_name` ASC' : '');
        }
        $query = 'SELECT idx, idx `id`,`uid`,`uuid`,folder_id, file_name, friendly_name, type, size, img_w, img_h'.$q_l
                .', unix_timestamp(ctime) ctime, unix_timestamp(atime) atime, unix_timestamp(mtime) mtime'
                .' FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.$q_r;
        if ($offset && $idx === false) {
            $query .= ' LIMIT '.(($pagesize) ? intval($offset).', '.intval($pagesize) : intval($offset));
        } elseif ($idx === false) {
            $query .= (($pagesize) ? ' LIMIT 0, '.intval($pagesize) : '');
        }
        $qh = $this->query($query);
        $itemcounter = ($offset) ? $offset : 0;
        while ($line = $this->assoc($qh)) {
            $return[$itemcounter] = $line;
            $return[$itemcounter]['thumb'] = isset($line['thumb']) ? $line['thumb'] : '';
            ++$itemcounter;
        }
        return $return;
    }

    public function item_get_real_location($uid = 0, $item = 0)
    {
        $return = false;
        $qid = $this->query
                ('SELECT f.folder_path, i.file_name FROM '.$this->Tbl['files_files'].' i, '.$this->Tbl['files_folders']
                .' f WHERE i.uid='.$uid.' and i.idx='.$item.' AND i.folder_id=f.idx'
                );
        $return = $this->fetchrow($qid);
        return $return;
    }

    /**
     * Checks, wether a file of the same name already exists in the given folder
     *
     * @param int $uid
     * @param string $name
     * @param int $folder
     */
    public function item_exists($uid = 0, $name, $folder)
    {
        $qid = $this->query('SELECT idx FROM '.$this->Tbl['files_files'].' WHERE uid='.intval($uid)
                .' AND folder_id='.intval($folder).' AND friendly_name="'.$this->esc($name).'"');
        if (!$this->numrows($qid)) return false;
        list ($idx) = $this->fetchrow($qid);
        return $idx;
    }

    /**
     * Tries to rename a file. Possible error conditions: The file ID does not exist,
     * the file cannot be renamed, since another file of that name already is stored
     * in the same folder.
     *
     * @param int $uid
     * @param int $id
     * @param string $name
     *[@param string $mime]
     * @return TRUE on success, FALSE on MySQL failure, -1 on nonexistant ID, -2 on doublette file name
     */
    public function item_rename($uid = 0, $id, $name, $mime = null)
    {
        $id = intval($id);
        $uid = intval($uid);
        $qid = $this->query('SELECT folder_id FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.' AND idx='.$id);
        if (!$this->numrows($qid)) return -1;
        list ($myfolder) = $this->fetchrow($qid);
        if ($this->item_exists($uid, $name, $myfolder)) return -2;
        return $this->query('UPDATE '.$this->Tbl['files_files']
                .' SET `uuid`="'.basics::uuid().'",`friendly_name`="'.$this->esc($name).'"'
                .(!is_null($mime) ? ',`type`="'.$this->esc($mime).'"' : '')
                .' WHERE idx='.$id);
    }

    /**
    * Delete either a single item or all items within a given folder from index
    *
    * @param  int  $uid  The user ID to perform the operation for
    * @param  int  $item  Optionally the item to delete; if FALSE, one MUST specify the affected folder
    * @param  int  $folder  Optionally the folder to delete all items for; if FALSE, one MUST specify the item ID
    * @return  bool  TRUE on success, FALSE on failure
    * @since 0.0.7
    */
    public function item_delete($uid = 0, $item = false, $folder = false)
    {
        if ($item === false && $folder === false) {
            $this->set_error('Please either specify the item or the folder, where all items should be killed');
            return false;
        }

        if ($item !== false) {
            $query = 'SELECT folder_id,size FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.' AND idx='.$item;
            list ($fid, $size) = $this->fetchrow($this->query($query));
            if (!$fid) return false;

            $query = 'DELETE FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.' AND idx='.$item;
            if ($this->query($query)) {
                $this->resync_folder($uid, $fid);
                return true;
            }
            return false;
        } elseif ($folder !== false) {
            $this->query('DELETE FROM '.$this->Tbl['core_thumbs'].' WHERE h="files" AND `item` IN (SELECT idx FROM '.$this->Tbl['files_files'].' WHERE folder_id='.$folder.')');
            if ($this->query('DELETE FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.' AND folder_id='.$folder)) {
                $query = 'UPDATE '.$this->Tbl['files_folders'].' SET `mtime`=NOW(),`uuid`="'.basics::uuid().'",filenum=0,filesize=0 WHERE idx='.$folder;
                $this->query($query);
                $err = $this->error();
                if ($err) {
                    $this->set_error($err);
                    return false;
                }
                return true;
            }
            return false;
        }
        // We fall down here, if some bogus input was given
        return false;
    }

    /**
    * Move a item from one folder to another
    *
    * @param  int  $uid  The user ID to perform the operation for
    * @param  int  $item  Item ID to move
    * @param  int  $folder  The destination folder to move the item to
    * @return  bool  TRUE on success, FALSE on failure
    * @since 0.0.8
    */
    public function item_move($uid = 0, $item = false, $folder = false, $forced = false)
    {
        if ($item === false || $folder === false) {
            $this->set_error('Please specify the item to move and its target folder');
            return false;
        }
        // Get current folder and item size for updating the meta data of old and new folder
        $query = 'SELECT friendly_name,folder_id,size FROM '.$this->Tbl['files_files'].' WHERE idx='.$item;
        list ($friendlyname, $fid, $hsize) = $this->fetchrow($this->query($query));
        if (!$fid || !$hsize) return false;

        $exists = $this->item_exists($uid, $friendlyname, $folder);
        if ($exists) {
            if (!$forced) return -2;
            $expos = strrpos($friendlyname, '.');
            // Yes, we mean "Not found" AND "on position 0"!
            if (!$expos) {
                $basename = $friendlyname;
                $ext = '';
            } else {
                $basename = substr($friendlyname, 0, $expos);
                $ext = substr($friendlyname, $expos);
            }
            $adder = 1;
            while (true) {
                $match = $FS->item_exists($basename.' ('.$adder.')'.$ext, $folder);
                if (!$match) {
                    $friendlyname = $basename.' ('.$adder.')'.$ext;
                    break;
                }
                ++$adder;
            }
        }
        $query = 'UPDATE '.$this->Tbl['files_files'].' SET folder_id='.$folder.', friendly_name="'.$this->esc($friendlyname).'"'
                .' WHERE idx='.$item.' AND uid='.$uid;
        if ($this->query($query)) {
            // Remove from old folder
            $query = 'UPDATE '.$this->Tbl['files_folders']
                    .' SET `mtime`=NOW(),`uuid`="'.basics::uuid().'",filenum=filenum-1,filesize=filesize-'.($hsize+0)
                    .' WHERE idx='.$fid;
            $this->query($query);
            $err = $this->error();
            if ($err) {
                $this->set_error($err);
                return false;
            }
            // Add to new folder
            $query = 'UPDATE '.$this->Tbl['files_folders']
                    .' SET `mtime`=NOW(),`uuid`="'.basics::uuid().'",SET filenum=filenum+1,filesize=filesize+'.($hsize+0)
                    .' WHERE idx='.$folder;
            $this->query($query);
            $err = $this->error();
            if ($err) {
                $this->set_error($err);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
    * Copy a item from one folder to another
    *
    * @param  int  $uid  The user ID to perform the operation for
    * @param  int  $item  Mail ID to copy
    * @param  int  $folder  The destination folder to copy the item to
    * @param  string  $newname  New UID of the item
    * @return  bool  TRUE on success, FALSE on failure
    * @since 0.1.4
    */
    public function item_copy($uid = 0, $item = false, $folder = false, $newname = false, $forced = false)
    {
        if ($item === false || $folder === false || $newname === false) {
            $this->set_error('Please specify the item to copy and its target folder');
            return false;
        }
        // Get current folder and itemsize for updating the meta data of old and new folder
        $query = 'SELECT `friendly_name`, `size` FROM '.$this->Tbl['files_files'].' WHERE idx='.$item;
        $from = $this->assoc($this->query($query));
        if (!$from || empty($from)) return false;
        // Prevent having multiple files of the same name within one folder
        $exists = $this->item_exists($uid, $from['friendly_name'], $folder);
        if ($exists) {
            if (!$forced) return -2;
            $expos = strrpos($from['friendly_name'], '.');
            // Yes, we mean "Not found" AND "on position 0"!
            if (!$expos) {
                $basename = $from['friendly_name'];
                $ext = '';
            } else {
                $basename = substr($from['friendly_name'], 0, $expos);
                $ext = substr($from['friendly_name'], $expos);
            }
            $adder = 1;
            while (true) {
                $match = $FS->item_exists($basename.' ('.$adder.')'.$ext, $folder);
                if (!$match) {
                    $from['friendly_name'] = $basename.' ('.$adder.')'.$ext;
                    break;
                }
                ++$adder;
            }
        }
        // Duplicate item in index
        $query = 'INSERT '.$this->Tbl['files_files'].' SELECT NULL, '.$folder.',uid, "'.$this->esc($newname).'"'
                .', "'.$this->esc($from['friendly_name']).'", type, img_w, img_h, size'
                .', NOW(), NULL, NULL, 0 FROM '.$this->Tbl['files_files'].' WHERE idx='.$item;
        if ($this->query($query)) {
            // Add to new folder
            $query = 'UPDATE '.$this->Tbl['files_folders']
                    .' SET `mtime`=NOW(),`uuid`="'.basics::uuid().'",filenum=filenum+1,filesize=filesize+'.($from['size']+0)
                    .' WHERE idx='.$folder;
            $this->query($query);
            $err = $this->error();
            if ($err) {
                $this->set_error($err);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
    * Add a item to the index
    *
    * @param  int  ID of the user to add the item to
    * @param  int  Folder ID to add the item to
    * @param  array  detailed header and meta information about the item
    * @return  mixed  new ID of the item on success, FALSE otherwise
    * @since 0.0.9
    */
    public function item_add($uid = 0, $folder, $data)
    {
        $query = 'SELECT 1 FROM '.$this->Tbl['files_folders'].' WHERE uid='.$uid.' AND idx='.$folder.' LIMIT 1';
        list ($fold_exists) = $this->fetchrow($this->query($query));
        if (!$fold_exists) {
            $this->set_error('Folder not owned by this user');
            return false;
        }
        $fields = '';
        foreach (array('filename' => 'file_name', 'friendlyname' => 'friendly_name'
                ,'type' => 'type', 'size' => 'size', 'img_w' => 'img_w', 'img_h' => 'img_h'
                ) as $k => $v) {
            $fields .= ', '.$v.'="'.(isset($data[$k]) ? $this->esc($data[$k]) : '').'"';
        }
        $query = 'INSERT '.$this->Tbl['files_files'].' SET uid='.$uid.',`uuid`="'.basics::uuid().'",folder_id='.$folder.',ctime=NOW(),atime=NULL,mtime=NOW()'.$fields;
        $res = $this->query($query);
        if ($res) {
            $newID = $this->insertid();
            $this->resync_folder($uid, $folder);
            return $newID;
        }
        return false;
    }

    public function item_update($uid, $id, $params)
    {
        $uid = floatval($uid);
        $id = floatval($id);
        $query = 'UPDATE '.$this->Tbl['files_files'].' SET `uuid`="'.basics::uuid().'"'
                .(isset($params['size']) ? ',`size`='.floatval($params['size']) : '')
                .(isset($params['img_w']) ? ',`img_w`='.floatval($params['img_w']) : '')
                .(isset($params['img_h']) ? ',`img_h`='.floatval($params['img_h']) : '')
                .(isset($params['type']) ? ',`type`="'.$this->esc($params['type']).'"' : '')
                ;
        $this->query($query.' WHERE `idx`='.$id.' AND uid='.$uid);
        $query = 'SELECT folder_id FROM '.$this->Tbl['files_files'].' WHERE uid='.$uid.' AND idx='.$id;
        list ($fid) = $this->fetchrow($this->query($query));
        $this->resync_folder($uid, $fid);
        return true;
    }

    /**
    * Install the required table(s) for the handler
    * @param  void
    * @return  boolean  return value of the MySQL query
    * @since 0.3.9
    */
    public function handler_install()
    {
    	$query = 'CREATE TABLE IF NOT EXISTS '.$this->Tbl['files_folders'].' (`idx` bigint(20) NOT NULL auto_increment,`uid` bigint(20) NOT NULL default "0",`layered_id` bigint(20) NOT NULL default "0",`folder_path` text collate utf8_bin NOT NULL,`friendly_name` varchar(255) collate utf8_bin NOT NULL default "",`childof` bigint(20) NOT NULL default "0",`att_type` tinyint(4) NOT NULL default "0",`att_icon` text collate utf8_bin NOT NULL,`att_big_icon` text collate utf8_bin NOT NULL,`att_has_folders` enum("0","1") collate utf8_bin NOT NULL default "0",`att_has_items` enum("0","1") collate utf8_bin NOT NULL default "0",`filenum` bigint(20) NOT NULL default "0",`filesize` bigint(20) NOT NULL default "0",PRIMARY KEY (`idx`), KEY `uid` (`uid`), KEY `childof` (`childof`), KEY `layered_id` (`layered_id`))';
    	if ($this->query($query)) {
    		$query = 'CREATE TABLE IF NOT EXISTS '.$this->Tbl['files_files'].' (`idx` bigint(20) NOT NULL auto_increment,`folder_id` bigint(20) NOT NULL default "0",`uid` bigint(20) NOT NULL default "0",`file_name` varchar(255) NOT NULL default "",`friendly_name` varchar(255) NOT NULL default "",`type` varchar(255) NOT NULL default "application/octet-stream",`img_w` INT NOT NULL DEFAULT 0, `img_h` INT NOT NULL DEFAULT 0, `size` bigint(20) NOT NULL default "0", `ctime` DATETIME NULL DEFAULT NULL, `atime` DATETIME NULL DEFAULT NULL, `mtime` DATETIME NULL DEFAULT NULL, `is_locked` enum("0","1") NOT NULL default "0",PRIMARY KEY (`idx`),KEY `folder_id` (`folder_id`),KEY `uid` (`uid`),KEY `friendly_name` (`friendly_name`))';
    		if ($this->query($query)) {
    			return true;
    		}
    	}
    	return false;
    }

    /**
    * Uninstall the required table(s) of the handler
    * @param  void
    * @return  boolean  return value of the MySQL query
    * @since 0.3.9
    */
    public function handler_uninstall()
    {
    	return ($this->query('DROP TABLE IF EXISTS '.$this->Tbl['files_folders'])
    			&& $this->query('DROP TABLE IF EXISTS '.$this->Tbl['files_files']));
    }

    /**
     * Quota related: Returns the number of folders, the given user has created.
     * For statistical purposes we return the overall amount of folders created, the number of users who did so
     * and the uids of the users with the most and the least folders.
     *
     * @param int $uid  User ID
     * @param bool Whether to return statistics instead of the usage of the currently given user id
     * @return int|array $num Number of user defined local folders created / Statistics array
     * @since 0.7.1
     */
    public function quota_getfoldernum($uid = 0, $stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT COUNT(*) FROM '.$this->Tbl['files_folders'].' WHERE uid='.intval($uid).' AND att_type=1';
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT COUNT(DISTINCT uid), COUNT(*) FROM '.$this->Tbl['files_folders'].' WHERE att_type=1';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, COUNT(*) summe FROM '.$this->Tbl['files_folders'].' WHERE att_type=1 GROUP BY uid ORDER BY summe DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    /**
     * Qutoa related: Returns the overall size of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @param int $uid  User ID
     * @return int $size Size of all mails in bytes
     * @since 0.7.1
     */
    public function quota_getitemsize($uid = 0, $stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT SUM(filesize) FROM '.$this->Tbl['files_folders'].' WHERE uid='.intval($uid).' AND att_type<10';
            list ($size) = $this->fetchrow($this->query($query));
            return $size;
        }
        $query = 'SELECT COUNT(DISTINCT uid), SUM(filesize) FROM '.$this->Tbl['files_folders'].' WHERE att_type<10';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, SUM(filesize) summe FROM '.$this->Tbl['files_folders'].' WHERE att_type<10 GROUP BY uid ORDER BY summe DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    /**
     * Qutoa related: Returns the number of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @param int $uid  User ID
     * @return int $size Number of all mails
     * @since 0.7.1
     */
    public function quota_getitemnum($uid = 0, $stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT SUM(filenum) FROM '.$this->Tbl['files_folders'].' WHERE uid='.intval($uid).' AND att_type<10';
            list ($size) = $this->fetchrow($this->query($query));
            return $size;
        }
        $query = 'SELECT COUNT(DISTINCT uid), SUM(filenum) FROM '.$this->Tbl['files_folders'].' WHERE att_type<10';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, SUM(filenum) summe FROM '.$this->Tbl['files_folders'].' WHERE att_type<10 GROUP BY uid ORDER BY summe DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }
}

