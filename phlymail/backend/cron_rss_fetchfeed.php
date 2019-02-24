<?php
/**
 * Read feed's content, put into DB
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2013-2016 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.3 2016-01-21 $Id: cron_email_syncfoldertree.php 2731 2013-03-25 13:24:16Z mso $
 */
class cron_rss_fetchfeed
{
    public function __construct($cronjob)
    {
        $this->job   = $cronjob;
        $this->_PM_  = &$GLOBALS['_PM_'];
    }

    public function Run()
    {
        $DB = new handler_rss_driver();
        $http = new Protocol_Client_HTTP();
        $SP = new SimplePie();
        $SP->enable_cache(false);

        $feed = $DB->get_feed($this->job['item']);
        $req = parse_url($feed['xml_uri']);
        if (!empty($feed['ext_un'])) {
            $req['auth_user'] = $feed['ext_un'];
        }
        if (!empty($feed['ext_pw'])) {
            $req['auth_pass'] = $feed['ext_pw'];
        }
        $http->setAdditionalHeaders([
            'User-Agent' => sprintf('Mozilla/5.0 (compatible; %s %s)',
                    trim(file_get_contents($this->_PM_['path']['conf'].'/build.name')),
                    trim(file_get_contents($this->_PM_['path']['conf'].'/current.build'))
                    )
        ]);

        $content = $http->send_request($req);
        if (empty($content)) {
            $DB->update_feed($feed['id'], array('laststatus' => $http->getErrorNo(), 'lasterror' => $http->getErrorString()));
            echo $http->getErrorNo().' '.$http->getErrorString();
            echo $http->getResponseHeader();
            return false;
        }
        // Update info on feed
        $updateInfo = array('updated' => date('Y-m-d H:i:s'), 'laststatus' => $http->getErrorNo(), 'lasterror' => $http->getErrorString());
        $DB->update_feed($feed['id'], $updateInfo);
        $DB->change_owner($feed['owner']);
        $SP->set_raw_data($content);
        $SP->init();
        foreach ($SP->get_items() as $item) {
            $uuid = $item->get_id();
            if (empty($uuid)) {
                $uuid = $item->get_id(true);
            }
            # FIXME Use an extra table containing feed <-> uuid mappings, which get kept even if I delete an item from the items table!
            $exists = $DB->item_exists($feed['id'], $uuid);
            if ($exists) {
                continue;
            }
            $authorName = '';
            $author = $item->get_author();
            if (!empty($author)) {
                $authorName = $author->get_name();
            }
            $content = $item->get_content();
            if (!handler_rss_helper::isHTML($content)) {
                $content = handler_rss_helper::makeHTML($content);
            }
            $DB->add_item(array(
                    'uuid' => $uuid,
                    'author' => $authorName,
                    'title' => $item->get_title(),
                    'url' => $item->get_permalink(),
                    'content' => $content,
                    'feed_id' => $feed['id'],
                    'published' => $item->get_date('Y-m-d H:i:s')
            ));
        }
        return true;
    }
}