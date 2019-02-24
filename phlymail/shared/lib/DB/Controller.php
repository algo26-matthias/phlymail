<?php
/**
 * Boilerplate for a controller using a DB singleton
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.1.0 2015-10-04
 */
class DB_Controller
{
    // This holds all config options
    public $DB;
    public $Tbl;
    public $dbh;
    protected $mode = 'classic';
    public $features;
    protected $settings;

    /**
     * Constructor
     * Read the config and get an instance of the DB singleton
     */
    public function __construct()
    {
        global $_PM_;

        $Conf = $_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.ini.php';
        $secaccpass = isset($_PM_['core']['accountpass_security']) && $_PM_['core']['accountpass_security'] == 'cleartext' ? false : true;

        if (!file_exists($Conf)) {
            // Translate old CSV like mysql settings file over to the ini based one
            if (file_exists($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.conf.php')
                    && !file_exists($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.ini.php')) {
                if (!is_writable($_PM_['path']['conf'])) {
                    die('Please check permissions for '.$_PM_['path']['conf'].' and repeat!');
                }
                $newDB = array();
                foreach (file($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.conf.php') as $l) {
                    if ($l{0} == '#') {
                        continue;
                    }
                    if (substr($l, 0, 15) == '<?php die(); ?>') {
                        continue;
                    }
                    list ($k, $v) = explode(';;', $l);
                    $newDB[str_replace('mysql_', '', $k)] = trim($v);
                }
                $state = basics::save_config($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.ini.php', $newDB, true, $_PM_['core']['file_umask']);
                if ($state) {
                    @unlink($_PM_['path']['conf'].'/driver.'.$_PM_['core']['database'].'.conf.php');
                } else {
                    die('Could not convert old DB settings file into current format. Check permissions for '.$_PM_['path']['conf'].' and repeat!');
                }
            } else {
                die('Old configuration file not found');
            }
        }
        // Initialise database driver choices
        $this->DB = parse_ini_file($Conf);
        // Make sure, logging would be possible
        $this->settings['logpath'] = false;
        if (isset($GLOBALS['_PM_']['logging']['log_sql']) && $GLOBALS['_PM_']['logging']['log_sql']) {
            $logpath = $GLOBALS['_PM_']['path']['logging'].'/sql/'.preg_replace_callback('!\%(\w)!', create_function('$s', 'return date($s[1]);'), $GLOBALS['_PM_']['logging']['basename']);
            if (basics::create_dirtree(dirname($logpath))) {
                $this->settings['logpath'] = $logpath;
            }
        }
        $this->DB['secaccpass'] = $secaccpass;
        $this->DB['db_pref'] = '`'.$this->DB['database'].'`.'.$this->DB['prefix'].'_';
        $this->DB['utc_offset'] = defined('PHM_UTCOFFSET') ? PHM_UTCOFFSET : utc_offset();

        // Open Database connection
        $this->dbh = DB_Singleton::getInstance($this->DB);
    }

    /**
     * Interfacing methods to allow the handlers to use this DB connection
     */
    public function query($query)
    {
        if ($this->settings['logpath']) {
            file_put_contents($this->settings['logpath'], date('Y-m-d H:i:s').' ' .$query.LF, FILE_APPEND);
        }
        $qh = $this->dbh->query($query);
        if (false === $qh) {
            trigger_error($this->dbh->error(), E_USER_WARNING);
            trigger_error('Invalid SQL "'.$query.'"', E_USER_WARNING);
        }
        return $qh;
    }

    /**
     * Use with absolute care - closing the connection might kill every other
     * instance's connection at once!
     *
     * @return boolean
     */
    public function close()
    {
        return $this->dbh->close();
    }

    public function fetchrow($qid)
    {
        if (false == $qid) {
            return false;
        }
        return $this->dbh->fetchrow($qid);
    }
    public function fetchassoc($qid)
    {
        if (false == $qid) {
            return false;
        }
        return $this->dbh->fetchassoc($qid);
    }
    public function fetchobj($qid)
    {
        if (false == $qid) {
            return false;
        }
        return $this->dbh->fetchobj($qid);
    }
    public function affected()
    {
        return $this->dbh->affected();
    }
    public function numrows($qid)
    {
        if (false == $qid) {
            return false;
        }
        return $this->dbh->numrows($qid);
    }
    public function error()
    {
        return $this->dbh->error();
    }
    public function errno()
    {
        return $this->dbh->errno();
    }
    public function serverinfo()
    {
        return $this->dbh->serverinfo();
    }
    public function insertid()
    {
        return $this->dbh->insertid();
    }
    public function setcharset($set)
    {
        return $this->dbh->set_charset($set);
    }
    public function settimezone($tz)
    {
        $this->DB['utc_offset'] = $tz;
        return $this->dbh->set_timezone($tz);
    }
    public function ping()
    {
        if (!$this->dbh->ping()) {
            unset($this->dbh);
            $this->open();
        }
    }

    /**
     * Method used to escape passed data before building queries thus preventing SQL Injection Attacks.
     * This method takes care of the magic quotes setting.
     * @param  string  Unescaped string
     *[@param  resource  The connection handle of an open MySQL connection for obeying the current encoding]
     *[@param  string  Per default, non numeric values are surrounded by double quotes. Pass whatever you like]
     * @return string  Escaped string
     * @since 3.9.2
     */
    public function esc($value, $res = null, $q = '')
    {
    	if (get_magic_quotes_gpc()) {
            $value = stripslashes($value); // Stripslashes
        }
    	if (!is_numeric($value)) {
            return $q.$this->dbh->esc($value, null).$q; // Quote if not integer
        }
    	return $q.$value.$q;
    }

    // Shortcut
    public function assoc($qid)
    {
        return $this->dbh->fetchassoc($qid);
    }

    // Encrypt a string
    // Input:   confuse(string $data, string $key);
    // Returns: encrypted string
    public function confuse($data = '', $key = '')
    {
        $encoded = ''; $DataLen = strlen($data);
        if (strlen($key) < $DataLen) {
            $key = str_repeat($key, ceil($DataLen/strlen($key)));
        }
        for ($i = 0; $i < $DataLen; ++$i) {
            $encoded .= chr((ord($data{$i}) + ord($key{$i})) % 256);
        }
        return base64_encode($encoded);
    }

    // Decrypt a string
    // Input:   deconfuse(string $data, string $key);
    // Returns: decrypted String
    public function deconfuse($data = '', $key = '')
    {
        $data = base64_decode($data);
        $decoded = '';  $DataLen = strlen($data);
        if (strlen($key) < $DataLen) {
            $key = str_repeat($key, ceil($DataLen/strlen($key)));
        }
        for ($i = 0; $i < $DataLen; ++$i) {
            $decoded .= chr((256 + ord($data{$i}) - ord($key{$i})) % 256);
        }
        return $decoded;
    }
}
