<?php
/**
 * Receive something from another handler
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2006-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.0 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$iconSize = 128; // Size of the MIME type icon

$tpl = new phlyTemplate($_PM_['path']['templates'].'files.sendto.tpl');
$FS = new handler_files_driver($_SESSION['phM_uid']);
$MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
$srchdl = preg_replace('![^a-zA-Z_]!', '', $_REQUEST['source']);
$toload = 'handler_'.$srchdl.'_api';
$API = new $toload($_PM_, $_SESSION['phM_uid']);
$srcinfo = $API->sendto_fileinfo($_REQUEST['resid']);
// User specified a new name to use. We stick to it, even in case of bullshit...
if (isset($_REQUEST['override_name']) && strlen($_REQUEST['override_name'])) {
    $srcinfo['filename'] = basename($_REQUEST['override_name']);
    $srcinfo['content_type'] = $MIME->get_type_from_name($srcinfo['filename'], 1);
    $srcinfo['content_type'] = $srcinfo['content_type'][0];
}

$bicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/'.$iconSize, $srcinfo['content_type'], array('gif', 'png', 'jpg', 'jpeg'));
if (substr($bicon, 0, 3) == '__.') {
    $bicon = $MIME->get_type_from_name($srcinfo['filename']);
    $bicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/'.$iconSize, $bicon[0], array('gif', 'png', 'jpg', 'jpeg'));
}
if (isset($_REQUEST['destfolder'])) {
    $uploadname = time().'.'.getmypid();
    $destinfo = $FS->get_folder_info($_REQUEST['destfolder']);
    $destpath = $_PM_['path']['userbase'].'/'.$_SESSION['phM_uid'].'/files/'.$destinfo['folder_path'].'/'.$uploadname;
    // Receive the file - currently one huge string, so beware
    file_put_contents($destpath, $API->sendto_sendinit($_REQUEST['resid']));
    // Images get a nice thumbnail, additionally we gather some info for the indexer
    $ii = array(0, 0, 0);
    if (substr($srcinfo['content_type'], 0, 6) == 'image/') $ii = getimagesize($destpath);
    // Only try creating the thumbnail with the correct GD support. Unfortunately GIF got dropped a while ago, JPEG or PNG might not be compiled in
    if ($ii[2] == 1 && !function_exists('imagecreatefromgif')) $ii[2] = 0;
    if ($ii[2] == 2 && !function_exists('imagecreatefromjpeg')) $ii[2] = 0;
    if ($ii[2] == 3 && !function_exists('imagecreatefrompng')) $ii[2] = 0;
    if ($ii[2] == 15 && !function_exists('imagecreatefromwbmp')) $ii[2] = 0;
    if (false !== $ii && $ii[2] > 0 && $ii[2] < 4) {
        $has_thumb = '0';
        if (function_exists('imagecreatetruecolor')) {
            $has_thumb = '1';
            $ti = $ii;
            if ($ti[0] > 190 || $ti[1] > 190) {
                $wf = $ti[0] / 190; // Calculate width factor
                $hf = $ti[1] / 190; // Calculate height factor
                if ($wf >= $hf && $wf > 1) {
                    $ti[0] /= $wf;
                    $ti[1] /= $wf;
                } elseif ($hf > 1) {
                    $ti[0] /= $hf;
                    $ti[1] /= $hf;
                }
                $ti[0] = round($ti[0], 0);
                $ti[1] = round($ti[1], 0);
            }
            if ($ii[2] == 1) {
                $si = imagecreatefromgif($destpath);
            } elseif ($ii[2] == 2) {
                $si = imagecreatefromjpeg($destpath);
            } elseif ($ii[2] == 3) {
                $si = imagecreatefrompng($destpath);
            } elseif ($ii[2] == 15) {
                $si = imagecreatefromwbmp($destpath);
            }

            if (!$si) {
                $has_thumb = '0';
                $ti = array(0 => 0, 1 => 0);
            } else {
                $tn = imagecreatetruecolor($ti[0], $ti[1]);
                imagecopyresampled($tn, $si, 0, 0, 0, 0, $ti[0], $ti[1], $ii[0], $ii[1]);
                // Get the thumbnail and populate thumbinfo
                ob_start();
                if (imagetypes() & IMG_PNG) {
                    $thmime = 'image/png';
                    imagepng($tn, null);
                } elseif (imagetypes() & IMG_JPG) {
                    $thmime = 'image/jpeg';
                    imagejpeg($tn, null, 75);
                } elseif (imagetypes() & IMG_GIF) {
                    $thmime = 'image/gif';
                    imagegif($tn, null);
                }
                $thsize = ob_get_length();
                $thstream = ob_get_contents();
                ob_end_clean();
                imagedestroy($tn);
            }
        }
    } else {
        $ti = array(0 => 0, 1 => 0);
        $has_thumb = '0';
    }
    $friendlyname = basename(phm_stripslashes($srcinfo['filename']));
    $exists = $FS->item_exists($friendlyname, $_REQUEST['destfolder']);
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
            $match = $FS->item_exists($basename.' ('.$adder.')'.$ext, $_REQUEST['destfolder']);
            if (!$match) {
                $friendlyname = $basename.' ('.$adder.')'.$ext;
                break;
            }
            ++$adder;
        }
    }
    $FS->file_item(array
            ('folder_id' => $_REQUEST['destfolder']
            ,'filed' => true
            ,'filename' => $uploadname
            ,'friendlyname' => $friendlyname
            ,'type' => $srcinfo['content_type']
            ,'size' => filesize($destpath)
            ,'img_w' => $ii[0]
            ,'img_h' => $ii[1]
            ,'thumb' => serialize(array
                    ('has' => $has_thumb, 'w' => $ti[0], 'h' => $ti[1]
                    ,'mime' => ($has_thumb) ? $thmime : ''
                    ,'size' => ($has_thumb) ? $thsize : 0
                    ,'stream' => ($has_thumb) ? $thstream : ''
                    ))
            ));
    $tpl->assign_block('done');
    return;
}
$FS->init_folders();
$t_inb = $tpl->get_block('destfolder');
foreach ($FS->read_folders_flat() as $id => $data) {
    $lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level'] * 2) : '';
    $t_inb->assign(array
            ('id' => (!$data['has_items'])
                    ? '" style="color:darkgray;" disabled="disabled'
                    : $id.(isset($defaultFolder) && $id == $defaultFolder ? '" selected="selected' : '')
            ,'name' => $lvl_space . phm_entities($data['foldername'])
            ));
    $tpl->assign('destfolder', $t_inb);
    $t_inb->clear();
}
$tpl->assign(array
        ('baseurl' => htmlspecialchars(PHP_SELF.'?l=sendto&h=files&source='.$_REQUEST['source'].'&resid='.$_REQUEST['resid'].'&'.give_passthrough(1))
        ,'msg_filetofolder' => $WP_msg['FldrBrwsSelect']
        ,'msg_ok' => $WP_msg['save']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'big_icon' => $_PM_['path']['frontend'].'/filetypes/'.$iconSize.'/'.$bicon
        ,'mimetype' => $srcinfo['content_type']
        ,'name' => htmlspecialchars($srcinfo['filename'], ENT_COMPAT, 'utf-8')
        ));
