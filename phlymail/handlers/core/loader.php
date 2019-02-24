<?php
/**
 * loader.php - central loader for popped-up and framed modules
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Core
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.7 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$outer_template = 'framed.tpl';
$modname = false;
switch ($load) {
    case 'compose_sms': $modname = 'compose.sms.php'; break;
    case 'compose_fax': $modname = 'compose.fax.php'; break;
    case 'compose_email': $modname = 'compose.email.php'; break;
    case 'compose_email_upload': $modname = 'compose.upload.php'; break;
    case 'compose_email_sig': $modname = 'select.signature.php'; break;
    case 'send_email': $modname = 'send.email.php'; break;
    case 'setup': $modname = 'setup.php'; $outer_template = '3dframed.tpl'; break;
    case 'selectfile': $modname = 'fileselector.php'; $outer_template = '3dframed.tpl'; break;
    case 'foldershares': $modname = 'foldershares.php'; $outer_template = '3dframed.tpl'; break;
    case 'ilist': $modname = 'pinboard.php'; break;
    case 'flist': $modname = 'flist.php'; break;
    case 'about': $modname = 'about.php'; break;
    case 'worker': $modname = 'worker.php'; break;
    case 'sendto': $modname = 'sendto.php'; break;
}
if ($modname) {
    require __DIR__.'/'.$modname;
}
