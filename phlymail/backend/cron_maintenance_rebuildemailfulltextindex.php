<?php
/**
 * Since adding a whole bunch of fulltext indexes on potentially large DB tables
 * takes a while, this process is handled separately.
 * Additionally the configuration is changed after successfully enabling
 * the indexes - fulltext seearch bceomes available to users once the process
 * has finished.
 *
 * MySQL 5.6 is required to use fulltext indexes on InnoDB tables
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2015 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.2 2015-03-31 
 */
class cron_maintenance_rebuildemailfulltextindex
{
    public function __construct($cronjob)
    {
        $this->job   = $cronjob;
    }

    public function Run()
    {
        global $_PM_;
        // API
        $API = new handler_email_api($_PM_, 0);
        // DB
        $DB = new DB_Base();

        if (!empty($_PM_['fulltextsearch']['available'])) {
            if (empty($_PM_['fulltextsearch']['enabled'])) {
                // The admin can switch off fulltext search completely - then we do'nt need the data in the database
                $DB->query('UPDATE ' . $DB->DB['db_pref'] . 'email_index SET `search_body`="www", search_body_type="none"');
            } else {
                // It's alright to ignore truncated strings in this context - what else can we do anyway?
                $DB->query('SET SESSION sql_mode=""');
                //
                // And now finally build the local search index by adding the mail bodies to the database
                //
                while (true) {
                    // "www" is a stop-word and as such sutiable to mark unindexed entries
                    // ORDER BY RAND() to prevent a single failed entry to completely clog up the indexing
                    // one record at a time is not perfectly efficient in terms of SQL yet has a lower memory profile
                    $qid = $DB->query('SELECT idx,uid FROM ' . $DB->DB['db_pref'] . 'email_index WHERE `search_body`="www" AND search_body_type="none" ORDER BY RAND() LIMIT 1');
                    if ($DB->numrows($qid) == 0) {
                        break;
                    }
                    $res = $DB->assoc($qid);
                    if (empty($res)) {
                        break;
                    }
                    $API->changeUID($res['uid']);

                    $mailInfo = $API->give_mail_part($res['idx'], null, false, true);
                    $mailbody = $API->give_mail_part($res['idx']);

                    if ($mailInfo['content_type'] == 'text/html' && strlen($mailbody) > 0) {
                        $mailbody = preg_replace(
                                array('!\<head.+\</head\>!simU', '!\<style.+\</style\>!simU', '!\<script.+\</script\>!simU', '!</?html(.+)?>!iU', '!</?body(.+)?>!iU'),
                                '',
                                $mailbody
                                );
                        try {
                            $mailbody = \Format\Convert\Html2Text::convert('<html><head></head><body>'.$mailbody.'</body></html>');
                        } catch (\Format\Convert\Html2TextException $e) {
                            $mailbody = '';
                            vecho($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                        }
                    }
                    $DB->query('UPDATE ' . $DB->DB['db_pref'] . 'email_index SET `search_body`="' . $DB->esc(trim($mailbody)) . '"'.
                            ', search_body_type="' . $DB->esc($mailInfo['content_type']) . '" WHERE idx=' . $res['idx']);
                    unset($mailbody); // prevent mem leaks
                }
            }
        }
        // Reindexing finished successfully - mark as done in DB
        $Cron = new DB_Controller_Cron();
        $Cron->markJobRunOnce('maintenance', 'rebuildemailfulltextindex', null);
    }
}
