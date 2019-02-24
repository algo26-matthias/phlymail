<script type="text/javascript">
//<![CDATA[
var anzahl = 0, markeditems = [], lastfocus = false,
        lastclicked = false, total_fetched = 0, curr_fetch = 0,
        action_queue = [], AJAX_url = false,
        fetcher_url = '{fetcher_url}',
        feedops_url = '{feedops_url}',
        jsrequrl = '{jsrequrl}',
        fetcher_list = [], rssnum = 0, actionpane_open = 0,
        searchbar_open = 0, skimbar_open = 0,ctxmen_id = false,
        pagestats = {};
ctxmen =
        {0 : {'status' : 1, 'link' : 'set_boxes(1)', 'name' : '{msg_all}', 'icon' : '{theme_path}/icons/selall_ctx.gif'}
        ,1 : {'status' : 3, 'link' : 'set_boxes(0)', 'name' : '{msg_none}', 'icon' : '{theme_path}/icons/unselall_ctx.gif'}
        ,2 : {'status' : 1, 'link' : 'set_boxes(-1)', 'name' : '{msg_rev}', 'icon' : '{theme_path}/icons/invsel_ctx.gif'}
        ,3 : {'status' : 2 }
        ,4:  {'status' : 1 ,'link' : 'collect_and_react_rss("mark")', 'name' : '{msg_markreadset}', 'icon' : '{theme_path}/icons/markread_ctx.gif'}
        ,5:  {'status' : 1 ,'link' : 'collect_and_react_rss("unmark")', 'name' : '{msg_markreadunset}', 'icon' : '{theme_path}/icons/markunread_ctx.gif'}
        ,6 : {'status' : 2 }
        ,7 : {'status' : 1, 'link' : 'collect_and_react_rss("delete")', 'name' : '{msg_dele}', 'icon' : '{theme_path}/icons/delete_ctx.gif'}
        };

function reapplymarks()
{
    var re = markeditems;
    anzahl = 0;
    markeditems = [];
    $('#rsslines > li').each(function () {
        var $this = $(this);
        for (var i in re) {
            if ($this.data('uidl') == re[i]) {
                markline(j);
                break;
            }
        }
    });
}

function update_pagestats()
{
    pagestats = {
        'preview' : $('#rsslines').data('preview'),
        'maxpage' : $('#rsslines').data('pages'),
        'page' : $('#rsslines').data('page'),
        'displaystart' : $('#rsslines').data('displaystart'),
        'displayend' : $('#rsslines').data('displayend'),
        'items' : $('#rsslines').data('items'),
    };
    var jumpSize = (pagestats.maxpage.toString().length) ? (pagestats.maxpage.toString().length) : 1;
    $('#WP_jumppage').val((pagestats.page) ? pagestats.page : 1).attr('size', jumpSize).attr('maxlength', jumpSize);
    $('#pagenum').text((pagestats.maxpage == 0) ? '-' : pagestats.page + '/' + pagestats.maxpage);
    $('#folderinfo').attr('title', (pagestats.items == 0) ? '' : pagestats.displaystart + ' - ' + pagestats.displayend + ' / ' + pagestats.items);
    rssnum = (pagestats.displayend == pagestats.displaystart)
            ? (pagestats.displayend == 0 ? 0 : 1)
            : parseInt(pagestats.displayend)-parseInt(pagestats.displaystart)+1;
    if (skimbar_open == 1) { // Actually resets it
        open_skimbar();
        open_skimbar();
    }
}

function refreshlist(additional)
{
    $('#ico_dl').show();
    $('#busy_fetching').css('visibility', 'visible');
    if (!additional) additional = '';

    // Hier $.load() benutzen

    return false;
    $.ajax({url: jsrequrl + additional, dataType: 'json', success: function (next) {
        pagestats = next['page_stats'];
        rsslines = next['rsslines'];
        jsrequrl = next['jsrequrl'];
        empty_rsslist();
        build_rsslist();
        reapplymarks();
    	}
    });
}

function ml_ctxmen(e)
{
    var src = !e ? event.srcElement : e.target;
    if (src.className.substr(0, 5) === 'inbox' || src.parentNode.className.substr(0, 5) === 'inbox') {
        if (src.parentNode.className.substr(0, 5) === 'inbox') {
            src = src.parentNode;
        }
    }
    selectline(src.id.replace(/^listitem_/, ''), e, true);
}

function set_boxes(anaus)
{
    var lineid = 0, lineno = 0, lines = 0;

    var anaus = anaus;

    $('.itemlist > li').each(function () {
        lines++;
        lineid = $(this).data('uidl');
        lineno = this.id.replace(/^listitem_/, '');
        if (anaus == 1 && typeof markeditems[lineno] === 'undefined') {
            markline(lineno);
        } else if (anaus == 0 && typeof markeditems[lineno] !== 'undefined') {
            markline(lineno);
        } else if (anaus == -1) {
            markline(lineno);
        }
    });
    if (lines > 0) {
        lastfocus = lineno;
    }
    if (anaus == 1) {
        ctxmen[0]['status'] = 3;
        ctxmen[1]['status'] = 1;
    }
    if (anaus == 0) {
        ctxmen[0]['status'] = 1;
        ctxmen[1]['status'] = 3;
    }
}

function selectline(lineno, e, onlythis)
{
    var zwischen;
    lineno = lineno.replace(/^listitem_/, '');
    if (onlythis) {
        if (!lastfocus) markline(lineno);
        return true;
    }
    if (!e && window.event) {
        e = window.event;
    }
    if ((!e.ctrlKey && !e.shiftKey) || (!lastfocus && e.shiftKey)) {
        set_boxes(0);
        lastfocus = lineno;
        markline(lineno);
    } else if (e.shiftKey) {
        var dfrom = lastfocus*1;
        var dto = lineno*1;
        set_boxes(0);
        lastfocus = dfrom;
        if (dfrom > dto) { zwischen = dto; dto = dfrom; dfrom = zwischen; }
        for (var i = dfrom; i <= dto; ++i) {
            if (!markeditems[i]) {
                markline(i);
            }
        }
    } else if (e.ctrlKey) {
        lastfocus = lineno;
        markline(lineno);
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

function markline(lineno, rescroll)
{
    var $ele = $('#listitem_' + lineno);
    if (typeof markeditems[lineno] !== 'undefined') {
        // unset mark
        delete markeditems[lineno];
        anzahl--;
        $ele.removeClass('marked');
    } else {
        // set mark
        markeditems[lineno] = lineno;
        anzahl++;
        $ele.addClass('marked');
        if (rescroll) {
            mlines.scrollTop = $ele.get(0).offsetTop - toph;
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
    $('.itemlist > li.marked').each(function() {
        var lineid = $(this).data('uidl');
        selected.push(lineid);
    });
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
    if (ud === '+') {
        dir++;
    } else if (ud === '-') {
        dir--;
    }
    refreshlist('&pagenum=' + dir);
}

function disable_jump()
{
    if (2 > pagestats.maxpage) {
       $('#submit_jump').attr('disabled', 'disabled');
    }
    $('#skimleft').css('visibility', (pagestats.page > 1) ? 'visible' : 'hidden');
    $('#skimright').css('visibility', (pagestats.page < pagestats.maxpage) ? 'visible' : 'hidden');
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

    var midh = avail_screen - toph;
    mlines = document.getElementById('rsslist_container');
    mlines.style.height = midh + 'px';
    topmenw = document.getElementById('topmen').offsetWidth;
}

function which_rssop()
{
    var sel = document.rssops.action;
    var val = sel.options[sel.selectedIndex].value;
    collect_and_react_rss(val);
    return false;
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
            rsslines = next['rsslines'];
            jsrequrl = next['jsrequrl'];
            empty_rsslist();
            build_rsslist();
            reapplymarks();
        }
    }
}

function search_me()
{
    var crit = document.getElementById('search_criteria');
    crit = crit.options[crit.selectedIndex].value;
    var pattern = encodeURIComponent(document.getElementById('search_pattern_txt').value);
    if (pattern.length === 0) {
        crit = '';
    }
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
        if (crit.options[i].value === criteria) {
            crit.selectedIndex = i;
        }
    }
    document.getElementById('search_pattern_txt').value = decodeURIComponent(pattern);
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
        if (rssnum > 0) {
            selectline('listitem_' + rssnum, e);
            preview(rssnum);
        }
        break;
    case 36: // Home
        fetched = true;
        selectline('listitem_1', e);
        preview(1);
        break;
    case 38: // Cursor up
        fetched = true;
        var where = (!lastfocus) ? 1 : parseInt(lastfocus)-1;
        if (where < 1) break;
        selectline('listitem_' + where, e);
        preview(where);
        break;
    case 40: // Cursor down
        fetched = true;
        var where = (!lastfocus) ? 1 : parseInt(lastfocus)+1;
        if (where > rssnum) break;
        selectline('listitem_' + where, e);
        preview(where);
        break;
    case 46: // Entf (Del)
        collect_and_react_rss("delete");
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
            exec = 'collect_and_react_rss("unmark")';
            fetched = true;
        } else {
            exec = 'collect_and_react_rss("mark")';
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
    resize_elements();
}

function open_searchbar()
{
    if (searchbar_open == 0) {
        searchbar_open = 1;
        $('#search').addClass('open');
        $('#searchbar').show();
        actionpane_open++;
    } else {
        searchbar_open = 0;
        $('#search').removeClass('open');
        $('#searchbar').hide();
        actionpane_open--;
    }
    open_actionpane();
}

function open_skimbar()
{
    if (skimbar_open == 0) {
        skimbar_open = 1;
        $('#skim').addClass('open');
        $('#skimbar').show();
        $('#skimslider').slider({min: 1, max: pagestats.maxpage, stepping: 1
                ,startValue: $('#WP_jumppage').val()
                ,slide: function (e, ui) { $('#WP_jumppage').val(ui.value); }
                ,change: function (e, ui) { if (pagestats.page != $('#WP_jumppage').val()) { jumppage(); } }
                });
        actionpane_open++;
    } else {
        skimbar_open = 0;
        $('#skim').removeClass('open');
        $('#skimbar').hide();
        $('#skimslider').slider('destroy');
        actionpane_open--;
    }
    open_actionpane();
}


function collect_and_react_rss(ops)
{
    list = get_selected_items();
    if (list.length == 0) return true;

    switch (ops) {
    case 'delete':
        var answer = confirm('{msg_killconfirm}');
        if (!answer) {
            return false;
        }
        drop_screen_selection();
        url = feedops_url + ops + '&alternate=1';
        for (ID in list) {
            url += '&item[]=' + list[ID];
        }
        $.ajax({url : url, dataType : 'json', success : refreshlist});
        break;
    case 'mark':
    case 'unmark':
        url = feedops_url + ops;
        for (ID in list) {
            url += '&item[]=' + list[ID];
        }
        $.ajax({ url : url, dataType : 'json', success : refreshlist});
        break;
    }
}

$(document).ready(function (e) {
    update_pagestats();

    $('.itemlist li').on('click', function (e) {
        selectline(this.id, e);
        e.preventDefault();
        e.stopPropagation();
        return false;
    }).on('dblclick', function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.open($(this).find('> a').attr('href'), '_blank');
        return false;
    });

    resize_elements();
    init_searchform();
    setlinks(0);
});
$(window).resize( function() { resize_elements(); });
if (window.captureEvenets) {
    window.onkeydown = fetchkey;
} else {
    document.onkeydown = fetchkey;
}
//]]>
</script>
<div id="topmen">
    <div id="buttonbar_email" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="notnull" onclick="collect_and_react_rss('delete');">
                    <img src="{theme_path}/icons/delete.gif" alt="" /><span>{but_dele}</span>
                </li>
                <li class="activebut men_drop" id="search" onclick="open_searchbar();">
                    <img src="{theme_path}/icons/search.gif" alt="" /><span>{search}</span>
                </li>
            </ul>
            <ul class="r">
                <li class="activebut" onclick="skim('-');" id="skimleft">
                    <img src="{theme_path}/icons/nav_left_big.gif" alt="" title="{but_last}" />
                </li>
                <li class="activebut" onclick="skim('+');" id="skimright">
                    <img src="{theme_path}/icons/nav_right_big.gif" alt="" title="{but_next}" />
                </li>
                <li class="activebut men_drop" id="skim" onclick="open_skimbar();">
                    <img src="{theme_path}/icons/page_men.gif" alt="{msg_page}" />
                    <span></span>
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
                    </select>
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="submit" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
</div>
<div id="rsslist_container" style="overflow:auto;vertical-align:top;text-align:left;" onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();">
    <ul id="rsslines" class="itemlist compact" data-preview="0" date-page="{page}" data-pages="{pages}" data-displaystart="{displaystart}"
            data-displayend="{displayend}" data-items="{items}"><!-- START item -->
        <li id="listitem_{num}" class="rss {item_classes}" data-uidl="{id}">
            <a href="{item_url}" target="_blank">
                <span class="icon xl"></span>
                <h3>
                    {item_title}
                </h3>
                <h5 title="{item_author_title}">
                    <span class="aside">{item_date}</span>
                    {item_author}
                </h5>
            </a>
        </li><!-- END item -->
    </ul>
</div>