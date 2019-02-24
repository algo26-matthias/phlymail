<script type="text/javascript" src="{frontend_path}/js/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="{frontend_path}/js/ckeditor/adapters/jquery.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
aliasaction = false;
aliasid = false;
open_mask = false;
sigid = false;
sigaction = false;
sigcache = [];
currprofile = 0;
acctype = false;
EffectiveUID = {effective_uid};
themeBaseColour = '#eeeeee';
_editor_url  = '{frontend_path}/js/ckeditor/'
_editor_lang = '{user_lang}';
ssl_available = 0<!-- START ssl_available --> + 1<!-- END ssl_available -->;

function confirm_delete() { return confirm('{kill_request}'); }

function copyf(from, to) { document.getElementsByName(to)[0].value = document.getElementsByName(from)[0].value; }

function AJAX_call(url, method, data)
{
    if (!method || method == 'get') method = 'GET';
    if (method == 'post') method = 'POST';
    var payload = {'url': url, 'type': method, 'success': AJAX_process};
    if (method == 'POST' && data) {
        payload.data = data;
    }
    $.ajax(payload);
}

function AJAX_process(next)
{
    if (next['alias']) build_aliaslist(next['alias']);
    if (next['uhead']) build_uheadlist(next['uhead']);
    if (next['signatures']) build_siglist(next['signatures']);
    if (next['signature']) {
        sigcache[sigid]['signature'] = next['signature'];
        sigcache[sigid]['signature_html'] = next['signature_html'];
        while (next['signature'].match(/\n/)) {
            next['signature'] = next['signature'].replace(/\n/, '<br />');
        }
        fill_sigpreview(next['signature']);
    }
    if (next['profsaved']) window.location.reload();
    if (next['profile']) fillfields(next['profile']);
}

function build_aliaslist(data)
{
    cnt = document.getElementById('aliascontainer');
    // Throw away any already existant childs
    $(cnt).empty();

    for (var i in data) {
        showname = data[i]['email'];
        if (data[i]['real_name']) showname = data[i]['real_name'] + ' <' + showname +'>';
        aline = document.createElement('div');
        aline.className = 'menuline';
        edlnk = document.createElement('a');
        edlnk.href = 'javascript:open_editalias(' + data[i]['aid'] + ', "' + data[i]['email'] + '", "' + data[i]['real_name'].replace(/\"/g, '&quot;') + '", "' + data[i]['signature'] + '", "' + data[i]['sendvcf'] + '")';
        edlnk.style.textDecoration = 'none';
        img = document.createElement('img');
        img.src = '{confpath}/icons/edit_menu.gif';
        img.alt = '';
        img.title = '[E]';
        edlnk.appendChild(img);
        aline.appendChild(edlnk);
        aline.appendChild(document.createTextNode(' '));
        delnk = document.createElement('a');
        delnk.href = 'javascript:deletealias(' + data[i]['aid'] + ');';
        delnk.style.textDecoration = 'none';
        img = document.createElement('img');
        img.src = '{confpath}/icons/dustbin_menu.gif';
        img.alt = '';
        img.title = '[X]';
        delnk.appendChild(img);
        aline.appendChild(delnk);
        aline.appendChild(document.createTextNode(' '));
        aline.appendChild(document.createTextNode(showname));
        cnt.appendChild(aline);
    }
}

function open_editalias(id, email, realname, sig, vcf)
{
    aliasaction = 'add';
    aliasid = false;
    if (id) {
        aliasaction = 'edit';
        aliasid = id;
    }

    var html = '<option value="">{msg_sig_default}</option><option value="0">{msg_sig_none}</option>';
    $.each(sigcache, function(i, val) {
        if (!val || !val['title']) {
            return;
        }
        html += '<option value="' + i + '">' + val['title'] + '</option>';
    });
    $('#aliassignature').html(html);
    if (sig || sig == 0) $('#aliassignature').val(sig);

    $('#aliasrealname').val(realname ? realname.replace(/&quot;/g, '"') : '');
    $('#aliasemail').val(email ? email : '');
    if (vcf) $('#aliassendvcf').val(vcf);
    $('#editalias').show();
}

function react_editalias()
{
    if ('add' == aliasaction) { addalias(); } else { editalias(aliasid); }
}

function cancel_alias()
{
    $('#editalias').hide();
}

function addalias()
{
    var email = $('#aliasemail').val();
    if (!email) {
        alert('{e_enteremail}');
        return;
    }
    var realname = $('#aliasrealname').val();
    var sig = $('#aliassignature').val();
    var vcf = $('#aliassendvcf').val();
    url = '{addaliaslink}&id=' + currprofile + '&email=' + encodeURIComponent(email) + '&real_name=' + encodeURIComponent(realname);
    url += '&signature=' + encodeURIComponent(sig) + '&sendvcf=' + encodeURIComponent(vcf);
    cancel_alias();
    AJAX_call(url, 'get');
}

function editalias(id)
{
    var email = $('#aliasemail').val();
    if (!email) {
        alert('{e_enteremail}');
        return;
    }
    var realname = $('#aliasrealname').val();
    var sig = $('#aliassignature').val();
    var vcf = $('#aliassendvcf').val();
    url = '{editaliaslink}&id=' + currprofile + '&aid=' + id + '&email=' + encodeURIComponent(email) + '&real_name=' + encodeURIComponent(realname);
    url += '&signature=' + encodeURIComponent(sig) + '&sendvcf=' + encodeURIComponent(vcf);
    cancel_alias();
    AJAX_call(url, 'get');
}

function deletealias(id)
{
    if (confirm('{msg_reallydropalias}')) {
        AJAX_call('{dropaliaslink}&id=' + currprofile + '&aid=' + id, 'get');
    }
}

function build_uheadlist(data)
{
    cnt = document.getElementById('uheadcontainer');
    // Throw away any already existant childs
    $(cnt).empty();

    for (var i in data) {
        showname = data[i]['hkey'] + ': ' + data[i]['hval'];
        aline = document.createElement('div');
        aline.className = 'menuline';
        edlnk = document.createElement('a');
        edlnk.href = 'javascript:open_edituhead("' + data[i]['hkey'] + '", "' + data[i]['hkey'] + '", "' + data[i]['hval'].replace(/\"/g, '&quot;') + '")';
        edlnk.style.textDecoration = 'none';

        img = document.createElement('img');
        img.src = '{confpath}/icons/edit_menu.gif';
        img.alt = '';
        img.title = '[E]';
        edlnk.appendChild(img);
        aline.appendChild(edlnk);

        aline.appendChild(document.createTextNode(' '));

        delnk = document.createElement('a');
        delnk.href = 'javascript:deleteuhead("' + data[i]['hkey'] + '");';
        delnk.style.textDecoration = 'none';

        img = document.createElement('img');
        img.src = '{confpath}/icons/dustbin_menu.gif';
        img.alt = '';
        img.title = '[X]';
        delnk.appendChild(img);
        aline.appendChild(delnk);
        aline.appendChild(document.createTextNode(' ' + showname));

        cnt.appendChild(aline);
    }
}

function open_edituhead(id, hkey, hval)
{
    uheadaction = 'add';
    uheadid = false;
    if (id) {
        uheadaction = 'edit';
        uheadid = id;
    }
    document.getElementById('uheadhval').value = (hval) ? hval.replace(/&quot;/g, '"') : '';
    document.getElementById('uheadhkey').value = (hkey) ? hkey : '';
    $('#edituhead').show();
}

function react_edituhead()
{
    if ('add' == uheadaction) { adduhead(); } else { edituhead(uheadid); }
}

function cancel_uhead()
{
    $('#edituhead').hide();
}

function adduhead()
{
    hkey = document.getElementById('uheadhkey').value;
    hval = document.getElementById('uheadhval').value;
    if (!hkey) {
        alert('{e_enterhkey}');
    } else {
        url = '{adduheadlink}&id=' + currprofile + '&hkey=' + encodeURIComponent(hkey) + '&hval=' + encodeURIComponent(hval);
        cancel_uhead();
        AJAX_call(url, 'get');
    }
}

function edituhead(id)
{
    hkey = document.getElementById('uheadhkey').value;
    hval = document.getElementById('uheadhval').value;
    if (!hkey) {
        alert('{e_enterhkey}');
    } else {
        url = '{edituheadlink}&id=' + currprofile + '&ohkey=' + encodeURIComponent(id) + '&hkey=' + encodeURIComponent(hkey) + '&hval=' + encodeURIComponent(hval);
        cancel_uhead();
        AJAX_call(url, 'get');
    }
}

function deleteuhead(id)
{
    if (confirm('{msg_reallydropuhead}')) {
        AJAX_call('{dropuheadlink}&id=' + currprofile + '&hkey=' + encodeURIComponent(id), 'get');
    }
}

function build_siglist(data)
{
    var cnt = document.getElementById('sigcontain');
    // Throw away any already existant childs
    while (cnt.childNodes.length) cnt.removeChild(cnt.firstChild);
    for (var i in data) {
        sigcache[data[i]['id']] = {'title' : data[i]['title'], 'signature' : '', 'signature_html' : ''};
        var div = document.createElement('div');
        div.style.cursor = 'pointer';
        div.onclick = switchsig;
        div.id = 'sigsel_' + data[i]['id'];
        if ((data[i]['id'] == sigid)) {
            div.className = 'menuline marked';
            AJAX_call('{getsignatureurl}&id=' + sigid, 'get');
        } else {
            div.className = 'menuline';
        }
        div.appendChild(document.createTextNode(data[i]['title']));
        cnt.appendChild(div);
    }
}

function fill_sigpreview(data)
{
    document.getElementById('sigpreview').innerHTML = data;
}

function open_editsig(is_edit)
{
    $('#sigvaltabs').tabs();
    document.getElementById('editsig').style.display = 'block';
    if (is_edit) {
        sigaction = 'edit';
        document.getElementById('ta_sigval_text').value = sigcache[sigid]['signature'];
        document.getElementById('ta_sigval_html').value = sigcache[sigid]['signature_html'];
        document.getElementById('sigtitle').value = sigcache[sigid]['title'];
    } else {
        sigaction = 'add';
        document.getElementById('ta_sigval_text').value = '';
        document.getElementById('ta_sigval_html').value = '';
        document.getElementById('sigtitle').value = '';
    }
    $('#ta_sigval_html').ckeditor(function() { /* callback code */ },{ baseHref : _editor_url, language : _editor_lang, uiColor : themeBaseColour, toolbarStartupExpanded : true, toolbar : 'Basic', height: 210} );
}

function react_editsig()
{
    if ('add' == sigaction) { addsig(); } else { editsig(sigid); }
}

function cancel_sig()
{
    $('#sigvaltabs').tabs('destroy');
    $('#ta_sigval_html').ckeditorGet().destroy();
    document.getElementById('editsig').style.display = 'none';
}

function addsig()
{
    var sigval = encodeURIComponent(document.getElementById('ta_sigval_text').value);
    var sigvalhtml = encodeURIComponent($('#ta_sigval_html').val());
    var sigttl = encodeURIComponent(document.getElementById('sigtitle').value);
    if (sigval.length || sigvalhtml.length) {
        AJAX_call('{addsignaturelink}&signature=' + sigval + '&signature_html=' + sigvalhtml + '&title=' + sigttl, 'get');
    }
    cancel_sig();
}

function editsig(id)
{
    var sigval = encodeURIComponent(document.getElementById('ta_sigval_text').value);
    var sigvalhtml = encodeURIComponent($('#ta_sigval_html').val());
    var sigttl = encodeURIComponent(document.getElementById('sigtitle').value);
    if (sigval.length || sigvalhtml.length) {
        AJAX_call('{editsignaturelink}&id=' + id + '&signature=' + sigval + '&signature_html=' + sigvalhtml + '&title=' + sigttl, 'get');
    }
    cancel_sig();
}

function dele_signature()
{
    if (confirm('{q_reallydelesig}')) {
        AJAX_call('{dropsignaturelink}&id=' + encodeURIComponent(sigid), 'get');
    }
}

function switchsig(id)
{
    $('#sigsel_' + sigid).removeClass('marked');
    sigid = this.id.replace(/^sigsel_/, '');
    $('#sigsel_' + sigid).addClass('marked');
    AJAX_call('{getsignatureurl}&id=' + sigid, 'get');
}

function loadprofile(id)
{
    $('#core_accountslist_sortable li').removeClass('marked');
    currprofile = id;
    AJAX_call('{editlink}' + id, 'get');
}

function fillfields(data)
{
    $('#tabpane').tabs('destroy').tabs().tabs('select', 0);
    // Mark current profile in list
    $('#prof_' + currprofile).addClass('marked');
    emptyfields();
    sigid = data['signature'];
    form = document.forms.mainform;
    form.popname.value = data['profilename'];
    form.address.value = data['address'];
    form.real_name.value = data['real_name'];
    form.sent_objects.selectedIndex = 0;
    for (var i = 0; i < form.sent_objects.options.length; ++i) {
        if (form.sent_objects.options[i].value == data['sent_objects']) {
            form.sent_objects.selectedIndex = i;
            break;
        }
    }
    form.sendvcf.selectedIndex = 0;
    for (var i = 0; i < form.sendvcf.options.length; ++i) {
        if (form.sendvcf.options[i].value == data['sendvcf']) {
            form.sendvcf.selectedIndex = i;
            break;
        }
    }
    form.junk.selectedIndex = 0;
    for (var i = 0; i < form.junk.options.length; ++i) {
        if (form.junk.options[i].value == data['junk']) {
            form.junk.selectedIndex = i;
            break;
        }
    }
    form.waste.selectedIndex = 0;
    for (var i = 0; i < form.waste.options.length; ++i) {
        if (form.waste.options[i].value == data['waste']) {
            form.waste.selectedIndex = i;
            break;
        }
    }
    form.drafts.selectedIndex = 0;
    for (var i = 0; i < form.drafts.options.length; ++i) {
        if (form.drafts.options[i].value == data['drafts']) {
            form.drafts.selectedIndex = i;
            break;
        }
    }
    form.templates.selectedIndex = 0;
    for (var i = 0; i < form.templates.options.length; ++i) {
        if (form.templates.options[i].value == data['templates']) {
            form.templates.selectedIndex = i;
            break;
        }
    }
    form.archive.selectedIndex = 0;
    for (var i = 0; i < form.archive.options.length; ++i) {
        if (form.archive.options[i].value == data['archive']) {
            form.archive.selectedIndex = i;
            break;
        }
    }
    $('#convert_to_imap, #convert_to_pop3').hide();
    if (data['acctype'] == 'imap') {
        acctype = 'imap';
        form.imapserver.value = data['popserver'];
        form.imapport.value = data['popport'];
        form.imapuser.value = data['popuser'];
        form.imappass.value = data['poppass'];
        /* form.cachetype.selectedIndex = (data['cachetype'] == 'struct') ? 0 : 1;
        try {
            document.getElementById('imapcache').style.display = 'table-row';
        } catch (e) {
            document.getElementById('imapcache').style.display = 'inline';
        }*/
        $('#imapsec_selector').val(data['popsec']);
        form.onlysubscribed.checked = (data['onlysubscribed'] == 1) ? true : false;
        form.imapallowselfsigned.checked = (data['popallowselfsigned'] == 1) ? true : false;
        form.imapprefix.value = data['imapprefix'];
        $('#tabpane').tabs('disable', 1).tabs('enable', 2);
        $('#convert_to_pop3').show();
    } else {
        acctype = 'pop3';
        form.popserver.value = data['popserver'];
        form.popport.value = data['popport'];
        form.popuser.value = data['popuser'];
        form.poppass.value = data['poppass'];
        $('#popsec_selector').val(data['popsec']);
        form.inbox.selectedIndex = 0;
        for (var i = 0; i < form.inbox.options.length; ++i) {
            if (form.inbox.options[i].value == data['inbox']) {
                form.inbox.selectedIndex = i;
                break;
            }
        }
        document.getElementById('pop3_leaveonserver').style.display = 'block';
        form.leaveonserver.checked = (data['leaveonserver'] == 1) ? true : false;
        form.localkillserver.checked = (data['localkillserver'] == 1) ? true : false;
        form.popallowselfsigned.checked = (data['popallowselfsigned'] == 1) ? true : false;
        try {
            document.getElementById('pop3_inboxline').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_inboxline').style.display = 'inline';
        }
        $('#tabpane').tabs('disable', 2).tabs('enable', 1);
        $('#convert_to_imap').show();
    }
    form.smtp_host.value = data['smtp_host'];
    form.smtp_port.value = data['smtp_port'];
    form.smtp_user.value = data['smtp_user'];
    form.smtp_pass.value = data['smtp_pass'];
    $('#smtpsec_selector').val(data['smtpsec']);
    $('#checkevery').val(data['checkevery']);
    form.checkspam.checked = (data['checkspam'] == 1) ? true : false;
    form.trustspamfilter.checked = (data['trustspamfilter'] == 1) ? true : false;
    form.sig_on.checked = (data['sig_on'] == 1) ? true : false;
    form.smtpallowselfsigned.checked = (data['smtpallowselfsigned'] == 1) ? true : false;
    document.getElementById('addalias').style.display = 'inline';
    document.getElementById('adduhead').style.display = 'inline';
    document.getElementById('delebutton').style.display = 'inline';
    AJAX_call('{getaliasesurl}&id=' + currprofile, 'get');
    AJAX_call('{getuheadsurl}&id=' + currprofile, 'get');
    AJAX_call('{getsignaturesurl}', 'get');
    document.getElementById('navcont').style.display = 'block';
}

function emptyfields()
{
    form = document.forms.mainform;
    form.popname.value = '';
    form.address.value = '';
    form.real_name.value = '';
    form.popserver.value = '';
    form.popport.value = '110';
    form.popuser.value = '';
    form.poppass.value = '';
    form.imapserver.value = '';
    form.imapport.value = '143';
    form.imapuser.value = '';
    form.imappass.value = '';
    form.smtp_host.value = '';
    form.smtp_port.value = '587';
    form.smtp_user.value = '';
    form.smtp_pass.value = '';
    form.sendvcf.selectedIndex = 0;
    form.inbox.selectedIndex = 0;
    form.sent_objects.selectedIndex = 0;
    form.junk.selectedIndex = 0;
    form.waste.selectedIndex = 0;
    form.drafts.selectedIndex = 0;
    form.templates.selectedIndex = 0;
    form.archive.selectedIndex = 0;
    $('#checkevery').val('15');
    $('#popsec_selector').val('SSL');
    $('#imapsec_selector').val('SSL');
    $('#smtpsec_selector').val('SSL');
    form.checkspam.checked = false;
    form.trustspamfilter.checked = true;
    form.leaveonserver.checked = true;
    form.localkillserver.checked = true;
    form.onlysubscribed.checked = true;
    form.sig_on.checked = false;
    form.imapallowselfsigned.checked = false;
    form.popallowselfsigned.checked = false;
    form.smtpallowselfsigned.checked = false;
    form.imapprefix.value = '';

    $('#pop3_inboxline,#pop3_leaveonserver,#addalias,#adduhead').hide();
    // Throw away any already existant childs
    $('#aliascontainer,#uheadcontainer,#sigcontain,#sigpreview').empty();
    $('#tabpane').tabs('select', 0);
}

function addprofile(acc_type)
{
    $('#tabpane').tabs('destroy').tabs().tabs('select', 0);

    emptyfields();
    currprofile = 0;
    sigid = false;
    AJAX_call('{getsignaturesurl}', 'get');
    if (acc_type == 'imap') {
        acctype = 'imap';/*
        try {
            document.getElementById('imapcache').style.display = 'table-row';
        } catch (e) {
            document.getElementById('imapcache').style.display = 'inline';
        }*/
        $('#tabpane').tabs('disable', 1).tabs('enable', 2);
    } else {
        acctype = 'pop3';
        document.getElementById('pop3_leaveonserver').style.display = 'block';
        try {
            document.getElementById('pop3_inboxline').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_inboxline').style.display = 'inline';
        }
        $('#tabpane').tabs('disable', 2).tabs('enable', 1);
    }
    document.getElementById('delebutton').style.display = 'none';
    document.getElementById('navcont').style.display = 'block';
}

function saveprofile()
{
    var error = false;
    form = document.forms.mainform;
    if (ssl_available == 0) {
        if (form.smtp_host.value.substr(0, 6) == 'ssl://') {
            error = true;
            alert('{msg_nossl_smtp}');
        }
        if (form.popserver.value.substr(0, 6) == 'ssl://') {
            if (error == false) alert('{msg_nossl_pop3}');
            error = true;
        }
    }

    if (error != false) return;
    data = 'acctype=' + encodeURIComponent(acctype)
            + '&popname=' + encodeURIComponent(form.popname.value)
            + '&address=' + encodeURIComponent(form.address.value)
            + '&real_name=' + encodeURIComponent(form.real_name.value)
            + '&smtp_host=' + encodeURIComponent(form.smtp_host.value)
            + '&smtp_port=' + encodeURIComponent(form.smtp_port.value)
            + '&smtp_user=' + encodeURIComponent(form.smtp_user.value)
            + '&smtp_pass=' + encodeURIComponent(form.smtp_pass.value)
            + '&smtpsec=' + encodeURIComponent($('#smtpsec_selector').val())
            + '&smtpallowselfsigned=' + ((form.smtpallowselfsigned.checked) ? 1 : 0)
            + '&checkevery=' + encodeURIComponent($('#checkevery').val())
            + '&checkspam=' + ((form.checkspam.checked) ? 1 : 0)
            + '&trustspamfilter=' + ((form.trustspamfilter.checked) ? 1 : 0)
            + '&sig_on=' + ((form.sig_on.checked) ? 1 : 0)
            + '&signature=' + sigid
            + '&sendvcf=' + encodeURIComponent(form.sendvcf.options[form.sendvcf.selectedIndex].value)
            + '&sent_objects=' + encodeURIComponent(form.sent_objects.options[form.sent_objects.selectedIndex].value)
            + '&junk=' + encodeURIComponent(form.junk.options[form.junk.selectedIndex].value)
            + '&waste=' + encodeURIComponent(form.waste.options[form.waste.selectedIndex].value)
            + '&drafts=' + encodeURIComponent(form.drafts.options[form.drafts.selectedIndex].value)
            + '&templates=' + encodeURIComponent(form.templates.options[form.templates.selectedIndex].value)
            + '&archive=' + encodeURIComponent(form.archive.options[form.archive.selectedIndex].value);
    if (acctype == 'imap') {
        data += '&popserver=' + encodeURIComponent(form.imapserver.value)
                + '&popport=' + encodeURIComponent(form.imapport.value)
                + '&popuser=' + encodeURIComponent(form.imapuser.value)
                + '&poppass=' + encodeURIComponent(form.imappass.value)
                + '&popsec=' + encodeURIComponent($('#imapsec_selector').val())
                + '&popallowselfsigned=' + ((form.imapallowselfsigned.checked) ? 1 : 0)
                // + '&cachetype=' + encodeURIComponent(form.cachetype.options[form.cachetype.selectedIndex].value)
                + '&onlysubscribed=' + ((form.onlysubscribed.checked) ? 1 : 0)
                + '&imapprefix=' + encodeURIComponent(form.imapprefix.value);
    } else {
        data += '&popserver=' + encodeURIComponent(form.popserver.value)
                + '&popport=' + encodeURIComponent(form.popport.value)
                + '&popuser=' + encodeURIComponent(form.popuser.value)
                + '&poppass=' + encodeURIComponent(form.poppass.value)
                + '&popsec=' + encodeURIComponent($('#popsec_selector').val())
                + '&popallowselfsigned=' + ((form.popallowselfsigned.checked) ? 1 : 0)
                + '&leaveonserver=' + ((form.leaveonserver.checked) ? 1 : 0)
                + '&localkillserver=' + ((form.localkillserver.checked) ? 1 : 0)
                + '&inbox=' + encodeURIComponent(form.inbox.options[form.inbox.selectedIndex].value);
    }
    AJAX_call('{savelink}' + ((currprofile != 0) ? 'saveold&account=' + currprofile : 'savenew'), 'post', data);
}

function deleprofile()
{
    if (!currprofile) return;
    if (confirm('{kill_request}')) {
        AJAX_call('{delelink}' + currprofile, 'get');
    }
}

function store_profile_order()
{
    var orders = '{saveordersurl}';
    var pos = 1;
    $('#core_accountslist_sortable li').each(function () {
        orders += '&id[' + this.id.replace(/^prof_/, '') + ']=' + pos;
        pos++;
    });
    $.ajax({ url: orders });
}

$(document).ready(function() {
    $('#core_accountslist_sortable').sortable({stop: store_profile_order }).disableSelection();
    $('#convert_to_imap, #convert_to_pop3').click(function () {
        $.ajax({ 'url' : '{savelink}' + 'converttype&account=' + currprofile, success : function() {
            loadprofile(currprofile);
        }});
    });
});
/*]]>*/
</script>

<div style="clear:both;height:260px;padding:4px;">
    <div class="sendmenubut sendmenuborder" style="width:200px;height:246px;overflow:auto;float:left;margin:0 4px 0 0;">
        <ul id="core_accountslist_sortable"><!-- START menline -->
            <li id="prof_{id}" style="cursor:pointer;" onclick="loadprofile({id});" title="{profilenm}">
                <span class="ui-icon"></span><!-- START acctype_pop3 -->
                <img class="protocol_icon" src="{confpath}/icons/proto_pop3.gif" alt="" title="POP3" /><!-- END acctype_pop3 --><!-- START acctype_imap -->
                <img class="protocol_icon" src="{confpath}/icons/proto_imap.gif" alt="" title="IMAP" /><!-- END acctype_imap -->
                {profilenm}
            </li><!-- END menline -->
        </ul>
    </div>
    <div style="float:left;">
        <form name="mainform" onsubmit="saveprofile();" action="#" method="get">
            <div id="navcont" style="display:none;text-align:left;width:530px;height:246px;">
                <div id="tabpane" class="ui-tabpane" style="height:226px;">
                    <ul>
                        <li><a href="#generic"><span>{msg_generic}</span></a></li>
                        <li><a href="#pop3fields"><span>POP3</span></a></li>
                        <li><a href="#imapfields"><span>IMAP</span></a></li>
                        <li><a href="#smtpfields"><span>SMTP</span></a></li>
                        <li><a href="#folderfields"><span>{msg_folders}</span></a></li>
                        <li><a href="#aliasfields"><span>{msg_aliases}</span></a></li>
                        <li><a href="#uheadfields"><span>{msg_uhead}</span></a></li>
                        <li><a href="#advancedfields"><span>{msg_various}</span></a></li>
                    </ul>
                    <div id="generic">
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td class="l"><strong>{msg_profile}:</strong></td>
                                    <td class="l"><input type="text" name="popname" size="48" value="" maxlength="64" /></td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_email}:</td>
                                    <td class="l"><input type="text" name="address" size="48" value="" maxlength="64" /></td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_realname}:</td>
                                    <td class="l"><input type="text" name="real_name" size="48" value="" maxlength="64" /></td>
                                </tr><!-- START infuture -->
                                <tr id="imapcache" style="display:none">
                                    <td class="l">{msg_cachetype}:</td>
                                    <td class="l">
                                        <select size="1" name="cachetype"><!-- START cacheline -->
                                            <option value="{id}">{name}</option><!-- END cacheline -->
                                        </select>
                                    </td>
                                </tr><!-- END infuture -->
                                <tr>
                                    <td class="l">{msg_sendvcf}:</td>
                                    <td class="l">
                                        <select size="1" name="sendvcf">
                                            <option value="none">{msg_vcf_none}</option>
                                            <option value="priv">{msg_vcf_priv}</option>
                                            <option value="busi">{msg_vcf_busi}</option>
                                            <option value="all">{msg_vcf_all}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="l">
                                        <input type="checkbox" id="lbl_chkspam" name="checkspam" value="1" />
                                        <label for="lbl_chkspam">{msg_checkspam}</label><br />
                                        <input type="checkbox" id="lbl_trustspamfilter" name="trustspamfilter" value="1" />
                                        <label for="lbl_trustspamfilter">%h%TrustSpamFilter%</label><br />
                                        <div id="pop3_leaveonserver">
                                            <input type="checkbox" id="lbl_leave" name="leaveonserver" value="1" />
                                            <label for="lbl_leave">{msg_leaveonserver}</label>
                                            <div id="pop3_localkillserver" style="padding-left:12px">
                                                <input type="checkbox" id="lbl_localkill" name="localkillserver" value="1" />
                                                <label for="lbl_localkill">{msg_localkillserver}</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <button type="button" id="convert_to_imap">{msg_convert_to_imap}</button>
                                        <button type="button" id="convert_to_pop3">{msg_convert_to_pop3}</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pop3fields">
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td class="l">{msg_popserver}:</td>
                                    <td class="l">
                                        <input type="text" name="popserver" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('popserver', 'smtp_host')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popport}:</td>
                                    <td class="l">
                                        <input type="text" name="popport" size="8" value="" maxlength="8" />
                                        110 / 995 (SSL)
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popuser}:</td>
                                    <td class="l">
                                        <input type="text" name="popuser" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('popuser', 'smtp_user')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_poppass}:</td>
                                    <td class="l">
                                        <input type="password" name="poppass" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('poppass', 'smtp_pass')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popsec}:</td>
                                    <td class="l">
                                        <select size="1" id="popsec_selector" name="popsec"><!-- START popsec -->
                                            <option value="{key}">{val}</option><!-- END popsec -->
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l" colspan="2">
                                        <input type="checkbox" id="lbl_popallowselfsigned" name="popallowselfsigned" value="1" />
                                        <label for="lbl_popallowselfsigned">%h%AllowSelfSignedCertificates%</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="imapfields">
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td class="l">{msg_popserver}:</td>
                                    <td class="l">
                                        <input type="text" name="imapserver" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('imapserver', 'smtp_host')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popport}:</td>
                                    <td class="l">
                                        <input type="text" name="imapport" size="8" value="" maxlength="8" />
                                        143 / 993 (SSL)
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popuser}:</td>
                                    <td class="l">
                                        <input type="text" name="imapuser" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('imapuser', 'smtp_user')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_poppass}:</td>
                                    <td class="l">
                                        <input type="password" name="imappass" size="32" value="" maxlength="64" />
                                        <button type="button" onclick="copyf('imappass', 'smtp_pass')" title="{copy_smtp}">
                                            -&gt; SMTP
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_popsec}:</td>
                                    <td class="l">
                                        <select size="1" id="imapsec_selector" name="popsec">{popsec}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l" colspan="2">
                                        <input type="checkbox" id="lbl_onlysubscribed" name="onlysubscribed" value="1" />
                                        <label for="lbl_onlysubscribed">{msg_onlysubscribed}</label><br />
                                        <div style="display:none">{msg_imapprefix}: <input type="text" name="imapprefix" value="" size="6" maxlength="250" /></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l" colspan="2">
                                        <input type="checkbox" id="lbl_imapallowselfsigned" name="imapallowselfsigned" value="1" />
                                        <label for="lbl_imapallowselfsigned">%h%AllowSelfSignedCertificates%</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="smtpfields">
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td class="l">{msg_smtphost}:</td>
                                    <td class="l">
                                        <input type="text" name="smtp_host" size="32" value="" maxlength="64" />
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_smtpport}:</td>
                                    <td class="l">
                                        <input type="text" name="smtp_port" size="8" value="" maxlength="8" />
                                        587 / 465 (SSL)
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_smtpuser}:</td>
                                    <td class="l">
                                        <input type="text" name="smtp_user" size="32" value="" maxlength="64" />
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_smtppass}:</td>
                                    <td class="l">
                                        <input type="password" name="smtp_pass" size="32" value="" maxlength="64" />
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_smtpsec}:</td>
                                    <td class="l">
                                        <select size="1" id="smtpsec_selector" name="smtpsec"><!-- START smtpsec -->
                                            <option value="{key}">{val}</option><!-- END smtpsec -->
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l" colspan="2">
                                        <input type="checkbox" id="lbl_smtpallowselfsigned" name="smtpallowselfsigned" value="1" />
                                        <label for="lbl_smtpallowselfsigned">%h%AllowSelfSignedCertificates%</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="folderfields">
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tbody>
                                <tr id="pop3_inboxline" style="display:none">
                                    <td class="l">{msg_inboxfolder}:</td>
                                    <td class="l">
                                        <select size="1" name="inbox">
                                            <option value="0">{msg_defaultfolder}</option><!-- START inboxline -->
                                            <option value="{id}">{name}</option><!-- END inboxline -->
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_sentfolder}:</td>
                                    <td class="l">
                                        <select size="1" name="sent_objects">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_wastefolder}:</td>
                                    <td class="l">
                                        <select size="1" name="waste">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_junkfolder}:</td>
                                    <td class="l">
                                        <select size="1" name="junk">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_draftsfolder}:</td>
                                    <td class="l">
                                        <select size="1" name="drafts">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_templatesfolder}:</td>
                                    <td class="l">
                                        <select size="1" name="templates">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="l">{msg_archivefolder}:</td>
                                    <td class="l">
                                        <select size="1" name="archive">
                                            <option value="0">{msg_defaultfolder}</option>{inboxline}
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="aliasfields">
                        <div class="sendmenuborder" id="aliascontainer" style="width:98%;height:160px;overflow:auto;clear:both;margin-bottom:4px;">
                        </div>
                        <button id="addalias" style="display:none" type="button" onclick="open_editalias()">{msg_addalias}</button>
                    </div>

                    <div id="uheadfields">
                        {about_uheaders}<br />
                        <br />
                        <div class="sendmenuborder" id="uheadcontainer" style="width:98%;height:120px;overflow:auto;clear:both;margin-bottom:4px;">
                        </div>
                        <button id="adduhead" style="display:none" type="button" onclick="open_edituhead()">{msg_adduhead}</button>
                    </div>

                    <div id="advancedfields">
                        <div style="margin-bottom:4px;">
                            {msg_fetchevery}
                            <select size="1" name="checkevery" id="checkevery" class="r">
                                <option value="0"{fetchevery_0}>%h%FetchManual%</option>
                                <option value="5"{fetchevery_5}>5&thinsp;min</option>
                                <option value="10"{fetchevery_10}>10&thinsp;min</option>
                                <option value="15"{fetchevery_15}>15&thinsp;min</option>
                                <option value="30"{fetchevery_30}>30&thinsp;min</option>
                                <option value="60"{fetchevery_60}>1&thinsp;h</option>
                                <option value="120"{fetchevery_120}>2&thinsp;h</option>
                                <option value="240"{fetchevery_240}>4&thinsp;h</option>
                                <option value="360"{fetchevery_360}>6&thinsp;h</option>
                                <option value="480"{fetchevery_480}>8&thinsp;h</option>
                                <option value="720"{fetchevery_720}>12&thinsp;h</option>
                                <option value="1440"{fetchevery_1440}>24&thinsp;h</option>
                            </select>
                        </div>
                        <input type="checkbox" id="lbl_sigon" name="sig_on" value="1" />
                        <label for="lbl_sigon"><strong>{msg_sigon}</strong></label><br />
                        <div style="float:right;width:20px;padding:4px;text-align:left;vertical-align:top;height:140px;">
                            <img onclick="open_editsig(1);" style="cursor:pointer;display:block;" src="{confpath}/icons/edit_menu.gif" alt="" title="{msg_editsig}" /><br />
                            <img onclick="dele_signature();" style="cursor:pointer;display:block;" src="{confpath}/icons/dustbin_menu.gif" alt="" title="{msg_delesig}" /><br />
                            <img onclick="open_editsig();" style="cursor:pointer;display:block;" src="{confpath}/icons/add_menu.gif" alt="" title="{msg_addsig}" /><br />
                        </div>
                        <div class="sendmenuborder" id="sigpreview" style="float:right;width:265px;height:140px;overflow:auto;"></div>
                        <div class="sendmenuborder" id="sigcontain" style="width:200px;height:140px;overflow:auto;">
                        </div>
                    </div>
                </div>
                <div style="text-align:right;margin-top:2px;">
                    <button type="button" class="error" style="display:none;" id="delebutton" onclick="deleprofile()">{msg_dele}</button>
                    <button type="button" class="ok" onclick="saveprofile()">{msg_save}</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="l">
    {msg_addacct}:
    <button type="button" onclick="addprofile('pop3');">POP3</button>&nbsp;
    <button type="button" onclick="addprofile('imap');">IMAP</button>
</div>

<div id="editalias" class="sendmenubut shadowed" style="display:none;width:285px;height:115px;z-index:100;position:absolute;left:50px;top:100px;">
    <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="l">{msg_realname}:</td>
                <td class="l"><input type="text" name="alRN" tabindex="2001" id="aliasrealname" size="20" maxlength="32" /></td>
            </tr>
            <tr>
                <td class="l">{msg_email}:</td>
                <td class="l"><input type="text" name="alEM" tabindex="2002" id="aliasemail" size="20" maxlength="255" /></td>
            </tr>
            <tr>
                <td class="l">{msg_signature}:</td>
                <td class="l">
                    <select size="1" name="alSI" id="aliassignature" tabindex="2003">
                        <option value="">{msg_sig_default}</option>
                        <option value="0">{msg_sig_none}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="l">{msg_sendvcf}:</td>
                <td class="l">
                    <select size="1" name="alVI" id="aliassendvcf" tabindex="2004">
                        <option value="default">{msg_vcf_default}</option>
                        <option value="none">{msg_vcf_none}</option>
                        <option value="priv">{msg_vcf_priv}</option>
                        <option value="busi">{msg_vcf_busi}</option>
                        <option value="all">{msg_vcf_all}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="l"><button type="button" class="error" onclick="cancel_alias();" tabindex="2006">{msg_cancel}</button></td>
                <td class="r"><button type="button" class="ok" onclick="react_editalias();" tabindex="2005">{msg_save}</button></td>
            </tr>
        </table>
    </form>
</div>

<div id="edituhead" class="sendmenubut shadowed" style="display:none;width:285px;height:75px;z-index:100;position:absolute;left:50px;top:100px;">
    <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="l">{msg_hkey}:</td>
                <td class="l"><input type="text" name="uheadK" id="uheadhkey" size="20" maxlength="32" tabindex="3001" /></td>
            </tr>
            <tr>
                <td class="l">{msg_hval}:</td>
                <td class="l"><input type="text" name="uheadV" id="uheadhval" size="20" maxlength="255" tabindex="3002" /></td>
            </tr>
            <tr>
                <td class="l"><button type="button" class="error" onclick="cancel_uhead();" tabindex="3004">{msg_cancel}</button></td>
                <td class="r"><button type="button" class="ok" onclick="react_edituhead();" tabindex="3003">{msg_save}</button></td>
            </tr>
        </table>
    </form>
</div>

<div id="editsig" class="sendmenubut shadowed" style="display:none;z-index:100;position:absolute;left:10px;top:12px;width:700px;height:355px;">
    <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
        <div style="margin:2px 0 4px 0;">
            <label for="sigtitle">{msg_sigtitle}:</label> <input type="text" name="sigtitle" id="sigtitle" size="32" maxlength="32" /><br />
        </div>
        <div id="sigvaltabs" class="ui-tabpane" style="height:298px;margin-top:4px;">
            <ul>
                <li><a href="#sigval_text_c"><span>{msg_sigval_txt}</span></a></li>
                <li><a href="#sigval_html_c"><span>{msg_sigval_html}</span></a></li>
            </ul>
            <div id="sigval_text_c" style="height:282px;">
                <textarea name="sigval_text" id="ta_sigval_text" rows="10" cols="80" style="width:690px;height:270px;"></textarea>
            </div>
            <div id="sigval_html_c" style="height:280px;">
                <textarea name="sigval_html" id="ta_sigval_html" rows="10" cols="80" style="width:690px;height:270px;"></textarea>
            </div>
        </div>
        <div id="sigeditbuttons" style="margin-top:4px;">
            <button type="button" class="ok" onclick="react_editsig();" style="float:right;">{msg_save}</button>
            <button type="button" class="error" onclick="cancel_sig();">{msg_cancel}</button>
        </div>
    </form>
</div>

<br />
<div class="l">
    <form action="{form_target}" method="post">
        <fieldset>
            <legend>{msg_defacc}</legend>
            {about_defacc}<br />
            <br />
            <strong>{msg_defacc}:</strong>&nbsp;
            <select name="def_prof" size="1">
                <option value="0">{msg_notdef}</option><!-- START profline -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END profline -->
            </select>&nbsp;
            <input type="submit" value="{msg_save}" />
        </fieldset>
    </form>
</div>