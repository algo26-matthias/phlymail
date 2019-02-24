<?php
/**
 * Central file for listing all items within a folder
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Files Handler
 * @copyright 2001-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.5 2013-02-01 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['files_see_files']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

$uplProgress = false;
$uplIDName = 'UPLOAD_IDENTIFIER';
$apc_is_on = ini_get('apc.rfc1867');
$apcName = ini_get('apc.rfc1867_name');
$apcPrefix = ini_get('apc.rfc1867_prefix');
if (1 == $apc_is_on && $apcName && $apcPrefix) {
    $uplProgress = 'apc';
    $uplIDName = $apcName;
} elseif (function_exists('uploadprogress_get_info')) {
    $uplProgress = 'pecl';
}
$ordfields = array('type', 'size', 'mtime', 'atime', 'friendly_name');
// We changed the folder, got to reset the page number to 0
if (isset($_REQUEST['workfolder'])
        && isset($_SESSION['files_workfolder'])
        && $_REQUEST['workfolder'] != $_SESSION['files_workfolder']) {
    $_SESSION['files_pagenum'] = 0;
}
$_SESSION['files_workfolder'] = (isset($_REQUEST['workfolder'])) ? $_REQUEST['workfolder'] : false;
if (isset($_REQUEST['WP_core_pagenum'])) {
    $_SESSION['files_pagenum'] = $_REQUEST['WP_core_pagenum'];
}
if (isset($_REQUEST['WP_core_jumppage'])) {
    $_SESSION['files_pagenum'] = $_REQUEST['WP_core_jumppage'] - 1;
}
if (!isset($_SESSION['files_pagenum'])) {
    $_SESSION['files_pagenum'] = 0;
}
$passthrough = htmlspecialchars(give_passthrough(1));
$jumppath = PHP_SELF.'?h=files&l=ilist&'.give_passthrough(1).'&workfolder='.$_SESSION['files_workfolder'];
$skimpath = $jumppath.'&WP_core_pagenum=';

if (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $ordfields)) {
    $orderby = $_REQUEST['orderby'];
    $orderdir = (isset($_REQUEST['orderdir']) && 'DESC' == $_REQUEST['orderdir']) ? 'DESC' : 'ASC';
} else {
    $orderby  = 'type';
    $orderdir = 'ASC';
}
$ordlink = '&orderby='.$orderby.'&orderdir='.$orderdir;
$pattern = isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : false;
$search_path = '';
if ($pattern !== false) {
    $search_path = '&pattern='.$pattern;
    $ordlink .= $search_path;
}
$FS = new handler_files_driver($_SESSION['phM_uid']);
$MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop', true);
$TN = new DB_Controller_Thumb();

if (isset($_REQUEST['iteminfo']) && $_REQUEST['iteminfo']) {
    session_write_close();
    $info = $FS->get_item_info($_REQUEST['iteminfo'], true);
    $mimename = $MIME->get_typename_from_type($info['type']);
    $thumburl = '';
    $info['thumb'] = $TN->get('files', $_REQUEST['iteminfo'], 'fdetail');
    if (is_array($info['thumb'])) {
        if ($info['thumb']['mime'] && 0 != $info['thumb']['size']) {
            $thumburl = PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&getthumb='.$_REQUEST['iteminfo'];
        }
    }
    $dimensions = '';
    if ($info['img_w'] > 0 && $info['img_h'] > 0) $dimensions = $WP_msg['Measurements'].': '.$info['img_w'].' x '.$info['img_h'];
    $lastchange = '';
    if ($info['ctime']) {
        $df = (date('Y', $info['ctime']) == date('Y')) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'];
        $lastchange .= $WP_msg['UploadedOn'].' '.date($df, $info['ctime']).'<br />';
    }
    if ($info['atime']) {
        $df = (date('Y', $info['atime']) == date('Y')) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'];
        $lastchange .= $WP_msg['LastAccessOn'].' '.date($df, $info['ctime']).'<br />';
    }
    if ($info['mtime']) {
        $df = (date('Y', $info['mtime']) == date('Y')) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'];
        $lastchange .= $WP_msg['LastChangeOn'].' '.date($df, $info['ctime']).'<br />';
    }
    sendJS(array('iteminfo' => array('mimename' => $mimename, 'thumburl' => $thumburl
            ,'filesize' => $WP_msg['FileSize'].': '.size_format($info['size']), 'dimensions' => $dimensions
            ,'lastchange' => $lastchange)), 1, 1);
}
if (isset($_REQUEST['getthumb']) && $_REQUEST['getthumb']) {
    $thumb = $TN->get('files', $_REQUEST['getthumb'], (isset($_REQUEST['type'])) ? $_REQUEST['type'] : 'fdetail');
    if (empty($thumb)) { // Try to create thumbnail
        $FS->create_item_thumbs($_REQUEST['getthumb']);
        $thumb = $TN->get('files', $_REQUEST['getthumb'], (isset($_REQUEST['type'])) ? $_REQUEST['type'] : 'fdetail');
    }
    session_write_close();
    if (empty($thumb) || empty($thumb['mime']) || empty($thumb['size'])) {
        exit;
    }
    header('Content-Type: '.$thumb['mime']);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.$thumb['size']);
    header('Connection: close');
    echo $thumb['stream'];
    echo CRLF.CRLF;
    exit;
}
if (isset($_REQUEST['audioplayer']) && $_REQUEST['audioplayer']) {
    $info = $FS->get_item_info($_REQUEST['audioplayer'], true);
    if (!$info || !in_array($info['type'], array('audio/mpeg', 'audio/wav'))) {
        exit; #FIXME More graceful, please...
    }
    $outer_template = '3dframed.tpl';
    $tpl = new phlyTemplate($_PM_['path']['templates'].'core.audioplayer.tpl');
    $tpl->assign(array
            ('file_url' => PHP_SELF.'?l=output&h=files&'.$passthrough.'&item='
            ,'id' => intval($_REQUEST['audioplayer'])
            ));
    return;
}

if (isset($_REQUEST['videoplayer']) && $_REQUEST['videoplayer']) {
    $info = $FS->get_item_info($_REQUEST['audioplayer'], true);
    if (!$info || !in_array($info['type'], array('video/x-flv', 'video/mp4'))) {
        exit; #FIXME More graceful, please...
    }
    $tpl = new phlyTemplate($_PM_['path']['templates'].'core.videoplayer.tpl');
    $tpl->assign(array
            ('file_url' => PHP_SELF.'?l=output&h=files&'.$passthrough.'&item='
            ,'id' => intval($_REQUEST['videoplayer'])
            ));
    return;
}

$eingang = 0;
$choices = array();
$foldertype = false;
$folder = $FS->get_folder_info($_SESSION['files_workfolder']);
if (false !== $folder) {
    $eingang = $folder['itemnum'];
    $foldertype = $folder['icon'];
    $workfolder = $_SESSION['files_workfolder'];
    // Extract choices for this folder; the preview setting, fields to show
    /*$choices = unserialize($folder['settings']);
    if (isset($choices['use_preview'])) $use_preview = $choices['use_preview'];
    */
}

if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize']) {
    $displaystart = 1;
    $i = $displayend = $eingang;
} else {
    if ($_SESSION['files_pagenum'] < 0) $_SESSION['files_pagenum'] = 0;
    if ($_PM_['core']['pagesize'] * $_SESSION['files_pagenum'] > $eingang) {
        $_SESSION['files_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['files_pagenum'] + 1;
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['files_pagenum'] + 1);
    if ($displayend > $eingang) $displayend = $eingang;
    $i = $displayend;
}
$groesse = $FS->init_items($workfolder, $displaystart-1, ($displayend-$displaystart+1), $orderby, $orderdir, null, $pattern);
$all_size = isset($groesse['size']) ? $groesse['size'] : 0;
$eingang = isset($groesse['items']) ? $groesse['items'] : 0;
$plural = ($eingang == 1) ? $WP_msg['File'] : $WP_msg['Files'];
$myPageNum = $_SESSION['files_pagenum'];
// We do no longer need the session from this point on
session_write_close();
$tpl = new phlyTemplate($_PM_['path']['templates'].'files.view.tiled.tpl');

$sumgroess = 0;
$tpl_lines = $tpl->get_block('item');
$t_ii = $tpl_lines->get_block('isimage');
foreach (range($displaystart, $displayend) as $i) {
    $item = $FS->get_item_info($i-1);
    if (false === $item || empty($item)) {
        $FS->resync_folder($workfolder);
        $displayend--;
        $eingang--;
        continue;
    }
    if ($item['size'] == 0) { // Could happen when upgrading from Yoko 3.6 to 3.7
        $path = $FS->item_get_real_location($item['id']);
        $item['size'] = filesize($_PM_['path']['userbase'].'/'.$item['uid'].'/files/'.$path[0].'/'.$path[1]);
        $FS->update_item($item['id'], array('size' => $item['size']));
    }
    $groesse = isset($item['size']) ? $item['size'] : 0;

    if ($groesse > 0) {
        $sumgroess += $groesse;
    } else {
        $groesse = '-';
    }

    $mimeicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/64', $item['type'], array('png', 'gif', 'jpg'));
    if (!$mimeicon) {
        $mimeinfo = $MIME->get_type_from_name($item['friendly_name'], true);
        $mimeicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/64', $mimeinfo[0], array('png', 'gif', 'jpg'));
        $item['type'] = $mimeinfo[0];
    }

    if (in_array($item['type'], array('image/jpeg', 'image/png', 'image/x-ms-bmp', 'image/gif', 'image/ico', 'image/tiff'/*, 'application/pdf'*/))) {
        $thumb = $TN->get('files', $item['id'], 'ftile');
        $t_ii->assign(array
                ('mimetype' => $MIME->get_typename_from_type($item['type'], true)
                ,'imgsrc' => htmlspecialchars(PHP_SELF.'?l=output&h=files&inline=1&'.$passthrough.'&item='.$item['id'])
                ));
        if (empty($thumb)) {
            $t_ii->assign_block('mime_preload');
            $t_ii->assign(array
                    ('mimeicon' => htmlspecialchars($_PM_['path']['frontend'].'/filetypes/64/'.$mimeicon)
                    ,'thumbsrc' => htmlspecialchars(PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&getthumb='.$item['id'].'&type=ftile')
                    ));
        } else {
            $t_ii->assign('mimeicon', htmlspecialchars(PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&getthumb='.$item['id'].'&type=ftile'));
        }
        $tpl_lines->assign('isimage', $t_ii);
        $t_ii->clear();
    } else {
        // Change this to sth. more clever, which just adds the right ondblclick handler to the
        // itemcont div instead of different markup for different things.
        // Additonally it should be checked, how the automatic handling of text files, MP3/WAV and Video
        // formats can be achieved in a generalized manner
        if (in_array($item['type'], array('audio/mpeg'/*, 'audio/wav'*/))) {
            $tpl_lines->fill_block('isaudio', array
                    ('mimeicon' => $_PM_['path']['frontend'].'/filetypes/64/'.$mimeicon
                    ,'mimetype' => $MIME->get_typename_from_type($item['type'], true)
                    ));
        }/* elseif (in_array($item['type'], array('video/x-flv', 'video/mp4'))) { # FIXME the video player does not work right now
            $tpl_lines->fill_block('isvideo', array
                    ('mimeicon' => $_PM_['path']['frontend'].'/filetypes/64/'.$mimeicon
                    ,'mimetype' => $MIME->get_typename_from_type($item['type'], true)
                    ,'flvsrc' => htmlspecialchars(PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&videoplayer='.$item['id'])
                    ));
        }*/ else {
            $tpl_lines->fill_block('nohandling', array
                    ('mimeicon' => $_PM_['path']['frontend'].'/filetypes/64/'.$mimeicon
                    ,'mimetype' => $MIME->get_typename_from_type($item['type'], true)
                    ));
        }
    }
    $tpl_lines->assign(array
            ('filename' => phm_entities(phm_stripslashes($item['friendly_name']))
            ,'mimeicon_s' => $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/16', $item['type'], array('png', 'gif', 'jpg'))
            ,'id' => $i
            ,'uid' => $item['id']
            ));
    $tpl->assign('item', $tpl_lines);
    $tpl_lines->clear();
}
$max_page = 0;
// Handle Jump to Page Form
if (isset($_PM_['core']['pagesize']) && $_PM_['core']['pagesize']) $max_page = ceil($eingang / $_PM_['core']['pagesize']);
$jumpsize = strlen($max_page);

// Permissions reflected in context menu items
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_add_file']) {
    $tpl->assign_block('ctx_copy');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_update_file']) {
    $tpl->assign_block('ctx_rename');
    $tpl->assign_block('ctx_move');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['files_delete_file']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('rawsumsize' => number_format($sumgroess, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'sumsize' => size_format($sumgroess)
        ,'rawallsize' => number_format($all_size, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'allsize' => size_format($all_size)
        ,'size' => $jumpsize
        ,'maxlen' => $jumpsize
        ,'page' => $myPageNum + ($eingang < 1 ? 0 : 1)
        ,'pagenum' => $myPageNum
        ,'boxsize' => abs($max_page)
        ,'neueingang' => $eingang < 1 ? 0 : $eingang // number_format($eingang, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'displaystart' => ($eingang < 1) ? 0 : $displaystart
        ,'displayend' => ($eingang < 1) ? 0 : $displayend
        ,'plural' => $plural
        ,'msg_date' => $WP_msg['date']
        ,'preview_hdate' => $WP_msg['date']
        ,'msg_size' => $WP_msg['size']
        ,'del' => $WP_msg['del']
        ,'go' => $WP_msg['goto']
        ,'but_search' => $WP_msg['ButSearch']
        ,'msg_page' => $WP_msg['page']
        ,'passthrough_2' => give_passthrough(2)
        ,'selection' => $WP_msg['selection']
        ,'allpage' => $WP_msg['allpage']
        ,'msg_copy' => $WP_msg['copytofolder']
        ,'msg_move' => $WP_msg['movetofolder']
        ,'msg_rename' => $WP_msg['FileRename']
        ,'msg_sendasmail' => $WP_msg['FileSendAsMail']
        ,'msg_renameto' => $WP_msg['SetFldRenameTo']
        ,'newmails' => $WP_msg['Files']
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_save' => $WP_msg['FileDownload']
        ,'msg_killconfirm' => $WP_msg['killJSconfirm']
        ,'head_folderprops' => $WP_msg['FolderProps']
        ,'head_fileupload' => $WP_msg['FileUpload']
        ,'head_filefind' => $WP_msg['FileSearch']
        ,'head_fileprops' => $WP_msg['FileProps']
        ,'search' => $WP_msg['ButSearch']
        ,'but_dele' => $WP_msg['del']
        ,'but_save' => $WP_msg['FileDownload']
        ,'but_upload' => $WP_msg['FileUpload']
        ,'but_getfromurl' => $WP_msg['DownloadFromURL']
        ,'handler' => 'files'
        ,'PHP_SELF' => PHP_SELF
        ,'passthrough' => $passthrough
        ,'UL_ID' => uniqid($_SESSION['phM_uid'], true)
        ,'UL_ID_NAME' => $uplIDName
        ,'jump_url' => htmlspecialchars($jumppath.$ordlink)
        ,'jump_url_js' => $jumppath.$ordlink
        ,'itemops_url' => PHP_SELF.'?l=worker&h=files&'.$passthrough.'&what=item_'
        ,'sendasmail_url' => PHP_SELF.'?'.$passthrough.'&l=compose_email&receive_file=1&from_h=files&h=core'
        ,'search_url' => phm_entities($jumppath.$ordlink)
        ,'upload_url' => phm_entities($jumppath.$ordlink.'&upl=1')
        ,'upload_progress_url_js' => $jumppath.$ordlink.'&upl=1&uplInf='
        ,'iteminfo_url' => PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&iteminfo='
        ,'audioplayer_win_url' => PHP_SELF.'?l=ilist&h=files&'.$passthrough.'&audioplayer='
        ,'dlfile_url' => PHP_SELF.'?'.$passthrough.'&h=files&l=output&save_as=raw&item='
        ));
