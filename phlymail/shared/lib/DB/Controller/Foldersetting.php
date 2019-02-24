<?php
/**
 * Boilerplate for a controller using a DB singleton
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2012 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.1 2012-05-02 
 */
class DB_Controller_Foldersetting extends DB_Controller
{
    /**
     * Constructor
     * Read the config and get an instance of the DB singleton
     */
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['user_foldersettings'] = $this->DB['db_pref'].'user_foldersettings';
    }

    /**
     * Set setting(s) for a folder
     *
     * @param string $hdl  Name of the handler, e.g. email
     * @param int $fid  ID of the folder
     * @param int $uid  ID of the user
     * @param string|array $key  Name of the key or array with key => val pairs
     *[@param mixed $val  A scalar value, an array, object or null if $key is an array]
     * @return unknown
     */
    public function foldersetting_set($hdl, $fid, $uid, $key, $val = null)
    {
        if (!is_null($val)) {
            $key = array($key => $val);
        }
        $sql = 'REPLACE INTO '.$this->Tbl['user_foldersettings'].' (`handler`,`uid`,`fid`,`key`,`val`) VALUES ';
        $i = 0;
        foreach ($key as $k => $v) {
            if ($i) $sql .= ',';
            if (is_array($v) || is_object($v) || is_bool($v)) {
                $v = serialize($v);
            }
            $sql .= '("'.$this->esc($hdl).'", '.intval($uid).', '.intval($fid).', "'.$this->esc($k).'", "'.$this->esc($v).'")';
            $i++;
        }
        return $this->query($sql);
    }

    /**
     * Get setting(s) for a folder
     *
     * @param string $hdl  Name of the handler, e.g. email
     * @param int $fid  ID of the folder
     * @param int $uid  ID of the user
     *[@param string $key  f given, only the value for that key is returned]
     * @return mixed
     */
    public function foldersetting_get($hdl, $fid, $uid, $key = null)
    {
        $return = array();
        $qh = $this->query('SELECT `key`, `val` FROM '.$this->Tbl['user_foldersettings'].' WHERE `handler`="'.$this->esc($hdl).'"'
                .' AND `uid`='.intval($uid).' AND `fid`='.intval($fid)
                .(!is_null($key) ? ' AND `key`="'.$this->esc($key).'"' : '')
                );
        if ($this->numrows($qh)) {
            while ($line = $this->assoc($qh)) {
                $line['unser'] = @unserialize($line['val']);
                $return[$line['key']] = ($line['unser'] !== unserialize(false)) ? $line['unser'] : $line['val'];
            }
        }
        if (!is_null($key)) {
            return (isset($return[$key]) ? $return[$key] : null);
        }
        return $return;
    }

    /**
     * Retrieve a list of folders, where a certain setting is applied. This might
     * be only a key (and all possible values) or a certain value for a certain
     * key.
     *
     * @param string $hdl  Name of the handler, e.g. email
     *[@param int $uid  ID of the user]
     * @param string $key  Name of the key
     *[@param string $val  The desired value; serialize it for non scalars]
     */
    public function foldersettings_find($hdl, $uid = null, $key, $val = null)
    {
        $return = array();
        $qh = $this->query('SELECT `fid` FROM '.$this->Tbl['user_foldersettings']
                .' WHERE `handler`="'.$this->esc($hdl).'"'
                .(!is_null($uid) ? ' AND `uid`='.intval($uid) : '')
                .' AND `key`="'.$this->esc($key).'"'
                .(!is_null($val) ? ' AND `val`="'.$this->esc($val).'"' : '')
                );
        if ($this->numrows($qh)) {
            while ($line = $this->assoc($qh)) {
                $return[] = $line['fid'];
            }
        }
        return $return;
    }

    /**
     * Drop more or less specific settings for folders
     *
     * @param string $hdl  Name of the handler, e.g. email
     *[@param int $fid  ID of the folder to delete the settings for]
     *[@param int $uid  ID of the user to delete the settings for]
     *[@param string $key  Name of the key, depends on handler]
     * @return bool
     */
    public function foldersetting_del($hdl, $fid = null, $uid = null, $key = null)
    {
        return $this->query('DELETE FROM '.$this->Tbl['user_foldersettings'].' WHERE `handler`="'.$this->esc($hdl).'"'
                .(!is_null($fid) ? ' AND `fid`='.intval($fid) : '')
                .(!is_null($uid) ? ' AND `uid`='.intval($uid) : '')
                .(!is_null($key) ? ' AND `key`="'.$this->esc($key).'"' : '')
                );
    }

}
