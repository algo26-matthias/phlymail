<?php
/**
 * extending functionality for SabreDAV
 * @package phlyMail Nahariya 4.0+ Default branch
 * @subpackage WebDAV server
 * @copyright 2009-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.7 2015-03-12 
 */
class phlyDAV_File extends Sabre_DAV_Node implements Sabre_DAV_IFile
{
    protected $principalId;
    protected $handler;
    protected $item;
    protected $api;

    public function __construct($principalId, $handler, $item)
    {
        $this->principalId = $principalId;
        $this->handler     = $handler;
        $this->item        = $item;
    }

    /**
     * Deleted the current node
     *
     * @return void
     */
    public function delete()
    {
        global $_PM_;
        $api = 'handler_'.$this->handler.'_api';
        $this->api = new $api($_PM_, PHM_API_UID, $this->principalId);
        $this->api->remove_item($this->item['id']);
    }

    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return $this->item['friendly_name'];
    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name)
    {
        global $_PM_;
        $api = 'handler_'.$this->handler.'_api';
        $this->api = new $api($_PM_, PHM_API_UID, $this->principalId);
        $this->api->rename_item($this->item['id'], $name);
    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     */
    public function getLastModified()
    {
        return ($this->item['mtime'] ? $this->item['mtime'] : ($this->item['ctime'] ? $this->item['ctime'] : time()));
    }

    /**
     * Updates the data
     *
     * The data argument is a readable stream resource.
     *
     * @param resource $data
     * @return void
     */
    public function put($data)
    {
        global $_PM_;
        $api = 'handler_'.$this->handler.'_api';
        $this->api = new $api($_PM_, PHM_API_UID, $this->principalId);
        $this->api->update_item_content($this->item['id'], $data);
    }

    /**
     * Returns the data
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    public function get()
    {
        global $_PM_;
        $api = 'handler_'.$this->handler.'_api';
        $this->api = new $api($_PM_, PHM_API_UID, $this->principalId);
        return $this->api->read_item_content($this->item['id']);
    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return void
     */
    public function getContentType()
    {
        return $this->item['type'];
    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return void
     */
    public function getETag()
    {
        return $this->item['uuid'];
    }

    /**
     * Returns the size of the node, in bytes
     *
     * @return int
     */
    public function getSize()
    {
        return $this->item['size'];
    }
}
