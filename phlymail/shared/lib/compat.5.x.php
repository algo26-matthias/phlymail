<?php
/**
 * compatibility for PHP 5.x
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @author Matthias Sommerfeld, phlyLabs Berlin
 * @copyright 2003-2012 phlyLabs Berlin, http://phlylabs.de
 * @version 0.1.5 2012-09-28 
 */

if (!version_compare(phpversion(), '5.3.0', '>=')) {
    @ini_set('magic_quotes_runtime', 'Off');
    @ini_set('magic_quotes_sybase', 'Off');
    @set_magic_quotes_runtime(0);
}

// Borrowed from php.net; might be erroneous in edge cases
if (!function_exists('json_encode')) {
    function json_encode($a = false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }
            if (is_string($a)) {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            } else {
                return $a;
            }
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . implode(',', $result) . ']';
        } else {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . implode(',', $result) . '}';
        }
    }
}
if (!function_exists('json_decode')) {
    function json_decode($json, $assoc = true)
    {
        $comment = false;
        $out = '$x=';
        for ($i = 0; $i < strlen($json); $i++) {
            if (!$comment) {
                if (($json[$i] == '{') || ($json[$i] == '[')) {
                    $out .= ' array(';
                } elseif (($json[$i] == '}') || ($json[$i] == ']')) {
                    $out .= ')';
                } elseif ($json[$i] == ':') {
                    $out .= '=>';
                } else {
                    $out .= $json[$i];
                }
            } else {
                $out .= $json[$i];
            }
            if ($json[$i] == '"' && $json[($i-1)]!="\\") {
                $comment = !$comment;
            }
        }
        eval($out . ';');
        return $x;
    }
}
