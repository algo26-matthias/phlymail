<?php
/**
 * Factory for DB connections
 * Should be forkable, since it takes the process ID into account
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2012 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.2 2012-06-01 
 */
class DB_Singleton
{
    private static $instance = array();

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(array $cred)
    {
        // Key identifies connection
        $key = md5(json_encode($cred).getmypid());
        if (empty(self::$instance[$key])) {
            // Use the right type according to available features
            $driver = false;
            if (function_exists('mysqli_connect')) { // MySQLi support is best
                $driver = 'DB_MySQL_MySQLi';
            }
            if (false === $driver) {
                if (class_exists('PDO', false) && method_exists('PDO', 'getAvailableDrivers')) {
                    $pdo_drivers = PDO::getAvailableDrivers();
                    if (in_array('mysql', $pdo_drivers)) {
                        $driver = 'DB_MySQL_PDO';
                    }
                }
            }
            if (false === $driver && function_exists('mysql_connect')) {
                $driver = 'DB_MySQL_MySQL';
            }
            if (false !== $driver) {
                self::$instance[$key] = new $driver($cred);
            } else {
                throw new Exception('No suitable MySQL driver available');
            }
        }
        // Return the instance
        return self::$instance[$key];
    }
}
