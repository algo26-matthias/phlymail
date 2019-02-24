<!-- START query -->
<form action="{PHP_SELF}" method="post">
<table border="0" cellpadding="2" cellspacing="0" style="width:95%;margin:auto;">
<tr>
 <td colspan="2"><div align="center"><strong>{msg_lost_pw}</strong></div>
 <br />
 {msg_enter}<br />
 <!-- START error --><br />{error}<br /><!-- END error --></td>
</tr>
<tr>
 <td width="50%" align="right"><strong>{msg_popuser}:</strong></td>
 <td width="50%" align="left"><input id="user" type="text" name="user" value="{user}" size="20" /></td>
</tr>
<tr>
 <td></td>
 <td align="left">
  <input type="submit" value="{msg_send}" />
 </td>
</tr>
</table>
</form><!-- END query --><!-- START okay -->
<div style="margin:8px 8px 16px 8px;">{msg_okay}</div>
<a href="{login}">{msg_back}</a><br />
<br /><!-- END okay -->