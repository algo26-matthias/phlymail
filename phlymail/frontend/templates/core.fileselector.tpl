<script type="text/javascript">
//<![CDATA[
CurrFld = false;
CurrHdl = false;
markeditems = [];
lastfocus = false;
anzahl = 0;
itemList = [];

function open_folder()
{
    var myId = this.id;
    if (CurrFld != false) {
        $('#' + CurrFld).removeClass('marked');
    }
    CurrFld = myId;
    $('#' + CurrFld).addClass('marked');
    $('#ilist_container').empty().addClass('loading');
    myId = myId.replace(/^flist_/, '').split('_');
    $.ajax({url: '{ilist_url}&hdl=' + encodeURIComponent(myId[0]) + '&f=' + encodeURIComponent(myId[1]), dataType: 'json', success: got_items});

    CurrHdl = myId[0];

    lastfocus = false;
    markeditems = [];
    anzahl = 0;
    setlinks(0);
}

function got_items(data)
{
    var html;
    var container = $('#ilist_container');
    container.removeClass('loading');
    itemList = data;
    $.each(data, function (k, v) {
        html = '';
        if (typeof(v['i32']) != 'undefined' && v['i32'].length) {
            html += '<div class="i32"><img src="' + v['i32'] + '" alt="" /></div>';
        } else if (typeof(v['i16']) != 'undefined' && v['i16'].length) {
            html += '<div class="i16"><img src="' + v['i32'] + '" alt="" /></div>';
        }
        if (typeof(v['l1r']) != 'undefined' && v['l1r'].length) {
            html += '<div class="l1r">' + v['l1r'] + '</div>';
        }
        html += '<strong>' + v['l1'] + '</strong>';
        if (typeof(v['l2']) != 'undefined' && v['l2'].length) {
            html += '<br />' + v['l2'];
        }
        html = '<div id="ilist_' + k + '" class="menuline" title="' + v['l1'] + '">' + html + '</div>';
        $(html).appendTo(container);
    });
    container.find('.menuline').click(function (e) {
        selectline(this.id, e);
    });
}

function submit_files()
{
    opener.receive_files(CurrHdl, get_selected_items());
    self.close();
}

function set_boxes(anaus)
{
    $('#ilist_container .menuline').each( function () {
        var lineid = this.id.replace(/^ilist_/, '');
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
    });
}

function selectline(lineid, e, onlythis)
{
    var zwischen;
    lineid = lineid.replace(/^ilist_/, '');
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
        $('#ilist_' + lineid).removeClass('marked');
    } else {
        var moep = $('#ilist_' + lineid);
        // set mark
        markeditems[lineid] = itemList[lineid].id;
        anzahl++;
        moep.addClass('marked');
        if (rescroll) {
            mlines.scrollTop = moep.get(0).offsetTop - toph;
            if (lineid == lastclicked) preview(lineid);
        }
    }
    if (anzahl == 0) {
        lastfocus = false;
        markeditems = [];
    }
    // Let the topbuttonbar know, what's up
    setlinks(anzahl);
}

function setlinks(zahl)
{
    if (zahl < 1) {
        $('#submit').attr('disabled', 'disabled').removeClass('ok');
    } else {
        $('#submit').removeAttr('disabled').addClass('ok');
    }
}

function get_selected_items()
{
    var selected = [];
    for (var i in markeditems) {
        selected.push(markeditems[i]);
    }
    return selected;
}

$(document).ready( function() {
    adjust_height();
    $('#flist_container .foldername').click(open_folder);
    $('#submit').click(submit_files);
});

//]]>
</script>
<div id="core_fileselector">
    <div id="flist_container" class="sendmenuborder inboxline"><!-- START listfolder --><!-- START fhead -->
        <div class="flist_hhead"><img class="flist_hhead_icon" src="{theme_path}/icons/{handler_icon}" alt="" />{handler}</div><!-- END fhead --><!-- START folder -->
        <div class="foldername" id="flist_{handler}_{id}">
            <div class="folderlevel" style="margin-left:{spacer}px;"></div>
            <img class="foldericon" src="{icon}" alt="" />
            <span class="name">{name}</span>
        </div><!-- END folder --><!-- END listfolder -->
    </div>

    <div id="ilist_container" class="sendmenuborder inboxline">

    </div>

    <div id="buttons">
        <button type="button" id="submit" disabled="disabled">{msg_ok}</button>
    </div>
</div>