{head_text}<br />
<br />
<form action="{target_link}" method="POST">
<div align="left"><!-- START return -->
<b>{WP_return}</b><br />
<br /><!-- END return -->
<fieldset>
 <legend>{leg_misc}</legend>
  {msg_scheme}:&nbsp;<select name="WPnewcolscheme" size="1"><!-- START colschmopt -->
   <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{val}</option><!-- END colschmopt -->
  </select><br />
  {msg_language}:&nbsp;<select name="WPnewlanguage" size="1"><!-- START langopt -->
   <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{val}</option><!-- END langopt -->
  </select><br />
</fieldset>
<br />
<fieldset>
 <legend>{leg_allow_ip}</legend>
 <input type="checkbox" name="WPnewallowip" id="lbl_allowip" value="1"<!-- START allowip --> checked="checked"<!-- END allowip --> />
 <label for="lbl_allowip">&nbsp;{msg_allow_ip}</label><br />
 <br />
 {about_allow_ip}<br />
 <br />
 <textarea name="WPnewallowedips" cols="64" rows="5">{allowedips}</textarea>
</fieldset>
<br />
<input accesskey="S" type="submit" value="{msg_save}" />
</div>
</form>