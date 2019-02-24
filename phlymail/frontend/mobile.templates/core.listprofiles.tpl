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
_editor_url  = '{frontend_path}/js/ckeditor/'
_editor_lang = '{user_lang}';


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
    if (next['profsaved']) {
        if (next['mode'] == 'savenew') {
            opener.pm_menu_additem
                    ('fetchitems'
                    ,'{theme_path}/icons/email_men.gif'
                    ,next['profname']
                    ,'emailfetch_init("user", ' + next['profsaved'] + ')'
                    ,0
                    ,0
                    ,'js'
                    );
        } else if (next['mode'] == 'saveold') {
            opener.pm_menu_replaceitem
                    ('fetchitems'
                    ,'emailfetch_init("user", ' + next['profsaved'] + ')'
                    ,'{theme_path}/icons/email_men.gif'
                    ,next['profname']
                    ,'emailfetch_init("user", ' + next['profsaved'] + ')'
                    ,0
                    ,0
                    ,'js'
                    );
        } else if (next['mode'] == 'kill') {
            opener.pm_menu_removeitem('fetchitems', 'emailfetch_init("user", ' + currprofile + ')');
        }
        opener.frames.PHM_tl.setTimeout("flist_refresh('email')", 1);
        window.location.reload();
    }
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
        img.src = '{theme_path}/icons/edit_menu.gif';
        img.alt = '';
        img.title = '[E]';
        edlnk.appendChild(img);
        aline.appendChild(edlnk);
        aline.appendChild(document.createTextNode(' '));
        delnk = document.createElement('a');
        delnk.href = 'javascript:deletealias(' + data[i]['aid'] + ');';
        delnk.style.textDecoration = 'none';
        img = document.createElement('img');
        img.src = '{theme_path}/icons/dustbin_menu.gif';
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
        img.src = '{theme_path}/icons/edit_menu.gif';
        img.alt = '';
        img.title = '[E]';
        edlnk.appendChild(img);
        aline.appendChild(edlnk);

        aline.appendChild(document.createTextNode(' '));

        delnk = document.createElement('a');
        delnk.href = 'javascript:deleteuhead("' + data[i]['hkey'] + '");';
        delnk.style.textDecoration = 'none';

        img = document.createElement('img');
        img.src = '{theme_path}/icons/dustbin_menu.gif';
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
        try {
            document.getElementById('pop3_inboxline').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_inboxline').style.display = 'inline';
        }
        try {
            document.getElementById('pop3_smtpafterpop').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_smtpafterpop').style.display = 'inline';
        }
        $('#tabpane').tabs('disable', 2).tabs('enable', 1);
        $('#convert_to_imap').show();
    }
    form.smtp_host.value = data['smtp_host'];
    form.smtp_port.value = data['smtp_port'];
    form.smtp_user.value = data['smtp_user'];
    form.smtp_pass.value = data['smtp_pass'];
    $('#smtpsec_selector').val(data['smtpsec']);
    form.checkspam.checked = (data['checkspam'] == 1) ? true : false;
    $('#smtpsec_selector').val(data['checkevery']);
    form.sig_on.checked = (data['sig_on'] == 1) ? true : false;
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
    $('#checkevery').val('SSL');
    $('#popsec_selector').val('SSL');
    $('#imapsec_selector').val('SSL');
    $('#smtpsec_selector').val('SSL');
    form.checkspam.checked = false;
    form.leaveonserver.checked = true;
    form.localkillserver.checked = true;
    form.onlysubscribed.checked = true;
    form.sig_on.checked = false;
    form.imapprefix.value = '';

    $('#pop3_inboxline,#pop3_leaveonserver,#pop3_smtpafterpop,#addalias,#adduhead').hide();
    // Throw away any already existant childs
    $('#aliascontainer,#uheadcontainer,#sigcontain,#sigpreview').empty();
}

function addprofile(acc_type)
{
    emptyfields();
    currprofile = 0;
    sigid = false;
    AJAX_call('{getsignaturesurl}', 'get');
    if (acc_type == 'imap') {
        acctype = 'imap';
    } else {
        acctype = 'pop3';
        document.getElementById('pop3_leaveonserver').style.display = 'block';
        try {
            document.getElementById('pop3_inboxline').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_inboxline').style.display = 'inline';
        }
        try {
            document.getElementById('pop3_smtpafterpop').style.display = 'table-row';
        } catch (e) {
            document.getElementById('pop3_smtpafterpop').style.display = 'inline';
        }
    }
    document.getElementById('delebutton').style.display = 'none';
    document.getElementById('navcont').style.display = 'block';
}

function saveprofile()
{
    var error = false;
    form = document.forms.mainform;
    if (profilesSSLAvaialble == 0) {
        if (form.smtp_host.value.substr(0, 6) == 'ssl://') {
            error = true;
            alert('{msg_nossl_smtp}');
        }
        if (form.popserver.value.substr(0, 6) == 'ssl://') {
            if (error == false) {
                alert($('#core_listprofiles').data((acctype == 'imap') ? 'error-nossl-imap') : 'error-nossl-pop3');
            }

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
            + '&checkevery=' + encodeURIComponent($('#checkevery').val())
            + '&checkspam=' + ((form.checkspam.checked) ? 1 : 0)
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
                // + '&cachetype=' + encodeURIComponent(form.cachetype.options[form.cachetype.selectedIndex].value)
                + '&onlysubscribed=' + ((form.onlysubscribed.checked) ? 1 : 0)
                + '&imapprefix=' + encodeURIComponent(form.imapprefix.value);
    } else {
        data += '&popserver=' + encodeURIComponent(form.popserver.value)
                + '&popport=' + encodeURIComponent(form.popport.value)
                + '&popuser=' + encodeURIComponent(form.popuser.value)
                + '&poppass=' + encodeURIComponent(form.poppass.value)
                + '&popsec=' + encodeURIComponent($('#popsec_selector').val())
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

/*]]>*/
</script>
<div data-role="page" id="core_listprofiles" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%"
        data-saveordersurl="{saveordersurl}" data-ssl-available="<!-- START ssl_available --> + 1<!-- END ssl_available -->"
        data-error-no0ssl-imap="{msg_nossl_imap}" data-error-nossl-pop3="{msg_nossl_pop3}">
    <div data-role="header" data-position="fixed">
        <h3>%h%accounts%</h3>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <ul id="core_accountslist_sortable" data-role="listview" data-inset="true" class="x32"><!-- START menline -->
            <li>
                <a href="#core_editprofile" id="prof_{id}" class="editprofile"><!-- START acctype_pop3 -->
                    <img class="ui-li-icon" src="{theme_path}/icons/proto_pop3.png" alt="" title="POP3" /><!-- END acctype_pop3 --><!-- START acctype_imap -->
                    <img class="ui-li-icon" src="{theme_path}/icons/proto_imap.png" alt="" title="IMAP" /><!-- END acctype_imap -->
                    {profilenm}
                </a>
            </li><!-- END menline -->
        </ul><!-- START may_add_profile -->
        <hr>
        <h2>{msg_addacct}</h2>
        <a data-role="button" href="#core_editprofile" class="addprofile" id="addprofile_pop">POP3</a>
        <a data-role="button" href="#core_editprofile" class="addprofile" id="addprofile_imap">IMAP</a><!-- END may_add_profile -->
        <hr>
        <h2>{msg_defacc}</h2>
        <form action="{form_target}" method="post">
            <div>{about_defacc}</div>
            <select name="def_prof" size="1">
                <option value="0">{msg_notdef}</option><!-- START profline -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END profline -->
            </select>
            <input type="submit" value="{msg_save}" />
        </form>
    </div>
</div>

<div data-role="page" id="core_editprofile" data-add-back-btn="true" data-back-btn-iconpos="notext">
    <div data-role="header" data-position="fixed">
        <h3>%h%accounts%</h3>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <form name="mainform" onsubmit="saveprofile();" action="#" method="get">
            <div id="generic">
                <h3>{msg_generic}</h3>
                <div data-role="fieldcontain">
                    <label for="inp_popname">{msg_profile}</label>
                    <input type="text" name="popname" id="inp_popname" size="48" value="" maxlength="64" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_address">{msg_email}</label>
                    <input type="text" name="address" id="inp_address" size="48" value="" maxlength="64" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_realname">{msg_realname}</label>
                    <input type="text" name="real_name" id="inp_realname" size="48" value="" maxlength="64" />
                </div><!-- START infuture -->
                <div data-role="fieldcontain">
                    <label for="inp_cachetype">{msg_cachetype}</label>
                    <select size="1" name="cachetype" id="inp_cachetype"><!-- START cacheline -->
                        <option value="{id}">{name}</option><!-- END cacheline -->
                    </select>
                </div><!-- END infuture -->
                <div data-role="fieldcontain">
                    <label for="inp_sendvcf">{msg_sendvcf}</label>
                    <select size="1" name="sendvcf" id="inp_sendvcf">
                        <option value="none">{msg_vcf_none}</option>
                        <option value="priv">{msg_vcf_priv}</option>
                        <option value="busi">{msg_vcf_busi}</option>
                        <option value="all">{msg_vcf_all}</option>
                    </select>
                </div>
                <input type="checkbox" id="lbl_chkspam" name="checkspam" value="1" />
                <label for="lbl_chkspam">{msg_checkspam}</label>

                <div id="pop3_leaveonserver">
                    <input type="checkbox" id="lbl_leave" name="leaveonserver" value="1" />
                    <label for="lbl_leave">{msg_leaveonserver}</label>

                    <div id="pop3_localkillserver" style="padding-left:12px">
                        <input type="checkbox" id="lbl_localkill" name="localkillserver" value="1" />
                        <label for="lbl_localkill">{msg_localkillserver}</label>
                    </div>

                </div>

                <button type="button" id="convert_to_imap">{msg_convert_to_imap}</button>
                <button type="button" id="convert_to_pop3">{msg_convert_to_pop3}</button>
            </div>

            <div id="pop3fields">
                <h3>POP3</h3>
                <div data-role="fieldcontain">
                    <label for="inp_popserver">{msg_popserver}</label>
                    <input type="text" name="popserver" id="inp_popserver" size="32" value="" maxlength="64" />
                    <button type="button" onclick="copyf('popserver', 'smtp_host')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_popport">{msg_popport}</label>
                    <input type="text" name="popport" id="inp_popport" size="8" value="" maxlength="8" placeholder="110 / 995 (SSL)" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_popuser">{msg_popuser}</label>
                    <input type="text" name="popuser" id="inp_popuser" size="32" value="" maxlength="64" />
                    <button type="button" onclick="copyf('popuser', 'smtp_user')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_poppass">{msg_poppass}</label>
                    <input type="password" name="poppass" id="inp_poppass" size="32" value="" maxlength="64" />
                    <button type="button" onclick="copyf('poppass', 'smtp_pass')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="popsec_selector">{msg_popsec}</label>
                    <select size="1" id="popsec_selector" name="popsec"><!-- START popsec -->
                        <option value="{key}">{val}</option><!-- END popsec -->
                    </select>
                </div>

            </div>

            <div id="imapfields">
                <h3>IMAP</h3>
                <div data-role="fieldcontain">
                    <label for="inp_imapserver">{msg_popserver}</label>
                    <input type="text" name="imapserver" size="32" value="" id="inp_imapserver" maxlength="64" />
                    <button type="button" onclick="copyf('imapserver', 'smtp_host')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_imapport">{msg_popport}</label>
                    <input type="text" name="imapport" size="8" id="inp_imapport" value="" maxlength="8" plcaeholder="143 / 993 (SSL)" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_imapuser">{msg_popuser}</label>
                    <input type="text" name="imapuser" size="32" value="" id="inp_imapuser" maxlength="64" />
                    <button type="button" onclick="copyf('imapuser', 'smtp_user')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_imappass">{msg_poppass}</label>
                    <input type="password" name="imappass" size="32" value="" id="inp_imappass" maxlength="64" />
                    <button type="button" onclick="copyf('imappass', 'smtp_pass')" title="{copy_smtp}">
                        -&gt; SMTP
                    </button>
                </div>
                <div data-role="fieldcontain">
                    <label for="imapsec_selector">{msg_popsec}</label>
                    <select size="1" id="imapsec_selector" name="popsec">{popsec}
                    </select>
                </div>

                <input type="checkbox" id="lbl_onlysubscribed" name="onlysubscribed" value="1" />
                <label for="lbl_onlysubscribed">{msg_onlysubscribed}</label>

                <div style="display:none">
                    {msg_imapprefix}: <input type="text" name="imapprefix" value="" size="6" maxlength="250" />
                </div>
            </div>

            <div id="smtpfields">
                <h3>SMTP</h3>
                <div data-role="fieldcontain">
                    <label for="inp_smtphost">{msg_smtphost}</label>
                    <input type="text" name="smtp_host" size="32" value="" id="inp_smtphost" maxlength="64" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_smtpport">{msg_smtpport}</label>
                    <input type="text" name="smtp_port" size="8" value="" id="inp_smtpport" maxlength="8" placeholder="587 / 465 (SSL)" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_smtpuser">{msg_smtpuser}</label>
                    <input type="text" name="smtp_user" size="32" value="" id="inp_smtpuser" maxlength="64" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_smtppass">{msg_smtppass}</label>
                    <input type="password" name="smtp_pass" size="32" value="" id="inp_smtppass" maxlength="64" />
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_smtpsec">{msg_smtpsec}</label>
                    <select size="1" id="smtpsec_selector" name="smtpsec" id="inp_smtpsec"><!-- START smtpsec -->
                        <option value="{key}">{val}</option><!-- END smtpsec -->
                    </select>
                </div>
            </div>

            <div id="folderfields">
                <h3>{msg_folders}</h3>
                <div data-role="fieldcontain" id="pop3_inboxline" style="display:none;">
                    <label for="folder_inbox">{msg_inboxfolder}:</label>
                    <select size="1" name="inbox" id="folder_inbox">
                        <option value="0">{msg_defaultfolder}</option><!-- START inboxline -->
                        <option value="{id}">{name}</option><!-- END inboxline -->
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_sent_objects">{msg_sentfolder}:</label>
                    <select size="1" name="sent_objects" id="folder_sent_objects">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_waste">{msg_wastefolder}:</label>
                    <select size="1" name="waste" id="folder_waste">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_junk">{msg_junkfolder}:</label>
                    <select size="1" name="junk" id="folder_junk">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_drafts">{msg_draftsfolder}:</label>
                    <select size="1" name="drafts" id="folder_drafts">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_templates">{msg_templatesfolder}:</label>
                    <select size="1" name="templates" id="folder_templates">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="folder_archive">{msg_archivefolder}:</label>
                    <select size="1" name="archive" id="folder_archive">
                        <option value="0">{msg_defaultfolder}</option>{inboxline}
                    </select>
                </div>
            </div>

            <div id="aliasfields">
                <h3>{msg_aliases}</h3>
                <div class="sendmenuborder" id="aliascontainer" style="width:98%;height:160px;overflow:auto;clear:both;margin-bottom:4px;">
                </div>
                <button id="addalias" style="display:none" type="button" onclick="open_editalias()">{msg_addalias}</button>
            </div>

            <div id="uheadfields">
                <h3>{msg_uhead}</h3>
                {about_uheaders}<br />
                <br />
                <div class="sendmenuborder" id="uheadcontainer" style="width:98%;height:120px;overflow:auto;clear:both;margin-bottom:4px;">
                </div>
                <button id="adduhead" style="display:none" type="button" onclick="open_edituhead()">{msg_adduhead}</button>
            </div>

            <div id="advancedfields">
                <h3>{msg_various}</h3>
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <legend>{msg_fetchevery}</legend>
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
                    </fieldset>
                </div>
                <input type="checkbox" id="lbl_sigon" name="sig_on" value="1" />
                <label for="lbl_sigon"><strong>{msg_sigon}</strong></label><br />
                <div style="float:right;width:20px;padding:4px;text-align:left;vertical-align:top;height:95px;">
                    <img onclick="open_editsig(1);" style="cursor:pointer;display:block;" src="{theme_path}/icons/edit_menu.gif" alt="" title="{msg_editsig}" /><br />
                    <img onclick="dele_signature();" style="cursor:pointer;display:block;" src="{theme_path}/icons/dustbin_menu.gif" alt="" title="{msg_delesig}" /><br />
                    <img onclick="open_editsig();" style="cursor:pointer;display:block;" src="{theme_path}/icons/add_menu.gif" alt="" title="{msg_addsig}" /><br />
                </div>
                <div class="sendmenuborder" id="sigpreview" style="float:right;width:265px;height:95px;overflow:auto;"></div>
                <div class="sendmenuborder" id="sigcontain" style="width:200px;height:95px;overflow:auto;">
                </div>
            </div>

            <div>
                <button type="button" class="error" id="delebutton">{msg_dele}</button>
                <button type="button" class="ok" onclick="saveprofile()">{msg_save}</button>
            </div>
        </form>
    </div>
</div>

<div data-role="page" id="core_editalias" data-add-back-btn="true" data-back-btn-iconpos="notext">
    <div data-role="content">
        <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
            <div data-role="fieldcontain">
                <label for="aliasrealname">{msg_realname}</label>
                <input type="text" name="alRN" id="aliasrealname" size="20" maxlength="32" />
            </div>
            <div data-role="fieldcontain">
                <label for="aliasemail">{msg_email}</label>
                <input type="text" name="alEM" id="aliasemail" size="20" maxlength="255" />
            </div>
            <div data-role="fieldcontain">
                <label for="aliassignature">{msg_signature}</label>
                <select size="1" name="alSI" id="aliassignature">
                    <option class="keepme" value="">{msg_sig_default}</option>
                    <option class="keepme" value="0">{msg_sig_none}</option>
                </select>
            </div>

            <div data-role="fieldcontain">
                <label for="aliassendvcf">{msg_sendvcf}</label>
                <select size="1" name="alVI" id="aliassendvcf">
                    <option value="default">{msg_vcf_default}</option>
                    <option value="none">{msg_vcf_none}</option>
                    <option value="priv">{msg_vcf_priv}</option>
                    <option value="busi">{msg_vcf_busi}</option>
                    <option value="all">{msg_vcf_all}</option>
                </select>
            </div>

            <button type="submit" data-icon="check" data-inline="true">%h%save%</button>
            <a href="javascript:history.back(1);" data-role="button" data-icon="delete" data-inline="true">%h%cancel%</a>
        </form>
    </div>
</div>

<div data-role="page" id="core_edituhead" data-add-back-btn="true" data-back-btn-iconpos="notext">
    <div data-role="content">
        <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
            <div data-role="fieldcontain">
                <label for="uheadhkey">{msg_hkey}</label>
                <input type="text" name="uheadK" id="uheadhkey" size="20" maxlength="32" tabindex="3001" />
            </div>
            <div data-role="fieldcontain">
                <label for="uheadhval">{msg_hval}</label>
                <input type="text" name="uheadV" id="uheadhval" size="20" maxlength="255" tabindex="3002" />
            </div>
            <button type="submit" data-icon="check" data-inline="true">%h%save%</button>
            <a href="javascript:history.back(1);" data-role="button" data-icon="delete" data-inline="true">%h%cancel%</a>
        </form>
    </div>
</div>
<div data-role="page" id="core_editsig" data-add-back-btn="true" data-back-btn-iconpos="notext">
    <div data-role="content">
        <form action="#" method="get" onsubmit="return false;" accept-charset="UTF-8">
            <div data-role="fieldcontain">
                <label for="sigtitle">{msg_sigtitle}</label>
                <input type="text" name="sigtitle" id="sigtitle" size="32" maxlength="32" />
            </div>
            <div data-role="fieldcontain">
                <label for="ta_sigval_text">{msg_sigval_txt}</label>
                <textarea name="sigval_text" id="ta_sigval_text" rows="10" cols="80" style="width:690px;height:270px;"></textarea>
            </div>

            <div data-role="fieldcontain">
                <label for="ta_sigval_html">{msg_sigval_html}</label>
                <textarea name="sigval_html" id="ta_sigval_html" rows="10" cols="80" style="width:690px;height:270px;"></textarea>
            </div>
        <button type="submit" data-icon="check" data-inline="true">%h%save%</button>
        <a href="javascript:history.back(1);" data-role="button" data-icon="delete" data-inline="true">%h%cancel%</a>
        </form>
    </div>
</div>