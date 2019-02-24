<script type="text/javascript">
//<![CDATA[
var CurrFld = '{workfolder}';
var PageLoaded = 0;
var anzahl = 0;
var avail_screen = 500;
var preview_visible = 0;
var markeditems = {};
var lastfocus = false;
var lastfocus_prev = lastfocus_next = false;
var lastclicked = false;
var lastpreview = false;
var total_fetched = 0;
var total_deleted = 0;
var total_updated = 0;
var curr_fetch = 0;
var action_queue = [];
var bounce_list = [];
var bounce_all = 0;
var bounce_del = 0;
var AJAX_url = false;
var fetcher_url = '{fetcher_url}';
var mailops_url = '{mailops_url}';
var jsrequrl = '{jsrequrl}';
var fetcher_list = [];
var mailnum = 0;
var TOMarkRead = false;
var actionpane_open = searchbar_open = infobar_open = skimbar_open = 0;
var imap_checked = 0;
var open_threads = {};
var search_adb_field = '';
var search_adb_fragment = '';
var search_adb_cache = [];
var search_adb_queried_words = [];
var search_adb_for = '';
var search_adb_selected = false;
var search_adb_uptodate = false;

var myRight = htmlBiDi == 'ltr' ? 'right' : 'left';
var myLeft = htmlBiDi == 'ltr' ? 'left' : 'right';

var urlSendToAdb = '{link_sendtoadb}';
var urlEmailView = '{viewlink}';
var urlEmailPreiew = '{preview_url}';
var msgConfirmDelete = '{msg_killconfirm}';

var ctxmen_id = false;
var ctxmen =
        {0 : {'status': 1, 'link': 'set_boxes(1)', 'name' : '{msg_all}', 'icon' : '{theme_path}/icons/selall_ctx.gif'}
        ,1 : {'status': 3, 'link': 'set_boxes(0)', 'name' : '{msg_none}', 'icon' : '{theme_path}/icons/unselall_ctx.gif'}
        ,2 : {'status': 1, 'link': 'set_boxes(-1)', 'name' : '{msg_rev}', 'icon' : '{theme_path}/icons/invsel_ctx.gif'}
        ,3 : {'status': 2 }
        ,4 : {'status': 1, 'link': 'collect_and_react_email("mark", e)', 'name' : '{msg_markreadset}', 'icon' : '{theme_path}/icons/markread_ctx.gif'}
        ,5 : {'status': 1, 'link': 'collect_and_react_email("unmark", e)', 'name' : '{msg_markreadunset}', 'icon' : '{theme_path}/icons/markunread_ctx.gif'}
        ,6 : {'status': 1, 'link': 'collect_and_react_email("spam", e)', 'name' : '{msg_mark_spam}', 'icon' : '{theme_path}/icons/markspam_ctx.gif'}
        ,7 : {'status': 1, 'link': 'collect_and_react_email("unspam", e)', 'name' : '{msg_mark_ham}', 'icon' : '{theme_path}/icons/marknospam_ctx.gif'}
        ,8 : {'status': 1, 'link': 'collect_and_react_email("colourmark", e)', 'name' : '{msg_markcolour}', 'icon': '{theme_path}/icons/colourmark_ctx.gif'}
        ,9 : {'status': 3, 'link': 'collect_and_react_email("precopy", e)', 'name' : '{msg_copy}', 'icon': '{theme_path}/icons/copytofolder_ctx.gif'}
        ,10: {'status': 3, 'link': 'collect_and_react_email("premove", e)', 'name' : '{msg_move}', 'icon': '{theme_path}/icons/movetofolder_ctx.gif'}
        ,11: {'status': 2 }
        ,12: {'status': 3, 'link': 'collect_and_react_email("answer", e)', 'name' : '{answer}', 'icon': '{theme_path}/icons/answer_ctx.gif'}
        ,13: {'status': 3, 'link': 'collect_and_react_email("answerAll", e)', 'name' : '{answerAll}', 'icon': '{theme_path}/icons/answerall_ctx.gif'}
        ,14: {'status': 3, 'link': 'collect_and_react_email("forward", e)', 'name' : '{forward}', 'icon': '{theme_path}/icons/forward_ctx.gif'}
        ,15: {'status': 3, 'link': 'collect_and_react_email("bounce",e)', 'name':'{bounce}', 'icon': '{theme_path}/icons/bounce_ctx.gif'}
        ,16: {'status': 3, 'link': 'collect_and_react_email("template", e)', 'name' : '{msg_editasnew}', 'icon' : '{theme_path}/icons/editasnew_ctx.gif'}
        ,17: {'status': 2 }
        ,18: {'status': 3, 'link': 'collect_and_react_email("archive", e)', 'name' : '{archive}', 'icon' : '{theme_path}/icons/archive_men.gif'}
        ,19: {'status': 3, 'link': 'collect_and_react_email("delete", e)', 'name' : '{del}', 'icon' : '{theme_path}/icons/delete_ctx.gif'}
};
<!-- START ctx_newmail -->
ctxmen[12]['status'] = 1;
ctxmen[13]['status'] = 1;
ctxmen[14]['status'] = 1;
ctxmen[15]['status'] = 1;
ctxmen[16]['status'] = 1;<!-- END ctx_newmail --><!-- START ctx_copy -->
ctxmen[9]['status'] = 1;<!-- END ctx_copy --><!-- START ctx_move -->
ctxmen[10]['status'] = 1;
ctxmen[18]['status'] = 1;<!-- END ctx_move --><!-- START ctx_delete -->
ctxmen[19]['status'] = 1;<!-- END ctx_delete -->

var fieldinfo = {'status' : {'w' : 16, 'a' : '', 'ml' : 'statsutext'},'hpriority' : {'w' : 8, 'a' : '', 'ml' : 'priotext'},'attachments' : {'w' : 8, 'a' : '', 'ml' : 'att'},'hsubject' : {'w' : 0, 'a' : 'l', 'ml' : 'subj'},'hfrom' : {'w' : 0, 'a' : 'l', 'ml' : 'from_1'},'hto' : {'w' : 0, 'a' : 'l', 'ml' : 'to_1'},'hcc' : {'w' : 0, 'a' : 'l', 'ml' : 'cc_1'},'hbcc' : {'w' : 0, 'a' : 'l', 'ml' : 'bcc_1'},'hsize' : {'w' : 75, 'a' : 'r', 'ml' : 'sizeraw'},'hdate_sent' : {'w' : 100, 'a' : 'l', 'ml' : 'date'}};
var pagestats = {"mailnum" : {neueingang}, "allsize": "{allsize}", "rawallsize": "{rawallsize}", "showfields" : {showfields}
        ,"pagesize" : {pagesize}, "rawsumsize": "{rawsumsize}", "sumsize": "{sumsize}", "page" : {page}, "pagenum" : {pagenum}, "maxpage" : {boxsize}
        ,"groupby": "{groupby}", "orderby": "{orderby}", "orderdir": "{orderdir}"
        ,"displaystart" : {displaystart}, "displayend" : {displayend}, "plural": "{plural}", "automarkread": "{automarkread}"
        ,"is_imap": "{is_imap}", "is_junk": "{is_junk}", "mark_junk": "{mark_junk}", "use_preview": "{use_preview}"
        ,"allow_resize": "{allow_resize}", "collapse_threads": "{collapse_threads}"
        ,"viewlink": "{viewlink}", "customheight": {customheight}, "folder_writable": {folder_writable}};
var maillines = {<!-- START maillines -->{notfirst}{num} : {data}<!-- END maillines -->};

function build_maillist()
{
    // Some updates in the markup
    update_pagestats();
    disable_jump();
    // Go
    if (maillines.length == 0) {
        // document.getElementById('core_ico_refresh').style.display = 'none';
        return;
    }
    var mthead = document.getElementById('mailthead');
    var mlines = document.getElementById('maillines');
    mthead.className = 'listhead';
    topmenu = document.getElementById('topmen');
    var availwidth = mthead.offsetWidth-40;
    var verteilcount = 0;
    var verteilfields = [];
    groupby_groups = [];

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

    // Reset to really available space for the maillines minus scrollbar
    availwidth = mthead.offsetWidth-36;

    var grouphint = false;
    for (var i in maillines) {
        var cm = maillines[i]; // Current mail

        if (pagestats.groupby != '') {
            var currgroup = '';
            if (cm.groupsort) {
                currgroup = cm.groupsort;
            } else {
                currgroup = cm[(fieldinfo[pagestats.groupby]['ml'])];
            }
            if (currgroup != grouphint) {
                groupby_groups.push(i);
                grouphint = currgroup;
                var g = document.createElement('div');
                g.className = 'inbxgrpo';
                g.id = 'gl_' + (groupby_groups.length - 1);
                g.appendChild(document.createTextNode(pagestats.showfields[[pagestats.groupby]]['n'] + ': ' + currgroup));
                $(g).click(gl_switch);
                mlines.appendChild(g);
            }
        }

        var r = document.createElement('div');
        r.id = 'ml_' + cm.uidl;
        r.className = 'inboxline';
        if (cm.colour != '') {
            r.className += ' cmark_' + cm.colour;
        }
        if (cm.is_unread == 1) {
            r.className += ' unread';
        }
        r.oncontextmenu = function (e) { selectline(this.id.replace(/^ml_/, ''), e, true); }
        $(r).css({'width':(availwidth+4)+'px'})
            .click(function (e) {
                var src = this.id.replace(/^ml_/, '');
                selectline(src, e);
                preview(src);
                lastclicked = src;
            })
            .dblclick(function (e) {
                var id = this.id.replace(/^ml_/, '');
                window.open(urlEmailView + '&mail=' + id, 'mailread_' + id, 'width=700,height=500,left='
                        + (($(window).width()-700)/2).toString()
                        + ',top=' + (($(window).height()-500)/2).toString()
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
            switch (j) {
            case 'status':
                var img = document.createElement('img');
                img.src = urlThemePath+'/icons/' + cm.statusicon;
                img.alt = cm.statustext;
                img.title = cm.statustext;
                img.className = 'dragicon';
                d.appendChild(img);
                break;
            case 'hpriority':
                if (cm.prioicon != '') {
                    var img = document.createElement('img');
                    img.src = urlThemePath+'/icons/' + cm.prioicon + '.gif';
                    img.alt = cm.priotext;
                    img.title = cm.priotext;
                    d.appendChild(img);
                } else {
                    d.appendChild(document.createTextNode(' '));
                }
                break;
            case 'attachments':
                if (cm.att == 1) {
                    var img = document.createElement('img');
                    img.src = urlThemePath+'/icons/attach.gif';
                    img.alt = '{msg_attach}';
                    img.title = '{msg_attach}';
                    d.appendChild(img);
                } else {
                    d.appendChild(document.createTextNode(' '));
                }
                break;
            case 'hsubject':
                if (pagestats.collapse_threads == 1 && cm.thread_id > 0) {
                    r.className += ' threadroot closed';
                    var div = document.createElement('div');
                    div.className = 'threadmarker';
                    div.id = 'threadroot_' + cm.thread_id;
                    $(div).click(get_threadchilds);
                    d.appendChild(div);
                }
                d.appendChild(document.createTextNode(cm.subj));
                d.title = cm.subj;
                break;
            case 'hfrom':
                d.appendChild(document.createTextNode(cm.from_3));
                d.title = cm.from_2;
                break;
            case 'hto':
                d.appendChild(document.createTextNode(cm.to_3));
                d.title = cm.to_2;
                break;
            case 'hcc':
                d.appendChild(document.createTextNode(cm.cc_3));
                d.title = cm.cc_2;
                break;
            case 'hdate_sent':
                d.appendChild(document.createTextNode(cm.date));
                d.title = cm.dateraw;
                break;
            case 'hsize':
                d.appendChild(document.createTextNode(cm.size));
                d.title = cm.sizeraw;
                break;
            default:
                d.appendChild(document.createTextNode(' '));
            }
            r.appendChild(d);
            fc++;
        }
        mlines.appendChild(r);
    }

    /* This shall evade the problem, that nothing is selected in a folder after
       deleting one or more mails by selecting the neighbour of the last selected
       mail after building the list again. */
    if (lastfocus && $('#ml_' + lastfocus).length == 0) {
        markeditems = {};
        if ($('#ml_' + lastfocus_prev).length == 1) {
            lastfocus = lastfocus_prev;
            markline(lastfocus_prev, true);
        } else if ($('#ml_' + lastfocus_next).length == 1) {
            lastfocus = lastfocus_next;
            markline(lastfocus_next, true);
        } else {
            lastfocus = false;
        }
    }

    // Mark the first mail in the list, don't override existing marks
    if ($.isEmptyObject(markeditems)) {
        $('#maillines > :first-child').trigger('click');
    }
}

function empty_maillist()
{
    $('#mailthead,#maillines').empty();
}

function reapplymarks()
{
    var i,v;
    var re = markeditems;
    var lastreapplied = false;
    anzahl = 0;
    markeditems = {};
    for (var i in re) {
        for (var j in maillines) {
            if (maillines[j].uidl == re[i]) {
                markline(i, true);
                lastreapplied = i;
                break;
            }
        }
    }
    if (anzahl == 0) {
        try { $('#preview_content').attr('src', 'about:blank'); } catch(e) {}
        fill_preview_header('', '', '', '', '', '', '', '');
        lastpreview = false;
    } else {
        preview(lastreapplied);
    }
    $.each(open_threads, function (i, v) { $('#threadroot_' + i).trigger('click'); });
}

function get_threadchilds(event)
{
    var ID = this.id.replace(/^threadroot_/, '');
    var myLine = $(this.parentNode.parentNode);
    var myRoot = myLine.attr('id').replace(/^ml_/, '');
    if (myLine.hasClass('closed')) {
        $('#threadkids_' + ID + ', .threadkid_' + ID).remove(); // In case it's still open
        myLine.removeClass('closed').addClass('open');
        $('<div id="threadkids_' + ID + '" class="loading" />').height(40).width(myLine.width()).insertAfter(myLine);
        $.ajax({'url': jsrequrl + '&thread_id=' + ID + '&ignore=' + myRoot, 'success' : got_threadchilds});
        open_threads[ID] = 1;
    } else {
        $('#threadkids_' + ID + ', .threadkid_' + ID).remove();
        myLine.removeClass('open').addClass('closed');
        delete open_threads[ID];
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    event.stopPropagation();
}

/* This is essentially a clone of build_maillist() stripped from the header line drawing */
function got_threadchilds(data)
{
    maillines = data.maillines;

    if ($(maillines).length == 0) {
        return true;
    }

    var myThread = maillines[1]['thread_id'];
    var myParent = $('#threadkids_' + myThread);
    myParent.css('height', '0px').removeClass('loading');

    for (var i in maillines) {
        var cm = maillines[i]; // Current mail

        var r = document.createElement('div');
        r.id = 'ml_' + cm.uidl;
        r.className = 'inboxline threadkid_' + myThread;
        if (cm.colour != '') {
            r.className += ' cmark_' + cm.colour;
        }
        if (cm.is_unread == 1) {
            r.className += ' unread';
        }
        r.oncontextmenu = function (e) {
            selectline(this.id.replace(/^ml_/, ''), e, true);
        }
        $(r).css({'width':myParent.innerWidth()+'px'}).click(function (e) {
            var src = this.id.replace(/^ml_/, '');
            selectline(src, e);
            preview(src);
            lastclicked = src;
        }).dblclick(function (e) {
            var id = this.id.replace(/^ml_/, '');
            window.open(urlEmailView + '&mail=' + id, 'mailread_' + id, 'width=700,height=500,left='
                    + (($(window).width()-700)/2).toString()
                    + ',top=' + (($(window).height()-500)/2).toString()
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
            switch (j) {
            case 'status':
                var img = document.createElement('img');
                img.src = urlThemePath+'/icons/' + cm.statusicon;
                img.alt = cm.statustext;
                img.title = cm.statustext;
                img.className = 'dragicon';
                d.appendChild(img);
                break;
            case 'hpriority':
                if (cm.prioicon != '') {
                    var img = document.createElement('img');
                    img.src = urlThemePath+'/icons/' + cm.prioicon + '.gif';
                    img.alt = cm.priotext;
                    img.title = cm.priotext;
                    d.appendChild(img);
                } else {
                    d.appendChild(document.createTextNode(' '));
                }
                break;
            case 'attachments':
                if (cm.att == 1) {
                    var img = document.createElement('img');
                    img.src = urlThemePath+'/icons/attach.gif';
                    img.alt = '{msg_attach}';
                    img.title = '{msg_attach}';
                    d.appendChild(img);
                } else {
                    d.appendChild(document.createTextNode(' '));
                }
                break;
            case 'hsubject':
                if (pagestats.collapse_threads == 1 && cm.thread_id > 0) {
                    r.className += ' threadchild';
                    var div = document.createElement('div');
                    div.className = 'threadmarker';
                    if (CurrFld != cm.folder_id) {
                        div.className += ' other_folder';
                        div.title = '{msg_thread_other_folder}';
                    }
                    d.appendChild(div);
                }
                d.appendChild(document.createTextNode(cm.subj));
                d.title = cm.subj;
                break;
            case 'hfrom':
                d.appendChild(document.createTextNode(cm.from_3));
                d.title = cm.from_2;
                break;
            case 'hto':
                d.appendChild(document.createTextNode(cm.to_3));
                d.title = cm.to_2;
                break;
            case 'hcc':
                d.appendChild(document.createTextNode(cm.cc_3));
                d.title = cm.cc_2;
                break;
            case 'hdate_sent':
                d.appendChild(document.createTextNode(cm.date));
                d.title = cm.dateraw;
                break;
            case 'hsize':
                d.appendChild(document.createTextNode(cm.size));
                d.title = cm.sizeraw;
                break;
            default:
                d.appendChild(document.createTextNode(' '));
            }
            r.appendChild(d);
            fc++;
        }
        myParent.append($(r));
    }
    myParent.replaceWith(myParent.contents());
}


function update_pagestats()
{
    var jumpSize = (pagestats.maxpage.toString().length) ? (pagestats.maxpage.toString().length) : 1;
    $('#WP_jumppage').val((pagestats.page) ? pagestats.page : 1).attr('size', jumpSize).attr('maxlength', jumpSize);
    if (skimbar_open == 1) { // Actually resets it
        open_skimbar();
        open_skimbar();
    }
    $('#pagenum').text((pagestats.maxpage == 0) ? '-' : pagestats.page + '/' + pagestats.maxpage);
    var folderInfo = '';
    if (pagestats.mailnum > 0) {
        folderInfo = pagestats.displaystart + ' - ' + pagestats.displayend + ' / ' + pagestats.mailnum + ' (' + pagestats.sumsize + ' / ' + pagestats.allsize + ')';
    }
    $('#folderinfo').attr('title', folderInfo);
    if (pagestats.use_preview == 1) {
        $('#preview,#preview_content').show();
        if (pagestats.allow_resize == 1) {
            $('#resize_v').show();
        } else {
            $('#resize_v').hide();
        }
    } else {
        $('#preview,#preview_content,#resize_v').hide();
    }
    mailnum = (pagestats.displayend == pagestats.displaystart)
            ? (pagestats.displayend == 0 ? 0 : 1)
            : parseInt(pagestats.displayend)-parseInt(pagestats.displaystart)+1;

    if (pagestats.folder_writable != 1) {
        ctxmen[4]['status'] = ctxmen[5]['status'] = ctxmen[6]['status'] = ctxmen[7]['status'] =
                ctxmen[8]['status'] = ctxmen[9]['status'] = ctxmen[10]['status'] = ctxmen[15]['status'] = ctxmen[18]['status'] = 3;
        $('#but_answer, #but_answerall, #but_forward, #but_bounce, #but_delete').addClass('noaction');
    } else {
        ctxmen[4]['status'] = ctxmen[5]['status'] = ctxmen[6]['status'] = ctxmen[7]['status'] =
                ctxmen[8]['status'] = ctxmen[9]['status'] = ctxmen[10]['status'] = ctxmen[15]['status'] = ctxmen[18]['status'] = 1;
        $('#but_answer, #but_answerall, #but_forward, #but_bounce, #but_delete').removeClass('noaction');
        ctxmen[6]['status'] = 3;
        ctxmen[7]['status'] = 3;
        if (pagestats.mark_junk == 1) {
            if (pagestats.is_junk == 1) {
                ctxmen[7]['status'] = 1;
            } else {
                ctxmen[6]['status'] = 1;
            }
        }
    }
    if ($('#search_criteria').length == 1) {
        if (pagestats.is_imap == 1) {
            document.getElementById('search_criteria').options[4].style.color = '';
            document.getElementById('search_criteria').options[4].disabled = false;
        } else {
            document.getElementById('search_criteria').options[4].style.color = '#CCC';
            document.getElementById('search_criteria').options[4].disabled = true;
        }
    }

    if (pagestats.is_imap == 1) {
        if (imap_checked == 0) {
            imap_checked = 1;
            parent.emailfetch_init('user', 0, CurrFld);
        }
    }
}

function refreshlist(additional)
{
    if (!additional) additional = '';
    AJAX_call(jsrequrl + additional);
}

function gl_switch()
{
    var Me = $(this);

    if (Me.hasClass('inbxgrpo')) {
        var newcls = 'inbxgrpc';
        var newcss = 'none';
    } else {
        var newcls = 'inbxgrpo';
        var newcss = 'block';
    }
    Me.removeClass('inbxgrpo inbxgrpc').addClass(newcls);
    var mygroup = parseInt(this.id.replace(/^gl_/, ''));
    var from = groupby_groups[mygroup];
    var to = (groupby_groups[(mygroup+1)]) ? groupby_groups[(mygroup+1)] : mailnum+1;
    for (var i = from; i < to; ++i) {
        try { document.getElementById('ml_' + i).style.display = newcss; } catch (e) {}
    }
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
    var tb = $('#maillines').children();
    var tbl = tb.length;
    var lineid;
    tb.each(function () {
        lineid = $(this).attr('id').replace(/^ml_/, '');
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
        if (!lastfocus) {
            markline(lineid);
        }
        return true;
    }
    if (!e && window.event) {
        e = window.event;
    }
    if ((!e.ctrlKey && !e.shiftKey) || (!lastfocus && e.shiftKey)) {
        set_boxes(0);
        lastfocus = lineid;
        markline(lineid);
    } else if (e.shiftKey) {
        var dfrom = lastfocus*1;
        var dto = lineid*1;
        set_boxes(0);
        lastfocus = dfrom;
        var draw = 0;
        $('#maillines').children().each(function() {
            var ID = $(this).attr('id').replace(/^ml_/, '');
            if (ID == dfrom || ID == dto) draw++;
            if (draw > 0 && !markeditems[ID]) {
                markline(ID);
            }
            if (draw > 1) {
                return false;
            }
        });
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
    var item = $('#ml_' + lineid);
    if (item.length == 0) {
        return true;
    }

    if (markeditems[lineid]) {
        // unset mark
        delete markeditems[lineid];
        anzahl--;
        $('#ml_' + lineid).removeClass('marked');
    } else {
        // set mark
        markeditems[lineid] = lineid;
        anzahl++;
        item.addClass('marked');
        if (rescroll) {
            if (lineid == lastclicked) preview(lineid);
        }
        var myOffset = item.get(0).offsetTop - $('#maillines').offset()['top'];
        var myHeight = item.height();
        if ((myOffset + myHeight) > $(mlines).height() + mlines.scrollTop) {
            mlines.scrollTop = myOffset + myHeight - $(mlines).height();
        } else if (myOffset < mlines.scrollTop) {
            mlines.scrollTop = myOffset;
        }
    }
    // Hiding unavailable options or show available ones respectively
    if (anzahl == 1) {
        ctxmen[12]['status'] = ctxmen[13]['status'] = ctxmen[14]['status'] = ctxmen[15]['status'] = ctxmen[16]['status'] = ctxmen[18]['status'] = 1;
    } else {
        ctxmen[12]['status'] = ctxmen[13]['status'] = ctxmen[14]['status'] = ctxmen[15]['status'] = ctxmen[16]['status'] = ctxmen[18]['status'] = 3;
        if (anzahl != 0) {
            ctxmen[15]['status'] = ctxmen[18]['status'] = 1;
        }
    }
    if (anzahl == 0) {
        lastfocus = false;
        markeditems = {};
    }
    // Let the topbuttonbar know, what's up
    setlinks(anzahl);
}

function get_selected_items()
{
    var selected = [];
    $.each(markeditems, function (i, v) { selected.push(v); });
    return selected;
}

function jumppage()
{
    refreshlist('&WP_jumppage=' + $('#WP_jumppage').val());
    return false;
}

function skim(ud)
{
    refreshlist('&WP_core_pagenum=' + (pagestats.pagenum + (ud == '+' ? 1 : -1)));
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
    if (pagestats.use_preview == 1) {
        preview_visible = 1;
        if (!parent.email_customheight_preview || parent.email_customheight_preview+toph > avail_screen * 0.95) {
            var prevheight = parseInt(avail_screen * 0.5);
            usedh += prevheight;
            var prev_cont = document.getElementById('preview_content');
            var prev_height = document.getElementById('preview').offsetHeight;
            prev_cont.style.height = (prevheight - prev_height) + 'px';
        } else {
            document.getElementById('preview_content').style.height = parent.email_customheight_preview + 'px';
            usedh += (parent.email_customheight_preview + document.getElementById('preview').offsetHeight)*1;
        }
    }
    mlines = document.getElementById('maillines');
    mlines.style.height = (avail_screen - usedh) + 'px';
    topmenu = document.getElementById('topmen');
    if (PageLoaded && mailnum > 0) resize_maillist();
}

function resize_maillist()
{
    if (maillines.length == 0) return;

    var mthead = document.getElementById('mailthead');
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
    // Evenly distribute avail width to flexwidth fields
    if (verteilcount > 0) {
        availwidth /= verteilcount;
        for (i in verteilfields) {
            document.getElementById('mlh_' + verteilfields[i]).style.width = Math.floor(availwidth) + 'px';
            pagestats.showfields[verteilfields[i]].w = Math.floor(availwidth);
        }
    }
    $('#maillines .inboxline').each(function () {
        var r = $(this).get(0);
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
    });

}

function preview(id)
{
    if (TOMarkRead != false) window.clearTimeout(TOMarkRead);
    if (pagestats.use_preview != 1) {
        // No preview available for that folder
        return;
    }
    if (anzahl != 1) {
        $('#preview_content').attr('src', 'about:blank');
        if (typeof parent.previewplus == 'undefined') parent.previewplus = '';
        $('#pre_head' + parent.previewplus).hide();
        lastpreview = false;
        return;
    }
    if (!markeditems[id]) return;
    if (lastpreview != id) {
        lastpreview = id;
        $('#preview_unblock').hide();
        resize_elements();
        $('#preview_content').attr('src', '{preview_url}' + id);
    }
    if (pagestats.automarkread != '' && $('#ml_' + id).hasClass('unread')) {
        TOMarkRead = window.setTimeout('collect_and_react_email("mark");', parseInt(pagestats.automarkread) * 1000);
    }
}

function preview_blocked()
{
    if (!document.getElementById('preview_content')) return;
    $('#preview_unblock').show();
    resize_elements();
}

function preview_unblock(what, argument)
{
    var urlAdd = '&sanitize=0';
    if (what != 'single' && typeof argument != 'undefined') {
        urlAdd += '&unblockfilter=' + encodeURIComponent(argument);
    }
    var Me = $('#preview_content');
    Me.attr('src', Me.attr('src') + urlAdd);

    $('#preview_unblock').hide();
    resize_elements();
}

function fill_preview_header(from, xfrom, to, subj, cc, replyto, date, imgurl)
{
    if (typeof parent.previewplus == 'undefined') parent.previewplus = '';
    var pp = parent.previewplus;
    document.getElementById('pre_head' + pp).style.display = 'block';

    try {
        var xfrom_email = '{msg_unblock_email}'.replace(/\$1\$/, xfrom);
        $('#pre_unblock_email').attr('title', xfrom).unbind('click').click(function () { preview_unblock('email', $(this).attr('title')); } )
        xfrom = xfrom.split('@');
        var xfrom_domain = '{msg_unblock_domain}'.replace(/\$1\$/, xfrom[1]);
        $('#pre_unblock_domain').attr('title', xfrom[1]).unbind('click').click(function () { preview_unblock('domain', '@' + $(this).attr('title')); } )
    } catch (e) {}
    // Get DOM nodes
    var fields =
            {'pre_from' : from, 'pre_from_plus' : from, 'pre_to' : to
            ,'pre_to_plus' : to, 'pre_subj' : subj, 'pre_subj_plus' : subj
            ,'pre_replyto_plus' : replyto, 'pre_date_plus' : date
            ,'pre_cc_plus' : cc
            ,'pre_unblock_email' : xfrom_email, 'pre_unblock_domain' : xfrom_domain
            };
    for (var i in fields) {
        try { document.getElementById(i).innerHTML = fields[i]; } catch (e) { }
    }
    if (imgurl) {
        document.getElementById('pre_img_plus').innerHTML = '<img src="{theme_path}/images/x_image_placeholder.png" onclick="show_face_image(\''
                + encodeURIComponent(imgurl) +'\');" id="previewsenderimage" alt="Face Image" title="Face Image" style="cursor:pointer;" />';
    } else {
        document.getElementById('pre_img_plus').innerHTML = '';
    }
    resize_elements();
}

function switch_preview_headers(plus)
{
    if (plus == true) {
        $('#pre_head').hide();
        parent.previewplus = '_plus';
    } else {
        $('#pre_head_plus').hide();
        parent.previewplus = '';
    }
    $('#pre_head' + parent.previewplus).show();
    resize_elements();
}

function show_face_image(imgurl)
{
    var ele = document.getElementById('previewsenderimage');
    ele.src = decodeURIComponent(imgurl);
    ele.style.cursor = 'default';
    ele.onclick = null;
    if (ele.offsetHeight > 80) ele.style.height = '80px';
}

function mailaction(ops, alternate, opsfolder)
{
    if (fetcher_list.length > 0) return false;
    AJAX_url = mailops_url + ops;
    if (opsfolder) {
        AJAX_url += (ops == 'colourmark' ? '&colour=' : '&folder=') + opsfolder;
    }
    if (alternate) AJAX_url += '&alternate=1';
    fetcher_list = get_selected_items();
    AJAX_action = 'mailops';
    AJAX_ops = ops;
    update_counter(0, fetcher_list.length);
}

function search_me()
{
    var crit = $('#search_criteria').val();
    var pattern = encodeURIComponent($('#search_pattern_txt').val());
    if (pattern.length == 0) crit = '';
    var flags = '&searchflags=';

    $('#chk_search_unread:checked,#chk_search_forwarded:checked,#chk_search_answered:checked,#chk_search_bounced:checked,#chk_search_attach:checked,#chk_search_coloured:checked').each(function () {
        switch (this.id) {
            case 'chk_search_unread': flags += '&searchflags[unread]=1'; break;
            case 'chk_search_forwarded': flags += '&searchflags[forwarded]=1'; break;
            case 'chk_search_answered': flags += '&searchflags[answered]=1'; break;
            case 'chk_search_bounced': flags += '&searchflags[bounced]=1'; break;
            case 'chk_search_attach': flags += '&searchflags[attachments]=1'; break;
            case 'chk_search_coloured': flags += '&searchflags[coloured]=1'; break;
        }
    });
    refreshlist('&criteria=' + crit + '&pattern=' + pattern + flags);
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

function AJAX_call(url)
{
    $.ajax({'url': url, 'success': AJAX_process});
}

function AJAX_process(next)
{
    if (next['adb_found']) {
        adb_found(next['adb_found']);
        return;
    }
    if (next['deleted']) {
        total_deleted = next['deleted'];
    }
    if (next['updated']) {
        total_updated = next['updated'];
    }
    if (next['error']) {
        alert(next['error']);
    } else {
        if (next['items']) {
            if (next['items'].length == 0) {
                update_counter();
            } else {
                fetcher_list = next['items'];
                update_counter(0, next['items'].length);
            }
        }
        if (next['page_stats']) {
            pagestats = next['page_stats'];
            maillines = next['maillines'];
            jsrequrl = next['jsrequrl'];
            empty_maillist();
            build_maillist();
            reapplymarks();
        }
    }
    if (next['done']) {
       update_counter();
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

/**
* Customized drag object for resizing the preview window via mouse drag
*/
dragy =
{
    dragobj: null
    ,oy: 0
    ,my: 0
    ,oldmove: (document.onmousemove) ? document.onmousemove : null
    ,oldup: (document.onmouseup) ? document.onmouseup : null
    ,start: function(e) {
            if (!e) e = window.event;
            if (e.target) {
                targ = e.target;
            } else if (e.srcElement) {
                targ = e.srcElement;
            }
	        if (targ.nodeType == 3) {
	            // defeat Safari bug
	            targ = targ.parentNode;
	        }
            dragy.dragobj = targ;

	        if (e.pageY) {
	            dragy.oy = e.pageY;
	        } else if (e.clientY) {
	            dragy.oy = e.clientY + document.body.scrollTop;
	        }
            dragy.previewheight = document.getElementById('preview_content').offsetHeight;
            dragy.maillinesheight = document.getElementById('maillines').offsetHeight;
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
                document.getElementById('maillines').style.height = (dragy.maillinesheight + (dragy.my - dragy.oy)) + 'px';
                document.getElementById('preview_content').style.height = (dragy.previewheight - (dragy.my - dragy.oy)) + 'px';
                parent.email_customheight_preview = document.getElementById('preview_content').offsetHeight;
            }
        }
    ,stop: function() {
            dragy.dragobj = null;
            document.onmouseup = (dragy.oldup) ? dragy.oldup : null;
            document.onmousemove = (dragy.oldmove) ? dragy.oldmove : null;
            parent.save_custom_size('email_previewheight', parent.email_customheight_preview);
            if (preview_visible == 1) {
                document.getElementById('preview_content').style.visibility = 'visible';
            }
        }
}

function update_counter(curr, total)
{
    if (total) {
        total_fetched = total;
        if (AJAX_action == 'fetch') {
        }
    }
    if (!curr) {
        curr_fetch++;
    }
    if (curr_fetch <= total_fetched) {
        if (AJAX_action == 'fetch') {
            AJAX_call('{fetcher_url}&step=3&uidl=' + encodeURIComponent(fetcher_list[(curr_fetch-1)]));
        } else if (AJAX_action == 'mailops') {
            AJAX_call(AJAX_url + '&mail=' + encodeURIComponent(fetcher_list[(curr_fetch-1)]));
        }
    } else {
        curr_fetch = 0;
        fetcher_list = [];
        if (total_fetched != 0 || total_deleted != 0 || total_updated != 0) {
            parent.email_ext_checkmails();
            refreshlist();
        }
    }
}

function sendtoadb(address, realname)
{
    var mytime = new Date();
    if (address == realname) realname = '';
    window.open
            ('{link_sendtoadb}&email1=' + encodeURIComponent(address) + '&firstname=' + encodeURIComponent(realname)
            ,'contact_' + mytime.getTime()
            ,'width=870,height=620,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            )
}

function openadb(aid)
{
    var mytime = new Date();
    window.open
            ('{link_sendtoadb}&id=' + aid
            ,'contact_' + mytime.getTime()
            ,'width=870,height=620,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            )
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

function open_infobar()
{
    if (infobar_open == 0) {
        infobar_open = 1;
        $('#folderinfo').addClass('open');
        $('#folderinfobar').show();
        actionpane_open++;
    } else {
        infobar_open = 0;
        $('#folderinfo').removeClass('open');
        $('#folderinfobar').hide();
        actionpane_open--;
    }
    open_actionpane();
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
        $('#skimbar').hide();
        $('#skimslider').slider('destroy');
        actionpane_open--;
    }
    open_actionpane();
}

function create_bounce_form()
{
    if (bounce_list.length < 1) {
        cancel_bounce();
    }
    var i, ml;
    keyfetch_off();
    $('#mail_bouncer').show();
    if (bounce_list.length < 2) {
        $('#mail_bounce_all').attr('disabled', 'disabled');
    } else {
        $('#mail_bounce_all').removeAttr('disabled');
    }
    $.each(maillines, function (i, ml) {
        if (ml.uidl == bounce_list[0]) {
            $('#mail_bounce_ofrom').text(ml.from_3);
            $('#mail_bounce_osubj').text(ml.subj);
            return false;
        }
    });
}

function cancel_bounce()
{
    keyfetch_on();
    bounce_del = bounce_all = 0;
    var mBo = $('#mail_bouncer');
    mBo.find('input, button').removeAttr('disabled').removeAttr('checked');
    mBo.find('#mail_bounce_ofrom, #mail_bounce_osubj,#mail_bouncestat_msg').text('');
    mBo.find('#mail_bounce_to').val('');
    mBo.find('#mail_bounce_action').hide();
    mBo.hide();
}

function start_bounce(internal)
{
    var UID = bounce_list.shift();
    if (!internal) {
        if ($('#mail_bounce_all:checked').length == 1) {
            bounce_all = 1;
        }
        if ($('#mail_bounce_del:checked').length == 1) {
            bounce_del = UID;
        }
    }
    $('#mail_bouncestat_msg').text('{bounce} ...');
    $('#mail_bounce_action').show();
    $.ajax({url : '{bounce_url}' + UID + '&to=' + encodeURIComponent($('#mail_bounce_to').val()), dataType : 'json', success : process_bounce })
    $('#mail_bouncer').find('input,button').attr('disabled', 'disabled');
}

function process_bounce(next)
{
    if (next['error']) {
        alert(next['error']);
    }
    if (next['done']) {
        if (bounce_del != 0) {
            $.ajax({'url' : mailops_url + 'delete&alternate=1&mail[]=' + bounce_del, 'dataType' : 'json'});
        }
        if (bounce_list.length < 1) {
            cancel_bounce();
        } else {
            create_bounce_form();
            if (bounce_all != 1) {
                $('#mail_bouncer').find('input, button').removeAttr('disabled');
                $('#mail_bounce_action').hide();
                if (bounce_list.length < 2) {
                    $('#mail_bounce_all').attr('disabled', 'disabled');
                }
            } else {
                start_bounce(1);
            }
        }
    } else {
        $('#mail_bouncestat_msg').text(next['statusmessage']);
        $.ajax({'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : process_bounce});
    }
}

function collect_and_react_email(ops, e)
{
    var ID, url, width, height;

    var list = get_selected_items();
    if (list.length == 0) ops = false;
    var alternate = 0;

    lastfocus_next = lastfocus_prev = false;
    if (lastfocus) {
        var $lf = $('#ml_' + lastfocus);
        if ($lf.next().length) {
            lastfocus_next = $lf.next().attr('id').replace('ml_', '');
        }
        if ($lf.prev().length) {
            lastfocus_prev = $lf.prev().attr('id').replace('ml_', '');
        }
    }

    switch (ops) {
    case 'answer':
    case 'answerAll':
    case 'forward':
    case 'draft':
    case 'template':
        if (list.length != 1) return false;
        url = '{PHP_SELF}?{passthrough}&h=core&from_h={handler}&l=compose_email&replymode=' + ops;
        for (ID in list) { url += '&mail=' + list[ID]; }

        width = $(window).width()*.9;
        height = $(window).height()*.9;
        window.open
                (url
                ,'send_' + (Math.random() + '').replace(/\./, '_')
                ,'width=' + width + ',height=' + height + ',scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
                );
        break;
    case 'bounce':
        bounce_list = list;
        create_bounce_form();
        break;
    case 'save':
        if (list.length != 1) return false;
        // Involve the read module
        url = '{PHP_SELF}?{passthrough}&h=email&l=read&save_as=raw';
        for (ID in list) { url += '&mail=' + list[ID]; }
        self.location.href = url;
        break;
    case 'viewsrc':
    case 'print':
        if (list.length != 1) return false;
        // Involve the read module
        url = '{PHP_SELF}?{passthrough}&h=email&l=read';
        url += (ops == 'print') ? '&print=1' : '&viewsrc=1';
        for (ID in list) { url += '&mail=' + list[ID]; }
        window.open
                (url
                ,'print' + (Math.random() + '').replace(/\./, '_')
                ,'width=600,height=500,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
                );
        break;
    case 'precopy':
        create_folder_browser('copy', 'email');
        break;
    case 'premove':
        create_folder_browser('move', 'email');
        break;
    case 'delete':
        // Shift held -> throw them away, no dustbin at all
        try { if (typeof event != 'undefined' && typeof window.event.shiftKey != 'undefined') { e = window.event; } } catch (m) { }
        if (e.shiftKey) {
            answer = confirm('{msg_killconfirm}');
            if (!answer) {
                break;
            }
            drop_screen_selection();
            url = mailops_url + ops + '&alternate=1';
            for (ID in list) { url += '&mail[]=' + list[ID]; }
            $.ajax({url : url, dataType : 'json', success : cnr_instant});
            break;
        }
    case 'copy':
    case 'move':
    case 'archive':
    case 'spam':
    case 'unspam':
        // Involve the mail management module
        if ('copy' == ops || 'move' == ops) {
            window.setTimeout('mailaction("' + ops + '",' + alternate + ',"' + opsfolder + '")', 0);
        } else {
            window.setTimeout('mailaction("' + ops + '",' + alternate + ')', 0);
        }
        break;
    case 'mark':
    case 'unmark':
        url = mailops_url + ops;
        for (ID in list) { url += '&mail[]=' + list[ID]; }
        $.ajax({ url : url, dataType : 'json', success : cnr_instant});
        break;
    case 'colourmark':
        var top, left, right, bottom, menu, cX, cY;
        var msie = (typeof event != 'undefined');
        cX = Math.abs(e.clientX);
        cY = Math.abs(e.clientY);
        if (msie) e = window.event;
        menu = document.getElementById('mail_colourpick');
        menu.style.display = 'block';
        // Find out, how close we are to document corners, position accordingly
        right =  msie ? document.body.clientWidth - cX : window.innerWidth - cX;
        bottom = msie ? document.body.clientHeight - cY : window.innerHeight - cY;
        var mywidth  = menu.offsetWidth;
        var myheight = menu.offsetHeight;
        // Too far to the right ?
        if (right < mywidth) { // place it inside document's bounds
            left = msie ? document.body.scrollLeft + cX - (mywidth - right + 5) : window.pageXOffset + cX - (mywidth - right + 5);
        } else { // Let it pop up right where the mouse was clicked
            left = msie ? document.body.scrollLeft + cX : window.pageXOffset + cX;
        }
        // repeat game with bottom pos
        if (bottom < myheight) {
            top = msie ? document.body.scrollTop + cY - (myheight - bottom + 5) : window.pageYOffset + cY - (myheight - bottom + 5);
        } else if (myheight) {
            top = msie ? document.body.scrollTop + cY : window.pageYOffset + cY;
        } else {
            top = 20;
        }
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        menu.style.zIndex = 11;
        break;
    }
}

// Called after instant AJAX actions of collect_and_react_email above (e.g. colourmark, forced delete, (un)mark)
function cnr_instant()
{
    refreshlist();
    parent.email_check_mails();
}

function set_colour(colour)
{
    var list, url, ID;

    if (!colour || colour == 'FFFFFF') colour = 'none';
    $('#mail_colourpick').hide();

    list = get_selected_items();
    url = mailops_url + 'colourmark&colour=' + colour;
    for (ID in list) { url += '&mail[]=' + list[ID]; }
    $.ajax({ url : url, dataType : 'json', success : cnr_instant});
}

//
// Handle address book search for bounce recipients
//
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
        $.ajax({'url': '{search_adb_url}&find=' + encodeURIComponent(search_adb_for), 'success' : AJAX_process});
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

    div = document.createElement('div');
    div.id = 'adb_show_hits';
    div.style.position = 'absolute';
    div.style.top = (mycont.offsetHeight-1) + 'px';
    div.style.left = '0px';
    div.style.zIndex = "100";
    div.onmouseover = adb_mark_hit;
    div.onmouseout = adb_unmark_hit;
    div.onclick = adb_choose_hit;
    // Enabale reacting on cursors / enter
    $(window).bind('keydown.drop', adb_check_keys);

    div.style.width = (document.getElementById(search_adb_field).offsetWidth-2) + 'px';
    div.style.border = '1px solid black';
    div.style.backgroundColor = 'white';

    for (var i in search_adb_cache) {
        show_string = search_adb_cache[i].show_string;
        fundstart = show_string.toLowerCase().indexOf(search_adb_for.toLowerCase());
        if (-1 == fundstart) continue;
        fundende = fundstart + search_adb_for.length;
        l = document.createElement('div');
        l.className = 'adbfound';
        l.id = 'hit_' + i;
        l.appendChild(document.createTextNode(show_string.substr(0, fundstart)));
        s = document.createElement('strong');
        s.appendChild(document.createTextNode(show_string.substring(fundstart, fundende)));
        l.appendChild(s);
        l.appendChild(document.createTextNode(show_string.substring(fundende, show_string.length)));
        div.appendChild(l);
    }
    mycont.appendChild(div);
    adb_select_hit(0); // Select the first hit

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

//
// End address book search
//

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
        if (mailnum > 0) {
            var mailID = $('#maillines :last-child').attr('id');
            selectline(mailID, e);
            preview(mailID.replace(/^ml_/, ''));
        }
        break;
    case 36: // Home
        fetched = true;
        if (mailnum > 0) {
            var mailID = $('#maillines :first-child').attr('id');
            selectline(mailID, e);
            preview(mailID.replace(/^ml_/, ''));
        }
        break;
    case 38: // Cursor up
        fetched = true;
        if (!lastfocus) {
            var mailID = $('#maillines :first-child').attr('id');
        } else {
            var where = $('#ml_' + lastfocus).prev();
            if (where.length == 0) {
                break;
            }
            var mailID = where.attr('id');
        }
        selectline(mailID, e);
        preview(mailID.replace(/^ml_/, ''));
        break;
    case 40: // Cursor down
        fetched = true;
        if (!lastfocus) {
            var mailID = $('#maillines :first-child').attr('id');
        } else {
            var where = $('#ml_' + lastfocus).next();
            if (where.length == 0) {
                break;
            }
            var mailID = where.attr('id');
        }
        selectline(mailID, e);
        preview(mailID.replace(/^ml_/, ''));
        break;
    case 46: // Entf (Del)
        if (evt.ctrlKey) {
            exec = 'collect_and_react_email("archive")';
            fetched = true;
        } else {
            collect_and_react_email('delete', e);
            fetched = true;
        }
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
    case 67: // C
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_email("precopy")';
            fetched = true;
        } else if (evt.shiftKey) {
            exec = 'collect_and_react_email("colourmark")';
            fetched = true;
        }
        break;
    case 70: // F
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_email("bounce")';
            fetched = true;
        } else if (evt.ctrlKey) {
            exec = 'collect_and_react_email("forward")';
            fetched = true;
        }
        break;
    case 74: // J
        if (evt.shiftKey) {
            if (typeof ctxmen[6] != 'undefined') {
                exec = 'collect_and_react_email("spam")';
                fetched = true;
            } else if (typeof ctxmen[6] != 'undefined') {
                exec = 'collect_and_react_email("unspam")';
                fetched = true;
            }
        }
        break;
    case 77: // M
        if (evt.shiftKey) {
            exec = 'collect_and_react_email("unmark")';
            fetched = true;
        } else {
            exec = 'collect_and_react_email("mark")';
            fetched = true;
        }
        break;
    case 80: // P
        if (evt.ctrlKey) {
            exec = 'collect_and_react_email("print")';
            fetched = true;
        }
        break;
    case 82: // R
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_email("answerAll")';
            fetched = true;
        } else if (evt.ctrlKey) {
            exec = 'collect_and_react_email("answer")';
            fetched = true;
        }
        break;
    case 83: // S
        if (evt.ctrlKey) {
            exec = 'collect_and_react_email("save")';
            fetched = true;
        }
        break;
    case 85: // U
        if (evt.ctrlKey) {
            exec = 'collect_and_react_email("viewsrc")';
            fetched = true;
        }
        break;
    case 86: // V
        if (evt.ctrlKey && evt.shiftKey) {
            exec = 'collect_and_react_email("premove")';
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
$(document).ready(function (e) {
    setlinks(0);
    resize_elements();
    build_maillist();
    reapplymarks();
    keyfetch_on();
    PageLoaded = 1;
    // Make sure, the focus is set on THIS iframe
    parent.PHM_tr.focus();
    $('#topmen').focus();
});
$(window).resize(resize_elements);

//]]>
</script>
<div id="topmen">
    <div id="buttonbar_email" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="single" id="but_answer" onclick="collect_and_react_email('answer', event);">
                    <img src="{theme_path}/icons/answer.gif" alt="" /><span>{answer}</span>
                </li>
                <li class="single" id="but_answerall" onclick="collect_and_react_email('answerAll', event);">
                    <img src="{theme_path}/icons/answerall.gif" alt="" /><span>{answerAll}</span>
                </li>
                <li class="single" id="but_forward" onclick="collect_and_react_email('forward', event);">
                    <img src="{theme_path}/icons/forward.gif" alt="" /><span>{forward}</span>
                </li>
                <li class="notnull" id="but_bounce" onclick="collect_and_react_email('bounce', event);">
                    <img src="{theme_path}/icons/bounce.gif" alt="" /><span>{bounce}</span>
                </li>
                <li class="single" id="but_print" onclick="collect_and_react_email('print', event);">
                    <img src="{theme_path}/icons/print_men.gif" alt="" /><span>{print}</span>
                </li>
                <li class="notnull" id="but_archive" onclick="collect_and_react_email('archive', event);">
                    <img src="{theme_path}/icons/archive_men.gif" alt="" /><span>{archive}</span>
                </li>
                <li class="notnull" id="but_delete" onclick="collect_and_react_email('delete', event);">
                    <img src="{theme_path}/icons/delete.gif" alt="" /><span>{del}</span>
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
                <li class="activebut men_drop imgonly" id="skim" onclick="open_skimbar();">
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
                <form action="#" id="jumpform" method="get" style="display:inline;" onsubmit="return jumppage();">
                    <span id="pagenum"> </span>&nbsp;
                    <input type="text" size="1" maxlength="1" id="WP_jumppage" name="WP_jumppage" value="" />&nbsp;
                    <input type="submit" id="submit_jump" value="{go}" />
                </form>
            </div>

            <div id="searchbar" style="display:none;float:left;">
                <form action="#" id="searchform" method="get" style="display:inline;" onsubmit="return search_me();">
                    <input type="checkbox" name="unread" value="1" id="chk_search_unread" />
                    <label for="chk_search_unread"><img src="{theme_path}/icons/mail_unread.gif" alt="" title="" /></label>
                    &nbsp;
                    <input type="checkbox" name="forwarded" value="1" id="chk_search_forwarded" />
                    <label for="chk_search_forwarded"><img src="{theme_path}/icons/mail_forward.gif" alt="" title="" /></label>
                    &nbsp;
                    <input type="checkbox" name="answered" value="1" id="chk_search_answered" />
                    <label for="chk_search_answered"><img src="{theme_path}/icons/mail_answer.gif" alt="" title="" /></label>
                    &nbsp;
                    <input type="checkbox" name="bounced" value="1" id="chk_search_bounced" />
                    <label for="chk_search_bounced"><img src="{theme_path}/icons/mail_readbounced.gif" alt="" title="" /></label>
                    &nbsp;
                    <input type="checkbox" name="attach" value="1" id="chk_search_attach" />
                    <label for="chk_search_attach"><img src="{theme_path}/icons/attach.gif" alt="" title="" /></label>
                    &nbsp;
                    <input type="checkbox" name="coloured" value="1" id="chk_search_coloured" />
                    <label for="chk_search_coloured"><img src="{theme_path}/icons/colourmark_ctx.gif" alt="" title="" /></label>
                    &nbsp;<!-- START nofulltextsearch -->
                    <select size="1" name="criteria" id="search_criteria">
                        <option value="from">{msg_from}</option>
                        <option value="to">{msg_sto}</option>
                        <option value="subject">{msg_subject}</option>
                        <option value="allheaders">{msg_sallheaders}</option>
                        <option value="body">{msg_sbody}</option>
                        <option value="complete">{msg_scomplete}</option>
                    </select><!-- END nofulltextsearch -->
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="image" src="{theme_path}/icons/search.gif" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
    <div class="sendmenubut shadowed" id="mail_colourpick">
        <table border="0" cellpadding="0" cellspacing="2">
            <tr>
                <td onclick="set_colour('800000');"><div class="pick" style="background:#800000;">&nbsp;</div></td>
                <td onclick="set_colour('008000');"><div class="pick" style="background:#008000;">&nbsp;</div></td>
                <td onclick="set_colour('000080');"><div class="pick" style="background:#000080;">&nbsp;</div></td>
                <td onclick="set_colour('808000');"><div class="pick" style="background:#808000;">&nbsp;</div></td>
                <td onclick="set_colour('008080');"><div class="pick" style="background:#008080;">&nbsp;</div></td>
            </tr>
            <tr>
                <td onclick="set_colour('800080');"><div class="pick" style="background:#800080;">&nbsp;</div></td>
                <td onclick="set_colour('808080');"><div class="pick" style="background:#808080;">&nbsp;</div></td>
                <td onclick="set_colour('FF0000');"><div class="pick" style="background:#FF0000;">&nbsp;</div></td>
                <td onclick="set_colour('00FF00');"><div class="pick" style="background:#00FF00;">&nbsp;</div></td>
                <td onclick="set_colour('0000FF');"><div class="pick" style="background:#0000FF;">&nbsp;</div></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td onclick="set_colour('FFFF00');"><div class="pick" style="background:#FFFF00;">&nbsp;</div></td>
                <td onclick="set_colour('00FFFF');"><div class="pick" style="background:#00FFFF;">&nbsp;</div></td>
                <td onclick="set_colour('FF00FF');"><div class="pick" style="background:#FF00FF;">&nbsp;</div></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:center;"><button type="button" onclick="set_colour('FFFFFF');">{del}</button></td>
            </tr>
        </table>
    </div>
    <div id="mail_bouncer" class="sendmenubut shadowed" style="position:absolute;display:none;width:350px;height:185px;left:20px;top:40px;">
        <table border="0" cellpadding="2" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th class="c" colspan="2">{bounce}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="l">{preview_hfrom}:</td>
                    <td class="l"><div id="mail_bounce_ofrom" style="overflow:hidden;"></div></td>
                </tr>
                <tr>
                    <td class="l">{preview_subject}:</td>
                    <td class="l"><div id="mail_bounce_osubj" style="overflow:hidden;"></div></td>
                </tr>
                <tr>
                    <td class="l">{preview_hto}:</td>
                    <td class="l">
                        <div id="mail_bounce_to_container" style="position:relative;">
                            <input type="text" size="40" style="width:99%;" name="mail_bounce_to" id="mail_bounce_to" autocomplete="off" onkeyup="search_adb('mail_bounce_to', this.value);" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="mail_bounce_del" id="mail_bounce_del" value="1" />
                        <label for="mail_bounce_del">{msg_bounce_del}</label><br />
                        <input type="checkbox" name="mail_bounce_all" id="mail_bounce_all" value="1" />
                        <label for="mail_bounce_all">{msg_bounce_all}</label>
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">
                        <div>
                            <button type="button" class="ok" id="mail_bounce_start" onclick="start_bounce();" style="float:right;">OK</button>
                            <button type="button" class="error" onclick="cancel_bounce();">{msg_cancel}</button>
                        </div>
                        <div id="mail_bounce_action" style="margin-top:4px;display:none;height:40px;">
                            <div class="c t" id="mail_bouncestat_msg"> </div>
                            <div class="prgr_outer">
                                <div class="prgr_inner_busy"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="mailthead" style="overflow:hidden;vertical-align:top;text-align:left;height:16px;"></div>
</div>
<div id="maillines" style="overflow:auto;vertical-align:top;text-align:left;" onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();"></div>
<div id="preview">
    <div class="sendmenubut"><div id="resize_v" style="display:none;padding:0;width:100px;height:4px;margin:auto;cursor:n-resize;" onmousedown="dragy.start(event);"><img src="{theme_path}/images/resize_v.gif" alt="-" title="-" /></div>
        <div id="pre_head">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    <tr>
                        <td class="l pre_head_switcher" onclick="switch_preview_headers(true);"><img src="{theme_path}/icons/nav_down.png" alt="" title="+" /></td>
                        <td class="l">&nbsp;&nbsp;<strong>{preview_subject}: </strong><span id="pre_subj"></span></td>
                        <td class="r">&nbsp;&nbsp;<strong>{preview_hfrom}: </strong><span id="pre_from"></span></td>
                        <td class="r">&nbsp;&nbsp;<strong>{preview_hto}: </strong><span id="pre_to"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pre_head_plus">
            <div id="pre_img_plus"></div>
            <table border="0" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="l t pre_head_switcher" rowspan="6" onclick="switch_preview_headers(false);"><img src="{theme_path}/icons/nav_up.png" alt="" title="-" /></td>
                        <td class="l t"><strong>{preview_hfrom}: </strong></td>
                        <td class="l t"><div id="pre_from_plus"></div></td>
                    </tr>
                    <tr id="tr_pre_replyto_plus">
                        <td class="l t"><strong>Reply-To: </strong></td>
                        <td class="l t"><div id="pre_replyto_plus"></div></td>
                    </tr>
                    <tr>
                        <td class="l t"><strong>{preview_hdate}: </strong></td>
                        <td class="l t"><div id="pre_date_plus"></div></td>
                    </tr>
                    <tr>
                        <td class="l t"><strong>{preview_hto}: </strong></td>
                        <td class="l t"><div id="pre_to_plus"></div></td>
                    </tr>
                    <tr id="tr_pre_cc_plus">
                        <td class="l t"><strong>Cc: </strong></td>
                        <td class="l t"><div id="pre_cc_plus"></div></td>
                    </tr>
                    <tr>
                        <td class="l t"><strong>{preview_subject}: </strong></td>
                        <td class="l t" colspan="3"><div id="pre_subj_plus"></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="preview_unblock" class="topbarcontainer">
            <ul class="r">
                <li class="activebut">
                    <img src="{theme_path}/icons/blocked_items.png" alt="" title="{msg_blockedtitle}" /><span>{msg_blockedunblock}</span>&nbsp;
                </li>
                <li class="activebut" onclick="preview_unblock('single');">
                    <span>{msg_unblock_thismail}</span>
                </li>
                <li class="activebut" id="pre_unblock_email">
                    <span>-</span>
                </li>
                <li class="activebut" id="pre_unblock_domain">
                    <span>-</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<iframe width="100%" id="preview_content" scrolling="no" frameborder="0">
</iframe>