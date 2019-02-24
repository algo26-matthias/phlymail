<form action="{PHP_SELF}?{passthrough}&amp;special=register_me" method="post">
    <div data-role="fieldcontain">
            <strong>{msg_register}</strong><br>
    </div><!-- START error -->
    <div data-role="fieldcontain">
            <strong>{error}</strong>
    </div><!-- END error -->
    <div data-role="fieldcontain">
            <label for="username">{msg_popuser}</label>
            <input type="text" size="16" maxlength="32" id="username" name="username" value="{username}">
    </div>
    <div data-role="fieldcontain">
            <label for="password">{msg_syspass}</label>
            <input type="password" size="16" maxlength="32" id="password" name="password" value="{password}">
    </div>
    <div data-role="fieldcontain">
            <label for="password2">{msg_syspass2}</label>
            <input type="password" size="16" maxlength="32" id="password2" name="password2" value="{password2}">
    </div>
    <div data-role="fieldcontain">
            <label for="email">{msg_email}</label>
            <input type="text" size="24" maxlength="255" id="email" name="email" value="{email}">
    </div>
    <div data-role="fieldcontain">
            <hr>
    </div>
    <div data-role="fieldcontain">
            <label for="firstname">{msg_firstname}</label>
            <input type="text" size="24" maxlength="32" id="firstname" name="firstname" value="{firstname}">
    </div>
    <div data-role="fieldcontain">
            <label for="lastname">{msg_lastname}</label>
            <input type="text" size="24" maxlength="32" id="lastname" name="lastname" value="{lastname}">
    </div>
    <div data-role="fieldcontain">
            <label for="tel_private">{msg_tel_private}</label>
            <input type="text" size="24" maxlength="32" id="tel_private" name="tel_private" value="{tel_private}">
    </div>
    <div data-role="fieldcontain">
            <label for="tel_business">{msg_tel_business}</label>
            <input type="text" size="24" maxlength="32" id="tel_business" name="tel_business" value="{tel_business}">
    </div>
    <div data-role="fieldcontain">
            <label for="cellular">{msg_cellular}</label>
            <input type="text" size="24" maxlength="32" id="cellular" name="cellular" value="{cellular}">
    </div>
    <div data-role="fieldcontain">
            <label for="fax">{msg_fax}</label>
            <input type="text" size="24" maxlength="32" id="fax" name="fax" value="{fax}">
    </div>
    <div data-role="fieldcontain">
            <label for="www">{msg_www}</label>
            <input type="text" size="24" maxlength="32" id="www" name="www" value="{www}">
    </div>
    <div data-role="fieldcontain">
            <input data-inline="true" data-theme="b" type="submit" value="{msg_register}">
            <a data-role="button" data-inline="true" href="{PHP_SELF}">{msg_cancel}</a>
    </div>
</form>