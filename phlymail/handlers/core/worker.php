<?php
/**
 * worker.php - Fetching commands from frontend and react on them
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Core
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.7 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

switch ($_REQUEST['what']) {
case 'get_quota_state':
    $max_percentage = 0;
    $problems = '';
    // Iterate through handlers, see what we get
    foreach ($_SESSION['phM_uniqe_handlers'] as $handler => $name) {
        // Fetch the handler name for proper output
        $hdlChoic = (file_exists($_PM_['path']['handler'].'/'.$handler.'/description.ini'))
                ? parse_ini_file($_PM_['path']['handler'].'/'.$handler.'/description.ini', true)
                : array('properties' => array('name' => ucfirst($handler)));
        // Look for an API call available
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
            continue;
        }
        require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
        $call = 'handler_'.$handler.'_configapi';
        if (!in_array('get_quota_definitions', get_class_methods($call))) {
            continue;
        }
        $API = new $call($_PM_, $_SESSION['phM_uid']);
        $crit = $API->get_quota_definitions($WP_msg['language']);
        foreach ($crit as $k => $v) {
            if ($v['query'] === false) {
                continue;
            }
            $limit = $DB->quota_get($_SESSION['phM_uid'], $handler, $k);
            $usage = $API->get_quota_usage($k);
            if (false !== $limit) {
                $perc = (0 == $limit) ? $usage : $usage / $limit;
                if ($perc > $max_percentage) {
                    $max_percentage = $perc;
                }
            }
        }
        unset($API);
    }
    sendJS(array('get_quota_state' => $max_percentage, 'get_servertime' => date('F j, Y H:i:s')), 1, 1);
    break;
case 'customsize':
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    if (isset($_REQUEST['token']) && preg_match('!^[a-z_][a-z0-9_]+$!i', $_REQUEST['token']) && isset($_REQUEST['value'])) {
        $GlChFile['customsize'][$_REQUEST['token']] = intval($_REQUEST['value']);
        sendJS(array('result' => $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile) ? 'okay' : 'failed'), 1, 1);
    }
    sendJS(array(), 1, 1);
    break;
case 'collapsedfolder':
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    if (isset($_REQUEST['aufzu']) && preg_match('!^auf|zu+$!', $_REQUEST['aufzu']) && isset($_REQUEST['folder'])
            && preg_match('!^[a-z_]([a-z0-9_]+|)$!i', $_REQUEST['folder'])) {
        $GlChFile['foldercollapses'][$_REQUEST['folder']] = ('zu' == $_REQUEST['aufzu']);
        $return = $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile) ? 'okay' : 'failed';
        sendJS(array(), 1, 1);
    }
    sendJS(array(), 1, 1);
    break;
case 'get_shared':
    if (!isset($_REQUEST['hdl']) || !isset($_REQUEST['fid'])) {
        sendJS(array(), 1, 1);
    }
    $shares = $DB->get_share_folder($_REQUEST['hdl'], $_REQUEST['fid']);
    $groups = array();
    $gids = array();
    foreach ($DB->get_usergrouplist($_SESSION['phM_uid'], true, true) as $gid => $gname) {
        $gids[] = $gid;
        $groups[] = array('gid' => $gid, 'name' => $gname, 's' => isset($shares['gid'][$gid]) ? 1 : 0);
    }
    $users = array();
    if (!empty($gids)) {
        $criteria = 'gid';
    } else {
        $criteria = 'active';
        $gids = null;
    }
    foreach ($DB->get_usridx($gids, $criteria) as $uid => $uname) {
        if ($uid == $_SESSION['phM_uid']) {
            continue; // Don't include currently logged-in user in list
        }
        $users[] = array('uid' => $uid, 'name' => $uname, 's' => isset($shares['uid'][$uid]) ? 1 : 0);
    }
    sendJS(array('groups' => $groups, 'users' => $users), 1, 1);
    break;
case 'set_shares':
    /*if (!isset($_REQUEST['hdl']) || !isset($_REQUEST['fid'])) {
        sendJS(array(), 1, 1);
    }
    $groups = array();
    if (isset($_REQUEST['gid']) && is_array($_REQUEST['gid'])) {
        foreach ($_REQUEST['gid'] as $gid) {
            $groups[$gid] = array('may_list' => 1, 'may_read' => 1, 'may_write' => 1, 'may_delete' => 1, 'may_newfolder' => 1, 'may_delitems' => 1);
        }
    }
    $users = array();
    if (isset($_REQUEST['uid']) && is_array($_REQUEST['uid'])) {
        foreach ($_REQUEST['uid'] as $uid) {
            $users[$uid] = array('may_list' => 1, 'may_read' => 1, 'may_write' => 1, 'may_delete' => 1, 'may_newfolder' => 1, 'may_delitems' => 1);
        }
    }
    $DB->set_share_folder($_REQUEST['hdl'], $_REQUEST['fid'], $groups, $users);*/
    break;
case 'favfolders_set':
    $FF = new DB_Controller_Favfolder();
    if (!isset($_REQUEST['hdl']) || !isset($_REQUEST['fid']) || !isset($_REQUEST['m'])) {
        sendJS(array(), 1, 1);
    }
    if ($_REQUEST['m'] == 1) {
        $FF->add($_SESSION['phM_uid'], $_REQUEST['hdl'], $_REQUEST['fid']);
    } else {
        $FF->drop($_SESSION['phM_uid'], $_REQUEST['hdl'], $_REQUEST['fid']);
    }
case 'favfolders_get':
    $FF = new DB_Controller_Favfolder();
    $js = array();
    foreach ($FF->getList($_SESSION['phM_uid']) as $k => $v) {
        $js[] = array('handler' => basename($v['handler']), 'fid' => basename($v['fid']));
    }
    sendJS(array('favourites' => $js), 1, 1);
    break;
case 'favfolders_reorder':
    $FF = new DB_Controller_Favfolder();
    $FF->reorder($_SESSION['phM_uid'], $_REQUEST['id']);
    sendJS(array(), 1, 1);
    break;

case 'logincheckupdates':
    // Legacy code for MC / LE
    echo 'no';
    exit;
    break;
}
