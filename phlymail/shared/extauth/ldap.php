<?php
/**
 * phlyMail 4.x external auth module using LDAP
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage ExtAuth
 * @copyright 2002-2009 phlyLabs, Berlin (http://phlylabs.de)
 * @copyright 2008 Michael Reese
 * @version 0.0.1 2009-11-14
 */
/**
 * This is a module for phlyMail, which allows to authenticate against an external
 * source.
 * It implements authentication against LDAP
 * These settings are required to allow successfull login check:
 *
 * Open phlymail/shared/config/global.choices.ini.php or phlymail/choices.ini.php
 * and add this INI section (or append lines missing to it):
 * [extauth]
 * module = ldap
 * ldap_server = "<your server name>" 1)
 * ldap_port = "<LDAP port, usually 389>"
 * iswin2k3 = "0|1 ; When the LDAP Server runs on Windows 2003 Server"
 * create_user = 0|1 ; Whether to create users, which do not exist in phlyMail yet
 *
 * 1): You can use the placeholder {domain} in the server name. This will resolve
 * to the realm (the part after the @) of the user's email address:
 * mail.{domain} woudl resolve to mail.exmple.com
 */

/**
 * This function is called by phlymail/frontend/mod.auth.php
 *
 * @param string $user  The user name supplied on login
 * @param string $pass  The password in unencrypted form
 * @param array $_PM_ The complete _PM_ array of phlyMail
 * @return array
 * [0] => int - -1 for temporary error, -2 for permanent error, 0 for wrong auth, 1 for success
 * [1] => mixed - String on errors (error message), false on success
 */
function extauth($user, $pass, &$_PM_)
{
    if (!isset($_PM_['extauth']['ldap_server']) || !isset($_PM_['extauth']['ldap_port'])){
        return array(-2, 'Check your setup');
    }
    $localpart = $realm = '';
    if (preg_match('!^(.+)\@(.+)$!', $user, $found)) {
        $localpart = $found[1];
        $realm = $found[2];
    }

    if (strpos($_PM_['extauth']['ldap_server'], '{domain}') !== false) {
        if (!$realm) return array(-2, 'Check your setup');
        $_PM_['extauth']['ldap_server'] = str_replace('{domain}', $realm, $_PM_['extauth']['ldap_server']);
    }

    if (!$ldap=@ldap_connect($_PM_['extauth']['ldap_server'], $_PM_['extauth']['ldap_port'])) {
        return array(-2, 'Can not connect to LDAP Server!');
    } else {
        if (isset($_PM_['extauth']['iswin2k3'])) {
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        }
        if (!@ldap_bind($ldap, $user, $pass)) return array(0, $GLOBALS['WP_msg']['wrongauth'].' '.$user);
        ldap_close($ldap);
    }
    // All well, return success
    return array(1, false);
}
