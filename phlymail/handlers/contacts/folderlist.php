<?php
/**
 * Returing list of available folders (address books if you like)
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Contacts
 * @copyright 2004-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.1.3 2012-05-02 
 */
if (!defined('CONTACTS_PUBLIC_CONTACTS')) {
    if (isset($_PM_['core']['contacts_nopublics']) && $_PM_['core']['contacts_nopublics']) {
        define('CONTACTS_PUBLIC_CONTACTS', false);
    } elseif (!$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['contacts_see_global_contacts']) {
        define('CONTACTS_PUBLIC_CONTACTS', false);
    } else {
        define('CONTACTS_PUBLIC_CONTACTS', true);
    }
}

class handler_contacts_folderlist
{
    public function __construct(&$_PM_, $mode)
    {
        $this->cDB = new handler_contacts_driver($_SESSION['phM_uid']);
        $this->_PM_ = $_PM_;
    }

    public function get()
    {
        if (file_exists($this->_PM_['path']['handler'].'/contacts/lang.'.$GLOBALS['WP_msg']['language'].'.php')) {
            require($this->_PM_['path']['handler'].'/contacts/lang.'.$GLOBALS['WP_msg']['language'].'.php');
        } else {
            require($this->_PM_['path']['handler'].'/contacts/lang.de.php');
        }
        $return = array();
        foreach ($this->cDB->get_grouplist(CONTACTS_PUBLIC_CONTACTS) as $k => $v) {
            $return[] = array
                ('path' => $v['gid']
                ,'icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'.png'
                ,'big_icon' => $this->_PM_['path']['theme'].'/icons/contactsfolder_'.(($v['owner'] == 0) ? 'global' : 'personal').'_big.gif'
                ,'foldername' => $v['name']
                ,'type' => 2
                ,'subdirs' => false
                );
        }
        return array(0 => array
            ('path' => 0
            ,'icon' => ':contacts'
            ,'foldername' => $WP_msg['MainFoldername']
            ,'type' => 2
            ,'subdirs' => (!empty($return)) ? $return : false
            ));
    }
}
