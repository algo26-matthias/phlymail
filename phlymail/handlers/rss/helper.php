<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage Handler RSS
 * @copyright 2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.1 2013-08-21 $Id: preview.php 2731 2013-03-25 13:24:16Z mso $
 */
class handler_rss_helper
{
    /**
     * Acts as a companion to the classic links() function.
     * It parses an HTML document and makes all links absolute to the page's origin.
     *
     * @param string $body  HTML page to fix
     * @param string $url  URL of the page
     * @return string
     */
    public static function absolutizeURIs($body, $url)
    {
        $parsedHost = $parsedPath = parse_url($url);

        unset ($parsedHost['fragment'], $parsedHost['query'], $parsedHost['path']);
        $hostName = self::unparse_url($parsedHost);

        unset ($parsedPath['fragment'], $parsedPath['query']);
        $parsedPath['path'] = rtrim(dirname($parsedPath['path']), '/').'/';
        $pathName = self::unparse_url($parsedPath);

        preg_match_all('!(href|src)=("|\')?([^\<\>"\']+)\2!is', $body, $found, PREG_SET_ORDER);
        if (!empty($found)) {
            foreach ($found as $k => $link) {
                if (substr($link[3], 0, 1) == '#') {
                    continue;
                }
                $pruef = strtolower($link[1]);
                if (substr($link[3], 0, 1) == '/' && substr($link[3], 0, 2) != '//') {
                    $neu_link = $hostName.$link[3];
                } elseif (!preg_match('!(https?://|ftps?://|gopher://|file:///?|news:)(.+)!', $link[3])) {
                    $neu_link = $pathName.$link[3];
                } else {
                    continue;
                }
                $neu_link = str_replace($link[3], $neu_link, $link[0]);
                $body = str_replace($link[0], $neu_link, $body);
            }
        }
        return $body;
    }

    // Blatantly taken from php.net ...
    public static function unparse_url($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? $pass.'@' : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public static function isHTML($string)
    {
        return (preg_match("/([\<])([^\>]{1,})*([\>])/i", $string));
    }

    public static function makeHTML($string)
    {
        $string = htmlentities($string, null, 'utf-8');
        $string = nl2br($string);
        return $string;
    }
}