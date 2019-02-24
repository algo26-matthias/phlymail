<script type="text/javascript" src="{frontend_path}/js/timespan.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
RemindersCount = 1;
reminders = [];<!-- START multi_reminders -->
reminders.push({ 'time' : '{time}', 'range' : '{range}', 'mode' : '{mode}', 'mail' : '{mail}', 'sms' : '{sms}', 'text' : '{text}'});<!-- END multi_reminders -->
EditMode = '{editmode}';
function delete_task()
{
    if (confirm('{msg_reallydelete}') == true) {
        $.ajax({ url:'{delete_link}', success: save_done, dataType: 'json'});
    }
}

function disable_warn(active)
{
    $('#warndisable').css('display', (active) ? 'block' : 'none');
}

function disable_start()
{
    if ($(this).attr('checked')) {
        $('.task_start').removeAttr('disabled').css('visibility', 'visible');
    } else {
        $('.task_start').attr('disabled', 'disabled').css('visibility', 'hidden');
    }
}

function disable_end()
{
    if ($(this).attr('checked')) {
        $('.task_end').removeAttr('disabled').css('visibility', 'visible');
    } else {
        $('.task_end').attr('disabled', 'disabled').css('visibility', 'hidden');
    }
}

function transfer_date(f)
{
    if (EditMode != 'add') return true;
    $('#end_' + f).val($('#start_' + f).val());
}

function transfer_year()
{
    if (EditMode != 'add') return true;
    transfer_date('y');
}

function check_dates()
{
    // End >= Beginning
    var nowd   = new Date().getTime();
    var startd = new Date($('#date_start').datetimepicker('getDate')).getTime();
    var endd   = new Date($('#date_end').datetimepicker('getDate')).getTime();
    if (startd > endd
            && $('#has_start:checked').length
            && $('#has_end:checked').length) {
        alert('{msg_endlaterbegin}');
        return false;
    }

    var RetVal = true;
    $('.chk_repunt').each(function () {
        if (!$(this).is(':checked')) return true;
        var repUntDate = new Date($('#' + (this.id.replace(/^has_/, ''))).datetimepicker('getDate'));
        if (startd > repUntDate.getTime()) {
            alert('{msg_endlaterbegin}');
            RetVal = false;
        }
    });
    return RetVal;
}

function save_task()
{
    if (!check_dates()) return false;
    $.ajax({ url:'{form_target}', data: $(document.forms[0]).serialize(), type: 'POST', success: save_done, dataType: 'json'});
    return false;
}

function save_done(next)
{
    if (next['error']) {
        status_window();
        if (!confirm(next['error'])) done();
    }
    if (next['done']) {
        done();
    }
}

function done()
{
    if (opener.parent.parent.frames && opener.parent.parent.frames.PHM_tr) {
        try {
            opener.parent.parent.frames.PHM_tr.refreshlist();
        } catch (e) {
            opener.parent.parent.frames.PHM_tr.location.reload();
        }
    } else if (opener.frames && opener.frames.PHM_tr) {
        try {
            opener.frames.PHM_tr.refreshlist();
        } catch (e) {
            opener.frames.PHM_tr.location.reload();
        }
    } else {
        try { opener.refreshlist(); } catch (e) { opener.location.reload(); }
    }
    self.close();
}

function select_date(arg)
{
    date_to_select = arg;
    datecontrols.onselect = 'date_selected';
    var s_d = document.getElementById(arg + '_d').value;
    var s_m = document.getElementById(arg + '_m').value;
    var s_y = document.getElementById(arg + '_y').value;
    var sel = datecontrols.draw_selector('cal_', s_y, s_m, s_d);
    document.getElementById('dateselectorcont_' + arg).appendChild(sel);
    datecontrols.shadow();
}

function date_selected(jahr, monat, tag)
{
    var arg = date_to_select;
    $('#' + arg + '_y').val(jahr);
    $('#' + arg + '_m').val(monat);
    $('#' + arg + '_d').val(tag);
    if (arg == 'start') {
        transfer_date('d');
        transfer_date('m');
        transfer_year();
    }
}

function select_time(arg)
{
    time_to_select = arg;
    timecontrols.onselect = 'time_selected';
    var sel = timecontrols.draw_selector('cal_');
    document.getElementById('timeselectorcont_' + arg).appendChild(sel);
    timecontrols.shadow();
}

function time_selected(stund, minut)
{
    var arg = time_to_select;
    $('#' + arg + '_h').val(stund);
    $('#' + arg + '_mi').val(minut);
    if (arg == 'start') {
        transfer_date('h');
        transfer_date('mi');
    }
}

function draw_all_reminders()
{
    var pointer, i, j, worker;
    pointer = document.getElementById('reminders_template').parentNode;
    for (j = 1; j < reminders.length; ++j) {
        i = RemindersCount;
        worker = document.getElementById('reminders_template').cloneNode(true);
        worker.id = 'reminder_' + i;
        worker.insertBefore(document.createElement('hr'), worker.firstChild);
        $(worker).find('#reminders_mail_0_txt').attr('id', 'reminders_mail_' + i + '_txt').val(reminders[j].mail);
        $(worker).find('#reminders_mail_0_btn').attr('id', 'reminders_mail_' + i + '_btn').click(function() {
                combo_active('reminders_mail_'+i+'_txt','reminders_mail_'+i+'_sel','reminders_mail_'+i+'_btn')
                });
        $(worker).find('#reminders_mail_0_sel').attr('id', 'reminders_mail_' + i + '_sel');
        $(worker).find('#reminders_time_0').attr('id', 'reminders_time_' + i).val(reminders[j].time);
        $(worker).find('#reminders_mode_0').attr('id', 'reminders_mode_' + i).val(reminders[j].mode);
        $(worker).find('#reminders_range_0').attr('id', 'reminders_range_' + i).val(reminders[j].range);
        $(worker).find('#reminders_text_0').attr('id', 'reminders_text_' + i).val(reminders[j].text);
        $(worker).find('#reminders_delete_0').attr('id', 'reminders_delete_' + i).css('display', 'block').click(function() { $('#reminder_'+i).remove();});
        try { // SMS might be disabled
            $(worker).find('#reminders_sms_0_btn');
            $(worker).find('#reminders_sms_0_txt').attr('id', 'reminders_sms_' + i + '_txt').val(reminders[j].sms);
            $(worker).find('#reminders_sms_0_btn').attr('id', 'reminders_sms_' + i + '_btn').click(function() {
                    combo_active('reminders_sms_'+i+'_txt','reminders_sms_'+i+'_sel','reminders_sms_'+i+'_btn')
                    });
            $(worker).find('#reminders_sms_0_sel').attr('id', 'reminders_sms_' + i + '_sel');
        } catch (e) { }
        pointer.appendChild(worker);
        RemindersCount++;
    }
}

function add_new_reminder(data)
{
    var pointer, i, worker;
    i = RemindersCount;
    pointer = document.getElementById('reminders_template').parentNode;
    worker = document.getElementById('reminders_template').cloneNode(true);
    worker.id = 'reminder_' + i;
    worker.insertBefore(document.createElement('hr'), worker.firstChild);
    $(worker).find('#reminders_mail_0_txt').attr('id', 'reminders_mail_' + i + '_txt');
    $(worker).find('#reminders_mail_0_btn').attr('id', 'reminders_mail_' + i + '_btn').click(function() { combo_active('reminders_mail_'+i+'_txt','reminders_mail_'+i+'_sel','reminders_mail_'+i+'_btn')});
    $(worker).find('#reminders_mail_0_sel').attr('id', 'reminders_mail_' + i + '_sel');
    $(worker).find('#reminders_time_0').attr('id', 'reminders_time_' + i);
    $(worker).find('#reminders_mode_0').attr('id', 'reminders_mode_' + i);
    $(worker).find('#reminders_range_0').attr('id', 'reminders_range_' + i);
    $(worker).find('#reminders_text_0').attr('id', 'reminders_text_' + i);
    $(worker).find('#reminders_delete_0').attr('id', 'reminders_delete_' + i).css('display', 'block').click(function() { $('#reminder_'+i).remove();});
    try { // SMS might be disabled
        $(worker).find('#reminders_sms_0_txt').attr('id', 'reminders_sms_' + i + '_txt');
        $(worker).find('#reminders_sms_0_btn').attr('id', 'reminders_sms_' + i + '_btn').click(function() { combo_active('reminders_sms_'+i+'_txt','reminders_sms_'+i+'_sel','reminders_sms_'+i+'_btn')});
        $(worker).find('#reminders_sms_0_sel').attr('id', 'reminders_sms_' + i + '_sel');
    } catch (e) { }
    pointer.appendChild(worker);
    RemindersCount++;
}

function status_window(message)
{
    if (message) {
        $('#sendstatus').show();
        $('#sendstat_msg').text(message);
    } else {
        $('#sendstatus').hide();
    }
}

function showDuration(fromf, tof)
{
    var fromd = new Date($(fromf).datetimepicker('getDate'));
    var tod   = new Date($(tof).datetimepicker('getDate'));
    if (fromd > tod) {
        return '-------';
    }

    var span = timeSpan(fromd, tod, 'years,months,weeks,days,hours,minutes');
    var map = { 'years': 'y', "months": 'mo', "weeks": 'w', "days": 'd', "hours": 'h', "minutes": 'min'};
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

function addDuration(fromd, dauer, tod)
{
    var datum = new Date($(fromd).datetimepicker('getDate'));
    var dura  = $(dauer).val();
    // Cannot parse duration, or it is null
    if (dura.length == 0 || dura == '0min' || dura == '-------') {
        return datum;
    }
    // Tokenize and parse
    $.each(dura.split(' '), function (i, v) {
        var Match = v.match(/^(\d+)(y|mo|w|d|h|min)$/i);
        if (!Match) return true; // Nonparsable, ignore
        var typ = Match[2].toLowerCase(), wert = Match[1]*1;

        switch (typ) {
            case 'y':   datum.setFullYear(datum.getFullYear() + wert); break;
            case 'mo':  datum.setMonth(datum.getMonth() + wert);       break;
            case 'w':   datum.setDate(datum.getDate() + (7*wert));     break;
            case 'd':   datum.setDate(datum.getDate() + wert);         break;
            case 'h':   datum.setHours(datum.getHours() + wert);       break;
            case 'min': datum.setMinutes(datum.getMinutes() + wert);   break;
        }
    });
    return datum;
}

$(document).ready(function (e) {
    $('.datepicker,.datetimepicker,.timepicker,.duration').attr('autocomplete', 'off');

    disable_warn($('#warn').attr('checked'));
    combo_disable('reminders_mail_0_sel', 'reminders_mail_0_btn');
    try { combo_disable('reminders_sms_0_sel', 'reminders_sms_0_btn'); } catch (e) { }
    adjust_height();
    window.title = '{head_edit}';
    $('#tabpane').tabs().tabs('select', 0);
    $('#has_start').bind('keyup change', disable_start).change();
    $('#has_end').bind('keyup change', disable_end).change();
    $('#completionslider').slider(
            { "min": 0
            ,"max": 100
            ,"stepping": 1
            ,"value": $('#inp_completion').val()
            ,"slide": function (e, ui) { $('#inp_completion').val(ui.value); }
            });
    $('#inp_completion').bind('keyup change', function () { $('#completionslider').slider("value", $(this).val()) });
    draw_all_reminders();

    $('.datepicker').datepicker({ changeMonth: true, changeYear: true, selectOtherMonths: true, showOtherMonths: true, showWeek: true });
    $('.datetimepicker').datetimepicker({ changeMonth: true, changeYear: true, selectOtherMonths: true, showOtherMonths: true, showWeek: true });
    $('.timepicker').timepicker();
    $('#date_start').datetimepicker('option', 'onSelect', function () {
        $('#date_end').datetimepicker('option', 'minDate', new Date($(this).datetimepicker('getDate')).getTime());

        $('#date_end').datepicker('setDate', addDuration('#date_start', '#start_end_duration'));
        $('#start_end_duration').val(showDuration('#date_start', '#date_end'));

        if (EditMode != 'add') return;
        $('#date_end').val($(this).val());
    });
    $('#date_end').datetimepicker('option', 'onSelect', function () {

        $('#start_end_duration').val(showDuration('#date_start', '#date_end'));

        var newMinDate = new Date($(this).datetimepicker('getDate')).getTime();
        $('.repeat_until').each(function () {
            $(this).datetimepicker('option', 'minDate', newMinDate);
        });
    });
    $('#start_end_duration').val(showDuration('#date_start', '#date_end')).bind('keyup change', function () {
        $('#date_end').datepicker('setDate', addDuration('#date_start', '#start_end_duration'));
    });

    $('#sel_type option').each(function () {
        var evtClass = 'cal_edit_select';
        switch (parseInt($(this).attr('value'))) {
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
        $(this).addClass(evtClass);
    });

    $('#sel_status option').each(function () {
        var evtClass = 'cal_edit_select';
        switch (parseInt($(this).attr('value'))) {
            case  1: evtClass += ' cal_proposed'; break;
            case  3: evtClass += ' cal_cancelled'; break;
            case  4: evtClass += ' cal_delegated'; break;
            case  5: evtClass += ' cal_process'; break;
            case 10: evtClass += ' cal_tentative'; break;
            case 11: evtClass += ' cal_needsaction'; break;
            default: evtClass += ' cal_approved';
        }
        $(this).addClass(evtClass);
    });

    $('#title,#location').each(function() {
        var Node = $(this).parent();
        $(this).autocomplete({
            source : '{cal_search_uri}' + this.id + ''
            ,appendTo : Node
            ,position: { my: 'left top', at: 'left bottom', of: Node }
            ,minLength: 2
        })
    });
});
/*]]>*/
</script>
<div style="width:475px;">
    <form id="masterform" action="#" method="post" onsubmit="return save_task();">
        <div id="tabpane" class="ui-tabpane" style="height:456px;margin-top:4px;">
            <ul>
                <li><a href="#generic"><span>{msg_general}</span></a></li>
                <li><a href="#reminders"><span>{msg_reminder}</span></a></li><!-- START vielspaeter -->
                <li><a href="#attachments"><span>{msg_attachments}</span></a></li><!-- END vielspaeter -->
            </ul>
            <div id="generic">
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td class="l">{msg_title}</td>
                        <td class="l" colspan="3"><input id="title" type="text" name="title" value="{title}" size="36" maxlength="255" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td class="l">{msg_loc}</td>
                        <td class="l" colspan="3"><input id="location" type="text" name="location" value="{location}" size="36" maxlength="255" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td class="l">%h%CalProject%</td>
                        <td class="l">
                            <select size="1" name="pid">
                                <option value=""> --- </option><!-- START projectline -->
                                <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END projectline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_type} / {msg_group}</td>
                        <td class="l" colspan="3">
                            <select id="sel_type" size="1" name="type"><!-- START typeline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END typeline -->
                            </select>
                            /
                            <select size="1" name="gid">
                                <option value="">&lt; {msg_none} &gt;</option><!-- START groupline -->
                                <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END groupline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_status} / {msg_prio}</td>
                        <td class="l" colspan="3">
                            <select id="sel_status" size="1" name="status"><!-- START statusline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END statusline -->
                            </select>
                            /
                            <select size="1" name="importance"><!-- START prioline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END prioline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_completion}</td>
                        <td class="l" colspan="3">
                            <div id="completionslider" class="ui-slider" style="float:left;margin:0;"></div>
                            &nbsp;
                            <input type="text" size="3" maxlength="3" class="r" id="inp_completion" name="completion" value="{completion}" /> %
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_start}</td>
                        <td class="l">
                            <input type="checkbox" id="has_start" name="has_start" value="1"<!-- START has_start --> checked="checked"<!-- END has_start --> />
                                   <input type="text" class="datetimepicker" id="date_start" name="start" value="{start}" size="16" />
                        </td>
                        <td class="l">{msg_duration}</td>
                        <td class="l">
                            <input type="text" class="duration" id="start_end_duration" name="start_end_duration" value="" size="16" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_end}</td>
                        <td class="l" colspan="3">
                            <input type="checkbox" id="has_end" name="has_end" value="1"<!-- START has_end --> checked="checked"<!-- END has_end --> />
                                   <input type="text" class="datetimepicker" id="date_end" name="end" value="{end}" size="16" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_desc}</td>
                        <td class="l" colspan="3">
                            <textarea cols="36" rows="14" name="description" style="width:99%;">{description}</textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="reminders" class="l t">
                <input type="checkbox" id="warn" name="warn" onchange="disable_warn((this.checked)?true:false)" onclick="disable_warn((this.checked)?true:false)" value="1"<!-- START warn --> checked="checked"<!-- END warn --> />
                       <label for="warn">{head_warn}</label><br />
                <div id="warndisable">
                    <div id="reminders_template" style="position:relative;">
                        <button type="button" id="reminders_delete_0" style="display:none;position:absolute;top:10px;right:8px;">{msg_dele}</button>
                        <input type="text" name="reminders[time][]" id="reminders_time_0" value="{warn_time}" size="6" maxlength="6" class="r" />
                        <select size="1" name="reminders[range][]" id="reminders_range_0">
                            <option value="m"<!-- START s_w_m --> selected="selected"<!-- END s_w_m -->>{msg_minutes}</option>
                            <option value="h"<!-- START s_w_h --> selected="selected"<!-- END s_w_h -->>{msg_hours}</option>
                            <option value="d"<!-- START s_w_d --> selected="selected"<!-- END s_w_d -->>{msg_days}</option>
                            <option value="w"<!-- START s_w_w --> selected="selected"<!-- END s_w_w -->>{msg_weeks}</option>
                        </select>
                        <select size="1" name="reminders[mode][]" id="reminders_mode_0">
                            <option value="s"<!-- START s_w_s --> selected="selected"<!-- END s_w_s -->>{msg_warnbeforestart}</option>
                            <option value="e"<!-- START s_w_e --> selected="selected"<!-- END s_w_e -->>{msg_warnbeforeend}</option>
                        </select><br />
                        {msg_additionalalerts}:<br />
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tr>
                                <td class="l">{msg_title}</td>
                                <td class="l">
                                    <input type="text" name="reminders[text][]" id="reminders_text_0" value="{warn_text}" size="32" maxlength="255" />
                                </td>
                            </tr>
                            <tr>
                                <td class="l">{msg_mailto}</td>
                                <td class="l">
                                    <input type="text" name="reminders[mail][]" id="reminders_mail_0_txt" value="{warn_mail}" size="32" maxlength="255" />
                                    <img src="{theme_path}/icons/combobox_activator.gif" id="reminders_mail_0_btn" alt="" style="vertical-align:bottom;cursor:pointer;" onclick="combo_active('reminders_mail_0_txt','reminders_mail_0_sel','reminders_mail_0_btn');" />
                                    <select size="1" id="reminders_mail_0_sel" style="display:none;"><!-- START warnmail_profiles -->
                                        <option>{email}</option><!-- END warnmail_profiles -->
                                    </select>
                                </td>
                            </tr><!-- START external_alerting -->
                            <tr>
                                <td class="l">{msg_smsto}</td>
                                <td class="l">
                                    <input type="text" name="reminders[sms][]" id="reminders_sms_0_txt" value="{warn_sms}" size="32" maxlength="255" />
                                    <img src="{theme_path}/icons/combobox_activator.gif" id="reminders_sms_0_btn" alt="" style="vertical-align:bottom;cursor:pointer;" onclick="combo_active('reminders_sms_0_txt','reminders_sms_0_sel','reminders_sms_0_btn');" />
                                    <select size="1" id="reminders_sms_0_sel" style="display:none;"><!-- START warnsms_profiles -->
                                        <option>{sms}</option><!-- END warnsms_profiles -->
                                    </select>
                                </td>
                            </tr><!-- END external_alerting -->
                        </table>
                        <br />
                    </div>
                    <button type="button" onclick="add_new_reminder();">&nbsp;+&nbsp;</button>
                </div>
            </div>

            <div id="attachments" class="l t" style="display:none;">

            </div>

        </div>
        <div style="margin:2px 4px 4px 4px;;height:24p;"><!-- START delete_button -->
            <div style="float:right;margin-top:3px;">
                <button type="button" onclick="delete_task()" class="error">{msg_dele}</button>
            </div><!-- END delete_button -->
            <div style="float:left;"><!-- START save_button -->
                <input type="submit" value="{msg_save}" />&nbsp;<!-- END save_button --><!-- START saveascopy -->
                <input type="checkbox" name="copytask" value="1" id="copytask" />
                <label for="copytask">{msg_copytask}</label>&nbsp;<!-- END saveascopy -->
                <img id="busy" src="{theme_path}/images/busy.gif" style="visibility:hidden" alt="" title="Please wait" />
            </div>
        </div>
    </form>
</div>