<?php
/**
 * IMAP client connector class
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Email handler
 * @subpackage Network client connectivity
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.4.7 2015-07-20
 * @todo Parse out-of-band responses, which indicate changes to the mailbox
 * @todo Implement ACLs, Quotas
 * @todo Allow usage of transparent IMAP proxy, which caches connections
 */

class Protocol_Client_IMAP extends Protocol_Client_Base
{
    protected $_diag_session = false;
    protected $server_capa = false;
    protected $scount = 0;
    protected $currFolder = false;
    protected $folderInfo = false;

    /**
     * Constructor
     *
     * @param string $server Server name (or IP address) to connect to
     * @param int $port  Port number, default: 143 (SSL: 993)
     * @param int $recon_slp  Time in seconds to wait between disconnect and next connection attempt
     * @param string $conn_sec  One of SSL, STARTTLS, AUTO, none (case sensitive!); Default: SSL
     */
    public function __construct($server, $port = 143, $recon_slp = null, $conn_sec = null, $self_signed = false)
    {
        if ($this->_diag_session) {
            $this->diag = fopen(__DIR__.'/imap_diag.txt', 'a');
            fwrite($this->diag, 'Got these params: '.CRLF
                    .'- Host: '.strval($server).CRLF
                    .'- Port: '.strval($port).CRLF
                    .'- Security: '.strval($conn_sec)
                    .($self_signed ? ' + allow self signed certs' : '')
                    .CRLF
                    );
        }
        if (!is_null($conn_sec) && in_array($conn_sec, array('SSL', 'STARTTLS', 'AUTO', 'none'))) {
            $this->security = $conn_sec;
        }
        $this->reconnect_sleep = !is_null($recon_slp) ? intval($recon_slp) : 0;

        $this->allowSelfSigned = (bool)$self_signed;

        if ($this->connect($server, $port)) {
            $this->server = $server;
            $this->port = $port;
        }
    }

    /**
     * Log in to IMAP server
     * @param string  $user  Username
     * @param string  $pass  Password
     * @param string  $mailbox  Folder to connect to
     * @param bool  $ro  Whether to connect in read-only mode or not
     */
    public function login($user = '', $pass = '', $mailbox = null, $ro = false)
    {
        $return = array('type' => false, 'login' => false);

        // Try to find out about IMAP AUTH and use it on success
        // It's okay to do this again, even if we tried it for STARTTLS since
        // the servers' response to the CAPABILITY command may vary between
        // connected and logged-in state.
        $capa = $this->capa(false);
        $this->server_capa = $capa;
        // Server supports IMAP AUTH... try the supported mechanisms to authenticate
        if (false !== $capa && $capa['sasl'] != false) {
            // Find the mechanisms supported on both sides
            $SASL = array_intersect($this->SASL, $capa['sasl_internal']);
            $this->is_auth = false;
            foreach ($SASL as $v) {
                $function_name = '_auth_'.$v;
                if ($this->{$function_name}($user, $pass)) {
                    $return['login'] = 1;
                    break;
                }
            }
            // Now check, whether the connections has not been closed
            if (!$this->alive()) {
                $this->close();
                sleep($this->reconnect_sleep);
                $this->connect($this->server, $this->port);
                if (!$this->alive()) {
                    return $return;
                }
                if (!$this->is_ssl) {
                    $this->noop();
                }
            }
        }
        // Login; only if NOT disabled
        if (1 != $return['login'] && (false === $capa || !$capa['logindisabled'])) {
            $response = $this->talk('LOGIN', $this->escapeString($user, $pass), true);
            if ($response) {
                $return['login'] = 1;
            } else {
                $this->set_error($response);
                if (!$this->alive()) {
                    return $return;
                }
            }
        }
        if (!is_null($mailbox)) {
			$info = ($ro)
					? $this->examineFolder($mailbox)
			 		: $this->selectFolder($mailbox);
			if (false !== $info) {
				$this->folderInfo = $info;
				$return = array_merge($return, $info);
			}
        }
        $return['type'] = ($this->is_ssl || $this->is_tls) ? 'secure' : 'normal';
        return $return;
    }

    /**
     * Query capability information from the server.
     * @param bool Set to true to receive the unparsed response, Default: false,
     *     which will give you a nice little array of recognized capabilities
     * @return mixed  String on $raw = true, array of recognized features otherwise
     * @since 3.6.0
     */
    public function capa($raw = false)
    {
        $return = array(
                'acl' => false, 'binary' => false, 'children' => false, 'catenate' => false,
                'stls' => false, 'condstore' => false, 'convert' => false, 'enable' => false,
                'esearch' => false, 'id' => false, 'idle' => false, 'literal+' => false,
                'login-referrals' => false, 'logindisabled' => false, 'mailbox-referrals' => false,
                'namespace' => false, 'qresync' => false, 'quota' => false, 'sasl' => false,
                'sasl-ir' => false, 'searchres' => false, 'sort' => false,
                'thread' => false, 'uidplus' => false, 'unselect' => false, 'urlauth' => false
                );
        if (false !== strpos($this->greeting, '[CAPABILITY')) {
            preg_match('!\[(CAPABILITY .+)\]!', $this->greeting, $found);
            $response = explode(' ', $found[1]);
            $this->greeting = preg_replace('!\[(CAPABILITY .+)\]!', '', $this->greeting);
        } else {
            $response = $this->talk('CAPABILITY');
            $response = $response[0];
        }
        if ($raw) {
            return $response;
        }
        array_shift($response); // Drop Command echo from response
        while ($capa = array_shift($response)) {
            $capa = strtolower($capa);
            if ($capa == 'starttls') {
                $return['stls'] = true;
                continue;
            }
            if (substr($capa, 0, 5) == 'auth=') {
                $return['sasl'] = true;
                $return['sasl_internal'][] = str_replace('-', '_', substr($capa, 5));
                continue;
            }
            if (substr($capa, 0, 9) == 'compress=') {
                $return['compress'][] = str_replace('-', '_', substr($capa, 9));
                continue;
            }
            if (substr($capa, 0, 7) == 'thread=') {
                $return['thread'][] = str_replace('-', '_', substr($capa, 7));
                continue;
            }
            if (isset($return[$capa])) {
                $return[$capa] = true;
            }
        }
        return $return;
    }

    // Return LIST, if mail given of this one, else complete
    public function get_list($mail = false, $listonly = false)
    {
        $LBLs = array(1 => '$label1', 2 => '$label2', 3 => '$label3', 4 => '$label4', 5 => '$label5'
                ,6 => '$label6', 7 => '$label7', 8 => '$label8', 9 => '$label9', 10 => '$label10'
                ,11 => '$label11', 12 => '$label12', 13 => '$label13', 14 => '$label14');
        if ($mail) {
            $info = $this->fetch(array('FLAGS', 'RFC822.SIZE', 'UID'), $mail);
            $flags = array();
            foreach ($info['FLAGS'] as $k => $v) {
                $flags[strtolower($v)] = $k;
            }
            $return = array
                    ('size' => $info['RFC822.SIZE']
                    ,'rawflags' => $info['FLAGS']
                    ,'uidl' => $info['UID']
                    ,'recent' => (isset($flags['\recent']))
                    ,'flagged' => (isset($flags['\flagged']))
                    ,'answered' => (isset($flags['\answered']))
                    ,'seen' => (isset($flags['\seen']))
                    ,'draft' => (isset($flags['\draft']))
                    ,'forwarded' => (isset($flags['\forwarded']) || isset($flags['$forwarded']))
                    ,'bounced' => (isset($flags['\bounced']))
                    ,'label' => 0
                    );
            foreach ($LBLs as $off => $lbl) {
                if (isset($flags[$lbl])) {
                    $return['label'] = $off;
                    break;
                }
            }
            return $return;
        } else {
            $return = array();
            // Mailbox is empty?
            if ($this->countMessages() == 0) {
                return $return;
            }
            // Only UIDs to be listed
            if ($listonly) {
                return $this->fetch('UID', 1, INF);
            }
            // Full info requested
            foreach ($this->fetch(array('FLAGS', 'RFC822.SIZE', 'UID'), 1, INF) as $msgno => $info) {
                $flags = array();
                foreach ($info['FLAGS'] as $k => $v) {
                    $flags[strtolower($v)] = $k;
                }
                if (isset($flags['\deleted'])) {
                    continue;
                }
                $return[$msgno] = array
                        ('size' => $info['RFC822.SIZE']
                        ,'rawflags' => $info['FLAGS']
                        ,'uidl' => $info['UID']
                        ,'recent' => (isset($flags['\recent']))
                        ,'flagged' => (isset($flags['\flagged']))
                        ,'answered' => (isset($flags['\answered']))
                        ,'seen' => (isset($flags['\seen']))
                        ,'draft' => (isset($flags['\draft']))
                        ,'forwarded' => (isset($flags['\forwarded']) || isset($flags['$forwarded']))
                        ,'bounced' => (isset($flags['\bounced']))
                        ,'label' => 0
                        );
                foreach ($LBLs as $off => $lbl) {
                    if (isset($flags[$lbl])) {
                        $return[$msgno]['label'] = $off;
                        break;
                    }
                }
            }
            return $return;
        }
    }

    // Get the header lines of a mail
    // DEPRECATED
    public function top($mail = false)
    {
        if (!$mail) {
            $this->set_error('No mail given');
            return false;
        }
        return $this->getRawHeader($mail);
    }

    // Get the Unique ID of a mail
    // DEPRECATED
    public function uidl($mail = false)
    {
        if (!$mail) {
            $this->set_error('No mail given');
            return false;
        }
        return $this->getUniqueId($mail);
    }

    // DEPRECATED
    public function msgno($uidl = false)
    {
        if (!$uidl) {
            $this->set_error('No UID given');
            return false;
        }
        return $this->getNumberByUniqueId($uidl);
    }

    // Get stats of a IMAP box
    // WARNING: The returned data is incompatible to that of the POP3 driver
    public function stat()
    {
        $result = $this->selectFolder($this->currFolder);
        if (false !== $result) {
            return array('mails' => $result['exists'], 'size' => 0);
        } else {
            return false;
        }
    }

    // For compliance with the POP3 class only
    public function reset()
    {
        return true;
    }

    /**
     * Retrieve a mail from server and put into given file
     * @param int  $mail  The msgno of the mail to retrieve
     * @param string  $path  Where to store the file (full path including file name)
     * @return string $path  The path to the file
     */
    public function retrieve_to_file($mail = false, $path = false)
    {
        if (!$mail || !$path) {
            $this->set_error('Usage: retrieve_to_file(integer mail, string path)');
            return false;
        }
        if (!file_exists(dirname($path)) || !is_dir(dirname($path))) {
            $this->set_error('Non existent directory '.dirname($path));
            return false;
        }
        $fh = fopen($path, 'w');
        if (!is_resource($fh)) {
            $this->set_error('Could not open file handle');
            return false;
        }
        $bytes = $this->getRawContent($mail);
        while (true) {
            $line = $this->talk_ml();
            if (false === $line) {
                break; // probably end of file
            }
            $bytes -= strlen($line);
            fputs($fh, $line);
            if ($bytes <= 0) {
                break;
            }
        }
        fclose($fh);
        while (false !== $this->talk_ml()) { /* void */ }
        return $path;
    }

    /**
     * Append a new message to given folder. Use fputs / fwrite for the message
     * body subsequently and $this->finishAppend() to get the result.
     *
     * @param string $folder  name of target folder
     * @param int $length  lenth of the message
     * @param array  $flags   flags for new message
     * @param string $date    date for new message
     * @return resource $
     */
    public function appendMessage($folder, $length, $flags = null, $date = null)
    {
        if ($folder === null) {
            $folder = $this->currFolder;
        }
        // if ($flags === null) $flags = array('\Seen'); # Inspired by user report
        $tokens = array();
        $tokens[] = $this->escapeString($folder);
        if ($flags !== null) {
            $tokens[] = $this->escapeList($flags);
        }
        if ($date !== null) {
            $tokens[] = $this->escapeString($date);
        }
        $tokens[] = '{' . intval($length) . '}';
        $this->sendRequest('APPEND', $tokens);
        if ($this->_assumedNextLine('+ OK', '+')) {
            return $this->fp;
        }
        return false;
    }

    /**
     * copy message(s) from current folder to other folder
     *
     * @param string   $folder destination folder
     * @param int|null $to     if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message avaible
     * @return bool success
     */
    public function copyMessage($folder, $from, $to = null)
    {
        $set = (int)$from;
        if ($to != null) {
            $set .= ':' . ($to === INF ? '*' : (int)$to);
        }
        return $this->talk('COPY', array($set, $this->escapeString($folder)), true);
    }

    /**
     * set flags
     *
     * @param  array       $flags  flags to set, add or remove - see $mode
     * @param  int         $from   message for items or start message if $to !== null
     * @param  int|null    $to     if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message avaible; passing an array makes
     *                              up a comma separated message list consisting of $from,$to[0], $to[1], ...
     * @param  string|null $mode   '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param  bool        $silent if false the return values are the new flags for the wanted messages
     * @return bool|array new flags if $silent is false, else true or false depending on success
     */
    public function setFlags(array $flags, $from, $to = null, $mode = null, $silent = true)
    {
        $item = 'FLAGS';
        if ($mode == '+' || $mode == '-') {
            $item = $mode . $item;
        }
        if ($silent) {
            $item .= '.SILENT';
        }
        $flags = $this->escapeList($flags);
        $set = (int) $from;
        if ($to != null) {
            if (is_array($to) && !empty($to)) {
                $set .= ','.implode(',', $to);
            } else {
                $set .= ':'.($to === INF ? '*' : (int) $to);
            }
        }
        $result = $this->talk('STORE', array($set, $item, $flags), $silent);
        if ($silent) {
            return $result ? true : false;
        }
        $tokens = $result;
        $result = array();
        foreach ($tokens as $token) {
            if ($token[1] != 'FETCH' || $token[2][0] != 'FLAGS') {
                continue;
            }
            $result[$token[0]] = $token[2][1];
        }
        return $result;
    }

    /**
     * Count all messages in current box
     *
     * @return int number of messages
     */
    public function countMessages()
    {
        if (!$this->currFolder) {
            return false;
        }
        // we're reselecting the current mailbox, because STATUS is slow and shouldn't be used on the current mailbox
        $result = $this->selectFolder($this->currFolder);
        return $result['exists'];
    }

    /**
     * get a list of messages with number and size
     *
     * @param int $id number of message
     * @return int|array size of given message of list with all messages as array(num => size)
     */
    public function getSize($id = 0)
    {
        if ($id) {
            return $this->fetch('RFC822.SIZE', $id);
        }
        return $this->fetch('RFC822.SIZE', 1, INF);
    }

    /**
     * Fetch a message
     *
     * @param int $id number of message
     * @return array
     */
    public function getMessage($id)
    {
        $data = $this->fetch(array('FLAGS', 'RFC822.HEADER'), $id);
        $header = $data['RFC822.HEADER'];
        $flags = array();
        foreach ($data['FLAGS'] as $flag) { $flags[] = $flag; }
        return array('id' => $id, 'headers' => $header, 'flags' => $flags);
    }

    /*
     * Get raw header of message or part
     *
     * @param int  $id  number of message
     * @param null|string  $part  path to part or null for messsage header
     * @return string  raw header
     */
    public function getRawHeader($id, $part = null)
    {
        if ($part !== null) {
            return $this->fetch('BODY.PEEK[' . $part . '.HEADER]', $id);
        }
        return $this->fetch('RFC822.HEADER', $id);
    }

    /*
     * Get raw content of message or part
     *
     * @param int  $id  number of message
     * @param null|string  $part  path to part or null for messsage content
     * @return int  Number of bytes to read subsequently with $this->talk_ml()
     */
    public function getRawContent($id, $part = null)
    {
        $bytes = 0;
        $this->sendRequest('FETCH', array($id, 'BODY.PEEK['.($part !== null ? $part : '').']'), $tag);
        // Read ahead out of band responses
        while ($line = $this->talk_ml()) {
            if (false === $line) {
                return 0; // The command failed due to a protocol error;
            }
            if (0 === strpos($line, '* '.$id.' FETCH')) {
                if (preg_match('!\{(\d+)\}$!', trim($line), $found)) {
                    $bytes = $found[1];
                    break;
                }
            }
        }
        return $bytes;
    }

    /**
     * Remove a message / a message set from server. If you're doing that from a web enviroment
     * you should be careful and use a uniqueid as parameter if possible to
     * identify the message.
     *
     * @param  int         $from   message for items or start message if $to !== null
     * @param  int|null    $to     if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message avaible; passing an array makes
     *                             up a comma separated message list consisting of $from,$to[0], $to[1], ...
     * @return null
     */
    public function removeMessage($id, $to = null)
    {
        if (!$this->setFlags(array('\Deleted'), $id, $to, '+')) {
            return false;
        }
        return true;
    }

    /**
     * get unique id for one or all messages
     *
     * if storage does not support unique ids it's the same as the message number
     *
     * @param int|null $id message number
     * @return array|string message number for given message or all messages as array
     */
    public function getUniqueId($id = null)
    {
        if ($id) {
            return $this->fetch('UID', $id);
        }
        return $this->fetch('UID', 1, INF);
    }

    /**
     * get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @return int message number
     */
    public function getNumberByUniqueId($id)
    {
        $result = $this->talk('SEARCH', array('UID', $id));
        if (!empty($result) && isset($result[0][1])) {
            return $result[0][1];
        }
        return false;
    }

    /**
     * Search messages matching given parameters.
     * The parameter array follows the complicated yet flexible IMAP syntax.
     *
     * @param array $param Search criteria and search string
     * @return array message numbers found
     */
    public function searchMessages(array $params)
    {
        $search = '';
        while (count($params)) {
            $search .= trim(array_shift($params)).' ';
        }
        $result = $this->talk('UID SEARCH', array((string) $search));
        if (empty($result) || !isset($result[0][1])) {
            return array();
        }
        array_shift($result[0]);
        return $result[0];
    }

    /**
     * Examine and select have the same response. The common code for both
     * is in this method
     *
     * @param  string $command can be 'EXAMINE' or 'SELECT' and this is used as command
     * @param  string $box which folder to change to or examine
     * @return bool|array false if error, array with returned information otherwise
     *             (flags, exists, recent, uidvalidity, permanentflags, customflags)
     */
    public function examineOrSelectFolder($command = 'EXAMINE', $box = 'INBOX')
    {
        $this->sendRequest($command, array($this->escapeString($box)), $tag);
        $result = array('customflags' => 0);
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch ($tokens[1]) {
            case 'EXISTS':
            case 'RECENT':
                $result[strtolower($tokens[1])] = $tokens[0];
                break;
            case '[UIDVALIDITY':
                $result['uidvalidity'] = (int) $tokens[2];
                break;
            case '[PERMANENTFLAGS':
                $result['permanentflags'] = implode(' ', $tokens[2]);
                $result['permanentflags'] = substr($result['permanentflags'], 0, strpos($result['permanentflags'], ']')-1);
                $result['customflags'] = (strpos($result['permanentflags'], '\*') !== false) ? 1 : 0;
                $result['permanentflags'] = explode(' ', $result['permanentflags']);
                break;
            }
        }
        if ($tokens[0] != 'OK') {
            return false;
        }
        return $result;
    }

    /**
     * change folder
     *
     * @param  string $box change to this folder
     * @return bool|array see examineOrselect()
     */
    public function selectFolder($box = 'INBOX')
    {
        $this->currFolder = $box;
        return $this->examineOrSelectFolder('SELECT', $box);
    }

    /**
     * examine folder
     *
     * @param  string $box examine this folder
     * @return bool|array see examineOrselect()
     */
    public function examineFolder($box = 'INBOX')
    {
        return $this->examineOrSelectFolder('EXAMINE', $box);
    }

    public function status($box = 'INBOX')
    {
        $this->sendRequest('STATUS', array($this->escapeString($box), $this->escapeList(array('MESSAGES', 'RECENT', 'UNSEEN', 'UIDNEXT', 'UIDVALIDITY'))), $tag);
        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'STATUS' && is_array($tokens[2])) {
            	foreach ($tokens[2] as $k => $token) {
            		switch ($token) {
            			case 'MESSAGES': case 'RECENT':
            			case 'UNSEEN': case 'UIDNEXT':
            			case 'UIDVALIDITY':
            				$result[strtolower($token)] = isset($tokens[2][($k+1)]) ? $tokens[2][($k+1)] : 0;
            				break;
            		}
            	}
            }
        }
        return $result;
    }

    /**
     * fetch one or more items of one or more messages
     *
     * @param  string|array $items items to fetch from message(s) as string (if only one item)
     *         or array of strings
     * @param  int          $from  message for items or start message if $to !== null
     *[@param  int|null     $to    if null only one message ($from) is fetched, else it's the
     *         last message, INF means last message avaible]
     * @return string|array if only one item of one message is fetched it's returned as string
     *                      if items of one message are fetched it's returned as (name => value)
     *                      if one items of messages are fetched it's returned as (msgno => value)
     *                      if items of messages are fetchted it's returned as (msgno => (name => value))
     * @throws Exception
     */
    public function fetch($items, $from, $to = null)
    {
        if (is_array($from)) {
            $set = implode(',', $from);
        } elseif ($to === null) {
            $set = (int) $from;
        } elseif ($to === INF) {
            $set = strval(intval($from)) . ':*';
        } else {
            $set = strval(intval($from)) . ':' . strval(intval($to));
        }
        $items = (array)$items;
        $itemList = $this->escapeList($items);
        $this->sendRequest('FETCH', array($set, $itemList), $tag);
        // If we specify the PEEK param, the server won't return it....
        foreach ($items as $k => $v) { if (preg_match('!\.PEEK!', $v)) { $items[$k] = str_replace('.PEEK', '', $v); } }
        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }
            // ignore other messages
            if ($to === null && !is_array($from) && $tokens[0] != $from) {
                continue;
            }
            // if we only want one item we return that one directly
            if (count($items) == 1) {
                if ($tokens[2][0] == $items[0]) {
                    $data = $tokens[2][1];
                } else {
                    // maybe the server sent another field we didn't want
                    $count = count($tokens[2]);
                    // we start with 2, because 0 was already checked
                    for ($i = 2; $i < $count; $i += 2) {
                        if ($tokens[2][$i] != $items[0]) {
                            continue;
                        }
                        $data = $tokens[2][$i + 1];
                        break;
                    }
                }
            } else {
                $data = array();
                while (key($tokens[2]) !== null) {
                    $data[current($tokens[2])] = next($tokens[2]);
                    next($tokens[2]);
                }
            }
            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && $tokens[0] == $from) {
                // we still need to read all lines
                while (!$this->readLine($tokens, $tag));
                return $data;
            }
            $result[$tokens[0]] = $data;
        }
        if ($to === null && !is_array($from)) {
            throw new Exception('the single id was not found in response');
        }
        return $result;
    }

    /**
     * get mailbox list
     *
     * this method can't be named after the IMAP command 'LIST', as list is a reserved keyword
     *
     * @param  string $reference mailbox reference for list
     * @param  string $mailbox   mailbox name match with wildcards
     *[@param  bool  $lsub  Set to true to get the subcribed folders only]
     * @return array mailboxes that matched $mailbox as array(globalName => array('delim' => .., 'flags' => ..))
     */
    public function listMailbox($reference = '', $mailbox = '*', $lsub = false)
    {
        $result = array();
        $command = ($lsub !== false ? 'LSUB' : 'LIST');
        $list = $this->talk($command, $this->escapeString($reference, $mailbox));
        if (!$list) {
            return $result;
        }
        foreach ($list as $item) {
            if (count($item) != 4 || $item[0] != $command) {
                continue;
            }
            $result[$item[3]] = array('delim' => $item[2], 'flags' => $item[1]);
        }
        return $result;
    }


    /**
     * create a new folder
     *
     * This method also creates parent folders if necessary. Some mail storages may restrict, which folder
     * may be used as parent or which chars may be used in the folder name
     *
     * @param string  $name  global name of folder, local name if $parentFolder is set
     * @param string  $parentFolder  parent folder for new folder, else root folder is parent
     * @return bool
     * @todo  Find out correct delimiter first, make it public property or third parameter
     */
    public function createFolder($name, $parentFolder = null)
    {
        if ($parentFolder != null) {
            $folder = $parentFolder.'/'.$name;
        } else {
            $folder = $name;
        }
        return $this->talk('CREATE', array($this->escapeString($folder)), true);
    }


    /**
     * remove a folder
     *
     * @param string  $folder  name or instance of folder
     * @return bool
     */
    public function removeFolder($folder)
    {
        return $this->talk('DELETE', array($this->escapeString($folder)), true);
    }

    /**
     * rename and/or move folder
     *
     * The new name has the same restrictions as in createFolder()
     *
     * @param string  $old  name of folder
     * @param string  $new  new global name of folder
     * @return bool
     */
    public function renameFolder($old, $new)
    {
        return $this->talk('RENAME', $this->escapeString($old, $new), true);
    }

    /**
     * subscribe to a folder
     *
     * @param string  $folder  name of folder
     * @return bool
     */
    public function subscribeFolder($folder)
    {
        return $this->talk('SUBSCRIBE', array($this->escapeString($folder)), true);
    }

    /**
     * unsubscribe from a specific mailbox
     *
     * @param string $folder folder name
     * @return bool success
     */
    public function unsubscribeFolder($folder)
    {
        return $this->talk('UNSUBSCRIBE', array($this->escapeString($folder)), true);
    }

    /**
     * permanently remove messages
     *[@param bool  Pass TRUE to silently drop \Deleted messages without caring for repsonse codes; Default: FALSE]
     * @return bool success
     * @todo  Parse response!
     */
    public function expunge($short = false)
    {
        if ($short) {
            return $this->talk('CLOSE');
        }
        return $this->talk('EXPUNGE');
    }

    /**
     * send noop
     * @return bool success
     * @todo  Parse response!
     */
    public function noop()
    {
        return $this->talk('NOOP');
    }

    /**
     * Not yet supported
     *
     * @param string $root  Where to start fetching the quota, usually INBOX
     * @return mixed $quota Array (see php.net/imap_get_quotaroot) on success or FALSE on failure
     * @since 0.2.3
     */
    public function get_quota($root = 'INBOX')
    {
        /* $quota = @imap_get_quotaroot($this->mbox, $root);
        return (is_array($quota) ? $quota : false); */
    }

    /**
     * @return string current folder
     */
    public function getCurrentFolder()
    {
        return $this->currFolder;
    }

    // Close IMAP connection
    public function close()
    {
        if ($this->connected) {
            $this->talk_auth('LOGOUT', true); // Might prevent a hanging connection
            fclose($this->fp);
        }
        $this->fp = false;
        return true;
    }

    //
    // Private / protected methods
    //

    // Do the actual connect to the chosen server
    protected function connect($host = '', $port = 143)
    {
        // Avoid blocking
        if ($this->connected) {
            $this->close();
        }

        $context = stream_context_create();
        if ($this->allowSelfSigned) {
            stream_context_set_option($context, "ssl", "allow_self_signed", true);
            stream_context_set_option($context, "ssl", "verify_peer", false);
        }

        set_error_handler('protocol_client_error_to_exception');

        $this->connected = $ERRNO = $ERRSTR = false;
        // Try to connect according to security setting
        if ($this->security == 'SSL' || $this->security == 'AUTO') {
            $ssl_host = preg_replace('!^ssl\://!', 'tls://', substr($host, 0, 6) == 'ssl://' ? $host : 'ssl://' . $host);
            $ssl_port = empty($port) ? 993 : $port;
            $this->diag('Trying SSL connection');

            $ssl_capable = function_exists('extension_loaded') && extension_loaded('openssl');
            if (!$ssl_capable) {
                $this->diag('SSL not compiled into PHP!');
            } else {
                try {
                    $fp = stream_socket_client($ssl_host.':'.$ssl_port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
                } catch (Exception $e) {
                    $ERRSTR .= '; Exception: '.$e;
                }
                if (empty($fp)) {
                    $error = 'Connection to ' . $ssl_host . ':' . $ssl_port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')';
                    $this->diag($error);
                    $this->set_error($error);
                } else {
                    $this->is_ssl = true;
                    $this->connected = true;
                }
            }
            if (!$this->connected && $this->security == 'SSL') {
                return false;
            }
        }
        if (!$this->connected && ($this->security == 'STARTTLS' || $this->security == 'AUTO')) {
            $tls_host = (substr($host, 0, 6) == 'ssl://') ? str_replace('ssl://', '', $host) : $host;
            $tls_port = empty($port) ? 143 : $port;
            $this->diag('Trying TLS protected connection');
            $tls_capable = (function_exists('stream_socket_enable_crypto'));
            if (!$tls_capable) {
                if ($this->_diag_session) {
                    fputs($this->diag, 'TLS support not available in PHP!' . LF);
                }
            } else {
                try {
                    $fp = stream_socket_client($tls_host.':'.$tls_port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
                } catch (Exception $e) {
                    $ERRSTR .= '; Exception: '.$e;
                }
                if (empty($fp)) {
                    $error = 'Connection to ' . $tls_host . ':' . $tls_port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')';
                    $this->diag($error);
                    $this->set_error($error);
                } else {
                    $this->fp = $fp;
                    $this->greeting = trim(fgets($fp, 1024));
                    $this->diag($this->greeting);
                    if (strtolower(substr($this->greeting, 0, 4)) != '* ok') {
                        $error = ($this->greeting ? 'IMAP server response: '.$this->greeting : 'Bogus IMAP server behaviour!');
                        $this->diag($error);
                        $this->set_error($error);
                    } else {
                        $capa = $this->capa(false);
                        if (false !== $capa && isset($capa['stls']) && $capa['stls']) {
                            $this->talk('STARTTLS');
                            try {
                                $res = stream_socket_enable_crypto($this->fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                            } catch (Exception $e) {
                                $this->set_error($e->getMessage());
                                return false;
                            }
                            if (!isset($res) || false === $res) {
                                $error = 'Cannot enable TLS, although server advertises it';
                                $this->close();
                                $this->diag($error);
                                $this->set_error($error);
                            } else {
                                $this->is_tls = true;
                                $this->connected = true;
                            }
                        } else {
                            $error = 'Server does not offer STLS or no CAPABILITY at all';
                            $this->diag($error);
                            $this->set_error($error);
                        }
                    }
                }
            }
            if (!$this->connected && $this->security == 'STARTTLS') {
                return false;
            }
        }
        if (!$this->connected && ($this->security == 'none' || $this->security == 'AUTO')) {
            $host = (substr($host, 0, 6) == 'ssl://') ? str_replace('ssl://', '', $host) : $host;
            $port = empty($port) ? 143 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying unprotected connection' . LF);
            }
            $fp = @stream_socket_client($host.':'.$port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
            if (empty($fp)) {
                $error = 'Connection to ' . $host . ':' . $port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')';
                $this->diag($error);
                $this->set_error($error);
            } else {
                $this->connected = true;
            }
        }
        if ($this->connected) {
            restore_error_handler();
            $this->fp = $fp;
            $this->greeting = trim(fgets($fp, 1024));
            if (strtolower(substr($this->greeting, 0, 4)) != '* ok') {
                $error = ($this->greeting ? 'IMAP server response: '.$this->greeting : 'Bogus IMAP server behaviour!');
                $this->diag($error);
                $this->set_error($error);
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    // Try to find out, whether the connection is still alive
    protected function alive()
    {
        return $this->noop();
    }

    /**
     * Implementation of SASL mechanism CRAM-MD5
     *
     * @param string  Username
     * @param string  Password
     * @return boolean  TRUE on successful authentication, FALSE otherwise
     * @access private
     */
    protected function _auth_cram_md5($user = '', $pass = '')
    {
        // See RFC2104 (HMAC, also known as Keyed-MD5)
        $response = $this->talk_auth('AUTHENTICATE CRAM-MD5', true, $tag);
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('md5', $pass, $challenge);
            $response = $this->talk_auth(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3+strlen($tag))) != strtoupper($tag).' OK') {
                $this->error .= 'AUTH CRAM-MD5 failed: '.trim($response).LF;
                return false;
            }
            return true;
        } else {
            $this->error .= 'AUTH CRAM-MD5 rejected: '.trim($response).LF;
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism CRAM-SHA1
     *
     * @param  string  Username
     * @param  string  Password
     * @return  boolean  TRUE on successful authentication, FALSE otherwise
     * @access  private
     */
    protected function _auth_cram_sha1($user = '', $pass = '')
    {
        $response = $this->talk_auth('AUTHENTICATE CRAM-SHA1', true, $tag);
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('sha1', $pass, $challenge);
            $response = $this->talk_auth(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3+strlen($tag))) != strtoupper($tag).' OK') {
                $this->error .= 'AUTH CRAM-SHA1 failed: '.trim($response).LF;
                return false;
            }
            return true;
        } else {
            $this->error .= 'AUTH CRAM-SHA1 rejected: '.trim($response).LF;
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism CRAM-SHA256
     *
     * @param  string  Username
     * @param  string  Password
     * @return  boolean  TRUE on successful authentication, FALSE otherwise
     * @access  private
     */
    protected function _auth_cram_sha256($user = '', $pass = '')
    {
        $response = $this->talk_auth('AUTHENTICATE CRAM-SHA256', true, $tag);
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('sha256', $pass, $challenge);
            $response = $this->talk_auth(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3+strlen($tag))) != strtoupper($tag).' OK') {
                $this->error .= 'AUTH CRAM-SHA256 failed: '.trim($response).LF;
                return false;
            }
            return true;
        } else {
            $this->error .= 'AUTH CRAM-SHA256 rejected: '.trim($response).LF;
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism LOGIN
     *
     * @param  string  Username
     * @param  string  Password
     * @return  boolean  TRUE on successful authentication, FALSE otherwise
     * @access  private
     */
    protected function _auth_login($user = '', $pass = '')
    {
        $response = $this->talk_auth('AUTHENTICATE LOGIN', true, $tag);
        if (substr($response, 0, 2) == '+ ') {
            $response = $this->talk_auth(base64_encode($user));
            if (substr($response, 0, 1) != '+') {
                $this->error .= 'AUTH LOGIN failed, wrong username? Aborting authentication.'.LF;
                return false;
            }
            $response = $this->talk_auth(base64_encode($pass));
            if (strtoupper(substr($response, 0, 3+strlen($tag))) != strtoupper($tag).' OK') {
                $this->error .= 'AUTH LOGIN failed, wrong password? Aborting authentication.'.LF;
                return false;
            }
            return true;
        } else {
            $this->error .= 'AUTH LOGIN rejected: '.trim($response).LF;
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism PLAIN
     *
     * @param  string  Username
     * @param  string  Password
     * @return  boolean  TRUE on successful authentication, FALSE otherwise
     * @access  private
     */
    protected function _auth_plain($user = '', $pass = '')
    {
        $response = $this->talk_auth('AUTHENTICATE PLAIN '.base64_encode(chr(0).$user.chr(0).$pass), true, $tag);
        if (strtoupper(substr($response, 0, 3+strlen($tag))) != strtoupper($tag).' OK') {
            $this->error .= 'AUTH PLAIN failed: '.$response.LF;
            return false;
        }
        return true;
    }

    //
    // Communication layer methods - used to talk to the server, read its responses and encode/decode data accordingly
    //

    /**
     * get the next line from socket
     * @return string next line
     */
    protected function _nextLine()
    {
        if (!is_resource($this->fp)) {
            return false;
        }
        $line = @fgets($this->fp, 4096);
        if ($this->_diag_session) {
            fputs($this->diag, 'S: '.$line);
        }
        return $line;
    }

    /**
     * get next line and assume it starts with $start. some requests give a simple
     * feedback so we can quickly check if we can go on.
     *
     * @param  string $start  the first bytes we assume to be in the next line
     *[@param  string $optstart  Optional "second guess", a string the line could start with, too]
     * @return bool line starts with $start
     */
    protected function _assumedNextLine($start, $optstart = null)
    {
        $line = $this->_nextLine();
        if (strpos($line, $start) === 0) {
            return true;
        }
        if (!is_null($optstart) && strpos($line, $optstart) === 0) {
            return true;
        }
        return false;
    }

    /**
     * get next line and split the tag. that's the normal case for a response line
     *
     * @param  string $tag tag of line is returned by reference
     * @return string next line
     */
    protected function _nextTaggedLine(&$tag)
    {
        $line = $this->_nextLine();
        $line = explode(' ', $line, 2); // seperate tag from line
        $tag = $line[0];
        return isset($line[1]) ? $line[1] : false;
    }

    /**
     * split a given line in tokens. a token is literal of any form or a list
     * @param  string $line line to decode
     * @return array tokens, literals are returned as string, lists as array
     */
    protected function _decodeLine($line)
    {
        $tokens = array();
        $stack = array();
        /* We start to decode the response here. The understood tokens are:
                literal
                "literal" or also "lit\\er\"al"
                {bytes}<NL>literal
                (literals*)
            All tokens are returned in an array. Literals in braces (the last understood
            token in the list) are returned as an array of tokens. I.e. the following response:
                "foo" baz {3}<NL>bar ("f\\\"oo" bar)
            would be returned as:
                array('foo', 'baz', 'bar', array('f\\\"oo', 'bar')); */
        // replace any trailling <NL> including spaces with a single space
        $line = rtrim($line).' ';
        while (($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
            if (!strlen($token)) {
                $token = 0x0;
            }
            while ($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = array();
                $token = substr($token, 1);
            }
            if ($token[0] == '"') {
                if (preg_match('%^"((.|\\\\|\\")*?)"%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, strlen($matches[0]) + 1);
                    continue;
                }
            }
            if ($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos - 1);
                if (is_numeric($chars)) {
                    $token = '';
                    while (strlen($token) < $chars) {
                        $token .= $this->_nextLine();
                    }
                    $line = '';
                    if (strlen($token) > $chars) {
                        $line = substr($token, $chars);
                        $token = substr($token, 0, $chars);
                    } else {
                        $line .= $this->_nextLine();
                    }
                    $tokens[] = $token;
                    $line = trim($line) . ' ';
                    continue;
                }
            }
            if ($stack && $token[strlen($token) - 1] == ')') {
                // closing braces are not seperated by spaces, so we need to count them
                $braces = strlen($token);
                $token = rtrim($token, ')');
                // only count braces if more than one
                $braces -= strlen($token) + 1;
                // only add if token had more than just closing braces
                if ($token) {
                    $tokens[] = $token;
                }
                $token = $tokens;
                $tokens = array_pop($stack);
                // special handling if more than one closing brace
                while ($braces-- > 0) {
                    $tokens[] = $token;
                    $token = $tokens;
                    $tokens = array_pop($stack);
                }
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }
        // maybe the server forgot to send some closing braces
        while ($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }
        return $tokens;
    }

    /**
     * read a response "line" (could also be more than one real line if response has {..}<NL>)
     * and do a simple decode
     *
     * @param  array|string  $tokens    decoded tokens are returned by reference, if $dontParse
     *                                  is true the unparsed line is returned here
     * @param  string        $wantedTag check for this tag for response code. Default '*' is
     *                                  continuation tag.
     * @param  bool          $dontParse if true only the unparsed line is returned $tokens
     * @return bool if returned tag matches wanted tag
     */
    public function readLine(&$tokens = array(), $wantedTag = '*', $dontParse = false)
    {
        $line = $this->_nextTaggedLine($tag);
        $tokens = (!$dontParse) ? $this->_decodeLine($line) : $line;
        // if tag is wanted tag we might be at the end of a multiline response
        return $tag == $wantedTag;
    }

    /**
     * read all lines of response until given tag is found (last line of response)
     *
     * @param  string       $tag       the tag of your request
     * @param  bool         $dontParse if true every line is returned unparsed instead of
     *                                 the decoded tokens
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function readResponse($tag, $dontParse = false)
    {
        $lines = array();
        while (!$this->readLine($tokens, $tag, $dontParse)) {
            $lines[] = $tokens;
        }
        if ($dontParse) {
            // last two chars are still needed for response code
            $tokens = array(substr($tokens, 0, 2));
        }
        // last line has response code
        if ($tokens[0] == 'OK') {
            return $lines ? $lines : true;
        }
        if ($tokens[0] == 'NO') {
            return false;
        }
        return null;
    }

    /**
     * send a request
     *
     * @param  string $command your request command
     * @param  array  $tokens  additional parameters to command, use escapeString() to prepare
     * @param  string $tag     provide a tag otherwise an autogenerated is returned
     * @return null
     */
    public function sendRequest($command, $tokens = array(), &$tag = null)
    {
        if (null === $tokens) {
            $tokens = array();
        }
        if (!$tag) {
            $this->stag = $tag = sprintf('p%03d', $this->scount++);
        }
        $line = $tag.' '.$command;
        foreach ($tokens as $token) {
            if (is_array($token)) {
                @fputs($this->fp, $line.' '.$token[0].CRLF);
                if ($this->_diag_session) {
                    fputs($this->diag, 'C: '.$line.' '.$token[0].CRLF);
                }
                if (!$this->_assumedNextLine('+ OK', '+')) {
                    throw new Exception('cannot send literal string');
                }
                $line = $token[1];
            } else {
                $line .= ' '.$token;
            }
        }
        @fputs($this->fp, $line.CRLF);
        if ($this->_diag_session) {
            fputs($this->diag, 'C: '.$line.CRLF);
        }
    }

    /**
     * send a request and get response at once
     *
     * @param  string $command   command as in sendRequest()
     * @param  array  $tokens    parameters as in sendRequest()
     * @param  bool   $dontParse if true unparsed lines are returned instead of tokens
     * @return mixed response as in readResponse()
     */
    public function talk($command, $tokens = array(), $dontParse = false)
    {
        $this->sendRequest($command, $tokens, $tag);
        return $this->readResponse($tag, $dontParse);
    }

    public function talk_auth($command, $tagMe = false, &$tag = '')
    {
        if (!is_resource($this->fp)) {
            return false;
        }
        if ($tagMe) {
            $tag = sprintf('p%03d', $this->scount++);
            $command = $tag.' '.$command;
        }
        if ($this->_diag_session) {
            fputs($this->diag, 'C: '.$command.CRLF);
        }
        @fputs($this->fp, $command.CRLF);

        // The server may send untagged responses at ANY time, even in the middle
        // of an authentication process (Hello Exchange server...).
        // These will be parsed and handled in a future versin of this class,
        // for now these untagged responses are just dropped...
        while (true) {
            $line = $this->_nextLine();
            if (substr($line, 0, 1) == '*') {
                continue;
            }
            return $line;
        }
    }

    /**
     * Used for streaming calls, where the first initialization just sent a command and maybe
     * checked for a positive response from the server, subsequent lines get requested from
     * here.
     *
     * @return mixed  Returns the line on success, false on end of transmission.
     */
    public function talk_ml()
    {
        if (!is_resource($this->fp)) {
            return false;
        }
        $line = fgets($this->fp, 4096);
        if ($this->_diag_session) {
            fputs($this->diag, 'S: '.$line);
        }
        if (!$line || substr($line, 0, strlen($this->stag)) == $this->stag) {
            return false;
        }
        return $line;
    }

    public function append_ml($line)
    {
        if (!is_resource($this->fp)) {
            return false;
        }
        fwrite($this->fp, $line);
        if ($this->_diag_session) {
            fputs($this->diag, 'C: '.$line);
        }
    }

    /**
     * This method should be used to finalise an APPEND run and to check the
     * success of the APPEND operation.
     *
     * @return bool  TRUE on success, FALSE otherwise
     */
    public function finishAppend()
    {
        $this->append_ml(CRLF.CRLF);
        if ($this->readResponse($this->stag, true)) {
            return true;
        }
        return false;
    }

    /**
     * escape one or more literals i.e. for sendRequest
     *
     * @param  string|array $string the literal(s)
     * @return string|array escape literals, literals with newline are returned
     *                      as array('{size}', 'string');
     */
    public function escapeString($string)
    {
        if (func_num_args() < 2) {
            if (strpos($string, LF) !== false) {
                return array('{'.strlen($string).'}', $string);
            } else {
                return '"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), $string).'"';
            }
        }
        $result = array();
        foreach (func_get_args() as $string) {
            $result[] = $this->escapeString($string);
        }
        return $result;
    }

    /**
     * escape a list with literals or lists
     *
     * @param  array $list list with literals or lists as PHP array
     * @return string escaped list for imap
     */
    public function escapeList($list)
    {
        $result = array();
        foreach ($list as $v) {
            if (!is_array($v)) {
                $result[] = $v;
                continue;
            }
            $result[] = $this->escapeList($v);
        }
        return '('.implode(' ', $result).')';
    }
}
