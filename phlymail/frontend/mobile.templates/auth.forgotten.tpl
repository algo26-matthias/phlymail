<!-- START query -->
<form action="{PHP_SELF}" method="post">
    <div class="c t"><strong>%h%AuthLostPW%</strong></div>
    <br />
    {msg_enter}<br /><!-- START error -->
    <br />{error}<br /><!-- END error -->

    <div data-role="fieldcontain">
        <label for="user">{msg_popuser}:</label>
        <input id="user" type="text" id="user" name="user" value="{user}" size="20" />
    </div>
    <input type="submit" value="{msg_send}" />
</form><!-- END query --><!-- START okay -->
<div>
    {msg_okay}
</div>
<a data-role="button" href="{login}">{msg_back}</a><br />
<!-- END okay --><!-- START enternew -->
<form action="{PHP_SELF}" method="post">
    <div class="c t"><strong>%h%AuthLostPW%</strong></div>
    <br />
    {msg_enter}<br /><!-- START error -->
    <br />{error}<br /><!-- END error -->

    <div data-role="fieldcontain">
        <label for="newpw1">{msg_pw1}:</label>
        <input id="newpw1" type="password" id="newpw1" name="newpw1" value="" size="20" />
    </div>
    <div data-role="fieldcontain">
        <label for="newpw2">{msg_pw2}:</label>
        <input id="newpw2" type="password" id="newpw2" name="newpw2" value="" size="20" />
    </div>
    <input type="submit" value="{msg_send}" />
</form><!-- END enternew -->