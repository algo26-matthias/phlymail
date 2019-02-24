<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>{version}</title>
        {metainfo}
        <link rel="stylesheet" href="{confpath}/schemes/{scheme}.css?{current_build}" type="text/css" />
        <link rel="shortcut icon" type="image/png" href="favicon.png?{current_build}" />
        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-ui-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/thickbox.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/menus.js?{current_build}"></script>
    </head>
    <body>
        <div align="center"><br />
            <table cellpadding="0" cellspacing="0" border="0" width="381" style="border-radius:8px;box-shadow:2px 2px 8px 0px #808080">
                 <tr>
                    <td style="background:transparent;border-radius:8px 8px 0 0;">
                        <img src="{confpath}/icons/login_teaser_config.png" border="0" alt="phlyMail Config" width="381" height="84" style="display:block;border-radius:8px 8px 0 0;" />
                    </td>
                 </tr>
                 <tr>
                    <td align="left" valign="top" style="padding-left:10px;padding-right:10px;background:url(config/icons/maindrop.png) repeat-x;border-radius:0 0 8px 8px;">
                        <div align="center"><br />
                            {phlymail_content}<br />
                        </div>
                    </td>
                 </tr>
            </table>
        </div>
        <br clear="all" />
        <br />
    </body>
</html>