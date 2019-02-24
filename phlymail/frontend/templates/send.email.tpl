<script type="text/javascript" src="{frontend_path}/js/phlyThinEdit.js?{current_build}"></script>
<script type="text/javascript">
/*<![CDATA[*/
theme_path = '{theme_path}';
use_html = false;
content_type = 'text/plain';
msg_prio = 3;
return_receipt = 0;
show_bcc = 0;
show_att = 0;
bplates_open = 0;
contacts_open = 0;
attachlist = [];
attachments_visible = 0;
bplateHoverTO = false;
bplatePreViewTO = false;
addContactTarget = 'to';
openlist = {};
form_submitted = 0;
search_adb_field = '';
search_adb_fragment = '';
search_adb_cache = [];
search_adb_queried_words = [];
search_adb_for = '';
search_adb_selected = false;
search_adb_uptodate = false;
smileys_open = false;
return_receipt = 0<!-- START receipt --> + 1;<!-- END receipt -->;
vcf_default = '{vcf_default}';

pm_menu['prio'] = [];<!-- START priomen -->
curprio = {prioval}; // Avoiding assignment problems of the template. Ugly, but working
pm_menu['prio'][{prioval}] = {'name': '{priotxt}', 'link': 'select_prio("{prioval}")', 'linktype': 'js', 'selected': 0};<!-- START priosel -->
pm_menu['prio'][curprio]['selected'] = 1;
msg_prio = curprio;<!-- END priosel --><!-- END priomen -->

pm_menu['option'] =
        [{'name': '{msg_receipt}', 'link': 'toggle_receipt()', 'linktype': 'js', 'selected': return_receipt}
        ,{'name': '{msg_showbcc}', 'link': 'toggle_bcc()', 'linktype': 'js', 'selected': 0}
        ];
pm_menu['saveas'] =
        [{'name': '{msg_savedraft}', 'link': 'send_mail(1)', 'linktype': 'js'}
        ,{'name': '{msg_savetemplate}', 'link': 'send_mail(2)', 'linktype': 'js'}
        ];
pm_menu['attachments'] = [{'name': '{msg_upload}', 'icon': '{theme_path}/icons/files_upload.gif', 'link': 'open_attachs()', 'linktype': 'js'}];<!-- START attachreceiver -->
pm_menu_addline('attachments');
pm_menu_additem('attachments', '{theme_path}/icons/files_sendto.gif', '{msg_name}', 'open_attachbrowser();', 0, 0, 'js');<!-- END attachreceiver -->

function open_attachbrowser()
{
    var myleft = 200;
    var mytop = 200;
    browserwin = window.open
            ('{path_attachbrowse}'
            ,'attach_browser'
            ,'width=600,height=400,left=' + myleft + ',top=' + mytop + ',scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function receive_files(HDL, data)
{
    var URL = '{receive_files_url}&from_h=' + encodeURIComponent(HDL);
    $.each(data, function (i, v) {
        URL += '&item[]=' + encodeURIComponent(v);
    })
    $.ajax({url: URL, dataType: 'json', success : files_received})
}

function files_received(data)
{
    $.each(data, function (k, v) {
        addattach(v['name'], v['filename'], v['icon'], 'user', v['mimetype']);
    });
}

function adjustMyHeight()
{
    var avail_screen, attheight;
    // We have to force MSIE to accept our max-height setting for the attachment container
    if (show_att != 0) {
        attheight = $('#sendattachcont').height();
        if (attheight > 100) {
            $('#sendattachcont').height(100);
        }
    }
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
    availbody = (avail_screen * 1) - ($('#oben').height() * 1);
    if (show_att != 0) {
        availbody -= (attheight+8);
    }
    availbody -= 20; // Decreasing height by height of border and paddings

    if (availbody > 8) {
        $('#bplates_flist_container,#contacts_flist_container').height(availbody-8);
    }

    if (typeof CKEDITOR != 'undefined' && typeof CKEDITOR.instances != 'undefined' && CKEDITOR.instances.mbody) {
        $('#cke_contents_mbody').height($('#cke_contents_mbody').height() + (availbody - $('#cke_mbody').height()) - 12);
    } else {
        var mbody = $('#mbody');
        mbody.width('100%');
        if (availbody > 0) {
            mbody.height(availbody + 'px');
        }
    }
}

function send_mail(is_draft)
{
    if (!is_draft) {
        if ($('#subject').val() == '') {
            if (!confirm('{msg_confirm_no_subject}')) return false;
        }
        if ($('#to').val() == '' && $('#cc').val() == '' && $('#bcc').val() == '') {
            alert('{msg_confirm_no_receiver}');
            return false;
        }
    }
    var form = document.getElementById('sendform');
    var data = '';
    // Make sure to fetch the current text from the RichTextEditor
    if (use_html) {
        form_submitted = 1;
        form.mbody.value = $('#mbody').val();
    }

    for (var i = 0; i < form.elements.length; i++) {
        var ele = form.elements[i];
        if (!ele.type || !ele.name) continue;
        if ((ele.type == 'radio' || ele.type == 'checkbox') && !ele.checked) continue;
        if (data) data += '&';
        data += encodeForForm(ele.name, ele.value);
    }
    if (is_draft) {
        data += '&' + (is_draft == 2 ? 'template' : 'draft') + '=1';
    }
    if (use_html && content_type == 'text/html') {
        data += '&' + encodeForForm('WP_send[bodytype]', 'text/html');
    }
    data += '&' + encodeForForm('WP_send[prio]', msg_prio)
            + '&' + encodeForForm('WP_send[return_receipt]', return_receipt)
            + '&' + encodeForForm('WP_send[references]', '{head_references}')
            + '&' + encodeForForm('WP_send[inreply]', '{message_id}');
    if (attachlist.length && attachlist.length > 0) {
        for (var att = 0; att < attachlist.length; att++) {
            // Empty entry
            if (!attachlist[att]['name']) continue;
            data += '&' + encodeForForm('WP_send[attach][' + att + '][name]', attachlist[att]['name'])
                    + '&' + encodeForForm('WP_send[attach][' + att + '][filename]', attachlist[att]['filename'])
                    + '&' + encodeForForm('WP_send[attach][' + att + '][mode]', attachlist[att]['mode']);
            if (attachlist[att]['mimetype']) {
                data += '&' + encodeForForm('WP_send[attach][' + att + '][mimetype]', attachlist[att]['mimetype']);
            }
            // This ensures, that attachments which got "deleted" after uploading them
            // will be removed from the filesystem (less garbage piling up in the
            // user's storage area)
            if (attachlist[att]['deleted']) {
                data += '&' + encodeForForm('WP_send[attach][' + att + '][is_deleted]', '1');
            }
        }
    }
    // Done ... send out
    status_window('{msg_sendmail}');
    $.ajax({'url': form.action, 'data': data, 'type': form.method, 'dataType' : 'json', 'success' : AJAX_process});
}

function encodeForForm(name, value)
{
    return encodeURIComponent(name) + '=' + encodeURIComponent(value);
}

function AJAX_process(next)
{
    if (next['adb_found']) {
        adb_found(next['adb_found']);
        return;
    }
    if (next['boilerplate']) {
        boilerplate_insert(next['boilerplate']);
        return;
    }
    if (next['error']) {
        alert(next['error']);
        status_window();
        if (confirm('{msg_notsent_save}')) send_mail(true);
        return;
    }
    if (next['done']) {
        status_window();
        done();
    } else {
        status_window(next['statusmessage']);
        $.ajax({'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : AJAX_process});
    }
}

function done()
{
    try { opener.email_ext_checkmails(); } catch (e) { }
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
        document.getElementById('sendstatus').style.display = 'block';
        write_val(document.getElementById('sendstat_msg'), message);
    } else {
        document.getElementById('sendstatus').style.display = 'none';
    }
}

function write_val(node, val)
{
    if (node.childNodes.length) node.removeChild(node.firstChild);
    if (!val || val < 1) val = 0;
    node.appendChild(document.createTextNode(val));
}

function toggle_bcc()
{
    domBcc = document.getElementById('bcc_line');
    if (show_bcc) {
        domBcc.style.display = 'none';
        pm_menu['option'][1]['selected'] = 0;
        show_bcc = 0;
    } else {
        domBcc.style.display = (msie) ? 'block' : 'table-row';
        pm_menu['option'][1]['selected'] = 1;
        show_bcc = 1;
    }
    adjustMyHeight();
    return false;
}

function toggle_receipt()
{
    if (return_receipt) {
        pm_menu['option'][0]['selected'] = 0;
        return_receipt = 0;
    } else {
        pm_menu['option'][0]['selected'] = 1;
        return_receipt = 1;
    }
    return false;
}

function select_prio(prio)
{
    if (msg_prio) pm_menu['prio'][msg_prio]['selected'] = 0;
    msg_prio = prio;
    pm_menu['prio'][msg_prio]['selected'] = 1;
    return false;
}

function open_attachs()
{
    float_window('attachs', '{msg_attachs}', '309', '220');
}

function open_signature()
{
    float_window('signature', '{msg_signature}', '539', '450');
}

function open_contacts()
{
    float_window('selcontact', '{msg_contacts}', '570', '390');
}

function append_sig(txt, html)
{
    // Using preDOM methods here
    // Adding -- LF before the signature is the quasi standardized way to
    // denote the end of the mail and the beginning of the signature
    if (use_html) {
        var editor = $('#mbody').ckeditorGet();
        if ('{answer_style}' == 'tofu') {
            editor.setData("<br />\n--<br />\n" + html + '<br />\n<br />\n' + editor.getData());
        } else {
            editor.setData(editor.getData() + "<br />\n--<br />\n" + html);
        }
    } else {
        var mbody = document.getElementById('mbody');
        if (mbody.value != '') {
            mbody.value = '{answer_style}' == 'tofu'
                    ? "\n-- \n" + txt + "\n \n" + mbody.value
                    : mbody.value + "\n-- \n" + txt + "\n";
        } else {
            mbody.value = "\n-- \n" + txt + "\n";
        }
    }
}

function add_contact(string, field)
{
    if (!field || (field != 'to' && field != 'cc' && field != 'bcc')) return false;
    var target = document.getElementById(field);
    if (target.value != '') {
        target.value = target.value + ', ' + string;
    } else {
        target.value = string;
    }
    if ('bcc' == field && show_bcc == 0) {
        toggle_bcc();
    }
}

function addattach(name, filename, small_icon, mode, mimetype)
{
    var offset = attachlist.length;
    if (!name || !filename || !mode) return;

    attachlist[offset] = [];
    attachlist[offset]['name']       = name;
    attachlist[offset]['filename']   = filename;
    attachlist[offset]['small_icon'] = small_icon;
    attachlist[offset]['mode']       = mode;
    attachlist[offset]['mimetype']   = (mimetype) ? mimetype : false;

    var tr = document.createElement('tr');
    tr.setAttribute('id', 'att_' + offset);
    var td = document.createElement('td');
    td.setAttribute('align', 'left');
    td.setAttribute('class', 'menuline');

    var a = document.createElement('a');
    a.style.textDecoration = 'none';
    a.href = 'javas' + 'cript:delattach(' + offset + ');';

    var img = document.createElement('img');
    img.setAttribute('src', '{theme_path}/icons/dustbin_menu.gif');
    img.setAttribute('align', 'absmiddle');
    img.setAttribute('alt', '{msg_dele_att}');
    img.setAttribute('title', '{msg_dele_att}');
    img.style.marginRight = '4px';
    a.appendChild(img);
    td.appendChild(a);

    var img = document.createElement('img');
    img.setAttribute('src', '{root_path}/' + small_icon);
    img.setAttribute('align', 'absmiddle');
    img.setAttribute('alt', '');
    img.setAttribute('title', (attachlist[offset]['mimetype']) ? attachlist[offset]['mimetype'] : '');
    td.appendChild(img);
    td.appendChild(document.createTextNode(' ' + name));

    tr.appendChild(td);

    document.getElementById('attlines').appendChild(tr);
    attachments_visible += 1;
    // Make sure, the attachment block is visible
    $('#sendattachcont').show().focus();
    show_att = 1;
    adjustMyHeight();
}

function delattach(offset)
{
    attachlist[offset]['deleted'] = true;
    document.getElementById('att_' + offset).parentNode.removeChild(document.getElementById('att_' + offset));
    attachments_visible -= 1;
    if (1 > attachments_visible) {
        $('#sendattachcont').hide();
        show_att = 0;
    }
    adjustMyHeight();
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
        search_adb_fragment = '';
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

    var div = document.createElement('div');
    div.id = 'adb_show_hits';
    div.style.position = 'absolute';
    div.style.top = (mycont.offsetHeight-1) + 'px';
    div.style.left = '0px';
    div.style.zIndex = 100;
    div.onmouseover = adb_mark_hit;
    div.onmouseout = adb_unmark_hit;
    div.onclick = adb_choose_hit;

    // Enable reacting on cursors / enter
    $(window).bind('keydown.drop', adb_check_keys);

    div.style.width = (document.getElementById(search_adb_field).offsetWidth-2) + 'px';
    div.style.border = '1px solid black';
    div.style.backgroundColor = 'white';

    for (var i in search_adb_cache) {
        var show_string = search_adb_cache[i].show_string;
        var fundstart = show_string.toLowerCase().indexOf(search_adb_for.toLowerCase());
        if (-1 == fundstart) continue;
        var fundende = fundstart + search_adb_for.length;
        var l = document.createElement('div');
        l.className = 'adbfound';
        l.id = 'hit_' + i;
        l.appendChild(document.createTextNode(show_string.substr(0, fundstart)));
        var s = document.createElement('strong');
        s.appendChild(document.createTextNode(show_string.substring(fundstart, fundende)));
        l.appendChild(s);
        l.appendChild(document.createTextNode(show_string.substring(fundende, show_string.length)));
        div.appendChild(l);
    }
    mycont.appendChild(div);
    // Select the first hit
    adb_select_hit(0);

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

function boilerplate_get(id)
{
    status_window('{msg_bplate_fetching}');
    $.ajax({'url': '{path_bplateget}' + id, 'type' : 'GET', 'dataType' : 'json', 'success' : AJAX_process});
}

function boilerplates_collapse()
{
    var id, span, cFid, level, sublevel, currlevel, newstyle, mode;

    span = $(this).parent();
    id = span.attr('id');
    cFid = this.id.replace(/^flist_fico_/, '');
    level = span.attr('name').replace(/^lvl_/, '');
    sublevel = 0;
    if (openlist[id] == 1) {
        $(this).removeClass('folder_opn_close').addClass('folder_opn_open');
        openlist[id] = 0;
        newstyle = 'block';
        mode = 'auf';
    } else {
        $(this).removeClass('folder_opn_open').addClass('folder_opn_close');
        openlist[id] = 1;
        newstyle = 'none';
        mode = 'zu';
    }
    do {
        span = span.next();
        if (span == null || span == false || span.length == 0) {
            break;
        }
        cFid = span.attr('id');
        currlevel = span.attr('name').replace(/^lvl_/, '');
        if (currlevel < sublevel) {
            sublevel = 0;
        } else if (sublevel > 0) {
            continue;
        }
        if (currlevel <= level) break;
        // Find possible subnodes, which are not affected on reopening
        if (mode == 'auf' && openlist[cFid] == 1) sublevel = currlevel + 1;
        span.css('display', newstyle);
    } while (1)
}

function boilerplate_insert(text)
{
    status_window();
    boilerplates_preview_remove();
    if (use_html) {
        $('#mbody').ckeditorGet().insertHtml(text);
    } else {
        thinedit.inserttext(text);
    }
}

function boilerplates_switcher()
{
    if (bplates_open) {
        $('#li_bplates').removeClass('open');
        $('#email_bplates_container').hide();
        bplates_open = false;
    } else {
        $('#li_bplates').addClass('open');
        $('#email_bplates_container').show();
        bplates_open = true;
    }
    $.ajax({'url': '{path_bplatesetopen}' + (bplates_open == true ? 1 : 0)});
}

function boilerplates_preview(id, posX, posY)
{
    evtSrc = [$('#bplate_plate_' + id), posX, posY];
    $.ajax({url: '{path_bplateget}' + id, type : 'GET', dataType : 'json', success : boilerplates_preview_draw});
}

function boilerplates_preview_draw(data)
{
    boilerplates_preview_remove();
    var HTML = $('<div id="bplate_preview_popup" class="renamebox shadowed" style="position:absolute;z-index:1000;padding:4px;"></div>');
    if (!use_html) {
        data['boilerplate'] = data['boilerplate'].replace(/\n/g, '<br />');
    }
    HTML.html(data['boilerplate']);
    var offSet = evtSrc[0].offset();
    HTML.css({'left': (evtSrc[1]+20) + 'px', 'top': (offSet.top-5) + 'px'}).appendTo('body');

    // Don't move off the bottom of the window (if possible!)
    if (HTML.offset().top + HTML.outerHeight() > $('body').innerHeight()) {
        HTML.css({top: (HTML.offset().top - (16 + HTML.offset().top + HTML.outerHeight() - $('body').innerHeight())) + 'px'});
    }
    bplatePreViewTO = window.setTimeout("$('#bplate_preview_popup').remove();", 5000);
}

function boilerplates_preview_remove()
{
    if (bplatePreViewTO != false) window.clearTimeout(bplatePreViewTO);
    $('#bplate_preview_popup').remove();
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
        $.each(['email1', 'email2'], function (id2, token) {
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

function updatewindowtitle(text) { document.title = text; }

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

// This is used for HTML mails and smileys [but not right now]
jQuery.fn.outerHTML = function(s) {
    return (s)
        ? this.before(s).remove()
        : jQuery('<p>').append(this.eq(0).clone()).html();
}
$(document).ready(function () {
    dragme.init();
    adjustMyHeight();<!-- START bplates_are_open -->
    boilerplates_switcher();<!-- END bplates_are_open --><!-- START contacts_are_open -->
    contacts_switcher();<!-- END contacts_are_open -->
    contacts_get_groups();

    $('#sel_fromprofile').bind('change keyup', function () {
        $('#sel_sendvcf').val($(this).find('option:selected').attr('class').replace(/^vcf_/, ''));
    });
    $('#email_bplates_container .bpfolder').mouseover(function () {
            if (bplateHoverTO != false) window.clearTimeout(bplateHoverTO);
            boilerplates_preview_remove();
        });
    $('#email_bplates_container .collapsable').click(boilerplates_collapse).click();
    $('#email_bplates_container .bplate').mouseover(function (event) {
            if (bplateHoverTO != false) window.clearTimeout(bplateHoverTO);
            bplateHoverTO = window.setTimeout('boilerplates_preview(' + this.id.replace(/^bplate_plate_/, '') + ', ' + event.pageX + ')', 1000);
        }).click(function () {
            if ($(this).hasClass('disable_html')) return false;
            boilerplate_get(this.id.replace(/^bplate_plate_/, ''));
        });

    var subj = document.getElementById('subject').value;
    if (subj) updatewindowtitle(subj);
    if (use_html) {
        $('#mbody').ckeditor(adjustMyHeight, {baseHref: _editor_url, language: _editor_lang, startupFocus: true, uiColor : themeBaseColour, toolbarStartupExpanded: true});
        $('#smiley_selector img').click(function(event) {
            // $('#mbody').ckeditorGet().insertHtml(' ' + $(this).outerHTML() + ' '); // Nice idea, but right now not feasible
            $('#mbody').ckeditorGet().insertHtml(' ' + this.title + ' '); // Fallback for now
        });
    } else {
        thinedit.start(document.getElementById('mbody'));
        pm_menu_additem('option', '', '{msg_rewrap_text}', 'thinedit.wordwrap();', 0, 0, 'js');
        $('#smiley_selector img').click(function(event) {
            thinedit.inserttext(' ' + this.title + ' ');
        });
    }

    $('#to,#cc,#bcc').focus(function () {
        addContactTarget = this.id;
    });

    $(window).keydown(function(event) {
        if (event.keyCode == 13 && (event.metaKey || event.ctrlKey)) {
            window.setTimeout('send_mail();', 1);
            event.preventDefault();
            return false;
        }
    }).resize(adjustMyHeight);
});
/*]]>*/
</script><!-- START send_html -->
<script type="text/javascript">
/*<![CDATA[*/
_editor_url  = '{frontend_path}/js/ckeditor/'
_editor_lang = '{user_lang}';
use_html = true;
content_type = 'text/html';
/*]]>*/
</script>
<script type="text/javascript" src="{frontend_path}/js/ckeditor/ckeditor.js?{current_build}"></script>
<script type="text/javascript" src="{frontend_path}/js/ckeditor/adapters/jquery.js?{current_build}"></script><!-- END send_html -->
<form action="{sendtarget}" method="post" id="sendform">
    <div id="oben">
        <div class="solid_line" id="topmenucontainer" style="text-align:left;">
            <table border="0" cellpadding="0" cellspacing="0">
                <tr class="solid_nodrop" id="pm_menu_container">
                    <td><a href="javascript:void(0);" class="active" id="topmendrop_option" onmouseover="pm_menu_create('option');" onclick="pm_menu_switch(this)">{msg_options}</a></td>
                    <td class="men_separator"></td>
                    <td><a href="javascript:void(0);" class="active" id="topmendrop_saveas" onmouseover="pm_menu_create('saveas');" onclick="pm_menu_switch(this)">{msg_saveas}</a></td>
                    <td class="men_separator"></td>
                    <td><a href="javascript:void(0);" class="active" id="topmendrop_prio" onmouseover="pm_menu_create('prio');" onclick="pm_menu_switch(this)">{msg_priority}</a></td>
                    <td class="men_separator"></td>
                    <td><a href="javascript:void(0);" class="active" id="topmendrop_attachments" onmouseover="pm_menu_create('attachments');" onclick="pm_menu_switch(this)">{msg_attachs}</a></td>
                </tr>
            </table>
        </div>
        <div class="outset">
            <div class="topbarcontainer">
                <ul class="l">
                    <li class="activebut" onclick="send_mail();">
                        <img src="{theme_path}/icons/send_but.gif" alt="" /><span>{msg_send}</span>
                    </li>
                    <li class="activebut">
                        {msg_sendvcf}:
                        <select size="1" name="WP_send[sendvcf]" id="sel_sendvcf">
                            <option value="none"<!-- START vcf_none --> selected="selected"<!-- END vcf_none -->>{msg_vcf_none}</option>
                            <option value="priv"<!-- START vcf_priv --> selected="selected"<!-- END vcf_priv -->>{msg_vcf_priv}</option>
                            <option value="busi"<!-- START vcf_busi --> selected="selected"<!-- END vcf_busi -->>{msg_vcf_busi}</option>
                            <option value="all"<!-- START vcf_all --> selected="selected"<!-- END vcf_all -->>{msg_vcf_all}</option>
                        </select>
                    </li>
                    <li class="activebut" onclick="open_signature();">
                        <img src="{theme_path}/icons/signature_but.gif" alt="" /><span>{msg_signature}</span>
                    </li>
                    <li class="activebut" id="li_smiley" onclick="open_smileys();" style="position:relative;">
                        <img src="{frontend_path}/smileys/smile.gif" alt="" />
                        <div id="smiley_selector" class="sendmenubut"><!-- START smileyselector -->
                            <img src="{frontend_path}/smileys{icon}" title="{emoticon}" alt="emoticon" /><!-- END smileyselector -->
                        </div>
                    </li>
                    <li class="activebut" id="li_contacts" onclick="contacts_switcher();">
                        <img src="{theme_path}/icons/contacts_but.gif" alt="" /><span>{msg_contacts}</span>
                    </li><!-- START boilerplates -->
                    <li class="activebut" id="li_bplates" onclick="boilerplates_switcher();">
                        <img src="{theme_path}/icons/boilerplate_men.gif" alt="" /><span>{msg_boilerplates}</span>
                    </li><!-- END boilerplates -->
                </ul>
            </div>
        </div>
        <div class="sendmenubut">
            <table border="0" cellpadding="2" cellspacing="0" width="100%">
                <tr>
                    <td class="l" width="85"><strong>{msg_from}:</strong></td>
                    <td class="l"><!-- START on_account -->
                        <select name="WP_send[from_profile]" id="sel_fromprofile" size="1" style="width:99%"><!-- START accmenu -->
                            <option class="vcf_{vcf}" value="{counter}"<!-- START selected --> selected="selected"<!-- END selected -->>{profilenm}</option><!-- END accmenu -->
                        </select><!-- END on_account --><!-- START one_account -->
                        {from}&nbsp;({address}) <input type="hidden" name="WP_send[from_profile]" value="{profile}"><!-- END one_account -->
                    </td>
                </tr>
                <tr>
                    <td class="l"><strong>{msg_to}:</strong></td>
                    <td class="l">
                        <div id="to_container" style="position:relative">
                            <input type="text" id="to" name="WP_send[to]" value="{to}" size="60" style="width:99%" autocomplete="off" onkeyup="search_adb('to', this.value);" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="l"><strong>CC:</strong></td>
                    <td class="l">
                        <div id="cc_container" style="position:relative">
                            <input type="text" id="cc" name="WP_send[cc]" value="{cc}" size="60" style="width:99%" autocomplete="off" onkeyup="search_adb('cc', this.value);" />
                        </div>
                    </td>
                </tr>
                <tr style="display: none;" id="bcc_line">
                    <td class="l"><strong>BCC:</strong></td>
                    <td class="l">
                        <div id="bcc_container" style="position:relative">
                            <input type="text" id="bcc" name="WP_send[bcc]" value="{bcc}" size="60" style="width:99%" autocomplete="off" onkeyup="search_adb('bcc', this.value);" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="l"><strong>{msg_subject}:</strong></td>
                    <td class="l">
                        <div id="subject_container">
                            <input type="text" name="WP_send[subj]" id="subject" value="{subject}" style="width:99%" size="60" onkeyup="updatewindowtitle(this.value);" maxlength="255" />
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td id="email_bplates_container" class="sendmenubut">
                        <div id="bplates_flist_container" class="sendmenuborder inboxline"><!-- START bplatelist --><!-- START bplate_folder -->
                            <div class="foldername bpfolder" id="bplate_flist_{id}" title="{name}" name="lvl_{level}">
                                <div class="folderlevel collapsable folder_opn_open" id="flist_fico_{id}" style="margin-left:{spacer}px;"></div>
                                <img class="foldericon" src="{theme_path}/icons/{icon}" alt="" />
                                <span class="name">{name}</span>
                            </div><!-- END bplate_folder --><!-- START bplate_plate -->
                            <div class="foldername bplate<!-- START disable_html --> disable_html<!-- END disable_html -->" id="bplate_plate_{id}" name="lvl_{level}">
                                <div class="folderlevel" style="margin-left:{spacer}px;"></div>
                                <img class="foldericon" src="{theme_path}/icons/{icon}" alt="" />
                                <span class="name">{name}</span>
                            </div><!-- END bplate_plate --><!-- END bplatelist -->
                        </div>
                    </td>
                    <td id="email_contacts_container" class="sendmenubut">
                        <div id="contacts_flist_container" class="sendmenuborder inboxline loading">
                        </div>
                    </td>
                    <td>
                        <textarea id="mbody" name="WP_send[body]" rows="10" cols="70" class="borderless_mbody" style="width:100%;height:1px;">{body}</textarea>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="sendattachcont">
        <table border="0" cellpadding="0" cellspacing="1">
            <tbody id="attlines">
            </tbody>
        </table>
    </div>
</form>
<div id="float_win_src" style="display:none;" class="floatwin_outline"><table border="0" cellpadding="0" cellspacing="0" class="floatwin_container"><tbody><tr><td onmousedown="float_drag(false, this)" class="floatwin_headline_l" width="98%"></td><td width="2%" class="floatwin_headline_r"><a href="">&nbsp;&nbsp;&nbsp;&nbsp;</a></td></tr><tr><td class="floatwin_content" colspan="2"></td></tr></tbody></table></div>
<div style="display:none;width:300px;height:190px;overflow:auto;" id="attachs">
<iframe width="100%" height="100%" src="{att_link}" frameborder="0"></iframe>
</div>
<div style="display:none;width:530px;height:420px;overflow:auto;" id="signature">
<iframe width="100%" height="100%" src="{sig_link}" frameborder="0">
</iframe>
</div>
<div id="sendstatus" class="sendmenubut shadowed" style="display:none;width:200px;height:40px;z-index:100;position:absolute;left:100px;top:100px;">
  <div class="c t" id="sendstat_msg"> </div>
  <div class="prgr_outer">
   <div class="prgr_inner_busy"></div>
  </div>
</div><!-- START origattachs -->
<script type="text/javascript">
/*<![CDATA[*/ <!-- START attline -->
addattach('{name}', '{filename}', '{small_icon}', 'orig', '{mimetype}');<!-- END attline --><!-- START hdlattline -->
addattach('{name}', '{filename}', '{small_icon}', 'user', '{mimetype}');<!-- END hdlattline -->
/*]]>*/
</script>
<!-- END origattachs -->