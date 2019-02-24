<script type="text/javascript">
//<![CDATA[
selected_addresses = [];
function select_one(id, field)
{
    window.parent.add_contact($('#' + id).text(), field);
    window.parent.float_close_script('selcontact');
}

function add_multi(id, field) { selected_addresses[id] = field; }
function remove_multi(id) { selected_addresses[id] = 0; }

function select_multi()
{
    for (var ID in selected_addresses) {
        if (!selected_addresses[ID]) continue;
        window.parent.add_contact($('#' + ID).text(), selected_addresses[ID]);
    }
    window.parent.float_close_script('selcontact');
}

function sendtogroup()
{
    groupmembers = [];<!-- START addgroupmember -->
    groupmembers[{id}] = 1;<!-- END addgroupmember -->

    for (var ID in groupmembers) {
        add_multi('s_' + ID, ''<!-- START isphone --> + 'to'<!-- END isphone --><!-- START ismail --> + 'bcc'<!-- END ismail -->);
    }
    select_multi();
}
//]]>
</script>
<div class="l">
    {desc1}
    <img src="{theme_path}/icons/contacts_select.gif" title="{msg_sel}" class="b" alt="" /> {desc2}<br />
    <br />
    <form id="groupselectorform" action="{gtarget}" method="post">
        {msg_onlygroup}
        <select name="gfilter" size="1" onchange="document.getElementById('groupselectorform').submit();" onkeyup="document.getElementById('groupselectorform').submit();">
            <option value="">-- {msg_all} --</option><!-- START groupsel -->
            <option value="{gid}"<!-- START sel --> selected="selected"<!-- END sel -->>{gname}</option><!-- END groupsel -->
        </select><br /><!-- START sendtogroup -->
        <button type="button" onclick="sendtogroup();">{msg_sendtogroup}</button><br /><!-- END sendtogroup -->
        <br />
    </form>
    <form action="#" name="selform" method="post" onsubmit="return false;">
        <table border="0" width="100%" cellpadding="0" cellspacing="0"><!-- START entry --><!-- START name -->
            <tr>
                <td class="sendmenubut l">&nbsp;<strong>{nickname}</strong>&nbsp;</td>
                <td class="sendmenubut r">&nbsp;<i>{group}</i>&nbsp;</td>
            </tr><!-- END name --><!-- START sel_mail -->
            <tr>
                <td class="l">&nbsp;-&nbsp;<span id="s_{key}">{email}</span>&nbsp;</td>
                <td class="l" style="white-space: nowrap;">
                    <input type="radio" name="s_{key}" value="0" checked="checked" onclick="remove_multi('s_{key}')"/> -&nbsp;&nbsp;
                    <input type="radio" name="s_{key}" value="to" onclick="add_multi('s_{key}', 'to')" />
                    {msg_to}
                    <img src="{theme_path}/icons/contacts_select.gif" title="{msg_sel}" class="b" alt="" onclick="select_one('s_{key}', 'to')" />
                    &nbsp;&nbsp;
                    <input type="radio" name="s_{key}" value="cc" onclick="add_multi('s_{key}', 'cc')" />
                    {msg_cc}
                    <img src="{theme_path}/icons/contacts_select.gif" title="{msg_sel}" class="b" alt="" onclick="select_one('s_{key}', 'cc')" />
                    &nbsp;&nbsp;
                    <input type="radio" name="s_{key}" value="bcc" onclick="add_multi('s_{key}', 'bcc')" />
                    {msg_bcc}
                    <img src="{theme_path}/icons/contacts_select.gif" title="{msg_sel}" class="b" alt="" onclick="select_one('s_{key}', 'bcc')" />
                </td>
            </tr><!-- END sel_mail --><!-- START sel_phone -->
            <tr>
                <td class="l">&nbsp;-&nbsp;<span id="s_{key}">{mobile}</span>&nbsp;</td>
                <td class="l">
                    <input type="radio" name="s_{key}" value="0" checked="checked" onclick="remove_multi('s_{key}')"/> -&nbsp;&nbsp;
                    <input type="radio" name="s_{key}" value="to" onclick="add_multi('s_{key}', 'to')" />
                    {msg_to}
                    <img src="{theme_path}/icons/contacts_select.gif" title="{msg_sel}" class="b" alt="" onclick="select_one('s_{key}', 'to')" />
                    <script type="text/javascript">/*<![CDATA[*/max_num = {key};/*]]>*/</script>
                </td>
            </tr><!-- END sel_phone -->
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr><!-- END entry --><!-- START nothing -->
            <tr>
                <td class="l">{msg_none}</td>
            </tr><!-- END nothing -->
        </table>
    </form>
    <button type="button" onclick="select_multi();">{insert}</button>
</div>