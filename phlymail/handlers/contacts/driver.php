<?php
/**
 * driver.mysql.php - MySQL class for contacts handler
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Contacts handler
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.0 2015-04-13
 */
class handler_contacts_driver extends DB_Controller
{
    protected $queryType = 'default'; // @see $this->setQueryType()
    protected $criteria_list = array( // Valid Search Criteria
            'nick' => array('nick'),
            'name' => array('nick', 'firstname', 'lastname', 'thirdname', 'title'),
            'company' => array('customer_number', 'company', 'comp_dep', 'comp_role'),
            'address' => array('address', 'address2', 'street', 'zip', 'location',
                    'region', 'country', 'comp_address', 'comp_address2', 'comp_street',
                    'comp_zip', 'comp_location', 'comp_region', 'comp_country'
                    ),
            'email' => array('email1', 'email2'),
            'phone' => array('tel_private', 'tel_business', 'cellular', 'fax', 'comp_cellular', 'comp_fax'),
            'comment' => array('comment'),
            'group' => array('gid'),
            'birthday' => array('birthday'),
            'www' => array('www', 'comp_www')
            );
    protected $allShares = array();

    // This is the constructor
    public function __construct($uid = 0)
    {
        // Init translation of valid search criteria to actual field list
        $this->uid = intval($uid);

        parent::__construct();

        $this->Tbl['adb_address'] = $this->DB['db_pref'].'adb_adr';
        $this->Tbl['adb_adr_group'] = $this->DB['db_pref'].'adb_adr_group';
        $this->Tbl['adb_attach'] = $this->DB['db_pref'].'adb_attachemnts';
        $this->Tbl['adb_freefield'] = $this->DB['db_pref'].'adb_freefield';
        $this->Tbl['adb_freefield_type'] = $this->DB['db_pref'].'adb_freefield_type';
        $this->Tbl['adb_group'] = $this->DB['db_pref'].'adb_group';
        $this->Tbl['user'] = $this->DB['db_pref'].'user';
        $this->Tbl['user_foldersettings'] = $this->DB['db_pref'].'user_foldersettings';

        $this->DB['ServerVersionString'] = $this->serverinfo();
        $this->DB['ServerVersionNum'] = preg_replace('![^0-9\.]!', '', $this->DB['ServerVersionString']);

        try {
            $dbSh = new DB_Controller_Share();
            $allShares = $dbSh->getFolderList($this->uid, 'contacts');
            $this->allShares = (!empty($allShares[$this->uid]['contacts'])) ? $allShares[$this->uid]['contacts'] : array();
        } catch (Exception $e) {
            $this->allShares = array();
        }
    }

    public function setQueryType($type)
    {
        if (!in_array($type, array('default', 'sync', 'root'), true)) {
            $this->set_error('Illegal query type');
            return false;
        }
        $this->queryType = $type;
        return true;
    }

    /**
     * Get a list of valid search criteria
     * @return array Criteria list
     */
    public function get_criteria() { return $this->criteria_list; }

    /**
     * Shorthand for optionally including the event list filter depending on query type property
     * @param int $gid
     * @return string
     */
    protected function getQueryTypeFilter($gid = 0)
    {
        if ($gid == 0 && $this->queryType != 'default') {
            $field = $this->queryType == 'sync' ? 'not_in_sync' : 'not_in_root';
            return array
                    (' LEFT JOIN '.$this->Tbl['adb_adr_group'].' aag ON aag.aid=a.aid LEFT JOIN '.$this->Tbl['user_foldersettings'].' uf ON aag.gid=uf.fid'
                            .' AND uf.uid='.$this->uid.' AND uf.`handler`="contacts" AND uf.`key`="'.$field.'"'
                    , ' AND (aag.aid IS NULL OR uf.`val`="0" OR uf.`val` IS NULL)'
                    );
        }
        return array('', '');
    }

    /**
     * Get count of contact stored in the user's address book
     * @param bitfield 1 -> Include "global" addresses, 2 -> public contacts
     * [@param int Group (address book) ID to return the count for]
     * @return string count on success or FALSE on failure
     */
    public function get_adrcount($inc_global = 0, $gid = 0, $pattern = '', $criteria = '')
    {
        $q_l = 'SELECT count(*) FROM '.$this->Tbl['adb_address'].' a';
        $q_r = ' WHERE ';
        switch ($inc_global) {
            case 3: $q_r .= '(a.owner IN('.$this->uid.',0) OR (a.owner!='.$this->uid.' AND a.visibility="public"))'; break;
            case 2: $q_r .= '(a.owner='.$this->uid.' OR (a.owner!='.$this->uid.' AND a.visibility="public") OR (a.owner=0 AND a.`type`="contact"))'; break;
            case 1: $q_r .= 'a.owner IN('.$this->uid.',0)'; break;
            default: $q_r .= 'a.owner='.$this->uid;
        }
        if ($gid) {
            $q_l .= ','.$this->Tbl['adb_adr_group'].' ag';
            $q_r .= ' AND ag.aid=a.aid AND ag.gid='.intval($gid);
        } else {
            $contactListFilter = $this->getQueryTypeFilter();
            $q_l .= $contactListFilter[0];
            $q_r .= $contactListFilter[1];
        }
        // Do we have a search criteria and a pattern set?
        if ($criteria && $pattern) {
            $pattern = $this->esc($pattern);
            $pattern = (strstr($pattern, '*')) ? str_replace('*', '%', $pattern) : '%'.$pattern.'%';
            if (isset($this->criteria_list[$criteria])) {
                // Flatten the field list
                foreach ($this->criteria_list[$criteria] as $k) { $v[] = 'a.'.$k.' LIKE "'.$pattern.'"'; }
                $q_r .=' AND ('.implode(' OR ', $v).')';
            }
        }
        list ($count) = $this->fetchrow($this->query($q_l.$q_r));
        return $count;
    }

    /**
     * Get all contacts stored in the user's address book
     *[@param bitfield 1 -> Include "global" addresses, 2 -> public contacts]
     *[@param  string  Search pattern]
     *[@param  string Search criteria]
     *[@param  integer  Number of entries to return]
     *[@param  integer  Start entry]
     *[@param  string  order by field]
     *[@param  string  order direction ('asc|desc')]
     * @return  mixed  array data on success; FALSE otherwise
     */
    public function get_adridx($w_glbl = 0, $gid = null, $patt = '', $crit = '', $num = 0, $start = 0, $ordby = false, $orddir = 'ASC', $w_img = false)
    {
        $return = array();
        $w_img = ($w_img) ? ',a.image,a.imagemeta' : '';
        $q_l = 'SELECT a.*, IF(a.nick!="", a.nick, IF(a.lastname != "" AND a.firstname != "", CONCAT(a.lastname,", ", a.firstname), CONCAT(a.lastname, a.firstname) ) ) displayname'
                .((version_compare($this->DB['ServerVersionString'], '4.1.0', 'ge'))
                         ? ', (SELECT g2.name FROM '.$this->Tbl['adb_group'].' g2,'.$this->Tbl['adb_adr_group'].' ag2'
                                .' WHERE ag2.aid=a.aid AND g2.gid=ag2.gid LIMIT 1) `group`'
                         : ''
                )
                .',IF(a.email1 != "", a.email1, a.email2) displaymail'.$w_img
                .',IF(a.tel_private != "", a.tel_private, if(a.tel_business != "", a.tel_business, a.cellular)) displayphone'
                .',IF(a.owner!='.$this->uid.', 1, 0) global'
                .' FROM '.$this->Tbl['adb_address'].' a';
        $q_r = ' WHERE ';
        switch ($w_glbl) {
            case 3: $q_r .= '(a.owner IN('.$this->uid.',0) OR (a.owner!='.$this->uid.' AND a.visibility="public"))'; break;
            case 2: $q_r .= '(a.owner='.$this->uid.' OR (a.owner!='.$this->uid.' AND a.visibility="public") OR (a.owner=0 AND a.`type`="contact"))'; break;
            case 1: $q_r .= 'a.owner IN('.$this->uid.',0)'; break;
            default: $q_r .= 'a.owner='.$this->uid;
        }
        if (!is_null($gid) && $gid > 0) {
            $q_l .= ','.$this->Tbl['adb_adr_group'].' ag';
            $q_r .= ' AND ag.aid=a.aid AND ag.gid='.intval($gid);
        } else {
            $contactListFilter = $this->getQueryTypeFilter();
            $q_l .= $contactListFilter[0];
            $q_r .= $contactListFilter[1];
        }
        // Do we have a search criteria and a pattern set?
        if ($crit && $patt) {
            $patt = $this->esc($patt);
            $patt = (strstr($patt, '*')) ? str_replace('*', '%', $patt) : '%'.$patt.'%';
            if (isset($this->criteria_list[$crit])) {
                // Flatten the field list
                foreach ($this->criteria_list[$crit] as $k) { $v[] = 'a.'.$k.' LIKE "'.$patt.'"'; }
                $q_r .= ' AND ('.implode(' OR ', $v).')';
            }
        }
        // No doublettes please
        if (is_null($gid) || $gid == 0) {
            $q_r .= ' GROUP BY a.aid';
        }
        // Order by / direction given?
        $orddir = ('ASC' == $orddir) ? 'ASC' : 'DESC';
        $q_r .= ' ORDER BY ' . ($ordby ? $this->esc($ordby).' '.$orddir : 'displayname,displaymail');

        if ($num != 0) {
            $q_r .= ' LIMIT '.($start).','.($num);
        }

        $qid = $this->query($q_l . $q_r);
        while ($line = $this->assoc($qid)) {
            if (!isset($line['group'])) { // Must be a far too old MySQL version
                $qid2 = $this->query('SELECT g2.name FROM '.$this->Tbl['adb_group'].' g2,'.$this->Tbl['adb_adr_group'].' ag2'
                        .' WHERE ag2.aid='.$line['aid'].' AND g2.gid=ag2.gid LIMIT 1');
                $line2 = $this->assoc($qid2);
                $line['group'] = isset($line2['gname']) ? $line2['gname'] : '';
            }
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Return a specific contact
     * @param int  Contact ID
     *[@param bool  Include global contacts in the query; default is false]
     *[@param bool  Return the image data together with the rest of the contact data; default is false]
     * @return array data on success or FALSE on failure
     */
    public function get_contact($aid = 0, $inc_global = 0, $get_image = false)
    {
        $aid = ($aid) ? intval($aid) : 0;
        switch ($inc_global) {
            case 3: $q_r = '(a.owner IN('.$this->uid.',0) OR (a.owner!='.$this->uid.' AND a.visibility="public"))'; break;
            case 2: $q_r = '(a.owner='.$this->uid.' OR (a.owner!='.$this->uid.' AND a.visibility="public") OR (a.owner=0 AND a.`type`="contact"))'; break;
            case 1: $q_r = 'a.owner IN('.$this->uid.',0)'; break;
            default: $q_r = 'a.owner='.$this->uid;
        }
        $query = 'SELECT a.*, IF(a.nick!="", a.nick, IF(a.lastname != "" AND a.firstname != "", CONCAT(a.lastname,", ", a.firstname), CONCAT(a.lastname, a.firstname) ) ) displayname'
             .',IF(a.owner!='.$this->uid.', 1, 0) global'
             .',IF(a.email1 != "", a.email1, a.email2) displaymail'.($get_image ? ', a.image' : '')
             .',IF(a.tel_private != "", a.tel_private, IF(a.tel_business != "", a.tel_business, a.cellular)) displayphone'
             .' FROM '.$this->Tbl['adb_address'].' a WHERE a.aid='.$aid.' AND '.$q_r;
        $return = $this->assoc($this->query($query));
        $return['group'] = $return['free'] = array();
        $qid = $this->query('SELECT g.gid, g.name FROM '.$this->Tbl['adb_adr_group'].' ag, '.$this->Tbl['adb_group'].' g WHERE g.gid=ag.gid AND ag.aid='.$aid);
        while ($line = $this->assoc($qid)) {
            $return['group'][$line['gid']] = $line['name'];
        }
        $qid = $this->query('SELECT f.id, f.type_id, ft.name, ft.type, ft.token, f.value FROM '.$this->Tbl['adb_freefield'].' f '.
                'LEFT JOIN '.$this->Tbl['adb_freefield_type'].' ft ON ft.id=f.type_id '.
                'WHERE f.aid='.$aid);
        while ($line = $this->assoc($qid)) {
            $return['free'][$line['type_id']] = $line;
        }
        return $return;
    }

    /**
     * Retrieves the image for a certain contact
     *
     * @param int  Contact ID
     *[@param bool  Include global contacts in the query; default is false]
     * @return array data on success or FALSE on failure
     * @since 3.2.3
     */
    public function get_contactimage($aid = 0, $inc_global = 0)
    {
        $aid = ($aid) ? intval($aid) : 0;
        switch ($inc_global) {
            case 3: $q_r = '(owner IN('.$this->uid.',0) OR (owner!='.$this->uid.' AND visibility="public"))'; break;
            case 2: $q_r = '(owner='.$this->uid.' OR (owner!='.$this->uid.' AND visibility="public") OR (owner=0 AND `type`="contact"))'; break;
            case 1: $q_r = 'owner IN('.$this->uid.',0)'; break;
            default: $q_r = 'owner='.$this->uid;
        }
        $query = 'SELECT image, imagemeta FROM '.$this->Tbl['adb_address'].' WHERE aid='.$aid.' AND '.$q_r;
        return $this->assoc($this->query($query));
    }

    /**
     * Delete a given contact from address book
     * @param int $aid  contact id to delete
     * @return TRUE on success or FALSE on failure
     */
    public function delete_contact($aid = 0)
    {
        $aid = intval($aid);
        $query = 'SELECT 1 FROM '.$this->Tbl['adb_address'].' WHERE aid='.$aid.' AND owner='.$this->uid.' LIMIT 1';
        list ($result) = $this->fetchrow($this->query($query));
        if (!$result) {
            return false;
        }
        return $this->query('DELETE FROM '.$this->Tbl['adb_address'].' WHERE aid='.$aid)
                && $this->query('DELETE FROM '.$this->Tbl['adb_adr_group'].' WHERE aid='.$aid);
    }

    /**
     * Add an contact to the address book
     * Omit data you don't want to set
     * Set the owner to 0 for a global contact
     * Input  : adb_add_contact(array field data)
     * @return TRUE on success or FALSE on failure
     */
    public function add_contact($data)
    {
        $aid = false;

        $add = array();
        foreach (array(
                'nick' => '""', 'firstname' => '""', 'lastname' => '""', 'thirdname' => '""',
                'title' => '""', 'company' => '""', 'comp_dep' => '""', 'comp_role' => '""',
                'comp_address' => '""', 'comp_address2' => '""', 'comp_street' => '""',
                'comp_zip' => '""', 'comp_cellular' => '""', 'comp_www' => '""',
                'comp_fax' => '""', 'comp_location' => '""', 'comp_region' => '""',
                'comp_country' => '""', 'address' => '""', 'address2' => '""',
                'street' => '""', 'zip' => '""', 'location' => '""', 'region' => '""',
                'country' => '""', 'email1' => '""', 'email2' => '""', 'bday_cal_evt_id' => 'NULL',
                'tel_private' => '""', 'tel_business' => '""','cellular' => '""', 'fax' => '""', 'www' => '""',
                'birthday' => '""', 'customer_number' => '""', 'comments' => '""') as $k => $v) {
            $add[] = (isset($data[$k])) ? $k.'="'.$this->esc($data[$k]).'"' : $k.'='.$v;
        }
        if (!empty($add)) {
            $add[] = 'owner='.$this->uid;
            if ($this->query('INSERT '.$this->Tbl['adb_address'].' SET `uuid`="'.basics::uuid().'",' . implode(',', $add))) {
                $aid = $this->insertid();
                if (isset($data['group']) && !empty($data['group'])) {
                    foreach ($data['group'] as $gid) {
                        $this->query('INSERT '.$this->Tbl['adb_adr_group'].' SET `aid`='.$aid.', `gid`='.intval($gid).', `uid`='.intval($this->uid));
                    }
                }
            }
        }
        if (!empty($aid) && !empty($data['free'])) {
            foreach ($data['free'] as $type => $value) {
                $this->query('INSERT '.$this->Tbl['adb_freefield'].' SET `aid`='.$aid.', `type_id`='.intval($type).', `value`="'.$this->esc($value).'"');
            }
        }

        return $aid;
    }

    /**
     * Update an contact in the address book
     * Omit data you don't want to update
     * Input  : adb_update_contact(array field data)
     * @return TRUE on success or FALSE on failure
     */
    public function update_contact($data)
    {
        $add = array();
        foreach (array('nick', 'firstname', 'lastname', 'thirdname', 'title', 'company', 'comp_dep', 'comp_role'
                ,'comp_address', 'comp_address2', 'comp_street', 'comp_zip', 'comp_location', 'comp_region'
                ,'comp_country', 'comp_cellular', 'comp_www', 'comp_fax', 'address', 'address2', 'street', 'zip'
                ,'location', 'region', 'country', 'email1', 'email2', 'bday_cal_evt_id', 'tel_private', 'tel_business'
                ,'cellular', 'fax', 'www', 'birthday', 'customer_number', 'comments') as $k) {
            if (isset($data[$k])) {
                $add[] = $k . '="' . $this->esc($data[$k]) . '"';
            }
        }
        if (isset($data['group'])) {
            $this->query('DELETE FROM '.$this->Tbl['adb_adr_group'].' WHERE `aid`='.intval($data['aid']).' AND uid='.$this->uid);
            foreach ($data['group'] as $gid) {
                $this->query('INSERT '.$this->Tbl['adb_adr_group'].' SET `aid`='.intval($data['aid']).', `gid`='.intval($gid).', `uid`='.intval($this->uid));
             }
        }
        foreach (array_keys($this->get_freefield_types($this->uid)) as $type) {
            if (empty($data['free'][$type])) {
                $this->query('DELETE FROM '.$this->Tbl['adb_freefield'].' WHERE `aid`='.intval($data['aid']).' AND type_id='.intval($type));
            } else {
                $this->query('REPLACE INTO '.$this->Tbl['adb_freefield'].' SET `aid`='.intval($data['aid']).', `type_id`='.intval($type).', `value`="'.$this->esc($data['free'][$type]).'"');
            }
        }

        if (!empty($add)) {
            $where = 'aid='.intval($data['aid']).' AND owner='.intval($this->uid);
            if (isset($data['own_vcf'])) {
                list ($adrid) = $this->fetchrow($this->query('SELECT u.contactid FROM '.$this->Tbl['user'].' u,'.$this->Tbl['adb_address'].' a'
                    .' WHERE u.uid='.intval($this->uid).' AND a.aid=u.contactid'));
                if (0 != $adrid) {
                    $where = 'aid='.intval($adrid);
                }
            }
            $query = 'UPDATE '.$this->Tbl['adb_address'].' SET `uuid`="'.basics::uuid().'"'.','.implode(',', $add).' WHERE '.$where;
            return $this->query($query);
        }
        return false;
    }

    /**
     * Empties a given list of groups - or everything of that user
     * Right now it does not take care of images attached to the entries
     *
     *[@param array $groups List of groups to empty; Default: All]
     * @return bool
     * @since 4.2.5
     */
    public function empty_group($groups = array())
    {
        $sqladd = '';
        if (!is_array($groups)) {
            $groups = array($groups);
        }
        if (!empty($groups)) {
            foreach ($groups as $k => $v) {
                $groups[$k] = doubleval($v);
            }
            $sqladd = ' AND {TABLE}.`gid` IN('.join(',', $groups).')';
        }
        // Empty all necessary tables according to the given parameters
        $sec_sqladd = str_replace('{TABLE}', $this->Tbl['adb_adr_group'], $sqladd);
        foreach (array('adb_address', 'adb_attach', 'adb_freefield') as $sec_table) {
            $query = 'DELETE '.$this->Tbl[$sec_table].'.* FROM '.$this->Tbl[$sec_table].', '.$this->Tbl['adb_adr_group']
                    .' WHERE '.$this->Tbl[$sec_table].'.aid='.$this->Tbl['adb_adr_group'].'.aid'
                    .$sec_sqladd;
            $this->query($query);
        }
        $query = 'DELETE FROM '.$this->Tbl['adb_adr_group'].' WHERE `uid`='.$this->uid.str_replace('{TABLE}.', '', $sqladd);
        $this->query($query);
        return;
    }

    /**
     * Set whether a contact is visible to others or not
     *
     * @param int $aid Contact ID to set the visibility for
     * @param string $visibility  Either 'public' or 'private'
     * @return bool Whether the action was successfull or not
     * @since 3.3.8
     */
    public function set_contact_visibility($aid, $visibility = 'private')
    {
        return $this->query('UPDATE '.$this->Tbl['adb_address'].' SET visibility="'.('public' == $visibility ? 'public' : 'private').'"'
                .' WHERE aid='.intval($aid).' AND owner='.$this->uid);
    }

    public function get_freefield_types()
    {
        $return = array();
        $qid = $this->query('SELECT `id`, `name`, `token`, `type` FROM '.$this->Tbl['adb_freefield_type'].' WHERE uid='.intval($this->uid.' AND `active`="1"'));
        while ($line = $this->assoc($qid)) {
            $return[$line['id']] = $line;
        }
        return $return;
    }

    public function update_freefield_types($data)
    {
        $id = intval($id);

        foreach ($data as $id => $payload) {
            if (!empty($payload['token'])
                    && !preg_match('!^[a-z0-9]([a-z0-9-]+)$!i', $payload['token'])) {
                return 'TokenInvalidFormat';
            }
            $qid = $this->query('SELECT 1 FROM '.$this->Tbl['adb_freefield_type'].' WHERE id='.$id.' AND uid='.$this->uid);
            if (!$this->numrows($qid)) {
                $status = $this->add_freefield_type($payload);
                if ($status !== true) {
                    return $status;
                }
                continue;
            }
            $status = $this->query('UPDATE '.$this->Tbl['adb_freefield_type'].' SET name="'.$this->esc($payload['name']).'"'.
                    ',type="'.$this->esc($payload['type']).'",`token`="'.$this->esc($payload['token']).'"'.
                    ' WHERE id='.$id.' AND uid='.$this->uid);
            if (!$status) {
                $errno = $this->errno();
                if ($errno == 1586 || $errno == 1062) {
                    return 'DuplicateNameAssigned';
                } else {
                    echo $errno;
                }
            }
        }
        return true;
    }

    public function add_freefield_type($data)
    {
        $status = $this->query('INSERT INTO '.$this->Tbl['adb_freefield_type'].' (`name`, `type`, `token`, `uid`) VALUES ("'.$this->esc($data['name']).'"'.
                    ',"'.$this->esc($data['type']).'","'.$this->esc($data['token']).'",'.$this->uid.')');
        if (!$status) {
            $errno = $this->errno();
            if ($errno == 1586 || $errno == 1062) {
                return 'DuplicateNameAssigned';
            } else {
                echo $errno;
            }
        }
        return true;
    }

    public function delete_freefield_type($id)
    {
        $id = intval($id);
        return $this->query('DELETE FROM '.$this->Tbl['adb_freefield'].' WHERE type_id='.$id)
                && $this->query('DELETE FROM '.$this->Tbl['adb_freefield_type'].' WHERE id='.$id);
    }

    /**
     * Return list of groups associated with a certain user
     * @param integer user id
     * @param boolean with global groups?
     * [@param string pattern
     * [@param integer num
     * [@param integer start]]])
     * @return $return array data on success, FALSE otherwise
     */
    public function get_grouplist($inc_global = 0, $pattern = '', $num = 0, $start = 0)
    {
        $return = array();
        $q_r = '';
        $q_l = 'SELECT g.gid, g.name, COUNT(ag.aid) adrcount, g.owner FROM '.$this->Tbl['adb_group'].' g LEFT JOIN '.$this->Tbl['adb_adr_group'].' ag ON ag.gid = g.gid';
        $q_l .= ($inc_global) ? ' WHERE g.owner IN('.$this->uid.',0)' : ' WHERE g.owner='.$this->uid;
        if ($num != 0) {
            $q_r .= ' LIMIT ' . intval($start) . ',' . intval($num);
        }
        $qid = $this->query($q_l . ' GROUP BY g.gid ORDER BY IF(g.`owner`!= 0, 0, 1) ASC, g.`name`' . $q_r);
        while ($line = $this->assoc($qid)) {
            $line['is_shared'] = !empty($this->allShares[$line['gid']]) ? '1' : '0';
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Return group by given owner and group id
     * @param int  $gid  ID of the group
     *[@param bool $short Return short info only; Default: TRUE]
     * @return string group name on success, FALSE otherwise
     */
    public function get_group($gid = 0, $nameOnly = true)
    {
        if (!$gid) {
            return false;
        }
        $gid = (int) $gid;

        $query = 'SELECT g.*, u.username FROM '.$this->Tbl['adb_group'].' g'.
                ' LEFT JOIN '.$this->Tbl['user'].' u ON u.uid=g.owner'.
                ' WHERE g.gid='.doubleval($gid);
        $qh = $this->query($query);
        if (false === $qh || !$this->numrows($qh)) {
            return false;
        }

        $result = $this->assoc($qh);
        if ($nameOnly) {
            return $result['name'];
        }

        $fSet = new DB_Controller_Foldersetting();
        $sync = $fSet->foldersetting_get('contacts', $gid, $this->uid, 'not_in_sync');
        $root = $fSet->foldersetting_get('contacts', $gid, $this->uid, 'not_in_root');
        $result['show_in_sync'] = (is_null($sync) || !$sync) ? 1 : 0;
        $result['show_in_root'] = (is_null($root) || !$root) ? 1 : 0;
        $result['is_shared'] = !empty($this->allShares[$gid]) ? '1' : '0';

        return $result;
    }

    /**
     * Insert a group
     * Input  : adb_add_group(integer owner, integer group id, string group name)
     * @return TRUE on success, FALSE otherwise
     */
    public function add_group($name = '', $sync = 1, $root = 1, $type = 0, $uri = null, $mime = null, $check = 0)
    {
        $name = $this->esc($name);
        $query = 'INSERT '.$this->Tbl['adb_group'].' SET owner='.$this->uid.', name="'.$name.'"';
        if ($type == 1) {
            $query .= ',`type`=1,`checkevery`='.intval($check)
                    .',`uri`="'.(is_null($uri) ? '' : $this->esc($uri)).'"'
                    .',`mime`="'.(is_null($mime) ? '' : $this->esc($mime)).'"';
        } else {
            $query .= ',`type`=0';
        }
        $this->query($query);
        $gid = $this->insertid();
        $fSet = new DB_Controller_Foldersetting();
        if ($sync == 0) {
            $fSet->foldersetting_set('contacts', $gid, $this->uid, 'not_in_sync', 1);
        }
        if ($root == 0) {
            $fSet->foldersetting_set('contacts', $gid, $this->uid, 'not_in_root', 1);
        }
        return $gid;
    }

    /**
     * Update a given group
     * Input  : adb_update_group(integer owner, integer group id, string group name)
     * @return TRUE on success, FALSE otherwise
     */
    public function update_group($gid = 0, $name = null, $sync = null, $root = null, $uri = null, $mime = null, $check = null)
    {
        if (!$gid) {
            return false;
        }
        $gid = (int) $gid;
        $fSet = new DB_Controller_Foldersetting();
        if (!is_null($sync)) {
            if ($sync) {
                $fSet->foldersetting_del('contacts', $gid, $this->uid, 'not_in_sync');
            } else {
                $fSet->foldersetting_set('contacts', $gid, $this->uid, 'not_in_sync', 1);
            }
        }
        if (!is_null($root)) {
            if ($root) {
                $fSet->foldersetting_del('contacts', $gid, $this->uid, 'not_in_root');
            } else {
                $fSet->foldersetting_set('contacts', $gid, $this->uid, 'not_in_root', 1);
            }
        }
        $sqladd = array();
        if (!is_null($name)) {
            $sqladd[] = '`name`="'.$this->esc($name).'"';
        }
        if (!is_null($uri)) {
            $sqladd[] = '`uri`="'.$this->esc($uri).'"';
        }
        if (!is_null($mime)) {
            $sqladd[] = '`mime`="'.$this->esc($mime).'"';
        }
        if (!is_null($check)) {
            $sqladd[] = '`checkevery`='.intval($check);
        }
        if (empty($sqladd)) {
            return true;
        }
        $query = 'UPDATE '.$this->Tbl['adb_group'].' SET '.implode(',', $sqladd).' WHERE gid='.$gid.' AND owner='.$this->uid;
        return $this->query($query);
    }

    /**
     * Check, whether a group name for a ceratin user already exists
     * Input  : adb_checkfor_groupname(integer owner, string groupname)
     * @return group id if yes, FALSE otherwise
     */
    public function checkfor_groupname($name = '')
    {
        $query = 'SELECT gid FROM '.$this->Tbl['adb_group'].' WHERE owner='.$this->uid.' AND name="'.$this->esc($name).'"';
        list ($result) = $this->fetchrow($this->query($query));
        return ($result) ? $result : false;
    }

    /**
     * Delete a given group from address book
     * Input  : adb_dele_group(integer group id)
     * @return TRUE on success or FALSE on failure
     */
    public function dele_group($gid = 0)
    {
        $query = 'SELECT 1 FROM '.$this->Tbl['adb_group'].' WHERE gid='.intval($gid).' AND owner='.$this->uid.' LIMIT 1';
        list ($result) = $this->fetchrow($this->query($query));
        if (!$result) {
            return false;
        }
        return $this->query('DELETE FROM '.$this->Tbl['adb_group'].' WHERE gid='.intval($gid))
                && $this->query('DELETE FROM '.$this->Tbl['adb_adr_group'].' WHERE gid='.intval($gid));
    }

    /**
     * Get all mail addresses of a certain group
     * @param  int  Group ID
     * @param  bool  really all, by default only the first email address is taken [NOT YET SUPPORTED]
     * @param  bool  only_first, by default email2 is returned, if email1 is empty [NOT YET SUPPORTED]
     * @return array  All found email addresse
     * @since 3.2.2
     */
    public function get_mailsbygroup($gid = 0, $really_all = false, $only_first = false)
    {
        if (!$gid) {
            return array();
        }
        $return = array();
        $query = 'SELECT if(a.email1, a.email1, a.email2) email FROM '.$this->Tbl['adb_address'].' a, '.$this->Tbl['adb_adr_group'].' ag'
                .' WHERE ag.gid='.intval($gid).' AND ag.aid=a.aid AND (a.email1 != "" OR a.email2 != "")';
        $qid = $this->query($query);
        while (list ($mail) = $this->fetchrow($qid)) {
            $return[] = $mail;
        }
        return $return;
    }

    /**
     * Search for a contact in either email, lastname, firstname or cellular fields
     * Usually used for the address selection on composing mails or short messages.
     *
     * @param string $search
     * @param string $what
     * @param bool $checkonly
     * @return mixed  Boolean on $checkonly = TRUE, array with found entires otherwise
     */
    public function search_contact($search, $what = 'email', $checkonly = false, $inc_global = 0)
    {
        $return = array();
        switch ($inc_global) {
            case 3: $q_r = '(owner IN('.$this->uid.',0) OR (owner!='.$this->uid.' AND visibility="public"))'; break;
            case 2: $q_r = '(owner='.$this->uid.' OR (owner!='.$this->uid.' AND visibility="public"))'; break;
            case 1: $q_r = 'owner IN('.$this->uid.',0)'; break;
            default: $q_r = 'owner='.$this->uid;
        }
        $search = $this->esc($search);
        if ($what == 'email') {
            $retfields = '`email1`,`email2`';
            $searcher = ' OR `email1` LIKE "%'.$search.'%" OR `email2` LIKE "%'.$search.'%"';
        } elseif ($what == 'fax') {
            $retfields = '`fax`,`comp_fax`';
            $searcher = ' OR `fax` LIKE "%'.$search.'%" OR `comp_fax` LIKE "%'.$search.'%"';
        } else {
            $retfields = '`cellular`,`comp_cellular`';
            $searcher = ' OR `cellular` LIKE "%'.$search.'%" OR `comp_cellular` LIKE "%'.$search.'%"';
        }
        $query = 'SELECT aid,`nick`,`firstname`,`lastname`,'.$retfields.' FROM '.$this->Tbl['adb_address'].' WHERE '
                .$q_r
                .' AND (`nick` LIKE "%'.$search.'%" OR `firstname` LIKE "%'.$search.'%" OR `lastname` LIKE "%'.$search.'%"'
                .' OR CONCAT(`firstname`, " ", `lastname`) LIKE "%'.$search.'%"'.$searcher.')';
        if ($checkonly) {
            $qid = $this->query($query.' LIMIT 1');
            if ($this->numrows($qid) == 0) {
                return false;
            }
            $result = $this->assoc($qid);
            return $result['aid'];
        }
        $qid = $this->query($query);
        while ($line = $this->assoc($qid)) {
            $return[] = $line;
        }
        return $return;
    }

    public function remove_user()
    {
        return
                // All groups of this user get dropped
                $this->query('DELETE FROM '.$this->Tbl['adb_group'].' WHERE owner='.intval($this->uid))
                // All group connections for addresses of that user (regardless of their actual owner)
                && $this->query('DELETE ag.* FROM '.$this->Tbl['adb_adr_group'].' ag, '.$this->Tbl['adb_address'].' a'
                        .' WHERE a.owner='.intval($this->uid).' AND a.aid=ag.aid')
                // Finally all addresses of that user ...
                && $this->query('DELETE FROM '.$this->Tbl['adb_address'].' WHERE owner='.intval($this->uid));
    }

    public function quota_contactsnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['adb_address'].' WHERE owner='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct owner), count(*) FROM '.$this->Tbl['adb_address'].' WHERE owner>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT owner, count(owner) moep FROM '.$this->Tbl['adb_address'].' WHERE owner>0 GROUP BY owner ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array
                ('count' => isset($cnt) ? $cnt : 0
                ,'sum' => isset($sum) ? $sum : 0
                ,'max_uid' => isset($max_uid) ? $max_uid : 0
                ,'max_count' => isset($max_cnt) ? $max_cnt : 0
                );
    }

    public function quota_groupsnum($stats = false)
    {
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['adb_group'].' WHERE owner='.intval($this->uid);
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct owner), count(*) FROM '.$this->Tbl['adb_group'].' WHERE owner>0';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT owner, count(owner) moep FROM '.$this->Tbl['adb_group'].' WHERE owner>0 GROUP BY owner ORDER BY moep DESC LIMIT 1';
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
