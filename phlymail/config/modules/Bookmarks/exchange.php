<?php
/**
 * @package phlyMail Nahariya 4.0+
 * @subpackage Handler Bookmarks
 * @subpackage Import / Export
 * @copyright 2002-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.5 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (file_exists($_PM_['path']['handler'].'/bookmarks/lang.'.$WP_conf['language'].'.php')) {
    require($_PM_['path']['handler'].'/bookmarks/lang.'.$WP_conf['language'].'.php');
} else {
    require($_PM_['path']['handler'].'/bookmarks/lang.de.php');
}
$myurl = PHP_SELF.'?action=view&screen=exchange&module=Bookmarks&'.give_passthrough(1);
$do = (isset($_REQUEST['do']) && $_REQUEST['do']) ? $_REQUEST['do'] : false;
$return = false;

if ('export' == $do) {
    $exgroup = 0;
    if (isset($_REQUEST['exgroup'])) {
        $exgroup = intval($_REQUEST['exgroup']);
    }
    $export_format = false;
    if (isset($_REQUEST['exform'])) {
        $export_format = $_REQUEST['exform'];
    }

    switch ($export_format) {
    case 'HTML':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=bookmarks.html');

        break;
    case 'XBEL':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=bookmarks.xbel');

        if ($exgroup) {
            $root = $bDB->get_folder($exgroup);
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

            $folders = $bDB->get_folderlist(1, true);
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
                if (!isset($folders[$startWith])) {
                    return;
                }

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
    if (!$return) {
        exit;
    }
}
if ('import' == $do) {
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
                $fid = $bDB->add_folder($v['name'], $v['descr'], $v['parent']);
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
                $bDB->add_item($payload);
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
    if (!empty($imported)) {
        $return .= str_replace('$1', $imported, $WP_msg['ImpNum']).'<br />'.LF;
    }
    $tpl = new phlyTemplate(__DIR__.DIRECTORY_SEPARATOR.'bookmarks.exchange.tpl');
    $passthrough2 = give_passthrough(2);
    if ($return) {
        $tpl->fill_block('return', 'return', $return);
    }

    $tpl->assign(array
            ('target' => $myurl.'&amp;do=import'
            ,'msg_select' => $WP_msg['plsSel']
            ,'passthrough' => $passthrough2
            ,'about_import' => $WP_msg['AboutImport']
            ,'leg_import' => $WP_msg['Import']
            ,'msg_file' => $WP_msg['filename']
            ,'msg_format' => $WP_msg['format']
            ,'msg_group' => $WP_msg['group']
            ));
    $imop = $tpl->get_block('imoption');
    foreach (array('HTMLmoz' => 'HTML (Mozilla / Netscape)', 'HTMLopera' => 'HTML (Opera)', 'HTMLmsie' => 'HTML (InternetExplorer)', 'XBEL' => 'XBEL') as $val => $name) {
        $imop->assign(array('value' => $val, 'name' => $name));
        $tpl->assign('imoption', $imop);
        $imop->clear();
    }
    $imgr = $tpl->get_block('imgroup');
    foreach ($bDB->get_folderlist(0) as $k => $v) {
        $imgr->assign(array('id' => $k, 'name' => $v['name']));
        $tpl->assign('imgroup', $imgr);
        $imgr->clear();
    }

    if ($bDB->quota_bookmarksnum(false)) {
        $tpl_exp = $tpl->get_block('export');
        $tpl_exp->assign(array
                ('target' => $myurl.'&amp;do=export'
                ,'msg_select' => $WP_msg['plsSel']
                ,'passthrough' => $passthrough2
                ,'about_export' => $WP_msg['AboutExport']
                ,'leg_export' => $WP_msg['Export']
                ,'msg_format' => $WP_msg['format']
                ,'msg_group' => $WP_msg['group']
                ));
        $exop = $tpl_exp->get_block('exoption');
        foreach (array('HTML' => 'bookmakrs.html', 'XBEL' => 'XBEL') as $val => $name) {
            $exop->assign(array('value' => $val, 'name' => $name));
            $tpl_exp->assign('exoption', $exop);
            $exop->clear();
        }
        $exgr = $tpl_exp->get_block('exgroup');
        foreach ($bDB->get_folderlist(0) as $k => $v) {
            $imgr->assign(array('id' => $k, 'name' => $v['name']));
            $tpl_exp->assign('exgroup', $exgr);
            $exgr->clear();
        }
        $tpl->assign('export', $tpl_exp);
    }
}