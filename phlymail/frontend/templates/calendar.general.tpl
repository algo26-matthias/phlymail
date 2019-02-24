<script type="text/javascript" src="{frontend_path}/js/timespan.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
/**
* @author Matthias Sommerfeld, <mso@phlylabs.de>
* @version 4.1.1
* @copyright 2004-2011 phlyLabs, Berlin, http://phlylabs.de
*/
ctxmen =
        {0 : {'status' : 3, 'link' : 'javascript:parent.open_newevent("{ref_day}");', 'name' : '{msg_newevent}...' }
        ,1 : {'status' : 1, 'link' : 'javascript:jumpto("today");', 'name' : '{msg_jumptoday}' }
        ,2 : {'status' : 1, 'link' : 'javascript:open_jumpbar();', 'name' : '{msg_jumptodate}...' }
        ,3 : {'status' : 1, 'link' : 'javascript:jumpto("prevevt");', 'name' : '{msg_jumptoprev}' }
        ,4 : {'status' : 1, 'link' : 'javascript:jumpto("nextevt");', 'name' : '{msg_jumptonext}' }
        ,5 : {'status' : 3, 'link' : 'javascript:parent.parent.killalloldevents();', 'name' : '{msg_deleoldevt}' }
        };<!-- START ctx_new -->
ctxmen[0]['status'] = 1;<!-- END ctx_new --><!-- START ctx_delete -->
ctxmen[5]['status'] = 1;<!-- END ctx_delete -->
events_loaded = 0;
evt_detail = 0;
tsk_detail = 0;
resizeTO = false;
preEvtdetTO = false;
preTskdetTO = false;
evtdetTO = false;
tskdetTO = false;
actionpane_open = 0;
searchbar_open = 0;
skimbar_open = 0;
jumpbar_open = 0;
event_cache = [];
evt = [];
evtdata = [];<!-- START eventline -->
evtdata[{eid}] = {json};
evt[{id}] = evtdata[{eid}];<!-- END eventline -->
taskdata = [];<!-- START taskline -->
taskdata[{id}] = {json};<!-- END taskline -->

pagestats = {"use_preview":"{use_preview}","allow_resize":"{allow_resize}","viewlink":"{viewlink}","customheight":"{customheight}","folder_writable":"{folder_writable}"};

function draw_tasks()
{
    var Cont = $('#task_cont');
    for (var i in taskdata) {
        var imp = '';
        switch (taskdata[i].importance) {
            case '1': case '2': imp = ' taskprio_veryhigh'; break;
            case '3': case '4': imp = ' taskprio_high'; break;
            case '5': imp = ' taskprio_middle'; break;
            case '6': case '7': imp = ' taskprio_low'; break;
            case '8': case '9': imp = ' taskprio_verylow'; break;
        }
        html = '<div class="taskline' + imp + '" id="task_' + i + '">' + taskdata[i].title + '</div>';
        Cont.append(html);
    }
    Cont.find('.taskline').bind('dblclick', edit_task)
        .bind('mouseover', function() {
            if (preTskdetTO) window.clearTimeout(preTskdetTO);
            tskPrevTO = window.setTimeout('taskdetails(' + this.id.replace(/^task_/, '') + ')' , 750)
        })
        .bind('mouseout', function () {
            if (preTskdetTO) window.clearTimeout(preTskdetTO);
        });
}

function taskdetails(eid)
{
    if (tsk_detail != 0 && tsk_detail != eid) tskdet_close(tsk_detail);
    if (tsk_detail == eid) return;
    tsk_detail = eid;
    var src = $('#task_' + eid);
    var style = 'position:absolute;z-index=100;right:6px;top:' + src.offset().top + 'px';
    var evtClass;
    switch (parseInt(taskdata[eid]['type'])) {
        case  2: evtClass = 'cal_evt_holiday'; break;
        case  3: evtClass = 'cal_evt_bday'; break;
        case  4: evtClass = 'cal_evt_personal'; break;
        case  5: evtClass = 'cal_evt_education'; break;
        case  6: evtClass = 'cal_evt_travel'; break;
        case  7: evtClass = 'cal_evt_anniversary'; break;
        case  8: evtClass = 'cal_evt_notinoffice'; break;
        case  9: evtClass = 'cal_evt_sickday'; break;
        case 10: evtClass = 'cal_evt_meet'; break;
        case 11: evtClass = 'cal_evt_vaca'; break;
        case 12: evtClass = 'cal_evt_phonecall'; break;
        case 13: evtClass = 'cal_evt_business'; break;
        case 14: evtClass = 'cal_evt_nonworkinghours'; break;
        case 50: evtClass = 'cal_evt_specialoccasion'; break;
        default: evtClass = 'cal_evt_app';
    }
    switch (parseInt(taskdata[eid]['status'])) {
        case  1: evtClass += ' cal_proposed'; break;
        case  3: evtClass += ' cal_cancelled'; break;
        case  4: evtClass += ' cal_delegated'; break;
        case  5: evtClass += ' cal_process'; break;
        case 10: evtClass += ' cal_tentative'; break;
        case 11: evtClass += ' cal_needsaction'; break;
    }

    var html = '<div id="tskdet_' + eid + '" class="cal_evt_popup cal_evt_event ' + evtClass + '" style="' + style + '">';

    if (taskdata[eid]['colour'].length > 0 || taskdata[eid]['repeats'] == 1 || taskdata[eid]['alarm'] == 1) {
        html += '<div class="cal_evt_icons">';
        if (taskdata[eid]['colour'].length > 0) {
            html += '<div class="cal_evt_colourflag cal_evt_colour_' + taskdata[eid]['colour'] + '"></div>';
        }
        if (taskdata[eid]['alarm'] == 1) {
            html += '<img src="{theme_path}/icons/cal_alarm.gif" alt="" />';
        }
        if (taskdata[eid]['repeats'] == 1) {
            html += '<img src="{theme_path}/icons/cal_repeating.gif" alt="" />';
        }
        html += '</div>';
    }

    html += '<table class="cal_evt_body" border="0" cellpadding="2" cellspacing="0"><tbody>'
            + '<tr><td><strong>{msg_title}:</strong></td><td>' + taskdata[eid].title + '</td></tr>';
    if (taskdata[eid].has_start) {
        html += '<tr><td><strong>{msg_starts}:</strong></td><td>' + taskdata[eid].starts + '</td></tr>';
    }
    if (taskdata[eid].has_end) {
        html += '<tr><td><strong>{msg_ends}:</strong></td><td>' + taskdata[eid].ends + '</td></tr>';
    }
    if (taskdata[eid].loc.length > 0) {
        html += '<tr><td><strong>{msg_location}:</strong></td><td>' + taskdata[eid].loc + '</td></tr>';
    }
    if (taskdata[eid].importance_title.length > 0) {
        html += '<tr><td><strong>{msg_importance}:</strong></td><td>' + taskdata[eid].importance_title + '</td></tr>';
    }
    html += '<tr><td><strong>{msg_completion}:</strong></td><td>' + taskdata[eid].completion + '%</td></tr>';
    if (taskdata[eid].desc.length > 0) {
        html += '<tr><td class="t"><strong>{msg_description}:</strong></td><td>' + taskdata[eid].desc.replace(/\n/g, '<br />') + '</td></tr>';
    }
    html += '</tbody></table>';

    $('#outline').append(html);
    $('#tskdet_' + eid).removeClass('taskline')
            .bind('dblclick', edit_task)
            .bind('mouseout', function () {
                tskdetTO = window.setTimeout('tskdet_close(' + this.id.replace(/^tskdet_/, '') + ')', 750);
            })
            .bind('mouseover', function () {
                if (tskdetTO) window.clearTimeout(tskdetTO);
            })
    window.setTimeout('tskdet_close(' + eid + ')', 10000);
}

function tskdet_close(eid)
{
    try {
        $('#tskdet_' + eid).remove();
        tsk_detail = 0;
    } catch (e) { }
}

function edit_task()
{
    var evt_id = this.id.replace(/^(task|tskdet)_/, '');
    window.open
            ('{edit_tsk_link}' + (evt_id ? '&tid=' + taskdata[evt_id].eid : '&gid={gid}')
            ,'task_' + (evt_id ? evt_id : 'new')
            ,'width=400,height=500,scrollbars=yes,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            );
}

function edit_event(eid, start_d)
{
    window.open
            ('{edit_evt_link}' + (eid ? '&eid=' + eid : '&gid={gid}')
                    + (start_d ? '&start_y={curry}&start_m={currm}&start_d=' + encodeURIComponent(start_d)
                    + '&end_d=' + encodeURIComponent(start_d) : '')
            ,'event_' + (eid ? eid : 'new')
            ,'width=400,height=500,scrollbars=yes,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            );
}

function eventdetails(eid)
{
    if (evt_detail != 0 && evt_detail != eid) evtdet_close(evt_detail);
    if (evt_detail == eid) return;
    evt_detail = eid;
    var tbd = document.createElement('tbody');
    tbd.className = 'cal_evt_body';

    if (evtdata[eid]['title'].length > 0) {
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.className = 'l t';
        td.style.fontWeight = 'bold';
        td.appendChild(document.createTextNode('{msg_title}:'));
        tr.appendChild(td);
        var td = document.createElement('td');
        td.className = 'l t';
        td.appendChild(document.createTextNode(evtdata[eid]['title']));
        tr.appendChild(td);
        tbd.appendChild(tr);
    }

    var tr = document.createElement('tr');
    var td = document.createElement('td');
    td.className = 'l t';
    td.style.fontWeight = 'bold';
    td.appendChild(document.createTextNode('{msg_starts}:'));
    tr.appendChild(td);
    var td = document.createElement('td');
    td.className = 'l t';
    td.appendChild(document.createTextNode(evtdata[eid]['starts']));
    tr.appendChild(td);
    tbd.appendChild(tr);

    var tr = document.createElement('tr');
    var td = document.createElement('td');
    td.className = 'l t';
    td.style.fontWeight = 'bold';
    td.appendChild(document.createTextNode('{msg_ends}:'));
    tr.appendChild(td);
    var td = document.createElement('td');
    td.className = 'l t';
    td.appendChild(document.createTextNode(evtdata[eid]['ends']));
    tr.appendChild(td);
    tbd.appendChild(tr);

    var tr = document.createElement('tr');
    var td = document.createElement('td');
    td.className = 'l t';
    td.style.fontWeight = 'bold';
    td.appendChild(document.createTextNode('%h%CalDuration%:'));
    tr.appendChild(td);
    var td = document.createElement('td');
    td.className = 'l t';
    td.appendChild(document.createTextNode(showDuration(evtdata[eid])));
    tr.appendChild(td);
    tbd.appendChild(tr);

    if (evtdata[eid]['loc'].length > 0) {
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.className = 'l t';
        td.style.fontWeight = 'bold';
        td.appendChild(document.createTextNode('{msg_location}:'));
        tr.appendChild(td);
        var td = document.createElement('td');
        td.className = 'l t';
        td.appendChild(document.createTextNode(evtdata[eid]['loc']));
        tr.appendChild(td);
        tbd.appendChild(tr);
    }

    if (evtdata[eid]['desc'].length > 0) {
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.className = 'l t';
        td.style.fontWeight = 'bold';
        td.appendChild(document.createTextNode('{msg_description}:'));
        tr.appendChild(td);
        var td = document.createElement('td');
        td.className = 'l t';
        td.innerHTML = evtdata[eid]['desc'].replace(/\n/g, '<br />');
        tr.appendChild(td);
        tbd.appendChild(tr);
    }

    var ori = document.getElementById('shownevent_' + eid);
    var tbl = document.createElement('table');
    tbl.id = 'evtdet_' + eid;
    tbl.className = ori.className.replace(/ cal_evt_ispast$/, '');
    tbl.style.position = 'absolute';
    tbl.style.left = Math.round(ori.offsetLeft) + 'px';
    tbl.style.top = Math.round(ori.offsetTop) + 'px';
    tbl.cellPadding = 2;
    tbl.cellSpacing = 0
    tbl.appendChild(tbd);
    tbl.ondblclick = function (e) { edit_event(this.id.replace(/^evtdet_/, '')); }
    tbl.onmouseout = function (e) { evtdetTO = window.setTimeout('evtdet_close(' + this.id.replace(/^evtdet_/, '') + ')', 750); }
    tbl.onmouseover = function (e) { if (evtdetTO) window.clearTimeout(evtdetTO); }
    document.getElementById('outline').appendChild(tbl);
    if (fullh < (tbl.offsetTop+tbl.offsetHeight)) tbl.style.top = (tbl.offsetTop+(fullh-(tbl.offsetTop + tbl.offsetHeight))) + 'px';
    if (fullw < (tbl.offsetLeft+tbl.offsetWidth)) tbl.style.left = (tbl.offsetLeft+(fullw-(tbl.offsetLeft+tbl.offsetWidth))) + 'px';
    window.setTimeout('evtdet_close('+eid+')', 10000);
}

function showDuration(event)
{
    var fromd = new Date(event['start']);
    var tod   = new Date(event['end']);
    if (fromd > tod) {
        return '-------';
    }
    var span = timeSpan(fromd, tod, 'years,months,weeks,days,hours,minutes');
    var map = {'years': 'y', "months": 'mo', "weeks": 'w', "days": 'd', "hours": 'h', "minutes": 'min'};
    var res = '';
    $.each(span, function (k, v) {
        if (0 == v) return true;
        if (res.length > 0) {
            res +=  ' ';
        }
        res += v.toString() + map[k];
    })
    return res.length > 0 ? res : '0min';
}

function evtdet_close(eid)
{
    $('#evtdet_' + eid).remove();
    evt_detail = 0;
}

function resize_page()
{
    fullh = $(window).height();
    fullw = $(window).width();
    weekw = $('#overview').outerWidth();
    availw = fullw - (weekw+1); // Somehow firefox 4 manages to add a pixel which does not exist
    canvash = fullh - $('#topmen').outerHeight();
    $('#outline').height(canvash);
    $('#timeline').height(canvash).width(availw);
    $('#task_wrap').height(canvash - ($('#overview').outerHeight() + $('#task_head').outerHeight() ) );
    parent.update_headings('{calendarhead}');
    show_event(evt);
    events_loaded = 1;
}

function show_event(data)
{
    var rightNow = parseInt(new Date().getTime()/1000);
    var hh_height = (canvash / 48) - 1;  // Ugly Boxmodel geraffel
    // Remove old event DIVs from the screen
    for (var i = 0; i < event_cache.length; ++i) {
        var kill = document.getElementById(event_cache[i]);
        if (!kill) continue;
        kill.parentNode.removeChild(kill);
    }
    event_cache = [];
    hh_font = Math.floor(hh_height*.5);
    if (hh_font < 4) hh_font = 0;
    fh_font = Math.floor(hh_height);
    if (fh_font < 4) fh_font = 0;

    // Render position and size for the event by calculating the positions and heights of the
    // affected tr tags
    $('#event_area').find('tr .cal_tl_work, tr .cal_tl_spare, tr .cal_tl_fh_m, tr .cal_tl_fh_hh').css('height', hh_height + 'px')
        .end().find('tr .cal_tl_fh_h').css('font-size', fh_font + 'px')
        .end().find('tr .cal_tl_fh_m').css('font-size', hh_font + 'px');
    var canvas = $('#timeline');
    canvas.show();

    // First render the stacking of the events
    for (var i = 0; i < data.length; ++i) {
        data[i]['startm'] *= 1;
        data[i]['endm'] *= 1;
        if (i == 0) {
            data[i]['pos'] = 1; // The first event always starts on leftmost
            data[i]['max_pos'] = 1; // Until further notice, there's one element a line
            data[i]['slots'] = 1; // How many slots are occupied, this line: 1 of 1
            continue;
        }

        // if ((data[i]['starth']*60 + data[i]['startm']) < (data[(i-1)]['endh']*60 + data[(i-1)]['endm'])) {
            // Try to find out, if we could fit in a slot left to the current position
            var reset = false;
            var newmaxslot = false;
            var newminslot = false;
            if (data[(i-1)] && data[(i-1)]['pos'] > 1) {
                for (var j = 1; j <= data[(i-1)]['pos']; j++) {
                    if ((data[i]['starth'] * 60 + data[i]['startm'])
                            >= (data[(i-j)]['endh'] * 60 + data[(i-j)]['endm'])) {
                        if (!newmaxslot) newmaxslot = data[(i-j)]['pos'];
                        newminslot = data[(i-j)]['pos'];
                        reset = true;
                    }
                }
            }
            // Next thing to handle (via else if) here:
            // Ich muss schauen, ob ich den vorvorherigen Termin überlappe, wenn nein, bleibt die max_pos gleich
            // und ich setze mich selbst einfach da rein
            // EIGENTLICH müsste aber bei [i-1][slots] > 1 ein Halbieren des Platzes erfolgen, so dass beide Termine
            // den gleichen Platz einnehmen. Richtig schön wird's, wenn sich noch mehr Termine rechts einfädeln müssen...
            if (reset) {
                data[i]['pos'] = newminslot;
                data[i]['slots'] = newmaxslot - newminslot +1;
                data[i]['max_pos'] = data[(i-1)]['max_pos'];
            } else {
                data[i]['pos'] = (data[(i-1)]) ? data[(i-1)]['pos'] + 1 : 1;
                data[i]['slots'] = 1;
                for (var j = data[i]['pos'] - 1; j >= 0, data[(i-j)]; j--) {
                    data[(i-j)]['max_pos'] = data[i]['pos'];
                }
            }
        // Hier einzufügender else if Block:
        // Es reicht nicht, zu schauen, ob wir hinter den vorherigen Termin passen, sondern wir müssen bis zu dem
        // Termin zurück, der die erste von [i-1]['pos'] abweichende max_pos hat, um zu sehen, ob wir einn dieser Termine
        // evtl.überlappen. Dann müssten wir uns links von diesem Termin einsortieren, aber soviele Slots wie möglich einnehmen
        /*
        } else {
            data[i]['slots'] = data[i]['max_pos'] = data[i]['pos'] = 1;
        } */
    }

    // Now place the events on screen
    for (var i = 0; i < data.length; ++i) {
        var evt_id = 'evt_' + data[i]['eid'];
        // Hour is two digits
        if (data[i]['starth'].toString().length < 2) data[i]['starth'] = '0' + data[i]['starth'].toString();
        if (data[i]['endh'].toString().length < 2) data[i]['endh'] = '0' + data[i]['endh'].toString();
        // Minute is two digits
        if (data[i]['startm'].toString().length < 2) data[i]['startm'] = '0' + data[i]['startm'].toString();
        if (data[i]['endm'].toString().length < 2) data[i]['endm'] = '0' + data[i]['endm'].toString();
        var start_row = document.getElementById('tl_' + data[i]['starth'] + '_00');
        var end_row   = document.getElementById('tl_' + data[i]['endh'] + '_00');
        var start_pos = start_row.offsetTop -1;
        var start_hi  = start_row.offsetHeight;
        var start_lft = start_row.offsetLeft;
        var start_wid = start_row.offsetWidth;
        var end_pos   = end_row.offsetTop;
        var end_hi    = end_row.offsetHeight;

        // Effective size depending on elemnts stacked beside each other
        if (data[i]['max_pos'] != 1) {
            start_wid = start_wid / data[i]['max_pos'];    // Slice the area
            start_lft += (start_wid * (data[i]['pos']-1)); // Put the left into correct slot
            start_wid *= data[i]['slots'];                 // Occupy the number of slots rendered before
        }

        // Adjust top pos and height according to the actual starting / ending minute of the event
        start_pos += 2 * start_hi * data[i]['startm'] / 60;
        end_pos   += (2 * end_hi * data[i]['endm'] / 60) -1;
        // Create the event div, its container, position it nicely, append it to the doc
        var evtClass = 'cal_evt_event';
        switch (parseInt(data[i]['type'])) {
            case  2: evtClass += ' cal_evt_holiday'; break;
            case  3: evtClass += ' cal_evt_bday'; break;
            case  4: evtClass += ' cal_evt_personal'; break;
            case  5: evtClass += ' cal_evt_education'; break;
            case  6: evtClass += ' cal_evt_travel'; break;
            case  7: evtClass += ' cal_evt_anniversary'; break;
            case  8: evtClass += ' cal_evt_notinoffice'; break;
            case  9: evtClass += ' cal_evt_sickday'; break;
            case 10: evtClass += ' cal_evt_meet'; break;
            case 11: evtClass += ' cal_evt_vaca'; break;
            case 12: evtClass += ' cal_evt_phonecall'; break;
            case 13: evtClass += ' cal_evt_business'; break;
            case 14: evtClass += ' cal_evt_nonworkinghours'; break;
            case 50: evtClass += ' cal_evt_specialoccasion'; break;
            default: evtClass += ' cal_evt_app';
        }
        switch (parseInt(data[i]['status'])) {
            case  1: evtClass += ' cal_proposed'; break;
            case  3: evtClass += ' cal_cancelled'; break;
            case  4: evtClass += ' cal_delegated'; break;
            case 10: evtClass += ' cal_tentative'; break;
            case 11: evtClass += ' cal_needsaction'; break;
        }
        // Past events are marked through CSS
        if (rightNow > data[i].refstamp) evtClass += ' cal_evt_ispast';

        var evt = document.createElement('div');
        evt.className = evtClass;
        if (data[i].colour.length > 0) {
            var srcflag = document.createElement('div');
            srcflag.className = 'cal_evt_colourflag cal_evt_colour_' + data[i].colour;
            evt.appendChild(srcflag);
        }
        cnt = document.createElement('div');
        cnt.style.background = 'transparent';
        if (data[i]['repeats'] == 1 || data[i]['alarm'] == 1) {
            icns = document.createElement('div');
            icns.className = 'cal_evt_icons';
            if (data[i]['alarm'] == 1) {
                img = document.createElement('img');
                img.src = '{theme_path}/icons/cal_alarm.gif';
                icns.appendChild(img);
            }
            if (data[i]['repeats'] == 1 && data[i]['alarm'] == 1) icns.appendChild(document.createElement('br'));
            if (data[i]['repeats'] == 1) {
                img = document.createElement('img');
                img.src = '{theme_path}/icons/cal_repeating.gif';
                icns.appendChild(img);
            }
            cnt.appendChild(icns);
        }
        str = document.createElement('strong');
        str.appendChild(document.createTextNode(data[i]['title']));
        cnt.appendChild(str);
        cnt.appendChild(document.createElement('br'));
        cnt.appendChild(document.createTextNode(data[i]['starts'] + ' - ' + data[i]['ends']));
        cnt.appendChild(document.createElement('br'));
        if (data[i]['loc'].length > 0) {
            cnt.appendChild(document.createTextNode(data[i]['loc']));
            cnt.appendChild(document.createElement('br'));
        }
        if (data[i]['desc'].length > 0) {
            cnt.appendChild(document.createTextNode(data[i]['desc']));
        }
        evt.appendChild(cnt);
        evt.style.overflow = 'hidden';
        evt.title = data[i]['title'];
        evt.onmouseover = function(e) {
            if (preEvtdetTO) window.clearTimeout(preEvtdetTO);
            preEvtdetTO = window.setTimeout('eventdetails(' + this.id.replace(/^shownevent_/, '') + ')' , 750);
        }
        evt.onmouseout = function (e) { if (preEvtdetTO) window.clearTimeout(preEvtdetTO); }
        evt.ondblclick = function (e) { edit_event(this.id.replace(/^shownevent_/, '')); }
        evt.id = 'shownevent_' + data[i]['eid'];
        evt.style.position = 'absolute';
        evt.style.left   = Math.round(start_lft + 5) + 'px';
        evt.style.width  = Math.round(start_wid - 10) + 'px';
        evt.style.top    = Math.round(start_pos) + 'px';
        evt.style.height = Math.round((end_pos - start_pos > 0) ? end_pos - start_pos : 1) + 'px';
        canvas.append(evt);
        event_cache.push('shownevent_' + data[i]['eid']);
    }
}

function search_me()
{
    self.location.href = $('#searchform').attr('action') + '&pattern=' + encodeURIComponent($('#search_pattern_txt').val());
    return false;
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

function keyfetch_on() { }
function keyfetch_off() { }

function open_actionpane()
{
    if (actionpane_open < 1) {
        $('#actionpane').removeClass('open');
        actionplane_open = 0; // Prevent negative values;
    } else {
        $('#actionpane').addClass('open');
    }
    resize_page();
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

$(document).ready(function () {
    resize_page();
    draw_tasks();
});
$(window).resize(function () {
    if (resizeTO != false) window.clearTimeout(resizeTO);
    resizeTO = window.setTimeout('resize_page();', 250);
});
/*]]>*/
</script>
<div id="topmen">
    <div id="buttonbar_bookmarks" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="activebut" onclick="jumpto('today')">
                    <img src="{theme_path}/icons/men_caljumptotoday.gif" alt="" /><span>{msg_jumptoday}</span>
                </li>
                <li class="activebut" onclick="jumpto('prevevt')">
                    <img src="{theme_path}/icons/men_caljumpprevevt.gif" alt="" /><span>{msg_jumptoprev}</span>
                </li>
                <li class="activebut" onclick="jumpto('nextevt')">
                    <img src="{theme_path}/icons/men_caljumpnextevt.gif" alt="" /><span>{msg_jumptonext}</span>
                </li>
                <li class="activebut men_drop" id="jump" onclick="open_jumpbar();">
                    <span>{msg_jumptodate}...
                </li>
                <li class="activebut men_drop" id="search" onclick="open_searchbar();">
                    <img src="{theme_path}/icons/search.gif" alt="" /><span>{search}</span>
                </li>
            </ul>
            <ul class="r">
                <li class="activebut imgonly open">
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
                <li class="activebut imgonly" onclick="switchview('list');">
                    <img src="{theme_path}/icons/cal_listview.png" alt="{msg_listview}" title="{msg_listview}" />
                </li>
            </ul>
        </div>
        <div id="actionpane" class="actionpane">
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
                <form action="{search_url}" id="searchform" method="get" style="display:inline;" onsubmit="return search_me();">
                    <input type="text" name="pattern" value="" id="search_pattern_txt" onfocus="keyfetch_off();" onblur="keyfetch_on();" size="12" maxlength="64" />
                    <input type="submit" value="{but_search}" />
                </form>
            </div>
        </div>
    </div>
</div>

<div id="outline" class="cal_outline">
    <div id="timeline" style="display:none;float:left;position:relative;" onmouseover="ctxmen_activate_sensor(ctxmen);" onmouseout="ctxmen_disable_sensor();">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="empty-cells:show;">
            <tbody id="event_area"><!-- START timeline --><!-- START fullhour -->
                <tr ondblclick="edit_event(0, '{h}', '{m}');return false;">
                    <td width="20" rowspan="2" class="cal_tl_fh_h t">{h}</td>
                    <td width="20" class="cal_tl_fh_m">{m}</td><!-- START work -->
                    <td id="tl_{h}_{m}" class="cal_tl_work">&nbsp;</td><!-- END work --><!-- START spare -->
                    <td id="tl_{h}_{m}" class="cal_tl_spare">&nbsp;</td><!-- END spare -->
                </tr><!-- END fullhour --><!-- START halfhour -->
                <tr ondblclick="edit_event(0, '{h}', '{m}');return false;">
                    <td class="cal_tl_hh">&nbsp;</td><!-- START work -->
                    <td id="tl_{h}_{m}" class="cal_tl_work">&nbsp;</td><!-- END work --><!-- START spare -->
                    <td id="tl_{h}_{m}" class="cal_tl_spare">&nbsp;</td><!-- END spare -->
                </tr><!-- END halfhour --><!-- END timeline -->
            </tbody>
        </table>
    </div>
    <div style="float:right;">
        <table border="0" cellpadding="2" cellspacing="0" id="overview" name="overview">
            <tr>
                <td class="cal_monthhead" colspan="7">
                    <table border="0" width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="c l"><a href="{oneback}"><img src="{theme_path}/icons/nav_left.png" alt="" /></a></td>
                            <td class="c t">{month}</td>
                            <td class="c r"><a href="{oneforward}"><img src="{theme_path}/icons/nav_right.png" alt="" /></a></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="cal_wday_label" title="{monday_l}">{monday_s}</td>
                <td class="cal_wday_label" title="{tuesday_l}">{tuesday_s}</td>
                <td class="cal_wday_label" title="{wednesday_l}">{wednesday_s}</td>
                <td class="cal_wday_label" title="{thursday_l}">{thursday_s}</td>
                <td class="cal_wday_label" title="{friday_l}">{friday_s}</td>
                <td class="cal_wday_label" title="{saturday_l}">{saturday_s}</td>
                <td class="cal_wday_label" title="{sunday_l}">{sunday_s}</td>
            </tr><!-- START weekline -->
            <tr>{weekday}</tr><!-- END weekline -->
        </table>
        <div id="task_head" class="cal_monthhead">
            {head_tasks}
        </div>
        <div id="task_wrap">
            <div id="task_cont">
            </div>
        </div>
    </div>
</div><!-- START hasevents --> hasevents<!-- END hasevents -->
<!-- START ov_today --><td class="cal_wday_showday{has_events}">{date}</td>
<!-- END ov_today --><!-- START ov_weekend --><td class="cal_wday_weekend{has_events}" title="{title}"><a href="{goto}">{date}</a></td>
<!-- END ov_weekend --><!-- START ov_current --><td class="cal_wday_curr{has_events}"><a href="{goto}">{date}</a></td>
<!-- END ov_current --><!-- START ov_other --><td class="cal_wday_other{has_events}"><a href="{goto}">{date}</a></td>
<!-- END ov_other --><!-- START ov_space --><td class="cal_wday_space"> </td>
<!-- END ov_space --><!-- START ov_nextmonth --><td colspan="7" class="cal_monthhead">{month}</td>
<!-- END ov_nextmonth -->