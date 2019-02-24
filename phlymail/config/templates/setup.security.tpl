<form action="{target_link}" method="post">
<div align="left">
<strong>{WP_return}</strong><br />
<br />
{head_text}<br />
<br />
<fieldset>
 <legend>{leg_sessionsec}</legend>
 {about_sessionsec}<br />
 <br />
 <input type="checkbox" name="WP_newsessionip" id="lbl_tie_ip" value="1"<!-- START sessionip --> checked<!-- END sessionip --> />
 <label for="lbl_tie_ip">&nbsp;{msg_sessionip}</label><br />
 <input type="checkbox" name="WP_newsessioncookie" id="lbl_sesscookie" value="1"<!-- START sessioncookie --> checked<!-- END sessioncookie --> />
 <label for="lbl_sesscookie">&nbsp;{msg_sessioncookie}</label>
</fieldset>
<br />
<br />
<fieldset>
 <legend>%t%LegendForceSSL%</legend>
 %t%AboutForceSSL%<br />
 <br />
 <input type="checkbox" name="WP_newforcessl" id="inp_force_ssl" value="1"<!-- START forcessl --> checked<!-- END forcessl --> />
 <label for="inp_force_ssl">&nbsp;%t%ForceSSL%</label><br />
</fieldset>
<br />
<br />
<fieldset>
 <legend>{leg_wronglogin}</legend>
 {about_wronglogin}<br />
 <br />
 <table cellspacing="0" cellpading="2" border="0">
  <tr>
   <td align="left">{msg_waitonfail}:</td>
   <td align="left">
    <input type="text" name="WP_newwaitfail" style="text-align:right;" value="{waitonfail}" size="8" maxlength="8" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_countonfail}:</td>
   <td align="left">
    <input type="text" name="WP_newcountfail" style="text-align:right;" value="{countonfail}" size="8" maxlength="8" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_lockonfail}:</td>
   <td align="left">
    <input type="text" name="WP_newlockfail" style="text-align:right;" value="{lockonfail}" size="8" maxlength="8" />
   </td>
  </tr>
 </table>
</fieldset>
<br />
<br />
<fieldset>
 <legend>{leg_proxy}</legend>
 {about_proxy}<br />
 <br />
 <table cellspacing="0" cellpading="2" border="0">
  <tr>
   <td align="left">{msg_server_param}:</td>
   <td align="left">
    <input type="text" name="WP_serverparam" value="{proxy_serverparam}" size="32" maxlength="64" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_server_value}:</td>
   <td align="left">
    <input type="text" name="WP_servervalue" value="{proxy_servervalue}" size="32" maxlength="64" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_prepend}:</td>
   <td align="left">
    <input type="text" name="WP_prependpath" value="{prox_prepend_path}" size="32" maxlength="64" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_proxyhost}:</td>
   <td align="left">
    <input type="text" name="WP_proxyhost" value="{prox_proxyhost}" size="32" maxlength="64" />
   </td>
  </tr>
 </table>
</fieldset><br />
<br />
<input type="submit" value="{msg_save}" /><br />

<br />
<fieldset>
 <legend>{leg_accpass}</legend>
 {about_accpass}<br />
 <br />
 <a href="{switchaccpasslink}">{msg_switchnow}</a>
</fieldset>
</div>
</form>