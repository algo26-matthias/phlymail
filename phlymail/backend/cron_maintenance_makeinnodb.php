<?php
/**
 * Transfer the "engine" of the DB tables over to InnoDB from the too old MyISAM
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2001-2012 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.1 2012-09-12 
 */
class cron_maintenance_makeinnodb
{
    public function __construct($cronjob)
    {
        $this->job   = $cronjob;
        $this->_PM_  = $_PM_ = &$GLOBALS['_PM_'];
    }

    public function Run()
    {
        $TS = new DB_Controller_TableStructure();
        $orig_struct = $TS->get();
        $add = array();
        $alter = array();
        $sqls = array();
        foreach ($orig_struct as $tbl => $def) {
            if ($def['engine'] != 'InnoDB') {
                $sqls[] = 'ALTER TABLE {prefix}'.$tbl.' ENGINE=InnoDB';
            }
        }
        $TS->update($add, $alter, $sqls);
    }
}
