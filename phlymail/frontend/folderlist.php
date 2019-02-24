<?php
/**
 * mod.listfolder.php -> Folder overview / Folder browser
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.5.9 2015-04-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$link_base = PHP_SELF.'?'.give_passthrough(1);
// Allow other modules to use special modes of the folder list
$mode = 'default';
if (isset($listmode) && $listmode) {
    if (isset($only_handler) && $only_handler) {
        $handler = $only_handler;
        $mode = $listmode;
    }
}
try {
    $shDB = new DB_Controller_Share();
    $allShared = $shDB->getFolderList($_SESSION['phM_uid'], null);
} catch (Exception $e) {
    $allShared = array();
}

if (isset($handler)) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.general.tpl');
    $handlerlist[0] = array('type' => $handler);
    unset($_PM_['foldercollapses']); // No collapses in this case
    $tpl->assign_block('nocollapses');
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.2.tpl');
    $t_ah = $tpl->get_block('add_handler');
    foreach ($_SESSION['phM_uniqe_handlers'] as $k => $v) {
        if ($k == 'core') { // Sonderfall CORE
            $t_ah->assign(array('id' => $k, 'friendlyname' => $v['i18n'], 'is_open' => 0, 'is_hidden' => 1));
            $tpl->assign('add_handler', $t_ah);
            $t_ah->clear();
        } elseif (file_exists($_PM_['path']['handler'].'/'.$k.'/flist.php')) {
            $t_ah->assign(array
                    ('id' => $k
                    ,'friendlyname' => $v['i18n']
                    ,'is_open' => (isset($_PM_['foldercollapses']) && isset($_PM_['foldercollapses'][$k.'_']) && $_PM_['foldercollapses'][$k.'_']) ? 0 : 1
                    ,'is_hidden' => 0
                    ));
            $tpl->assign('add_handler', $t_ah);
            $t_ah->clear();
        }
    }
    $tpl->assign(array
            ('flist_loadurl' => $link_base.'&l=flist'
            ,'ilist_loadurl' => $link_base.'&l=ilist'
            ,'favfolders_loadurl' => $link_base.'&l=worker&h=core&what=favfolders_get'
            ,'favfolders_seturl' => $link_base.'&l=worker&h=core&what=favfolders_set'
            ,'foldercollapseurl' => $link_base.'&l=worker&h=core&what=collapsedfolder&folder='
            ,'customsize_url' => $link_base.'&l=worker&h=core&what=customsize'
            ));
    $mode = 'ajax';
}
$tpl->assign(array
        ('passthrough' => give_passthrough()
        ,'etooshort' => $WP_msg['SetFldEnametooshort']
        ,'etooslong' => $WP_msg['SetFldEnametoolong']
        ,'msg_properties' => $WP_msg['properties']
        ,'msg_resync' => $WP_msg['LegSyncFolder']
        ,'msg_move' => $WP_msg['LegMoveFolder']
        ,'msg_rename' => $WP_msg['LegRenameFolder']
        ,'msg_dele' => $WP_msg['LegDeleteFolder']
        ,'msg_subfolder' => $WP_msg['CreateSubfolder']
        ,'msg_foldername' => $WP_msg['FolderName']
        ,'msg_really_dele_folder' => $WP_msg['ReallyDeleFolder']
        ,'msg_really_empty_folder' => $WP_msg['ReallyEmptyFolder']
        ,'msg_emptytrash' => $WP_msg['ActionEmptyTrash']
        ,'msg_emptyjunk' => $WP_msg['ActionEmptyJunk']
        ,'msg_sharefolder' => $WP_msg['ShareFolder']
        ,'head_select' => $WP_msg['FldrBrwsSelect']
        ,'msg_select' => $WP_msg['Select']
        ,'msg_refresh' => $WP_msg['refresh']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'head_share' => $WP_msg['ShareFolder']
        ,'head_share_groups' => $WP_msg['HeadShareGroups']
        ,'head_share_users' => $WP_msg['HeadShareUsers']
        ,'msg_addfavoruites' => $WP_msg['FavFoldersAddTo']
        ,'msg_removefavourites' => $WP_msg['FavFoldersRemove']
        ,'login_handler' => $_SESSION['phM_login_handler']
        ,'login_folder' => $_SESSION['phM_login_folder']
        ));
if ($mode == 'ajax') {
    return; // Ends this script and returns to parent file
}

$tpl_line = $tpl->get_block('line');
$tpl_name = $tpl_line->get_block($mode);
$tpl_bars = $tpl_line->get_block('bars');
$tpl_auf  = $tpl_line->get_block('aufzu');
$tpl_crnp = $tpl_line->get_block('cornplus');
$tpl_root = $tpl_line->get_block('rootline');
$tpl_corn = $tpl_line->get_block('corn');
$tpl_trans = $tpl->get_block('add_handler_array');
$xbase = $_PM_['path']['handler'];
// Iteratively include() all registered handlers
$id = 0; // This is the unique id of a node, must be globally set to prevent ambigious output
foreach ($handlerlist as $handler) {
    if (is_readable($xbase.'/'.$handler['type'].'/folderlist.php')) {
        $_PM_['handler']['path'] = $xbase.'/'.$handler['type'];
        $_PM_['handler']['name'] = $handler['type'];
        $call = 'handler_'.$_PM_['handler']['name'].'_folderlist';
        $fl = new $call($_PM_, $mode);
        $folderList = $fl->get();
        $id = listfolder_do_output($folderList, $handler['type'], 0, false, $id);
        unset ($fl, $folderList);
    }
}

function listfolder_do_output(&$structure, $handler, $level = 0, $drawline = array(0 => 1), $id = 0)
{
    $alleShares = $GLOBALS['allShared'];
    $icon_path = $GLOBALS['_PM_']['path']['theme'].'/icons/';
    $passthru1 = give_passthrough(1);
    $passthru2 = give_passthrough(2);
    $groesse   = sizeof($structure);
    $linecounter = 0;
    foreach ($structure as $k => $v) {
        $menu_link = PHP_SELF.'?h='.$handler.'&amp;l=menflist&amp;'.$passthru1.'&amp;workfolder=';
        $fold_link = PHP_SELF.'?h='.$handler.'&amp;l=ilist&amp;'.$passthru1.'&amp;workfolder=';
        // Dead IMAP boxes must be handled differently
        $stale = (isset($v['stale']) && $v['stale']) ? true : false;

        $is_shared = !empty($alleShares[$_SESSION['phM_uid']][$handler][$k]);

        // Find special icons for folders
        switch ($v['icon']) {
            case ':inbox':     $v['big_icon'] = $icon_path.'inbox_big.gif';      $v['icon'] = $icon_path.'inbox.png';    break;
            case ':archive':   $v['big_icon'] = $icon_path.'archive_big.gif';    $v['icon'] = $icon_path.'archive.png';  break;
            case ':sent':      $v['big_icon'] = $icon_path.'sent_big.gif';       $v['icon'] = $icon_path.'sent.png';     break;
            case ':waste':     $v['big_icon'] = $icon_path.'waste_big.gif';      $v['icon'] = $icon_path.'waste.png'; $v['is_trash'] = true; break;
            case ':junk':      $v['big_icon'] = $icon_path.'junk_big.gif';       $v['icon'] = $icon_path.'junk.png'; $v['is_junk'] = true;  break;
            case ':drafts':    $v['big_icon'] = $icon_path.'drafts_big.gif';     $v['icon'] = $icon_path.'drafts.png'; break;
            case ':templates': $v['big_icon'] = $icon_path.'templates_big.gif';  $v['icon'] = $icon_path.'templates.png'; break;
            case ':mailbox':   $v['big_icon'] = $icon_path.'mailbox_big.gif';    $v['icon'] = $icon_path.'mailbox.png';  break;
            case ':imapbox':   $v['big_icon'] = $icon_path.'imapbox'.($stale ? '_stale' : '').'_big.gif';  $v['icon'] = $icon_path.'imapbox'.($stale ? '_stale' : '').'.png'; break;
            case ':calendar':  $v['big_icon'] = $icon_path.'calendar_big.gif';   $v['icon'] = $icon_path.'calendar.png'; break;
            case ':contacts':  $v['big_icon'] = $icon_path.'contacts_big.gif';   $v['icon'] = $icon_path.'contacts.png'; break;
            case ':notes':     $v['big_icon'] = $icon_path.'notes_big.gif';      $v['icon'] = $icon_path.'notes.png';    break;
            case ':files':     $v['big_icon'] = $icon_path.'files_big.gif';      $v['icon'] = $icon_path.'files.png';    break;
            case ':virtual':   $v['big_icon'] = $icon_path.'virtualfolder_big.gif'; $v['icon'] = $icon_path.'virtualfolder.png'; break;
        }
        if (!file_exists($v['icon'])) {
            $v['icon'] = $icon_path.'folder_def.png';
        }
        if (!isset($v['big_icon']) || !file_exists($v['big_icon'])) {
            $v['big_icon'] = $icon_path.'folder_def_big.gif';
        }
        // Draw lines
        for ($i = 0; $i < $level; ++$i) {
            if (1 == $drawline[$i]) {
                $GLOBALS['tpl_bars']->assign_block('vbar');
            } else {
                $GLOBALS['tpl_bars']->assign_block('novbar');
            }
            $GLOBALS['tpl_line']->assign('bars', $GLOBALS['tpl_bars']);
            $GLOBALS['tpl_bars']->clear();
        }
        // Corners and Plusminus
        if (is_array($v['subdirs']) && !$stale) {
            $GLOBALS['tpl_line']->assign('aufzu', $GLOBALS['tpl_auf']);
        } elseif (0 == $level) {
            $GLOBALS['tpl_line']->assign('rootline', $GLOBALS['tpl_root']);
        } elseif (($linecounter + 1) == $groesse) {
            $GLOBALS['tpl_line']->assign('corn', $GLOBALS['tpl_corn']);
        } else {
            $GLOBALS['tpl_line']->assign('cornplus', $GLOBALS['tpl_crnp']);
        }
        // Set correct draw state of this level
        $drawline[$level] = (($linecounter + 1) >= $groesse) ? 0 : 1;

        // A renamable folder
        if (($v['type'] == 1 || $v['type'] == 11) && $GLOBALS['mode'] == 'default') {
            $rename = $GLOBALS['tpl_name']->get_block('renamable');
            $rename->assign(array('Rfolder_path' => $id.'_'.str_replace('/', '_', phm_entities($v['path']))));
            $GLOBALS['tpl_name']->assign('renamable', $rename);
        }

        // Help JavaScript translate between its own ID and the handler / folder ID, assign CTXMenu options
        $newsub = (isset($v['has_folders']) && $v['has_folders']) ? true : false;
        $not_sys = (($v['type'] == 1 || $v['type'] == 11) && $GLOBALS['mode'] == 'default') ? true : false;
        $GLOBALS['tpl_trans']->assign_block('ctx');
        $GLOBALS['tpl_trans']->assign_block('props');
        if (isset($v['is_trash']) && $v['is_trash']) {
            $GLOBALS['tpl_trans']->assign_block('trash');
        }
        if (isset($v['is_junk']) && $v['is_junk']) {
            $GLOBALS['tpl_trans']->assign_block('junk');
        }
        if (isset($v['has_items']) && $v['has_items']) {
            $GLOBALS['tpl_trans']->assign_block('resync');
        }
        if (isset($GLOBALS['_PM_']['foldercollapses'])
                && isset($GLOBALS['_PM_']['foldercollapses'][$handler.'_'.$k])
                && $GLOBALS['_PM_']['foldercollapses'][$handler.'_'.$k]) {
            $GLOBALS['tpl_trans']->assign_block('is_collapsed');
        }
        if ($newsub) {
            $GLOBALS['tpl_trans']->assign_block('subfolder');
        }
        if ($not_sys) {
            $GLOBALS['tpl_trans']->assign_block('move');
            $GLOBALS['tpl_trans']->assign_block('rename');
            $GLOBALS['tpl_trans']->assign_block('dele');
        }

        $GLOBALS['tpl_trans']->assign(array
                ('fid' => $k
                ,'Rfolder_path' => $id.'_'.str_replace('/', '_', phm_entities($v['path']))
                ,'handler' => $handler
                ));

        $GLOBALS['tpl_name']->assign(array
                ('menu_target' => $menu_link.$v['path']
                ,'fid' => $k
                ,'folder_path' => str_replace('/', '_', phm_entities($v['path']))
                ,'link_target' => $fold_link.$v['path']
                ,'rename_target' => PHP_SELF.'?'.
                        phm_entities($passthru1.'&l=worker&what=rename_folder&rename_folder='.$k.'&h='.$GLOBALS['_PM_']['handler']['name'])
                ,'icon' => $v['icon']
                ,'Rfolder_path' => $id.'_'.str_replace('/', '_', phm_entities($v['path']))
                ,'passthrough2' => $passthru2
                ,'big_icon' => $v['big_icon']
                ,'namelength' => strlen($v['foldername'])
                ,'foldername' => phm_entities($v['foldername'])
                ,'unread' => (isset($v['unread']) && $v['unread']) ? '('.$v['unread'].')' : ''
                ,'handler' => $handler
                ));
        $GLOBALS['tpl_line']->assign($GLOBALS['mode'], $GLOBALS['tpl_name']);
        $GLOBALS['tpl_line']->assign(array
                ('level' => $level
                ,'id' => $id
                ,'Rfolder_path' => $id.'_'.str_replace('/', '_', phm_entities($v['path']))
                ));
        $GLOBALS['tpl']->assign('line', $GLOBALS['tpl_line']);
        $GLOBALS['tpl_name']->clear();
        $GLOBALS['tpl_line']->clear();
        $GLOBALS['tpl']->assign('add_handler_array', $GLOBALS['tpl_trans']);
        $GLOBALS['tpl_trans']->clear();
        ++$id;
        if (is_array($v['subdirs']) && !$stale) {
            $id = listfolder_do_output($v['subdirs'], $handler, ($level+1), $drawline, $id);
        }
        ++$linecounter;
    }
    return $id;
}
