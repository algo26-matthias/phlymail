<?php
/**
 * Add a file to the personal storage of a user
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Files
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.4 2015-06-29
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// We got to have the upload_tmp_dir set to something useful
if (!ini_get('upload_tmp_dir')) {
    ini_set('upload_tmp_dir', $_PM_['path']['temp']);
}

$uplIDName = 'DOWNLOAD_IDENTIFIER';
/**
 * Frontend requests download progress information
 * This requires either APCs implementation or the PECL extension uploadprogress,
 * you can easily get the PECL one with "pecl install uploadprogress", APC
 * is recommended for caching purposes
 */
if (isset($_REQUEST['uplInf']) && $_REQUEST['uplInf']) {
    $uid = $_SESSION['phM_uid'];
    session_write_close();
    $nfo = basename($_REQUEST['uplInf']);
    $info = array
            ('time_start' => 0, 'time_last' => 0, 'speed_average' => 0
            ,'speed_last' => 0, 'bytes_uploaded' => 0, 'bytes_total' => 0
            ,'files_uploaded' => 0, 'est_sec' => 0
            );
    if (false !== ($ulinfo = @file_get_contents($_PM_['path']['temp'].'/'.$nfo.'.dnl'))) {
        $ulinfo = explode(';', $ulinfo);
        if (is_array($ulinfo) && isset($ulinfo[3])) {
            $rate = ($ulinfo[0]-$ulinfo[1] != 0) ? $ulinfo[3]/($ulinfo[0]-$ulinfo[1]) : 0;
            $info = array('bytes_uploaded' => $ulinfo[3], 'bytes_total' => $ulinfo[2]
                    ,'time_start' => $ulinfo[0], 'time_last' => $ulinfo[1]
                    ,'est_sec' => ($rate != 0 && $ulinfo[3] != 0 && $ulinfo[2] != 0) ? ($ulinfo[2]-$ulinfo[3])/$rate : 0
                    ,'speed_average' => $rate
                    );
        }
    }
    $info['field'] = $nfo;
    sendJS(array('upload_stats' => $info), 1, 1);
}

$tpl  = new phlyTemplate($_PM_['path']['templates'].'files.upload.tpl');
$FS   = new handler_files_driver($_SESSION['phM_uid']);
$MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
$FS->init_folders();

$passthru = give_passthrough(1);
$defaultFolder = (isset($_SESSION['files_workfolder']) && $_SESSION['files_workfolder']) ? $_SESSION['files_workfolder'] : false;
$destfolder = (isset($_REQUEST['folder'])) ? intval($_REQUEST['folder']) : $defaultFolder;

// URL given
if (!empty($_REQUEST['get_from_url'])) {

    $uploadname = basename($_REQUEST[$uplIDName]);
    $destinfo = $FS->get_folder_info($destfolder);
    $destpath = $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/files/'.$destinfo['folder_path'].'/'.$uploadname;
    $ulinfo = $_PM_['path']['temp'].'/'.$uploadname.'.dnl';
    $stat = basics::download($_REQUEST['get_from_url'], $destpath, $ulinfo, 1073741824); // Replace max size with user's quota limit
    switch ($stat['error']) {
        case UPLOAD_ERR_INI_SIZE: $error = 'The file was too large.'; break;
        case UPLOAD_ERR_PARTIAL: $error = 'The file was only partially downloaded.'; break;
        case UPLOAD_ERR_NO_FILE: $error = 'Could not download the file'; break;
        case UPLOAD_ERR_CANT_WRITE: $error = 'Failed to write file to disk'; break;
    }
    list ($type) = $MIME->get_type_from_name($stat['name'], false);
    if (!$type) $type = ($stat['type']) ? $stat['type'] : 'application/octet-stream';
    $bicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/64', $type, array('png', 'jpg', 'jpeg', 'gif'));
    // Images get a nice thumbnail, additionally we gather some info for the indexer
    $ii = (substr($type, 0, 6) == 'image/') ? getimagesize($destpath) : array(0, 0, 0);
    $friendlyname = basename(phm_stripslashes($stat['name']));
    $exists = $FS->item_exists($friendlyname, $destfolder);
    if ($exists) {
        $expos = strrpos($friendlyname, '.');
        // Yes, we mean "Not found" AND "on position 0"!
        if (!$expos) {
            $basename = $friendlyname;
            $ext = '';
        } else {
            $basename = substr($friendlyname, 0, $expos);
            $ext = substr($friendlyname, $expos);
        }
        $adder = 1;
        while (true) {
            $match = $FS->item_exists($basename.' ('.$adder.')'.$ext, $destfolder);
            if (!$match) {
                $friendlyname = $basename.' ('.$adder.')'.$ext;
                break;
            }
            ++$adder;
        }
    }
    $FS->file_item(array(
            'folder_id' => $destfolder,
            'filed' => true,
            'filename' => $uploadname,
            'friendlyname' => $friendlyname,
            'type' => $type,
            'size' => $stat['size'],
            'img_w' => $ii[0],
            'img_h' => $ii[1]
            ));
    $tpl->assign_block('onupload');
    $tpl->assign(array
            ('name' => $stat['name']
            ,'big_icon' => $_PM_['path']['frontend'].'/filetypes/64/'.$bicon
            ,'mimetype' => $stat['type']
            ,'opener' => (isset($_REQUEST['opener'])) ? $_REQUEST['opener'] : false
            ));
// File(s) selected
} elseif (isset($_FILES) && isset($_FILES['file']) && !empty($_FILES['file'])) {
    foreach ($_FILES['file']['name'] as $k => $v) {
        if (!is_uploaded_file($_FILES['file']['tmp_name'][$k])) continue; // Skip things, being no upload
        switch ($_FILES['file']['error'][$k]) {
            case UPLOAD_ERR_OK:         /* Just catching it */  break;
            case UPLOAD_ERR_INI_SIZE:   throw new Exception('The uploaded file exceeds the upload_max_filesize directive ('.ini_get('upload_max_filesize').') in php.ini.'); break;
            case UPLOAD_ERR_FORM_SIZE:  throw new Exception('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'); break;
            case UPLOAD_ERR_PARTIAL:    throw new Exception('The uploaded file was only partially uploaded.'); break;
            case UPLOAD_ERR_NO_FILE:    throw new Exception('No file was uploaded.'); break;
            case UPLOAD_ERR_NO_TMP_DIR: throw new Exception('Missing a temporary folder.'); break;
            case UPLOAD_ERR_CANT_WRITE: throw new Exception('Failed to write file to disk'); break;
            default: throw new Exception('Unknown File Error');
        }
        $uploadname = uniqid(time().'.', true);
        list ($type) = $MIME->get_type_from_name($_FILES['file']['name'][$k], false);
        if (!$type) $type = ($_FILES['file']['type'][$k]) ? $_FILES['file']['type'][$k] : 'application/octet-stream';
        $bicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/64', $type, array('png', 'jpg', 'jpeg', 'gif'));
        $destinfo = $FS->get_folder_info($destfolder);
        $ii = getimagesize($_FILES['file']['tmp_name'][$k]);
        move_uploaded_file
                ($_FILES['file']['tmp_name'][$k]
                ,$_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/files/'.$destinfo['folder_path'].'/'.$uploadname
                );
        $friendlyname = basename(phm_stripslashes($_FILES['file']['name'][$k]));
        $exists = $FS->item_exists($friendlyname, $destfolder);
        if ($exists) {
            $expos = strrpos($friendlyname, '.');
            // Yes, we mean "Not found" AND "on position 0"!
            if (!$expos) {
                $basename = $friendlyname;
                $ext = '';
            } else {
                $basename = substr($friendlyname, 0, $expos);
                $ext = substr($friendlyname, $expos);
            }
            $adder = 1;
            while (true) {
                $match = $FS->item_exists($basename.' ('.$adder.')'.$ext, $destfolder);
                if (!$match) {
                    $friendlyname = $basename.' ('.$adder.')'.$ext;
                    break;
                }
                ++$adder;
            }
        }
        $id = $FS->file_item(array
                ('folder_id' => $destfolder
                ,'filed' => true
                ,'filename' => $uploadname
                ,'friendlyname' => $friendlyname
                ,'type' => $type
                ,'size' => $_FILES['file']['size'][$k]
                ,'img_w' => $ii[0]
                ,'img_h' => $ii[1]
                ));
        echo LF.LF.$id;
    }
    $tpl->assign_block('onupload');
    $tpl->assign(array
            ('name' => $friendlyname
            ,'big_icon' => $_PM_['path']['frontend'].'/filetypes/64/'.$bicon
            ,'mimetype' => $type
            ,'opener' => (isset($_REQUEST['opener'])) ? $_REQUEST['opener'] : false
            ));
} else {
    $tpl->assign_block('default');
}

$t_inb = $tpl->get_block('destfolder');
foreach ($FS->read_folders_flat() as $id => $data) {
    $lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level'] * 2) : '';
    $t_inb->assign(array
            ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id.($id == $destfolder ? '" selected="selected' : '')
            ,'name' => $lvl_space . phm_entities($data['foldername'])
            ));
    $tpl->assign('destfolder', $t_inb);
    $t_inb->clear();
}
$tpl->assign(array
        ('action' => htmlspecialchars(PHP_SELF.'?l=upload&h=files&'.$passthru, ENT_COMPAT, 'utf-8')
        ,'leg_choosefile' => $WP_msg['LegRemoteFile']
        ,'leg_localfolder' => $WP_msg['LegLocalFolder']
        ,'about_choosefile' => $WP_msg['UploadSelectFiles']
        ,'msg_upload' => $WP_msg['Upload']
        ,'msg_filetofolder' => $WP_msg['AboutLocalFolder']
        ,'msg_select' => $WP_msg['EnterURL']
        ,'leg_enterurl' => $WP_msg['DownloadFromURL']
        ,'UL_ID' => uniqid($_SESSION['phM_uid'], true)
        ,'UL_ID_NAME' => $uplIDName
        ,'upload_progress_url_js' => PHP_SELF.'?l=getfromurl&h=files&'.$passthru.'&uplInf='
        ,'about_done' => $WP_msg['UploadDone']
        ));
if (false !== ($maxfilesize = ini_get('upload_max_filesize')) && $maxfilesize) {
    $tpl->fill_block('maxfilesize', 'maxfilesize', wash_size_field($maxfilesize));
    $tpl->assign('msg_maxfilesize', $WP_msg['MaxFilesize'].': '.size_format(wash_size_field($maxfilesize), 0, 0, 0));
}
