<script type="text/javascript">
/*<![CDATA[*/
function confirm_delete(id)
{
    if (confirm('{msg_conf_dele}')) window.location = '{delelink}&reload_folderlist=1&id=' + id;
}

function edit_group(id, name)
{
    var name = prompt('{msg_newnamegroup}', name);
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{editlink}&reload_folderlist=1&id=' + id + '&name=' + encodeURIComponent(name);
}

function add_group()
{
    var name = prompt('{msg_newgroupname}', '');
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{addlink}&reload_folderlist=1&name=' + encodeURIComponent(name);
}

window.onload = function (e) {
    if (window.location.search.match(/reload_folderlist\=1/)) {
        opener.frames.PHM_tl.flist_refresh('{handler}');
    }
}
/*]]>*/
</script>
<div class="c" style="padding:8px;"><!-- START errors -->
    <div style="border:1px dashed black;"><strong>{error}</strong></div>
    <br /><!-- END errors -->
    <strong>{about_groups}</strong><br />
    <br />
    <table border="0" cellpadding="2" cellspacing="0"><!-- START groupline -->
        <tr>
            <td class="l">{group} {num}</td>
            <td class="l">
                <a href="javascript:void(0);" onclick="edit_group({id}, '{group}')"<!-- START noedit --> style="display:none;"<!-- END noedit -->><img src="{theme_path}/icons/edit_menu.gif" alt="" title="{msg_edit}" /></a>&nbsp;
                <a href="javascript:void(0);" onclick="confirm_delete({id})"<!-- START nodele --> style="display:none;"<!-- END nodele -->><img src="{theme_path}/icons/dustbin_menu.gif" alt="" title="{msg_dele}" /></a>
            </td>
        </tr><!-- END groupline --><!-- START none -->
        <tr>
            <td colspan="2" class="l">{nogroups}</td>
        </tr><!-- END none -->
    </table><br />
    <br />
    <button type="button" onclick="add_group()"<!-- START nomoreadd --> style="display:none;"<!-- END nomoreadd -->>
        <img src="{theme_path}/icons/groupadd_men.gif" alt="" title="{msg_add}" class="b" /> {msg_add}
    </button>
</div>