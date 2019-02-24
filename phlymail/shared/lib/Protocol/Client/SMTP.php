<?php
/**
 * SMTP client class
 * supports transparent SSL connection, if openSSL extension is installed
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Email handler
 * @subpackage Network client connectivity
 * @todo more tolerant against bogus servers (some use the optional initial-response argument for AUTH PLAIN, others do not)
 * @todo Implement DIGEST-MD5 SASL mechanism
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 1.1.6 2015-08-07
 */

class Protocol_Client_SMTP extends Protocol_Client_Base
{
    public $authonly = false; // If set to yes, only valid SMTP AUTH connections will be used
    public $_diag_session = false; // Switch this to true for writing the session to a diagnosis file

    public $server = false;
    public $port = false;

    protected $def_port = 587; // Default port number to use, if not specified
    protected $response  = '';

    private $SrvMaxSize = 0;
    private $SrvAuthMech = array();

    /**
     * The constructor method
     *
     * If called with a server name [and optionally a port number], it tries to
     * connect to that specific server immediately. If called without arguments,
     * you can connect by actually calling the method open_server() and let this
     * negotiate the correct server by itself.
     * If you pass a username and a password here, these will be used for
     * SMTP AUTH (if supported by server).
     *
     * @param string  Servername or IP address
     *[@param integer Port number, Default: 587]
     *[@param string  Username for SMTP AUTH]
     *[@param string  Password for SMTP AUTH]
     *[@param string $conn_sec  One of SSL, STARTTLS, AUTO, none (case sensitive!); Default: SSL]
     */
    public function __construct($server = '', $port = 587, $user = false, $pass = false, $conn_sec = null, $self_signed = false)
    {
        if ($this->_diag_session) {
            $this->diag = fopen(__DIR__.'/smtp_diag.txt', 'w');
            fwrite($this->diag, 'Got these params: '.$this->CRLF
                    .'- Host: '.strval($server).$this->CRLF
                    .'- Port: '.strval($port).$this->CRLF
                    .'- User: '.strval($user).$this->CRLF
                    .'- Pass: '.(is_string($pass) ? str_repeat('*', strlen($pass)) : print_r($pass, true)).$this->CRLF
                    .'- Security: '.strval($conn_sec)
                    .($self_signed ? ' + allow self signed certs' : '')
                    .$this->CRLF
                    );
        }
        if (!is_null($conn_sec) && in_array($conn_sec, array('SSL', 'STARTTLS', 'AUTO', 'none'))) {
            $this->security = $conn_sec;
        }
        $this->server = $server;
        $this->port = $port;
        if ($user) {
            $this->username = $user;
        }
        if ($pass) {
            $this->password = $pass;
        }

        $this->allowSelfSigned = (bool)$self_signed;
    }

    /**
     * Sets a new option value. Available options and values:
     * [authonly - use SMTP AUTH only ('yes', 'no')]
     * [error_nl - use HTML linebreaks ('HTML') or plain LF ('LF')]
     *
     * @param string Parameter to set
     * @param string Value to use
     * @return boolean TRUE on success, FALSE otherwise
     * @access public
     */
    public function set_parameter($option, $value = false)
    {
        switch ($option) {
        case 'authonly':
            $this->authonly = (bool) $value;
            break;
        case 'error_nl':
            $this->error_nl = ('HTML' == $value) ? 'HTML' : 'LF';
            break;
        default:
            $this->set_error('Unknown option '.$option);
            return false;
        }
        return true;
    }

    /**
     * Open a server connection
     *
     * If you've specified username and password on construction, these will be used here,
     * if you specified no server and port on construction, this method will negotiate
     * the server to be used by querying the MX root record for the first TO: address
     * passed.
     * Be aware, that using multiple TO: addresses with a negotiated SMTP server might
     * result in TO: addresses rejected due to server's No-Relay policy
     * This method makes use of the "authonly" setting
     *
     * @param string  FROM: address
     * @param array  TO: address(es)
     * @param int  Size of the message to be transferred in octets
     * @return boolean Returns TRUE on success, FALSE otherwise
     * @access public
     */
    public function open_server($from = false, $to = false, $size = 0)
    {
        if (!$from) {
            $this->set_error('You must specify a from address');
            return false;
        }
        if (!$to) {
            $this->set_error('You must specify at least one recipient address');
            return false;
        }
        if (!is_array($to)) {
            $to = array($to);
        }

        list(,$this->helodomain) = explode('@', $from);

        if ($this->server) {
            // We either use the global setting for the server to use (if given)...
            $mx[0] = &$this->server;
            $port[0] = isset($this->port) ? $this->port : $this->def_port;
            $user[0] = isset($this->username) ? $this->username : false;
            $pass[0] = isset($this->password) ? $this->password : false;
        } else {
            $this->security = 'none'; // Can't use the supplied credentials on foreign servers
            $this->port = 25;         // Non-AUTH connections don't use SMTP submission
            // ... or try to negotiate on our own
            list(,$querydomain) = explode('@', $to[0], 2);
            // On Windows systems this function is not available
            if (!function_exists('getmxrr')) {
                $this->set_error('No SMTP servers for '.$querydomain.' found');
                return false;
            }
            if (getmxrr($querydomain, $mx, $weight) == 0) {
                $this->set_error('No SMTP servers for '.$querydomain.' found');
                return false;
            }
            array_multisort($mx, $weight);
        }
        // Now trying to find one server to talk to... first come, first serve
        foreach ($mx as $id => $host) {
            if (!isset($port[$id])) {
                $port[$id] = $this->def_port;
            }
            // Try connection
            $this->connect($host, $port[$id]);
            // If we can't connect, try next server in list
            if (!$this->connected) {
                continue;
            }
            /**
             * Some servers, namely the qmail ones reject the first line, so we simply try to cycle through
             * the first handshake twice. In case of normal servers we break out of this loop once the
             * handshake was successfull.
             */
            for ($i = 0; $i < 2; ++$i) {
                // Handshake with the server, identify ourselves, get capabilities
                $response = $this->talk('EHLO '.$this->helodomain);
                if (substr($response, 0, 3) == '250') {
                    // Server supports SMTP AUTH... try the supported mechanisms to authenticate
                    $supported = $this->get_supported_sasl_mechanisms($response);
                    // Find the mechanisms supported on both sides
                    $SASL = array_intersect($this->SASL, $supported);
                    $this->diag('Our SASL methods: '.print_r($this->SASL, true));
                    $this->diag('Server\'s SASL methods: '.print_r($supported, true));
                    $this->diag('SASL methods left: '.print_r($SASL, true));
                    // RFC1870
                    $this->SrvMaxSize = $this->get_server_maxsize($response);
                    break;
                } else {
                    if ($i == 0) {
                        continue;
                    }
                    $response = $this->talk('HELO '.$this->helodomain);
                    if (substr($response, 0, 3) != '250') {
                        $this->close();
                        $this->set_error('HELO '.$this->helodomain.' failed. Aborting connection');
                        continue 2;
                    }
                    $SASL = array();
                    $this->SrvMaxSize = 0;
                }
            }
            // Server supports RFC1870 (SIZE extension) and mail size has been given
            if ($size && $this->SrvMaxSize && $size > $this->SrvMaxSize) {
                $this->close();
                $this->set_error('Message size exceeds server\'s known upper limit');
                continue;
            }
            // We've got credentials and try to use SMTP AUTH
            if (isset($user[$id]) && $user[$id]) {
                $this->is_auth = false;
                foreach ($SASL as $v) {
                    $function_name = '_auth_'.$v;
                    if ($this->{$function_name}($user[$id], $pass[$id])) {
                        $this->is_auth = true;
                        break;
                    }
                }
                if (!$this->is_auth && $this->authonly) {
                    $this->close();
                    $this->set_error('SMTP-AUTH failed. Aborting connection');
                    return false;
                }
            }
            if ($this->init_mail_transfer($from, $to, $size)) {
                return true;
            }
        }
        return false;
    }

    protected function init_mail_transfer($from = false, $to = false, $size = 0)
    {
        if (!$from) {
            $this->set_error('You must specify a from address');
            return false;
        }
        if (!$to) {
            $this->set_error('You must specify at least one recipient address');
            return false;
        }
        if (!is_array($to)) {
            $to = array($to);
        }

        $response = $this->talk('MAIL FROM: <'.$from.'>'.($size && $this->SrvMaxSize ? ' SIZE='.intval($size) : ''));
        if (substr($response, 0, 3) != '250') {
            $this->close();
            if (substr($response, 0, 3) == '452') {
                $this->set_error($response);
            } elseif (substr($response, 0, 3) == '552') {
                $this->set_error($response);
            } else {
                $this->set_error('FROM address '.$from.' rejected by server: ');
            }
            return false;
        }
        $accepted = 0;
        foreach ($to as $val) {
            $response = $this->talk('RCPT TO: <'.$val.'>');
            // All return codes of 25* mean, that the address is accepted
            if (substr($response, 0, 2) == '25') {
                $accepted = 1;
            } else {
                $failed[] = $this->LF.'- '.$val.': '.trim($response);
            }
        }
        if (0 == $accepted) {
            $this->close();
            $this->set_error('None of the TO addresses were accepted: '.implode(',', $failed));
            return false;
        }
        $response = $this->talk('DATA');
        if (substr($response, 0, 3) != '354') {
            $this->close();
            $this->set_error('Server rejected the DATA command: '.trim($response));
            return false;
        } else {
            if (isset($failed)) {
                $this->set_error('Some of the TO addresses were rejected: '.implode(',', $failed));
            }
            return true;
        }
    }

    /**
     * Write to the SMTP stream opened before by open_server()
     *
     * @param string Line of data to put to the stream
     * @return boolean Returns TRUE on success, FALSE otherwise
     * @access public
     */
    public function put_data_to_stream($line = false)
    {
        if (!is_resource($this->fp)) {
            return false;
        }
        if (!$line) {
            return false;
        }
        if ($this->_diag_session) {
            fwrite($this->diag, 'C:'.$line);
        }
        $line = rtrim($line, CRLF);
        if ($line == '.') {
            $line = '..';
        }
        fwrite($this->fp, $line.$this->CRLF);
        return true;
    }

    /**
     * Finishing a mail transfer to the server
     * Use this method, if your application doesn't automatically
     * put the final CRLF.CRLF to the SMTP stream after
     * putting al the mail data to it.
     * This method implicitly calls check_success().
     *
     * @param  void
     * @return boolean Return state of check_success()
     * @access public
     */
    public function finish_transfer()
    {
        fwrite($this->fp, $this->CRLF.'.'.$this->CRLF);
        if ($this->_diag_session) {
            fwrite($this->diag, 'C: '.$this->CRLF.'C: .'.$this->CRLF);
        }
    }

    /**
     * Call this method after putting your last mail line to the server
     *
     * @param void
     * @return boolean Returns TRUE on success, FALSE otherwise
     * @access public
     */
    public function check_success()
    {
        if (!is_resource($this->fp) || feof($this->fp)) {
            $line = '999 SMTP server connection already died';
        } else {
            $line = fgets($this->fp, 4096);
            if ($this->_diag_session) {
                fwrite($this->diag, 'S: '.$line);
            }
        }
        if (substr($line, 0, 3) != '250') {
            $this->set_error('Wrong DATA: '.trim($line));
            return false;
        }
        return true;
    }

    /**
     * Talk to the SMTP server directly (for things not covered by this class)
     *
     * @param  string Command to pass to the server
     * @return string Answer of the server
     * @access public
     */
    public function talk($input = null, $oneliner = false)
    {
        $output = '';
        if (!is_null($input)) {
            if ($this->_diag_session) {
                fputs($this->diag, 'C: '.$input.$this->CRLF);
            }
            fputs($this->fp, $input.$this->CRLF);
        }
        $end = 0;
        while (0 == $end && is_resource($this->fp)) {
            $line = fgets($this->fp, 4096);
            if ($this->_diag_session) {
                fputs($this->diag, 'S: '.$line);
            }
            if ($oneliner) {
                $end = 1;
            }
            if (' ' == substr($line, 3, 1)) {
                $end = 1;
            }
            $output .= $line;
        }
        return $output;
    }

    /**
     * Close a previously opened connection
     * Although it doesn't return you something, you can query the state by using
     * get_last_error()
     *
     * @param  void
     * @return void
     * @access public
     */
    public function close()
    {
        $error = '';
        if (is_resource($this->fp)) {
            $this->talk('QUIT');
            fclose($this->fp);
            $this->fp = false;
            $error = 'Connection closed';
        } else {
            $error = 'No connection to close. Did nothing.';
        }
        $this->set_error($error);
    }

    /**
     * Open socket to an SMTP server
     *
     * @param    string    Server name or IP address
     * @param    integer   Port number
     * @return   boolean   TRUE on success, FALSE otherwise
     * @access   protected
     */
    protected function connect($host = '', $port = 587)
    {
        // Avoid blocking
        if ($this->connected) {
            $this->close();
        }

        $host = strtolower($host);

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
            $ssl_port = empty($port) ? 465 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying TLS connection' . LF);
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
                    $this->set_error('Connection to ' . $ssl_host . ':' . $ssl_port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')');
                } else {
                    $this->fp = $fp;
                    $response = $this->talk(null);
                    if (!$response || substr($response, 0, 3) != '220') {
                        $this->close();
                        $this->set_error('Connecting to '.$ssl_host.':'.$ssl_port.' failed ('.$response.')');
                        $this->fp = false;
                    } else {
                        $this->is_ssl = true;
                        $this->connected = true;
                    }
                }
            }
            if (!$this->connected && $this->security == 'SSL') {
                return false;
            }
        }
        if (!$this->connected && ($this->security == 'STARTTLS' || $this->security == 'AUTO')) {
            $tls_host = (substr($host, 0, 6) == 'ssl://') ? str_replace('ssl://', '', $host) : $host;
            $tls_port = empty($port) ? 587 : $port;
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
                    $this->set_error('Connection to ' . $tls_host . ':' . $tls_port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')');
                } else {
                    $this->fp = $fp;
                    $response = $this->talk(null);
                    if (!$response || substr($response, 0, 3) != '220') {
                        $this->close();
                        $this->set_error('Connecting to '.$tls_host.':'.$tls_port.' failed ('.$response.')');
                        $this->fp = false;
                    } else {
                        // Some servers fail to read the first command sent to them correctly
                        // Thus we simply try twice if needed
                        for ($i = 0; $i < 2; ++$i) {
                            $response = $this->talk('EHLO ' . $this->helodomain);
                            if (substr($response, 0, 3) == '250') {
                                break;
                            }
                        }
                        $response = $this->talk('STARTTLS');
                        try {
                            $res = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                        } catch (Exception $e) {
                            $this->set_error($e->getMessage());
                            return false;
                        }
                        if (!isset($res) || false === $res) {
                            $this->set_error('Cannot enable TLS');
                            return false;
                            // Close won't work, when just validating the Cert failed
                            // $this->close();
                        } else {
                            $this->is_tls = true;
                            $this->connected = true;
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
            $port = empty($port) ? 587 : $port;
            if ($this->_diag_session) {
                fputs($this->diag, 'Trying unprotected connection' . LF);
            }
            $fp = @stream_socket_client($host.':'.$port, $ERRNO, $ERRSTR, 5, STREAM_CLIENT_CONNECT, $context);
            if (!$fp) {
                $this->set_error('Connection to ' . $host . ':' . $port . ' failed: ' . $ERRSTR . ' (' . $ERRNO . ')');
            } else {
                $this->fp = $fp;
                $response = $this->talk(null);
                if (!$response || substr($response, 0, 3) != '220') {
                    $this->close();
                    $this->set_error('Connecting to ' . $host . ':' . $port . ' failed (' . $response . ')');
                    $this->fp = false;
                } else {
                    $this->connected = true;
                }
            }
        }
        if ($this->connected) {
            restore_error_handler();
            return true;
        }
        return false;
    }

    /**
     * Find out about SASL mechanisms a specific SMTP server supports
     *
     * @param    string    Server answer to EHLO command
     * @return   array     list of supported SASL mechanisms
     * @access   private
     */
    private function get_supported_sasl_mechanisms($response)
    {
        if (preg_match('!^250(\ |\-)AUTH(\ |\=)([\w\s-_]+)$!Umi', $response, $found)) {
            $found[3] = strtolower(str_replace('-',  '_',  trim($found[3])));
            return explode(' ', $found[3]);
        }
        return array();
    }

    /**
     * Negotiate, whether this server supports RFC1870 (Message Size Declaration)
     *
     * @param    string    Server answer to EHLO command
     * @return   int    maximum size, defaults to 0 if not known or not supported
     * @access   private
     */
    private function get_server_maxsize($response)
    {
        if (preg_match('!^250(\ |\-)SIZE(\ |\=)([0-9]+)$!Umi', $response, $found)) {
            return $found[3];
        }
        return 0;
    }

    private function get_server_has_tls($response)
    {
        return preg_match('!^250(\ |\-)STARTTLS!Umi', $response);
    }

    /**
     * Implementation of SASL mechanism CRAM-MD5
     *
     * @param    string    Username
     * @param    string    Password
     * @return   boolean   TRUE on successful authentication, FALSE otherwise
     * @access   private
     */
    private function _auth_cram_md5($user = '', $pass = '')
    {
        // See RFC2104 (HMAC, also known as Keyed-MD5)
        $response = $this->talk('AUTH CRAM-MD5');
        if (substr($response, 0, 3) == '334') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 4));
            $shared = $this->hmac_xxx('md5', $pass, $challenge);

            $response = $this->talk(base64_encode($user.' '.$shared));
            if (substr($response, 0, 3) != '334' && substr($response, 0, 3) != '235') {
                $this->set_error('AUTH CRAM-MD5 failed:'.trim($response));
                return false;
            }
            return true;
        } else {
            $this->set_error('AUTH CRAM-MD5 rejected: '.trim($response));
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism CRAM-SHA1
     *
     * @param    string    Username
     * @param    string    Password
     * @return   boolean   TRUE on successful authentication, FALSE otherwise
     * @access   private
     */
    private function _auth_cram_sha1($user = '', $pass = '')
    {
        $response = $this->talk('AUTH CRAM-SHA1');
        if (substr($response, 0, 3) == '334') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 4));
            $shared = $this->hmac_xxx('sha1', $pass, $challenge);
            $response = $this->talk(base64_encode($user.' '.$shared));
            if (substr($response, 0, 3) != '334' && substr($response, 0, 3) != '235') {
                $this->set_error('AUTH CRAM-SHA1 failed:'.trim($response));
                return false;
            }
            return true;
        } else {
            $this->set_error('AUTH CRAM-SHA1 rejected: '.trim($response));
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism CRAM-SHA256
     *
     * @param    string    Username
     * @param    string    Password
     * @return   boolean   TRUE on successful authentication, FALSE otherwise
     * @access   private
     */
    private function _auth_cram_sha256($user = '', $pass = '')
    {
        // See RFC2104 (HMAC, also known as Keyed-MD5)
        $response = $this->talk('AUTH CRAM-SHA256');
        if (substr($response, 0, 3) == '334') {
            // Get the challenge from the server
            $challenge = base64_decode(substr(trim($response), 4));
            $shared = $this->hmac_xxx('sha256', $pass, $challenge);
            $response = $this->talk(base64_encode($user.' '.$shared));
            if (substr($response, 0, 3) != '334' && substr($response, 0, 3) != '235') {
                $this->set_error('AUTH CRAM-SHA256 failed:'.trim($response));
                return false;
            }
            return true;
        } else {
            $this->set_error('AUTH CRAM-SHA256 rejected: '.trim($response));
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism LOGIN
     *
     * @param    string    Username
     * @param    string    Password
     * @return   boolean   TRUE on successful authentication, FALSE otherwise
     * @access   private
     */
    private function _auth_login($user = '', $pass = '')
    {
        $response = $this->talk('AUTH LOGIN');
        if (substr($response, 0, 3) == '334') {
            $response = $this->talk(base64_encode($user));
            if (substr($response, 0, 3) != '334') {
                $this->set_error('AUTH LOGIN failed, wrong username? Aborting authentication.');
                return false;
            }
            $response = $this->talk(base64_encode($pass));
            if (substr($response, 0, 3) != '235') {
                $this->set_error('AUTH LOGIN failed, wrong password? Aborting authentication.');
                return false;
            }
            return true;
        } else {
            $this->set_error('AUTH LOGIN rejected: '.trim($response));
            return false;
        }
    }

    /**
     * Implementation of SASL mechanism PLAIN
     *
     * @param    string    Username
     * @param    string    Password
     * @return   boolean   TRUE on successful authentication, FALSE otherwise
     * @access   private
     */
    private function _auth_plain($user = '', $pass = '')
    {
        $response = $this->talk('AUTH PLAIN '.base64_encode(chr(0).$user.chr(0).$pass));
        if (substr($response, 0, 3) != '235') {
            $this->set_error('AUTH PLAIN failed. Aborting authentication.');
            return false;
        }
        return true;
    }
}