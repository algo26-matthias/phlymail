<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{iso_language}" xml:lang="{iso_language}">
<head>
<title>{version}</title>
{metainfo}
<script type="text/javascript">
/*<![CDATA[*/
{javascript}
//]]>
</script>
</head>
<body class="{bidi-direction}">
<div>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
 <td class="t l solid_line" id="topmenucontainer">
 <div style="float: left;">
 <table border="0" cellpadding="0" cellspacing="0">
  <tr class="solid_nodrop" id="pm_menu_container">
   <td><a href="javascript:void(0);" id="topmendrop_new" class="active" onmouseover="pm_menu_create('new');" onclick="pm_menu_switch(this)">{top_new}</a></td>
   <td class="men_separator"></td>
   <td><a href="javascript:void(0);" class="active" id="topmendrop_settings" onmouseover="pm_menu_create('settings');" onclick="pm_menu_switch(this)">{top_setup}</a></td>
   <td class="men_separator"></td>
   <td><a href="javascript:void(0);" id="topmendrop_exchange" class="active" onmouseover="pm_menu_create('exchange');" onclick="pm_menu_switch(this)">{top_exchange}</a></td>
   <td class="men_separator"></td>
   <td><a href="javascript:void(0);" class="active" id="topmendrop_view" onmouseover="pm_menu_create('view');" onclick="pm_menu_switch(this)">{top_view}</a></td>
   <td class="men_separator"></td>
   <td><a href="javascript:void(0);" class="active" id="topmendrop_fetchitems" onmouseover="pm_menu_create('fetchitems');" onclick="pm_menu_switch(this)">{top_getmsg}</a></td>
   <td class="men_separator"></td>
   <td><a href="javascript:void(0);" class="active" id="topmendrop_system" onmouseover="pm_menu_create('system');" onclick="pm_menu_switch(this)">{top_system}</a></td>
   <td class="men_separator"></td>
   <td><a class="active" href="{link_logout}" onclick="return core_prompt_logout();">{msg_logout}</a></td>
   <td class="men_separator"></td>
  </tr>
 </table>
 </div>
 </td>
</tr>
<tr>
 <td class="l t topbarcontainer" style="display:none;">
  {contextmenus} <!-- Kept for future addition: User definable button bar -->
 </td>
</tr>
</table>
</div>
<div id="namepane" class="sendmenutopline" style="display:none;">
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="mainbar">
  <tr>
   <td width="50%" class="l">
   <div style="float:left;margin-right:8px;"><img src="{theme_path}/icons/folder_def_big.gif" id="foldericon" alt="" /></div>
   <span id="foldername">{main_folderoverview}</span>
   </td>
   <td width="50%" class="r"> {loggedin_user} </td>
  </tr>
 </table>
</div>
<div id="favfolderpane" style="display:none;">
</div>
<div>
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="framecontainer">
 <tr>
  <td width="20%" height="200" id="PHM_tl_container" class="t l sendmenuborder">
   <iframe width="100%" height="200" src="about:blank" id="PHM_tl" name="PHM_tl" scrolling="auto" frameborder="0">
   </iframe>
  </td>
  <td width="4" class="greyed"><!-- START allowresize --><div id="middleresizer" style="padding:0px;margin:auto;height:100px;cursor:e-resize;" onmousedown="dragx.start(this)"><img src="{theme_path}/images/resize_h.gif" alt="|" title="|" /></div><!-- END allowresize --> </td>
  <td colspan="2" class="t l sendmenuborder">
   <iframe width="100%" height="200" src="about:blank" id="PHM_tr" name="PHM_tr" scrolling="no" frameborder="0">
   </iframe>
  </td>
 </tr>
 </table>
</div>
<div id="bottomline">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <colgroup>
            <col width="*" />
            <col width="4" />
            <col width="120" />
            <col width="4" />
            <col width="60" />
        </colgroup>
        <tbody>
            <tr class="solid_nodrop">
                <td class="tdl">
                    <div id="icontray">
                        <img id="quotaicon_okay" style="display:none;" src="{theme_path}/icons/quota_okay_men.gif" alt="{msg_quotaokay}" title="{msg_quotaokay}" />
                        <img id="quotaicon_medium" style="display:none;" src="{theme_path}/icons/quota_med_men.gif" alt="{msg_quotamedium}" title="{msg_quotamedium}" />
                        <img id="quotaicon_bad" style="display:none;" src="{theme_path}/icons/quota_bad_men.gif" alt="{msg_quotabad}" title="{msg_quotabad}" />
                        <img id="show_pinboard" src="{theme_path}/icons/pinboard_men.png" alt="{msg_showpinboard}" title="{msg_showpinboard}" />
                    </div>
                    <div id="statustext"></div>
                </td>
                <td class="men_separator"></td>
                <td class="tdc">
                    <div id="newmailsound" style="width:1px;height:1px;float:left;"></div>
                    <div id="fetcher_outer" class="prgr_outer" style="visibility:hidden;">
                        <div id="fetcher_inner" class="prgr_inner"></div>
                    </div>
                </td>
                <td class="men_separator"></td>
                <td class="tdc" id="showclock"></td>
            </tr>
        </tbody>
    </table>
</div>
<div id="notify-container">
    <div id="notify-basic-template">
        <a class="ui-notify-cross ui-notify-close" href="#">x</a>
        <h1>#{\title}</h1>
        <p>#{\text}</p>
    </div>
</div>
<div id="float_win_src" style="display: none;" class="floatwin_outline"><table border="0" cellpadding="0" cellspacing="0" class="floatwin_container"><tbody><tr><td onmousedown="float_drag(false, this)" class="floatwin_headline_l" width="98%"></td><td width="2%" class="floatwin_headline_r"><a href="">&nbsp;&nbsp;&nbsp;&nbsp;</a></td></tr><tr><td class="floatwin_content" colspan="2"></td></tr></tbody></table></div>
</body>
</html>