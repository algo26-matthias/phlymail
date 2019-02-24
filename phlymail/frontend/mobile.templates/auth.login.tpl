<script type="text/javascript" src="{frontend_path}/js/md5.js?{current_build}"></script>
<script type="text/javascript" src="{frontend_path}/js/core.cookies.js?{current_build}"></script>
<script type="text/javascript">
/*<![CDATA[*/
$(document).ready(function () {
    $('#cookie_warning').css('display', (checkCookiesEnabled() == false) ? 'block' : 'none');
    $('#user').focus();
});
/*]]>*/
</script>
<img id="auth_logo" src="{theme_path}/images/phlymail_logo.png" alt="phlyMail">
<form action="{PHP_SELF}" method="post">
    <div>
        <strong>%h%authenticate%</strong><br><!-- START error -->
        {error}<br><!-- END error -->
    </div>
    <div data-role="fieldcontain">
        <label for="user">%h%popuser%:</label>
        <input type="text" name="user" id="user" value="">
    </div>
    <div data-role="fieldcontain">
        <label for="pass">%h%poppass%:</label>
        <input type="password" name="pass" id="pass" value="">
    </div>
    <input type="submit" data-inline="true" data-icon="check" data-theme="b" value="%h%login%">
    <a data-role="button" data-inline="true" data-icon="alert" href="{PHP_SELF}&amp;special=lost_pw">%h%AuthLostPW%</a><!-- START register -->
    <a data-role="button" data-inline="true" data-icon="plus" href="{PHP_SELF}&amp;special=register_me">%h%RegNow%</a><!-- END register -->
    <a data-role="button" data-inline="true" data-icon="goto-desktop" data-ajax="false" href="./index.php?notmobile=1">%h%GoToStandardPage%</a>
</form>