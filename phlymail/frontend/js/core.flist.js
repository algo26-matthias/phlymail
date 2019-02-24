/**
 * Functionality regarding the folder list
 *
 * @copyright 2005-2010 phlyLabs Berlin, http://phlymail.com
 * @version 4.0.5 2010-07-09
 */
FLMarkedItem = 0;
flist_srcdat = {};
flist_favourites = [];
flist_hcount = 0;
flist_hsize = 0;
flist_open = {};
flist_opencount = 0;
flist_collapsable = 1;
fbrowse_open = false;
fshare_open = false;
fshare_folder = false;
fshare_handler = false;
ctxHdl = false;
ctxFld = false;

function flist_addhandler(handler, friendlyname, isopen, ishidden)
{
    flist_srcdat[handler] = {'fn': friendlyname, 'opn': isopen, 'hid': ishidden};
}

function flist_build()
{
    var HTML;
    $.each(flist_srcdat, function (handler, data) {
        if (data['hid'] == 1) { // Don't draw anything for hidden handlers
            window.setTimeout('flist_refresh("' + handler + '");', 1);
            return true;
        }
        HTML = '<div class="flist_hhead" id="flist_root_' + handler + '">';
        if (htmlBiDi == 'ltr') {
            HTML += '<img class="flist_hhead_icon" src="' + urlThemePath + '/icons/' + handler + '.png" alt="" />'
                    + '<img class="flist_hhead_opn" id="flist_root_opn_' + handler + '" src="' + urlThemePath + '/icons/nav_down.png" alt="" />'
                    + '<img class="flist_hhead_refresh" src="' + urlThemePath + '/icons/folderlist_refresh.png" alt="" title="' + msgRefresh + '" id="flist_root_refresh_' + handler + '" />';
        } else {
            HTML += '<img class="flist_hhead_opn" id="flist_root_opn_' + handler + '" src="' + urlThemePath + '/icons/nav_down.png" alt="" />'
                    + '<img class="flist_hhead_refresh" src="' + urlThemePath + '/icons/folderlist_refresh.png" alt="" title="' + msgRefresh + '" id="flist_root_refresh_' + handler + '" />'
                    + '<img class="flist_hhead_icon" src="' + urlThemePath + '/icons/' + handler + '.png" alt="" />';
        }
        HTML += data['fn'] + '</div>'
                + '<div class="flist_cont loading" id="flist_cont_' + handler + '">'
                + '<div class="flist_wrap" id="flist_wrap_' + handler + '"></div>'
                + '</div>';
        $('body').append(HTML);
        flist_hcount++;
        flist_open[handler] = 0;
        window.setTimeout('flist_refresh("' + handler + '");', 1);
    });

    $('body .flist_hhead').click(flist_switch);
    $('body .flist_hhead_refresh').click(flist_refresh_ico);
    $.each(flist_srcdat, function (handler, data) {
        if (data['opn'] == 1) $('#flist_root_' + handler).click();
    });
    return;
}

function flist_switch(e)
{
    var handler = this.id.replace(/^flist_root_/, '');
    var aufzu;
    if (flist_open[handler] == 1) {
        $('#flist_root_opn_' + handler).attr('src', urlThemePath + '/icons/nav_down.png');
        $('#flist_cont_' + handler).hide();
        flist_open[handler] = 0;
        flist_opencount--;
        aufzu = 'zu';
    } else {
        $('#flist_root_opn_' + handler).attr('src', urlThemePath + '/icons/nav_up.png');
        $('#flist_cont_' + handler).show();
        flist_open[handler] = 1;
        flist_opencount++;
        aufzu = 'auf';
    }
    $.ajax({"url": urlCollapseFolder + handler + '_&aufzu=' + aufzu});
    flist_resizeareas();
}

// Resize the open areas
function flist_resizeareas()
{
    if (flist_opencount == 0) return;

    /** Klappt noch nicht, später mal genauer ansehen
    // Fill as much vertical space as possible
    var corrector = (FrameHeight-(flist_hcount*24))*-1;
    for (var hdl in flist_open) {
        if (flist_open[hdl] == 0) {
            continue;
        }
        corrector += $('#flist_wrap_' + hdl).height();
    }
    if (corrector > 0) {
        corrector = 0;
    }
    ... bis dahin: */
    var corrector = 0;

    var myFlistOpen = {};
    var myOpenCount = flist_opencount;
    // Rule out all smaller than sizeperarea
    while (true) {
        var hit = false;
        for (var hdl in flist_open) {
            if (flist_hsize == 0) {
                flist_hsize = $('#flist_root_' + hdl).outerHeight();
            }
            var sizeperarea = ((FrameHeight-(flist_hcount * flist_hsize)-corrector) / myOpenCount) - 4; // Padding as set in CSS
            if (flist_open[hdl] == 0 || myFlistOpen[hdl] == 1) {
                myFlistOpen[hdl] = 1;
                continue;
            }
            var myHeight = $('#flist_wrap_' + hdl).height();
            if (myHeight <= sizeperarea) {
                $('#flist_cont_' + hdl).height(myHeight);
                myFlistOpen[hdl] = 1;
                hit = true;
                myOpenCount--;
                corrector += myHeight+4;
            }
        }
        if (hit == false) break; // Did not find an area small enough in the last run
    }
    for (var hdl in flist_open) {
        if (flist_open[hdl] == 0 || myFlistOpen[hdl] == 1) {
            continue;
        }
        $('#flist_cont_' + hdl).height(sizeperarea);
    }
}

function flist_loaded(next)
{
    flist_srcdat[next['handler']]['folders'] = next['folders'];
    flist_srcdat[next['handler']]['childof'] = next['childof'];
    $('#flist_cont_' + next['handler']).removeClass('loading');
    delete (flist_srcdat[next['handler']]['load']);
    if (flist_srcdat[next['handler']]['hid'] != 1) {
        flist_draw(next['handler'], 0);
    } else { // Handler is hidden, so its folders don't not get drawn, have to simulate the click here
        if (next['handler'] == CurrHdl) flist_open_folder(CurrHdl + '_' + CurrFld);
    }
    // Mark Folderlist as loaded, when all handlers have arrived
    var unloadedcount = 0;
    for (var handler in flist_srcdat) {
        if (typeof flist_srcdat[handler]['load'] != 'undefined') unloadedcount++;
    }
    if (unloadedcount == 0) {
        parent.FolderListLoaded = 1;
        parent.$('#show_pinboard').click(function () { flist_open_folder('flist_fld_core_root'); } ).css('cursor', 'pointer');
        window.setTimeout('flist_favfolders_get();', 1);
        window.setTimeout('parent.empty_statustext();', 1);
    }
    flist_resizeareas();
}

function flist_refresh_ico(e)
{
    var hdl = this.id.replace(/^flist_root_refresh_/, '');
    $('#flist_cont_' + hdl).addClass('loading');
    $('#flist_wrap_' + hdl).empty();
    flist_refresh(hdl);
    cancel_event(e);
}

function flist_refresh(handler)
{
    flist_srcdat[handler]['load'] = $.ajax({"url": urlLoadFList + '&handler=' + handler, "success": flist_loaded});
}

function flist_draw(handler, childof)
{
    var collapseMe = [];
    var cont = document.getElementById('flist_wrap_' + handler);
    while (childof == 0 && cont.childNodes && cont.childNodes.length) cont.removeChild(cont.firstChild);
    for (var i in flist_srcdat[handler]['childof'][childof]) {
        var CFid = flist_srcdat[handler]['childof'][childof][i];
        if (typeof flist_srcdat[handler]['folders'][CFid] != 'object') continue;
        var cFld = flist_srcdat[handler]['folders'][CFid];
        if (typeof cFld['visible'] != 'undefined' && cFld['visible'] == 0) continue; // Ignore hidden folders
        if (typeof cFld['is_shared'] == 'undefined' || cFld['is_shared'] != 1) {
            cFld['is_shared'] = 0;
        }
        if (typeof cFld['stale'] != 'undefined' && cFld['stale'] == 1) {
            delete flist_srcdat[handler]['childof'][CFid]; // Ignore subfolders of stale IMAP accounts
        }
        var div = document.createElement('div');
        div.className = 'foldername';
        var dv2 = document.createElement('div');
        dv2.className = 'folderlevel';
        dv2.id = 'flist_fico_' + handler + '_' + CFid;
        if (typeof flist_srcdat[handler]['childof'][CFid] == 'object') {
            dv2.className += ' collapsable folder_opn_open';
            div.className += ' collapsable';
            dv2.style.cursor = 'pointer';
        }
        dv2.style.marginLeft = (parseInt(cFld['level'])*16) + 'px';
        div.appendChild(dv2);

        var img = document.createElement('img');
        img.src = cFld['icon'];
        img.className = 'foldericon';
        div.appendChild(img);
        if (htmlBiDi === 'ltr') {
            div.appendChild(document.createTextNode(cFld['foldername']));
            if (typeof cFld['colour'] !== 'undefined' && cFld['colour'] !== '') {
                var spn = document.createElement('span');
                spn.className = 'cmark_circle cmark_' + cFld['colour'];
                div.appendChild(spn);
            }
            if (cFld['is_shared'] == 1) {
                var spn = document.createElement('span');
                spn.className = 'folder_is_shared';
                div.appendChild(spn);
            }
            var spn = document.createElement('span');
            spn.className = 'folder_unread';
            spn.id = 'flist_unread_' + handler + '_' + CFid;
            div.appendChild(spn);
        } else {
            var spn = document.createElement('span');
            spn.className = 'folder_unread';
            spn.id = 'flist_unread_' + handler + '_' + CFid;
            div.appendChild(spn);
            if (cFld['is_shared'] == 1) {
                var spn = document.createElement('span');
                spn.className = 'folder_is_shared';
                div.appendChild(spn);
            }
            if (typeof cFld['colour'] !== 'undefined' && cFld['colour'] !== '') {
                var spn = document.createElement('span');
                spn.className = 'cmark_circle cmark_' + cFld['colour'];
                div.appendChild(spn);
            }
            div.appendChild(document.createTextNode(cFld['foldername']));
        }
        div.id = 'flist_fld_' + handler + '_' + CFid;
        if (cFld['has_items'] && cFld['has_items'] == 1) {
            div.className += ' clickable';
        } else {
            div.style.cursor = 'default';
        }
        if (cFld['ctx']) {
            div.oncontextmenu = function (e) { flist_foldermenu(this.id); return false;}
            div.onmouseout = ctxmen_disable_sensor;
        }
        div.title = cFld['foldername'];
        if (cFld['level'] < 1) {
            div.className += ' isroot';
        }
        cont.appendChild(div);
        if (handler == CurrHdl && CFid == CurrFld) flist_open_folder(div.id);
        if (typeof flist_srcdat[handler]['childof'][CFid] == 'object') flist_draw(handler, CFid);
        if (cFld['is_collapsed']) collapseMe.push(CFid);
        if (typeof cFld['unread'] != 'undefined') {
            var tmp = cFld['unread'];
            flist_srcdat[handler]['folders'][CFid]['unread'] = 0;
            flist_set_unread_items(handler, CFid, tmp);
        }
    }
    $('#flist_wrap_' + handler).find('.folderlevel.collapsable').click(flist_collapse_evt)
            .end().find('.foldername.clickable').click(function () { flist_open_folder($(this).attr('id')); } )
            .end().find('.foldername.collapsable').dblclick(function() { flist_collapse($(this).attr('id')); } );
    // folder_collapses() anwenden
    for (var i = 0; i < collapseMe.length; ++i) {
        openlist['flist_fld_' + handler + '_' + collapseMe[i]] = 0;
        flist_collapse('flist_fld_' + handler + '_' + collapseMe[i], true);
    }
}

function flist_collapse_evt(e)
{
    e.preventDefault();
    e.stopImmediatePropagation();
    flist_collapse($(this).parent().attr('id'));
}

function flist_collapse(id, internal)
{
    var span, handler, cFid, cFld, level, sublevel, currlevel, newstyle, mode, hunread = 0;
    span = document.getElementById(id);
    cFld = id.replace(/^flist_fld_/, '').split('_');
    handler = cFld[0];
    cFid = cFld[1];
    level = flist_srcdat[handler]['folders'][cFid]['level'];
    sublevel = 0;
    if (openlist[id] === 1) {
        $('#flist_fico_' + handler + '_' + cFid).removeClass('folder_opn_close').addClass('folder_opn_open');
        openlist[id] = 0;
        newstyle = '';
        mode = 'auf';
    } else {
        $('#flist_fico_' + handler + '_' + cFid).removeClass('folder_opn_open').addClass('folder_opn_close');
        openlist[id] = 1;
        newstyle = 'none';
        mode = 'zu';
    }
    do {
        span = span.nextSibling;
        if (span == null) break;
        else if (span.nodeName == 'DIV') {
            cFld = span.id.replace(/^flist_fld_/, '').split('_');
            currlevel = flist_srcdat[handler]['folders'][cFld[1]]['level'];
            if (currlevel < sublevel) {
                sublevel = 0;
            } else if (sublevel > 0) {
                continue;
            }
            if (currlevel <= level) break;
            // Find possible subnodes, which are not affected on reopening
            if (mode == 'auf' && openlist['flist_fld_' + handler + '_' + cFld[1]] == 1) sublevel = currlevel + 1;
            span.style.display = newstyle;
            // Count unread items in subfolders
            if (typeof flist_srcdat[handler]['folders'][cFld[1]]['unread'] != 'undefined') {
                hunread += parseInt(flist_srcdat[handler]['folders'][cFld[1]]['unread']);
            }
        }
    } while (1)
    if (mode == 'zu' && hunread > 0) {
        $('#flist_fld_' + handler + '_' + cFid).addClass('subunread');
    } else {
        $('#flist_fld_' + handler + '_' + cFid).removeClass('subunread');
    }
    if (internal) return;
    if (flist_collapsable) $.ajax({url:urlCollapseFolder + handler + '_' + cFid + '&aufzu=' + mode});
    flist_resizeareas();
}

function flist_open_folder(id)
{
    var SplitId = id.replace(/^flist_fld_/, '').split('_');
    var cFld = flist_srcdat[SplitId[0]]['folders'][SplitId[1]];
    window.setTimeout('flist_mark_folder("' + id + '");', 1);
    CurrFld = SplitId[1];
    CurrHdl = SplitId[0];
    parent.PHM_tr.location.href = URLopenFolder + CurrHdl + '&workfolder=' + CurrFld;
    parent.update_headings(cFld['foldername'], cFld['big_icon']);
    parent.CurrentHandler = CurrHdl;
    parent.CurrentFolder = CurrFld;
    blockedmenu = 0;
}

function flist_mark_folder(id)
{
    if (FLMarkedItem != 0) $('#' + FLMarkedItem).removeClass('marked');
    $('#' + id).addClass('marked');
    FLMarkedItem = id;
}

function flist_reset_unseen(handler)
{
    if (!handler) return;
    $('#flist_wrap_' + handler + ' .foldername').removeClass('unseen');
}

function flist_mark_unseen(handler, id)
{
    $('#flist_fld_' + handler + '_' + id).addClass('unseen');
}

function flist_set_unread_items(handler, fid, count)
{
    try { if (flist_srcdat[handler]['folders'][fid]['unread'] == count) { return; } } catch (e) { }

    if (!count || count < 1) {
        flist_srcdat[handler]['folders'][fid]['unread'] = 0;
        $('#flist_unread_' + handler + '_' + fid).text('');
        $('#flist_fld_' + handler + '_' + fid).removeClass('hasunread');
    } else {
        flist_srcdat[handler]['folders'][fid]['unread'] = count;
        $('#flist_unread_' + handler + '_' + fid).text(count);
        $('#flist_fld_' + handler + '_' + fid).addClass('hasunread');
    }
}

function flist_favfolders_get()
{
    $.ajax({url:urlLoadFavFolders, success:flist_favfolders_got});
}

function flist_favfolders_got(next)
{
    flist_favourites = next['favourites'];
    flist_draw_favfolders();
}

var SorterTriggered = 0;

function flist_draw_favfolders()
{
    parent.$('#favfolderpane').empty();
    if (flist_favourites.length == 0) return;
    var flsize = parent.$('#favfolderpane').width()/flist_favourites.length;
    if (flsize < 100) flsize = 100;
    if (flsize > 200) flsize = 200;
    var HTML = '';
    for (var i = 0; i < flist_favourites.length; i++) {
        try {
            var cFld = flist_srcdat[flist_favourites[i].handler]['folders'][flist_favourites[i].fid];
            if (!cFld) throw '1';
        } catch (ex) {
            // Does not exist as a folder anymore, drop it from list
            $.ajax({url:urlSetFavFolders + '&m=0&hdl=' + flist_favourites[i].handler + '&fid=' + flist_favourites[i].fid});
            continue;
        }
        HTML += '<div id="flist_fav_' + flist_favourites[i].handler + '_' + flist_favourites[i].fid + '" class="favfolder"'
                + ' style="width:' + flsize + 'px;" title="' + cFld['foldername'] + '">'
                + '<img src="' + cFld['icon'] + '" class="foldericon" alt="" />'
                + cFld['foldername'] + '</div>';
    }
    parent.$('#favfolderpane').html(HTML).sortable(
            {axis: 'x'
            ,tolerance: 'pointer'
            ,stop: function (event, ui) {
                parent.store_favfolder_order();
                SorterTriggered = 1;
            }
            }).disableSelection().find('.favfolder').click(function () {
                if (!SorterTriggered) flist_open_folder($(this).attr('id').replace(/^flist_fav_/, 'flist_fld_'));
                SorterTriggered = 0
            });
}

function flist_ctxfolderaddfavs()
{
    $.ajax({url:urlSetFavFolders + '&m=1&hdl=' + ctxHdl + '&fid=' + ctxFld, success:flist_favfolders_got});
}

function flist_ctxfolderdropfavs()
{
    $.ajax({url:urlSetFavFolders + '&m=0&hdl=' + ctxHdl + '&fid=' + ctxFld, success:flist_favfolders_got});
}

function flist_foldermenu(id)
{
    var cSrc, cFld;
    cSrc = id.replace(/^flist_fld_/, '').replace(/^flist_fav_/, '').split('_');
    ctxHdl = cSrc[0];
    ctxFld = cSrc[1];
    cFld = flist_srcdat[ctxHdl]['folders'][ctxFld];
    if (cFld['ctx'] != 1) return false;
    ctxmen_activate_sensor(ctxmen);
    var is_in_favs = 0;
    for (var i = 0; i < flist_favourites.length; ++i) {
        if (flist_favourites[i].handler == ctxHdl && flist_favourites[i].fid == ctxFld) {
            is_in_favs = 1;
            break;
        }
    }
    ctxmen[0]['status'] = (cFld['ctx_props'] == 1) ? 1 : 3;
    ctxmen[1]['status'] = (cFld['ctx_share'] == 1 && cFld['has_items'] == 1) ? 1 : 3;
    ctxmen[2]['status'] = (cFld['ctx_resync'] == 1) ? 1 : 3;
    ctxmen[3]['status'] = (cFld['ctx_move'] == 1) ? 1 : 3;
    ctxmen[4]['status'] = (cFld['ctx_rename'] == 1) ? 1 : 3;
    ctxmen[5]['status'] = (cFld['ctx_dele'] == 1) ? 1 : 3;
    ctxmen[6]['status'] = (cFld['ctx_subfolder'] == 1) ? 1 : 3;
    ctxmen[7]['status'] = (cFld['is_trash'] == 1) ? 1 : 3;
    ctxmen[8]['status'] = (cFld['is_junk'] == 1) ? 1 : 3;
    ctxmen[9]['status'] = (is_in_favs == 0 && cFld['has_items'] == 1) ? 1 : 3;
    ctxmen[10]['status'] = (is_in_favs == 1) ? 1 : 3;
}

function flist_ctxrename()
{
    keyfetch_off();
    newname = prompt(msgFolderName, $('#flist_fld_' + ctxHdl + '_' + ctxFld).attr('title'));
    keyfetch_on();
    if (!newname || newname.length == 0) {
        alert(msgETooShort);
        return false;
    }
    if (newname.length > 32) {
        alert(msgETooLong);
        return false;
    }
    newname = encodeURIComponent(newname);
    $.ajax({url:urlMyBase+'&load=worker&handler='+ctxHdl+'&what=rename_folder&rename_folder='+ctxFld+'&rename_to='+newname, dataType: 'script'});
}

function flist_ctxdelete(e)
{
    alternate = 0;
    // Shift held -> throw them away, no dustbin at all
    if (!e && window.event) e = window.event;
    if (e.shiftKey) {
        alternate = 1;
        parent.drop_screen_selection();
    }
    var msg = msgReallyDeleteFolder.replace(/\$1/, $('#flist_fld_' + ctxHdl + '_' + ctxFld).attr('title'));
    if (confirm(msg)) {
        $.ajax({url:urlMyBase+'&load=worker&handler='+ctxHdl+'&what=folder_delete&directly='+alternate+'&remove_folder='+ctxFld, dataType: 'script'});
    }
}

function flist_ctxresync()
{
    $.ajax({url:urlMyBase+'&load=worker&handler='+ctxHdl+'&what=folder_resync&resync_folder='+ctxFld, dataType:'script'});
}

function flist_ctxexpungefolder()
{
    var msg = msgReallyEmptyFolder.replace(/\$1/, $('#flist_fld_' + ctxHdl + '_' + ctxFld).attr('title'));
    if (confirm(msg)) {
        $.ajax({url:urlMyBase+'&load=worker&handler=' + ctxHdl + '&what=folder_empty&empty_folder=' + ctxFld, dataType:'script'});
    }
}

function flist_ctxcreatesubfolder()
{
    keyfetch_off();
    newname = prompt(msgFolderName);
    keyfetch_on();
    if (!newname || newname.length == 0) {
        alert(msgETooShort);
        return false;
    }
    if (newname.length > 32) {
        alert(msgETooLong);
        return false;
    }
    newname = encodeURIComponent(newname);
    $.ajax({url:urlMyBase+'&load=worker&handler=' + ctxHdl + '&what=folder_create&childof=' + ctxFld + '&new_folder=' + newname, dataType:'script'});
}

function flist_folderprops()
{
    browserwin = window.open
            (urlMyBase + '&load=folderprops&handler=' + ctxHdl + '&fid=' + ctxFld
            ,'folderprops_' + ctxHdl + '_' + ctxFld
            ,'width=450,height=' + parent.FolderPropsHeight + ',left=200,top=200,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function flist_ctxshare()
{
    browserwin = window.open
            (urlMyBase + '&l=foldershares&h=core&hdl=' + ctxHdl + '&fid=' + ctxFld
            ,'foldershares_' + ctxHdl + '_' + ctxFld
            ,'width=490,height=' + parent.FolderPropsHeight + ',left=200,top=200,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function flist_ctxmove()
{
    browserwin = window.open
            (urlMyBase + '&load=browse&handler=' + ctxHdl + '&mode=move'
            ,'browser_movefolder'
            ,'width=450,height=400,left=200,top=200,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function submit_folder_browser(folder, handler, ops)
{
    browserwin.close();
    $.ajax({url:urlMyBase + '&load=worker&handler=' + ctxHdl + '&what=folder_' + ops + '&move_folder=' + ctxFld + '&move_to=' + folder, dataType:'script'});
}

function flist_dimensions()
{
    Frame = parent.$('#PHM_tl_container');
    FrameHeight = Frame.height();
    $('body').width(Frame.width()).height(Frame.height());
    try { flist_resizeareas(); } catch (e) {} // Wrapped to prevent errors on init
}