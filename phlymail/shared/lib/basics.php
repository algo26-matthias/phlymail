<?php
/**
 * Some basic functionality grouped into a class
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Basic functionalities
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.3 2015-04-17 
 */
class basics
{
    /**
     * Evaluate, whether a given parameter is TRUE or FALSE
     * @access  public
     * @param   Parameter (String, number or boolean)
     * @return  true | false
     * @since   0.0.1
     */
    public static function eval_param($param)
    {
        if (true === $param) {
            return true;
        }
        return (in_array($param, array('1', 1, 'yes', 'y')));
    }

    /**
     * Save configuration options in the global.choices.ini.php or any other file
     * of a similar structure. The saved format is a sectioned .ini file
     *
     * @param  string  $file  Path to the confugration file
     * @param  array   $data  Payload to write
     * [@param  boolean  $replace  If true, it replaces the passed data in the original file; Default: true]
     * [@param  octal  $chmod  Set this chmod value when creating the file]
     * @return  boolean  true on success, false otherwise
     * @since 0.0.2
     */
    public static function save_config($file, $data, $replace = true)
    {
        if ($replace) {
            $original = (file_exists($file) && is_readable($file)) ? parse_ini_file($file, 1) : array();
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $original[$k][$k2] = $v2;
                    }
                } else {
                    $original[$k] = $v;
                }
            }
        } else {
            $original = &$data;
        }
        if (!file_exists($file)) {
            touch($file);
        }
        $fid = fopen($file, 'w');
        if (!is_resource($fid)) {
            return false;
        }
        // Trying to lock the file... But don't care, if not possible. Prevents the user from a lot of PITA
        @flock($fid, LOCK_EX);
        fputs($fid, ';<?php die(); ?>'.LF);
        foreach ($original as $k => $v) {
            if (is_array($v)) {
                fputs($fid, '['.$k.']'.LF);
                foreach ($v as $k2 => $v2) {
                    fputs($fid, $k2.' = '.(preg_match('![^0-9]!i', $v2) ? '"'.$v2.'"'.LF : $v2.LF));
                }
            } else {
                fputs($fid, $k.' = '.(preg_match('![^0-9]!i', $v) ? '"'.$v.'"'.LF : $v.LF));
            }
        }
        fclose($fid);
        return true;
    }

    /**
     * Recursively create directoires
     * @param string $dirname complete pathname
     * @return mixed Either false on errors or the dirname itself on success
     * @since  0.0.4
     */
    public static function create_dirtree($dirname = '', $umask = null)
    {
        if (is_null($umask) || empty($umask)) {
            $umask = 0755;
        }
        if (is_dir($dirname)) {
            return $dirname;
        }
        $state = @mkdir($dirname, $umask, true);
        if (!$state) {
            return false;
        }
        return $dirname;
    }

    /**
     * Attempts to empty (and remove) the given dir, should also work with bigger dir structures
     * @param string $path  Path of the directory to empty
     *[@param bool $andRemove Set to TRUE to also remove the dir; Default: false]
     * @since 0.2.6 2012-05-03
     */
    public static function emptyDir($path, $andRemove = false)
    {
        $d = opendir($path);
        while (false !== ($file = readdir($d))) {
            $name = $path.'/'.$file;
            if ('.' == $file || '..' == $file) {
                continue;
            }
            if (is_dir($name)) {
                self::emptyDir($name, true);
            } else {
                unlink($name);
            }
        }
        closedir($d);
        if (true == $andRemove) {
            rmdir($path);
        }
    }

    /**
     * Bypass a weakness of PHP's unserialize when dealing with probably
     * mangled binary data
     *
     * @param string $str The serialized data
     * @return string The unserialized data
     * @since 0.0.9
     */
    public static function mb_unserialize($str)
    {
        if (!strlen($str)) {
            return array();
        }
        return unserialize(preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $str));
    }

    /**
     * Generates an UUID
     *
     * @author     Anis uddin Ahmad <admin@ajaxray.com>
     * @param      string  an optional prefix
     * @return     string  the formatted uuid
     */
    public static function uuid($prefix = '')
    {
        $chars = sha1(self::strong_uniqid());
        $uuid  = substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-' . substr($chars, 12, 4) . '-'
                . substr($chars, 16, 4) . '-' . substr($chars, 20, 12);
        return strval($prefix) . $uuid;
    }

    public static function strong_uniqid($maxLength = null)
    {
        $entropy = '';
        // try ssl first
        if (function_exists('openssl_random_pseudo_bytes')) {
            $entropy = openssl_random_pseudo_bytes(64, $strong);
            // skip ssl since it wasn't using the strong algo
            if ($strong !== true) {
                $entropy = '';
            }
        }

        // add some basic mt_rand/uniqid combo
        $entropy .= uniqid(mt_rand(), true);

        // try to read from the windows RNG
        try {
            if (class_exists('COM')) {
                $com = new COM('CAPICOM.Utilities.1');
                $entropy .= base64_decode($com->GetRandom(64, 0));
            }
        } catch (Exception $ex) {
            unset($ex);
        }

        // try to read from the unix RNG
        if (@is_readable('/dev/urandom')) {
            $h = fopen('/dev/urandom', 'rb');
            $entropy .= fread($h, 64);
            fclose($h);
        }

        $hash = hash('whirlpool', $entropy);
        if ($maxLength) {
            return substr($hash, 0, $maxLength);
        }
        return $hash;
    }

    public static function softbreak($text = '')
    {
        $split = explode(' ', $text);
        foreach ($split as $key => $value) {
            if (strlen($value) > 10) {
                $split[$key] = self::chunkSplitUnicode($value, 5, '&#8203;');
            }
        }
        return implode(' ', $split);
    }

    public static function shuffle_assoc($array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        $new = array();
        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }
        return $new;
    }

    /**
     * Takes an array as argument and ensures, that all array values are integers
     * This method does not check sanity, pass in bullshit, receive bullshit!
     *
     * @since 0.3.0 2013-11-01
     * @param array $array
     * @return multitype:multitype: number
     */
    public static function intify($array)
    {
        if (!is_array($array)) {
            $array = array(0 => $array);
        }
        foreach ($array as $k => $v) {
            $array[$k] = intval($v);
        }
        return $array;
    }

    public static function isURL ($url)
    {
        return preg_match('_^(https?://|ftps?://|gopher://|file:///?|news:)(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$_', $url);
    }

    /**
     * Grobe Prüfung, ob der übergebene String eine Emailadresse sein KÖNNTE.
     * Trifft keine Aussage, ob die Adresse wirklich valide ist.
     *
     * @param string $s_email  Zu prüfender String
     * @return bool
     */
    public static function isEmail($email)
    {
        return preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $email);
    }

    /**
     * Shorthanded version of fxl_date_conv. This unnecessarily was a full blown
     * class, whereas it could all be put into a single method.
     *
     * @param mixed $date  A date string you wish to convert or an assoc. array holding all the date parts
     * @param string $format  The format as of PHP's date() function
     * @return mixed  FALSE on failure, string on success
     * @since 0.1.0
     */
    public static function format_date($date, $format)
    {
        $year = $month = $day = $hour = $minute = $second = 0;
        if (is_array($date)) {
            foreach ($date as $k => $v) {
                ${$k} = $v;
            }
        } else {
            $date = trim($date);
            if (preg_match('/^(0?\d|[12]\d|3[01])\.(0?\d|1[012])\.(\d+)(?: ([0\s]?[\d]|1\d|2[0-3]):([0\s]?\d|[0-5]\d)(?::([0\s]?\d|[0-5]\d))?)?$/', $date, $m)) {
                array_shift($m);
                if (count($m) >= 3 && checkdate($m[1], $m[0], $m[2])) {
                    list ($day, $month, $year, $hour, $minute, $second) = array_pad($m, 6, 0);
                }
            } elseif (preg_match('/^(\d+)-([0\s]\d|1[012])-[\s]?(0?\d|[12]\d|3[01])(?:[\sT]([0\s]?\d|1\d|2[0-3]):([0\s]?\d|[0-5]\d)(?::([0\s]?\d|[0-5]\d))?)?$/', $date, $m)) {
                array_shift($m);
                if (count($m) >= 3 && checkdate($m[1], $m[2], $m[0])) {
                    list ($year, $month, $day, $hour, $minute, $second) = array_pad($m, 6, 0);
                }
            } elseif (preg_match('/^(0?\d|[12]\d|3[01])\.(0?\d|1[12])\.$/', $date, $m)) {
                array_shift($m);
                $m[2] = date('Y');
                if (count($m) == 3 && checkdate($m[1], $m[0], $m[2])) {
                    list ($day, $month, $year, $hour, $minute, $second) = array_pad($m, 6, 0);
                }
            } else {
                return false;
            }
        }
        if (!$month) {
            return false;
        }
        return date($format, mktime($hour, $minute, $second, $month, $day, $year));
    }

    /**
     * The internal function to download a file from a URL, given that allow_url_fopen is allowed
     *
     * @param string $file_source  a HTTP or FTP URI. No other means are supported right now due to
     *      security reasons (a malicious person could try to "download" local files
     * @param string $file_target internal target file
     * @param string $uploadinfo Where to put and how to name the donwload info for APC upload progress
     * @param string $maxsize maximum file size (not checked for right now)
     * @return array
     * 'error' int corresponding to the UPLOAD_ERR_xxx constants
     * 'size'  int  size in bytes actually downloaded
     * 'name'  string  file name as deliviered by the server
     * 'type' string  MIME type as delivered by the server
     */
    public static function download($file_source, $file_target, $uploadinfo, $maxsize = 1073741824)
    {
        $return = array('error' => UPLOAD_ERR_NO_FILE, 'size' => 0, 'name' => '', 'type' => 'application/octet-stream');
        $allowedSchemes = array('http', 'https', 'ftp', 'ftps');
        $file_source = str_replace(' ', '%20', html_entity_decode($file_source)); // fix url format
        $state = ($uriparts = @parse_url($file_source));
        if (!$state
                || empty($uriparts)
                || !in_array($uriparts['scheme'], $allowedSchemes)) {
            return $return;
        }
        if (($rh = fopen($file_source, 'rb')) === false) {
            $return['error'] = UPLOAD_ERR_NO_FILE;
            return $return;
        }
        if (($wh = fopen($file_target, 'wb')) === false) {
            $return['error'] = UPLOAD_ERR_CANT_WRITE;
            return $return;
        }
        $headers = stream_get_meta_data($rh);
        foreach ($headers['wrapper_data'] as $head) {
            if (strtolower(substr($head, 0, 13)) == 'content-type:') {
                $return['type'] = trim(strtolower(substr($head, 13)));
            }
            if (strtolower(substr($head, 0, 15)) == 'content-length:') {
                $return['size'] = trim(strtolower(substr($head, 15)));
            }
            if ($return['name'] == '' && preg_match('!name=("?)(.*)\1!i', $head, $found)) {
                $return['name'] = $found[2];
            }
        }
        // Set a default name
        if ($return['name'] == '' && isset($uriparts['path'])) {
            $return['name'] = basename(urldecode($uriparts['path']));
        }
        // error handling
        if ($return['size'] > 0 && $return['size'] > $maxsize) {
            fclose($rh);
            fclose($wh);
            $return['error'] = UPLOAD_ERR_INI_SIZE;
            return $return;
        }
        stream_copy_to_stream($rh, $wh);
        // Finished without errors
        fclose($rh);
        fclose($wh);
        @unlink($uploadinfo);
        $return['error'] = UPLOAD_ERR_OK;
        return $return;
    }

    /**
     * query the Google Maps Geocoder
     * It's highly recommended to cache the result, as Google limits the number of
     * geocode requests per day.
     *
     * @param array $obj  Holds the relevant address info. Keys:
     * - 'zip' => German PLZ or the city as a string, e.g. Berlin
     * - 'address' => string Street and no, e.g. Alexanderstrass 1
     * - 'country' => Optiional, Default: Germany
     * - 'apikey' => The Google Maps API key for the domain you query the coder for
     * @return array|false  Array with the keys 'longitude' and 'latitude' on success or false on error
     * @since 0.1.3
     */
    public static function queryGoogleGeoCoder($obj)
    {
        if (empty($obj) || !isset($obj['zip']) || !isset($obj['apikey'])) {
            return false;
        }
        $zip = ($obj['zip']) ? $obj['zip'] : 'Berlin';
        $country = isset($obj['country']) && $_REQUEST['country'] ? $_REQUEST['cuntry'] : 'Germany';
        $response = basics::http_request(array
                ('host' => 'maps.google.com'
                ,'path' => '/maps/geo'
                ,'query' => 'q='.urlencode($obj['address']).',+'.urlencode($zip).',+'.urlencode($country).'&output=xml&key='.$obj['apikey']
                ,'method' => 'GET'
                ));
        if (preg_match('!\<coordinates\>([-0-9.,]+)\<\/coordinates\>!U', $response, $found)) {
            $coords = explode(',', $found[1]);
            if (isset($coords[1])) {
                return array('longitude' => $coords[0], 'latitude' => $coords[1]);
            }
        }
        return false;
    }

    /**
     * Inspired by a post on php.net, this methode does a chunk_split with
     * Unicode (UTF-8) strings. Thus breaking UTF-8 chars in the middle is
     * effectively prevented
     *
     * @param string $str  The source string
     * @param int $l  Chunk length exluding newline; Default: 76 chars
     * @param string $e  Newline sequence; Default: \r\n
     * @return string  The resulting string
     * @since 0.2.5
     */
    public static function chunkSplitUnicode($str, $l = 76, $e = CRLF)
    {
        $tmp = array_chunk(preg_split('!!u', $str, -1, PREG_SPLIT_NO_EMPTY), $l);
        $str = '';
        foreach ($tmp as $t) {
            $str .= implode('', $t).$e;
        }
        return rtrim($str, $e);
    }

    public static function tokenTruncate($string, $your_desired_width)
    {
        $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
        $parts_count = count($parts);

        for ($length = 0, $last_part = 0; $last_part < $parts_count; ++$last_part) {
            $length += strlen($parts[$last_part]);
            if ($length > $your_desired_width) {
                break;
            }
        }
        return implode(array_slice($parts, 0, $last_part));
    }

    /**
     * Check whether there's still deposit available for a user to send a short message
     *
     * @param type $uid
     * @param type $monthlyMax
     * @param type $allowOverLimit
     * @return boolean
     */
    public static function SmsDepositAvailable($uid, $monthlyMax, $allowOverLimit)
    {
        $DB = new DB_Base();
        $smsSent = (int) $DB->get_user_accounting('sms', date('Ym'), $uid);
        $globalLimit = (int) $DB->get_sms_global_deposit();

        if ($monthlyMax) {
            $nochfrei = $monthlyMax - $smsSent;
            return ($nochfrei > 0) ? $nochfrei : false;
        }
        if ($allowOverLimit) {
            return true;
        }
        return $globalLimit;
    }
}