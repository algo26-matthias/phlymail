<?php
/**
 * Boiler plates main class
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Handler Email
 * @subpackage Boilerplates
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.7 2012-05-02 
 */
class handler_email_boilerplates extends DB_Controller {
    public $enabled = true;

    // Constructor
    // Read the config and open the DB
    public function __construct($uid = 0)
    {
        if (false === $uid) return false;
        $this->uid = intval($uid);

        parent::__construct();

        $this->Tbl['plates'] = $this->DB['db_pref'].'email_boilerplates';
        $this->Tbl['plategroups'] = $this->DB['db_pref'].'email_boilerplate_groups';
    }

    /**
     * Retrieves all saved boiler plates for this user
     *
     *[@param bool $inc_global TRUE to also include global entries; Default: true]
     *[@param int $gid  Group to return items for; Default: all groups]
     *[@param string $type one of 'html' or 'text', if not specified, all types are returned]
     * @return array  All matching entries
     */
    public function get_list($inc_global = true, $gid = false, $type = false, $count = false)
    {
        $return = array();
        $q_r = '';
        if ($gid !== false) {
            $q_r .= ' AND `gid`='.intval($gid);
        }
        if ($type !== false) {
            $q_r .= ($type == 'html') ? ' AND `type`="html"' :  ' AND `type`="text"';
        }
        $q_r .= ($inc_global) ? ' AND `owner` IN(0,'.$this->uid.')' : ' AND `owner`='.$this->uid;

        $q_l = 'COUNT(*)';
        if (!$count) {
            $q_l = '`id`,`gid`,`type`,`name`,`owner`';
            $q_r .= ' ORDER BY `type` DESC, `name` ASC';
        }
        $qid = $this->query('SELECT '.$q_l.' FROM '.$this->Tbl['plates'].' WHERE 1 '.$q_r);
        if ($count) {
            if ($this->numrows($qid)) {
                list ($num) = $this->fetchrow($qid);
                return $num;
            }
            return 0;
        }
        while ($line = $this->assoc($qid)) {
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Returns the given entry (specifying a global one is okay)
     *
     * @param int $id  ID of the entry
     * @return array  The entry
     */
    public function get_item($id)
    {
        $qid = $this->query('SELECT `id`,`gid`,`name`,`body`,`type` FROM '.$this->Tbl['plates'].' WHERE `id`='.intval($id).' AND owner IN(0,'.$this->uid.')');
        return $this->assoc($qid);
    }

    /**
     * Adds a boilerplate to the database
     *
     * @param array $data Contains type, name, body of the boilerplate
     * @return boolean
     */
    public function add_item($data)
    {
        if (empty($data) || !isset($data['name']) || !isset($data['body']) || !isset($data['type'])) {
            return false;
        }
        return $this->query('INSERT '.$this->Tbl['plates'].' SET `gid`='.intval($data['gid'])
                .',`name`="'.$this->esc($data['name']).'"'
                .',`type`="'.$this->esc($data['type']).'"'
                .',`body`="'.$this->esc($data['body']).'"'
                .',`owner`='.$this->uid);
    }

    public function update_item($id, $data)
    {
        if (false === $id || empty($data) || !isset($data['name']) || !isset($data['body']) || !isset($data['type'])) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['plates'].' SET `gid`='.intval($data['gid'])
                .',`name`="'.$this->esc($data['name']).'"'
                .',`type`="'.$this->esc($data['type']).'"'
                .',`body`="'.$this->esc($data['body']).'"'
                .' WHERE id='.intval($id).' AND owner='.$this->uid);
    }

    public function drop_item($id)
    {
        if (false === $id) return false;
        return $this->query('DELETE FROM '.$this->Tbl['plates'].' WHERE id='.intval($id).' AND owner='.$this->uid);
    }

    public function get_group_list($inc_global = true, $flatten = true)
    {
        $q_r = ($inc_global) ? ' owner IN(0,'.$this->uid.')' : ' owner='.$this->uid;
        $qid = $this->query('SELECT `id`,`name`,`owner`,`childof` FROM '.$this->Tbl['plategroups'].' WHERE '.$q_r.' ORDER BY `owner` ASC, `childof` ASC, `name` ASC');
        $return = array();
        while ($line = $this->assoc($qid)) {
            $return[$line['childof']][$line['id']] = $line;
        }
        return false == $flatten ? $return : $this->flatten_groups($return, 0, 0);
    }

    public function add_group($data)
    {
        if (empty($data) || !isset($data['name']) || !isset($data['childof'])) {
            return false;
        }
        return $this->query('INSERT '.$this->Tbl['plategroups'].' SET `name`="'.$this->esc($data['name']).'"'
                .',`childof`='.intval($data['childof']).', owner='.$this->uid);
    }

    public function update_group($id, $data)
    {
        if (false === $id || empty($data) || !isset($data['name'])) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['plategroups'].' SET '
                .'`name`="'.$this->esc($data['name']).'"'
                .(!isset($data['childof']) || false === $data['childof'] ? '' : ',`childof`='.intval($data['childof']))
                .' WHERE id='.intval($id).' AND owner='.$this->uid);
    }

    public function drop_group($id)
    {
        if (false === $id) return false;
        // First make sure no groups / plates disappear, who belong to this group
        $this->query('UPDATE '.$this->Tbl['plates'].' SET `gid`=0 WHERE `gid`='.intval($id));
        $this->query('UPDATE '.$this->Tbl['plategroups'].' SET `childof`=0 WHERE `childof`='.intval($id));
        // Now kill the group
        return $this->query('DELETE FROM '.$this->Tbl['plategroups'].' WHERE id='.intval($id).' AND owner='.$this->uid);
    }

    public function get_everything()
    {
        $plates = array();
        try {
            foreach ($this->get_list(true, false, false, false) as $line) {
                $plates[$line['gid']][$line['id']] = $line;
            }
        } catch (Exception $e) {
            return array();
        }

        return $this->flatten_groups($this->get_group_list(true, false), 0, 0, $plates);
    }


    protected function flatten_groups($data, $parent_id, $level = 0, $plates = null)
    {
        $return = array();
        // Not valid parent ID
        if (!isset($data[$parent_id])) return false;
        foreach ($data[$parent_id] as $k => $v) {
            $key = 'f_'.$k;
            $return[$key] = $v;
            $return[$key]['level'] = $level;
            if (isset($data[$k])) {
                $return[$key]['subdirs'] = true;
                $return = $return + $this->flatten_groups($data, $k, $level+1, $plates);
            } else {
                $return[$key]['subdirs'] = false;
            }
            if (!is_null($plates) && isset($plates[$k])) {
                foreach ($plates[$k] as $k2 => $v2) {
                    $key = 'p_'.$k2;
                    $return[$key] = $v2;
                    $return[$key]['level'] = ($level+1); // These are kids of the folder!
                }
            }
        }
        return $return;
    }
}
