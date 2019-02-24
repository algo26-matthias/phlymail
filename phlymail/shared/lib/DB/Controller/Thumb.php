<?php
/**
 * XNA - eXternal No Auth requests
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.2 2013-02-10 
 */
class DB_Controller_Thumb extends DB_Controller
{
    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['core_thumbs'] = $this->DB['db_pref'].'core_thumbs';
    }

    /**
     * Adds a thumbnail to the database
     *
     * @param string $handler  The handler this thumb belongs to
     * @param int $item The item ID within the handler the thumb belongs to
     *[@param string $type An optional type of thumb, which can be used to store various sizes of thumbs per item]
     * @param string $mime  MIME type of the thumb, usually one of the image/* subtypes
     *[@param int $l The strlen() of the thumbnail stream in bytes]
     *[@param int $w The effective width of the thumbnail (NOT the boundary size!)]
     *[@param int $h The effective height of the thumbnail (NOT the boundary size!)
     * @param string $stream The raw binary thumbnail image data
     * @return unknown
     */
    public function add($handler, $item, $type = '', $mime, $l = 0, $w = 0, $h = 0, $stream)
    {
        $this->query('INSERT INTO '.$this->Tbl['core_thumbs'].' SET `uuid`="'.basics::uuid().'",`handler`="'.$this->esc($handler).'"'
                .',`item`='.intval($item).',`type`="'.$this->esc($type).'",`mime`="'.$this->esc($mime).'"'
                .',`len`='.intval($l).',`w`='.intval($w).',`h`='.intval($h).',`body`="'.addslashes($stream).'"');
        return $this->insertid();
    }

    public function drop($handler, $item, $type = null)
    {
        return $this->query('DELETE FROM '.$this->Tbl['core_thumbs'].' WHERE `handler`="'.$handler.'" AND `item`='.intval($item)
                .(!is_null($type) ? ' AND `type`="'.$this->esc($type).'"' : ''));
    }

    public function get($handler, $item, $type = null)
    {
        $qid = $this->query('SELECT `mime`, `len` size, `w` width, `h` height, `body` as `stream`,`uuid` FROM '.$this->Tbl['core_thumbs']
                .' WHERE `handler`="'.$handler.'" AND `item`='.intval($item)
                .(!is_null($type) ? ' AND `type`="'.$this->esc($type).'"' : '').' LIMIT 1');
        if (!$this->numrows($qid)) return false; // No thumb found
        return $this->assoc($qid);
    }
}
