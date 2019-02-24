<?php
/**
 * Cron Job Master Controller
 * calls all scripts according to the cron tab definitions
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2001-2015 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.9mod3 2015-08-10
 */

@set_time_limit(0);
declare ( ticks = 1 );

define('_IN_PHM_', true);
define('_IN_PHM_CRON', true);
define('VECHO', false);
define('FORKABLE', function_exists('pcntl_fork'));
define('FORKLIMIT', 13); // Hard coded for now
// Which PHP version do we use?
if (!version_compare(phpversion(), '5.3.0', '>=')) {
    vecho('phlyMail requires PHP 5.3.0 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
    exit;
}

function handle_signal($signo)
{
    global $parent, $waitPIDs;

    switch ($signo) {
        case SIGTERM:
            if ($parent === 0) {
                // we are the parent
                foreach (array_keys($waitPIDs) as $key) {
                    if (function_exists('posix_kill')) {
                        posix_kill($key, SIGKILL);
                    }
                    $child = pcntl_waitpid(0, $status);
                    if ($child > 0) {
                        unset($waitPIDs[$child]);
                    }
                }
                exit;
            } else { // we are a child
                exit;
            }
            break;
    }
}
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, 'handle_signal');
}

chdir(__DIR__);
chdir('../');
// Load necessary files
$_PM_ = [];
foreach (['defaults.ini.php', 'choices.ini.php'] as $choices) {
    if (!file_exists($choices) || !is_readable($choices)) {
        continue;
    }
    $_PM_ = array_replace_recursive($_PM_, parse_ini_file($choices, true));
}
if (empty($_PM_)) {
    die('Error initializing core, defaults.ini.php not found?');
}
// Comaptibility layer
if (!version_compare(phpversion(), '6.0.0', '>=')) {
    require_once $_PM_['path']['lib'].'/compat.5.x.php';
}
require $_PM_['path']['lib'].'/init.backend.php';
if (!empty($_PM_['core']['debugging_level']) && $_PM_['core']['debugging_level'] != 'system') {
    Debug::off();
    if (isset($_PM_['core']['debugging_level']) && 'disabled' != $_PM_['core']['debugging_level']) {
        Debug::on();
    }
}
$waitPIDs = array();
$Cron = new DB_Controller_Cron();
$Cron->settimezone(PHM_TIMEZONE, PHM_UTCOFFSET);
$jobList = $Cron->getDueJobs(FORKABLE ? FORKLIMIT : null);
if (FORKABLE) {
    // Before forking the DB handle must be closed, since cloning it on fork
    // does not work (think about it, it can't!)
    $Cron->close();
}

$parent = 0;
foreach ($jobList as $job) {
        $className = 'cron_'.basename($job['handler']).'_'.basename($job['job']);
        $classPath = __DIR__.DIRECTORY_SEPARATOR.$className.'.php';
        if (!file_exists($classPath) || !is_readable($classPath)) {
        vecho($classPath.' is NOT readable! Skipping');
        continue;
    }
    $pid = 0;
    if (FORKABLE) {
        $pid = pcntl_fork();
    }
    if (0 === $pid) { // I am the child (or running in non-forking mode)
        $parent = function_exists('posix_getppid') ? posix_getppid() : 0;
        $me = function_exists('posix_getpid') ? posix_getpid() : getmypid();
        require_once $classPath;
        if (FORKABLE) {
            unset($DB);
            $DB = new DB_Base();
            $DB->settimezone(PHM_TIMEZONE, PHM_UTCOFFSET);

            $Cron = new DB_Controller_Cron(); // We forked, the DB handle didn't
        }
        $jobIdentifier = $className.(!is_null($job['item']) ? '_'.$job['item'] : '');
        process_set_title($jobIdentifier);

        vecho($jobIdentifier.' started');
        $Cron->markJobRunning($job['handler'], $job['job'], $job['item'], $me); // Mark running
        $instance = new $className($job);
        $instance->Run();
        unset($instance);
        $Cron->markJobDone($job['handler'], $job['job'], $job['item']); // Mark Done

        vecho($jobIdentifier.' finished');

        // This is important in forked mode: Exit the script, otherwise it would resume the outer loop
        if (FORKABLE) {
            exit();
        }
    } elseif (-1 == $pid) {
        vecho('Forking failed!');
        break;
    } else {
        $waitPIDs[$pid] = 1;
    }
}
// Clean up after ourselves
if (FORKABLE) {
    while (!empty($waitPIDs)) {
        $pid = pcntl_waitpid(-1, $status, WNOHANG);
        if ($pid > 0) {
            unset ($waitPIDs[$pid]);
        }
        usleep(100);
    }
}

// We are done
exit();

/**
 * Conditional echo -> Sends a line of text to STDOUT, if the constant VECHO
 * is set to true, otherwise it does nothing
 *
 * @param string $string  The text tou output
 */
function vecho($string = '')
{
    if ('file' === VECHO) {
        file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'cron_diag.txt', date('Ymd His').' '.$string.LF, FILE_APPEND);
    }
    if (true === VECHO) {
        echo $string.LF;
    }
}

/**
 * Abstraction, there's various ways ...
 *
 * @param string $title  Title of the process. Keep it short!
 */
function process_set_title($title)
{
    if (function_exists('cli_set_process_title')) {
        // As of PHP 5.5.0
        cli_set_process_title($title);
    } elseif (function_exists('setproctitle')) {
        // Classic via PECL, deprecated
        setproctitle($title);
    }
}