<?php
/**
 * Modularized Setup of the handler
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Email
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.3 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$action = (isset($_REQUEST['mod'])) ? $_REQUEST['mod'] : false;
switch ($action) {
    case 'folders':      $include = 'setup.folders.php';  break;
    case 'mails':        $include = 'setup.mails.php';    break;
    case 'filters':      $include = 'setup.filters.php';  break;
    case 'boilerplates': $include = 'setup.boilerplates.php';  break;
    default:             exit;
}
require_once __DIR__.'/'.$include;
