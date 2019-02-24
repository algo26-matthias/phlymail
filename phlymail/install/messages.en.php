<?php
/**
 * Messages file for the phlyMail Installer
 * en (English)
 * 2013-02-04 
 */

$WP_msg['language'] = 'en';
$WP_msg['tz'] = 'Europe/London';

$WP_msg['NoChangeSetting'] = 'Could not change setting for $1 in the choices.ini.php.';
$WP_msg['NotUpdateConfF'] = 'It seems I could not update the configuration file';
$WP_msg['NoOpenDB'] = 'I could not open the DB. Please check your settings.';
$WP_msg['NoFindRunDB'] = 'It seems like I could not find and run the driver you chose.';
$WP_msg['NoValidLKey'] = 'This is not a valid licence key. Please try again.';
$WP_msg['NoSaveLKey'] = 'Couldn\'t save licence key.';
$WP_msg['AccessBlock'] = 'It seems, that there will be problems with installing and running phlyMail due to insufficent file permissions. PHP must be able to write to certain folders and files.<br />Make sure, that the file permissions are set correctly on these files and folders:.<br /><ul><li>phlymail/choices.ini.php (755, 775)</li><li>phlymail/storage/config/ (777)</li><li>phlymail/storage/ (777)</li><li>phlymail/install/ (777)</li><li>phlymail/installer.php (755, 775)</li></ul>To fix it, try "chmod -R 777 phlymail/storage/config/", where you should type the value given above for 777. If more than one value is specified in parantheses, try the first value at first, then the second.<br />Repeat this step for all the mentioned files and folders.';
$WP_msg['HeadInstall'] = 'phlyMail Installation';
$WP_msg['Greeting'] = 'We are setting some basic configuration options here to allow you to start phlyMail.\nMost of the settings can be made later in the Config Interface.';
$WP_msg['German'] = 'Deutsch';
$WP_msg['English'] = 'English';
$WP_msg['HeadStep1'] = 'Setup basic paths';
$WP_msg['AboutDriver'] = 'Which of the available database driver(s) shall be used?';
$WP_msg['Driver'] = 'DB driver';
$WP_msg['AboutLang'] = 'Please choose the default language of phlyMail\'s FrontEnd and Config Interface.';
$WP_msg['Language'] = 'Language';
$WP_msg['AboutSkin'] = 'Which default theme do you wish to use?';
$WP_msg['Skin'] = 'Theme';
$WP_msg['HeadStep2'] = 'Setup database connection';
$WP_msg['AboutDriverSetup'] = 'Please fill in the requested fields.';
$WP_msg['HeadStep3'] = 'Importing Licence Key';
$WP_msg['FoundValidLKey'] = 'I have found a valid licence key inside your configuration folder';
$WP_msg['FoundInvalidLKey'] = 'The licence key found inside your configuration dir is not valid. Please import a valid one. Please make sure, that you have entered your personal data correctly. Pay special attention to the capitalization.';
$WP_msg['ImportLKey'] = 'Please import your licence key here by uploading it from your local machine or entering it in the text box. Please enter your name as you did when ordering phlyMail. Your customer number has been sent to you in the order receipt email. The licence key was part 2 of your order.';
$WP_msg['LKeyFirstname'] = 'First Name';
$WP_msg['LKeySurtname'] = 'Surname';
$WP_msg['LKeyCustomer'] = 'Customer No.';
$WP_msg['RemoveDir'] = 'Removing installation dir';
$WP_msg['RemoveMe'] = 'Removing myself';
$WP_msg['InstComplete'] = 'Installation complete. You can now start phlyMail\'s Config Interface.';
$WP_msg['CompleteManually'] = 'Please complete installation by proceeding the steps above manually.\nPlease run the Config interface manually afterwards.';
$WP_msg['Failed'] = 'FAILED';
$WP_msg['Success'] = 'SUCCESS';
// Since 4.5.0
$WP_msg['LanguageConfig'] = 'Language of Config';
$WP_msg['Administrator'] = 'Create Administrator';
$WP_msg['AboutAdmin'] = 'Please fill in the fields for the administrative user.';
$WP_msg['Username'] = 'Username';
$WP_msg['Password'] = 'Password';
$WP_msg['Repeat'] = 'Repeat password';
$WP_msg['FEUser'] = 'Create Frontend User';
$WP_msg['AboutFEUser'] = 'This is the account you will login to the fronten (dekstop and mobile) and the API.';
$WP_msg['PWsDoNotMatch'] = 'The passwords do not match';
$WP_msg['LicenceKey'] = 'Licence Key';
$WP_msg['StartInstall'] = 'Install!';
$WP_msg['ECompleteForm'] = 'Please fill in the form completely before starting the installation';
$WP_msg['HeadCleanup'] = 'Finish Installation';
$WP_msg['AboutFinal'] = 'I am now running finailising tasks.';
$WP_msg['HeadConfig'] = 'Running the Conifg Interface';
$WP_msg['GoConfig'] = 'Go to Config';
$WP_msg['HeadLinks'] = 'More detailed Information';
$WP_msg['ExtraLinks'] = '<p>Don\'t forget to <a href="http://phlymail.com/forum/install-phlymail-s-background-services-t2500.html" target="_blank">setup the Cronjob</a> for the automated background tasks.</p><p>Please do always consult our <a href="http://phlymail.com/forum/" target="_blank">Support Board</a> for free and thorough support with problems or to get answers to specific questions.</p>';

