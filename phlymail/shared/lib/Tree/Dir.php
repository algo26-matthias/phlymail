<?php
/**
 * @package phlyGallery 1.0
 * @copyright 2010-2011 phlyLabs Berlin, http://phlylabs.de
 * @version 0.0.1 2011-03-12
 */
abstract class Tree_Dir extends Tree_Node
{
    public function __construct($table, $id = null)
    {
        parent::__construct($table, $id);
    }
}
?>