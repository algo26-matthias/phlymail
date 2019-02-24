<?php
/**
 * handlers/email/index.mysql.php
 * Proivdes indexing functions for use with a mySQL database
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler email
 * @author Matthias Sommerfeld
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.5.8 2015-08-07
 */
class handler_email_indexer extends DB_Controller
{
    private $error = array();

    public function __construct()
    {
        parent::__construct();

        $this->Tbl['email_filter'] = $this->DB['db_pref'].'email_filters';
        $this->Tbl['email_filter_rule'] = $this->DB['db_pref'].'email_filterrules';
        $this->Tbl['email_folder'] = $this->DB['db_pref'].'email_folders';
        $this->Tbl['email_index'] = $this->DB['db_pref'].'email_index';
        $this->Tbl['email_thread'] = $this->DB['db_pref'].'email_threads';
        $this->Tbl['email_thread_items'] = $this->DB['db_pref'].'email_thread_items';
        $this->Tbl['email_uidlcache'] = $this->DB['db_pref'].'email_uidlcache';
        $this->Tbl['email_whitelist'] = $this->DB['db_pref'].'email_whitelist';
        $this->Tbl['profiles'] = $this->DB['db_pref'].'profiles';
        $this->Tbl['user_foldersettings'] = $this->DB['db_pref'].'user_foldersettings';
    }

    public function affected() { return $this->affected(); }

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
     * Removes all information about a user from the index
     *
     * @param  int  User ID affected
     * @return  boolean  TRUE on success, FALSE otherwise
     * @since 0.2.6
     */
    public function remove_user($uid)
    {
        $uid = (float) $uid;
        if (!$uid) {
            return false;
        }
        // Get profiles of the user - if any
        $profiles = array();

        $qid = $this->query('SELECT id FROM '.$this->Tbl['profiles'].' WHERE uid='.$uid);
        while (list($profile) = $this->fetchrow($qid)) {
            $profiles[] = (float) $profile;
        }
        if (!empty($profiles)) {
            $this->query('DELETE FROM '.$this->Tbl['email_uidlcache'].' WHERE profile IN('.implode(',', $profiles).')');
        }
        // Remove filter rules
        foreach ($this->filters_getlist($uid, 'all') as $filter) {
            $this->filters_removefilter($uid, $filter['id']);
        }
        // And the base tables' contents
        return ($this->query('DELETE FROM '.$this->Tbl['email_folder'].' WHERE uid='.$uid)
                && $this->query('DELETE FROM '.$this->Tbl['email_index'].' WHERE uid='.$uid)
                && $this->query('DELETE FROM '.$this->Tbl['email_thread'].' WHERE uid='.$uid)
                && $this->query('DELETE FROM '.$this->Tbl['email_thread_items'].' WHERE uid='.$uid)
                && $this->query('DELETE FROM '.$this->Tbl['email_whitelist'].' WHERE uid='.$uid)
                && $this->query('DELETE FROM '.$this->Tbl['user_foldersettings'].' WHERE `handler`="email" AND `uid`='.$uid));
    }

    public function get_imapboxes($uid = 0)
    {
        if (false === $uid) {
            return array();
        }
        $return = array();
        $qh = $this->query('SELECT idx id,layered_id, childof, folder_path, friendly_name FROM '
                .$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND att_type="10" AND att_icon=":imapbox"');
        while ($line = $this->assoc($qh)) {
            $return[] = $line;
        }
        return $return;
    }

    /**
     * Get the folder structure that a user sees
     *
     * @param int $uid  UserID to get the folder structure for
     * @return array
     */
    public function get_folder_structure($uid = 0)
    {
        if (false === $uid) {
            return false;
        }
        $uid = intval($uid);
        $return = array();
        $syssorter = 'CASE att_icon WHEN ":inbox" THEN 0 WHEN ":outbox" THEN 1 WHEN ":drafts" THEN 2 WHEN ":templates" THEN 3'
                .' WHEN ":sent" THEN 4 WHEN ":waste" THEN 5 WHEN ":junk" THEN 6 WHEN ":archive" THEN 7 WHEN ":mailbox" THEN 0'
                .' WHEN ":imapbox" THEN 1 ELSE 8 END';
        $qh = $this->query
                ('SELECT idx id,layered_id, childof, folder_path, friendly_name, att_type `type`, att_icon `icon`,'
                .'att_has_folders has_folders, att_has_items has_items, mailnum,mailsize,`unread`,`unseen`,`stale`,`visible`,`secure`'
                .',IF (att_type in(0,10,20), CONCAT('.$syssorter.', "_", IF(att_type IN(0,10,20), 1000+layered_id, friendly_name)), friendly_name) namesorter'
                .' FROM '.$this->Tbl['email_folder'].' WHERE uid IN(0,'.$uid.') ORDER BY childof ASC, namesorter ASC'
                );
        $layered_0 = 0;
        while ($line = $this->assoc($qh)) {
            if ($line['childof'] == 0 && $line['layered_id'] > $layered_0) {
                $layered_0 = $line['layered_id'];
            }
            $return[$line['childof']][$line['id']] = $line;
        }
        if (!$this->features['shares']) {
            return $return;
        }

        $groups = $this->get_usergrouplist($uid, true);
        $gAdd = empty($groups) ? '' : (count($groups) == 1 ? ' OR gid='.intval($groups[0]) : ' OR gid IN('.implode(',', $groups).')');
        $qh = $this->query('SELECT fid FROM '.$this->Tbl['share_folder'].' WHERE h="email" AND (uid='.$uid.$gAdd.') GROUP BY fid');
        if (!$this->numrows($qh)) {
            return $return;
        }

        $return[0]['shareroot'] = array(
                'layered_id' => (++$layered_0), 'childof' => 0,
                'folder_path' => 'shareroot', 'friendly_name' => 'Shared Folders',
                'type' => 2, 'icon' => ':sharedbox' ,'has_folders' => 1, 'has_items' => 0,
                'mailnum' => 0, 'mailsize' => 0, 'unread' => 0, 'unseen' => 0,
                'stale' => 0, 'visible' => 1, 'secure' => 0
                );
        $layered_1 = 0;
        $sortnames = array();
        while ($line = $this->assoc($qh)) { $sortnames[$line['fid']] = $line['fid']; }
        foreach (array_keys($sortnames) as $k) {
            $fi = $this->get_folder_info(null, $k);
            $return['shareroot'][$k] = array
                    ('layered_id' => (++$layered_1), 'childof' => 'shareroot'
                    ,'folder_path' => $fi['folder_path'], 'friendly_name' => $fi['foldername']
                    ,'type' => 2, 'icon' => $fi['icon']
                    ,'has_folders' => $fi['has_folders'], 'has_items' => $fi['has_items']
                    ,'mailnum' => $fi['mailnum'], 'mailsize' => $fi['mailsize']
                    ,'unread' => $fi['unread'], 'unseen' => $fi['unseen']
                    ,'stale' => 0, 'visible' => $fi['visible'], 'secure' => $fi['secure']
                    );
            $sortnames[$k] = $fi['foldername'];
        }
        return $return;
    }

    /**
     * Retrieve the list of unread items for all folders of given user
     * @param int User ID
     * @return array Containing folder IDs as keys, unread items and unseen flag in array as values
     * @since 0.1.4
     */
    public function folders_get_unread($uid)
    {
        $return = array();
        $query = 'SELECT idx,unread,unseen,mailnum,att_icon FROM '.$this->Tbl['email_folder'].' WHERE uid IN(0,'.intval($uid).') ORDER BY idx ASC';
        $qid = $this->query($query);
        while (list ($id, $num, $unseen, $total, $icon) = $this->fetchrow($qid)) {
            $return[$id] = array('unread' => $num, 'unseen' => ($unseen), 'total' => $total, 'icon' => $icon);
        }
        // Include shared folders from other users
        if (!$this->features['shares']) {
            return $return;
        }
        $groups = $this->get_usergrouplist($uid, true);
        $gAdd = empty($groups) ? '' : (count($groups) == 1 ? ' OR sf.gid='.intval($groups[0]) : ' OR sf.gid IN('.implode(',', $groups).')');
        $qh = $this->query('SELECT f.idx,f.unread,f.unseen,f.mailnum,f.att_icon FROM '.$this->Tbl['share_folder'].' sf'
                .','.$this->Tbl['email_folder'].' f WHERE sf.h="email" AND (sf.uid='.$uid.$gAdd.')'
                .' AND f.idx=sf.fid GROUP BY sf.fid');
        if (!$this->numrows($qh)) {
            return $return;
        }
        while ($line = $this->assoc($qh)) {
            $return[$line['idx']] = array('unread' => $line['unread'], 'unseen' => false, 'total' => $line['mailnum'], 'icon' => $line['att_icon']);
        }
        return $return;
    }

    /**
     * Reset unseen flag for all folders of given user
     *
     * @param int $uid
     * @since 4.2.9
     */
    public function folders_set_seen($uid)
    {
        $this->query('UPDATE '.$this->Tbl['email_folder'].' SET unseen="0" WHERE uid IN(0,'.intval($uid).')');
    }

    public function folder_mark_secure($uid, $profile, $secure)
    {
        $this->query('UPDATE '.$this->Tbl['email_folder'].' SET secure="'.($secure == 1 ? 1 : 0).'"'
                .' WHERE uid IN(0,'.intval($uid).') AND folder_path="'.intval($profile).':"');
    }

    public function get_folder_info($uid = 0, $folder = 0)
    {
        if (false === $folder) {
            return false;
        }
        if (!$this->check_perm_folder($uid, $folder)) {
            return false;
        }
        $query = 'SELECT friendly_name foldername, uid, uuid, childof, folder_path, att_type `type`, att_icon icon'
                .',att_has_folders has_folders, att_has_items has_items'
                .',mailnum, mailsize,`unread`,`unseen`,`visible`,`secure`'
                .' FROM '.$this->Tbl['email_folder'].' WHERE idx='.intval($folder);
        $return = $this->assoc($this->query($query));
        if (empty($return) || false === $return) {
            return false;
        }
        $fSet = new DB_Controller_Foldersetting();
        $return['settings'] = $fSet->foldersetting_get('email', $folder, $uid);
        return $return;
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
    public function folder_exists($uid = 0, $folder = '', $childof = false)
    {
        $query = 'SELECT idx FROM '.$this->Tbl['email_folder'].' WHERE uid IN(0,'.intval($uid).') AND friendly_name="'.$this->esc($folder).'"';
        if ($childof) $query .= ' AND childof='.intval($childof);
        list ($return) = $this->fetchrow($this->query($query));
        return ($return) ? $return : false;
    }

    /**
     * Try to find the ID of a folder by specifying its path within the docroot
     *
     * @param  int  ID of the affected user, 0 for global folders
     * @param  string  folder path to look for
     *[@param  bool  Check for roles and return these (like WASTE, ...); default: false]
     * @return  int  ID of the folder, if found, FALSE otherwise
     * @since 0.0.9
     */
    public function get_folder_id_from_path($uid = 0, $path = '', $roles = false, $allOfThem = false)
    {
        $query = (!$roles)
                ? 'SELECT idx FROM '.$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND folder_path="'.$this->esc($path).'"'
                : 'SELECT idx FROM '.$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND att_icon="'.$this->esc(':'.$path).'"';
        if (false !== $allOfThem) {
            $return = array();
            $qid = $this->query($query);
            while ($line = $this->assoc($qid)) {
                $return[] = $line['idx'];
            }
            return $return;
        } else {
            list ($return) = $this->fetchrow($this->query($query));
            return ($return) ? $return : false;
        }
    }

    /**
     * Returns ID or list of IDs for a certain type of system folder. Valid types at the moment
     * are: waste, sent, junk, drafts, templates, inbox, imapbox and mailbox. If the optional profile is given,
     * then only the relevant system folder for that IMAP profile is returned. To query the local
     * system folder, specify profile 0.
     *
     * @param  int  ID of the affected user
     * @param string $type  Type of the folder, see description
     *[@param int $profile  Id of the IMAP profile, 0 for a local folder]
     * @return array  An array consisting of arrays, which hold folder ID, folder path and profile ID
     * @since 0.6.1
     */
    public function get_system_folder($uid = 0, $type, $profile = false)
    {
        if (false === $profile) {
            $return = array();
            $res = $this->query('SELECT idx, folder_path FROM '.$this->Tbl['email_folder'].' where uid IN(0,'.intval($uid).')'
                    .' AND att_icon="'.$this->esc(':'.$type).'"');
            while (list($idx, $path) = $this->fetchrow($res)) {
                if (preg_match('!^(\d+)\:.*$!', $path, $found)) { // Matches syntax for IMAP folder
                    $return[] = array('idx' => $idx, 'path' => $path, 'profile' => $found[1]);
                } else {
                    $return[] = array('idx' => $idx, 'path' => $path, 'profile' => 0);
                }
            }
            return $return;
        } elseif (0 != $profile) {
            $res = $this->query('SELECT idx, folder_path FROM '.$this->Tbl['email_folder'].' where uid IN(0,'.intval($uid).')'
                    .' AND att_icon="'.$this->esc(':'.$type).'"'
                    .' AND folder_path LIKE "'.intval($profile).':%"');
            list($idx, $path) = $this->fetchrow($res);
            return $idx;
        } else {
            $res = $this->query('SELECT idx, folder_path FROM '.$this->Tbl['email_folder'].' where uid='.intval($uid)
                    .' AND att_icon="'.$this->esc(':'.$type).'"');
            while (list($idx, $path) = $this->fetchrow($res)) {
                if (preg_match('!^(\d+)\:.*$!', $path)) continue; // Matches syntax for IMAP folder
                return $idx;
            }
        }
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
        if (!isset($pass['filter']))      $pass['filter'] = '';
        if (!isset($pass['has_folders'])) $pass['has_folders'] = true;
        if (!isset($pass['has_items']))   $pass['has_items'] = true;
        if (!isset($pass['visible']))     $pass['visible'] = 1;
        // These settings are useful for IMAP only
        if (!isset($pass['mailnum']))     $pass['mailnum'] = 0;
        if (!isset($pass['mailsize']))    $pass['mailsize'] = 0;
        if (!isset($pass['unread']))      $pass['unread'] = 0;
        if (!isset($pass['unseen']))      $pass['unseen'] = 0;
        if (!isset($pass['srv_unseen']))  $pass['srv_unseen'] = 0;
        if (!isset($pass['uidnext']))     $pass['uidnext'] = 0;
        if (!isset($pass['uidvalidity'])) $pass['uidvalidity'] = 0;

        // A bit lousy, should better be done by subquerying...
        $qid = $this->query('SELECT max(layered_id) FROM '.$this->Tbl['email_folder'].' WHERE childof='.intval($pass['childof']));
        list ($max_layered) = $this->fetchrow($qid);
        $query = 'INSERT INTO '.$this->Tbl['email_folder'].' SET uid='.intval($pass['uid'])
                .',`uuid`="'.basics::uuid().'"'
                .',friendly_name="'.$this->esc($pass['friendly_name']).'"'
                .',folder_path="'.$this->esc($pass['folder_path']).'"'
                .',childof='.intval($pass['childof'])
                .',layered_id='.($max_layered+1)
                .',att_type="'.$this->esc($pass['type']).'"'
                .',att_icon="'.$this->esc($pass['icon']).'"'
                .',att_has_folders="'.($pass['has_folders'] ? 1 : 0).'"'
                .',att_has_items="'.($pass['has_items'] ? 1 : 0).'"'
                .',mailnum='.intval($pass['mailnum'])
                .',mailsize='.intval($pass['mailsize'])
                .',unread='.intval($pass['unread'])
                .',unseen="'.intval($pass['unseen']).'"'
                .',srv_unseen='.intval($pass['srv_unseen'])
                .',uidnext='.intval($pass['uidnext'])
                .',uidvalidity='.intval($pass['uidvalidity'])
                .',visible="'.($pass['visible'] ? 1 : 0).'"';
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
                ,array('pass' => 'type', 'key' => 'att_type', 'quote' => true)
                ,array('pass' => 'icon', 'key' => 'att_icon', 'quote' => true)
                ,array('pass' => 'has_folders', 'key' => 'att_has_folders', 'quote' => true)
                ,array('pass' => 'has_items', 'key' => 'att_has_items', 'quote' => true)
                ,array('pass' => 'mailnum', 'key' => 'mailnum', 'quote' => false)
                ,array('pass' => 'mailsize', 'key' => 'mailsize', 'quote' => false)
                ,array('pass' => 'unread', 'key' => 'unread', 'quote' => true)
                ,array('pass' => 'unseen', 'key' => 'unseen', 'quote' => true)
                ,array('pass' => 'stale', 'key' => 'stale', 'quote' => true)
                ,array('pass' => 'visible', 'key' => 'visible', 'quote' => true)
                ,array('pass' => 'srv_unseen', 'key' => 'srv_unseen', 'quote' => false)
                ,array('pass' => 'uidnext', 'key' => 'uidnext', 'quote' => false)
                ,array('pass' => 'uidvalidity', 'key' => 'uidvalidity', 'quote' => false)
                ) as $v) {
            if (!isset($pass[$v['pass']])) continue;
            $out[] = $v['key'].'='.($v['quote'] ? '"'.$this->esc($pass[$v['pass']]).'"' : intval($pass[$v['pass']]));
        }
        $query = implode(',', $out);
        unset($out);
        // If nothing found to update, return as false
        if (!$query) return false;
        return $this->query('UPDATE '.$this->Tbl['email_folder'].' SET `uuid`="'.basics::uuid().'",'.$query
                .' WHERE idx='.intval($pass['id']).' AND uid IN(0,'.intval($pass['uid']).')');
    }

    /**
     * Sets visibility status of a given folder id
     *
     * @param int $uid
     * @param int $id
     * @param bool $visible
     * @return bool
     * @since 1.0.2
     */
    public function hide_folder($uid, $id, $visible)
    {
        return $this->query('UPDATE '.$this->Tbl['email_folder'].' SET visible="'.($visible ? 1 : 0).'" WHERE idx='.intval($id).' AND uid='.intval($uid));
    }

    /**
     * Delete index information about a given folder
     *
     * @param int $uid  User ID of the given folder
     * @param int $id   Unique ID of the folder to remove
     * [@param bool $with_childs  Whether to also delete the information about any subfolder - if set to
     *                           TRUE, subfolder information will also be removed; Default: FALSE]
     * [@param bool $with_mails  Whether to also delete the mails in this folder from the index database;
     *                           if set to TRUE, mail information will also be removed; Default: TRUE]
     * @return bool  TRUE on succes, FALSE on failure
     */
    public function remove_folder($uid, $id, $with_childs = false, $with_mails = true)
    {
        $affected = false;
        if ($with_childs) {
            $qid = $this->query('SELECT idx FROM '.$this->Tbl['email_folder'].' WHERE childof='.intval($id).' AND uid='.intval($uid));
            while (list($child) = $this->fetchrow($qid)) {
                $affected = $this->remove_folder($uid, $child, true);
                if (!$affected) return false;
            }
        }
        if ($with_mails) $this->mail_delete($uid, false, $id);
        return ($this->query('DELETE FROM '.$this->Tbl['email_folder'].' WHERE idx='.intval($id).' AND uid='.intval($uid))
                && $this->query('DELETE FROM '.$this->Tbl['user_foldersettings'].' WHERE `handler`="email" AND `fid`='.intval($id)));
    }

    /**
     * Resyncs index fields with real amount of messages, unread and unseen states
     *
     * @param int $uid  User ID of the given folder
     * @param int $id   Unique ID of the folder to resync
     * @return bool  TRUE on succes, FALSE on failure
     * @since 0.3.9
     */
    public function resync_folder($uid, $id)
    {
        if (!$this->check_perm_folder($uid, $id)) return false;
    	$query = 'SELECT sum(hsize), count(*), sum(if(`read`="0",1,0)) FROM '.$this->Tbl['email_index'].' WHERE folder_id='.intval($id);
        list ($size, $mailnum, $unread) = $this->fetchrow($this->query($query));
    	$query = 'UPDATE '.$this->Tbl['email_folder'].' SET `uuid`="'.basics::uuid().'",`mailsize`='.($size+0).',`mailnum`='.($mailnum+0)
    	       .',`unread`='.($unread+0).($unread == 0 ? ',`unseen`="0"' : '')
    	       .' WHERE idx='.intval($id);
        return $this->query($query);
    }

    /**
     * Marks all mails in a folder as "seen". This does not affect the (un)read flag.
     *
     * @param integer $id  folder id
     * @return true (Does not care about errors right now)
     * @since 4.1.5
     */
    public function folder_setseen($uid, $id)
    {
        $id = intval($id);
        $uid = intval($uid);
        if (!$this->check_perm_folder($uid, $id, 'w')) return false;
        $this->query('UPDATE '.$this->Tbl['email_folder'].' SET `uuid`="'.basics::uuid().'",unseen="0" WHERE idx='.$id);
        $this->query('UPDATE '.$this->Tbl['email_index'].' SET seen="1" WHERE folder_id='.$id);
        return true;
    }

    /**
     * Save the settings for an individual folder (preview, which fields to show ...)
     *
     * @param int $uid  User ID of the given folder
     * @param int $id   Unique ID of the folder to save the settings for
     * @param string Serialized folder settings, take care for updates by yourself please
     * @since 0.4.3
     */
    public function set_folder_settings($uid, $id, $settings)
    {
        if (!$this->check_perm_folder($uid, $id, 'r')) return false;
        // Delete everything since this saves us from iterating over both the current DB and the passed argument
        $fSet = new DB_Controller_Foldersetting();
        $fSet->foldersetting_del('email', $id, $uid);
        // Push into DB
        return $fSet->foldersetting_set('email', $id, $uid, $settings);
    }

    /**
     * Retrieves a list of all UIDLs of a specific folder, mainly interesting for IMAP, when passing
     * $retfield = 'uidl' also for checking mails in the file system for their existance in the DB index
     *
     * @param int $uid  User ID for the given folder
     * @param int $id   Unique ID of the folder
     *[@param string $orderby  Order by this DB field]
     *[@param ASC|DESC $orderdir Order direction]
     * @param string|array  Return this DB field, by deafult "ouidl" is used, optionally an array of diels can be passed
     * @return array
     * @since 0.5.0
     */
    public function get_folder_uidllist($uid, $id, $orderby = false, $orderdir = false, $retfield = 'ouidl')
    {
        $return = array();
        $orderadd = '';
        $id = intval($id);
        $uid = intval($uid);
        if (!$this->check_perm_folder($uid, $id)) {
            return false;
        }
        if (false !== $orderby) {
            $orderadd = ' ORDER BY `'.$this->esc($orderby).'` '.('ASC' == $orderdir ? 'ASC' : 'DESC');
        }
        // Automatically drop doublettes
        $qid = $this->query('SELECT idx,profile,ouidl, count(*) zahl FROM '.$this->Tbl['email_index'].' WHERE folder_id='.$id
                .' GROUP BY CONCAT(profile, ".", ouidl) HAVING zahl > 1');
        if ($this->numrows($qid)) {
            while (list($idx, $profile, $uidl) = $this->fetchrow($qid)) {
                if (!$profile || !$uidl) {
                    continue; // Prevent killing entries without profile or uidl data contained
                }
                $this->query('DELETE FROM '.$this->Tbl['email_index'].' WHERE CONCAT(profile,".",ouidl)="'.$profile.'.'.$uidl.'" AND idx!='.$idx);
            }
        }
        // Finally fetch the list
        if (is_array($retfield)) {
            foreach ($retfield as $k => $v) {
                $retfield[$k] = '`'.$this->esc($v).'`';
            }
            $retfield = implode(',', $retfield);
            $retmode = 1;
        } else {
            $retfield = $this->esc($retfield);
            $retmode = 0;
        }
        $qid = $this->query('SELECT idx, '.$retfield.' FROM '.$this->Tbl['email_index'].' WHERE folder_id='.$id.$orderadd);
        if (!$this->numrows($qid)) {
            return $return;
        }
        if (0 == $retmode) {
            while (list($idx, $ret) = $this->fetchrow($qid)) {
                $return[$idx] = $ret;
            }
        } else {
            while ($res = $this->assoc($qid)) {
                $idx = $res['idx'];
                unset($res['idx']);
                $return[$idx] = $res;
            }
        }
        return $return;
    }

    /**
     * Returns all folders belonging to a speficif IMAP profile
     *
     * @param int $uid  User ID to perform the query under
     * @param int $profile ID of the profile to get the folder list for
     * @return array
     * @since 0.5.2
     */
    public function get_imapkids($uid, $profile, $extended = false)
    {
    	$sqladd = ($extended) ? ',`mailnum`,`unread`,`srv_unseen`,`uidnext`,`uidvalidity`' : '';
        $return = array();
        $qid = $this->query('SELECT idx, folder_path'.$sqladd
        		.' FROM '.$this->Tbl['email_folder']
                .' WHERE uid IN(0,'.intval($uid).')'
                .' AND folder_path LIKE "'.intval($profile).':%"'
                .' ORDER by folder_path ASC');
        if ($extended) {
        	while ($line = $this->assoc($qid)) {
        		$return[$line['idx']] = $line;
        	}
        } else {
        	while (list($idx, $path) = $this->fetchrow($qid)) {
        		$return[$idx] = $path;
        	}
        }
        return $return;
    }

    /**
     * Retrieve the mail index of a given folder and return as array
     *
     * @param int  ID of the affected user, give 0 for global folders
     * @param int|array  ID(s) of the folder(s)
     *[@param mixed  Skimming option, pass a FALSE value for no skimming, nonngeative integer for an offset]
     *[@param mixed  Skimming option, pass a FALSE value for no skimming, nonngeative integer for a pagesize]
     *[@param string  name of the DB field for ordering by; Default: hdate_sent]
     *[@param 'ASC'|'DESC'  Direction to order; Default: ASC]
     *[@param int  ID of a mail for getting only this mail's information, omit everything else, pass folder ID 0 then]
     *[@param string  Search criteria to match mails against, also pass pattern then]
     *[@param string  Search pattern to match mails against, also pass criteria]
     */
    public function get_mail_list($uid = 0, $folderList = 0, $offset = false, $pagesize = false,
            $ordby = false, $orddir = 'ASC', $idx = false,
            $criteria = false, $pattern = false, $flags = null)
    {
        if (false === $uid) {
            return false;
        }
        if (false === $folderList && false === $idx) {
            return false;
        }
        if ($folderList !== false && !empty($folderList)) {
            if (!is_array($folderList)) {
                $folderList = array($folderList);
            }
            foreach ($folderList as $k => $fid) {
               if (!$this->check_perm_folder($uid, $fid)) {
                   unset($folderList[$k]);
               }
               $folderList[$k] = (int) $fid;
            }
            if (empty($folderList)) {
                return false;
            }
        }

        $return = array();
        $q_r = '';
        $valiCrit = array('from' => 'hfrom', 'to' => array('hto', 'hcc', 'hbcc')
                ,'cc' => 'hcc', 'bcc' => 'hbcc', 'subject' => 'hsubject', 'ouidl' => 'ouidl'
                ,'allheaders' => array('hfrom', 'hto', 'hcc', 'hbcc', 'hsubject')
                );
        if (!$ordby) {
            $ordby = 'i.`hdate_sent`';
        } elseif ($ordby == 'status') {
            $ordby = 'i.`read`';
        } else {
            $ordby = '`'.$this->esc($ordby).'`';
        }

        // Limit result set to mails matching search criteria and search pattern
        if ($criteria !== false && $pattern !== false && isset($valiCrit[$criteria])) {
            if (is_array($valiCrit[$criteria])) {
                $searches = array();
                $pattern = $this->esc($pattern);
                foreach ($valiCrit[$criteria] as $crit) {
                    $searches[] = $crit.' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
                }
                $q_r .= ' AND ('.implode(' OR ', $searches).')';
            } elseif (is_array($pattern) && count($pattern)) {
                foreach ($pattern as $k => $v) {
                    $pattern[$k] = '"'.$this->esc($v).'"';
                }
                $q_r .= ' AND '.$valiCrit[$criteria].' IN('.implode(',', $pattern).')';
            } else {
                $pattern = $this->esc($pattern);
                $q_r .= ' AND '.$valiCrit[$criteria].' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
            }
        } elseif ($criteria == '@@thread@@') { // Use with care!
            $q_r .= ' AND `thread_id`='.floatval($pattern);
            if (isset($GLOBALS['ignore'])) {
                $q_r .= ' AND i.idx!='.intval($GLOBALS['ignore']);
            }
            $folderList = array();
            $offset = $pagesize = 0;
        } elseif ($criteria == '@@internal@@') { // Use with care!
            $q_r .= $pattern;
        } elseif (!empty($GLOBALS['_PM_']['fulltextsearch']['enabled']) // Fulltext search available?
                && $pattern !== false) {
            $pattern = $this->esc($pattern);
            $q_r .= ' AND MATCH(hfrom,hcc,hsubject,hto,search_body) AGAINST("'.$pattern.'" IN NATURAL LANGUAGE MODE)';
        }
        if (!is_null($flags) && !empty($flags)) {
            foreach (array('unread' => '`read`="0"', 'forwarded' => '`forwarded`="1"', 'answered' => '`answered`="1"'
                    ,'bounced' => '`bounced`="1"', 'attachments' => '`attachments`="1"'
                    ,'coloured' => '(`colour` IS NOT NULL AND `colour`!="")') as $k => $v) {
                if (isset($flags[$k])) {
                    $q_r .= ' AND '.$v;
                }
            }
        }

        // Either select data for a single mail or optionally ordered, skimmable data from a folder
        if ($idx) {
            $q_r .= ' AND i.idx='.intval($idx);
        } else {
            if (!empty($folderList)) {
                $q_r .= ' AND i.folder_id IN('.implode(',', $folderList).')';
            } else {
                $q_r .= ' AND i.`uid` IN(0,'.intval($uid).')';
            }
            $q_r .= ' ORDER BY '.$ordby.' '.($orddir == 'ASC' ? 'ASC' : 'DESC');
        }
        $query = 'SELECT i.idx, uidl, folder_id, hfrom, hto, hcc, hbcc, hsubject, hdate_recv, hdate_sent, hsize, hpriority'
                .',`attachments`,`read`,`answered`,`forwarded`,`bounced`,`type`,`ouidl`,`profile`,`dsn_sent`'
                .',`cached`, if(`colour` IS NULL, "", `colour`) `colour`,`htmlunblocked`,ti.`thread_id`, t.`known_mails`'
                .' FROM '.$this->Tbl['email_index'].' i'
                .' LEFT JOIN '.$this->Tbl['email_thread_items'].' ti ON ti.`mail_id`=i.`idx` AND ti.`uid`=i.`uid`'
                .' LEFT JOIN '.$this->Tbl['email_thread'].' t ON ti.`thread_id`=t.`idx`'
                .' WHERE 1'.$q_r;
        if ($offset && $idx === false) {
            $query .= ' LIMIT '.(($pagesize) ? intval($offset).', '.intval($pagesize) : intval($offset));
        } elseif ($idx === false && $pagesize) {
            $query .= ' LIMIT '.intval($pagesize);
        }
        $qh = $this->query($query);
        $mailcounter = ($offset) ? $offset : 0;
        while ($line = $this->assoc($qh)) {
            $return[$mailcounter] = array
                    ('id' => $line['idx']
                    ,'uidl' => $line['uidl']
                    ,'folder_id' => $line['folder_id']
                    ,'from' => $line['hfrom']
                    ,'to' => $line['hto']
                    ,'cc' => $line['hcc']
                    ,'bcc' => $line['hbcc']
                    ,'subject' => $line['hsubject']
                    ,'date_received' => $line['hdate_recv']
                    ,'date_sent' => $line['hdate_sent']
                    ,'size' => $line['hsize']
                    ,'priority' => $line['hpriority']
                    ,'attachments' => $line['attachments'] == 1 ? 1 : 0
                    ,'status' => $line['read'] == 1 ? 1 : 0
                    ,'answered' => $line['answered'] == 1 ? 1 : 0
                    ,'forwarded' => $line['forwarded'] == 1 ? 1 : 0
                    ,'bounced' => $line['bounced'] == 1 ? 1 : 0
                    ,'type' => $line['type']
                    ,'ouidl' => $line['ouidl']
                    ,'profile' => $line['profile']
                    ,'dsn_sent' => $line['dsn_sent']
                    ,'cached' => $line['cached']
                    ,'colour' => $line['colour']
                    ,'htmlunblocked' => $line['htmlunblocked']
                    ,'thread_id' => ($line['known_mails']>1) ? $line['thread_id'] : null
                    );
            ++$mailcounter;
        }
        return $return;
    }

    /**
     * This method emulates the search for certain mails (in fact: it searches for all the mails) but does not impose the page size
     * and offset parameters of get_mail_list(). For the meanings of the parameters see get_mail_list() above
     *
     * @param unknown_type $uid
     * @param unknown_type $folder
     * @param unknown_type $idx
     * @param unknown_type $criteria
     * @param unknown_type $pattern
     */
    public function mail_aggregate_search($uid = 0, $folder = 0, $idx = false, $criteria = false, $pattern = false, $flags = null)
    {
        if (!$this->check_perm_folder($uid, $folder)) {
            return false;
        }
        $q_r = '';
        $valid_criteria = array('from' => 'hfrom', 'to' => array('hto', 'hcc', 'hbcc')
                ,'cc' => 'hcc', 'bcc' => 'hbcc', 'subject' => 'hsubject', 'ouidl' => 'ouidl'
                ,'allheaders' => array('hfrom', 'hto', 'hcc', 'hbcc', 'hsubject')
                );
        // Limit result set to mails matching search criteria and search pattern
        if ($criteria !== false && isset($valid_criteria[$criteria])
                 && $pattern !== false) {
            $pattern = $this->esc($pattern);
            if (is_array($valid_criteria[$criteria])) {
                $searches = array();
                foreach ($valid_criteria[$criteria] as $crit) {
                    $searches[] = $crit.' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
                }
                $q_r .= ' AND ('.implode(' OR ', $searches).')';
            } else {
                $q_r .= ' AND '.$valid_criteria[$criteria].' LIKE "%'.str_replace('%', '\%', str_replace('_', '\_', $pattern)).'%"';
            }
        } elseif ($criteria == '@@thread@@') { // Use with care!
            $q_r .= ' AND `thread_id`='.floatval($pattern);
            $folder = 0;
        } elseif (!empty($GLOBALS['_PM_']['fulltextsearch']['enabled'])
                && !empty($pattern)) {
            // Fulltext search available?
            $pattern = $this->esc($pattern);
            $q_r .= ' AND MATCH(hfrom,hcc,hsubject,hto,search_body) AGAINST("'.$pattern.'" IN NATURAL LANGUAGE MODE)';
        }

        if (!is_null($flags) && !empty($flags)) {
            foreach (array('unread' => '`read`="0"', 'forwarded' => '`forwarded`="1"', 'answered' => '`answered`="1"'
                    ,'bounced' => '`bounced`="1"', 'attachments' => '`attachments`="1"'
                    ,'coloured' => '(`colour` IS NOT NULL AND `colour`!="")') as $k => $v) {
                if (isset($flags[$k])) {
                    $q_r .= ' AND '.$v;
                }
            }
        }
        if ($idx) {
            $q_r .= ' AND i.idx='.intval($idx);
        } elseif ($folder) {
            $q_r .= ' AND i.folder_id='.intval($folder);
        } else {
            $q_r .= ' AND i.`uid` IN(0,'.intval($uid).')';
        }
        $query = 'SELECT COUNT(*) `mails`, SUM(i.hsize) `size` FROM '.$this->Tbl['email_index'].' i'
                .' LEFT JOIN '.$this->Tbl['email_thread_items'].' ti ON ti.`mail_id`=i.`idx` AND ti.`uid`=i.`uid`'
                .' WHERE 1'.$q_r;
        $qh = $this->query($query);
        return $this->assoc($qh);
    }

    /**
     * Retrieves a list of the latest received mails for the pinboard view
     * @param int $uid  ID of the user
     * @return array @see get_mail_list()
     * @since 4.3.4
     */
    public function mail_pinboard_digest($uid)
    {
        $fSet = new DB_Controller_Foldersetting();
        $folders = $fSet->foldersettings_find('email', $uid, 'not_in_pinboard');
        $sqlAdd = (!empty($folders)) ? ' AND folder_id NOT IN('.implode(',', $folders).')' : '';
        return $this->get_mail_list($uid, 0, 0, 10, 'hdate_sent', 'DESC', false, '@@internal@@', $sqlAdd);
    }

    public function get_archivable_items($uid, $fid, $age)
    {
        if (!preg_match('!^(\d+)\s([a-z]+)$!i', $age)) {
            return array();
        }
        $sqlAdd = ' AND DATE_FORMAT(DATE_ADD(`hdate_sent`, INTERVAL '.$age.'), "%Y%m%d") < DATE_FORMAT(NOW(), "%Y%m%d")';
        return $this->get_mail_list($uid, $fid, null, null, 'hdate_sent', 'DESC', false, '@@internal@@', $sqlAdd);
    }

    /**
     * Allows to check for the type of a given mail
     *
     * @param int|null $uid  User id who checks (might be null for global queries
     * @param int $mail  IDX of the mail
     * @return string  One of 'mail','sms','ems','mms','fax','appointment','away','receipt','sysmail'
     * @since 4.4.7
     */
    public function mail_get_type($uid = null, $mail)
    {
        $maylook = false;
        $qid = $this->query('SELECT `folder_id`,`type` FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail));
        list ($fid, $type) = $this->fetchrow($qid);
        if ($fid) {
            $maylook = $this->check_perm_folder($uid, $fid, 'r');
        }
        return ($maylook) ? $type : false;
    }

    /**
     * Find the previous and next mails in list relative to given mail ID.
     * Right now there's no other sorting available than by date, DESC.
     *
     * @param int $id
     * @return array Possible keys: 'prev', 'next': both may be unset.
     */
    public function mail_prevnext($uid, $id)
    {
        $uid = null; // will be used in conjunction with shares later
        $qid = $this->query('SELECT'
                .' (SELECT i2.idx FROM '.$this->Tbl['email_index'].' i2 WHERE i2.folder_id=i1.folder_id AND i2.`hdate_sent`<=i1.`hdate_sent` AND i2.`hdate_recv`!=i1.`hdate_recv` AND i2.idx!=i1.idx ORDER by i2.hdate_sent DESC LIMIT 1) prev'
                .' ,(SELECT i3.idx FROM '.$this->Tbl['email_index'].' i3 WHERE i3.folder_id=i1.folder_id AND i3.`hdate_sent`>=i1.`hdate_sent` AND i3.`hdate_recv`!=i1.`hdate_recv` AND i3.idx!=i1.idx ORDER by i3.hdate_sent ASC LIMIT 1) next'
                .' FROM '.$this->Tbl['email_index'].' i1'
                .' WHERE i1.idx='.intval($id));
        if (false !== $qid && $this->numrows($qid)) {
            return $this->assoc($qid);
        }
        return array();
    }

    public function mail_set_status($uid = 0, $mail, $rd = null, $aw = null, $fw = null, $bn = null)
    {
        if (is_null($rd) && is_null($aw) && is_null($fw) && is_null($bn)) {
            return true;
        }
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder, 'w')) {
            return false;
        }
        $this->query('UPDATE '.$this->Tbl['email_index'].' SET uid=uid'
                .',`read`='.(is_null($rd) ? '`read`' : ($rd ? '"1"' : '"0"'))
                .',answered='.(is_null($aw) ? 'answered' : ($aw ? '"1"' : '"0"'))
                .',forwarded='.(is_null($fw) ? 'forwarded' : ($fw ? '"1"' : '"0"'))
                .',bounced='.(is_null($bn) ? 'bounced' : ($bn ? '"1"' : '"0"'))
                .' WHERE idx='.intval($mail));
        return $this->resync_folder($uid, $folder);
    }

    public function mail_set_dsnsent($uid = 0, $mail = 0, $status)
    {
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder, 'w')) return false;
        return $this->query('UPDATE '.$this->Tbl['email_index'].' SET dsn_sent="'.(($status) ? 1 : 0).'" WHERE idx='.intval($mail));
    }

    public function mail_set_htmlunblocked($uid = 0, $mail = 0, $status)
    {
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder, 'w')) return false;
        return $this->query('UPDATE '.$this->Tbl['email_index'].' SET htmlunblocked="'.(($status) ? 1 : 0).'" WHERE idx='.intval($mail));
    }

    public function mail_set_colour($uid = 0, $mail = 0, $colour)
    {
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder, 'w')) return false;
        return $this->query('UPDATE '.$this->Tbl['email_index']
                .' SET colour='.(($colour !== false) ? '"'.$this->esc($colour).'"' : 'NULL').' WHERE idx='.intval($mail));
    }

    public function mail_get_real_location($uid = 0, $mail = 0)
    {
        $uid = null; // will be used in conjunction with shares later
        $return = false;
        $qid = $this->query('SELECT f.uid, f.folder_path, m.uidl FROM '.$this->Tbl['email_index'].' m'
                .', '.$this->Tbl['email_folder'].' f WHERE m.idx='.intval($mail).' AND m.folder_id=f.idx');
        $return = $this->fetchrow($qid);
        return $return;
    }

    /**
     * Returns the mail structure from index
     *
     * @param  int  $uid  The user ID to perform the operation for
     * @param  int  $mail  The mail to get the structure of
     * @return string  serialized mail structure
     * @since 0.1.6
     */
    public function mail_get_structure($uid = 0, $mail = 0)
    {
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder)) return false;
        $query = 'SELECT struct FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail);
        list ($struct) = $this->fetchrow($this->query($query));
        return $struct;
    }

    /**
     * Set the mail structure in index
     *
     * @param  int  $uid  The user ID to perform the operation for
     * @param  int  $mail  The mail to set the structure of
     * @param string  serialized mail structure
     * @since 0.5.5
     */
    public function mail_set_structure($uid = 0, $mail = 0, $struct)
    {
        list ($folder) = $this->fetchrow($this->query('SELECT folder_id FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail)));
        if (!$this->check_perm_folder($uid, $folder)) return false;
        return $this->query('UPDATE '.$this->Tbl['email_index'].' set struct="'.$this->esc($struct).'" WHERE idx='.intval($mail));
    }

    /**
     * Delete either a single mail or all mails within a given folder from index
     *
     * @param  int  $uid  The user ID to perform the operation for
     * @param  int  $mail  Optionally the mail to delete; if FALSE, one MUST specify the affected folder
     * @param  int  $folder  Optionally the folder to delete all mails for; if FALSE, one MUST specify the mail ID
     * @return  bool  TRUE on success, FALSE on failure
     * @since 0.0.7
     */
    public function mail_delete($uid = 0, $mail = false, $folder = false, $ouidl = false)
    {
        if ($mail === false && $folder === false && $ouidl === false) {
            $this->set_error('Please either specify the mail, its original UIDL or the folder, where all mails should be killed');
            return false;
        }
        if ($mail !== false) {
            $mail = floatval($mail);

            $query = 'SELECT folder_id,hsize,`read`,`ouidl`,`profile` FROM '.$this->Tbl['email_index'].' WHERE idx='.$mail;
            list ($fid, $size, $is_read, $suidl, $sprofile) = $this->fetchrow($this->query($query));
            if (!$this->check_perm_folder($uid, $fid, 'w')) {
                return false;
            }

            $query = 'DELETE FROM '.$this->Tbl['email_index'].' WHERE idx='.$mail;
            if ($this->query($query)) {
                // Unregister from thread index
                $this->query('UPDATE '.$this->Tbl['email_thread'].' t,'.$this->Tbl['email_thread_items'].' ti'
                        .' SET t.known_mails=IF(t.known_mails=0, 0, t.known_mails-1)'
                        .' WHERE ti.mail_id='.$mail.' AND ti.thread_id=t.idx');
                $this->query('DELETE FROM '.$this->Tbl['email_thread_items'].' WHERE mail_id='.$mail);
                // Update folder information
                $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=IF(CAST(mailnum AS SIGNED)-1 < 1, 0, mailnum-1)'
                        .', mailsize=IF(CAST(mailsize AS SIGNED)-'.($size+0).' < 1, 0, mailsize-'.($size+0).')'
                        .', unread=IF(unread=0, 0, unread-'.(1-$is_read).')'
                        .', unseen=IF(unread=0, "0", unseen) WHERE idx='.$fid;
                $this->query($query);
                if ($suidl && $sprofile) {
                    $this->uidlcache_markdeleted($sprofile, $suidl);
                }
                return true;
            }
            return false;
        } elseif ($ouidl !== false) {
            $query = 'SELECT idx,hsize,`read`,ouidl,profile FROM '.$this->Tbl['email_index']
                    .' WHERE folder_id='.intval($folder).' AND ouidl="'.$this->esc($ouidl).'"';
            list ($mail, $size, $is_read, $suidl, $sprofile) = $this->fetchrow($this->query($query));
            if (!$this->check_perm_folder($uid, $folder, 'w')) {
                return false;
            }
            if (!$mail) {
                return false;
            }

            $query = 'DELETE FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail);
            if ($this->query($query)) {
                list ($mailnum) = $this->fetchrow($this->query('SELECT mailnum FROM '.$this->Tbl['email_folder'].' WHERE idx='.intval($folder)));
                if ($mailnum < 2) {
                    $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=0,mailsize=0,unread=0,unseen="0" WHERE idx='.intval($folder);
                } else {
                    $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=IF(CAST(mailnum AS SIGNED)-1 < 1, 0, mailnum-1)'
                            .', mailsize=IF(CAST(mailsize AS SIGNED)-'.($size+0).' < 1, 0, mailsize-'.($size+0).')'
                            .', unread=IF(unread=0, 0, unread-'.(1-$is_read).')'
                            .', unseen=IF(unread=0, "0", unseen) WHERE idx='.intval($folder);
                }
                $this->query($query);
                if ($suidl && $sprofile) {
                    $this->uidlcache_markdeleted($sprofile, $suidl);
                }
                return true;
            }
            return false;
        } elseif ($folder !== false) {
            if (!$this->check_perm_folder($uid, $folder, 'w')) {
                return false;
            }
            $qid = $this->query('SELECT profile, ouidl FROM '.$this->Tbl['email_index'].' WHERE folder_id='.intval($folder));
            while (list ($sprofile, $suidl) = $this->fetchrow($qid)) {
                if (!$sprofile || !$suidl) continue;
                $this->uidlcache_markdeleted($sprofile, $suidl);
            }
            $query = 'DELETE FROM '.$this->Tbl['email_index'].' WHERE folder_id='.intval($folder);
            if ($this->query($query)) {
                $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=0,mailsize=0,unread=0,unseen="0" WHERE idx='.intval($folder);
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
     * Move a mail from one folder to another. As of phlyMail 4 the owner id is
     * changed according to the owner id of the folder the mail now belongs to.
     * Additionally the uid is no longer checked!
     *
     * @param  int  $uid  The user ID to perform the operation for (actually ignored)
     * @param  int  $mail  Mail ID to move
     * @param  int  $folder  The destination folder to move the mail to
     * @param  string  $newname  In case the file name changed, pass it here, Default: null
     * @param  bool $cached Whether this mail is cached locally (POP3) or not (IMAP)
     * @param  string $newouidl  In case the UIDL of an IMAP mail changes, pass it here, Default: null
     * @return  bool  TRUE on success, FALSE on failure
     * @since 0.0.8
     */
    public function mail_move($uid = 0, $mail = false, $folder = false, $newname = null, $cached = true, $newouidl = null)
    {
        if ($mail === false || $folder === false) {
            $this->set_error('Please specify the mail to move and its target folder');
            return false;
        }
        $mail = intval($mail);
        $folder = intval($folder);
        // Get current folder and mailsize for updating the meta data of old and new folder
        $query = 'SELECT m.folder_id,m.hsize,m.`read`,f.uid FROM '.$this->Tbl['email_index'].' m'
                .','.$this->Tbl['email_folder'].' f WHERE m.idx='.$mail.' AND f.idx=m.folder_id';
        list ($fid, $hsize, $is_read, $newowner) = $this->fetchrow($this->query($query));
        if (!$fid || !$hsize) {
            return false;
        }

        $query = 'UPDATE '.$this->Tbl['email_index'].' SET folder_id='.$folder
                .', uidl='.(!is_null($newname) ? '"'.$this->esc($newname).'"' : 'uidl')
                .', ouidl='.(!is_null($newouidl) ? '"'.$this->esc($newouidl).'"' : 'ouidl')
                .', cached="'.($cached ? 1 : 0).'", uid='.$newowner
                .' WHERE idx='.$mail;
        if ($this->query($query)) {
            // Remove from old folder
            $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=mailnum-1'.
                    ',mailsize=IF(CAST(mailsize AS SIGNED)-'.($hsize+0).' < 1, 0, mailsize-'.($hsize+0).')'.
                    ',unread=unread-'.(1-$is_read).' WHERE idx='.$fid;
            $this->query($query);
            $err = $this->error();
            if ($err) {
                $this->set_error($err);
                return false;
            }
            // Add to new folder
            $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=mailnum+1,mailsize=mailsize+'.($hsize+0)
                    .',unread=unread+'.(1-$is_read).' WHERE idx='.$folder;
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
     * Copy a mail from one folder to another As of phlyMail 4 the owner id is
     * changed according to the owner id of the folder the mail now belongs to.
     * Additionally the uid is no longer checked!
     *
     * @param  int  $uid  The user ID to perform the operation for (actually ignored)
     * @param  int  $mail  Mail ID to copy
     * @param  int  $folder  The destination folder to copy the mail to
     * @param  string  $newname  In case the file name changed, pass it here, Default: null
     * @param  bool $cached Whether this mail is cached locally (POP3) or not (IMAP)
     * @param  string $newouidl  In case the UIDL of an IMAP mail changes, pass it here, Default: null
     * @return  bool  TRUE on success, FALSE on failure
     * @since 0.1.4
     */
    public function mail_copy($uid = 0, $mail = false, $folder = false, $newname = null, $cached = true, $newouidl = null)
    {
        if ($mail === false || $folder === false || $newname === false) {
            $this->set_error('Please specify the mail to copy and its target folder');
            return false;
        }
        // Get current folder and mailsize for updating the meta data of old and new folder
        $query = 'SELECT * FROM '.$this->Tbl['email_index'].' WHERE idx='.intval($mail);
        $from = $this->assoc($this->query($query));
        if (!$from || empty($from)) {
            return false;
        }
        $is_read = $from['read'] ? 1 : 0;
        unset($from['idx']);
        list ($newowner) = $this->fetchrow($this->query('SELECT uid FROM '.$this->Tbl['email_folder']
                .' WHERE idx='.intval($from['folder_id'])));
        foreach ($from as $k => $v) {
            $from[$k] = $this->esc($v);
        }
        // Duplicate mail in index
        $query = 'INSERT '.$this->Tbl['email_index'].' SET folder_id='.$folder.',uid='.$newowner
                .', uidl='.(!is_null($newname) ? '"'.$this->esc($newname).'"' : 'uidl')
                .', ouidl='.(!is_null($newouidl) ? '"'.$this->esc($newouidl).'"' : 'ouidl')
                .',hfrom="'.$from['hfrom'].'",hto="'.$from['hto'].'",hsubject="'.$from['hsubject'].'"'
                .',hdate_sent="'.$from['hdate_sent'].'",hdate_recv="'.$from['hdate_recv'].'",hcc="'.$from['hcc'].'"'
                .',hbcc="'.$from['hbcc'].'",search_body="'.$from['search_body'].'",search_body_type="'.$from['search_body_type'].'"'
                .',hsize="'.$from['hsize'].'",hpriority="'.$from['hpriority'].'"'
                .',attachments="'.$from['attachments'].'",`read`="'.$from['read'].'"'
                .',hmessage_id="'.uniqid(time().'.').'@phlymail.local",answered="'.$from['answered'].'"'
                .',forwarded="'.$from['forwarded'].'",bounced="'.$from['bounced'].'"'
                .',struct="'.$from['struct'].'",type="'.$from['type'].'",dsn_sent="1", cached="'.($cached ? 1 : 0).'"'
                .',colour='.($from['colour'] == 'NULL' || $from['colour'] == '0' ? 'NULL' : '"'.$from['colour'].'"')
                .',htmlunblocked="'.$from['htmlunblocked'].'"';
        if ($this->query($query)) {
            // Add to new folder
            $query = 'UPDATE '.$this->Tbl['email_folder'].' SET mailnum=mailnum+1'
                    .',mailsize=mailsize+'.($from['hsize']+0).',unread=unread+'.(1-$is_read)
                    .' WHERE idx='.intval($folder);
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
     * Add a mail to the index
     *
     * @param  int  ID of the user to add the mail to
     * @param  int  Folder ID to add the mail to
     * @param  array  detailed header and meta information about the mail
     * @return  mixed  new ID of the mail on success, FALSE otherwise
     * @since 0.0.9
     */
    public function mail_add($uid = 0, $folder, $data)
    {
        $query = 'SELECT 1,uid FROM '.$this->Tbl['email_folder'].' WHERE uid IN(0,'.intval($uid).') AND idx='.intval($folder).' LIMIT 1';
        list ($fold_exists, $newowner) = $this->fetchrow($this->query($query));
        if (!$fold_exists) {
            $this->set_error('Folder not owned by this user');
            return false;
        }
        if (!isset($data['status'])) {
            $data['status'] = 0;
        }
        if (!isset($data['type'])) {
            $data['type'] = 'mail';
        }
        if (!isset($data['priority']) || !is_numeric($data['priority'])) {
            $data['priority'] = 3;
        }
        if (empty($data['search_body'])) {
            $data['search_body'] = '';
        }
        if (empty($data['message_id'])) {
            $data['message_id'] = '';
        }

        if (empty($data['search_body_type'])) {
            $data['search_body_type'] = 'text';
        }
        $fields = '';
        foreach (array(
                'uidl' => 'uidl', 'from' => 'hfrom', 'to' => 'hto', 'cc' => 'hcc',
                'bcc' => 'hbcc', 'subject' => 'hsubject', 'date_received' => 'hdate_recv',
                'date_sent' => 'hdate_sent', 'size' => 'hsize', 'priority' => 'hpriority',
                'attachments' => 'attachments', 'type' => 'type', 'status' => 'read',
                'answered' => 'answered', 'forwarded' => 'forwarded', 'bounced' => 'bounced',
                'struct' => 'struct', 'message_id' => 'hmessage_id', 'ouidl' => 'ouidl',
                'profile' => 'profile', 'cached' => 'cached', 'colour' => 'colour',
                'search_body' => 'search_body', 'search_body_type' => 'search_body_type'
                ) as $k => $v) {
            if (!isset($data[$k])) {
                continue;
            }
            $fields .= ',`'.$v.'`="'.$this->esc($data[$k]).'"';
        }
        $is_read = (isset($data['status']) && $data['status']) ? 0 : 1;
        $unseen = (isset($data['unseen']) && !$data['unseen']) ? 0 : 1;
        // Make MySQL ignore too long search body contents - let it trim
        $this->query("SET sql_mode = ''");

        $query = 'INSERT '.$this->Tbl['email_index'].' SET uid='.intval($newowner).', folder_id='.intval($folder).$fields;
        $res = $this->query($query);
        if ($res) {
            $newID = $this->insertid();
            $query = 'UPDATE '.$this->Tbl['email_folder']
                    .' SET mailnum=mailnum+1, mailsize=mailsize+'.intval($data['size'])
                    .',unread=unread+'.(1-$is_read).',unseen="'.$unseen.'"'
                    .' WHERE idx='.intval($folder);
            $this->query($query);
            // If belongs to a thread put relation into DB
            $this->thread_add_item($newID, $data, $newowner);
            // return
            return $newID;
        }
        return false;
    }

    /**
     * Automatically adding emails to the internal thread index to allow
     * a quick and comfortable Thread View
     *
     * @param int $id   ID of the mail in the database
     * @param array $header  Message header, at least having Message-ID, References and In-Reply-To
     * @param int $uid  ID of the user this mail (and adjacent threads) belong to
     * @return bool  True, if added to thread, False, if not added to any thread
     * @since 4.3.7
     */
    public function thread_add_item($id, $header, $uid = 0)
    {
        // We need the message ID for safely identifying thread members
        // This does NOT check, whether the message ID is sensible and no duplicate
        if (empty($header['message_id'])) return false;

        $uid = intval($uid);
        // Remove < and > around Message-IDs
        foreach (array('message_id', 'inreplyto', 'references') as $h) {
            if (empty($header[$h])) continue;
            $header[$h] = str_replace(array('><', '<', '>', '  ', '  ', '  '), ' ', trim($header[$h]));
        }
        // Either In-Reply-To or References mail headers should be present (and not empty!)
        if (!empty($header['inreplyto']) || !empty($header['references'])) {
            // Unify header to check
            if (empty($header['references'])) {
                $header['references'] = (!empty($header['inreplyto'])) ? array($header['inreplyto']) : array();
            } else {
                $header['references'] = explode(' ', $header['references']);
            }
            // This mail belongs to the thread, too
            $header['references'][] = $header['message_id'];

            $toAdd = $mIdList = array();
            foreach ($header['references'] as $h) {
                $h = trim($h);
                if (empty($h)) continue; // Do not process empty reference headers!
                $mIdList[] = '"'.$this->esc($h).'"';
                $toAdd[$h] = 1;
            }
            $myThread = false;
            $sql = 'SELECT `thread_id`, `hmessage_id` FROM '.$this->Tbl['email_thread_items']
                    .' WHERE `hmessage_id` IN('.implode(',', $mIdList).') AND `uid`='.$uid;
            $qid = $this->query($sql);
            while ($line = $this->assoc($qid)) {
                $myThread = $line['thread_id'];
                unset($toAdd[$line['hmessage_id']]);
            }
            if (false === $myThread) { // No thread exists yet
                $this->query('INSERT '.$this->Tbl['email_thread'].' SET `uid`='.$uid
                        .',`date_first`="'.$this->esc($header['date_sent']).'"'
                        .',`date_last`="'.$this->esc($header['date_sent']).'"'
                        .',`last_message_id`="'.$this->esc($header['message_id']).'"');
                $myThread = $this->insertid();
            } else { // Updating existing thread
                $this->query('UPDATE '.$this->Tbl['email_thread'].' SET '
                        .'`date_last`="'.$this->esc($header['date_sent']).'"'
                        .',`last_message_id`="'.$this->esc($header['message_id']).'"'
                        .' WHERE `idx`='.intval($myThread).' AND `uid`='.$uid);
            }
            // Add mail(s) to the thread
            foreach (array_keys($toAdd) as $msgid) {
                $this->query('INSERT '.$this->Tbl['email_thread_items'].' SET `uid`='.$uid
                        .',`hmessage_id`="'.$this->esc($msgid).'"'
                        .',`thread_id`='.intval($myThread));
            }
            // This mail is known to belong to an index, but maybe the mail index ID is missing
            $this->query('UPDATE '.$this->Tbl['email_thread_items'].' SET `mail_id`='.intval($id)
                    .' WHERE `hmessage_id`="'.$this->esc($header['message_id']).'"'
                    .' AND `thread_id`='.intval($myThread));
        } else {
            $mIdList = array('"'.$this->esc(trim($header['message_id'])).'"');
        }
        // Bind together loose ends ...
        if (!empty($mIdList)) {
            $this->query('UPDATE IGNORE '.$this->Tbl['email_thread_items'].' ti, '.$this->Tbl['email_index'].' i SET ti.`mail_id`=i.idx'
                    .' WHERE ti.`mail_id` IS NULL AND i.hmessage_id!="" AND i.idx IS NOT NULL AND ti.`uid`=i.`uid`'
                    .' AND ti.`hmessage_id`=i.`hmessage_id` AND i.`hmessage_id` IN('.implode(',', $mIdList).')');
        }
        if (empty($header['delay_thread_cleanup'])) {
            $this->thread_cleanup();
        }
        return true;
    }

    public function thread_cleanup()
    {
        $this->query('UPDATE LOW_PRIORITY '.$this->Tbl['email_thread'].' t'
                .' SET t.`known_mails`=(SELECT COUNT(*) FROM '.$this->Tbl['email_thread_items'].' ti'
                .' WHERE t.`idx`=ti.`thread_id` AND ti.`mail_id` IS NOT NULL)');
        return true;
    }

    /**
     * Retrieve the list of filters defined for the current user
     * @param  int  $uid  User's ID to get the list for
     * @param  string $type  One of incoming, outgoing, system
     *[@param  bool  $global  Whether to fetch the global ones, too; Default: true]
     * @return  array  List of all filters, each line containing an array:
     * - id  int  databse ID of the filter
     * - name  string  user defined name of that filter
     * - active  bool  whether this filter is currently active
     * - layered_id  number of that filter to allow sorting
     * @since 0.3.5
     */
    public function filters_getlist($uid = 0, $type = 'incoming', $global = false)
    {
    	$return = array();
    	$query = 'SELECT `filter` `id`,`uid`,`name`,`active`,`layered_id`,`type` FROM '.$this->Tbl['email_filter']
    	       .' WHERE `uid`'.($global ? ' IN(0,'.intval($uid).')' : '='.intval($uid))
    	       .($type != 'all' ? ' AND `type`="'.$this->esc($type).'"' : '')
    	       .' ORDER BY `uid` ASC, `layered_id` ASC';
    	$qid = $this->query($query);
    	while ($line = $this->assoc($qid)) $return[] = $line;
    	return $return;
    }

    /**
     * Retrieve basic data and all rules for a specific filter
     *
     * @param  int  user id affected
     * @param  int  id of the filter
     * @return  array
     * @since 0.3.5
     */
    public function filters_getfilter($uid = 0, $filter = 0)
    {
        $query = 'SELECT `filter` as `id`, `name`, `active`, `layered_id`, `match`, `move`, `move_to`, `copy`, `copy_to`'
                .', `bounce`, `bounce_to`,`forward`,`forward_to`,`set_prio`,`new_prio`,`mark_read`,`markread_status`,`set_colour`'
                .',`new_colour`,`mark_junk`,`archive`,`delete`,`run_script`,`script_name`'
                .',`alert_sms`,`sms_to`, `sms_timeframe`, `sms_minpause`, UNIX_TIMESTAMP(`sms_lastuse`) `sms_lastuse`'
                .',`alert_email`,`email_to`, `email_timeframe`, `email_minpause`, UNIX_TIMESTAMP(`email_lastuse`) `email_lastuse`'
                .' FROM '.$this->Tbl['email_filter'].' WHERE `uid` IN(0,'.intval($uid).') AND `filter`='.intval($filter);
    	$return = $this->assoc($this->query($query));
    	if (!isset($return) || empty($return) || !is_array($return)) return false;
    	$query = 'SELECT `id`, `field`, `operator`, `search` FROM '.$this->Tbl['email_filter_rule'].' WHERE `filter`='.intval($filter);
    	$qid = $this->query($query);
    	while ($line = $this->assoc($qid)) $return['rules'][] = $line;
    	return $return;
    }

    /**
     * Add a filter to the user's filter list
     *
     * @param  int  user id affected
     * @param  array  Payload to add to the database
     * @return  bool  TRUE on success, FALSE on any failure
     * @since 0.3.5
     */
    public function filters_addfilter($uid = 0, $filter)
    {
        $parts = array();
        foreach (array('name', 'active', 'type', 'match', 'move', 'move_to', 'copy', 'copy_to', 'bounce', 'bounce_to', 'forward', 'forward_to'
                ,'set_prio', 'new_prio', 'mark_read', 'markread_status', 'set_colour', 'new_colour', 'mark_junk', 'archive', 'delete'
                ,'alert_sms', 'sms_to', 'sms_timeframe', 'sms_minpause','alert_email', 'email_to', 'email_timeframe', 'email_minpause') as $k) {
            if (!isset($filter[$k])) continue;
    		$parts[] = '`'.$k.'`="'.(isset($filter[$k]) ? $this->esc($filter[$k]) : '').'"';
    	}
    	$query = 'SELECT max(`layered_id`) FROM '.$this->Tbl['email_filter'].' WHERE `uid`='.intval($uid);
    	list ($max) = $this->fetchrow($this->query($query));

    	$query = 'INSERT '.$this->Tbl['email_filter'].' SET `uid`='.intval($uid).',`layered_id`='.($max+1).','.implode(', ', $parts);
    	if (!$this->query($query)) return false;
    	$id = $this->insertid();
    	foreach ($filter['rules'] as $k => $v) {
    		$query = 'INSERT '.$this->Tbl['email_filter_rule'].' SET `field`="'.$this->esc($v['field']).'",`filter`='.$id
    				.',`operator`="'.$this->esc($v['operator']).'",`search`="'.$this->esc($v['search']).'"';
    		if (!$this->query($query)) return false;
    	}
    	return true;
    }

    /**
     * Update a filter
     *
     * @param  int  user id affected
     * @param  array  Payload to add to the database (@see filters_getfilters() for the desired structure)
     * @return  bool  TRUE on success, FALSE on any failure
     * @since 0.3.5
     */
    public function filters_updatefilter($uid = 0,  $filter)
    {
    	$parts = array();
    	foreach (array('name', 'active', 'type', 'match', 'move', 'move_to', 'copy', 'copy_to', 'bounce', 'bounce_to', 'forward', 'forward_to'
                ,'set_prio', 'new_prio', 'mark_read', 'markread_status', 'set_colour', 'new_colour', 'mark_junk', 'archive', 'delete'
                ,'alert_sms', 'sms_to', 'sms_timeframe', 'sms_minpause','alert_email', 'email_to', 'email_timeframe', 'email_minpause') as $k) {
            if (!isset($filter[$k])) continue;
    		$parts[] = '`'.$k.'`="'.(isset($filter[$k]) ? $this->esc($filter[$k]) : '').'"';
    	}
    	$query = 'UPDATE '.$this->Tbl['email_filter'].' SET '.implode(', ', $parts).' WHERE uid='.intval($uid).' AND filter='.intval($filter['id']);
    	if (!$this->query($query)) return false;
    	$this->query('DELETE FROM '.$this->Tbl['email_filter_rule'].' WHERE filter='.intval($filter['id']));
    	foreach ($filter['rules'] as $k => $v) {
    		$query = 'INSERT '.$this->Tbl['email_filter_rule'].' SET field="'.$this->esc($v['field']).'",filter='.intval($filter['id'])
    				.',operator="'.$this->esc($v['operator']).'",search="'.$this->esc($v['search']).'"';
    		if (!$this->query($query)) return false;
    	}
    	return true;
    }

    /**
     * Remove a certain filter
     *
     * @param  int  user id affected
     * @param  int  id of the filter
     * @return  bool  TRUE on success, FALSE on failure
     * @since 0.3.5
     */
    public function filters_removefilter($uid = 0, $filter)
    {
    	return ($this->query('DELETE FROM '.$this->Tbl['email_filter'].' WHERE uid='.intval($uid).' AND filter='.intval($filter))
    			&& $this->query('DELETE FROM '.$this->Tbl['email_filter_rule'].' WHERE filter='.intval($filter)));
    }

    /**
     * Switch activation state of a filter
     *
     * @param  int  user id affected
     * @param  int  id of the filter
     * @return  bool  TRUE on success, FALSE on failure
     * @since 0.3.8
     */
    public function filters_activatefilter($uid = 0, $filter)
    {
    	return $this->query('UPDATE '.$this->Tbl['email_filter'].' SET active = CONCAT("", 1-active) WHERE uid='.intval($uid).' AND filter='.intval($filter));
    }

    /**
     * Apply a new ordering to the list of filtes
     *
     * @param  int  user id affected
     * @param  int  Filter to get moved around
     * @param  'up'|'down'  Direction to move the filter to
     * @return  bool  TRUE on succes, FALSE on any failure
     * @since 0.3.5
     */
    public function filters_reorder($uid = 0, $filter, $dir)
    {
        $uid = intval($uid);
        $filter = intval($filter);
    	$query = 'SELECT `layered_id`, `type` FROM '.$this->Tbl['email_filter'].' WHERE uid='.$uid.' AND filter='.$filter;
    	list ($cur, $type) = $this->fetchrow($this->query($query));
    	$query = 'SELECT min(layered_id), max(layered_id) FROM '.$this->Tbl['email_filter'].' WHERE uid='.$uid.' AND `type`="'.$type.'"';
    	list ($min, $max) = $this->fetchrow($this->query($query));
    	if ('up' == $dir) {
    		if ($cur == $min) return true;
    		$this->query('UPDATE '.$this->Tbl['email_filter'].' SET layered_id='.$cur.' WHERE layered_id='.($cur-1).' AND `type`="'.$type.'" AND uid='.$uid);
    		$this->query('UPDATE '.$this->Tbl['email_filter'].' SET layered_id='.($cur-1).' WHERE filter='.$filter.' AND `type`="'.$type.'" AND uid='.$uid);
    	} else {
    		if ($cur == $max) return true;
    		$this->query('UPDATE '.$this->Tbl['email_filter'].' SET layered_id='.$cur.' WHERE layered_id='.($cur+1).' AND `type`="'.$type.'" AND uid='.$uid);
    		$this->query('UPDATE '.$this->Tbl['email_filter'].' SET layered_id='.($cur+1).' WHERE filter='.$filter.' AND `type`="'.$type.'" AND uid='.$uid);
    	}
    	return true;
    }

    /**
     * Sets the last use timestamp for a filter, that matched and caused an email / SMS to be sent
     *
     * @param int user id
     * @param int filter id
     * @param email|sms Name of the method (Email or SMS) that was fired
     * @param int Timestamp the action took place
     * @return bool
     * @since 0.4.7
     */
    public function filters_set_lastuse($uid = 0, $filter, $mode = 'email', $time = 0)
    {
        if (!$filter) return true;
        if (!$time || !is_numeric($time)) $time = time();
        $query = 'UPDATE '.$this->Tbl['email_filter'].' SET '.($mode == 'sms' ? 'sms' : 'email').'_lastuse="'.date('Y-m-d H:i:s', $time).'" WHERE filter='.intval($filter)
                .' AND uid IN(0,'.intval($uid).')';
        return $this->query($query);
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
        $cnt = $sum = $max_uid = $max_cnt = 0;
        if (false == $stats) {
            $query = 'SELECT count(*) FROM '.$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND att_type=1';
            list ($num) = $this->fetchrow($this->query($query));
            return $num;
        }
        $query = 'SELECT count(distinct uid), count(*) FROM '.$this->Tbl['email_folder'].' WHERE att_type=1';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, count(*) moep FROM '.$this->Tbl['email_folder'].' WHERE att_type=1 GROUP BY uid ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array('count' => $cnt, 'sum' => $sum, 'max_uid' => $max_uid, 'max_count' => $max_cnt);
    }

    /**
     * Qutoa related: Returns the overall size of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @param int $uid  User ID
     * @return int $size Size of all mails in bytes
     * @since 0.7.1
     */
    public function quota_getmailsize($uid = 0, $stats = false)
    {
        $cnt = $sum = $max_uid = $max_cnt = 0;
        if (false == $stats) {
            $query = 'SELECT sum(mailsize) FROM '.$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND att_type<10';
            list ($size) = $this->fetchrow($this->query($query));
            return $size;
        }
        $query = 'SELECT count(distinct uid), sum(mailsize) FROM '.$this->Tbl['email_folder'].' WHERE att_type<10';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, sum(mailsize) moep FROM '.$this->Tbl['email_folder'].' WHERE att_type<10 GROUP BY uid ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array('count' => $cnt, 'sum' => $sum, 'max_uid' => $max_uid, 'max_count' => $max_cnt);
    }

    /**
     * Qutoa related: Returns the number of all mails this user has stored in his
     * local folders (including the system folders, of course).
     * @param int $uid  User ID
     * @return int $size Number of all mails
     * @since 0.7.1
     */
    public function quota_getmailnum($uid = 0, $stats = false)
    {
        $cnt = $sum = $max_uid = $max_cnt = 0;
        if (false == $stats) {
            $query = 'SELECT sum(mailnum) FROM '.$this->Tbl['email_folder'].' WHERE uid='.intval($uid).' AND att_type<10';
            list ($size) = $this->fetchrow($this->query($query));
            return $size;
        }
        $query = 'SELECT count(distinct uid), sum(mailnum) FROM '.$this->Tbl['email_folder'].' WHERE att_type<10';
        list ($cnt, $sum) = $this->fetchrow($this->query($query));
        if ($cnt) {
            $query = 'SELECT uid, sum(mailnum) moep FROM '.$this->Tbl['email_folder'].' WHERE att_type<10 GROUP BY uid ORDER BY moep DESC LIMIT 1';
            list ($max_uid, $max_cnt) = $this->fetchrow($this->query($query));
        }
        return array('count' => $cnt, 'sum' => $sum, 'max_uid' => $max_uid, 'max_count' => $max_cnt);
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
     * @since 0.7.2
     */
    public function uidlcache_match($profile, $maillist, $dblist = false)
    {
        $profile = intval($profile);
        $cached = array();
        $q_r = '';
        if (false !== $dblist) {
            $cached = &$dblist;
        } else {
            $qid = $this->query('SELECT uidl FROM '.$this->Tbl['email_uidlcache'].' WHERE profile='.$profile);
            while (list ($uidl) = $this->fetchrow($qid)) $cached[] = phm_stripslashes($uidl);
        }
        $olist = array_diff($cached, $maillist);
        $nlist = array_diff($maillist, $cached);
        // Drop items from the cache table, which do no longer exist on the mailserver
        if (false === $dblist && !empty($olist)) {
            foreach ($olist as $uidl) { $q_r .= ($q_r ? ',' : '').'"'.$this->esc($uidl).'"'; }
            $this->query('DELETE FROM '.$this->Tbl['email_uidlcache'].' WHERE profile='.$profile.' AND uidl in ('.$q_r.')');
        }
        return array($nlist, $olist);
    }

    /**
     * Adds an item to the UIDL cache
     *
     * @param int $profile
     * @param string $item
     * @return bool
     * @since 0.7.2
     */
    public function uidlcache_additem($profile, $item)
    {
        return $this->query('INSERT '.$this->Tbl['email_uidlcache'].' SET profile='.intval($profile).', uidl="'.$this->esc($item).'"');
    }

    /**
     * Enter description here...
     *
     * @param int $profile
     * @param string $item
     * @return bool  TRUE, if UIDL already in DB, FALSE otherwise
     * @since 0.7.2
     */
    public function uidlcache_checkitem($profile, $item)
    {
        $qid = $this->query('SELECT 1 FROM '.$this->Tbl['email_uidlcache'].' WHERE profile='.intval($profile).' AND uidl="'.$this->esc($item).'" LIMIT 1');
        list ($true) = $this->fetchrow($qid);
        return (1 == $true);
    }

    /**
     * Drops all information about already downloaded UIDLs for a certain profile.
     * This method is used by the fetchers when a profile is switched from "Keep on server" to not doing so.
     *
     * @param int $profile
     * @return bool
     * @since 0.7.2
     */
    public function uidlcache_remove($profile)
    {
        return $this->query('DELETE FROM '.$this->Tbl['email_uidlcache'].' WHERE profile='.intval($profile));
    }

    /**
     * Used for profile, where mails deleted locally shall get deleted on the POP3 server, too.
     *
     * @param int $profile
     * @param string $item
     * @return true
     * @since 0.7.3
     */
    public function uidlcache_markdeleted($profile, $item)
    {
        list ($delonserver) = $this->fetchrow($this->query('SELECT localkillserver FROM '.$this->Tbl['profiles'].' WHERE id='.intval($profile)));
        if (!$delonserver) return true;
        return $this->query('UPDATE '.$this->Tbl['email_uidlcache'].' SET deleted="1" WHERE profile='.intval($profile).' AND uidl="'.$this->esc($item).'"');
    }

    /**
     * Delivers a list of all UIDLs in a given profile, which were deleted locally, thus can get deleted from the server, too.
     * This applies to all POP3 profile which have leaveonserver On and localkillserver On.
     *
     * @param int $profile
     * @return array
     * @since 0.7.3
     */
    public function uidlcache_getdeleted($profile)
    {
        $return = array();
        $qid = $this->query('SELECT uidl FROM '.$this->Tbl['email_uidlcache'].' WHERE profile='.intval($profile).' AND deleted="1"');
        while (list($uidl) = $this->fetchrow($qid)) $return[] = $uidl;
        return $return;
    }

    /**
     * Add a whitelist entry, at least one of the filter flags must be set. IF the
     * filter is already in the system, the filter flags override the stored ones.
     *
     * @param string $filter  Either email address or domain ([local]@domain)
     * @param null|0|1 $html Whether to unblock HTML mails
     * @param null|0|1 $ical Whether to auto process iCal files
     * @param null|0|1 $vcf  Whether to auto process VCFs
     */
    public function whitelist_addfilter($uid, $filter, $html = null, $ical = null, $vcf = null)
    {
        if (is_null($html) && is_null($ical) && is_null($vcf)) return false; // At least one flag MUST be set
        if (!strlen($filter) || false === strpos($filter, '@')) return false;

        $qid = $this->query('SELECT id FROM '.$this->Tbl['email_whitelist'].' WHERE `uid`='.intval($uid).' AND `filter`="'.$this->esc($filter).'"');
        if ($this->numrows($qid)) { // Hit!
            $orig = $this->assoc($qid);
            $sql = 'UPDATE '.$this->Tbl['email_whitelist'].' SET `uid`=`uid`';
            if (!is_null($html)) $sql .= ',`htmlunblocked`="'.intval($html).'"';
            if (!is_null($ical)) $sql .= ',`process_cal`="'.intval($ical).'"';
            if (!is_null($vcf))  $sql .= ',`process_vcf`="'.intval($vcf).'"';
            $sql .= ' WHERE `id`='.$orig['id'];
        } else { // New entry
            $sql = 'INSERT INTO '.$this->Tbl['email_whitelist'].' SET `uid`='.intval($uid).', `filter`="'.$this->esc($filter).'"';
            if (!is_null($html)) $sql .= ',`htmlunblocked`="'.intval($html).'"';
            if (!is_null($ical)) $sql .= ',`process_cal`="'.intval($ical).'"';
            if (!is_null($vcf))  $sql .= ',`process_vcf`="'.intval($vcf).'"';
        }
        return $this->query($sql);
    }

    /**
     * Retrieve whitelist filter entry
     *
     * @param int $idr  ID of the filter
     * @return false|array
     */
    public function whitelist_getfilter($uid, $id)
    {
        $qid = $this->query('SELECT * FROM '.$this->Tbl['email_whitelist'].' WHERE `uid`='.intval($uid).' AND `id`='.intval($id));
        if (!$this->numrows($qid)) {
            return false; // Nothing found
        }
        return $this->assoc($qid);
    }

    /**
     * Retrieve whitelist filter list
     * @return array
     */
    public function whitelist_getlist($uid)
    {
        $qid = $this->query('SELECT * FROM '.$this->Tbl['email_whitelist'].' WHERE `uid`='.intval($uid).' ORDER BY `filter` ASC');
        if (!$this->numrows($qid)) {
            return false; // Nothing found
        }
        $return = array();
        while ($line = $this->assoc($qid)) {
            $return[$line['id']] = $line;
        }
        return $return;
    }

    /**
     * Search for whitelist settings for given email address.
     * If there's a specific entry for the address, this is returned.
     * If we have an entry for the domain of the email, this is returned as fallback.
     * That means: The email address always has precedence!
     *
     * @param string $email
     * @return false|array
     */
    public function whitelist_search($uid, $email)
    {
        if (strpos($email, '@') === false) return false;

        $dom = explode('@', $email);
        $dom = '@'.$dom[1];
        $qid = $this->query('SELECT * FROM '.$this->Tbl['email_whitelist'].' WHERE `uid`='.intval($uid)
                .' AND `filter` IN("'.$this->esc($email).'","'.$this->esc($dom).'")');
        // Nothing found
        if (!$this->numrows($qid)) return false;
        $res = array();
        while ($line = $this->assoc($qid)) {
            $res[$line['filter']] = $line;
        }
        // Got the specific entry for the email address
        if (isset($res[$email])) return $res[$email];
        // Got a generic entry for the domain
        if (isset($res[$dom])) return $res[$dom];
        // Just in case...
        return $res;
    }

    /**
     * This method checks, whether a user might access a given folder
     *
     * @param int $uid  The user ID
     * @param int $folder  The folder ID
     *[@param string $mode One of 'r' for reading operations, 'w' for writing, 'd' deleting that folder
     *     ,'di' deleting items in the folder; Currently NOT CHECKED]
     * @return bool  TRUE, if access okay; FALSE, if not
     * @since 4.1.6
     */
    protected function check_perm_folder($uid, $folder, $mode = 'r')
    {
        // Bypass in any cases, where previous operations already ruled out the user ID
        if (is_null($uid) || false == $uid) return true;
        // Directly owned by this user?
        $qh = $this->query('SELECT 1 FROM '.$this->Tbl['email_folder'].' WHERE idx='.intval($folder).' AND uid='.intval($uid));
        if ($this->numrows($qh)) return true;
        // Do we have shares at all?
        if (!isset($this->features['shares']) || !$this->features['shares']) return false;
        // Shares directly assigned to the user
        $qh = $this->query('SELECT 1 FROM '.$this->Tbl['share_folder'].' WHERE h="email" AND fid='.intval($folder).' AND uid='.intval($uid));
        if ($this->numrows($qh)) return true;
        // Do we have groups at all?
        if (!isset($this->features['groups']) || !$this->features['groups']) return false;
        // Any other group share, where the groups the user is in match
        $qh = $this->query('SELECT 1 FROM '.$this->Tbl['share_folder'].' sf, '.$this->Tbl['user_group'].' ug WHERE sf.h="email" AND sf.fid='.intval($folder).' AND sf.gid=ug.gid AND ug.uid='.intval($uid));
        if ($this->numrows($qh)) return true;
        // None of the above matched
        return false;
    }
}
