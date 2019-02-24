{head_text}<br />
<form action="{target_link}" method="post">
    <strong>{WP_return}</strong><br />
    <br />
    <fieldset><legend>{leg_online}</legend>
        {msg_online}:&nbsp;<select name="WP_isonline">
            <option value="1"<!-- START online_yes --> selected="selected"<!-- END online_yes -->>{msg_onlineyes}</option>
            <option value="0"<!-- START online_no --> selected="selected"<!-- END online_no -->>{msg_onlineno}</option>
        </select><br />
        <br />
        {about_online}<br />
    </fieldset>
    <br />
    <fieldset><legend>{leg_debugging}</legend>
        {msg_debugging}:&nbsp;<select name="debugging_level"><!-- START debug_level -->
            <option value="{level}"<!-- START sel --> selected="selected"<!-- END sel -->>{msg_level}</option><!-- END debug_level -->
        </select><br />
        <br />
        {about_debugging}<br />
    </fieldset>
    <br />
    <fieldset><legend>{leg_providername}</legend>
        {msg_providername}:&nbsp;<input type="text" size=32 name="WP_newprovidername" value="{providername}" /><br />
        <br />
        {about_providername}<br />
        <br />
    </fieldset>
    <br /><!-- fieldset><legend>Google Maps</legend>
     Google Maps API Key:&nbsp;<input type="text" size=32 name="WP_gmapsapikey" value="{gmapsapikey}" /><br />
     <br />
    {about_gmapsapikey}<br />
    <br />
   </fieldset>
   <br / -->
    <fieldset>
        <legend>{msg_optsendmethod}:</strong>&nbsp;
            <select name="WP_newsendmethod" onChange="hide();" id="sendmethod">
                <option value="sendmail"<!-- START methsmsel --> selected<!-- END methsmsel -->>Sendmail</option>
                <option value="smtp"<!-- START methsmtpsel --> selected<!-- END methsmtpsel -->>SMTP</option>
            </select>
        </legend>
        <br />
        {about_sendmethod}<br />
        <br />
        <fieldset><legend>Sendmail</legend>
            <span id="hide_sendmail">{msg_fillin_sm}<br /><br /></span>
            {msg_path}:&nbsp;<input type="text" size=24 name="WP_newsendmail" value="{sendmail}" /><br />
        </fieldset>
        <br />
        <fieldset><legend>SMTP</legend>
            <span id="hide_smtp">{msg_fillin_smtp}<br /><br /></span>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td align="left">{msg_smtphost}:</td>
                    <td align="left"><input type="text" size=24 name="WP_newsmtphost" value="{smtphost}" /></td>
                </tr>
                <tr>
                    <td align="left">{msg_smtpport}:</td>
                    <td align="left"><input type="text" size=24 name="WP_newsmtpport" value="{smtpport}" /></td>
                </tr>
                <tr>
                    <td align="left">{msg_smtpuser}:</td>
                    <td align="left"><input type="text" size=24 name="WP_newsmtpuser" value="{smtpuser}" /></td>
                </tr>
                <tr>
                    <td align="left">{msg_smtppass}:</td>
                    <td align="left"><input type="password" autocomplete="off" size=24 name="WP_newsmtppass" value="{smtppass}" /></td>
                </tr>
                <tr>
                    <td align="left">{msg_smtpsec}:</td>
                    <td align="left">
                        <select size="1" name="WP_newsmtpsec"><!-- START smtpsec -->
                            <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{val}</option><!-- END smtpsec -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" id="lbl_allowselfsigned" name="WP_newallowselfsigned" value="1"<!-- START smtpallowselfsigned --> checked="checked"<!-- END smtpallowselfsigned --> />
                        <label for="lbl_allowselfsigned">%h%AllowSelfSignedCertificates%</label>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br />
    </fieldset>
    <br />
    <fieldset><legend>{leg_misc}</legend>
        <input type="checkbox" name="WP_usegzip" id="lbl_usegzip" value="1"<!-- START usegzip --> checked<!-- END usegzip --> />&nbsp;
        <label for="lbl_usegzip">{msg_usegzip}</label><br />
        <br />
        {msg_pagesize}:&nbsp;<input type="text" size="5" name="WP_newpagesize" value="{pagesize}" style="text-align:right;" /><br />
        {about_pagesize}<br />
        <br />
    </fieldset>
    <br />
    <fieldset><legend>{leg_fsig}</legend>
        <input type="checkbox" name="WP_useprovsig" id="lbl_provsig" value="true"<!-- START use_provsig --> checked<!-- END use_provsig --> />&nbsp;
        <label for="lbl_provsig">{msg_forcedsig}</label><br />
        <br />
        <textarea name="WP_provsig" rows="5" cols="56">{provsig}</textarea>
        <br />
    </fieldset>
    <br />
    <fieldset><legend>{leg_motd}</legend>
        <input type="checkbox" name="WP_newshowmotd" id="lbl_motd" value="1"<!-- START showmotd --> checked<!-- END showmotd --> />&nbsp;
        <label for="lbl_motd">{msg_showmotd}</label><br />
        <br />
        <textarea name="WP_MOTD" rows="5" cols="56">{MOTD}</textarea>
        <br />
    </fieldset>
    <br />
    <input type="submit" value="{msg_save}" />
</form>
<script type="text/javascript">
<!--
var TypeList = new Array("sendmail", "smtp");
var Durchlauf = TypeList.length;
function hide()
{
    for (var j = 0; j < Durchlauf; j++) {
        var what = "hide_" + TypeList[j];
        document.getElementById(what).style.display = "none";
    }
    var sel  = document.getElementById("sendmethod");
    var type = sel.options[sel.selectedIndex].value;
    CurrType = type;
    var what = "hide_" + type;
    document.getElementById(what).style.display = "block";
    return true;
}
hide();
// -->
</script>