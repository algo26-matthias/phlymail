<?php
/**
 * Offering API calls for other handlers or the main application
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Contacts
 * @copyright 2004-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.1 2016-06-10
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_contacts_api
{
    private $DB = false;
    private $errortext = false;

    /**
     * Constructor method, this special constructor also attempts to create the required
     * docroot of the email storage for the given user
     *
     * @param  array $_PM_ reference  public settings structure
     * @param  int $uid  ID of the user to perform the operation for
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid)
    {
        $this->_PM_ = $_PM_;
        $this->uid = $uid;
        $this->DB = new handler_contacts_driver($uid);

        $WP_msg = &$GLOBALS['WP_msg'];
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir(__DIR__);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) continue;
            if ('..' == $f) continue;
            if (preg_match('!^lang\.'.$GLOBALS['WP_msg']['language'].'(.*)\.php$!', $f)) {
                require(__DIR__.'/'.$f);
                break;
            }
        }
        closedir($d);
        $this->WP_msg = $WP_msg;
    }

    /**
     * Returns errors which happened
     * @param void
     * @return string error message(s)
     * @since 0.0.1
     */
    public function get_errors() { return $this->errortext; }

    /**
     * @see contacts_driver::search_contact()
     */
    public function search_contact($search, $what = 'email', $checkonly = false)
    {
        $inc_globals = (isset($this->_PM_['core']['contacts_nopublics']) && $this->_PM_['core']['contacts_nopublics']);
        return $this->DB->search_contact($search, $what, $checkonly, $inc_globals);
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
        if ($fid == 'root') {
            $WP_msg = &$this->WP_msg;
            return array('foldername' => $WP_msg['MyContacts']);
        } else {
            $return = $this->DB->get_group($fid, false);
            $return['foldername'] = $return['name'];
            return $return;
        }
    }

    /**
     * Returns a list of existing folders for a given user
     * @param  bool  If set to true, only local folders will be returned (no LDAP or others)
     * @return  array  Folder list with various meta data
     * @since 0.0.2
     */
    public function give_folderlist($local_only = false)
    {
        global $DB;

        $WP_msg = &$this->WP_msg;
        $return = array('root' => false);
        foreach ($this->DB->get_grouplist(!$local_only) as $v) {
            $return[$v['gid']] = array
                    ('folder_path' => $v['gid']
                    ,'icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'.png'
                    ,'big_icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'_big.gif'
                    ,'foldername' => $v['name']
                    ,'type' => 2
                    ,'childof' => 'root'
                    ,'has_folders' => 0
                    ,'has_items' => 1
                    ,'level' => 1
                    ,'unread' => 0
                    ,'unseen' => 0
                    ,'stale' => 0
                    ,'visible' => 1
            );
        }
        $return['root'] = array
                ('folder_path' => 0
                ,'icon' => $this->_PM_['path']['theme'].'/icons/contacts.png'
                ,'big_icon' => $this->_PM_['path']['theme'].'/icons/contacts_big.gif'
                ,'foldername' => $WP_msg['MyContacts']
                ,'type' => 2
                ,'subdirs' => (!empty($return)) ? 1 : 0
                ,'has_folders' => (!empty($return)) ? 1 : 0
                ,'has_items' => 1
                ,'childof' => 0
                ,'level' => 0
                );

        // Are there shared folders?
        $myGroups = $myShares = array();
        if (!empty($DB->features['groups'])) {
            $myGroups = $DB->get_usergrouplist($_SESSION['phM_uid'], true);
        }
        try {
            $DCS = new DB_Controller_Share();
            $myShares = $DCS->getMySharedFolders($_SESSION['phM_uid'], 'contacts', $myGroups);
        } catch (Exception $e) {
            // void
        }

        if (!empty($myShares['contacts'])) {
            $return['shareroot'] = array(
                    'path' => 0,
                    'foldername' => $WP_msg['SharedContacts'],
                    'type' => 2,
                    'icon' => $this->_PM_['path']['theme'].'/icons/sharedbox.png',
                    'big_icon' => $this->_PM_['path']['theme'].'/icons/sharedbox_big.gif',
                    'colour' => '',
                    'subdirs' => 0, 'has_folders' => 1, 'has_items' => 0, 'childof' => 0, 'level' => 0,
                    'ctx' => 0, 'ctx_props' => 0, 'ctx_resync' => 0, 'ctx_subfolder' => 0,
                    'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0,
                    'is_collapsed' => !empty($this->_PM_['foldercollapses']['contacts_shareroot']) ? 1 : 0
            );
            foreach (array_keys($myShares['contacts']) as $sharedFolder) {
                $v = $this->DB->get_group($sharedFolder, false, true);
                if (!isset($return['user'.$v['owner']])) {
                    $return['user'.$v['owner']] = array(
                            'path' => 'nil',
                            'foldername' => $v['username'],
                            'type' => 2,
                            'icon' => $this->_PM_['path']['theme'].'/icons/contacts.png',
                            'big_icon' => $this->_PM_['path']['theme'].'/icons/contacts_big.gif',
                            'colour' => '',
                            'has_folders' => 1,
                            'has_items' => 0,
                            'level' => 1,
                            'ctx' => 0, 'ctx_props' => 0, 'ctx_resync' => 0, 'ctx_subfolder' => 0,
                            'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0,
                            'is_collapsed' => !empty($this->_PM_['foldercollapses']['contacts_user'.$v['owner']]) ? 1 : 0
                    );
                    $childof['shareroot'][] = 'user'.$v['owner'];
                }

                $return[$sharedFolder] = array(
                        'path' => $sharedFolder,
                        'foldername' => $v['name'],
                        'type' => 2,
                        'icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'.png',
                        'big_icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'_big.gif',
                        'colour' => '',
                        'subdirs' => 0, 'childof' => 'user'.$v['owner'], 'has_folders' => 0, 'has_items' => 1, 'level' => 2,
                        'ctx' => 1, 'ctx_props' => 1, 'ctx_resync' => 0, 'ctx_subfolder' => 0,
                        'ctx_move' => 0, 'ctx_rename' => 0, 'ctx_dele' => 0, 'ctx_share' => 0,
                        'is_collapsed' => !empty($this->_PM_['foldercollapses']['contacts_'.$sharedFolder]) ? 1 : 0
                );
            }
        }

        return $return;
    }

    public function give_itemlist($fid)
    {

    }

    public function selectfile_itemlist($fid, $offset = 0, $amount = 100, $orderby = 'a.lastname', $orderdir = 'ASC')
    {
        $fid = intval($fid);
        $return = array();
        foreach ($this->DB->get_adridx(3, $fid > 0 ? $fid : null, null, null, $amount, $offset, $orderby, $orderdir) as $item) {
            $item['line1'] = ($item['title'] ? $item['title'].' ' : '')
                    .$item['firstname']
                    .($item['thirdname'] ? ' '.$item['thirdname'] : '')
                    .($item['lastname'] ? ' '.$item['lastname'] : '');
            if (!strlen(trim($item['line1'])) && $item['nick']) $item['line1'] = $item['nick'];
            $item['line2'] = '';
            if ($item['company']) {
                $item['line2'] = $item['company'];
                if ($item['comp_street'] || $item['comp_location']) {
                    $item['line2'] .= ', '.$item['comp_street'].', '.$item['comp_location'];
                } elseif ($item['email2']) {
                    $item['line2'] .= ', '.$item['email2'];
                }
            } else {
                $item['line2'] = ($item['street'] || $item['location'])
                        ? $item['street'].', '.$item['location']
                        : ($item['email1'] ? $item['email1'] : '');
            }
            if (empty($item['line2'])) {
                if ($item['cellular']) {
                    $item['line2'] = $item['cellular'];
                } elseif ($item['tel_business']) {
                    $item['line2'] = $item['tel_business'];
                } elseif ($item['tel_private']) {
                    $item['line2'] = $item['tel_private'];
                }
            }
            $return[] = array
                    ('id' => $item['aid']
                    ,'i32' => $this->_PM_['path']['frontend'].'/filetypes/32/text_vcard.png'
                    ,'mime' => 'text/vcard'
                    ,'l1' => str_replace('  ', ' ', $item['line1'])
                    ,'l2' => str_replace(array('  ', ', , '), array(' ', ', '), $item['line2'])
                    );
        }
        return $return;
    }

    /**
     * Inits a SendTo handshake as the initiator of a SendTo. This method is called
     * by the receiving handler to get some info about the mail part it will receive.
     * This info usually is displayed to the user to allow some dedicated action by him.
     *
     * @param int $item  ID of the item you wish to address
     * @since 4.0.6
     */
    public function sendto_fileinfo($item)
    {
        $info = $this->DB->get_contact($item, 3);
        if (false === $info || empty($info)) return false;

        $_PM_ = &$this->_PM_;
        $tmpName = uniqid(time().'.');
        $this->sendto_tempfile = $_PM_['path']['temp'].'/'.$tmpName;
        $PHM_ADB_EX_DO = 'export';
        $PHM_ADB_EX_ENTRY = intval($item);
        $PHM_ADB_EX_FORMAT = 'VCF';
        $PHM_ADB_EX_TYPE = 'all';
        $PHM_ADB_EX_PUTTOFILE = $this->sendto_tempfile; // Will put VCF file as attachment into FS
        require(__DIR__.'/exchange.php');

        return array('content_type' => 'text/vcard', 'encoding' => '8bit'
                ,'charset' => 'UTF-8', 'filename' => $info['firstname'].' '.$info['lastname'].'.vcf'
                ,'length' => filesize($this->sendto_tempfile));
    }

    /**
     * SendTo handshake part 2: The receiver now tells us to initialise the sending process.
     *
     * @param int $item ID of the item we wish to read
     * @return bool TRUE on success, FALSE on failure to open the stream for reading from
     * @since 4.0.6
     */
    public function sendto_sendinit($item)
    {
        if (!isset($this->sendto_tempfile) || !$this->sendto_tempfile) {
            $this->sendto_fileinfo($item);
        }
        $this->sendto_filehandle = fopen($this->sendto_tempfile, 'r');
        return true;
    }

    /**
     * Extending the inital SendTo protocol by methods for line or block wise reading.
     *
     *[@param int $len Number of bytes to read at once; Default: 0, which will return max. 1024B]
     * @return string
     * @since 4.0.6
     */
    public function sendto_sendline($len = 0)
    {
        if (feof($this->sendto_filehandle)) return false;
        $line = ($len > 0) ? fgets($this->sendto_filehandle, $len) : fgets($this->sendto_filehandle);
        return (strlen($line)) ? $line : false;
    }

    /**
     * Closes the stream to the sent file again
     *
     * @param void
     * @return true
     * @since 4.0.6
     */
    public function sendto_finish()
    {
        fclose($this->sendto_filehandle);
        unlink($this->sendto_tempfile);
        unset($this->sendto_tempfile, $this->sendto_filehandle);
        return true;
    }
}
