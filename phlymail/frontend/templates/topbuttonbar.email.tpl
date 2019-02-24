<script type="text/javascript">
//<![CDATA[
opsfolder = false;
browserwin = false;
email_fetchInProgress = 0;
email_FetcherQueue = [];
email_customheight_preview = <!-- START customheight -->{height} + <!-- END customheight -->0;
checkInterval = window.setTimeout('email_check_mails()', 10000);
mailops_url = '{mailops_url}';
email_fetcher_url = '{fetcher_url}';
email_recheck_url = '{checkmail_url}';
fetcher_mode = 'profile';
email_FetcherFolder = 0;
msgConfirmDelete = '{msg_killconfirm}';
msgUpdatingIndex = '{msg_updatingindex}';
msgDowningMsgs = '{msg_dlingmessages}';
msgGettingMsgs = '{msg_getmessages}';
msgMail = '{msg_mail}';
msgProfile = '{msg_profile}';
msgAttach = '{msg_attach}';
msgNewMail = '{msg_newmail}';

pm_menu_additem('fetchitems', '{theme_path}/icons/email_men.gif', '{msg_mailbox}', 'emailfetch_init("user")', 0, 0, 'js');
pm_menu_addline('fetchitems');<!-- START fetchprof -->
pm_menu_additem('fetchitems', '{theme_path}/icons/email_men.gif', '{msg_mailbox}', 'emailfetch_init("user", {pid})', 0, 0, 'js');<!-- END fetchprof -->
pm_menu_additem('settings', '{theme_path}/icons/emailfilters_men.gif', '{msg_filters}', '{PHP_SELF}?l=setup&mod=filters&h=email&{passthrough}', 435, 180);<!-- START global_filters -->
pm_menu_additem('settings', '{theme_path}/icons/emailfilters_men.gif', '{msg_global_filters}', '{PHP_SELF}?l=setup&mod=filters&h=email&global=1&{passthrough}', 435, 180);<!-- END global_filters --><!-- START boilerplates -->
pm_menu_additem('settings', '{theme_path}/icons/boilerplate_men.gif', '{msg_boilerplates}', '{PHP_SELF}?l=setup&mod=boilerplates&h=email&{passthrough}', 1050, 470);<!-- END boilerplates --><!-- START global_boilerplates -->
pm_menu_additem('settings', '{theme_path}/icons/boilerplate_men.gif', '{msg_global_boilerplates}', '{PHP_SELF}?l=setup&mod=boilerplates&h=email&global=1&{passthrough}', 1050, 470);<!-- END global_boilerplates -->

// Since this is a quite central place for handling requests, multiple parallel requests must be traced
emailRq = [];
function email_AJAX(url)
{
    if (window.XMLHttpRequest) {
        var req = new XMLHttpRequest();
        text = null;
    } else if (window.ActiveXObject) {
        var req = new ActiveXObject("Microsoft.XMLHTTP");
        text = false;
    }
    if (req) {
        req.onreadystatechange = email_AJAX_ORS;
        req.open("GET", url, true);
        req.send(text);
        emailRq.push(req);
    }
}

function email_AJAX_ORS()
{
    if (emailRq.length == 0) return;

    for (var i = 0; i < emailRq.length; ++i) {
        if (emailRq[i].readyState == 4) {
            var myRq = emailRq[i];
            emailRq.splice(i, 1);
            if (typeof(myRq.status) != 'undefined' && (myRq.status == 304 || myRq.status == 200)) {
                if (!myRq.responseText.match(/^\{.+\}$/)) eval(myRq.responseText);
            }
            break;
        }
    }
}

function email_pinboard_opener(eid)
{
    var date = new Date();
    var parts = eid.split('_');
    var url = '{PHP_SELF}?l=read&h=email&{passthrough}&mail=' + parts[3];
    window.open(url, 'mailread_' + date.getTime(), 'width=600,height=500,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no');
}

// THX, Gecko...
function email_ext_checkmails()
{
    window.setTimeout('email_check_mails()', 1);
}

function email_check_mails()
{
    if (!FolderListLoaded) {
        window.clearTimeout(checkInterval);
        checkInterval = window.setTimeout('email_check_mails()', 10000); // Check again in 10s
        return;
    }
    window.clearTimeout(checkInterval);
    checkInterval = window.setTimeout('email_check_mails()', 60000); // Once every minute
    email_AJAX('{checkmail_url}');
}

function newmail_playsound(filename)
{
    if (!filename) filename = 'default_newmail.mp3';
    $('#newmailsound').empty().html('<object id="preview_player" width="1" height="1" uiMode="none" type="application/x-shockwave-flash"'
            + ' data="{frontend_path}/js/bgsoundplay.swf?file={frontend_path}/sounds/' + filename + '"'
            + '><param name="movie" value="{frontend_path}/js/bgsoundplay.swf?file={frontend_path}/sounds/' + filename + '" />'
            + '<' + '/object>');

}

function newmail_showalert()
{
    window.setTimeout("alert('{msg_newmail}')", 1);
}

function emailfetch_init(issuer, pid, folder)
{
    if (email_fetchInProgress) {
        email_FetcherQueue.push([issuer, pid, folder]);
        return;
    }
    email_fetchInProgress = 1;
    fetcher_url = email_fetcher_url;
    fetcher_profiles = [];
    fetcher_items = [];
    prof_nr = 0;
    prof_curr = 0;
    prof_all = 0;
    mail_nr = 0;
    mail_curr = 0;
    mail_all = 0;
    p_prof = document.getElementById('fetcher_inner');
    o_prof = document.getElementById('fetcher_outer');
    if (typeof profwidth == 'undefined') profwidth = p_prof.offsetWidth;
    o_prof.style.visibility = 'visible';
    if (issuer == 'user' && folder) {
        fetcher_mode = 'folder';
        email_FetcherFolder = folder;
        p_prof.className = 'prgr_inner_busy';
        p_prof.style.width = profwidth + 'px';
        set_statustext(msgUpdatingIndex + '...');
    } else {
        fetcher_mode = 'menu';
        email_FetcherFolder = 0;
        p_prof.className = 'prgr_inner';
        p_prof.style.width = '0';
    }
    fetcher_url += (issuer && issuer.length ? '&issuer=' + encodeURIComponent(issuer) : '')
            + (pid && pid > 0 ? '&single=' + pid : '')
            + (folder && folder > 0 ? '&folder=' + folder : '');
    fetcher_mode = (folder && folder > 0) ? 'folder' : 'default';
    $.ajax({'url': fetcher_url, 'success': emailfetch_process});
}

function emailfetch_process(next)
{
    if (next['error']) {
        core_log_error(next['error']);
    }
    if (next['archive'] && next['archive'] != 0) {
        $.ajax({'url':mailops_url + 'archive&mail=' + encodeURIComponent(next['archive']) + '&alternate=1', dataType:'script'});
    }
    if (next['delete'] && next['delete'] != 0) {
        $.ajax({'url':mailops_url + 'delete&mail=' + encodeURIComponent(next['delete']) + '&alternate=1', dataType:'script'});
    }
    if (next['markjunk'] && next['markjunk'] != 0) {
        $.ajax({'url':mailops_url + 'spam&mail=' + encodeURIComponent(next['markjunk']), dataType:'script'});
    }
    if (next['copy_mail'] && next['copy_to']) {
        $.ajax({'url':mailops_url + 'copy&mail=' + encodeURIComponent(next['copy_mail']) + '&folder=' + encodeURIComponent(next['copy_to']), dataType:'script'});
    }
    if (next['move_mail'] && next['move_to']) {
        $.ajax({'url':mailops_url + 'move&mail=' + encodeURIComponent(next['move_mail']) + '&folder=' + encodeURIComponent(next['move_to']), dataType:'script'});
    }
    if (next['deleted'] || next['updated']) email_refreshlist();
    if (next['profiles']) {
        if (next['profiles'].length == 0) emailfetch_done();
        fetcher_profiles = next['profiles'];
        prof_all = (next['profiles'].length);
        prof_nr = 0;
    }
    if (next['items']) {
        fetcher_items = next['items'];
        mail_all = fetcher_items.length;
        mail_nr = 0;
    }
    // Ran out of mails, try to get the list for the next profile
    if (fetcher_items.length == 0) {
        // Ran out of profiles, too - done();
        if (fetcher_profiles.length == 0) {
            emailfetch_done();
            return;
        }
        prof_curr = fetcher_profiles.shift();
        prof_nr++;
        emailfetch_progress(prof_nr, prof_all, 0, 0);
        $.ajax({'url': email_fetcher_url + '&step=2&pid=' + prof_curr, success: emailfetch_process});
        return;
    }
    mail_curr = fetcher_items.shift();
    mail_nr++;
    emailfetch_progress(prof_nr, prof_all, mail_nr, mail_all);
    $.ajax(
            {'url': email_fetcher_url + '&step=3'
                    + (fetcher_mode == 'folder' ? '&folder=' + email_FetcherFolder + '&uidl=' : '&pid=' + prof_curr + '&mail=')
                    + encodeURIComponent(mail_curr)
            ,'success': emailfetch_process
            });
}

function emailfetch_progress(profnr, profall, mailnr, mailall)
{
    if (!profnr) profnr = 0;
    if (!profall) profall = 0;
    if (!mailnr) mailnr = 0;
    if (!mailall) mailall = 0;
    // Draws the bar even on loading of mails for an IMAP folder
    if (profall == 0 && mailall > 0) {
        set_statustext(msgDowningMsgs + '... (' + msgMail + ' ' + mailnr + ' / ' + mailall + ')');
        profall = mailall;
        profnr = mailnr;
        p_prof.className = 'prgr_inner';
    } else {
        set_statustext(msgGettingMsgs + '... (' + msgProfile + ' ' + profnr + ' / ' + profall + ', ' + msgMail + ' ' + mailnr + ' / ' + mailall + ')');
    }
    if (profnr > profall) profnr = profall;
    p_prof.style.width = ((profall == 0) ? 0 : (profnr / profall) * profwidth) + 'px';
}

function emailfetch_done()
{
    email_fetchInProgress = 0;
    o_prof.style.visibility = 'hidden';
    empty_statustext();
    window.setTimeout('email_check_mails();', 60000);
    if (email_FetcherQueue.length) {
        var Now = email_FetcherQueue.shift();
        window.setTimeout('emailfetch_init("' + Now[0] + '","' + Now[1] + '","' + Now[2] + '");', 1);
    } else {
        email_refreshlist();
    }
}

function email_refreshlist()
{
    $.ajax({'url': email_recheck_url, dataType: 'script'});
}
// ]]>
</script>