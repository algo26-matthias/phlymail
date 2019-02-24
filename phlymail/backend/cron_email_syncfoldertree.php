<?php
/**
 * Syncing the subfolders of IMAP accounts and their state
 * After running this task, the servers folder index should be in sync
 * with the server's.
 * This job does not fetch any mails or stuff like that.
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2001-2012 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.1 2012-06-01 
 */

class cron_email_syncfoldertree
{
    public function __construct($cronjob)
    {
        $this->job   = $cronjob;
        $this->_PM_  = &$GLOBALS['_PM_'];
    }

    public function Run()
    {
        $Acnt = new DB_Controller_Account();
        $accdata = $Acnt->getAccount(null, null, $this->job['item']);
        if (empty($accdata)) {
            $Cron = new DB_Controller_Cron();
            $Cron->removeJob($this->job['handler'], $this->job['job'], $this->job['item']);
            return false;
        }
        $FS = new handler_email_driver($accdata['uid']);
        $folderID = $FS->get_folder_id_from_path($this->job['item'].':');
        $folder = $FS->get_folder_info($folderID);
        if (empty($folder)) {
            return false;
        }
        $folder['id'] = $folderID;
        $FS->init_imapbox($folder);
    }
}
