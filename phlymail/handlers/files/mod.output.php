<?php
/**
 * mod.output.php -> Make a file available for download
 * @package phlyMail "Yokohama 2" 4.x Default Branch
 * @subpackage Handler Files
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.8 2012-07-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (empty($_REQUEST['item']) && empty($_REQUEST['i'])) {
    die();
}
$item = (!empty($_REQUEST['i'])) ? $_REQUEST['i'] : $_REQUEST['item'];

$FS = new handler_files_driver($_SESSION['phM_uid']);
$info = $FS->get_item_info($item, true);
$path = $FS->item_get_real_location($item);
$uid = $_SESSION['phM_uid'];
session_write_close();
unset($FS);
header('Content-Type: '.($info['type'] ? $info['type'] : 'application/octet-stream'));
if (!isset($_REQUEST['inline'])) {
    header('Content-Disposition: attachment; filename="'.basename($info['friendly_name']).'"');
}
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.$info['size']);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: public');
    header('Content-Description: File Transfer');
} else {
    header('Cache-Control: post-check=0, pre-check=0');
}
if (false !== ($fp = fopen($_PM_['path']['userbase'].'/'.$uid.'/files/'.$path[0].'/'.$path[1], 'rb'))) {
    while((!feof($fp)) && (connection_status() == 0)) {
        echo fread($fp, 1024*8);
        flush();
    }
    fclose($fp);
}
exit;
