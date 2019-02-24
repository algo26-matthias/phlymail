<!-- START error -->
<br />
<strong>{error}</strong><br />
<br /><!-- END error -->
<fieldset>
    <legend>{leg_global_settings}</legend>
    <form action="{target}" method="POST">
        <input type="checkbox" name="use_sms" value="1"<!-- START useit --> checked="checked"<!-- END useit --> id="lbl_useit" />
               <label for="lbl_useit"><strong>{msg_use}</strong></label><br />
        {about_use}<br />
        <br />
        <strong>{msg_global_prefix}</strong>:
        <input type="text" name="global_prefix" size="6" maxlength="6" value="{global_prefix}" /><br />
        {about_global_prefix}<br />
        <br />
        <strong>{msg_gw_to_use}</strong>:
        <select size="1" name="use_gw"><!-- START used_gw_line -->
            <option value="{name}"<!-- START sel --> selected="selected"<!-- END sel -->>{name} ({Server})</option><!-- END used_gw_line -->
        </select><br />
        <br />
        <a target="_blank" href="{order_deposit_uri}">%h%SMSOrderDeposit%</a><br />
        <br />
        <input type="submit" value="{msg_save}" /><br />
    </form>
</fieldset> <br />
<br />
<fieldset>
    <legend>{leg_gwdep} ({server})</legend>
    <form action="{target}" method="POST">
        <table border="0" cellpadding="2" cellsapcing="0"><!-- START gw_has_key -->
            <tr>
                <td align="left">{msg_userkey}:</td>
                <td algin="left"><input type="password" autocomplete="off" name="username" size="20" maxlength="32" value="{username}" /></td>
            </tr><!-- END gw_has_key --><!-- START gw_has_pw -->
            <tr>
                <td align="left">{msg_username}:</td>
                <td algin="left"><input type="text" name="username" size="16" maxlength="32" value="{username}" /></td>
            </tr>
            <tr>
                <td align="left">{msg_password}:</td>
                <td algin="left"><input type="password" autocomplete="off" name="password" size="16" maxlength="32" value="{password}" /></td>
            </tr><!-- END gw_has_pw -->
        </table><br />
        <input type="submit" value="{msg_save}" /><br />
        <br />
    </form><!-- START setfreely -->
    <form action="{target}" method="POST">
        <strong>{msg_deposit}:</strong>&nbsp;<input type="text" name="deposit" value="{deposit}" size="6" maxlength="8" />
        <input type="checkbox" name="is_monthly" value="1" id="lbl_monthly"<!-- START ismonth --> checked="checked"<!-- END ismonth --> />
               <label for="lbl_monthly">{msg_ismonthly}</label><br />
        <br />
        <input type="checkbox" name="allowover" value=1 id="lbl_over"<!-- START over --> checked="checked"<!-- END over --> />
               <label for="lbl_over">{msg_over}</label><br />
        <input type="submit" value="{msg_save}" /><br />
    </form><!-- END setfreely --><!-- START accountsaved -->
    <fieldset>
        <legend>{leg_gateway}</legend>
        {about_gateway}<br />
        <br /><!-- START gw_has_test -->
        {about_test}<br />
        <a href="{link_test}">{msg_test}</a><br /><!-- END gw_has_test -->
        <br />
        {about_deposit}<br />
        <br />
        <strong>{msg_deposit}:</strong>&nbsp;{deposit}<br /><!-- START gw_has_synchro -->
        <a href="{link_synchro}">{msg_synchro}</a><br /><!-- END gw_has_synchro -->
    </fieldset>
    <br />
    <br /><!-- END accountsaved -->
</fieldset><br />
<br />
<fieldset>
    <legend>{leg_userdep}</legend>
    <form action="{target}" method="POST">
        {about_userdep}<br />
        <br />
        <input type="radio" name="default_active" value="1" id="defact1"<!-- START defact1 --> checked="checked"<!-- END defact1 --> />
               <label for="defact1">{msg_allmay}</label><br />
        <input type="radio" name="default_active" value="0" id="defact0"<!-- START defact0 --> checked="checked"<!-- END defact0 --> />
               <label for="defact0">{msg_nomay}</label><br />
        <br />
        {msg_freesms}:&nbsp;<input type="text" name="freesms" value="{freesms}" size="4" maxlength="8" />
        <input type="checkbox" name="freemonthly" value="1" id="lbl_freemon"<!-- START freemon --> checked="checked"<!-- END freemon --> />
               <label for="lbl_freemon">{msg_monthly}</label><br />
        <br />
        {msg_maxmonthly}:
        <input type="text" name="maxlimit" value="{maxlimit}" size="4" maxlength="8" /><br />
        <br />
        <input type="radio" name="fax_default_active" value="1" id="faxdefact1"<!-- START faxdefact1 --> checked="checked"<!-- END faxdefact1 --> />
               <label for="faxdefact1">{msg_allmayfax}</label><br />
        <input type="radio" name="fax_default_active" value="0" id="faxdefact0"<!-- START faxdefact0 --> checked="checked"<!-- END faxdefact0 --> />
               <label for="faxdefact0">{msg_nomayfax}</label><br />
        <br />
        <br />
        <input type="submit" value="{msg_save}" /><br />
    </form>
</fieldset><br />
<br />
<br />
<fieldset>
    <legend>{leg_currstat}</legend>
    <table border="0" cellpadding="2" cellspacing="0">
        <tr>
            <td align="left">{msg_curruse}:</td>
            <td align="left">{curr_use} {msg_sms} ({msg_approx} {curr_approx}/{msg_month})</td>
        </tr>
        <tr>
            <td align="left">{msg_avgperuser}:</td>
            <td align="left">{curr_avg} {msg_sms}</td>
        </tr>
        <tr>
            <td align="left">{msg_maxuse}:</td>
            <td align="left">{curr_max} {msg_sms}<!-- START showcurrmax -->
                (<a href="{link_show}" title="{user}">{msg_showuser}</a>)<!-- END showcurrmax -->
            </td>
        </tr>
        <tr>
            <td align="left">{msg_leastuse}:</td>
            <td align="left">{curr_min} {msg_sms}<!-- START showcurrmin -->
                (<a href="{link_show}" title="{user}">{msg_showuser}</a>)<!-- END showcurrmin -->
            </td>
        </tr>
    </table>
</fieldset><br />
<br />
<fieldset>
    <legend>{leg_laststat}</legend>
    <table border="0" cellpadding="2" cellspacing="0">
        <tr>
            <td align="left">{msg_lastuse}:</td>
            <td align="left">{last_use} {msg_sms}</td>
        </tr>
        <tr>
            <td align="left">{msg_avgperuser}:</td>
            <td align="left">{last_avg} {msg_sms}</td>
        </tr>
        <tr>
            <td align="left">{msg_maxuse}:</td>
            <td align="left">{last_max} {msg_sms}<!-- START showlastmax -->
                (<a href="{link_show}" title="{user}">{msg_showuser}</a>)<!-- END showlastmax --></td>
        </tr>
        <tr>
            <td align="left">{msg_leastuse}:</td>
            <td align="left">{last_min} {msg_sms}<!-- START showlastmin -->
                (<a href="{link_show}" title="{user}">{msg_showuser}</a>)<!-- END showlastmin --></td>
        </tr>
    </table>
</fieldset>