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
 * @version 0.0.1 2015-03-27 
 */
class cron_maintenance_enableemailfulltextsearch
{
    public function __construct($cronjob)
    {
        $this->job   = $cronjob;
        $this->_PM_  = &$GLOBALS['_PM_'];
    }

    public function Run()
    {
        // Master config
        $config = parse_ini_file($this->_PM_['path']['conf'].'/choices.ini.php', true);
        // Table Structure
        $TS = new DB_Controller_TableStructure();
        // Do we have the right verion number?
        $ServerVersionString = $TS->serverinfo();
        $ServerVersionNum = preg_replace('![^0-9\.]!', '', $ServerVersionString);
        // Enable it in the database
        if (version_compare($ServerVersionNum, '5.6.4') >= 0) {
            // First the Fulltext indexes are added.
            if (empty($config['fulltextsearch']['available'])) {
                $add = array();
                $alter = array();
                // Adding (mulitple) indexes to large tables is better done via a temporary clone of the original table
                $sqls = array(
                        'CREATE TABLE {prefix}email_index_new LIKE {prefix}email_index',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_allfields` (`hsubject`,`hfrom`,`hto`,`hcc`,`search_body`)',
                        /*'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject` (`hsubject`) , ADD FULLTEXT INDEX `search_from` (`hfrom`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_to` (`hto`) , ADD FULLTEXT INDEX `search_cc` (`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_body` (`search_body`) , ADD FULLTEXT INDEX `search_subject_from` (`hsubject`,`hfrom`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_to` (`hsubject`,`hto`) , ADD FULLTEXT INDEX `search_subject_cc` (`hsubject`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_body` (`hsubject`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_from_to` (`hsubject`,`hfrom`,`hto`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_from_cc` (`hsubject`,`hfrom`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_from_body` (`hsubject`,`hfrom`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_from_to_cc` (`hsubject`,`hfrom`,`hto`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_from_to_body` (`hsubject`,`hfrom`,`hto`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_to_cc` (`hsubject`,`hto`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_to_body` (`hsubject`,`hto`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_to_cc_body` (`hsubject`,`hto`,`hcc`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_subject_cc_body` (`hsubject`,`hcc`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_from_to` (`hfrom`,`hto`) , ADD FULLTEXT INDEX `search_from_cc` (`hfrom`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_from_body` (`hfrom`,`hto`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_from_to_cc` (`hfrom`,`hto`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_from_to_body` (`hfrom`,`hto`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_from_to_cc_body` (`hfrom`,`hto`,`hcc`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_to_cc` (`hto`,`hcc`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_to_body` (`hto`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_to_cc_body` (`hto`,`hcc`,`search_body`)',
                        'ALTER TABLE {prefix}email_index_new ADD FULLTEXT INDEX `search_cc_body` (`hcc`,`search_body`)',*/
                        'INSERT INTO {prefix}email_index_new SELECT * FROM {prefix}email_index',
                        'ALTER TABLE {prefix}email_index RENAME {prefix}email_index_old',
                        'ALTER TABLE {prefix}email_index_new RENAME {prefix}email_index',
                        'DROP TABLE {prefix}email_index_old'
                        );
                $TS->update($add, $alter, $sqls);
                // Now enable full text seearch in phlyMail
                $config['fulltextsearch']['available'] = 1;
                $config['fulltextsearch']['enabled'] = 1;
                basics::save_config($this->_PM_['path']['conf'].'/choices.ini.php', $config);
                // Tell the crontab, that this script has been successfully run - never run it again, then
                $Cron = new DB_Controller_Cron();
                $Cron->markJobRunOnce('maintenance', 'enableemailfulltextsearch', null);
                // rebuild the index for all existing mails
                $Cron->setJob('maintenance', 'rebuildemailfulltextindex', null, 1440, 1);
            }
        }
    }
}
