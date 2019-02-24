<?php
/**
 * Return a list of available folders
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Files
 * @copyright 2001-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.3 2012-05-02 
 */
class handler_files_folderlist
{
    public $sysfolders = array
            ('mailbox' => array('root' => 1, 'de' => 'Meine Dateien', 'en' => 'My files', 'icon' => ':files')
            ,'waste' => array('root' => 0, 'de' => 'Papierkorb', 'en' => 'Trash', 'icon' => ':waste')
            );

    public function __construct(&$_PM_, $mode)
    {
        $this->folders = new handler_files_driver($_SESSION['phM_uid']);
        $this->folders->init_folders(false);
    }

    public function get()
    {
        return $this->translate($this->folders->read_folders(0), $GLOBALS['WP_msg']['language']);
    }

    private function translate($return, $language)
    {
        foreach ($return as $k => $v) {
            if ($v['type'] == 0) {
                foreach ($this->sysfolders as $data) {
                    if ($v['icon'] == $data['icon']) {
                        $return[$k]['foldername'] = (isset($data[$language])) ? $data[$language] : $v['foldername'];
                        break;
                    }
                }
            }
            if (is_array($v['subdirs'])) {
                $return[$k]['subdirs'] = $this->translate($v['subdirs'], $language);
            }
        }
        return $return;
    }
}
