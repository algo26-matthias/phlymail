<?php
/**
 * Main display of a folder's content
 * @package phlyMail Yokohama 4.x Default Branch
 * @subpackage  Handler Bookmarks
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['bookmarks_see_bookmarks']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

if (!isset($_REQUEST['jsreq'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'bookmarks.general.tpl');
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'bookmarks.json.tpl');
}
$use_preview = (isset($_PM_['core']['folders_usepreview']) && $_PM_['core']['folders_usepreview']) ? true : false;
$use_preview = (isset($_PM_['bookmarks']['use_preview']) && $_PM_['bookmarks']['use_preview']) ? true : $use_preview;
$fieldnames = array
        ('name' => array('n' => $WP_msg['BMName'], 't' => '', 'i' => '', 'db' => 'name')
        ,'url' => array('n' => $WP_msg['BMURL'], 't' => '', 'i' => '', 'db' => 'url')
        );
$showfields  = /*(isset($_PM_['bookmarks']['show_fields']) && !empty($_PM_['bookmarks']['show_fields']) && !$_PM_['bookmarks']['use_default_fields'])
        ? $_PM_['bookmarks']['show_fields']
        :*/ array('name' => 1, 'url' => 1);
$workfolder = (isset($_REQUEST['workfolder']) && $_REQUEST['workfolder'])
        ? (in_array($_REQUEST['workfolder'], array('favourites', 'root', 'shareroot')) ? $_REQUEST['workfolder'] : intval($_REQUEST['workfolder']))
        : 0;
$_PM_['core']['pass_through'][] = 'workfolder';
$passthrough = give_passthrough(1);
$base_link = PHP_SELF.'?h=bookmarks&l=ilist&'.$passthrough;
$edit_link = PHP_SELF.'?h=bookmarks&l=edit_bookmark&'.$passthrough;

if (isset($_REQUEST['pagenum'])) $_SESSION['bookmarks_pagenum'] = intval($_REQUEST['pagenum']);
if (isset($_REQUEST['jumppage'])) $_SESSION['bookmarks_pagenum'] = intval($_REQUEST['jumppage']) - 1;
if (!isset($_SESSION['bookmarks_pagenum'])) $_SESSION['bookmarks_pagenum'] = 0;

if (isset($_REQUEST['orderby']) && isset($fieldnames[$_REQUEST['orderby']])) {
    $orderby = $_REQUEST['orderby'];
    $orderdir = (isset($_REQUEST['orderdir']) && ('ASC' == $_REQUEST['orderdir'] || 'DESC' == $_REQUEST['orderdir'])) ? $_REQUEST['orderdir'] : 'ASC';
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    $GlChFile['bookmarks']['orderby'] = $orderby;
    $GlChFile['bookmarks']['orderdir'] = $orderdir;
    $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
} else {
    // Try to find a field to order the whole list by
    $orderby  = 'name';
    foreach (array('name', 'url') as $field) {
        if (isset($showfields[$field]) && $showfields[$field]) {
            $orderby = $field;
            break;
        }
    }
    $orderdir = 'ASC';
}
$ordlink = '&orderby='.$orderby.'&orderdir='.$orderdir;

$criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
$pattern = isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : null;
if ($pattern && $criteria) {
    $ordlink .= '&criteria='.$criteria.'&pattern='.$pattern;
}

$cDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
$folderInfo = $cDB->get_folder($workfolder);
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
    if ($_SESSION['bookmarks_pagenum'] < 0) $_SESSION['bookmarks_pagenum'] = 0;
    if ($_PM_['core']['pagesize'] * $_SESSION['bookmarks_pagenum'] > $eingang) {
        $_SESSION['bookmarks_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $i = $eingang - ($_PM_['core']['pagesize'] * $_SESSION['bookmarks_pagenum']);
    $i2 = $i - $_PM_['core']['pagesize'];
    if ($i2 < 0) $i2 = 0;
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['bookmarks_pagenum'] +1;
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['bookmarks_pagenum'] + 1);
    if ($displayend > $eingang) $displayend = $eingang;
}
$myPageNum = $_SESSION['bookmarks_pagenum'];
// That's it with the session
session_write_close();

// Initialise the ShowFields array passed to JavaScript with the icons field always displayed in front
$sf_js = array('"type":{"n":"","i":"","t":"'.$WP_msg['VisibilityTag'].'"}', '"nwin":{"n":"","i":"","t":"'.$WP_msg['OpenInNewWindow'].'"}');
foreach ($showfields as $f => $a) {
    if (!$a) continue;
    $sf_js[] = '"'.$f.'":{"n":"'.$fieldnames[$f]['n'].'","i":"'.$fieldnames[$f]['i'].'","t":"'.$fieldnames[$f]['t'].'"}';
}

$plural = ($eingang == 1) ? $WP_msg['entry'] : $WP_msg['entries'];
// Handle Jump to Page Form
if ($_PM_['core']['pagesize']) {
    $max_page = ceil($eingang / $_PM_['core']['pagesize']);
} else {
    $max_page = 0;
}
$jumpsize = strlen($max_page);

$tpl_lines = $tpl->get_block('bookmarklines');
$i = $displaystart;
foreach ($cDB->get_index($withPublic, $workfolder, $pattern, $criteria, ($displayend-$displaystart+1), $displaystart-1, $orderby, $orderdir) as $line) {
    if (isset($line['global']) && $line['global']) {
        $typetext = $WP_msg['GlobalBookmark'];
        $typeicon = 'global';
    } elseif (isset($line['visibility']) && $line['visibility'] == 'public') {
        $typetext = $WP_msg['PublicBookmark'];
        $typeicon = 'public';
    } else {
        $typetext = $WP_msg['PersonalBookmark'];
        $typeicon = 'personal';
    }
    $tpl_lines->assign(array
            ('num' => $i
            ,'data' => '{ name : "'.phm_addcslashes(html_entity_decode($line['name'], null, 'utf-8')).'"'
                    .', url : "'.phm_addcslashes($line['url']).'"'
                    .', typetext : "'.phm_addcslashes($typetext).'"'
                    .', typeicon : "'.$typeicon.'"'
                    .', uidl :"'.$line['id'].'"'
                    .' }'
            ,'notfirst' => $i == $displaystart ? '' : ','
            ));
    $tpl->assign('bookmarklines', $tpl_lines);
    $tpl_lines->clear();
    $i++;
}
// Handle Jump to Page Form
if (isset($_PM_['core']['pagesize']) && $_PM_['core']['pagesize']) {
    $max_page = ceil($eingang / $_PM_['core']['pagesize']);
} else {
    $max_page = 0;
}
$jumpsize = strlen($max_page);
// Assign things, both template modes (HTML and JSON) will need
$tpl->assign(array
        ('size' => $jumpsize
        ,'maxlen' => $jumpsize
        ,'page' => $myPageNum + ($eingang == 0 ? 0 : 1)
        ,'boxsize' => $max_page
        ,'plural' => $plural
        ,'size' => $jumpsize
        ,'maxlen' => $jumpsize
        ,'bookmarks' => $WP_msg['entries']
        ,'neueingang' => number_format($eingang, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'displaystart' => ($eingang == 0) ? 0 : $displaystart
        ,'displayend' => $displayend
        ,'showfields' => '{'.join(', ', $sf_js).'}'
        ,'orderby' => $orderby
        ,'orderdir' => $orderdir
        ,'pagenum' => $myPageNum
        ,'pagesize' => $_PM_['core']['pagesize']
        ,'jsrequrl' => $base_link.$ordlink.'&jsreq=1'
        ,'folder_writable' => (int) ($folderInfo['owner'] == $_SESSION['phM_uid'])
        ,'use_preview' => (isset($use_preview) && $use_preview) ? 1 : 0
        ,'allow_resize' => (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) ? 1 : 0
        ,'customheight' => (isset($_PM_['customsize']['bookmarks_previewheight']) && $_PM_['customsize']['bookmarks_previewheight'])
                ? $_PM_['customsize']['bookmarks_previewheight']
                : 0
        ));
// This is a JSON request, which just needs the maillist and a few info bits 'bout that folder
if (isset($_REQUEST['jsreq'])) {
    header('Content-Type: text/json; charset=UTF-8');
    $tpl->display();
    exit;
}
if (isset($use_preview) && $use_preview) {
    $t_prev = $tpl->get_block('preview');
    // Some people have trouble with the vertical resizability of the preview window, so we got to allow switching this off
    if (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) {
        $t_prev->assign_block('allowresize');
    }
    $tpl->assign('preview', $t_prev);
}
// Allow to disable public bookmarks
if (!BOOKMARKS_PUBLIC_BOOKMARKS) $tpl->assign_block('nopublicbookmarks');
// Permissions reflected in context menu items
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_add_bookmark']) {
    $tpl->assign_block('ctx_copy');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_update_bookmark']) {
    $tpl->assign_block('ctx_move');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['bookmarks_delete_bookmark']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('bookmarks' => $WP_msg['entries']
        ,'handler' => 'bookmarks'
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
        ,'msg_copy' => $WP_msg['copytofolder']
        ,'msg_move' => $WP_msg['movetofolder']
        ,'msg_dele' => $WP_msg['del']
        ,'but_dele' => $WP_msg['del']
        ,'but_print' => $WP_msg['prnt']
        ,'msg_killconfirm' => $WP_msg['killJSconfirm']
        ,'msg_makepublic' => $WP_msg['VisibilityMakePublic']
        ,'msg_makeprivate' => $WP_msg['VisibilityMakePrivate']
        ,'msg_open_preivew' => $WP_msg['OpenInPreview']
        ,'msg_open_newwin' => $WP_msg['OpenInNewWindow']
        ,'search' => $WP_msg['ButSearch']
        ,'PHP_SELF' => PHP_SELF
        ,'jump_url' => $base_link.$ordlink
        ,'search_url' => $base_link.$ordlink
        ,'edit_link' => $edit_link
        ,'preview_url' => PHP_SELF.'?l=preview&h=bookmarks&'.$passthrough.'&id='
        ));
