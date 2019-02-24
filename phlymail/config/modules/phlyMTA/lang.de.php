<?php
/**
* Messages File for the phlyMTA Mail Server
* Deutsch (de)
* @version 0.1
*/

$modmsg['phlyMTA'] = 'phlyMTA';
$modmsg['MenPOP3Service'] = 'POP3-Server';
$modmsg['MenSMTPService'] = 'SMTP-Server';
$modmsg['MenSMTPRelay'] = 'SMTP-Relay';
$modmsg['MenSettings'] = 'Allgemeine Einstellungen';

$modmsg['AboutPOP3d'] = 'Mit dem POP3-Server des phlyMTAs können Sie Ihren Usern POP3-Zugriff auf ihren Posteingangsordner in phlyMail zu Verfügung stellen. Die Zugangsdaten zum System sind die gleichen wie für den Login am Frontend.';
$modmsg['LegSettings'] = 'Einstellungen';
$modmsg['LegState'] = 'Status des Dienstes';
$modmsg['MaxChilds'] = 'Anzahl Clients';
$modmsg['Port'] = 'Lokaler Port';
$modmsg['TimeoutAuth'] = 'Timeout Anmeldung';
$modmsg['TimeoutTrans'] = 'Timeout nach Anmeldung';
$modmsg['RunAs'] = 'Laufe als User';
$modmsg['ServiceName'] = 'Servicename';

$modmsg['AboutSettings'] = 'Konfigurieren Sie hier den POP3-Server. Bitte beachten sie, dass Änderungen an der Konfiguration erst nach einem Neustart des Servers wirksam werden.';
$modmsg['AboutMaxChilds'] = 'Um die Last auf Ihrem Server so gering wie möglich zuhalten, sollten Sie nur soviele maximale Clients wie nötig zulassen.';
$modmsg['AboutPort'] = 'Je nachdem, ob auf dieser Maschine bereits ein POP3-Server läuft, sollten Sie für den Port den Standardport 110 oder einen davon abweichenden Port für den Parallelbetrieb verwenden.';
$modmsg['AboutTimeouts'] = 'Um sicherzustellen, dass blockierende Clients nicht die Erreichbarkeit des Servers beeinträchtigen, sollten Sie hier vernünftige Timeout-Werte eintsellen. Es empfiehlt sich ein Timeout von ca. 30s für die Anmeldephase (bevor ein Client eingeloggt ist) und ca. 60s für die Übertragungsphase (nach dem Login des Clients). Diese Werte betreffen nur die Wartezeit zwischen 2 Eingaben des Clients.';
$modmsg['AboutRunAs'] = 'Tragen Sie hier nur dann einen Wert ein, wenn Sie den Server manuell oder anderweitig als User root starten, da sich der Server nur dann selbst einem anderen User zuordnen kann. Möchten Sie den Server immer hier über das Frontend starten können, lassen Sie den Wert leer, stellen Sie dann aber sicher, dass der Server nicht als root läuft.';
$modmsg['AboutClearPW'] = 'Der Server stellt verschiedene Anmeldeverfahren zur Verfügung. Im Moment sind dies:<ul>
 <li>USER/PASS - klassisch, unsicher</li>
 <li>APOP - klassisch, sicherer*</li>
 <li>AUTH LOGIN - modern, unsicher</li>
 <li>AUTH PLAIN - modern, unsicher</li>
 <li>AUTH CRAM-MD5 - modern, sicher*</li>
</ul>
Die mit <strong>*</strong> markierten Anmeldeverfahren benötigen ein Klartextpasswort des Users in der Datenbank. Bisher werden die Passwörter der User allerdings als MD5-Hash gespeichert, sind somit also nicht mehr im Klartext vorhanden. Damit Ihre User die sicheren Verfahren nutzen können, muss also parallel zum sicheren Passwort noch ein Klartextpasswort für den Zugang zum POP3-Server hinterlegt werden.';
$modmsg['DaemonRunning'] = 'Der Daemon ist aktiv';
$modmsg['DaemonNotRunning'] = 'Der Daemon ist nicht aktiv';
$modmsg['StopDaemon'] = 'Daemon anhalten';
$modmsg['StartDaemon'] = 'Daemon starten';
