<?php
/**
 * HTTP client - make HTTP requests and return the result
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Basic functionalities
 * @copyright 2004-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.3.0 2016-01-21
 */
class Protocol_Client_HTTP
{
    protected $req;
    protected $body = '';
    protected $header = '';
    protected $errno= 0;
    protected $errstr = '';
    protected $isSSL = false;
    protected $auth_method = false; // or "Basic" / "Digest"
    protected $auth_realm = '';
    protected $auth_nonce = '';
    protected $auth_opaque = '';
    protected $auth_cnonce = '';
    protected $auth_nc = 0;
    protected $auth_qop = 'auth';
    protected $auth_HA1 = false;
    protected $auth_HA2 = false;
    protected $additionalHeaders = [];

    /**
     * Sends an HTPP request to a foreign host. It should support most of the HTTP 1.0
     * request methods and is even capable of following 301 responses.
     *
     * @param array $req holds
     *  - 'host' string Hostname (without http and stuff)
     *  - 'port' int; defaults to 80
     *  - 'path' string; e.g. '/myscript.php'
     *  - 'query' string Data to submit to the host, must be already in the form 'param1=value&param2=value2'
     *  - 'method' string  One of the HTTP methods, if none given, GET is assumed
     *  - 'connect_host' string  Hostname or IP to connect to; if defined, 'host' is given in the HTTP header
     *  Optionally passing a fully valid URI is okay, too
     * @return string $captured Captured HTTP response body on success, FALSE on failure
     */
    public function send_request($req)
    {
        $this->auth_nc = 0;
        $redirMax = 5;
        $redirCnt = 0;
        // PATCH, PROPFIND, PROPPATCH, COPY, MOVE for WebDAV are not supproted yet
        $allowMeth = array('HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'MKCOL', 'LOCK', 'UNLOCK');

        if (is_string($req)) {
            $req = @parse_url($req);
            if (false === $req) {
                return false; // Did not work out
            }
        }
        if (empty($req)) {
            return false; // No data to work upon
        }

        if ((!empty($req['scheme']) && $req['scheme'] == 'https' && (empty($req['port']) || $req['port'] == 443)) || !empty($req['ssl'])) {
            $req['port'] = 443;
            $this->isSSL = true;
        }
        $req['port'] = (isset($req['port'])) ? intval($req['port']) : 80;

        if (!isset($req['path'])) {
            $req['path'] = '/';
        }
        if (!isset($req['query'])) {
            $req['query'] = '';
        }
        $req['method'] = (isset($req['method']) && in_array(strtoupper($req['method']), $allowMeth)) ? strtoupper($req['method']) : 'GET';
        $req['connect_host'] = (isset($req['connect_host'])) ? $req['connect_host'] : $req['host'];

        // IP resolution cache
        if (!$this->isSSL) {
            if (isset($_SESSION['phM_IPcache']) && isset($_SESSION['phM_IPcache'][$req['connect_host']])) {
                $req['connect_host'] = $_SESSION['phM_IPcache'][$req['connect_host']];
            } elseif (function_exists('gethostbyname') && !preg_match('!^\d+\.\d+\.\d+\.\d+$!', $req['connect_host'])) {
                $IP = @gethostbyname(substr($req['connect_host'], 0, 6) == 'ssl://' ? substr($req['connect_host'], 6) : $req['connect_host']);
                if (false !== $IP) {
                    $_SESSION['phM_IPcache'][$req['connect_host']] = $IP;
                    $req['connect_host'] = $IP;
                }
            }
        }

        $this->req = $req;

        while (true) {
            // If used, must be different for each request
            $this->auth_cnonce = uniqid();
            // Send the request
            $res = $this->request();
            if (false === $res) {
                return false;
            }
            if (preg_match('!^HTTP/1\.[01]\ (301|302)!m', $this->header, $statusCode)) {
                $redirCnt++;
                if ($redirCnt > $redirMax) {
                    return false;
                }
                // Read new location
                preg_match('!^Location\:\ (.+)$!Um', $this->header, $found);
                foreach (parse_url(trim($found[1])) as $k => $v) {
                    $this->req[$k] = $v;
                }
                // Important to change that alongside the acquired data
                $this->req['connect_host'] = $this->req['host'];

                continue;
            }
            if (preg_match('!HTTP/1\.[01]\ (401)!', $this->header)) {
                if (empty($this->req['auth_user']) || empty($this->req['auth_pass'])) {
                    return false;
                }
                $res = $this->auth();
                if (false === $res) { // Unable to send AUTH request
                    return false;
                }
                continue; // Auth header has been injected into $req, try request again
            }

            if (!preg_match('!HTTP/1\.[01]\ (200|304)!', $this->header)) {
                return false;
            }
            break;
        }
        return $this->body;
    }

    public function getResponseHeader()
    {
        return $this->header;
    }

    public function getErrorString()
    {
        return $this->errstr;
    }

    public function getErrorNo()
    {
        return $this->errno;
    }

    public function setAdditionalHeaders($headers = [])
    {
        $this->additionalHeaders = $headers;
    }

    public function getAdditionalHeaders()
    {
        return $this->additionalHeaders;
    }

    protected function formattedAdditionalHeaders()
    {
        if (empty($this->additionalHeaders)) {
            return '';
        }
        $return = '';
        foreach ($this->additionalHeaders as $k => $v) {
            $return .= $k.': '.$v.CRLF;
        }
        return $return;
    }

    protected function request()
    {
        $this->header = $this->body = '';

        $fp = fsockopen($this->req['connect_host'], $this->req['port'], $this->errno, $this->errstr, 5);
        if (!$fp) {
            return false;
        }
        if ($this->isSSL) {
            $res = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$res) {
                $res = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
                if (!$res) {
                    return false;
                }
            }
        }

        switch ($this->req['method']) {
            case 'GET': case 'OPTIONS': case 'DELETE': case 'MKCOL': case 'LOCK': case 'UNLOCK':
                fwrite($fp, $this->req['method'].' '.$this->req['path'].(strlen($this->req['query']) ? '?'.$this->req['query'] : '').' HTTP/1.1'.CRLF
                        .'Host: '.$this->req['host'].CRLF
                        .$this->authenticated_header()
                        .$this->formattedAdditionalHeaders()
                        .CRLF);
                break;
            default:
                fwrite($fp, $this->req['method'].' '.$this->req['path'].' HTTP/1.1'.CRLF
                        .'Host: '.$this->req['host'].CRLF
                        .$this->authenticated_header()
                        .$this->formattedAdditionalHeaders()
                        .'Content-Type: application/x-www-form-urlencoded'.CRLF
                        .'Content-Length: '.strlen($this->req['query']).CRLF
                        .'Connection: close'.CRLF.CRLF);
                fwrite($fp, $this->req['query']);
        }
        while (!feof($fp) && ($line = fgets($fp, 4096)) && ($line != CRLF)) {
            $this->header .= $line;
        }
        // Quick unfolding of the header
        $this->header = preg_replace('!\r\n[\s\t]!', '', $this->header);

        while (!feof($fp)) {
            $this->body .= fread($fp, 8192);
        }
        fclose($fp);
        return true;
    }

    protected function auth()
    {
        if (!preg_match('!^WWW\-Authenticate\:\ (Digest|Basic)\ (.+)$!smi', $this->header, $particles)) {
            return false;
        }
        if (strtoupper($particles[1]) == 'BASIC') {
            preg_match('!realm\=\"([^"]+)\"!i', $particles[2], $realm);

            $this->auth_method = 'Basic';
            $this->auth_realm = $realm[1];

        } elseif (strtoupper($particles[1]) == 'DIGEST') {
            preg_match('!realm\=\"([^"]+)\"!i', $particles[2], $realm);
            preg_match('!qop\=\"([^"]+)\"!i', $particles[2], $qop);
            preg_match('!nonce\=\"([^"]+)\"!i', $particles[2], $nonce);
            preg_match('!opaque\=\"([^"]+)\"!i', $particles[2], $opaque);

            $this->auth_HA1 = md5($this->req['auth_user'] . ':' . $realm[1] . ':' . $this->req['auth_pass']);
            $this->auth_HA2 = md5($this->req['method'] . ':' . $this->req['path']);

            $this->auth_method = 'Digest';
            $this->auth_realm = $realm[1];
            $this->auth_nonce = $nonce[1];
            $this->auth_opaque = $opaque[1];
            $this->auth_qop = 'auth'; // We don't want anything else right now
        }
        return true;
    }

    protected function authenticated_header()
    {
        if ($this->auth_method == 'Basic') {
            return 'Authorization: '.$this->auth_method.' '.base64_encode($this->req['auth_user'] . ':' . $this->req['auth_pass']).CRLF;
        } elseif ($this->auth_method == 'Digest') {
            $response = md5($this->auth_HA1 . ':' . $this->auth_nonce . ':' . $this->calc_8lhex($this->auth_nc) .':' . $this->auth_cnonce . ':' . $this->auth_qop . ':' . $this->auth_HA2);
            return 'Authorization: '.$this->auth_method.' username="' . $this->req['auth_user'].'"'
                    .', realm="'.$this->auth_realm.'", qop='.$this->auth_qop
                    .', nonce="'.$this->auth_nonce.'", uri="'.$this->req['path'].'"'
                    .', cnonce="'.$this->auth_cnonce.'", opaque="'.$this->auth_cnonce_opaque.'"'
                    .', nc='.$this->auth_nc.', response="'.$response.'"'.CRLF;
        }
        return '';
    }

    protected function calc_8lhex($int)
    {
        str_pad(dechex($int), 8, '0', STR_PAD_LEFT);
    }
}
