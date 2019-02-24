<!DOCTYPE html>
<html lang="{iso_language}">
    <head>
        <title>{version}</title>
        {metainfo}
        <link rel="stylesheet" href="{confpath}/schemes/{scheme}.css?{current_build}" type="text/css" />
        <link rel="shortcut icon" type="image/png" href="favicon.png?{current_build}" />
        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/jquery/jquery-ui-min.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/thickbox.js?{current_build}"></script>
        <script type="text/javascript" src="{frontend_path}/js/menus.js?{current_build}"></script>
        <script type="text/javascript" src="{confpath}/js/main.js?{current_build}"></script>
        <script type="text/javascript">
        //<![CDATA[
        function pleasewait_on() { document.getElementById('pleasewait').style.display = 'block'; }
        function pleasewait_off() { document.getElementById('pleasewait').style.display = 'none'; }
        //]]>
        </script>
    </head>
    <body>
        {phlymail_content}
    </body>
</html>
