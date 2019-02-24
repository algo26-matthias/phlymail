<?php
/**
 * extauth.imap.php - phlyMail 4.x external auth module using POP3
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage ExtAuth (External Authentication)
 * @copyright 2002-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.4 2012-05-02 
 */

/**
 * This is a module for phlyMail, which allows to authenticate against an external
 * source.
 * It implements authentication against POP3
 * These settings are required to allow successfull login check:
 *
 * Open phlymail/shared/config/global.choices.ini.php or phlymail/choices.ini.php
 * and add this INI section (or append lines missing to it):
 * [extauth]
 * module = pop3
 * pop3_server = "<your server name>" 1)
 * pop3_port = "<POP3 port, usually 110>"
 * pop3_security = "SSL|STARTTLS|AUTO|none" ; Force SSL / force STARTTLS / autonegotiate / no security at all
 * create_user = 0|1 ; Whether to create users, which do not exist in phlyMail yet
 * smtp_server = "<SMTP-Server to use for new accounts>" 1)
 * smtp_port = "<SMTP port, usually 25>"
 * smtp_auth = 0|1 ; Whether to use SMTP AUTH
 * smtp_security = "SSL|STARTTLS|AUTO|none" ; Force SSL / force STARTTLS / autonegotiate / no security at all
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
    if (!isset($_PM_['extauth']['pop3_server']) || !isset($_PM_['extauth']['pop3_port'])
            || !isset($_PM_['extauth']['pop3_apop'])) {
        return array(-2, 'Check your setup');
    }
    $localpart = $realm = '';
    if (preg_match('!^(.+)\@(.+)$!', $user, $found)) {
        $localpart = $found[1];
        $realm = $found[2];
    }
    // Allow to have an individual MX per domain
    if (strpos($_PM_['extauth']['pop3_server'], '{domain}') !== false) {
        if (!$realm) return array(-2, 'Check your setup');

        $_PM_['extauth']['pop3_server'] = str_replace('{domain}', $realm, $_PM_['extauth']['pop3_server']);
        $_PM_['api_user']['popserver'] = $_PM_['extauth']['pop3_server'];
    }
    if (strpos($_PM_['extauth']['smtp_server'], '{domain}') !== false) {
        if (!$realm) return array(-2, 'Check your setup');

        $_PM_['extauth']['smtp_server'] = str_replace('{domain}', $realm, $_PM_['extauth']['smtp_server']);
        $_PM_['api_user']['smtpserver'] = $_PM_['extauth']['smtp_server'];
    }

    $POP = new Protocol_Client_POP3(
        $_PM_['extauth']['pop3_server'],
        $_PM_['extauth']['pop3_port'],
        0,
        $_PM_['extauth']['pop3_security'],
        $_PM_['extauth']['pop3_allowselfsigned']
    );
    if (!$POP) return array(-2, 'Check your setup');
    if ($POP->check_connected() !== true) {
        return array(-1, $GLOBALS['WP_msg']['noconnect'].' AUTH server ('.$POP->get_last_error().')');
    }
    $li = $POP->login($user, $pass, $_PM_['extauth']['pop3_apop']);
    if (!$li || !$li['login']) {
        $POP->close();
        return array(0, $GLOBALS['WP_msg']['wrongauth'].' '.$user.' ('.$POP->get_last_error().')');
    }
    $POP->close();
    // Login has been successfull, now overload some settings for Config API in case this is a new user
    $_PM_['api_user']['popuser'] = $user;
    $_PM_['api_user']['poppass'] = $pass;
    if (isset($_PM_['extauth']['smtp_auth'])) {
        $_PM_['api_user']['smtpuser'] = $_PM_['api_user']['popuser'];
        $_PM_['api_user']['smtppass'] = $_PM_['api_user']['poppass'];
    }
    $_PM_['api_user']['popport'] = $_PM_['extauth']['pop3_port'];
    $_PM_['api_user']['popsec'] = $_PM_['extauth']['pop3_security'];
    $_PM_['api_user']['smtpport'] = $_PM_['extauth']['smtp_port'];
    $_PM_['api_user']['smtpsec'] = $_PM_['extauth']['smtp_security'];

    // All well, return success
    return array(1, false);
}
