<?php
/**
 * phm_shortmessage.php -> Send SMS through msggw.phlymail.de
 *
 * This class provides most of the useful methods to communicate with
 * phlyMail.com's Short Message Gateway.
 * This includes the essential send methods, tests and reportings.
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @author Matthias Sommerfeld
 * @copyright 2010-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.3 2012-06-14 
 */
class phm_shortmessage
{
    public $append_errors = false;
    public $timestamp_errors = false;
    private $error = false;

    /**
     * constructor method
     *
     * @param  string  $path  Path to the settings file (msggws.ini.php)
     * @param  string  $credentials  File name of the credentials file
     */
    public function __construct($path, $credentials)
    {
        $gwini = $path.'/msggws.ini.php';
        if (!file_exists($gwini) || !is_readable($gwini) || !file_exists($credentials) || !is_readable($credentials)) {
            return false;
        }
        // init random number generator
        srand ((double) microtime() * 1000000);
        // Read config
        $this->gwini = parse_ini_file($gwini, true);
        $credentials = parse_ini_file($credentials);
        $this->user = deconfuse($credentials['sms_user'], $credentials['sms_stamp']);
        $this->pass = deconfuse($credentials['sms_pass'], $credentials['sms_user']);
        $this->responses = array
                (100 => 'Message sent successfully'
                ,101 => 'Message sent successfully, split into more than one part'
                ,200 => 'Username or Password wrong'
                ,301 => 'Uplink provider unreachable'
                ,400 => 'Wrong input format, some arguments missing'
                ,401 => 'SMS text too long'
                ,402 => 'attachment too large'
                ,500 => 'Your prepaid limit has exceeded'
                ,600 => 'Other error'
                );
        $this->srvr2resp = array
                ('100' => 100, '101' => 101, '201' => 401, '202' => 400, '203' => 500, '402' => 200, '411' => 301);
        return true;
    }

    public function test()
    {
        $return = $this->handshake(array('mode' => 'test'));
        $return = explode(' ', $return, 2);
        if ($return[0] == 100) return '1';
        if ($return[0] == 410) return '-';
        return '0';
    }

    /**
     * Query the current usage stats. If you pass a uid to it, only the appropriate data is
     * returned (Will work only when outgoing SMS are sent with a uid).
     *
     * @param  string  User id (optional)
     */
    public function stats($uid = false)
    {
        $pass = array('mode' => 'stats');
        if ($uid) $pass['uid'] = $uid;
        return $this->handshake($pass);
    }

    /**
     * Enhanced as of v4.1 to get deposit and prices from the gateway
     *
     *[@param bool $enhanced If true, return all available stats; FALSE for SMS limit only; Default: FALSE]
     * @return unknown
     */
    public function synchro($enhanced = false)
    {
        $keys = array('Deposit' => 'deposit', 'SMS' => 'sms', 'UDH' => 'udh', 'Unicode' => 'utf', 'Fax' => 'fax', 'Month' => 'monthly');
        $return = array();
        $gw = $this->handshake(array('mode' => 'synchro'));
        $gw = explode(LF, $gw); // All result lines
        if (empty($gw)) return false; // sth. went wrong
        $classic = explode(' ', $gw[0], 2); // First line has limit
        if (!$enhanced) {
            if ($classic[0] == 410) return '-';
            if ($classic[0] == 100) return $classic[1];
            return 'no';
        }
        foreach ($gw as $line) {
            if (isset($keys[$line[0]])) $return[$keys[$line[0]]] = array($line[1], $line[2]);
        }
        return $return;
    }

    public function send_sms($in, $type = 'sms')
    {
        if (!isset($in['from']) || !isset($in['to']) || !isset($in['text'])) return false;
        if (!in_array($type, array('sms', 'udh', 'utf'))) {
            return false;
        }
        // Data necessary anyway
        $payload = array('mode' => $type, 'from' => $in['from'], 'to' => $in['to'], 'text' => $in['text']);
        if ($type == 'udh') {
            if (!isset($in['udh'])) return false;
            $payload['udh'] = bin2hex($in['udh']);
            $payload['text'] = bin2hex($in['text']);
            $sms_num = 1;
            if (strlen($payload['udh'].$payload['text']) > 280) {
                return array(0 => 401, $this->srvr2resp[401], 0);
            }
        } elseif ($type == 'utf') {
            $payload['text'] = decode_utf8($in['text'], 'utf-16');
            $sms_num = strlen($payload['text']) > 140 ? ceil($payload['text']/134) : 1;
        } else {
            $sms_num = (strlen(trim($in['text'])) > 160) ? ceil(strlen($in['text']) / 153) : 1;
        }
        // Amount of SMS effective
        $return = $this->handshake($payload);
        if (false == $return) return array (0 => 600, 1 => $this->get_last_error(), 2 => 0);
        $return = explode(' ', trim($return));
        if ($return[0] == 100 && isset($return[1]) && $return[1] > 1) {
            $return[0] = 101;
        }
        if (isset($this->srvr2resp[$return[0]])) {
            return array(0 => $this->srvr2resp[$return[0]], 1 => $this->responses[$this->srvr2resp[$return[0]]], 2 => $sms_num);
        }
        return array(0 => 600, 1 => implode(' ', $return), 2 => $sms_num);
    }

    public function send_fax($in)
    {
        if (!isset($in['from']) || !isset($in['from_name'])  || !isset($in['to']) || !isset($in['file'])) return false;
        // Data necessary anyway
        $payload = array
                ('mode' => 'fax'
                ,'from' => $in['from']
                ,'from_name' => $in['from_name']
                ,'to' => $in['to']
                ,'file' => str_replace(array('+', '/' , '='), array('-', '_', ''), $in['file'])); // HTTP safe transfer
        if (!empty($in['status_to'])) {
            $payload['status_to'] = $in['status_to'];
        }
        $return = $this->handshake($payload, 'POST');
        if (false == $return) return array (0 => 600, 1 => $this->get_last_error());
        return explode(' ', trim($return), 2);
    }

    /**
     * Trys to clean up the input a bit
     * We do currently only allow to send SMS to German cellphone numbers
     * We do not want to send to "Kurzwahl" numbers, since these could be
     * too expensive for the user :)
     * @param array
     * @return array
     */
    public function wash_input($in)
    {
        // Checking and washing Sender field
        if (isset($in['from'])) {
            // Only ANUM allowed
            $in['from'] = preg_replace('!^\+!', '00', $in['from']);
            $in['from'] = preg_replace('![^a-z0-9]!i', '', $in['from']);
            // Sender may be alphanum -> MaxLen: 11 chars
            // Numbers must match the format MSISDN
            if (preg_match('![a-z]!i', $in['from'])) {
                $in['from'] = substr($in['from'], 0, 11);
            } elseif (!preg_match('!^00!', $in['from'])) {
                $this->_set_error('Wash Input: Wrong number format of From Field');
                return false;
            }
        }
        // Checking and washing Recipient field
        if (isset($in['to'])) {
            // Only NUM allowed
            $in['to'] = preg_replace('!^\+!', '00', $in['to']);
            $in['to'] = preg_replace('![^0-9]!i', '', $in['to']);
            if (!preg_match('!^00!', $in['to'])) {
                $this->_set_error('Wash Input: Wrong number format of To Field');
                return false;
            }
        }
        // Sender name (for fax service)
        if (isset($in['from_name'])) {
            $in['from_name'] = decode_utf8($in['from_name']);
        }
        /* Status-To: (Email address for fax status mails)
        if (isset($in['status_to'])) {
            if (!preg_match('!^(.+)\@([a-z0-9]{1}([-a-z0-9]+)\.([a-z0-9]){2,6}$!i')) {
                $in['status_to'] = '';
            }
        }*/
        return $in;
    }

    /**
     * Read out the last error that occured
     * @return   string    Returns the last error, if one exists, else an emtpy string
     * @access   public
     */
    public function get_last_error() { return ($this->error) ? $this->error : ''; }

    // Add or set an (timestamped) error, that can be requested via get_last_error()
    private function _set_error($error)
    {
        $vorn = ($this->timestamp_errors) ? time().' ' : '';
        if ($this->append_errors) {
            $this->error .= $vorn.$error.LF;
        } else {
            $this->error  = $vorn.$error;
        }
    }

    /**
     * Redundant request of the GW servers
     * @param array $in Pass key => value pairs with payload to query
     *[@param string $method HTTP method to use; Default: GET]
     * @return string Data on success or false on failure
     */
    private function handshake($in, $method = 'GET')
    {
        $ssl_capable = function_exists('extension_loaded') && extension_loaded('openssl');

        // Init Query
        $query = 'user='.urlencode($this->user).'&encpass='.urlencode(md5($this->pass));
        // Pass everything
        foreach ($in as $k => $v) {
            $query .= '&'.$k.'='.urlencode($v);
        }
        // Fallback in mind
        foreach (array($this->gwini['primary'], $this->gwini['secondary']) as $GW) {
            $GW['query'] = $query;
            $GW['method'] = $method;
            // SLL available? Use it!
            if ($ssl_capable) {
                $GW['scheme'] = 'https';
            // SSL not available, but required by admin -> no success!
            } elseif (!empty($GLOBALS['_PM_']['auth']['force_ssl'])) {
                return false;
            }
            $httpClient = new Protocol_Client_HTTP();
            $captured = $httpClient->send_request($GW);
            if (!empty($captured)) {
                return $captured;
            }
        }
        return false;
    }
}
