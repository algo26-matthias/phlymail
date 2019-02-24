<?php
/**
 * driver.mysql.php - MySQL class for bookmarks handler
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Bookmarks
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.9 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_bookmarks_driver extends DB_Controller
{
    // Valid Search Criteria
    public $criteria_list;

    protected $allShares = array();

    private $error = array();
    private $append_errors = false;
    private $retain_errors = false;

    // This is the constructor
    public function __construct($uid = 0)
    {
        $this->uid = intval($uid);

        parent::__construct();

        $this->Tbl['bookmarks_item'] = $this->DB['db_pref'].'bookmarks_item';
        $this->Tbl['bookmarks_folder'] = $this->DB['db_pref'].'bookmarks_folder';

        try {
            $dbSh = new DB_Controller_Share();
            $allShares = $dbSh->getFolderList($this->uid, 'bookmarks');
            $this->allShares = (!empty($allShares[$this->uid]['bookmarks'])) ? $allShares[$this->uid]['bookmarks'] : array();
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
        if (!$this->retain_errors) {
            $this->error = array();
        }
        return $error;
    }

    /**
     * Get count of item stored in the user's address book
     * @param bitfield 1 -> Include "global" addresses, 2 -> public bookmarks
     * [@param int folder (address book) ID to return the count for]
     * @return string count on success or FALSE on failure
     */
    public function get_itemcount($inc_global = 0, $fid = 0, $pattern = '', $criteria = '')
    {
        $folderFilter = 'b.`fid`='.intval($fid);
        if ($fid === 'favourites') {
            $folderFilter = 'b.`favourite`="1"';
        }
        $sql = 'SELECT count(*) FROM '.$this->Tbl['bookmarks_item'].' b'
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
                foreach ($this->criteria_list[$criteria] as $k) { $v[] = 'a.'.$k.' LIKE "'.$pattern.'"'; }
                $sql .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        list ($count) = $this->fetchrow($this->query($sql));
        return $count;
    }

    /**
     * Get all bookmarks stored for a user
     *[@param bitfield 1 -> Include "global" addresses, 2 -> public bookmarks]
     *[@param  string  Search pattern]
     *[@param  string Search criteria]
     *[@param  integer  Number of entries to return]
     *[@param  integer  Start entry]
     *[@param  string  order by field]
     *[@param  string  order direction ('asc|desc')]
     * @return  mixed  array data on success; FALSE otherwise
     */
    public function get_index($inc_global = 0, $fid = 0, $pattern = '', $criteria = '', $num = 0, $start = 0, $order_by = false, $order_dir = 'ASC')
    {
        $return = array();
        $folderFilter = 'b.`fid`='.intval($fid);
        if ($fid === 'favourites') {
            $folderFilter = 'b.`favourite`="1"';
        }
        $sql = 'SELECT b.`id`, b.`name`, b.`url`, b.`favourite`, b.`fid` '
                 .', (SELECT f.`name` FROM '.$this->Tbl['bookmarks_folder'].' f WHERE f.`id`=b.`fid` LIMIT 1) `folder`'
                 .', IF (b.owner!='.$this->uid.', 1, 0) `global`'
                 .' FROM '.$this->Tbl['bookmarks_item'].' b'
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
                foreach ($this->criteria_list[$criteria] as $k) { $v[] = 'a.'.$k.' LIKE "'.$pattern.'"'; }
                $sql .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        // Order by / direction given?
        $order_dir = ('ASC' == $order_dir) ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . ($order_by ? $this->esc($order_by).' '.$order_dir : 'b.`name`');
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
     * Return a specific bookmark
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
        $query = 'SELECT b.`id`, b.`name`, b.`url`, b.`description`, b.`favourite`, b.`fid` '
             .', (SELECT f.`name` FROM '.$this->Tbl['bookmarks_folder'].' f WHERE f.`id`=b.`fid` LIMIT 1) `folder`'
             .', IF (b.`owner`!='.$this->uid.', 1, 0) `global`'
             .' FROM '.$this->Tbl['bookmarks_item'].' b WHERE b.`id`='.$id.' AND '.$q_r;
        $return = $this->assoc($this->query($query));
        return $return;
    }

    /**
     * Returns the list of favourite bookmarks for the current user
     *
     *[@param bool  Include global bookmarks in the query; default is false]
     * @return array data on success or FALSE on failure
     */
    public function get_favourites($inc_global = 0, $countOnly = false)
    {
        switch ($inc_global) {
            case 1: $q_r = 'b.owner IN('.$this->uid.',0)'; break;
            case 2: $q_r = 'b.owner=0'; break;
            default: $q_r = 'b.owner='.$this->uid;
        }
        if ($countOnly) {
            $query = 'SELECT COUNT(*) `anzahl` FROM '.$this->Tbl['bookmarks_item'].' b WHERE b.`favourite`="1" AND '.$q_r;
            $return = $this->assoc($this->query($query));
            $return = $return['anzahl'];
        } else {
            $query = 'SELECT b.`id`, b.`name` FROM '.$this->Tbl['bookmarks_item'].' b'
                    .' WHERE b.`favourite`="1" AND '.$q_r.' ORDER BY b.`name` ASC';
            $return = $this->assoc($this->query($query));
        }
        return $return;
    }

    /**
     * Delete a given item from address book
     * @param int $aid  item id to delete
     * @return TRUE on success or FALSE on failure
     */
    public function delete_item($id = 0)
    {
        $id = intval($id);
        $query = 'SELECT 1 FROM '.$this->Tbl['bookmarks_item'].' WHERE `id`='.$id.' AND `owner`='.$this->uid.' LIMIT 1';
        list ($result) = $this->fetchrow($this->query($query));
        if (!$result) {
            return -2;
        }
        return $this->query('DELETE FROM '.$this->Tbl['bookmarks_item'].' WHERE `id`='.$id);
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
        $add = array();
        foreach (array('name' => '""', 'url' => '""', 'description' => '""', 'favourite' => '"0"', 'fid' => '"0"'
                ,'added' => 'NULL', 'modified' => 'NULL', 'visited' => 'NULL') as $k => $v) {
            $add[] = (isset($data[$k])) ? $k.'="'.$this->esc($data[$k]).'"' : $k.'='.$v;
        }
        if (!empty($add)) {
            $add[] = '`owner`='.(isset($data['owner']) && 0 == $data['owner'] ? '0' : $this->uid);
            if ($this->query('INSERT '.$this->Tbl['bookmarks_item'].' SET ' . implode(',', $add)).',`uuid`="'.$this->esc(basics::uuid()).'"') {
                return true;
            }
        }
        return false;
    }

    /**
     * Update an item in the address book
     * Omit data you don't want to update
     * Input  : adb_update_item(array field data)
     * @return TRUE on success or FALSE on failure
     */
    public function update_item($data)
    {
        $add = array();
        foreach (array('name', 'url', 'description', 'favourite', 'fid', 'added', 'modified', 'visited') as $k) {
            if (isset($data[$k])) {
                $add[] = '`'.$k.'`="' . $this->esc($data[$k]) . '"';
            }
        }
        if (!empty($add)) {
            $query = 'UPDATE '.$this->Tbl['bookmarks_item'].' SET '.implode(',', $add).',`uuid`="'.$this->esc(basics::uuid()).'" WHERE `id`='.intval($data['id']).' AND `owner`='.$this->uid;
            return $this->query($query);
        }
        return false;
    }

    public function copy_item($id, $folder)
    {
        $orig = $this->get_item($id, 3);
        $orig['fid'] = $folder;
        $orig['added'] = time();
        return $this->add_item($orig);
    }

    /**
     * Moves an item form one folder to another
     *
     * @param int $item  ID of the item to move
     * @param int $folder  ID of the folder to move the item to
     */
    public function move_item($item, $folder)
    {
        return $this->update_item(array('id' => $item, 'fid' => $folder));
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
        $q_l = 'SELECT `id`,`name`,`description`,`layered_id`,`childof`,`owner` FROM '.$this->Tbl['bookmarks_folder']
                .($inc_global ? ' WHERE `owner` IN('.$this->uid.',0)' : ' WHERE `owner`='.$this->uid)
                .' ORDER BY IF(`owner`!= 0, 0, 1) ASC, `childof` ASC, `layered_id` ASC';
        $qid = $this->query($q_l);
        while ($line = $this->assoc($qid)) {
            $line['is_shared'] = !empty($this->allShares[$line['id']]) ? '1' : '0';
            $this->fidx[$line['childof']][$line['id']] = $line;
        }
        return $raw === true ? $this->fidx : $this->read_folders_flat(0, 0);
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
            $return[$k] = array(
                    'path' => $k,
                    'name' => $v['name'],
                    'is_shared' => $v['is_shared'],
                    'level' => $level,
                    'childof' => $v['childof'],
                    'owner' => $v['owner'],
                    'description' => $v['description']
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
        $query = 'SELECT * FROM '.$this->Tbl['bookmarks_folder'].' WHERE `id`='.$fid;
        $qid = $this->query($query);
        $line = $this->assoc($qid);
        $line['is_shared'] = !empty($this->allShares[$line['id']]) ? '1' : '0';
        return $line;
    }

    /**
     * Insert a folder
     * Input  : adb_add_folder(integer owner, integer folder id, string folder name)
     * @return TRUE on success, FALSE otherwise
     */
    public function add_folder($name = '', $desc = '', $childof = 0)
    {
        $name = $this->esc($name);
        $childof = intval($childof);
        $qid = $this->query('SELECT max(layered_id) FROM '.$this->Tbl['bookmarks_folder'].' WHERE `childof`='.$childof.' AND `owner`='.$this->uid);
        list ($max_layered) = $this->fetchrow($qid);
        $query = 'INSERT '.$this->Tbl['bookmarks_folder'].' SET `owner`='.$this->uid
                .',`name`="'.$this->esc($name).'",`description`="'.$this->esc($desc).'",`childof`='.$childof
                .',`layered_id`='.(intval($max_layered)+1).',`uuid`="'.$this->esc(basics::uuid()).'"';
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
    public function update_folder($fid = 0, $name = null, $desc = null, $childof = false, $layered = false)
    {
        if (!$fid || !$name) {
            return false;
        }
        $query = 'UPDATE '.$this->Tbl['bookmarks_folder'].' SET `uuid`="'.$this->esc(basics::uuid()).'"';
        if (!is_null($name)) {
            $query .= ',`name`="'.$this->esc($name).'"';
        }
        if (!is_null($desc)) {
            $query .= ',`description`="'.$this->esc($desc).'"';
        }
        if ($childof) {
            $query .= ',childof='.intval($childof);
        }
        if ($layered) {
            $query .= ',layered_id='.intval($layered);
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
        $query = 'SELECT `id` FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`='.$this->uid.' AND name="'.$this->esc($name).'"';
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
        $qid = $this->query('SELECT `id` FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`='.$this->uid.' AND `childof`='.$fid);
        while ($line = $this->assoc($qid)) {
            // Go deeper in the structure and drop all children first
            $this->dele_folder($line['id']);
        }
        // Only drop items, when deleting parent folder succeeded
        if ($this->query('DELETE FROM '.$this->Tbl['bookmarks_folder'].' WHERE `id`='.$fid)) {
            $this->query('DELETE FROM '.$this->Tbl['bookmarks_item'].' WHERE `fid`='.$fid.' AND `owner`='.$this->uid);
        }
        return true;
    }

    public function move_folder($fid, $childof)
    {
        $query = 'UPDATE '.$this->Tbl['bookmarks_folder'].' SET childof='.intval($childof).' WHERE `id`='.intval($fid).' AND `owner`='.$this->uid;
        if (!$this->query($query)) {
            $this->set_error($this->error());
            return false;
        }
        return true;
    }

    public function remove_user()
    {
        return
                // All folders of this user get dropped
                $this->query('DELETE FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`='.intval($this->uid))
                // and all addresses of that user ...
                && $this->query('DELETE FROM '.$this->Tbl['bookmarks_item'].' WHERE `owner`='.intval($this->uid));
    }

    public function quota_bookmarksnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['bookmarks_item'].' WHERE `owner`='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct `owner`), count(*) FROM '.$this->Tbl['bookmarks_item'].' WHERE `owner`>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT `owner`, count(owner) moep FROM '.$this->Tbl['bookmarks_item'].' WHERE `owner`>0 GROUP BY `owner` ORDER BY moep DESC LIMIT 1';
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
            $query = 'SELECT count(*) FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct `owner`), count(*) FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT `owner`, count(owner) moep FROM '.$this->Tbl['bookmarks_folder'].' WHERE `owner`>0 GROUP BY `owner` ORDER BY moep DESC LIMIT 1';
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
