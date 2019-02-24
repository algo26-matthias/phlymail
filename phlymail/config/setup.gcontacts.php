<?php
/**
 * Edit global contacts (main listing)
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @subpackage Global Contacts
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.5 2012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['gcontacts_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}

if (file_exists($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php')) {
    require_once($_PM_['path']['handler'].'/contacts/lang.'.$WP_conf['language'].'.php');
} else {
    require_once($_PM_['path']['handler'].'/contacts/lang.de.php');
}

$cDB = new handler_contacts_driver(0);
if (isset($_REQUEST['dele']) && $_REQUEST['dele']) {
    foreach ($_REQUEST['id'] as $id) $cDB->delete_contact($id);
}
$tpl = new phlyTemplate(CONFIGPATH.'/templates/gcontacts.tpl');

$ordfields = array('displayname', 'lastname', 'firstname', 'displaymail', 'displayphone');
$passthrough = give_passthrough(1);
$base_link = PHP_SELF.'?action=gcontacts&'.$passthrough;
$edit_link = PHP_SELF.'?action=gconedit&'.$passthrough;
$dele_link = PHP_SELF.'?action=gcontacts&dele=1&'.$passthrough;

if (isset($_REQUEST['pagenum'])) $_SESSION['contacts_pagenum'] = $_REQUEST['pagenum'];
if (isset($_REQUEST['jumppage'])) $_SESSION['contacts_pagenum'] = $_REQUEST['jumppage'] - 1;
if (!isset($_SESSION['contacts_pagenum'])) $_SESSION['contacts_pagenum'] = 0;

if (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $ordfields)) {
    $orderby = $_REQUEST['orderby'];
    $orderdir = (isset($_REQUEST['orderdir']) &&
            ('ASC' == $_REQUEST['orderdir'] || 'DESC' == $_REQUEST['orderdir'])) ? $_REQUEST['orderdir'] : 'ASC';
} else {
    $orderby  = 'displayname';
    $orderdir = 'ASC';
}
$ordlink = '&orderby='.$orderby.'&orderdir='.$orderdir;
$eingang = $cDB->get_adrcount(0, 0);

if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize']) {
    $displayend = $i = $eingang;
    $displaystart = 0;
    $i2 = 0;
} else {
    if ($_SESSION['contacts_pagenum'] < 0) $_SESSION['contacts_pagenum'] = 0;
    if ($_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum'] > $eingang) {
        $_SESSION['contacts_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $i = $eingang - ($_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum']);
    $i2 = $i - $_PM_['core']['pagesize'];
    if ($i2 < 0) $i2 = 0;
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['contacts_pagenum'];
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['contacts_pagenum'] + 1);
    if ($displayend > $eingang) $displayend = $eingang;
}

if ($eingang == 0) {
    $tpl->assign_block('nocontactsblock');
    $tpl->assign('nonewmail', $WP_msg['APInone']);
} else {
    $tc = $tpl->get_block('contactblock');
    if ($eingang == 1) $plural = $WP_msg['entry']; else $plural = $WP_msg['entries'];
    // Handle Jump to Page Form
    if ($_PM_['core']['pagesize']) {
        $max_page = ceil($eingang / $_PM_['core']['pagesize']);
    } else {
        $max_page = 0;
    }
    $jumpsize = strlen($max_page);
    $nickordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum'].'&orderby=displayname&orderdir=ASC';
    $fnamordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum'].'&orderby=firstname&orderdir=ASC';
    $lnamordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum'].'&orderby=lastname&orderdir=ASC';
    $mailordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum'].'&orderby=displaymail&orderdir=ASC';
    $block = ('DESC' == $orderdir) ? 'ordupico' : 'orddownico';
    switch ($orderby) {
    case 'displayname':
        $tc->assign_block('nick'.$block);
        if ('ASC' == $orderdir) $nickordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum']
                .'&orderby=displayname&orderdir=DESC';
        break;
    case 'firstname':
        $tc->assign_block('fnam'.$block);
        if ('ASC' == $orderdir) $fnamordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum']
                .'&orderby=firstname&orderdir=DESC';
        break;
    case 'lastname':
        $tc->assign_block('lnam'.$block);
        if ('ASC' == $orderdir) $lnamordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum']
                .'&orderby=lastname&orderdir=DESC';
        break;
    case 'displaymail':
        $tc->assign_block('mail'.$block);
        if ('ASC' == $orderdir) $mailordlink = $base_link.'&pagenum='.$_SESSION['contacts_pagenum']
                .'&orderby=displaymail&orderdir=DESC';
        break;
    }
    $tc->assign(array
            ('nickordurl' => htmlspecialchars($nickordlink)
            ,'fnamordurl' => htmlspecialchars($fnamordlink)
            ,'lnamordurl' => htmlspecialchars($lnamordlink)
            ,'mailordurl' => htmlspecialchars($mailordlink)
            ,'neueingang' => $eingang
            ,'plural' => $plural
            ,'contacts' => $WP_msg['entries']
            ,'PHP_SELF' => PHP_SELF
            ,'displaystart' => $displaystart
            ,'displayend' => $displayend
            ,'page' => $_SESSION['contacts_pagenum'] + 1
            ,'msg_page' => $WP_msg['page']
            ,'boxsize' => $max_page
            ,'passthrough_2' => give_passthrough(2)
            ,'go' => $WP_msg['goto']
            ,'size' => $jumpsize
            ,'maxlen' => $jumpsize
            ,'selection' => $WP_msg['selection']
            ,'allpage' => $WP_msg['allpage']
            ,'msg_nick' => $WP_msg['nick']
            ,'msg_fname' => $WP_msg['fnam']
            ,'msg_lname' => $WP_msg['snam']
            ,'msg_email' => $WP_msg['emai1']
            ,'msg_phone' => $WP_msg['Phone']
            ));

    if ($_SESSION['contacts_pagenum'] > 0) {
        $tc->fill_block('blstblk', array
                ('link_last' => htmlspecialchars($base_link.'&pagenum='.($_SESSION['contacts_pagenum']-1).$ordlink)
                ,'but_last' => '&lt;&lt;'
                ));
    }
    if ($displayend < $eingang) {
        $tc->fill_block('bnxtblk', array
                ('link_next' => htmlspecialchars($base_link.'&pagenum='.($_SESSION['contacts_pagenum']+1).$ordlink)
                ,'but_next' => '&gt;&gt;'
                ));
    }

    $t_l = $tc->get_block('contactlines');
    foreach ($cDB->get_adridx(0, 0, null, null, ($displayend-$displaystart), $displaystart, $orderby, $orderdir) as $line) {
        $nick_title = $nick = $line['nick'];
        if (strlen($nick) > 30) $nick = substr($nick, 0, 27).'...';
        $fname_title = $fname = $line['firstname'];
        if (strlen($fname) > 30) $fname = substr($fname, 0, 27).'...';
        $lname_title = $lname = $line['lastname'];
        if (strlen($lname) > 30) $lname = substr($lname, 0, 27).'...';
        $email_title = $email = $line['displaymail'];
        if (strlen($email) > 30) $email = substr($email, 0, 27).'...';
        $t_l->assign(array
                ('nick' => htmlspecialchars($nick)
                ,'nick_title' => htmlspecialchars($nick_title)
                ,'fname' => htmlspecialchars($fname)
                ,'fname_title' => htmlspecialchars($fname_title)
                ,'lname' => htmlspecialchars($lname)
                ,'lname_title' => htmlspecialchars($lname_title)
                ,'email' => htmlspecialchars($email)
                ,'email_title' => htmlspecialchars($email_title)
                ,'editlink' => htmlspecialchars($edit_link.'&id='.$line['aid'])
                ,'id' => $line['aid']
                ));
        $tc->assign('contactlines', $t_l);
        $t_l->clear();
    }
    $tpl->assign('contactblock', $tc);
}
$tpl->assign(array
        ('passthrough_2' => give_passthrough(2)
        ,'neueingang' => $eingang
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'PHP_SELF' => PHP_SELF
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_dele' => $WP_msg['del']
        ,'msg_really_del' => $WP_msg['AskDelAdrs']
        ,'delelink' => $dele_link
        ,'msg_add' => $WP_msg['NewContact']
        ,'newlink' => htmlspecialchars($edit_link)
        ));
