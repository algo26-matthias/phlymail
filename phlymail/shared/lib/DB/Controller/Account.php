<?php
/**
 * Konkreter Controller
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.2.6 2015-07-20
 */
class DB_Controller_Account extends DB_Controller
{
    /**
     * Constructor
     * Read the config and get an instance of the DB singleton
     */
    public function __construct()
    {
        parent::__construct();

        $this->Tbl['profiles'] = $this->DB['db_pref'].'profiles';
        $this->Tbl['signatures'] = $this->DB['db_pref'].'signatures';
        $this->Tbl['aliases'] = $this->DB['db_pref'].'profile_alias';
    }

    // Get index for all accounts of a certain user
    // Input  : get_accidx(integer user id, string user name)
    // Returns: $return      array data on success, FALSE otherwise
    //          $return[id]  Display name of the account(s)
    public function getAccountIndex($uid = 0, $extended = false, $protocol = false)
    {
        $return = array();
        $q_r = ($protocol) ? ' AND `acctype`="'.$this->esc($protocol).'"' : '';
        $qid = $this->query('SELECT `id`, `accid`, `accname`, `acctype` FROM '.$this->Tbl['profiles']
                .' WHERE uid='.intval($uid).$q_r.' ORDER BY `order` ASC, `accid` ASC');
        if ($qid) {
            while ($line = $this->assoc($qid)) {
            	if ($extended) {
                	$return[$line['id']] = $line;
            	} else {
            		$return[$line['accid']] = $line['accname'];
            	}
            }
        }
        return $return;
    }

    /**
     * Get personal data of a certain user
     * @param integer user id
     * @param integer account number
     * @param integer real account ID
     * @return array data on success, FALSE otherwise; The erray contains
     * - sig_on     integer is the signature active?
     * - real_name  string real name of the user
     * - address    string email address to use for sending
     * - signature  blob signature
     * - aliases  array (aid => int, real_name => string, email => string)
     */
    public function getAccount($uid = 0, $accid = 0, $real_id = 0)
    {
        $q_r = ($real_id) ? '`id`='.intval($real_id) : 'uid='.intval($uid).' AND accid='.intval($accid);
        $return = $this->assoc($this->query('SELECT * FROM '.$this->Tbl['profiles'].' WHERE '.$q_r));
        if (empty($return)) {
            return false;
        }
        $return['aliases'] = array();
        $return['userheaders'] = ($return['userheaders'])
                ? unserialize($return['userheaders'])
                : array();
        $return['logintime'] = strtotime($return['logintime']); //

        if ($this->DB['secaccpass']) {
            $return['poppass'] = $this->deconfuse($return['poppass'], $return['popserver'].$return['popport'].$return['popuser']);
            $return['smtppass'] = $this->deconfuse($return['smtppass'], $return['smtpserver'].$return['smtpport'].$return['smtpuser']);
        }
        $return['be_checkevery'] = 0;
        $Cron = new DB_Controller_Cron();
        $job = $Cron->getJobs('email', 'fetchmails', $return['id']);
        if (!empty($job) && !empty($job[0])) {
            $return['be_checkevery'] = $job[0]['interval'];
        }

        // Stored before we unified the check interval
        if ($return['be_checkevery'] != $return['checkevery']) {
            if (!$return['checkevery'] || $return['be_checkevery'] < $return['checkevery']) {
                $return['checkevery'] = $return['be_checkevery'];
            }
        }
        unset($return['be_checkevery']);

        // Older installations allowed free entry, nowadays we've got a few, fixed values
        if ($return['checkevery'] > 0) {
            # FIXME put the values into global configuration
            $legalFetchValues = array(5, 10, 15, 30, 60, 120, 360, 480, 720, 1440);
            $newFetchValue = 0;
            foreach ($legalFetchValues as $chkMe) {
                if ($return['checkevery'] > $chkMe) {
                    $newFetchValue = $chkMe;
                    continue;
                }
                $newFetchValue = $chkMe;
                break;
            }
            $newFetchValue = $newFetchValue;
        }

        if (!isset($return['uid'])) {
            return $return;
        }
        $qid = $this->query('SELECT `aid`,`real_name`,`email`,`signature`,`sendvcf` FROM '.$this->Tbl['aliases']
                .' WHERE uid='.$return['uid'].' AND profile='.$return['accid']);
        while ($alias = $this->assoc($qid)) {
            $return['aliases'][$alias['aid']] = $alias;
        }
        return $return;
    }

    /** Set login timestamp of a specific account of a user
     * @param integer user id
     * @param integer account number
     * @param integer real account ID
     * @return bool
     */
    public function setLoginTime($uid = false, $accid = false, $pid = false)
    {
        if (!$uid) {
            return;
        }
        if (!$accid && !$pid) {
            return;
        }
        $q_r = ($accid) ? ' AND accid='.intval($accid) : ' AND `id`='.intval($pid);
        return $this->query('UPDATE '.$this->Tbl['profiles'].' set logintime=NOW() WHERE uid='.intval($uid).$q_r);
    }

    // Check, if a given username (already) exists in the database
    // Input  : checkfor_username(string username)
    // Returns: Account ID if exists, FALSE otherwise
    public function AccountNameExists($uid, $accname = '')
    {
        list ($exists) = $this->fetchrow($this->query('SELECT accid FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accname="'.$this->esc($accname).'"'));
        return ($exists) ? $exists : false;
    }

    /**
     * Insert new account for a user into the database
     * @param array $input  array containing user data
     * @return  Record ID of created account on success, FALSE otherwise
     */
    public function addAccount($input)
    {
        list ($input['accid']) = $this->fetchrow($this->query('SELECT max(accid)+1 FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($input['uid'])));
        if ($input['accid'] == 0) {
            $input['accid'] = 1;
        }

        if ($this->DB['secaccpass']) {
            $input['poppass'] = $this->confuse($input['poppass'], $input['popserver'].$input['popport'].$input['popuser']);
        }
        if ($this->DB['secaccpass']) {
            $input['smtppass'] = $this->confuse($input['smtppass'], $input['smtpserver'].$input['smtpport'].$input['smtpuser']);
        }
        $input['userheaders'] = isset($input['userheaders']) ? serialize($input['userheaders']) : '';

        $qid = $this->query('INSERT '.$this->Tbl['profiles']
                .' (uid,accid,acctype,accname,sig_on,popserver,popport,popuser,poppass,popsec'
                .',smtpserver,smtpport,smtpuser,smtppass,smtpsec,real_name,address,signature,leaveonserver'
                .',localkillserver,cachetype,checkspam,checkevery,inbox,sent,drafts,waste,junk,templates,archive'
                .',onlysubscribed,trustspamfilter,imapprefix,userheaders,sendvcf'
                .',popallowselfsigned,smtpallowselfsigned) values ('
                .'"'.intval($input['uid']).'","'.intval($input['accid']).'","'.$this->esc($input['acctype']).'"'
                .',"'.$this->esc($input['accname']).'"'
                .',"'.$this->esc($input['sig_on']).'","'.$this->esc($input['popserver']).'"'
                .',"'.intval($input['popport']).'","'.$this->esc($input['popuser']).'"'
                .',"'.$this->esc($input['poppass']).'","'.$this->esc($input['popsec']).'"'
                .',"'.$this->esc($input['smtpserver']).'","'.intval($input['smtpport']).'"'
                .',"'.$this->esc($input['smtpuser']).'","'.$this->esc($input['smtppass']).'"'
                .',"'.$this->esc($input['smtpsec']).'","'.$this->esc($input['real_name']).'"'
                .',"'.$this->esc($input['address']).'","'.intval($input['signature']).'"'
                .',"'.$this->esc($input['leaveonserver']).'","'.$this->esc($input['localkillserver']).'"'
                .',"'.(isset($input['cachetype']) ? $this->esc($input['cachetype']) : 'full').'"'
                .',"'.$this->esc($input['checkspam']).'","'.intval($input['checkevery']).'"'
                .','.intval($input['inbox'])
                .','.(isset($input['sent']) ? intval($input['sent']) : '0')
                .','.(isset($input['drafts']) ? intval($input['drafts']) : '0')
                .','.(isset($input['waste']) ? intval($input['waste']) : '0')
                .','.(isset($input['junk']) ? intval($input['junk']) : '0')
                .','.(isset($input['templates']) ? intval($input['templates']) : '0')
                .','.(isset($input['archive']) ? intval($input['archive']) : '0')
                .',"'.(isset($input['onlysubscribed']) ? intval($input['onlysubscribed']) : 0).'"'
                .',"'.(isset($input['trustspamfilter']) ? intval($input['trustspamfilter']) : 0).'"'
                .',"'.(isset($input['imapprefix']) ? $this->esc($input['imapprefix']) : '').'"'
                .',"'.$this->esc($input['userheaders']).'"'
                .',"'.(isset($input['sendvcf']) ? $this->esc($input['sendvcf']) : 'none').'"'
                .',"'.(isset($input['popallowselfsigned']) ? intval($input['popallowselfsigned']) : 0).'"'
                .',"'.(isset($input['smtpallowselfsigned']) ? intval($input['smtpallowselfsigned']) : 0).'")'
                );
        if ($qid) {
            $pid = $this->insertid();
            $Cron = new DB_Controller_Cron();
            if ($input['checkevery'] > 0) {
                $Cron->setJob('email', 'fetchmails', $pid, $input['checkevery']*1, 50);
            }
            if ($input['acctype'] == 'imap') {
                $Cron->setJob('email', 'syncfoldertree', $pid, 1, 50);
            }
            return $input['accid'];
        }
        return false;
    }

    /**
     * Update the record of an account for a user in the database
     * @param array $input  array containing user data
     * @return  TRUE on success, FALSE otherwise
     */
    public function updateAccount($input)
    {
        $sql = 'SELECT p.`id` FROM '.$this->Tbl['profiles'].' p'
                .' WHERE p.`uid`='.intval($input['uid']).' AND p.`accid`='.intval($input['accid']);
        list ($pid) = $this->fetchrow($this->query($sql));

        $Cron = new DB_Controller_Cron();
        if ($input['checkevery'] > 0) {
            $Cron->setJob('email', 'fetchmails', $pid, $input['checkevery']*1, 50);
        } else {
            $Cron->removeJob('email', 'fetchmails', $pid);
        }
        if (!$Cron->jobExists('email', 'syncfoldertree', $pid)) {
            $Cron->setJob('email', 'syncfoldertree', $pid, 1, 50);
        }
        if (isset($input['userheaders'])) {
            $input['userheaders'] = serialize($input['userheaders']);
        }
        $query = 'UPDATE '.$this->Tbl['profiles'].' SET ';
        foreach (array
                ('accname', 'acctype', 'sig_on', 'popserver', 'popport', 'popuser', 'popsec'
                ,'smtpserver', 'smtpport', 'smtpuser', 'smtpsec' ,'real_name', 'address'
                ,'signature', 'leaveonserver', 'localkillserver', 'cachetype', 'checkspam'
                ,'checkevery', 'onlysubscribed', 'trustspamfilter', 'imapprefix', 'userheaders', 'sendvcf'
                ,'inbox', 'sent', 'drafts', 'waste', 'junk', 'templates', 'archive', 'popallowselfsigned'
                ,'smtpallowselfsigned') as $field) {
            if (!isset($input[$field])) {
                continue;
            }
            $query .= $field.'="'.$this->esc($input[$field]).'", ';
        }
        if ($input['poppass'] != false) {
            if ($this->DB['secaccpass']) {
                $input['poppass'] = $this->confuse($input['poppass'], $input['popserver'].$input['popport'].$input['popuser']);
            }
            $query .= 'poppass="'.$this->esc($input['poppass']).'", ';
        }
        if ($input['smtppass'] != false) {
            if ($this->DB['secaccpass']) {
                $input['smtppass'] = $this->confuse($input['smtppass'], $input['smtpserver'].$input['smtpport'].$input['smtpuser']);
            }
            $query .= 'smtppass="'.$this->esc($input['smtppass']).'", ';
        }
        $query .= 'logintime=logintime WHERE `id`='.intval($pid);
        return ($this->query($query));
    }

    // Delete an account of a given user from database
    // Input:  delete_account(string username, integer accountID)
    // Return: TRUE on success, FALSE otherwise
    public function deleteAccount($uid, $accountnumber)
    {
        $sql = 'SELECT p.`id` FROM '.$this->Tbl['profiles'].' p'
                .' WHERE p.`uid`='.intval($uid).' AND p.`accid`='.intval($accountnumber);
        list ($pid) = $this->fetchrow($this->query($sql));

        $Cron = new DB_Controller_Cron();
        $Cron->removeJob('email', 'fetchmails', $pid);

        if ($this->query('DELETE FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND `id`='.intval($pid))) {
            $this->query('DELETE FROM '.$this->Tbl['aliases'].' WHERE `profile`='.intval($pid));
            return true;
        }
        return false;
    }

    public function convertAccount($uid, $accid)
    {
        $query = 'UPDATE '.$this->Tbl['profiles'].' SET `acctype`=IF(`acctype`="imap", "pop3", "imap") WHERE uid='.intval($uid).' AND `accid`='.intval($accid);
        return ($this->query($query));
    }

    /**
     * Takes an array as argument, where the order is contained
     * @param array $input Key: Account ID, Value: position in list
     * @return bool
     */
    public function reorderAccounts($uid, $input)
    {
        $uid = intval($uid);
        $oldMap = array();
        foreach ($this->getAccountIndex($uid, true, null) as $id => $arr) {
            $oldMap[$arr['accid']] = $id;
        }
        foreach ($input as $k => $v) {
            $this->query('UPDATE '.$this->Tbl['profiles'].' SET `order`='.($v).' WHERE `id`='.$oldMap[intval($k)].' AND `uid`='.$uid);
            $this->query('UPDATE '.$this->DB['db_pref'].'email_folders SET `layered_id`='.$v // This query possibly breaks some rules ;)
                    .' WHERE `uid`='.$uid.' AND `att_icon`=":imapbox" AND `folder_path`="'.$oldMap[intval($k)].':"');
        }
    }

    // Get the highest account id in use for a specific user
    // Input  : get_maxaccid(integer user id)
    // Returns: integer next possible profile id
    public function getMaxAccountId($uid = false)
    {
        if (false === $uid) {
            return 1;
        }
        list ($curr) = $this->fetchrow($this->query('SELECT max(accid) FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid)));
        return ($curr+1);
    }

    /**
     * Returns the ID of the profile a certain email address matches against
     * @param  int  User ID to query the DB for
     * @param  string  Email address to find the profile for
     * @return  int  ID of the profile or FALSE on no match
     * @since 3.6.2
     */
    public function getProfileFromEmail($uid = 0, $email = '')
    {
        $return = array(0, 0);
        if (!$email) {
            return $return;
        }
        $email = trim($email);
        $IDN = new idnaConvert();
        $idnemail = $IDN->decode($email);

        $where = ' WHERE uid='.intval($uid).' AND ("'.$this->esc($email).'" LIKE CONCAT("%", <<<$1>>>, "%")';
        if (strlen($idnemail)) {
            $where .= ' OR "'.$this->esc($idnemail).'" LIKE CONCAT("%", <<<$1>>>, "%")';
        }
        $where .= ') LIMIT 1';

        $query = 'SELECT accid, 0 FROM '.$this->Tbl['profiles'].str_replace('<<<$1>>>', 'address', $where);
        $return = $this->fetchrow($this->query($query));
        if (!$return[0]) {
            $query = 'SELECT profile, aid FROM '.$this->Tbl['aliases'].str_replace('<<<$1>>>', 'email', $where);
            $return = $this->fetchrow($this->query($query));
        }
        return $return;
    }

    /**
     * Returns the real DB index for a given "account id" for the given user
     *
     * @param int $uid
     * @param int $accid
     * @return int
     * @since 3.7.1
     */
    public function getProfileFromAccountId($uid, $accid)
    {
        list ($return) = $this->fetchrow($this->query('SELECT `id` FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accid='.intval($accid)));
        return $return;
    }

    /**
     * Returns the "account id" for a given real DB index for the given user
     *
     * @param int $uid
     * @param int $id
     * @return int
     * @since 4.3.0
     */
    public function getAccountIdFromProfile($uid, $id)
    {
        list ($return) = $this->fetchrow($this->query('SELECT `accid` FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND `id`='.intval($id)));
        return $return;
    }

    /**
     * Tries to determine a default email address for a user, if none is defined, any email address is returned
     * @param  int  user ID to query the DB for
     * @param  ref  _PM_ structure
     * @return string  Email address on success, false on failure (aka: no profiles yet)
     * @since  3.6.4
     */
    public function getDefaultEmail($uid, &$settings)
    {
        $return = false;
        if (isset($settings['core']['default_profile']) && $settings['core']['default_profile']) {
            $query = 'SELECT address FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accid='.intval($settings['core']['default_profile']);
            list ($return) = $this->fetchrow($this->query($query));
        }
        if (!$return) {
            $query = 'SELECT address FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' LIMIT 1';
            list ($return) = $this->fetchrow($this->query($query));
        }
        return $return;
    }

    /**
     * A speical method pair to switch between confused and clear text passwords for SMTP / POP3 / IMAP
     */
    public function confused_cleartext()
    {
        $r = array();
        $qid = $this->query('SELECT id, poppass, popserver, popport, popuser, smtppass, smtpserver, smtpport, smtpuser FROM '.$this->Tbl['profiles']);
        while ($line = $this->assoc($qid)) {
            $r[] = array
                    ('i' => $line['id']
                    ,'p' => $this->deconfuse($line['poppass'], $line['popserver'].$line['popport'].$line['popuser'])
                    ,'s' => $this->deconfuse($line['smtppass'], $line['smtpserver'].$line['smtpport'].$line['smtpuser'])
                    );
        }
        foreach ($r as $v) {
            $this->query('UPDATE '.$this->Tbl['profiles'].' SET `poppass`="'.$this->esc($v['p']).'", `smtppass`="'.$this->esc($v['s']).'" WHERE id='.$v['i']);
        }
    }

    public function cleartext_confused()
    {
        $r = array();
        $qid = $this->query('SELECT id, poppass, popserver, popport, popuser, smtppass, smtpserver, smtpport, smtpuser FROM '.$this->Tbl['profiles']);
        while ($line = $this->assoc($qid)) {
            $r[] = array
                    ('i' => $line['id']
                    ,'p' => $this->confuse($line['poppass'], $line['popserver'].$line['popport'].$line['popuser'])
                    ,'s' => $this->confuse($line['smtppass'], $line['smtpserver'].$line['smtpport'].$line['smtpuser'])
                    );
        }
        foreach ($r as $v) {
            $this->query('UPDATE '.$this->Tbl['profiles'].' SET `poppass`="'.$this->esc($v['p']).'", `smtppass`="'.$this->esc($v['s']).'" WHERE id='.$v['i']);
        }
    }

    /**
     * Add an alias (alternative email address plus real name) for a user and profile
     * @param  int  User ID to add the alias for
     * @param  int  profile ID to add the alias to
     * @param  string  Email address
     *[@param  string  Real name (might be empty)]
     *[@param  null|int  NULL for as of account, 0 for none, sig. ID otherwise; Default: NULL]
     *[@param  string  One of the tokens for the VCF type; Default: as account]
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.6.2
     */
    public function add_alias($uid = 0, $pid = 0, $email = '', $realname = '', $sig = null, $vcf = 'default')
    {
        if (!$email) {
            return false;
        }
        $query = 'INSERT '.$this->Tbl['aliases'].' SET `uid`='.intval($uid).',`profile`='.intval($pid)
                .',`email`="'.$this->esc($email).'",`real_name`="'.$this->esc($realname).'"'
                .',`signature`='.(is_null($sig) ? 'NULL' : intval($sig)).',`sendvcf`="'.$this->esc($vcf).'"';
        return $this->query($query);
    }

    /**
     * Update an alias
     * @param  int  User ID to update the alias for
     * @param  int  alias ID to update
     * @param  string  Email address
     *[@param  string  Real name (might be empty)]
     *[@param  null|int  NULL for as of account, 0 for none, sig. ID otherwise; Default: NULL]
     *[@param  string  One of the tokens for the VCF type; Default: as account]
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.6.2
     */
    public function update_alias($uid = 0, $aid = 0, $email = '', $realname = '', $sig = null, $vcf = 'default')
    {
        if (!$email) {
            return;
        }
        $query = 'UPDATE '.$this->Tbl['aliases'].' SET `email`="'.$this->esc($email)
                .'",`real_name`="'.$this->esc($realname).'"'
                .',`signature`='.(is_null($sig) ? 'NULL' : intval($sig)).',`sendvcf`="'.$this->esc($vcf).'"'
                .' WHERE uid='.intval($uid).' AND aid='.intval($aid);
        return $this->query($query);
    }

    /**
     * Delete an alias again
     * @param  int  User ID to delete the alias for
     * @param  int  alias ID to delete
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.6.2
     */
    public function delete_alias($uid, $aid)
    {
        return $this->query('DELETE FROM '.$this->Tbl['aliases'].' WHERE uid='.intval($uid).' AND aid='.intval($aid));
    }

    /**
     * Add an userheader (user defined mail header) for a user and profile
     * @param  int  User ID to add the userheader for
     * @param  int  profile ID to add the userheader to
     * @param  string  Header name
     * @param  string  Header value
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.8.2
     */
    public function add_uhead($uid = 0, $pid = 0, $hkey = '', $hval = '')
    {
        if (!$hkey) {
            return false;
        }
        list ($uheads) = $this->fetchrow($this->query('SELECT userheaders FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accid='.intval($pid)));
        $uheads = ($uheads) ? unserialize($uheads) : array();
        $uheads[$hkey] = $hval;
        $uheads = serialize($uheads);
        $query = 'UPDATE '.$this->Tbl['profiles'].' SET userheaders="'.$this->esc($uheads).'" WHERE uid='.intval($uid).' AND accid='.intval($pid);
        return $this->query($query);
    }

    /**
     * Update an userheader
     * @param  int  User ID to update the userheader for
     * @param  int  userheader ID to update
     * @param  string  Header name to replace
     * @param  string  Header name (new value)
     * @param  string  Header value
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.8.2
     */
    public function update_uhead($uid = 0, $pid = 0, $oldkey = '', $hkey = '', $hval = '')
    {
        if (!$hkey) {
            return;
        }
        list ($uheads) = $this->fetchrow($this->query('SELECT userheaders FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accid='.intval($pid)));
        $uheads = ($uheads) ? unserialize($uheads) : array();
        if ($hkey != $oldkey) {
            unset($uheads[$oldkey]);
        }
        $uheads[$hkey] = $hval;
        $uheads = serialize($uheads);
        $query = 'UPDATE '.$this->Tbl['profiles'].' SET userheaders="'.$this->esc($uheads).'" WHERE uid='.intval($uid).' AND accid='.intval($pid);
        return $this->query($query);
    }

    /**
     * Delete an user defined mail header again
     * @param  int  User ID to delete the userheader for
     * @param  string  Header name
     * @return  bool  TRUE if successfull, FALSE on failures
     * @since 3.8.2
     */
    public function delete_uhead($uid = 0, $pid = 0, $hkey = null)
    {
        if (!$hkey) {
            return;
        }
        list ($uheads) = $this->fetchrow($this->query('SELECT userheaders FROM '.$this->Tbl['profiles'].' WHERE uid='.intval($uid).' AND accid='.intval($pid)));
        $uheads = ($uheads) ? unserialize($uheads) : array();
        unset($uheads[$hkey]);
        $uheads = serialize($uheads);
        $query = 'UPDATE '.$this->Tbl['profiles'].' SET userheaders="'.$this->esc($uheads).'" WHERE uid='.intval($uid).' AND accid='.intval($pid);
        return $this->query($query);
    }

    /**
     * Add a signature
     *
     * @param integer $uid
     * @param string $title
     * @param string $signature
     * @param string $signature_html
     * @return bool
     */
    public function add_signature($uid = 0, $title = '', $signature = '', $signature_html = '')
    {
        if (!$uid || !$signature) {
            return;
        }
        return $this->query('INSERT '.$this->Tbl['signatures'].' SET uid='.intval($uid).', title="'.$this->esc($title).'"'
                .', signature="'.$this->esc($signature).'", signature_html="'.$this->esc($signature_html).'"');
    }

    /**
     * Update a signature
     *
     * @param integer $uid
     * @param integer $id
     * @param string $title
     * @param string $signature
     * @param string $signature_html
     * @return bool
     */
    public function update_signature($uid = 0, $id = 0, $title = '', $signature = '', $signature_html = '')
    {
        if (!$uid || ! $id || !$signature) {
            return;
        }
        return $this->query('UPDATE '.$this->Tbl['signatures'].' SET title="'.$this->esc($title).'"'
                .', signature="'.$this->esc($signature).'", signature_html="'.$this->esc($signature_html).'"'
                .' WHERE id='.intval($id).' AND uid='.intval($uid));
    }

    /**
     * Delete a signature
     *
     * @param int $uid
     * @param int $id
     * @return bool
     */
    public function delete_signature($uid = 0, $id = 0)
    {
        return $this->query('DELETE FROM '.$this->Tbl['signatures'].' WHERE id='.intval($id).' AND uid='.intval($uid));
    }

    /**
     * Return list of signatures defined for a specific user id
     *
     * @param int $uid
     * @return array
     */
    public function get_signature_list($uid)
    {
        $return = array();
        $qid = $this->query('SELECT id, title, signature, signature_html FROM '.$this->Tbl['signatures'].' WHERE uid='.intval($uid));
        while ($sig = $this->assoc($qid)) {
            $return[$sig['id']] = array('title' => $sig['title'], 'signature' => $sig['signature'], 'signature_html' => $sig['signature_html']);
        }
        return $return;
    }

    /**
     * Get a specific signature
     *
     * @param int $uid
     * @param int $id
     * @return array
     */
    public function get_signature($uid, $id)
    {
        $qid = $this->query('SELECT title, signature, signature_html FROM '.$this->Tbl['signatures'].' WHERE id='.intval($id).' AND uid='.intval($uid));
        list ($title, $signature, $signature_html) = $this->fetchrow($qid);
        return array('title' => $title, 'signature' => $signature, 'signature_html' => $signature_html);
    }
}
