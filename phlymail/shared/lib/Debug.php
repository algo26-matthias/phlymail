<?php
/**
 * Small shorthand for switching visible errors on / off during development or debugging.
 *
 * @author Matthias Sommerfeld
 * @copyright 2012-2015 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.4 2015-04-21 
 */
class Debug
{
    public static function on()
    {
        $GLOBALS['_PM_']['core']['debugging_is_on'] = 1;
        // enforce error reporting and displaying / logginh errors
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
        ini_set('log_errors', 'On');
    }

    public static function off()
    {
        $GLOBALS['_PM_']['core']['debugging_is_on'] = 0;
        //error_reporting(0); # no longer override the server's setting!
        ini_set('display_errors', 'Off');
    }
}
