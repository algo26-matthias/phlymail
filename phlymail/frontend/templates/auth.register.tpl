<form action="{PHP_SELF}?{passthrough}&amp;special=register_me" method="post">
    <table border="0" cellpadding="2" cellspacing="0" style="margin:auto;">
        <tr>
            <td>&nbsp;</td>
            <td class="l"><strong>{msg_register}</strong><br /></td>
        </tr><!-- START error -->
        <tr>
            <td class="l" colspan="2"><strong>{error}</strong></td>
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
            <td class="l">{msg_syspass2}</td>
            <td class="l"><input type="password" size="16" maxlength="32" name="password2" value="{password2}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_email}</td>
            <td class="l"><input type="text" size="24" maxlength="255" name="email" value="{email}" /></td>
        </tr>
        <tr>
            <td colspan="2"><hr style="width:100%;" /></td>
        </tr>

        <tr>
            <td class="l">{msg_firstname}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="firstname" value="{firstname}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_lastname}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="lastname" value="{lastname}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_tel_private}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="tel_private" value="{tel_private}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_tel_business}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="tel_business" value="{tel_business}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_cellular}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="cellular" value="{cellular}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_fax}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="fax" value="{fax}" /></td>
        </tr>
        <tr>
            <td class="l">{msg_www}</td>
            <td class="l"><input type="text" size="24" maxlength="32" name="www" value="{www}" /></td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td class="l"><input type="submit" value="{msg_register}" /></td>
        </tr>
        <tr>
            <td colspan="2" class="r"><a href="{PHP_SELF}">{msg_cancel}</a></td>
        </tr>
    </table>
</form>