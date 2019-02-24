<?php
/**
 * Break up an OPML file and return its structure
 *
 * @package phlyMail Nahariya 4.0+
 * @author Matthias Sommerfeld, phlyLabs
 * @copyright 2013 phlyLabs, Berlin http://phlylabs.de/
 * @version 0.0.1 2013-08-09 $Id: RSS.php 2731 2013-03-25 13:24:16Z mso $
 */
class Format_Parse_OPML
{
    protected $feeds = array(), $folders = array();

    /**
     * Constructor method.
     */
    public function __construct()
    {
        // void
    }

    /**
     *
     *
     * @param  string  $filename  File to read the information from
     * @return array  'folders' Flat structure of the folders; 'feeds' the list of feeds found
     * @access public
     * @since 0.0.1
     */
    public function read($filename)
    {
        $xml = simplexml_load_string(file_get_contents($filename));
        $array = json_decode(json_encode((array) $xml), 1);
        $array = array($xml->getName() => $array);

        if (empty($array['opml']) || empty($array['opml']['body']) || empty($array['opml']['body']['outline'])) {
            return false;
        }
        $this->parseOutline($array['opml']['body']['outline'] , 0);

        return array('folders' => $this->folders, 'feeds' => $this->feeds);
    }

    protected function parseOutline($outlines, $childof)
    {
        foreach ($outlines as $item) {
            if (isset($item['@attributes']['xmlUrl'])) {
                $feed = array(
                        'name' => !empty($item['@attributes']['title']) ? $item['@attributes']['title'] : '',
                        'description' => !empty($item['@attributes']['text']) ? $item['@attributes']['text'] : '',
                        'xml_uri' => !empty($item['@attributes']['xmlUrl']) ? $item['@attributes']['xmlUrl'] : '',
                        'html_uri' => !empty($item['@attributes']['htmlUrl']) ? $item['@attributes']['htmlUrl'] : '',
                        'childof' => $childof
                        );
                $this->feeds[] = $feed;
            } else {
                $newChildof = sizeof($this->folders);
                $folder = array(
                        'name' => !empty($item['@attributes']['text']) ? $item['@attributes']['text'] : '',
                        'childof' => $childof
                        );
                $this->folders[] = $folder;
                if (!empty($item['outline'])) {
                    $this->parseOutline($item['outline'], $newChildof);
                }
                unset($item['@attributes'], $item['outline']);
                if (!empty($item)) {
                    $this->parseOutline($item, $childof);
                }
            }
        }
    }
}