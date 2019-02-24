<!DOCTYPE html>
<html lang="{iso_language}">
    <head>
        <title>{version}</title>
        {metainfo}
        <link rel="stylesheet" href="{confpath}/schemes/{scheme}.css?{current_build}" type="text/css" />
        <link rel="icon" type="image/png" href="{frontend_path}/img/favicon.png?{current_build}">
        <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="{frontend_path}/img/favicon.ico?{current_build}">

        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-ui-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/thickbox.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/menus.js?{current_build}"></script>
        <script type="text/javascript" src="{confpath}/js/main.js?{current_build}"></script>
        <script type="text/javascript">
        //<![CDATA[
        function pleasewait_on() { $('#pleasewait').show(); }
        function pleasewait_off() { $('#pleasewait').hide(); }
        tb_pathToImage = '{frontend_path}/themes/Yokohama/images/pleasewait.gif'; // Thickbox loading animation (taken from theme's image library)
        //]]>
        </script>
    </head>
    <body lang="{iso_language}">
        <div style="width:900px;margin:16px auto;">
            <table cellpadding="0" cellspacing="0" border="0" style="background-color:#FFFFFF;width:900px;box-shadow:4px 4px 16px rgb(70%,70%,70%);border-radius:8px;">
                <tr>
                    <td colspan="2" style="height:80px;background:rgb(1,124,179) url({confpath}/icons/config_top_back.png) no-repeat;border-radius:8px 8px 0 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="text-align:right !important;vertical-align:top !important">
                                    <div class="topteaser">{provider_name}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:right !important;vertical-align:bottom !important;">
                                    <div class="topmenu">
                                        <a href="{link_logout}">{msg_logout}</a> <strong>|</strong> <a href="{link_frontend}">{msg_frontend}</a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:200px;background:rgb(1,124,179);border-radius:0 0 0 8px;">
                        {menu}
                    </td>
                    <td style="padding:8px 10px 12px 10px;">
                        {phlymail_content}
                    </td>
                </tr>
            </table>
        </div>
        <div id="pleasewait" style="display:none;position:absolute;top:200px;width:100%;">
            <div style="margin:auto;width:65px;height:16px;background:url({confpath}/icons/semitrans.png) repeat;padding:10px;z-index:200;">
                <img src="{confpath}/icons/pleasewait.gif" style="display:block;" alt="..." />
            </div>
        </div>
    </body>
</html>