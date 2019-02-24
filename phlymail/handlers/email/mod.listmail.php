<?php
/**
 * Display contents of a mail folder
 *
 * @todo Consider caching the current page number per folder so switching between folders is nicer
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Email
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.0 2015-05-19
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_see_emails']) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'all.general.tpl');
    $tpl->assign('output', $WP_msg['PrivNoAccess']);
    return;
}
$use_preview = (isset($_PM_['core']['folders_usepreview']) && $_PM_['core']['folders_usepreview']) ? true : false;
/**
 * @todo Fix the naming scheme, so the keys are shorter and consistent across JS, this file and the folder settings
 */
$fieldnames = array
        ('hfrom' => array('n' => $WP_msg['from'], 't' => '', 'i' => '', 'db' => 'from')
        ,'hto' => array('n' => $WP_msg['to'], 't' => '', 'i' => '', 'db' => 'to')
        ,'hcc' => array('n' => 'CC', 't' => '', 'i' => '', 'db' => 'cc')
        ,'hbcc' => array('n' => 'BCC', 't' => '', 'i' => '', 'db' => 'bcc')
        ,'hsubject' => array('n' => $WP_msg['subject'], 't' => '', 'i' => '', 'db' => 'subject')
        ,'hdate_sent' => array('n' => $WP_msg['date'], 't' => '', 'i' => '', 'db' => 'date_sent')
        ,'hsize' => array('n' => $WP_msg['size'], 't' => '', 'i' => '', 'db' => 'size')
        ,'hpriority' => array('n' => '', 't' => $WP_msg['prio'], 'i' => 'hprio.gif', 'db' => 'priority')
        ,'attachments' => array('n' => '', 't' => $WP_msg['attach'], 'i' => 'attach_head.gif', 'db' => 'attachments')
        ,'status' => array('n' => '', 't' => '', 'i' => '', 'db' => 'status')
        ,'type' => array('n' => '', 't' => '', 'i' => '', 'db' => 'type')
        );
// We changed the folder, got to reset the page number to 0
if (isset($_REQUEST['workfolder']) && isset($_SESSION['workfolder'])
        && $_REQUEST['workfolder'] != $_SESSION['workfolder']) {
    $_SESSION['phM_pagenum'] = 0;
}
$_SESSION['workfolder'] = (isset($_REQUEST['workfolder'])) ? intval($_REQUEST['workfolder']) : false;

if (isset($_REQUEST['WP_core_pagenum'])) {
    $_SESSION['phM_pagenum'] = intval($_REQUEST['WP_core_pagenum']);
}
if (isset($_REQUEST['WP_jumppage'])) {
    $_SESSION['phM_pagenum'] = intval($_REQUEST['WP_jumppage']) - 1;
}
if (!isset($_SESSION['phM_pagenum'])) {
    $_SESSION['phM_pagenum'] = 0;
}

$passthrough = give_passthrough(1);
$jumppath = PHP_SELF.'?h=email&l=ilist&'.$passthrough.'&workfolder='.$_SESSION['workfolder'];
$orderby  = 'hdate_sent';
$orderdir = 'DESC';
$ordlink = '';
$groupby = false;
$criteria = !empty($_REQUEST['criteria']) ? $_REQUEST['criteria'] : false;
$pattern = !empty($_REQUEST['pattern']) ? $_REQUEST['pattern'] : false;
$flags = isset($_REQUEST['searchflags']) ? $_REQUEST['searchflags'] : false;
if ($pattern !== false && $criteria !== false) {
    $search_path = '&criteria='.urlencode($criteria).'&pattern='.urlencode($pattern);
    $ordlink .= $search_path;
} else {
    $search_path = '';
}
if (false !== $flags && is_array($flags) && !empty($flags)) {
    foreach ($flags as $k => $v) {
        $search_path .= '&searchflags'.urlencode('['.basename($k).']').'='.intval($v);
    }
}
$emailCollapseThreads = (isset($_PM_['core']['email_collapse_threads']) && $_PM_['core']['email_collapse_threads']);

// How many mails are on the server? Size of 'em?
$FS = new handler_email_driver($_SESSION['phM_uid']);

$folder = $FS->get_folder_info($_SESSION['workfolder']);
if (false !== $folder) {
    $is_imap = ($folder['type'] == 10 || $folder['type'] == 11) && $folder['icon'] != ':imapbox';
    $eingang = $folder['mailnum'];
    $foldertype = $folder['icon'];
    $workfolder = $_SESSION['workfolder'];
    // Extract choices for this folder; the preview setting, fields to show
    $choices = $folder['settings'];
    if (isset($choices['use_preview'])) {
        $use_preview = $choices['use_preview'];
    }
    // Virtual folder support
    if ($foldertype == ':virtual') {
        /**
         * Some super black magic got to jump in here ...
         */
    }

    if (isset($choices['orderby']) && $choices['orderby']) {
        $orderby = $choices['orderby'];
        $orderdir = $choices['orderdir'];
    }
    if (isset($choices['groupby']) && $choices['groupby']) {
        $groupby = $choices['groupby'];
    }
} else {
    $is_imap = false;
    $eingang = 0;
    $choices = array();
    $foldertype = false;
}
if (isset($_REQUEST['orderby']) && isset($fieldnames[$_REQUEST['orderby']])) {
    $orderby = $_REQUEST['orderby'];
    $orderdir = (isset($_REQUEST['orderdir']) && 'DESC' == $_REQUEST['orderdir']) ? 'DESC' : 'ASC';
    if (!empty($choices)) {
        $choices['orderby'] = $orderby;
        $choices['orderdir'] = $orderdir;
        $FS->set_folder_settings($_SESSION['workfolder'], $choices);
    }
}
$ordlink .= '&orderby='.$orderby.'&orderdir='.$orderdir;
if (isset($_REQUEST['groupby']) && isset($fieldnames[$_REQUEST['groupby']])) {
    $groupby = $_REQUEST['groupby'];
    $ordlink .= '&groupby='.$groupby;
    if (!empty($choices)) {
        $choices['groupby'] = $groupby;
        $FS->set_folder_settings($_SESSION['workfolder'], $choices);
    }
}
// This if() handles the display of child mails for a given thread
if (!empty($_REQUEST['thread_id'])) {
    $criteria = '@@thread@@';
    $pattern  = $_REQUEST['thread_id'];
    $ignore   = $_REQUEST['ignore'];
    $emailCollapseThreads = false;
}

$showfields = ($foldertype == ':sent')
        ? array('status' => 1, 'hpriority' => 1, 'attachments' => 1, 'hsubject' => 1, 'hto' => 1, 'hdate_sent' => 1, 'hsize' => 1)
        : array('status' => 1, 'hpriority' => 1, 'attachments' => 1, 'hsubject' => 1, 'hfrom' => 1, 'hdate_sent' => 1, 'hsize' => 1);
if (isset($choices['show_fields']) && !empty($choices['show_fields'])
        && (!isset($choices['use_default_fields']) || !$choices['use_default_fields'])) {
    $showfields = $choices['show_fields'];
    if (isset($showfields['hdate'])) {
        $showfields['hdate_sent'] = $showfields['hdate'];
        unset($showfields['hdate']);
    }
} elseif (isset($_PM_['email']['folder_default_fields']) && !empty($_PM_['email']['folder_default_fields'])) {
    $showfields = $_PM_['email']['folder_default_fields'];
    if (isset($showfields['hdate'])) {
        $showfields['hdate_sent'] = $showfields['hdate'];
        unset($showfields['hdate']);
    }
}

$sf_js = array();
foreach ($showfields as $f => $a) {
    if (!$a) {
        continue;
    }
    if ($f == 'colour') {
        continue; // Colour mark no longer via extra field, but line mark instead
    }
    $sf_js[] = '"'.$f.'" : {"n":"'.$fieldnames[$f]['n'].'","i":"'.$fieldnames[$f]['i'].'","t":"'.$fieldnames[$f]['t'].'" }';
}

if (!empty($criteria) && !empty($pattern)) {
    $groesse = $FS->mail_test_search($workfolder, $criteria, $pattern, $flags);
    $all_size = isset($groesse['size']) ? $groesse['size'] : 0;
    $eingang = isset($groesse['mails']) ? $groesse['mails'] : 0;
}

if (!isset($_PM_['core']['pagesize']) || !$_PM_['core']['pagesize'] || $criteria == '@@thread@@') {
    $displaystart = 1;
    $i = $displayend = $eingang;
} else {
    if ($_SESSION['phM_pagenum'] < 0) {
        $_SESSION['phM_pagenum'] = 0;
    }
    if ($_PM_['core']['pagesize'] * $_SESSION['phM_pagenum'] > $eingang) {
        $_SESSION['phM_pagenum'] = ceil($eingang/$_PM_['core']['pagesize']) - 1;
    }
    $displaystart = $_PM_['core']['pagesize'] * $_SESSION['phM_pagenum'] + 1;
    $displayend = $_PM_['core']['pagesize'] * ($_SESSION['phM_pagenum'] + 1);
    if ($displayend > $eingang) {
        $displayend = $eingang;
    }
    $i = $displayend;
}
$groesse = $FS->init_mails($workfolder, $displaystart, ($displayend-$displaystart+1), $orderby, $orderdir, $criteria, $pattern, $flags);
$all_size = isset($groesse['size']) ? $groesse['size'] : 0;
$eingang = isset($groesse['mails']) ? $groesse['mails'] : 0;
$myPageNum = $_SESSION['phM_pagenum'];

// We do no longer need the session from this point on
session_write_close();

if (!isset($_REQUEST['jsreq'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'inbox.general.tpl');
} else {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'inbox.json.tpl');
}
$sumgroess = 0;
$maillines = array();
if ($eingang > 0) {
    if (function_exists('mb_strtolower')) {
        $strtolower = 'mb_strtolower';
        mb_internal_encoding('UTF-8');
    } else {
        $strtolower = 'strtolower';
    }
    // First get all mails in range, put into array to work on
    foreach (range($displaystart, $displayend) as $i) {
        $workmail = $FS->get_mail_info($i-1);
        if (empty($workmail)) {
            continue;
        }
        $maillines[$i] = $workmail;
        // Grouping enabled? Handle some special cases here
        if (false !== $groupby) {
            if (in_array($groupby, array('hto', 'hfrom', 'hcc', 'hbcc'))) {
                $cleanedup = multi_address($maillines[$i][$fieldnames[$groupby]['db']], 1, 'sort');
                $maillines[$i]['groupsort'] = $cleanedup;
            } elseif (in_array($groupby, array('hdate_sent', 'hdate_received'))) {
                $maillines[$i]['groupsort'] = substr($maillines[$i][$fieldnames[$groupby]['db']], 0, 10);
            } elseif ($groupby == 'hsize') {
                $maillines[$i]['groupsort'] = group_size($maillines[$i]['size']);
            } elseif ($groupby == 'hsubject') {
                $maillines[$i]['groupsort'] = preg_replace
                        ('!^((Re|Re\-(\d)+|AutoReply|AW|WG|Fw|Fwd|Gelesen|Read)\:(\s)?)+!i'
                        ,''
                        ,preg_replace('!^\[|\]$!', '', $maillines[$i]['subject']) // [Fwd ... ] is found, too
                        );
            }
        }
    }
    // Apply grouping
    if (false !== $groupby) {
        $sort = $sort2 = array();
        if (in_array($groupby, array('hto', 'hfrom', 'hcc', 'hbcc', 'hsubject'))) {
            foreach ($maillines as $i => $workmail) {
                $sort[$i] = $strtolower($workmail['groupsort']);
                $sort2[$i] = $workmail[$fieldnames[$orderby]['db']];
            }
            $groupdir = SORT_ASC;
            $grouptype = SORT_STRING;
        } elseif (in_array($groupby, array('hdate_sent', 'hdate_received'))) {
            foreach ($maillines as $i => $workmail) {
                $date = $workmail['groupsort'];
                $sort[$i] = $date;
                $sort2[$i] = $workmail[$fieldnames[$orderby]['db']];
                $date = date($WP_msg['dateformat_old'], mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4)));
                $maillines[$i]['groupsort'] = $date;
            }
            $groupdir = SORT_DESC;
            $grouptype = SORT_STRING;
        } elseif ($groupby == 'hsize') {
            foreach ($maillines as $i => $workmail) {
                $size = $workmail['groupsort'];
                $sort[$i] = $size;
                $sort2[$i] = $workmail[$fieldnames[$orderby]['db']];
                $maillines[$i]['groupsort'] = ($size == 9999999) ? '> '.size_format(5242880, true, false) : '< '.size_format($size, true, false);
            }
            $groupdir = SORT_DESC;
            $grouptype = SORT_NUMERIC;
        } else {
            foreach ($maillines as $i => $workmail) {
                $sort[$i] =  $strtolower($workmail[$fieldnames[$groupby]['db']]);
                $sort2[$i] = $workmail[$fieldnames[$orderby]['db']];
            }
            $groupdir = SORT_DESC;
            $grouptype = SORT_NUMERIC;
        }
        array_multisort($sort, $groupdir, $grouptype, $sort2, ($orderdir == 'ASC' ? SORT_ASC : SORT_DESC), SORT_STRING, $maillines);
    }
    // Do output
    $tpl_lines = $tpl->get_block('maillines');
    $i = $displaystart;
    $threadcache = array();
    foreach ($maillines as $workmail) {
        $groesse = isset($workmail['size']) ? $workmail['size'] : 0;
        if ($groesse > 0) {
            $sumgroess += $groesse;
        } else {
            $groesse = '-';
        }
        if ($emailCollapseThreads && $workmail['thread_id']) {
            if (isset($threadcache[$workmail['thread_id']])) {
                continue;
            } else {
                $threadcache[$workmail['thread_id']] = true;
            }
        }
        $mailcolour = (!is_null($workmail['colour']) && $workmail['colour'] != '') ? $workmail['colour'] : '';
        $status = isset($workmail['status']) && $workmail['status'] ? 1 : 0;
        $answered = isset($workmail['answered']) && $workmail['answered'] ? 1 : 0;
        $forwarded = isset($workmail['forwarded']) && $workmail['forwarded'] ? 1 : 0;
        $bounced = isset($workmail['bounced']) && $workmail['bounced'] ? 1 : 0;

        if (in_array($workmail['type'], array('sms', 'ems', 'mms', 'fax'))) { // These have numeric addresses
            $to = array(0 => $workmail['to'], 1 => $workmail['to'], 2 => $workmail['to']);
            $cc = Format_Parse_Email::parse_email_address($workmail['cc']);
            $from = array(0 => $workmail['from'], 1 => $workmail['from'], 2 => $workmail['from']);
            if ($status) {
                $statusicon = 'fax' == $workmail['type'] ? 'fax_read.png' : 'sms_read.gif';
                $statustext  = $WP_msg['marked_read'];
            } else {
                $statusicon = 'fax' == $workmail['type'] ? 'fax_unread.png' : 'sms_unread.gif';
                $statustext  = $WP_msg['marked_unread'];
            }
        } else {
            if ('receipt' == $workmail['type']) {
                $statusicon = 'mdn_read.gif';
                $statustext  = $WP_msg['stat_mdn_read'];
            } elseif ('appointment' == $workmail['type']) {
                $statusicon = 'appointment.gif';
                $statustext  = $WP_msg['stat_appointment'];
            } elseif ('sysmail' == $workmail['type']) {
                $statusicon = ($status) ? 'sysmail_read.gif' : 'sysmail.gif';
                $statustext  = $WP_msg['stat_sysmail'];
            } else {
                switch (($status*1000) + ($answered*100) + ($forwarded*10) + ($bounced)) {
                case 1000:
                case 1001:
                    $statusicon = 'mail_read.gif';
                    $statustext = $WP_msg['marked_read'];
                    break;
                case 1100:
                case 1101:
                    $statusicon = 'mail_answer.gif';
                    $statustext = $WP_msg['marked_answered'];
                    break;
                case 1010:
                case 1011:
                    $statusicon = 'mail_forward.gif';
                    $statustext = $WP_msg['marked_forwarded'];
                    break;
                case 1110:
                case 1111:
                    $statusicon = 'mail_forwardedanswered.gif';
                    $statustext = $WP_msg['marked_forwarded'];
                    break;
                case 100:
                case 101:
                    $statusicon = 'mail_unreadanswered.gif';
                    $statustext = $WP_msg['marked_answered'];
                    break;
                case 110:
                case 111:
                    $statusicon = 'mail_unreadforwardedanswered.gif';
                    $statustext = $WP_msg['marked_forwarded'];
                    break;
                case 10:
                case 11:
                    $statusicon = 'mail_unreadforwarded.gif';
                    $statustext = $WP_msg['marked_forwarded'];
                    break;
                default:
                    $statusicon = 'mail_unread.gif';
                    $statustext = $WP_msg['marked_unread'];
                    break;
                }
            }
            $to = multi_address($workmail['to'], 5, 'maillist');
            $cc = multi_address($workmail['cc'], 5, 'maillist');
            $from = multi_address($workmail['from'], 5, 'maillist');
        }
        $workmail['date_sent'] = strtotime($workmail['date_sent']);
        if (-1 == $workmail['date_sent']) {
            $short_datum = $datum = '---';
        } else {
            $datum = htmlspecialchars(date($WP_msg['dateformat'], $workmail['date_sent']));
            if (date('Y', $workmail['date_sent']) == date('Y')) {
                $short_datum = htmlspecialchars(date($WP_msg['dateformat_new'], $workmail['date_sent']));
            } else {
                $short_datum = htmlspecialchars(date($WP_msg['dateformat_old'], $workmail['date_sent']));
            }
        }
        $prioicon = $priotext = '';
        if (1 == $workmail['priority']) {
            $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['high'].' (1)');
            $prioicon = 'prio_1';
        } elseif (2 == $workmail['priority']) {
            $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['high'].' (2)');
            $prioicon = 'prio_2';
        } elseif (4 == $workmail['priority']) {
            $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['low'].' (4)');
            $prioicon = 'prio_4';
        } elseif (5 == $workmail['priority']) {
            $priotext = phm_entities($WP_msg['prio'].' '.$WP_msg['low'].' (5)');
            $prioicon = 'prio_5';
        }
        $data = array(
                'from_1' => uctc::convert($from[0], 'utf8', 'utf8', true),
                'from_2' => uctc::convert($from[2], 'utf8', 'utf8', true),
                'from_3' => uctc::convert($from[1], 'utf8', 'utf8', true),
                'to_1' => uctc::convert($to[0], 'utf8', 'utf8', true),
                'to_2' => uctc::convert($to[2], 'utf8', 'utf8', true),
                'to_3' => uctc::convert($to[1], 'utf8', 'utf8', true),
                'cc_1' => uctc::convert($cc[0], 'utf8', 'utf8', true),
                'cc_2' => uctc::convert($cc[2], 'utf8', 'utf8', true),
                'cc_3' => uctc::convert($cc[1], 'utf8', 'utf8', true),
                'statusicon' => $statusicon,
                'statustext' => $statustext,
                'unread' => $status ? 0 : 1,
                'prioicon' => $prioicon,
                'priotext' => $priotext,
                'colour' => 'NULL' == $mailcolour ? '': $mailcolour,
                'is_unread' => $status ? 0 : 1,
                'att' => isset($workmail['attachments']) && $workmail['attachments'] ? 1 : 0,
                'subj' => uctc::convert($workmail['subject'], 'utf8', 'utf8', true),
                'uidl' => $workmail['id'],
                'thread_id' => $workmail['thread_id'] ? $workmail['thread_id'] : '',
                'date' => $short_datum,
                'dateraw' => $datum,
                'size' => size_format($groesse, 1, 0),
                'sizeraw' => $groesse
                );
        if (isset($workmail['groupsort'])) {
            $data['groupsort'] = $workmail['groupsort'];
        }
        if (isset($criteria) && $criteria == '@@thread@@') {
            $data['folder_id'] = $workmail['folder_id'];
        }

        $tpl_lines->assign(array
                ('num' => $i
                ,'data' => str_replace('{', "{\\", json_encode($data))
                ,'notfirst' => $i == $displaystart ? '' : ','
                ));
        $tpl->assign('maillines', $tpl_lines);
        $tpl_lines->clear();
        $i++;
    }
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
        ('rawsumsize' => number_format($sumgroess, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'sumsize' => size_format($sumgroess, 1, 0)
        ,'rawallsize' => number_format($all_size, 0, $WP_msg['dec'], $WP_msg['tho'])
        ,'allsize' => size_format($all_size, 1, 0)
        ,'size' => $jumpsize
        ,'maxlen' => $jumpsize
        ,'page' => $myPageNum + ($eingang == 0 ? 0 : 1)
        ,'boxsize' => $max_page
        ,'neueingang' => $eingang
        ,'displaystart' => ($eingang == 0) ? 0 : $displaystart
        ,'displayend' => $displayend
        ,'showfields' => '{'.implode(', ', $sf_js).'}'
        ,'groupby' => (false !== $groupby) ? $groupby : ''
        ,'orderby' => $orderby
        ,'orderdir' => $orderdir
        ,'pagenum' => $myPageNum
        ,'pagesize' => $_PM_['core']['pagesize']
        ,'jsrequrl' => $jumppath.$search_path.$ordlink.'&jsreq=1'
        ,'automarkread' => (!empty($_PM_['core']['automarkread'])) ? $_PM_['core']['automarkread_time'] : ''
        ,'is_imap' => ($is_imap) ? 1 : 0
        ,'is_junk' => (':junk' == $folder['icon']) ? 1 : 0
        ,'collapse_threads' => ($emailCollapseThreads) ? 1 : 0
        ,'mark_junk' => (isset($_PM_['antijunk']['use_feature']) && $_PM_['antijunk']['use_feature']
                && isset($_PM_['antijunk']['cmd_learnspam']) && $_PM_['antijunk']['cmd_learnspam']
                && isset($_PM_['antijunk']['cmd_learn_ham']) && $_PM_['antijunk']['cmd_learn_ham']) ? 1 : 0
        ,'folder_writable' => (int) ($folder['uid'] == $_SESSION['phM_uid'])
        ,'use_preview' => (isset($use_preview) && $use_preview) ? 1 : 0
        ,'allow_resize' => (!isset($_PM_['core']['resize_mainwindows']) || $_PM_['core']['resize_mainwindows']) ? 1 : 0
        ,'customheight' => (isset($_PM_['customsize']['email_previewheight']) && $_PM_['customsize']['email_previewheight'])
                ? $_PM_['customsize']['email_previewheight']
                : 0
        ));
// This is a JSON request, which just needs the maillist and a few info bits 'bout that folder
if (isset($_REQUEST['jsreq'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $tpl->display();
    exit;
}

if (empty($_PM_['fulltextsearch']['enabled'])) {
    $tpl->assign_block('nofulltextsearch');
}

$viewlink = PHP_SELF.'?l=read&h=email&'.$passthrough;
if ($foldertype == ':drafts' || $foldertype == ':templates') {
    $viewlink = PHP_SELF.'?l=compose_email&h=core&'.$passthrough.'&replymode='.($foldertype == ':drafts' ? 'draft' : 'template').'&from_h=email';
    if (':templates' == $foldertype) {
        $viewlink .= '&isatpl=1';
    }
}

// Permissions reflected in context menu items
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_new_email']) {
    $tpl->assign_block('ctx_newmail');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_copy_email']) {
    $tpl->assign_block('ctx_copy');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_move_email']) {
    $tpl->assign_block('ctx_move');
}
if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_delete_email']) {
    $tpl->assign_block('ctx_delete');
}

$tpl->assign(array
        ('msg_subject' => $WP_msg['subject']
        ,'preview_subject' => $WP_msg['subject']
        ,'msg_from' => $WP_msg['from']
        ,'preview_hfrom' => $WP_msg['from']
        ,'preview_hreplyto' => 'Reply-To'// $WP_msg['replyto']
        ,'msg_to' => $WP_msg['to']
        ,'msg_cc' => 'CC'
        ,'msg_bcc' => 'BCC'
        ,'msg_sallheaders' => $WP_msg['MailSearchAllHead']
        ,'msg_sbody' => $WP_msg['MailSearchBody']
        ,'msg_scomplete' => $WP_msg['MailSearchAll']
        ,'msg_sto' => $WP_msg['MailSearchToCc']
        ,'preview_hto' => $WP_msg['to']
        ,'msg_date' => $WP_msg['date']
        ,'preview_hdate' => $WP_msg['date']
        ,'msg_size' => $WP_msg['size']
        ,'del' => $WP_msg['del']
        ,'bounce' => $WP_msg['bounce']
        ,'print' => $WP_msg['prnt']
        ,'go' => $WP_msg['goto']
        ,'but_search' => $WP_msg['ButSearch']
        ,'msg_page' => $WP_msg['page']
        ,'selection' => $WP_msg['selection']
        ,'allpage' => $WP_msg['allpage']
        ,'msg_markreadset' => $WP_msg['markread_set']
        ,'msg_markreadunset' => $WP_msg['markread_unset']
        ,'answer' => $WP_msg['answer']
        ,'answerAll' => $WP_msg['answerAll']
        ,'forward' => $WP_msg['forward']
        ,'search' => $WP_msg['ButSearch']
        ,'msg_copy' => $WP_msg['copytofolder']
        ,'msg_move' => $WP_msg['movetofolder']
        ,'msg_blockedwarning' => $WP_msg['BlockedWarning']
        ,'msg_blockedtitle' => $WP_msg['BlockedTitle']
        ,'msg_blockedunblock' => $WP_msg['BlockedUnblock']
        ,'msg_markcolour' => $WP_msg['markmailColour']
        ,'msg_mark_spam' => $WP_msg['markmailSPAM']
        ,'msg_mark_ham' => $WP_msg['markmailHAM']
        ,'msg_none' => $WP_msg['selNone']
        ,'msg_all' => $WP_msg['selAll']
        ,'msg_rev' => $WP_msg['selRev']
        ,'msg_editasnew' => $WP_msg['EditAsNew']
        ,'archive' => $WP_msg['EmailSendToArchive']
        ,'but_last' => '&lt;&lt;'
        ,'but_next' => '&gt;&gt;'
        ,'msg_killconfirm' => $WP_msg['killJSconfirm']
        ,'msg_cancel' => $WP_msg['cancel']
        ,'msg_ok' => 'OK'
        ,'msg_bounce_del' => $WP_msg['DelAfterBounce']
        ,'msg_bounce_all' => $WP_msg['BounceForAll']
        ,'msg_unblock_thismail' => $WP_msg['HTMLUnblockThisMail']
        ,'msg_unblock_email' => $WP_msg['HTMLUnblockEmailAddr']
        ,'msg_unblock_domain' => $WP_msg['HTMLUnblockWholeDomain']
        ,'msg_thread_other_folder' => $WP_msg['ThreadOtherFolder']
        ,'handler' => 'email'
        ,'PHP_SELF' => PHP_SELF
        ,'passthrough' => $passthrough
        ,'passthrough_2' => give_passthrough(2)
        ,'viewlink' => $viewlink
        ,'preview_url' => PHP_SELF.'?l=read&preview=true&h=email&'.$passthrough.'&mail='
        ,'fetcher_url' => PHP_SELF.'?h=email&l=fetcher.run&issuer=user&'.$passthrough.'&folder='.$_SESSION['workfolder']
        ,'link_sendtoadb' => PHP_SELF.'?l=edit_contact&h=contacts&'.$passthrough
        ,'mailops_url' => PHP_SELF.'?l=worker&h=email&'.$passthrough.'&what=mail_'
        ,'bounce_url' => PHP_SELF.'?'.$passthrough.'&l=send_email&h=core&WP_do=bounce&from_h=email&mail='
        ,'search_adb_url' => PHP_SELF.'?l=apiselect&h=contacts&what=email&'.$passthrough
        ,'workfolder' => $_SESSION['workfolder']
        ));

/**
 * Return the nearest higher size block, a mailsize falls under. e.g.
 * 99.7KB is < 100 KB, so 100000 is returned
 */
function group_size($size)
{
    foreach (array(1024, 10240, 51200, 102400, 524288, 1048576, 5242880) as $thresh) {
        if ($size < $thresh) {
            return $thresh;
        }
    }
    return 9999999;
}