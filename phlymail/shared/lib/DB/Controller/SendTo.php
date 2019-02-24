<?php
/**
 * Send To mappings
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.1 2013-01-30 
 */
class DB_Controller_SendTo extends DB_Controller
{
    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['sendto_handler'] = $this->DB['db_pref'].'sendto_handler';
    }

    /**
     * This is used by a handler, to add capabilities for certain mimetypes to the global
     * SendTo management table.
     * To signal, that your handler basically handles everything, just pass array('%' => 'accept').
     * To tell, you will handle all text files but HTML, pass array('text/%' => 'accept', 'text/html' => 'ignore').
     *
     * @param array $mimetypes Pass all MIME types here. Keys: MIME type, Values 'accept' or 'ignore'.
     * @param string $handler Handler's internal name, e.g. calendar or files
     * @param bool $on_context Whether to show a context menu entry, Default: true
     * @param bool $on_fetch Whether to be included in mail fetching filters; Default: false
     * @return bool
     * @since 3.9.7
     */
    public function addMimeHandler($mimetypes, $handler, $on_context = 1, $on_fetch = 0)
    {
        if (!$handler) return false;
        $q_l = 'INSERT INTO '.$this->Tbl['sendto_handler'].' (`behaviour`, `mimetype`, `handler`, `on_context`, `on_fetch`) ';
        $q_r = array();
        foreach ($mimetypes as $type => $behave) {
            $q_r[] = 'VALUES ("'.($behave == 'accept' ? 'accept' : 'ignore').'", "'.$this->esc($type).'"'
                    .', "'.$this->esc($handler).'", "'.($on_context).'", "'.($on_fetch).'")';
        }
        return $this->query($q_l.implode(',', $q_r));
    }

    /**
     * Removes all SendTo entries associated with a certain handler
     *
     * @param string $handler
     * @return bool
     * @since 3.9.7
     */
    public function removeMimeHandler($handler)
    {
        return $this->query('DELETE FROM '.$this->Tbl['sendto_handler'].' WHERE `handler`="'.$this->esc($handler).'"');
    }

    /**
     * Returns a list of known handlers, which can handle a given MIME type
     *
     * @param string $mimetype
     * @return array  A list of handlers, which can handle the given MIME type
     * @since 3.9.7
     */
    public function getMimeHandlers($type)
    {
        $type = $this->esc($type);
        $return = array();
        $res = $this->query('SELECT `handler` FROM '.$this->Tbl['sendto_handler']
                .' WHERE (`behaviour`="accept" AND "'.$type.'" LIKE `mimetype`) AND NOT (`behaviour`="ignore" AND "'.$type.'" LIKE `mimetype`) GROUP BY `handler`');
        while (list($handler) = $this->fetchrow($res)) {
            $return[] = $handler;
        }
        return $return;
    }

    /**
     * This returns a list of all supported (or ignored) MIME types for a specific handler.
     * Useful for checks on updates.
     *
     * @param string $handler
     * @return array Keys: MIME types, values: 'accept' or 'ignore'
     * @since 3.9.7
     */
    public function handlerSupports($handler)
    {
        $res = $this->query('SELECT `behaviour`, `mimetype` FROM '.$this->Tbl['sendto_handler'].' WHERE `handler`="'.$this->esc($handler).'"');
        $return = array();
        while (list($behave, $type) = $this->fetchrow($res)) {
            $return[$type] = $behave;
        }
        return $return;
    }

    /**
     * Management method for checking, whether the SendTo DB needs updates. Simply
     * returns all entries of the DB
     *
     * @return array
     * @since 3.9.8
     */
    public function listAll()
    {
        $res = $this->query('SELECT `handler`, `behaviour`, `mimetype` FROM '.$this->Tbl['sendto_handler'].' ORDER BY `handler`');
        $return = array();
        while ($res && $line = $this->assoc($res)) {
            $return[$line['handler']][$line['mimetype']] = $line['behaviour'];
        }
        return $return;
    }
}
