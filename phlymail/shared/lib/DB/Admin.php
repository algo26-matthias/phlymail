<?php
/**
 * Administrative methods for use with the MySQL driver
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Config interface
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.8 2015-02-09 
 */
class DB_Admin extends DB_Base
{

    // This is the constructor
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['admin'] = $this->DB['db_pref'].'admin';
        $this->Tbl['admin_apikey'] = $this->DB['db_pref'].'admin_apikey';
    }

    /**
     * Administrators counterpart of authenticate()
     * @param string admin name
     * @return $return array data on success, FALSE otherwise
     * $return[0] uid of the admin
     * $return[1] MD5 hash of admin's password
     */
    public function adm_auth($un, $pw = null, $md5 = null, $a1 = null, $salt = null)
    {
        $res = $this->getadminauthinfo($un);
        if (empty($res) || $res['active'] != 1) {
            return array(false, false);
        }
        // First the "classic" approach via clear text password
        if (!empty($pw)) {
            if (!empty($res['pw_digesta1']) && $res['pw_digesta1'] == md5($un.':'.$salt.':'.$pw)) {
                return array($res['uid'], true);
            }
            if (!empty($res['password']) && $res['password'] == md5($pw)) {
                // Trying to transfer the old PW to the new one
                $this->query('UPDATE '.$this->Tbl['admin'].' SET pw_digesta1="'.md5($un.':'.$salt.':'.$pw).'",password="" WHERE username="'.$this->esc($un).'"');
                return array($res['uid'], true);
            }
            return array($res['uid'], false);
        }
        // Then the "modern" approach via Digest A1
        if (!empty($a1)) {
            if (!empty($res['pw_digesta1']) && $res['pw_digesta1'] == $a1) {
                return array($res['uid'], true);
            }
            return array($res['uid'], false);
        }
        // This only works while we still have an old password for that admin
        if (!empty($md5)) {
            if (!empty($res['password']) && $res['password'] == $md5) {
                return array($res['uid'], true);
            }
            return array($res['uid'], false);
        }
        return array(false, false);
    }

    public function getadminauthinfo($un)
    {
        $query = 'SELECT uid,password,pw_digesta1,externalemail,`token`,`token_valid`,`active` FROM '.$this->Tbl['admin'].' WHERE username="'.$this->esc($un).'"';
        $qid = $this->query($query);
        if (empty($qid)) {
            return false;
        }
        $res = $this->assoc($qid);
        // Found no matching record
        if (empty($res)) {
            return false;
        }
        return $res;
    }

    public function getadminpwtype($un)
    {
        $res = $this->getadminauthinfo($un);
        if (empty($res) || $res['active'] != 1) {
            return false;
        }
        if (!empty($res['pw_digesta1'])) {
            return 'digest';
        } elseif (!empty($res['password'])) {
            return 'plain';
        }
        return false;
    }

    public function adm_auth_apikey($un, $apik, $salt)
    {
        $qid = $this->query('SELECT uid, id, apikey FROM '.$this->Tbl['admin_apikey']);
        if (empty($qid)) {
            return array(false, false);
        }
        while ($row = $this->assoc($qid)) {
            $decrypted = CryptoLib::decryptData($row['apikey'], $un.'-'.$salt);
            if ($decrypted == $apik) {
                $this->query('UPDATE ' . $this->Tbl['admin_apikey'] . ' SET lastauth=NOW() WHERE id=' . $row['id']);
                return array($row['uid'], true);
            }
        }
        return array(false, false);
    }

    public function getadminbytoken($token)
    {
        // Remove all expired tokens
        $this->query('UPDATE '.$this->Tbl['admin'].' SET `token`=NULL,`token_valid`=NULL WHERE `token_valid` IS NOT NULL AND `token_valid`<NOW()');
        $query = 'SELECT uid,`username` FROM '.$this->Tbl['admin'].' WHERE `token`="'.$this->esc($token).'" AND `token_valid`>=NOW()';
        $qid = $this->query($query);
        if (empty($qid)) {
            return false;
        }
        $res = $this->assoc($qid);
        // Found no matching record
        if (empty($res) || empty($res['uid'])) {
            return false;
        }
        return $res;
    }

    public function setadmintoken($uid, $valid = 86400)
    {
        $token = md5(uniqid(null, true));
        $valid = date('Y-m-d H:i:s', time() + $valid);
        $query = 'UPDATE '.$this->Tbl['admin'].' SET `token`="'.$this->esc($token).'", `token_valid`="'.$this->esc($valid).'" WHERE `uid`='.intval($uid);
        return ($this->query($query)) ? $token : false;
    }

    public function removeadmintoken($uid)
    {
        return $this->query('UPDATE '.$this->Tbl['admin'].' SET `token`=NULL, `token_valid`=NULL WHERE `uid`='.intval($uid));
    }

    /**
     *
     * Return the basic user data for an admin's user ID
     * @param integer user id
     * @return $return array data on success, FALSE otherwise
     */
    public function get_admdata($uid = 0)
    {
        return $this->assoc($this->query('SELECT uid,username,externalemail email,active,choices,permissions,is_root,unix_timestamp(logintime) as login_time, '
                .'unix_timestamp(logouttime) as logout_time FROM '.$this->Tbl['admin'].' WHERE uid="'.intval($uid).'"'));
    }

    // Administrators counterparts for failure count (identical API)
    public function get_admfail($uid = false)
    {
        if (!$uid) {
            return false;
        }
        return $this->assoc($this->query('SELECT fail_count,fail_time FROM '.$this->Tbl['admin'].' WHERE uid='.intval($uid)));
    }

    public function set_admfail($uid = false)
    {
        if (!$uid) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['admin'].' set fail_count=fail_count+1, fail_time=unix_timestamp() WHERE uid='.intval($uid));
    }

    public function reset_admfail($uid = false)
    {
        if (!$uid) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['admin'].' set fail_count=0, fail_time=0 WHERE uid='.intval($uid));
    }

    /**
     * Set login timestamp of a specific admin
     * @param integer user id)
     * @return void
     */
    public function set_admlogintime($uid = false)
    {
        if (!$uid) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['admin'].' set logintime=NOW() WHERE uid='.intval($uid));
    }

    /**
     * Set logout timestamp of a specific admin
     * @param integer user id)
     * @return void
     */
    public function set_admlogouttime($uid = false)
    {
        if (!$uid) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['admin'].' set logouttime=NOW() WHERE uid='.intval($uid));
    }

    /**
     * Update the record of an admin in the database
     * @param $input  array containing user data
     * @return TRUE on success, FALSE otherwise
     */
    public function upd_admin($data)
    {
        if (empty($data['username'])) {
            $qh = $this->query('SELECT `username` FROM '.$this->Tbl['admin'].' WHERE uid='.intval($data['uid']));
            if (!$qh) {
                return false;
            }
            $ret = $this->assoc($qh);
            if (empty($ret['username'])) {
                return false;
            }
            $data['username'] = $ret['username'];
        }
        $query = 'UPDATE '.$this->Tbl['admin'].' SET uid=uid'
                .(!empty($data['username']) ? ', username="'.$this->esc($data['username']).'"' : '')
                .(isset($data['email']) ? ',externalemail="'.$this->esc($data['email']).'"' : '')
                .(!empty($data['password'])
                        ? ',pw_digesta1="'.$this->esc(md5($data['username'].':'.$data['salt'].':'.$data['password'])).'",`password`=""'
                        : '')
                .(isset($data['active']) ? ',active="'.intval($data['active']).'"' : '')
                .(isset($data['is_root']) ? ',is_root="'.$this->esc($data['is_root']).'"' : '')
                .(!empty($data['choices']) ? ',choices="'.$this->esc($data['choices']).'"' : '')
                .(!empty($data['permissions']) ? ',permissions="'.$this->esc($data['permissions']).'"' : '')
                .' WHERE uid="'.intval($data['uid']).'"';
        return ($this->query($query));
    }

    /**
     * Get index for all administrators
     * If you pass "include superadmins" as boolean TRUE, you will also get SAs in the list, else not
     * If a search pattern is given, only usernames containing it will be returned;
     * the pattern may contain '*' or '%' as wildcards
     * If the num (number of admins) and optionally the start values are given, only the search results
     * within this range are returned
     * @param integer user id
     * @param boolean include superadmins
     *[@param string pattern]
     *[@param string criteria]
     *[@param integer num]
     *[@param integer start]
     * @return array data on success, FALSE otherwise
     */
    public function get_admidx($uid = 0, $include_sa = true, $pattern = '', $criteria = '', $num = 0, $start = 0)
    {
        $return = array();
        $q_l = 'SELECT uid,username FROM '.$this->Tbl['admin'].' WHERE 1';
        if (!$include_sa) {
            $q_l .= ' AND is_root!="yes"';
        }
        $pattern = addslashes($pattern);
        if (strlen($pattern) > 0) {
            $pattern = str_replace('*', '%', $this->esc($pattern)); $q_l.=' AND username LIKE "'.$pattern.'"';
        }
        switch ($criteria) {
            case 'inactive': $q_l .= ' AND active="0"';  break;
            case 'active':   $q_l .= ' AND active="1"';  break;
            case 'locked':   $q_l .= ' AND fail_count>='.$GLOBALS['WP_core']['countonfail']; break;
        }
        $q_r = ($num != 0) ? ' LIMIT '.intval($start).','.intval($num) : '';
        $qid = $this->query($q_l.' ORDER BY username'.$q_r);
        while (list ($uid, $username) = $this->fetchrow($qid)) {
            $return[$uid] = $username;
        }
        return $return;
    }

    /** Get numbers of users, acitve users, inactive users, locked administrators
     * @param integer $failcount  the number of failed logins to be considered as 'locked'
     * @return array data on Succes, empty array on failure
     *           $return['all']       All users
     *           $return['active']    active
     *           $return['inactive']  inactive
     *           $return['locked']    locked
     */
    public function get_admoverview($failcount)
    {
        $qid = $this->query('SELECT count(*), active FROM '.$this->Tbl['admin'].' GROUP by active');
        while (list ($number, $active) = $this->fetchrow($qid)) {
            $num[$active] = $number;
        }
        list ($locked) = $this->fetchrow($this->query('SELECT count(*) FROM '.$this->Tbl['admin'].' where fail_count >= '.intval($failcount)));
        $return = array
                ('inactive' => isset($num['0']) ? $num['0'] : 0
                ,'active' => isset($num['1']) ? $num['1'] : 0
                ,'locked' => isset($locked) ? $locked : 0
                );
        $return['all'] = $return['active'] + $return['inactive'] + $return['locked'];
        return $return;
    }

    /**
     * Insert a new admin into the database
     * @param $input array containing admin data
     *           $input['username']       Login name
     *           $input['password']       Password
     *           $input['email']  Email address for notifications
     *           $input['active']         '0' for no, '1' for yes
     *           $input['is_root']        SuperAdmin flag; 'no'|'yes' (Default: 'no')
     *           $input['choices']        string settings (Default:empty string)
     *           $input['permissions']    string permissions (Default:empty string)
     * @return  UserID of created user on success, FALSE otherwise
     */
    public function add_admin($data)
    {
        if (!isset($data['choices'])) {
            $data['choices'] = '';
        }
        if (!isset($data['permissions'])) {
            $data['permissions'] = '';
        }
        if (!isset($data['is_root'])) {
            $data['is_root'] = 'no';
        }
        if ($this->query('INSERT '.$this->Tbl['admin'].' (username,pw_digesta1,externalemail,active,is_root,choices,permissions) VALUES ("'
                .$this->esc($data['username']).'","'.$this->esc(md5($data['username'].':'.$data['salt'].':'.$data['password'])).'","'
                .$this->esc($data['email']).'","'.$this->esc($data['active']).'","'
                .$this->esc($data['is_root']).'","'.$this->esc($data['choices']).'","'
                .$this->esc($data['permissions']).'")')) {
            return $this->insertid();
        }
        return false;
    }

    /**
     * Delete an admin from the database
     * @param $username  username of the admin to be deleted
     * @return  TRUE on success, FALSE otherwise
     */
    public function delete_admin($un)
    {
        list ($uid) = $this->fetchrow($this->query('SELECT uid FROM '.$this->Tbl['admin'].' WHERE username="'.$this->esc($un).'"'));
        return $this->query('DELETE FROM '.$this->Tbl['admin'].' WHERE uid="'.$uid.'"');
    }

    /**
     * Switch activity status of a user
     * @param string username
     * @param 0|1 status
     * @return TRUE on success, FALSE otherwise
     */
    public function onoff_admin($username, $active)
    {
        return $this->query('UPDATE '.$this->Tbl['admin'].' SET active="'.$this->esc($active).'" WHERE username="'.$this->esc($username).'"');
    }

    /**
     * Check, if a given admin's name (already) exists in the database
     * @param string username
     * @return TRUE if exists, FALSE otherwise
     */
    public function checkfor_admname($admname = '')
    {
        list ($exists) = $this->fetchrow($this->query('SELECT 1 FROM '.$this->Tbl['user'].' WHERE username="'.$this->esc($admname).'" LIMIT 1'));
        return (1 == $exists);
    }

    public function add_group($name, $childof = 0, $description = '')
    {
        $this->query('INSERT '.$this->Tbl['group'].' SET `friendly_name`="'.$this->esc($name).'"'
                .',`childof`='.intval($childof).',`description`="'.$this->esc($description).'", `active`="1"');
        return $this->insertid();
    }

    /**
     * Update system group
     *
     * @param int $id  Group's id
     * @param string $name Group's new name or NULL to leave unchanged
     * @param string $description descriptive text or NULL to leave unchanged
     * @param int $childof New parent group ID or NULL to leave unchanged
     * @param bool $active TRUE or FALSE or NULL to leave unchanged
     * @return bool
     */
    public function update_group($id, $name = null, $description = null, $childof = null, $active = null)
    {
        if (func_num_args() < 2) {
            return false;
        }
        return $this->query('UPDATE '.$this->Tbl['group'].' SET `gid`=`gid`'
                .(!is_null($name) ? ', `friendly_name`="'.$this->esc($name).'"' : '')
                .(!is_null($description) ? ', `description`="'.$this->esc($description).'"' : '')
                .(!is_null($childof) ? ', `childof`='.intval($childof) : '')
                .(!is_null($active) ? ', `active`="'.($active ? 1 : 0).'"' : '')
                .' WHERE `gid`='.intval($id));
    }

    public function checkfor_groupname($name)
    {
        $qid = $this->query('SELECT `gid` FROM '.$this->Tbl['group'].' WHERE `friendly_name`="'.$this->esc($name).'"');
        if ($this->numrows($qid)) {
            list ($gid) = $this->fetchrow($qid);
            return $gid;
        }
        return false;
    }

    public function dele_group($id)
    {
        return $this->query('DELETE FROM '.$this->Tbl['group'].' WHERE `gid`='.intval($id));
    }

    /**
     * Handy short cut method to check, whether this installation has any groups
     * or permissions defined. This is used on new installations or those upgraded from
     * a version prior to 4, where permissions were not used.
     *
     * @return bool  TRUE, if there's at least one group and a few permissions set, FALSE otherwise
     */
    public function has_permissions_set()
    {
        // Do we have at least one group?
        $qid = $this->query('SELECT 1 FROM '.$this->Tbl['group']);
        if ($this->numrows($qid)) {
            // Do we have any permissions set for any group?
            $qid = $this->query('SELECT 1 FROM '.$this->Tbl['group_perms']);
            if ($this->numrows($qid)) {
                return true;
            }
        }
        // Do we have any permissions set for any user?
        $qid = $this->query('SELECT 1 FROM '.$this->Tbl['user_perms']);
        if ($this->numrows($qid)) {
            return true;
        }
        // Nothing set, so we consider the permissions NOT set up
        return false;
    }

    /**
     * Sets or drops permissions for a group
     *
     * @param int $gid
     * @param array $perms
     *  values: array('handler' => handler name, 'action' => action name, 'perm' => 0,1,2)
     * @return true
     * @since 4.0.4
     */
    public function set_group_permissions($gid, $perms)
    {
        $gid = intval($gid);
        foreach ($perms as $perm) {
            if ($perm['perm'] != 0 && $perm['perm'] != 1) {
                $perm['perm'] = 2;
            }
            list ($gpid) = $this->fetchrow($this->query('SELECT gpid FROM '.$this->Tbl['group_perms'].' WHERE '
                    .'`handler`="'.$this->esc($perm['handler']).'" AND '
                    .'`action`="'.$this->esc($perm['action']).'" AND `gid`='.$gid));
            if ($gpid) {
                if (2 == $perm['perm']) {
                    $this->query('DELETE FROM '.$this->Tbl['group_perms'].' WHERE `gpid`='.intval($gpid));
                } else {
                    $this->query('UPDATE '.$this->Tbl['group_perms'].' SET `perm`="'.$this->esc($perm['perm']).'"'
                            .' WHERE `gpid`='.intval($gpid));
                }
            } else {
                if (2 == $perm['perm']) {
                    continue;
                }
                $this->query('INSERT INTO '.$this->Tbl['group_perms'].' SET `handler`="'.$this->esc($perm['handler']).'"'
                        .',`action`="'.$this->esc($perm['action']).'",`perm`="'.$this->esc($perm['perm']).'",`gid`='.$gid);
            }
        }
        return true;
    }

    /**
     * Sets or drops permissions for a user
     *
     * @param int $uid
     * @param array $perms values: array('handler' => handler name, 'action' => action name, 'perm' => 0,1,2)
     * @since 4.0.4
     */
    public function set_user_permissions($uid, $perms)
    {
        $uid = intval($uid);
        foreach ($perms as $perm) {
            if ($perm['perm'] != 0 && $perm['perm'] != 1) {
                $perm['perm'] = 2;
            }
            list ($upid) = $this->fetchrow($this->query('SELECT upid FROM '.$this->Tbl['user_perms'].' WHERE '
                    .'`handler`="'.$this->esc($perm['handler']).'" AND '
                    .'`action`="'.$this->esc($perm['action']).'" AND '
                    .'`uid`='.$uid));
            if ($upid) {
                if (2 == $perm['perm']) {
                    $this->query('DELETE FROM '.$this->Tbl['user_perms'].' WHERE `upid`='.intval($upid));
                } else {
                    $this->query('UPDATE '.$this->Tbl['user_perms'].' SET `perm`="'.$this->esc($perm['perm']).'"'
                            .' WHERE `upid`='.intval($upid));
                }
            } else {
                if (2 == $perm['perm']) {
                    continue;
                }
                $this->query('INSERT INTO '.$this->Tbl['user_perms'].' SET `handler`="'.$this->esc($perm['handler']).'"'
                        .',`action`="'.$this->esc($perm['action']).'",`perm`="'.$this->esc($perm['perm']).'"'
                        .',`uid`='.$uid);
            }
        }
        return true;
    }

    /**
     * Adds a user to a system group
     *
     * @param int $uid
     * @param int $gid
     * @return bool
     * @since 4.0.0
     */
    public function add_usertogroup($uid, $gid)
    {
        list ($is_in) = $this->fetchrow($this->query('SELECT 1 FROM '.$this->Tbl['user_group'].' WHERE `gid`='.intval($gid).' AND `uid`='.intval($uid).' LIMIT 1'));
        if ($is_in) {
            return true;
        }
        return $this->query('INSERT '.$this->Tbl['user_group'].' SET `gid`='.intval($gid).', `uid`='.intval($uid));
    }

    /**
     * Removes a user from a certain group. If you specify $gid = 'all', this user
     * is removed from *all* system groups.
     *
     * @param int $uid
     * @param mixed $gid  INT group ID for a certain group, 'all' for really all groups (Beware the consequences!)
     * @return bool
     * @since 4.0.0
     */
    public function remove_userfromgroup($uid, $gid)
    {
        return $this->query('DELETE FROM '.$this->Tbl['user_group'].' WHERE `uid`='.intval($uid).($gid != 'all' ? ' AND `gid`='.intval($gid) : ''));
    }

    public function get_apikey($uid = null, $id = null)
    {
        $salt = $GLOBALS['_PM_']['auth']['system_salt'];

        if (empty($uid) && empty($id)) {
            return false;
        }
        $sql = 'SELECT * FROM '.$this->Tbl['admin_apikey'].' WHERE 1=1';
        if (!empty($uid)) {
            $sql .= ' AND uid='.intval($uid);
        }
        if (!empty($id)) {
            $sql .= ' AND id='.intval($id);
        }
        $sql .= ' ORDER BY uid ASC, id ASC';
        $qid = $this->query($sql);
        if (!$qid) {
            return false;
        }
        $return = array();
        $mySalt = false;
        while ($row = $this->fetchassoc($qid)) {
            if (empty($mySalt)) {
                if (empty($uid)) {
                    $uid = $row['uid'];
                }
                $userInfo = $this->get_admdata($uid);
                $mySalt = $userInfo['username'].'-'.$salt;
            }
            $row['apikey'] = CryptoLib::decryptData($row['apikey'], $salt);
            $return[$row['id']] = $row;
        }
        if (!empty($id) && count($return) == 1) {
            return array_pop($return);
        }
        return $return;
    }

    public function generate_apikey($uid, $comment)
    {
        $salt = $GLOBALS['_PM_']['auth']['system_salt'];
        $key = SecurePassword::generate(24, false, STRONGPASS_LOWERCASE | STRONGPASS_DECIMALS);

        $adminData = $this->get_admdata($uid);
        $encrypted = CryptoLib::encryptData($key, $adminData['username'].'-'.$salt);
        $sql = 'INSERT INTO '.$this->Tbl['admin_apikey'].' (uid, apikey, comment, lastchange) VALUES ('.intval($uid).', "'.$this->esc($encrypted).'", "'.$this->esc($comment).'", NOW())';
        $qid = $this->query($sql);
        if ($qid) {
            return $key;
        }
    }
}
