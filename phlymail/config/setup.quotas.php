<?php
/**
 * Managing system wide and user level quotas
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Config interface
 * @copyright 2006-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.0.4 20012-05-02 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

if (!isset($_SESSION['phM_perm_read']['quotas_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$WP_return = (isset($_REQUEST['WP_return']) && $_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : false;
$uid = (isset($_REQUEST['uid']) && $_REQUEST['uid']) ? $_REQUEST['uid'] : false;

if ($uid) {
    if ('saveuser' == $whattodo) {
        if (isset($_SESSION['phM_perm_write']['quotas_']) || $_SESSION['phM_superroot']) {
            foreach ($_REQUEST['crit'] as $handler => $crits) {
                foreach ($crits as $crit => $v) {
                    if (!strlen($v) || (0 == $v && $_SESSION['QuotaCritKeep'][$handler][$crit] == 0)) {
                        $DB->quota_drop($uid, $handler, $crit);
                        continue;
                    }
                    if (isset($_SESSION['QuotaCritTypes'][$handler][$crit]) && $_SESSION['QuotaCritTypes'][$handler][$crit] == 'filesize') {
                        $v = $v * 1048576;
                    }
                    $DB->quota_set($uid, $handler, $crit, $v);
                }
            }
            unset($_SESSION['QuotaCritTypes'], $_SESSION['QuotaCritKeep']);
            $error = $WP_msg['optssaved'];
            header('Location: '.$link_base.'quotas&whattodo=edituser&uid='.$uid.'&error='.urlencode($error));
            exit;
        } else {
            $error = $WP_msg['no_access'];
        }
    }
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.quota.edituser.tpl');
    $t_hl = $tpl->get_block('handlerline');
    $t_cl = $t_hl->get_block('critline');
    // Iterate through handlers, see what we get
    foreach ($_PM_['handlers'] as $handler => $active) {
        // Only look for active handlers
        if (!$active) continue;
        // Fetch the handler name for proper output
        $hdlChoic = (file_exists($_PM_['path']['handler'].'/'.$handler.'/description.ini'))
                ? parse_ini_file($_PM_['path']['handler'].'/'.$handler.'/description.ini', true)
                : array('properties' => array('name' => ucfirst($handler)));
        // Look for an installation API call available
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) continue;
        require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
        $call = 'handler_'.$handler.'_configapi';
        if (!in_array('get_quota_definitions', get_class_methods($call))) continue;
        $API = new $call($_PM_, $uid);
        $crit = $API->get_quota_definitions($WP_conf['language']);
        // Output, what was found
        $critcount = 0;
        foreach ($crit as $k => $v) {
            if ($v['query'] === false) continue;
            $critcount++;
            $limit = $DB->quota_get($uid, $handler, $k);
            $usage = $API->get_quota_usage($k);
            $t_cl->assign(array
                    ('msg_crit' => $v['name']
                    ,'crit' => $handler.'_'.$k
                    ,'input' => 'crit['.$handler.']['.$k.']'
                    // Filesizes are stated in MB, hence the division
                    ,'crit_limit' => (false !== $limit) ? (('filesize' == $v['type']) ? $limit/1048576 : $limit) : ''
                    ,'crit_use' => (false !== $usage) ? (('filesize' == $v['type']) ? intval($usage/1048576) : $usage) : ''
                    ,'crit_unit' => ('filesize' == $v['type']) ? 'MB' : ''
                    ,'crit_keep' => ($v['on_zero'] != 'drop' && $limit !== false) ? 1 : 0
                    ));
            $t_hl->assign('critline', $t_cl);
            $t_cl->clear();
            $_SESSION['QuotaCritTypes'][$handler][$k] = $v['type'];
            $_SESSION['QuotaCritKeep'][$handler][$k] = $v['on_zero'] != 'drop' ? 1 : 0;
        }
        if ($critcount) {
            $t_hl->assign('handler', $hdlChoic['properties']['name']);
            $tpl->assign('handlerline', $t_hl);
            $t_hl->clear();
        }
        unset($API);
    }
    // Get user's data
    $userdata = $DB->get_usrdata($uid);
    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'quotas&whattodo=saveuser&uid='.$uid)
            ,'where_um' => $WP_msg['UMLinkUM']
            ,'link_um' => htmlspecialchars($link_base.'users')
            ,'where_user' => str_replace('$1', $userdata['username'], $WP_msg['UMLinkUser'])
            ,'link_user' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$uid)
            ,'where_setquota' => $WP_msg['setquota']
            ,'msg_save' => $WP_msg['save']
            ,'about_quota' => $WP_msg['QuotaAboutUser']
            ,'head_crit' => $WP_msg['QuotaCrit']
            ,'head_limit' => $WP_msg['QuotaLimit']
            ,'head_usage' => $WP_msg['QuotaUsage']
            ));
    if (isset($_REQUEST['error']) && $_REQUEST['error']) {
        $tpl->fill_block('error', 'error', $_REQUEST['error']);
    }
    return;
}

if ('save' == $whattodo) {
    if (isset($_SESSION['phM_perm_write']['quotas_']) || $_SESSION['phM_superroot']) {
        foreach ($_REQUEST['crit'] as $handler => $crits) {
            foreach ($crits as $crit => $v) {
                if (!strlen($v) || (0 == $v && $_SESSION['QuotaCritKeep'][$handler][$crit] == 0)) {
                    $DB->quota_drop(0, $handler, $crit);
                    continue;
                }
                if (isset($_SESSION['QuotaCritTypes'][$handler][$crit]) && $_SESSION['QuotaCritTypes'][$handler][$crit] == 'filesize') {
                    $v = $v * 1048576;
                }
                $DB->quota_set(0, $handler, $crit, $v);
            }
        }
        unset($_SESSION['QuotaCritTypes'], $_SESSION['QuotaCritKeep']);
        $error = $WP_msg['optssaved'];
        header('Location: '.$link_base.'quotas&error='.urlencode($error));
        exit;
    } else {
        $error = $WP_msg['no_access'];
        $whattodo = false;
    }
}

if (!$whattodo) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.quota.main.tpl');
    if (isset($_REQUEST['error']) && $_REQUEST['error']) $error = $_REQUEST['error'];
    if (isset($error) && $error) $tpl->fill_block('error', 'error', $error);
    $t_hl = $tpl->get_block('handlerline');
    $t_cl = $t_hl->get_block('critline');
    // Iterate through handlers, see what we get
    foreach ($_PM_['handlers'] as $handler => $active) {
        // Only look for active handlers
        if (!$active) continue;
        // Fetch the handler name for proper output
        $hdlChoic = (file_exists($_PM_['path']['handler'].'/'.$handler.'/description.ini'))
                ? parse_ini_file($_PM_['path']['handler'].'/'.$handler.'/description.ini', true)
                : array('properties' => array('name' => ucfirst($handler)));
        // Look for an installation API call available
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) continue;
        require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
        $call = 'handler_'.$handler.'_configapi';
        if (!in_array('get_quota_definitions', get_class_methods($call))) continue;
        $API = new $call($_PM_, 0);
        $crit = $API->get_quota_definitions($WP_conf['language']);
        // Output, what was found
        $critcount = 0;
        foreach ($crit as $k => $v) {
            if ($v['query'] === false) continue;
            $critcount++;
            $limit = $DB->quota_get(0, $handler, $k);
            $usage = $API->get_quota_usage($k, true);
            $t_cl->assign(array
                    ('msg_crit' => $v['name']
                    ,'crit' => $handler.'_'.$k
                    ,'input' => 'crit['.$handler.']['.$k.']'
                    // Filesizes are stated in MB, hence the division
                    ,'crit_limit' => (false !== $limit) ? (('filesize' == $v['type']) ? $limit/1048576 : $limit) : ''
                    ,'crit_unit' => ('filesize' == $v['type']) ? 'MB' : ''
                    ,'crit_avg' => ($usage['count']) ? (('filesize' == $v['type']) ? size_format($usage['sum']/$usage['count']) : intval($usage['sum']/$usage['count'])) : 0
                    ,'crit_max' => ('filesize' == $v['type']) ? size_format($usage['max_count']) : intval($usage['max_count'])
                    ,'link_maxuser' => htmlspecialchars($link_base.'quotas&uid='.$usage['max_uid'])
                    ));
            $t_hl->assign('critline', $t_cl);
            $t_cl->clear();
            $_SESSION['QuotaCritTypes'][$handler][$k] = $v['type'];
            $_SESSION['QuotaCritKeep'][$handler][$k] = $v['on_zero'] != 'drop' ? 1 : 0;
        }
        if ($critcount) {
            $t_hl->assign('handler', $hdlChoic['properties']['name']);
            $tpl->assign('handlerline', $t_hl);
            $t_hl->clear();
        }
        unset($API);
    }
    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'quotas&whattodo=save')
            ,'msg_save' => $WP_msg['save']
            ,'about_quota' => $WP_msg['QuotaAboutGeneral']
            ,'head_crit' => $WP_msg['QuotaCrit']
            ,'head_limit' => $WP_msg['QuotaLimit']
            ,'head_usage' => $WP_msg['QuotaUsage']
            ,'msg_avguser' => $WP_msg['SMSAvgUser']
            ,'msg_maxuser' => $WP_msg['SMSMaxUse']
            ,'msg_showuser' => $WP_msg['SMSShowUser']
            ));
}
