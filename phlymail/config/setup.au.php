<?php
/**
 * AutoUpdate Module [supports transparent GZip / BZip2]
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2002-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0 2016-11-08
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : false;
$CurrBuild = trim(file_get_contents($_PM_['path']['conf'].'/current.build'));
$relstatus = (isset($_REQUEST['relstatus']) && $_REQUEST['relstatus'] == 'beta') ? 'beta' : 'stable';

// Prüfe auf passende Rechte (es sei denn, das Frontend bindet uns ein, um auf Updates zu checken)
if (!defined('FE_CHECKUPDATE') && !isset($_SESSION['phM_perm_read']['AU_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

// Schreibberechtigung (es sei denn, das Frontend bindet uns ein, um auf Updates zu checken)
if (!defined('FE_CHECKUPDATE') && !isset($_SESSION['phM_perm_write']['AU_']) && isset($mode) && !$_SESSION['phM_superroot']) {
    if (in_array($mode, array('AUdele', 'AUidx', 'AUgo', 'AUdone', 'checkversion', 'checkkey'))) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
}

define('AU_SSL_CAPABLE', function_exists('extension_loaded') && extension_loaded('openssl'));
define('AU_HAS_GZIP', function_exists('gzinflate'));
define('AU_HAS_BZIP', function_exists('bzdecompress'));
// Get the server settings
$_PM_['AU'] = parse_ini_file($_PM_['path']['conf'].'/autoupdate.ini.php', true);

// Replaces placeholders for the various target directories, which can be placed
// quite freely in the filesystem
$sonder = array
        ('../' => '' // Just a little security precaution
        ,'/..' => '' // Just a little security precaution
        ,'phlymail/shared/config' => $_PM_['path']['conf']
        ,'phlymail/storage/config' => $_PM_['path']['conf']
        ,'phlymail/frontend/themes' => $_PM_['path']['theme']
        ,'phlymail/config' => defined('CONFIGPATH') ? CONFIGPATH : $_PM_['path']['admin']
        ,'phlymail/shared/lib' => $_PM_['path']['lib']
        ,'phlymail/shared/msggw' => $_PM_['path']['msggw']
        ,'phlymail/shared/messages' => $_PM_['path']['message']
        ,'phlymail/shared/drivers' => $_PM_['path']['driver']
        ,'phlymail/handlers' => $_PM_['path']['handler']
        ,'phlymail/frontend' => $_PM_['path']['frontend']
        ,'phlymail/' => ''
        );

if ($mode == 'changelog') {
    $outer_template = 'setup.au.changelog.tpl';
    $tpl = 'Failed to get info from server';
    if (isset($_SESSION['AU_versioninfo'][$relstatus])) {
        if (preg_match('!<description lang="'.$WP_conf['language'].'">(.*)</description>!isU', $_SESSION['AU_versioninfo'][$relstatus], $found)) {
            $tpl = '<div style="text-align:center;font-size:12pt;font-weight:bold;margin-bottom:4px;">Changelog Build '
                    .$_SESSION['AU_versionnumber'][$relstatus].'</div>'.$found[1];
        } elseif (preg_match('!<description lang="en">(.*)</description>!isU', $_SESSION['AU_versioninfo'][$relstatus], $found)) {
            $tpl = '<div style="text-align:center;font-size:12pt;font-weight:bold;margin-bottom:4px;">Changelog Build '
                    .$_SESSION['AU_versionnumber'][$relstatus].'</div>'.$found[1];
        }
    }
    return;
}

// Init of the AU process, checking the keys validity for AU, get current online version
if ($mode == 'checkkey' || defined('FE_CHECKUPDATE')) {
    $out = '';
    if (defined('FE_CHECKUPDATE')) {
        $_SESSION['AU_versionnumber']['stable'] = 'no';
    }
    $captured = au_handshake(array('query' => 'ver='.$CurrBuild, 'primary' => $_PM_['AU']['0'], 'secondary' => $_PM_['AU']['1']));
    preg_match('!<version>(.*)</version>!isU', $captured, $new_version);
    preg_match('!<version relstatus="beta">(.*)</version>!isU', $captured, $new_beta);
    preg_match('!<error_id>(.*)</error_id>!isU', $captured, $new_error);
    preg_match('!<(phm[a-z][a-z]_'.$new_version[1].')>(.*)</\1>!isU', $captured, $versioninfo);
    header('Content-Type: application/json; charset=UTF-8');
    $_SESSION['AU_versionnumber']['stable'] = version_format(trim($new_version[1]));
    $_SESSION['AU_versioninfo']['stable'] = !empty($versioninfo) ? $versioninfo[2] : '';
    $out .= '{"newversion":"'.version_format(trim($new_version[1])).'"';
    if (!empty($new_error[1])) {
        $out .= ',"error":'.$new_error[1];
    }
    if (!empty($new_beta[1]) && $new_beta[1] > $new_version[1]) {
        $out .= ',"newbeta":"'.version_format(trim($new_beta[1])).'"';
        preg_match('!<(phm[a-z][a-z]_'.$new_beta[1].')>(.*)</\1>!isU', $captured, $versioninfo);
        $_SESSION['AU_versionnumber']['beta'] = version_format(trim($new_beta[1]));
        $_SESSION['AU_versioninfo']['beta'] = !empty($versioninfo) ? $versioninfo[2] : '';
    }
    $out .= '}';

    if (defined('FE_CHECKUPDATE')) {
        return; // Got all info in the $_SESSION now ...
    }
    echo $out;
    exit;
}
// User decided to update the installation, we fetch the serialised lists of files available in the core
if ($mode == 'getfilelist') {
    $handlers = array();
    $d = opendir($_PM_['path']['handler']);
    while (false !== ($hdl = readdir($d))) {
        if ('.' == $hdl) continue;
        if ('..' == $hdl) continue;
        if ('email' == $hdl) continue; // Don't look for separate updates for core handlers
        if ('contacts' == $hdl) continue;
        if ('core' == $hdl) continue;
        if ('calendar' == $hdl) continue;
        if ('files' == $hdl) continue;
        $handlers[] = $hdl;
    }
    closedir($d);
    $themes = array();
    $d = opendir($_PM_['path']['theme']);
    while (false !== ($hdl = readdir($d))) {
        if ('.' == $hdl) continue;
        if ('..' == $hdl) continue;
        if ('Yokohama' == $hdl) continue; // Never mind default theme
        $themes[] = $hdl;
    }
    closedir($d);
    $captured = au_handshake(array
            ('query' => '&getHashes=1'.($relstatus == 'beta' ? '&force_beta=1' : '')
                    .(!empty($handlers) ? '&handlers='.implode('|', $handlers) : '')
                    .(!empty($themes) ? '&themes='.implode('|', $themes) : '')
            ,'primary' => $_PM_['AU']['0']
            ,'secondary' => $_PM_['AU']['1']
            ,'post' => true
            ));
    file_put_contents($_PM_['path']['au_tmp'].'/AUpdate.rawfilelist.php', $captured);
    header('Content-Type: application/json; charset=UTF-8');
    preg_match('!<main>(.*)</main>!isU', $captured, $au_idx);
    preg_match('!<error_id>(.*)</error_id>!isU', $captured, $new_error);
    if (!isset($au_idx[1])) {
        if (isset($new_error[1])) {
            echo '{"error":'.$new_error[1].',"gotfilelist":""}';
        } else {
            echo '{"error":"Could not parse result, see update/AUpdate.rawfilelist.php for details","gotfilelist":""}';
        }
    } else {
        if (!file_exists($_PM_['path']['au_tmp'])) {
            mkdir($_PM_['path']['au_tmp']);
        }
        file_put_contents($_PM_['path']['au_tmp'].'/AUpdate.ser.php', AU_HAS_BZIP ? bzdecompress($au_idx[1]) : (AU_HAS_GZIP ? gzinflate($au_idx[1]) : $au_idx[1]));
        // Look for optionally transferred lists of updatable handlers ans themes
        preg_match_all('!<theme name="(.+)">(.*)</theme>!isU', $captured, $theme_idx, PREG_SET_ORDER);
        foreach ($theme_idx as $matches) {
            file_put_contents($_PM_['path']['au_tmp'].'/AUtheme.'.$matches[1].'.ser.php', AU_HAS_BZIP ? bzdecompress($matches[2]) : ((AU_HAS_GZIP) ? gzinflate($matches[2]) : $matches[2]));
        }
        preg_match_all('!<handler name="(.+)">(.*)</handler>!isU', $captured, $handler_idx, PREG_SET_ORDER);
        foreach ($handler_idx as $matches) {
            file_put_contents($_PM_['path']['au_tmp'].'/AUhandler.'.$matches[1].'.ser.php', AU_HAS_BZIP ? bzdecompress($matches[2]) : ((AU_HAS_GZIP) ? gzinflate($matches[2]) : $matches[2]));
        }
        echo '{"gotfilelist":"1"}';
    }
    exit;
}
// Read in and unserialize all file list sources, create AUpdate.idx from it
if ($mode == 'checklist') {
    $delelist = array();
    if (file_exists($_PM_['path']['au_tmp'].'/AUpdate.idx')) unlink($_PM_['path']['au_tmp'].'/AUpdate.idx');

    $f = fopen($_PM_['path']['au_tmp'].'/roh', 'w');
    foreach (unserialize(file_get_contents($_PM_['path']['au_tmp'].'/AUpdate.ser.php')) as $filename => $hashes) {
        fputs($f, $filename.' ');
        $clean = str_replace(array_keys($sonder), array_values($sonder), $filename);
        fputs($f, $clean.' ');
        if (!file_exists($clean) || $hashes['SHA1'] != sha1_file($clean)) {
            file_put_contents($_PM_['path']['au_tmp'].'/AUpdate.idx', 'main:'.$filename.LF, FILE_APPEND);
            fputs($f, 'x');
        }
        fputs($f, LF);
    }
    $delelist[] = $_PM_['path']['au_tmp'].'/AUpdate.ser.php';
    $d = opendir($_PM_['path']['au_tmp']);
    while (false !== ($f = readdir($d))) {
        unset($f2);
        if (preg_match('!^AUhandler\.(.+)\.ser\.php$!', $f, $f2)) {
            foreach (unserialize(file_get_contents($_PM_['path']['au_tmp'].'/'.$f)) as $filename => $hashes) {
                $clean = str_replace(array_keys($sonder), array_values($sonder), $filename);
                if (!file_exists($clean) || $hashes['SHA1'] != sha1_file($clean)) {
                    file_put_contents($_PM_['path']['au_tmp'].'/AUpdate.idx', 'ao_'.$f2[1].':'.$filename.LF, FILE_APPEND);
                }
            }
            $delelist[] = $_PM_['path']['au_tmp'].'/'.$f;
        }
        if (preg_match('!^AUtheme\.(.+)\.ser\.php$!', $f, $f2)) {
            foreach (unserialize(file_get_contents($_PM_['path']['au_tmp'].'/'.$f)) as $filename => $hashes) {
                $clean = $_PM_['path']['theme'].'/'.(str_replace('../', '', str_replace('/..', '', $filename)));
                if (!file_exists($clean) || $hashes['SHA1'] != sha1_file($clean)) {
                    file_put_contents($_PM_['path']['au_tmp'].'/AUpdate.idx', 'th_'.$f2[1].':'.$filename.LF, FILE_APPEND);
                }
            }
            $delelist[] = $_PM_['path']['au_tmp'].'/'.$f;
        }
    }
    closedir($d);
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"checkedlist":"1"}';
    foreach ($delelist as $dele) unlink($dele);
    exit;
}
if ($mode == 'download_init') {
    $_SESSION['AUfiles'] = file($_PM_['path']['au_tmp'].'/AUpdate.idx');
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"dlinit_ok":"'.sizeof($_SESSION['AUfiles']).'"}';
    exit;
}

if ($mode == 'download') {
    header('Content-Type: application/json; charset=UTF-8');
    $offset = intval($_REQUEST['download']);
    if (!isset($_SESSION['AUfiles'][$offset])) {
        echo '{"error":"-1","downloaded":"'.$offset.'"}';
        exit;
    }
    $tmp = au_handshake(array
            ('query' => 'key='.$LicKey.($relstatus == 'beta' ? '&force_beta=1' : '').'&download='.urlencode(trim($_SESSION['AUfiles'][$offset]))
            ,'primary' => $_PM_['AU']['0']
            ,'secondary' => $_PM_['AU']['1']
            ));
    if ($tmp === false) {
        echo '{"error":"-2","downloaded":"'.$offset.'"}';
        exit;
    }
    $written = file_put_contents($_PM_['path']['au_tmp'].'/'.$offset, ((AU_HAS_BZIP) ? bzdecompress($tmp) : ((AU_HAS_GZIP) ? gzinflate($tmp) : $tmp)));
    if ($written === false) {
        echo '{"error":"-3","downloaded":"'.$offset.'"}';
        exit;
    }
    echo '{"downloaded":"'.$offset.'"}';
    exit;
}

if ($mode == 'install') {
    $copied = 0;
    $failed = 0;
    $runonces = array();
    $currentbuild = array();
    foreach ($_SESSION['AUfiles'] as $offset => $filename) {
        if (!file_exists($_PM_['path']['au_tmp'].'/'.$offset)) continue;
        /*if (filesize($_PM_['path']['au_tmp'].'/'.$offset) == 0) { // There's indeed some 0 byte files in 3rd party stuff
            ++$failed;
            continue;
        }*/
        list ($type, $filename) = explode(':', trim($filename), 2);
        if ($type == 'main') {
            $clean = str_replace(array_keys($sonder), array_values($sonder), $filename);
        } elseif (preg_match('!^ao_(.+)$!', $type, $f)) {
            $clean = str_replace(array_keys($sonder), array_values($sonder), $filename);
        } elseif (preg_match('!^th_(.+)$!', $type, $f)) {
            $clean = $_PM_['path']['frontend'].'/themes/'.$filename;
        }
        if (!file_exists(dirname($clean))) {
            basics::create_dirtree(dirname($clean));
        }
        $cleanfile = basename($clean);
        // Beachte Endung von index.php / config.php
        if (in_array($cleanfile, array('index.php', 'config.php', 'email.fetcher.php', 'calendar.externalalerts.php'))) {
            $myfile = basename(PHP_SELF);
            $myext = substr($myfile, strrpos($myfile, '.') + 1);
            list ($clbase, $clext) = explode('.', $clean);
            $clean = $clbase.'.'.$myext;
        }
        if ($cleanfile == 'current.build') { // Wird nur im Erfolgsfalle eingespielt
            $currentbuild[$offset] = $clean;
            continue;
        } elseif ($cleanfile == 'runonce.php') { // Runonce.php (egal welche) als letztes
            $runonces[] = $offset;
        } elseif (copy($_PM_['path']['au_tmp'].'/'.$offset, $clean) && unlink($_PM_['path']['au_tmp'].'/'.$offset)) {
            ++$copied;
        } else {
            ++$failed;
        }
    }
    // Run runonce.php (Might be more then once)
    foreach ($runonces as $offset) {
        ++$copied;
        require($_PM_['path']['au_tmp'].'/'.$offset);
        @unlink($_PM_['path']['au_tmp'].'/'.$offset);
    }
    @unlink($_PM_['path']['au_tmp'].'/roh');
    @unlink($_PM_['path']['au_tmp'].'/AUpdate.rawfilelist.php');
    if ($failed == 0) {
        foreach ($currentbuild as $offset => $clean) {
            if (copy($_PM_['path']['au_tmp'].'/'.$offset, $clean) && unlink($_PM_['path']['au_tmp'].'/'.$offset)) {
                ++$copied;
            } else {
                ++$failed;
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"installed":"'.$copied.'","failed":"'.$failed.'"}';
    exit;
}
$WP_return = isset($_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : '';
if ('AUdone' == $mode) {
    unset($tpl);
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.au.done.tpl');
    $tpl->assign(array('msg_setAU' => $WP_msg['setAU'], 'WP_return' => $WP_return
                      ,'msg_ok' => $WP_msg['AU_OK'], 'link_target' => htmlspecialchars($link_base.'AU')));
}
if (!$mode) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.au.main.tpl');
    $tpl->fill_block('main', array
           ('version_installed' => version_format(trim($CurrBuild))
           ,'link_base' => htmlspecialchars($link_base)
           ,'msg_version_server' => $WP_msg['AU_VersionServer']
           ,'msg_version_installed' => $WP_msg['AU_VersionInstalled']
           ,'baseurl' => $link_base.'AU&mode='
           ,'msg_uptodate' => $WP_msg['AU_uptodate']
           ,'msg_ok' => $WP_msg['AU_OK']
           ,'msg_failed' => $WP_msg['AU_Fail']
           ,'msg_runAU' => $WP_msg['AU_runAU']
           ,'msg_dl_aborted' => $WP_msg['AU_ErrTransfer']
           ,'msg_inst_aborted' => $WP_msg['AU_ErrInstall']
           ,'msg_install_okay' => $WP_msg['AU_installOkay']
           ,'msg_abort' => $WP_msg['AU_abort']
           ,'msg_retry' => $WP_msg['AU_retry']
           ,'msg_expired' => $WP_msg['AU_expired']
           ,'msg_invalid' => $WP_msg['AU_invalid']
           ,'msg_wrongIP' => $WP_msg['AU_wrongIP']
           ,'msg_checkingkey' => $WP_msg['AU_checkingKey']
           ,'msg_checkingversion' => $WP_msg['AU_checkingVersion']
           ,'msg_fetchinglist' => $WP_msg['AU_fetchingList']
           ,'msg_checkinglist' => $WP_msg['AU_checkingList']
           ,'msg_downloadingfiles' => $WP_msg['AU_downloadingFiles']
           ,'msg_installingfiles' => $WP_msg['AU_installingFiles']
           ,'msg_install_failed_num' => $WP_msg['AU_installFailedNnum']
           ));
}

/**
 * Talks to the AU server
 *
 * @param array $in Possible keys are: 'primary' and 'secondary' for the AU servers
 *      , 'query' for the Query string to submit (all three mandatory)
 *      , optionally you can pass 'post' as true for a POST request, otherwise GET is assumed
 * @return string  Holding the response text from the server or FALSE on failure
 */
function au_handshake($in)
{
    $httpClient = new Protocol_Client_HTTP();
    $captured = '';
    if (AU_HAS_BZIP) {
        $in['query'] .= '&bzok=1';
    } elseif (AU_HAS_GZIP) {
        $in['query'] .= '&gzok=1';
    }
    foreach (array($in['primary'], $in['secondary']) as $AU) {
        $AU['method'] = (isset($in['post']) && $in['post']) ? 'post' : 'get';
        $AU['query'] = $in['query'];
        // Skip the HTTPS server(s) if SSL is not available
        if ($AU['port'] == 443 && !AU_SSL_CAPABLE) {
            continue;
        // SSL required, but non-HTTPS host -> skip
        } elseif ($AU['port'] == 80 && !empty($GLOBALS['_PM_']['auth']['force_ssl'])) {
            continue;
        }
        $captured = $httpClient->send_request($AU);
        if ($captured) {
            return $captured;
        }
    }
    return false;
}
