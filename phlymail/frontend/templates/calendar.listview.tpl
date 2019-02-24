<script type="text/javascript">
//<![CDATA[
PageLoaded = 0;
anzahl = 0;
avail_screen = 500;
markeditems = [];
lastfocus = false;
lastclicked = false;
curr_fetch = 0;
action_queue = [];
AJAX_url = false;
fetcher_url = '{fetcher_url}';
eventops_url = '{eventops_url}';
jsrequrl = '{jsrequrl}';
fetcher_list = [];
eventnum = 0;
actionpane_open = 0;
searchbar_open = 0;
skimbar_open = 0;
jumpbar_open = 0;
TOMarkRead = false;

urleventOps = '{eventops_url}';
urlEventFetcher = '{fetcher_url_2}';
urlEventView = '{viewlink}';
msgConfirmDelete = '{msg_killconfirm}';

ctxmen_id = false;
ctxmen =
    {0 : {'status' : 1, 'link' : 'set_boxes(1)', 'name' : '{msg_all}', 'icon' : '{theme_path}/icons/selall_ctx.gif'}
    ,1 : {'status' : 3, 'link' : 'set_boxes(0)', 'name' : '{msg_none}', 'icon' : '{theme_path}/icons/unselall_ctx.gif'}
    ,2 : {'status' : 1, 'link' : 'set_boxes(-1)', 'name' : '{msg_rev}', 'icon' : '{theme_path}/icons/invsel_ctx.gif'}
    ,3 : {'status' : 2 }
    ,4 : {'status' : 1, 'link' : 'parent.collect_and_react_calendar("delete", e)', 'name' : '{msg_dele} ...', 'icon' : '{theme_path}/icons/delete_ctx.gif'}
    };<!-- START ctx_new --><!-- END ctx_new --><!-- START ctx_delete -->
ctxmen[4]['status'] = 1;<!-- END ctx_delete -->
fieldinfo =
    {'starts' : { 'w' : 100, 'a' : 'l', 'ml' : 'starts' }
    ,'ends' : { 'w' : 100, 'a' : 'l', 'ml' : 'ends' }
    ,'repetitions' : { 'w' : 16, 'a' : '', 'ml' : 'repetitions' }
    ,'reminders' : { 'w' : 16, 'a' : '', 'ml' : 'reminders' }
    ,'reminders_sms' : { 'w' : 16, 'a' : '', 'ml' : 'reminders_sms' }
    ,'reminders_email' : { 'w' : 16, 'a' : '', 'ml' : 'reminders_email' }
    ,'title' : { 'w' : 0, 'a' : 'l', 'ml' : 'title' }
    ,'location' : { 'w' : 0, 'a' : 'l', 'ml' : 'location' }
    ,'description' : { 'w' : 0, 'a' : 'l', 'ml' : 'description' }
};

pagestats = {'eventnum' : '{neueingang}', 'allsize' : '{allsize}', 'rawallsize' : '{rawallsize}', 'showfields' : {showfields}, 'pagesize' : '{pagesize}'
        ,'page' : '{page}', 'pagenum' : '{pagenum}', 'maxpage' : '{boxsize}', 'orderby' : '{orderby}', 'orderdir' : '{orderdir}'
        ,'displaystart' : '{displaystart}', 'displayend' : '{displayend}', 'plural' : '{plural}'
        ,'use_preview': '{use_preview}', 'allow_resize': '{allow_resize}', 'viewlink': '{viewlink}'
        ,'customheight': '{customheight}', 'folder_writable': '{folder_writable}'};
eventlines = {<!-- START eventlines -->{notfirst}"{num}" : {data}<!-- END eventlines -->};

function build_eventlist()
{
    // Some updates in the markup
    update_pagestats();
    disable_jump();
    var myRight = htmlBiDi == 'ltr' ? 'right' : 'left';
    var myLeft = htmlBiDi == 'ltr' ? 'left' : 'right';
    // Go
    if (eventlines.length == 0) {
        $('#ico_refresh').hide();
        return;
    }
    var mthead = document.getElementById('eventthead');
    var mlines = document.getElementById('eventlines');
    mthead.className = 'listhead';
    topmenu = document.getElementById('topmen');
    var availwidth = mthead.offsetWidth-40;
    var verteilcount = 0;
    var verteilfields = [];

    for (var i in pagestats.showfields) {
        var d = document.createElement('div');
        d.id = 'mlh_' + i;
        d.className = 'lheadfield';
        d.style.width = fieldinfo[i].w + 'px';
        availwidth-=5; // Take the paddings and borders into account!
        if (fieldinfo[i].w == 0) {
            verteilcount++;
            verteilfields.push(i);
        } else {
            availwidth -= fieldinfo[i].w;
            pagestats.showfields[i].w = fieldinfo[i].w;
        }
        $(d).click(change_order);
        d.title = pagestats.showfields[i].t;
        if (pagestats.showfields[i]['i'] != '') {
            var img = document.createElement('img');
            img.src = urlThemePath+'/icons/' + pagestats.showfields[i]['i'];
            d.appendChild(img);
        } else {
            if (pagestats.orderby == i) {
                d.className = pagestats.orderdir == 'DESC' ? 'lheadfield ordup' : 'lheadfield orddw';
            }
            if (fieldinfo[i].a != '') {
                d.align = (fieldinfo[i].a == 'r') ? myRight : myLeft;
                d.style.backgroundPosition = (fieldinfo[i].a == 'r') ? myLeft : myRight;
            }
            d.appendChild(document.createTextNode(pagestats.showfields[i].n));
        }
        mthead.appendChild(d);
    }
    mthead.appendChild(d);

    // Evenly distribute avail width to flexwidth fields
    if (verteilcount > 0) {
        availwidth /= verteilcount;
        for (var i in verteilfields) {
            if (typeof (verteilfields[i]) != 'string') continue;
            document.getElementById('mlh_' + verteilfields[i]).style.width = Math.floor(availwidth) + 'px';
            pagestats.showfields[verteilfields[i]].w = Math.floor(availwidth);
        }
    }

    // Reset to really available space for the eventlines minus scrollbar
    availwidth = mthead.offsetWidth-36;

    for (var i in eventlines) {
        var cm = eventlines[i]; // Current event
        var r = document.createElement('div');
        r.id = 'ml_' + i;
        r.className = 'inboxline';
        r.oncontextmenu = function (e) { selectline(this.id.replace(/^ml_/, ''), e, true); }
        $(r).css({'width':(availwidth+4)+'px'}).click(function (e) {
            var src = this.id.replace(/^ml_/, '');
            selectline(src, e);
            lastclicked = src;
        }).dblclick(function (e) {
            var id = this.id.replace(/^ml_/, '');
            var event = eventlines[id].uidl;
            window.open(urlEventView + '&eid=' + event, 'eventread_' + id, 'width=450,height=560,left='
                    + (($(window).width()-450)/2).toString()
                    + ',top=' + (($(window).height()-560)/2).toString()
                    + ',scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no');
        });

        fc = 0;
        for (var j in pagestats.showfields) {
            var d = document.createElement('div');
            d.className = 'inboxfield';
            if (fc) d.className += ' inboxfspace';  // Spacer line
            d.style.width = pagestats.showfields[j].w + 'px';
            if (fieldinfo[j].a != '') {
                d.align = (fieldinfo[j].a == 'r') ? myRight : myLeft;
            }
            if (cm.is_unread == 1) d.style.fontWeight = 'bold';
            switch (j) {
            case 'reminders':
            case 'reminders_sms':
            case 'reminders_email':
            case 'repetitions':
                var img = document.createElement('img');
                if (cm[j] > 0) {
                    img.src = urlThemePath+'/icons/calendar_listview_yes.png';
                    img.alt = '{msg_yes}';
                    img.title = '{msg_yes}';
                    img.className = 'dragicon';
                    d.appendChild(img);
                } else {
                    d.appendChild(document.createTextNode(' '));
                }
                break;
            case 'title':
            case 'location':
            case 'description':
                d.appendChild(document.createTextNode(cm[j]));
                d.title = cm[j];
                break;
            case 'starts':
            case 'ends':
                d.appendChild(document.createTextNode(cm[j]));
                d.title = cm[(j + '_title')];
                break;
            default:
                d.appendChild(document.createTextNode(' '));
            }
            r.appendChild(d);
            fc++;
        }
        mlines.appendChild(r);
    }
    $('#ico_refresh').hide();
    
    // Mark the first mail in the list, don't override existing marks
    if ($.isEmptyObject(markeditems)) {
        $('#eventlines > :first-child').trigger('click');
    }
}

function empty_eventlist()
{
    $('#eventthead,#eventlines').empty();
}

function reapplymarks()
{
    var re = markeditems;
    var lastreapplied = false;
    anzahl = 0;
    markeditems = [];
    for (var i in re) {
        for (var j in eventlines) {
            if (eventlines[j].uidl == re[i]) {
                markline(j, true);
                lastreapplied = j;
                break;
            }
        }
    }
}

function update_pagestats()
{
    var jumpSize = (pagestats.maxpage.toString().length) ? (pagestats.maxpage.toString().length) : 1;
    $('#WP_jumppage').val((pagestats.page) ? pagestats.page : 1).attr('size', jumpSize).attr('maxlength', jumpSize);
    $('#pagenum').text((pagestats.maxpage == 0) ? '-' : pagestats.page + '/' + pagestats.maxpage);
    $('#folderinfo').attr('title', (pagestats.contactnum == 0) ? '' : pagestats.displaystart + ' - ' + pagestats.displayend + ' / ' + pagestats.eventnum);
    /*
    if (pagestats.use_preview == 1) {
        $('#preview').show();
    } else {
        $('#preview').hide();
    }
    if (pagestats.allow_resize == 1) {
        $('#resize_v').show();
    } else {
        $('#resize_v').hide();
    } */
    eventnum = (pagestats.displayend == pagestats.displaystart)
            ? (pagestats.displayend == 0 ? 0 : 1)
            : parseInt(pagestats.displayend)-parseInt(pagestats.displaystart)+1;
    if (skimbar_open == 1) { // Actually resets it
        open_skimbar();
        open_skimbar();
    }
}

function refreshlist(additional)
{
    $('#ico_refresh').show();
    if (!additional) additional = '';
    $.ajax({url: (jsrequrl + additional), dataType: 'json', success: AJAX_process, error: function (a, b, c) { alert(c);}});
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
        if (e.shiftKey) {
            refreshlist('&groupby=' + id);
            return;
        }
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
    var tb = document.getElementById('eventlines');
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

function markline(lineid, rescroll)
{
    if (markeditems[lineid]) {
        // unset mark
        delete markeditems[lineid];
        anzahl--;
        $('#ml_' + lineid).removeClass('marked');
    } else {
        var item = $('#ml_' + lineid);
        // set mark
        markeditems[lineid] = eventlines[lineid].uidl;
        anzahl++;
        item.addClass('marked');
        if (rescroll) {
            /* void */
        }
        var myOffset = item.get(0).offsetTop - $('#eventlines').offset()['top'];
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
    parent.setlinks(anzahl);
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
    var usedh;
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    } else {
        avail_screen = 500;
    }
    toph  = document.getElementById('topmen').offsetHeight;
    usedh = toph;
    mlines = document.getElementById('eventlines');
    mlines.style.height = (avail_screen - usedh) + 'px';
    topmenu = document.getElementById('topmen');
    if (PageLoaded && eventnum > 0) resize_eventlist();
}

function resize_eventlist()
{
    if (eventlines.length == 0) return;
    var mthead = document.getElementById('eventthead');
    var availwidth = mthead.offsetWidth-48;
    var verteilcount = 0;
    var verteilfields = [];
    var c, i, j;

    c = 0;
    for (i in pagestats.showfields) {
        mthead.childNodes[c].style.width = fieldinfo[i].w + 'px';
        availwidth-=4; // Take the paddings and borders into account!
        if (fieldinfo[i].w == 0) {
            verteilcount++;
            verteilfields.push(i);
        } else {
            availwidth -= fieldinfo[i].w;
            pagestats.showfields[i].w = fieldinfo[i].w;
        }
        c++;
    }
    // availwidth-=28;
    // Evenly distribute avail width to flexwidth fields
    if (verteilcount > 0) {
        availwidth /= verteilcount;
        for (i in verteilfields) {
            document.getElementById('mlh_' + verteilfields[i]).style.width = Math.floor(availwidth) + 'px';
            pagestats.showfields[verteilfields[i]].w = Math.floor(availwidth);
        }
    }
    for (i in eventlines) {
        var r = document.getElementById('ml_' + i);
        var linefields = [];
        for (c = 0; c < r.childNodes.length; ++c) {
            if (r.childNodes[c].className == 'inboxfspace') continue;
            linefields.push(c);
        }
        c = 0;
        for (j in pagestats.showfields) {
            r.childNodes[linefields[c]].style.width = Math.floor(pagestats.showfields[j].w) + 'px';
            c++;
        }
    }
}

function search_me()
{
    var pattern = encodeURIComponent($('#search_pattern_txt').val());
    var myaction = $('#searchform').attr('action');
    refreshlist('&pattern=' + pattern);
    return false;
}

function init_searchform()
{
    var pattern = (jsrequrl.match(/pattern\=([^\&]*)/)) ? RegExp.$1 : '';
    $('#search_pattern_txt').val(decodeURIComponent(pattern));
}

function AJAX_process(next)
{
    if (next['deleted']) {
        total_deleted = next['deleted'];
    }
    if (next['error']) {
        alert(next['error']);
    } else if (next['page_stats']) {
        pagestats = next['page_stats'];
        eventlines = next['eventlines'];
        jsrequrl = next['jsrequrl'];
        empty_eventlist();
        build_eventlist();
        reapplymarks();
    }
    if (next['done']) {
        $('#ico_dl').hide();
        $('#ico_search').hide();
    }
}

function fetchkey(e)
{
    var evt =  e || window.event;
    var key = (evt.which) ? evt.which : evt.keyCode;
    var fetched = false; // Pass on keycodes we did not fetch
    var exec = false; // Holds command to execute
    // React on pressed key
    switch (key) {
    case 35: // End
        fetched = true;
        if (eventnum > 0) {
            selectline('ml_' + eventnum, e);
        }
        break;
    case 36: // Home
        fetched = true;
        selectline('ml_1', e);
        break;
    case 38: // Cursor up
        fetched = true;
        var where = (!lastfocus) ? 1 : parseInt(lastfocus)-1;
        if (where < 1) break;
        selectline('ml_' + where, e);
        break;
    case 40: // Cursor down
        fetched = true;
        var where = (!lastfocus) ? 1 : parseInt(lastfocus)+1;
        if (where > eventnum) break;
        selectline('ml_' + where, e);
        break;
    case 46: // Entf (Del)
        parent.collect_and_react_calendar('delete', e);
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
    case 77: // M
        if (evt.shiftKey) {
            exec = 'parent.collect_and_react_calendar("unmark")';
            fetched = true;
        } else {
            exec = 'parent.collect_and_react_calendar("mark")';
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
        if (exec) window.setTimeout(exec, 1);
        return false;
    }
}

function switchview(what)
{
    if (what != 'daily' && what != 'weekly' && what != 'monthly' && what != 'yearly' && what != 'list') return false;
    self.location.href = '{lnk_switchview}' + what;
}

function jumpto(when)
{
    switch (when) {
        case 'today':   urladd = 'gototoday=1';      break;
        case 'nextevt': urladd = 'jumpto=nextevent'; break;
        case 'prevevt': urladd = 'jumpto=prevevent'; break;
    }
    self.location.href = '{PHP_SELF}?{passthrough}&l=ilist&h=calendar&' + urladd;
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
    $(window).resize();
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

function open_jumpbar()
{
    if (jumpbar_open == 0) {
        jumpbar_open = 1;
        $('#jump').addClass('open');
        $('#jumpbar').css('display', 'block');
        actionpane_open++;
    } else {
        jumpbar_open = 0;
        $('#jump').removeClass('open');
        $('#jumpbar').css('display', 'none');
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

$(document).ready(function () {
    resize_elements();
    build_eventlist();
    keyfetch_on();
    PageLoaded = 1;
});
$(window).resize(function () {
    resize_elements();
});
//]]>
</script>
<div id="topmen">
    <div id="buttonbar_bookmarks" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="activebut men_drop" id="search" onclick="open_searchbar();">
                    <img src="{theme_path}/icons/search.gif" alt="" /><span>{search}</span>
                </li>
            </ul>
            <ul class="r">
                <li class="activebut imgonly" onclick="switchview('daily');">
                    <img src="{theme_path}/icons/cal_dayview.png" alt="{msg_dayview}" title="{msg_dayview}" />
                </li>
                <li class="activebut imgonly" onclick="switchview('weekly');">
                    <img src="{theme_path}/icons/cal_weekview.png" alt="{msg_weekview}" title="{msg_weekview}" />
                </li>
                <li class="activebut imgonly" onclick="switchview('monthly');">
                    <img src="{theme_path}/icons/cal_monthview.png" alt="{msg_monthview}" title="{msg_monthview}" />
                </li>
                <li class="activebut imgonly" onclick="switchview('yearly');">
                    <img src="{theme_path}/icons/cal_yearview.png" alt="{msg_yearview}" title="{msg_yearview}" />
                </li>
                <li class="activebut imgonly open">
                    <img src="{theme_path}/icons/cal_listview.png" alt="{msg_listview}" title="{msg_listview}" />
                </li>
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
                <div id="skimslider" class="ui-slider" style="float:left;margin:0 4px;"></div>
                &nbsp;
                <form action="#" id="jumpform" method="get" style="display:inline" onsubmit="return jumppage();">
                    <span id="pagenum"> </span>&nbsp;
                    <input type="text" size="1" maxlength="1" id="WP_jumppage" name="WP_jumppage" value="" />&nbsp;
                    <input type="submit" id="submit_jump" value="{go}" />
                </form>
            </div>

            <div id="jumpbar" style="display:none;float:left;">
                <form action="{PHP_SELF}" method="get" id="calendar_jump_form">
                    {passthrough_2}
                    <input type="hidden" name="load" value="ilist" />
                    <input type="hidden" name="handler" value="calendar" />
                    <input type="text" name="goto_day" class="datepicker" value="{jumpto}" size="10" maxlength="12" />
                    <input type="submit" value="Go!" />
                </form>
            </div>

            <div id="searchbar" style="display:none;float:left;">
                <img src="{theme_path}/icons/search.gif" style="vertical-align:middle;" alt="{but_search}" />
                <form action="#" id="searchform" method="get" style="display:inline;" onsubmit="return search_me();">
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="submit" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
    <div id="eventthead" style="overflow:hidden;vertical-align:top;text-align:left;height:16px;"></div>
</div>
<div id="eventlines" style="overflow:auto;vertical-align:top;text-align:left;" onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();"></div>