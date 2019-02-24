<div align="left">
{about}<br />
<br />
<table cellpadding="2" cellspacing="0" border="0" width="100%">
 <tr>
  <td class="contthleft"><strong>{msg_optactive}</strong></td>
  <td class="contthmiddle" align="left"><strong>{msg_opthandler}</strong></td>
  <td class="contthmiddle" align="left"><strong>{msg_optdescr}</strong></td>
 </tr><!-- START modline --><!-- START odd --><!-- END odd -->
 <tr>
  <td class="conttd" valign="top" align="left"><!-- START isactive -->
   <img src="{confpath}/icons/module_active.png" title="{title}" /><!-- END isactive --><!-- START notactive -->
   <img src="{confpath}/icons/module_inactive.png" title="{title}" /><!-- END notactive -->
  </td>
  <td class="conttd" valign="top" align="left">&nbsp;{plugname}&nbsp;{version}</td>
  <td class="conttd" valign="top" align="left">{description}</td>
 </tr>
 <tr>
  <td class="conttdd" valign="top" align="left" colspan="3"><!-- START install -->
   <a href="{link}">{msg_install}</a>&nbsp;<!-- END install --><!-- START uninstall -->
   <a href="{link}">{msg_uninstall}</a>&nbsp;<!-- END uninstall --><!-- START noinstall -->
   &nbsp;<!-- END noinstall --><!-- START configure -->
   <a href="{link}" target="_blank">{msg_configure}</a>&nbsp;<!-- END configure --><!-- START noconfig -->
   &nbsp;<!-- END noconfig --><!-- START au -->
   <a href="{link}" target="_blank">{msg_au}</a>&nbsp;<!-- END au --><!-- START noau -->
   &nbsp;<!-- END noau -->
  </td>
 </tr><!-- END modline -->
</table>
</div>