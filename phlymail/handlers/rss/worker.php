<?php
/**
 * worker.php - Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Bookmarks
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.6 2015-03-30 $Id: $
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

switch ($_REQUEST['what']) {
    case 'rename_folder':
    case 'folder_move':
    case 'folder_delete':
    case 'folder_create':
    // case 'folder_empty':
        header('Content-Type: text/javascript; charset=UTF-8');
        // Include the setup module and let it hanlde the operation
        require_once(__DIR__.'/setup.folders.php');
        if ($error) { // React on errors
            echo 'alert("'.addcslashes($error, '"').'")'.LF;
        } else { // No errors - force reload of the folder list to reflect changes done
            echo 'flist_refresh("rss");'.LF.'if (parent.CurrentHandler == "rss") parent.frames.PHM_tr.refreshlist()'.LF;
        }
        exit;
        break;
    case 'item_mark':
    case 'item_unmark':
    case 'item_delete':
        header('Content-Type: '.(!isset($_REQUEST['no_json']) ? 'application/json' : 'text/javascript').'; charset=UTF-8');
        // Include the setup module and let it hanlde the operation
        require_once(__DIR__.'/setup.items.php');
        if ($error) { // React on errors
            echo (!isset($_REQUEST['no_json'])) ? '{"error":"'.addcslashes($error, '"').'","done":"1"}' : 'alert("'.addcslashes($error, '"').'")'.LF;;
        } else { // No errors - force reload of the folder to reflect changes done
            echo (!isset($_REQUEST['no_json']))
                    ? '{"done":"1"}'
                    : 'parent.frames.PHM_tl.flist_refresh("rss");'.LF.'if (parent.CurrentHandler == "rss") parent.frames.PHM_tr.refreshlist()'.LF;
        }
        exit;
        break;
}