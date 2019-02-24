<?php
/**
 * Derived from:
 * http://clickontyler.com/blog/2010/08/generating-strong-user-friendly-passwords-in-php/
 *
 * @author Tyler Hall
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @version 0.0.2 2013-05-29 $Id: SecurePassword.php 2800 2013-06-19 16:36:55Z mso $
 */

define('STRONGPASS_LOWERCASE', 1);
define('STRONGPASS_UPPERCASE', 2);
define('STRONGPASS_DECIMALS', 4);
define('STRONGPASS_SPECIALS', 8);

class SecurePassword
{
    private function __construct() { }

    public static function generate($length = 9, $add_dashes = false, $available_sets = 15)
    {
        $sets = array();
        if ($available_sets & STRONGPASS_LOWERCASE) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if ($available_sets & STRONGPASS_UPPERCASE) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if ($available_sets & STRONGPASS_DECIMALS) {
            $sets[] = '23456789';
        }
        if ($available_sets & STRONGPASS_SPECIALS) {
            $sets[] = '!@#$%&*?';
        }

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        if (!$add_dashes) {
            return $password;
        }

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}