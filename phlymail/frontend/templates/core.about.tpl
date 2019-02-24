<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>phlyMail - {about}</title>
    <style type="text/css">
    html {background:white;color:black;font-family:Arial,Sans-Serif,Verdana;font-size:9pt;}
    #logo {height:66px;}
    #logo img {display:block;margin:auto;}
    #name {font-weight:bold;text-align:center;margin:8px;}
    #copy {text-align:left;vertical-align:top;margin:16px 0;}
    #ackno {font-size:8pt;text-align:left;vertical-align:top;}
    a {color:rgb(4,54,88);text-decoration:underline;}
    ul {margin:0;padding-left:16px;}
    </style>
    <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-min.js"></script>
    <script type="text/javascript">
    //<![CDATA[
    $(document).ready(function (e) {
        var wh = $(window).height();
        var dh = $(document).height();
        var ww = $(window).width();
        var dw = $(document).width();
        window.resizeBy(ww < dw ? dw-ww : 0, wh < dh ? dh-wh : 0);
    });
    //]]>
    </script>
</head>
<body>
    <div id="logo"><img src="{frontend_path}/templates/about_phlymail.png" alt="{about_product} {about_version}" title="{about_product} {about_version}" /></div>
    <div id="name">{about_product} {about_version}</div>
    <div id="copy">
        <a href="http://phlymail.com" target="_blank">phlyMail</a>
        is &copy; 2001-2016 <a href="http://phlylabs.de" target="_blank">phlyLabs</a>, Berlin.
        All rights reserved. phlyMail, the phlyMail logo and phlyLabs are trademarks of phlyLabs, Berlin.
    </div>
    <div id="ackno">
        <strong>This software makes use of:</strong>
        <ul>
            <li><a href="http://www.feverxl.org/template/" target="_blank">FXL Template</a> engine by <a href="http://www.feverxl.com/" target="_blank">fever XL</a>, Berlin</li>
            <li><a href="http://code.google.com/p/sabredav/" target="_blank">SabreDAV</a>, a PHP WebDAV server framework</li>
            <li><a href="http://jquery.com" target="_blank">jQuery</a> Javascript framework</li>
            <li><a href="http://ckeditor.com" target="_blank">CKeditor</a> by Frederico Caldeira Knabben</li>
            <li><a href="http://wpaudioplayer.com" target="_blank">WP Audio Player</a> for MP3 playback</li>
        </ul>
        <br />
        <strong>We wish to thank:</strong>
        <ul>
            <li>Nicolas Schmerber, Bertrand Wolf for the French translation</li>
            <li>Wladimir Ganopolsky &#10013; for the Russian translation</li>
            <li>funknetz.at for the Slovak translation</li>
        </ul>
    </div>
</body>
</html>