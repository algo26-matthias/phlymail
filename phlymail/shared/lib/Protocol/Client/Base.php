<?php
/**
 * Protocol client hull class
 *
 * @package phlyMail Nahariya 4.0+
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2015-04-10
 */

if (!function_exists('protocol_client_error_to_exception')) {
    function protocol_client_error_to_exception($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
    {
        throw new Exception($errstr);
    }
}

class Protocol_Client_Base
{
    public $CRLF = "\r\n"; // Define standard line endings (CRLF and LF)
    public $LF   = "\n";
    public $error_nl = 'LF'; // Multiple errors can be returned in either HTML linebreaks or plain LF
    public $append_errors = true;
    public $timestamp_errors = false;
    protected $_diag_session = false;
    protected $error = false;
    protected $fp = false;
    protected $is_tls = false;
    protected $is_ssl = false;
    protected $security = 'SSL';
    protected $connected = false;
    protected $server_capa = false;
    protected $reconnect_sleep = false;

    // List of SASL mechanisms we support
    protected $SASL = array('cram_sha256', 'cram_sha1', 'cram_md5', 'login', 'plain');

    /**
     * Sole aim is, to know, whether we are connected or not
     * since we cannot return something useful on construction
     * of the object
     */
    public function check_connected()
    {
        return $this->connected;
    }

    public function get_last_error()
    {
        $return = ($this->error) ? $this->error : '';
        unset ($this->error);
        return $return;
    }

    // Add or set an (timestamped error), that can be requested via get_last_error()
    protected function set_error($error)
    {
        $vorn = ($this->timestamp_errors) ? time().' ' : '';
        $this->diag($error);
        if ($this->append_errors) {
            $this->error .= $vorn.$error.LF;
        } else {
            $this->error  = $vorn.$error;
        }
    }

    protected function diag($msg)
    {
        if ($this->_diag_session) {
            fputs($this->diag, $msg.LF);
        }
    }

    protected function hmac_xxx($hashFunc, $password, $challenge)
    {
        // Rightpad with NUL bytes to have 64 chars
        if (strlen($password) < 64) {
            $password = $password.str_repeat(chr(0x00), 64 - strlen($password));
        }
        // In case, the secret is longer than 64 chars, hash it
        if (strlen($password) > 64) {
            $password = $hashFunc($password);
        }
        $ipad = str_repeat(chr(0x36), 64);
        $opad = str_repeat(chr(0x5c), 64);
        return bin2hex(pack('H*', $hashFunc(($password ^ $opad).pack('H*', $hashFunc(($password ^ $ipad).$challenge)))));
    }
}
