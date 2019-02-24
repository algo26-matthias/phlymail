<?php
/**
 * extending functionality for SabreDAV
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.7 2015-03-12 
 */
class phlyDAV_Dir extends Sabre_DAV_Node implements Sabre_DAV_ICollection
{
    protected $principalId;
    protected $handler;
    protected $path;

    public function __construct($principalId, $handler, $path)
    {
        $this->principalId = $principalId;
        $this->handler     = $handler;
        $this->path       = $path;
    }

    /**
     * Deleted the current node
     *
     * @return void
     */
    public function delete()
    {
        if ($this->path == '/') {
            throw new Sabre_DAV_Exception_BadRequest('Cannot delete root level');
        }
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            throw new Sabre_DAV_Exception_FileNotFound('File at location ' . $this->path . ' not found');
        }
        require_once($_PM_['path']['handler'].'/'.$this->handler.'/api.php');
        if (!in_array('remove_dir', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_FileNotFound('File at location ' . $this->path . ' not found');
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        return $api->remove_dir($this->path);
    }

    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return $this->path == '/' ? $this->handler : basename($this->path).'/';
    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name)
    {
        if ($this->path == '/') {
            new Sabre_DAV_Exception_BadRequest('Cannot rename root level');
        }
        if (!strlen($name)) {
            new Sabre_DAV_Exception_BadRequest('Cannot rename to nothing');
        }
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        if (!in_array('rename_dir', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        $api->rename_dir($this->path, $name);
    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     */
    public function getLastModified()
    {
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            return null;
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        $info = $api->resolve_path($this->path, true);
        return (empty($info) || empty($info['item']['mtime'])) ? false : $info['item']['mtime'];
    }

    /**
     * Creates a new file in the directory
     *
     * data is a readable stream resource
     *
     * @param string $name Name of the file
     * @param resource $data Initial payload
     * @return void
     */
    public function createFile($name, $data = null)
    {
        if ($this->childExists($name)) {
            throw new Sabre_DAV_Exception_BadRequest('File / folder of that name already exists');
        }
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        if (!in_array('save_item', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        if (!strlen($name)) {
            $name = uniqid();
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        $state = $api->save_item(array('path_canon' => $this->path, 'friendlyname' => $name), $data);
        if (!$state) {
            throw new Sabre_DAV_Exception_BadRequest('Error creating the file');
        }
    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @return void
     */
    public function createDirectory($name)
    {
        if ($this->childExists($name)) {
            throw new Sabre_DAV_Exception_BadRequest('File / folder of that name already exists');
        }
        if (!strlen($name)) {
            throw new Sabre_DAV_Exception_BadRequest('Please specify the folder\'s name');
        }
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        if (!in_array('create_dir', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        $state = $api->create_dir($this->path, $name);

        if (!$state) {
            throw new Sabre_DAV_Exception_BadRequest('Error creating the directory');
        }
    }

    /**
     * Returns a specific child node, referenced by its name
     *
     * @param string $name
     * @return Sabre_DAV_INode
     */
    public function getChild($name)
    {
        foreach ($this->getChildren() as $item) {
            if ($item->getName() == $name) {
                return $item;
            }
        }
        throw new Sabre_DAV_Exception_FileNotFound('File not found: ' . $name);
    }

    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren()
    {
        if (is_null($this->handler) && $this->path == '/') {
            return $this->listHandlers();
        }
        global $_PM_;
        $call = 'handler_'.$this->handler.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.$this->handler.'/api.php')) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        if (!in_array('resolve_path', get_class_methods($call))) {
            throw new Sabre_DAV_Exception_NotImplemented('Not implemented for this handler');
        }
        $api = new $call($_PM_, PHM_API_UID, $this->principalId);
        $return = array();
        foreach ($api->give_folderlist() as $folder) {
            if ($this->path == $folder['path_canon']) {
                continue;
            }
            if (dirname($folder['path_canon']) == $this->path) {
                $return[] = new phlyDAV_Dir($this->principalId, $this->handler, $folder['path_canon']);
            }
        }
        foreach ($api->give_itemlist(null, $this->path) as $file) {
            $return[] = new phlyDAV_File($this->principalId, $this->handler, $file);
        }
        return $return;
    }

    /**
     * Checks for given child's existence
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name)
    {
        foreach ($this->getChildren() as $item) {
            if ($item->getName() == $name) {
                return true;
            }
        }
        return false;
    }

    protected function listHandlers()
    {
        global $_PM_;
        $return = array();
        foreach (scandir($_PM_['path']['handler']) as $f) {
            if ('.' == $f || '..' == $f) {
                continue;
            }
            if (!file_exists($_PM_['path']['handler'].'/'.$f.'/api.php')) {
                continue;
            }
            require_once($_PM_['path']['handler'].'/'.$f.'/api.php');
            if (!in_array('resolve_path', get_class_methods('handler_'.$f.'_api'))) {
                continue;
            }
            $return[] = new phlyDAV_Dir($this->principalId, $f, '/');
        }
        return $return;
    }
}
