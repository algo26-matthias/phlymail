<?php
/**
 * api.php - Offering API calls for interoperating with other handlers
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler RSS
 * @copyright 2004-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2013-08-12 $Id: api.php 2731 2013-03-25 13:24:16Z mso $
 */
// Only valid within phlyMail
if (!defined('_IN_PHM_')) die();

if (!defined('RSS_PUBLIC_FEEDS')) {
    if (isset($_PM_['core']['rss_nopublics']) && $_PM_['core']['rss_nopublics']) {
        define('RSS_PUBLIC_FEEDS', false);
    } else {
        define('RSS_PUBLIC_FEEDS', true);
    }
}

class handler_rss_api extends handler_rss_driver
{
    protected $_PM_, $uid;

    /**
     * Constructor method
     *
     * @param  array $_PM_ reference  public settings structure
     * @param  int  $uid ID of the user to perform the operation for
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid)
    {
        $this->_PM_ = $_PM_;
        $this->uid = $uid;
        parent::__construct($uid);
    }

    /**
     * Returns a list of existing folders for a given user
     * @param  bool  If set to true, only local folders will be returned (no IMAP or others)
     * @return  array  Folder list with various meta data
     * @since 0.0.9
     */
    public function give_folderlist($local_only = false)
    {
        $_PM_ = $this->_PM_;
    	$myUsername = 'unknown user';
    	if (!empty($_SESSION['phM_username'])) {
    	    $myUsername = basename($_SESSION['phM_username']);
    	} elseif (!empty($this->uid)) {
    	    $DB = new DB_Base();
    	    $userInfo = $DB->get_usrdata($this->uid, true);
    	    $myUsername = $userInfo['username'];
    	}
        $icon_path = $this->_PM_['path']['theme'].'/icons/';
        $WP_msg = &$GLOBALS['WP_msg'];
        if (file_exists($_PM_['path']['handler'].'/rss/lang.'.$WP_msg['language'].'.php')) {
            require_once($_PM_['path']['handler'].'/rss/lang.'.$WP_msg['language'].'.php');
        } else {
            require_once($_PM_['path']['handler'].'/rss/lang.de.php');
        }

        $folders = array();
        foreach ($this->get_hybridlist(true) as $k => $v) {
            if ($v['owner'] == 0) {
                if (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['rss_see_global_feeds']) continue;
                $hasSharedFolders = true;
            } else {
                $hasPersonalFolders = true;
            }
            $folders[$k] = $v;
        }
        if (empty($folders)) return array();

    	$return = array();
    	foreach ($folders as $k => $v) {
    	    $basefolder = 'folder_def';
            if ($v['childof'] == 0) {
                $v['childof'] = 'root';
            }
            if (!empty($v['type']) && $v['type'] == 2) {
                $basefolder = 'rss';
            } elseif ($v['owner'] == 0) {
                $basefolder = 'contactsfolder_global';
                if ($v['childof'] == 0) $v['childof'] = 'shareroot';
            }
            $return[$k] = array(
                    'path' => $v['path'],
                    'foldername' => $v['name'],
                    'type' => !empty($v['type']) ? $v['type'] : 0,
                    'icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'.png',
                    'big_icon' => $_PM_['path']['theme'].'/icons/'.$basefolder.'_big.gif',
                    'subdirs' => !empty($v['subdirs']) ? 1 : 0,
                    'childof' => $v['childof'],
                    'has_folders' => !empty($v['has_folders']) ? 1 : 0,
                    'has_items' => !empty($v['has_items']) ? 1 : 0,
                    'level' => $v['level']+1
                    );
    	}
    	return $return;
    }

    public function selectfile_itemlist($fid, $offset = 0, $amount = 100, $orderby = 'added', $orderdir = 'DESC')
    {
        $fid = intval($fid);
        global $WP_msg;
        $return = array();

        foreach ($this->get_index(RSS_PUBLIC_FEEDS, $fid, '', '', $amount, $offset, $orderby, $orderdir) as $workmail) {

            $mailcolour = (!is_null($workmail['colour']) && $workmail['colour'] != '') ? $workmail['colour'] : '';
            $status = isset($workmail['status']) && $workmail['status'] ? 1 : 0;
            $answered = isset($workmail['answered']) && $workmail['answered'] ? 1 : 0;
            $forwarded = isset($workmail['forwarded']) && $workmail['forwarded'] ? 1 : 0;
            $bounced = isset($workmail['bounced']) && $workmail['bounced'] ? 1 : 0;
            $itemtype = isset($workmail['type']) ? $workmail['type'] : 'mail';

            if (in_array($workmail['type'], array('sms', 'ems', 'mms', 'fax'))) { // These have numeric addresses
                $from = array(0 => $workmail['from'], 1 => $workmail['from'], 2 => $workmail['from']);
                if ($status) {
                    $statusicon = 'fax' == $workmail['type'] ? 'fax_read' : 'sms_read';
                    $statustext  = $WP_msg['marked_read'];
                } else {
                    $statusicon = 'fax' == $workmail['type'] ? 'fax_unread' : 'sms_unread';
                    $statustext  = $WP_msg['marked_unread'];
                }
            } else {
                $from = multi_address($workmail['from'], 5, 'maillist');
                if ('receipt' == $workmail['type']) {
                    $statusicon = 'mdn_read';
                    $statustext  = $WP_msg['stat_mdn_read'];
                } elseif ('appointment' == $workmail['type']) {
                    $statusicon = 'appointment';
                    $statustext  = $WP_msg['stat_appointment'];
                } elseif ('sysmail' == $workmail['type']) {
                    $statusicon = ($status) ? 'sysmail_read.gif' : 'sysmail.gif';
                    $statustext  = $WP_msg['stat_sysmail'];
                } else {
                    switch (($status*1000) + ($answered*100) + ($forwarded*10) + ($bounced)) {
                        case 1000: case 1001: $statusicon = 'mail_read';    $statustext = $WP_msg['marked_read'];      break;
                        case 1100: case 1101: $statusicon = 'mail_answer';  $statustext = $WP_msg['marked_answered'];  break;
                        case 1010: case 1011: $statusicon = 'mail_forward'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 1110: case 1111: $statusicon = 'mail_forwardedanswered'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 100:  case 101:  $statusicon = 'mail_unreadanswered';    $statustext = $WP_msg['marked_answered'];  break;
                        case 110:  case 111:  $statusicon = 'mail_unreadforwardedanswered'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 10:   case 11:   $statusicon = 'mail_unreadforwarded';   $statustext = $WP_msg['marked_forwarded']; break;
                        default:              $statusicon = 'mail_unread';            $statustext = $WP_msg['marked_unread'];
                    }
                }
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
            if (!empty($statusicon)) {
                // Prefer PNG over old fashioned gif
                if (file_exists($this->_PM_['path']['frontend'].'/filetypes/32/x_phlymail_'.$statusicon.'.png')) {
                    $statusicon = $this->_PM_['path']['frontend'].'/filetypes/32/x_phlymail_'.$statusicon.'.png';
                } else {
                    $statusicon = $this->_PM_['path']['frontend'].'/filetypes/32/x_phlymail_'.$statusicon.'.gif';
                }
            }
            if (!empty($prioicon)) {
                // Prefer PNG over old fashioned gif
                if (file_exists($this->_PM_['path']['theme'].'/icons/'.$prioicon.'.png')) {
                    $prioicon = $this->_PM_['path']['theme'].'/icons/'.$prioicon.'.png';
                } else {
                    $prioicon = $this->_PM_['path']['theme'].'/icons/'.$prioicon.'.gif';
                }
            }
            $data = array
                    ('id' => $workmail['id']
                    ,'i32' => $statusicon
                    ,'mime' => $statustext
                    ,'aside' => $short_datum
                    ,'l1' => str_replace('  ', ' ', $workmail['subject'])
                    ,'l2' => str_replace('  ', ' ', $from[1])
                    ,'unread' => $status ? 0 : 1
                    ,'prioicon' => $prioicon
                    ,'priotext' => $priotext
                    ,'colour' => 'NULL' == $mailcolour ? '': $mailcolour
                    ,'is_unread' => $status ? 0 : 1
                    ,'att' => isset($workmail['attachments']) && $workmail['attachments'] ? 1 : 0
                    );
            $return[] = $data;
            unset($data);
        }
        return $return;
    }

    /**
     * Inits a SendTo handshake as the initiator of a SendTo. This method is called
     * by the receiving handler to get some info about the mail part it will receive.
     * This info usually is displayed to the user to allow some dedicated action by him.
     *
     * @param string $item  ID of the mail you wish to address and the part, appended as .<part>
     * @return mixed Array | false
     * @since 0.2.6
     */
    public function sendto_fileinfo($item)
    {
        $item = preg_replace('![^0-9\.]!', '', $item);
        list ($item, $part) = explode('.', $item, 2);
        if (isset($part) && $part !== false) {
            return $this->give_mail_part($item, $part, false, true);
        }
        return false;
    }

    /**
     * SendTo handshake part 2: The receiver now tells us to initialise the sending process
     * A known weakness of the current method is, that it does not do well with large mail attachments,
     * since those are read in at once and returned. This will be a problem with less powerful setups
     * and low memory_limit values.
     * The method name suggests, that it is used to initalise the SendTo process, whereas it currently completes
     * it. This is done by choice: Later improvements on this mechanism might really init the process here, using
     * further methods to finish it.
     *
     * @param string $item ID of the mail we wish to address and the part, appended as .<part>
     * @return string The whole mail part in one huge string, so beware ...
     * @since 0.2.6
     */
    public function sendto_sendinit($item)
    {
        $item = preg_replace('![^0-9\.]!', '', $item);
        list ($item, $part) = explode('.', $item, 2);
        if (isset($part) && $part !== false) {
            return $this->give_mail_part($item, $part);
        }
        return false;
    }

    /**
     * Returns data of boyes for the pinboard
     *
     *[@param string $box  Name of the box; Default: all boxes]
     * @return array  Data fo all boxes or just the specified one's rows
     */
    public function pinboard_boxes($box = null)
    {
        return array();

        $WP_msg = &$GLOBALS['WP_msg'];
        $return = array();
        if (is_null($box) || $box == 'emails') {
            $return['emails'] = array
                    ('headline' => 'Emails'
                    ,'icon' => 'email.png'
                    ,'action' => 'email_pinboard_opener'
                    ,'cols' => array
                            ('ico' => array('w' => 20, 'a' => 'l')
                            ,'subj' => array('w' => '', 'a' => 'l')
                            ,'from' => array('w' => '', 'a' => 'l')
                            ,'date' => array('w' => 104, 'a' => 'l')
                            )
                    );
            $rows = array();
            foreach ($this->STOR->mail_pinboard_digest() as $workmail) {
                $mailcolour = (!is_null($workmail['colour']) && $workmail['colour'] != '') ? $workmail['colour'] : '';
                $status = isset($workmail['status']) && $workmail['status'] ? 1 : 0;
                $answered = isset($workmail['answered']) && $workmail['answered'] ? 1 : 0;
                $forwarded = isset($workmail['forwarded']) && $workmail['forwarded'] ? 1 : 0;
                $bounced = isset($workmail['bounced']) && $workmail['bounced'] ? 1 : 0;
                $itemtype = isset($workmail['type']) ? $workmail['type'] : 'mail';
                if ('receipt' == $workmail['type']) {
                    $statusicon = 'mdn_read';
                    $statustext  = $WP_msg['stat_mdn_read'];
                } elseif ('appointment' == $workmail['type']) {
                    $statusicon = 'appointment';
                    $statustext  = $WP_msg['stat_appointment'];
                } elseif ('sysmail' == $workmail['type']) {
                    $statusicon = ($status) ? 'sysmail_read' : 'sysmail';
                    $statustext  = $WP_msg['stat_sysmail'];
                } else {
                    switch (($status*1000) + ($answered*100) + ($forwarded*10) + ($bounced)) {
                        case 1000: case 1001: $statusicon = 'mail_read';    $statustext = $WP_msg['marked_read'];      break;
                        case 1100: case 1101: $statusicon = 'mail_answer';  $statustext = $WP_msg['marked_answered'];  break;
                        case 1010: case 1011: $statusicon = 'mail_forward'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 1110: case 1111: $statusicon = 'mail_forwardedanswered'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 100:  case 101:  $statusicon = 'mail_unreadanswered';    $statustext = $WP_msg['marked_answered'];  break;
                        case 110:  case 111:  $statusicon = 'mail_unreadforwardedanswered'; $statustext = $WP_msg['marked_forwarded']; break;
                        case 10:   case 11:   $statusicon = 'mail_unreadforwarded';   $statustext = $WP_msg['marked_forwarded']; break;
                        default:              $statusicon = 'mail_unread';            $statustext = $WP_msg['marked_unread'];
                    }
                }
                $from = multi_address($workmail['from'], 5, 'maillist');
                $workmail['date_sent'] = strtotime($workmail['date_sent']);
                if (-1 == $workmail['date_sent']) {
                    $short_datum = $datum = '---';
                } else {
                    $datum = date($WP_msg['dateformat'], $workmail['date_sent']);
                    if (date('Y', $workmail['date_sent']) == date('Y')) {
                        $short_datum = date($WP_msg['dateformat_new'], $workmail['date_sent']);
                    } else {
                        $short_datum = date($WP_msg['dateformat_old'], $workmail['date_sent']);
                    }
                }
                $css = ($status ? '' : 'font-weight:bold;').('NULL' == $mailcolour ? '' : 'color:'.$mailcolour);
                $rows[] = array(
                        'id' => $workmail['id'],
                        'ico' => array('v' => '<img src="'.$this->_PM_['path']['theme'].'/icons/'.$statusicon.'.gif" />', 't' => $statustext),
                        'subj' => array('v' => $workmail['subject'], 't' => $workmail['subject'], 'css' => $css),
                        'from' => array('v' => $from[1], 't' => $from[2], 'css' => $css),
                        'date' => array('v' => $short_datum, 't' => $datum, 'css' => $css)
                        );
                }
            $return['rss']['rows'] = $rows;
        }
        return (is_null($box)) ? $return : $return[$box]['rows'];
    }

    /**
     * Triggers the archive action according to global settings on all
     * matching items in the given folder.
     * Items are selected by their age.
     *
     * @param int $fid  ID of the folder
     * @param string  $age  Age in SQL form, e.g. "1 month"
     */
    public function folder_archive_items($fid, $age)
    {
        foreach ($this->STOR->get_archivable_items($fid, $age) as $mail) {
            $this->STOR->archive_mail($mail['id'], false, $this->_PM_);
        }

    }

    /**
     * Triggers the expire (autodelete) action according to global settings on all
     * matching items in the given folder.
     * Items are selected by their age.
     *
     * @param int $fid  ID of the folder
     * @param string  $age  Age in SQL form, e.g. "1 month"
     */
    public function folder_expire_items($fid, $age)
    {
        foreach ($this->STOR->get_archivable_items($fid, $age) as $mail) {
            $this->STOR->delete_mail($mail['id']);
        }
    }
}