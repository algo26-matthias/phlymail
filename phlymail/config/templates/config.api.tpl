<!-- START error --><div class="errorbox">{error}</div><!-- END error -->
{headtext}<br />
<br />
<form action="{target_link}" method="post">
    <fieldset>
        <legend>{leg_create}</legend>
        {about_create}<br />
        <br />
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="newuser_runscript" value="1" id="nu_runscr"<!-- START nu_rs --> checked="checked"<!-- END nu_rs --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="nu_runscr"><strong>{msg_runscript}</strong></label><br />
                    {msg_scriptpath}:<br />
                    <input type="text" name="newuser_scriptpath" value="{newuser_scriptpath}" size="48" maxlength="255" />
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="newuser_writefile" value="1" id="nu_wrfile"<!-- START nu_wf --> checked="checked"<!-- END nu_wf --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="nu_wrfile"><strong>{msg_writefile}</strong></label><br />
                    {msg_fileformat}:<br />
                    <input type="text" name="newuser_fileformat" value="{newuser_fileformat}" size="48" maxlength="255" /><br />
                    {msg_filepath}:<br />
                    <input type="text" name="newuser_filepath" value="{newuser_filepath}" size="48" maxlength="255" />
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="newuser_createprofile" value="1" id="nu_crprof"<!-- START nu_cp --> checked="checked"<!-- END nu_cp --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="nu_crprof"><strong>{msg_createprofile}</strong></label><br />
                    {msg_aboutprofile}<br />
                    <br />
                    <table border="0" cellpadding="2" cellspacing="0">
                        <tr>
                            <td align="left" valign="top">{msg_profname}:</td>
                            <td align="left" valign="top"><input type="text" name="accname" value="{accname}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_email}:</td>
                            <td align="left" valign="top"><input type="text" name="address" value="{address}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_realname}:</td>
                            <td align="left" valign="top"><input type="text" name="real_name" value="{real_name}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_checkevery}</td>
                            <td align="left" valign="top"><input type="text" name="checkevery" value="{checkevery}" size="3" maxlength="5" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top" colspan="2">
                                <input type="checkbox" name="checkspam" value="1" id="checkspam"<!-- START checkspam --> checked="checked"<!-- END checkspam --> />
                                <label for="checkspam">{msg_checkspam}</label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="left" valign="top" colspan="2">
                                <input type="radio" name="acctype" value="pop3" onchange="showhidefields();" id="acctype_pop3" <!-- START acctype_pop3 -->checked="checked" <!-- END acctype_pop3 -->/>
                                <label for="acctype_pop3"><strong>POP3</strong></label>
                                <input type="radio" name="acctype" value="imap" onchange="showhidefields();" id="acctype_imap" <!-- START acctype_imap -->checked="checked" <!-- END acctype_imap -->/>
                                <label for="acctype_imap"><strong>IMAP</strong></label>
                            </td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_popserver}:</td>
                            <td align="left" valign="top"><input type="text" name="popserver" value="{popserver}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_popport}:</td>
                            <td align="left" valign="top">
                                <input type="text" name="popport" value="{popport}" size="3" maxlength="5" />
                                (POP3: 110, IMAP: 143, POP3-SSL: 995, IMAP-SSL: 993)
                            </td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_popuser}:</td>
                            <td align="left" valign="top"><input type="text" name="popuser" value="{popuser}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_poppass}:</td>
                            <td align="left" valign="top"><input type="text" name="poppass" value="{poppass}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_popsec}:</td>
                            <td align="left" valign="top">
                                <select size="1" name="popsec"><!-- START popsec -->
                                    <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{val}</option><!-- END popsec -->
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="left" valign="top" colspan="2">
                                <div id="leaveserver_pop3" style="display:none;">
                                    <input type="checkbox" name="leaveonserver" value="1" id="leaveonserver"<!-- START leaveonserver --> checked="checked"<!-- END leaveonserver --> />
                                    <label for="leaveonserver">{msg_leaveonserver}</label>
                                </div>
                                <div id="onlysubscribed_imap" style="display:none;">
                                    <input type="checkbox" name="onlysubscribed" value="1" id="onlysubscribed"<!-- START onlysubscribed --> checked="checked"<!-- END onlysubscribed --> />
                                    <label for="onlysubscribed">{msg_onlysubscribed}</label>
                                </div>
                                <div id="localkillserver_pop3" style="display:none;">
                                    <input type="checkbox" name="localkillserver" value="1" id="localkillserver"<!-- START localkillserver --> checked="checked"<!-- END localkillserver --> />
                                    <label for="localkillserver">{msg_localkillserver}</label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="left" valign="top" colspan="2"><strong>SMTP</strong></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_smtphost}:</td>
                            <td align="left" valign="top"><input type="text" name="smtpserver" value="{smtpserver}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_smtpport}:</td>
                            <td align="left" valign="top">
                                <input type="text" name="smtpport" value="{smtpport}" size="3" maxlength="5" />
                                (SMTP: 587 / 25, SMTP-SSL: 465)
                            </td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_smtpuser}:</td>
                            <td align="left" valign="top"><input type="text" name="smtpuser" value="{smtpuser}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_smtppass}:</td>
                            <td align="left" valign="top"><input type="text" name="smtppass" value="{smtppass}" size="32" maxlength="255" /></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">{msg_smtpsec}:</td>
                            <td align="left" valign="top">
                                <select size="1" name="smtpsec"><!-- START smtpsec -->
                                    <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{val}</option><!-- END smtpsec -->
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </fieldset><br />
    <br />
    <fieldset>
        <legend>{leg_edit}</legend>
        {about_edit}<br />
        <br />
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="edituser_runscript" value="1" id="eu_runscr"<!-- START eu_rs --> checked="checked"<!-- END eu_rs --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="eu_runscr"><strong>{msg_runscript}</strong></label><br />
                    {msg_scriptpath}:<br />
                    <input type="text" name="edituser_scriptpath" value="{edituser_scriptpath}" size="48" maxlength="255" />
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="edituser_writefile" value="1" id="eu_wrfile"<!-- START eu_wf --> checked="checked"<!-- END eu_wf --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="eu_wrfile"><strong>{msg_writefile}</strong></label><br />
                    {msg_fileformat}:<br />
                    <input type="text" name="edituser_fileformat" value="{edituser_fileformat}" size="48" maxlength="255" /><br />
                    {msg_filepath}:<br />
                    <input type="text" name="edituser_filepath" value="{edituser_filepath}" size="48" maxlength="255" />
                </td>
            </tr>
        </table>
    </fieldset><br />
    <br />
    <fieldset>
        <legend>{leg_delete}</legend>
        {about_delete}<br />
        <br />
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="deleteuser_runscript" value="1" id="du_runscr"<!-- START du_rs --> checked="checked"<!-- END du_rs --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="du_runscr"><strong>{msg_runscript}</strong></label><br />
                    {msg_scriptpath}:<br />
                    <input type="text" name="deleteuser_scriptpath" value="{deleteuser_scriptpath}" size="48" maxlength="255" />
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td class="bodycolour" align="left" valign="top">
                    <input type="checkbox" name="deleteuser_writefile" value="1" id="du_wrfile"<!-- START du_wf --> checked="checked"<!-- END du_wf --> />
                </td>
                <td class="bodycolour" align="left" valign="top">
                    <label for="du_wrfile"><strong>{msg_writefile}</strong></label><br />
                    {msg_fileformat}:<br />
                    <input type="text" name="deleteuser_fileformat" value="{deleteuser_fileformat}" size="48" maxlength="255" /><br />
                    {msg_filepath}:<br />
                    <input type="text" name="deleteuser_filepath" value="{deleteuser_filepath}" size="48" maxlength="255" />
                </td>
            </tr>
        </table>
    </fieldset><br />
    <br />
    <input type="submit" value="{msg_save}" />
</form>
<script type="text/javascript">
//<![CDATA[
currProto = null;
showHideFields =
    {'pop3' : ['localkillserver_pop3', 'leaveserver_pop3']
    ,'imap' : ['onlysubscribed_imap']
    };
function showhidefields()
{
    if (currProto !== null) {
        for (var i = 0; i < showHideFields[currProto].length; ++i) {
            document.getElementById(showHideFields[currProto][i]).style.display = 'none';
        }
    }
    currProto = document.getElementById('acctype_pop3').checked ? 'pop3' : 'imap';
    for (var i = 0; i < showHideFields[currProto].length; ++i) {
        document.getElementById(showHideFields[currProto][i]).style.display = 'block';
    }
}
if (window.addEventListener) {
    window.addEventListener('load', showhidefields, true);
} else if (window.attachEvent) {
    window.attachEvent('onload', showhidefields, true);
}
//]]>
</script>