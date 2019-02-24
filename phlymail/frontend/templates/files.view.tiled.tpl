<script type="text/javascript">
/*<![CDATA[*/
anzahl = 0;
avail_screen = 500;
markeditems = [];
myurl = window.location;
lastfocus = false;
total_fetched = 0;
total_deleted = 0;
curr_fetch = 0;
action_queue = [];
AJAX_url = false;
itemops_url = '{itemops_url}';
fetcher_list = [];
idToIdx = []
actionpane_open = 0;
searchbar_open = 0;
skimbar_open = 0;
ctxmen =
        {0:{'status':1,'link':'setBoxes(1)','name':'{msg_all}','icon':'{theme_path}/icons/selall_ctx.gif'}
        ,1:{'status':3,'link':'setBoxes(0)','name':'{msg_none}','icon':'{theme_path}/icons/unselall_ctx.gif'}
        ,2:{'status':1,'link':'setBoxes(-1)','name':'{msg_rev}','icon':'{theme_path}/icons/invsel_ctx.gif'}
        ,3:{'status':2}
        ,4:{'status':3,'link':'prerename()','name':'{msg_rename} ...','icon':'{theme_path}/icons/files_rename.gif'}
        ,5:{'status':3,'link':'collect_and_react_files("precopy",e)','name':'{msg_copy} ...','icon':'{theme_path}/icons/copytofolder_ctx.gif'}
        ,6:{'status':3,'link':'collect_and_react_files("premove",e)','name':'{msg_move} ...','icon':'{theme_path}/icons/movetofolder_ctx.gif'}
        ,7:{'status':1,'link':'sendasmail()','name':'{msg_sendasmail} ...','icon':'{theme_path}/icons/files_sendasmail.gif'}
        ,8:{'status':2}
        ,9:{'status':1,'link':'collect_and_react_files("save",e)','name':'{msg_save}','icon':'{theme_path}/icons/files_download.gif'}
        ,10:{'status':2}
        ,11:{'status':3,'link':'collect_and_react_files("delete",e)','name':'{del}','icon':'{theme_path}/icons/delete_ctx.gif'}
        };<!-- START ctx_rename -->
ctxmen[4]['status'] = 1;<!-- END ctx_rename --><!-- START ctx_copy -->
ctxmen[5]['status'] = 1;<!-- END ctx_copy --><!-- START ctx_move -->
ctxmen[6]['status'] = 1;<!-- END ctx_move --><!-- START ctx_delete -->
ctxmen[11]['status'] = 1;<!-- END ctx_delete -->
ctxmen_id = false;

pagestats = {"use_preview":"{use_preview}","allow_resize":"{allow_resize}","viewlink":"{viewlink}","customheight":"{customheight}","folder_writable":"{folder_writable}", 'page' : {page}, 'pagenum' : {pagenum}, 'maxpage' : {boxsize}};

function refreshlist(additional)
{
    self.location.reload();
}

function fetchkey(e)
{
    var evt =  e || window.event;
    var key = (evt.which) ? evt.which : evt.keyCode;
    var fetched = false; // Pass on keycodes we did not fetch
    var exec = false; // Holds command to execute
    // React on pressed key
    switch (key) {
    case 46: // Entf (Del)
        collect_and_react_files('delete', e);
        fetched = true;
        break;
    case 65: // A
        if (evt.ctrlKey && evt.shiftKey) {
            setBoxes(0);
            fetched = true;
        } else if (evt.ctrlKey) {
            setBoxes(1);
            fetched = true;
        }
        break;
    case 67: // C
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_files("precopy")';
            fetched = true;
        }
        break;
    case 82: // R
        if (evt.ctrlKey && evt.shiftKey) {
            prerename();
            fetched = true;
        }
        break;
    case 83: // S
        if (evt.ctrlKey && evt.shiftKey) {
            sendasmail();
            fetched = true;
        } else if (evt.ctrlKey) {
            exec = 'collect_and_react_files("save")';
            fetched = true;
        }
        break;
    case 86: // V
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_files("premove")';
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

function create_folder_browser(operation, handler)
{
    var myleft = 200;
    var mytop = 200;
    browserwin = window.open
            ('{PHP_SELF}?l=browse&h=' + handler + '&mode=' + operation + '&{passthrough}'
            ,'browser_{id}'
            ,'width=450,height=400,left=' + myleft + ',top=' + mytop
                    + ',scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function submit_folder_browser(folder, handler, ops)
{
    opsfolder = folder;
    browserwin.close();
    eval('collect_and_react_' + handler + '("' + ops + '")');
}

function prerename()
{
    if (1 != anzahl) return;
    var ID, orign, newn;
    for (ID in markeditems) {
        if (markeditems[ID] != 1) continue;
        break;
    }
    orign = document.getElementById(ID).title;
    newn = prompt('{msg_renameto}', orign);
    if (newn == null || newn.length == 0 || newn == orign) return;
    fileaction('rename&newname=' + encodeURIComponent(newn));
}

function sendasmail()
{
    if (0 == anzahl) return;
    var URL = '';
    for (var ID in markeditems) {
        if (markeditems[ID] != 1) continue;
        URL += '&item[]=' + idToIdx[(ID.replace(/^line_/, ''))];
    }
    window.open
            ('{sendasmail_url}' + URL
            ,'sendasmail_' + new Date().getTime()
            ,'width=700,height=500,scrollbars=no,resizable=yes,statusbar=yes,locationbar=no,personalbar=no'
            );
}

function setBoxes(anaus)
{
    var tb = document.getElementById('itempane');
    var tbl = tb.childNodes.length;
    for (var i = 0; i < tbl; ++i) {
        var child = tb.childNodes[i];
        if (child.nodeName != 'DIV') continue;
        var lineid = child.id;
        if (typeof markeditems[lineid] != 'undefined') {
            if (anaus == 1 && markeditems[lineid] == null) {
                markline(lineid);
            } else if (anaus == 0 && markeditems[lineid] == 1) {
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

function selectline(lineid, e, onlythis) {
    if (onlythis) {
        if (!lastfocus) markline(lineid);
        return true;
    }
    if (!e && window.event) e = window.event;
    if ((!e.ctrlKey && !e.shiftKey) || (!lastfocus && e.shiftKey)) {
        setBoxes(0);
        lastfocus = lineid;
        markline(lineid);
    } else if (e.shiftKey) {
        var dfrom = lastfocus.replace(/line_/, '');
        var dto = lineid.replace(/line_/, '');
        setBoxes(0);
        lastfocus = 'line_' + dfrom;
        if (dfrom > dto) { zwischen = dto; dto = dfrom; dfrom = zwischen; }
        for (var i = dfrom; i <= dto; ++i) {
            dline = 'line_' + i;
            if (typeof(markeditems[dline]) != 'undefined') {
                if (markeditems[dline] == null) markline(dline);
            } else {
                markline(dline);
            }
        }
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
    if (markeditems[lineid] == 1) { // unset mark
        markeditems[lineid] = null;
        anzahl--;
        $('#' + lineid).removeClass('selected');
    } else { // set mark
        markeditems[lineid] = 1;
        anzahl++;
        $('#' + lineid).addClass('selected');
    }
    // Hiding unavailable options or show available ones respectively
    if (anzahl == 1) {
        ctxmen[4]['status'] = 1;
        ctxmen[8]['status'] = 2;
        ctxmen[9]['status'] = 1;
    } else {
        ctxmen[4]['status'] = 3;
        ctxmen[8]['status'] = 3;
        ctxmen[9]['status'] = 3;
    }

    if (0 == anzahl) lastfocus = false;
    setlinks(anzahl); // Let the topbuttonbar know what's up
}

function get_selected_items()
{
    selected = [];
    for (var ID in markeditems) { if (markeditems[ID] == 1) { selected.push(idToIdx[(ID.replace(/^line_/, ''))]); } }
    return selected;
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
    avail_screen = 500;
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    }
    $('#itempane').css('height', avail_screen + 'px');
    $('#jobspane').css('height', avail_screen + 'px');
}

function jumppage()
{
    self.location.href = $('#jumpform').attr('action') + '&WP_core_jumppage=' + $('#WP_core_jumppage').val();
    return false;
}

function search_me()
{
    var pattern = encodeURIComponent($('#search_pattern_txt').val());
    var myaction = $('#searchform').attr('action');
    myaction = myaction.replace(/pattern\=([^\&]*)/, '');
    self.location.href = myaction + '&pattern=' + pattern;
    return false;
}

function init_searchform()
{
    var pattern = (window.location.search.match(/pattern\=([^\&]*)/)) ? RegExp.$1 : '';
    $('#search_pattern_txt').val(decodeURIComponent(pattern));
}

function preview(iid, uid, mimeicon)
{
    if (0 == iid) {
        $('#previewmimeicon').attr('src', '{theme_path}/icons/files.fileprops_men.png');
        $('#previewfilename').attr('title', '');
        $('#previewthumb,#previewfilename,#previewmimename,#previewdimensions,#previewfilesize,#previewlastchange').empty();
        return;
    }
    // AJAX-Request zum Dateiinfo holen
    $.ajax({"url": '{iteminfo_url}' + uid, "success": AjaxProcess, "dataType": 'json'});
    if (mimeicon == '') {
        $('#previewmimeicon').attr('src', '{theme_path}/icons/files.fileprops_men.png');
    } else {
        $('#previewmimeicon').attr('src', '{frontend_path}/filetypes/16/' + mimeicon);
    }
    $('#previewthumb').html('<img src="' + $('#ithumb_' + iid).attr('src').replace(/\/64\//, '/128/') + '" alt="" title="" />');
    var filename = $('#fname_' + iid).text();
    $('#previewfilename').text(filename).attr('title', filename);
}

function preview_iteminfo(info)
{
    $('#previewmimename').html(info['mimename'] ? info['mimename'] : '');
    $('#previewdimensions').html(info['dimensions'] ? info['dimensions'] : '');
    $('#previewfilesize').html(info['filesize'] ? info['filesize'] : '');
    $('#previewlastchange').html(info['lastchange'] ? info['lastchange'] : '');
    if (info['thumburl']) {
        $('#previewthumb').html('<img src="' + info['thumburl'] + '" alt="" title="" />');
    }
}

function dlfile(iid)
{
    self.location.href = '{dlfile_url}' + iid;
}

function AjaxProcess(next)
{
    if (next === null) return;

    if (next['deleted']) total_deleted = next['deleted'];
    if (next['error']) alert(next['error']);
    if (next['items']) {
        if (next['items'].length == 0) {
            update_counter();
        } else {
            fetcher_list = next['items'];
            update_counter(0, next['items'].length);
        }
    }
    if (next['iteminfo']) preview_iteminfo(next['iteminfo']);
    if (next['upload_stats']) upload_progress_draw(next['upload_stats']);
    if (next['done']) update_counter();
}

function fileaction(ops, alternate, opsfolder)
{
    if (fetcher_list.length > 0) return false;
    var selected = [];
    for (var ID in markeditems) { if (markeditems[ID] == 1) selected.push(idToIdx[(ID.replace(/^line_/, ''))]); }
    $('#busy_fetching').css('visibility', 'visible');
    $('#ico_search').show();
    AJAX_url = itemops_url + ops;
    if (opsfolder) AJAX_url += '&folder=' + opsfolder;
    if (alternate) AJAX_url += '&alternate=1';
    fetcher_list = selected;
    AJAX_action = 'itemops';
    update_counter(0, fetcher_list.length);
}

function update_counter(curr, total)
{
    if (total) {
        total_fetched = total;
        if (AJAX_action == 'fetch') {
            $('#ico_search').hide();
            $('#ico_dl').show();
        }
        $('#fetch_all').text(total_fetched);
    }
    if (!curr) curr_fetch++;
    $('#fetch_curr').text(curr_fetch);
    if (curr_fetch <= total_fetched) {
        if (AJAX_action == 'itemops') {
            $.ajax({url:AJAX_url+'&item='+encodeURIComponent(fetcher_list[(curr_fetch-1)]), success:AjaxProcess, dataType:'json'});
        }
    } else {
        curr_fetch = 0;
        $('#busy_fetching').css('visibility', 'hidden');
        if (AJAX_action == 'fetch') {
            $('#ico_dl').hide();
        } else {
            $('#ico_search').hide();
        }
        if (total_fetched != 0 || total_deleted != 0) window.location.reload();
    }
}

function upload_progress()
{
    $.ajax({url:'{upload_progress_url_js}{UL_ID}', success: AjaxProcess, dataType:'json'});
}

function upload_progress_draw(data)
{
    if (data['bytes_uploaded'] > 0 && data['bytes_total'] > 0) {
        if (data['bytes_uploaded'] >= data['bytes_total']) {
            $('#busy_fetching').css('visibility', 'hidden');
            $('#prgr_inner_busy').css('width', '100%');
            return;
        }
        pleasewait_off();
        data['speed_average'] = parseInt(data['speed_average']/1024*10)/10;
        var Hs = parseInt(data['est_sec']/3600);
        var Ms = parseInt((data['est_sec']+Hours*3600)/60);
        var Ss = data['est_sec']-(Mins*60 + Hours*3600);
        data['est_sec'] = Hs + ':' + (Ms < 10 ? '0' : '') + Ms + ':' + (Ss < 10 ? '0' : '') + Ss;
        $('#busy_fetching').css('visibility', 'visible');
        $('#prgr_inner_busy').css('width', (100*data['bytes_uploaded']/data['bytes_total']) + '%');
        $('#fetch_curr').text(parseInt(data['bytes_uploaded']/1024));
        $('#fetch_all').text(parseInt(data['bytes_total']/1024));
        $('#kb_p_sec').text(data['speed_average']);
        $('#time_remain').text(data['est_sec']);
    } else {
        pleasewait_on();
    }
    window.setTimeout('upload_progress();', 5000);
}

function pleasewait_on()
{
    $('#pleasewait').show();
}

function pleasewait_off()
{
    $('#pleasewait').hide();
}

function nw_audioplayer(ID)
{
    window.open
            ('{audioplayer_win_url}' + ID
            ,'files_audioplayer'
            ,'width=400,height=80,scrollbars=no,resizable=yes,statusbar=no,locationbar=no,personalbar=no'
            );
}

function collect_and_react_files(ops, e)
{
    var list = get_selected_items();
    if (list.length == 0) ops = false;
    var alternate = 0;

    switch (ops) {
    case 'save':
        if (list.length != 1) return false;
        // Involve the read module
        url = '{PHP_SELF}?{passthrough}&h=files&l=output&save_as=raw';
        for (var ID in list) { url += '&item=' + list[ID]; }
        self.location.href = url;
        break;
    case 'precopy':
        create_folder_browser('copy', 'files');
        break;
    case 'premove':
        create_folder_browser('move', 'files');
        break;
    case 'delete':
        // Shift held -> throw them away, no dustbin at all
        try { if (typeof event != 'undefined' && typeof window.event.shiftKey != 'undefined') { e = window.event; } } catch (m) { }
        if (e.shiftKey) {
            answer = confirm('{msg_killconfirm}');
            if (!answer) break;
            alternate = 1;
            drop_screen_selection();
        }
        window.setTimeout('fileaction("' + ops + '",' + alternate + ')', 0);
        break;
    case 'copy':
    case 'move':
        // Involve the files management module
        window.setTimeout('fileaction("' + ops + '",' + alternate + ',"' + opsfolder + '")', 0);
        break;
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

function grab_thumbs()
{
    $('img.mime_preload').each(function () {
        var IMG = new Image();
        IMG
        $(IMG).attr('src', $(this).attr('rel'))
            .attr('rel', $(this).attr('id'))
            .load(function () {
                $('#' + $(this).attr('rel')).attr('src', $(this).attr('src'));
            });
    });
}

$(document).ready(function () {
    resize_elements();
    init_searchform();
    disable_jump();
    setlinks(0);
    grab_thumbs();
});

window.onresize = resize_elements;
if (window.captureEvenets) {
    window.onkeydown = fetchkey;
} else {
    document.onkeydown = fetchkey;
}
/*]]>*/
</script>
<div id="topmen">
    <div id="buttonbar_email" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="activebut" onclick="window.open('{PHP_SELF}?l=upload&amp;h=files&amp;{passthrough}','upload_{id}','width=320,height=270,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no')">
                    <img src="{theme_path}/icons/files_upload.gif" alt="" /><span>{but_upload}</span>
                </li>
                <li class="disabledbut notnull" onclick="collect_and_react_files('delete', event);">
                    <img src="{theme_path}/icons/delete.gif" alt="" /><span>{but_dele}</span>
                </li>
                <li class="disabledbut single" onclick="collect_and_react_files('save', event);">
                    <img src="{theme_path}/icons/files_download.gif" alt="" /><span>{but_save}</span>
                </li>
                <li class="activebut men_drop" id="search" onclick="open_searchbar();">
                    <img src="{theme_path}/icons/search.gif" alt="" /><span>{search}</span>
                </li>
            </ul>
            <ul class="r">
                <li class="activebut" onclick="self.location.href='{jump_url_js}&WP_core_jumppage='+({page}-1);" id="skimleft">
                    <img src="{theme_path}/icons/nav_left_big.gif" alt="" title="{but_last}" />
                </li>
                <li class="activebut" onclick="self.location.href='{jump_url_js}&WP_core_jumppage='+({page}+1);" id="skimright">
                    <img src="{theme_path}/icons/nav_right_big.gif" alt="" title="{but_next}" />
                </li>
                <li class="activebut imgonly men_drop" id="skim" onclick="open_skimbar();">
                    <img src="{theme_path}/icons/page_men.gif" alt="{msg_page}" />
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
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="submit" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
</div>

<div class="files_jobpane" id="jobspane">
    <br />
    <div class="files_panehead">
        <div class="files_paneheadicon"><img src="{theme_path}/icons/files.folderprops_men.png" alt="" title="" /></div>
        {head_folderprops}
    </div>
    <div class="files_jobcontainer">
        <span title="{rawallsize}">{neueingang} {plural} ({allsize})</span><br />
        {newmails} {displaystart} - {displayend}<br />
        <span title="{rawsumsize}">({sumsize}{eval_headsize})</span><br />
    </div>

    <div class="files_panehead">
        <div class="files_paneheadicon"><img id="previewmimeicon" src="{theme_path}/icons/files.fileprops_men.png" alt="" title="" /></div>
        {head_fileprops}
    </div>
    <div class="files_jobcontainer">
        <div id="previewthumb" style="width:190px;padding-bottom:8px;text-align:center;"></div>
        <div id="previewfilename" style="font-weight:bold;width:192px;overflow:hidden;"></div>
        <div id="previewmimename"></div>
        <div id="previewdimensions"></div>
        <div id="previewfilesize"></div>
        <div id="previewlastchange"></div>
    </div>

    <div id="busy_fetching" style="visibility:hidden;position:relative;width:190px;height:15px;">
        <div id="ico_search" style="display:none;position:absolute;top:-5px;z-index:2;left:0px;"><img src="{theme_path}/images/mailsearch.gif" alt="" title="" /></div>
        <div id="ico_dl" style="display:none;position:absolute;top:-5px;z-index:2;left:0px;"><img src="{theme_path}/images/maildownload.gif" alt="" title="" /></div>
        <div class="sendmenuborder" style="width:80px;height:10px;position:absolute;left:22px;"><div id="prgr_inner_busy" class="prgr_inner_busy"></div></div><div style="position:absolute;left:110px;z-index:1;"><span id="fetch_curr">0</span>/<span id="fetch_all">0</span></div>
    </div>
</div>
<div style="overflow:auto;vertical-align:top;text-align:left;" id="itempane" onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();"><!-- START item -->
 <div class="files_itemcont" onclick="selectline('line_{id}',event);preview({id},{uid},'{mimeicon_s}');" id="line_{id}" title="{filename}" oncontextmenu="selectline('line_{id}',event,true);" ondblclick="dlfile({uid});">
 <!-- START nohandling --><img id="ithumb_{id}" src="{mimeicon}" alt="" title="{mimetype}" /><br /><!-- END nohandling --><!-- START isimage -->
  <a class="thickbox" rel="folder-images" href="{imgsrc}&amp;KeepThis=true&amp;TBlinktype=image" onclick="selectline('line_{id}',event);preview({id},{uid},'{mimeicon_s}');">
   <img id="ithumb_{id}"<!-- START mime_preload --> class="mime_preload"<!-- END mime_preload --> src="{mimeicon}" rel="{thumbsrc}" alt="" title="{mimetype}" />
  </a><!-- END isimage --><!-- START isaudio -->
  <a href="#" onclick="selectline('line_{id}',event);preview({id},{uid},'{mimeicon_s}');" ondblclick="nw_audioplayer({uid})">
   <img id="ithumb_{id}" src="{mimeicon}" alt="" title="{mimetype}" />
  </a><!-- END isaudio --><!-- START isvideo -->
  <a class="thickbox" href="{flvsrc}&amp;KeepThis=true&amp;TB_iframe=true&amp;height=600&amp;width=750" onclick="selectline('line_{id}',event);preview({id},{uid},'{mimeicon_s}');">
   <img id="ithumb_{id}" src="{mimeicon}" alt="" title="{mimetype}" />
  </a><!-- END isvideo -->
  <span id="fname_{id}">{filename}</span>
  <script type="text/javascript">/*<![CDATA[*/idToIdx[{id}] = {uid};/*]]>*/</script>
 </div><!-- END item -->
</div>
<div id="pleasewait" style="display:none;position:absolute;top:200px;width:100%;">
    <img src="{theme_path}/images/pleasewait.gif" style="display:block;margin:auto;padding:10px;z-index:200;" alt="..." />
</div>
