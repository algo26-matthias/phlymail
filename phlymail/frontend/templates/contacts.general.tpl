<script type="text/javascript">
/*<![CDATA[*/
anzahl = 0;
markeditems = [];
preview_visible = 0;
lastfocus = false;
total_fetched = 0;
curr_fetch = 0;
action_queue = [];
AJAX_url = false;
fetcher_url = '{fetcher_url}';
contactops_url = '{contactops_url}';
jsrequrl = '{jsrequrl}';
fetcher_list = [];
contactnum = 0;
actionpane_open = searchbar_open = infobar_open = skimbar_open = 0;
ctxmen_id = false;
ctxmen =
        {0 : {'status' : 1, 'link' : 'set_boxes(1)', 'name' : '{msg_all}', 'icon' : '{theme_path}/icons/selall_ctx.gif'}
        ,1 : {'status' : 3, 'link' : 'set_boxes(0)', 'name' : '{msg_none}', 'icon' : '{theme_path}/icons/unselall_ctx.gif'}
        ,2 : {'status' : 1, 'link' : 'set_boxes(-1)', 'name' : '{msg_rev}', 'icon' : '{theme_path}/icons/invsel_ctx.gif'}
        ,3 : {'status' : 2 }
        ,4 : {'status' : 3, 'link' : 'do_contactops("make_public")', 'name' : '{msg_makepublic}', 'icon' : '{theme_path}/icons/public_contact.gif'}
        ,5 : {'status' : 3, 'link' : 'do_contactops("make_private")', 'name' : '{msg_makeprivate}', 'icon' : '{theme_path}/icons/personal_contact.gif'}
        ,6 : {'status' : 2}
        ,7 : {'status' : 3, 'link' : 'do_contactops("delete")', 'name' : '{msg_dele}', 'icon' : '{theme_path}/icons/delete_ctx.gif'}
        };
<!-- START ctx_global -->
ctxmen[4]['status'] = 1;
ctxmen[5]['status'] = 1;<!-- END ctx_global --><!-- START ctx_delete -->
ctxmen[7]['status'] = 1;<!-- END ctx_delete -->

fieldinfo =
    {'type' : {'w' : 24, 'a' : '', 'ml' : 'typetext'}
    ,'displayname' : {'w' : 0, 'a' : '', 'ml' : 'nick'}
    ,'nick' : {'w' : 0, 'a' : '', 'ml' : 'nick'}
    ,'firstname' : {'w' : 0, 'a' : '', 'ml' : 'firstname'}
    ,'lastname' : {'w' : 0, 'a' : '', 'ml' : 'lastname'}
    ,'company' : {'w' : 0, 'a' : '', 'ml' : 'company'}
    ,'displaymail' : {'w' : 0, 'a' : 'l', 'ml' : 'email1'}
    ,'email1' : {'w' : 0, 'a' : 'l', 'ml' : 'email1'}
    ,'email2' : {'w' : 0, 'a' : 'l', 'ml' : 'email2'}
    ,'displayphone' : {'w' : 0, 'a' : 'l', 'ml' : 'tel_private'}
    ,'tel_private' : {'w' : 0, 'a' : 'l', 'ml' : 'tel_private'}
    ,'tel_business' : {'w' : 0, 'a' : 'l', 'ml' : 'tel_business'}
    ,'cellular' : {'w' : 0, 'a' : 'l', 'ml' : 'cellular'}
    ,'fax' : {'w' : 0, 'a' : 'r', 'ml' : 'fax'}
    };
pagestats = {'contactnum' : '{neueingang}', 'showfields' : {showfields}, 'pagesize' : '{pagesize}', 'page' : '{page}', 'pagenum' : '{pagenum}'
        ,'maxpage' : '{boxsize}', 'orderby' : '{orderby}', 'orderdir' : '{orderdir}', 'displaystart' : '{displaystart}', 'displayend' : '{displayend}'
        ,'plural' : '{plural}', 'use_preview': '{use_preview}', 'allow_resize': '{allow_resize}', 'viewlink': '{viewlink}'
        ,'customheight': '{customheight}', 'folder_writable': '{folder_writable}'};
contactlines = {<!-- START contactlines -->{notfirst}{num} : {data}<!-- END contactlines -->};

function build_contactlist()
{
    // Some updates in the markup
    update_pagestats();
    disable_jump();
    // Go
    if (contactlines.length == 0) return;
    var mthead = document.getElementById('contactthead');
    mthead.className = 'listhead';
    var availwidth = topmenw;
    var verteilcount = 0;
    var verteilfields = [];

    for (var i in pagestats.showfields) {
        var d = document.createElement('div');
        d.id = 'mlh_' + i;
        d.className = 'lheadfield';
        d.style.width = fieldinfo[i].w + 'px';
        availwidth-=4; // Take the paddings and borders into account!
        if (fieldinfo[i].w == 0) {
            verteilcount++;
            verteilfields.push(i);
        } else {
            availwidth -= fieldinfo[i].w;
            pagestats.showfields[i].w = fieldinfo[i].w;
        }
        d.onclick = change_order;
        d.title = pagestats.showfields[i].t;
        if (pagestats.showfields[i]['i'] != '') {
            var img = document.createElement('img');
            img.src = '{theme_path}/icons/' + pagestats.showfields[i]['i'];
            d.appendChild(img);
        } else {
            if (pagestats.orderby == i) {
                d.className = pagestats.orderdir == 'DESC' ? 'lheadfield ordup' : 'lheadfield orddw';
            }
            if (fieldinfo[i].a != '') {
                d.align = (fieldinfo[i].a == 'r') ? 'right' : 'left';
                d.style.backgroundPosition = (fieldinfo[i].a == 'r') ? 'left' : 'right';
            }
            d.appendChild(document.createTextNode(pagestats.showfields[i].n));
        }
        mthead.appendChild(d);
    }
    // Free space to the right for the scrollbar
    var d = document.createElement('div');
    d.id = 'mlh_scroll';
    d.className = 'lheadfield nosort';
    d.style.width = '24px';
    d.appendChild(document.createTextNode(' '));
    availwidth-=28;
    mthead.appendChild(d);

    // Evenly distribute avail width to flexwidth fields
    if (verteilcount > 0) {
        availwidth /= verteilcount;
        for (var i in verteilfields) {
            document.getElementById('mlh_' + verteilfields[i]).style.width = Math.floor(availwidth) + 'px';
            pagestats.showfields[verteilfields[i]].w = Math.floor(availwidth);
        }
    }
    for (var i in contactlines) {
        var cm = contactlines[i]; // Current contact
        var r = document.createElement('div');
        r.className = 'inboxline';
        r.style.clear = 'both';
        r.id = 'ml_' + i;
        r.onclick = ml_click;
        r.ondblclick = ml_dblclick;
        r.oncontextmenu = ml_ctxmen;

        fc = 0;
        for (var j in pagestats.showfields) {
            if (fc) { // Spacer
                var d = document.createElement('div');
                d.className = 'inboxspacer';
                d.style.width = '2px';
                d.appendChild(document.createTextNode(' '));
                r.appendChild(d);
            }
            var d = document.createElement('div');
            d.className = 'inboxfield';
            d.style.width = pagestats.showfields[j].w + 'px';
            if (fieldinfo[j].a != '') {
                d.align = (fieldinfo[j].a == 'r') ? 'right' : 'left';
            }
            if ('type' == j) {
                var img = document.createElement('img');
                var typeicon = ('global' == cm.typeicon || 'personal' == cm.typeicon || 'private' == cm.typeiocn) ? cm.typeicon : 'public';
                img.src = '{theme_path}/icons/' + typeicon + '_contact.gif';
                img.alt = cm.typetext;
                img.title = cm.typetext;
                d.appendChild(img);
            } else {
                d.appendChild(document.createTextNode(cm[j]));
                d.title = cm[j];
            }
            r.appendChild(d);
            fc++;
        }
        mlines.appendChild(r);
    }
    
    // Mark the first mail in the list, don't override existing marks
    if ($.isEmptyObject(markeditems)) {
        $('#contactlines > :first-child').trigger('click');
    }
}

function empty_contactlist()
{
    $('#contactthead,#contactlines').empty();
}

function reapplymarks()
{
    var re = markeditems;
    anzahl = 0;
    markeditems = [];
    for (var i in re) {
        for (var j in contactlines) {
            if (contactlines[j].uidl == re[i]) {
                markline(j);
                break;
            }
        }
    }
}

function update_pagestats()
{
    var jumpSize = (pagestats.maxpage.toString().length) ? (pagestats.maxpage.toString().length) : 1;
    $('#WP_jumppage').val((pagestats.page) ? pagestats.page : 1).attr('size', jumpSize).attr('maxlength', jumpSize);
    if (skimbar_open == 1) { // Actually resets it
        open_skimbar();
        open_skimbar();
    }
    contactnum = (pagestats.displayend == pagestats.displaystart)
            ? (pagestats.displayend == 0 ? 0 : 1)
            : parseInt(pagestats.displayend)-parseInt(pagestats.displaystart)+1;
    $('#pagenum').text((pagestats.maxpage == 0) ? '-' : pagestats.page + '/' + pagestats.maxpage);
    $('#folderinfo').attr('title', (pagestats.contactnum == 0) ? '' : pagestats.displaystart + ' - ' + pagestats.displayend + ' / ' + pagestats.contactnum);
    if (pagestats.use_preview == 1) {
        $('#preview').show();
    } else {
        $('#preview').hide();
    }
    if (pagestats.allow_resize == 1) {
        $('#resize_v').show();
    } else {
        $('#resize_v').hide();
    }
}

function refreshlist(additional)
{
    $('#ico_dl').show();
    $('#busy_fetching').css('visibility', 'visible');
    if (!additional) additional = '';
    AJAX_call(jsrequrl + additional);
}

function ml_click(e)
{
    var src = !e ? event.srcElement : e.target;
    if (src.className.substr(0, 5) == 'inbox' || src.parentNode.className.substr(0, 5) == 'inbox') {
        if (src.parentNode.className.substr(0, 5) == 'inbox') {
            src = src.parentNode;
        }
    }
    selectline(src.id.replace(/^ml_/, ''), e);
    preview(src.id.replace(/^ml_/, ''));
}

function ml_dblclick(e)
{
    var src = !e ? event.srcElement : e.target;
    if (src.className.substr(0, 5) == 'inbox' || src.parentNode.className.substr(0, 5) == 'inbox') {
        if (src.parentNode.className.substr(0, 5) == 'inbox') {
            src = src.parentNode;
        }
        var id = src.id.replace(/^ml_/, '');
        var contact = contactlines[id].uidl;
        window.open('{edit_link}&id=' + contact, 'contact_' + id, 'width=510,height=470,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no');
    }
}

function ml_ctxmen(e)
{
    var src = !e ? event.srcElement : e.target;
    if (src.className.substr(0, 5) == 'inbox' || src.parentNode.className.substr(0, 5) == 'inbox') {
        if (src.parentNode.className.substr(0, 5) == 'inbox') {
            src = src.parentNode;
        }
    }
    selectline(src.id.replace(/^ml_/, ''), e, true);
}

function change_order(e)
{
    var src = !e ? event.srcElement : e.target;
    if (!e && window.event) e = window.event;
    if (src.className.substr(0, 10) == 'lheadfield' || src.parentNode.className.substr(0, 10) == 'lheadfield'
            || src.parentNode.parentNode.className.substr(0, 10) == 'lheadfield') {
        if (src.parentNode.parentNode && src.parentNode.parentNode.className.substr(0, 10) == 'lheadfield') {
            src = src.parentNode.parentNode;
        } else if (src.parentNode.className.substr(0, 10) == 'lheadfield') {
            src = src.parentNode;
        }
        var id = src.id.replace(/^mlh_/, '');
        if (id == pagestats.orderby) {
            var dir = pagestats.orderdir == 'ASC' ? 'DESC' : 'ASC';
        } else {
            var dir = 'ASC';
        }
        refreshlist('&orderby=' + id + '&orderdir=' + dir);
    }
}

function set_boxes(anaus)
{
    var tb = document.getElementById('contactlines');
    var tbl = tb.childNodes.length;
    for (var i = 0; i < tbl; ++i) {
        var child = tb.childNodes[i];
        if (child.nodeName != 'DIV') continue;
        if (!$(child).hasClass('inboxline')) continue;
        var lineid = child.id.replace(/^ml_/, '');
        if (typeof markeditems[lineid] != 'undefined') {
            if (anaus == 1 && !markeditems[lineid]) {
                markline(lineid);
            } else if (anaus == 0 && markeditems[lineid]) {
                markline(lineid);
            } else if (anaus == -1) {
                markline(lineid);
            }
        } else if (anaus != 0) {
            markline(lineid);
        }
    }
    if (tbl > 0) lastfocus = lineid;
    if (anaus == 1) {
        ctxmen[0]['status'] = 3;
        ctxmen[1]['status'] = 1;
    }
    if (anaus == 0) {
        ctxmen[0]['status'] = 1;
        ctxmen[1]['status'] = 3;
    }
}

function selectline(lineid, e, onlythis)
{
    var zwischen;
    lineid = lineid.replace(/^ml_/, '');
    if (onlythis) {
        if (!lastfocus) markline(lineid);
        return true;
    }
    if (!e && window.event) e = window.event;
    if ((!e.ctrlKey && !e.shiftKey) || (!lastfocus && e.shiftKey)) {
        set_boxes(0);
        lastfocus = lineid;
        markline(lineid);
    } else if (e.shiftKey) {
        var dfrom = lastfocus*1;
        var dto = lineid*1;
        set_boxes(0);
        lastfocus = dfrom;
        if (dfrom > dto) { zwischen = dto; dto = dfrom; dfrom = zwischen; }
        for (var i = dfrom; i <= dto; ++i) { if (!markeditems[i]) markline(i); }
    } else if (e.ctrlKey) {
        lastfocus = lineid;
        markline(lineid);
    }
    drop_screen_selection();
}

function drop_screen_selection()
{
    try { document.selection.empty(); } catch (e) {
        try { window.getSelection().collapseToStart(); } catch (e) {
            try { document.getSelection().collapseToStart(); } catch (e) { }
        }
    }
}

function markline(lineid)
{
    if (markeditems[lineid]) {
        // unset mark
        delete markeditems[lineid];
        anzahl--;
        $('#ml_' + lineid).removeClass('marked');
    } else {
        var item = $('#ml_' + lineid);
        // set mark
        markeditems[lineid] = contactlines[lineid].uidl;
        anzahl++;
        item.addClass('marked');
        var myOffset = item.get(0).offsetTop - $('#contactlines').offset()['top'];
        var myHeight = item.height();
        if ((myOffset + myHeight) > $(mlines).height() + mlines.scrollTop) {                
            mlines.scrollTop = myOffset + myHeight - $(mlines).height(); 
        } else if (myOffset < mlines.scrollTop) {                
            mlines.scrollTop = myOffset;
        }        
    }
    if (anzahl == 0) {
        lastfocus = false;
        markeditems = [];
    }
    // Let the topbuttonbar know, what's up
    setlinks(anzahl);
}

function get_selected_items()
{
    var selected = [];
    for (var i in markeditems) {
        selected.push(markeditems[i]);
    }
    return selected;
}

function jumppage()
{
    refreshlist('&jumppage=' + document.getElementById('WP_jumppage').value);
    return false;
}

function skim(ud)
{
    var dir = pagestats.pagenum;
    if (ud == '+') { dir++; } else if (ud == '-') { dir--; }
    refreshlist('&pagenum=' + dir);
}

function disable_jump()
{
    if (2 > pagestats.maxpage) {
        $('#skim').hide();
    } else {
        $('#skim').show();
    }
    if (pagestats.page > 1) {
        $('#skimleft').show();
    } else {
        $('#skimleft').hide();
    }
    if (pagestats.page < pagestats.maxpage){
        $('#skimright').show();
    } else {
        $('#skimright').hide();
    }
    if (pagestats.contactnum < 1) {
        $('#search').hide();
    } else {
        $('#search').show();
    }
}

function resize_elements()
{
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    } else {
        avail_screen = 500;
    }
    var toph  = document.getElementById('topmen').offsetHeight;

    if (document.getElementById('preview')) {
        preview_visible = 1;
        if (!parent.contacts_customheight_preview || parent.contacts_customheight_preview+toph > avail_screen * 0.95) {
            var prevheight = parseInt(avail_screen * 0.5);
            toph = toph + prevheight;
            var prev_cont = document.getElementById('preview_content');
            var prev_height = document.getElementById('preview').offsetHeight;
            prev_cont.style.height = (prevheight - prev_height) + 'px';
        } else {
            document.getElementById('preview_content').style.height = parent.contacts_customheight_preview + 'px';
            toph = toph + (parent.contacts_customheight_preview + document.getElementById('preview').offsetHeight);
        }
    }
    var midh = avail_screen - toph;
    mlines = document.getElementById('contactlines');
    mlines.style.height = midh + 'px';
    topmenw = document.getElementById('topmen').offsetWidth;
}

function do_contactops(which)
{
    collect_and_react_contacts(which);
}

function which_contactop()
{
    var sel = document.contactops.action;
    var val = sel.options[sel.selectedIndex].value;
    do_contactops(val);
    return false;
}

function preview(id)
{
    if (anzahl != 1) {
        document.getElementById('preview_content').src = 'about:blank';
        return;
    }
    if (!document.getElementById('preview_content')) return;
    if (!markeditems[id]) return;
    var uidl = contactlines[id].uidl;
    document.getElementById('preview_content').src = '{preview_url}' + uidl;
}

function AJAX_call(url)
{
    $.ajax({'url': url, 'success': AJAX_process});
}

function AJAX_process(next)
{
    if (next['deleted']) {
        total_deleted = next['deleted'];
    }
    if (next['error']) {
        alert(next['error']);
        return;
    } else {
        if (next['items']) {
            if (next['items'].length == 0) {
                update_counter();
            } else {
                fetcher_list = next['items'];
                update_counter(0, next['items'].length);
            }
        }
        if (next['done']) {
           update_counter();
        }
        if (next['page_stats']) {
            pagestats = next['page_stats'];
            contactlines = next['contactlines'];
            jsrequrl = next['jsrequrl'];
            empty_contactlist();
            build_contactlist();
            reapplymarks();
        }
    }
}

/**
* Customized drag object for resizing the preview window via mouse drag
*/
dragy =
        {dragobj: null
        ,oy: 0
        ,my: 0
        ,oldmove: (document.onmousemove) ? document.onmousemove : null
        ,oldup: (document.onmouseup) ? document.onmouseup : null
        ,start: function(e) {
                if (!e) e = window.event;
                if (e.target) targ = e.target;
	            else if (e.srcElement) targ = e.srcElement;
	            if (targ.nodeType == 3) // defeat Safari bug
                    targ = targ.parentNode;
                dragy.dragobj = targ;

	            if (e.pageY) {
		            dragy.oy = e.pageY;
	            } else if (e.clientY) {
		            dragy.oy = e.clientY + document.body.scrollTop;
	            }
                dragy.previewheight = document.getElementById('preview_content').offsetHeight;
                dragy.contactlinesheight = document.getElementById('contactlines').offsetHeight;
                document.onmousemove = dragy.drag;
                document.onmouseup = dragy.stop;
                if (preview_visible == 1) {
                    document.getElementById('preview_content').style.visibility = 'hidden';
                }
            }
        ,drag: function(e) {
                if (!e) e = window.event;
	            if (e.pageY) {
		            dragy.my = e.pageY;
	            } else if (e.clientY) {
		            dragy.my = e.clientY + document.body.scrollTop;
	            }
                // Object is given, pointer does not leave screen on top and left
                if (dragy.dragobj != null && dragy.my >= 0 && dragy.my < avail_screen) {
                    document.getElementById('contactlines').style.height = (dragy.contactlinesheight + (dragy.my - dragy.oy)) + 'px';
                    document.getElementById('preview_content').style.height = (dragy.previewheight - (dragy.my - dragy.oy)) + 'px';
                    parent.contacts_customheight_preview = document.getElementById('preview_content').offsetHeight;
                }
            }
        ,stop: function() {
                dragy.dragobj = null;
                document.onmouseup = (dragy.oldup) ? dragy.oldup : null;
                document.onmousemove = (dragy.oldmove) ? dragy.oldmove : null;
                parent.save_custom_size('contacts_previewheight', parent.contacts_customheight_preview);
                if (preview_visible == 1) {
                    document.getElementById('preview_content').style.visibility = 'visible';
                }
            }
        }

function search_me()
{
    var crit = document.getElementById('search_criteria');
    crit = crit.options[crit.selectedIndex].value;
    var pattern = encodeURIComponent(document.getElementById('search_pattern_txt').value);
    if (pattern.length == 0) crit = '';
    var myaction = document.getElementById('searchform').action;
    refreshlist('&criteria=' + crit + '&pattern=' + pattern);
    return false;
}

function init_searchform()
{
    var pattern = (jsrequrl.match(/pattern\=([^\&]*)/)) ? RegExp.$1 : '';
    criteria = (jsrequrl.match(/criteria\=([^\&]*)/)) ? RegExp.$1 : '';
    var crit = document.getElementById('search_criteria');
    for (var i = 0; i < crit.options.length; i++) {
        if (crit.options[i].value == criteria) {
            crit.selectedIndex = i;
        }
    }
    document.getElementById('search_pattern_txt').value = decodeURIComponent(pattern);
}

function collect_and_react_contacts(ops)
{
    list = get_selected_items();
    if (list.length == 0) return true;

    switch (ops) {
    case 'print':
        if (list.length != 1) return false;
        url = '{PHP_SELF}?{passthrough}&l=preview&h=contacts&print=1';
        for (var ID in list) {
            url += '&id=' + list[ID];
        }
        // Involve the read module
        window.open
                (url
                ,'print'
                ,'width=510,height=470,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
                );
        break;
    case 'delete':
        var answer = confirm('{msg_killconfirm}');
        if (!answer) return false;
        url = '{PHP_SELF}?{passthrough}&l=worker&h=contacts&what=contact_' + ops;
        for (var ID in list) {
            url += '&contact[]=' + list[ID];
        }
        parent.email_AJAX(url);
        break;
    case 'make_public':
    case 'make_private':
        url = '{PHP_SELF}?{passthrough}&l=worker&h=contacts&what=contact_visibility&visible=' + (ops == 'make_public' ? 'public' : 'private');
        for (var ID in list) {
            url += '&contact[]=' + list[ID];
        }
        parent.email_AJAX(url);
        break;
    }
}

function fetchkey(e)
{
    var evt =  e || window.event;
    var key = (evt.which) ? evt.which : evt.keyCode;
    var fetched = false;
    // React on pressed key
    switch (key) {
    case 35: // End
        fetched = true;
        if (contactnum > 0) {
            var lineID = $('#contactlines :last-child').attr('id');
            selectline(lineID, e);
            preview(lineID.replace(/^ml_/, ''));
        }
        break;
    case 36: // Home
        fetched = true;
        if (contactnum > 0) {
            var lineID = $('#contactlines :first-child').attr('id');
            selectline(lineID, e);
            preview(lineID.replace(/^ml_/, ''));
        }
        break;
    case 38: // Cursor up
        fetched = true;
        if (!lastfocus) {
            var lineID = $('#contactlines :first-child').attr('id');
        } else {
            var where = $('#ml_' + lastfocus).prev();
            if (where.length == 0) {
                break;
            }
            var lineID = where.attr('id');
        }
        selectline(lineID, e);
        preview(lineID.replace(/^ml_/, ''));
        break;
    case 40: // Cursor down
        fetched = true;
        if (!lastfocus) {
            var lineID = $('#contactlines :first-child').attr('id');
        } else {
            var where = $('#ml_' + lastfocus).next();
            if (where.length == 0) {
                break;
            }
            var lineID = where.attr('id');
        }
        selectline(lineID, e);
        preview(lineID.replace(/^ml_/, ''));
        break;
    case 46: // Entf (Del)
        do_contactops("delete");
        fetched = true;
        break;
    case 65: // A
        if (evt.ctrlKey && evt.shiftKey) {
            set_boxes(0);
            fetched = true;
        } else if (evt.ctrlKey) {
            set_boxes(1);
            fetched = true;
        }
        break;
    case 80: // P
        if (evt.ctrlKey) {
            do_contactops("print");
            fetched = true;
        }
        break;
    }
    if (fetched) {
        if (window.event) {
            evt.cancelBubble = true;
        } else if (evt.preventDefault) {
            evt.preventDefault();
        } else {
            evt.stopPropagation();
        }
        evt.returnValue = false;
        return false;
    }
}

function keyfetch_on()
{
    if (window.captureEvenets) {
        window.onkeydown = fetchkey;
    } else {
        document.onkeydown = fetchkey;
    }
}

function keyfetch_off()
{
    if (window.captureEvenets) {
        window.onkeydown = null;
    } else {
        document.onkeydown = null;
    }
}

function open_actionpane()
{
    if (actionpane_open < 1) {
        $('#actionpane').removeClass('open');
        actionplane_open = 0; // Prevent negative values;
    } else {
        $('#actionpane').addClass('open');
    }
}

function open_searchbar()
{
    if (searchbar_open == 0) {
        searchbar_open = 1;
        $('#search').addClass('open');
        $('#searchbar').css('display', 'block');
        actionpane_open++;
    } else {
        searchbar_open = 0;
        $('#search').removeClass('open');
        $('#searchbar').css('display', 'none');
        actionpane_open--;
    }
    open_actionpane();
}

function open_skimbar()
{
    if (skimbar_open == 0) {
        skimbar_open = 1;
        $('#skim').addClass('open');
        $('#skimbar').css('display', 'block');
        $('#skimslider').slider(
                {"min": 1
                ,"max": pagestats.maxpage
                ,"stepping": 1
                ,"value": pagestats.page
                ,"slide": function (e, ui) { $('#WP_jumppage').val(ui.value); }
                ,"change": function (e, ui) { if (pagestats.page != $('#WP_jumppage').val()) { jumppage(); } }
                });
        actionpane_open++;
    } else {
        skimbar_open = 0;
        $('#skim').removeClass('open');
        $('#skimbar').css('display', 'none');
        $('#skimslider').slider('destroy');
        actionpane_open--;
    }
    open_actionpane();
}

$(document).ready(function (e) {
    resize_elements();
    build_contactlist();
    init_searchform();
    setlinks(0);
});
$(window).resize(function() { resize_elements(); });
if (window.captureEvenets) {
    window.onkeydown = fetchkey;
} else {
    document.onkeydown = fetchkey;
}
/*]]>*/
</script>
<div id="topmen">
    <div id="buttonbar_bookmarks" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="single" onclick="collect_and_react_contacts('print');">
                    <img src="{theme_path}/icons/print_men.gif" alt="" /><span>{but_print}</span>
                </li>
                <li class="notnull" onclick="collect_and_react_contacts('delete');">
                    <img src="{theme_path}/icons/delete.gif" alt="" /><span>{but_dele}</span>
                </li>
                <li class="activebut men_drop" id="search" onclick="open_searchbar();">
                    <img src="{theme_path}/icons/search.gif" alt="" /><span>{but_search}</span>
                </li>
            </ul>
            <ul class="r">
                <li class="activebut" onclick="skim('-');" id="skimleft">
                    <img src="{theme_path}/icons/nav_left_big.gif" alt="" title="{but_last}" />
                </li>
                <li class="activebut" onclick="skim('+');" id="skimright">
                    <img src="{theme_path}/icons/nav_right_big.gif" alt="" title="{but_next}" />
                </li>
                <li class="activebut imgonly men_drop" id="skim" onclick="open_skimbar();">
                    <img src="{theme_path}/icons/page_men.gif" alt="{msg_page}" />
                </li>
                <li class="activebut imgonly" id="folderinfo">
                    <img src="{theme_path}/icons/about_men.gif" alt="i" />
                </li>
            </ul>
        </div>
        <div id="actionpane" class="actionpane">
            <div id="skimbar" style="display:none;float:right;">
                <img src="{theme_path}/icons/page_men.gif" style="float:left;" alt="{msg_page}" />
                <div id="skimslider" class="ui-slider" style="float:left;margin:0 4px;">
                </div>
                &nbsp;
                <form action="#" id="jumpform" method="get" style="display:inline" onsubmit="return jumppage();">
                    <span id="pagenum"> </span>&nbsp;
                    <input type="text" size="1" maxlength="1" id="WP_jumppage" name="WP_jumppage" value="" />&nbsp;
                    <input type="submit" id="submit_jump" value="{go}" />
                </form>
            </div>

            <div id="searchbar" style="display:none;float:left;">
                <img src="{theme_path}/icons/search.gif" style="vertical-align:middle;" alt="{but_search}" />
                <form action="#" id="searchform" method="get" style="display:inline;" onsubmit="return search_me();">
                    <select size="1" name="criteria" id="search_criteria">
                        <option value="name">{msg_name}</option>
                        <option value="address">{msg_address}</option>
                        <option value="company">{msg_company}</option>
                        <option value="email">{msg_email}</option>
                        <option value="phone">{msg_phone}</option>
                    </select>
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="submit" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
    <div id="contactthead" style="overflow:hidden;vertical-align:top;text-align:left;height:16px;"></div>
</div>
<div id="contactlines" style="overflow:auto;vertical-align:top;text-align:left;" onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();"></div><!-- START nocontacts --><div class="emptymailbox">{nonewcontact}</div><!-- END nocontacts --><!-- START preview -->
<div id="preview">
    <div class="sendmenubut"<!-- START allowresize --> style="cursor:n-resize;" onmousedown="dragy.start(event);"<!-- END allowresize -->>
        <span style="font-size:0;">&nbsp;</span>
    </div>
</div>
<iframe width="100%" height="100%" id="preview_content" scrolling="auto" frameborder="0">
</iframe>
<!-- END preview -->