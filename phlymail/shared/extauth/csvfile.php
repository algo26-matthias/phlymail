<?php
/**
 * phlyMail 4.x external auth module using CSV
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage ExtAuth
 * @copyright 2002-2009 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2009-11-14
 */
/**
 * This is a module for phlyMail, which allows to authenticate against an external
 * source.
 * It implements authentication against a flat file in CSV format, where the
 * delimiter is always considered to be a semicolon ";", no other characters are
 * supported right now.
 * Offsets defined below are the field number within a line, where the
 * corresponding entry can be found. Counting starts with 0, NOT 1!
 *
 * These settings are required to allow successfull login check:
 *
 * Open phlymail/shared/config/global.choices.ini.php or phlymail/choices.ini.php
 * and add this INI section (or append lines missing to it):
 * [extauth]
 * module = csvfile
 * encoding = "<charset>" ; The character set, e.g. iso-8859-1
 * quoted = 1|0 ; Whether the strings are surrounded by double quotes
 * ignorefirstline = 1|0 ; Set to 1 if the first line of the CSV contains the field description
 * filename = "/path/to/csv" ; Full path is ideal, else it starts at phlyMail root dir
 * create_user = 0|1 ; Whether to create users, which do not exist in phlyMail yet
 * passwords_hashed = 1|0 ; If set to 1, store the passwords as a MD5 hash
 * field_username = <int> ; Offset of the username
 * field_password = <int> ; Offset of the password field
 */

/**
 * This function is called by phlymail/frontend/mod.auth.php
 *
 * @param string $user  The user name supplied on login
 * @param string $pass  The password in unencrypted form
 * @param array $_PM_ The complete _PM_ array of phlyMail
 * @return array
 * [0] => bool - Whether login was successfull
 * [1] => mixed - String on errors (error message), false on success
 */
function extauth($user, $pass, &$_PM_)
{
    if (!isset($_PM_['extauth']['filename']) || !isset($_PM_['extauth']['field_username'])
            || !isset($_PM_['extauth']['field_password'])) {
        return array(-2, 'Check your setup');
    }
    if (!file_exists($_PM_['extauth']['filename']) || !is_readable($_PM_['extauth']['filename'])) {
        return array(-2, 'ExtAuth CSV file not found');
    }
    $fp = fopen($_PM_['extauth']['filename'], 'r');
    if (!is_resource($fp)) {
        return array(-2, 'ExtAuth CSV file could not be read');
    }
    $encoding = isset($_PM_['extauth']['encoding']) ? $_PM_['extauth']['encoding'] : 'iso-8859-1';
    $k = -1;
    while (!feof($fp) && false !== ($line = fgets($fp, 4096))) {
        ++$k;
        if (0 == $k && $_PM_['extauth']['ignorefirstline']) continue;
        $line = encode_utf8($line, $encoding, true);
        if (isset($_PM_['extauth']['quoted']) && $_PM_['extauth']['quoted']) $line = str_replace('"', '', $line);
        $line = explode(';', trim($line));
        if (!isset($line[$_PM_['extauth']['field_username']]) || !isset($line[$_PM_['extauth']['field_password']])) {
            continue; // Might be an empty line
        }
        // Hashed passwords
        if (isset($_PM_['extauth']['passwords_hashed']) && $_PM_['extauth']['passwords_hashed']) {
            $pass = md5($pass);
        }
        // Got a match here
        if ($line[$_PM_['extauth']['field_username']] == $user) {
            if ($line[$_PM_['extauth']['field_password']] == $pass) {
                fclose($fp);
                return array(1, false);
            } else {
                break;
            }
        }

    }
    fclose($fp);
    return array(0, $GLOBALS['WP_msg']['wrongauth'].' '.$user);
}
