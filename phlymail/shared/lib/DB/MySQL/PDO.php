<?php
/**
 * DB driver for PDO using MySQL driver
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.5 2015-04-16 
 */
class DB_MySQL_PDO extends PDO
{
    public function __construct($DB)
    {
        $port = $socket = null;
        if (false !== (strpos($DB['host'], ':'))) {
            list ($DB['host'], $socket) = explode(':', $DB['host'], 2);
            if (is_numeric($socket)) {
                $port = $socket;
                $socket = null;
            }
        }
        $DSN = 'mysql:dbname='.$DB['database'].';host='.$DB['host'];
        if (!empty($socket)) {
            $DSN .= ';unix_socket='.$socket;
        }
        if (!empty($port)) {
            $DSN .= ';port='.$port;
        }
        parent::__construct($DSN, $DB['user'], $DB['pass'], !empty($DB['charset']) ? array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$DB['charset']) : null);
        if (!empty($DB['utc_offset'])) {
            $this->query('SET TIME_ZONE="'.$this->esc($DB['utc_offset']).'"');
        }
    }

    public function fetchrow($qid) { return $qid->fetch(PDO::FETCH_NUM); }
    public function fetchassoc($qid) { return $qid->fetch(PDO::FETCH_ASSOC); }
    public function fetchobj($qid) { return $qid->fetch_object(); }
    public function affected() { return null; }
    public function numrows($qid) { return $qid->rowCount(); }
    public function error()
    {
        $err = $this->errorInfo();
        return $err[2]; // Prepending [0] would even reveal the ANSI SQL error code
    }
    public function errno($ansi = false)
    {
        $err = $this->errorInfo();
        return ($ansi) ? $err[0] : $err[1]; // Prepending [0] would even reveal the ANSI SQL error code
    }
    public function serverinfo() { return $this->getAttribute(PDO::ATTR_SERVER_INFO); }
    public function insertid() { return $this->lastInsertId(); }
    public function esc($value) { return $this->quote($value); }
    public function set_charset($charset)
    {
        return $this->query('SET NAMES "'.$this->esc($charset).'"');
    }
    public function set_timezone($tz)
    {
        return $this->query('SET TIME_ZONE="'.$this->esc($tz).'"');
    }
}
