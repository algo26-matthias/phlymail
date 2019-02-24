<script language="javascript">
// <![CDATA[
function disable(anaus)
{
    var moep = document.forms['menu'].elements['save_opt'];
    var betroffen = (typeof(moep.length) != 'undefined') ? moep.length : 0;
    if (betroffen) {
        for (var i = 0; i < betroffen; i++) {
            moep[i].disabled = anaus;
        }
    }
    document.forms['menu'].elements['save_att'].disabled = anaus;
    this.schalter.disabled = anaus;
    return true;
}
// ]]>
</script>
<form action="{linkbase}&action={action}&mail={mail}&pure=true" target="_blank" method="post" name="menu" onmousemove="init();">
{msg_choose}:<br />
<input type="radio" name="save_as" value="raw" onclick="disable(1);" checked />&nbsp;{msg_complete}<br />
<input type="radio" name="save_as" value="body" onclick="disable(1);" />&nbsp;{msg_body}<br />
<br />
<table class="l" cellpadding="2" cellspacing="0">
<tr>
 <td class="t l" {body_tag}>
  <input type="radio" name="save_as" value="txt" onclick="disable(0);" />&nbsp;Text<br />
  <input type="radio" name="save_as" value="html" onclick="disable(0);" />&nbsp;HTML<br />
  <input type="radio" name="save_as" value="xml" onclick="disable(0);" />&nbsp;XML<br />
 </td>
 <td class="t l" id="schalter" {body_tag}>
  <input type="radio" name="save_opt" value="shead" checked="checked" />&nbsp;{msg_shead}<br />
  <input type="radio" name="save_opt" value="complete" />&nbsp;{msg_ahead}<br />
  <br />
  <input type="checkbox" name="save_att" value="yes" checked="checked" />&nbsp;{msg_alist}<br />
 </td>
</tr>
</table><br clear="all" />
<br />
<input type="submit" value="{msg_save}" />&nbsp;<a href="{linkbase}&action={action}&mail={mail}">{msg_cancel}</a><br />
<script language="javascript">
<!--
 disable(1);
-->
</script>
</form>