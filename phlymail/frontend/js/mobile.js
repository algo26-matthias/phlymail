/*
 * All the JS bits and pieces for the mobile frontend
 *
 * @copyright 2012-2013 phlyLabs, Berlin
 * @version 0.2.1 2013-01-09
 */

$.mobile.loader.prototype.options.textVisible = true;

var idCounter = 1;
var saveProfileOrderUri = null;
var profilesSSLAvaialble = false;
var sendEmailIsDraft  = 0;
var emailAttachUniqids = {};
var isTouchSupported = 'ontouchend' in window.document;
var ABSTRACTCLICK = isTouchSupported ? 'touchend' : 'click';

function slider2hrtime(inp)
{
    var hour, minute;
    hour = parseInt(inp/100);
    minute = (((inp/100)-hour)*60).toString();
    hour = hour.toString();
    if (hour.length < 2) hour = '0' + hour;
    if (minute.length < 2) minute = '0' + minute;
    return hour + ':' + minute;
}

function fullEmail2RealName(jQ)
{
    var str = jQ.text();
    if (!str.length) {
        return;
    }
    if (str.match(/^\<.+\>$/)) { // No real name, just email address
        return;
    }
    jQ.text(str.replace(/\<.+\>/, ''));
}

function encodeForForm(name, value)
{
    return encodeURIComponent(name) + '=' + encodeURIComponent(value);
}

function sendSmsAjaxProcess(next)
{
    if (next['error']) {
        $.mobile.hidePageLoadingMsg();
        alert(next['error']);
        return;
    }
    if (next['done']) {
        $.mobile.hidePageLoadingMsg();
        // $('#page_sms_compose_go_home').trigger(ABSTRACTCLICK);
        self.location.href = $('#page_sms_compose_go_home').attr('href');
    } else {
        $.mobile.showPageLoadingMsg('b', next['statusmessage']);
        $.ajax({'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : sendSmsAjaxProcess});
    }
}

function sendEmailAjaxProcess(next)
{
    if (next['error']) {
        $.mobile.hidePageLoadingMsg();
        alert(next['error']);
        if (confirm($('#compose_email_sendform').data('notsentsave'))) {
            $('#compose_email_a_save_as_draft').trigger(ABSTRACTCLICK);
        }
        return;
    }
    if (next['done']) {
        $.mobile.hidePageLoadingMsg();
        // $('#page_email_compose_go_home').trigger(ABSTRACTCLICK);
        self.location.href = $('#page_email_compose_go_home').attr('href');
    } else {
        $.mobile.showPageLoadingMsg('b', next['statusmessage']);
        $.ajax({'url' : next['url'], 'type' : 'GET', 'dataType' : 'json', 'success' : sendEmailAjaxProcess});
    }
}

function sendEmailReceiveFiles(HDL, data)
{
    var URL = '{receive_files_url}&from_h=' + encodeURIComponent(HDL);
    $.each(data, function (i, v) {
        URL += '&item[]=' + encodeURIComponent(v);
    });
    $.ajax({url: URL, dataType: 'json', success : files_received});
}

function sendEmailFilesReceived(data)
{
    $.each(data, function (k, v) {
        sendEmailAddAttach(v['name'], v['filename'], v['icon'], 'user', v['mimetype']);
    });
}

function SendEmailAppendSig(txt, html)
{
    // Using preDOM methods here
    // Adding -- LF before the signature is the quasi standardized way to
    // denote the end of the mail and the beginning of the signature
    var $answerStyle = $('#page_email_compose').attr('data-answer-style');
    if (use_html) {
        var editor = $('#mbody').ckeditorGet();
        if ($answerStyle == 'tofu') {
            editor.setData("<br />\n--<br />\n" + html + '<br />\n<br />\n' + editor.getData());
        } else {
            editor.setData(editor.getData() + "<br />\n--<br />\n" + html);
        }
    } else {
        var mbody = document.getElementById('mbody');
        if (mbody.value != '') {
            mbody.value = $answerStyle == 'tofu'
                    ? "\n-- \n" + txt + "\n \n" + mbody.value
                    : mbody.value + "\n-- \n" + txt + "\n";
        } else {
            mbody.value = "\n-- \n" + txt + "\n";
        }
    }
}


function sendEmailAddAttach(name, filename, small_icon, mode, mimetype)
{
    var offset = $('#compose_email_attachcontainer li').length;
    if (!name || !filename || !mode) return;

    if (typeof emailAttachUniqids[filename] !== 'undefined') {
        return;
    }
    emailAttachUniqids[filename] = 1;
    $('#email_compose_attachment_tenplate')
        .clone()
        .attr('id', 'compose_email_attached_' + offset.toString())
        .css('display', 'block')
        .find('a')
            .attr({'data-filename' : filename, 'data-mimetype' : (mimetype) ? mimetype : false, 'data-src' : mode})
            .addClass('attachlink')
            .end()
        .find('a > span.name').text(name).end()
        .find('a > img.atticon').attr('src', small_icon).end()
        .appendTo('#compose_email_attachcontainer');
    window.setTimeout('sendEmailDoneAttaching();', 1000);
}

function sendEmailDoneAttaching()
{
    // Cleaning up after receiving attachment(s)
    $('#email_compose_upload_form')[0].reset();
    $.mobile.hidePageLoadingMsg();
    $.mobile.changePage('#page_email_compose', {'reverse' : true});
    $.mobile.silentScroll($('#compose_email_attachcontainer').get(0).offsetTop);
}

function sendEmailDelAttach(offset)
{
    attachlist[offset]['deleted'] = true;
    document.getElementById('att_' + offset).parentNode.removeChild(document.getElementById('att_' + offset));
    attachments_visible -= 1;
    if (1 > attachments_visible) {
        $('#sendattachcont').hide();
        show_att = 0;
    }
    adjustMyHeight();
}

$(document).on('pageinit', '#page_setup_calendar', function () {
    $(document).on('slide change', '#setup_calendar_workingtime_min, #setup_calendar_workingtime_max').unbind('slide change', function() {
        var sVal = $('#setup_calendar_workingtime_min').val();
        var eVal = $('#setup_calendar_workingtime_max').val();
        $('#wd_start').val(sVal/50);
        $('#wd_end').val(eVal/50);
        $('#setup_calendar_workingtime_human').html(slider2hrtime(sVal) + ' - ' + slider2hrtime(eVal));
    }).trigger('change');

    $(document).on('change', '#setup_calendar_workingtime_min', function() {
        var min = parseInt($(this).val());
        var max = parseInt($('#setup_calendar_workingtime_max').val());
        if (min > max) {
            $(this).val(max);
            $(this).slider('refresh');
        }
    });

    $(document).on('change', '#setup_calendar_workingtime_max', function() {
        var min = parseInt($('#setup_calendar_workingtime_min').val());
        var max = parseInt($(this).val());
        if (min > max) {
            $(this).val(min);
            $(this).slider('refresh');
        }
    });

    $(document).on('change vclick', '#setup_calendar_chk_warn').unbind('change vclick', function () {
        if ($(this).is(':checked')) {
            $('#setup_calendar_warndisable').show('slow');
        } else {
            $('#setup_calendar_warndisable').hide('slow');
        }
    }).trigger('change');

    $(document).on('change', '#setup_calendar_sel_warnmail, #setup_calendar_sel_warnsms').unbind('change', function () {
        var ID = '#' + this.id.replace(/^setup_calendar_sel/, 'setup_calendar_inp');
        $(ID).val($(this).val());
    });
});

function store_profile_order()
{
    var orders = saveProfileOrderUri;
    var pos = 1;
    // Abweichend von Desktop-Code, da jQ mobile Extra-Markup generiert
    $('#core_accountslist_sortable li a').each(function () {
        orders += '&id[' + this.id.replace(/^prof_/, '') + ']=' + pos;
        pos++;
    });
    $.ajax({ url: orders });
}

function jumpToNextItem()
{
    if ($('#a_skim_next').length) {
        self.location.href = $('#a_skim_next').attr('href');
    } else if ($('#a_skim_prev').length) {
        self.location.href = $('#a_skim_prev').attr('href');
    } else {
        self.location.href = $('#a_skim_levelup').attr('href');
    }
}

function contactsDeleteContact(i18n)
{
    state = confirm(i18n);
    if (state === true) {
        var $masterForm = $('#masterform');
        $masterForm.attr('src', $masterForm.data('deletelink'));
        $masterForm.submit();
    }
}

$(document).on('swiperight', '#page_email_read', function (event) {
    if ($('#a_skim_next').length == 1) {
        self.location.href = $('#a_skim_next').attr('href');
    }
});
$(document).on('swipeleft', '#page_email_read', function (event) {
    if ($('#a_skim_prev').length == 1) {
        self.location.href = $('#a_skim_prev').attr('href');
    }
});
$(document).on('tap', '#page_email_read a.attachlink', function () {
    $('#attach_context ul li.sendto_link').hide();
    var hdlList = $(this).data('hdllist').split(',');
    var href = $(this).data('ref');
    var resId = $(this).data('resid');
    $.each(hdlList, function (k, v) {
        $('#page_email_read_attach_context ul li.sendto_link.handler_' + v).show().find('a').data('resid', resId);
    });
    $('#page_email_read_attach_context_save_link').attr('href', href);
});
$(document).on('tap', '#page_email_read_attach_context ul li.sendto_link', function () {
    self.location.href = $(this).attr('href') + $(this).data('resid');
});
$(document).on('tap', '#page_email_read_action_delete', function (evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();
    $.ajax({
        'url' : $(this).attr('href'),
        'success' : function (data) {
            if (data['error']) {
                alert(data['error']);
            } else {
                jumpToNextItem();
            }
        }
    });
    return false;
});

$(document).on('pageinit', '#page_email_read', function () {
    // Build compact mail header
    $('<div class="table">').appendTo('#kopfzeilen_kompakt');
    $('#kopfzeilen .hl_from').clone().appendTo('#kopfzeilen_kompakt .table');
    $('#kopfzeilen .hl_to').clone().appendTo('#kopfzeilen_kompakt .table');
    $('#kopfzeilen .hl_subject').clone().appendTo('#kopfzeilen_kompakt .table');

    fullEmail2RealName($('#kopfzeilen_kompakt .hl_from .td:nth-child(2)'));
    fullEmail2RealName($('#kopfzeilen_kompakt .hl_to .td:nth-child(2)'));

    $(document).on('tap', '#kopfzeilen, #kopfzeilen_kompakt', function (evt) {
        var Sibling = (this.id == 'kopfzeilen') ? '#kopfzeilen_kompakt' : '#kopfzeilen';
        $(this).hide();
        $(Sibling).show().trigger('updatelayout');
        evt.preventDefault();
    });

    $(document).on(ABSTRACTCLICK, '#taptoatt', function() {
        $.mobile.silentScroll($('#attachlist').get(0).offsetTop);
    });
    // Make tables automagically scroll horizontally if necessary
    $('#mailbody table').each(function() {
        $(this).wrap('<div style="overflow:auto"/>');
    });

});

$(document).on(ABSTRACTCLICK, '#page_email_read_action_context li a.mailop, #page_email_read_select_colour li a.mailop', function(evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();

    var myOp = $(this).data('op');
    $.ajax({
        'url' : $(this).attr('data-href'),
        'success' : function () {
            switch (myOp) {
                case 'mark': case 'unmark': window.location.reload(); break;
                case 'spam': case 'unspam': jumpToNextItem(); break;
                case 'colour':
                    $('#page_email_read_select_colour').dialog('close');
                    window.location.reload();
                    break;
            }
            try {
                $('#page_email_read_action_context').dialog('close');
            } catch (exception) {}
        }
    });
    return false;
});

$(document).on('change keyup', '#compose_email_sel_fromprofile', function () {
    $('#compose_email_sel_sendvcf').val($(this).find('option:selected').attr('class').replace(/^vcf_/, ''));
});

$(document).on(ABSTRACTCLICK, '#page_email_compose_addrecipient_menu li a.ohkeh',function() {
    var str = '<li>'
            + '<input type="hidden" name="WP_send[' + $(this).data('target') + '][]" value="' + $(this).data('address') + '">'
            + '<a href="#page_email_compose_editrecipient_menu" data-rel="popup" id="uniq' + (idCounter++).toString() + '" data-target="' + $(this).data('target') + '" data-address="' + $(this).data('address') + '">'
            + $(this).text() + ' ' + $(this).data('address')
            + '</a></li>';
    $('#email_compose_recipients_selector').autocomplete('clear').val('');
    $('#email_compose_recipients_container').append(str).listview('refresh');
    $('#page_email_compose_addrecipient_menu').popup('close');
});

$(document).on(ABSTRACTCLICK, '#email_compose_recipients_container li a',function () {
    $('#page_email_compose_editrecipient_menu li a').data( { 'address' : $(this).data('address'), 'id' : $(this).attr('id') } );
});

$(document).on(ABSTRACTCLICK, '#page_email_compose_editrecipient_menu li a.ohkeh',function() {
    if ($(this).data('target') == 'remove') {
        $('#' + $(this).data('id')).parentsUntil('li').remove();
    } else {
        $('#' + $(this).data('id')).attr('data-target', $(this).data('target')).text($(this).text() + ' ' + $(this).data('address'));
    }
    $('#page_email_compose_editrecipient_menu').popup('close');
});

$(document).on(ABSTRACTCLICK, '#compose_email_a_send_mail,#compose_email_a_save_as_draft,#compose_email_a_save_as_template',function (evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();
    sendEmailIsDraft = $(this).data('is-draft');
    $('#compose_email_sendform').trigger('submit.sendemail');
    return false;
});

$(document).on('submit', '#email_compose_upload_form',function () {
    $.mobile.showPageLoadingMsg();
});

$(document).on('submit', '#compose_email_sendform',function (evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();
    return false;
});

$(document).on('submit.sendemail', '#compose_email_sendform', function () {
    var $this = $(this);

    var use_html = false; // Maybe later ...
    if (!sendEmailIsDraft) {
        if ($('#email_compose_recipients_container li').length == 0
                && $('email_compose_recipients_selector').val() == '') {
            alert($this.data('error-no-rcpt'));
            return false;
        }
        if ($('#compose_email_subject').val() == '') {
            if (!confirm($this.data('confirm-no-subject'))) {
                return false;
            }
        }
    }
    // Make sure to fetch the current text from the RichTextEditor
    if (use_html) {
        form_submitted = 1;
        form['mbody'] = $('#compose_email_mbody').val();
    }

    $this.find('#compose_email_hidden_draft,#compose_email_hidden_bodytype').remove();
    if (sendEmailIsDraft == 2) {
        $this.append('<input type="hidden" id="compose_email_hidden_draft" name="template" value="1">');
    } else if (sendEmailIsDraft == 1) {
        $this.append('<input type="hidden" id="compose_email_hidden_draft" name="draft" value="1">');
    }
    if (use_html && content_type == 'text/html') {
        $this.append('<input type="hidden" id="compose_email_hidden_bodytype" name="WP_send[bodytype]" value="text/html">');
    }
    var $attachomtainer = $('#compose_email_attachcontainer');

    $('#compose_email_attachcontainer input').remove();

    $('#compose_email_attachcontainer a.attachlink').each(function(att) {
        var $this = $(this);
        if ($this.parentsUntil('li').attr('id') == 'email_compose_attachment_tenplate') {
            return true; // continue; in jQ each
        }
        $attachomtainer.append('<input type="hidden" name="WP_send[attach][' + att + '][name]" value="' + $this.find('span.name').text() + '">');
        $attachomtainer.append('<input type="hidden" name="WP_send[attach][' + att + '][filename]" value="' + $this.data('filename') + '">');
        $attachomtainer.append('<input type="hidden" name="WP_send[attach][' + att + '][mode]" value="' + $this.data('src') + '">');
        $attachomtainer.append('<input type="hidden" name="WP_send[attach][' + att + '][mimetype]" value="' + $this.data('mimetype') + '">');
        // This ensures, that attachments which got "deleted" after uploading them will be removed from the filesystem (less
        // garbage piling up in the user's storage area)
        if ($this.parent().is('.deleted')) {
            $attachomtainer.append('<input type="hidden" name="WP_send[attach][' + att + '][deleted]" value="1">');
        }
    });

    // Done ... send out
    $.mobile.showPageLoadingMsg('c', $this.data('sending-mail'));
    $.ajax({'url': $this.data('action'), 'data': $this.serializeArray(), 'type': $this.attr('method'), 'dataType' : 'json', 'success' : sendEmailAjaxProcess});

    return false;
});

$(document).on('pageinit', '#page_email_compose', function () {
    $('#email_compose_recipients_selector').autocomplete({
        target : $('#email_compose_recipients_autocomplete'),
        source : MYSELF + '?' + SID + '&h=contacts&l=apiselect&jqm=1&what=email',
        matchFromStart: false,
        mingLength: 3,
        callback: function (evt) {
            $('#page_email_compose_addrecipient_menu li a').data('address', $(evt.currentTarget).text());
            $('#page_email_compose_addrecipient_menu').popup('open');
        }
    });
});

$(document).on('change keyup', '#compose_sms_sel_fromprofile', function () {
    $('#compose_sms_sel_sendvcf').val($(this).find('option:selected').attr('class').replace(/^vcf_/, ''));
});


$(document).on(ABSTRACTCLICK, '#sms_compose_recipients_container li a',function () {
    $('#page_sms_compose_editrecipient_menu li a').data( { 'address' : $(this).data('address'), 'id' : $(this).attr('id') } );
});

$(document).on(ABSTRACTCLICK, '#page_sms_compose_editrecipient_menu li a.ohkeh', function() {
    if ($(this).data('target') == 'remove') {
        $('#' + $(this).data('id')).parentsUntil('li').remove();
    } else {
        $('#' + $(this).data('id')).attr('data-target', $(this).data('target')).text($(this).text() + ' ' + $(this).data('address'));
    }
    $('#page_sms_compose_editrecipient_menu').popup('close');
});
$(document).on('change keyup', '#sms_compose_mbody', function () {
    var curr = $(this).val().length;
    $('#compose_sms_count_chars').text(smsMaxLength - curr);
    $('#compose_sms_count_sms').text((curr < 160) ? 1 : (Math.ceil(curr / 153)));
    if (curr >= smsMaxLength) {
        $('#smstext_lengthalert').show();
    } else {
        $('#smstext_lengthalert').hide();
    }
}).trigger('change');

$(document).on('submit', '#compose_sms_sendform', function (evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();
    var error = false;
    if ($('#sms_compose_recipients_container').is(':empty')) {
        alert($('#compose_sms_sendform').data('error-norcpt'));
        error = true;
    }
    if (!error && $('#sms_compose_mbody').val().length == 0) {
        var state = confirm($('#compose_sms_sendform').data('error-notxt'));
        if (!state) error = true;
    }
    if (!error && $('#sms_compose_mbody').val().length > smsMaxLength) {
        var state = confirm($('#compose_sms_sendform').data('error-toolong'));
        if (!state) error = true;
    }

    if (error) {
        return false;
    }

    $('#compose_sms_hidden_to').remove();
    var i = 0;
    var hiddenTo = '';
    $('#sms_compose_recipients_container li a').each(function () {
        if (i != 0) hiddenTo += ',';
        hiddenTo += $(this).data('address');
        i++;
    });
    if ($('#sms_compose_recipients_selector').val().length > 0) {
        if (hiddenTo.length > 0) hiddenTo += ',';
        hiddenTo += $('#sms_compose_recipients_selector').val();
    }

    $('#compose_sms_sendform').append('<input type="hidden" name="to" value="' + hiddenTo + '" id="compose_sms_hidden_to">');
    $.mobile.showPageLoadingMsg('b', '{msg_sendmail}');
    $.ajax({'url': $('#compose_sms_sendform').attr('action'),
        'data': $('#compose_sms_sendform').serialize(),
        'type':  $('#compose_sms_sendform').attr('method'),
        'dataType' : 'json',
        'success' : sendSmsAjaxProcess
    });
    return false;
});

$(document).on('pageinit', '#page_sms_compose', function () {

    smsMaxLength = $('#compose_sms_sendform').data('smsmaxlen');

    $('#sms_compose_recipients_selector').autocomplete({
        target : $('#sms_compose_recipients_autocomplete'),
        source : MYSELF + '?' + SID + '&h=contacts&l=apiselect&jqm=1&what=sms',
        matchFromStart: false,
        mingLength: 3,
        callback: function (e) {
            var str = '<li>'
                + '<a href="#page_sms_compose_editrecipient_menu" id="uniq' + (idCounter++).toString() + '" data-target="' + $(this).data('target') + '" data-address="' + $(e.currentTarget).text() + '">'
                + $('#sms_compose_recipients_selector').attr('placeholder') + ' ' + $(e.currentTarget).text()
                + '</a></li>';
            $('#sms_compose_recipients_selector').autocomplete('clear').val('');
            $('#sms_compose_recipients_container').append(str).listview('refresh');
        }
    });
});