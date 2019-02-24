<form action="{PHP_SELF}?{passthrough}&amp;special=activate" method="post">
    <input type="hidden" name="uid" value="{uid}" />
    {msg_actnow}<br /><!-- START error -->
    <div data-theme="e">{error}</div><!-- END error -->
    <div data-role="fieldcontain">
        <label for="username">{msg_popuser}</label>
        <input type="text" size="16" maxlength="32" id="username" name="username" value="{username}" />
    </div>
    <div data-role="fieldcontain">
        <label for="password">{msg_syspass}</label>
        <input type="password" size="16" maxlength="32" id="password" name="password" value="{password}" />
    </div>
    <input type="submit" value="{msg_activate}" />
</form>