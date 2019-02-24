<script type="text/javascript">
//<![CDATA[
ExpUList = false;
function dele_group(id)
{
    if (confirm('{msg_conf_dele}')) window.location = '{delegrouplink}&id=' + id;
}

function edit_group(id, name)
{
    var name = prompt('{msg_newnamegroup}', name);
    if (!name) return false;
    if (name.length == 0 || name.length > 64) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{editgrouplink}&id=' + id + '&name=' + encodeURIComponent(name);
}

function add_group(childof)
{
    var name = prompt('{msg_newgroupname}', '');
    if (!name) return false;
    if (name.length == 0 || name.length > 64) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{addgrouplink}&name=' + encodeURIComponent(name)
            + '&childof=' + encodeURIComponent(childof);
}

function add_item(parent)
{
    window.open('{edititemlink}&childof=' + parent, 'bookmark_n_' + parent, 'width=410,height=250,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
}

function edit_item()
{
    var ID = this.id.replace(/^edit_bm_/, '');
    window.open('{edititemlink}&id=' + ID, 'bookmark_' + ID, 'width=410,height=250,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
}

function dele_item()
{
    if (!confirm('{msg_conf_deleitem}')) return false;
    var ID = this.id.replace(/^dele_bm_/, '');
    window.location = '{deleitemlink}&id=' + ID;
}

function userlist_get(id)
{
    $.ajax({url : '{itemlist_geturl}&gid=' + id, dataType : 'json', success : userlist_got });
}

function userlist_got(next)
{
    if (ExpUList != false) {
        $('#IL_' + ExpUList + ' a.ico_dele_bm').unbind('click');
        $('#IL_' + ExpUList + ' a.ico_edit_bm').unbind('click');
        $('#IL_' + ExpUList).remove();
    }

    if (next['items'].length == 0) return;

    var html = '<div style="padding:2px 2px 2px 16px;" id="IL_' + next['gid'] + '">'
             + '<table border="0" cellpadding="1" cellspacing="0" width="100%"><tbody id="tbg_' + next['gid'] + '">'
             + '</tbody></table></div>';
    $('#g_' + next['gid']).append(html);
    ExpUList = next['gid'];

    jQuery.each(next['items'], function (i, val) {
        val.title = val.name;
        if (val.name.length > 75) {
            val.name = val.name.substr(0, 72) + '...';
        }
        var icon = 'bookmark_men.gif';
        if (val.favourite == 1) icon = 'bookmark_favourite_men.gif';
        var line = '<tr><td><img src="{skin_path}/icons/' + icon + '" class="inlineicon" alt="" /></td>';
        line += '<td title="' + val.title + '">' + val.name + '</td><td>';
        line += '<a href="#" class="ico_edit_bm" id="edit_bm_' + val.id + '"><img border="0" src="{skin_path}/icons/edit.gif" alt="" title="{msg_edit}" /></a>';
        line += '<a href="#" class="ico_dele_bm" id="dele_bm_' + val.id + '"><img src="{skin_path}/icons/delete.gif" border="0" alt="" title="{msg_dele}" /></a>';
        line += '</td></tr>';
        $('#tbg_' + next['gid']).append(line);
    });
    $('#tbg_' + next['gid'] + ' a').href = 'javascript:void(0)';
    $('#tbg_' + next['gid'] + ' a.ico_dele_bm').click(dele_item);
    $('#tbg_' + next['gid'] + ' a.ico_edit_bm').click(edit_item);
}

//]]>
</script>
<div><!-- START errors -->
    <div class="errorbox"><strong>{error}</strong></div>
    <br /><!-- END errors -->
    {about_groups}<br />
    <br />
    <table border="0" cellpadding="0" cellspacing="0" width="99%">
        <tbody>
            <tr>
                <td class="contthmiddle"><strong>{msg_gname}</strong></td>
                <td class="contthright">&nbsp;</td>
            </tr><!-- START groupline -->
            <tr>
                <td class="conttd">
                    <div id="g_{id}" style="padding-left:{levelspacer}px;text-align:left;">
                        <img src="{skin_path}/icons/folder_men.gif" class="inlineicon" alt="" /> {group}
                    </div>
                </td>
                <td class="conttd r">
                    <a href="javascript:void(0);" onclick="userlist_get({id})"><img border="0" src="{skin_path}/icons/copy_down.gif" alt="" title="{msg_showusers}" /></a>
                    <a href="javascript:void(0);" onclick="edit_group({id},'{group}')"><img border="0" src="{skin_path}/icons/edit.gif" alt="" title="{msg_edit}" /></a>
                    <a href="javascript:void(0);" onclick="dele_group({id})"><img src="{skin_path}/icons/delete.gif" border="0" alt="" title="{msg_dele}" /></a>
                    <a href="javascript:void(0);" onclick="add_group({id})"><img src="{skin_path}/icons/folderadd_men.gif" border="0" alt="" title="{msg_add}" /></a>
                    <a href="javascript:void(0);" onclick="add_item({id})"><img src="{skin_path}/icons/bookmarkadd_men.gif" border="0" alt="" title="{msg_additem}" /></a>
                    &nbsp;
                </td>
            </tr><!-- END groupline --><!-- START none -->
            <tr>
                <td colspan="2" class="conttd" style="text-align:center;">{nogroups}</td>
            </tr><!-- END none -->
        </tbody>
    </table>
</div>
<br />
<div align="left">
    <a href="javascript:void(0);" onclick="add_group(0)"><img src="{skin_path}/icons/folderadd_men.gif" border="0" alt="" title="{msg_add}" /></a>&nbsp;
    <a href="javascript:void(0);" onclick="add_group(0)">{msg_add}</a>
</div>