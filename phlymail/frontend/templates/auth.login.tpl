<script type="text/javascript" src="{frontend_path}/js/md5.js?{current_build}"></script>
<script type="text/javascript" src="{frontend_path}/js/core.cookies.js?{current_build}"></script>
<script type="text/javascript">
//<![CDATA[
var OTP = false;
function AJAX_process(next)
{
    $('#prgr').css('visibility', 'hidden');
    if (next['error']) {
        alert(next['error']);
        return;
    }
    if (next['otp']) {
        secure_transmission(next['otp'], next['method']);
    }
}

function get_otp()
{
    $('#prgr').css('visibility', 'visible');
    $.ajax({url:'{PHP_SELF}&give_otp=1&un=' + encodeURIComponent($('#user').val()), dataType: 'json', success: AJAX_process, error: function() {$('#authform').submit();} });
    return false;
}

function secure_transmission(otp, method)
{
    OTP = otp;
    var sec;
    if (method == 'digest') {
        sec = MD5($('#user').val() + ':' + otp + ':' + $('#pw').val());
        $('#pw').val('');
        $('#authform').append('<input type="hidden" name="digest" value="' + encodeURIComponent(sec) + '" />');
    } else if (method == 'md5') {
        sec = MD5(MD5($('#pw').val()) + otp);
        $('#pw').val('');
        $('#authform').append('<input type="hidden" name="secure" value="' + encodeURIComponent(sec) + '" />');
    }
    $('#authform')
            .append('<input type="hidden" name="orig_url" value="' + encodeURIComponent(self.location.href) + '" />')
            .submit();
}

$(document).ready(function () {
    $('#cookie_warning').css('display', (checkCookiesEnabled() == false) ? 'block' : 'none');
    $('#authform').submit(function(evt) {
        if (OTP == false) {
            evt.stopImmediatePropagation();
            evt.preventDefault();
            evt.stopPropagation();
            get_otp();
        }
    });
    $('#user').focus();
});
//]]>
</script>
<form action="{PHP_SELF}" name="authform" id="authform" method="post">
    <table border="0" cellpadding="2" cellspacing="0" style="margin:auto;">
        <tr>
            <td>&nbsp;</td>
            <td colspan="2" class="l"><strong>{msg_authenticate}</strong><br />
            <!-- START error -->{error}<br /><!-- END error -->
            </td>
        </tr>
        <tr>
            <td class="r"><strong>{msg_popuser}:</strong></td>
            <td class="l" colspan="2"><input id="user" type="text" name="user" value="{user}" size="20" /></td>
        </tr>
        <tr>
            <td class="r"><strong>{msg_poppass}:</strong></td>
            <td class="l" colspan="2"><input id="pw" type="password" name="pass" size="20" /></td>
        </tr>
        <tr>
            <td class="t l">
                <div id="prgr" class="prgr_outer" style="visibility:hidden">
                    <div class="prgr_inner_busy"></div>
                </div>
            </td>
            <td class="t l">
                <input type="submit" value="{msg_login}" style="margin-right: 8px;" />
            </td>
            <td class="l">
                <a href="{PHP_SELF}&amp;special=lost_pw">{msg_lost_pw}</a><!-- START register --><br />
                <a href="{PHP_SELF}&amp;special=register_me">{msg_reg_now}</a><!-- END register -->
            </td>
        </tr>
    </table>
</form>