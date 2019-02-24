<!-- START query -->
<form action="{PHP_SELF}" method="post">
    <table border="0" cellpadding="2" cellspacing="0" style="width:95%;margin:auto;">
        <tr>
             <td colspan="2"><div class="c t"><strong>{msg_lost_pw}</strong></div>
                <br />
                {msg_enter}<br /><!-- START error -->
                <br />{error}<br /><!-- END error -->
             </td>
        </tr>
        <tr>
             <td width="50%" class="r"><strong>{msg_popuser}:</strong></td>
             <td width="50%" class="l"><input id="user" type="text" name="user" value="{user}" size="20" /></td>
        </tr>
        <tr>
             <td>&nbsp;</td>
             <td class="l">
                <input type="submit" value="{msg_send}" />
             </td>
        </tr>
    </table>
</form><!-- END query --><!-- START okay -->
<div style="margin:8px; margin-bottom: 16px;">
    {msg_okay}
</div>
<a href="{login}">{msg_back}</a><br />
<br /><!-- END okay --><!-- START enternew -->
<form action="{PHP_SELF}" method="post">
    <table border="0" cellpadding="2" cellspacing="0" style="width:95%;margin:auto;">
        <tr>
             <td colspan="2">
                <div class="c t"><strong>{msg_lost_pw}</strong></div>
                <br />
                {msg_enter}<br /><!-- START error -->
                <br />{error}<br /><!-- END error -->
             </td>
        </tr>
        <tr>
             <td width="50%" class="r"><strong>{msg_pw1}:</strong></td>
             <td width="50%" class="l"><input id="newpw1" type="password" name="newpw1" value="" size="20" /></td>
        </tr>
        <tr>
             <td width="50%" class="r"><strong>{msg_pw2}:</strong></td>
             <td width="50%" class="l"><input id="newpw2" type="password" name="newpw2" value="" size="20" /></td>
        </tr>
        <tr>
             <td>&nbsp;</td>
             <td class="l">
                <input type="submit" value="{msg_send}" />
             </td>
        </tr>
    </table>
</form><!-- END enternew -->