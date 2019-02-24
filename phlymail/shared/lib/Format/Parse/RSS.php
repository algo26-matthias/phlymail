<?php
/**
 * Offers functions for reading a given RSS file and returning the feed's
 * data as an array structure
 * By using more generalized RegEx for parsing RSS data this class should be
 * fairly resistant against unclean XML data. Additionally you can pass it more
 * tag names (case insensitive) for recognizing future enhancements of the
 * standards or private use tags.
 *
 * @package phlyMail Nahariya 4.0+
 * @author Matthias Sommerfeld, phlyLabs
 * @copyright 2005-2015 phlyLabs, Berlin http://phlylabs.de/
 * @uses phlyMail 4.5+ shared/lib/functions.php
 * @version 0.6.1 2015-02-25 
 */
class Format_Parse_RSS
{
    // used, when no encoding found
    private $default_enc = 'UTF-8';
    // You might limit, how many entries you wish to get from top of the feed
    private $items_limit = 0;
    // This is really desired - don not leave any HTML in the feed's data
    protected $strip_html   = true;
    // Format of how the lastBuildDate of the feed is returned
    protected $date_format = 'Y-m-d H:i:s';

    protected $channeltags = array(
            'title', 'link', 'description', 'language', 'copyright', 'updated',
            'managingEditor', 'webMaster', 'pubDate', 'lastBuildDate', 'rating', 'docs'
            );
    protected $itemtags = array(
            'title', 'link', 'description', 'author', 'category', 'comments', 'id',
            'guid', 'enclosure', 'pubDate', 'source', 'updated', 'summary', 'content'
            );
    protected $imagetags = array('title', 'url', 'link', 'width', 'height');
    protected $textinputtags = array('title', 'description', 'name', 'link');

    /**
     * Constructor method. By passing an array with options you might influence
     * the behaviour of the class.
     *
     *[@param array $options  Pass some options for influence on behaviour]
     * @return object instance
     * @since 0.5.8
     */
    public function __construct($options = null)
    {
        if (!is_null($options)) {
            if (isset($options['$date_format']) && is_string($options['$date_format'])) {
                $this->date_format = $options['$date_format'];
            }
            if (isset($options['strip_html']) && is_bool($options['strip_html'])) {
                $this->strip_html = $options['strip_html'];
            }
            if (isset($options['items_limit']) && is_numeric($options['items_limit'])) {
                $this->items_limit = $options['items_limit'];
            }
        }
    }

    /**
     * Return the channel information for a given filename
     *
     * @param  string  $filename  File to read the information from; optional
     * @param  string  $content   Feed content as a string; optional
     * @return array  empty array on failure, structured associative array on success
     * @access public
     * @since 0.0.1
     */
    public function read($filename = false, $content = false)
    {
        if (!empty($content)) {
            $result = $this->parse(false, $content);
        } else {
            $result = $this->parse($filename);
        }
        // return result
        return $result;
    }

    /**
     * Actual parsing method used by the public method get() to read and parse the given RSS file
     * @param  string  URL or filename of the RSS
     * @return  array  structured array with found info, false on failure
     * @access  protected
     * @since  0.0.1
     */
    protected function parse($filename = false, $content = false)
    {
        if (!empty($content)) {
            $rss_content = &$content;
        } else {
            // Opening the given file impossibe
            if (!@is_readable($filename)) {
                return false;
            }
            $rss_content = file_get_contents($filename);
        }
        // Parse document encoding
        $result['encoding'] = $this->rss_preg_match('!encoding=[\'\"](.*?)[\'\"]!si', $rss_content);
        // This id used by $this->rss_preg_match
        $this->rssenc = ($result['encoding'] != '') ? $result['encoding'] : $this->default_enc;
        //
        // Parse CHANNEL info
        //
        preg_match('!<(channel|feed).*?>(.*?)</\1>!si', $rss_content, $out_channel);

        // It's intended to not just parse anything into an array, but have semantic parsing instead:
        // Since RSS / Atom feeds usually contain the same fields (author, title, content, ...),
        // it seems sensible to parse these into named fields and return them in an normalized
        // fashion. This makes upstream code leaner and less erroneous.

        // Go parsing
        foreach ($this->channeltags as $channeltag) {
            $channeltag = preg_quote($channeltag, '!');
            $temp = $this->rss_preg_match('!<'.$channeltag.'.*?>(.*?)</'.$channeltag.'>!si', $out_channel[2]);
            if ($temp != '') {
                $result[$channeltag] = $temp; // Set only if not empty
                if ($this->strip_html) {
                    $result[$channeltag] = strip_tags($this->unhtmlentities(strip_tags($result[$channeltag])));
                }
            }
        }
        if (!empty($result['updated'])) {
            $result['lastBuildDate'] = $result['updated'];
        } elseif (!empty($result['pubDate'])) {
            $result['lastBuildDate'] = $result['pubDate'];
        }

        // If date_format is specified and lastBuildDate is valid
        if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !== -1) {
            // convert lastBuildDate to specified date format
            $result['lastBuildDate'] = date($this->date_format, $timestamp);
        }
        // Parse TEXTINPUT info
        preg_match('!<textinput(|[^>]*[^/])>(.*?)</textinput>!si', $rss_content, $out_textinfo);
        // This a little strange regexp means:
        // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput />
        // (it's not beggining tag)
        if (isset($out_textinfo[2])) {
            foreach ($this->textinputtags as $textinputtag) {
                $temp = $this->rss_preg_match('!<'.$textinputtag.'.*?>(.*?)</'.$textinputtag.'>!si', $out_textinfo[2]);
                if ($temp != '') {
                    $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
                }
            }
        }
        // Parse IMAGE info
        preg_match('!<image.*?>(.*?)</image>!si', $rss_content, $out_imageinfo);
        if (isset($out_imageinfo[1])) {
            foreach ($this->imagetags as $imagetag) {
                $temp = $this->rss_preg_match('!<'.$imagetag.'.*?>(.*?)</'.$imagetag.'>!si', $out_imageinfo[1]);
                if ($temp != '') {
                    $result['image_'.$imagetag] = $temp; // Set only if not empty
                }
            }
        }
        //
        // Parse ITEMS
        //
        preg_match_all('!<(item|entry)(| .*?)>(.*?)</\1>!si', $rss_content, $items);

        print_r($items);

        $rss_items = $items[2];
        $i = 0;
        $result['items'] = array(); // create array even if there are no items
        foreach ($rss_items as $rss_item) {
            // If number of items is lower then limit: Parse one item
            if ($i < $this->items_limit || $this->items_limit == 0) {
                foreach ($this->itemtags as $itemtag) {
                    $temp = $this->rss_preg_match('!<'.$itemtag.'.*?>(.*?)</'.$itemtag.'>!si', $rss_item);
                    if ($temp != '') {
                        $result['items'][$i][$itemtag] = $temp; // Set only if not empty
                    }
                }
                if (preg_match('!<link href="(.+)" />!Usi', $rss_item, $found)) {
                    $result['items'][$i]['link'] = $found[1];
                }

                // Strip HTML tags and other bullshit from DESCRIPTION
                if ($this->strip_html && isset($result['items'][$i]['description']) && $result['items'][$i]['description']) {
                    $result['items'][$i]['description'] = strip_tags(strip_tags($result['items'][$i]['description']));
                }
                // Strip HTML tags and other bullshit from TITLE
                if ($this->strip_html && isset($result['items'][$i]['title']) && $result['items'][$i]['title']) {
                    $result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
                }

                if (!empty($result['items'][$i]['updated'])) {
                    $result['items'][$i]['pubDate'] = $result['items'][$i]['updated'];
                }

                // If date_format is specified and pubDate is valid
                if ($this->date_format != '' && isset($result['items'][$i]['pubDate'])
                        && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !== -1) {
                    // convert pubDate to specified date format
                    $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                }
                // Item counter
                $i++;
            }
        }
        $result['items_count'] = $i;
        return $result;
    }

    /**
     * Adjusts the return of preg_match to a more suitable method
     * @param  string  Pattern
     * @param  string  Subject
     * @return trimmed field with index 1 of the preg_match() array output
     * @since 0.0.1
     * @access protected
     */
    protected function rss_preg_match($pattern, $subject)
    {
        preg_match($pattern, $subject, $out);
        // no result -> empty string
        if (!isset($out[1])) {
            return '';
        }
        // CDATA ist i-bä-bä
        $out[1] = str_replace(array('<![CDATA[', ']]>'), array('', ''), $out[1]);
        // Try to push the data to phlyMail's internal encoding (UTF-8)
        $enc = (isset($this->rssenc) && $this->rssenc) ? $this->rssenc : $this->default_enc;
        $out[1] = encode_utf8($out[1], $enc, true);
        // Return result
        return trim($out[1]);
    }

    /**
     * Replace HTML entities &something; by real characters
     * @param  string  String to do the replacements in
     * @return  string  Replaced string
     * @access  protected
     * @since 0.0.1
     */
    protected function unhtmlentities($string)
    {
        // First let PHP do its job with UTF-8 and stuff
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        // Now shooting at the rest
        $string = preg_replace
        // Illegal multibyte, decimal, hexa entities
                (array('!&#(x)?([a-f0-9]{3,4});!i', '!&#(\d+);!me', '!&#x([a-f0-9]+);!mei')
                ,array('', 'chr(\1)', 'chr(0x\1)')
                ,$string
                );
        // What's left, will be transferred on return
        $trans_tbl = array_merge
                (array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES))
                ,array('&gt;' => '>', '&lt;' => '<', '&amp;' => '&', '&quot;' => '"', '&apos;' => "'")
                );
        return strtr($string, $trans_tbl);
    }
}