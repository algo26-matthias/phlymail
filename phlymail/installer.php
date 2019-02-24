<?php
/**
 * phlyMail 4 (Nahariya engine) Installation Script
 * @package phlyMail Nahariya 4.0+, Branch Lite
 * @subpackage Installation procedure
 * @copyright 2002-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.7mod2 2016-02-09
 */

/* ---- MAKE SURE TO DELETE THIS SCRIPT IMMEDIATELY AFTER INSTALLATION! ---- */

// Which PHP version do we use?
if (!version_compare(phpversion(), '5.4.0', '>=')) {
    header('Content-Type: text/plain; charset=utf-8');
    die('phlyMail requires PHP 5.4.0 or higher, you are running '.phpversion().'.'.LF.'Please upgrade your PHP');
}

define('CRLF', "\r\n");
define('LF', "\n");
define('_IN_PHM_', true);
define('_IN_INSTALLER_', true);
if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
	define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
} else {
	define('PHP_SELF', $_SERVER['PHP_SELF']);
}
@set_include_path(__DIR__);
// Do not use cookies for session management
@ini_set('session.use_cookies', 'Off');
@ini_set('url_rewriter.tags', '');

// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

session_start();
// Spracheinstellung vermerken
if (isset($_REQUEST['WPInstLang'])) {
    $_SESSION['WPInstLang'] = ('en' == $_REQUEST['WPInstLang']) ? $_REQUEST['WPInstLang'] : 'de';
} elseif (!isset($_SESSION['WPInstLang']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $_SESSION['WPInstLang'] = (substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) == 'de') ? 'de' : 'en';
} elseif (!isset($_SESSION['WPInstLang'])) {
    $_SESSION['WPInstLang'] = 'de';
}
$WPInstLang = $_SESSION['WPInstLang'];
$error = false;
// Load required files
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
require_once($_PM_['path']['lib'].'/autoload.php');
require_once($_PM_['path']['lib'].'/functions.php');
if (!file_exists($_PM_['path']['storage'].'/tplcache')) {
    basics::create_dirtree($_PM_['path']['storage'].'/tplcache', 0777);
}

require_once(__DIR__.'/install/messages.'.$WPInstLang.'.php');
// Timezone
if (isset($_PM_['core']['timezone'])) {
    define('PHM_TIMEZONE', $_PM_['core']['timezone']);
    date_default_timezone_set($_PM_['core']['timezone']);
} elseif (isset($WP_msg['tz'])) {
    define('PHM_TIMEZONE', $WP_msg['tz']);
    date_default_timezone_set($WP_msg['tz']);
} else {
    define('PHM_TIMEZONE', date_default_timezone_get());
}
define('PHM_UTCOFFSET', utc_offset());
// AJAX request on changing DB driver
if (!empty($_REQUEST['changedriver'])) {
    header('Content-Type: text/html; charset="utf-8"');
    $drivername = preg_replace('![^a-zA-Z_0-9]!', '', $_REQUEST['changedriver']);
    if (file_exists($_PM_['path']['driver'] . '/' . $drivername . '/setup.php')) {
        $_PM_['tmp']['driver_dir'] = $_PM_['path']['driver'] . '/' . $_PM_['core']['database'];
        require_once ($_PM_['path']['driver'] . '/' . $drivername . '/setup.php');
        echo $conf_output;
    } else {
        echo '';
    }
    exit;
}

// Storing, what we've got
if (!empty($_REQUEST['save'])) {
    // Basic settings
    $tokens = array(
            'language' => 'language',
            'theme_name' => 'skin',
            'database' => 'database',
            'send_method' => 'send_method',
            'sendmail' => 'sendmail'
            );
    $tokval['core'] = array();
    foreach ($tokens as $k => $v) {
        if (!isset($_REQUEST[$v])) {
            continue;
        }
        $tokval['core'][$k] = basename($_REQUEST[$v]);
    }
    // Generate system salt, DB needs it below
    $tokval['auth']['system_salt'] = uniqid();

    $res = basics::save_config('choices.ini.php', $tokval);
    if (!$res) {
        $error .= $WP_msg['NotUpdateConfF'] . ' choices.ini.php';
    }
    // Config setting(s)
    $suf = fopen($_PM_['path']['conf'] . '/config.choices.ini.php', 'w');
    $GlChFile = ';<?php die(); ?>'.LF.'language = "'.basename($_REQUEST['language_conf']).'"' . LF;
    if ($suf) {
        fputs($suf, $GlChFile);
        fclose($suf);
        chmod($_PM_['path']['conf'] . '/config.choices.ini.php', 0755);
    } else {
        $error .= $WP_msg['NotUpdateConfF'] . ' ' . $_PM_['path']['conf'] . '/config.choices.ini.php';
    }
    // Reload configuration (!)
    foreach (['defaults.ini.php', 'choices.ini.php'] as $choices) {
        if (!file_exists($choices) || !is_readable($choices)) {
            continue;
        }
        $_PM_ = array_replace_recursive($_PM_, parse_ini_file($choices, true));
    }
    // Tells both the setup and the installation module of the DB driver what to do
    $WP_DBset_action = 'do';
    // DB settings
    if (file_exists($_PM_['path']['driver'] . '/' . $_PM_['core']['database'] . '/setup.php')
            && is_readable($_PM_['path']['driver'] . '/' . $_PM_['core']['database'] . '/setup.php')) {
        $_PM_['tmp']['driver_dir'] = $_PM_['path']['driver'] . '/' . $_PM_['core']['database'];
        require $_PM_['tmp']['driver_dir'] . '/setup.php';
    }
    $DB = new DB_Admin();
    if (!$DB) {
        $error .= $WP_msg['NoOpenDB'];
    } else {
        // Install tables
        require_once('runonce.php');
        // Create admin user
        $DB->add_admin(array(
                'username' => $_REQUEST['admin_name'],
                'salt' => $_PM_['auth']['system_salt'],
                'password' => $_REQUEST['admin_pw_1'],
                'is_root' => 'yes',
                'active' => '1',
                'email' => '',
                'choices' => ''
                ));
    }

    if (!$error) {
        $DB->settimezone(PHM_TIMEZONE, PHM_UTCOFFSET);
        $_PM_['handlers'] = parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php');
        $d_ = opendir($_PM_['path']['handler']);
        // Make sure, all not yet covered handlers get to install themselves
        while (false !== ($handler = readdir($d_))) {
            if ($handler == '.' || $handler == '..') {
                continue;
            }
            if (isset($_PM_['handlers'][$handler]) && $_PM_['handlers'][$handler]) {
                continue;
            }
            if (file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
                $call = 'handler_'.$handler.'_configapi';
                $API = new $call($_PM_, $firstUID);
                if (in_array('handler_install', get_class_methods($call))) {
                    $state = $API->handler_install();
                }
                if (in_array('create_user', get_class_methods($call))) {
                    $state = $API->create_user();
                }
                unset($API);
            }
            $_PM_['handlers'][$handler] = 1;
        }
        basics::save_config($_PM_['path']['conf'].'/active_handlers.ini.php', $_PM_['handlers']);
        // END handler installation

        $tpl = new phlyTemplate('install/install.done.tpl');
        foreach (array(
                'install/phlymail.png',
                'install/messages.de.php',
                'install/messages.en.php',
                'install/installer.tpl',
                'install/install.done.tpl') as $kill) {
            @unlink($kill);
        }
        @rmdir('install');
        $nogo = 0;
        if (file_exists('install') && is_dir('install')) {
            $nogo = 1;
            $tpl->assign_block('dirfail');
        } else {
            $tpl->assign_block('dirfine');
        }
        if (!@unlink(basename(__FILE__))) {
            $nogo = 1;
            $tpl->assign_block('myfail');
        } else {
            $tpl->assign_block('myfine');
        }
        if ($nogo == 1) {
            $tpl->assign_block('removemanually');
        }
        $myfile = basename(PHP_SELF);
        $tpl->assign('file_ext', substr($myfile, strrpos($myfile, '.') + 1));

        $tpl->display();

        exit;
    }
}

$tpl = new phlyTemplate('install/installer.tpl');

$permError = false;
if (!@touch($_PM_['path']['storage'] . '/install.test', 0777) || ! @unlink($_PM_['path']['storage'] . '/install.test')) {
    $permError = true;
}
if (!@touch('install/install.test', 0777) || ! @unlink('install/install.test')) {
    $permError = true;
}
if ($permError) {
    $probs = str_replace(
            array('$1', '$2', '$3', '$4'),
            array(getmyuid(), getmygid(), __DIR__, get_current_user()),
            $WP_msg['AccessBlock']
            );
    $tpl->fill_block('access', 'Probs', $probs);
}

if ($error) {
    $tpl->fill_block('generic_error', 'error', $error);
}
$d_ = opendir($_PM_['path']['driver']);
while (false !== ($drivername = readdir($d_))) {
    if ($drivername == '.' || $drivername == '..') {
        continue;
    }
    if (!is_readable($_PM_['path']['driver'] . '/' . $drivername . '/setup.php')) {
        continue;
    }
    $friendlyName = $drivername;
    if (file_exists($_PM_['path']['driver'] . '/' . $drivername . '/driver.name')) {
        $friendlyName = file_get_contents($_PM_['path']['driver'] . '/' . $drivername . '/driver.name');
    }
    $drivers[$drivername] = $friendlyName;
    if (empty($_PM_['core']['database'])) {
        $_PM_['core']['database'] = $drivername;
    }

    if (!empty($_PM_['core']['database']) && $_PM_['core']['database'] == $drivername) {
        $_PM_['tmp']['driver_dir'] = $_PM_['path']['driver'] . '/' . $_PM_['core']['database'];
        require $_PM_['path']['driver'] . '/' . $drivername . '/setup.php';
    }
}
closedir($d_);
ksort($drivers);
$t_d = $tpl->get_block('driverline');
foreach ($drivers as $drivername => $friendlyName) {
    $t_d->assign(array('key' => $drivername, 'drivername' => $friendlyName));
    if ($drivername == $_PM_['core']['database']) {
        $t_d->assign_block('sel');
    }
    $tpl->assign('driverline', $t_d);
    $t_d->clear();
}

$d_ = opendir($_PM_['path']['message']);
while (false !== ($langname = readdir($d_))) {
    if ($langname == '.' || $langname == '..') {
        continue;
    }
    if (!preg_match('!\.php$!i', trim($langname))) {
        continue;
    }
    preg_match(
            '!\$WP_msg\[\'language_name\'\]\ \=\ \'([^\']+)\'!',
            file_get_contents($_PM_['path']['message'].'/'.$langname),
            $found
            );
    $langname = preg_replace('/\.php$/i', '', trim($langname));
    $langnames[$langname] = $found[1];
}
closedir($d_);
ksort($langnames);
$t_d = $tpl->get_block('langline');
foreach ($langnames as $id => $langname) {
    $t_d->assign(array('key' => $id, 'langname' => $langname));
    if ($id == $_PM_['core']['language']) {
        $t_d->assign_block('sel');
    }
    $tpl->assign('langline', $t_d);
    $t_d->clear();
}
$langnames = array();
$d_ = opendir($_PM_['path']['admin'] . '/messages/');
while (false !== ($langname = readdir($d_))) {
    if ($langname == '.' || $langname == '..') {
        continue;
    }
    if (!preg_match('!\.php$!i', trim($langname))) {
        continue;
    }
    preg_match(
            '!\$WP_msg\[\'language_name\'\]\ \=\ \'([^\']+)\'!',
            file_get_contents($_PM_['path']['admin'] . '/messages/'.$langname),
            $found
            );
    $langname = preg_replace('/\.php$/i', '', trim($langname));
    $langnames[$langname] = $found[1];
}
closedir($d_);
ksort($langnames);
$t_d = $tpl->get_block('langconfline');
foreach ($langnames as $id => $langname) {
    $t_d->assign(array('key' => $id, 'langname' => $langname));
    if ($id == $_PM_['core']['language']) {
        $t_d->assign_block('sel');
    }
    $tpl->assign('langconfline', $t_d);
    $t_d->clear();
}

$d_ = opendir($_PM_['path']['theme']);
$themeEngine = trim(file_get_contents($_PM_['path']['conf'] . '/theme.engine'));
while (false !== ($skinname = readdir($d_))) {
    if ($skinname == '.' || $skinname == '..') {
        continue;
    }
    if (! is_dir($_PM_['path']['theme'].'/'.$skinname)) {
        continue;
    }
    if (! file_exists($_PM_['path']['theme'].'/'.$skinname.'/main.tpl')) {
        continue;
    }
    if (! file_exists($_PM_['path']['theme'].'/'.$skinname.'/choices.ini.php')) {
        continue;
    }
    // Read theme's chocies
    $thChoi = parse_ini_file($_PM_['path']['theme'].'/'.$skinname.'/choices.ini.php');
    if (empty($thChoi['client_type']) || $thChoi['client_type'] != 'desktop') {
        continue;
    }
    if (! isset($thChoi['engine']) || $thChoi['engine'] != $themeEngine) {
        continue; // This theme ought to be imcompatible
    }
    $skins[] = $skinname;
}
closedir($d_);
sort($skins);
$t_d = $tpl->get_block('skinline');
foreach ($skins as $skinname) {
    $t_d->assign('skinname', $skinname);
    if ($skinname == $_PM_['core']['theme_name']) {
        $t_d->assign_block('sel');
    }
    $tpl->assign('skinline', $t_d);
    $t_d->clear();
}

$tpl->assign(array(
        'form_target' => PHP_SELF . '?' . session_name() . '=' . session_id() . '&amp;save=1',
        'link_english' => PHP_SELF . '?' . session_name() . '=' . session_id() . '&amp;WPInstLang=en',
        'link_german' => PHP_SELF . '?' . session_name() . '=' . session_id() . '&amp;WPInstLang=de',
        'db_driver_specific' => !empty($conf_output) ? $conf_output : '',
        'admin_name' => !empty($_REQUEST['admin_name']) ? htmlentities($_REQUEST['admin_name'], null, 'utf-8') : '',
        'admin_pw_1' => !empty($_REQUEST['admin_pw_1']) ? htmlentities($_REQUEST['admin_pw_1'], null, 'utf-8') : '',
        'admin_pw_2' => !empty($_REQUEST['admin_pw_2']) ? htmlentities($_REQUEST['admin_pw_2'], null, 'utf-8') : '',
        'user_name' => !empty($_REQUEST['user_name']) ? htmlentities($_REQUEST['user_name'], null, 'utf-8') : '',
        'user_pw_1' => !empty($_REQUEST['user_pw_1']) ? htmlentities($_REQUEST['user_pw_1'], null, 'utf-8') : '',
        'user_pw_2' => !empty($_REQUEST['user_pw_2']) ? htmlentities($_REQUEST['user_pw_2'], null, 'utf-8') : ''
        ));

header('Content-Type: text/html; charset=UTF-8');
$tpl->display();
