<?php
/**
 * Just display an about page nicely ...
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @subpackage Handler Core
 * @copyright 2005-2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2012-05-03 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
$tpl = new phlyTemplate($_PM_['path']['templates'].'core.about.tpl');
if (file_exists($_PM_['path']['conf'].'/build.name')) {
    $product = file_get_contents($_PM_['path']['conf'].'/build.name');
} else {
    $product = '&lt;unknown&gt;';
}
if (file_exists($_PM_['path']['conf'].'/current.build')) {
    $version = version_format(trim(file_get_contents($_PM_['path']['conf'].'/current.build')));
} else {
    $product = '&lt;broken install&gt;';
}
$tpl->assign(array
        ('about' => $WP_msg['About']
        ,'about_product' => $product
        ,'about_version' => $version
        ));
$load = false; // Make themes.php load no outer tpl
