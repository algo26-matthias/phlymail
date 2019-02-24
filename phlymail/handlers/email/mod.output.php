<?php
/**
 * mod.output.php -> Output a certain MIME part
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Handler Email
 * @copyright 2004-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.2.9 2015-05-21
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// We need the mail id
if (!empty($item)) {
    $id = $item;
} elseif (!empty($_REQUEST['mail'])) {
    $id = $_REQUEST['mail'];
}

// The print view includes this module for inline view of the mail body
if (defined('_PM_OUTPUTTER_INCLUDED_')) {
    $save = false;
} else {
    if (!isset($id) || (!isset($_REQUEST['part']) && !isset($_REQUEST['cid']))) {
        exit; // Nothing given, nothing to do
    }
    $STOR = new handler_email_driver($_SESSION['phM_uid']);
    $struct = $STOR->get_mail_structure($id);
    $print = false;
    $mobile = (defined('PHM_MOBILE'));
    if (isset($_REQUEST['cid'])) {
        $num = false;
        foreach ($struct['body']['content_id'] as $k => $v) {
            if ('<'.$_REQUEST['cid'].'>' == $v) {
                $num = $k;
                break;
            }
        }
        if (false === $num) {
            exit;
        }
        $save = true;
    } else {
        $num = $_REQUEST['part'];
        $save = (isset($_REQUEST['save']) && 1 == $_REQUEST['save']);
    }
    $sanitize = isset($_REQUEST['sanitize'])
            ? ($_REQUEST['sanitize'])
            : ((isset($_SESSION['phM_sanitize_html']) && !$_SESSION['phM_sanitize_html']) ? false : true);
}
$content_type = (isset($struct['body']['part_type'][$num]) && $struct['body']['part_type'][$num])
        ? $struct['body']['part_type'][$num]
        : ((isset($struct['header']['content_type'])) ? $struct['header']['content_type'] : 'text/plain' );
$encoding = (isset($struct['body']['part_encoding'][$num]) && $struct['body']['part_encoding'][$num])
        ? $struct['body']['part_encoding'][$num]
        : ((isset($struct['header']['content_encoding'])) ? $struct['header']['content_encoding'] : '7bit' );
$ctype_pad = (isset($struct['body']['part_detail'][$num]) && $struct['body']['part_detail'][$num])
        ? $struct['body']['part_detail'][$num]
        : ((isset($struct['header']['content_type_pad'])) ? $struct['header']['content_type_pad'] : '' );
$teletype = $_SESSION['phM_tt'];
session_write_close();

// The given part is invalid, so we don't output anything
if (!isset($struct['body']['length'][$num])) {
    exit;
}

$mailinfo = $STOR->get_mail_info($id, true);
if ($mailinfo['cached']) {
    $STOR->mail_open_stream($id, 'r');
    $STOR->mail_seek_stream($struct['body']['offset'][$num]);
    $length = $struct['body']['length'][$num];
} else {
    list ($mbox, $length) = $STOR->get_imap_part($id, $struct['body']['imap_part'][$num]);
}
if ($save) {
    $save_as = 'noname';
    if (isset($struct['body']['part_detail'][$num])
            && preg_match('!name=("?)(.*)(\1)!i', $struct['body']['part_detail'][$num], $found)) {
        $save_as = $found[2];
    } elseif (isset($struct['body']['dispo_pad'][$num])
            && preg_match('/name=("?)(.*)(\1)/i', $struct['body']['dispo_pad'][$num], $found)) {
        $save_as = $found[2];
    }
    if ($content_type == 'message/delivery-status') {
       $content_type = 'text/plain';
       $save_as = 'delivery_status.txt';
    }
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
    } else {
        header('Cache-Control: post-check=0, pre-check=0');
    }
    header('Content-Type: application/octet-stream; name="'.basename($save_as).'"');
    if (isset($_REQUEST['inline'])) {
        header('Content-Disposition: inline; filename="'.basename($save_as).'"');
    } else {
        header('Content-Disposition: attachment; filename="'.basename($save_as).'"');
    }
    header('Content-Transfer-Encoding: binary');
    $read = $echoed = 0;
    while (true) {
        $line = ($mailinfo['cached']) ? $STOR->mail_read_stream(0) : $mbox->talk_ml();
        if (!$line) {
            exit;
        }
        $read += strlen($line);
        // Prevent echoing the final ")" in IMAP communication on unterminated final line
        if ($read > $length) {
            $line = substr($line, 0, $length-$echoed);
        }
        if (strtolower($encoding) == 'quoted-printable') {
            echo quoted_printable_decode(str_replace('='.CRLF, '', $line));
        } elseif(strtolower($encoding) == 'base64') {
            echo base64_decode($line);
        } else {
            echo $line;
        }
        $echoed = $read;
        if ($read >= $length) {
            if (!$mailinfo['cached']) {
                while (false !== $mbox->talk_ml()) { /* void */ }
                $mbox->close();
            }
            exit;
        }
    }
} else {
    $mailbody = '';
    if ($mailinfo['cached']) {
        $mailbody = $STOR->mail_read_stream($struct['body']['length'][$num]);
    } else {
        $read = 0;
        while (true) {
            $line = $mbox->talk_ml();
            if (false === $line) {
                break;
            }
            $read += strlen($line);
            $mailbody .= $line;
            if ($read >= $length) {
                // Prevent echoing the final ")" in IMAP communication on unterminated final line
                $mailbody = substr($mailbody, 0, $length);
                // Prevent hanging of comm. channel
                while (false !== $mbox->talk_ml()) { /* void */ }
                $mbox->close();
                break;
            }
        }
    }

    if (strtolower($encoding) == 'quoted-printable') {
        $mailbody = quoted_printable_decode(str_replace('='.CRLF, '', $mailbody));
    } elseif (strtolower($encoding) == 'base64') {
        $mailbody = base64_decode($mailbody);
    }
    // This fails miserably whenever dumb clients like Outlook get used, which do not denote quote level by the use of >
    // $mailbody = Format_Parse_Email::hidePgpMarkup($mailbody);
    // Find charset
    if ($ctype_pad) {
        preg_match('!charset(\s*)=(\s*)"?([^";]+)("|$|;)!i', $ctype_pad, $found);
    }
    $charset = (isset($found[3])) ? $found[3] : 'utf-8';
    if (strtolower($charset) == 'us-ascii') { // htmlspecialchars does not know it ...
        $charset = 'utf-8';
    }

    if (strtolower($content_type) == 'text/html') {
        if (defined('_PM_OUTPUTTER_HTML2TEXT_')) {
            // Remove anything, that might disturb the HTML output. We cannot use an iframe in this context, so special care must be taken
            $mailbody = preg_replace(array('!\<head.+\</head\>!simU', '!\<style.+\</style\>!simU', '!\<script.+\</script\>!simU'), '', $mailbody);
            $mailbody = strip_tags($mailbody, '<a><p><div><img><br><strong><b><i><em><u><s><ul><li><ol><dl><dt><di><table><tr><td><th><col><colgroup><fieldset><legend><h1><h2><h3><h4><h5><h6>');
            $mailbody = preg_replace( '/\s+/', ' ', $mailbody);
        }
        if ($print || $mobile) {
            $tpl->assign('mbody', links
                    (encode_utf8($mailbody, $charset, true)
                    ,'html'
                    ,$sanitize
                    ,htmlspecialchars(PHP_SELF.'?l=output&h=email&mail='.$id.'&'.give_passthrough(1).'&cid=')
                    ));
            return;
        }
        header('Content-Type: text/html; charset='.$charset);
        if (!preg_match('!(\<body)!', $mailbody)) {
            $mailbody = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'.LF
                    .'<html>'.LF
                    .'<head>'.LF
                    .'<title>phlyMail</title>'.LF
                    .'</head>'.LF
                    .'<body>'.LF
                    .$mailbody
                    .'</body>'.LF
                    .'</html>';
        }
        $mailbody = links
                ($mailbody
                ,'html'
                ,$sanitize
                ,htmlspecialchars(PHP_SELF.'?l=output&h=email&mail='.$id.'&'.give_passthrough(1).'&cid=')
                );
        echo preg_replace('!(\<body([^>]*))\>!i', '\\1\\2 id="mailtext" style="background:white;" onload="parent.view_inline();">', $mailbody);
        exit;
    } elseif (strtolower($content_type) == 'text/enriched') {
        // Convert Richtext to HTML
        $mailbody = str_replace('<<', '&lt;', $mailbody);
        $mailbody = nl2br(enriched_correct_newlines($mailbody));
        $mailbody = str_replace
                (array('<bold>', '</bold>', '<italic>', '</italic>', '<underline>', '</underline>', '<fixed>', '</fixed>'
                        , '<smaller>', '</smaller>', '<bigger>', '</bigger>', '<center>', '</center>')
                ,array('<strong>', '</strong>', '<i>', '</i>', '<span style="text-decoration:underline;">', '</span>', '<tt>', '</tt>'
                        , '<font size="-1">', '</font>', '<font size="+1">', '</font>', '<div style="text-align:center;">', '</div>')
                ,$mailbody
                );
        $mailbody = enriched_correct_colour($mailbody);
        $mailbody = enriched_remove_unsupported($mailbody);
        if ($print || $mobile) {
            $tpl->assign('mbody', links
                    (encode_utf8($mailbody, $charset, true)
                    ,'html'
                    ,$sanitize
                    ,htmlspecialchars(PHP_SELF.'?l=output&h=email&mail='.$id.'&'.give_passthrough(1).'&cid=')
                    ));
            return;
        }
        header('Content-Type: text/html; charset='.$charset);
        echo '<html><head><title>Autconverted text/enriched part</title><meta http-equiv="content-type" content="text/html; charset='
                .$charset
                .'"></head><body id="mailtext" onload="parent.view_inline();">'
                .links
                        ($mailbody
                        ,'html'
                        ,$sanitize
                        ,htmlspecialchars(PHP_SELF.'?l=output&h=email&mail='.$id.'&'.give_passthrough(1).'&cid=')
                        )
                .'</body></html>';
        exit;
    } else {
        $fonttag = $endtag = '';
        if (!$mobile) {
            if ('sys' == $teletype) {
                $fonttag = '<tt style="font-size:'.$_PM_['core']['plaintext_fontsize'].'pt;">';
                $endtag = '</tt>';
            } elseif (isset($_PM_['core']['plaintext_fontface']) && $_PM_['core']['plaintext_fontface']) {
                $fonttag = '<div style="font-family:'.$_PM_['core']['plaintext_fontface'].';font-size:'.$_PM_['core']['plaintext_fontsize'].'pt;">';
                $endtag = '</div>';
            }
        }
        $parseFormat = (!isset($_PM_['core']['parseformat']) || $_PM_['core']['parseformat']);
        $mailbody = nice_view($mailbody, $teletype, $parseFormat, $charset);
        if (!isset($_PM_['core']['parsesmileys']) || $_PM_['core']['parsesmileys']) {
            $mailbody = Smiley::parse($mailbody, $_PM_['path']['frontend'].'/smileys');
        }
        $mailbody = $fonttag.str_replace('{', '{\\', $mailbody).$endtag;
        if ($print || $mobile) {
            $tpl->assign('mbody', $mailbody);
            return;
        }
    }
    $tpl = $mailbody;
}

function enriched_correct_newlines($input)
{
    $input = str_replace(CRLF, LF, $input);
    $len = strlen($input);
    $LE = 0;
    $output = '';
    for ($i = 0; $i < $len; $i++){
        $c = $input{$i};
        if ($c == LF) {
            ++$LE;
        }
        if ($LE && $c != LF) {
            $LE = 0;
        }
        $output .= ($LE != 1) ? $c : ' ';
    }
    return $output;
}

function enriched_correct_colour($input)
{
    while (preg_match('!(.*)\<color\>\<param\>(.*)\<\/param\>(.*)\<\/color\>(.*)!smi', $input, $found)) {
        if (!isset($found[4])) {
            continue;
        }
        if (strpos($found[2], ',')) {
            $rgb = explode(',', $found[2]);
            $colour ='#';
            for ($i = 0; $i < 3; ++$i) {
                $colour .= substr($rgb{$i}, 0, 2);
            }
        } else {
            $color = $found[2];
        }
        $input = $found[1].'<span style="color: '.$color.'">'.$found[3].'</span>'.$found[4];
    }
    return $input;
}

function enriched_remove_unsupported($input)
{
    preg_match_all('!<(\w+)>(.+)</\1>!Us', $input, $found);
    foreach ($found[1] as $k => $matches) {
        // Those are left in
        if (in_array($matches, array('strong', 'i', 'span', 'tt', 'font'))) {
            continue;
        }
        // Those are ignored, but the content may be of interest
        if (in_array($matches, array('no-op', 'paraindent', 'fontfamily', 'flushleft', 'flushright', 'flushboth', 'indent', 'indentright', 'excerpt'))) {
            $input = str_replace($found[0][$k], preg_replace('!<param>.*</param>!Uis', '', $found[2][$k]), $input);
            continue;
        }
        $input = str_replace($found[0][$k], '', $input);
    }
    return $input;
}


/**
 * Nicely formats plain text body parts for HTML output in the forntend
 *
 * @param strin $return   The body part to format
 * @param string $teletype   Either sys or pro
 * @param bool $parseFormat  Whether to replace *bold*, /italic/ and _underline_ by HTML markup
 * @param string $charset  Default: utf-8, any charset, htmlspecialchars supports might be used
 */
function nice_view($return = '', $teletype = '', $parseFormat = true, $charset = 'utf-8')
{
    $sigon = false;
    $return = str_replace(array(CRLF, "\r"), array(LF, LF), $return);
    $lines = explode(LF, $return);
    if (!count($lines)) {
        return '';
    }
    foreach ($lines as $ky => $val) {
        $val = encode_utf8($val, $charset);
        if ($val == '-- ') {
            $sigon = true;
        }
        if ($sigon) {
            $lines[$ky] = '<span class="quote_1">'.links($val, 'text').'</span>';
            continue;
        }
        if (!empty($GLOBALS['_PM_']['theme']['read_wordwrap'])
                && preg_match('/([^\s]{'.$GLOBALS['_PM_']['theme']['read_wordwrap'].',})/', $val)) {
            if (!preg_match('!(http://|https://|ftp://|gopher://|mailto:|news:)!', $val)) {
                $val = preg_replace('/([^\s]{'.$GLOBALS['_PM_']['theme']['read_wordwrap'].'})/', '\\1 ', $val);
            }
        }
        // Replace text formattings (if set)
        if ($parseFormat) {
            $val = preg_replace
                    (array('/(?<=^|\s)\*(?=\S)(.+)(?<=\S)\*(?=\s|,|.|$)/U'
                            ,'/(?<=^|\s)\/(?=\S)(.+)(?<=\S)\/(?=\s|,|.|$)/U'
                            ,'/(?<=^|\s)\_(?=\S)(.+)(?<=\S)\_(?=\s|,|.|$)/U'
                            )
                    ,array('<strong>*\1*</strong>', '<em>/\1/</em>', '<span class="underline">_\1_</span>')
                    ,$val
                    );
        }
        unset($found);
        if (preg_match_all('!^(\ ?(\&gt;\ ?)+)!i', $val, $found)) {
            $farbe = (substr_count($found[0][0], '&gt;') % 4);
            if (0 == $farbe) {
                $farbe = 4;
            }
            $lines[$ky] = '<span class="quote_'.$farbe.'">'. (('sys' == $teletype) ? '<tt>'.links($val, 'text').'</tt>' : links($val, 'text')) . '</span>';
        } else {
            $lines[$ky] = links($val, 'text');
        }
    }
    return implode('sys' == $teletype ? LF : '<br />'.LF, $lines);
}
