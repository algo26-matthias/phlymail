<?php
/**
 * Static logging class using syslog service
 * Requires you to have the relevant extensions available
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Logging
 * @copyright 2004-2009 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.5 2009-11-14
 */
// Necessary for using syslog
define_syslog_variables();

class Log_Syslog {
    // Def
    static $log_open = false;
    static $log_levels;
    static $log_ident = false;

    /**
     * Logger method
     *
     * write given message into Syslog
     *
     * @param int Logging level - everything of lower prio then the defined loglevel gets silently ignored
     * @param string Log message  One line only please
     *[@param string identifier; E.g. sysauth, sql, ...]
     *[@param string subidentifer; E.g. calendar, email, system, ...]
     * @since 0.0.1
     */
    static public function write($level = LOG_NOTICE, $msg = '', $ident = 'misc', $subident = null)
    {
        // Check for global logging setting - if the level of the current logging
        // message indicates higher importance (or the same) as the defined logging
        // level we will write the message, else we will silently drop it
        if ($level > LOGLEVEL) return;
        $ident .= (!is_null($subident)) ? '_'.$subident : '';
        if (!self::$log_open) {
            // INIT
            openlog('phlymail_'.$ident, LOG_PID | LOG_ODELAY, LOG_LOCAL0);
            self::$log_ident = $ident;
            self::$log_open = true;
            self::$log_levels = array
                    (LOG_DEBUG   => '[DEBUG]'     // lowest priority
                    ,LOG_INFO    => '[INFO]'
                    ,LOG_NOTICE  => '[NOTICE]'
                    ,LOG_WARNING => '[WARNING]'
                    ,LOG_ERR     => '[ERROR]'
                    ,LOG_CRIT    => '[CRITICAL]'
                    ,LOG_ALERT   => '[ALERT]'
                    ,LOG_EMERG   => '[EMERGENCY]' // highest priority
                    );
        }
        // If the identifier changes, we unfortunately have to reopen the syslog...
        if (self::$log_ident && self::$log_ident != $ident) {
            self::$log_ident = $ident;
            closelog();
            openlog('phlymail_'.$ident, LOG_PID | LOG_ODELAY, LOG_LOCAL0);
        }

        $level_msg = self::$log_levels[$level];
        syslog($level, $level_msg.' '.$msg);
    }
}
