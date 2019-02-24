<?php
/**
 * @package phlyGallery 1.0
 * @copyright 2010-2011 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.1 2011-03-12
 */
class Tree_Tag extends Tree_Dir
{
    public function __construct($id = null)
    {
        parent::__construct('tag', $id);
    }
}
?>