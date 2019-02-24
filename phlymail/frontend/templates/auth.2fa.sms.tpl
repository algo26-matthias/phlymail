<script type="text/javascript">
//<![CDATA[
$(document).ready(function () {
    $('#otp').focus();
});
//]]>
</script>
<form action="{PHP_SELF}" method="post">
    <div style="width:95%;margin:auto;" class="l t">
        <p>
            <strong>%h%2FaModeSmsEnterCode%</strong>
        </p><!-- START error -->
        <p>{error}</p><!-- END error -->
        <p>
            <strong>%h%2FaModeSmsCode%:</strong>
            <input id="otp" type="text" name="2fa_otp" value="" size="12" maxlength="16" autocomplete="off" />
            <input type="submit" value="%h%login%" />
        </p>
    </div>
</form>