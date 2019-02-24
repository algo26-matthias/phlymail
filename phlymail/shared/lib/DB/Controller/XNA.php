<?php
/**
 * XNA - eXternal No Auth requests
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.1 2013-02-01 
 */
class DB_Controller_XNA extends DB_Controller
{
    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['core_noauth'] = $this->DB['db_pref'].'core_noauth';
    }

    /**
     * Register an XNA
     *
     * @param string $handler  Name of the handler, required
     * @param string $load  Load argument, required
     *[@param string $action  Action argument, optional]
     *[@param string $uuid  UUID (if applicable), optional]
     * @return string  UUID on success, FALSE on failure
     */
    public function register($handler, $load, $action = '', $uuid = null)
    {
        if (is_null($uuid)) {
            $uuid = basics::uuid();
        }
        $query = 'INSERT '.$this->Tbl['core_noauth'].' SET `uuid`="'.$this->esc($uuid).'"'
                .',`handler`="'.$this->esc($handler).'", `load`="'.$this->esc($load).'"'
                .',`action`="'.$this->esc($action).'"';
        if ($this->query($query)) {
            return $uuid;
        }
        return false;
    }

    /**
     * Check, whether the given combination of poarameters already is registerted as XNA.
     * IF so, the UUID is returned
     *
     * @param string $handler  Name of the handler, required
     * @param string $load  Load argument, required
     *[@param string $action  Action argument, optional]
     * @return string  UUID on success, FALSE otherwise
     */
    public function registered($handler, $load, $action = '')
    {
        $query = 'SELECT `uuid` FROM '.$this->Tbl['core_noauth'].' WHERE '
                .'`handler`="'.$this->esc($handler).'" AND `load`="'.$this->esc($load).'"'
                .' AND `action`="'.$this->esc($action).'" LIMIT 1';
        $qid = $this->query($query);
        if (false === $qid || !$this->numrows($qid)) return false;
        $return = $this->assoc($qid);
        return $return['uuid'];
    }

    /**
     * Unregister XNA (delete from DB)
     *
     * @param string $uuid  UUID to identify request
     * @return bool
     */
    public function unregister($uuid)
    {
        return $this->query('DELETE FROM '.$this->Tbl['core_noauth'].' WHERE `uuid`="'.$this->esc($uuid).'"');
    }

    /**
     * Query details for an XNA
     *
     * @param string $uuid  UUID to identify request
     * @return array|false  Array on success, FALSE on failure
     */
    public function getUuid($uuid)
    {
        $qid = $this->query('SELECT * FROM '.$this->Tbl['core_noauth'].' WHERE `uuid`="'.$this->esc($uuid).'"');
        if (false === $qid || !$this->numrows($qid)) return false;
        return $this->assoc($qid);
    }
}
