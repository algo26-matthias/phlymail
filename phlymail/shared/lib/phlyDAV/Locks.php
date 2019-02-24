<?php
/**
 * extending functionality for SabreDAV
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.3 2015-03-12 
 */
class phlyDAV_Locks extends Sabre_DAV_Locks_Backend_Abstract {

    /**
     * The DB connection object
     *
     * @var $DB
     */
    protected $DB;

    public function __construct()
    {
        $this->DB = &$GLOBALS['DB'];
        $this->DB->Tbl['core_lock'] = '`'.$this->DB->DB['database'].'`.`'.$this->DB->DB['prefix'].'_core_lock`';
    }

    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    public function getLocks($uri, $returnChildLocks)
    {
        $query = 'SELECT `owner`,`token`,`timeout`,`created`,`scope`,`depth`,`uri` FROM '. $this->DB->Tbl['core_lock']
                .' WHERE ((`created` + `timeout`) > CAST('.time().' AS UNSIGNED INTEGER)) AND ((`uri`="'.$this->DB->esc($uri).'")';
        // We need to check locks for every part in the uri.
        $uriParts = explode('/', $uri);
        // We already covered the last part of the uri
        array_pop($uriParts);

        $currentPath = '';
        foreach ($uriParts as $part) {
            if ($currentPath) {
                $currentPath .= '/';
            }
            $currentPath .= $part;
            $query.=' OR (`depth`!=0 AND `uri`="'.$this->DB->esc($currentPath).'")';
        }
        if ($returnChildLocks) {
            $query .= ' OR (`uri` LIKE "'.$this->DB->esc($uri).'/%")';
        }
        $query .= ')';

        $qid = $this->DB->query($query);
        $lockList = array();
        while ($row = $this->DB->fetchassoc($qid)) {
            $lockInfo = new Sabre_DAV_Locks_LockInfo();
            foreach ($row as $k => $v) {
                $lockInfo->$k = $v;
            }
            $lockList[] = $lockInfo;
        }
        return $lockList;
    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri, Sabre_DAV_Locks_LockInfo $lockInfo)
    {
        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 30*60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getLocks($uri, false);
        $exists = false;
        foreach ($locks as $lock) {
            if ($lock->token == $lockInfo->token) {
                $exists = true;
            }
        }
        if ($exists) {
            $query = 'UPDATE '. $this->DB->Tbl['core_lock']
                .' SET `owner`="'.$this->DB->esc($lockInfo->owner).'"'
                .',`timeout`='.intval($lockInfo->timeout)
                .',`scope`='.intval($lockInfo->scope)
                .',`depth`='.intval($lockInfo->depth)
                .',`uri`="'.$this->DB->esc($uri).'"'
                .',`created`='.intval($lockInfo->created)
                .' WHERE `token`="'.$this->DB->esc($lockInfo->token).'"';
        } else {
            $query = 'INSERT INTO '. $this->DB->Tbl['core_lock']
                .' SET `owner`="'.$this->DB->esc($lockInfo->owner).'"'
                .',`timeout`='.intval($lockInfo->timeout)
                .',`scope`='.intval($lockInfo->scope)
                .',`depth`='.intval($lockInfo->depth)
                .',`uri`="'.$this->DB->esc($uri).'"'
                .',`created`='.intval($lockInfo->created)
                .',`token`="'.$this->DB->esc($lockInfo->token).'"';
        }
        return $this->DB->query($query);
    }

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function unlock($uri, Sabre_DAV_Locks_LockInfo $lockInfo)
    {
        $query = 'DELETE FROM '. $this->DB->Tbl['core_lock'].' WHERE `uri`="'.$this->DB->esc($uri).'" AND `token`="'.$this->DB->esc($lockInfo->token).'"';
        return $this->DB->query($query);
    }
}
