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
*                     Installation and Configuration                          *
*                                                                             *
*******************************************************************************

Installation guide phlyMail Free
--------------------------------

This archive contains the folders 'phlymail' and 'Docs'.

'phlymail' contains the application phlyMail Free.

Copy this folder anywhere suitable on your web server, where you can run PHP.

Then set up the MySQL database you want to use for phlyMail. The installer
will create the tables it needs.

After you have done this and have set appropriate file permissions
(recommended: 700 for files and folders) and an appropriate owner, which
should - in general - be the same as the one of PHP, on the phlymail/ folder and
its contents you can run the installer, which will do the basic configuration.

Start it by typing into your browser's URL bar:
https://your-domain.tld/path/to/phlymail/installer.php

The installer queries you for the MySQL table prefix for use with the
needed tables. As long as it does not collide with any existing table names,
you should accept the prefix as recommended by the installer.

After successfull installation the installer will delete itself and
you can start phlyMail MessageCenter.

The installer creates the super administrator with the password and username
you defined for it. Log in with this data and do the detailed setup.
https://your-domain.tld/path/to/phlymail/config.php

After everything's set and done, run the frontend via:
https://your-domain.tld/path/to/phlymail/index.php or simply via:
https://your-domain.tld/path/to/phlymail/


Advanced tools
--------------

Please note: https://phlymail.com/forum/tipps-tricks-f29.html

In case you run into problems - just visit or support board at
https://phlymail.com/?forum

The team of https://phlymail.com
A division of phlyLabs