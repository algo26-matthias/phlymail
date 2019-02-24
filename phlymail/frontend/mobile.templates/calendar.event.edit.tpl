<script type="text/javascript" src="{frontend_path}/js/timespan.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
var RemindersCount = 1;
var reminders = [];<!-- START multi_reminders -->
reminders.push({'time' : '{time}', 'range' : '{range}', 'mode' : '{mode}', 'mail' : '{mail}', 'sms' : '{sms}', 'text' : '{text}'});<!-- END multi_reminders -->
var RepetitionsCount = 1;
var repetitions = [];<!-- START multi_repetitions -->
repetitions.push({'type':'{type}','repeat':'{repeat}','extra':'{extra}','has_until':'{has_until}','until':'{until}'});<!-- END multi_repetitions -->
var NewAttendeesCount = 0;
var MayInviteEmail = 0<!-- START js_mayinviteemail -->+1<!-- END js_mayinviteemail -->;
var InvitationsQueue = [];
var InvitationEID = 0;
var EditMode = '{editmode}';
var ACCache = [];

function delete_event()
{
    if (confirm('{msg_reallydelete}') == true) {
        $.ajax({url:'{delete_link}', success: save_done, dataType: 'json'});
    }
}

function check_dates()
{
    // End >= Beginning
    var nowd   = new Date().getTime();
    var startd = new Date($('#date_start').datetimepicker('getDate')).getTime();
    var endd   = new Date($('#date_end').datetimepicker('getDate')).getTime();
    if (startd > endd) {
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

function save_event()
{
    if (!check_dates()) return false;
    $.ajax({ url:'{form_target}', data: $(document.forms[0]).serialize(), type: 'POST', success: save_done, dataType: 'json' });
    return false;
}

function save_done(next)
{
    if (next['error']) {
        status_window();
        if (!confirm(next['error'])) done();
    }
    if (next['send_invites']) {
        status_window();
        InvitationsQueue = next['send_invites'];
        InvitationEID = next['invite_eid'];
    }
    if (next['url']) {
        status_window(next['statusmessage']);
        $.ajax({ 'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : save_done});
        return;
    }
    if (next['done']) {
        if (InvitationsQueue.length) {
            send_invite();
        } else {
            done();
        }
    }
}

function send_invite()
{
    status_window('{msg_sending_invitation} ... ' + InvitationsQueue.length);
    var ID = InvitationsQueue.shift();
    $.ajax({ url:'{invite_link}' + InvitationEID + '&att=' + ID, success: save_done, dataType: 'json'});
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
        $(worker).find('#reminders_delete_0').attr('id', 'reminders_delete_' + i).css('display', 'block').click(function() {
            $('#reminder_'+i).remove();
        });
        try { // SMS might be disabled
            $(worker).find('#reminders_sms_0_btn');
            $(worker).find('#reminders_sms_0_txt').attr('id', 'reminders_sms_' + i + '_txt').val(reminders[j].sms);
            $(worker).find('#reminders_sms_0_btn').attr('id', 'reminders_sms_' + i + '_btn').click(function() {
                    combo_active('reminders_sms_'+i+'_txt','reminders_sms_'+i+'_sel','reminders_sms_'+i+'_btn')
                    });
            $(worker).find('#reminders_sms_0_sel').attr('id', 'reminders_sms_' + i + '_sel');
        } catch (e) {
        }
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
    $(worker).find('#reminders_mail_0_btn').attr('id', 'reminders_mail_' + i + '_btn')
        .click(function() {
            combo_active('reminders_mail_'+ i +'_txt', 'reminders_mail_' + i + '_sel', 'reminders_mail_' + i + '_btn');
        });
    $(worker).find('#reminders_mail_0_sel').attr('id', 'reminders_mail_' + i + '_sel');
    $(worker).find('#reminders_time_0').attr('id', 'reminders_time_' + i);
    $(worker).find('#reminders_mode_0').attr('id', 'reminders_mode_' + i);
    $(worker).find('#reminders_range_0').attr('id', 'reminders_range_' + i);
    $(worker).find('#reminders_text_0').attr('id', 'reminders_text_' + i);
    $(worker).find('#reminders_delete_0').attr('id', 'reminders_delete_' + i)
        .show()
        .click(function() {
            $('#reminder_' + i).remove();
        });
    try { // SMS might be disabled
        $(worker).find('#reminders_sms_0_txt').attr('id', 'reminders_sms_' + i + '_txt');
        $(worker).find('#reminders_sms_0_btn').attr('id', 'reminders_sms_' + i + '_btn')
            .click(function() {
                combo_active('reminders_sms_' + i + '_txt', 'reminders_sms_' + i + '_sel', 'reminders_sms_' + i + '_btn');
            });
        $(worker).find('#reminders_sms_0_sel').attr('id', 'reminders_sms_' + i + '_sel');
    } catch (e) {
    }
    pointer.appendChild(worker);
    RemindersCount++;
}

function draw_all_repetitions()
{
    for (var j = 1; j < repetitions.length; ++j) {
        add_new_repetition(repetitions[j]);
    }
}

function add_new_repetition(data)
{
    var pointer, i, worker;
    var dayNames = {1 : 'on_monday', 2 : 'on_tuesday', 3 : 'on_wednesday', 4 : 'on_thursday', 5 : 'on_friday', 6 : 'on_saturday', 7 : 'on_sunday'};
    i = RepetitionsCount;
    pointer = document.getElementById('repetitions_template').parentNode;
    worker = document.getElementById('repetitions_template').cloneNode(true);
    worker.id = 'repetition_' + i;
    worker.insertBefore(document.createElement('hr'), worker.firstChild);
    $(worker).find('label[for=repeat_none_0]').attr('for', 'repeat_none_' + i);
    $(worker).find('label[for=repeat_year_0]').attr('for', 'repeat_year_' + i);
    $(worker).find('label[for=repeat_month_0]').attr('for', 'repeat_month_' + i);
    $(worker).find('label[for=repeat_week_0]').attr('for', 'repeat_week_' + i);
    $(worker).find('label[for=repeat_day_0]').attr('for', 'repeat_day_' + i);
    $(worker).find('#repeat_none_0').attr('id', 'repeat_none_' + i).attr('name', 'repetitions[type][' + i + ']');
    $(worker).find('#repeat_year_0').attr('id', 'repeat_year_' + i).attr('name', 'repetitions[type][' + i + ']');
    $(worker).find('#repeat_month_0').attr('id', 'repeat_month_' + i).attr('name', 'repetitions[type][' + i + ']');
    $(worker).find('#repeat_week_0').attr('id', 'repeat_week_' + i).attr('name', 'repetitions[type][' + i + ']');
    $(worker).find('#repeat_day_0').attr('id', 'repeat_day_' + i).attr('name', 'repetitions[type][' + i + ']');
    $(worker).find('#has_repunt_0').attr('id', 'has_repunt_' + i).attr('name', 'repetitions[has_repunt][' + i + ']')
    $(worker).find('label[for=has_repunt_0]').attr('for', 'has_repunt_' + i);
    $(worker).find('#repunt_0').attr('id', 'repunt_' + i);
    $(worker).find('#repetitions_delete_0').attr('id', 'repetitions_delete_' + i)
            .show()
            .click(function() {
                $('#repetition_' + i).remove();
            });
    $(worker).find('[name*="repmon_"]').removeAttr('checked');

    $.each([1,2,3,4,5,6,7,8,9,10,11,12], function (k, val) {
        $(worker).find('#rep_repmon_' + val + '_0')
                .attr({'id' : 'rep_repmon_' + val + '_' + i, 'name' : 'repetitions[repmon_' + val + '][' + i + ']'})
                .removeAttr('checked');
        if (k < 8) {
            $(worker).find('#rep_repday_' + val + '_0')
                    .attr({'id' : 'rep_repday_' + val + '_' + i, 'name' : 'repetitions[' + dayNames[val] + '][' + i + ']'})
                    .removeAttr('checked');
        }
    });
    if (data) {
        $(worker).find('#repeat_' + data['type'] + '_' + i).attr('checked', 'checked');
        $(worker).find('#repunt_' + i).val(data.until);

        if (data.has_until == 1) {
            $(worker).find('#has_repunt_' + i).attr('checked', 'checked');
        } else {
            $(worker).find('#has_repunt_' + i).removeAttr('checked');
        }
        if (data.type == 'day') {
            if (data['repeat'] &  1) $(worker).find('[name*=on_sunday]').attr('checked', 'checked');
            if (data['repeat'] &  2) $(worker).find('[name*=on_saturday]').attr('checked', 'checked');
            if (data['repeat'] &  4) $(worker).find('[name*=on_friday]').attr('checked', 'checked');
            if (data['repeat'] &  8) $(worker).find('[name*=on_thursday]').attr('checked', 'checked');
            if (data['repeat'] & 16) $(worker).find('[name*=on_wednesday]').attr('checked', 'checked');
            if (data['repeat'] & 32) $(worker).find('[name*=on_tuesday]').attr('checked', 'checked');
            if (data['repeat'] & 64) $(worker).find('[name*=on_monday]').attr('checked', 'checked');
        }
        if (data.type == 'week') {
            $(worker).find('[name=repetitions\\[week\\]\\[\\]]').val(data['repeat']);
        }
        if (data.type == 'month') {
            $(worker).find('[name=repetitions\\[month\\]\\[\\]]').val(data['repeat']);
            if (data.extra.length > 0) {
                $.each(data.extra.split(','), function () {
                    $(worker).find('#rep_repmon_' + this + '_' + i).attr('checked', 'checked');
                });
            }
        }
    }
    pointer.appendChild(worker);
    RepetitionsCount++;
}

function pre_editattendee(id)
{
    var aname, email, attendance, arole, atype, myrow;
    myrow = $('#attendee_row_' + id);
    aname = myrow.find('.attendee_name').html();
    email = myrow.find('.attendee_email').html();
    attendance = myrow.find('.attendance');
    if (attendance.hasClass('attend_1')) {
        attendance = 1;
    } else if (attendance.hasClass('attend_2')) {
        attendance = 2;
    } else if (attendance.hasClass('attend_3')) {
        attendance = 3;
    } else {
        attendance = 0;
    }
    arole = myrow.find('.role');
    if (arole.hasClass('role_chair')) {
        arole = 'chair';
    } else if (arole.hasClass('role_req')) {
        arole = 'req';
    } else if (arole.hasClass('role_none')) {
        arole = 'none';
    } else {
        arole = 'optional';
    }
    atype = myrow.find('.type');
    if (atype.hasClass('type_resource')) {
        atype = 'resource';
    } else if (atype.hasClass('type_room')) {
        atype = 'room';
    } else if (atype.hasClass('type_group')) {
        atype = 'group';
    } else if (atype.hasClass('type_unknown')) {
        atype = 'unknown';
    } else {
        atype = 'person';
    }

    open_editattendee(id, aname, email, attendance, arole, atype);
}

function open_editattendee(id, aname, email, attend, arole, atype)
{
    editattendeeaction = 'add';
    editattendeeid = false;
    if (id) {
        editattendeeaction = 'edit';
        editattendeeid = id;
    }
    if (typeof attend == 'undefined' || attend.length == 0) attend = 0;
    $('#attendee_name').val((aname) ? aname.replace(/&quot;/g, '"') : '');
    $('#attendee_email').val((email) ? email.replace(/&quot;/g, '"') : '');
    $('#attendee_status_' + attend).attr('checked', 'checked');
    $('#attendee_role').val((arole) ? arole.replace(/&quot;/g, '"') : 'optional');
    $('#attendee_type').val((atype) ? atype.replace(/&quot;/g, '"') : 'person');

    $('#attendee_name').autocomplete({
         appendTo : $('#attendee_name').parent()
        ,source: function( request, response ) {
            var term = request.term;
			if (typeof ACCache[term] != 'undefined' ) {
				response(ACCache[term]);
				return;
			}
			lastXhr = $.getJSON('{adb_search_uri}', request, function( data, status, xhr ) {
                $.each(data, function (k, v) {
                    data[k]['label'] = v.fname + ' ' + v.lname + ' - ' + v.email;
                });
				ACCache[term] = data;
				if (xhr === lastXhr) {
					response(data);
				}
            });
        }
        ,position: { my: 'left top', at: 'left bottom', of: $('#attendee_name').parent() }
        ,minLength: 2
        ,focus: function( event, ui ) {
			$('#attendee_name').val(ui.item.fname + ' ' + ui.item.lname + ' - ' + ui.item.email);
			return false;
		}
        ,select: function( event, ui ) {
			$('#attendee_name').val(ui.item.fname + ' ' + ui.item.lname);
			$('#attendee_email').val(ui.item.email);
			return false;
		}
    });
    $('#div_edit_attendee').show();
}

function react_editattendee()
{
    if ('add' == editattendeeaction) { addattendee(); } else { editattendee(editattendeeid); }
}

function cancel_editattendee()
{
    $('#div_edit_attendee').hide();
}

function addattendee()
{
    var aname, email, attendance, html, rsvp, rsvpicon, arole, roleicon, roletext, atype, typeicon, typetext;
    aname = $('#attendee_name').val();
    email = $('#attendee_email').val();
    arole = $('#attendee_role').val();
    atype = $('#attendee_type').val();
    if (!aname && !email) {
        alert('{e_enterhkey}');
    } else {
        if ($('#attendee_status_1:checked').length == 1) {
            attendance = 1;
            rsvpicon = '{theme_path}/icons/cal_men_rsvp_yes.png';
            rsvp = '{msg_attendance_yes}';
        } else if ($('#attendee_status_2:checked').length == 1) {
            attendance = 2;
            rsvpicon = '{theme_path}/icons/cal_men_rsvp_no.png';
            rsvp = '{msg_attendance_no}';
        } else if ($('#attendee_status_3:checked').length == 1) {
            attendance = 3;
            rsvpicon = '{theme_path}/icons/cal_men_rsvp_maybe.png';
            rsvp = '{msg_attendance_maybe}';
        } else {
            attendance = 0;
            rsvpicon = '{theme_path}/icons/cal_men_rsvp_none.png';
            rsvp = '{msg_attendance_none}';
        }
        var mayinvite = (email.length > 0 && email.match(/.+\@.+/)) ? '' : ' disabled="disabled"';
        if (arole == 'chair') {
            roletext = '{msg_role_chair}';
            roleicon = '{theme_path}/icons/cal_men_role_chair.png';
        } else if (arole == 'req') {
            roletext = '{msg_role_required}';
            roleicon = '{theme_path}/icons/cal_men_role_req.png';
        } else if (arole == 'none') {
            roletext = '{msg_role_none}';
            roleicon = '{theme_path}/icons/cal_men_role_none.png';
        } else  {
            roletext = '{msg_role_optional}';
            roleicon = '{theme_path}/icons/cal_men_role_opt.png';
        }
        if (atype == 'resource') {
            typetext = '{msg_type_resource}';
            typeicon = '{theme_path}/icons/cal_men_type_resource.png';
        } else if (atype == 'room') {
            typetext = '{msg_type_room}';
            typeicon = '{theme_path}/icons/cal_men_type_room.png';
        } else if (atype == 'group') {
            typetext = '{msg_type_group}';
            typeicon = '{theme_path}/icons/cal_men_type_group.png';
        } else if (atype == 'unknown') {
            typetext = '{msg_type_unknown}';
            typeicon = '{theme_path}/icons/cal_men_rsvp_none.png';
        } else {
            typetext = '{msg_type_person}';
            typeicon = '{theme_path}/icons/cal_men_type_person.png';
        }
        // Appned HTML to attendee table
        html = '<tr id="attendee_row_x_' + NewAttendeesCount + '">';
        html += '<td>&nbsp;</td>';
        html += '<td style="cursor:pointer" onclick="deleteattendee(\'x_' + NewAttendeesCount + '\')" title="{msg_dele}">';
        html += '<img src="{theme_path}/icons/dustbin_menu.gif" alt="" /></td>';
        html += '<td>' + aname + '</td><td>' + email + '</td>';
        html += '<td title="' + roletext + '" class="role role_' + arole + '"><img src="' + roleicon + '" alt="" /></td>';
        html += '<td title="' + typetext + '" class="type type_' + atype + '"><img src="' + typeicon + '" alt="" /></td>';
        html += '<td title="{msg_invite_email}">';
        html += '<input type="checkbox" name="invite_email[x_' + NewAttendeesCount + ']" value="' + NewAttendeesCount + '"' + mayinvite + ' /></td>';
        html += '<td>&nbsp;</td>';
        html += '<td title="' + rsvp + '" class="attendance attend_' + attendance + '">';
        html += '<img src="' + rsvpicon + '" alt="" /></td></tr>';
        $('#attendees_tbody').append(html);

        // Add hidden fields to tell PHP
        html = '<input type="hidden" name="addattendee[' + NewAttendeesCount + '][name]" value="' + aname.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="addattendee[' + NewAttendeesCount + '][email]" value="' + email.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="addattendee[' + NewAttendeesCount + '][role]" value="' + arole.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="addattendee[' + NewAttendeesCount + '][type]" value="' + atype.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="addattendee[' + NewAttendeesCount + '][status]" value="' + attendance +'" />';
        $('#masterform').append(html);
        // Close box;
        cancel_editattendee();
    }
    NewAttendeesCount++;
}

function editattendee(id)
{
    var aname, email, attendance, myrow, rsvp, icon, html, arole, roleicon, roletext, atype, typeicon, typetext;
    aname = $('#attendee_name').val();
    email = $('#attendee_email').val();
    arole = $('#attendee_role').val();
    atype = $('#attendee_type').val();
    if (!aname && !email) {
        alert('{e_enterhkey}');
    } else {
        if ($('#attendee_status_1:checked').length == 1) {
            attendance = 1;
            icon = 'cal_men_rsvp_yes.png';
            rsvp = '{msg_attendance_yes}';
        } else if ($('#attendee_status_2:checked').length == 1) {
            attendance = 2;
            icon = 'cal_men_rsvp_no.png';
            rsvp = '{msg_attendance_no}';
        } else if ($('#attendee_status_3:checked').length == 1) {
            attendance = 3;
            icon = 'cal_men_rsvp_maybe.png';
            rsvp = '{msg_attendance_maybe}';
        } else {
            attendance = 0;
            icon = 'cal_men_rsvp_none.png';
            rsvp = '{msg_attendance_none}';
        }
        if (arole == 'chair') {
            roletext = '{msg_role_chair}';
            roleicon = '{theme_path}/icons/cal_men_role_chair.png';
        } else if (arole == 'req') {
            roletext = '{msg_role_required}';
            roleicon = '{theme_path}/icons/cal_men_role_req.png';
        } else if (arole == 'none') {
            roletext = '{msg_role_none}';
            roleicon = '{theme_path}/icons/cal_men_role_none.png';
        } else {
            roletext = '{msg_role_optional}';
            roleicon = '{theme_path}/icons/cal_men_role_opt.png';
        }
        if (atype == 'resource') {
            typetext = '{msg_type_resource}';
            typeicon = '{theme_path}/icons/cal_men_type_resource.png';
        } else if (atype == 'room') {
            typetext = '{msg_type_room}';
            typeicon = '{theme_path}/icons/cal_men_type_room.png';
        } else if (atype == 'group') {
            typetext = '{msg_type_group}';
            typeicon = '{theme_path}/icons/cal_men_type_group.png';
        } else if (atype == 'unknown') {
            typetext = '{msg_type_unknown}';
            typeicon = '{theme_path}/icons/cal_men_rsvp_none.png';
        } else {
            typetext = '{msg_type_person}';
            typeicon = '{theme_path}/icons/cal_men_type_person.png';
        }
        // Update DOM nodes related to <id>
        myrow = $('#attendee_row_' + id);
        myrow.find('.attendee_name').html(aname);
        myrow.find('.attendee_email').html(email);
        myrow.find('.attendance').removeClass().addClass('attendance attend_' + attendance).attr('title', rsvp).find('img').attr('src', '{theme_path}/icons/' + icon);
        myrow.find('.role').removeClass().addClass('role role_' + arole).find('img').attr({'src': roleicon, 'title': roletext, 'alt': roletext});
        myrow.find('.type').removeClass().addClass('type type_' + atype).find('img').attr({'src': typeicon, 'title': typetext, 'alt': typetext});
        // Add hidden fields to tell PHP
        html = '<input type="hidden" name="editattendee[' + id + '][name]" value="' + aname.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="editattendee[' + id + '][email]" value="' + email.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="editattendee[' + id + '][role]" value="' + arole.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="editattendee[' + id + '][type]" value="' + atype.replace(/\"/g, '&quot;') +'" />'
            + '<input type="hidden" name="editattendee[' + id + '][status]" value="' + attendance +'" />';
        $('#masterform').append(html);
        // Close box;
        cancel_editattendee();
    }
}

function deleteattendee(id)
{
    if (confirm('{msg_delattendee}')) {
        if (id.toString().match(/^x_(\d+)/)) {
            $('#masterform input[type=hidden][name^=addattendee\\[' + id.replace(/^x_/, '') + '\\]]').remove();
        } else {
            $('#masterform').append('<input type="hidden" name="delattendee[' + id + ']" value="1" />');
        }
        $('#attendee_row_' + id).remove();
    }
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
    $('.chk_repunt').live('change keyup', function () {
        var field = this.id.replace(/^has_/, '');
        if ($(this).is(':checked')) {
            $('#' + field).removeAttr('disabled');
        } else {
            $('#' + field).attr('disabled', 'disabled');
        }
    }).keyup();

    combo_disable('reminders_mail_0_sel', 'reminders_mail_0_btn');
    try { combo_disable('reminders_sms_0_sel', 'reminders_sms_0_btn'); } catch (e) { }
    adjust_height();
    window.title = '{head_edit}';
    $('#tabpane').tabs().tabs('select', 0);
    draw_all_reminders();
    draw_all_repetitions();
    $('#chk_allday').bind('click keyup', function () {
        if ($(this).is(':checked')) {
            $('#date_start').val($('#date_start').val().substr(0, 11) + '00:00');
            $('#date_end').val($('#date_end').val().substr(0, 11) + '23:59');
        }
    }).keyup();

    $('#warn').bind('click keyup', function () {
        if ($(this).is(':checked')) {
            $('#warndisable').show();
        } else {
            $('#warndisable').hide();
        }
    }).keyup();

    $('#date_start').datetimepicker('option', 'onSelect', function () {
        $('#date_end')
                .datetimepicker('option', 'minDate', new Date($(this).datetimepicker('getDate')).getTime())
                .datetimepicker('setDate', addDuration('#date_start', '#start_end_duration'));
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
        $('#date_end').datetimepicker('setDate', addDuration('#date_start', '#start_end_duration'));
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
<div style="width:455px;">
    <form id="masterform" action="#" method="post" onsubmit="return save_event();">
        <div id="tabpane" class="ui-tabpane" style="height:456px;margin-top:4px;">
            <ul>
                <li><a href="#generic"><span>{msg_general}</span></a></li>
                <li><a href="#reminders"><span>{msg_reminder}</span></a></li>
                <li><a href="#repetition"><span>{msg_repetition}</span></a></li>
                <li><a href="#attendees"><span>{msg_attendees}</span></a></li><!-- START vielspaeter -->
                <li><a href="#attachments"><span>{msg_attachments}</span></a></li><!-- END vielspaeter -->
            </ul>
            <div id="generic">
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td class="l">{msg_title}</td>
                        <td class="l"><input id="title" type="text" name="title" value="{title}" size="36" maxlength="255" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td class="l">{msg_loc}</td>
                        <td class="l"><input type="text" id="location" name="location" value="{location}" size="36" maxlength="255" style="width:99%;" /></td>
                    </tr>
                    <!-- tr>
                        <td class="l">%h%CalProject%</td>
                        <td class="l">
                            <select size="1" name="pid">
                                <option value=""> - </option><!-- START projectline -->
                                <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END projectline -->
                            </select>
                        </td>
                    </tr -->
                    <tr>
                        <td class="l">{msg_group} / {msg_type}</td>
                        <td class="l">
                            <select size="1" name="gid"><!-- START groupline -->
                                <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END groupline -->
                            </select>
                            /
                            <select id="sel_type" size="1" name="type"><!-- START typeline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END typeline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l">{msg_status}</td>
                        <td class="l">
                            <select id="sel_status" size="1" name="status"><!-- START statusline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END statusline -->
                            </select>
                            /
                            <select size="1" name="opaque"><!-- START opacityline -->
                                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END opacityline -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_start}</td>
                        <td class="l t">
                            <input type="text" class="datetimepicker" id="date_start" name="start" value="{start}" size="16" />
                            <div style="float:right;">
                                {msg_end}
                                <input type="text" class="datetimepicker" id="date_end" name="end" value="{end}" size="16" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="l">
                            <input type="checkbox" name="allday" value="1" id="chk_allday"<!-- START is_allday --> checked="checked"<!-- END is_allday --> />
                            <label for="chk_allday">{msg_allday}</label>

                            <div style="float:right;">
                                {msg_duration}
                                <input type="text" class="duration" id="start_end_duration" name="start_end_duration" value="" size="16" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_desc}</td>
                        <td class="l">
                            <textarea cols="36" rows="15" name="description" style="width:99%;">{description}</textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="reminders">
                <input type="checkbox" id="warn" name="warn" value="1"<!-- START warn --> checked="checked"<!-- END warn --> />
                <label for="warn">{head_warn}</label>
                <br />
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
                        </select>
                        <br />
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tr>
                                <td class="l">{msg_title}</td>
                                <td class="l">
                                    <input type="text" name="reminders[text][]" id="reminders_text_0" value="{warn_text}" size="32" maxlength="255" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="l">
                                    {msg_additionalalerts}:
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

            <div id="repetition" class="l t">
                <div id="repetitions_template" style="position:relative;">
                    <button type="button" id="repetitions_delete_0" style="display:none;position:absolute;top:10px;right:8px;">{msg_dele}</button>
                    <input type="radio" name="repetitions[type][0]" value="-" id="repeat_none_0"{selrepeatnone}/><label for="repeat_none_0">{msg_none}</label><br />
                    <input type="radio" name="repetitions[type][0]" value="year" id="repeat_year_0"{selrepeatyear}/><label for="repeat_year_0">{msg_yearly}</label><br />
                    <input type="radio" name="repetitions[type][0]" value="month" id="repeat_month_0"{selrepeatmonth}/><label for="repeat_month_0">{msg_monthly} {msg_on}</label>
                    <select name="repetitions[month][]" size="1"><!-- START repmonlin -->
                        <option value="{day}"<!-- START sel --> selected="selected"<!-- END sel -->>{msg_day}</option><!-- END repmonlin -->
                    </select>
                    <br />
                    <table border="0" cellpadding="2" cellspacing="0">
                        <tr>
                            <td class="c" title="{title_jan}">{msg_jan}</td>
                            <td class="c" title="{title_feb}">{msg_feb}</td>
                            <td class="c" title="{title_mar}">{msg_mar}</td>
                            <td class="c" title="{title_apr}">{msg_apr}</td>
                            <td class="c" title="{title_may}">{msg_may}</td>
                            <td class="c" title="{title_jun}">{msg_jun}</td>
                            <td class="c" title="{title_jul}">{msg_jul}</td>
                            <td class="c" title="{title_aug}">{msg_aug}</td>
                            <td class="c" title="{title_sep}">{msg_sep}</td>
                            <td class="c" title="{title_oct}">{msg_oct}</td>
                            <td class="c" title="{title_nov}">{msg_nov}</td>
                            <td class="c" title="{title_dec}">{msg_dec}</td>
                        </tr>
                        <tr>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_1][0]" id="rep_repmon_1_0" value="1"{sel_repmon_1} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_2][0]" id="rep_repmon_2_0" value="1"{sel_repmon_2} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_3][0]" id="rep_repmon_3_0" value="1"{sel_repmon_3} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_4][0]" id="rep_repmon_4_0" value="1"{sel_repmon_4} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_5][0]" id="rep_repmon_5_0" value="1"{sel_repmon_5} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_6][0]" id="rep_repmon_6_0" value="1"{sel_repmon_6} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_7][0]" id="rep_repmon_7_0" value="1"{sel_repmon_7} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_8][0]" id="rep_repmon_8_0" value="1"{sel_repmon_8} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_9][0]" id="rep_repmon_9_0" value="1"{sel_repmon_9} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_10][0]" id="rep_repmon_10_0" value="1"{sel_repmon_10} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_11][0]" id="rep_repmon_11_0" value="1"{sel_repmon_11} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[repmon_12][0]" id="rep_repmon_12_0" value="1"{sel_repmon_12} /></td>
                        </tr>
                    </table>
                    <br />
                    <br />
                    <input type="radio" name="repetitions[type][0]" value="week" id="repeat_week_0"{selrepeatweek}/><label for="repeat_week_0">{msg_weekly} {msg_on}</label>
                    <select name="repetitions[week][]" size="1"><!-- START repweelin -->
                        <option value="{day}"<!-- START sel --> selected="selected"<!-- END sel -->>{msg_day}</option><!-- END repweelin -->
                    </select>
                    <br />
                    <input type="radio" name="repetitions[type][0]" value="day" id="repeat_day_0"{selrepeatday}/><label for="repeat_day_0">{msg_daily} {msg_on}</label><br />
                    <table border="0" cellpadding="2" cellspacing="0">
                        <tr>
                            <td class="c">{msg_monday}</td>
                            <td class="c">{msg_tuesday}</td>
                            <td class="c">{msg_wednesday}</td>
                            <td class="c">{msg_thursday}</td>
                            <td class="c">{msg_friday}</td>
                            <td class="c">{msg_saturday}</td>
                            <td class="c">{msg_sunday}</td>
                        </tr>
                        <tr>
                            <td class="c"><input type="checkbox" name="repetitions[on_monday][0]" id="rep_repday_1_0" value="1"{sel_monday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_tuesday][0]" id="rep_repday_2_0" value="1"{sel_tuesday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_wednesday][0]" id="rep_repday_3_0" value="1"{sel_wednesday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_thursday][0]" id="rep_repday_4_0" value="1"{sel_thursday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_friday][0]" id="rep_repday_5_0" value="1"{sel_friday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_saturday][0]" id="rep_repday_6_0" value="1"{sel_saturday} /></td>
                            <td class="c"><input type="checkbox" name="repetitions[on_sunday][0]" id="rep_repday_7_0" value="1"{sel_sunday} /></td>
                        </tr>
                    </table>
                    <br />
                    <input type="checkbox" name="repetitions[has_repunt][0]" id="has_repunt_0" class="chk_repunt" value="1"<!-- START has_repunt --> checked="checked"<!-- END has_repunt --> />
                    <label for="has_repunt_0">{msg_repunt}</label>
                    <input type="text" class="datetimepicker repeat_until" name="repetitions[repunt][]" id="repunt_0" value="{repunt}" size="16" />
                    <br />
                </div>
                <button type="button" onclick="add_new_repetition();">&nbsp;+&nbsp;</button>
            </div>

            <div id="attendees" class="l t">
                <div>
                    <table border="0" cellpadding="2" cellspacing="0" width="100%">
                        <colgroup>
                            <col width="16" />
                            <col width="16" />
                            <col width="*" />
                            <col width="*" />
                            <col width="16" />
                            <col width="16" />
                            <col width="16" />
                            <col width="16" />
                            <col width="16" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                                <th>{msg_attendee}</th>
                                <th>{msg_email}</th>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                                <th title="{msg_invite_email}"><img src="{theme_path}/icons/mail_unread.gif" alt="" /></th>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody id="attendees_tbody"><!-- START attendee_line -->
                            <tr id="attendee_row_{id}">
                                <td style="cursor:pointer;" onclick="pre_editattendee({id});" title="{msg_edit}">
                                    <img src="{theme_path}/icons/edit_menu.gif" alt="" />
                                </td>
                                <td style="cursor:pointer" onclick="deleteattendee({id})" title="{msg_dele}">
                                    <img src="{theme_path}/icons/dustbin_menu.gif" alt="" />
                                </td>
                                <td><span class="attendee_name">{name}</span></td>
                                <td><span class="attendee_email">{email}</span></td>
                                <td class="role role_{role}"><!-- START role_chair -->
                                    <img src="{theme_path}/icons/cal_men_role_chair.png" alt="{msg_role}" title="{msg_role}" /><!-- END role_chair --><!-- START role_req -->
                                    <img src="{theme_path}/icons/cal_men_role_req.png" alt="{msg_role}" title="{msg_role}" /><!-- END role_req --><!-- START role_opt -->
                                    <img src="{theme_path}/icons/cal_men_role_opt.png" alt="{msg_role}" title="{msg_role}" /><!-- END role_opt --><!-- START role_none -->
                                    <img src="{theme_path}/icons/cal_men_role_none.png" alt="{msg_role}" title="{msg_role}" /><!-- END role_none -->
                                </td>
                                <td class="type type_{type}"><!-- START type_person -->
                                    <img src="{theme_path}/icons/cal_men_type_person.png" alt="{msg_type}" title="{msg_type}" /><!-- END type_person --><!-- START type_group -->
                                    <img src="{theme_path}/icons/cal_men_type_group.png" alt="{msg_type}" title="{msg_type}" /><!-- END type_group --><!-- START type_resource -->
                                    <img src="{theme_path}/icons/cal_men_type_resource.png" alt="{msg_type}" title="{msg_type}" /><!-- END type_resource --><!-- START type_room -->
                                    <img src="{theme_path}/icons/cal_men_type_room.png" alt="{msg_type}" title="{msg_type}" /><!-- END type_room --><!-- START type_unknown -->
                                    <img src="{theme_path}/icons/cal_men_rsvp_none.png" alt="{msg_type}" title="{msg_type}" /><!-- END type_unknown -->
                                </td>
                                <td title="{msg_invite_email}">
                                    <input type="checkbox" name="invite_email[{id}]" value="{uuid}"<!-- START disable_invite_email --> disabled="disabled"<!-- END disable_invite_email --> />
                                </td>
                                <td title="{msg_invited_on}"><!-- START is_invited -->
                                    <img src="{theme_path}/icons/selected_men.png" alt="" /><!-- END is_invited -->
                                </td>
                                <td title="{msg_rsvp_status}" class="attendance attend_{status}"><!-- START rsvp_yes -->
                                    <img src="{theme_path}/icons/cal_men_rsvp_yes.png" alt="" /><!-- END rsvp_yes --><!-- START rsvp_no -->
                                    <img src="{theme_path}/icons/cal_men_rsvp_no.png" alt="" /><!-- END rsvp_no --><!-- START rsvp_maybe -->
                                    <img src="{theme_path}/icons/cal_men_rsvp_maybe.png" alt="" /><!-- END rsvp_maybe --><!-- START rsvp_none -->
                                    <img src="{theme_path}/icons/cal_men_rsvp_none.png" alt="" /><!-- END rsvp_none -->
                                </td>
                            </tr><!-- END attendee_line -->
                        </tbody>
                    </table>
                </div>
                <button type="button" onclick="open_editattendee();">&nbsp;+&nbsp;</button>
            </div>
            <div id="attachments"></div>
        </div>

        <div style="margin:2px 4px 4px 4px;;height:24px;"><!-- START delete_button -->
            <div style="float:right;margin-top:3px;">
                <button type="button" onclick="delete_event()" class="error">{msg_dele}</button>
            </div><!-- END delete_button -->
            <div style="float:left;"><!-- START save_button -->
                <input type="submit" value="{msg_save}" />&nbsp;<!-- END save_button --><!-- START saveascopy -->
                <input type="checkbox" name="copyevent" value="1" id="copyevent" />
                <label for="copyevent">{msg_copyevent}</label>&nbsp;<!-- END saveascopy -->
                <img id="busy" src="{theme_path}/images/busy.gif" style="visibility:hidden" alt="" title="Please wait" />
            </div>
        </div>
    </form>
    <div id="div_edit_attendee" class="sendmenubut shadowed" style="display:none;position:absolute;left:20px;top:20px;z-index:100;width:320px;">
        <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td class="l t">{msg_name}:</td>
                    <td class="l t"><input type="text" name="name" id="attendee_name" size="32" maxlength="64" /></td>
                </tr>
                <tr>
                    <td class="l t">{msg_email}:</td>
                    <td class="l t"><input type="text" name="email" id="attendee_email" size="32" maxlength="255" /></td>
                </tr>
                <tr>
                    <td class="l t">{msg_attendance}:</td>
                    <td class="l t">
                        <input type="radio" name="attendance" value="1" id="attendee_status_1" />
                        <label for="attendee_status_1" title="{msg_attendance_yes}"><img src="{theme_path}/icons/cal_men_rsvp_yes.png" alt="" /></label>
                        &nbsp;
                        <input type="radio" name="attendance" value="2" id="attendee_status_2" />
                        <label for="attendee_status_2" title="{msg_attendance_no}"><img src="{theme_path}/icons/cal_men_rsvp_no.png" alt="" /></label>
                        &nbsp;
                        <input type="radio" name="attendance" value="3" id="attendee_status_3" />
                        <label for="attendee_status_3" title="{msg_attendance_maybe}"><img src="{theme_path}/icons/cal_men_rsvp_maybe.png" alt="" /></label>
                        &nbsp;
                        <input type="radio" name="attendance" value="0" id="attendee_status_0" />
                        <label for="attendee_status_0" title="{msg_attendance_none}"><img src="{theme_path}/icons/cal_men_rsvp_none.png" alt="" /></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        {msg_role}
                        <select size="1" name="role" id="attendee_role">
                            <option value="opt">{msg_role_optional}</option>
                            <option value="req">{msg_role_required}</option>
                            <option value="chair">{msg_role_chair}</option>
                            <option value="none">{msg_role_none}</option>
                        </select>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        {msg_CuType}
                        <select size="1" name="type" id="attendee_type">
                            <option value="person">{msg_type_person}</option>
                            <option value="group">{msg_type_group}</option>
                            <option value="resource">{msg_type_resource}</option>
                            <option value="room">{msg_type_room}</option>
                            <option value="unknown">{msg_type_unknown}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="l"><button type="button" class="ok" onclick="react_editattendee();">{msg_save}</button></td>
                    <td class="r"><button type="button" class="error" onclick="cancel_editattendee();">{msg_cancel}</button></td>
                </tr>
            </table>
        </form>
    </div>
    <div id="sendstatus" class="sendmenubut shadowed" style="display:none;width:200px;height:40px;z-index:100;position:absolute;right:50px;top:50px;">
        <div class="c t" id="sendstat_msg"> </div>
        <div class="prgr_outer">
            <div class="prgr_inner_busy"></div>
        </div>
    </div>
</div>