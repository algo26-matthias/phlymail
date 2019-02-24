<?php
/**
 * driver.mysql.php - MySQL class for bookmarks handler
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler RSS
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.1 2015-04-01 $Id: driver.php 2731 2013-03-25 13:24:16Z mso $
 */

class handler_rss_driver extends DB_Controller
{
    // Valid Search Criteria
    public $criteria_list;

    protected $fidx, $feed_index, $hybrid_index;

    protected $allShares = array();

    private $error = array(),
            $append_errors = false,
            $retain_errors = false;

    // This is the constructor
    public function __construct($uid = 0)
    {
        $this->uid = intval($uid);

        parent::__construct();

        $this->Tbl['rss_folder'] = $this->DB['db_pref'].'rss_folder';
        $this->Tbl['rss_feed'] = $this->DB['db_pref'].'rss_feed';
        $this->Tbl['rss_uuid'] = $this->DB['db_pref'].'rss_feed_uuid';
        $this->Tbl['rss_item'] = $this->DB['db_pref'].'rss_feed_item';
        try {
            $dbSh = new DB_Controller_Share();
            $allShares = $dbSh->getFolderList($this->uid, 'rss');
            $this->allShares = (!empty($allShares[$this->uid]['rss'])) ? $allShares[$this->uid]['rss'] : array();
        } catch (Exception $e) {
            $this->allShares = array();
        }
    }

    private function set_error($error)
    {
        if ($this->append_errors) {
            $this->error[] = $error;
        } else {
            $this->error[0] = $error;
        }
    }

    public function get_errors($nl = LF)
    {
        $error = implode($nl, $this->error);
        if (!$this->retain_errors) $this->error = array();
        return $error;
    }

    public function change_owner($uid)
    {
        $this->uid = intval($uid);
    }


    /**
     * Get count of item stored in the user's feed store
     * @param bitfield 1 -> Include "global" addresses, 2 -> public feed's items
     * [@param int folder (address book) ID to return the count for]
     * @return string count on success or FALSE on failure
     */
    public function get_itemcount($inc_global = 0, $fid = 0, $pattern = '', $criteria = '')
    {
        $folderFilter = 'b.`feed_id`='.intval($fid);
        $sql = 'SELECT COUNT(*) FROM '.$this->Tbl['rss_item'].' b WHERE '.$folderFilter.' AND ';
        switch ($inc_global) {
            case 1: $sql .= 'b.owner IN('.$this->uid.',0)'; break;
            case 2: $sql .= 'b.owner=0'; break;
            default: $sql .= 'b.owner='.$this->uid;
        }
        // Do we have a search criteria and a pattern set?
        if ($criteria && $pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            if (isset($this->criteria_list[$criteria])) {
                // Flatten the field list
                foreach ($this->criteria_list[$criteria] as $k) { $v[] = 'a.'.$k.' LIKE "'.$pattern.'"'; }
                $sql .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        list ($count) = $this->fetchrow($this->query($sql));
        return $count;
    }

    /**
     * Get all items stored for a user
     *[@param bitfield 1 -> Include "global" addresses, 2 -> public feed's items]
     *[@param  string  Search pattern]
     *[@param  string Search criteria]
     *[@param  integer  Number of entries to return]
     *[@param  integer  Start entry]
     *[@param  string  order by field]
     *[@param  string  order direction ('asc|desc')]
     * @return  mixed  array data on success; FALSE otherwise
     */
    public function get_index($inc_global = 0, $fid = 0, $pattern = '', $criteria = '', $num = 0, $start = 0, $order_by = false, $order_dir = 'DESC')
    {
        $return = array();
        $folderFilter = 'b.`feed_id`='.intval($fid);
        $sql = 'SELECT b.`id`, b.`title`, b.`url`, b.`author`, b.`feed_id`, b.`published`, b.`read`, b.`seen`, f.`name` AS `folder`'
                .', IF (b.owner!='.$this->uid.', 1, 0) `global`'
                .' FROM '.$this->Tbl['rss_item'].' b'
                .' LEFT JOIN '.$this->Tbl['rss_folder'].' f ON f.`id`=b.`feed_id`'
                .' WHERE '.$folderFilter.' AND ';
        switch ($inc_global) {
            case 1: $sql .= 'b.owner IN('.$this->uid.',0)'; break;
            case 2: $sql .= 'b.owner=0'; break;
            default: $sql .= 'b.owner='.$this->uid;
        }
        // Do we have a search criteria and a pattern set?
        if ($criteria && $pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            if (isset($this->criteria_list[$criteria])) {
                // Flatten the field list
                foreach ($this->criteria_list[$criteria] as $k) {
                    $v[] = 'a.'.$k.' LIKE "'.$pattern.'"';
                }
                $sql .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        // Order by / direction given?
        $order_dir = ('ASC' == $order_dir) ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . ($order_by ? $this->esc($order_by).' '.$order_dir : 'b.`added`');
        if ($num != 0) {
            $sql .= ' LIMIT '.($start).','.($num);
        }

        $qid = $this->query($sql);
        if ($qid) {
            while ($line = $this->assoc($qid)) {
                $return[] = $line;
            }
        } else {
            $this->set_error($this->error());
            print_r($this->get_errors());
        }
        return $return;
    }

    /**
     * Return a specific feed item
     * @param int  item ID
     *[@param bool  Include global bookmarks in the query; default is false]
     * @return array data on success or FALSE on failure
     */
    public function get_item($id = 0, $inc_global = 0)
    {
        $id = ($id) ? intval($id) : 0;
        switch ($inc_global) {
            case 1: $q_r = 'b.owner IN('.$this->uid.',0)'; break;
            case 2: $q_r = 'b.owner=0'; break;
            default: $q_r = 'b.owner='.$this->uid;
        }
        $query = 'SELECT b.`id`, b.`title`, b.`url`, b.`content`, b.`author`, b.`feed_id`, b.`published`, b.`read`, b.`seen` '
             .', (SELECT f.`name` FROM '.$this->Tbl['rss_feed'].' f WHERE f.`id`=b.`feed_id` LIMIT 1) `folder`'
             .', IF (b.`owner`!='.$this->uid.', 1, 0) `global`'
             .' FROM '.$this->Tbl['rss_item'].' b WHERE b.`id`='.$id.' AND '.$q_r;
        $return = $this->assoc($this->query($query));
        return $return;
    }

    /**
     * Delete a given item from address book
     * @param int $aid  item id to delete
     * @return TRUE on success or FALSE on failure
     */
    public function delete_item($id = 0)
    {
        if (is_array($id)) {
            $id = basics::intify($id);
            $idSql = ' IN('.implode(',', $id).')';
            $query = 'SELECT 1 FROM '.$this->Tbl['rss_item'].' WHERE `id`='.$id[0].' AND `owner`='.$this->uid.' LIMIT 1';
        } else {
            $idSql = '='.intval($id);
            $query = 'SELECT 1 FROM '.$this->Tbl['rss_item'].' WHERE `id`='.$id.' AND `owner`='.$this->uid.' LIMIT 1';
        }
        list ($result) = $this->fetchrow($this->query($query));
        if (!$result) {
            return -2;
        }
        return $this->query('DELETE FROM '.$this->Tbl['rss_item'].' WHERE `id`'.$idSql);
    }

    /**
     * If an item exists, it does not need to be added to the database again
     *
     * @param int $feed_id
     * @param string $uuid
     * @return bool
     */
    public function item_exists($feed_id, $uuid)
    {
        // SimplePie returns the full URI sometimes, which is rahter long, so hashing it shall help
        if (strlen($uuid) > 32) {
            $uuid = md5($uuid);
        }

        $query = 'SELECT `uuid` FROM '.$this->Tbl['rss_uuid'].' WHERE feed_id='.intval($feed_id).' AND `uuid`="'.$this->esc($uuid).'"';
        $qid = $this->query($query);
        return $this->numrows($qid);
    }


    /**
     * Add an item to the database
     * Omit data you don't want to set
     * Set the owner to 0 for a global item
     * @param array $data
     * @return TRUE on success or FALSE on failure
     */
    public function add_item($data)
    {
        if (empty($data['uuid'])) {
            throw new Exception('UUID needed');
        }
        // SimplePie returns the full URI sometimes, which is rahter long, so hashing it shall help
        if (strlen($data['uuid']) > 32) {
            $data['uuid'] = md5($data['uuid']);
        }
        $add = array();
        foreach (array('title' => '""', 'url' => '""', 'content' => '""',
                'author' => '""','feed_id' => '"0"', 'uuid' => false,
                'added' => 'NOW()', 'visited' => 'NULL', 'published' => 'NULL',
                'read' => '"0"', 'seen' => '"0"') as $k => $v) {
            $add[] = '`'.$k.'`='.(isset($data[$k]) ? '"'.$this->esc($data[$k]).'"' : $v);
        }
        if (!empty($add)) {
            // Log this into a separate table. Needed to prevent repeated download of entries, we already fetched in the past
            $this->query('INSERT '.$this->Tbl['rss_uuid'].' SET feed_id='.intval($data['feed_id']).', `uuid`="'.$this->esc($data['uuid']).'"');

            $add[] = '`owner`='.(isset($data['owner']) && 0 == $data['owner'] ? '0' : $this->uid);
            if ($this->query('INSERT '.$this->Tbl['rss_item'].' SET ' . implode(',', $add))) {
                return true;
            }
        } else {
            // echo '$data was empty??'.LF;
        }
        return false;
    }

    /**
     * Update an item (or multiple at once, if the 'id' key is an array of IDs).
     * Omit data you don't want to update
     * @param array $data  Pass all fields to change and hte id of the element
     * @return TRUE on success or FALSE on failure
     */
    public function update_item($data)
    {
        $add = array();
        foreach (array('title', 'url', 'content', 'author', 'feed_id', 'uuid', 'added', 'visited', 'read', 'seen') as $k) {
            if (isset($data[$k])) {
                $add[] = '`'.$k.'`="' . $this->esc($data[$k]) . '"';
            }
        }
        if (!empty($add)) {
            $idSql = '='.intval($data['id']);
            if (is_array($data['id'])) {
                $data['id'] = basics::intify($data['id']);
                $idSql = ' IN('.implode(',', $data['id']).')';
            }
            $query = 'UPDATE '.$this->Tbl['rss_item'].' SET '.implode(',', $add).' WHERE `id`'.$idSql.' AND `owner`='.$this->uid;
            return $this->query($query);
        }
        return false;
    }

    /**
     * Return list of folders associated with a certain user
     * @param integer user id
     * @param boolean with global folders?
     * [@param string pattern
     * [@param integer num
     * [@param integer start]]])
     * @return $return array data on success, FALSE otherwise
     */
    public function get_folderlist($inc_global = 0, $raw = false)
    {
        $this->fidx = array();
        $q_l = 'SELECT `id`,`name`,`childof`,`owner` FROM '.$this->Tbl['rss_folder']
                .($inc_global ? ' WHERE `owner` IN('.$this->uid.',0)' : ' WHERE `owner`='.$this->uid)
                .' ORDER BY IF(`owner`!= 0, 0, 1) ASC, `childof` ASC';
        $qid = $this->query($q_l);
        while ($line = $this->assoc($qid)) {
            $this->fidx[$line['childof']][$line['id']] = $line;
        }
        return $raw === true ? $this->fidx : $this->read_folders_flat(0, 0);
    }

    protected function read_folders($parent_id = 0)
    {
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) {
            return false;
        }

        $return = array();
        foreach ($this->fidx[$parent_id] as $k => $v) {
            $return[$k] = array(
                    'path' => $k,
                    'icon' => isset($v['icon']) ? $v['icon'] : '',
                    'foldername' => $v['name'],
                    'type' => 1,
                    'has_folders' => 1,
                    'has_items' => 0
                    );
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = $this->read_folders($k);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $return;
    }

    /**
    * Read all available folders below doc root and return as array,
    * opposed to read_folders() the returned array does not reflect the real
    * structure of the folders, just the order and a 'level' attribute will tell
    * you about it.
    * @param  int $parent_id  ID of the folder to start with, Default: 0
    *[@param  int $level  Starting level, in doubt leave blank]
    * @return  array of folders and their meta data
    */
    protected function read_folders_flat($parent_id = 0, $level = 0)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) return array();

        foreach ($this->fidx[$parent_id] as $k => $v) {
            $return[$k] = array
                    ('path' => $k
                    ,'name' => $v['name']
                    ,'level' => $level
                    ,'childof' => $v['childof']
                    ,'owner' => $v['owner']
                    );
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = true;
                $return = $return + $this->read_folders_flat($k, $level+1);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $return;
    }

    /**
     * Special method just needed for the folder browser on copy / move
     */
    public function return_fidx()
    {
        return $this->fidx;
    }

    public function get_sharedfolderlist()
    {
        return false;
    }

    /**
     * Return folder by given owner and folder id
     * Input  : adb_get_folder(integer owner, integer folder id)
     * @return string folder name on success, FALSE otherwise
     */
    public function get_folder($fid = 0)
    {
        if (!$fid) {
            return false;
        }
        $fid = (int) $fid;
        $query = 'SELECT * FROM '.$this->Tbl['rss_folder'].' WHERE `id`='.$fid;
        return $this->assoc($this->query($query));
    }

    /**
     * Insert a folder
     * Input  : adb_add_folder(integer owner, integer folder id, string folder name)
     * @return TRUE on success, FALSE otherwise
     */
    public function add_folder($name = '', $childof = 0)
    {
        $query = 'INSERT '.$this->Tbl['rss_folder'].' SET `owner`='.$this->uid
                .',`name`="'.$this->esc($name).'",`childof`='.intval($childof)
                .',`uuid`="'.$this->esc(basics::uuid()).'"';
        if ($this->query($query)) {
            return $this->insertid();
        }
        return false;
    }

    /**
     * Update a given folder
     * Input  : adb_update_folder(integer owner, integer folder id, string folder name)
     * @return TRUE on success, FALSE otherwise
     */
    public function update_folder($fid = 0, $name = null, $childof = false)
    {
        if (!$fid || !$name) {
            return false;
        }
        $query = 'UPDATE '.$this->Tbl['rss_folder'].' SET `uuid`="'.$this->esc(basics::uuid()).'"';
        if (!is_null($name)) {
            $query .= ',`name`="'.$this->esc($name).'"';
        }
        if ($childof) {
            $query .= ',childof='.intval($childof);
        }
        $query .= ' WHERE `id`='.intval($fid).' AND `owner`='.$this->uid;
        if (!$this->query($query)) {
            $this->set_error($this->error());
            return false;
        }
        return true;
    }

    /**
     * Check, whether a folder name for a ceratin user already exists
     * Input  : adb_checkfor_foldername(integer owner, string foldername)
     * @return folder id if yes, FALSE otherwise
     */
    public function checkfor_foldername($name = '', $childof = false)
    {
        $query = 'SELECT `id` FROM '.$this->Tbl['rss_folder'].' WHERE `owner`='.$this->uid.' AND name="'.$this->esc($name).'"';
        if ($childof !== false) {
            $query .= ' AND childof='.intval($childof);
        }
        list ($result) = $this->fetchrow($this->query($query));
        return ($result) ? $result : false;
    }

    /**
     * Delete a given folder from the DB.
     * This method drops all the items AND SUBFOLDERS contained!
     * @param int $fid  ID of the folder to delete
     * @return TRUE on success or FALSE on failure
     */
    public function dele_folder($fid = 0)
    {
        $fid = intval($fid);
        $qid = $this->query('SELECT `id` FROM '.$this->Tbl['rss_folder'].' WHERE `owner`='.$this->uid.' AND `childof`='.$fid);
        while ($line = $this->assoc($qid)) {
            // Go deeper in the structure and drop all children first
            $this->dele_folder($line['id']);
        }
        // Only drop items, when deleting parent folder succeeded
        if ($this->query('DELETE FROM '.$this->Tbl['rss_folder'].' WHERE `id`='.$fid)) {
            $this->dele_feed(null, $fid);
        }
        return true;
    }

    public function move_folder($fid, $childof)
    {
        $query = 'UPDATE '.$this->Tbl['rss_folder'].' SET childof='.intval($childof).' WHERE `id`='.intval($fid).' AND `owner`='.$this->uid;
        if (!$this->query($query)) {
            $this->set_error($this->error());
            return false;
        }
        return true;
    }

    /**
     * Create a new feed
     * @return TRUE on success, FALSE otherwise
     */
    public function add_feed($data)
    {
        $query = 'INSERT '.$this->Tbl['rss_feed'].' SET `owner`='.$this->uid.
                ',`uuid`="'.$this->esc(basics::uuid()).'"'.
                (!empty($data['name']) ? ',`name`="'.$this->esc($data['name']).'"' : '').
                (!empty($data['description']) ? ',`description`="'.$this->esc($data['description']).'"' : '').
                (!empty($data['xml_uri']) ? ',`xml_uri`="'.$this->esc($data['xml_uri']).'"' : '').
                (!empty($data['html_uri']) ? ',`html_uri`="'.$this->esc($data['html_uri']).'"' : '').
                (!empty($data['childof']) ? ',`childof`='.intval($data['childof']) : '');
        if ($this->query($query)) {
            return $this->insertid();
        }
        return false;
    }

    /**
     * Update a feed
     * @return TRUE on success, FALSE otherwise
     */
    public function update_feed($id, $data)
    {
        $updateUUID = false;
        $fields = array();
        foreach (array(
                'name' => array('t' => 's', 'uuid' => true),
                'description' => array('t' => 's', 'uuid' => true),
                'xml_uri' => array('t' => 's', 'uuid' => true),
                'html_uri' => array('t' => 's', 'uuid' => true),
                'childof' => array('t' => 'i', 'uuid' => false),
                'owner' => array('t' => 'i', 'uuid' => false),
                'ext_un' => array('t' => 's', 'uuid' => false),
                'ext_pw' => array('t' => 's', 'uuid' => false),
                'mime' => array('t' => 's', 'uuid' => false),
                'view' => array('t' => 's', 'uuid' => false),
                'updated' => array('t' => 's', 'uuid' => false),
                'laststatus' => array('t' => 'i', 'uuid' => false),
                'lasterror' => array('t' => 's', 'uuid' => false)
                ) as $token => $flags) {
            if (isset($data[$token])) {
                $fields[] = '`'.$token.'`='.($flags['t'] == 's' ? '"'.$this->esc($data[$token]).'"' : intval($token));
                if ($flags['uuid'] === true) {
                    $updateUUID = true;
                }
            }
        }

        if (!empty($updateUUID)) {
            $fields[] = '`uuid`="'.$this->esc(basics::uuid()).'"';
        }
        if (!empty($fields)) {
            return $this->query('UPDATE '.$this->Tbl['rss_feed'].' SET '.implode(',', $fields).' WHERE id='.intval($id));
        }
        return true;
    }

    /**
     * Return list of folders associated with a certain user
     * @param integer user id
     * @param boolean with global folders?
     * [@param string pattern
     * [@param integer num
     * [@param integer start]]])
     * @return $return array data on success, FALSE otherwise
     */
    public function get_feedlist($inc_global = 0, $raw = false)
    {
        $this->feed_index = array();
        $q_l = 'SELECT * FROM '.$this->Tbl['rss_feed']
                .($inc_global ? ' WHERE `owner` IN('.$this->uid.',0)' : ' WHERE `owner`='.$this->uid)
                .' ORDER BY IF(`owner`!= 0, 0, 1) ASC, `childof` ASC';
        $qid = $this->query($q_l);
        while ($line = $this->assoc($qid)) {
            $line['is_shared'] = !empty($this->allShares[$line['id']]) ? '1' : '0';
            $this->feed_index[$line['childof']][$line['id']] = $line;
        }
        return $raw === true ? $this->feed_index : $this->read_feeds_flat(0, 0);
    }

    public function get_feed($id)
    {
        $qid = $this->query('SELECT * FROM '.$this->Tbl['rss_feed'].' WHERE `id`='.intval($id));
        while ($line = $this->assoc($qid)) {
            $line['is_shared'] = !empty($this->allShares[$line['id']]) ? '1' : '0';
            return $line;
        }
        return false;
    }

    /**
     * Check, whether a folder name for a ceratin user already exists
     * Input  : adb_checkfor_foldername(integer owner, string foldername)
     * @return folder id if yes, FALSE otherwise
     */
    public function checkfor_feedname($name = '', $childof = false)
    {
        $query = 'SELECT `id` FROM '.$this->Tbl['rss_feed'].' WHERE `owner`='.$this->uid.' AND name="'.$this->esc($name).'"';
        if ($childof !== false) $query .= ' AND childof='.intval($childof);
        list ($result) = $this->fetchrow($this->query($query));
        return ($result) ? $result : false;
    }

    /**
     * Delete a given folder from the DB.
     * This method drops all the items AND SUBFOLDERS contained!
     * @param int $fid  ID of the folder to delete
     * @return TRUE on success or FALSE on failure
     */
    public function dele_feed($fid = 0)
    {
        return $this->query('DELETE FROM '.$this->Tbl['rss_feed'].' WHERE `id`='.intval($fid));
    }

    public function move_feed($fid, $childof)
    {
        $query = 'UPDATE '.$this->Tbl['rss_feed'].' SET childof='.intval($childof).' WHERE `id`='.intval($fid).' AND `owner`='.$this->uid;
        if (!$this->query($query)) {
            $this->set_error($this->error());
            return false;
        }
        return true;
    }

    /**
     * Read all available folders below doc root and return as array,
     * opposed to read_folders() the returned array does not reflect the real
     * structure of the folders, just the order and a 'level' attribute will tell
     * you about it.
     * @param  int $parent_id  ID of the folder to start with, Default: 0
     *[@param  int $level  Starting level, in doubt leave blank]
     * @return  array of folders and their meta data
     */
    protected function read_feeds_flat($parent_id = 0, $level = 0)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->feed_index[$parent_id])) return array();

        foreach ($this->feed_index[$parent_id] as $k => $v) {
            $return['f'.$k] = array(
                    'path' => 'f'.$k,
                    'name' => $v['name'],
                    'level' => $level,
                    'childof' => $v['childof'],
                    'is_shared' => !empty($v['is_shared']) ? 1 : 0,
                    'description' => $v['description'],
                    'xml_uri' => $v['xml_uri'],
                    'html_uri' => $v['html_uri'],
                    'view' => $v['view']
                    );
            if (isset($this->feed_index['f'.$k])) {
                $return['f'.$k]['subdirs'] = true;
                $return = $return + $this->read_feeds_flat($k, $level+1);
            } else {
                $return['f'.$k]['subdirs'] = false;
            }
        }
        return $return;
    }

    public function get_hybridlist($inc_global = 0, $raw = false)
    {
        $this->get_folderlist($inc_global, true);
        $this->get_feedlist($inc_global, true);

        $this->hybrid_index = $this->fidx;
        foreach ($this->feed_index as $childof => $children) {
            foreach ($children as $k => $v) {
                $this->hybrid_index[$childof]['f'.$k] = $v;
            }
        }
        return $raw === true ? $this->hybrid_index : $this->read_hybrid_flat(0, 0);
    }

    protected function read_hybrid_flat($parent_id = 0, $level = 0)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($this->hybrid_index[$parent_id])) return array();

        foreach ($this->hybrid_index[$parent_id] as $k => $v) {
            $return[$k] = array(
                    'path' => $k,
                    'name' => $v['name'],
                    'level' => $level,
                    'childof' => $v['childof'],
                    'is_shared' => !empty($v['is_shared']) ? 1 : 0,
                    'owner' => $v['owner'],
                    'type' => isset($v['xml_uri']) ? 2 : 1,
                    'has_folders' => isset($v['xml_uri']) ? 0 : 1,
                    'has_items' => isset($v['xml_uri']) ? 1 : 0
                    );
            if (isset($this->hybrid_index[$k])) {
                $return[$k]['subdirs'] = true;
                $return = $return + $this->read_hybrid_flat($k, $level+1);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $return;
    }

    public function remove_user()
    {
        return
                // All folders of this user get dropped
                $this->query('DELETE FROM '.$this->Tbl['rss_folder'].' WHERE `owner`='.intval($this->uid))
                // and all feed of that user ...
                && $this->query('DELETE FROM '.$this->Tbl['rss_feed'].' WHERE `owner`='.intval($this->uid))
                // and all feed items
                && $this->query('DELETE FROM '.$this->Tbl['rss_item'].' WHERE `owner`='.intval($this->uid));
    }

    public function quota_feedsnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['rss_feed'].' WHERE `owner`='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct `owner`), count(*) FROM '.$this->Tbl['rss_feed'].' WHERE `owner`>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT `owner`, count(owner) moep FROM '.$this->Tbl['rss_feed'].' WHERE `owner`>0 GROUP BY `owner` ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    public function quota_foldersnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['rss_folder'].' WHERE `owner`='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct `owner`), count(*) FROM '.$this->Tbl['rss_folder'].' WHERE `owner`>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT `owner`, count(owner) moep FROM '.$this->Tbl['rss_folder'].' WHERE `owner`>0 GROUP BY `owner` ORDER BY moep DESC LIMIT 1';
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