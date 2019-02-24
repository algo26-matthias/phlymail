<?php
/**
 * POP3 client connector class with transparent SSL support
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Email handler
 * @subpackage Network client connectivity
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 3.8.7 2015-12-16
 */

class Protocol_Client_POP3 extends Protocol_Client_Base
{
    protected $_diag_session = false;

    /**
     * Constructor
     *
     * @param string $server Server name (or IP address) to connect to
     * @param int $port  Port number, default: 110 (SSL: 995)
     * @param int $recon_slp  Time in seconds to wait between disconnect and next connection attempt
     * @param string $conn_sec  One of SSL, STARTTLS, AUTO, none (case sensitive!); Default: SSL
     */
    public function __construct($server, $port = 110, $recon_slp = null, $conn_sec = null, $self_signed = false)
    {
        if ($this->_diag_session) {
            $this->diag = fopen(__DIR__.'/pop3_diag.txt', 'a');
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
        if (!$port) {
            $port = ($conn_sec == 'SSL' || $conn_sec == 'AUTO') ? 995 : 110;
        }
        $this->reconnect_sleep = !is_null($recon_slp) ? intval($recon_slp) : 0;

        $this->allowSelfSigned = (bool)$self_signed;

        if ($this->connect($server, $port)) {
            $this->server = $server;
            $this->port = $port;
        }
    }

    /**
     * Log in to POP3 server
     * @param string $username
     * @param string $password
     * @param int $apop  Set to 1 to allow APOP, set to 0 to disallow even if advertised by server
     */
    public function login($username = '', $password = '', $apop = 1)
    {
        $return = array('type' => false, 'login' => false);
        // Issue empty command - some POP3 server constantly drop the first command sent to them
        // This is somewhat violating RFC1939 but who is mistaking here?
        // Mainly to blame is qmail at this point ...
        if (!$this->is_ssl) {
            $this->noop();
        }
        // Try to find out about POP3 AUTH and use it on success
        $capa = $this->capa(false);
        // Server supports POP3 AUTH... try the supported mechanisms to authenticate
        if (false !== $capa && $capa['sasl'] != false) {
            // Find the mechanisms supported on both sides
            $SASL = array_intersect($this->SASL, $capa['sasl_internal']);
            $this->is_auth = false;
            foreach ($SASL as $v) {
                $function_name = '_auth_'.$v;
                if ($this->{$function_name}($username, $password)) {
                    $return['login'] = 1;
                    $return['type'] = 'secure';
                    return $return;
                }
            }
            // Now check, whether the connections has not been closed
            if (!$this->alive()) {
                $this->set_error('Connection got lost');
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
        // APOP
        if (!$this->is_ssl && !$this->is_tls && 1 != $return['login']
                && preg_match('/(<.+@.+>)$/', $this->greeting, $token) && $apop == 1) {
            $response = $this->talk('APOP '.$username.' '.md5($token[1].$password));
            if (strtolower(substr($response, 0, 3)) == '+ok') {
                $return['login'] = 1;
                $return['type'] = 'secure';
                return $return;
            }
            // APOP failed due to bogus server advertising
            // Now check, whether the connections has not been closed
            if (!$this->alive()) {
                $this->set_error($response);
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
        // USER/PASS
        if (1 != $return['login']) {
            $response = $this->talk('USER '.$username);
            if (strtolower(substr($response, 0, 4)) == '-err') {
                $this->set_error($response);
                if (!$this->alive()) {
                    return $return;
                }
            }
            $response = $this->talk('PASS '.$password);
            if (strtolower(substr($response, 0, 3)) == '+ok') {
                $return['login'] = 1;
                $return['type'] = 'normal';
            } else {
                $this->set_error($response);
                if (!$this->alive()) {
                    return $return;
                }
            }
        }
        return $return;
    }

    /**
     * Allows to specifically query the server for a capabilites list. As defined
     * in RFC2449, only two server reactions are possible:
     * +OK <Multiline capabilites reponse> and -ERR, where the latter means, that
     * this server does not support this command. The multiline response can
     * reveal some useful information about the type of mailserver, the retention
     * policy and supported SASL mechanisms, if any.
     *
     * @param bool Set to true to receive the unparsed response, Default: false,
     *     which will give you a nice little array of recognized capabilities
     * @return mixed  String on $raw = true, array of recognized features otherwise
     * @since 3.6.0
     */
    public function capa($raw = false)
    {
        $return = array('top' => false, 'user' => false, 'uidl' => false,
                'stls' => false, 'sasl' => false, 'login-delay' => 0,
                'expire' => 'never', 'implementation' => 'unknown',
                'resp-codes' => false, 'pipelining' => false);
        $response = $this->talk('CAPA');
        if ('+ok' == strtolower(substr($response, 0, 3))) {
            if ($raw) {
                $return = $response;
                while ($line = $this->talk_ml()) {
                    $return .= $line;
                }
                return $return;
            }
            while ($line = $this->talk_ml()) {
                $capa = explode(' ', trim($line), 2);
                $capa[0] = strtolower($capa[0]);
                switch ($capa[0]) {
                    case 'top':
                    case 'user':
                    case 'uidl':
                    case 'stls':
                    case 'resp-codes':
                    case 'pipelining':
                        $return[$capa[0]] = true;
                        break;
                    case 'implementation':
                    case 'login-delay':
                    case 'expire':
                        $return[$capa[0]] = $capa[1];
                        break;
                    case 'sasl':
                        if (!empty($capa[1])) {
                            $return[$capa[0]] = explode(' ', $capa[1]);
                            $return['sasl_internal'] = explode(' ', strtolower(str_replace('-', '_', $capa[1])));
                        }
                        break;
                }
            }
            return $return;
        } else {
            $response = trim($response);
            if (!$response) {
                return $return;
            }

            $this->set_error('POP server response: '.$response);
            return false;
        }
    }

    /**
     * Return LIST, if mail given of this one, else complete
     *
     *[@param int $mail  Number of the mail in the list to get info about]
     * @return array|false  Array data on succes, false on failure
     */
    public function get_list($mail = false)
    {
        if ($mail) {
            $line = explode(' ', $this->talk('LIST '.$mail));
            if ('+ok' == strtolower($line[0])) {
                return array(
                        'size' => $line[2], 'recent' => true,
                        'flagged' => false, 'answered' => false,
                        'seen' => false, 'draft' => false
                        );
            } else {
                $this->set_error('POP server response: '.implode(' ', $line));
                return false;
            }
        } else {
            $line = explode(' ', $this->talk('LIST'));
            if ('+ok' == strtolower($line[0])) {
                $return = array();
                while ($line = $this->talk_ml()) {
                    list($nummer, $bytes) = explode(' ', trim($line), 2);
                    $return[$nummer] = array
                            ('size' => $bytes, 'recent' => true
                            ,'flagged' => false, 'answered' => false
                            ,'seen' => false, 'draft' => false
                            );
                }
                foreach (array_keys($return) as $num) {
                    $return[$num]['uidl'] = $this->uidl($num);
                }
                return $return;
            } else {
                $this->set_error('POP server response: '.implode(' ', $line));
                return false;
            }
        }
    }

    // Get the header lines of a mail
    public function top($mail)
    {
        $return = '';
        $response = explode(' ', $this->talk('TOP '.$mail.' 0'));
        if ('+ok' == strtolower($response[0])) {
            while ($line = $this->talk_ml()) {
                $return .= $line;
            }
            return $return;
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    // Get the Unique ID of a mail
    public function uidl($mail)
    {
        $response = explode(' ', $this->talk('UIDL '.$mail));
        if ('+ok' == strtolower($response[0])) {
            return $response[2];
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    // Get stats of a POP3 box
    public function stat()
    {
        // $return = array('mails' => false, 'size' => false);
        $response = explode(' ', $this->talk('STAT'));
        if ('+ok' == strtolower($response[0])) {
            return array('mails' => $response[1], 'size' => $response[2]);
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    // Delete a selected Email from POP3 server
    public function delete($mail)
    {
        $response = explode(' ', $this->talk('DELE '.$mail));
        if ('+ok' == strtolower($response[0])) {
            return true;
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    /**
     * Just an alias of delete()
     *
     * @param int $mail  Number of the mail
     * @return bool
     * @see delete()
     */
    public function removeMessage($mail)
    {
        return $this->delete($mail);
    }

    /**
     * Takes a list of UIDLs, which are to delete from the server's mailbox
     * @param  array  values are the UIDLs
     * @return  mixed  TRUE on success, FALSE on failures, array of unknown UIDLs
     * @since 3.1.9
     */
    public function delete_by_uidl($uidls)
    {
        if (!$uidls || empty($uidls)) {
            return true;
        }
        $response = explode(' ', $this->talk('UIDL'));
        if ('-err' == strtolower($response[0])) {
            return $uidls;
        }

        $check = array();
        while ($line = $this->talk_ml()) {
            $serv = explode(' ', trim($line));
            $check[$serv[0]] = $serv[1];
        }
        if (empty($check)) {
            return $uidls;
        }
        $return = array();
        foreach ($uidls as $uidl) {
            $hit = array_search($uidl, $check);
            if ($hit) {
                $this->delete($hit);
            } else {
                $return[] = $uidl;
            }
        }
        return (empty($return)) ? true : $return;
    }

    // Do nothing.
    // Since RFC1939 requires a positive response, we don't care about errors yet
    public function noop()
    {
        $this->talk('NOOP');
        return true;
    }

    // Unmark any mails marked as deleted.
    // Since RFC1939 requires a positive response, we don't care about errors yet
    public function reset()
    {
        $this->talk('RSET');
        return true;
    }

    // Send RETR command to POP3 server
    // Get subsequent server responses via talk_ml()
    public function retrieve($mail)
    {
        $response = explode(' ', $this->talk('RETR '.$mail));
        if ('+ok' == strtolower($response[0])) {
            return true;
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    // Retrieve a mail from server and put into given file
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
        $out = fopen($path, 'w');
        if (!$out) {
            $this->set_error('Could not open file '.$path);
            return false;
        }
        $response = explode(' ', $this->talk('RETR '.$mail));
        if ('+ok' == strtolower($response[0])) {
            while (true) {
                $line = $this->talk_ml();
                if (false === $line) {
                    break;
                }
                fputs($out, $line);
            }
            fclose($out);
            return $path;
        } else {
            $this->set_error('POP server response: '.implode(' ', $response));
            return false;
        }
    }

    // Send command to POP3 server and return first line of response
    public function talk($input = false)
    {
        if (!$input) {
            return false;
        }
        if ($this->_diag_session) {
            fputs($this->diag, 'C: '.$input.CRLF);
        }
        fputs($this->fp, $input.CRLF);
        $line = fgets($this->fp, 4096);
        if ($this->_diag_session) {
            fputs($this->diag, 'S: '.$line);
        }
        return trim($line);
    }

    // Return a line of multiline POP3 responses, return false on last line
    public function talk_ml()
    {
        $line = fgets($this->fp, 1024);
        if ($this->_diag_session) {
            fputs($this->diag, 'S: '.$line);
        }
        if (isset($line{0}) && $line{0} == '.') {
            $line = substr($line, 1);
            if (CRLF == $line) {
                return false;
            }
            return $line;
        }
        return $line;
    }

    // Close POP3 connection
    public function close()
    {
        $this->talk('QUIT');
        fclose($this->fp);
        $this->fp = false;
        return true;
    }

    //
    // internal methods
    //

    // Do the actual connect to the chosen server
    protected function connect($host = '', $port = 110)
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
            $ssl_port = ($port == 110) ? 995 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying SSL connection' . LF);
            }
            $ssl_capable = function_exists('extension_loaded') && extension_loaded('openssl');
            if (!$ssl_capable) {
                if ($this->_diag_session) {
                    fputs($this->diag, 'SSL not compiled into PHP!' . LF);
                }
            } else {
                try {
                    $fp = stream_socket_client($ssl_host.':'.$ssl_port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
                } catch (Exception $e) {
                    $ERRSTR .= '; Exception: '.$e;
                }
                if (empty($fp)) {
                    $error = 'Connection to ' . $ssl_host . ':' . $ssl_port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')';
                    if ($this->_diag_session) {
                        fputs($this->diag, $error . LF);
                    }
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
            $tls_port = ($port == 995) ? 110 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying TLS protected connection' . LF);
            }
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
                    if ($this->_diag_session) {
                        fputs($this->diag, $error . LF);
                    }
                    $this->set_error($error);
                } else {
                    $this->fp = $fp;
                    $this->greeting = trim(fgets($fp, 1024));
                    if (strtolower(substr($this->greeting, 0, 3)) != '+ok') {
                        $error = ($this->greeting ? 'POP3 server response: '.$this->greeting : 'Bogus POP3 server behaviour!');
                        if ($this->_diag_session) {
                            fputs($this->diag, $error.LF);
                        }
                        $this->set_error($error);
                    } else {
                        $capa = $this->capa(false);
                        if (false !== $capa && isset($capa['stls']) && $capa['stls']) {
                            $this->talk('STLS');
                            try {
                                $res = stream_socket_enable_crypto($this->fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                            } catch (Exception $e) {
                                $this->set_error($e->getMessage());
                                return false;
                            }
                            if (!isset($res) || false === $res) {
                                $this->set_error('Cannot enable TLS');
                                return false;
                            } else {
                                $this->is_tls = true;
                                $this->connected = true;
                            }
                        } else {
                            $error = 'Server does not offer STLS or no CAPABILITY at all';
                            if ($this->_diag_session) {
                                fputs($this->diag, $error . LF);
                            }
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
            $port = ($port == 995) ? 110 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying unprotected connection' . LF);
            }
            $fp = @stream_socket_client($host.':'.$port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
            if (empty($fp)) {
                $error = 'Connection to ' . $host . ':' . $port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')';
                if ($this->_diag_session) {
                    fputs($this->diag, $error . LF);
                }
                $this->set_error($error);
            } else {
                $this->connected = true;
            }
        }
        if ($this->connected) {
            restore_error_handler();
            $this->fp = $fp;
            $this->greeting = trim(fgets($fp, 1024));
            if (strtolower(substr($this->greeting, 0, 3)) != '+ok') {
                $error = ($this->greeting ? 'POP3 server response: '.$this->greeting : 'Bogus POP3 server behaviour!');
                if ($this->_diag_session) {
                    fputs($this->diag, $error.LF);
                }
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
        // Invalid or non-existent handler
        if (!$this->fp || !is_resource($this->fp)) {
            return false;
        }
        $response = @socket_get_status($this->fp);
        if (!$response || $response['timed_out']) {
            return false;
        }
        return true;
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
        $response = $this->talk('AUTH CRAM-MD5');
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('md5', $pass, $challenge);
            $response = $this->talk(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3)) != '+OK') {
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
        $response = $this->talk('AUTH CRAM-SHA1');
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('sha1', $pass, $challenge);
            $response = $this->talk(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3)) != '+OK') {
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
        $response = $this->talk('AUTH CRAM-SHA256');
        if (strtoupper(substr($response, 0, 2)) == '+ ') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 2));
            $shared = $this->hmac_xxx('sha256', $pass, $challenge);
            $response = $this->talk(base64_encode($user.' '.$shared));
            if (strtoupper(substr($response, 0, 3)) != '+OK') {
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
        $response = $this->talk('AUTH LOGIN');
        if (substr($response, 0, 2) == '+ ') {
            $response = $this->talk(base64_encode($user));
            if (substr($response, 0, 1) != '+') {
                $this->error .= 'AUTH LOGIN failed, wrong username? Aborting authentication.'.LF;
                return false;
            }
            $response = $this->talk(base64_encode($pass));
            if (strtoupper(substr($response, 0, 3)) != '+OK') {
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
        $response = $this->talk('AUTH PLAIN '.base64_encode(chr(0).$user.chr(0).$pass));
        if (substr($response, 0, 3) != '+OK') {
            $this->error .= 'AUTH PLAIN failed: '.$response.LF;
            return false;
        }
        return true;
    }
}
