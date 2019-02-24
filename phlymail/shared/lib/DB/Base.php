<?php
/**
 * The system wide database driver.
 * Provides storage functions for use with a mySQL database
 * It automatically detects, whether it can use ext/mysqli and makes use of it, if so
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage main application
 * @author  Matthias Sommerfeld
 * @copyright 2002-2016 phlyLabs, Berlin http://phlylabs.de
 * @version 4.6.2 2016-11-04
 */
class DB_Base extends DB_Controller
{
    // This holds all config options
    public $features;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['adb_address'] = $this->DB['db_pref'].'adb_adr';
        $this->Tbl['group'] = $this->DB['db_pref'].'groups';
        $this->Tbl['group_perms'] = $this->DB['db_pref'].'group_permissions';
        $this->Tbl['group_quota'] = $this->DB['db_pref'].'group_quota';
        $this->Tbl['user']  = $this->DB['db_pref'].'user';
        $this->Tbl['user_accounting'] = $this->DB['db_pref'].'user_accounting';
        $this->Tbl['user_group'] = $this->DB['db_pref'].'user_group';
        $this->Tbl['user_perms'] = $this->DB['db_pref'].'user_permissions';
        $this->Tbl['user_quota'] = $this->DB['db_pref'].'user_quota';
        $this->Tbl['user_smslogging'] = $this->DB['db_pref'].'user_smslogging';
        $this->Tbl['user_favfolders'] = $this->DB['db_pref'].'user_favouritefolders';

        $this->Tbl['share_folder'] = $this->DB['db_pref'].'share_folder';
        $this->Tbl['share_item'] = $this->DB['db_pref'].'share_item';
        $this->Tbl['profiles'] = $this->DB['db_pref'].'profiles';
        $this->Tbl['signatures'] = $this->DB['db_pref'].'signatures';
        $this->Tbl['aliases'] = $this->DB['db_pref'].'profile_alias';

        // Putting some info to the object, what features the underlying DB supports
        $this->features = array('shares' => true, 'groups' => true, 'permissions' => true);
    }

    /**
     * Authenticates a given user by his/her username against the DB.
     *
     * @param string $un    User name
     * @param string $pw    Password (clear text!)
     * @param string $md5   MD5 hash of the password (w/o salt!); old mechanism of storing the password
     * @param string $a1    Digest auth's digest "A1" (username, salt, password)
     * @param string $salt  Installation's salt
     * @return array [uid, status]
     */
    public function authenticate($un, $pw = null, $md5 = null, $a1 = null, $salt = null)
    {
        $res = $this->getuserauthinfo($un);
        if (empty($res) || $res['active'] != 1) return array(false, false);
        // First the "classic" approach via clear text password
        if (!empty($pw)) {
            if (!empty($res['pw_digesta1']) && $res['pw_digesta1'] == md5($un.':'.$salt.':'.$pw)) {
                return array($res['uid'], true);
            }
            if (!empty($res['password']) && $res['password'] == md5($pw)) {
                // Trying to transfer the old PW to the new one
                $this->query('UPDATE '.$this->Tbl['user'].' SET pw_digesta1="'.md5($un.':'.$salt.':'.$pw).'",password="" WHERE username="'.$this->esc($un).'"');
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
        // This only works while we still have an old password for that user
        if (!empty($md5)) {
            if (!empty($res['password']) && $res['password'] == $md5) {
                return array($res['uid'], true);
            }
            return array($res['uid'], false);
        }
        return array(false, false);
    }

    public function getuserauthinfo($un)
    {
        $query = 'SELECT uid,password,pw_digesta1,externalemail,`token`,`token_valid`,`active` FROM '.$this->Tbl['user'].' WHERE username="'.$this->esc($un).'"';
        $qid = $this->query($query);
        if (empty($qid)) return false;
        $res = $this->assoc($qid);
        // Found no matching record
        if (empty($res)) return false;
        return $res;
    }

    public function getuserpwtype($un)
    {
        $res = $this->getuserauthinfo($un);
        if (empty($res) || $res['active'] != 1) return false;
        if (!empty($res['pw_digesta1'])) {
            return 'digest';
        } elseif (!empty($res['password'])) {
            return 'plain';
        }
        return false;
    }

    public function getuserbytoken($token)
    {
        // Remove all expired tokens
        $this->query('UPDATE '.$this->Tbl['user'].' SET `token`=NULL,`token_valid`=NULL WHERE `token_valid` IS NOT NULL AND `token_valid`<NOW()');
        $query = 'SELECT uid,`username` FROM '.$this->Tbl['user'].' WHERE `token`="'.$this->esc($token).'" AND `token_valid`>=NOW()';
        $qid = $this->query($query);
        if (empty($qid)) return false;
        $res = $this->assoc($qid);
        // Found no matching record
        if (empty($res) || empty($res['uid'])) return false;
        return $res;
    }

    public function setusertoken($uid, $valid = 86400)
    {
        $token = md5(uniqid(null, true));
        $valid = date('Y-m-d H:i:s', time() + $valid);
        $query = 'UPDATE '.$this->Tbl['user'].' SET `token`="'.$this->esc($token).'", `token_valid`="'.$this->esc($valid).'" WHERE `uid`='.intval($uid);
        return ($this->query($query)) ? $token : false;
    }

    public function removeusertoken($uid)
    {
        return $this->query('UPDATE '.$this->Tbl['user'].' SET `token`=NULL, `token_valid`=NULL WHERE `uid`='.intval($uid));
    }

    /**
     * Return the basic user data for an user ID
     *
     * @param int $uid  ID of the user
     *[@param bool $short  Only some of the data; Default: FALSE]
     * @return  $return    array data on success, FALSE otherwise
     */
    public function get_usrdata($uid = 0, $short = false)
    {
        $return = $this->assoc($this->query('SELECT uid,username,externalemail,contactid'
                .',active,unix_timestamp(logintime) login_time, unix_timestamp(logouttime) logout_time'
                .' FROM '.$this->Tbl['user'].' WHERE uid='.intval($uid)));
        if ($return['contactid'] && !$short) {
            $qid = $this->query('SELECT `visibility`,firstname,lastname,birthday,email1 email,www,tel_business,tel_private,cellular,fax,customer_number'
                    .' FROM '.$this->Tbl['adb_address']
                    .' WHERE aid='.$return['contactid']);
            if ($qid) {
                $usr = $this->assoc($qid);
                if (is_array($usr)) $return = array_merge($return, $usr);
            }
        }
        return $return;
    }

    // Returns failure count and timestamp for an user ID
    // Input  : get_usrfail(integer user id)
    // Returns: $return    array data on success, FALSE otherwise
    //          $return['fail_count']  Number of failures
    //          $return['fail_time']   Timestamp of last fail
    public function get_usrfail($uid = false)
    {
        if (!$uid) return false;
        return $this->assoc($this->query('SELECT fail_count,fail_time FROM '.$this->Tbl['user'].' WHERE uid='.intval($uid)));
    }

    // Set failure count of a certain user
    // Input  : set_usrfail(integer user id)
    // Returns: $return    boolean, TRUE on success, FALSE otherwise
    public function set_usrfail($uid = false)
    {
        if (!$uid) return;
        return $this->query('UPDATE '.$this->Tbl['user'].' set fail_count=fail_count+1, fail_time=unix_timestamp() WHERE uid='.intval($uid));
    }

    // Reset failure count ( == set to 0) of a certain user
    // Input  : reset_usrfail(integer user id)
    // Returns: $return    boolean, TRUE on success, FALSE otherwise
    public function reset_usrfail($uid = false)
    {
        if (!$uid) return;
        return $this->query('UPDATE '.$this->Tbl['user'].' set fail_count=0, fail_time=0 WHERE uid='.intval($uid));
    }

    // Set login timestamp of a specific user
    // Input : set_logintime(integer user id)
    // Return: void
    public function set_logintime($uid = false)
    {
        if (!$uid) return;
        return $this->query('UPDATE '.$this->Tbl['user'].' set logintime=NOW() WHERE uid='.intval($uid));
    }

    // Set logout timestamp of a specific user
    // Input : set_logouttime(integer user id)
    // Return: void
    public function set_logouttime($uid = false)
    {
        if (!$uid) return;
        $this->query('UPDATE '.$this->Tbl['user'].' set logouttime=NOW() WHERE uid='.intval($uid));
        $qid = $this->query('SELECT TIME_TO_SEC(TIMEDIFF(logouttime, logintime)) FROM '.$this->Tbl['user'].' WHERE uid='.intval($uid));
        list ($online) = $this->fetchrow($qid);
        return $this->set_user_accounting('online', date('Ym'), $uid, $online);
    }

    /**
     * Insert a new user into the database
     * @param array  $data  User's data
     * @return mixed UserID of created user on success, FALSE otherwise
     */
    public function add_user($data)
    {
        $this->query('INSERT '.$this->Tbl['adb_address'].' SET `type`="user",`uuid`="'.basics::uuid().'"'
                .',`visibility`='.(isset($data['visibility']) && $data['visibility'] == 'public' ? '"public"' : '"private"')
                .',firstname="'.(isset($data['firstname']) ? $this->esc($data['firstname']) : '').'"'
                .',lastname="'.(isset($data['lastname']) ? $this->esc($data['lastname']) : '').'"'
                .',birthday="'.(isset($data['birthday']) ? $this->esc($data['birthday']) : '').'"'
                .',email1="'.(isset($data['email']) ? $this->esc($data['email']) : '').'"'
                .',www="'.(isset($data['www']) ? $this->esc($data['www']) : '').'"'
                .',tel_private="'.(isset($data['tel_private']) ? $this->esc($data['tel_private']) : '').'"'
                .',tel_business="'.(isset($data['tel_business']) ? $this->esc($data['tel_business']) : '').'"'
                .',cellular="'.(isset($data['cellular']) ? $this->esc($data['cellular']) : '').'"'
                .',fax="'.(isset($data['fax']) ? $this->esc($data['fax']) : '').'"'
                .(isset($data['customer_number']) ? ',`customer_number`="'.$this->esc($data['customer_number']).'"' : '')
                );
        $data['contactid'] = $this->insertid();
        if ($this->query('INSERT '.$this->Tbl['user'].' SET'
                .' username="'.$this->esc($data['username']).'"'
                .',externalemail="'.$this->esc($data['externalemail']).'"'
                .',pw_digesta1="'.$this->esc(md5($data['username'].':'.$data['salt'].':'.$data['password'])).'"'
                .',contactid='.(isset($data['contactid']) ? intval($data['contactid']) : 'NULL')
                .',active="'.$this->esc($data['active']).'"'
                .',choices="'.(isset($data['choices']) ? $this->esc($data['choices']) : '').'"')) {
            return $this->insertid();
        }
        return false;
    }

    /**
     * Update the record of a user in the database
     * @param array  $data  User's data
     * @return bool TRUE on success, FALSE otherwise
     */
    public function upd_user($data)
    {
        if (empty($data)) return true;
        $sqladd = '';
        list ($adrid) = $this->fetchrow($this->query('SELECT u.contactid FROM '.$this->Tbl['user'].' u,'.$this->Tbl['adb_address'].' a'
                .' WHERE u.uid='.intval($data['uid']).' AND a.aid=u.contactid'));
        if ($adrid) {
            $this->query('UPDATE '.$this->Tbl['adb_address'].' SET `uuid`="'.basics::uuid().'"'
                    .',firstname='.(isset($data['firstname']) ? '"'.$this->esc($data['firstname']).'"' : 'firstname')
                    .',lastname='.(isset($data['lastname']) ? '"'.$this->esc($data['lastname']).'"' : 'lastname')
                    .',birthday='.(isset($data['birthday']) ? '"'.$this->esc($data['birthday']).'"' : 'birthday')
                    .',email1='.(isset($data['email']) ? '"'.$this->esc($data['email']).'"' : 'email1')
                    .',www='.(isset($data['www']) ? '"'.$this->esc($data['www']).'"' : 'www')
                    .',tel_private='.(isset($data['tel_private']) ? '"'.$this->esc($data['tel_private']).'"' : 'tel_private')
                    .',tel_business='.(isset($data['tel_business']) ? '"'.$this->esc($data['tel_business']).'"' : 'tel_business')
                    .',cellular='.(isset($data['cellular']) ? '"'.$this->esc($data['cellular']).'"' : 'cellular')
                    .',fax='.(isset($data['fax']) ? '"'.$this->esc($data['fax']).'"' : 'fax')
                    .(isset($data['visibility']) ? ',`visibility`='.($data['visibility'] == 'public' ? '"public"' : '"private"') : '')
                    .(isset($data['customer_number']) ? ',`customer_number`="'.$this->esc($data['customer_number']).'"' : '')
                    .' WHERE aid='.intval($adrid) # .' AND `type`="user" AND `owner`=0' # FIXME: Better would be .intval($data['uid'])
                    );
        } else {
            $this->query('INSERT '.$this->Tbl['adb_address'].' SET `type`="user",`uuid`="'.basics::uuid().'"'
                    .',`visibility`='.(isset($data['visibility']) && $data['visibility'] == 'public' ? '"public"' : '"private"')
                    .',firstname="'.(isset($data['firstname']) ? $this->esc($data['firstname']) : '').'"'
                    .',lastname="'.(isset($data['lastname']) ? $this->esc($data['lastname']) : '').'"'
                    .',birthday="'.(isset($data['birthday']) ? $this->esc($data['birthday']) : '').'"'
                    .',email1="'.(isset($data['email']) ? $this->esc($data['email']) : '').'"'
                    .',www="'.(isset($data['www']) ? $this->esc($data['www']) : '').'"'
                    .',tel_private="'.(isset($data['tel_private']) ? $this->esc($data['tel_private']) : '').'"'
                    .',tel_business="'.(isset($data['tel_business']) ? $this->esc($data['tel_business']) : '').'"'
                    .',cellular="'.(isset($data['cellular']) ? $this->esc($data['cellular']) : '').'"'
                    .',fax="'.(isset($data['fax']) ? $this->esc($data['fax']) : '').'"'
                    .(isset($data['customer_number']) ? ',`customer_number`="'.$this->esc($data['customer_number']).'"' : '')
                    );
            $sqladd = ',contactid='.$this->insertid();
        }
        if (empty($data['username'])) {
            $qh = $this->query('SELECT `username` FROM '.$this->Tbl['user'].' WHERE uid='.intval($data['uid']));
            if (!$qh) return false;
            $ret = $this->assoc($qh);
            if (empty($ret['username'])) return false;
            $data['username'] = $ret['username'];
        } else {
            $sqladd .= ',username="'.$this->esc($data['username']).'"';
        }
        if (isset($data['externalemail'])) {
            $sqladd .= ',externalemail="'.$this->esc($data['externalemail']).'"';
        }
        if (!empty($data['password'])) {
            $sqladd .= ',pw_digesta1="'.$this->esc(md5($data['username'].':'.$data['salt'].':'.$data['password'])).'",`password`=""';
        }
        if (isset($data['active']) && $data['active'] != '') {
            $sqladd .= ',active="'.$this->esc($data['active']).'"';
        }

        return $this->query('UPDATE '.$this->Tbl['user'].' SET uid=uid'.$sqladd.' WHERE uid='.intval($data['uid']));
    }

    // Delete a user and his/her accounts from the database
    // Input  : $username  username of the user to be deleted
    // Returns: $return    TRUE on success, FALSE otherwise
    public function delete_user($un)
    {
        list ($uid, $contactid) = $this->fetchrow($this->query('SELECT uid,contactid FROM '.$this->Tbl['user'].' WHERE username="'.$this->esc($un).'"'));
        if ($this->query('DELETE FROM '.$this->Tbl['user'].' WHERE uid='.intval($uid))) {
            $this->query('DELETE FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['user_quota'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['user_accounting'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['user_favfolders'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['user_smslogging'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['signatures'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['aliases'].' WHERE uid='.intval($uid));
            $this->query('DELETE FROM '.$this->Tbl['adb_address'].' WHERE aid='.intval($contactid));
            if (isset($this->features['shares']) && $this->features['shares']) {
                $this->query('DELETE FROM '.$this->Tbl['share_folder'].' WHERE uid='.intval($uid));
                $this->query('DELETE FROM '.$this->Tbl['share_item'].' WHERE uid='.intval($uid));
            }
            if (isset($this->features['groups']) && $this->features['groups']) {
                $this->query('DELETE FROM '.$this->Tbl['user_group'].' WHERE uid='.intval($uid));
            }
            if (isset($this->features['permissions']) && $this->features['permissions']) {
                $this->query('DELETE FROM '.$this->Tbl['user_perms'].' WHERE uid='.intval($uid));
            }
            return true;
        }
        return false;
    }

    /**
     * Check, if a given username (already) exists in the database
     *
     * @param string $username Username to check the DB for
     * @param bool $giveuid  Set to true to get the UID, if any; Default false
     * @return bool|int If $giveuid == true returns the UID, else TRUE if the username already exists
     */
    public function checkfor_username($username = '', $giveuid = false)
    {
        $qid = $this->query('SELECT uid FROM '.$this->Tbl['user'].' WHERE username="'.$this->esc($username).'"');
        if (!$this->numrows($qid)) {
            return false;
        }
        if (!$giveuid) {
            return true;
        }
        list ($exists) = $this->fetchrow($qid);
        return $exists;
    }

    // Switch activity status of a user
    // Input:  onoff_user(string username, integer status) status[0|1]
    // Return: TRUE on success, FALSE otherwise
    public function onoff_user($username, $active)
    {
        return $this->query('UPDATE '.$this->Tbl['user'].' SET active="'.$this->esc($active).'" WHERE username="'.$this->esc($username).'"');
    }

    /**
     * Check for shared folder permissions applying to a given user
     *
     * @param int $uid  ID of the user to check the shares for
     * @param string $handler  Handler name, e.g. "calendar"
     * @param int $fid  ID of the folder we check for shres
     * @return array  Permissions as per the group or user permissions set
     */
    public function getUserSharedFolderPermissions($uid, $handler, $fid)
    {
        $DCS = new DB_Controller_Share();
        $myGroups = $this->get_usergrouplist($uid, true);
        $theShares = $DCS->getFolder($handler, $fid == 'root' ? 0 : $fid);

        // Initially forbidden access
        $return = array('list' => 0, 'read' => 0, 'write' => 0, 'delete' => 0, 'newfolder' => 0, 'delitems' => 0);

        // First check for group permissions matching my groups. These are additive
        foreach ($theShares['gid'] as $gid => $perms) {
            if (in_array($gid, $myGroups)) {
                foreach (array('list', 'read', 'write', 'delete', 'newfolder', 'delitems') as $tok) {
                    if (!empty($perms[$tok])) {
                        $return[$tok] = true;
                    }
                }
            }
        }

        // User permissions, if set, always overrule the group permissions as to allow to revoke a user a specific permissions
        if (!empty($theShares['uid'][$uid])) {
            foreach (array('list', 'read', 'write', 'delete', 'newfolder', 'delitems') as $tok) {
                if (isset($theShares['uid'][$uid][$tok])) {
                    $return[$tok] = $theShares['uid'][$uid][$tok];
                }
            }
        }

        return $return;
    }

    /**
     * Get index for all users
     * If a search pattern is given, only usernames containing it will be returned;
     * the pattern may contain '*' or '%' as wildcards
     * If the num (number of users) and optionally the start values are given, only the
     * search results within this range are returned
     *
     *[@param string $pattern]
     *[@param string $criteria]
     *[@param int $num]
     *[@param int $start]
     * @return array $return data on success, FALSE otherwise
     */
    public function get_usridx($pattern = '', $criteria = '', $num = 0, $start = 0)
    {
        $return = array();
        $q_l = 'SELECT uid,username FROM '.$this->Tbl['user'].' WHERE 1';
        if (is_array($pattern)) {
            foreach ($pattern as $k => $v) {
                $pattern[$k] = (is_numeric($v)) ? $v : '"'.$this->esc($v).'"';
            }
            $q_l .= ' AND '.$this->esc($criteria).' IN ('.implode(',', $pattern).')';
        } elseif (strlen($pattern) > 0) {
            $q_l .= ' AND username LIKE "'.str_replace('*', '%', $this->esc($pattern)).'"';
        }
        switch($criteria) {
            case 'inactive': $q_l .= ' AND active="0"';  break;
            case 'active': $q_l .= ' AND active="1"';  break;
            case 'locked': $q_l .= ' AND fail_count>='.intval($GLOBALS['_PM_']['auth']['countonfail']);  break;
        }
        $q_r = ($num != 0 ) ? ' LIMIT '.($start).','.($num) : '';
        $qid = $this->query($q_l.' ORDER BY username'.$q_r);
        while (list($uid, $username) = $this->fetchrow($qid)) {
            $return[$uid] = $username;
        }
        return $return;
    }

    // Get numbers of users, acitve users, inactive users, locked users
    // Input  : get_usroverview(integer $failcount)
    //          where $failcount is the number of failed logins to be considered as 'locked'
    // Returns: $return              array data on Succes, empty array on failure
    //          $return['all']       All users
    //          $return['active']    active
    //          $return['inactive']  inactive
    //          $return['locked']    locked
    public function get_usroverview($failcount)
    {
        $qid = $this->query('SELECT count(*), active FROM '.$this->Tbl['user'].' GROUP by active');
        while(list($number, $active) = $this->fetchrow($qid)) {
            $num[$active] = $number;
        }
        list ($locked) = $this->fetchrow($this->query('SELECT count(*) FROM '.$this->Tbl['user'].' where fail_count >= '.intval($failcount)));
        $return = array
                ('inactive' => isset($num['0']) ? $num['0'] : 0
                ,'active'   => isset($num['1']) ? $num['1'] : 0
                ,'locked'   => isset($locked)   ? $locked   : 0
                );
        $return['all'] = $return['active'] + $return['inactive'] + $return['locked'];
        return $return;
    }

    // Get user's personal setup from the DB
    // Input  : get_usr_choices(integer user id)
    // Returns: $return    string data on success, FALSE otherwise
    public function get_usr_choices($uid)
    {
        if ($choices = $this->query('SELECT choices FROM '.$this->Tbl['user'].' WHERE uid='.intval($uid))) {
            list ($choices) = $this->fetchrow($choices);
            if (strstr($choices, ';;')) {
                $return = array();
                foreach (explode(LF, $choices) as $l) {
                    if (strlen(trim($l)) == 0) continue;
                    if ($l{0} == '#') continue;
                    $parts = explode(';;', trim($l));
                    if (!isset($parts[1])) $parts[1] = false;
                    $return['core'][$parts[0]] = $parts[1];
                }
                return $return;
            } else {
                return @unserialize($choices);
            }
        }
        return false;
    }

    // Input  : set_usr_choices(integer user id, string choices)
    // Returns: $return    TRUE on success, FALSE otherwise
    public function set_usr_choices($uid, $choices)
    {
        $choices = serialize($choices);
        $query = 'UPDATE '.$this->Tbl['user'].' SET choices="'.$this->esc($choices).'" WHERE uid='.intval($uid);
        return $this->query($query);
    }

    // Activate user (Register now)
    public function activate($uid, $un, $pw, $salt)
    {
        $uid = intval($uid);
        list ($return) = $this->fetchrow($this->query('SELECT 1 FROM '.$this->Tbl['user']
                .' WHERE username="'.$this->esc($un).'" AND pw_digesta1="'.md5($un.':'.$salt.':'.$pw).'" AND uid='.$uid.' LIMIT 1'));
        if (1 == $return) $this->query('UPDATE '.$this->Tbl['user'].' SET active="1" WHERE uid='.$uid);
        return $return;
    }

    // Tell, how many users there are in the database
    public function get_usercount()
    {
        list ($return) = $this->fetchrow($this->query('SELECT COUNT(*) FROM '.$this->Tbl['user']));
        return $return;
    }

    // Get amount of sent SMS for a certain user in a given month
    public function get_user_accounting($type, $month = '197001', $uid = false)
    {
        $query = 'SELECT SUM(setting) FROM '.$this->Tbl['user_accounting']
                .' WHERE `what`="'.$this->esc($type).'" AND `when`="'.intval($month).'" AND uid='.intval($uid);
        list ($sum) = $this->fetchrow($this->query($query));
        return $sum;
    }

    public function set_user_accounting($type, $month = '197001', $uid, $amount)
    {
        $type = $this->esc($type);
        $amount = intval($amount);
        $month = intval($month);
        $uid = intval($uid);
        $qid = $this->query('SELECT 1 FROM '.$this->Tbl['user_accounting'].' WHERE `what`="'.$type.'" AND `when`='.$month.' AND uid='.$uid.' LIMIT 1');
        list ($exists) = $this->fetchrow($qid);
        if ($exists) {
            $query = 'UPDATE '.$this->Tbl['user_accounting'].' SET `setting`=`setting`+'.$amount.' WHERE `what`="'.$type.'" AND `when`='.$month.' AND `uid`='.$uid;
        } else {
            $query = 'INSERT '.$this->Tbl['user_accounting'].' (`what`,`when`,`uid`,`setting`) VALUES ("'.$type.'",'.$month.','.$uid.','.$amount.')';
        }
        $this->query($query);
        return true;
    }

    // Log a sent SMS to MySQL
    public function log_sms_sent($pass)
    {
        if (!isset($pass['when'])) $pass['when'] = time();
        if (!isset($pass['uid']))  $pass['uid'] = 0;
        if (!isset($pass['type'])) $pass['type'] = 0;
        if (!isset($pass['text'])) $pass['text'] = '';
        if (!isset($pass['receiver']) || !isset($pass['size'])) return;

        $query = 'INSERT '.$this->Tbl['user_smslogging'].' (uid, moment, target_number, size, type, content) VALUES ('
                 .intval($pass['uid']).',"'.date('Y-m-d H:i:s', $pass['when']).'","'.$this->esc($pass['receiver']).'",'
                 .intval($pass['size']).','.$this->esc($pass['type']).',"'.$this->esc($pass['text']).'")';
        $this->query($query);
    }

     // Set current deposit of the system
    public function get_sms_global_deposit()
    {
        $query = 'SELECT setting FROM '.$this->Tbl['user_accounting'].' WHERE `what`="sms" AND uid=0';
        list ($sum) = $this->fetchrow($this->query($query));
        return $sum;
    }

     // Decrease the global deposit by one
    public function decrease_sms_global_deposit($amount = 1)
    {
        if (!$amount) $amount = 1;
        $query = 'UPDATE '.$this->Tbl['user_accounting'].' SET setting=setting-'.intval($amount).' WHERE `what`="sms" AND uid=0';
        $this->query($query);
    }

    // Set current deposit of the system
    public function set_sms_global_deposit($deposit)
    {
        list ($exists) = $this->fetchrow($this->query('SELECT 1 FROM '.$this->Tbl['user_accounting'].' WHERE `what`="sms" AND uid=0 LIMIT 1'));
        $query = ($exists)
                ? 'UPDATE '.$this->Tbl['user_accounting'].' SET setting='.intval($deposit).' WHERE `what`="sms" AND uid=0'
                : 'INSERT '.$this->Tbl['user_accounting'].' (setting, `what`, uid) VALUES ('.intval($deposit).',"sms",0)'
                ;
        $this->query($query);
    }

     // Get sent SMS statistics for a specific month
    // Input:  get_sms_stats(int month); Format: YYYYMM
    // Return: array
    //         (int sum of sent SMS
    //         ,int max sent SMS by single user
    //         )
    public function get_sms_stats($month = '197001', $uid = false)
    {
        $add = (!$uid) ? 'uid!=0' : 'uid='.intval($uid);
        $query = 'SELECT sum(setting), min(setting), max(setting), count(*) FROM '.$this->Tbl['user_accounting']
                .' WHERE `what`="sms" AND `when`="'.intval($month).'" AND '.$add;
        list ($sum, $min, $max, $cnt) = $this->fetchrow($this->query($query));
        // If we have no events, we don't need to try to get those:
        // Same applies on fetching stats for a specific user
        if (isset($sum) && $sum && !$uid) {
            $query = 'SELECT u.uid, u.username FROM '.$this->Tbl['user_accounting'].' a,'.$this->Tbl['user']
                    .' u WHERE a.`what`="sms" AND a.`when`="'.intval($month).'" AND a.'.$add
                    .' AND a.uid=u.uid ORDER by a.setting ASC LIMIT 1';
            list ($min_uid, $min_usr) = $this->fetchrow($this->query($query));
            $query = 'SELECT u.uid, u.username FROM '.$this->Tbl['user_accounting'].' a,'.$this->Tbl['user']
                    .' u WHERE a.`what`="sms" AND a.`when`="'.intval($month).'" AND a.'.$add
                    .' AND a.uid=u.uid ORDER by a.setting DESC LIMIT 1';
            list ($max_uid, $max_usr) = $this->fetchrow($this->query($query));
        }
        return array(isset($sum) ? $sum : 0, isset($min) ? $min : 0, isset($max) ? $max : 0, isset($cnt) ? $cnt : 0
                ,isset($min_uid) ? array('min_usr' => $min_usr, 'min_uid' => $min_uid, 'max_usr' => $max_usr, 'max_uid' => $max_uid) : false
                );
    }

    /**
     * Tries to find out the specific quota setting effective for the given user ID.
     * If no setting was found, false will be returned. The same applies, if the setting
     * has been explicitly set to false. False values mean: no limit set.
     *
     * @param int $uid Either a value > 0 for a specific user or == 0 for the global definition
     * @param string $handler The handler this quota setting applies to
     * @param string $what The quota setting to query
     * @return mixed Either FALSE for no setting / unlimited or the specific value defined for the setting
     * @since 3.9.1
     */
    public function quota_get($uid, $handler, $what)
    {
        if ($uid !== 0) {
            $res = $this->query('SELECT `setting` FROM '.$this->Tbl['user_quota'].' WHERE `uid`='
                    .intval($uid).' AND `handler`="'.$this->esc($handler).'" AND `what`="'
                    .$this->esc($what).'"');
            // At least one result for that query
            if ($this->numrows($res)) {
                list ($setting) = $this->fetchrow($res);
                return $setting;
            }
        }
        $res = $this->query('SELECT `setting` FROM '.$this->Tbl['user_quota'].' WHERE `uid`=0'
                .' AND `handler`="'.$this->esc($handler).'" AND `what`="'
                .$this->esc($what).'"');
        // At least one result for that query
        if ($this->numrows($res)) {
            list ($setting) = $this->fetchrow($res);
            return $setting;
        }
        return false;
    }

    /**
     * Sets a quota value effective for a specific user ID and handler name. Passing a
     * user ID of 0 you define the global setting, which will take effect, whenever there's
     * no specific value defined for a specific user.
     *
     * @param int $uid Either a value > 0 for a specific user or == 0 for the global definition
     * @param string $handler The handler this quota setting applies to
     * @param string $what The quota setting
     * @param string $setting Its value
     * @return bool
     * @since 3.9.1
     */
    public function quota_set($uid, $handler, $what, $setting)
    {
        $res = $this->query('SELECT 1 FROM '.$this->Tbl['user_quota'].' WHERE `uid`='.intval($uid)
                .' AND `handler`="'.$this->esc($handler).'" AND `what`="'
                .$this->esc($what).'" LIMIT 1');
        // Determine, whether to update the column or insert it
        $query = ($this->numrows($res))
                ? 'UPDATE '.$this->Tbl['user_quota'].' SET `setting`="'.$this->esc($setting)
                        .'" WHERE `uid`='.intval($uid).' AND `handler`="'.$this->esc($handler)
                        .'" AND `what`="'.$this->esc($what).'"'
                : 'INSERT '.$this->Tbl['user_quota'].' SET `uid`='.intval($uid)
                        .', `handler`="'.$this->esc($handler).'", `what`="'
                        .$this->esc($what).'", `setting`="'.$this->esc($setting).'"'
                ;
        return $this->query($query);
    }

    /**
     * Explicitly removes a quota definition.
     *
     * @param int $uid Either a value > 0 for a specific user or == 0 for the global definition
     * @param string $handler The handler this quota setting applies to
     * @param string $what The quota setting
     * @return bool
     * @since 3.9.1
     */
    public function quota_drop($uid, $handler, $what)
    {
        return $this->query('DELETE FROM '.$this->Tbl['user_quota'].' WHERE `uid`='.intval($uid).' AND `handler`="'.$this->esc($handler).'" AND `what`="'.$this->esc($what).'"');
    }

    //
    // Section for group management
    //

    /**
     * Gets a list of all system groups. If $active is set to FALSE, all groups
     * are returned, regardless of their activity status. Otherwise only active
     * groups are returned.
     *
     *[@param bool $active  FALSE to return really all groups, not just the active ones, default: TRUE]
     * @return array
     * @since 4.0.0
     */
    public function get_grouplist($active = true, $rawonly = false)
    {
        $return = array();
        $qh = $this->query('SELECT g.`gid`, g.`childof`, g.`friendly_name`, g.`description`, g.`active`'
                .', (SELECT COUNT(*) FROM '.$this->Tbl['user_group'].' ug,'.$this->Tbl['user'].' u WHERE ug.`gid`=g.`gid` AND ug.`uid`=u.`uid`) `num_users`'
                .' FROM '.$this->Tbl['group'].' g'.($active ? ' WHERE g.`active`="1"' : ''));
        while ($line = $this->assoc($qh)) {
            if ($rawonly) {
                $return[$line['gid']] = $line['friendly_name'];
            } else {
                $return['raw'][$line['gid']] = $line;
        	    $return['childs'][$line['childof']][$line['gid']] = $line;
            }
        }
        return $return;
    }

    /**
     * Return details about given system group
     *
     * @param int $gid
     * @return mixed FALSE on failure, Group data as array otherweise
     * @since 4.0.0
     */
    public function get_groupinfo($gid)
    {
        $qh = $this->query('SELECT g.`gid`, g.`childof`, g.`friendly_name`, g.`description`, g.`active`'
                .', (SELECT COUNT(*) FROM '.$this->Tbl['user_group'].' ug,'.$this->Tbl['user'].' u WHERE ug.`gid`=g.`gid` AND ug.`uid`=u.`uid`) `num_users`'
                .' FROM '.$this->Tbl['group'].' g WHERE g.`gid`='.intval($gid));
        if (!$this->numrows($qh)) {
            return false;
        }
        return $this->assoc($qh);
    }

    /**
     * Retrieves a list of users, which are in a certain system group
     *
     * @param int|array $gid Group ID or array of group IDs
     * @return mixed FALSE on failure, Group data as array otherweise
     * @since 4.0.0
     */
    public function get_groupuserlist($gid, $extended = false)
    {
        $gidSql = '`gid`';
        if (is_array($gid)) {
            $gidSql .= ' IN('.implode(',', basics::intify($gid)).')';
        } else {
            $gidSql .= '='.intval($gid);
        }

        $return = array();
        if ($extended) {
            $qh = $this->query('SELECT ug.`uid`, u.`username` FROM '.$this->Tbl['user_group'].' ug,'.$this->Tbl['user'].' u'
                    .' WHERE ug.'.$gidSql.' AND u.`uid`=ug.`uid`');
            while ((list($uid, $uname) = $this->fetchrow($qh))) {
                $return[$uid] = $uname;
            }
        } else {
            $qh = $this->query('SELECT `uid` FROM '.$this->Tbl['user_group'].' WHERE '.$gidSql);
            while ((list($uid) = $this->fetchrow($qh))) {
                $return[] = $uid;
            }
        }
        return $return;
    }

    /**
     * Returns a list of all system groups a given user is in
     *
     * @param int $uid ID of the user
     *[@param bool $active  Return really all groups, not just the active ones]
     * @return array
     * @since 4.0.0
     */
    public function get_usergrouplist($uid, $active = true, $long = false)
    {
        $return = array();
        if ($long) {
            $qh = $this->query('SELECT ug.`gid`, g.`friendly_name` FROM '.$this->Tbl['user_group'].' ug,'.$this->Tbl['group']
                    .' g WHERE ug.`uid`='.intval($uid).' AND ug.`gid`=g.`gid`'.($active ? ' AND g.`active`="1"' : ''));
            while (list($gid, $gname) = $this->fetchrow($qh)) {
                $return[$gid] = $gname;
            }
            return $return;
        }
        $qh = $this->query('SELECT ug.`gid` FROM '.$this->Tbl['user_group'].' ug,'.$this->Tbl['group'].' g WHERE ug.`uid`='.intval($uid)
                .' AND ug.`gid`=g.`gid`'.($active ? ' AND g.`active`="1"' : ''));
        while (list($gid) = $this->fetchrow($qh)) {
            $return[] = $gid;
        }
        return $return;
    }

    /**
     * Set, which groups a user belongs to
     *
     * @param int $uid ID of the user
     * @param array $groups  The values hold the group IDs, where the user is a member of
     * @return true
     * @since 4.1.3
     */
    public function set_usergrouplist($uid, $groups)
    {
        $oldgroups = $this->get_usergrouplist($uid);
        $already = array(); // Entries present in both arrays don't need an update
        foreach ($oldgroups as $gid) {
            if (!in_array($gid, $groups)) {
                $this->query('DELETE FROM '.$this->Tbl['user_group'].' WHERE `uid`='.intval($uid).' AND `gid`='.intval($gid));
            } else {
                $already[] = $gid;
            }
        }
        foreach ($groups as $gid) {
            if (!in_array($gid, $already)) {
                $this->query('INSERT INTO '.$this->Tbl['user_group'].' SET `uid`='.intval($uid).',`gid`='.intval($gid));
            }
        }
        return true;
    }

    //
    // Section of methods for access permissions according to groups defined and share definitions
    //

    /**
     * Retrieves an array, which contains an access table for a given user.
     * According to the groups, the user is in and the permissions defined we render
     * the effective permissions.
     *
     * If a user has individual permissions set, they override group permissions!
     * Take care of this behaviour!
     *
     * @param int  $uid   UID of the user
     *[@param bool $strict  Set to true to return only the perms set for that user, no inheritance then; Default: FALSE]
     * @return array Table holding all modules of all handlers the user can access at all
     *    and whether he/she has read or readwrite access.
     * @since 4.0.5
     */
    public function get_user_permissions($uid, $strict = false)
    {
        $return = array();
        if (!$strict) {
            $qid = $this->query('SELECT gp.`handler`, gp.`action`, gp.`perm` FROM '
                    .$this->Tbl['group_perms'].' gp, '.$this->Tbl['user_group'].' ug'
                    .' WHERE gp.`gid`=ug.`gid` AND ug.`uid`='.intval($uid));
            if ($this->numrows($qid)) {
                while ($line = $this->assoc($qid)) {
                    if (!isset($return[$line['handler'].'_'.$line['action']])
                            || $return[$line['handler'].'_'.$line['action']] < $line['perm']) {
                        $return[$line['handler'].'_'.$line['action']] = $line['perm'];
                    }
                }
            }
        }
        $qid = $this->query('SELECT `handler`, `action`, `perm` FROM '.$this->Tbl['user_perms'].' WHERE `uid`='.intval($uid));
        if ($this->numrows($qid)) {
            while ($line = $this->assoc($qid)) {
                $return[$line['handler'].'_'.$line['action']] = $line['perm'];
            }
        }
        return $return;
    }

    public function get_group_permissions($gid)
    {
        $return = array();
        while (true) {
            list ($childof) = $this->fetchrow($this->query('SELECT childof FROM '.$this->Tbl['group'].' WHERE gid='.intval($gid)));
            $qid = $this->query('SELECT `handler`,`action`,`perm` FROM '.$this->Tbl['group_perms'].' WHERE `gid`='.intval($gid));
            while ($line = $this->assoc($qid)) {
                if (isset($return[$line['handler'].'_'.$line['action']])) continue;
                $return[$line['handler'].'_'.$line['action']] = $line['perm'];
            }
            if ($childof == 0) return $return;
            $gid = $childof;
        }
        // Coming here means, the group has no privileges set...
        return $return;
    }

}
