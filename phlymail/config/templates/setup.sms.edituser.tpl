<a href="{link_um}">{where_um}</a>&nbsp;/&nbsp;<a href="{link_user}">{where_user}</a>&nbsp;/&nbsp;{where_setsms}<br />
<form action="{target_link}" method="post">
<div align="left"><!-- START error -->
 <br />
 <span class="texterror">{error}</span><br /><!-- END error -->
 <br />
 <fieldset>
  <legend>{leg_sms}</legend>
  {about_sms}<br />
  <br />
  <input type="checkbox" name="sms_active" value="1" id="smsact"<!-- START smsact --> checked="checked"<!-- END smsact --> />
  <label for="smsact">{msg_maysendsms}</label>
  <br />
  {msg_freesms}:&nbsp;<input type="text" name="freesms" value="{freesms}" size="4" maxlength="8" />
  <input type="checkbox" name="freemonthly" value="1" id="lbl_freemon"<!-- START freemon --> checked="checked"<!-- END freemon --> />
  <label for="lbl_freemon">{msg_monthly}</label>
  <br />
  <br />
  {msg_maxmonthly}:
  <input type="text" name="maxlimit" value="{maxlimit}" size="4" maxlength="8" /><br />
  <br />
  <input type="checkbox" name="fax_active" value="1" id="faxact"<!-- START faxact --> checked="checked"<!-- END faxact --> />
  <label for="faxact">{msg_maysendfax}</label>
  <br />
  <input type="checkbox" name="fax_0180_active" value="1" id="fax0180act"<!-- START fax0180act --> checked="checked"<!-- END fax0180act --> />
  <label for="fax0180act">{msg_maysendfax0180}</label>
  <br />
 </fieldset>
 <br />
 <input type="submit" value="{msg_save}" />
</div>
</form><br />
<br />
<fieldset>
 <legend>{leg_smsstat}</legend>
 <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td align="left">{msg_curruse}:</td>
   <td align="left">{curr_use} {msg_sms} ({msg_approx} {curr_approx}/{msg_month})</td>
  </tr>
  <tr>
   <td align="left">{msg_lastuse}:</td>
   <td align="left">{last_use} {msg_sms}</td>
  </tr>
 </table>
</fieldset>