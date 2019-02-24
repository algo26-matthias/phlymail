<script type="text/javascript">
<!--
function confirm_delete(id)
{
    if (confirm('{msg_conf_dele}')) window.location = '{delelink}&id=' + id;
}

function edit_group(id, name)
{
    var name = prompt('{msg_newnamegroup}', name);
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{editlink}&id=' + id + '&name=' + encodeURIComponent(name);
}

function add_group()
{
    var name = prompt('{msg_newgroupname}', '');
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{addlink}&name=' + encodeURIComponent(name);
}
// -->
</script>
<div align="center" style="padding:8px"><!-- START errors -->
<div style="border: 1px dashed black">
<strong>{error}</strong></div><br /><!-- END errors -->
<strong>{about_groups}</strong><br />
<br />
<table border="0" cellpadding="2" cellspacing="0"><!-- START groupline -->
 <tr>
  <td align="left">{group} {num}</td>
  <td align="left">
   <a href="javascript:void(0);" onclick="edit_group({id}, '{group}')"><img border="0" src="{skin_path}/icons/edit.gif" alt="" title="{msg_edit}" /></a>&nbsp;
   <a href="javascript:void(0);" onclick="confirm_delete({id})"><img src="{skin_path}/icons/delete.gif" border="0" alt="" title="{msg_dele}" /></a>
  </td>
 </tr><!-- END groupline --><!-- START none -->
 <tr>
  <td colspan="2" align="left">{nogroups}</td>
 </tr><!-- END none -->
</table><br />
<br />
<button type="button" onclick="add_group()">
 <img src="{skin_path}/icons/groupadd_men.gif" border="0" alt="" title="{msg_add}" valign="bottom" /> {msg_add}
</button>
</div>