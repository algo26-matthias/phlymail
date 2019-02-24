<script type="text/javascript">
//<![CDATA[
rulecount = 0;

function new_rule()
{
    var zeit = new Date();
    var newrule = document.getElementById('ruletpl').cloneNode(true);
    newrule.setAttribute('id', zeit.getTime());
    newrule.style.display = 'block';
    document.getElementById('rulecontainer').appendChild(newrule);
    rulecount++;
    if (rulecount > 1) {
        for (var i = 0; i < document.getElementsByName('deletebutton').length; i++) {
            document.getElementsByName('deletebutton')[i].disabled = false;
        }
    }
}

function remove_rule(id)
{
    if (rulecount < 2) return
    id.parentNode.removeChild(id);
    rulecount--;
    greyout();
}

function greyout()
{
    if (rulecount < 2) {
        document.getElementsByName('deletebutton')[0].disabled = true;
    }
    if (document.getElementById('newcolour').value.length != 0) {
        document.getElementById('colourpreview').style.background =
                '#' + document.getElementById('newcolour').value;
    }
}

function send()
{
    // Prevent the rule template from being transmitted
    var tpl = document.getElementById('ruletpl');
    var temp = tpl.cloneNode(true);
    var papa = tpl.parentNode;
    papa.removeChild(tpl);

    var error = false;

    var url = '{formlink}';
    for (var i = 0; i < document.forms[0].elements.length; i++) {
        var ele = document.forms[0].elements[i];
        if (ele.type == 'button') continue;
        if (ele.type == 'submit') continue;
        if (!ele.name) continue;
        if (ele.type == 'radio' && !ele.checked) continue;
        if (ele.type == 'checkbox' && !ele.checked) continue;

        if (ele.type == 'text' && ele.name == 'search[]' && ele.value == '') {
            alert('{e_searchterm}');
            error = true;
            ele.focus();
            break;
        }

        url += '&' + ele.name + '=' + encodeURIComponent(ele.value);
    }
    if (!error) {
        opener.location.href = url;
        self.close();
    }
    papa.appendChild(temp);
    return false;
}

function colourpicker(e)
{
    var menu = document.getElementById('mail_colourpick');
    menu.style.display = 'block';
    var gecko = (document.getElementById && window.outerHeight);
    var msie  = (document.getElementById && !window.outerHeight);

    // Find out, how close we are to document corners, position accordingly
    var right =  msie ? document.body.clientWidth - event.clientX : window.innerWidth - e.clientX;
    var bottom = msie ? document.body.clientHeight - event.clientY : window.innerHeight - e.clientY;
    var mywidth  = menu.offsetWidth;
    var myheight = menu.offsetHeight;

    // Too far to the right ?
    if (right < mywidth) {
        // place it inside document's bounds
        var left = msie ? document.body.scrollLeft + event.clientX - (mywidth - right + 5)
                : window.pageXOffset + e.clientX - (mywidth - right + 5);
    } else {
        // Let it pop up right where the mouse was clicked
        var left = msie ? document.body.scrollLeft + event.clientX
                : window.pageXOffset + e.clientX;
    }
    // repeat game with bottom pos
    if (bottom < myheight) {
        var top = msie ? document.body.scrollTop + event.clientY - (myheight - bottom + 5)
                : window.pageYOffset + e.clientY - (myheight - bottom + 5);
    } else if (myheight) {
        var top = msie ? document.body.scrollTop + event.clientY
                : window.pageYOffset + e.clientY;
    } else {
        top = 20;
    }

    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
    menu.style.zIndex = 11;
}

function email_set_colour(colour)
{
    $('#mail_colourpick').hide();
    $('#colourpreview').css('background', '#' + colour);
    $('#newcolour').val(colour);
    $('#colourmark').attr('checked', true);
}

$(document).ready(function () {
    greyout();
    $('#delete').bind('keyup click', function () {
        if ($(this).is(':checked')) {
            $('#archive,#junk').removeAttr('checked');
        }
    });
    $('#archive').bind('keyup click', function () {
        if ($(this).is(':checked')) {
            $('#delete,#junk').removeAttr('checked');
        }
    });
    $('#junk').bind('keyup click', function () {
        if ($(this).is(':checked')) {
            $('#delete,#archive').removeAttr('checked');
        }
    });
});
//]]>
</script>
<form action="#" name="form" method="get" onsubmit="return send();">
    <div class="t l" id="content">
        <fieldset>
            <legend><strong>{msg_name}:</strong></legend>
            <input type="text" name="filtername" value="{filtername}" size="32" maxlength="64" />
        </fieldset>
        <br />
        <fieldset>
            <legend><strong>{msg_headrules}:</strong></legend>
            <div style="position:relative;">

                <button type="button" onclick="new_rule();" style="position:absolute;bottom:0;right:0;">{msg_addrule}</button>

                <input type="radio" name="match" value="any"<!-- START match_any --> checked="checked"<!-- END match_any --> id="match_any" />
                <label for="match_any">{msg_matchany}</label><br />
                <input type="radio" name="match" value="all"<!-- START match_all --> checked="checked"<!-- END match_all --> id="match_all" />
                <label for="match_all">{msg_matchall}</label><br />
            </div>
            <br />
            <div id="rulecontainer"><!-- START ruleset -->
                <script type="text/javascript">/*<![CDATA[*/rulecount++/*]]>*/</script>
                <div id="{id}">
                    <select size="1" name="field[]"><!-- START field -->
                        <option value="{k}"<!-- START sel --> selected="selected"<!-- END sel -->>{v}</option><!-- END field -->
                    </select>
                    <select size="1" name="operator[]"><!-- START operator -->
                        <option value="{k}"<!-- START sel --> selected="selected"<!-- END sel -->>{v}</option><!-- END operator -->
                    </select>
                    <input type="text" name="search[]" value="{search}" size="21" maxlength="255" />
                    <button type="button" name="deletebutton" onclick="remove_rule(this.parentNode)">{msg_delete}</button>
                </div><!-- END ruleset -->
            </div>
            <div id="ruletpl" style="display:none;">
                <select size="1" name="field[]"><!-- START field -->
                    <option value="{k}">{v}</option><!-- END field -->
                </select>
                <select size="1" name="operator[]"><!-- START operator -->
                    <option value="{k}">{v}</option><!-- END operator -->
                </select>
                <input type="text" name="search[]" value="" size="21" maxlength="255" />
                <button type="button" name="deletebutton" onclick="remove_rule(this.parentNode)">{msg_delete}</button>
            </div>
            <br />
        </fieldset>
        <br />
        <fieldset>
            <legend><strong>{msg_headactions}:</strong></legend>

            <input type="checkbox" name="mv" value="1" id="move"<!-- START move --> checked="checked"<!-- END move --> />
            <label for="move">{msg_move}</label>&nbsp;
            <select size="1" name="mv_folder"><!-- START moveline -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{friendly_name}</option><!-- END moveline -->
            </select>
            <br />
            <input type="checkbox" name="cp" value="1" id="copy"<!-- START copy --> checked="checked"<!-- END copy --> />
            <label for="copy">{msg_copy}</label>&nbsp;
            <select size="1" name="cp_folder"><!-- START copyline -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{friendly_name}</option><!-- END copyline -->
            </select>
            <br />
            <input type="checkbox" name="priority" value="1" id="prio"<!-- START prio --> checked="checked"<!-- END prio --> />
            <label for="prio">{msg_setprio}</label>&nbsp;
            <select size="1" name="prio_level"><!-- START prioline -->
                <option value="{val}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END prioline -->
            </select>
            <br />
            <input type="checkbox" name="markread" value="1" id="read"<!-- START read --> checked="checked"<!-- END read --> />
            <label for="read">{msg_markas}</label>&nbsp;
            <select size="1" name="readstat"><!-- START readline -->
                <option value="{val}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END readline -->
            </select>
            <br />
            <input type="checkbox" name="colourmark" value="1" id="colourmark"<!-- START colour --> checked="checked"<!-- END colour --> />
            <label for="colourmark">{msg_setcolour}</label>
            <span id="colourpreview" style="border:1px solid black;width:16px;height:12px;background:none;font-size:12px;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
            <input type="hidden" name="newcolour" id="newcolour" value="{newcolour}" />
            <img src="{theme_path}/icons/colourmark_ctx.gif" onclick="colourpicker(event);" style="cursor:pointer" alt="" title="" />
            <br />
            <input type="checkbox" name="archive" value="1" id="archive"<!-- START archive --> checked="checked"<!-- END archive --> />
            <label for="archive">{msg_archivemail}</label>
            <br />
            <input type="checkbox" name="delete" value="1" id="delete"<!-- START delete --> checked="checked"<!-- END delete --> />
            <label for="delete">{msg_deletemail}</label>
            <br />
            <input type="checkbox" name="junk" value="1" id="junk"<!-- START junk --> checked="checked"<!-- END junk --> />
            <label for="junk">{msg_markjunk}</label>
            <br />
            <input type="checkbox" name="alert_sms" value="1" id="alert_sms"<!-- START alert_sms --> checked="checked"<!-- END alert_sms --> />
            <label for="alert_sms">{msg_alert_sms}:</label>&nbsp;<input type="text" name="sms_to" value="{sms_to}" size="14" maxlength="24" />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;{msg_between}
            <select size="1" name="sms_timeframe_fromh"><!-- START sms_tf_fh -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END sms_tf_fh -->
            </select>
            :
            <select size="1" name="sms_timeframe_fromm"><!-- START sms_tf_fm -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END sms_tf_fm -->
            </select> -
            <select size="1" name="sms_timeframe_toh"><!-- START sms_tf_th -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END sms_tf_th -->
            </select>
            :
            <select size="1" name="sms_timeframe_tom"><!-- START sms_tf_tm -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END sms_tf_tm -->
            </select>
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;{msg_minpause_sms}:
            <input type="text" name="sms_minpause_val" value="{sms_minpause_val}" size="3" maxlength="4" /> min
            <br />
            <input type="checkbox" name="alert_email" value="1" id="alert_email"<!-- START alert_email --> checked="checked"<!-- END alert_email --> />
            <label for="alert_email">{msg_alert_email}:</label>
            &nbsp;
            <input type="text" name="email_to" value="{email_to}" size="14" maxlength="255" />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;{msg_between}
            <select size="1" name="email_timeframe_fromh"><!-- START email_tf_fh -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END email_tf_fh -->
            </select>
            :
            <select size="1" name="email_timeframe_fromm"><!-- START email_tf_fm -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END email_tf_fm -->
            </select> -
            <select size="1" name="email_timeframe_toh"><!-- START email_tf_th -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END email_tf_th -->
            </select>
            :
            <select size="1" name="email_timeframe_tom"><!-- START email_tf_tm -->
                <option value="{h}"<!-- START sel --> selected="selected"<!-- END sel -->>{h}</option><!-- END email_tf_tm -->
            </select>
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;{msg_minpause_email}:
            <input type="text" name="email_minpause_val" value="{email_minpause_val}" size="3" maxlength="4" /> min<br />
        </fieldset>
        <br />
        <input type="submit" value="{msg_save}" />
    </div>
</form>
<div id="mail_colourpick" class="sendmenubut shadowed" style="position:absolute;display:none;width:93px;height:56px;">
    <table border="0" cellpadding="0" cellspacing="2">
        <tr>
            <td onclick="email_set_colour('800000');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#800000;">&nbsp;</div></td>
            <td onclick="email_set_colour('008000');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#008000;">&nbsp;</div></td>
            <td onclick="email_set_colour('000080');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#000080;">&nbsp;</div></td>
            <td onclick="email_set_colour('808000');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#808000;">&nbsp;</div></td>
            <td onclick="email_set_colour('008080');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#008080;">&nbsp;</div></td>
        </tr>
        <tr>
            <td onclick="email_set_colour('800080');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#800080;">&nbsp;</div></td>
            <td onclick="email_set_colour('808080');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#808080;">&nbsp;</div></td>
            <td onclick="email_set_colour('FF0000');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#FF0000;">&nbsp;</div></td>
            <td onclick="email_set_colour('00FF00');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#00FF00;">&nbsp;</div></td>
            <td onclick="email_set_colour('0000FF');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#0000FF;">&nbsp;</div></td>
        </tr>
        <tr>
            <td><div style="font-size:1px;">&nbsp;</div></td>
            <td onclick="email_set_colour('FFFF00');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#FFFF00;">&nbsp;</div></td>
            <td onclick="email_set_colour('00FFFF');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#00FFFF;">&nbsp;</div></td>
            <td onclick="email_set_colour('FF00FF');"><div style="cursor:pointer;width:16px;height:16px;font-size:1px;background:#FF00FF;">&nbsp;</div></td>
            <td><div style="font-size:1px;">&nbsp;</div></td>
        </tr>
    </table>
</div>