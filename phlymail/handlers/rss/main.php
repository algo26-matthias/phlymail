<?php
/**
 * Main display of a folder's content
 * @package phlyMail Yokohama 4.x Default Branch
 * @subpackage  Handler RSS
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.3 2013-11-01 $Id: main.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['rss_see_feeds']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

$tpl = new phlyTemplate($_PM_['path']['templates'].'rss.general.tpl');

$use_preview = (isset($_PM_['core']['folders_usepreview']) && $_PM_['core']['folders_usepreview']) ? true : false;
$use_preview = (isset($_PM_['rss']['use_preview']) && $_PM_['rss']['use_preview']) ? true : $use_preview;

$workfolder = !empty($_REQUEST['workfolder'])
        ? (in_array($_REQUEST['workfolder'], array('favourites', 'root', 'shareroot'))
                ? $_REQUEST['workfolder']
                : intval(preg_replace('![^0-9]!', '', $_REQUEST['workfolder']))
        )
        : 0;
$_PM_['core']['pass_through'][] = 'workfolder';
$passthrough = give_passthrough(1);
$base_link = PHP_SELF.'?h=rss&l=ilist&'.$passthrough;

if (isset($_REQUEST['pagenum'])) $_SESSION['rss_pagenum'] = intval($_REQUEST['pagenum']);
if (isset($_REQUEST['jumppage'])) $_SESSION['rss_pagenum'] = intval($_REQUEST['jumppage']) - 1;
if (!isset($_SESSION['rss_pagenum'])) $_SESSION['rss_pagenum'] = 0;

// Try to find a field to order the whole list by
$orderby  = 'published';
$orderdir = 'DESC';
$ordlink = ''; // Leer zurzeit, Hanlding mangels Kopfzeile im UI unklar

$criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
$pattern = isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : null;
if ($pattern && $criteria) {
    $ordlink .= '&criteria='.$criteria.'&pattern='.$pattern;
}

$cDB = new handler_rss_driver($_SESSION['phM_uid']);
$folderInfo = $cDB->get_feed($workfolder);

$withPublic = 1;
if ($workfolder == 'shareoot') {
    $withPublic = 2;
} elseif ($workfolder == 'root') {
    $withPublic = 0;
}

$eingang = $cDB->get_itemcount($withPublic, $workfolder, $pattern, $criteria);

if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize']) {
    $displayend = $i = $eingang;
    $displaystart = 1;
    $i2 = 0;
} else {
    if ($_SESSION['rss_pagenum'] < 0) $_SESSION['rss_pagenum'] = 0;
    if ($_PM_['core']['pagesize'] * $_SESSION['rss_pagenum'] > $eingang) {
        $_SESSION['rss_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $i = $eingang - ($_PM_['core']['pagesize'] * $_SESSION['rss_pagenum']);
    $i2 = $i - $_PM_['core']['pagesize'];
    if ($i2 < 0) $i2 = 0;
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['rss_pagenum'] +1;
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['rss_pagenum'] + 1);
    if ($displayend > $eingang) $displayend = $eingang;
}
$myPageNum = $_SESSION['rss_pagenum'];
// That's it with the session
session_write_close();

$plural = ($eingang == 1) ? $WP_msg['entry'] : $WP_msg['entries'];
// Handle Jump to Page Form
if ($_PM_['core']['pagesize']) {
    $max_page = ceil($eingang / $_PM_['core']['pagesize']);
} else {
    $max_page = 0;
}
$jumpsize = strlen($max_page);

$tpl_lines = $tpl->get_block('item');
$i = $displaystart;
$markseen = array();
foreach ($cDB->get_index($withPublic, $workfolder, $pattern, $criteria, ($displayend-$displaystart+1), $displaystart-1, $orderby, $orderdir) as $line) {
    $item_classes = array();
    $line['published'] = strtotime($line['published']);
    if (-1 == $line['published']) {
        $short_datum = $datum = '---';
    } else {
        $datum = date($WP_msg['dateformat'], $line['published']);
        if (date('Y', $line['published']) == date('Y')) {
            $short_datum = date($WP_msg['dateformat_new'], $line['published']);
        } else {
            $short_datum = date($WP_msg['dateformat_old'], $line['published']);
        }
    }
    if ($line['seen'] != 1) {
        $item_classes[] = 'unseen';
        $markseen[] = $line['id'];
    }
    if ($line['read'] != 1) {
        $item_classes[] = 'unread';
    }
    $tpl_lines->assign(array(
            'num' => $i,
            'id' => $line['id'],
            'item_title' => phm_entities(html_entity_decode($line['title'], null, 'utf-8')),
            'item_url' => phm_entities($line['url']),
            'item_date' => phm_entities($datum),
            'item_author' => phm_entities($line['author']),
            'item_author_title' => phm_entities($line['author']),
            'item_classes' => implode(' ', $item_classes)
            ));
    $tpl->assign('item', $tpl_lines);
    $tpl_lines->clear();
    $i++;
}
if (!empty($markseen)) {
    // Alle ausgelieferten IDs gelten als gesehen ...
    $cDB->update_item(array('id' => $markseen, 'seen' => '1'));
}

// Handle Jump to Page Form
if (isset($_PM_['core']['pagesize']) && $_PM_['core']['pagesize']) {
    $max_page = ceil($eingang / $_PM_['core']['pagesize']);
} else {
    $max_page = 0;
}
$jumpsize = strlen($max_page);

// Allow to disable public feeds
if (!RSS_PUBLIC_FEEDS) {
    $tpl->assign_block('nopublicfeeds');
}

if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['rss_delete_feed']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('handler' => 'rss'
        ,'msg_page' => $WP_msg['page']
        ,'passthrough' => $passthrough
        ,'go' => $WP_msg['goto']
        ,'but_search' => $WP_msg['ButSearch']
        ,'selection' => $WP_msg['selection']
        ,'allpage' => $WP_msg['allpage']
        ,'msg_group' => $WP_msg['group']
        ,'msg_name' => $WP_msg['name']
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_dele' => $WP_msg['del']
        ,'but_dele' => $WP_msg['del']
        ,'msg_killconfirm' => $WP_msg['killJSconfirm']
        ,'msg_open_preivew' => $WP_msg['OpenInPreview']
        ,'msg_open_newwin' => $WP_msg['OpenInNewWindow']
        ,'msg_markreadset' => $WP_msg['markread_set']
        ,'msg_markreadunset' => $WP_msg['markread_unset']
        ,'search' => $WP_msg['ButSearch']
        ,'PHP_SELF' => PHP_SELF
        ,'jump_url' => $base_link.$ordlink
        ,'search_url' => $base_link.$ordlink
        ,'preview_url' => PHP_SELF.'?l=preview&h=rss&'.$passthrough.'&id='
        ,'size' => $jumpsize
        ,'maxlen' => $jumpsize
        ,'page' => $myPageNum + ($eingang == 0 ? 0 : 1)
        ,'pages' => $max_page
        ,'plural' => $plural
        ,'rss' => $WP_msg['entries']
        ,'neueingang' => number_format($eingang, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'displaystart' => ($eingang == 0) ? 0 : $displaystart
        ,'displayend' => $displayend
        ,'pagenum' => $myPageNum
        ,'pagesize' => $_PM_['core']['pagesize']
        ,'feedops_url' => PHP_SELF.'?h=rss&l=worker&'.$passthrough.'&what=item_'
        ,'jsrequrl' => $base_link.$ordlink.'&jsreq=1'
        ,'folder_writable' => (int) ($folderInfo['owner'] == $_SESSION['phM_uid'])
        ,'use_preview' => !empty($use_preview) ? 1 : 0
        ,'allow_resize' => (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) ? 1 : 0
        ,'customheight' => !empty($_PM_['customsize']['rss_previewheight']) ? $_PM_['customsize']['rss_previewheight'] : 0
        ));
