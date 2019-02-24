<?php
/**
 * @package phlyGallery 1.0
 * @copyright 2010-2011 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.1 2011-03-12
 */
abstract class Tree_Node
{
    public $created;
    public $changed;
    public $deleted;

    public $name;
    public $owner;
    public $childof;
    public $active;

    protected static $DB;
    protected static $Table;

    protected function __construct(string $table, int $id = null)
    {
        $a_cred = $GLOBALS['a_db_credentials'][$a_db[0]];
        self::$DB = DB_Singleton::getInstance($a_cred);
        self::$Table = $a_cred['db_prefix'].'_'.$table;

        if (!is_null($id)) {
            $this->get($id);
        }
    }

    public function get(int $id)
    {
        $res = self::$DB->query('SELECT * FROM '.self::$Table.' WHERE `id`='.intval($id));
        if ($record = self::$DB->assoc($res)) {
            var_dump($record);
        } else {
            throw new Exception('Could not retreive node '.$id.' of type '.self::$Table, 100);
        }
    }
}
?>