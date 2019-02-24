<?php
/**
 * Static logging class using filesystem
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Logging
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.4 2012-12-16 
 */
// Necessary for using syslog
define_syslog_variables();

class Log_FS {
    /**
     * Logger method
     *
     * write given message into filesyste,
     *
     * @param int Logging level - everything of lower prio then the defined loglevel gets silently ignored
     * @param string Log message  One line only please
     * @param string identifier; E.g. sysauth, sql, ...
     * @param string subidentifer; E.g. calendar, email, system, ...
     * @return void
     * @since 0.0.1
     */
    static public function write($level = LOG_NOTICE, $msg = '', $ident = 'sql', $subident = 'system')
    {
        // Check for global logging setting - if the level of the current logging
        // message indicates higher importance (or the same) as the defined logging
        // level we will write the message, else we will silently drop it
        if ($level > LOGLEVEL) return;

        $logpath = $GLOBALS['_PM_']['path']['logging'].'/'.basename(trim($ident)).'/'.preg_replace_callback('!\%(\w)!', create_function ('$s', 'return date($s[1]);'), $GLOBALS['_PM_']['logging']['basename']);
        basics::create_dirtree(dirname($logpath));
        file_put_contents($logpath, date('Y-m-d H:i:s ').$subident.' '.$msg.LF, FILE_APPEND);
    }
}
