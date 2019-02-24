<?php
/**
 * Clean up outdated temporary files and other left overs
 *
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2006-2013 phlyLabs Berlin, http://phlylabs.de
 * @version 4.0.2 2013-07-07 $Id: cron_calendar_remotefolders.php 1001 2013-02-21 16:15:24Z mso $
 */
class cron_maintenance_cleanupfs
{
    public function __construct($cronjob)
    {
        $this->job = $cronjob;
    }

    public function Run()
    {
        $DB = new DB_Base();

        $_PM_ = &$GLOBALS['_PM_'];
        $userlist = array();
        $d = opendir($_PM_['path']['userbase']);
        while (false !== ($fid = readdir($d))) {
            if (substr($fid, 0, 1) == '.') continue; // Ignore '.', '..', '.htacces', '.tmp' and so on
            if (!is_numeric($fid)) continue; // Only take UID based folders into account; ignore every other system folder
            $userlist[] = $fid;
        }
        closedir($d);

        foreach ($userlist as $uid) {
            vecho('UID: '.$uid.' ');
            $usrInfo = $DB->get_usrdata($uid, true);
            if (empty($usrInfo)) {
                vecho(' - not valid, dropping dir ...');
                // Deleting the dir depends on the OS we are running on
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || ini_get('safe_mode') || !is_callable('system')) {
                    basics::emptyDir($_PM_['path']['userbase'].'/'.$uid, true);
                } else {
                    system('rm -rf '.$_PM_['path']['userbase'].'/'.$uid);
                }
                vecho(' Done!'.LF.LF);
                continue;
            }
            vecho($usrInfo['username'].LF);
            $f = opendir($_PM_['path']['userbase'].'/'.$uid.'/email/');
            $folderlist = array();
            while (false !== ($fid = readdir($f))) {
                if (substr($fid, 0, 1) == '.') continue; // Ignore '.', '..', '.htacces', '.tmp' and so on
                $folderlist[] = $fid;
            }
            closedir($f);

            $unlink = array();
            $FS = new handler_email_driver($uid);
            foreach ($folderlist as $fid) {
                vecho(' -> Folder ID '.$fid.' ');
                $db_fid = $FS->get_folder_id_from_path($fid);
                if (false === $db_fid) {
                    vecho(' - DB problem, skipping'.LF);
                    continue;
                } else {
                    $folderInfo = $FS->get_folder_info($db_fid);
                    $fname = $folderInfo['foldername'];
                }
                if (empty($folderInfo)) {
                    vecho(' - not in DB, dropping dir ...'.LF);
                    // Deleting the dir depends on the OS we are running on
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || ini_get('safe_mode') || !is_callable('system')) {
                        basics::emptyDir($_PM_['path']['userbase'].'/'.$uid.'/email/'.$fid, true);
                    } else {
                        system('rm -rf '.$_PM_['path']['userbase'].'/'.$uid.'/email/'.$fid);
                    }
                    vecho(' Done!'.LF.LF);
                    continue;
                } else {
                    vecho(' - found as ID '.$db_fid.', Name: "'.$fname.'"'.LF);
                }

                $uidls = $FS->get_folder_uidllist($db_fid, false, false, 'uidl');
                if (false === $uidls) {
                    vecho('No permission for that folder'.LF);
                    continue;
                }
                $m = opendir($_PM_['path']['userbase'].'/'.$uid.'/email/'.$fid);
                while (false !== ($mid = readdir($m))) {
                    if ('.' == $mid || '..' == $mid) continue;
                    vecho(' ----> '.$mid.' ');
                    if (in_array($mid, $uidls)) {
                        vecho('OK.'.LF);
                        continue;
                    }
                    vecho('Not in DB, deleting');
                    $unlink[] = $fid.'/'.$mid;
                    vecho(' ... OK'.LF);
                }
                closedir($m);
            }
            unset($FS);
            // Checking for outdated temp files
            foreach (array(
                    $_PM_['path']['userbase'].'/'.$uid.'/email/.tmp/', // trailing slashes, please
                    $_PM_['path']['userbase'].'/'.$uid.'/core/'
                    ) as $tmpFolder) {
                if (!file_exists($tmpFolder)) {
                    continue;
                }
                $tmp = opendir($tmpFolder);
                if (false === $tmp) {
                    continue;
                }
                while (false !== ($mid = readdir($tmp))) {
                    if ('.' == $mid || '..' == $mid) {
                        continue;
                    }
                    if (is_dir($tmpFolder.$mid)) {
                        continue;
                    }
                    $del = false;
                    $mtime = filemtime($tmpFolder.$mid);
                    $ctime = filectime($tmpFolder.$mid);
                    if (false !== $mtime && $mtime < time()-172800) {
                        $del = true;
                    } elseif (false !== $ctime && $ctime < time()-172800) {
                        $del = true;
                    }
                    if ($del == true) {
                        $unlink[] = $mid;
                    }
                }
                closedir($tmp);
                foreach ($unlink as $mid) {
                    if (!strlen($mid)) continue; // Prevent deleting the whole base folder
                    unlink($tmpFolder.$mid);
                }
            }
        }
    }
}
