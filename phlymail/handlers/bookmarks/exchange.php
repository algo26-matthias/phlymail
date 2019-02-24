<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Bookmarks
 * @subpackage Import / Export
 * @copyright 2002-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2013-07-07 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Might exist in both the sessin and a variable form the API
$myPrivs = isset($_phM_privs) ? $_phM_privs : $_SESSION['phM_privs'];

$myurl = PHP_SELF.'?l=exchange&h=bookmarks';
if (!$myPrivs['all'] &&
        ($myPrivs['bookmarks_export_bookmarks'] == 0 && $myPrivs['bookmarks_import_bookmarks'] == 0)) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$cDB = new handler_bookmarks_driver(defined('PHM_API_UID') ? PHM_API_UID : $_SESSION['phM_uid']);
$do = false;
if (defined('PHM_BM_EX_DO')) {
    $do = PHM_BM_EX_DO;
} elseif (isset($_REQUEST['do']) && $_REQUEST['do']) {
    $do = $_REQUEST['do'];
}
$return = false;

if ('export' == $do) {
    if (!$myPrivs['all'] && !$myPrivs['bookmarks_export_bookmarks']) {
        $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
        $tpl->assign('output', $WP_msg['PrivNoAccess']);
        return;
    }
    $exgroup = 0;
    if (defined('PHM_BM_EX_GROUP')) {
        $exgroup = PHM_BM_EX_GROUP;
    } elseif (isset($_REQUEST['exgroup'])) {
        $exgroup = intval($_REQUEST['exgroup']);
    }
    $export_format = false;
    if (defined('PHM_BM_EX_FORMAT')) {
        $export_format = PHM_BM_EX_FORMAT;
    } elseif (isset($_REQUEST['exform'])) {
        $export_format = $_REQUEST['exform'];
    }

    switch ($export_format) {
    case 'HTML':
        if (defined('PHM_CAL_EX_FORMAT')) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename=bookmarks.html');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=bookmarks.html');
        }
        break;
    case 'XBEL':
        if (defined('PHM_CAL_EX_FORMAT')) {
            header('Content-Type: text/xml; charset=UTF-8');
            header('Content-Disposition: inline; filename=bookmarks.xbel');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=bookmarks.xbel');
        }

        if ($exgroup) {
            $root = $cDB->get_folder($exgroup);
        } else {
            $root = array('id' => 0, 'name' => 'phlyMail Bookmarks', 'description' => '');
        }
        if (false !== $root) {
            echo '<?xml version="1.0" encoding="utf-8"?>'.LF
                    .'<!DOCTYPE xbel PUBLIC "+//IDN python.org//DTD XML Bookmark Exchange Language 1.0//EN//XML" "http://www.python.org/topics/xml/dtds/xbel-1.0.dtd">'.LF
                    .'<xbel version="1.0">'.LF
                    .'    <title>phlyMail Bookmarks</title>'.LF
                    .'    <desc>written on '.date('Y-m-d H:i:s').'</desc>'.LF
                    .'    <folder id="f'.$root['id'].'">'.LF #FIXME  Support added="2007-11-10" folded="yes|no"
                    .'        <title><![CDATA['.$root['name'].']]></title>'.LF
                    .'        <desc><![CDATA['.$root['description'].']]></desc>'.LF;

            $folders = $cDB->get_folderlist($exgroup, true);
            function xbel_output_folder(&$folders, $startWith = 0, $level = 0)
            {
                $space = str_repeat('    ', $level);
                // Output all contained bookmarks
                $items = $GLOBALS['cDB']->get_index(0, $startWith);
                foreach ($items as $item) {
                    echo $space.'<bookmark href="'.htmlspecialchars($item['url']).'" id="b'.$item['id'].'">'.LF #FIXME Support:  added="2007-11-11" modified="2007-11-14" visited="2007-11-14">
                            .$space.'    <title><![CDATA['.$item['name'].']]></title>'.LF
                            .$space.'</bookmark>'.LF;
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
            echo '    </folder>'.LF.'</xbel>'.LF;
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
    if (!$myPrivs['all'] && !$myPrivs['bookmarks_import_bookmarks']) {
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
        case 'HTMLmoz':
        case 'HTMLopera':
        case 'HTMLmsie':
            if ($_REQUEST['imform'] == 'HTMLmoz') {
                $format = 'moz';
            } elseif ($_REQUEST['imform'] == 'HTMLopera') {
                $format = 'op';
            } elseif ($_REQUEST['imform'] == 'HTMLmsie') {
                $format = 'moz';
            }
            $bmp = new Format_Parse_Bookmarks();
            $structure = $bmp->parse($temp_name, $format);
            $folderMap = array();
            foreach ($structure['folders'] as $k => $v) {
                $v['parent'] = ($v['parent'] != 0) ? $folderMap[$v['parent']] : $imgroup;
                $fid = $cDB->add_folder($v['name'], $v['descr'], $v['parent']);
                $folderMap[$k] = $fid;
                ++$imported;
            }
            foreach ($structure['items'] as $k => $v) {
                $v['parent'] = ($v['parent'] != 0) ? $folderMap[$v['parent']] : $imgroup;
                $payload = array();
                foreach (array('name' => 'name', 'url' => 'url', 'descr' => 'description', 'parent' => 'fid'
                        ,'added' => 'added', 'modified' => 'modified', 'visited' => 'visited') as $k2 => $v2) {
                    if (isset($v[$k2]) && !is_null($v[$k2])) {
                        $payload[$v2] = $v[$k2];
                    }
                }
                $cDB->add_item($payload);
                ++$imported;
            }
            @unlink($temp_name);
            break;
        case 'XBEL':

            break;
        default:
            $return .= $WP_msg['unkImpFrmt'].'<br />'.LF;
            break;
        }
    }
    $do = false;
}
if (!$do) {
    if (isset($imported) && $imported) $return .= str_replace('$1', $imported, $WP_msg['ImpNum']).'<br />'.LF;
    $tpl = new phlyTemplate($_PM_['path']['templates'].'bookmarks.exchmenu.tpl');
    $passthrough2 = give_passthrough(2);
    if ($return) $tpl->fill_block('return', 'return', $return);
    if ($myPrivs['all'] || $myPrivs['bookmarks_import_bookmarks']) {
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
        foreach (array('HTMLmoz' => 'HTML (Mozilla / Netscape)', 'HTMLopera' => 'HTML (Opera)', 'HTMLmsie' => 'HTML (InternetExplorer)', 'XBEL' => 'XBEL') as $val => $name) {
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
    if ($cDB->quota_bookmarksnum(false) && ($myPrivs['all'] || $myPrivs['bookmarks_export_bookmarks'])) {
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
        foreach (array(/*'HTML' => 'bookmarks.html', */'XBEL' => 'XBEL') as $val => $name) {
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