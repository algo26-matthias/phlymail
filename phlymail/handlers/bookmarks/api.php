<?php
/**
 * Offering API calls for other handlers or the main application
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Bookmarks
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2015-03-30 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_bookmarks_api
{
    private $DB, $uid, $_PM_, $errortext;

    /**
     * Constructor method, this special constructor also attempts to create the required
     * docroot of the email storage for the given user
     *
     * @param  array reference  public settings structure
     * @param  int  ID of the user to perform the operation for
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid)
    {
        $this->_PM_ = $_PM_;
        $this->uid = $uid;
        $this->DB = new handler_bookmarks_driver($uid);

        $WP_msg = &$GLOBALS['WP_msg'];
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir(__DIR__);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) {
                continue;
            }
            if ('..' == $f) {
                continue;
            }
            if (preg_match('!^lang\.'.$GLOBALS['WP_msg']['language'].'(.*)\.php$!', $f)) {
                require(__DIR__.DIRECTORY_SEPARATOR.$f);
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
            return array('foldername' => $WP_msg['MyBookmarks']);
        } else {
            $return = $this->DB->get_folder($fid, false);
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
        $WP_msg = &$this->WP_msg;
        $return = array('root' => false);
        foreach ($this->DB->get_folderlist(true) as $k => $v) {
            $return[$k] = array
                    ('folder_path' => $k
                    ,'icon' => $this->_PM_['path']['theme'].'/icons/'.(($v['owner'] == 0) ? 'contactsfolder_global' : 'folder_def').'.png'
                    ,'big_icon' => $this->_PM_['path']['theme'].'/icons/'.(($v['owner'] == 0) ? 'contactsfolder_global' : 'folder_def').'_big.gif'
                    ,'foldername' => $v['name']
                    ,'type' => 2
                    ,'childof' => 'root'
                    ,'has_folders' => 0
                    ,'has_items' => 1
                    ,'level' => $v['level']+1
                    ,'unread' => 0
                    ,'unseen' => 0
                    ,'stale' => 0
                    ,'visible' => 1
            );
        }
        $return['root'] = array
                ('folder_path' => 0
                ,'icon' => $this->_PM_['path']['theme'].'/icons/bookmarks.png'
                ,'big_icon' => $this->_PM_['path']['theme'].'/icons/bookmarks_big.gif'
                ,'foldername' => $WP_msg['MainFoldername']
                ,'type' => 2
                ,'subdirs' => (!empty($return)) ? 1 : 0
                ,'has_folders' => (!empty($return)) ? 1 : 0
                ,'has_items' => 1
                ,'childof' => 0
                ,'level' => 0
                );
        return $return;
    }

    public function selectfile_itemlist($fid, $offset = 0, $amount = 100, $orderby = 'b.name', $orderdir = 'ASC')
    {
        $fid = intval($fid);
        $return = array();
        foreach ($this->DB->get_index(1, $fid > 0 ? $fid : null, null, null, $amount, $offset, $orderby, $orderdir) as $item) {
            $return[] = array
                    ('id' => $item['id']
                    ,'i32' => $this->_PM_['path']['frontend'].'/filetypes/32/text_uri-list.png'
                    ,'mime' => 'text/uri-list'
                    ,'l1' => $item['name']
                    ,'l2' => $item['url']
                    );
        }
        return $return;
    }
}
