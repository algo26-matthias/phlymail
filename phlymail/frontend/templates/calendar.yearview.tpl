<script type="text/javascript">
// <![CDATA[
/**
* @author Matthias Sommerfeld, <mso@phlylabs.de>
* @version 4.0.1
* @copyright 2004-2010 phlyLabs, Berlin, http://phlylabs.de
*/
ctxmen =
        {0 : {'status' : 3, 'link' : 'javascript:parent.open_newevent("{ref_day}");', 'name' : '{msg_newevent}...' }
        ,1 : {'status' : 1, 'link' : 'javascript:jumpto("today");', 'name' : '{msg_jumptoday}' }
        ,2 : {'status' : 1, 'link' : 'javascript:open_jumpbar();', 'name' : '{msg_jumptodate}...' }
        ,3 : {'status' : 1, 'link' : 'javascript:jumpto("prevevt");', 'name' : '{msg_jumptoprev}' }
        ,4 : {'status' : 1, 'link' : 'javascript:jumpto("nextevt");', 'name' : '{msg_jumptonext}' }
        ,5 : {'status' : 3, 'link' : 'javascript:parent.killalloldevents();', 'name' : '{msg_deleoldevt}' }
        };<!-- START ctx_new -->
ctxmen[0]['status'] = 1;<!-- END ctx_new --><!-- START ctx_delete -->
ctxmen[5]['status'] = 1;<!-- END ctx_delete -->
events_loaded = 0;
actionpane_open = 0;
searchbar_open = 0;
skimbar_open = 0;
jumpbar_open = 0;
taskdata = [];<!-- START taskline -->
taskdata[{id}] = {json};<!-- END taskline -->

pagestats = {"use_preview":"{use_preview}","allow_resize":"{allow_resize}","viewlink":"{viewlink}","customheight":"{customheight}","folder_writable":"{folder_writable}"};

evt = [];
resizeTO = false;

/** Etwas krude das Ganze... **/
function resize_page()
{
    $('#timeline').hide();
    fullh = $(document).height();
    fullw = $(document).width();
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
    var canvas = document.getElementById('timeline');
    var canvtbl = document.getElementById('event_area');
    var mhead_height = 0;
    canvas.style.display = 'block';
    for (var i in canvtbl.childNodes) {
        if (canvtbl.childNodes[i].nodeName == 'TR') {
            if (canvtbl.childNodes[i].id && canvtbl.childNodes[i].id == 'main_heading') {
                for (var j in canvtbl.childNodes[i].childNodes) {
                    if (canvtbl.childNodes[i].childNodes[j].nodeName == 'TD') {
                        mhead_height = canvtbl.childNodes[i].childNodes[j].offsetHeight;
                        break;
                    }
                }
            }
        }
    }
    hh_realh = hh_height = ((canvash-mhead_height) / 4) - 1;
    hh_height = Math.floor(hh_height);
    hh_realh -= hh_height;
    hh_realw = hh_width = (availw/3) - (msie ? 1 : 0);
    hh_width = Math.floor(hh_width);
    hh_realw -= hh_width;
    // Render position and size for the event by calculating the positions and heights of the
    // affected tr tags
    var differenzcounter = 0;
    for (var i in canvtbl.childNodes) {
        var myhh_height = hh_height;
        differenzcounter += hh_realh;
        if (differenzcounter > 1) {
            myhh_height += 1;
            differenzcounter -= 1;
        }
        if (canvtbl.childNodes[i].nodeName == 'TR') {
            for (var j in canvtbl.childNodes[i].childNodes) {
                if (!canvtbl.childNodes[i].childNodes[j].className) continue;
                if (!canvtbl.childNodes[i].childNodes[j].className.match(/^cal_mnth_other/)) continue;
                canvtbl.childNodes[i].childNodes[j].style.height = myhh_height + 'px';
                for (var k in canvtbl.childNodes[i].childNodes[j].childNodes) {
                    if (canvtbl.childNodes[i].childNodes[j].childNodes[k].className
                            && canvtbl.childNodes[i].childNodes[j].childNodes[k].className.match(/^cal_mnth_date/)) {
                        canvtbl.childNodes[i].childNodes[j].childNodes[k].style.height = (myhh_height - 4) + 'px';
                        canvtbl.childNodes[i].childNodes[j].childNodes[k].style.width  = (hh_width - 4) + 'px';
                        break;
                    }
                }
            }
        }
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
    self.location.href = '{PHP_SELF}?{passthrough}&l=ilist&hcalendar&' + urladd;
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

function open_skimbar()
{
    if (skimbar_open == 0) {
        skimbar_open = 1;
        $('#skim').addClass('open');
        $('#skimbar').css('display', 'block');
        $('#skimslider').slider({min: 1, max: pagestats.maxpage, stepping: 1
                ,startValue: $('#WP_jumppage').val()
                ,slide: function (e, ui) { $('#WP_jumppage').val(ui.value); }
                ,change: function (e, ui) { if (pagestats.page != $('#WP_jumppage').val()) { jumppage(); } }
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
    resize_page();
});
$(window).resize(function () {
    if (resizeTO != false) window.clearTimeout(resizeTO);
    resizeTO = window.setTimeout('resize_page();', 250);
});
// ]]>
</script>
<div id="topmen">
    <div id="buttonbar_bookmarks" class="outset">
        <div class="topbarcontainer">
            <ul class="l">
                <li class="activebut" onclick="jumpto('today')">
                    <img src="{theme_path}/icons/men_caljumptotoday.gif" alt="" /><span>{msg_jumptoday}</span>
                </li>
                <!-- li class="activebut" onclick="jumpto('prevevt')">
                    <img src="{theme_path}/icons/men_caljumpprevevt.gif" alt="" /><span>{msg_jumptoprev}</span>
                </li>
                <li class="activebut" onclick="jumpto('nextevt')">
                    <img src="{theme_path}/icons/men_caljumpnextevt.gif" alt="" /><span>{msg_jumptonext}</span>
                </li -->
                <li class="activebut men_drop" id="jump" onclick="open_jumpbar();">
                    <span>{msg_jumptodate}...
                </li>
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
                <li class="activebut imgonly open">
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
    <div id="timeline" style="display:none;float:left;" onmouseover="ctxmen_activate_sensor(ctxmen);" onmouseout="ctxmen_disable_sensor();">
        <table cellpadding="0" width="100%" cellspacing="0" style="empty-cells:show;border-collapse:collapse;">
            <tbody id="event_area">
                <tr id="main_heading">
                    <td colspan="3">
                        <div class="sendmenubut cal_mnth_monthhead">
                            <div style="float:left"><a href="{oneyearback}"><img src="{theme_path}/icons/nav_left_big.gif" alt="" /></a></div>
                            <div style="float:right;"><a href="{oneyearforward}"><img src="{theme_path}/icons/nav_right_big.gif" alt="" /></a></div>
                            {month_l}
                        </div>
                    </td>
                </tr>
                <tr class="monthline">
                    <td class="cal_mnth_other" id="td_1" onclick="self.location.href='{detaillink}-01-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_1"><div class="cal_mnth_kw">{january_l}</div>{date_0}</div></td>
                    <td class="cal_mnth_other" id="td_5" onclick="self.location.href='{detaillink}-02-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_2"><div class="cal_mnth_kw">{february_l}</div>{date_2}</div></td>
                    <td class="cal_mnth_other" id="td_9" onclick="self.location.href='{detaillink}-03-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_3"><div class="cal_mnth_kw">{march_l}</div>{date_3}</div></td>
                </tr>
                <tr class="monthline">
                    <td class="cal_mnth_other" id="td_4" onclick="self.location.href='{detaillink}-04-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_2"><div class="cal_mnth_kw">{april_l}</div>{date_4}</div></td>
                    <td class="cal_mnth_other" id="td_5" onclick="self.location.href='{detaillink}-05-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_6"><div class="cal_mnth_kw">{may_l}</div>{date_5}</div></td>
                    <td class="cal_mnth_other" id="td_6" onclick="self.location.href='{detaillink}-06-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_6"><div class="cal_mnth_kw">{june_l}</div>{date_6}</div></td>
                </tr>
                <tr class="monthline">
                    <td class="cal_mnth_other" id="td_7" onclick="self.location.href='{detaillink}-07-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_7"><div class="cal_mnth_kw">{july_l}</div>{date_7}</div></td>
                    <td class="cal_mnth_other" id="td_8" onclick="self.location.href='{detaillink}-08-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_8"><div class="cal_mnth_kw">{august_l}</div>{date_8}</div></td>
                    <td class="cal_mnth_other" id="td_9" onclick="self.location.href='{detaillink}-09-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_9"><div class="cal_mnth_kw">{september_l}</div>{date_9}</div></td>
                </tr>
                <tr class="monthline">
                    <td class="cal_mnth_other" id="td_10" onclick="self.location.href='{detaillink}-10-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_10"><div class="cal_mnth_kw">{october_l}</div>{date_10}</div></td>
                    <td class="cal_mnth_other" id="td_11" onclick="self.location.href='{detaillink}-11-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_11"><div class="cal_mnth_kw">{november_l}</div>{date_11}</div></td>
                    <td class="cal_mnth_other" id="td_12" onclick="self.location.href='{detaillink}-12-01'" style="cursor:pointer" title="{msg_monthview}"><div class="cal_mnth_date" id="draw_12"><div class="cal_mnth_kw">{december_l}</div>{date_12}</div></td>
                </tr>
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
<!-- END ov_today --><!-- START ov_weekend --><td class="cal_wday_weekend{has_events}"><a href="{goto}">{date}</a></td>
<!-- END ov_weekend --><!-- START ov_current --><td class="cal_wday_curr{has_events}"><a href="{goto}">{date}</a></td>
<!-- END ov_current --><!-- START ov_other --><td class="cal_wday_other{has_events}"><a href="{goto}">{date}</a></td>
<!-- END ov_other --><!-- START ov_space --><td class="cal_wday_space"> </td>
<!-- END ov_space --><!-- START ov_nextmonth --><td colspan="7" class="cal_monthhead">{month}</td>
<!-- END ov_nextmonth -->