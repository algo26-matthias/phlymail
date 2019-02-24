<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler Calendar
 * @subpackage Import / Export
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2015-02-25 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// This is an XNA request
if (!empty($XNA)) {
    // Security measure, since other things are not supported right now
    if ($load != 'feed') {
        $load = 'feed';
    }
    $action = json_decode($action, true);
    define('PHM_API_UID', $action['uid']);
    // Apply permission checks, read settings for user
    if (isset($DB->features['permissions']) && $DB->features['permissions']) {
        $_phM_privs = $DB->get_user_permissions(PHM_API_UID);
        $_phM_privs['all'] = false;
    } else {
        $_phM_privs['all'] = true;
    }
    $folder = $action['g'];
    $format = $action['f'];
} else { // Normal invocation through HTTP AUTH
    $load = 'feed';
    $folder = isset($_REQUEST['g']) ? intval($_REQUEST['g']) : 0;
    $format = isset($_REQUEST['f']) ? basename($_REQUEST['f']) : 'ICS';
}

// This is going to become a separate module once there's more than just this API call
if ($load == 'feed') {
    $API = new handler_email_api($_PM_, PHM_API_UID);
    if (!empty($folder)) {
        if (preg_match('!^\d+(,\d+){1,}$!', $folder)) {
            $folders = explode(',', $folder);
            $pageTitle = $WP_msg['InboxFeeds'];
        } else {
            $folders = array($folder);
            $folderInfo = $API->get_folder_info($folder);
            $pageTitle = str_replace('$2', $folderInfo['foldername'], $WP_msg['FolderFeed']);
        }
    } else {
        $folders = array();
        $arrSysFld = $API->get_system_folder('inbox');
        if (!empty($arrSysFld)) {
            foreach ($arrSysFld as $folder) {
                $folders[] = $folder['idx'];
            }
        }
        $pageTitle = $WP_msg['InboxFeeds'];
    }
    if (isset($_PM_['core']['provider_name']) && $_PM_['core']['provider_name'] != '') {
        $providerName = $_PM_['core']['provider_name'];
    } elseif (file_exists($_PM_['path']['conf'].'/build.name')) {
        $providerName = file_get_contents($_PM_['path']['conf'].'/build.name');
    } else {
        $providerName = 'phlyMail';
    }
    $pageTitle = str_replace('$1', $providerName, $pageTitle);

    header('Content-type: text/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>'.LF;
    echo '<rss version="2.0">'.LF.
        '<channel>'.LF.
		'<title><![CDATA['.phm_entities($pageTitle).']]></title>'.LF.
		'<link>'.PHM_SERVERNAME.'/</link>'.LF.
		'<description><![CDATA['.phm_entities($pageTitle).']]></description>'.LF.
		'<pubDate>'. date('r') . '</pubDate>'.LF;

    $maillist = $API->list_items($folders, 0, 20);
    if (!empty($maillist)) {
        foreach ($maillist as $item) {
            if (empty($item['from'])) {
                continue;
            }
            $author = Format_Parse_Email::parse_email_address($item['from']);
            $author = $author[0].($author[1] ? ' ('.$author[1].')' : '');
            echo '<item>'.LF.
                '<title><![CDATA['. phm_entities($item['subject']) . ']]></title>'.LF.
                '<author>'.  $author .'</author>'.LF.
                '<pubDate>' . date('r', strtotime($item['date_sent'])) .'</pubDate>'.LF.
                '<guid isPermaLink="false">' . phm_entities($item['uidl']) . '</guid>'.LF.
                '<description><![CDATA[' . $author . ' - ' . date($WP_msg['dateformat']).' - '. size_format($item['size'], true) .']]></description>'.LF.
                '</item>'.LF;
        }
    }
    echo '</channel>'.LF.'</rss>'.LF;

}