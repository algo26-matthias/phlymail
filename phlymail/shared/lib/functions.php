<?php
/**
 * functions.php - General purpose functions
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.3.3 2015-07-26
 */

function version_format($version = '')
{
    $return = '';
    $version = trim($version);
    for ($i = strlen($version); $i >= 0; $i--) {
        if (((strlen($version) - $i) % 2 == 1) && (strlen($version) - $i) != 1) {
            $return = '.' . $return;
        }
        $return = substr($version, $i, 1) . $return;
    }
    return $return;
}

/**
 * Recursive stripslashes of a given data structure
 * @param string|array  String / array to be stripped from slashes
 * @return all slashes stripped
 */
function phm_stripslashes($return = '')
{
    if (!get_magic_quotes_gpc()) {
        return $return;
    }
    if (is_array($return)) {
        foreach ($return as $k => $v) {
            $return[$k] = (is_array($v)) ? phm_stripslashes($return[$k]) : stripslashes($return[$k]);
        }
        return $return;
    }
    return stripslashes($return);
}

/**
 * most useful in sending JSON / javascript arrays out, which may contain "evil" characters
 *
 * @param string $str The string to be escaped
 * @param string $chars List of characters to escape @see addcslashes()
 * @return string The escaped string
 * @since 4.0.1
 */
function phm_addcslashes($str, $chars = '"/\\')
{
    return str_replace(array(CRLF, LF, "\r", "\t"), array('\n', '\n', '', '\t'), addcslashes(uctc::convert($str, 'utf8', 'utf8', true, 0x3F), $chars));
}

function phm_entities($str)
{
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

function phm_strtoupper($string)
{
    return (function_exists('mb_strtoupper'))
            ? mb_strtoupper($string)
            : strtoupper($string);
}

/**
 * Searches through the subject for mailto links and changes these in a way, that clicking on one of
 * those opens the compose window of phlyMail, currently situated in the "core" handler
 * @param string  The string to search in
 * @return string The same string, but mailto: replaced
 * @since 3.0.7
 */
function mailto_2_send($return = '')
{
    return preg_replace
           ('!(mailto:)([^\s<>"?]+)(\?subject=([^\s<>"]+))?!i'
           ,PHP_SELF.'?l=compose_email&amp;h=core&amp;'.give_passthrough(1).'&amp;to=\2&amp;subj=\4'
           ,$return
           );
}

/**
 * Used to effectively preparing the input string for output in the frontend
 * Links and milto:s are replaced, HTML will be optionally sanitized (no external references)
 * @param  string  The string to process
 * @param  string  Output mode: Can be either 'text' for "Plain text" output or 'html'
 * @param  boolean  Sanitize the output (only used, if param 2 is 'text'
 * @param  string  Base link for referring to CID: references for inline elements like images
 * @param  boolean reference  Reference to a variable, where the status is stored, whether the function found inline elements
 * @since 3.0.0
 */
function links($return = '', $mode = 'text', $sanitize = false, $cid_link = '', &$found_inline = false)
{
    $found_inline = false;
    if ('text' == $mode) { // Plain text...
        // Emailadressen
        $return = preg_replace
                ('!(mailto:)([^\s<>"?]+)(\?subject=([^\s<>"]+))?!i'
                ,'<a href="'.PHP_SELF.'?l=compose_email&amp;h=core&amp;'.give_passthrough(1).'&amp;to=\2&amp;subj=\4" target="_blank">\2\3</a>'
                ,$return
                );
        // Internet-Protokolle:
        $return = preg_replace_callback('!(https?://|ftp://|gopher://|file:///?|news:|www\.)[^/\s]{3,}(/[^\s]*)?!s', 'links_find_url', $return);
        return $return;
    } else { // HTML ...
        // Remove all dangerous items
        $return = preg_replace('!<(script|frame|iframe|embed|object|applet).*?>.*?</\1>!si', '', $return);
        // Try some basic cleanup to make the parser simpler
        $return = preg_replace_callback('!(\</?)(\w+)!', create_function('$a', 'return $a[1].strtolower($a[2]);'), $return);
        // Identify style settings for background images
        preg_match_all('!\<style.*\>(.+)?\<\/style\>|style="(.+)?"!Us', $return, $found, PREG_SET_ORDER);
        if (isset($found) && !empty($found)) {
            foreach ($found as $k => $style) {
                if (isset($style[2])) {
                    $style[1] = $style[2];
                }
                if (!isset($style[1])) {
                    continue;
                }
                preg_match_all('!background.*:.*url\((.+)\)!Ui', $style[1], $found2, PREG_SET_ORDER);
                if (isset($found2) && !empty($found2)) {
                    $found_inline = true;
                    foreach ($found2 as $url) {
                        $new_url = str_replace
                                ($url[1]
                                ,($sanitize) ? '' : PHP_SELF.'?deref='.links_html($url[1])
                                ,$url[0]
                                );
                        $style[1] = str_replace($url[0], $new_url, $style[0]);
                    }
                    $return = str_replace($style[0], $style[1], $return);
                }
            }
        }
        // Prevent external stylesheets from being loaded if Sanitize is on!
        preg_match_all('!\<link(.+)href=("|\'|)(.+)(\s|"|\'|\>)!Uis', $return, $found, PREG_SET_ORDER);
        if (!empty($found)) {
            foreach ($found as $k => $link) {
                if (substr($link[3], 0, 1) == '#') {
                    continue;
                }
                if (substr($link[3], 0, 4) == 'cid:') {
                    $neu_link = $cid_link.urlencode(substr($link[3], 4));
                } else {
                    $found_inline = true;
                    $neu_link = ($sanitize) ? '' : PHP_SELF.'?deref='.links_html($link[3]);
                }
                $neu_link = str_replace($link[3], $neu_link, $link[0]);
                $return = str_replace($link[0], $neu_link, $return);
            }
        }
        // Derefer
        preg_match_all('!(href|src|action|bgcolor|background|on[a-zA-Z]+)=("|\')?([^\<\>"\']+)\2!is', $return, $found, PREG_SET_ORDER);
        if (!empty($found)) {
            $dublettes = array();
            foreach ($found as $k => $link) {
                if ($link[3]{0} == '#') {
                    continue;
                }
                if (isset($dublettes[$link[3]])) {
                    continue;
                }
                $dublettes[$link[3]] = 1;

                $pruef = strtolower($link[1]);
                $dataOrigUri = '' ;
                if (substr($link[3], 0, 4) == 'cid:') {
                    $neu_link = $cid_link.urlencode(substr($link[3], 4));
                    // Leave a mark in the source to later recognize replaced CID links
                    $return = str_replace(
                            $link[2].$link[3].$link[2],
                            $link[2].$link[3].$link[2].' data-original-cid='.$link[2].substr($link[3], 4).$link[2],
                            $return);
                } else {
                    $found_inline = true;
                    if ('src' == $pruef || 'background' == $pruef) {
                        $neu_link = ($sanitize)
                                ? ('src' == $pruef ? addslashes($GLOBALS['_PM_']['path']['theme']).'/images/blocked.gif' : '')
                                : PHP_SELF.'?deref='.links_html($link[3])
                                ;
                    } elseif ('href' == $pruef || 'action' == $pruef) {
                        $ignore = false;
                        $link_html = links_html($link[3], $ignore);
                        $neu_link = ($ignore) ? $link_html : PHP_SELF.'?deref='.$link_html;
                        if ('href' == $pruef) {
                            $dataOrigUri = ' data-original-href="'.$link[3].'"';
                        }
                    } else {
                        $neu_link = '';
                    }
                }
                $neu_link = str_replace($link[3], $neu_link, $link[0]).$dataOrigUri;
                $return = str_replace($link[0], $neu_link, $return);
            }
        }

        // target="_blank"
        preg_match_all('!\<a\b.+\>!Us', $return, $found, PREG_SET_ORDER);
        if (!empty($found)) {
            foreach ($found as $k => $href) {
                if (preg_match('!href="?#!i', $href[0])) {
                    continue;
                }
                if (!preg_match('!href=!i', $href[0])) {
                    continue;
                }
                $neu_href = $href[0];

                // Put the original URL in the title attribute for all URLs we have rewritten
                if (preg_match('!data-original-href="([^"]+)"!', $href[0], $dataOrigUri)
                        && !preg_match('!href=("|\')?(mailto\:)!i', $href[0])) {
                    if (!preg_match('!title=!i', $href[0])) {
                        // no title attribute: We can safely rename our helper attribute to title
                        $neu_href = str_replace('data-original-href="', 'title="', $neu_href);
                    } else {
                        // extract our helper attribute, attach to title atztribute in place
                        $neu_href = str_replace(array($dataOrigUri[0], ''), array('title="', 'title="'.$dataOrigUri[1].' - '), $neu_href);
                    }
                }
                // target="_blank"
                $neu_href = preg_replace(array('!target="?\w+"?!i', '!(\<a\s)(.+\>)!Usi'), array('', '\1target="_blank" \2'), $neu_href);
                $return = str_replace($href[0], $neu_href, $return);
            }
        }
        return $return;
    }
}

function links_html($return = '', &$ignore = null)
{
    if (substr(strtolower($return), 0, 7) == 'mailto:') {
        $ignore = true;
        return mailto_2_send($return);
    }
    if (basics::isEmail($return)) {
        $ignore = true;
        return mailto_2_send('mailto:'.$return);
    }
    if (!basics::isURL($return)) {
        $return = 'http://'.$return;
    }
    $ignore = false;
    return derefer($return);
}

function links_find_url($u)
{
    $url = $u[0];
    $afterUrl = ''; // Bucket
    if (substr($url, -4, 4) == '&gt;') {
        $afterUrl = '&gt;';
        $url = substr($url, 0, -4);
    }

    while (preg_match('#[[:punct:]]$#', $url, $found)) {
        $chr = $found[0]; // letztes Zeichen
        if ($chr == '.' || $chr == ',' || $chr == '!' || $chr == '?' || $chr == ':' || $chr == ';' || $chr == '>' || $chr == '<') {
            // Ein Satzzeichen, das nicht zur URL gehört
            $afterUrl = $chr.$afterUrl;
            $url = substr($url, 0, -1);
        } elseif (($chr == ')' && strpos($url, '(') !== false) || ($chr == ']' && strpos($url, '[') !== false) || ($chr == '}' && strpos($url, '{') !== false)) {
            break; // Klammer gehört nur zur URL, wenn auch öffnende Klammer vorkommt.
        } elseif ($chr == ')' || $chr == ']' || $chr == '}') {
            // .. Klammer gehört nicht zur URL
            $afterUrl = $chr.$afterUrl;
            $url = substr($url, 0, -1);
        } elseif($chr == '(' || $chr == '[' || $chr == '{') {
            // öffnende Klammer am Ende gehört nicht zur URL
            $afterUrl = $chr.$afterUrl;
            $url = substr($url, 0, -1);
        } else {
            break; // Zeichen gehört zur URL
        }
    }
    // URL als HTML-Link zurück geben
    $title = phm_entities($url);
    if (strpos($url, PHP_SELF.'?deref=') === 0
            || strpos($url, '/index.php?deref=' === 0)
            || strpos($url, PHP_SELF.'?l=compose_email') === 0) {
        $derefer = $url;
    } else {
        $derefer = PHP_SELF.'?deref='.phm_entities(derefer($url)).'" data-original-href="'.phm_entities($url);
    }
    return '<a href="'.$derefer.'" title="'.$title.'" target="_blank">'.links_linebreak($title).'</a>'.$afterUrl;
}


function derefer($return)
{
    static $Deref;
    if (empty($Deref)) {
        $Deref = new DB_Controller_Derefer();
    }
    // If link source is HTML, we need to de-HTML it
    $deHTMLed = html_entity_decode($return, null, 'utf-8');
    if (false !== $deHTMLed) {
        $return = $deHTMLed;
    }
    if (basics::isEmail($return)) {
        return PHP_SELF.'?l=compose_email&h=core&'.give_passthrough(1).'&to='.$return;
    }
    if (!basics::isURL($return)) {
        $return = 'http://'.$return;
    }
    return $Deref->register($return);
}

function links_linebreak($return = '')
{
    if (!empty($GLOBALS['_PM_']['core']['read_wordwrap']) && preg_match('/([^\s]{'.$GLOBALS['_PM_']['core']['read_wordwrap'].',})/', $return)) {
        return htmlspecialchars(preg_replace('/([^\s]{'.$GLOBALS['_PM_']['core']['read_wordwrap'].'})/', '\\1 ', un_html($return)));
    }
    return $return;
}

function un_html($return = '')
{
    return preg_replace
           (array('!&gt;!i', '!&lt;!i', '!&quot;!i', '!&amp;!i', '!&nbsp;!i', '!&copy;!i')
           ,array('>', '<', '"', '&', ' ', '(c)')
           ,$return
           );
}

/**
 * Converts plain text to HTML,underlines links
 * @param  string  Text to convert
 * @return  string  HTMLized text
 * @since 3.1.7
 */
function text2html($text)
{
    $text = nl2br(htmlspecialchars($text, ENT_COMPAT, 'utf-8'));
    // Emailadressen
    $text = preg_replace
            ('!(mailto:)([^\s<>"?]+)(\?subject=([^\s<>"]+))?!i'
            ,'<a href="\1\2\3">\2</a>'
            ,$text
            );
    // Internet-Protokolle:
    $text = preg_replace
            ('!(http://|https:|ftp://|gopher://|news:|www\.)(.+)(?=<|>|\W[\s\n\r]|[\s\r\n]|$)!Umi'
            ,'<a href="\\1\\2">\\1\\2</a>'
            ,$text.LF
            );
    return $text;
}

/**
 * Runs thorugh any mail header field containing multiple addresses such as To: or Cc:
 * What you receive as return value(s) quiet much depends on the parameters.
 * You can either set $print, $sendtoadb or $separate to true.
 *
 * @param string $address The header fields' value
 * @param int $limit Maximum number of individual addresses to process, pass 0 for no limit; Default: 10
 * @param print|maillist|read
 *   'print': Prepare the return val for printing (no linkage, complete adress and real name)
 *   'read': Prepare the "add to address book" linkage
 *   'maillist': Return an array structurally compatible to Format_Parse_Email::parse_email_address()
 *[@param int $shorten_to Whether to shorten the return value to a certain length; Default: 0 (no)]
 * @return mixed
 */
function multi_address($address = '', $limit = 10, $usage = 'read', $shorten_to = 0)
{
    if ($usage == 'read') {
        $cAPI = new handler_contacts_api($GLOBALS['_PM_'], $_SESSION['phM_uid']);
    }
    $return = ('maillist' == $usage || 'sort' == $usage) ? array('', '', '') : '';
    $duration = strlen($address);
    $mode = '';
    $j = 1;
    $add = '';
    for ($i = 0; $i <= $duration; ++$i) {
        $test = substr($address, $i, 1);
        if ('comment' == $mode && ')' == $test) {
            $mode = '';
            continue;
        }
        if ('string' == $mode && '"' == $test) {
            $mode = '';
            continue;
        }
        if ('' == $mode) {
            if ('(' == $test) { $mode = 'comment'; continue; }
            if ('"' == $test) { $mode = 'string'; continue; }
            if (',' == $test) {
                $found[$j] = $i;
                $j++;
                if ($limit > 0 && $j > $limit) {
                    if ($duration > $i && $usage != 'sort') {
                        $add = ', ...';
                    }
                    $duration = $i;
                    $address = substr($address, 0, $i);
                    break;
                }
            }
        }
    }
    $found[0] = 0;
    $found[$j] = $duration;
    for ($k = 0; $k < $j; ++$k) {
        $l = $k + 1;
        if (0 != $k) {
            ++$found[$k];
        }
        $build = substr($address, $found[$k], ($found[$l] - $found[$k]));
        $build = Format_Parse_Email::parse_email_address($build, 0, ($usage == 'send'));
        if ('print' == $usage) {
            $build = htmlspecialchars($build[2]);
            if (0 != $k) {
                $return .= ', ';
            }
            $return .= $build;
        } elseif ('maillist' == $usage || 'sort' == $usage) {
            if (0 != $k) {
                $return[0] .= ', ';
                $return[1] .= ', ';
                $return[2] .= ', ';
            }
            $return[0] .= $build[0];
            $return[1] .= $build[1];
            $return[2] .= $build[2];
        } elseif ('send' == $usage) {
            if (0 != $k) {
                $return .= ', ';
            }
            $return .= !empty($build[1]) && $build[1] != $build[0] ? '"'.$build[1].'" '.'<'.$build[0].'>' : $build[0];
        } else {
            if ($build[2] == '') {
                continue;
            }
            $AID = $cAPI->search_contact($build[0], 'email', true);
            if (!$AID) {
                $sendtoadb = '<img src="$themes$/icons/contacts_sendto.gif" title="$title$" class="sendtoadb" onclick="sendtoadb(\''
                        .phm_addcslashes($build[0], "'").'\', \''.phm_addcslashes($build[1], "'").'\');" />';
            } else {
                $sendtoadb = '<img src="$themes$/icons/contacts_isindb.gif" title="" class="sendtoadb" onclick="openadb('.$AID.');" />';
            }
            $build = '<span title="'.$build[2].'">'.$build[1].$sendtoadb.'</span>';
            if (0 != $k) {
                $return .= ', ';
            }
            $return .= $build;
        }
    }
    if ('maillist' == $usage) {
        if ($shorten_to && strlen($return[1]) > $shorten_to) {
            $return[1] = substr($return[1], 0, ($shorten_to - 3)) . '...';
        } else {
            $return[1] = $return[1].$add;
        }
        return array($return[0].$add, $return[1], $return[2].$add);
    } elseif ('sort' == $usage) {
        return $return[0];
    }
    return $return.$add;
}

function give_passthrough($mode = 1)
{
    $return = '';
    if (isset($GLOBALS['_PM_']['core']['pass_through'])) {
        if (1 == $mode) {
            foreach ($GLOBALS['_PM_']['core']['pass_through'] as $key => $value) {
                if (0 < $key) {
                    $return .= '&';
                }
                if (is_array($GLOBALS[$value])) {
                    $i = 0;
                    foreach ($GLOBALS[$value] as $ke2 => $valu2) {
                        if (0 < $i) {
                            $return .= '&';
                        }
                        $return .= $value.'['.$ke2.']='.$valu2;
                        ++$i;
                    }
                }  else {
                    $return .= $value.'='.$GLOBALS[$value];
                }
            }
        } elseif (2 == $mode) {
            foreach ($GLOBALS['_PM_']['core']['pass_through'] as $key => $value) {
                if (is_array($GLOBALS[$value])) {
                    foreach ($GLOBALS[$value] as $ke2 => $valu2) {
                        $return .= '<input type="hidden" name="'.$value.'['.$ke2.']" value="'.$valu2.'" />'.LF;
                    }
                } else {
                    $return .= '<input type="hidden" name="'.$value.'" value="'.$GLOBALS[$value].'" />'.LF;
                }
            }
        } elseif (3 == $mode) {
            foreach ($GLOBALS['_PM_']['core']['pass_through'] as $key => $value) {
                if (is_array($GLOBALS[$value]))  {
                    foreach($GLOBALS[$value] as $ke2 => $valu2) {
                        $return[$value.'['.$ke2.']'] = $valu2;
                    }
                } else {
                    $return[$value] = $GLOBALS[$value];
                }
            }
        }
    }
    if (1 == $mode) {
        if ($return) {
            $return .= '&';
        }
        $return .= SESS_NAME.'='.SESS_ID;
    } elseif (2 == $mode) {
        $return .= '<input type="hidden" name="'.SESS_NAME.'" value="'.SESS_ID.'" />'.LF;
    } elseif (3 == $mode) {
        $return[SESS_NAME] = SESS_ID;
    }
    return $return;
}

/**
 * Meant for formatting byte values (file / mail sizes and the like) to make a sensible but short output
 * Capable of GB, MB, B(ytes)
 * @param int  The value to format
 *[@param bool  Set to true, the spacer between the number and the "B(yte)" will be just a space else &nbsp;
 *[@param bool  true for Bytes, MBytes, ...; else B, MB, ... will be output
 *[@param bool  true for using units of 1000 (binary prefix), else units of 1024 (SI prefixes) will be used
 * @since 3.0.0
 */
function size_format($size = '', $plain = false, $long = false, $si = false)
{
    // Is probably wrong, better a input switch which tells, whether the input uses 1024 or 1000
    if (preg_match('!^(\d+)(M|K|G)$!', trim($size), $found)) {
        $size = $found[1] * ($found[2] == 'K' ? pow(2, 10) : ($found[2] == 'M' ? pow(2, 20) : pow(2, 30)));
    }
    if (preg_match('!^(\d+)(M|K|G)iB$!', trim($size), $found)) {
        $size = $found[1] * ($found[2] == 'K' ? pow(10, 3) : ($found[2] == 'M' ? pow(10, 6) : pow(10, 9)));
    }
    $msg = &$GLOBALS['WP_msg'];
    $n = ($plain) ? ' ' : '&nbsp;';
    $b = (!$long) ? 'B' : 'Bytes';
    if ($si) {
        if (floor($size/pow(10, 9)) > 0) {
            return number_format(($size/pow(10, 9)), 1, $msg['dec'], $msg['tho']).$n.'G'.$b;
        }
        if (floor($size/pow(10, 6)) > 0) {
            return number_format(($size/pow(10, 6)), 1, $msg['dec'], $msg['tho']).$n.'M'.$b;
        }
        if (floor($size/pow(10, 3)) > 0) {
            return number_format(($size/pow(10, 3)), 1, $msg['dec'], $msg['tho']).$n.'K'.$b;
        }
    } else {
        if (floor($size/pow(2, 30)) > 0) {
            return number_format(($size/pow(2, 30)), 1, $msg['dec'], $msg['tho']).$n.'Gi'.$b;
        }
        if (floor($size/pow(2, 20)) > 0) {
            return number_format(($size/pow(2, 20)), 1, $msg['dec'], $msg['tho']).$n.'Mi'.$b;
        }
        if (floor($size/pow(2, 10)) > 0) {
            return number_format(($size/pow(2, 10)), 1, $msg['dec'], $msg['tho']).$n.'Ki'.$b;
        }
    }
    return trim(floor($size)).$n.$b;
}

function save_config($file, $tokens, $tokval)
{
    if (!file_exists($file)) {
        touch($file);
    }
    $GlChFile = file_get_contents($file);
    // Remove PHP tags
    $GlChFile = preg_replace('!^'.preg_quote('<?php die(); ?>', '!').LF.'!', '', $GlChFile);
    foreach ($tokens as $k => $v) {
        if (preg_match('/^'.$tokens[$k].';;[^\r^\n]*/mi', $GlChFile)) {
            $tokval[$k] = str_replace('$', '\$', $tokval[$k]); // Treat '$' literally
            $GlChFile = preg_replace('/^'.$tokens[$k].';;[^\r^\n]*/mi', $tokens[$k].';;'.$tokval[$k], $GlChFile);
        } else {
            $GlChFile .= $tokens[$k].';;'.$tokval[$k].LF;
        }
    }
    $stat = file_put_contents($file, '<?php die(); ?>'.LF.$GlChFile);
    if ($$stat) {
        return true;
    }
    return false;
}

function wash_size_field($size = '0')
{
    $size = preg_replace('![\ \.,]!', '', $size);
    $size = preg_replace('!^([^0-9]*)([0-9]+)(t|m|k|g){0,1}!i', '\\2 \\3', $size);
    $parts = explode(' ', $size, 2);
    if (!isset($parts[1])) {
        return $parts[0];
    }
    $size = $parts[0];
    switch (strtolower($parts[1])) {
        case 't': $size *= 1024; # fall through
        case 'g': $size *= 1024; # fall through
        case 'm': $size *= 1024; # fall through
        case 'k': $size *= 1024;
    }
    return $size;
}

// Encrypt a string
// Input:   confuse(string $data, string $key);
// Returns: encrypted string
function confuse($data = '', $key = '')
{
    $encoded = ''; $DataLen = strlen($data);
    if (strlen($key) < $DataLen) {
        $key = str_repeat($key, ceil($DataLen/strlen($key)));
    }
    for ($i = 0; $i < $DataLen; ++$i) {
        $encoded .= chr((ord($data{$i}) + ord($key{$i})) % 256);
    }
    return base64_encode($encoded);
}

// Decrypt a string
// Input:   deconfuse(string $data, string $key);
// Returns: decrypted String
function deconfuse($data = '', $key = '')
{
    $data = base64_decode($data);
    $decoded = '';  $DataLen = strlen($data);
    if (strlen($key) < $DataLen) {
        $key = str_repeat($key, ceil($DataLen/strlen($key)));
    }
    for($i = 0; $i < $DataLen; ++$i) {
        $decoded .= chr((256 + ord($data{$i}) - ord($key{$i})) % 256);
    }
    return $decoded;
}

/**
 * Find out, whether a given string is UTF-8
 *
 * @param string $string
 * @return string
 */
function is_utf8($string)
{
    $splitLength = 10000;
    if (strlen($string) > $splitLength) {
        // Based on: http://mobile-website.mobi/php-utf8-vs-iso-8859-1-59
        for ($i = 0, $s = $splitLength, $j = ceil(strlen($string)/$splitLength); $i < $j; $i++, $s += $splitLength) {
            if (is_utf8(substr($string, $s, $splitLength))) {
                return true;
            }
        }
        return false;
    } else {
        // From http://w3.org/International/questions/qa-forms-utf-8.html
        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $string);
    }
}

/**
 * Convert a string from any of various encodings to UTF-8
 *
 * @param  string  String to encode
 * [@param  string  Encoding; Default: ISO-8859-1]
 * [@param  bool  Safe Mode: if set to TRUE, the original string is retunred on errors]
 * @return  string  The encoded string or false on failure
 * @since 3.0.5
 */
function encode_utf8($string = '', $encoding = 'iso-8859-1', $safe_mode = false)
{
    $safe = ($safe_mode) ? $string : false;
    if (strtoupper($encoding) == 'UTF-8' || strtoupper($encoding) == 'UTF8') {
        return $string;
    } elseif (strtoupper($encoding) == 'ISO-8859-1') {
        return utf8_encode($string);
    } elseif (strtoupper($encoding) == 'WINDOWS-1252') {
        return utf8_encode(map_w1252_iso8859_1($string));
    } elseif (strtoupper($encoding) == 'UNICODE-1-1-UTF-7') {
        $encoding = 'utf-7';
    }
    if (function_exists('mb_convert_encoding')) {
        $conv = @mb_convert_encoding($string, 'UTF-8', strtoupper($encoding));
        if ($conv) {
            return $conv;
        }
    }
    if (function_exists('iconv')) {
        $conv = @iconv(strtoupper($encoding), 'UTF-8', $string);
        if ($conv) {
            return $conv;
        }
    }
    if (function_exists('libiconv')) {
        $conv = @libiconv(strtoupper($encoding), 'UTF-8', $string);
        if ($conv) {
            return $conv;
        }
    }
    return $safe;
}

/**
 * Convert a string from UTF-8 to any of various encodings
 *
 * @param  string  String to decode
 * [@param  string  Encoding; Default: ISO-8859-1]
 * [@param  bool  Safe Mode: if set to TRUE, the original string is retunred on errors]
 * @return  string  The decoded string or false on failure
 * @since 3.0.5
 */
function decode_utf8($string = '', $encoding = 'iso-8859-1', $safe_mode = false)
{
    $safe = ($safe_mode) ? $string : false;
    if (!$encoding) {
        $encoding = 'ISO-8859-1';
    }
    if (strtoupper($encoding) == 'UTF-8' || strtoupper($encoding) == 'UTF8') {
        return $string;
    } elseif (strtoupper($encoding) == 'ISO-8859-1') {
        return utf8_decode($string);
    } elseif (strtoupper($encoding) == 'WINDOWS-1252') {
        return map_iso8859_1_w1252(utf8_decode($string));
    } elseif (strtoupper($encoding) == 'UNICODE-1-1-UTF-7') {
        $encoding = 'utf-7';
    }
    if (function_exists('mb_convert_encoding')) {
        $conv = @mb_convert_encoding($string, strtoupper($encoding), 'UTF-8');
        if ($conv) {
            return $conv;
        }
    }
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', strtoupper($encoding), $string);
        if ($conv) {
            return $conv;
        }
    }
    if (function_exists('libiconv')) {
        $conv = @libiconv('UTF-8', strtoupper($encoding), $string);
        if ($conv) {
            return $conv;
        }
    }
    return $safe;
}

/**
 * Special treatment for our guys in Redmond
 * Windows-1252 is basically ISO-8859-1 -- with some exceptions, which get accounted for here
 * @param  string  Your input in Win1252
 * @param  string  The resulting ISO-8859-1 string
 * @since 3.0.8
 */
function map_w1252_iso8859_1($string = '')
{
    if ($string === '') {
        return '';
    }
    $return = '';
    for ($i = 0; $i < strlen($string); ++$i) {
        $c = ord($string{$i});
        switch ($c) {
            case 129: $return .= chr(252); break;
            case 132: $return .= chr(228); break;
            case 142: $return .= chr(196); break;
            case 148: $return .= chr(246); break;
            case 153: $return .= chr(214); break;
            case 154: $return .= chr(220); break;
            case 225: $return .= chr(223); break;
            default:  $return .= chr($c);  break;
        }
    }
    return $return;
}

/**
 * Special treatment for our guys in Redmond
 * Windows-1252 is basically ISO-8859-1 -- with some exceptions, which get accounted for here
 * @param  string  Your input in ISO-8859-1
 * @param  string  The resulting Win1252 string
 * @since 3.0.8
 */
function map_iso8859_1_w1252($string = '')
{
    if ($string === '') {
        return '';
    }
    $return = '';
    for ($i = 0; $i < strlen($string); ++$i) {
        $c = ord($string{$i});
        switch ($c) {
            case 196: $return .= chr(142); break;
            case 214: $return .= chr(153); break;
            case 220: $return .= chr(154); break;
            case 223: $return .= chr(225); break;
            case 228: $return .= chr(132); break;
            case 246: $return .= chr(148); break;
            case 252: $return .= chr(129); break;
            default:  $return .= chr($c);  break;
        }
    }
    return $return;
}

/**
 * Compares to version of a specific software with each other. Both arguments should follow the same versioning scheme
 * @param string  Version of the software, which is currently in use or checked for
 * @param string  Minimum required version for a certain functionality
 * @return bool  TRUE, if the required version is there, FALSE if not
 * @since 3.2.2
 */
function PHM_version_compare($is, $should)
{
    $is = preg_replace('![^0-9\.]!', '', $is);
    $is = explode('.', $is);

    $should = preg_replace('![^0-9\.]!', '', $should);
    $should = explode('.', $should);

    foreach ($should as $k => $v) {
        if (!isset($is[$k])) {
            $is[$k] = 0;
        }
        if ($should[$k] < $is[$k]) {
            return true;
        }
        if ($should[$k] > $is[$k]) {
            return false;
        }
    }
    return true;
}

/**
 * Function to join _PM_ with other config sources (like user settings).
 * Since array_merge canonly merge flat arrays and array_merge_recursive appends doublettes
 * to the father element we have to do the merge "manually"
 * @param array  inital _PM_ array
 * @param array  Data to join the array with (identical structure!)
 * @return array  Merged _PM_ array
 * @since 3.2.4
 */
function merge_PM($_PM_, $import)
{
    if (!is_array($import) || empty($import)) {
        return $_PM_;
    }
    foreach ($import as $k => $v) {
        if (is_array($v)) {
            foreach ($v as $k2 => $v2) {
                $_PM_[$k][$k2] = $v2;
            }
        } else {
            $_PM_[$k] = $v;
        }
    }
    return $_PM_;
}


/**
 * Outputs Javascript to the client to allow communication between frontend and application.
 * In case of JSON data to send, just pass the array, object or scalar to send, otherwise
 * pass a string.
 *
 * @param string|array Javascript command string / JSON structure to send
 * @param bool Whether this is JSON or real javascript code; Default: TRUE
 * @param bool Whether to exit right after sending the output; Defautl true
 * @return void
 * @since 3.3.6
 */
function sendJS($command, $is_json = true, $exit = true)
{
    if (!headers_sent()) {
        header('ETag: PUB' . time());
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()-10) . ' GMT');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 5) . ' GMT');
        header('Pragma: no-cache');
        header('Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate');
        header('Content-Type: '.($is_json ? 'application/json' : 'text/javascript').'; charset=utf-8');
    }
    if ($is_json) {
        echo json_encode($command);
    } else {
        echo $command, LF;
    }
    if ($exit) {
        exit;
    }
}

/**
 * Use our own implementation of QP encoder
 *
 * @param string $return  Unencoded string
 *[@param int $maxlen  Maimum line length; Deafult: 75]
 * @return string Encoded string
 * @since 4.1.2
 */
function phm_quoted_printable_encode($return = '', $maxlen = 75)
{
    $schachtel = '';
    $return = rtrim($return, "\r\n");
    // Ersetzen der lt. RFC 1521 nötigen Zeichen
    $return = preg_replace_callback('/([^\t\x20\x2E\041-\074\076-\176])/i', create_function('$s', 'return sprintf("=%02X", ord($s[1]));'), $return);
    // Einfügen von QP-Breaks (=\r\n)
    if ($maxlen && strlen($return) > $maxlen) {
        $length = strlen($return); $offset = 0;
        do {
            $step = 76;
            $add_mode = (($offset+$step) < $length) ? 1 : 0;
            $auszug = substr($return, $offset, $step);
            if (preg_match('!\=$!', $auszug)) {
                $step = 75;
            }
            if (preg_match('!\=.$!', $auszug)) {
                $step = 74;
            }
            if (preg_match('!\=..$!', $auszug)) {
                $step = 73;
            }
            $auszug = substr($return, $offset, $step);
            $offset += $step;
            $schachtel .= $auszug;
            if (1 == $add_mode) {
                $schachtel.= '='.CRLF;
            }
        } while ($offset < $length);
        $return = $schachtel;
    }
    $return = preg_replace('!\.$!', '. ', $return);
    return rtrim($return, "\r\n").CRLF;
}

/**
 * Returns the current timezone as an offset (e.g. +08:00) from GMT
 * @param int  $refdate  A timestamp representing the reference date
 **/
function utc_offset($refdate = null)
{
    // Find the difference in seconds between GMT and local time.
    $year = date('Y', $refdate);
    $month = date('n', $refdate);
    $day = date('j', $refdate);

    $diff = gmmktime(0, 0, 0, $month, $day, $year) - mktime(0, 0, 0, $month, $day, $year);
    $sign = $diff >= 0 ? '+' : '-';
    $diff = abs($diff);
    $hours = str_pad(strval(floor($diff / 3600)), 2, '0', STR_PAD_LEFT);
    $minutes = str_pad(strval(floor($diff / 60) % 60), 2, '0', STR_PAD_LEFT);
    return $sign.$hours.':'.$minutes;
}

// if sha1() is not available, we use the PHP implementation
if (!function_exists('sha1')) {
    if (function_exists('hash_algos') && in_array('sha1', hash_algos())) {
        function sha1($str)
        {
            return hash('sha1', $str);
        }
    } else {
        function sha1($str)
        {
            return SHA1::compute($str);
        }
    }
}
// Likewise for sha256
if (!function_exists('sha256')) {
    if (function_exists('hash_algos') && in_array('sha256', hash_algos())) {
        function sha256($str)
        {
            return hash('sha256', $str);
        }
    } else {
        function sha256($str)
        {
            return SHA256::hash($str, 'hex');
        }
    }
}
