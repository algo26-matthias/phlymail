<script type="text/javascript" src="{frontend_path}/js/core.flist.js?{current_build}"></script>
<!-- script type="text/javascript" src="{frontend_path}/js/core.fbrowse.js?{current_build}"></script>
<script type="text/javascript" src="{frontend_path}/js/core.fshare.js?{current_build}"></script -->
<script type="text/javascript">
/*<![CDATA[*/
URLopenFolder = '{PHP_SELF}?{passthrough}&l=ilist&h=';
urlThemePath = '{theme_path}';
urlFrontend = '{frontend_path}';
urlCollapseFolder = '{foldercollapseurl}';
urlLoadFList = '{flist_loadurl}';
urlLoadFavFolders = '{favfolders_loadurl}';
urlSetFavFolders = '{favfolders_seturl}';
urlMyBase = '{PHP_SELF}?{passthrough}';
msgRefresh = '{msg_refresh}';
msgFolderName = '{msg_foldername}';
msgETooShort = '{etooshort}';
msgETooLong = '{etoolong}';
msgReallyDeleteFolder = '{msg_really_dele_folder}';
msgReallyEmptyFolder = '{msg_really_empty_folder}';
htmlBiDi = '{bidi-direction}';
theme_path = '{theme_path}';
loggedin_user = '{loggedin_user}';
CurrHdl = '{login_handler}';
CurrFld = '{login_folder}';
ctxmen = {
     0 : {'status': 1, 'link': 'flist_folderprops()', 'name': '{msg_properties} ...', 'icon' : urlThemePath + '/icons/folderprops_ctx.gif'}
    ,1 : {'status': 3, 'link': 'flist_ctxshare()', 'name': '{msg_sharefolder}', 'icon' : urlThemePath + '/icons/sharefolder_ctx.gif'}
    ,2 : {'status': 1, 'link': 'flist_ctxresync()', 'name': '{msg_resync}', 'icon' : urlThemePath + '/icons/resyncfolder_ctx.gif'}
    ,3 : {'status': 1, 'link': 'flist_ctxmove()', 'name': '{msg_move} ...', 'icon' : urlThemePath + '/icons/movefolder_ctx.gif'}
    ,4 : {'status': 1, 'link': 'flist_ctxrename()', 'name': '{msg_rename} ...', 'icon' : urlThemePath + '/icons/renamefolder_ctx.gif'}
    ,5 : {'status': 1, 'link': 'flist_ctxdelete(e)', 'name': '{msg_dele}', 'icon' : urlThemePath + '/icons/deletefolder_ctx.gif'}
    ,6 : {'status': 1, 'link': 'flist_ctxcreatesubfolder()', 'name': '{msg_subfolder} ...', 'icon' : urlThemePath + '/icons/createfolder_ctx.gif'}
    ,7 : {'status': 3, 'link': 'flist_ctxexpungefolder()', 'name': '{msg_emptytrash}', 'icon' : urlThemePath + '/icons/emptytrash_ctx.gif'}
    ,8 : {'status': 3, 'link': 'flist_ctxexpungefolder()', 'name': '{msg_emptyjunk}', 'icon' : urlThemePath + '/icons/emptytrash_ctx.gif'}
    ,9 : {'status': 3, 'link': 'flist_ctxfolderaddfavs()', 'name': '{msg_addfavoruites}', 'icon' : urlThemePath + '/icons/addfavourites_ctx.png'}
    ,10 : {'status': 3, 'link': 'flist_ctxfolderdropfavs()', 'name': '{msg_removefavourites}', 'icon' : urlThemePath + '/icons/dropfavourites_ctx.png'}
    };
<!-- START add_handler -->
flist_addhandler('{id}', '{friendlyname}', '{is_open}', '{is_hidden}');<!-- END add_handler -->

function fetchkey(e)
{
    var evt =  e || window.event;
    var key = (evt.which) ? evt.which : evt.keyCode;
    var fetched = false; // Pass on keycodes we did not fetch
    var exec = false; // First return from the keypress, then execute the command
    // React on pressed key
    switch (key) {
    case 46: // Entf (Del)
        flist_foldermenu(FLMarkedItem);
        flist_ctxdelete(e);
        fetched = true;
        break;
    case 69: // E
        if (evt.shiftKey) {
            exec = 'flist_ctxexpungefolder();';
            fetched = true;
        }
        break;
    case 78: // N
        if (evt.ctrlKey) {
            exec = 'flist_ctxcreatesubfolder();';
            fetched = true;
        }
        break;
    case 80: // P
        if (evt.ctrlKey) {
            exec = 'flist_folderprops();';
            fetched = true;
        }
        break;
    case 82: // R
        if (evt.ctrlKey) {
            exec = 'flist_ctxrename();';
            fetched = true;
        }
        break;
    case 83: // S
        if (evt.shiftKey) {
            exec = 'flist_ctxresync();';
            fetched = true;
        }
        break;
    case 86: // V
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'flist_ctxmove();';
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
        if (exec) window.setTimeout('flist_foldermenu(FLMarkedItem); ' + exec, 1);
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

$(document).ready(function() {
    $.ajaxSetup({'method': 'GET', 'cache': false, 'dataType': 'json'});
    parent.set_statustext('{msg_statusloading}');
    flist_dimensions();
    $(window).resize(flist_dimensions);
    flist_build();
    keyfetch_on();
});
/*]]>*/
</script>