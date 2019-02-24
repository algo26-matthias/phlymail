<?php
/**
 * Trigger archive action for supporting handlers (email, calendar)
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2013 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.3 2013-03-27 $Id: cron_calendar_remotefolders.php 1001 2013-02-21 16:15:24Z mso $
 */

class cron_core_archive
{
    public function __construct($cronjob)
    {
        $this->job = $cronjob;
    }

    public function Run()
    {
        $_PM_ = $GLOBALS['_PM_'];
        $handlers = array();

        foreach (parse_ini_file($_PM_['path']['conf'].'/active_handlers.ini.php') as $activeHandler => $anAus) {
            if (!$anAus) {
                continue;
            }
            if (!file_exists($_PM_['path']['handler'].'/'.$activeHandler.'/api.php')) {
                continue;
            }
            $className = 'handler_'.$activeHandler.'_api';
            $testAPI = new $className($_PM_, 0);
            if (!is_callable(array($testAPI, 'folder_archive_items'))) {
                unset($testAPI);
                continue;
            } else {
                unset($testAPI);
                $handlers[$activeHandler] = $className;
            }
        }
        // This is pointless, then
        if (empty($handlers)) {
            vecho('No active handlers available ...');
            return true;
        }
        // Gather user list
        $DCF = new DB_Controller_Foldersetting();
        $DB = new DB_Base();
        $userList = $DB->get_usridx(null, 'active');
        // And go
        foreach ($userList as $userID => $userName) {
            vecho('**** User: '.$userID.' => '.$userName.' ****');

            $myPM = $_PM_; // Init or reset
            $choices = $DB->get_usr_choices($userID);
            if (!empty($choices)) {
                $myPM = merge_PM($myPM, $choices);
            }

            foreach ($handlers as $handler => $apiClassName) {
                vecho('--- Handler '.ucfirst($handler).' ---');

                $globalArchive    = isset($myPM['archive'][$handler.'_autoarchive']) ? $myPM['archive'][$handler.'_autoarchive'] : false;
                $globalArchiveAge = isset($myPM['archive'][$handler.'_autoarchive_age']) ? $myPM['archive'][$handler.'_autoarchive_age'] : false;
                $globalDelete     = isset($myPM['archive'][$handler.'_autodelete']) ? $myPM['archive'][$handler.'_autodelete'] : false;
                $globalDeleteAge  = isset($myPM['archive'][$handler.'_autodelete_age']) ? $myPM['archive'][$handler.'_autodelete_age'] : false;

                $API = new $apiClassName($myPM, $userID);
                foreach ($API->give_folderlist() as $fid => $folderInfo) {
                    vecho(' -> '.$folderInfo['foldername']);

                    $localArchive    = $DCF->foldersetting_get($handler, $fid, $userID, 'autoarchive');
                    $localArchiveAge = $DCF->foldersetting_get($handler, $fid, $userID, 'autoarchive_age');
                    $localDelete     = $DCF->foldersetting_get($handler, $fid, $userID, 'autodelete');
                    $localDeleteAge  = $DCF->foldersetting_get($handler, $fid, $userID, 'autodelete_Age');

                    $myArchive    = (!is_null($localArchive)) ? $localArchive : $globalArchive;
                    $myArchiveAge = (!is_null($localArchiveAge)) ? $localArchiveAge : $globalArchiveAge;
                    $myDelete     = (!is_null($localDelete)) ? $localDelete : $globalDelete;
                    $myDeleteAge  = (!is_null($localDeleteAge)) ? $localDeleteAge : $globalDeleteAge;

                    // Only try archiving on correct settings
                    if (!empty($myArchive) && !empty($myArchiveAge) && preg_match('!^(\d+)\s([a-z]+)$!i', $myArchiveAge)) {
                        vecho('+ archiving older than "'.$myArchiveAge.'"');
                        $API->folder_archive_items($fid, $myArchiveAge);
                    } else {
                        vecho('- archiving off');
                    }
                    // Only try expiration on correct settings
                    if (!empty($myDelete) && !empty($myDeleteAge) && preg_match('!^(\d+)\s([a-z]+)$!i', $myDeleteAge)) {
                        vecho('+ expiring older than "'.$myDeleteAge.'"');
                        $API->folder_expire_items($fid, $myDeleteAge);
                    } else {
                        vecho('- expiring off');
                    }
                }
                unset($API);
            }
        }
        return true;
    }
}
