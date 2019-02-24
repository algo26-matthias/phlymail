# phlyMail Free

This repository is an archive snapshot of the latest release of phlyMail Free,
an open-source Webmail and PIM solution.
Please note, that I cannot provide you with any support, this piece of software is now fairly outdated.

## Installation guide phlyMail Free

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

The installer queries you for the MySQL table prefix to be used for the
created tables. As long as it does not collide with any existing table names,
you should accept the prefix as recommended by the installer.

After successful installation the installer will delete itself and
you can start phlyMail Free.

The installer creates the super administrator with the password and username
you defined for it. Log in with this data and do the detailed setup.
https://your-domain.tld/path/to/phlymail/config.php

After everything's set and done, run the frontend via:
https://your-domain.tld/path/to/phlymail/index.php or simply via:
https://your-domain.tld/path/to/phlymail/
