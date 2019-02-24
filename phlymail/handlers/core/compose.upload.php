<?php
/**
 * Add an attachment to a currently edited mail
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Core
 * @copyright 2005-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.0 2013-01-08 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

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
/**
 * Frontend requests upload progress information
 * This requires either APCs implementation or the PECL extension uploadprogress,
 * you can easily get the PECL one with "pecl install uploadprogress"
 */
if (isset($_REQUEST['uplInf']) && $_REQUEST['uplInf']) {
    session_write_close();
    $info = array('time_start' => 0, 'time_last' => 0, 'speed_average' => 0
            ,'speed_last' => 0, 'bytes_uploaded' => 0, 'bytes_total' => 0
            ,'files_uploaded' => 0, 'est_sec' => 0);
    if ($uplProgress == 'apc') {
        $ulinfo = apc_fetch($apcPrefix.$_REQUEST['uplInf']);
        if (is_array($ulinfo)) {
            $info = array('speed_average' => $ulinfo['rate']
                    ,'bytes_uploaded' => $ulinfo['current']
                    ,'bytes_total' => $ulinfo['total']
                    );
        }
    } elseif ($uplProgress == 'pecl') {
        try {
            $ulinfo = @uploadprogress_get_info($_REQUEST['uplInf']);
            if (is_array($ulinfo)) $info = $ulinfo;
        } catch (Exception $e) { }
    }
    $info['field'] = $_REQUEST['uplInf'];
    sendJS(array('upload_stats' => $info), 1, 1);
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'send.upload.tpl');
$t_js = $tpl->get_block('jsforparent');
if (isset($_FILES) && isset($_FILES['file']) && !empty($_FILES['file'])) {
    $assigned = false;
    foreach ($_FILES['file']['name'] as $k => $v) {
        if (!is_uploaded_file($_FILES['file']['tmp_name'][$k])) continue; // Skip things, being no upload
        $uploadname = uniqid(time().'.', true);
        move_uploaded_file($_FILES['file']['tmp_name'][$k], $_PM_['path']['temp'].'/'.$uploadname);
        $MIME = new handleMIME($_PM_['path']['conf'].'/mime.map.wpop');
        $type = ($_FILES['file']['type'][$k]) ? $_FILES['file']['type'][$k] : $MIME->get_type_from_name($_FILES['file']['name'][$k]);
        $sicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/16', $type, array('gif', 'png', 'jpg', 'jpeg'));
        $bicon = $MIME->get_icon_from_type($_PM_['path']['frontend'].'/filetypes/64', $type, array('gif', 'png', 'jpg', 'jpeg'));
        if (!$assigned) {
            if ($tpl->block_exists('onupload')) {
                $tpl->assign_block('onupload');
            }
            $assigned = true;
            $tpl->assign(array
                    ('name' => $_FILES['file']['name'][$k]
                    ,'filename'  => $uploadname
                    ,'big_icon' => $_PM_['path']['frontend'].'/filetypes/64/'.$bicon
                    ,'mimetype' => $_FILES['file']['type'][$k]
                    ,'opener' => (isset($_REQUEST['opener'])) ? $_REQUEST['opener'] : false
                    ));
        }
        $t_js->assign(array
                    ('name' => $_FILES['file']['name'][$k]
                    ,'filename'  => $uploadname
                    ,'small_icon' => $_PM_['path']['frontend'].'/filetypes/16/'.$sicon
                    ,'mimetype' => $_FILES['file']['type'][$k]
                    ));
        $tpl->assign('jsforparent', $t_js);
        $t_js->clear();
    }
} else {
    $tpl->assign_block('default');
}
$passthru = give_passthrough(1);
$tpl->assign(array
        ('action' => htmlspecialchars(PHP_SELF.'?l=compose_email_upload&h=core&'.$passthru)
        ,'opener' => (isset($_REQUEST['opener'])) ? $_REQUEST['opener'] : false
        ,'msg_select' => $WP_msg['UploadSelectFiles']
        ,'msg_upload' => $WP_msg['Upload']
        ,'about_done' => $WP_msg['UploadDone']
        ,'UL_ID' => uniqid($_SESSION['phM_uid'], true)
        ,'UL_ID_NAME' => $uplIDName
        ,'upload_progress_url_js' => PHP_SELF.'?l=upload&h=files&'.$passthru.'&uplInf='
        ));
if (false !== ($maxfilesize = ini_get('upload_max_filesize')) && $maxfilesize) {
    if ($tpl->block_exists('maxfilesize')) {
        $tpl->fill_block('maxfilesize', 'maxfilesize', wash_size_field($maxfilesize));
    }
    $tpl->assign('msg_maxfilesize', $WP_msg['MaxFilesize'].': '.size_format(wash_size_field($maxfilesize), 0, 0, 0));
}
