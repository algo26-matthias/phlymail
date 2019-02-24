<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{iso_language}" xml:lang="{iso_language}">
<head>
 <title>{version}</title>
 {metainfo}
 <style type="text/css">
 body { background:rgb(70,70,70); }
 #loginbox {
    width:383px;
 }
 #cookie_warning {
    width:375px;
 }
 #loginbox, #cookie_warning {
    -moz-box-shadow:0px 0px 8px 2px rgba(0, 0, 0, 0.6);
    -webkit-box-shadow:0px 0px 8px 2px rgba(0, 0, 0, 0.6);
    box-shadow:0px 0px 8px 2px rgba(0, 0, 0, 0.6);
 }
 </style>
</head>
<body class="{bidi-direction}">
    <div id="loginbox">
        <table cellpadding="0" cellspacing="0" border="0" width="383">
            <tr>
                <td style="background:black;border:1px solid black;border-top:0px;border-collapse:collapse;">
                    <a href="http://phlymail.com/" target="_blank" style="text-decoration:none;padding:0;margin:0;">
                        <img src="{theme_path}/images/login_teaser.png" alt="phlyMail" style="display:block;width:381px;height:84px;" />
                    </a>
                </td>
            </tr>
            <tr>
                <td class="sendmenubut" style="background:black;text-align:center;vertical-align:top;"><br />
                    {phlymail_content}<br />
                </td>
            </tr>
        </table>
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