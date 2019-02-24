<?php
/**
 * extending functionality for SabreDAV
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.7 2015-03-12 
 */
class phlyDAV_TempFileFilter extends Sabre_DAV_TemporaryFileFilterPlugin
{
    protected $threshold = 86400;

    public function __construct($dataDir)
    {
        parent::__construct($dataDir);
        $this->cleanup();
    }

    protected function cleanup()
    {
        $deltime = time() - $this->threshold;
        $toDel = array();
        if (empty($this->dataDir)) {
            return;
        }

        foreach (scandir($this->dataDir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $mtime = filemtime($this->dataDir.'/'.$item);
            $ctime = filectime($this->dataDir.'/'.$item);
            $lookat = $mtime > $ctime ? $mtime : $ctime;
            if (!empty($lookat) && $lookat < $deltime) {
                $toDel[] = $item;
            }
        }
        if (empty($toDel)) {
            return;
        }

        foreach ($toDel as $item) {
            unlink($this->dataDir.'/'.$item);
        }
    }
}