<?php
/**
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Contacts
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.3 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_see_contacts']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}

if (!isset($_REQUEST['jsreq'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.general.tpl');
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'contacts.json.tpl');
}

$use_preview = (isset($_PM_['core']['folders_usepreview']) && $_PM_['core']['folders_usepreview']) ? true : false;
$use_preview = (isset($_PM_['contacts']['use_preview']) && $_PM_['contacts']['use_preview']) ? true : $use_preview;
$fieldnames = array
        ('displayname' => array('n' => $WP_msg['nick'], 't' => '', 'i' => '', 'db' => 'nick')
        ,'nick' => array('n' => $WP_msg['nick'], 't' => '', 'i' => '', 'db' => 'nick')
        ,'lastname' => array('n' => $WP_msg['snam'], 't' => '', 'i' => '', 'db' => 'lastname')
        ,'firstname' => array('n' => $WP_msg['fnam'], 't' => '', 'i' => '', 'db' => 'firstname')
        ,'company' => array('n' => $WP_msg['company'], 't' => '', 'i' => '', 'db' => 'company')
        ,'displaymail' => array('n' => $WP_msg['emai1'], 't' => '', 'i' => '', 'db' => 'displaymail')
        ,'email1' => array('n' => $WP_msg['emai1'], 't' => '', 'i' => '', 'db' => 'email1')
        ,'email2' => array('n' => $WP_msg['emai2'], 't' => '', 'i' => '', 'db' => 'email2')
        ,'displayphone' => array('n' => $WP_msg['Phone'], 't' => '', 'i' => '', 'db' => 'displayphone')
        ,'tel_private' => array('n' => $WP_msg['Phone'], 't' => '', 'i' => '', 'db' => 'tel_private')
        ,'tel_business' => array('n' => $WP_msg['fon2'], 't' => '', 'i' => '', 'db' => 'tel_business')
        ,'cellular' => array('n' => $WP_msg['cell'], 't' => '', 'i' => '', 'db' => 'cellular')
        ,'fax' => array('n' => $WP_msg['fax'], 't' => '', 'i' => '', 'db' => 'fax')
        );
$showfields  = (isset($_PM_['contacts']['show_fields']) && !empty($_PM_['contacts']['show_fields']) && !$_PM_['contacts']['use_default_fields'])
        ? $_PM_['contacts']['show_fields']
        : array('lastname' => 1, 'firstname' => 1, 'displaymail' => 1, 'displayphone' => 1);
$workfolder = (isset($_REQUEST['workfolder']) && $_REQUEST['workfolder']) ? intval($_REQUEST['workfolder']) : 0;

$_PM_['core']['pass_through'][] = 'workfolder';
$passthrough = give_passthrough(1);
$base_link = PHP_SELF.'?h=contacts&l=ilist&'.$passthrough;
$edit_link = PHP_SELF.'?h=contacts&l=edit_contact&'.$passthrough;

if (isset($_REQUEST['pagenum'])) $_SESSION['contacts_pagenum'] = intval($_REQUEST['pagenum']);
if (isset($_REQUEST['jumppage'])) $_SESSION['contacts_pagenum'] = intval($_REQUEST['jumppage']) - 1;
if (!isset($_SESSION['contacts_pagenum'])) $_SESSION['contacts_pagenum'] = 0;

if (isset($_REQUEST['orderby']) && isset($fieldnames[$_REQUEST['orderby']])) {
    $orderby = $_REQUEST['orderby'];
    $orderdir = (isset($_REQUEST['orderdir']) && ('ASC' == $_REQUEST['orderdir'] || 'DESC' == $_REQUEST['orderdir'])) ? $_REQUEST['orderdir'] : 'ASC';
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    $GlChFile['contacts']['orderby'] = $orderby;
    $GlChFile['contacts']['orderdir'] = $orderdir;
    $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile);
} else {
    // Try to find a field to order the whole list by
    $orderby  = 'displayname';
    foreach (array('lastname', 'firstname', 'nick', 'company') as $field) {
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

$cDB = new handler_contacts_driver($_SESSION['phM_uid']);
if ($workfolder == 0) {
    $cDB->setQueryType('root');
}
$eingang = $cDB->get_adrcount(CONTACTS_VISIBILITY_MODE, $workfolder, $pattern, $criteria);
$folder = $cDB->get_group($workfolder, false);

if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize']) {
    $displayend = $i = $eingang;
    $displaystart = 1;
    $i2 = 0;
} else {
    if ($_SESSION['contacts_pagenum'] < 0) $_SESSION['contacts_pagenum'] = 0;
    if ($_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum'] > $eingang) {
        $_SESSION['contacts_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $i = $eingang - ($_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum']);
    $i2 = $i - $_PM_['core']['pagesize'];
    if ($i2 < 0) $i2 = 0;
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum'] +1;
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['contacts_pagenum'] + 1);
    if ($displayend > $eingang) $displayend = $eingang;
}
$myPageNum = $_SESSION['contacts_pagenum'];
// That's it with the session
session_write_close();

// Initialise the ShowFields array passed to JavaScript with the icon field always displayed in front
$sf_js = array('"type":{"n":"","i":"","t":"'.$WP_msg['VisibilityTag'].'"}');
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

$tpl_lines = $tpl->get_block('contactlines');
$i = $displaystart;
foreach ($cDB->get_adridx(CONTACTS_VISIBILITY_MODE, $workfolder, $pattern, $criteria, ($displayend-$displaystart+1), $displaystart-1, $orderby, $orderdir) as $line) {
    if (isset($line['global']) && $line['global']) {
        $typetext = $WP_msg['GlobalContact'];
        $typeicon = 'global';
    } elseif (isset($line['visibility']) && $line['visibility'] == 'public') {
        $typetext = $WP_msg['PublicContact'];
        $typeicon = 'public';
    } else {
        $typetext = $WP_msg['PersonalContact'];
        $typeicon = 'personal';
    }
    $tpl_lines->assign(array
            ('num' => $i
            ,'data' => '{"nick": "'.phm_addcslashes($line['nick']).'"'
                    .',"displayname": "'.(isset($showfields['displayname']) && $showfields['displayname'] ? phm_addcslashes($line['displayname']) : '').'"'
                    .',"firstname": "'.phm_addcslashes($line['firstname']).'"'
                    .',"lastname": "'.phm_addcslashes($line['lastname']).'"'
                    .',"company": "'.phm_addcslashes($line['company']).'"'
                    .',"displaymail": "'.(isset($showfields['displaymail']) && $showfields['displaymail'] ? phm_addcslashes($line['displaymail']) : '').'"'
                    .',"email1": "'.phm_addcslashes($line['email1']).'"'
                    .',"email2": "'.phm_addcslashes($line['email2']).'"'
                    .',"displayphone": "'.(isset($showfields['displayphone']) && $showfields['displayphone'] ? phm_addcslashes($line['displayphone']) : '').'"'
                    .',"tel_private": "'.phm_addcslashes($line['tel_private']).'"'
                    .',"tel_business": "'.phm_addcslashes($line['tel_business']).'"'
                    .',"cellular": "'.phm_addcslashes($line['cellular']).'"'
                    .',"fax": "'.phm_addcslashes($line['fax']).'"'
                    .',"typetext": "'.phm_addcslashes($typetext).'"'
                    .',"typeicon": "'.$typeicon.'"'
                    .',"uidl": "'.$line['aid'].'"'
                    .'}'
            ,'notfirst' => $i == $displaystart ? '' : ','
            ));
    $tpl->assign('contactlines', $tpl_lines);
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
        ,'contacts' => $WP_msg['entries']
        ,'neueingang' => number_format($eingang, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'displaystart' => ($eingang == 0) ? 0 : $displaystart
        ,'displayend' => $displayend
        ,'showfields' => '{'.implode(', ', $sf_js).'}'
        ,'orderby' => $orderby
        ,'orderdir' => $orderdir
        ,'pagenum' => $myPageNum
        ,'pagesize' => $_PM_['core']['pagesize']
        ,'jsrequrl' => $base_link.$ordlink.'&jsreq=1'
        ));
// This is a JSON request, which just needs the maillist and a few info bits 'bout that folder
if (isset($_REQUEST['jsreq'])) {
    header('Content-Type: application/json; charset=UTF-8');
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
// Allow to disable public contacts
if (!CONTACTS_PUBLIC_CONTACTS) $tpl->assign_block('nopubliccontacts');

// Permissions reflected in context menu items
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_make_contact_global']) {
    $tpl->assign_block('ctx_global');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['contacts_delete_contact']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('contacts' => $WP_msg['entries']
        ,'PHP_SELF' => PHP_SELF
        ,'msg_page' => $WP_msg['page']
        ,'passthrough_2' => give_passthrough(2)
        ,'go' => $WP_msg['goto']
        ,'selection' => $WP_msg['selection']
        ,'allpage' => $WP_msg['allpage']
        ,'msg_nick' => $WP_msg['nick']
        ,'msg_fname' => $WP_msg['fnam']
        ,'msg_lname' => $WP_msg['snam']
        ,'msg_email' => $WP_msg['emai1']
        ,'msg_phone' => $WP_msg['Phone']
        ,'msg_phon2' => $WP_msg['fon2']
        ,'msg_company' => $WP_msg['company']
        ,'msg_email2' => $WP_msg['emai2']
        ,'msg_cell' => $WP_msg['cell']
        ,'msg_fax' => $WP_msg['fax']
        ,'msg_group' => $WP_msg['group']
        ,'msg_name' => $WP_msg['name']
        ,'msg_address' => $WP_msg['address']
        ,'msg_company' => $WP_msg['company']
        ,'passthrough_2' => give_passthrough(2)
        //,'neueingang' => $eingang
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_dele' => $WP_msg['del']
        ,'msg_makepublic' => $WP_msg['VisibilityMakePublic']
        ,'msg_makeprivate' => $WP_msg['VisibilityMakePrivate']
        ,'but_dele' => $WP_msg['del']
        ,'but_print' => $WP_msg['prnt']
        ,'msg_killconfirm' => $WP_msg['killJSconfirm']
        ,'but_search' => $WP_msg['ButSearch']
        ,'handler' => 'contacts'
        ,'PHP_SELF' => PHP_SELF
        ,'passthrough' => $passthrough
        ,'jump_url' => $base_link.$ordlink
        ,'search_url' => $base_link.$ordlink
        ,'edit_link' => $edit_link
        ,'preview_url' => PHP_SELF.'?l=preview&h=contacts&'.$passthrough.'&id='
        ,'folder_writable' => (int) ($folder['owner'] == $_SESSION['phM_uid'])
        ,'use_preview' => (isset($use_preview) && $use_preview) ? 1 : 0
        ,'allow_resize' => (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) ? 1 : 0
        ,'customheight' => (isset($_PM_['customsize']['contacts_previewheight']) && $_PM_['customsize']['contacts_previewheight'])
                ? $_PM_['customsize']['contacts_previewheight']
                : 0
        ));
