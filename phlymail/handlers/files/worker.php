<?php
/**
 * worker.php - Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Files
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.0 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

switch ($_REQUEST['what']) {
case 'rename_folder':
case 'folder_move':
case 'folder_delete':
case 'folder_create':
case 'folder_empty':
    header('Content-Type: text/html; charset=UTF-8');
    // Include the setup module and let it hanlde the operation
    require_once(__DIR__.'/setup.folders.php');
    if ($error) { // React on errors
        echo 'alert("'.addcslashes($error, '"').'")'.LF;
    } else { // No errors - force reload of the folder list to reflect changes done
        echo 'flist_refresh("files");'.LF.'if (parent.CurrentHandler == "files") parent.frames.PHM_tr.location.reload()'.LF;
    }
    exit;
    break;
case 'item_move':
case 'item_copy':
case 'item_delete':
case 'item_rename':
    header('Content-Type: text/html; charset=UTF-8');
    // Include the setup module and let it hanlde the operation
    require_once(__DIR__.'/setup.items.php');
    if ($error) { // React on errors
        echo (!isset($_REQUEST['no_json']))
                ? '{"error":"'.addcslashes($error, '"').'","done":"1"}'
                : 'alert("'.addcslashes($error, '"').'")'.LF;;
    } else { // No errors - force reload of the folder to reflect changes done
        echo (!isset($_REQUEST['no_json']))
                ? '{"done":"1"}'
                : 'flist_refresh("files");'.LF.'if (parent.CurrentHandler == "files") parent.frames.PHM_tr.location.reload()'.LF;
    }
    exit;
    break;
case 'folder_export':
    // Später mal... vielleicht...: require_once($_PM_['path']['handler'].'/files/folderexport.php');
    break;
}
