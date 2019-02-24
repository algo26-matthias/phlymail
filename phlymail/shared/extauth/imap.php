<?php
/**
 * extauth.imap.php - phlyMail 4.x external auth module using IMAP
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage ExtAuth (External Authentication)
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.3 2012-05-02 
 */

/**
 * This is a module for phlyMail, which allows to authenticate against an external
 * source.
 * It implements authentication against IMAP
 * These settings are required to allow successfull login check:
 *
 * Open phlymail/shared/config/global.choices.ini.php or phlymail/choices.ini.php
 * and add this INI section (or append lines missing to it):
 * [extauth]
 * module = imap
 * imap_server = "<your server name>" 1)
 * imap_port = "<IMAP port, usually 143>"
 * imap_security = "SSL|STARTTLS|AUTO|none" ; Force SSL / force STARTTLS / autonegotiate / no security at all
 * create_user = 0|1 ; Whether to create users, which do not exist in phlyMail yet
 * smtp_server = "<SMTP-Server to use for new accounts>" 1)
 * smtp_port = "<SMTP port, usually 25>"
 * smtp_auth = 0|1 ; Whether to use SMTP AUTH
 * smtp_security = "SSL|STARTTLS|AUTO|none" ; Force SSL / force STARTTLS / autonegotiate / no security at all
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
 * [0] => bool - Whether login was successfull
 * [1] => mixed - String on errors (error message), false on success
 */
function extauth($user, $pass, &$_PM_)
{
    if (!isset($_PM_['extauth']['imap_server']) || !isset($_PM_['extauth']['imap_port'])
            || !isset($_PM_['extauth']['imap_tls'])) {
        return array(-2, 'Check your setup');
    }
    $localpart = $realm = '';
    if (preg_match('!^(.+)\@(.+)$!', $user, $found)) {
        $localpart = $found[1];
        $realm = $found[2];
    }
    // Allow to have an individual MX per domain
    if (strpos($_PM_['extauth']['imap_server'], '{domain}') !== false) {
        if (!$realm) return array(-2, 'Check your setup');

        $_PM_['extauth']['imap_server'] = str_replace('{domain}', $realm, $_PM_['extauth']['imap_server']);
        $_PM_['api_user']['popserver'] = $_PM_['extauth']['imap_server'];
    }
    if (strpos($_PM_['extauth']['smtp_server'], '{domain}') !== false) {
        if (!$realm) return array(-2, 'Check your setup');

        $_PM_['extauth']['smtp_server'] = str_replace('{domain}', $realm, $_PM_['extauth']['smtp_server']);
        $_PM_['api_user']['smtpserver'] = $_PM_['extauth']['smtp_server'];
    }

    $IMAP = new Protocol_Client_IMAP(
            $_PM_['extauth']['imap_server'],
            $_PM_['extauth']['imap_port'],
            0,
            $_PM_['extauth']['imap_security'],
            $_PM_['extauth']['imap_allowselfsigned']
    );
    if (!$IMAP) return array(-2, 'Check your setup');
    if ($IMAP->check_connected() !== true) {
        return array(-1, $GLOBALS['WP_msg']['noconnect'].' AUTH server ('.$IMAP->get_last_error().')');
    }
    $li = $IMAP->login($user, $pass, null, true, $_PM_['extauth']['imap_tls']);
    if (!$li || !$li['login']) {
        $IMAP->close();
        return array(0, $GLOBALS['WP_msg']['wrongauth'].' '.$user.' ('.$IMAP->get_last_error().')');
    }
    $IMAP->close();
    // Login has been successfull, now overload some settings for Config API in case this is a new user
    $_PM_['api_user']['popuser'] = $user;
    $_PM_['api_user']['poppass'] = $pass;
    if (isset($_PM_['extauth']['smtp_auth'])) {
        $_PM_['api_user']['smtpuser'] = $_PM_['api_user']['popuser'];
        $_PM_['api_user']['smtppass'] = $_PM_['api_user']['poppass'];
    }
    $_PM_['api_user']['popport'] = $_PM_['extauth']['imap_port'];
    $_PM_['api_user']['popsec'] = $_PM_['extauth']['imap_security'];
    $_PM_['api_user']['smtpport'] = $_PM_['extauth']['smtp_port'];
    $_PM_['api_user']['smtpsec'] = $_PM_['extauth']['smtp_security'];

    // All well, return success
    return array(1, false);
}
