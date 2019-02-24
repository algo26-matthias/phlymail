<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Email Handler
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.5 2012-11-29 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$FS = new handler_email_driver($_SESSION['phM_uid']);

// The subscribe browser for IMAP accounts
if (isset($_REQUEST['subscribe'])) {
    if ('init' == $_REQUEST['subscribe']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.subscribe.tpl');
        $tpl_line = $tpl->get_block('line');
        $tpl_bars = $tpl_line->get_block('bars');
        $tpl_auf = $tpl_line->get_block('aufzu');
        $tpl_crnp = $tpl_line->get_block('cornplus');
        $tpl_root = $tpl_line->get_block('rootline');
        $tpl_corn = $tpl_line->get_block('corn');
        listfolder_do_output($FS->get_imapsubscriptions($_REQUEST['fid']), 0, false, 0, 'sub');
        $folder = $FS->get_folder_info($_REQUEST['fid']);
        $tpl->assign(array
                ('msg_save' => $WP_msg['save']
                ,'subscribetarget' => PHP_SELF.'?'.give_passthrough().'&l=setup&mod=folders&subscribe=do&h=email'
                ,'head_select' => str_replace('$1',  $folder['foldername'], $WP_msg['AboutSubscribe'])
                ));
    }
    if ('do' == $_REQUEST['subscribe']) {
        $folders = array();
        foreach ($_REQUEST['sub'] as $k => $realpath) {
            $folders[] = array('path' => $realpath, 'sub' => ($_REQUEST['stat_sub'][$k] && $_REQUEST['stat_sub'][$k]));
        }
        $FS->subscribe_folders($folders);
        $FS->init_folders(true);
        sendJS(array('done' => 1), 1, 1);
    }
    return;
}
// Allows to hide certain folders from the listing
if (isset($_REQUEST['hidefolders'])) {
    if ('init' == $_REQUEST['hidefolders']) {
        $FS->init_folders(false);
        $tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.subscribe.tpl');
        $tpl_line = $tpl->get_block('line');
        $tpl_bars = $tpl_line->get_block('bars');
        $tpl_auf = $tpl_line->get_block('aufzu');
        $tpl_crnp = $tpl_line->get_block('cornplus');
        $tpl_root = $tpl_line->get_block('rootline');
        $tpl_corn = $tpl_line->get_block('corn');
        listfolder_do_output($FS->read_folders(0, false, false), 0, false, 0, 'hid');
        $tpl->assign(array
                ('msg_save' => $WP_msg['save']
                ,'subscribetarget' => PHP_SELF.'?'.give_passthrough().'&l=setup&mod=folders&hidefolders=do&h=email'
                ,'head_select' => $WP_msg['AboutHideFolders']
                ));
    }
    if ('do' == $_REQUEST['hidefolders']) {
        $folders = array();
        foreach ($_REQUEST['sub'] as $k => $realpath) {
            $folders[] = array('id' => $realpath, 'visible' => ($_REQUEST['stat_sub'][$k] && $_REQUEST['stat_sub'][$k]));
        }
        $FS->hide_folders($folders);
        $FS->init_folders(true);
        sendJS(array('done' => 1), 1, 1);
    }
    return;
}

$error = false;
$update_folderlist = false;
$nl = (isset($_PM_['tmp']['setup']['no_output'])) ? LF : '<br />';
// Get current folder structure
$FS->init_folders(false);

if (isset($_REQUEST['new_folder']) && isset($_REQUEST['childof'])) {
    // Quotas: Check the space left and how many messages this user might store
    $quota_number_folder = $DB->quota_get($_SESSION['phM_uid'], 'email', 'number_folders');
    if (false !== $quota_number_folder) {
        $quota_folderleft = $FS->quota_getfoldernum(false);
        $quota_folderleft = $quota_number_folder - $quota_folderleft;
    } else {
        $quota_folderleft = false;
    }
    // End Quota definitions
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_add_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif (false !== $quota_folderleft && $quota_folderleft < 1) { // No more folders allowed
        $error .= $WP_msg['QuotaExceeded'];
    } elseif ($_REQUEST['new_folder'] && $_REQUEST['childof']) {
        if (strlen($_REQUEST['new_folder']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$FS->folder_exists($_REQUEST['new_folder'], $_REQUEST['childof'])) {
            $res = $FS->create_folder($_REQUEST['new_folder'], $_REQUEST['childof']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnocreate'].': '.$FS->get_errors($nl);
            } else {
                $FS->init_folders(true); // Update current folder structure
            }
        } else {
            $error .= str_replace('$1', $_REQUEST['new_folder'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['childof']) {
        $error .= $WP_msg['SetFldEwherecreate'];
    } else {
        $error .= $WP_msg['SetFldEnametooshort'];
    }
}

if (isset($_REQUEST['move_folder']) && isset($_REQUEST['move_to'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_edit_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['move_folder'] && $_REQUEST['move_to']) {
        $folderinfo = $FS->get_folder_info($_REQUEST['move_folder']);
        if (!$FS->folder_exists($folderinfo['foldername'], $_REQUEST['move_to'])) {
            $res = $FS->move_folder($_REQUEST['move_folder'], $_REQUEST['move_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnomove'].': '.$FS->get_errors($nl);
            } else {
                $FS->init_folders(true); // Update current folder structure
            }
        } else {
            $error .= str_replace('$1', $folderinfo['foldername'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['move_to']) {
        $error .= $WP_msg['SetFldEwheremove'];
    }
}

if (isset($_REQUEST['rename_folder']) && isset($_REQUEST['rename_to'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_edit_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['rename_folder'] && $_REQUEST['rename_to']) {
        $info = $FS->get_folder_info($_REQUEST['rename_folder']);
        $if_exists = $FS->folder_exists($_REQUEST['rename_to'], $info['childof']);
        if (strlen($_REQUEST['rename_to']) > 32) {
            $error .= $WP_msg['SetFldEnametoolong'];
        } elseif (!$if_exists || $if_exists == $_REQUEST['rename_folder']) {
            $res = $FS->rename_folder($_REQUEST['rename_folder'], $_REQUEST['rename_to']);
            if (!$res) {
                $error .= $WP_msg['SetFldEnorename'].': '.$FS->get_errors($nl);
            } else {
                $FS->init_folders(true); // Update current folder structure
            }
        } else {
            $error .= str_replace('$1', $_REQUEST['rename_to'], $WP_msg['SetFldEalreadyexists']);
        }
    } elseif (!$_REQUEST['rename_folder']) {
        $error .= $WP_msg['SetFldEwhichrename'];
    } else {
        $error .= $WP_msg['SetFldEnametooshort'];
    }
}

if (isset($_REQUEST['remove_folder'])) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_delete_folder']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif ($_REQUEST['remove_folder']) {
        $forced = (isset($_REQUEST['directly']) && $_REQUEST['directly']);
        $res = $FS->remove_folder($_REQUEST['remove_folder'], $forced, false);
        if (!$res) {
             $error .= $WP_msg['SetFldEnodelete'].': '.$FS->get_errors($nl);
        } else {
            $FS->init_folders(true); // Update current folder structure
        }
    } else {
        $error .= $WP_msg['SetFldEwhichdelete'];
    }
}

if (isset($_REQUEST['resync_folder']) && $_REQUEST['resync_folder']) {
	$res = $FS->resync_folder($_REQUEST['resync_folder']);
	if (!$res) {
		$error .= $WP_msg['SetFldEnosync'].': '.$FS->get_errors($nl);
	} else {
		$FS->init_folders(false); // Update current folder structure
	}
}

if (isset($_REQUEST['empty_folder']) && $_REQUEST['empty_folder']) {
    if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_delete_email']) {
        $error .= $WP_msg['PrivNoAccess'];
    } elseif (!$FS->delete_mail(false, $_REQUEST['empty_folder'])) {
		$error .= $WP_msg['SetFldEnoempty'].': '.$FS->get_errors($nl);
	} else {
		$FS->init_folders(false); // Update current folder structure
	}
}

// This function is used for the subscribe browser
function listfolder_do_output($structure, $level = 0, $drawline = array(0 => 1), $id = 0, $mode = 'sub')
{
    $groesse = sizeof($structure);
    $linecounter = 0;
    $icon_path = $GLOBALS['_PM_']['path']['theme'].'/icons/';
    foreach ($structure as $k => $v) {
        $icon = $v['icon'];
        // Find special icons for folders
        switch ($icon) {
            case ':inbox':     $icon = $icon_path.'inbox.png';     break;
            case ':outbox':    $icon = $icon_path.'outbox.png';    break;
            case ':archive':   $icon = $icon_path.'archive.png';   break;
            case ':sent':      $icon = $icon_path.'sent.png';      break;
            case ':waste':     $icon = $icon_path.'waste.png';     break;
            case ':junk':      $icon = $icon_path.'junk.png';      break;
            case ':drafts':    $icon = $icon_path.'drafts.png';    break;
            case ':templates': $icon = $icon_path.'templates.png'; break;
            case ':imapbox':   $icon = $icon_path.'imapbox.png';   break;
            case ':pop3box':   $icon = $icon_path.'popbox.png';    break;
            case ':calendar':  $icon = $icon_path.'calendar.png';  break;
            case ':contacts':  $icon = $icon_path.'contacts.png';  break;
            case ':notes':     $icon = $icon_path.'notes.png';     break;
            case ':files':     $icon = $icon_path.'files.png';     break;
            case ':mailbox':   $icon = $icon_path.'mailbox.png';
                $v['foldername'] = $GLOBALS['WP_msg']['mailbox'].' '.basename($_SESSION['phM_username']);
                break;
        }
        if (!file_exists($icon)) $icon = $icon_path.'folder_def.png';
        // Draw lines
        for ($i = 0; $i < $level; ++$i) {
            $GLOBALS['tpl_bars']->assign_block(1 == $drawline[$i] ? 'vbar' : 'novbar');
            $GLOBALS['tpl_line']->assign('bars', $GLOBALS['tpl_bars']);
            $GLOBALS['tpl_bars']->clear();
        }
        // Corners and Plusminus
        if (is_array($v['subdirs'])) {
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
        $GLOBALS['tpl_line']->assign(array
                ('fid' => $k
                ,'folder_path' => str_replace('/', '_', phm_entities($v['path']))
                ,'fullpath' => ('sub' == $mode) ? urlencode($v['folder_path']) : intval($k)
                ,'icon' => $icon
                ,'Rfolder_path' => $id.'_'.str_replace('/', '_', phm_entities($v['path']))
                ,'foldername' => phm_entities($v['foldername'])
                ,'level' => $level
                ,'id' => $id
                ));
        if ('sub' == $mode) {
            if ($v['subscribed']) $GLOBALS['tpl_line']->assign_block('subbed');
            if ($v['icon'] == ':imapbox') $GLOBALS['tpl_line']->assign_block('nonselect');
        }
        if ('hid' == $mode) if ($v['visible']) $GLOBALS['tpl_line']->assign_block('subbed');
        $GLOBALS['tpl']->assign('line', $GLOBALS['tpl_line']);
        $GLOBALS['tpl_line']->clear();
        ++$id;
        if (is_array($v['subdirs'])) $id = listfolder_do_output($v['subdirs'], ($level+1), $drawline, $id, $mode);
        ++$linecounter;
  }
  return $id;
}
