<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{iso_language}" xml:lang="{iso_language}">
<head>
    <title>{version}</title>
    {metainfo}
    <style type="text/css">
    html {
        height:100%;
        background:white;
        background:-moz-linear-gradient(center top, white 0%, #e6e6e6 25%, white 100%) repeat fixed 0 0 transparent;
        background:-webkit-gradient(linear, left top, left bottom, color-stop(0%,white), color-stop(25%,#e6e6e6), color-stop(100%,white));
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e6e6e6', endColorstr='#ffffff',GradientType=0 );
        background:linear-gradient(center top, white 0%, #e6e6e6 25%, white 100%) repeat fixed 0 0 transparent;
        -moz-box-shadow:inset 0px 0px 8px rgb(150,150,150);
        -webkit-box-shadow:inset 0px 0px 8px rgb(150,150,150);
        box-shadow:inset 0px 0px 8px rgb(150,150,150);
    }
    body { background:transparent; }
    #loginbox, #cookie_warning {
        -moz-box-shadow:2px 2px 8px rgba(0,0,0,0.4);
        -webkit-box-shadow:2px 2px 8px rgba(0,0,0,0.4);
        box-shadow:2px 2px 8px rgba(0,0,0,0.4);
    }
    #loginbox { margin-top:100px;width:384px;background:#bcccff url({theme_path}/images/login_teaser.png) 0px 0px no-repeat; }
    #cookie_warning { width:374px;padding:8px;}
    </style>
</head>
<body class="{bidi-direction}">
    <div class="sendmenubut" id="loginbox">
        <a href="http://phlymail.com" style="display:block;width:384px;height:84px;">&nbsp;</a>
        <div style="text-align:center;vertical-align:top;margin-bottom:12px;">
        {phlymail_content}
        </div>
    </div><!-- START sessioncookie_on -->
    <div id="cookie_warning">
        <img src="{theme_path}/images/raw_cookie.png" id="cookie_image" alt="" />
        {msg_cookie_warning}
    </div><!-- END sessioncookie_on --><!-- START mobile_advertise -->
    <div id="boxgotomobile">
        <img src="{theme_path}/images/fat_arrow_down.png" alt="" />
        <a href="./m.php">
            <img src="{theme_path}/images/goto_mobile.png" alt="" title="%h%GoToMobilePage%" />
        </a>
    </div><!-- END mobile_advertise -->
</body>
</html>