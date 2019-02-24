// Break out of possible frameset in case a user was logged off accidentially, but insist on this only if the window name matches
if (top !== self && self.name && self.name === 'PHM_tr') {
    top.location = self.location;
}
anzahl = 0;
theme_path = '{theme_path}';
window.name = 'PHM_main';
avail_screen = 500;
core_clockoffset = 0;
core_errorlog = [];
core_statustext = '';
FolderPropsHeight = '{folder_props_height}' ? '{folder_props_height}' : 500;
FolderListWidth = '{folderlist_width}';
FolderListWidthEffective = FolderListWidth;
CurrentHandler = '';
CurrentFolder = 0;
FolderListLoaded = 0;

function update_headings(name, icon)
{
    $('#foldername').text(name);
    if (icon) $('#foldericon').attr('src', icon);
}

function adjust_height()
{
    // Get the available Window height
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    }
    try {
        var stat = document.getElementById('bottomline');
        var stath  = stat.offsetHeight;
        var statpos = stat.offsetTop;
    } catch (e) {
        var stath = 0;
        var statpos = 0;
    }
    var framel = document.getElementById('PHM_tl');
    var framer = document.getElementById('PHM_tr');
    var current = (framel.height) * 1;

    frameheight = current + (avail_screen - (stath + statpos + 2));
    if (msie) frameheight -= 2;

    framel.height = frameheight;
    framer.height = frameheight;

    avail_width = document.getElementById('framecontainer').offsetWidth;
    var flwidth = FolderListWidthEffective;
    if (flwidth !== '20%') {
        if (flwidth * 1 > avail_width * 0.9) {
            flwidth = avail_width * 0.2 + 'px';
        } else {
            flwidth = flwidth + 'px';
        }
    }
    $('#PHM_tl_container').css('width', flwidth);
}

function show_clock()
{
    var jetzt = new Date();
    jetzt.setTime(jetzt.getTime() + core_clockoffset);
    var Std = jetzt.getHours();
    var Min = jetzt.getMinutes();
    $('#showclock').text(((Std < 10) ? '0' + Std : Std) + ':' + ((Min < 10) ? '0' + Min : Min));
    window.setTimeout('show_clock();', (60-jetzt.getSeconds())*1000);
}

function save_custom_size(token, value)
{
    $.ajax({url: '{customsize_url}&token=' + token + '&value=' + value });
}

function drop_screen_selection()
{
    try {
        document.selection.empty();
    } catch (e) {
        try {
            window.getSelection().collapseToStart();
        } catch (e) {
            try {
                document.getSelection().collapseToStart();
            } catch (e) { }
        }
    }
}

function set_statustext(txt)
{
    if (core_errorlog.length === 0) {
        $('#statustext').html(txt);
    }
    core_statustext = txt;
}

function empty_statustext()
{
    set_statustext('{msg_statusready}');
}


function store_favfolder_order()
{
    var orders = '{favfolders_reorderurl}';
    var pos = 1;
    $('#favfolderpane .favfolder').each(function () {
        orders += '&id[' + this.id.replace(/^flist_fav_/, '') + ']=' + pos;
        pos++;
    });
    $.ajax({url: orders});
}

/**
* Customized drag object for resizing the preview window via mouse drag
*/
dragx =
        {dragobj: null
        ,ox: 0
        ,mx: 0
        ,oldmove: (document.onmousemove) ? document.onmousemove : null
        ,oldup: (document.onmouseup) ? document.onmouseup : null
        ,start: function(o) {
            dragx.dragobj = o;
            dragx.ox = document.getElementById('middleresizer').parentNode.offsetLeft;
            dragx.folderswidth = document.getElementById('PHM_tl_container').offsetWidth;
            document.onmousemove = dragx.drag;
            document.onmouseup = dragx.stop;
            opensemitrans();
        }
        ,drag: function(e) {
            dragx.mx = document.all ? window.event.clientX : e.pageX;
            // Object is given, pointer does not leave screen on top and left
            if (dragx.dragobj !== null && dragx.mx >= 0) {
                document.getElementById('PHM_tl_container').style.width = dragx.folderswidth + (dragx.mx - dragx.ox - 4) + 'px';
                main_customwidth_folders = document.getElementById('PHM_tl_container').offsetWidth;
            }
        }
        ,stop: function() {
            hidesemitrans();
            dragx.dragobj = null;
            document.onmouseup = (dragx.oldup) ? dragx.oldup : null;
            document.onmousemove = (dragx.oldmove) ? dragx.oldmove : null;
            save_custom_size('core_folderlistwidth', main_customwidth_folders);
        }
    };

function opensemitrans()
{
    $('<div id="semitrans"></div>').height(avail_screen).appendTo('body');
}

function hidesemitrans()
{
    $('#semitrans').remove();
}

function core_log_error(txt)
{
    core_errorlog.push(txt);
    $('#statustext').html('<img src="{theme_path}/icons/warning_men.gif" alt="" valign="absmiddle" />&nbsp;' + txt).attr('title', txt);
    window.setTimeout('core_empty_error();', 30000);
}

function core_empty_error()
{
    core_errorlog = [];
    $('#statustext').text(core_statustext).attr('title', '');
}

function core_icontray_add(ID, imgsrc, title, onclick)
{
    $('#icontray').prepend('<img src="' + imgsrc + '" alt="' + title + '" title="' + title + '" id="' + ID + '" />');
    if (onclick) {
        $('#' + ID).click(onclick);
    }
}

function core_icontray_remove(ID)
{
    $('#' + ID).remove();
}

function core_prompt_logout()
{
    if ('{core_prompt_logout}' == 0) {
        return true;
    }
    return (confirm('{msg_reallylogut}') == true);
}

$(window).resize(adjust_height);
$(document).ready(function() {
    set_statustext('{msg_statusloading}');
    dragme.init();
    adjust_height();<!-- START showfavourites -->
    core_switchview("favourites", 1);<!-- END showfavourites --><!-- START showfolderlist -->
    core_switchview("folderlist", 1);<!-- END showfolderlist --><!-- START shownamepane -->
    core_switchview("namepane", 1);<!-- END shownamepane -->
    $('#PHM_tl').attr('src', '{left_target}'.replace(/\&amp;/g, '&'));
    $('#PHM_tr').attr('src', '{right_target}'.replace(/\&amp;/g, '&'));
    show_clock();
});