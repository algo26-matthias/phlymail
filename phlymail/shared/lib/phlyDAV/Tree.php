<?php
/**
 * extending functionality for SabreDAV
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.8 2015-03-12 
 */

class phlyDAV_Tree extends Sabre_DAV_Tree
{
    protected $handler;

    /**
     * Returns a new node for the given path
     *
     * @param string $path
     * @return void
     */
    public function getNodeForPath($path)
    {
        global $_PM_;
        if (!$path || $path == '/') {
            return new phlyDAV_Dir(0, null, '/');
        }

        if ($path == basename($path)) {
            $principal = $path;
            $path = '/';
        } elseif ($path == basename(dirname($path)).'/'.basename($path)) {
            list ($principal, $handler) = explode('/', ltrim($path, '/'), 2);
            $path = '/';
        } else {
            list ($principal, $handler, $path) = explode('/', ltrim($path, '/'), 3);
            $path = '/'.$path; // Cannonical paths get stored WITH a leading slash
        }

        $principalID = $GLOBALS['DB']->checkfor_username($principal, true);
        if (!$principalID) {
            throw new Sabre_DAV_Exception_FileNotFound('User '.$principal.' not found');
        }

        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/api.php')) {
            throw new Sabre_DAV_Exception_FileNotFound('File at location '.$path.' not found');
        }
        require_once($_PM_['path']['handler'].'/'.$handler.'/api.php');
        $call = 'handler_'.$handler.'_api';
        if (!in_array('resolve_path', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_FileNotFound('File at location '.$path.' not found');
        }
        $api = new $call($_PM_, PHM_API_UID, $principalID);
        $info = $api->resolve_path($path, true);

        if ($info['type'] == 'd') {
            return new phlyDAV_Dir($principalID, $handler, $path);
        } elseif ($info['type'] == 'f') {
            return new phlyDAV_File($principalID, $handler, $info['item']);
        } else {
            throw new Sabre_DAV_Exception_FileNotFound('File at location '.$path.' not found');
        }
    }

    public function nodeExists($path)
    {
        if (!$path || $path == '/') {
            return true;
        }

        if ($path == basename($path)) {
            $principal = $path;
            $path = '/';
        } elseif ($path == basename(dirname($path)).'/'.basename($path)) {
            list ($principal, $handler) = explode('/', ltrim($path, '/'), 2);
            $path = '/';
        } else {
            list ($principal, $handler, $path) = explode('/', ltrim($path, '/'), 3);
            $path = '/'.$path; // Cannonical paths get stored WITH a leading slash
        }

        $principalID = $GLOBALS['DB']->checkfor_username($principal, true);
        if (!$principalID) {
            return false;
        }

        global $_PM_;
        if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/api.php')) {
            return false;
        }
        require_once($_PM_['path']['handler'].'/'.$handler.'/api.php');
        $call = 'handler_'.$handler.'_api';
        if (!in_array('resolve_path', get_class_methods($call))) {
            return false;
        }
        $api = new $call($_PM_, PHM_API_UID, $principalID);
        $info = $api->resolve_path($path, true);
        if ($info['type'] == 'd') {
            return true;
        } elseif ($info['type'] == 'f') {
            return true;
        } else {
            return false;
        }
    }
}
