<form action="{target_link}" method="POST"><!-- START return -->
<div>
<strong>{WP_return}</strong><br />
<br /><!-- END return -->
{head_text}<br />
<br />
<fieldset>
 <legend>{leg_junkprotect}</legend>
<input type="checkbox" id="use_this" name="use_feature" value="1"<!-- START junkprotect --> checked="checked"<!-- END junkprotect --> />
<label for="use_this"><strong>{msg_junkprotect}</strong></label><br />
{about_junkprotect}<br />
</fieldset><br />
<br />
<fieldset>
 <legend>{leg_pathSA}</legend>
<strong>{msg_pathSA}:</strong>
<input type="text" name="pathSA" value="{pathSA}" size="32" maxlength="255" /><br />
{about_pathSA}<br />
</fieldset><br />
<br />
<fieldset>
 <legend>{leg_userland}</legend>
 <table border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td align="left"><strong>{msg_markSPAM}:</strong></td>
  <td align="left"><input type="text" name="markSPAM" value="{markSPAM}" size="28" maxlength="255" /></td>
 </tr>
 <tr>
  <td align="left"><strong>{msg_unmarkSPAM}:</strong>&nbsp;&nbsp;</td>
  <td align="left"><input type="text" name="unmarkSPAM" value="{unmarkSPAM}" size="28" maxlength="255" /></td>
 </tr>
</table><br />
{about_markSPAM}<br />
</fieldset><br />
<br />
<input type="submit" value="{msg_save}" />
</div>
</form>