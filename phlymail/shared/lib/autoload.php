<?php
/**
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage core application
 * @copyright 2011-2013 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.2 2013-02-05 
 */
function classAutoLoad($className)
{
    if (strpos($className, 'handler_') === 0) {
        $fileName = $GLOBALS['_PM_']['path']['handler'].'/'.str_replace(array('_', '\\'), '/', strtolower(substr($className, 8))).'.php';
    } else {
        $fileName = $GLOBALS['_PM_']['path']['lib'].'/'.str_replace(array('_', '\\'), '/', $className).'.php';
    }
    $fileName = str_replace('/', DIRECTORY_SEPARATOR, $GLOBALS['_PM_']['path']['base'].'/'.$fileName);
    if (file_exists($fileName)) {
        require_once $fileName;
    } else {
        throw new Exception($className.' not found at '.$fileName);
    }
}

spl_autoload_register('classAutoLoad');
