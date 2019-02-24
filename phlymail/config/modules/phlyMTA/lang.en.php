<?php
/**
* Messages File for the phlyMTA Mail Server
* English (en)
* @version 0.1
*/
$modmsg['phlyMTA'] = 'phlyMTA';
$modmsg['MenPOP3Service'] = 'POP3 Server';
$modmsg['MenSMTPService'] = 'SMTP Server';
$modmsg['MenSMTPRelay'] = 'SMTP Relay';
$modmsg['MenSettings'] = 'General Settings';

$modmsg['AboutPOP3d'] = 'With the POP3 server of the phlyMTA you can grant your users POP3 access to their local inbox of phlyMail. The credentials to use are the same as of the frontend login.';
$modmsg['LegSettings'] = 'Settings';
$modmsg['LegState'] = 'Status of the service';
$modmsg['MaxChilds'] = 'Number of clients';
$modmsg['Port'] = 'Local port';
$modmsg['TimeoutAuth'] = 'Timeout Authentication';
$modmsg['TimeoutTrans'] = 'Timeout when logged in';
$modmsg['RunAs'] = 'Run as user';
$modmsg['ServiceName'] = 'Service\'s name';

$modmsg['AboutSettings'] = 'Here you can configure the POP3 server. Please note, that changes made here become active after restarting the server.';
$modmsg['AboutMaxChilds'] = 'To keep the load on your machine low, you should allow just as many clients as necessary.';
$modmsg['AboutPort'] = 'Depending on whether there\'s already a POP3 server running on this machine you should either choose the standard port 110 or another one for parallel services.';
$modmsg['AboutTimeouts'] = 'To make sure blocking clients don\'t lock up the service you should define reasonable timeout values here. We recommend timeouts of 30s for the authentication phase (before a client is logged in) and 60s for the transaction phase (after a client has logged in successfully). These values only affect the waiting period between two subsequent client inputs.';
$modmsg['AboutRunAs'] = 'Only enter a vlue here, if you manually or otherwise start the server as root, since only then the server will be able to become the desired user. In case you always wish to start the server from this frontend, please leave the field blank, but ensure, neither PHP does not run as root, nor does the POP3 server.';
$modmsg['AboutClearPW'] = 'The server offers various authentication methods. These are at the moment:<ul>
 <li>USER/PASS - classic, unsecure</li>
 <li>APOP - classic, more secure*</li>
 <li>AUTH LOGIN - modern, unsecure</li>
 <li>AUTH PLAIN - modern, unsecure</li>
 <li>AUTH CRAM-MD5 - modern, secure*</li>
</ul>
The entries marked with <strong>*</strong> require plain text password of the users in the database. Until now these passwords are saved in hashed form (MD5). TO allow these secure algorithms to be used plain text passwords need to be saved besides the hashed ones.';
$modmsg['DaemonRunning'] = 'The daemon is active';
$modmsg['DaemonNotRunning'] = 'The daemon is NOT active';
$modmsg['StopDaemon'] = 'Stop daemon';
$modmsg['StartDaemon'] = 'Start daemon';
