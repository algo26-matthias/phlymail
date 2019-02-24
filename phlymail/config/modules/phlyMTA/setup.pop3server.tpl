<div align="left">
{about_pop3server}<br />
<br />
<fieldset>
 <legend>{leg_settings}</legend>
 <form action="{form_action}" method="post">
 {about_settings}<br />
 <br />
 <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td align="left" valign="top" width="35%"><b>{msg_max_childs}:</b></td>
   <td align="left" valign="top">
    <input type="text" name="max_childs" value="{max_childs}" size="3" maxlength="4" />
   </td>
  </tr>
  <tr>
   <td colspan="2" align="left" class="conttd">
    {about_machilds}</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
   <td align="left" valign="top"><b>{msg_listeningport}:</b></td>
   <td align="left" valign="top">
    <input type="text" name="listening_port" value="{listening_port}" size="5" maxlength="5" />
   </td>
  </tr>
  <tr>
   <td colspan="2" align="left" class="conttd">
    {about_port}</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
   <td align="left" valign="top"><b>{msg_timeo_auth}:</b></td>
   <td align="left" valign="top">
    <input type="text" name="timeout_auth" value="{timeout_auth}" size="2" maxlength="3" /> <strong>s</strong>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top"><b>{msg_timeo_trans}:</b></td>
   <td align="left" valign="top">
    <input type="text" name="timeout_trans" value="{timeout_trans}" size="2" maxlength="3" /> <strong>s</strong>
   </td>
  </tr>
  <tr>
   <td colspan="2" align="left" class="conttd">
    {about_timeouts}</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
   <td align="left" valign="top"><b>{msg_runas}:</b></td>
   <td align="left" valign="top">
    <input type="text" name="runas" value="{runas}" size="8" maxlength="64" />
   </td>
  </tr>
  <tr>
   <td colspan="2" align="left" class="conttd">
    {about_runas}</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr><!-- START plaintext -->
  <tr>
   <td align="left" colspan="2">
    <input type="checkbox" name="use_clearpw" id="use_clearpw" <!-- START useclear -->checked="checked"<-- END useclear --> value="1" />
    <label for="use_clearpw">{msg_useclearpw}</label>
   </td>
  </tr><!-- END plaintext -->
  <tr>
   <td align="left" valign="top"> </td>
   <td align="left" valign="top">
    <input type="submit" value="{msg_save}" />
   </td>
  </tr>
 </table>
 </form>
</fieldset><br />
<br />
<fieldset>
 <legend>{leg_state}</legend><!-- START state_stop -->
 {msg_is_running} <form action="{stop_url}" method="post" style="display:inline"><input type="submit" value="{msg_stop}" /></form><!-- END state_stop --><!-- START state_start -->
 {msg_not_running} <form action="{start_url}" method="post" style="display:inline"><input type="submit" value="{msg_start}" /></form><!-- END state_start -->
</fieldset>
</div>