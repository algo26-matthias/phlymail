*******************************************************************************
*                    __    __      __  ___      _ __                          *
*             ____  / /_  / /_  __/  |/  /___ _(_) /                          *
*            / __ \/ __ \/ / / / / /|_/ / __ `/ / /                           *
*           / /_/ / / / / / /_/ / /  / / /_/ / / /                            *
*          / .___/_/ /_/_/\__, /_/  /_/\__,_/_/_/ Free                        *
*         /_/            /____/                                               *
* https://phlymail.com                            mailto:phlymail@phlylabs.de *
*******************************************************************************
* (c) 2001-2016 phlyLabs, Berlin                                              *
*******************************************************************************
*                                                                             *
*                     Installation und Konfiguration                          *
*                                                                             *
*******************************************************************************

Installationshinweise phlyMail Free
--------------------------------------------

Dieses Archiv enthält die Ordner 'phlymail' und 'Docs'.

'phlymail' enthält die Applikation phlyMail Free.

Kopieren Sie diesen Ordner in ein geeignetes Verzeichnis auf Ihrer Präsenz /
Ihrem Webserver, wo Sie PHP ausführen können.

Richten Sie dann die MySQL-Datenbank ein, die Sie für phlyMail verwenden
möchten. Die Tabellen werden durch den Installer angelegt.

Sobald Sie dies getan und die Zugriffsrechte korrekt vergeben haben (dabei ist
insbesondere darauf zu achten, daß sowohl die Zugriffsmaske per chmod
ausreichend gesetzt ist [wir empfehlen chmod -r 777 bzw.
chown -r <apacheuser>.<apachegroup>], als auch der Eigentümer von PHP
Schreibrechte auf die Ordnerstruktur von phlyMail hat), können Sie den
Installer ausführen, der die Grundkonfiguration vornimmt.

Ein Hinweis zu Apache und mod_php (PHP läuft als Modul und nicht als CGI):
-------------------------------------------------------------------------------
Achten Sie darauf, daß Sie den phlyMail-Ordner per chown auf den Nutzer und die
Gruppe übertragen, unter denen der Apache läuft. Dazu müssen Sie i.d.R.
root-Rechte haben bzw. Komandos per sudo ausführen können.
Können Sie dies nicht tun, sollte es genügen, den phlyMail-Ordner per
chmod -r 777 für alle les- und schreibbar zu machen.
Beachten Sie aber, dass es im Allgemeinen ausreicht, die Struktur mit der Maske
755 bzw. 775 zu versehen. Je lockerer die Rechte vergeben werden, desto
unsicherer ist Ihre Installation.
-------------------------------------------------------------------------------

Der Aufruf des Installers erfolgt mit
https://ihre-domain.tld/pfad/zu/phlymail/installer.php

Während der Installation werden der Präfix für die zu verwendenden
MySQL-Tabellen abgefragt. Sollte dies nicht mit bereits vorhandenen Tabellen in
der gewählten DB kollidieren, empfehlen wir, den vom Installer gemachten
Vorschlag zu übernhemen.

Nach erfolgreicher Installation löscht sich der Installer selbst und
phlyMail ist grundsätzlich betriebsbereit.

Mit dem Installer wurde ein Administrator mit dem von Ihnen vergebenen
Usernamen und Paßwort eingerichtet. Loggen Sie sich mit diesen Daten in die
Konfigurationsboerfläche ein und nehmen Sie nun die Detailkonfiguration von
phlyMail vor:
https://ihre-domain.tld/pfad/zu/phlymail/config.php

Der Aufruf der Applikation erfolgt später mit:
https://ihre-domain.tld/pfad/zu/phlymail/index.php oder einfach nur
https://ihre-domain.tld/pfad/zu/phlymail/


Weitere Möglichkeiten
---------------------

Bitte beachten Sie auch: https://phlymail.com/forum/tipps-tricks-f28.html

Bei Problemen besuchen Sie bitte unser Forum unter https://phlymail.com/forum/

Viel Spaß wünscht

Das Team von phlyLabs