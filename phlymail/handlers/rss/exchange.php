<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler RSS
 * @subpackage Import / Export
 * @copyright 2002-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2013-08-06 $Id: exchange.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

// Might exist in both the sessin and a variable form the API
$myPrivs = isset($_phM_privs) ? $_phM_privs : $_SESSION['phM_privs'];

$myurl = PHP_SELF.'?l=exchange&h=rss';
if (!$myPrivs['all'] &&
        ($myPrivs['rss_export_feeds'] == 0 && $myPrivs['rss_import_feeds'] == 0)) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$cDB = new handler_rss_driver(defined('PHM_API_UID') ? PHM_API_UID : $_SESSION['phM_uid']);
$do = false;
if (defined('PHM_RSS_EX_DO')) {
    $do = PHM_RSS_EX_DO;
} elseif (isset($_REQUEST['do']) && $_REQUEST['do']) {
    $do = $_REQUEST['do'];
}
$return = false;

if ('export' == $do) {
    if (!$myPrivs['all'] && !$myPrivs['rss_export_feeds']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $exgroup = 0;
    if (defined('PHM_RSS_EX_GROUP')) {
        $exgroup = PHM_RSS_EX_GROUP;
    } elseif (isset($_REQUEST['exgroup'])) {
        $exgroup = intval($_REQUEST['exgroup']);
    }
    $export_format = false;
    if (defined('PHM_RSS_EX_FORMAT')) {
        $export_format = PHM_RSS_EX_FORMAT;
    } elseif (isset($_REQUEST['exform'])) {
        $export_format = $_REQUEST['exform'];
    }

    switch ($export_format) {
    case 'OPML':
        if (defined('PHM_RSS_EX_FORMAT')) {
            header('Content-Type: text/x-opml; charset=UTF-8');
            header('Content-Disposition: inline; filename=feeds.opml');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=feeds.opml');
        }
        break;

        if ($exgroup) {
            $root = $cDB->get_folder($exgroup);
        } else {
            $root = array('id' => 0, 'name' => 'phlyMail Feeds', 'description' => '');
        }
        if (false !== $root) {
            echo '<?xml version="1.0" encoding="utf-8"?>'.LF
                    .'<opml version="2.0">'.LF
                    .'    <head>'.LF
                    .'        <title>phlyMail Feeds</title>'.LF
                    .'        <dateCreated>'.date('r').'</dateCreated>'.LF
                    .'    </head>'.LF
                    .'    <body>'.LF;
            $folders = $cDB->get_feedlist($exgroup, true);
            function xbel_output_folder(&$folders, $startWith = 0, $level = 0)
            {
                $space = str_repeat('    ', $level);
                // Output all contained feeds
                $items = $GLOBALS['cDB']->get_index(0, $startWith);
                foreach ($items as $item) {
                    echo $space.'<feed href="'.htmlspecialchars($item['url']).'" id="b'.$item['id'].'">'.LF #FIXME Support:  added="2007-11-11" modified="2007-11-14" visited="2007-11-14">
                            .$space.'    <title><![CDATA['.$item['name'].']]></title>'.LF
                            .$space.'</feed>'.LF;
                }
                // Does not have subfolders
                if (!isset($folders[$startWith])) return;

                // Iterate over subfolders
                foreach ($folders[$startWith] as $id => $folder) {
                    echo $space.'<folder id="f'.$id.'">'.LF #FIXME  Support added="2007-11-10" folded="yes|no"
                            .$space.'    <title><![CDATA['.$folder['name'].']]></title>'.LF
                            .$space.'    <desc><![CDATA['.$folder['description'].']]></desc>'.LF;
                    xbel_output_folder($folders, $id, $level+1);
                    echo $space.'</folder>'.LF;
                }
            }
            xbel_output_folder($folders, $exgroup, 2);
            echo '    </body>'.LF.'</opeml>'.LF;
        }
        break;
    default:
        $return .= $WP_msg['unkExpFrmt'].'<br />'.LF;
        $do = false;
        break;
    }
    if (!$return) exit;
}
if ('import' == $do) {
    if (!$myPrivs['all'] && !$myPrivs['rss_import_feeds']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $imported = 0;
    $imgroup = isset($_REQUEST['imgroup']) ? intval($_REQUEST['imgroup']) : 0;
    if (isset($_FILES['imfile']) || isset($_SESSION['WP_impfile'])) {
        $temp_name = $_PM_['path']['temp'].'/'.SecurePassword::generate(16, false, STRONGPASS_LOWERCASE | STRONGPASS_DECIMALS);

        if (!isset($_FILES['imfile']) && isset($_SESSION['WP_impfile'])) {
            $file = $_SESSION['WP_impfile'];
            unset($_SESSION['WP_impfile']);
        } elseif (is_uploaded_file($_FILES['imfile']['tmp_name'])) {
            move_uploaded_file($_FILES['imfile']['tmp_name'], $temp_name);
        }
        switch ($_REQUEST['imform']) {
        case 'OPML':
            $opml = new Format_Parse_OPML();
            $array = $opml->read($temp_name);
            $xmlId2dbId = array();
            if (!empty($array['folders'])) {
                foreach ($array['folders'] as $fid => $folder) {
                    $folder['childof'] = isset($xmlId2dbId[$folder['childof']]) ? $xmlId2dbId[$folder['childof']] : 0;
                    $newId = $cDB->add_folder($folder['name'], $folder['childof']);
                    $xmlId2dbId[$fid] = $newId;
                }
            }
            if (!empty($array['feeds'])) {
                foreach ($array['feeds'] as $feed) {
                    $feed['childof'] = isset($xmlId2dbId[$feed['childof']]) ? $xmlId2dbId[$feed['childof']] : 0;
                    $newFeedId = $cDB->add_feed($feed);
                    $imported++;
                }
            }
            @unlink($temp_name);
            break;
        default:
            $return .= $WP_msg['unkImpFrmt'].'<br />'.LF;
            break;
        }
    }
    $do = false;
}
if (!$do) {
    if (isset($imported) && $imported) {
        $return .= str_replace('$1', $imported, $WP_msg['ImpNum']).'<br />'.LF;
    }
    $tpl = new phlyTemplate($_PM_['path']['templates'].'rss.exchmenu.tpl');
    $passthrough2 = give_passthrough(2);
    if ($return) {
        $tpl->fill_block('return', 'return', $return);
    }
    if ($myPrivs['all'] || $myPrivs['rss_import_feeds']) {
        $tpl_imp = $tpl->get_block('import');
        $tpl_imp->assign(array
                ('target' => $myurl
                ,'msg_select' => $WP_msg['plsSel']
                ,'passthrough' => $passthrough2
                ,'about_import' => $WP_msg['AboutImport']
                ,'leg_import' => $WP_msg['Import']
                ,'msg_file' => $WP_msg['filename']
                ,'msg_format' => $WP_msg['format']
                ,'msg_group' => $WP_msg['group']
                ));
        $imop = $tpl_imp->get_block('imoption');
        foreach (array('OPML' => 'OPML') as $val => $name) {
            $imop->assign(array('value' => $val, 'name' => $name));
            $tpl_imp->assign('imoption', $imop);
            $imop->clear();
        }
        $imgr = $tpl_imp->get_block('imgroup');
        foreach ($cDB->get_folderlist(0) as $k => $v) {
            $imgr->assign(array('id' => $k, 'name' => $v['name']));
            $tpl_imp->assign('imgroup', $imgr);
            $imgr->clear();
        }
        $tpl->assign('import', $tpl_imp);
    }
    if ($cDB->quota_feedsnum(false) && ($myPrivs['all'] || $myPrivs['rss_export_feeds'])) {
        $tpl_exp = $tpl->get_block('export');
        $tpl_exp->assign(array
                ('target' => $myurl
                ,'msg_select' => $WP_msg['plsSel']
                ,'passthrough' => $passthrough2
                ,'about_export' => $WP_msg['AboutExport']
                ,'leg_export' => $WP_msg['Export']
                ,'msg_format' => $WP_msg['format']
                ,'msg_group' => $WP_msg['group']
                ));
        $exop = $tpl_exp->get_block('exoption');
        foreach (array('OPML' => 'OPML') as $val => $name) {
            $exop->assign(array('value' => $val, 'name' => $name));
            $tpl_exp->assign('exoption', $exop);
            $exop->clear();
        }
        $exgr = $tpl_exp->get_block('exgroup');
        foreach ($cDB->get_folderlist(0) as $k => $v) {
            $exgr->assign(array('id' => $k, 'name' => $v['name']));
            $tpl_exp->assign('exgroup', $exgr);
            $exgr->clear();
        }
        $tpl->assign('export', $tpl_exp);
    }
}