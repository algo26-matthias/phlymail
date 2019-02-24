<?php
/**
 * Messages file for the phlyMail Installer
 * de (German)
 * 2013-02-08 
 */

$WP_msg['language'] = 'de';
$WP_msg['tz'] = 'Europe/Berlin';

$WP_msg['NoChangeSetting'] = 'Konnte die Einstellung für $1 in der choices.ini.php nicht ändern.';
$WP_msg['NotUpdateConfF'] = 'Konnte die Konfigurationsdatei choices.ini.php nicht ändern.';
$WP_msg['NoOpenDB'] = 'Konnte die DB nicht öffnen. Bitte prüfen Sie Ihre Einstellungen.';
$WP_msg['NoFindRunDB'] = 'Konnte den gewählten DB-Treiber nicht finden oder starten.';
$WP_msg['NoValidLKey'] = 'Dies ist kein valider Lizenzschlüssel.';
$WP_msg['NoSaveLKey'] = 'Konnte den Lizenzschlüssel nicht speichern.';
$WP_msg['AccessBlock'] = 'Wahrscheinlich haben Sie Probleme bei der Installation und dem Betrieb von phlyMail, da die Dateizugriffsrechte nicht stimmen. PHP benötigt Schreibberechtigung für einige Ordner und Dateien.<br />Stellen Sie sicher, dass die Zugriffsrechte der folgenden Dateien und Ordner hinreichend locker gesetzt sind:<br /><ul><li>phlymail/choices.ini.php (755, 775)</li><li>phlymail/storage/config/ (777)</li><li>phlymail/storage/ (777)</li><li>phlymail/install/ (777)</li><li>phlymail/installer.php (755, 775)</li></ul>Zur Korrektur der Zugriffsrechte probieren Sie bitte "chmod -R 777 phlymail/storage/config/", wobei Sie anstelle von 777 den oben in Klammern genannten Wert eintragen. Bei mehreren durch Komma getrennte Werte probieren Sie bitte erst den ersten, danach den zweiten Wert. Wiederholen Sie dies für alle oben erwähnten Dateien und Ordner.<br /><br />Reduzieren Sie die Rechte nach der Installation gemäß unserer <a href="http://phlymail.com/forum/erhoehen-sie-die-sicherheit-ihrer-installation-t2481.html" target="_blank">Hinweise im Forum</a>';
$WP_msg['HeadInstall'] = 'Installation';
$WP_msg['Greeting'] = 'Wir treffen nun einige Grundeinstellungen, um phlyMail starten zu können.\nDen größten Teil der Einstellungen können Sie später jedoch über die Config-Oberfläche vornehmen.';
$WP_msg['German'] = 'Deutsch';
$WP_msg['English'] = 'English';
$WP_msg['HeadStep1'] = 'Grundeinstellungen';
$WP_msg['AboutDriver'] = 'Welchen der verfügbaren Datenbank-Schnittstellen wollen Sie verwenden?';
$WP_msg['Driver'] = 'DB-Treiber';
$WP_msg['AboutLang'] = 'Bitte wählen Sie die Standardsprache von phlyMails FrontEnd und der Config-Oberfläche.';
$WP_msg['Language'] = 'Sprache';
$WP_msg['AboutSkin'] = 'Welches Standard-Theme möchten Sie verwenden?';
$WP_msg['Skin'] = 'Theme';
$WP_msg['HeadStep2'] = 'Datenbankverbindung konfigurieren';
$WP_msg['AboutDriverSetup'] = 'Bitte geben Sie die abgefragten Werte ein und klicken Sie danach auf "Testen" um sie zu prüfen.';
$WP_msg['HeadStep3'] = 'Lizenzschlüssel importieren';
$WP_msg['FoundValidLKey'] = 'Ich habe einen gültigen Lizenzschlüssel in Ihrem Konfigurationsverzeichnis gefunden';
$WP_msg['FoundInvalidLKey'] = 'Der vorhandene Lizenzschlüssel ist nicht gültig. Bitte importieren Sie einen gültigen. Prüfen Sie auch, ob Sie Ihre Daten korrekt eingegeben haben. Achten Sie dabei insbesondere auf Groß-/Kleinschreibung.';
$WP_msg['ImportLKey'] = 'Bitte importieren Sie hier Ihren Lizenzschlüssel, indem Sie ihn von Ihrem Rechner hochladen oder im Textfeld eingeben. Geben Sie Ihre Daten bitte so ein, wie Sie dies bei der Bestellung getan haben. Ihre Kundennummer finden Sie in der Bestellbestätigunsemail, Ihren Lizenzschlüssel haben wir Ihnen per Email zugesandt.';
$WP_msg['LKeyFirstname'] = 'Vorname';
$WP_msg['LKeySurtname'] = 'Nachname';
$WP_msg['LKeyCustomer'] = 'Kundennummer';
$WP_msg['RemoveDir'] = 'Entferne das Installationsverzeichnis';
$WP_msg['RemoveMe'] = 'Entferne mich selbst (installer.php)';
$WP_msg['InstComplete'] = 'Die Installation ist komplett. Sie können nun phlyMails Config-Oberfläche starten, um die weitere Konfiguration Ihrer Installation vorzunehmen:';
$WP_msg['CompleteManually'] = 'Bitte vollenden Sie die Installation, indem Sie die oben angegebenen Schritte manuell durchführen.';
$WP_msg['Failed'] = 'Fehlgeschlagen';
$WP_msg['Success'] = 'Erfolgreich';
// Since 4.5.0
$WP_msg['LanguageConfig'] = 'Sprache der Config';
$WP_msg['Administrator'] = 'Administrator anlegen';
$WP_msg['AboutAdmin'] = 'Geben sie im Folgenden die gewünschten Zugangsdaten für den administrativen User ein.';
$WP_msg['Username'] = 'Benutzername';
$WP_msg['Password'] = 'Passwort';
$WP_msg['Repeat'] = 'Passwort wiederholen';
$WP_msg['PWsDoNotMatch'] = 'Die Passwörter sind nicht gleich';
$WP_msg['FEUser'] = 'Frontend-Benutzer anlegen';
$WP_msg['AboutFEUser'] = 'Mit diesem Benutzter loggen Sie sich am Frontend (Desktop und/oder mobil) und bei der API ein.';
$WP_msg['LicenceKey'] = 'Lizenzschlüssel';
$WP_msg['StartInstall'] = 'Installieren!';
$WP_msg['ECompleteForm'] = 'Bitte füllen Sie das Formular komplett aus, bevor Sie auf Installieren klicken';
$WP_msg['HeadCleanup'] = 'Installation fertigstellen';
$WP_msg['AboutFinal'] = 'Ich führe abschließende Aufräumarbeiten durch.';
$WP_msg['HeadConfig'] = 'Config-Oberfläche aufrufen';
$WP_msg['GoConfig'] = 'Zur Config';
$WP_msg['HeadLinks'] = 'Weiter führende Informationen';
$WP_msg['ExtraLinks'] = '<p>Vergessen Sie nicht, <a href="http://phlymail.com/forum/cronjobs-einrichten-ab-phlymail-4.4-t2498.html" target="_blank">den Cronjob</a> für die automatischen Aufgaben einzurichten.</p><p>Lesen Sie auch unsere <a href="http://phlymail.com/forum/erhoehen-sie-die-sicherheit-ihrer-installation-t2481.html" target="_blank">Hinweise zur Sicherheit Ihrer Installation</a>.</p>';
