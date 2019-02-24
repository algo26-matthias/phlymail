<?php
/**
 * DB driver for the classic mysql_*() functions of PHP
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.5 2015-04-16 
 */
class DB_MySQL_MySQL
{
    public $dbh;

    public function __construct($DB)
    {
        $dbh = mysql_connect($DB['host'], $DB['user'], $DB['pass']);
        if (is_resource($dbh)) {
            $this->dbh = $dbh;
            if (!empty($DB['database'])) {
                mysql_select_db($DB['database'], $dbh);
            }
            if (!empty($DB['charset'])) {
                mysql_set_charset($DB['charset'], $dbh);
            }
            if (!empty($DB['utc_offset'])) {
                $this->query('SET TIME_ZONE="'.$this->esc($DB['utc_offset']).'"');
            }
        }
    }

    public function close()
    {
        return mysql_close($this->dbh);
    }

    public function query($query)
    {
        return mysql_query($query, $this->dbh);
    }

    public function fetchrow($qid)
    {
        return mysql_fetch_row($qid);
    }

    public function fetchassoc($qid)
    {
        return mysql_fetch_assoc($qid);
    }

    public function fetchobj($qid)
    {
        return mysql_fetch_object($qid);
    }

    public function affected()
    {
        return mysql_affected_rows($this->dbh);
    }

    public function numrows($qid)
    {
        return mysql_num_rows($qid);
    }

    public function error()
    {
        return mysql_error($this->dbh);
    }

    public function errno()
    {
        return mysql_errno($this->dbh);
    }

    public function serverinfo()
    {
        return mysql_get_server_info();
    }

    public function ping()
    {
        return mysql_ping();
    }

    public function set_charset($charset)
    {
        return mysql_set_charset($charset);
    }
    public function set_timezone($tz)
    {
        return $this->query('SET TIME_ZONE="'.$this->esc($tz).'"');
    }

    // Since it does not use the bulit in function it is safe against overflow
    // when using BIGINT columns for the auto_increment field.
    public function insertid()
    {
        list ($return) = $this->fetchrow($this->query('SELECT LAST_INSERT_ID()'));
        return $return;
    }

    public function esc($value)
    {
        return mysql_real_escape_string($value, $this->dbh);
    }
}
