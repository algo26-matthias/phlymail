<?php
/**
 * Offering API calls for interoperating with other handlers
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Calendar
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.7 2015-03-30
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

class handler_calendar_api
{
    /**
     * Constructor method
     *
     * @param  array reference  public settings structure
     * @param  int  ID of the user to perform the operation for
     * @return  boolean  true on success, false otherwise
     * @since 0.0.1
     */
    public function __construct(&$_PM_, $uid, $principalID = 0)
    {
        $this->_PM_ = $_PM_;
        $this->uid = (int) $uid;
        $this->principalID = (int) $principalID;

        $this->cDB = new handler_calendar_driver($this->uid, $this->principalID);

        $WP_msg = &$GLOBALS['WP_msg'];
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir(__DIR__);
        while (false !== ($f = readdir($d))) {
            if ('.' == $f)
                { continue;
            }
            if ('..' == $f) {
                continue;
            }
            if (preg_match('!^lang\.'.$GLOBALS['WP_msg']['language'].'(.*)\.php$!', $f)) {
                require(__DIR__.'/'.$f);
                break;
            }
        }
        closedir($d);
        $this->WP_msg = $WP_msg;
    }

    /**
     * Adds an event to the database
     *
     * @param array $data
     * @return mixed
     */
    public function add_event($data)
    {
        return $this->cDB->add_event($data);
    }

    public function get_event($id)
    {
        return $this->cDB->get_event($id);
    }

    public function get_task($id)
    {
        return $this->cDB->get_task($id);
    }

    /**
     * Adds an event to the database
     *
     * @param array $data
     * @return mixed
     */
    public function update_event($data)
    {
        return $this->cDB->update_event($data);
    }

    public function delete_event($id)
    {
        return $this->cDB->delete_event($id);
    }

    /**
     * Returns all known event types as indexed array, where the key is the ID of the type and the
     * value holds a short string enumerating the type
     *
     * @return array
     */
    public function get_event_types()
    {
        return $this->cDB->get_event_types();
    }

    /**
     * Returns all known event status types as indexed array, where the key is the ID of the type and the
     * value holds a short string enumerating the type
     *
     * @return array
     */
    public function get_event_status()
    {
        return $this->cDB->get_event_status();
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
            return array('foldername' => $WP_msg['CalAllEvents']);
        } else {
            $return = $this->cDB->get_group($fid);
            $return['foldername'] = $return['name'];
            return $return;
        }
    }

    /**
     * Returns a list of existing folders for a given user
     * @param  bool  $local_only  If set to true, only local folders will be returned (no LDAP or others)
     * @return  array  Folder list with various meta data
     * @since 0.2.6
     */
    public function give_folderlist($local_only = false)
    {
        $WP_msg = &$this->WP_msg;
        $return = array('root' => false);
        foreach ($this->cDB->get_grouplist(true) as $v) {
            $return[$v['gid']] = array
                    ('folder_path' => $v['gid']
                    ,'icon' => $this->_PM_['path']['theme'].'/icons/calendar.png'
                    ,'big_icon' => $this->_PM_['path']['theme'].'/icons/calendar_big.gif'
                    ,'foldername' => $v['name']
                    ,'path_canon' => '/'.$v['name']
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
                ,'icon' => $this->_PM_['path']['theme'].'/icons/calendar.png'
                ,'big_icon' => $this->_PM_['path']['theme'].'/icons/calendar_big.gif'
                ,'foldername' => $WP_msg['CalAllEvents']
                ,'path_canon' => '/'
                ,'type' => 2
                ,'subdirs' => (!empty($return)) ? 1 : 0
                ,'has_folders' => (!empty($return)) ? 1 : 0
                ,'has_items' => 1
                ,'childof' => 0
                ,'level' => 0
                );
        return $return;
    }

    /**
     * Takes a path and tries to find out, whether the referenced item is a dir
     * or a file.
     *
     * @param string $path  Path to parse
     * @param boolean  $ext  Extended mode, which returns the item, not just the type
     * @return array|'f'|'d'|false  F for a file, d for a dir, false otherwise
     */
    public function resolve_path($path, $ext = false)
    {
        $parent = dirname($path);
        $me     = basename($path);
        $hit    = false;
        foreach ($this->give_folderlist() as $folder) {
            if ($folder['path_canon'] == $path || 'folder_'.$folder['folder_path'] == $path) {
                if (!$ext) {
                    return 'd';
                }
                return array('type' => 'd', 'item' => $folder);
            }
            if ($folder['path_canon'] == $parent || 'folder_'.$folder['folder_path'] == $parent) {
                if ($me == 'folder.ics') {
                    if (!$ext) {
                        return 'f';
                    }
                    return array
                            ('type' => 'f'
                            ,'item' => array
                                    ('id' => 'd'.$folder['folder_path']
                                    ,'friendly_name' => 'folder.ics'
                                    ,'uuid' => null
                                    ,'size' => null
                                    ,'type' => 'text/calendar'
                                    ,'ctime' => null
                                    ,'mtime' => null
                                    )
                            );
                }
                $hit = $folder;
            }
        }
        if ($hit) {
            foreach ($this->give_itemlist($hit['folder_path']) as $file) {
                if ($me == $file['friendly_name']) {
                    if (!$ext) {
                        return 'f';
                    }
                    return array('type' => 'f', 'item' => $file);
                }
            }
        }
        return false;
    }

    public function give_itemlist($fid = null, $path = null)
    {
        $return = array();
        $folders = $this->give_folderlist();
        if (!is_null($path)) {
            foreach ($folders as $folder) {
                if ($folder['path_canon'] == $path) {
                    $fid = $folder['folder_path'];
                    break;
                }
                if ($folder['path_canon'].'/' == $path) {
                    $fid = $folder['folder_path'];
                    break;
                }
            }
            if (is_null($fid)) {
                return $return;
            }
        }
        foreach ($folders as $folder) {
            if (!is_null($fid) && $fid == $folder['folder_path']) {
                $return[] = array
                        ('id' => 'd'.$folder['folder_path']
                        ,'friendly_name' => 'folder.ics'
                        ,'uuid' => null
                        ,'size' => null
                        ,'type' => 'text/calendar'
                        ,'ctime' => null
                        ,'mtime' => null
                        );
                break;
            }
        }

        foreach ($this->cDB->get_eventlist($fid, true) as $item) {
            $return[] = array
                    ('id' => 'e'.$item['id']
                    ,'friendly_name' => $item['uuid'].'.ics'
                    ,'uuid' => $item['uuid']
                    ,'size' => null
                    ,'type' => 'text/calendar'
                    ,'ctime' => null
                    ,'mtime' => !empty($item['mtime']) ? $item['mtime'] : null
                    );
        }
        foreach ($this->cDB->get_tasklist($fid, true) as $item) {
            $return[] = array
                    ('id' => 't'.$item['id']
                    ,'friendly_name' => $item['uuid'].'.ics'
                    ,'uuid' => $item['uuid']
                    ,'size' => null
                    ,'type' => 'text/calendar'
                    ,'ctime' => null
                    ,'mtime' => !empty($item['mtime']) ? $item['mtime'] : null
                    );
        }
        return $return;
    }

    public function selectfile_itemlist($fid, $offset = null, $amount = 100, $orderby = 'starts', $orderdir = 'ASC')
    {
        $WP_msg = &$this->WP_msg;
        $return = array();
        if (is_null($offset)) {
            $offset = 0;
            $pattern = '@@upcoming@@';
        } else {
            $pattern = null;
        }
        foreach ($this->cDB->get_eventlist($fid, true, $pattern, $amount, $offset, $orderby, $orderdir) as $item) {
            $item['start'] = strtotime($item['starts']);
            $item['end'] = strtotime($item['ends']);
            $item['starts'] = date(date('Y') == date('Y', $item['start']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['start']);
            $item['ends'] = date(date('Y') == date('Y', $item['end']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['end']);
            $return[] = array
                    ('id' => $item['id']
                    ,'i32' => $this->_PM_['path']['frontend'].'/filetypes/32/text_calendar.png'
                    ,'mime' => 'text/calendar'
                    ,'l1' => $item['title']
                    ,'l2' => $item['starts'].' - '.$item['ends']
                    );
        }
        return $return;
    }

    /**
     * Returns data of boyes for the pinboard
     *
     *[@param string $box  Name of the box; Default: all boxes]
     * @return array  Data fo all boxes or just the specified one's rows
     */
    public function pinboard_boxes($box = null)
    {
        $WP_msg = &$this->WP_msg;
        // For a correct translation we unfortunately have to read in a messages file
        $d = opendir($this->_PM_['path']['handler'].'/calendar');
        while (false !== ($f = readdir($d))) {
            if ('.' == $f) {
                continue;
            }
            if ('..' == $f) {
                continue;
            }
            if (preg_match('!^lang\.'.$GLOBALS['WP_msg']['language'].'(.*)\.php$!', $f)) {
                require($this->_PM_['path']['handler'].'/calendar/'.$f);
                break;
            }
        }
        $return = array();
        if (is_null($box) || $box == 'events') {
            $return['events'] = array
                    ('headline' => $WP_msg['PinboardHeadEvents']
                    ,'icon' => 'calendar.png'
                    ,'action' => 'calendar_pinboard_opener'
                    ,'cols' => array
                            ('rem' => array('w' => 16, 'a' => 'l')
                            ,'rep' => array('w' => 16, 'a' => 'l')
                            ,'starts' => array('w' => 170, 'a' => 'l')
                            ,'title' => array('w' => '', 'a' => 'l')
                            )
                    );
            $rows = array();
            foreach ($this->cDB->get_eventlist(0, true, '@@upcoming@@', 10) as $item) {
                $item['start'] = strtotime($item['starts']);
                $item['end'] = strtotime($item['ends']);
                $item['starts'] = date(date('Y') == date('Y', $item['start']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['start']);
                $item['ends'] = date(date('Y') == date('Y', $item['end']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['end']);
                $rows[] = array(
                        'id' => $item['id'],
                        'rem' => array(
                                'v' => ($item['reminders'] > 0) ? '<img src="'.$this->_PM_['path']['theme'].'/icons/cal_alarm.gif" alt="" />' : '',
                                't' => ''
                                ),
                        'rep' => array(
                                'v' => ($item['repetitions'] > 0) ? '<img src="'.$this->_PM_['path']['theme'].'/icons/cal_repeating.gif" alt="" />' : '',
                                't' => ''
                                ),
                        'starts' => array('v' => $item['starts'].' - '.$item['ends'], 't' => $item['starts'].' - '.$item['ends']),
                        'title' => array('v' => $item['title'], 't' => $item['title'])
                        );
            }
            $return['events']['rows'] = $rows;
        }
        if (is_null($box) || $box == 'tasks') {
            $return['tasks'] = array
                    ('headline' => $WP_msg['PinboardHeadTasks']
                    ,'icon' => 'tasks.png'
                    ,'action' => 'calendar_pinboard_opener'
                    ,'cols' => array
                            ('imp' => array('w' => 16, 'a' => 'l')
                            ,'rem' => array('w' => 16, 'a' => 'l')
                            ,'ends' => array('w' => 170, 'a' => 'l')
                            ,'title' => array('w' => '', 'a' => 'l')
                            )
                    );
            $rows = array();
            foreach ($this->cDB->get_tasklist(0, '@@upcoming@@', '', 10, 0, 'starts') as $item) {
                if (is_null($item['start'])) {
                    $item['starts'] = '';
                } else {
                    $item['start'] = strtotime($item['starts']);
                    $item['starts'] = date(date('Y') == date('Y', $item['start']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['start']);
                }
                if (is_null($item['end'])) {
                    $item['ends'] = '';
                } else {
                    $item['end'] = strtotime($item['ends']);
                    $item['ends'] = date(date('Y') == date('Y', $item['end']) ? $WP_msg['dateformat_new'] : $WP_msg['dateformat_old'], $item['end']);
                }
                switch ($item['importance']) {
                    case 1: case 2: $imp = 'veryhigh'; break;
                    case 3: case 4: $imp = 'high'; break;
                    case 5:         $imp = 'middle'; break;
                    case 6: case 7: $imp = 'low'; break;
                    case 8: case 9: $imp = 'verylow'; break;
                    default: $imp = false;
                }
                $rows[$item['id']] = array
                        ('rem' => array
                                ('v' => !empty($item['reminders']) ? '<img src="'.$this->_PM_['path']['theme'].'/icons/cal_alarm.gif" alt="" />' : ''
                                ,'t' => ''
                                )
                        ,'imp' => array
                                ('v' => $imp ? '<img src="'.$this->_PM_['path']['theme'].'/icons/task_imp_'.$imp.'.png" alt="" />' : ''
                                ,'t' => ''
                                )
                        ,'ends' => array('v' => $item['starts'].' - '.$item['ends'], 't' => $item['starts'].' - '.$item['ends'])
                        ,'title' => array('v' => $item['title'], 't' => $item['title'])
                        );
            }
            $return['tasks']['rows'] = $rows;
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
        return ($this->cDB->archive_events($fid, $age)
                && $this->cDB->archive_tasks($fid, $age));
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
        return ($this->cDB->expire_events($fid, $age)
                && $this->cDB->expire_tasks($fid, $age));
    }

    /**
     * Inits a SendTo handshake as the initiator of a SendTo. This method is called
     * by the receiving handler to get some info about the mail part it will receive.
     * This info usually is displayed to the user to allow some dedicated action by him.
     *
     * @param int $item  ID of the item you wish to address
     * @since 0.2.9
     */
    public function sendto_fileinfo($item)
    {
        $WP_msg = &$this->WP_msg;
        $info = $this->get_event($item);
        if (false === $info || empty($info)) {
            return false;
        }

        $Acnt = new DB_Controller_Account();
        $_PM_ = &$this->_PM_;
        $tmpName = uniqid(time().'.');
        $this->sendto_tempfile = $_PM_['path']['temp'].'/'.$tmpName;
        $PHM_CAL_EX_DO = 'export';
        $PHM_CAL_EX_NOATTENDEES = true;
        $PHM_CAL_EX_FORMAT = 'ICS';
        $PHM_CAL_EX_ORGANIZER = $Acnt->getDefaultEmail($_SESSION['phM_uid'], $this->_PM_);
        $PHM_CAL_EX_EVENT = intval($item);
        $PHM_CAL_EX_PUTTOFILE = $this->sendto_tempfile; // Will put ICS file as attachment into FS
        if (!isset($GLOBALS['eventTypes'])) {
            $GLOBALS['eventTypes'] = $this->cDB->get_event_types();
        }
        if (!isset($GLOBALS['eventStatus'])) {
            $GLOBALS['eventStatus'] = $this->cDB->get_event_status();
        }
        require(__DIR__.'/exchange.php');
        return array('content_type' => 'text/calendar', 'encoding' => '8bit'
                ,'charset' => 'UTF-8', 'filename' => date('Ymd').' - '.$info['title'].'.ics'
                ,'length' => filesize($this->sendto_tempfile));
    }

    /**
     * SendTo handshake part 2: The receiver now tells us to initialise the sending process.
     *
     * @param int $item ID of the item we wish to read
     * @return bool TRUE on success, FALSE on failure to open the stream for reading from
     * @since 0.2.9
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
     * @since 0.2.9
     */
    public function sendto_sendline($len = 0)
    {
        if (feof($this->sendto_filehandle)) {
            return false;
        }
        $line = ($len > 0) ? fgets($this->sendto_filehandle, $len) : fgets($this->sendto_filehandle);
        return (strlen($line)) ? $line : false;
    }

    /**
     * Closes the stream to the sent file again
     *
     * @param void
     * @return true
     * @since 0.2.9
     */
    public function sendto_finish()
    {
        fclose($this->sendto_filehandle);
        unlink($this->sendto_tempfile);
        unset($this->sendto_tempfile, $this->sendto_filehandle);
        return true;
    }

    // Following for WebDAV

    public function remove_dir($path)
    {
        $info = $this->resolve_path($path, true);
        if ($info['type'] != 'd') {
            return false;
        }

        return $this->cDB->dele_group($info['item']['folder_path']);
    }

    public function rename_dir($path, $name)
    {
        $info = $this->resolve_path($path, true);
        if ($info['type'] != 'd') {
            return false;
        }

        return $this->cDB->update_group($info['item']['folder_path'], $name);
    }

    public function create_dir($path, $name)
    {
        if ($path != '/') {
            return false;
        }
        return $this->cDB->add_group($name);
    }

    public function remove_item($id)
    {
        return $this->cDB->delete_event($id);
    }

    public function rename_item($id, $name)
    {
        return false;
    }

    public function read_item_content($item)
    {
        if (substr($item, 0, 1) == 'd') { // "d" stands for "directory"
            $PHM_CAL_EX_GROUP = intval(substr($item, 1));
            if ($item != 'd0') {
                $info = $this->cDB->get_group($PHM_CAL_EX_GROUP, true, true);
                if (false === $info || empty($info)) {
                    return false;
                }
            }
        } elseif (substr($item, 0, 1) == 'e') { // "e" -> event
            $PHM_CAL_EX_EVENT = intval(substr($item, 1));
            $info = $this->get_event($PHM_CAL_EX_EVENT);
            if (false === $info || empty($info)) {
                return false;
            }
        } elseif (substr($item, 0, 1) == 't') { // "t" -> task / todo
            $PHM_CAL_EX_TODO = intval(substr($item, 1));
            $info = $this->get_task($PHM_CAL_EX_TODO);
            if (false === $info || empty($info)) {
                return false;
            }
        }

        $_PM_ = &$this->_PM_;
        global $WP_msg, $_phM_privs;
        $tmpName = uniqid(time().'.').'.ics';
        $PHM_CAL_EX_DO = 'export';
        $PHM_CAL_EX_FORMAT = 'ICS';

        $PHM_CAL_EX_PUTTOFILE = $_PM_['path']['temp'].'/'.$tmpName; // Will put ICS file as attachment into FS
        if (!isset($GLOBALS['eventTypes'])) {
            $GLOBALS['eventTypes'] = $this->cDB->get_event_types();
        }
        if (!isset($GLOBALS['eventStatus'])) {
            $GLOBALS['eventStatus'] = $this->cDB->get_event_status();
        }
        require(__DIR__.'/exchange.php');

        // Hackish, yet it makes sure, that the temporary file is removed when the PHP process ends
        $onShutdown = 'chdir("'.getcwd().'"); unlink("'.$PHM_CAL_EX_PUTTOFILE.'");';
        register_shutdown_function(create_function('', $onShutdown));

        // Return as usual
        return fopen($PHM_CAL_EX_PUTTOFILE, 'r');
    }

    public function update_item_content($id, $res)
    {
        if (substr($id, 0, 1) == 'd') { // "d" stands for "directory"
            $PHM_CAL_IM_GROUP = intval(substr($id, 1));
            if ($id != 'd0') {
                $info = $this->cDB->get_group($PHM_CAL_IM_GROUP, true, true);
                if (false === $info || empty($info)) {
                    return false;
                }
            }
            $PHM_CAL_IM_SYNC = true; // Force deep sync
        } elseif (substr($id, 0, 1) == 'e') { // "e" -> event
            $PHM_CAL_IM_EVENT = intval(substr($id, 1));
            $info = $this->get_event($PHM_CAL_IM_EVENT);
            if (false === $info || empty($info)) {
                return false;
            }
        } elseif (substr($id, 0, 1) == 't') { // "t" -> task / todo
            $PHM_CAL_IM_TODO = intval(substr($id, 1));
            $info = $this->get_task($PHM_CAL_IM_TODO);
            if (false === $info || empty($info)) {
                return false;
            }
        }
        $_PM_ = &$this->_PM_;
        global $WP_msg, $_phM_privs;
        $PHM_CAL_IM_DO = 'import';
        $PHM_CAL_IM_FORMAT = 'ICS';
        // Daten entgegennehmen, $res ist eine Stream Resource
        $PHM_CAL_IM_FILERES = $res;
        $PHM_CAL_NO_OUTPUT = true;
        require __DIR__.'/exchange.php';
    }
}
