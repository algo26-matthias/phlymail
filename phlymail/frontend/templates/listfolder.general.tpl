<script type="text/javascript">
openlist = [];
FormOpen = 0;
TextClosed = 0;
CurrentId = 0;
blockedmenu = 0;
nocollapses = 0<!-- START nocollapses -->+1<!-- END nocollapses -->;
idToHandlerId = [];<!-- START add_handler_array -->
idToHandlerId['txt_{Rfolder_path}'] = {'handler' : '{handler}', 'fid' : '{fid}', 'ctx' : <!-- START ctx -->1+<!-- END ctx -->0 ,'props' : <!-- START props -->1+<!-- END props -->0,'move' : <!-- START move -->1+<!-- END move -->0, 'rename' : <!-- START rename -->1+<!-- END rename -->0, 'dele' : <!-- START dele -->1+<!-- END dele -->0, 'resync' : <!-- START resync -->1+<!-- END resync -->0,'subfolder' : <!-- START subfolder -->1+<!-- END subfolder -->0,'is_trash' : <!-- START trash -->1+<!-- END trash -->0, 'is_junk' : <!-- START junk -->1+<!-- END junk -->0, 'is_collapsed' : <!-- START is_collapsed -->1+<!-- END is_collapsed -->0};<!-- END add_handler_array -->

function submit_folder_browser(folder, handler, ops)
{
    opsfolder = folder;
    browserwin.close();
    url = '{PHP_SELF}?{passthrough}&l=worker&h=' + handler + '&what=folder_' + ops
            + '&move_folder=' + idToHandlerId[active_folder]['fid'] + '&move_to=' + opsfolder;
    sendAJAX(url);
    blockedmenu = 0;
}

function sendAJAX(url)
{
    if (opener) {
        window.setTimeout('opener.parent.email_AJAX("' + url + '")', 0);
    } else {
        window.setTimeout('parent.email_AJAX("' + url + '")', 0);
    }
}

function collapse_folders()
{
    for (var i in idToHandlerId) {
        if (idToHandlerId[i]['is_collapsed']) {
            aufzu(i.replace(/^txt_/, '').replace(/_.+$/, ''), true);
        }
    }
}

function aufzu(id, internal)
{
    var i, img, span, level, Durchlauf, sublevel, currlevel, newstyle, mode;

    img = document.getElementById(id);
    if (!img) return;
    span = img.parentNode;
    level = span.getAttribute('name');
    Durchlauf = 1;
    sublevel = 0;
    if (openlist[id] == 1) {
        img.src = '{theme_path}/icons/minus.png';
        openlist[id] = 0;
        newstyle = 'inline';
        mode = 'auf';
    } else {
        img.src = '{theme_path}/icons/plus.png';
        openlist[id] = 1;
        newstyle = 'none';
        mode = 'zu';
    }
    do {
        span = span.nextSibling;
        if (span == null) Durchlauf = 0;
        else if (span.nodeName == 'DIV') {
            currlevel = span.getAttribute('name');
            if (currlevel < sublevel) {
                sublevel = 0;
            } else if (sublevel > 0) {
                continue;
            }
            if (currlevel <= level) Durchlauf = 0;
            else {
                // Find possible subnodes, which are not affected on reopening
                if (mode == 'auf') {
                    for (i = 0; i < span.childNodes.length; ++i) {
                        if (span.childNodes[i].nodeName == 'IMG') {
                            var ifid = span.childNodes[i].getAttribute('id');
                            if (openlist[ifid] == 1) {
                                sublevel = currlevel + 1;
                                continue;
                            }
                        }
                    }
                }
                span.style.display = newstyle;
            }
        }
    } while (1 == Durchlauf)
    blockedmenu = 0;
    if (internal) return;
    var realfolder = 'txt_' + id + img.nextSibling.getAttribute('id').replace(/^img/, '');
    if (!nocollapses) sendAJAX('{fordercollapseurl}' + idToHandlerId[realfolder]['handler'] + '_' + idToHandlerId[realfolder]['fid'] + '&aufzu=' + mode);
}

function open_folder(id, link, name, icon, handler)
{
    parent.PHM_tr.location.href = link;
    parent.update_headings(name, icon);
    mark_folder(id);
    parent.OpenFolder = id;
    blockedmenu = 0;
}

function mark_folder(id)
{
    if (parent.FLMarkedItem != 0) {
        document.getElementById(parent.FLMarkedItem).className = 'foldername';
    }
    document.getElementById(id).className = 'marked foldername';
    parent.FLMarkedItem = id;
    blockedmenu = 0;
}

function select_folder(id, fid)
{
    mark_folder(id);
    CurrentId = fid;
    document.getElementById('browse_submit').disabled = false;
    blockedmenu = 0;
}

function submit_folder(fid)
{
    if (!fid && CurrentId) fid = CurrentId;
    opener.submit_folder_browser(fid, '{handler}', '{mode}');
    blockedmenu = 0;
}

function folder_set_unread_items(fid, count)
{
    var node = document.getElementById('unread_' + fid);
    if (!node) return;
    if (node.childNodes.length) {
        node.removeChild(node.firstChild);
    }
    if (!count || count < 1) return;
    node.appendChild(document.createTextNode('(' + count + ')'));
}

function drop_screen_selection()
{
    try { document.selection.empty(); } catch (e) {
        try { window.getSelection().collapseToStart(); } catch (e) {
            try { document.getSelection().collapseToStart(); } catch (e) { }
        }
    }
}

function init_folderlist()
{
    if (parent.FLMarkedItem && idToHandlerId[parent.FLMarkedItem]) {
        mark_folder(parent.FLMarkedItem);
    } else {
        parent.FLMarkedItem = 0;
    }
    collapse_folders();
    parent.FolderListLoaded = 1;
    try { parent.empty_statustext(); } catch (e) { }
}

if (window.addEventListener) {
    window.addEventListener('load', init_folderlist, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', init_folderlist);
}
window.onunload = function (e) {
    parent.FolderListLoaded = 0;
}
</script>
<div style="text-align:left; vertical-align:top;">
<!-- START line --><div class="listfolderline" name="{level}"><!-- START bars --><!-- START vbar --><img src="{theme_path}/icons/corn2.png" alt="" align="middle" /><!-- END vbar --><!-- START novbar --><img src="{theme_path}/icons/corn3.png" alt="" align="middle" /><!-- END novbar --><!-- END bars --><!-- START aufzu --><img src="{theme_path}/icons/minus.png" alt="" align="middle" onclick="aufzu({id});" id="{id}" /><!-- END aufzu --><!-- START cornplus --><img src="{theme_path}/icons/corn1.png" alt="" align="middle" /><!-- END cornplus --><!-- START corn --><img src="{theme_path}/icons/corn0.png" alt="" align="middle" /><!-- END corn --><!-- START rootline --><img src="{theme_path}/icons/root.png" alt="" align="middle" /><!-- END rootline --><!-- START default --><img id="img_{folder_path}" style="cursor:pointer"<!-- START renamable --><!-- END renamable -->  src="{icon}" alt="" title="{foldername}" align="middle" />&nbsp;<span class="foldername" id="txt_{Rfolder_path}" title="{foldername}" onclick="open_folder('txt_{Rfolder_path}', '{link_target}','{foldername}','{big_icon}', '{handler}');">{foldername}<!-- START folder_is_shared --> <span class="folder_is_shared"></span><!-- END folder_is_shared --> <span class="folder_unread" id="unread_{fid}">{unread}</span></span><form action="{rename_target}" id="form_{Rfolder_path}" style="display:none; vertical-align:middle;" onsubmit="return rename_folder('form_{Rfolder_path}');" method="get"><input type="text" name="rename_to" value="{foldername}" size="{namelength}" class="renamebox" /></form><!-- END default --><!-- START browse --><img id="img_{folder_path}" style="cursor:pointer" src="{icon}" alt="" title="{foldername}" align="middle" />&nbsp;<span class="foldername" id="txt_{Rfolder_path}" title="{foldername}" onclick="select_folder('txt_{Rfolder_path}', '{fid}');" ondblclick="submit_folder('{fid}');">{foldername}</span><!-- END browse --><br /></div>
<!-- END line -->
</div>