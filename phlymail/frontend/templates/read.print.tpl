<script type="text/javascript">/*<![CDATA[*/window.onload = function (e) { window.setTimeout('window.print();', 10); }/*]]>*/</script>
<div class="l" style="font-size: 10pt; padding: 4px; font-family: Arial, Helvetica, Verdana, Sans Serif; border-top: 2px solid black; background: rgb(245, 245, 245);">
 <strong>{msg_printview}</strong>
</div>
<div id="header" class="l" style="background: rgb(245, 245, 245); padding: 0px 4px 4px 4px; margin-bottom: 4px;">
 <table border="0" cellpadding="2" cellspacing="0"><!-- START headerlines -->
  <tr>
   <td class="t l" width="50"><strong>{hl_key}:</strong>&nbsp;</td>
   <td class="t l"><span{hl_add}>{hl_val}</span>&nbsp;{hl_eval}</td>
  </tr><!-- END headerlines -->
 </table>
</div>
<div id="mbody_prev">{mbody}</div><!-- START attachblock -->
<div id="attachments" style="text-align: left; font-size: 10pt; padding: 4px; font-family: Arial, Helvetica, Verdana, Sans Serif; border-top: 1px solid black; background: rgb(245, 245, 245);">
 <strong>{msg_attachs}</strong><br />
 <div id="attachmentcontainer">
  <table border="0" cellpadding="0" cellspacing="1"><!-- START attachline -->
   <tr>
    <td class="l" class="menuline">
     <img src="{frontend_path}/filetypes/16/{att_icon}" align="absmiddle" alt="" title="{att_icon_alt}" />&nbsp;
     <a href="{link_target}" target="_blank">{att_name}</a>&nbsp;&nbsp;{att_size}&nbsp;{msg_att_type}:&nbsp;{att_type}
    </td>
   </tr><!-- END attachline -->
  </table>
 </div>
</div><!-- END attachblock -->