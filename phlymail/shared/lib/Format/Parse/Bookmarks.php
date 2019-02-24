<?php
/*
////////////////////////////////////////////////////////////////

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
////////////////////////////////////////////////////////////////
/**
 * This class parses IE, Netscape and Opera bookmark files and returns arrays with the bookmark / folder information.
 *
 * @author Lennart Groetzbach <lennartg@web.de>
 * @copyright Lennart Groetzbach <lennartg@web.de> - distributed under the LGPL
 * @version 0.61 2003/07/07
 *
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @version 0.6.3 2011-04-13
 */

class Format_Parse_Bookmarks {

    /**
     * The generated error messages, line feed seperated
     *
     * @access   public
     * @var     String
     */
    public $error_message = '';

    /**
     * The number of folders parsed after a function call
     *
     * @access   protected
     * @var     Integer
     */
    protected $foldersParsed = 0;

    /**
     * The number of bookmarks parsed after a function call
     *
     * @access   protected
     * @var     Integer
     */
    protected $urlsParsed = 0;

    /**
     * Holds the structure as parsed
     *
     * @access   protected
     * @var array
     */
    protected $structure = array();


    public function __construct()
    {
        $this->structure = array('folders' => array(), 'items' => array());
    }

    public function parse($url, $format = 'mozilla')
    {
        switch ($format) {
            case 'mozilla': case 'moz': case 'netscape': case 'ns':
                $this->parseNetscape($url, 0);
                break;
            case 'msie': case 'ie': case 'internetexplorer': case 'explorer':
                $this->parseInternetExplorer($url, 0);
                break;
            case 'opera': case 'op':
                $this->parseOpera($url, 0);
                break;
            default:
                $this->error_message = 'Unknown format';
                return false;
        }
        return $this->structure;
    }

    /**
     * Parses an Opera bookmark file
     *
     * Parses the file, default name for bookmark file is "Opera6.adr"
     * Tested with Opera 6.
     *
     * @param String $url   url to the bookmark file
     * @param int  $folderID  id of the root folder
     * @return int  -1 if error occurs
     */
    protected function parseOpera($url, $folderID)
    {
        $this->foldersParsed = 0;
        $this->urlsParsed = 0;
        $depth = 0;
        $parents = array();
        array_push($parents, $folderID);
        // is it a file?
        if (!is_file($url)) {
            $this->error_message .= 'parseOpera(): File error'.LF;
            return -1;
        }
        // open file
        $fp = @fopen($url, 'r-');
        if (!is_resource($fp)) {
            $this->error_message .= 'parseOpera(): File error'.LF;
            return -1;
        }
        // is it an opara bookmark file?
        $line = str_replace(LF, '', fgets($fp, 4096));
        if (!preg_match('/Opera Hotlist version 2.0/', $line)) {
            fclose($fp);
            $this->error_message .= 'parseOpera(): Wrong header'.LF;
            return -1;
        }
        // insert Opera root in DB
        // read lines
        while (!@feof($fp)) {
            $line = str_replace(LF, '', fgets($fp, 4096));
            if (preg_match('/^[\s]*#folder/i', $line)) { // folder found
                // extract the name
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $name = $tmp[1];
                // extract create creation date
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $created = $tmp[1];
                // extract the visit date
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $visited = $tmp[1];
                // insert into db
                $this->foldersParsed++;

                $this->structure['folders'][$this->foldersParsed] = array
                        ('name' => $name
                        ,'descr' => ''
                        ,'created' => $created
                        ,'parent' => $parents[$depth]
                        ,'added' => $created
                        ,'visited' =>  $visited
                        );
                // current id of folder is stored in a stack
                array_push($parents, $folderID + $this->foldersParsed);
                $depth++;
            } elseif (preg_match('/^#url/i', $line)) { // bookmark found
                // extract url
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $descr = $tmp[1];
                // extract the name
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $url = $tmp[1];
                // extract create creation date
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $created = $tmp[1];
                // extract the visit date
                $line = str_replace(LF, '', fgets($fp, 4096));
                $tmp = explode('=', $line, 2);
                $visited = $tmp[1];
                // insert into db
                $this->urlsParsed++;
                $this->structure['items'][$this->urlsParsed] = array
                        ('url' => $url
                        ,'descr' => $descr
                        ,'parent' => $parents[$depth]
                        ,'added' => $created
                        ,'visited' => $visited
                        );
            } elseif (preg_match('/^[\s]*-/', $line)) { // folder closed
                array_pop($parents);
                $depth--;
            }
        }
        fclose($fp);
        return true;
    }

    /**
     * Parses a Netscape bookmark file
     *
     * Parses the file, default name is "bookmarks.html".
     * Tested with Netscape 4.x and 6.x.
     *
     * @param    String      $url   url to the bookmark file
     * @param    int         $folderID  id of the root folder
     * @return   int         -1 if error occurs
     */
    protected function parseNetscape($url, $folderID)
    {
        $this->foldersParsed = 0;
        $this->urlsParsed = 0;
        $depth = 0;
        $parents = array();
        array_push($parents, $folderID);
        // is it a file?
        if (!is_file($url)) {
            $this->error_message .= 'parseNetscape(): File error'.LF;
            return -1;
        }
        // open file
        $fp = @fopen($url, 'r-');
        if (!is_resource($fp)) {
            $this->error_message .= 'parseNetscape(): File error'.LF;
            return -1;
        }
        // is it an opara bookmark file?
        $line = str_replace(LF, '', fgets($fp, 4096));
        if (!preg_match('/<!DOCTYPE NETSCAPE-Bookmark-file-1>/i', $line)) {
            fclose($fp);
            $this->error_message .= 'parseNetscape(): Wrong header'.LF;
            return -1;
        }
        // insert NS root in DB
        // read lines
        while (!@feof($fp)) {
            $line = str_replace(LF, '', fgets($fp, 4096));
            // extract add_date
            preg_match('(/ADD_DATE="([^"]*/i))', $line, $match);
            @$added = $match[1];
            // folder found
            if (preg_match('/<H3[^>]*>(.*)<\/H3>/i', $line, $match)) {
                $name = $match[1];
                $this->foldersParsed++;
                $this->structure['folders'][$this->foldersParsed] = array('name' => $name, 'parent' => $parents[$depth], 'added' => $added, 'descr' => '');
                array_push($parents, $folderID + $this->foldersParsed);
                $depth++;
            } elseif (preg_match('/<A HREF="([^"]*)[^>]*>(.*)<\/A>/i', $line, $match)) { // bookmark found
                // extract url and descr
                $url = $match[1];
                $name = $match[2];
                // extract dates
                preg_match('/ADD_DATE="([^"]*)/i', $line, $match);
                $created = isset($match[1]) ? $match[1] : null;
                preg_match('/LAST_VISIT="([^"]*)/i', $line, $match);
                $visited = isset($match[1]) ? $match[1] : null;
                preg_match('/LAST_MODIFIED="([^"]*)/i', $line, $match);
                $modified = isset($match[1]) ? $match[1] : null;
                // insert into db
                $this->urlsParsed++;
                $this->structure['items'][$this->urlsParsed] = array
                        ('url' => $url
                        ,'name' => $name
                        ,'parent' => $parents[$depth]
                        ,'added' => $created
                        ,'modified' => $modified
                        ,'visited' => $visited
                        );
            } elseif (preg_match('/<\/DL>/i', $line)) { // folder closed
                array_pop($parents);
                $depth--;
            }
        }
        fclose($fp);
        return true;
    }

    /**
     * Parses an IE bookmarks folder.
     *
     * Parses the IE folder and files.
     *
     * @param String  $url   url to the bookmark file
     * @param int  $folderID  id of the root folder
     * @param boolean  $firstCall  only true, upon the first call
     * @return   int  -1 if error occurs
     */
    protected function parseInternetExplorer($url, $folderID, $firstCall = true)
    {
        if ($firstCall) {
            $this->foldersParsed = 0;
            $this->urlsParsed = 0;
        }
        static $depth = 0;
        // open directory
        $d = @dir($url);
        while ($entry = $d->read()) {
            // is not . or ..
            if ($entry != '.' && $entry != '..') {
                // is it a dir?
                if (is_dir($url.'/'.$entry)) {
                    $depth++;
                    $this->structure['folders'][$this->foldersParsed+$length] = array('name' => $entry, 'descr' => '', 'parent' => $folderID);
                    // visit it
                    $this->parseInternetExplorer($url.'/'.$entry, $folderID + 1, false);
                    $this->foldersParsed++;
                    $depth--;
                    // is there a ie internet shortcut?
                } elseif (preg_match('/.url$/i', $entry)) {
                    $modified = '';
                    $lineno = 0;
                    // open it
                    $fp = @fopen($url.'/'.$entry, 'r-');
                    if (@$fp) {
                        $name = substr(basename($entry), 0, strlen(basename($entry)) - 4);
                        while (!@feof ($fp)) {
                            $lineno++;
                            $line = str_replace(LF, '', @fgets($fp, 4096));
                            // extract url
                            if (preg_match('/^url=/i', $line)) {
                                $href = trim(substr($line, 4));
                            } elseif (preg_match('/^modified=/i', $line)) {
                                $modified = trim(substr($line, 9));
                            }
                        }
                        // insert into db
                        $this->urlsParsed++;
                        $this->structure['items'][$this->urlsParsed] = array( 'url' => $href, 'descr' => $name,'parent' => $folderID + $this->foldersParsed);
                    } else {
                        $this->error_message .= 'parseInternetExplorer(): file error: '.$url.LF;
                        return -1;
                    }
                    fclose ($fp);
                }
            }
        }
        $d->close();
    }
}
