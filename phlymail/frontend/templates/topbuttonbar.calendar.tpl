<script type="text/javascript">
//<![CDATA[
pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/calendar_men.gif'
        ,'{msg_setup_calendar}'
        ,'{PHP_SELF}?l=setup&h=calendar&{passthrough}'
        ,450
        ,560
        );<!-- START has_new_event -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/calendar_men.gif'
        ,'{msg_newevent}'
        ,'{PHP_SELF}?l=edit_event&h=calendar&{passthrough}'
        );<!-- END has_new_event --><!-- START has_new_task -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/tasks_men.gif'
        ,'{msg_newtask}'
        ,'{PHP_SELF}?l=edit_task&h=calendar&{passthrough}'
        );<!-- END has_new_task --><!-- START has_exchange -->
pm_menu_additem
        ('exchange'
        ,'{theme_path}/icons/calendar_men.gif'
        ,'{msg_setup_calendar}'
        ,'{PHP_SELF}?l=exchange&h=calendar&{passthrough}'
        ,500
        ,500
        );<!-- END has_exchange -->

var calendar_open_alerts = [];
var calendar_alerts_done = [];

function calendar_schedule_alert(id, time)
{
    if (!calendar_open_alerts[id] && !calendar_alerts_done[id]) {
        calendar_open_alerts[id] = time;
        check_alerttime();
    }
}

function check_alerttime()
{
    mytime = new Date();
    mytime = mytime.getTime();

    nexttime = false;
    nexteid = false;
    // Find the next alert to schedule
    for (var check in calendar_open_alerts) {
        checktime = calendar_open_alerts[check];
        if (!checktime) continue;
        // Already overdue
        if (checktime <= mytime) {
            open_alertbox(check);
            calendar_alerts_done[check] = calendar_open_alerts[check];
            calendar_open_alerts[check] = null;
        }
        if (!nexttime || checktime < nexttime) {
            nexttime = checktime;
            nexteid = check;
        }
    }
    window.setTimeout('check_alerttime()', (nexttime - mytime));
}

function open_alertbox(eid)
{
    $('#alertiframe').attr('src', '{alert_url}' + eid);
    float_window('alertevent', '{head_reminder}', '380', '270', true, 'alertevent_' + eid);
}

function open_newevent(ref_day)
{
    date = new Date();
    window.open
            ('{PHP_SELF}?l=edit_event&h=calendar&{passthrough}&ref_date=' + ref_day
            ,'_cal_' + date.getTime()
            ,'width=400,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function calendar_repeatevent(eid)
{
    url = '{PHP_SELF}?{passthrough}&l=worker&h=calendar&what=event_repeat&eid=' + eid;
    email_AJAX(url);
}

function calendar_discardevent(eid)
{
    url = '{PHP_SELF}?{passthrough}&l=worker&h=calendar&what=event_discard&eid=' + eid;
    email_AJAX(url);
}

function calendar_pinboard_opener(eid)
{
    var date = new Date();
    var parts = eid.split('_');
    var url = parts[1] == 'tasks'
            ? '{PHP_SELF}?l=edit_task&h=calendar&{passthrough}&tid=' + parts[3]
            : '{PHP_SELF}?l=edit_event&h=calendar&{passthrough}&eid=' + parts[3];
    window.open(url, '_cal_' + date.getTime(), 'width=400,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
}

function killalloldevents()
{
    url = '{PHP_SELF}?{passthrough}&l=worker&h=calendar&what=killalloldevents';
    email_AJAX(url);
}

function collect_and_react_calendar(ops)
{
    list = this.frames.PHM_tr.get_selected_items();
    if (list.length == 0) return true;

    switch (ops) {
    case 'delete':
        var answer = confirm('{msg_killconfirm}');
        if (!answer) return false;
        url = '{PHP_SELF}?{passthrough}&l=worker&h=calendar&what=event_' + ops;
        for (var ID in list) {
            url += '&eid[]=' + list[ID];
        }
        email_AJAX(url);
        break;
    }
}

function calendar_worker()
{
    window.setTimeout('calendar_worker();', 600000); // Check every 10 minutes
    email_AJAX('{PHP_SELF}?{passthrough}&l=worker&h=calendar');
}
window.setTimeout('calendar_worker();', 1000);
// ]]>
</script>
<div style="display: none; width: 350px; height: 240px; overflow: auto;" id="alertevent">
 <iframe width="100%" height="100%" id="alertiframe" name="alertiframe" src="" frameborder="0"></iframe>
</div>