#!/usr/bin/php
<?php
/**
* phlyMail POP3 Server offering POP3 access to user mailboxes
* Supports RFCs 1939 (POP3), 1734 (POP3 AUTH command), 2195 (CRAMD-MD5) and a few others
*
* @package phlyMail Nahariya 4.0+
* @subpackage Backend
* @author  Matthias Sommerfeld, <mso@phlylabs.de>
* @author  Michael Reese <email suppressed>
* @copyright 2005-2012 phlyLabs Berlin, http://phlylabs.de
* @version 0.3.3 2012-05-02 
*/
set_time_limit(0);
declare ( ticks = 1 );
define('_IN_PHM_', true);

if (!version_compare(phpversion(), '5.3.0', '>=')) {
    // Magic Quotes deaktivieren!
    @ini_set('magic_quotes_runtime', '0');
    set_magic_quotes_runtime(0);
}
// Standard line ending, don't change it
define('CRLF', "\r\n");
define('LF', "\n");

chdir(__DIR__);
chdir('../');
$_PM_ = parse_ini_file('choices.ini.php', true);
require_once($_PM_['path']['lib'].'/functions.php');

// Global Choices, overloading core settings
if (file_exists($_PM_['path']['conf'].'/choices.ini.php')) {
    $_PM_ = merge_PM($_PM_, parse_ini_file($_PM_['path']['conf'].'/choices.ini.php', true));
}
$_PM_['core']['file_umask'] = octdec($_PM_['core']['file_umask']);
$_PM_['core']['dir_umask']  = octdec($_PM_['core']['dir_umask']);

if (!become_desired_user($_PM_['phlymta']['pop3d_runas'])) {
    die('Could not become desired user'.$_PM_['phlymta']['pop3d_runas'].LF);
}

class pop3_server {
    // RFC compliant states of the session
    private $session_states = array('authorization', 'transaction', 'update');
    // Used to check, whether a given command may be executed in a certain session state
    private $valid_commands = array
            ('authorization' => array('USER', 'PASS'/*, 'APOP'*/, 'AUTH', 'CAPA', 'QUIT')
            ,'transaction' => array('STAT', 'LIST', 'RETR', 'DELE', 'CAPA', 'NOOP', 'RSET', 'TOP', 'UIDL', 'QUIT')
            ,'update' => array()
            );
    // Start off with the AUTHORIZATION state
    private $state = 'authorization';
    // Time out values in seconds
    private $timeouts = array('transaction' => 600, 'authorization' => 30);
    // Will we use APOP? If set to false, only USER/PASS (or the AUTH command) will be available
    private $use_APOP = false;
    // Will we allow secure login? If set to false, only USER/PASS (and maybe APOP) will be available
    private $use_AUTH = true;
    // The domain name to use for the APOP greeting
    private $domainname = 'phlymail.com';
    private $servicenname = 'phlyMTA POP3 service';
    // Will be filled with the username given on login. This is NOT the user id...
    private $username;
    // The same for the password given.
    private $password;
    // The identity of the user -> only set after successfully logging in
    private $identity;
    // Holds the APOP greeting issued, if any
    private $apop_greeting;

    // Will be filled with the maildrop listing
    private $maildrop = array();
    // Will be listing all mails marked as deleted, those get deleted in the UPDATE state
    private $markeddeleted = array();
    // Number of mails
    private $mailnum;
    // Mailbox size
    private $mailboxsize;

    // Go!
    public function __construct($socket, $_PM_)
    {
        $this->socket = fopen("php://stdin", "r");

        // These lines are phlyMail specific!
        $this->DB = new DB_Base();
        $this->_PM_ = $_PM_;
        $this->timeouts = array
                ('authorization' => (isset($_PM_['phlymta']['pop3d_timeoutauth']) && $_PM_['phlymta']['pop3d_timeoutauth'])
                        ? $_PM_['phlymta']['pop3d_timeoutauth']
                        : $this->timeouts['authorization']
                ,'transaction' => (isset($_PM_['phlymta']['pop3d_timeouttrans']) && $_PM_['phlymta']['pop3d_timeouttrans'])
                        ? $_PM_['phlymta']['pop3d_timeouttrans']
                        : $this->timeouts['transaction']
                );
        $this->domainname = (isset($_PM_['phlymta']['pop3d_domain']) && $_PM_['phlymta']['pop3d_domain'])
                ? $_PM_['phlymta']['pop3d_domain']
                : $this->domainname;
        $this->servicename = (isset($_PM_['phlymta']['pop3d_servicename']) && $_PM_['phlymta']['pop3d_servicename'])
                ? $_PM_['phlymta']['pop3d_servicename']
                : $this->servicename;
        // End specific

        // Send the client a greeting
        $this->send_greeting();
        // Main processing loop
        $this->exit = false;
        do { $this->read_input(); } while (!$this->exit);
    }

    /**
     * Reads a line from the input, parses it and tries to pass the parsed line to the relevant command handler
     */
    private function read_input()
    {
        declare ( ticks = 1 );
        $command = $this->socket_get();

        if (trim($command) == '') return;

        $parts = explode(' ', $command);
        if (empty($parts)) {
            $this->send_wrong_command();
            return;
        }
        $method_name = 'command_'.strtolower($parts[0]);
        if (!method_exists($this, $method_name)) {
            $this->send_wrong_command('unknown', $parts[0]);
            return;
        }
        if (!in_array(strtoupper($parts[0]), $this->valid_commands[$this->state])) {
            $this->send_wrong_command('invalid');
            return;
        }
        // The command itself is not needed for passing it to the relevant method ;)
        array_shift($parts);
        $this->$method_name($parts);
    }

    /**
     * Sends the POP3 server greeting to the client. If APOP is enabled, the machine key is issued here
     * @since 0.0.1
     */
    private function send_greeting()
    {
        if ($this->use_APOP) {
            $this->apop_greeting = ' <'.time().'.'.md5(time().getmypid().$this->domainname).'@'.$this->domainname.'>';
        }
        $this->socket_print('+OK '.$this->servicenname.' ready'.$this->apop_greeting.CRLF);
    }

    /**
     * Generic message to tell the client about an unknown or invalid command
     * @param 'invalid'|'unknown'
     */
    private function send_wrong_command($what = 'unknown', $cmd = '')
    {
        $this->socket_print('-ERR '.($what == 'invalid' ? 'Command not valid in this state' : 'Unknown command '.$cmd).CRLF);
    }

    private function command_noop($args)
    {
        $this->socket_print('+OK idling around'.CRLF);
    }

    private function command_rset($args)
    {
        $this->markeddeleted = array();
        $mailnum = 0;
        $size = 0;
        foreach ($this->maildrop as $mail) {
            $mailnum++;
            $size += $mail['size'];
        }
        $this->mailnum = $mailnum;
        $this->mailboxsize = $size;
        $this->socket_print('+OK All deletes are reset'.CRLF);
    }

    private function command_stat($args)
    {
        if (!empty($args)) {
            $this->socket_print('-ERR no arguments expected'.CRLF);
            return;
        }
        $this->socket_print('+OK '.$this->mailnum.' '.$this->mailboxsize.CRLF);
    }

    private function command_capa()
    {
        $this->socket_print
                ('+OK Capability list follows'.CRLF
                .'TOP'.CRLF
                .'UIDL'.CRLF
                .($this->use_AUTH ? 'SASL PLAIN LOGIN'.($this->use_APOP ? ' CRAM-MD5' : '').CRLF : '')
                .'EXPIRE NEVER'.CRLF
                .'IMPLEMENTATION phlyMTA'.CRLF
                .'.'.CRLF
                );
    }

    private function command_list($args)
    {
        if (isset($args[0])) {
            $args[0] = intval($args[0]);
            if (isset($this->maildrop[$args[0]]) && !isset($this->markeddeleted[$args[0]])) {
                $this->socket_print('+OK '.$args[0].' '.$this->maildrop[$args[0]]['size'].CRLF);
            } else {
                $this->socket_print('-ERR No such message'.CRLF);
            }
        } else {
            $this->socket_print('+OK listing follows'.CRLF);
            foreach ($this->maildrop as $num => $mail) {
                if (isset($this->markeddeleted[$num])) continue;
                $this->socket_print($num.' '.$mail['size'].CRLF);
            }
            $this->socket_print('.'.CRLF);
        }
    }

    private function command_uidl($args)
    {
        if (isset($args[0])) {
            $args[0] = intval($args[0]);
            if (isset($this->maildrop[$args[0]]) && !isset($this->markeddeleted[$args[0]])) {
                $this->socket_print('+OK '.$args[0].' '.$this->maildrop[$args[0]]['uidl'].CRLF);
            } else {
                $this->socket_print('-ERR No such message'.CRLF);
            }
        } else {
            $this->socket_print('+OK UIDL listing follows'.CRLF);
            foreach ($this->maildrop as $num => $mail) {
                if (isset($this->markeddeleted[$num])) continue;
                $this->socket_print($num.' '.$mail['uidl'].CRLF);
            }
            $this->socket_print('.'.CRLF);
        }
    }

    private function command_dele($args)
    {
        if (!isset($args[0]) || intval($args[0]) != $args[0] || !$args[0]) {
            $this->socket_print('-ERR no such message'.CRLF);
        } elseif (isset($this->markeddeleted[$args[0]])) {
            $this->socket_print('-ERR message '.$args[0].' already deleted'.CRLF);
        } elseif (!isset($this->maildrop[$args[0]])) {
            $this->socket_print('-ERR no such message'.CRLF);
        } else {
            $this->markeddeleted[$args[0]] = 1;
            $this->mailnum--;
            $this->mailboxsize -= $this->maildrop[$args[0]]['size'];
            $this->socket_print('+OK message deleted'.CRLF);
        }
    }

    private function command_top($args)
    {
        if (empty($args) || !isset($args[0])) {
            $this->socket_print('-ERR no message identifier given'.CRLF);
            return;
        }
        if (!isset($args[0])) {
            $this->socket_print('-ERR Please give me some clue about the number of lines to show you, 0 would be fine'.CRLF);
            return;
        }
        $args[0] = intval($args[0]);
        $args[1] = intval($args[1]);
        if (!isset($this->maildrop[$args[0]]) || isset($this->markeddeleted[$args[0]])) {
            $this->socket_print('-ERR No such message'.CRLF);
            return;
        }
        if ($args[1] < 0) {
            $this->socket_print('-ERR illegal offset given'.CRLF);
            return;
        }
        if (!$this->FS->give_mail($this->maildrop[$args[0]]['id'])) {
            $this->socket_print('-ERR erroring retrieving your mail'.CRLF);
            return;
        }
        $this->socket_print('+OK Listing follows'.CRLF);
        $counter = 0;
        $mode = 'header';
        while ($line = $this->FS->mailpart_giveline()) {
            if ($mode == 'body') {
                if ($counter >= $args[1]) break;
                ++$counter;
            }
            if (strlen(trim($line)) == 0) {
                $this->socket_print(CRLF);
                $mode = 'body';
            } elseif ($line{0} == '.') {
                $this->socket_print('.'.rtrim($line).CRLF);
            } else {
                $this->socket_print(rtrim($line).CRLF);
            }
        }
        $this->socket_print('.'.CRLF);
    }

    private function command_retr($args)
    {
        if (empty($args) || !isset($args[0])) {
            $this->socket_print('-ERR no message identifier given'.CRLF);
            return;
        }
        $args[0] = intval($args[0]);
        if (!isset($this->maildrop[$args[0]]) || isset($this->markeddeleted[$args[0]])) {
            $this->socket_print('-ERR No such message'.CRLF);
            return;
        }
        if (!$this->FS->give_mail($this->maildrop[$args[0]]['id'])) {
            $this->socket_print('-ERR erroring retrieving your mail.'.CRLF);
            return;
        }
        $this->socket_print('+OK Mail data follows'.CRLF);
        $this->FS->give_mail($this->maildrop[$args[0]]['id']);
        while ($line = $this->FS->mailpart_giveline()) {
            $this->socket_print(rtrim($line).CRLF);
        }
        $this->socket_print('.'.CRLF);
        $this->FS->mail_set_status($this->maildrop[$args[0]]['id'], 1);
    }

    private function command_quit($args)
    {
        if ($this->state == 'transaction') {
            $this->switch_to_update();
        }
        $this->socket_print('+OK Bye, bye'.CRLF);
        $this->exit = true;
    }

    private function command_user($args)
    {
        if (!isset($args[0])) {
            $this->socket_print('-ERR no username specified'.CRLF);
            return;
        }
        $this->username = $args[0];
        $this->socket_print('+OK Give password, please'.CRLF);
    }

    private function command_pass($args)
    {
        if (!$this->username) {
            $this->socket_print('-ERR Please issue the username first'.CRLF);
            return;
        }
        if (empty($args)) {
            $this->socket_print('-ERR no password specified'.CRLF);
            return;
        }
        list ($uid, $success) = $this->DB->authenticate($this->username, $args[0], null, null, $this->_PM_['auth']['system_salt']);
        if (false !== $uid && $success) {
            $this->identity = $uid;
            $this->switch_to_transaction();
        } else {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
    }

    private function command_apop($args)
    {
        if (!$this->use_APOP) {
            $this->send_wrong_command();
            return;
        }
        if (!isset($args[0]) || !isset($args[1])) {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
        list ($uid, $realpass) = $this->DB->authenticate($args[0]);

        if ($uid !== false && $args[1] == md5($this->apop_greeting.$realpass)) {
            $this->identity = $uid;
            $this->switch_to_transaction();
        } else {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
    }

    private function command_auth($args)
    {
        if (!isset($args[0]) || !$args[0]) {
            $this->socket_print('-ERR No authentication mechanism given'.CRLF);
            return;
        }
        $sasl_mech = '_auth_'.str_replace('-', '_', strtolower($args[0]));
        if (!method_exists($this, $sasl_mech)) {
            $this->socket_print('-ERR Unsupported SASL mechanism'.CRLF);
            return;
        }
        // Hand the AUTH process over to the method implementing the chosen SASL mechanism
        $this->$sasl_mech($args);
    }



    /**
     * Implementation of SASL mechanism CRAM-MD5
     */
    private function _auth_cram_md5($args)
    {
        // Needs a clear text password from the DB
        $this->socket_print('-ERR Not supported'.CRLF);
        return;
        $challenge = time().'.'.md5(time().getmypid().$this->domainname);
        $this->socket_print('+ '.base64_encode($challenge).CRLF);
        $response = explode(' ', base64_decode($this->socket_get()));
        if (!isset($response[0]) || !isset($response[1])) {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
        list ($uid, $realpass) = $this->DB->authenticate($response[0]);
        if ($uid != 0) {
            // Secret to use
            $secret = $realpass;
            // Rightpad with NUL bytes to have 64 chars
            if (strlen($secret) < 64) {
                $secret = $secret.str_repeat(chr(0x00), 64 - strlen($secret));
            }
            // In case, the secret is longer than 64 chars, md5() it
            if (strlen($secret) > 64) {
                $secret = md5($secret);
            }
            $ipad = str_repeat(chr(0x36), 64);
            $opad = str_repeat(chr(0x5c), 64);
            $shared = bin2hex(pack('H*', md5(($secret ^ $opad).pack('H*', md5(($secret ^ $ipad).$challenge)))));
            // Compare
            if ($shared == $response[1]) {
                $this->switch_to_transaction();
            }
        }
        $this->socket_print('-ERR Login failed'.CRLF);
        return;
    }

    /**
     * Implementation of SASL mechanism LOGIN
     */
    private function _auth_login($args)
    {
        $this->socket_print('+ '.base64_encode('Username:').CRLF);
        $username = base64_decode($this->socket_get());
        $this->socket_print('+ '.base64_encode('Password:').CRLF);
        $password = base64_decode($this->socket_get());

        list ($uid, $success) = $this->DB->authenticate($username, $password, null, null, $this->_PM_['auth']['system_salt']);
        if ($uid !== false && $success) {
            $this->identity = $uid;
            $this->switch_to_transaction();
        } else {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
    }

    /**
     * Implementation of SASL mechanism PLAIN
     */
    private function _auth_plain($args)
    {
        if (!isset($args[1]) || !$args[1]) {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
        $cred = explode(chr(0), base64_decode(trim($args[1])));
        if (!isset($cred[1]) || !isset($cred[2])) {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
        list ($uid, $success) = $this->DB->authenticate($cred[1], $cred[2], null, null, $this->_PM_['auth']['system_salt']);
        if ($uid !== false && $success) {
            $this->identity = $uid;
            $this->switch_to_transaction();
        } else {
            $this->socket_print('-ERR Login failed'.CRLF);
            return;
        }
    }


    // Switches over to the transaction states, aquires the mailbox and issues the positive response
    private function switch_to_transaction()
    {
        $this->FS = new handler_email_api($this->_PM_, $this->identity);
        $mailnum = 1;
        $octets = 0;
        foreach ($this->FS->list_inbox() as $mail) {
            $this->maildrop[$mailnum] = array('id' => $mail['id'], 'uidl' => $mail['uidl'], 'size' => $mail['size']);
            ++$mailnum;
            $octets += $mail['size'];
        }
        $this->state = 'transaction';
        $this->mailboxsize = $octets;
        $this->mailnum = ($mailnum-1);
        $this->socket_print('+OK logged in, got '.($this->mailnum).' messages with '.$this->mailboxsize.' octets.'.CRLF);
    }

    // Switches to update state, deletes mails marked as such, frees mailbox, returns
    private function switch_to_update()
    {
        if (!empty($this->markeddeleted)) {
            foreach ($this->markeddeleted as $mail => $yes) {
                $this->FS->mail_delete($this->maildrop[$mail]['id']);
            }
        }
        $this->exit = true;
    }

    private function socket_print($message)
    {
    		echo $message;
//        socket_write($this->socket, $message, strlen($message));
    }

    private function socket_get()
    {
        $mybuff = '';
        while (true) {
            $data = fgets($this->socket, 255);
//            $data = socket_read($this->socket, 255);
            if (strlen($data) == 0) {
                continue;
            }
            $mybuff .= $data;
            if (preg_match('!.+(\n|\r\n)$!', $data)) {
                return rtrim($mybuff);
            }
        }
    }
}

new pop3_server($ch, $_PM_);

function become_desired_user($desired)
{
    $user = posix_getpwuid(posix_getuid());
    if (strtolower($user['name']) == 'root') {
        $me = posix_getpwnam($desired);
        posix_setuid($me['uid']);
        posix_setgid($me['gid']);
        if (posix_getuid() == $me['uid']) {
            return true;
        } else {
            die(posix_getuid().' != '.$me['uid']);
        }
    } elseif (strtolower($user['name']) == $desired) {
        return true;
    }
    return false;
}
