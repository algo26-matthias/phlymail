<form action="{PHP_SELF}?{passthrough}&special=activate" method="POST">
<input type="hidden" name="uid" value="{uid}" />
<table border="0" cellpaddin="2" cellspacing="0">
<tr>
 <td class="l" colspan="2">{msg_actnow}<br /></td>
</tr><!-- START error -->
<tr>
 <td class="l" colspan="2" style="color:darkred"><strong>{error}</strong></td>
</tr><!-- END error -->
<tr>
 <td class="l">{msg_popuser}</td>
 <td class="l"><input type="text" size="16" maxlength="32" name="username" value="{username}" /></td>
</tr>
<tr>
 <td class="l">{msg_syspass}</td>
 <td class="l"><input type="password" size="16" maxlength="32" name="password" value="{password}" /></td>
</tr>
<tr>
 <td>&nbsp;</td>
 <td class="l"><input type="submit" value="{msg_activate}" /></td>
</tr>
</table>
</form>