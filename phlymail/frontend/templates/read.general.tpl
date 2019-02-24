<script type="text/javascript">
/*<![CDATA[*/
// This should reflect setting the mail status to read in the inbox folder
try { if (opener.CurrentHandler == "email") { opener.email_refreshlist(); } } catch (e) {}
var theme_path = '{theme_path}';
var active_part = false;
var inline_att = [];<!-- START showinline -->
inline_att[{id}] = {"type": '{type}', "name" : '{name}'};<!-- END showinline -->
var is_inline = 0<!-- START is_inline -->+1<!-- END is_inline -->;
var replysamewin = 0<!-- START replysamewin -->+1<!-- END replysamewin -->
var link = [];
<!-- START normalheader -->
pm_menu_additem('view', false, '{but_header}', '{link_header}', 0, 0, 'href');<!-- END normalheader --><!-- START fullheader -->
pm_menu_additem('view', false, '{but_header}', '{link_header}', 0, 0, 'href');<!-- END fullheader --><!-- START teletype_pro -->
pm_menu_additem('view', false, '{but_teletype}', '{link_teletype}', 0, 0, 'href');<!-- END teletype_pro --><!-- START teletype_sys -->
pm_menu_additem('view', false, '{but_teletype}', '{link_teletype}', 0, 0 , 'href');<!-- END teletype_sys -->
pm_menu_addline('view');<!-- START but_txtvers -->
pm_menu_additem('view', false, '{msg_textversion}',"switch_part('text')", 0, 0, 'js');
link['text'] = '{link}';<!-- END but_txtvers --><!-- START but_htmlvers -->
pm_menu_additem('view', false, '{msg_securehtml}', "switch_part('sanitizedhtml')", 0, 0, 'js');
pm_menu_additem('view', false, '{msg_originalhtml}', "switch_part('originalhtml')", 0, 0, 'js');
link['sanitizedhtml'] = '{link}';
link['originalhtml']  = '{link}&sanitize=0';<!-- END but_htmlvers -->
pm_menu_addline('view');
pm_menu_additem('view', '{theme_path}/icons/raw_ctx.gif', '{msg_viewsrc}', "do_mailop('viewsrc')", 0, 0, 'js');
pm_menu_additem('mail', '{theme_path}/icons/save_ctx.gif', '{msg_save}', '{link_save}', 0, 0, 'href');
var ctxmen = [ {'status' : 1, 'link' : 'dl_attach()', 'name' : '{msg_save}', 'icon' : '{theme_path}/icons/save_ctx.gif'} ];
var ctxmen_id = false;
var ctxadded = false;
var hdl_ctx = [];
var att_info = [];
var bounce_del = 0;
var ctxover = false;<!-- START availhdls -->
context_addhandler('{icon}', '{handler}', '{msg}');<!-- END availhdls -->
var search_adb_field = '';
var search_adb_fragment = '';
var search_adb_cache = [];
var search_adb_queried_words = [];
var search_adb_for = '';
var search_adb_selected = false;
var search_adb_uptodate = false;

function context_addhandler(icon, handler, msg)
{
    if (!ctxadded) {
        ctxmen.push({'status' : 2});
        ctxadded = true;
    }
    ctxmen.push({'status':3,'link':'sendto("' + handler + '")','name':msg,'icon':'{theme_path}/icons/' + icon});
    hdl_ctx[handler] = ctxmen.length-1;
}

function menuattach(attnum)
{
    for (var i = 0; i < ctxmen.length; ++i) {
        if (i < 2) continue;
        ctxmen[i]['status'] = 3;
    }
    for (var i = 0; i < att_info[attnum]['hdls'].length; ++i) ctxmen[hdl_ctx[att_info[attnum]['hdls'][i]]]['status'] = 1;
    ctxover = attnum;
}

function dl_attach()
{
    if (ctxover == false) return;
    var ahref = document.getElementById('ahref_' + ctxover);
    if (ahref) {
        if (typeof ahref.target != 'undefiend' && ahref.target == '_blank')  {
            window.open(ahref.href);
        } else {
            self.location.href = ahref.href;
        }
    }
}

function sendto(hndlr)
{
    var resid = att_info[ctxover]['resid'];
    date = new Date();
    ctime = date.getTime();
    window.open
            ('{link_sendto}&handler=' + hndlr + '&resid=' + encodeURIComponent(resid)
            ,'_sendto_' + ctime
            ,'width=250,height=250,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no,personalbar=no'
            );
}

function switch_part(part)
{
    if (active_part != part) document.getElementById('mbody_iframe').src = link[part];
    mpart = part_to_menu(active_part);
    if (!pm_menu['view'][mpart]) return;
    pm_menu['view'][mpart]['selected'] = 0;
    mpart = part_to_menu(part);
    if (!pm_menu['view'][mpart]) return;
    pm_menu['view'][mpart]['selected'] = 1;
    active_part = part;
}

function part_to_menu(part)
{
    switch (part) {
    case 'text': return 3; break;
    case 'sanitizedhtml': return ((link['text']) ? 4 : 3); break;
    case 'originalhtml':  return ((link['text']) ? 5 : 4); break;
    default: return 3; break;
    }
}

function AJAX_process(next)
{
    if (next['adb_found']) {
        adb_found(next['adb_found']);
        return;
    }
    if (next['done']) {
        try { // Syntax for Yokohama 4.0+
            if (opener.CurrentHandler == 'email') { opener.email_refreshlist(); }
        } catch (e) {
            try { // Syntax for Nahariya 4.0+
                if (opener.CurrentHandler == 'email') { opener.refreshlist(); }
            } catch (e) {
                /* void */
            }
        }
        window.close();
    }
}
//
//Handle address book search for bounce recipients
//
function search_adb(field, value)
{
    if (search_adb_uptodate) {
        search_adb_uptodate = false;
        return;
    }
    search_adb_field = field;
    f1_end = value.lastIndexOf(', ');
    f2_end = value.lastIndexOf(',');
    if (f1_end != -1) {
        search_adb_fragment = value.substr(0, f1_end+2);
        now_search_for = value.substring(f1_end+2, value.length);
    } else if (f2_end != -1) {
        search_adb_fragment = value.substr(0, f2_end+1);
        now_search_for = value.substring(f2_end+1, value.length);
    } else {
        now_search_for = value;
    }
    // Avoid querying too much data at once
    if (now_search_for.length < 2) {
        adb_hide_hits();
        return;
    }
    if (now_search_for == search_adb_for) return;
    search_adb_for = now_search_for;
    if (adb_query_cache(search_adb_for)) {
        adb_show_hits();
    } else {
        $.ajax({'url': '{search_adb_url}&find=' + encodeURIComponent(search_adb_for), 'success' : AJAX_process});
    }
}

function adb_query_cache(value)
{
    for (var i in search_adb_queried_words) {
        if (search_adb_queried_words[i].toLowerCase().indexOf(value.toLowerCase()) != -1) {
            return true;
        }
    }
    return false;
}

function adb_add_cache(data)
{
    search_adb_queried_words.push(search_adb_for);
    for (var i in data) {
        if (data[i].email1) {
            var show_string = '<' + data[i].email1 + '> ' + data[i].fname + ' ' + data[i].lname;
            var found = false;
            for (var j in search_adb_cache) {
                if (search_adb_cache[j].show_string == show_string) {
                    found = true;
                    break;
                }
            }
            if (found) continue;
            search_adb_cache.push({'email' : data[i].email1 + ' (' + data[i].fname + ' ' + data[i].lname + ')', 'show_string' : show_string});
        }
        if (data[i].email2) {
            var show_string = '<' + data[i].email2 + '> ' + data[i].fname + ' ' + data[i].lname;
            var found = false;
            for (var j in search_adb_cache) {
                if (search_adb_cache[j].show_string == show_string) {
                    found = true;
                    break;
                }
            }
            if (found) continue;
            search_adb_cache.push({'email' : data[i].email2 + ' (' + data[i].fname + ' ' + data[i].lname + ')', 'show_string' : show_string});
        }     
    }
}

function adb_found(data)
{
 if (data.length == 0) {
     adb_hide_hits();
     return;
 }
 adb_add_cache(data)
 adb_show_hits()
}

function adb_show_hits()
{
 adb_hide_hits();
 mycont = document.getElementById(search_adb_field + '_container');

 if (search_adb_cache.length == 0) return;

 div = document.createElement('div');
 div.id = 'adb_show_hits';
 div.style.position = 'absolute';
 div.style.top = (mycont.offsetHeight-1) + 'px';
 div.style.left = '0px';
 div.style.zIndex = "100";
 div.onmouseover = adb_mark_hit;
 div.onmouseout = adb_unmark_hit;
 div.onclick = adb_choose_hit;
 // Enabale reacting on cursors / enter
 $(window).bind('keydown.drop', adb_check_keys);

 div.style.width = (document.getElementById(search_adb_field).offsetWidth-2) + 'px';
 div.style.border = '1px solid black';
 div.style.backgroundColor = 'white';

 for (var i in search_adb_cache) {
     show_string = search_adb_cache[i].show_string;
     fundstart = show_string.toLowerCase().indexOf(search_adb_for.toLowerCase());
     if (-1 == fundstart) continue;
     fundende = fundstart + search_adb_for.length;
     l = document.createElement('div');
     l.className = 'adbfound';
     l.id = 'hit_' + i;
     l.appendChild(document.createTextNode(show_string.substr(0, fundstart)));
     s = document.createElement('strong');
     s.appendChild(document.createTextNode(show_string.substring(fundstart, fundende)));
     l.appendChild(s);
     l.appendChild(document.createTextNode(show_string.substring(fundende, show_string.length)));
     div.appendChild(l);
 }
 mycont.appendChild(div);
 adb_select_hit(0); // Select the first hit

}

function adb_hide_hits()
{
 // Disable reacting on cursors / enter
 $(window).unbind('keydown.drop');

 search_adb_selected = 0;
 if (document.getElementById('adb_show_hits')) {
     document.getElementById('adb_show_hits').parentNode.removeChild(document.getElementById('adb_show_hits'));
 }
}

function adb_mark_hit(e)
{
 var src = msie ? event.srcElement : e.target;
 if (src.className == 'adbfound' || src.parentNode.className == 'adbfound') {
     if (src.parentNode.className == 'adbfound') {
         src = src.parentNode;
     }
     src.className = 'adbfound_hover';
 }
}

function adb_unmark_hit(e)
{
 var src = msie ? event.srcElement : e.target;
 if (src.className == 'adbfound_hover' || src.parentNode.className == 'adbfound_hover') {
     if (src.parentNode.className == 'adbfound_hover') {
         src = src.parentNode;
     }
     src.className = 'adbfound';
     adb_select_hit();
 }
}

function adb_choose_hit(e)
{
 var src = msie ? event.srcElement : e.target;
 if (src.className == 'adbfound_hover' || src.parentNode.className == 'adbfound_hover') {
     if (src.parentNode.className == 'adbfound_hover') {
         src = src.parentNode;
     }
     adb_use_hit(src.id);
     adb_hide_hits();
 }
}

function adb_use_hit(hit)
{
 search_adb_uptodate = true;
 hit = hit.replace(/^hit_/, '');
 document.getElementById(search_adb_field).value = search_adb_fragment + search_adb_cache[hit].email;
 document.getElementById(search_adb_field).focus();
}

function adb_select_hit(number)
{
 document.getElementById('adb_show_hits').childNodes[search_adb_selected].className = 'adbfound';
 if (0 == number) {
     search_adb_selected = 0;
 } else if (1 == number) {
     search_adb_selected++;
     if (document.getElementById('adb_show_hits').childNodes.length <= search_adb_selected) {
          search_adb_selected--;
     }
 } else if (-1 == number) {
     search_adb_selected--;
     if (search_adb_selected < 0) search_adb_selected = 0;
 }
 document.getElementById('adb_show_hits').childNodes[search_adb_selected].className = 'adbfound_hover';
}

function adb_enter_hit()
{
 adb_use_hit(document.getElementById('adb_show_hits').childNodes[search_adb_selected].id);
 adb_hide_hits();
}

function adb_check_keys(e)
{
 var key = e.keyCode;
 if (key == 13 || key == 38 || key == 40 || key == 27) {
     e.preventDefault();
     e.stopPropagation();
     switch (key) {
         case 13: adb_enter_hit(); break;
         case 38: adb_select_hit(-1); break;
         case 40: adb_select_hit(1); break;
         case 27: adb_hide_hits(); break;
     }
 }
}

//
//End address book search
//

/**
 * Handling answering, answering all, forwarding, bouncing,
 * printing, view source
 */
function do_mailop(which)
{
    var sanitize = '';
    var link;
    if (active_part && active_part == 'originalhtml') sanitize = '&sanitize=0';
    switch (which) {
    case 'answer':
        if (is_inline) return false;
        link = '{link_answer}';
        break;
    case 'answerAll':
        if (is_inline) return false;
        link = '{link_answerAll}';
        break;
    case 'forward':
        if (is_inline) return false;
        link = '{link_forward}';
        break;
    case 'bounce':
        if (is_inline) return false;
        create_bounce_form();
        return false;
        break;
    case 'print':
        link = '{link_print}';
        break;
    case 'viewsrc':
        if (is_inline) return false;
        link = '{link_viewsrc}';
        break;
    default:
        return false;
        break;
    }
    if ('viewsrc' != which && 'print' != which && replysamewin == 1) {
        self.location.href = link + sanitize;
    } else {
        window.open
                (link + sanitize
                ,'send_' + new Date().getTime()
                ,'width=600,height=500,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
                );
    }
}

function delete_mail()
{
    if (confirm('{msg_dele}')) $.ajax({"url": '{link_dele}', "success": AJAX_process});
}

function archive_mail()
{
    $.ajax({"url": '{link_archive}', "success": AJAX_process});
}

function adjustMyHeight()
{
    var avail_screen = 500;
    // Eeeeeextrawurst, Extrawurst .... fuer den IE natuerlich
    if (document.getElementById('kopfzeilen').offsetHeight > 150) {
        document.getElementById('kopfzeilen').style.height = '150px';
    }
    // Get the available Window height
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    }

    var mbody = document.getElementById('mailbody');
    var mbody_height = mbody.offsetHeight;
    var iframe_height = document.getElementById('mbody_iframe').offsetHeight;
    var availbody = avail_screen - (mbody.offsetTop);
    if (document.getElementById('attachments')) {
        availbody = availbody - (document.getElementById('attachments').offsetHeight + 6);
    }
    if (availbody > 0)  {
        mbody.style.height = availbody + 'px';
        var new_iframe = availbody - 8;
        document.getElementById('mbody_iframe').style.height = new_iframe + 'px';
    }
}

function view_inline()
{
    // On inline mails, disable all not supported items
    if (1 == is_inline) {
        document.getElementById('but_answer').className = 'disabledbut';
        document.getElementById('but_answerall').className = 'disabledbut';
        document.getElementById('but_forward').className = 'disabledbut';
        document.getElementById('but_dele').className = 'disabledbut';
        document.getElementById('pm_menu_container').style.visibility = 'hidden';
    }
    var hr, im, div;
    if (inline_att.length == 0) return;
    AppDoc = frames.mbody_iframe.document;
    if (typeof AppDoc == 'undefined') return;

    AppNode = AppDoc.getElementById('mailtext');
    for (var ID in inline_att) {
        if (inline_att[ID]['type'] != 'image' && inline_att[ID]['type'] != 'text') continue;

        // We don't have a generic style sheet for HTML mails
        hr = AppDoc.createElement('div');
        hr.style.borderBottom = '1px solid darkgray';
        hr.style.padding = '4px';
        hr.style.margin = '2px';
        hr.style.fontSize = '9pt';
        hr.style.backgroundColor = 'white';
        hr.style.fontFamily = 'Verdana, Arial, Helvetica, "Sans Serif"';
        hr.style.color = 'darkgray';
        hr.appendChild(AppDoc.createTextNode(inline_att[ID]['name']));
        AppNode.appendChild(hr);

        if (inline_att[ID]['type'] == 'image') {
            img = AppDoc.createElement('img');
            img.style.display = 'block';
            img.style.margin = 'auto';
            img.src = '{showinlineurl}' + ID;
            AppNode.appendChild(img);
        } else if (inline_att[ID]['type'] == 'text') {
            div = AppDoc.createElement('div');
            div.id = 'inline_div_' + ID;
            AppNode.appendChild(div);
            $.ajax(
                {url : '{showinlineurl}' + ID
                ,dataType: 'text'
                ,success: function (data) {
                    AppDoc.getElementById('inline_div_' + ID).innerHTML = '<pre>' + data + '</pre>';
                }});
        }
    }
}

function create_bounce_form()
{
    $('#mail_bouncer').show();
}

function cancel_bounce()
{
    bounce_del = 0;
    var mBo = $('#mail_bouncer');
    mBo.find('input, button').removeAttr('disabled').removeAttr('checked');
    mBo.find('#mail_bounce_to').val('');
    mBo.find('#mail_bounce_action').hide();
    mBo.hide();
}

function start_bounce(internal)
{
    if (!internal) {
        if ($('#mail_bounce_del:checked').length == 1) {
            bounce_del = 1;
        }
    }
    $('#mail_bouncestat_msg').text('%j%bounce% ...');
    $('#mail_bounce_action').show();
    $.ajax({url : '{bounce_url}&to=' + encodeURIComponent($('#mail_bounce_to').val()), dataType : 'json', success : process_bounce })
    $('#mail_bouncer').find('input,button').attr('disabled', 'disabled');
}

function process_bounce(next)
{
    if (next['error']) {
        alert(next['error']);
    }
    if (next['done']) {
        if (bounce_del != 0) {
            $.ajax({'url' : '{bounce_del_url}', 'dataType' : 'json', 'success' : AJAX_process});
        } else {
            cancel_bounce();
        }
    } else {
        $('#mail_bouncestat_msg').text(next['statusmessage']);
        $.ajax({'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : process_bounce});
    }
}

function sendtoadb(address, realname)
{
    if (address == realname) realname = '';
    window.open
            ('{link_sendtoadb}&email1=' + address + '&firstname=' + realname
            ,'contact_' + new Date().getTime()
            ,'width=870,height=620,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            )
}
<!-- START mdn -->
send_dsn = ('{dispomode}' == 'manual') ? confirm('{msg_confirm_mdn}') : true;
if (send_dsn) $.ajax({url:'{send_url}'});
$.ajax({url:'{status_url}'});<!-- END mdn -->
active_part = '{active_part}';
switch_part('{active_part}');
$(document).ready(function () {
	adjustMyHeight();
});
$(window).resize(function () {
    adjustMyHeight();
});
/*]]>*/
</script>
<div class="solid_line" style="text-align:left;" id="topmenucontainer">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr class="solid_nodrop" id="pm_menu_container">
            <td><a href="javascript:void(0);" class="active" id="topmendrop_mail" onmouseover="pm_menu_create('mail');" onclick="pm_menu_switch(this);">{msg_mail}</a></td>
            <td class="men_separator"></td>
            <td><a href="javascript:void(0);" class="active" id="topmendrop_view" onmouseover="pm_menu_create('view');" onclick="pm_menu_switch(this);">{msg_view}</a></td>
            <td class="men_separator"></td>
        </tr>
    </table>
</div>
<div class="outset">
    <div class="topbarcontainer">
        <ul class="l">
            <li class="activebut" id="but_answer" onclick="do_mailop('answer')" title="{but_answer}">
                <img src="{theme_path}/icons/answer.gif" alt="" /><span>{but_answer}</span>
            </li>
            <li class="activebut" id="but_answerall" onclick="do_mailop('answerAll')" title="{but_answerAll}">
                <img src="{theme_path}/icons/answerall.gif" alt="" /><span>{but_answerAll}</span>
            </li>
            <li class="activebut" id="but_forward" onclick="do_mailop('forward')" title="{but_forward}">
                <img src="{theme_path}/icons/forward.gif" alt="" /><span>{but_forward}</span>
            </li>
            <li class="activebut" id="but_bounce" onclick="do_mailop('bounce')" title="{but_bounce}">
                <img src="{theme_path}/icons/bounce.gif" alt="" /><span>{but_bounce}</span>
            </li>
            <li class="activebut" onclick="do_mailop('print')" title="{but_print}">
                <img src="{theme_path}/icons/print_men.gif" alt="" /><span>{but_print}</span>
            </li>
            <li class="activebut" id="but_archive" onclick="archive_mail();" title="{but_archive}">
                <img src="{theme_path}/icons/archive_men.gif" alt="" /><span>{but_archive}</span>
            </li>
            <li class="activebut" id="but_dele" onclick="delete_mail();" title="{but_dele}">
                <img src="{theme_path}/icons/delete.gif" alt="" /><span>{but_dele}</span>
            </li>
        </ul>
    </div>
</div>
<div class="sendmenubut tdl" style="max-height:150px;overflow:auto;" id="kopfzeilen">
    <table border="0" cellpadding="2" cellspacing="0"><!-- START headerlines -->
        <tr>
            <td class="t l nowrap"><strong>{hl_key}:</strong></td>
            <td class="t l {hl_add}">{hl_val}</td>
        </tr><!-- END headerlines -->
    </table>
</div>
<div id="mailbody">
    <iframe src="{body_link}" width="100%" height="100%" id="mbody_iframe" name="mbody_iframe" frameborder="0" class="mailtext"></iframe>
</div><!-- START attachblock -->
<div id="attachments">
    <div class="sendmenubut" id="attachmentdivider"></div>
    <div id="attachmentcontainer"><!-- START attachline -->
        <script type="text/javascript">/*<![CDATA[*/att_info[{att_num}] = { 'resid' : '{resid}', 'hdls' : [{hdllist}]};/*]]>*/</script>
        <span onmouseover="ctxmen_activate_sensor(ctxmen);" onmouseout="ctxmen_disable_sensor();if(ctxmen_id==false){ctxover=false;}" oncontextmenu="menuattach('{att_num}');" style="white-space:nowrap;"><img src="{frontend_path}/filetypes/32/{att_icon}" alt="" title="{att_icon_alt}" />&nbsp;<a id="ahref_{att_num}" title="{att_size} {msg_att_type}: {att_type}" href="{link_target}"<!-- START inline --> target="_blank"<!-- END inline -->>{att_name}</a></span><!-- END attachline -->
    </div>
</div><!-- END attachblock --><!-- START preview_blocked --><!-- END preview_blocked -->
    <div id="mail_bouncer" class="sendmenubut shadowed" style="position:absolute;display:none;width:370px;height:155px;left:80px;top:40px;">
        <table border="0" cellpadding="2" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th class="c" colspan="2">{bounce}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="l nowrap">%h%from%:</td>
                    <td class="l"><div id="mail_bounce_ofrom" style="overflow:hidden;">{hl_bounce_from}</div></td>
                </tr>
                <tr>
                    <td class="l nowrap">%h%subject%:</td>
                    <td class="l"><div id="mail_bounce_osubj" style="overflow:hidden;">{hl_bounce_subject}</div></td>
                </tr>
                <tr>
                    <td class="l nowrap">%h%bounce_to%:</td>
                    <td class="l">
                        <div id="mail_bounce_to_container" style="position:relative;">
                            <input type="text" size="40" style="width:99%;" name="mail_bounce_to" id="mail_bounce_to" autocomplete="off" onkeyup="search_adb('mail_bounce_to', this.value);" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="mail_bounce_del" id="mail_bounce_del" value="1" />
                        <label for="mail_bounce_del">%h%killorig%</label>
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">
                        <div>
                            <button type="button" class="ok" id="mail_bounce_start" onclick="start_bounce();" style="float:right;">OK</button>
                            <button type="button" class="error" onclick="cancel_bounce();">%h%cancel%</button>
                        </div>
                        <div id="mail_bounce_action" style="margin-top:4px;display:none;height:40px;">
                            <div class="c t" id="mail_bouncestat_msg"> </div>
                            <div class="prgr_outer">
                                <div class="prgr_inner_busy"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>