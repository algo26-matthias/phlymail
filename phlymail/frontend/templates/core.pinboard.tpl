<script type="text/javascript" src="{frontend_path}/js/core.pinboard.js?{current_build}"></script>
<script type="text/javascript">
//<![CDATA[
urlThemePath = '{theme_path}';
urlFrontend = '{frontend_path}';
urlRefreshBox = '{url_refresh}';
htmlBiDi = '{bidi-direction}';
msgRefresh = '{msg_refresh}';

boxinfo = {};<!-- START addbox -->
boxinfo['{handler}_{boxname}'] = {'stats' : { 'headline' : '{headline}', 'icon' : '{icon}', 'action' : '{action}' }
    ,'cols' : {cols}
    ,'rows' : {rows}
    };
boxCount++;<!-- END addbox -->
//]]>
</script>
<div id="core_pinboard"></div>