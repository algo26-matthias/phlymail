<?php
/**
 * api.php - Offering API calls for interoperating with other handlers
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler: Email
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.0 2015-05-22
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_email_api
{
    private $STOR, $_PM_, $uid;

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
        $this->STOR = new handler_email_driver($uid, '');
        $this->uid = $uid;
    }

    public function __destruct()
    {
        unset($this->STOR);
    }

    public function changeUID($uid)
    {
    	$this->uid = abs(intval($uid));
        $this->STOR->changeUID($uid);
    	return true;
    }

    /**
     * Attempts to "create" an IMAP mailbox, which basically means to add it's mailbox node to the index
     *
     * @param string  Friendly name of the folder
     * @param int  ID of the profile this mailbox belongs to
     * @return int ID of the mailbox on success, false on failure
     * @since 0.1.4
     */
    public function create_imapbox($folder, $profile)
    {
        $status = $this->STOR->create_folder($folder, 0, 10, ':imapbox', true, false, $profile.':');
        if ($status) {
            $this->STOR->init_folders(true, $profile);
        }
        return $status;
    }

    /**
     * Drops an IMAP mailbox from the index again, basically that means removing all indexed data
     *
     * @param int ID of the folder to remove
     * @return bool  TRUE at the moment, since this is still a placeholder
     */
    public function drop_imapbox($profile)
    {
        foreach (array_keys($this->STOR->get_imapkids($profile)) as $k) {
            $this->STOR->IDX->remove_folder($this->uid, $k, false, true);
        }
        return true;
    }

    /**
     * Renames the friendly name of an IMAP box whenever a user changes the profile name
     * @param int IDX of the folder to renam
     * @param string  New name of the folder
     * @return bool
     */
    public function rename_imapbox($idx, $newname)
    {
        return $this->STOR->rename_folder($idx, $newname);
    }

    /**
     * Returns ID or list of IDs for a certain type of system folder. Valid types in the moment
     * are: waste, sent, junk, drafts, inbox, imapbox and mailbox. If the optional profile is given,
     * then only the relevant system folder for that IMAP profile is returned. To query the local
     * system folder, specify profile 0.
     *
     * @param  int  ID of the affected user
     * @param string $type  Type of the folder, see description
     *[@param int $profile  Id of the IMAP profile, 0 for a local folder]
     * @return array  An array consisting of arrays, which hold folder ID, folder path and profile ID
     * @since 0.2.1
     */
    public function get_system_folder($type, $profile = false, $autocreate = false)
    {
        return $this->STOR->get_system_folder($type, $profile, (bool) $autocreate);
    }

    /**
     * Query some info about a given folder
     *
     * @param int $fid  ID of the folder you are interested in
     * @return array  Detailed info about the folder
     * @see indexer::get_folder_info()
     * @since 0.2.3
     */
    public function get_folder_info($fid)
    {
        $info = $this->STOR->IDX->get_folder_info($this->uid, $fid);
        $trans = $this->STOR->translate(array(0 => $info), $GLOBALS['WP_msg']);
        return $trans[0];
    }

    /**
     * Returns the profile ID from given folder ID, if applicable.
     * This method will only return a profile ID when fed with an IMAP folder.
     *
     * @param int $fid  ID of the folder to lookup info for
     * @return false|int  Profile ID on success, false otherwise
     * @since 4.0.7
     */
    public function get_profile_from_folder($fid)
    {
        $found = null;
        $info = $this->STOR->IDX->get_folder_info($this->uid, $fid);
        if (preg_match('!^(\d+)\:.*$!', $info['folder_path'], $found)) {
            return $found[1];
        }
        return false;
    }

    /**
     * Finds the folder ID a given mail resides in
     *
     * @param int $id  ID of the mail
     * @return int   Folder ID
     * @since 4.0.8
     */
    public function get_folder_from_item($id)
    {
        $info = $this->STOR->get_mail_info($id, true);
        return $info['folder_id'];
    }

    /**
     * Allows other handlers to save a mail in a desired folder within given
     * user context. Please be aware, that you will have to pass all necessary
     * mail header fields by yourself. For inserting mail into the indexer, consider
     * using parse_and_save_mail() instead.
     *
     * @see handler_email_driver::file_mail()
     * @param  array Mail data
     *[@param  resource  Open stream to read the mail data from]
     *[@param  string  Path to the source file, ONLY valid for IMAP folders as the destination!; Default: false]
     * @return  bool  true on success, false otherwise
     * @sinxe 0.0.1
     */
    public function save_item($maildata, $res = false, $from_path = false)
    {
        global $DB;

        // Quotas: Check the space left and how many messages this user might store
        $quota_size_storage = $DB->quota_get($this->uid, 'email', 'size_storage');
        if (false !== $quota_size_storage) {
            $quota_spaceleft = $this->STOR->quota_getmailsize(false);
            $quota_spaceleft = $quota_size_storage - $quota_spaceleft;
        } else {
            $quota_spaceleft = false;
        }
        $quota_number_items = $DB->quota_get($this->uid, 'email', 'number_mails');
        if (false !== $quota_number_items) {
            $quota_itemsleft = $this->STOR->quota_getmailnum(false);
            $quota_itemsleft = $quota_number_items - $quota_itemsleft;
        } else {
            $quota_itemsleft = false;
        }
        // No more items allowed to save
        if ((false !== $quota_itemsleft && $quota_itemsleft < 1)
                || (false !== $quota_spaceleft && $quota_spaceleft < 1)) {
            return false;
        }
        // End Quotas

        return $this->STOR->file_mail($maildata, $res, $from_path);
    }

    /**
     * Allows to pass a mail file or open stream to get parsed and saved. This way
     * you don't need to parse the mail yourself. This method is only capable of
     * parsing emails, no other items like SMS.
     *
     * @see handler_email_driver::file_mail()
     * @param  string  $path  file system path to the mail file,
     * @param  string  $folder_path  Path of the folder within docroot to save the mail to
     * @param  int  $folder_id  ID of the folder, either this or the folder_path MUST be given;
     *          if both are given, the ID takes precedence
     *[@param  bool  $leave  Leave the original file? Default: false]
     *[@param  string  $type  One of the possible known item types @see save_item()]
     *[@param  bool  $unread  Set to TRUE, if the mail is unread, default is FALSE]
     * @return  bool  true on success, false otherwise
     * @since 0.0.1
     */
    public function parse_and_save_mail($path = false, $f_path = false, $f_id = false, $leave = false, $type = 'mail', $unread = false)
    {
        if (!$path || !file_exists($path)) {
            return false;
        }
        if (false === $f_id && !$f_path) {
            return false;
        }
        // Read the mail structure
        $mh = fopen($path, 'r');
        list ($header, $struct) = Format_Parse_Email::parse($mh);
        $header['struct'] = serialize($struct);
        if ($f_path) {
            $header['folder_path'] = $f_path;
            $info = $this->STOR->get_folder_info($this->STOR->get_folder_id_from_path($f_path));
        } else {
            $header['folder_id'] = $f_id;
            $info = $this->STOR->get_folder_info($f_id);
        }
        $mail_size = fstat($mh);
        $header['size'] = $mail_size[7];
        $header['date_sent'] = date('Y-m-d H:i:s');
        if (isset($header['date']) && $header['date']) {
            $header['date_sent'] = date('Y-m-d H:i:s', ($header['date']) ? $header['date'] : time());
        } else {
            $header['date_sent'] = date('Y-m-d H:i:s');
        }
        $header['filed'] = false;
        $header['status'] = ($unread) ? 0 : 1;
        $header['uidl'] = basename($path);
        $header['type'] = $type;
        $header['priority'] = $header['importance'];
        $header['unseen'] = false;
        $header['cached'] = true;
        foreach (array('subject', 'from', 'to', 'cc', 'bcc') as $k) { if (!isset($header[$k])) {
            $header[$k] = ''; }
        }
        $header['attachments'] = 0;
        if (isset($header['content_type']) && isset($header['mime'])
                && !preg_match('!^text/(plain|html)!i', $header['content_type'])
                && '1.0' == trim($header['mime'])) {
            $header['attachments'] = 1;
        }
        rewind($mh);
        $state = $this->STOR->file_mail($header, $mh, $path);
        fclose($mh);
        if (!$state) {
            return $state;
        }
        if (!$leave) {
            unlink($path);
        }
        return true;
    }

    /**
     * Used to determine, which editor to use for a certain "mail",
     * which actually can also be SMS and the like
     *
     * @param int $id
     * @return string  One of 'mail','sms','ems','mms','fax','appointment','away','receipt','sysmail'
     * @since 4.0.9
     */
    public function give_mail_type($id)
    {
        return $this->STOR->get_mail_type($id);
    }

    /**
     * Returns the structure (header fields and attachment list) of a given mail;
     *
     * @param  int  ID of the mail to get the data for
     * @return  array  Array data with the structure and header information of the mail
     * @since  0.0.1
     */
    public function give_mail_struct($id)
    {
        return array
                ('structure' => $this->STOR->get_mail_structure($id)
                ,'header' => $this->STOR->get_mail_header($id)
                );
    }

    /**
     * Returns a given part from a given mail. This part is already decoded
     *
     * @param  int  $id  ID of the mail to get the mail body for
     * @param  int  $num  ID of the part to return; NULL makes this method determine the mailbody and return it
     * @param  bool  $save  TRUE if you need to pipe the orignal mail part line by line;
     *        FALSE for getting the whole mailpart at once; Default: FALSE
     * @param  bool  $infoonly  TRUE, if you are only interested in information about the
     *        given part (useful for SendTo); Default: FALSE
     * @return  string|array  The mail part, if param 3 is false; an array with the relevant MIME info,
     *        if param 3 is TRUE
     * @since 0.0.1
     */
    public function give_mail_part($id, $num = null, $save = false, $infoonly = false)
    {
        $found = $ctypname = $cdisname = null;
        $struct = $this->STOR->get_mail_structure($id);

        if (is_null($num)) {
            $userChoices = $GLOBALS['DB']->get_usr_choices($this->uid);
            $preferredPart = !empty($userChoices['core']['email_preferred_part']) ? $userChoices['core']['email_preferred_part'] : 'html';
            list ($typeToDisplay, $num) = Format_Parse_Email::determineVisibleBody($struct, $preferredPart);
            if ($typeToDisplay != 'html' && $typeToDisplay != 'enriched' && $typeToDisplay != 'text') {
                return '';
            }
        }

        $content_type = !empty($struct['body']['part_type'][$num])
                ? $struct['body']['part_type'][$num]
                : ((isset($struct['header']['content_type'])) ? $struct['header']['content_type'] : 'text/plain' );
        $encoding = !empty($struct['body']['part_encoding'][$num])
                ? $struct['body']['part_encoding'][$num]
                : ((isset($struct['header']['content_encoding'])) ? $struct['header']['content_encoding'] : '7bit' );
        $ctype_pad = !empty($struct['body']['part_detail'][$num])
                ? $struct['body']['part_detail'][$num]
                : ((isset($struct['header']['content_type_pad'])) ? $struct['header']['content_type_pad'] : '' );
        $cdispo = !empty($struct['body']['dispo_pad'][$num]) ? $struct['body']['dispo_pad'][$num] : '';
        if ($ctype_pad) {
            preg_match('!charset="?([^";]+)("|$|;)!', $ctype_pad, $found);
            preg_match('!name=("?)(.*)\1!i', $ctype_pad, $ctypname);
        } else {
            $found = $ctypname = array();
        }
        if ($cdispo) {
            preg_match('!name=("?)(.*)\1!i', $cdispo, $cdisname);
        } else {
            $cdisname = array();
        }
        $charset = isset($found[1]) ? $found[1] : 'iso-8859-1';
        $filename = isset($cdisname[2]) ? $cdisname[2] : (isset($ctypname[2]) ? $ctypname[2] : false);

        $mailinfo = $this->STOR->get_mail_info($id, true);
        if (!$infoonly) {
            if ($mailinfo['cached']) {
                $this->STOR->mail_open_stream($id, 'r');
                $this->STOR->mail_seek_stream($struct['body']['offset'][$num]);
            } else {
                $part = $struct['body']['imap_part'][$num];
            }
        }

        if ($save || $infoonly) {
            return array(
                    'content_type' => $content_type,
                    'content_id' => !empty($struct['body']['content_id'][$num]) ? $struct['body']['content_id'][$num] : null,
                    'encoding' => $encoding,
                    'charset' => $charset,
                    'filename' => $filename,
                    'is_imap' => $mailinfo['cached'] ? false : ($infoonly ? true : $part),
                    'length' => $struct['body']['length'][$num]
                    );
        }
        if ($mailinfo['cached']) {
            $mailbody = $this->STOR->mail_read_stream($struct['body']['length'][$num]);
            $this->STOR->mail_close_stream();
        } else {
            $mailbody = $this->mailpart_giveall($id, $part);
        }
        if (strtolower($encoding) == 'quoted-printable') {
            $mailbody = quoted_printable_decode(str_replace('='.CRLF, '', $mailbody));
        } elseif (strtolower($encoding) == 'base64') {
            $mailbody = base64_decode($mailbody);
        }
        // $mailbody = Format_Parse_Email::hidePgpMarkup($mailbody);

        if (strtolower($content_type) == 'text/html') {
            // Clean Up HTML
            return preg_replace(
                    array('!<script.*?>.+</script>!si', '!<iframe.*?>.*?</iframe>!si'),
                    array('', ''),
                    encode_utf8($mailbody, $charset, true)
                    );
        } elseif (strtolower($content_type) == 'text/plain') { // Charset conversion
            return encode_utf8($mailbody, $charset, true);
        } else {
            return $mailbody;
        }
    }

    /**
     * Returns the next line of a mail part, previously opened via give_mail_part() or give_mail()
     * @param  void
     * @return string | bool  The next line of the original mail (part); FALSE, if no more data available
     * @since 0.0.5
     */
    public function mailpart_giveline()
    {
        $return = $this->STOR->mail_read_stream();
        if (!$return) {
            $this->STOR->mail_close_stream();
        }
        return $return;
    }

    /**
     * Intended to get used for IMAP mails, where the combination of
     * give_mail_part() and mailpart_giveline() does not work. In case of an IMAP
     * mail give_mail_part() will return is_imap with the number of the part set
     * so you can pass this blindly here to receive the complete IMAP part.
     *
     * @param int $mail
     * @param int $part
     * @return string  The complete mail part as a string
     * @since 0.2.2
     */
    public function mailpart_giveall($mail, $part)
    {
        list($mbox, $length) = $this->STOR->get_imap_part($mail, $part);
        $mailbody = '';
        $read = 0;
        while (true) {
            if (!is_object($mbox)) {
                break;
            }
            $line = $mbox->talk_ml();
            if (false === $line) {
                break;
            }
            $read += strlen($line);
            $mailbody .= $line;
            if ($read >= $length) {
                while (false !== $mbox->talk_ml()) { /* void */ }
                $mbox->close();
                break;
            }
        }
        return $mailbody;
    }

    /**
     * Opens a filehandle to a given mail, which can be read line by line afterwards
     * @param int ID of the mail to open
     * @return bool  TRUE, if opening the mail was successful, false otherwise
     * @since 0.1.2
     */
    public function give_mail($id)
    {
        $state = $this->STOR->mail_open_stream($id, 'r');
        return $state;
    }

    /**
     * Intended for integrated services like the POP3 server - this needs the listing of the inbox first
     * @param  void
     * @return Array structure with a lot of mail info
     * @since 0.1.1
     */
    public function list_inbox()
    {
        return $this->STOR->get_mail_info(false, true, $this->STOR->get_folder_id_from_path('inbox'));
    }

    public function list_items($folders, $offset = 0, $pageSize = 100, $orderBy = 'hdate_sent', $orderDir = 'DESC')
    {
        return $this->STOR->IDX->get_mail_list($this->uid, $folders, $offset, $pageSize, $orderBy, $orderDir);
    }

    /**
     * Set the status of a mail (Read, Unread, Answered, ...) and combinations of them
     * @param    int   ID of the mail to set the status
     * @since 0.1.0
     */
    public function mail_set_status($mail = 0, $rd = null, $aw = null, $fw = null, $bn = null)
    {
    	return $this->STOR->mail_set_status($mail, $rd, $aw, $fw, $bn);
    }

    /**
     * Used to delete a mail. Mails not in the dustbin get moved there, mails in the dustbin get removed forever
     * @param int  ID of the mail to delete
     * @return bool  true or false
     * @since 0.1.2
     */
    public function mail_delete($mail = 0, $folder = false, $ouidl = false, $forced = false)
    {
        return $this->STOR->delete_mail($mail, $folder, $ouidl, $forced);
    }

    /**
     * Returns a list of existing folders for a given user
     * @param  bool  If set to true, only local folders will be returned (no IMAP or others)
     * @return  array  Folder list with various meta data
     * @since 0.0.9
     */
    public function give_folderlist($local_only = false)
    {
    	$this->STOR->init_folders(false);

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

        $folders = $this->STOR->read_folders_flat(0, $local_only);
        if (empty($folders)) {
            return array();
        }

    	$return = array();
    	foreach ($folders as $k => $v) {
            $v['is_junk'] = $v['is_trash'] = 0;
            $stale = (isset($v['stale']) && $v['stale']) ? 1 : 0;
            $secure = (isset($v['secure']) && $v['secure']) ? 1 : 0;
            // Find special icons for folders
            switch ($v['icon']) {
                case ':inbox':     $v['big_icon'] = $icon_path.'inbox_big.gif';     $v['icon'] = $icon_path.'inbox.png'; break;
                case ':outbox':    $v['big_icon'] = $icon_path.'outbox_big.gif';    $v['icon'] = $icon_path.'outbox.png'; break;
                case ':archive':   $v['big_icon'] = $icon_path.'archive_big.gif';   $v['icon'] = $icon_path.'archive.png'; break;
                case ':sent':      $v['big_icon'] = $icon_path.'sent_big.gif';      $v['icon'] = $icon_path.'sent.png'; break;
                case ':waste':     $v['big_icon'] = $icon_path.'waste_big.gif';     $v['icon'] = $icon_path.'waste.png'; $v['is_trash'] = 1; break;
                case ':junk':      $v['big_icon'] = $icon_path.'junk_big.gif';      $v['icon'] = $icon_path.'junk.png'; $v['is_junk'] = 1; break;
                case ':drafts':    $v['big_icon'] = $icon_path.'drafts_big.gif';    $v['icon'] = $icon_path.'drafts.png'; break;
                case ':templates': $v['big_icon'] = $icon_path.'templates_big.gif'; $v['icon'] = $icon_path.'templates.png'; break;
                case ':calendar':  $v['big_icon'] = $icon_path.'calendar_big.gif';  $v['icon'] = $icon_path.'calendar.png';  break;
                case ':contacts':  $v['big_icon'] = $icon_path.'contacts_big.gif';  $v['icon'] = $icon_path.'contacts.png'; break;
                case ':notes':     $v['big_icon'] = $icon_path.'notes_big.gif';     $v['icon'] = $icon_path.'notes.png'; break;
                case ':tasks':     $v['big_icon'] = $icon_path.'tasks_big.gif';     $v['icon'] = $icon_path.'tasks.png'; break;
                case ':files':     $v['big_icon'] = $icon_path.'files_big.gif';     $v['icon'] = $icon_path.'files.png'; break;
                case ':rss':       $v['big_icon'] = $icon_path.'rss_big.gif';       $v['icon'] = $icon_path.'rss.png'; break;
                case ':virtual':   $v['big_icon'] = $icon_path.'virtualfolder_big.gif'; $v['icon'] = $icon_path.'virtualfolder.png'; break;
                case ':mailbox':   $v['big_icon'] = $icon_path.'mailbox_big.gif';   $v['icon'] = $icon_path.'mailbox.png';
                    $v['foldername'] = $WP_msg['mailbox'].' '.$myUsername;
                    break;
                case ':imapbox':   $v['big_icon'] = $icon_path.'imapbox'.($stale ? '_stale' : ($secure ? '_secure' : '')).'_big.gif';
                    $v['icon'] = $icon_path.'imapbox'.($stale ? '_stale' : ($secure ? '_secure' : '')).'.png';
                    break;
                case ':sharedbox': $v['big_icon'] = $icon_path.'sharedbox_big.gif'; $v['icon'] = $icon_path.'sharedbox.png';
                    $v['foldername'] = $WP_msg['SharedFolders'];
                    break;
            }
            // Shared folders
            if ($v['type'] == 2) {
                $v['ctx_share'] = $v['ctx_subfolder'] = 0;
            }
            if (!file_exists($v['icon'])) {
                $v['icon'] = $icon_path.'folder_def.png';
            }
            if (!isset($v['big_icon']) || !file_exists($v['big_icon'])) {
                $v['big_icon'] = $icon_path.'folder_def_big.gif';
            }
            $return[$k] = $v;
    	}
    	return $return;
    }

    public function selectfile_itemlist($fid, $offset = 0, $amount = 100, $orderby = 'hdate_sent', $orderdir = 'DESC')
    {
        $fid = intval($fid);
        global $WP_msg;
        $return = array();

        $groesse = $this->STOR->init_mails($fid, $offset+1, $amount, $orderby, $orderdir);
        if ($groesse['mails'] == 0) {
            return $return;
        }

        $forEnd = $offset + $groesse['mails'];
        for ($i = $offset; $i <= $forEnd; $i++) {
            $workmail = $this->STOR->get_mail_info($i);
            if (empty($workmail)) {
                continue;
            }

            $mailcolour = (!is_null($workmail['colour']) && $workmail['colour'] != '') ? $workmail['colour'] : '';
            $status = isset($workmail['status']) && $workmail['status'] ? 1 : 0;
            $answered = isset($workmail['answered']) && $workmail['answered'] ? 1 : 0;
            $forwarded = isset($workmail['forwarded']) && $workmail['forwarded'] ? 1 : 0;
            $bounced = isset($workmail['bounced']) && $workmail['bounced'] ? 1 : 0;
            // $itemtype = isset($workmail['type']) ? $workmail['type'] : 'mail';

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
                        case  100: case  101: $statusicon = 'mail_unreadanswered';    $statustext = $WP_msg['marked_answered'];  break;
                        case  110: case  111: $statusicon = 'mail_unreadforwardedanswered'; $statustext = $WP_msg['marked_forwarded']; break;
                        case   10: case   11: $statusicon = 'mail_unreadforwarded';   $statustext = $WP_msg['marked_forwarded']; break;
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
                // $itemtype = isset($workmail['type']) ? $workmail['type'] : 'mail';
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
                        'subj' => array('v' => htmlspecialchars($workmail['subject'], ENT_QUOTES, 'utf-8'), 't' => $workmail['subject'], 'css' => $css),
                        'from' => array('v' => htmlspecialchars($from[1], ENT_QUOTES, 'utf-8'), 't' => $from[2], 'css' => $css),
                        'date' => array('v' => htmlspecialchars($short_datum, ENT_QUOTES, 'utf-8'), 't' => $datum, 'css' => $css)
                        );
                }
            $return['emails']['rows'] = $rows;
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