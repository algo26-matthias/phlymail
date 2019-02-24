<?php
/**
 * Shares for folders and items
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.2 2013-04-10 
 */
class DB_Controller_Share extends DB_Controller
{
    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['share_folder'] = $this->DB['db_pref'].'share_folder';
        $this->Tbl['share_item'] = $this->DB['db_pref'].'share_item';
    }

    /**
     * Add a new shared folder, specify either the GID for a group share or the UID for a user share.
     * Group shares are folders shared for the given group and thus all users in it, User shares are
     * shares for a specific user.
     *
     * @param string $handler  Name of the handler
     * @param int $folder  Folder ID within given handler
     * @param int $gid Group ID to add the share for, NULL for not a group share
     * @param int $uid User ID to add the share for, NULL for not a user share
     * @param array $permissions
     * @return bool
     * @since 0.0.1
     */
    public function addFolder($handler, $folder, $owner, $gid, $uid, array $permissions)
    {
        $query = 'INSERT '.$this->Tbl['share_folder'].
                ' SET handler="'.$this->esc($handler).'", fid='.intval($folder).
                ', gid='.(is_null($gid) ? 'NULL' : intval($gid)).
                ', uid='.(is_null($uid) ? 'NULL' : intval($uid)).
                ', owner='.(is_null($owner) ? 'NULL' : intval($owner)).
                ', may_list="'.(!empty($permissions['list']) ? 1 : 0).'"'.
                ', may_read="'.(!empty($permissions['read']) ? 1 : 0).'"'.
                ', may_write="'.(!empty($permissions['write']) ? 1 : 0).'"'.
                ', may_delete="'.(!empty($permissions['delete']) ? 1 : 0).'"'.
                ', may_newfolder="'.(!empty($permissions['newfolder']) ? 1 : 0).'"'.
                ', may_delitems="'.(!empty($permissions['deleitems']) ? 1 : 0).'"';
        return $this->query($query);
    }

    /**
     * Sets the shares for a given folder in the given handler
     *
     * @param string $handler
     * @param int $folder
     * @param int $owner
     * @param array $gid
     * @param array $uid
     * @since 0.0.1
     */
    public function setFolder($handler, $folder, $owner = null, $groups = array(), $users = array())
    {
        $oldshares = $this->getFolder($handler, $folder, $owner);
        //
        // Groups
        //
        $gdrop = array();
        foreach ($oldshares['gid'] as $gid => $operm) {
            if (!isset($groups[$gid])) {
                $gdrop[] = $gid; // No longer set
            } else {
                $permissions = $groups[$gid];
                $this->query('UPDATE '.$this->Tbl['share_folder'].' SET '.
                        ' may_list="'.(!empty($permissions['list']) ? 1 : 0).'"'.
                        ',may_read="'.(!empty($permissions['read']) ? 1 : 0).'"'.
                        ',may_write="'.(!empty($permissions['write']) ? 1 : 0).'"'.
                        ',may_delete="'.(!empty($permissions['delete']) ? 1 : 0).'"'.
                        ',may_newfolder="'.(!empty($permissions['newfolder']) ? 1 : 0).'"'.
                        ',may_delitems="'.(!empty($permissions['deleitems']) ? 1 : 0).'"'.
                        ' WHERE handler="'.$this->esc($handler).'" AND fid='.intval($folder).
                        ' AND gid='.intval($gid)).
                        (!empty($owner) ? ' AND `owner`='.intval($owner) : '');

            }
        }
        // Remove old
        if (!empty($gdrop)) {
            $this->removeFolder($handler, $folder, $owner, $gdrop);
        }
        // Add new
        foreach ($groups as $gid => $permissions) {
            if (!isset($oldshares['gid'][$gid])) { // New share
                $this->addFolder($handler, $folder, $owner, $gid, null, $permissions);
            }
        }
        //
        // Users
        //
        $udrop = array();
        foreach ($oldshares['uid'] as $uid => $operm) {
            if (!isset($users[$uid])) {
                $udrop[] = $uid; // No longer set
            } else {
                $permissions = $users[$uid];
                $this->query('UPDATE '.$this->Tbl['share_folder'].' SET '.
                        ' may_list="'.(!empty($permissions['list']) ? 1 : 0).'"'.
                        ',may_read="'.(!empty($permissions['read']) ? 1 : 0).'"'.
                        ',may_write="'.(!empty($permissions['write']) ? 1 : 0).'"'.
                        ',may_delete="'.(!empty($permissions['delete']) ? 1 : 0).'"'.
                        ',may_newfolder="'.(!empty($permissions['newfolder']) ? 1 : 0).'"'.
                        ',may_delitems="'.(!empty($permissions['deleitems']) ? 1 : 0).'"'.
                        ' WHERE handler="' . $this->esc($handler) . '" AND fid=' . intval($folder).
                        ' AND uid=' . intval($uid)).
                        (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
            }
        }
        // Remove old
        if (!empty($udrop)) {
            $this->removeFolder($handler, $folder, $owner, null, $udrop);
        }
        // Add new
        foreach ($users as $uid => $permissions) {
            if (!isset($oldshares['uid'][$uid])) { // New share
                $this->addFolder($handler, $folder, $owner, null, $uid, $permissions);
            }
        }
    }

    /**
     * Remove one ore more share(s) on a folder given by uid(s) or gid(s).
     *
     * @param string $handler  Name of the handler
     * @param int $folder  Item ID within given handler
     * @param null|array|int $groups Either one group or array to remove the share for
     * @param null|array|int $users Either oneuser or array to remove the share for
     */
    public function removeFolder($handler, $folder, $owner = null, $groups = null, $users = null)
    {
        if (empty($folder)) {
            return true;
        }
        $folder = (is_array($folder)) ? ' IN ('.$this->esc(implode(',', $folder)).')' : '='.intval($folder);

        $sql = 'DELETE FROM ' . $this->Tbl['share_folder'].
                ' WHERE handler="' . $this->esc($handler) . '" AND fid' . $folder.
                (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
        if (!empty($groups)) {
            if (is_array($groups)) {
                foreach ($groups as $k => $gid) {
                    $groups[$k] = intval($gid);
                }
                $sql .= ' AND gid IN('.join(',', $groups).')';
            } else {
                $sql .= ' AND gid=' . intval($groups);
            }
        }
        if (!empty($users)) {
            if (is_array($users)) {
                foreach ($users as $k => $gid) {
                    $users[$k] = intval($gid);
                }
                $sql .= ' AND uid IN('.join(',', $users).')';
            } else {
                $sql .= ' AND uid=' . intval($users);
            }
        }
        return $this->query($sql);
    }

    /**
     * Retrieves the list of shared groups / users for a given folder in the given handler
     *
     * @param string $handler  Name of the handler
     * @param int $folder  Folder ID within given handler
     *[@param int $owner  Owner of the share; optional; Default: all for handler/folder]
     * @return array $permissions
     * @since 0.0.1
     */
    public function getFolder($handler, $folder, $owner = null)
    {
        $return = array('gid' => array(), 'uid' => array());
        $query = 'SELECT gid, uid, may_list, may_read, may_write, may_delete, may_newfolder, may_delitems FROM '.$this->Tbl['share_folder'].
                ' WHERE handler="'.$this->esc($handler).'" AND fid='.intval($folder).
                (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
        $qid = $this->query($query);
        while ($res = $this->assoc($qid)) {
            if (!is_null($res['gid'])) {
                $return['gid'][$res['gid']] = array(
                        'list' => $res['may_list'],
                        'read' => $res['may_read'],
                        'write' => $res['may_write'],
                        'delete' => $res['may_delete'],
                        'newfolder' => $res['may_newfolder'],
                        'delitems' => $res['may_delitems']
                );
            }
            if (!is_null($res['uid'])) {
                $return['uid'][$res['uid']] = array(
                        'list' => $res['may_list'],
                        'read' => $res['may_read'],
                        'write' => $res['may_write'],
                        'delete' => $res['may_delete'],
                        'newfolder' => $res['may_newfolder'],
                        'delitems' => $res['may_delitems']
                );
            }
        }
        return $return;
    }

    /**
     * Retrieves  list of folders shared with others
     * @param int|null $owner
     * @param string|null $handler
     */
    public function getFolderList($owner = null, $handler = null)
    {
        $query = 'SELECT `handler`, `owner`, `fid` FROM '.$this->Tbl['share_folder'].' WHERE 1';
        if (!empty($owner)) {
            $query .= ' AND `owner`='.intval($owner);
        }
        if (!empty($handler)) {
            $query .= ' AND handler="'.$this->esc($handler).'"';
        }
        $query .= ' GROUP BY CONCAT(`handler`, "|", `fid`) ORDER BY `owner`, `handler`, `fid`';
        $return = array();
        $qid = $this->query($query);
        while ($res = $this->assoc($qid)) {
            if (!isset($return[$res['owner']])) {
                $return[$res['owner']] = array();
            }
            if (!isset($return[$res['owner']][$res['handler']])) {
                $return[$res['owner']][$res['handler']] = array();
            }
            $return[$res['owner']][$res['handler']][$res['fid']] = 1;
        }
        return $return;
    }

    /**
     * Retrieves a list of folders shared by others with me or my groups
     * @param int|null $owner
     * @param string|null $handler
     */
    public function getMySharedFolders($uid, $handler = null, $gid = null)
    {
        $uid = intval($uid);

        $query = 'SELECT `handler`, `fid` FROM '.$this->Tbl['share_folder'].' WHERE `owner`!='.$uid.' AND (`uid`='.$uid;
        if (!empty($gid)) {
            $query .= ' OR `gid`'.(is_array($gid) ? ' IN ('.implode(',', basics::intify($gid)).')' : '='.intval($gid));
        }
        if (!empty($handler)) {
            $query .= ') AND handler="'.$this->esc($handler).'"';
        }
        $query .= ' GROUP BY CONCAT(`handler`, "|", `fid`) ORDER BY `handler`, `fid`';
        $return = array();
        $qid = $this->query($query);
        while ($res = $this->assoc($qid)) {
            if (!isset($return[$res['handler']])) {
                $return[$res['handler']] = array();
            }
            $return[$res['handler']][$res['fid']] = 1;
        }
        return $return;
    }

    /**
     * Add a new shared folder, specify either the GID for a group share or the UID for a user share.
     * Group shares are folders shared for the given group and thus all users in it, User shares are
     * shares for a specific user.
     *
     * @param string $handler  Name of the handler
     * @param int $item  Item ID within given handler
     * @param int $gid Group ID to add the share for, NULL for not a group share
     * @param int $uid User ID to add the share for, NULL for not a user share
     * @param array $permissions
     * @return bool
     * @since 0.0.1
     */
    public function addItem($handler, $item, $owner, $gid, $uid, array $permissions)
    {
        $query = 'INSERT '.$this->Tbl['share_item'].
                ' SET handler="'.$this->esc($handler).'", idx='.intval($item).
                ', gid='.(is_null($gid) ? 'NULL' : intval($gid)).
                ', uid='.(is_null($uid) ? 'NULL' : intval($uid)).
                ', owner='.(is_null($owner) ? 'NULL' : intval($owner)).
                ', may_read="'.(!empty($permissions['read']) ? 1 : 0).'"'.
                ', may_write="'.(!empty($permissions['write']) ? 1 : 0).'"'.
                ', may_delete="'.(!empty($permissions['delete']) ? 1 : 0).'"';
        return $this->query($query);
    }

    /**
     * Sets the shares for a given folder in the given handler
     *
     * @param string $handler
     * @param int $item
     * @param array $gid
     * @param array $uid
     * @since 0.0.1
     */
    public function setItem($handler, $item, $owner = null, $groups = array(), $users = array())
    {
        $oldshares = $this->getItem($handler, $item);
        //
        // Groups
        //
        $gdrop = array();
        foreach ($oldshares['gid'] as $gid => $operm) {
            if (!isset($groups[$gid])) {
                $gdrop[] = $gid; // No longer set
            } else {
                $permissions = $groups[$gid];
                $this->query('UPDATE '.$this->Tbl['share_item'].' SET '.
                        ' may_read="'.(isset($permissions['read']) && $permissions['read'] ? 1 : 0).'"'.
                        ',may_write="'.(isset($permissions['write']) && $permissions['write'] ? 1 : 0).'"'.
                        ',may_delete="'.(isset($permissions['delete']) && $permissions['delete'] ? 1 : 0).'"'.
                        ' WHERE handler="'.$this->esc($handler).'" AND idx='.intval($item).
                        ' AND gid='.intval($gid)).
                        (!empty($owner) ? ' AND `owner`='.intval($owner) : '');

            }
        }
        // Remove old
        $this->removeItem($gdrop, null);
        // Add new
        foreach ($groups as $gid => $permissions) {
            if (! isset($oldshares['gid'][$gid])) { // New share
                $this->addItem($handler, $item, $owner, $gid, null, $permissions);
            }
        }
        //
        // Users
        //
        $udrop = array();
        foreach ($oldshares['uid'] as $uid => $operm) {
            if (! isset($users[$uid])) {
                $udrop[] = $uid; // No longer set
            } else {
                $permissions = $users[$uid];
                $this->query('UPDATE '.$this->Tbl['share_item'].' SET '.
                        ' may_read="'.(isset($permissions['read']) && $permissions['read'] ? 1 : 0).'"'.
                        ',may_write="'.(isset($permissions['write']) && $permissions['write'] ? 1 : 0).'"'.
                        ',may_delete="'.(isset($permissions['delete']) && $permissions['delete'] ? 1 : 0).'"'.
                        ' WHERE handler="' . $this->esc($handler) . '" AND idx=' . intval($item).
                        ' AND uid=' . intval($uid)).
                        (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
            }
        }
        // Remove old
        $this->removeItem(null, $udrop);
        // Add new
        foreach ($users as $uid => $permissions) {
            if (! isset($oldshares['uid'][$uid])) { // New share
                $this->addItem($handler, $item, $owner, null, $uid, $permissions);
            }
        }
    }

    /**
     * Remove one ore more share(s) on an item given by uid(s) or gid(s).
     *
     * @param string $handler  Name of the handler
     * @param int $item  Item ID within given handler
     * @param null|array|int $groups Either one group or array to remove the share for
     * @param null|array|int $users Either oneuser or array to remove the share for
     */
    public function removeItem($handler, $item, $owner = null, $groups = null, $users = null)
    {
        $sql = 'DELETE FROM ' . $this->Tbl['share_item'].
                ' WHERE handler="' . $this->esc($handler) . '" AND idx=' . intval($item).
                (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
        if (!empty($groups)) {
            if (is_array($groups)) {
                foreach ($groups as $k => $gid) {
                    $groups[$k] = intval($gid);
                }
                $sql .= ' AND gid IN('.join(',', $groups).')';
            } else {
                $sql .= ' AND gid=' . intval($groups);
            }
        }
        if (!empty($users)) {
            if (is_array($users)) {
                foreach ($users as $k => $gid) {
                    $users[$k] = intval($gid);
                }
                $sql .= ' AND uid IN('.join(',', $users).')';
            } else {
                $sql .= ' AND uid=' . intval($users);
            }
        }
        return $this->query($sql);
    }

    /**
     * Retrieves the list of shared groups / users for a given folder in the given handler
     *
     * @param string $handler  Name of the handler
     * @param int $item  Item ID within given handler
     *[@param int $owner  Owner of the share; optional; Default: all for handler/folder]
     * @return array $permissions
     * @since 0.0.1
     */
    public function getItem($handler, $item, $owner = null)
    {
        $return = array('gid' => array(), 'uid' => array());
        $query = 'SELECT gid, uid, may_read, may_write, may_delete FROM '.$this->Tbl['share_item'].
                ' WHERE handler="' . $this->esc($handler) . '" AND idx=' . intval($item).
                (!empty($owner) ? ' AND `owner`='.intval($owner) : '');
        $qid = $this->query($query);
        while ($res = $this->assoc($qid)) {
            if (!is_null($res['gid'])) {
                $return['gid'][$res['gid']] = array(
                        'read' => $res['may_read'],
                        'write' => $res['may_write'],
                        'delete' => $res['may_delete']
                );
            }
            if (!is_null($res['uid'])) {
                $return['uid'][$res['uid']] = array(
                        'read' => $res['may_read'],
                        'write' => $res['may_write'],
                        'delete' => $res['may_delete']
                );
            }
        }
        return $return;
    }
}
