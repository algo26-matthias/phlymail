<script type="text/javascript">
//<![CDATA[
AvailPerm = [];

function open_profiles(fuerwen)
{
    window.open
            ('{link_edpf}' + fuerwen
            ,'prof_editor'
            ,'width=770,height=410,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no,personalbar=no'
            )
}

function popwin_open(id) { document.getElementById(id).style.display = 'block'; }
function popwin_close(id) { document.getElementById(id).style.display = 'none'; }
function userpriv_get(id) { AJAX('{userpriv_geturl}&gid=' + id); }

function userpriv_got(data, id, name)
{
    var i, Prm, Val;
    $('.permradio_i').attr('checked', 'checked');

    $.each(data, function (Prm, Val) {
        if (Val == 0 || Val == 1) {
            $('#' + Prm + '_' + Val).attr('checked', 'checked');
        }
    });
    $('#pw_priv_title').text('{head_privs_user}'.replace(/\$1/, name));
    $('#pw_priv_form').attr('action', '{userpriv_seturl}');
    popwin_open('pw_priv');
}

function privshortcut(hdl, mode)
{
    for (i in AvailPerm) {
        Prm = AvailPerm[i];
        if (hdl != '' && Prm.substr(0, hdl.length) != hdl) continue;
        $('#' + Prm + '_' + mode).attr('checked', 'checked');
    }
}

function set_perm()
{
    var Form = $('#pw_priv_form');
    AJAX(Form.attr('action') + '&' + Form.serialize(), true);
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
    if (next['got_uperm']) userpriv_got(next['got_uperm'], next['uid'], next['uname']);
    if (next['set_uperm']) popwin_close('pw_priv');
}

$(document).ready(function () {
    $('#logincheckupdatebetas').bind('mouseup keyup', function () {
        if ($(this).is(':checked')) {
            $('#logincheckupdates').attr('checked', 'checked');
        }
    });
})

//]]>
</script>
<a href="{link_um}">{where_um}</a>&nbsp;/&nbsp;{where_user}
<br />
<br />
<form action="{target_link}" method="post">
    <div>{head_text}<br /><!-- START error -->
        <div class="errorbox">{error}</div><!-- END error --><!-- START warn_max_users -->
        <div class="errorbox">{msg_warn_max_users}</div><!-- END warn_max_users -->
        <br />
        <fieldset>
            <legend>{leg_basic}</legend>
                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="l">{msg_CustomerNumber}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="64" name="PHM[customer_number]" value="{customer_number}" />
                        </td><!-- START has_groups -->
                        <td rowspan="6">{msg_groups}</td>
                        <td rowspan="6">
                            <select size="11" multiple="multiple" name="groups[]"><!-- START groupline -->
                                <option value="{gid}"<!-- START sel --> selected="selected"<!-- END sel -->>{gname}</option><!-- END groupline -->
                            </select>
                        </td><!-- END has_groups --><!-- START no_groups -->
                        <td colspan="2" rowspan="6">&nbsp;</td><!-- END no_groups -->
                    </tr>
                    <tr>
                        <td class="l">{msg_sysuser}</td>
                        <td class="l"><!-- START adduser -->
                            <input type="text" size="24" maxlength="64" name="PHM[username]" value="{name}" /><!-- END adduser --><!-- START edituser -->
                            <!-- input type="text" size="24" maxlength="64" name="PHM[username]" value=" -->{name}<!-- " readonly="readonly" / -->
                            <input type="hidden" name="uid" value="{uid}" /><!-- END edituser -->
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_syspass}</td>
                        <td class="l">
                            <input type="password" autocomplete="off" size="24" maxlength="32" name="PHM[password]" value="{password}" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_syspass2}</td>
                        <td class="l">
                            <input type="password" autocomplete="off" size="24" maxlength="32" name="PHM[password2]" value="{password2}" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_firstname}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[firstname]" value="{firstname}" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_lastname}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[lastname]" value="{lastname}" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_tel_private}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[tel_private]" value="{tel_private}" />
                        </td>
                        <td class="l">{msg_opttheme}:</td>
                        <td class="l">
                            <select name="theme"><!-- START themeline -->
                                <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{themename}</option><!-- END themeline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_tel_business}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[tel_business]" value="{tel_business}" />
                        </td>
                        <td class="l">{msg_optlang}:</td>
                        <td class="l">
                            <select name="language"><!-- START langline -->
                                <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{langname}</option><!-- END langline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_cellular}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[cellular]" value="{cellular}" />
                        </td>
                        <td class="l">{msg_visibility}</td>
                        <td class="l">
                            <select size="1" name="PHM[visibility]">
                                <option value="private"{sel_visibility_private}>{msg_private}</option>
                                <option value="public"{sel_visibility_public}>{msg_public}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_fax}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[fax]" value="{fax}" />
                        </td>
                        <td class="l">{msg_active}</td>
                        <td class="l">
                            <select name="PHM[active]" size="1">
                                <option value="0"<!-- START selno --> selected="selected"<!-- END selno -->>{msg_no}</option>
                                <option value="1"<!-- START selyes --> selected="selected"<!-- END selyes -->>{msg_yes}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_www}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="32" name="PHM[www]" value="{www}" />
                        </td>
                        <td class="l" colspan="2">
                            <input type="checkbox" name="showlinkconfig"<!-- START showlinkconfig --> checked="checked"<!-- END showlinkconfig --> value="1" id="showlinkconfig" />
                            <label for="showlinkconfig">{msg_showlinkconfig}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_email}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="255" name="PHM[email]" value="{email}" />
                        </td>
                        <td class="l" colspan="2">
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_externalemail}</td>
                        <td class="l">
                            <input type="text" size="24" maxlength="255" name="PHM[externalemail]" value="{externalemail}" />
                        </td>
                        <td class="l" colspan="2">
                            &nbsp;
                        </td>
                    </tr>
                </table>
            </fieldset>
            <br />
            <fieldset>
                <legend>{leg_motd}</legend>
                <div class="permradiobar">
                    <input type="radio" name="showmotd" class="permradio_y" value="1" id="showmotd_1"<!-- START showmotd_1 --> checked="checked"<!-- END showmotd_1 --> />
                    <label  for="showmotd_1">&radic;</label>
                    <input type="radio" name="showmotd" class="permradio_n" value="0" id="showmotd_0"<!-- START showmotd_0 --> checked="checked"<!-- END showmotd_0 --> />
                    <label  for="showmotd_0">-</label>
                    <input type="radio" name="showmotd" class="permradio_i" value="2" id="showmotd_2"<!-- START showmotd_2 --> checked="checked"<!-- END showmotd_2 --> />
                    <label  for="showmotd_2" title="{msg_inherit}">?</label>
                </div>
                {msg_showmotd}<br />
                <textarea name="MOTD" rows="5" cols="56">{MOTD}</textarea>
                <br />
            </fieldset>
            <br />
            <fieldset>
                <legend>{leg_sessionsec}</legend>
                {about_sessionsec}<br />
                <br />
                <div class="permradiobar">
                    <input type="radio" name="sessionip" class="permradio_y" value="1" id="sessionip_1"<!-- START sessionip_1 --> checked="checked"<!-- END sessionip_1 --> />
                    <label  for="sessionip_1">&radic;</label>
                    <input type="radio" name="sessionip" class="permradio_n" value="0" id="sessionip_0"<!-- START sessionip_0 --> checked="checked"<!-- END sessionip_0 --> />
                    <label  for="sessionip_0">-</label>
                    <input type="radio" name="sessionip" class="permradio_i" value="2" id="sessionip_2"<!-- START sessionip_2 --> checked="checked"<!-- END sessionip_2 --> />
                    <label  for="sessionip_2" title="{msg_inherit}">?</label>
                </div>
                {msg_sessionip}
                <br />
                <div class="permradiobar">
                    <input type="radio" name="sessioncookie" class="permradio_y" value="1" id="sessioncookie_1"<!-- START sessioncookie_1 --> checked="checked"<!-- END sessioncookie_1 --> />
                    <label  for="sessioncookie_1">&radic;</label>
                    <input type="radio" name="sessioncookie" class="permradio_n" value="0" id="sessioncookie_0"<!-- START sessioncookie_0 --> checked="checked"<!-- END sessioncookie_0 --> />
                    <label  for="sessioncookie_0">-</label>
                    <input type="radio" name="sessioncookie" class="permradio_i" value="2" id="sessioncookie_2"<!-- START sessioncookie_2 --> checked="checked"<!-- END sessioncookie_2 --> />
                    <label  for="sessioncookie_2" title="{msg_inherit}">?</label>
                </div>
                {msg_sessioncookie}
                <br />
            </fieldset>
            <br />
            <fieldset>
                <legend>{leg_debugging}</legend>
                {msg_debugging}:
                <select name="debugging_level"><!-- START debug_level -->
                    <option value="{level}"<!-- START sel --> selected="selected"<!-- END sel -->>{msg_level}</option><!-- END debug_level -->
                </select><br />
                <br />
                {about_debugging}<br />
            </fieldset>
            <br />
            <input type="submit" value="{msg_save}" /><br />
            <br /><!-- START editprof -->
            <a href="javascript:open_profiles('{uid}');">{msg_edit}</a>&nbsp;|&nbsp;<!-- START delprof -->
            <a href="{link_del}" style="color:darkred">{msg_del}</a>&nbsp;|&nbsp;<!-- END delprof --><!-- END editprof --><!-- START editsms -->
            <a href="{link_sms}">{msg_sms}</a>&nbsp;|&nbsp;<!-- END editsms --><!-- START editquota -->
            <a href="{link_quota}">{msg_quota}</a><!-- END editquota --><!-- START editprivs -->
            &nbsp;|&nbsp;<a href="javascript:userpriv_get({uid});">{msg_privileges}</a><!-- END editprivs --><!-- START usermod -->
            &nbsp;|&nbsp;<a href="{link_usermod}">{msg_usermod}</a><!-- END usermod --><!-- START loginfail --><br />
            <br />
            <fieldset>
                <legend>{leg_stat}</legend>
                    <table cellpadding="2" cellspacing="0" border="0">
                        <tr>
                            <td class="l">{msg_lastlogin}</td>
                            <td class="l">{lastlogin}</td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="l">{msg_lastlogout}</td>
                            <td class="l">{lastlogout}</td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="l">{msg_loginfail}</td>
                            <td class="l">{loginfail}</td>
                            <td class="l"><!-- START resetfail --><a href="{link_resetfail}">{msg_resetfail}</a><!-- END resetfail --></td>
                        </tr>
                    </table>
                </fieldset>
                <br /><!-- END loginfail -->
            </div>
        </form>

        <div id="pw_priv" class="popwin_container" style="display:none;">
            <div class="popwin_title">
                <div class="popwin_close" onclick="popwin_close('pw_priv');">&nbsp;</div>
                <span id="pw_priv_title">{poptitle_privileges}</span>
            </div>
            <div class="popwin" id="pw_priv_content">
                <form id="pw_priv_form" action="#" method="post" accept-charset="utf-8" onsubmit="return set_perm();">
                    <div>
                        <div style="height:500px;overflow:auto;"><!-- START priv_handler -->
                            <fieldset>
                                <legend>{handlername}</legend><!-- START priv_priv -->
                                <script type="text/javascript">/*<![CDATA[*/AvailPerm.push('{handler}_{priv}');/*]]>*/</script>
                                <div class="permradiobar">
                                    <input type="radio" name="p[{handler}_{priv}]" class="permradio_y" value="1" id="{handler}_{priv}_1" />
                                    <label  for="{handler}_{priv}_1">&radic;</label>
                                    <input type="radio" name="p[{handler}_{priv}]" class="permradio_n" value="0" id="{handler}_{priv}_0" />
                                    <label  for="{handler}_{priv}_0">-</label>
                                    <input type="radio" name="p[{handler}_{priv}]" class="permradio_i" value="2" id="{handler}_{priv}_2" />
                                    <label  for="{handler}_{priv}_2" title="{msg_inherit}">?</label>
                                </div>
                                <label>{privname}</label><br /><!-- END priv_priv -->
                                <br />
                                {msg_simple}:
                                <button type="button" onclick="privshortcut('{handler}',1);">{msg_all}</button>
                                <button type="button" onclick="privshortcut('{handler}',0);">{msg_none}</button>
                                <button type="button" onclick="privshortcut('{handler}',2);">{msg_inherit}</button>
                                <br />
                            </fieldset>
                            <br /><!-- END priv_handler -->
                        </div>
                        <br />
                        <fieldset>
                            {msg_simple}:
                            <button type="button" onclick="privshortcut('',1);">{msg_all}</button>
                            <button type="button" onclick="privshortcut('',0);">{msg_none}</button>
                            <button type="button" onclick="privshortcut('',2);">{msg_inherit}</button><br />
                            <br />
                            <input type="submit" value="{msg_save}" />
                            <button type="button" onclick="popwin_close('pw_priv');">{msg_cancel}</button>
                    </fieldset>
                </div>
            </form>
    </div>
</div>