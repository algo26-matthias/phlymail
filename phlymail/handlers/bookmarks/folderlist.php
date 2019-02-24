<?php
/**
 * Snap in module for the folder browser shown on copy / move.
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage  Handler Bookmarks
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.3 2015-03-30 
 */
class handler_bookmarks_folderlist
{
    public function __construct(&$_PM_, $mode)
    {
        $this->cDB = new handler_bookmarks_driver($_SESSION['phM_uid']);
        $this->_PM_ = $_PM_;
    }

    public function get()
    {
        if (file_exists(__DIR__.'/lang.'.$GLOBALS['WP_msg']['language'].'.php')) {
            require(__DIR__.'/lang.'.$GLOBALS['WP_msg']['language'].'.php');
        } else {
            require(__DIR__.'/lang.de.php');
        }
        $this->cDB->get_folderlist();
        $this->fidx = $this->cDB->return_fidx();
        return array(0 => array
                ('path' => 0
                ,'icon' => $this->_PM_['path']['theme'].'/icons/bookmarks.png'
                ,'foldername' => $WP_msg['MyBookmarks']
                ,'type' => 2
                ,'has_folders' => 1
                ,'has_items' => 1
                ,'subdirs' => $this->read_folders()
                ));
    }

    private function read_folders($parent_id = 0, $path = '')
    {
        $return = false;
        // Not valid parent ID
        if (!isset($this->fidx[$parent_id])) {
            return false;
        }

        foreach ($this->fidx[$parent_id] as $k => $v) {
            $return[$k] = array
                    ('path' => $k
                    ,'icon' => isset($v['icon']) ? $v['icon'] : ''
                    ,'foldername' => $v['name']
                    ,'type' => 2, 'has_folders' => 1, 'has_items' => 1
                    );
            if (isset($this->fidx[$k])) {
                $return[$k]['subdirs'] = $this->read_folders($k);
            } else {
                $return[$k]['subdirs'] = false;
            }
        }
        return $return;
    }
}
