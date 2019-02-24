<!-- START nosender --><br />
<br />
<p class="emptymailbox">{msg_nosender}<br /><br /><a href="{link_setup}">{msg_setup}</a></p>
<!-- END nosender --><!-- START normal -->
<script type="text/javascript" src="{frontend_path}/js/phlyThinEdit.js?{current_build}"></script>
<script type="text/javascript">
/*<![CDATA[*/
search_adb_field = '';
search_adb_fragment = '';
search_adb_cache = [];
search_adb_queried_words = [];
search_adb_for = '';
search_adb_selected = false;
search_adb_uptodate = false;
smileys_open = false;
bplates_open = 0;
contacts_open = 0;
addContactTarget = 'to';

function send_mail()
{
    error = false;
    if (document.forms[0].to.value.length == 0) {
        alert('{err_norcpt}');
        error = true;
    }
    if (!error && document.forms[0].body.value.length == 0) {
        var state = confirm('{err_notxt}');
        if (!state) error = true;
    }
    if (!error && document.forms[0].body.value.length > {max_len}) {
        var state = confirm('{err_toolong}');
        if (!state) error = true;
    }

    if (error) return;

    form = document.forms[0];
    var data = '';
    for (var i = 0; i < form.elements.length; i++) {
        var ele = form.elements[i];
        if (!ele.type || !ele.name) continue;
        if ((ele.type == 'radio' || ele.type == 'checkbox') && !ele.checked) continue;
        if (data) data += '&';
        data += encodeURIComponent(ele.name) + '=' + encodeURIComponent(ele.value);
    }
    status_window('{msg_sendmail}');
    $.ajax({url: form.action, data: data, type: form.method, dataType: 'json', success: AJAX_process});
}

function AJAX_process(next)
{
    if (next['adb_found']) {
        adb_found(next['adb_found']);
        return;
    }
    if (next['to']) document.forms[0].to.value = next['to'];
    if (next['error']) {
        status_window();
        alert(next['error']);
        return;
    }
    if (next['done']) {
        status_window();
        done();
    } else {
        status_window(next['statusmessage']);
        $.ajax({url: next['url'], type: 'GET', dataType: 'json', success: AJAX_process});
    }
}

function done()
{
    if (opener && opener.PHM_tr) {
        try {
            if (opener.CurrentHandler == 'email') opener.PHM_tr.refreshlist();
        } catch (e) { }
    }
    self.close();
}

function status_window(message)
{
    if (message) {
        $('#sendstatus').show();
        write_val(document.getElementById('sendstat_msg'), message);
    } else {
        $('#sendstatus').hide();
    }
}

function write_val(node, val)
{
    if (node.childNodes.length) node.removeChild(node.firstChild);
    if (!val || val < 1) val = 0;
    node.appendChild(document.createTextNode(val));
}

function adjustMyHeight()
{
    var avail_screen, mbody, availbody;

    // Get the available Window height
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    } else {
        avail_screen = 480;
    }
    mbody = document.getElementById('body');
    availbody = (avail_screen * 1) - (document.getElementById('bodycontainer').offsetTop * 1);
    if (document.getElementById('stats')) {
        availbody = availbody - document.getElementById('stats').offsetHeight;
    }
    // A size < 0 doesn't make sense, does it?
    if (availbody > 0)  {
        mbody.style.height = (availbody-4) + 'px'; // Decreasing height by height of border and paddings
    }
    if (availbody > 8) {
        $('#bplates_flist_container,#contacts_flist_container').height(availbody-8 + 'px');
    }
    // mbody.style.width = '99%';
}

function open_contacts()
{
    float_window('selcontact', '{msg_contacts}', '550', '370');
}

function add_contact(string, field)
{
    var target = document.getElementById('to');
    target.value = (target.value != '') ? target.value + ', ' + string : string;
}

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
        $.ajax({url: '{search_adb_url}&find=' + encodeURIComponent(search_adb_for), type: 'GET', dataType: 'json', success: AJAX_process});
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
        if (data[i].cell) {
            var show_string = data[i].fname + ' ' + data[i].lname + ' - ' + data[i].cell;
            var found = false;
            for (var j in search_adb_cache) {
                if (search_adb_cache[j].show_string == show_string) {
                    found = true;
                    break;
                }
            }
            if (found) continue;
            search_adb_cache.push({cell: data[i].cell + ' (' + data[i].fname + ' ' + data[i].lname + ')', show_string: show_string});
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
    document.getElementById(search_adb_field).value = search_adb_fragment + search_adb_cache[hit].cell;
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

function update_counter()
{
    var curr = $('#body').val().length;
    $('#counter').text({max_len} - curr);
    $('#count_sms').text((curr < 160) ? 1 : (Math.ceil(curr / 153)));
    if (curr >= {max_len}) {
        $('#smstext_lengthalert').show();
    } else {
        $('#smstext_lengthalert').hide();
    }
}

function open_smileys()
{
    if (smileys_open) {
        $('#li_smiley').removeClass('open');
        $('#smiley_selector').hide();
        smileys_open = false;
    } else {
        $('#li_smiley').addClass('open');
        $('#smiley_selector').show();
        smileys_open = true;
    }
}

function contacts_switcher()
{
    if (contacts_open) {
        $('#li_contacts').removeClass('open');
        $('#email_contacts_container').hide();
        contacts_open = false;
    } else {
        $('#li_contacts').addClass('open');
        $('#email_contacts_container').show();
        contacts_open = true;
    }
    $.ajax({url: '{path_contactsbarsetopen}' + (contacts_open == true ? 1 : 0)});
}

function contacts_get_groups()
{
    $.ajax({url: '{path_contactsbarget}', success: contacts_draw_groups, dataType: 'json'});
}

function contacts_draw_groups(groups)
{
    var HTML = '<div id="contacts_flist_head" class="sendmenubut">'
            + '<select size="1" id="contacts_groupselect" style="width:99%;" /><br />'
            + '<button type="button" id="sendtogroup" style="visibility:hidden;margin-top:2px;">{msg_sendtogroup}</button>'
            + '</div>'
            + '<div id="contacts_items"></div>';

    $(HTML).appendTo('#contacts_flist_container');
    $.each(groups, function (ID, data) {
        if (ID == 'root') ID = '';
        var HTML = '<option value="' + data['id'] + '"';
        if (!data['has_items']) {
            HTML += ' disabled="disabled" readonly="readonly"';
        }
        HTML += '>';
        HTML += stringRepeat('&nbsp;', data['level']);
        HTML += data['name'] + '</option>';
        $(HTML).appendTo('#contacts_groupselect');
    });
    $('#contacts_groupselect').bind('change keyup', contacts_get_contacts).keyup();
    $('#sendtogroup').click(function () {
        $('#contacts_items .foldername.contactline').each(function () {
            $(this).next().click();
        });
    });
}

function contacts_get_contacts()
{
    var gid = $(this).val();
    $('#sendtogroup').css('visibility', (gid == 0) ? 'hidden' : 'visible');
    $('#contacts_flist_container').addClass('loading');
    $('#contacts_items').empty();
    $.ajax({url: '{contacts_link}&gfilter=' + gid, success: contacts_draw_contacts, dataType: 'json'});
}

function contacts_draw_contacts(contacts)
{
    var targ = $('#contacts_items');
    $.each(contacts, function (ID, data) {
        data['fullname'] = (data['fname'].length > 0 && data['lname'].length > 0) ? data['fname'] + ' ' + data['lname'] : data['name'];
        var HTML = '<div class="foldername contactline" style="cursor:default;" id="flist_contact_' + ID + '" title="' + data['fullname'] + '">'
                    + '<img class="foldericon" src="{theme_path}/icons/personal_contact.gif" alt="" />'
                    + '<span class="name">' + data['name'] + '</span>'
                    + '</div>';
        $.each(['cellular', 'comp_cellular', 'tel_private', 'tel_business'], function (id2, token) {
            if (typeof data[token] == 'undefined' || !data[token].length) return true;
            HTML += '<div class="foldername clickable ' + token + '" id="flist_' + token + '_' + ID + '" title="' + data[token] + '" rel="flist_contact_' + ID + '">'
                    + '<span class="name">&#8594; ' + data[token] + '</span>'
                    + '</div>';
        });
        targ.append(HTML);
    });
    $('#contacts_flist_container')
        .removeClass('loading')
        .find('.foldername.clickable').click(function () {
            var email = $(this).attr('title');
            var realName = $('#' + $(this).attr('rel')).attr('title');
            add_contact(email + ' (' + realName + ')', addContactTarget);
        });
}

$(document).ready(function () {
    dragme.init();
    adjustMyHeight();
    update_counter();<!-- START contacts_are_open -->
    contacts_switcher();<!-- END contacts_are_open -->
    contacts_get_groups();
    thinedit.start(document.getElementById('body'));
    $(window).keydown(function(event) {
        if (event.keyCode == 13 && (event.metaKey || event.ctrlKey)) {
            window.setTimeout('send_mail();', 1);
            event.preventDefault();
            return false;
        }
    });
    $('#smiley_selector img').click(function(event) {
        thinedit.inserttext(' ' + this.title + ' ');
    });
});
$(window).resize(function() { adjustMyHeight(); });
/*]]>*/
</script>
<form action="{form_action}" method="post" name="SendForm" id="sendform">
<div class="solid_line" style="text-align:left;height:21px;" id="topmenucontainer">&nbsp;</div>
<div class="outset">
    <div class="topbarcontainer">
        <ul class="l">
            <li class="activebut" onclick="send_mail();">
                <img src="{theme_path}/icons/send_but.gif" alt="" /><span>{msg_send}</span>
            </li>
            <li class="activebut">
                <select size="1" name="sendpause">
                    <option value="0">0</option>
                    <option value="2">2</option>
                    <option value="5">5</option>
                    <option value="10">10</option>
                </select>
                {msg_sendpause}
            </li>
            <li class="activebut" id="li_smiley" onclick="open_smileys();" style="position:relative;">
                <img src="{frontend_path}/smileys/smile.gif" alt="" />
                <div id="smiley_selector" class="sendmenubut"><!-- START smileyselector -->
                    <img src="{frontend_path}/smileys{icon}" title="{emoticon}" alt="emoticon" /><!-- END smileyselector -->
                </div>
            </li>
            <li class="activebut" id="li_contacts" onclick="contacts_switcher();">
                <img src="{theme_path}/icons/contacts_but.gif" alt="" /><span>{msg_contacts}</span>
            </li>
        </ul>
    </div>
</div>
<div class="sendmenubut">
<table border="0" cellpadding="2" cellspacing="1" width="100%"><!-- START error -->
 <tr>
    <td class="l t" width="100%" colspan="2">{error}</td>
 </tr><!-- END error -->
 <tr>
  <td class="td l" width="50"><strong>{msg_from}:</strong></td>
  <td class="td l">{from}</td>
 </tr>
 <tr>
  <td class="td l"><strong>{msg_to}:</strong></td>
  <td class="td l"><div id="to_container" style="position:relative;">
   <input type="text" id="to" name="to" value="{to}" size="56" style="width:99%;" autocomplete="off" onkeyup="search_adb('to', this.value);" />
   </div>
  </td>
 </tr>
 <tr>
  <td class="td l t" colspan="2">
   <img id="smstext_lengthalert" style="float:left;margin:4px 4px 0 0;display:none;" src="frontend/themes/Carbo/icons/warning_men.png" alt="" />
   <span id="counter"></span> / {max_len} {msg_charsleft}&nbsp;|&nbsp;
   <span id="count_sms"></span> SMS&nbsp;|&nbsp;
   {msg_savecopy}:
   <select size="1" name="savefolder"><!-- START savefolder -->
    <option value="{id}">{name}</option><!-- END savefolder -->
   </select>
  </td>
 </tr><!-- START answerchoice -->
 <tr>
  <td class="td l t" colspan="2">
  {msg_answervia}:
  <input type="radio" name="answer" value="sms" id="answer_sms" checked="checked" />
  <label for="answer_sms">{msg_sms}</label>
  <input type="radio" name="answer" value="email" id="answer_email" />
  <label for="answer_email">{msg_email}</label>
  </td>
 </tr><!-- END answerchoice -->
</table>
</div>
<div id="bodycontainer">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tbody>
            <tr>
                <td id="email_contacts_container" class="sendmenubut">
                    <div id="contacts_flist_container" class="sendmenuborder inboxline loading">
                    </div>
                </td>
                <td>
                    <textarea cols="64" rows="5" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width:100%;height:1px;" name="body" id="body" class="borderless_mbody" onkeyup="update_counter();">{body}</textarea>
                </td>
            </tr>
        </tbody>
    </table>
</div>
{passthrough_2}
<input type="hidden" name="oldaction" value="{oldaction}" />
</form>
<div class="sendmenubut" id="stats"><!-- END normal --><!-- START stats -->
<fieldset>
 <legend>{leg_smsstat}</legend>
 <table border="0" cellpadding="2" cellspacing="0" class="l">
  <tr>
   <td class="l">{msg_curruse}:</td>
   <td class="l">{curr_use} {msg_sms} ({msg_approx} {curr_approx}/{msg_month})</td>
  </tr><!-- START iffree -->
  <tr>
   <td class="l">{msg_freesms}:</td>
   <td class="l">{free_used}/{free_given}</td>
  </tr><!-- END iffree -->
  <tr>
   <td class="l">{msg_lastuse}:</td>
   <td class="l">{last_use} {msg_sms}</td>
  </tr>
 </table>
</fieldset><!-- END stats -->
</div>
<div id="sendstatus" class="sendmenubut shadowed" style="display:none;width:200px;height:40px;z-index:100;position:absolute;left:100px;top:100px;">
  <div class="c t" id="sendstat_msg"> </div>
  <div class="prgr_outer">
   <div class="prgr_inner_busy"></div>
  </div>
</div>