<script type="text/javascript" src="{frontend_path}/js/menus.js"></script>
<script type="text/javascript">
//<![CDATA[
AvailPerm = [];
ExpUList = false;
function confirm_delete(id)
{
    if (confirm('{msg_conf_dele}')) window.location = '{delelink}&id=' + id;
}

function edit_group(id, name)
{
    var name = prompt('{msg_newnamegroup}', name);
    if (!name) return false;
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{editlink}&id=' + id + '&name=' + encodeURIComponent(name);
}

function add_group(childof)
{
    var name = prompt('{msg_newgroupname}', '');
    if (!name) return false;
    if (name.length == 0 || name.length > 32) {
        alert('{msg_name_error}');
        return false;
    }
    window.location = '{addlink}&name=' + encodeURIComponent(name)
            + '&childof=' + encodeURIComponent(childof);
}

function popwin_open(id)
{
    document.getElementById(id).style.display = 'block';
}

function popwin_close(id)
{
    document.getElementById(id).style.display = 'none';
}

function grouppriv_get(id)
{
    AJAX('{grouppriv_geturl}&gid=' + id);
}

function userlist_get(id)
{
    AJAX('{userlist_geturl}&gid=' + id);
}

function userpriv_get(id)
{
    AJAX('{userpriv_geturl}&gid=' + id);
}

function grouppriv_got(data, id, name)
{
    var i, Prm;
    for (i in AvailPerm) {
        Prm = AvailPerm[i];
        document.getElementById(Prm).checked = (typeof data[Prm] != 'undefined' && data[Prm] == 1) ? true : false;
    }
    document.getElementById('pw_priv_title').firstChild.nodeValue = '{head_privs_group}'.replace(/\$1/, name);
    document.getElementById('pw_priv_form').action = '{grouppriv_seturl}&gid=' + id;
    popwin_open('pw_priv');
}

function userlist_got(data, id)
{
    if (ExpUList != false) {
        var Weg = document.getElementById('UL_' + ExpUList);
        Weg.parentNode.removeChild(Weg);
    }
    var div = document.createElement('div');
    div.style.padding = '2px 2px 2px 16px';
    div.id = 'UL_' + id;
    for (var i in data) {
        var div2 = document.createElement('div');
        var icn = document.createElement('img');
        icn.src = '{skin_path}/icons/user_men.gif';
        icn.className = 'inlineicon';
        div2.appendChild(icn);
        div2.appendChild(document.createTextNode(data[i]));
        div2.id = 'u_' + i;
        div2.style.clear = 'both';
        div.appendChild(div2);
    }
    document.getElementById('g_' + id).appendChild(div);
    ExpUList = id;
}

function userpriv_got(data, id, name)
{
    var i, Prm;
    for (i in AvailPerm) {
        Prm = AvailPerm[i];
        document.getElementById(Prm).checked = (typeof data[Prm] != 'undefined' && data[Prm] == 1) ? true : false;
    }
    document.getElementById('pw_priv_title').firstChild.nodeValue = '{head_privs_user}'.replace(/\$1/, name);
    document.getElementById('pw_priv_form').action = '{userpriv_seturl}&gid=' + id;
    popwin_open('pw_priv');
}

function privshortcut(hdl, mode)
{
    for (i in AvailPerm) {
        Prm = AvailPerm[i];
        if (hdl != '' && Prm.substr(0, hdl.length) != hdl) continue;
        document.getElementById(Prm).checked = (mode == 1) ? true : false;
    }
}

function set_perm()
{
    var i, Prm, inp;
    var reqtxt = '';
    for (i in AvailPerm) {
        Prm = AvailPerm[i];
        inp = document.getElementById(Prm);
        reqtxt += '&' + inp.name + '=' + (inp.checked ? '1' : '0');
    }
    AJAX(document.getElementById('pw_priv_form').action + reqtxt, true);
    return false;
}

// Since this is a quite central place for handling requests, multiple parallel requests must be traced
Rq = new Array();
function AJAX(url, post)
{
    if (window.XMLHttpRequest) {
        var req = new XMLHttpRequest();
        text = null;
    } else if (window.ActiveXObject) {
        var req = new ActiveXObject("Microsoft.XMLHTTP");
        text = false;
    }
    if (req) {
        pleasewait_on();
        req.onreadystatechange = AJAX_ORS;
        req.open((post ? 'POST' : 'GET'), url, true);
        req.send(text);
        Rq.push(req);
    }
}

function AJAX_ORS()
{
    if (Rq.length == 0) {
        pleasewait_off();
        return;
    }
    for (i = 0; i < Rq.length; ++i) {
        if (Rq[i].readyState == 4) {
            var myRq = Rq[i];
            Rq.splice(i, 1);
            if (typeof(myRq.status) != 'undefined' && (myRq.status == 304 || myRq.status == 200)) {
                AJAX_process(myRq.responseText);
            }
            break;
        }
    }
    if (Rq.length == 0) window.setTimeout('pleasewait_off();', 2000);
}

function AJAX_process(response)
{
    if (!response) return;
    eval('next = ' + response);
    if (next['got_gperm']) grouppriv_got(next['got_gperm'], next['gid'], next['gname']);
    if (next['set_gperm']) popwin_close('pw_priv');
    if (next['got_uperm']) grouppriv_got(next['got_uperm'], next['uid'], next['uname']);
    if (next['set_uperm']) popwin_close('pw_priv');
    if (next['got_gulist']) userlist_got(next['got_gulist'], next['gid']);
}
//]]>
</script>
<div><!-- START errors -->
 <div class="errorbox"><strong>{error}</strong></div><br /><!-- END errors -->
 {about_groups}<br />
 <br />
 <table border="0" cellpadding="0" cellspacing="0" width="400">
 <tbody>
  <tr>
   <td class="contthleft"><strong>ID</strong></td>
   <td class="contthmiddle"><strong>{msg_gname}</strong></td>
   <td class="contthright">&nbsp;</td>
  </tr><!-- START groupline -->
  <tr>
   <td class="conttd r">{id}&nbsp;</td>
   <td class="conttd">
    <div id="g_{id}" style="padding-left:{levelspacer}px;text-align:left;">
     <img src="{skin_path}/icons/group_men.gif" class="inlineicon" alt="" /> {group} {num}
    </div>
   </td>
   <td class="conttd r">
    <a href="javascript:void(0);" onclick="userlist_get({id})"><img border="0" src="{skin_path}/icons/user_men.gif" alt="" title="{msg_showusers}" /></a>
    <a href="javascript:void(0);" onclick="edit_group({id},'{group}')"><img border="0" src="{skin_path}/icons/edit.gif" alt="" title="{msg_edit}" /></a>
    <a href="javascript:void(0);" onclick="confirm_delete({id})"><img src="{skin_path}/icons/delete.gif" border="0" alt="" title="{msg_dele}" /></a>
    <a href="javascript:void(0);" onclick="add_group({id})"><img src="{skin_path}/icons/groupadd_men.gif" border="0" alt="" title="{msg_add}" /></a>
    <a href="javascript:void(0);" onclick="grouppriv_get({id})"><img src="{skin_path}/icons/privileges.gif" border="0" alt="" title="{msg_privileges}" /></a>
    &nbsp;
   </td>
  </tr><!-- END groupline --><!-- START none -->
  <tr>
   <td colspan="3" class="conttd" style="text-align:center;">{nogroups}</td>
  </tr><!-- END none -->
 </tbody>
 </table>
</div>
<br />
<div align="left">
 <a href="javascript:void(0);" onclick="add_group(0)"><img src="{skin_path}/icons/groupadd_men.gif" border="0" alt="" title="{msg_add}" /></a>&nbsp;
 <a href="javascript:void(0);" onclick="add_group(0)">{msg_add}</a>
</div>

<div id="pw_priv" class="popwin_container" style="display:none">
 <div class="popwin_title">
  <div class="popwin_close" onclick="popwin_close('pw_priv');">&nbsp;</div>
  <span id="pw_priv_title">{poptitle_privileges}</span>
 </div>
 <div class="popwin" id="pw_priv_content">
 <form id="pw_priv_form" action="#" method="post" accept-charset="utf-8" onsubmit="return set_perm();">
 <div style="height:500px;overflow:auto;"> <!-- START priv_handler -->
  <fieldset>
   <legend>{handlername}</legend><!-- START priv_priv -->
   <script type="text/javascript">/*<![CDATA[*/AvailPerm.push('{handler}_{priv}');/*]]>*/</script>
   <input type="checkbox" name="p[{handler}_{priv}]" value="1" id="{handler}_{priv}" />
   <label for="{handler}_{priv}">{privname}</label><br /><!-- END priv_priv -->
   <br />
   {msg_simple}: <button type="button" onclick="privshortcut('{handler}',1);">{msg_all}</button> <button type="button" onclick="privshortcut('{handler}',0);">{msg_none}</button><br />
  </fieldset><br /><!-- END priv_handler -->
  </div>
  <br />
  {msg_simple}: <button type="button" onclick="privshortcut('',1);">{msg_all}</button> <button type="button" onclick="privshortcut('',0);">{msg_none}</button><br />
  <br />
  <input type="submit" value="{msg_save}" />
  <button type="button" onclick="popwin_close('pw_priv');">{msg_cancel}</button>
 </form>
 </div>
</div>