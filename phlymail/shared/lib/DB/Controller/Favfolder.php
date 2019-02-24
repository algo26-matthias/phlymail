<?php
/**
 * Favourite foldersw
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.1 2013-01-30 
 */
class DB_Controller_Favfolder extends DB_Controller
{
    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['user_favfolders'] = $this->DB['db_pref'].'user_favouritefolders';
    }


    /**
     * Retrieves the list of favourite folders for a given user
     *
     * @param int $uid
     * @return array
     */
    public function getList($uid)
    {
        $return = array();
        $qid = $this->query('SELECT `handler`,`fid` FROM '.$this->Tbl['user_favfolders'].' WHERE `uid`='.intval($uid)
                .' ORDER BY `order` ASC, `ffid` ASC');
        while ($res = $this->assoc($qid)) {
            if ($res['fid'] == 0) $res['fid'] = 'root';
            $return[] = $res;
        }
        return $return;
    }

    /**
     * Adds a favourite folder for a given user
     *
     * @param int $uid ID of the user
     * @param string $handler Name of the handler the folder belongs to
     * @param int $fid ID of the folder within the handler
     * @return boolean
     */
    public function add($uid, $handler, $fid)
    {
        $order = 0;
        $qid = $this->query('SELECT MAX(`order`) maxorder FROM '.$this->Tbl['user_favfolders'].' WHERE `uid`='.intval($uid));
        if ($qid) {
            $line = $this->assoc($qid);
            if (isset($line['maxorder'])) $order = $line['maxorder'];
        }
        return $this->query('REPLACE INTO '.$this->Tbl['user_favfolders'].' SET `uid`='.intval($uid)
                .',`handler`="'.$this->esc($handler).'",`fid`='.intval($fid).',`order`='.intval($order+1));
    }

    /**
     * Removes a favourite folder for a given user
     *
     * @param int $uid ID of the user
     * @param string $handler Name of the handler the folder belongs to
     * @param int $fid ID of the folder within the handler
     * @return boolean
     */
    public function drop($uid, $handler, $fid)
    {
        return $this->query('DELETE FROM '.$this->Tbl['user_favfolders'].' WHERE `uid`='.intval($uid)
                .' AND `handler`="'.$this->esc($handler).'" AND `fid`='.intval($fid));
    }


    /**
     * Takes an array as argument, where the order is contained
     *
     * @param int $uid ID of the user
     * @param array $input Key: Account ID, Value: position in list
     * @return bool
     */
    public function reorder($uid, $input)
    {
        $uid = intval($uid);
        foreach ($input as $k => $v) {
            list ($handler, $fid) = explode('_', $k);
            if ($fid == 'root') $fid = 0; // Reverse mapping
            $this->query('UPDATE '.$this->Tbl['user_favfolders'].' SET `order`='.($v).' WHERE `uid`='.$uid
                    .' AND `handler`="'.$this->esc($handler).'" AND `fid`='.intval($fid));
        }
    }
}
