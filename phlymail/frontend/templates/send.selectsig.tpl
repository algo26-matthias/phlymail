<script type="text/javascript">
/*<![CDATA[*/
function select_signature(id)
{
    window.parent.append_sig
            (document.getElementById(id).firstChild.nodeValue
            ,document.getElementById('html_' + id).innerHTML
            );
    window.parent.float_close_script('signature');
}
/*]]>*/
</script>
<div class="l">
    {about_select}<br />
    <br />
    <table border="0" width="100%" cellpadding="0" cellspacing="0"><!-- START sigs -->
        <tr>
            <td class="sendmenubut t l" style="padding:8px;">
                <strong>{profile}</strong><br />
                <button type="button" onclick="select_signature({profid})">{msg_select}</button>
            </td>
            <td class="sendmenubut t l" style="padding:8px;">
                <div class="sendmenuborder" id="{profid}" style="white-space:pre;padding:4px;" ondblclick="select_signature({profid})">{sig}</div>
                <div id="html_{profid}" style="display:none;">{htmlsig}</div>
            </td>
        </tr><!-- END sigs --><!-- START no_sigs -->
        <tr>
            <td class="l">{msg_no_sigs}</td>
        </tr><!-- END no_sigs -->
    </table>
</div>