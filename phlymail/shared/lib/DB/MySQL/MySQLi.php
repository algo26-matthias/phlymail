<?php
/**
 * DB driver for the classic mysql_*() functions of PHP
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.4 2015-04-16 
 */
class DB_MySQL_MySQLi extends mysqli
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
        parent::__construct($DB['host'], $DB['user'], $DB['pass'], $DB['database'], $port, $socket);

        if (!empty($DB['charset'])) {
            parent::set_charset($DB['charset']);
        }
        if (!empty($DB['utc_offset'])) {
            $this->query('SET TIME_ZONE="'.$this->esc($DB['utc_offset']).'"');
        }
    }

    public function fetchrow($qid) { return $qid->fetch_row(); }
    public function fetchassoc($qid) { return $qid->fetch_assoc(); }
    public function fetchobj($qid) { return $qid->fetch_object(); }
    public function affected() { return $this->affected_rows; }
    public function numrows($qid) { return $qid->num_rows; }
    public function error() { return $this->error; }
    public function errno() { return $this->errno; }
    public function serverinfo() { return $this->server_info; }
    public function set_timezone($tz)
    {
        return $this->query('SET TIME_ZONE="'.$this->esc($tz).'"');
    }
    public function insertid() { return $this->insert_id; }
    public function esc($value) { return $this->real_escape_string($value); }
}
