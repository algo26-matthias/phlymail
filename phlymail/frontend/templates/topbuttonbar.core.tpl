<script type="text/javascript">
//<![CDATA[
core_view = {'favourites':0, 'folderlist':0, 'namepane':0};<!-- START has_new_email -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/email_men.gif'
        ,'{msg_newemail}'
        ,'{PHP_SELF}?l=compose_email&h={handler}&{passthrough}'
        ,'100%'
        ,'100%'
        );<!-- END has_new_email --><!-- START smsactive -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/sms_men.gif'
        ,'{msg_newsms}'
        ,'{PHP_SELF}?l=compose_sms&h={handler}&{passthrough}'
        ,700
        ,500
        );<!-- END smsactive --><!-- START faxactive -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/fax_men.png'
        ,'{msg_newfax}'
        ,'{PHP_SELF}?l=compose_fax&h={handler}&{passthrough}'
        ,700
        ,500
        );<!-- END faxactive -->
pm_menu_additem
        ('system'
        ,'{theme_path}/icons/about_men.gif'
        ,'{msg_about} ...'
        ,'{PHP_SELF}?l=about&h=core&{passthrough}'
        ,320
        ,256
        );
pm_menu_additem
        ('system'
        ,'{theme_path}/icons/logout_men.gif'
        ,'{msg_logout}'
        ,'{PHP_SELF}?action=logout&{passthrough}'
        ,0
        ,0
        ,'href'
        );<!-- START showlinkconfig -->
pm_menu_additem
        ('system'
        ,'{theme_path}/icons/men_gotoconfig.png'
        ,'{msg_gotoconfig}'
        ,'{PHP_SELF}?action=logout&{passthrough}&redir=config'
        ,0
        ,0
        ,'href'
        );<!-- END showlinkconfig --><!-- START usersetup -->
pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/setup_men.gif'
        ,'{msg_setup_programme}'
        ,'{PHP_SELF}?l=setup&mode=general&h={handler}&{passthrough}'
        ,610
        ,400
        );<!-- END usersetup --><!-- START profiles -->
pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/email_men.gif'
        ,'{msg_setup_pop3_accounts}'
        ,'{PHP_SELF}?l=setup&mode=profiles&h={handler}&{passthrough}'
        ,770
        ,410
        );<!-- END profiles -->

pm_menu['view'] = [];
pm_menu['view'][0] = [];
pm_menu['view'][0]['name'] = '{msg_showfavfolderss}';
pm_menu['view'][0]['link'] = 'core_switchview("favourites", -1)';
pm_menu['view'][0]['linktype'] = 'js';
pm_menu['view'][0]['selected'] = 0;
pm_menu['view'][1] = [];
pm_menu['view'][1]['name'] = '{msg_showfolderlist}';
pm_menu['view'][1]['link'] = 'core_switchview("folderlist", -1)';
pm_menu['view'][1]['linktype'] = 'js';
pm_menu['view'][1]['selected'] = 0;
pm_menu['view'][2] = [];
pm_menu['view'][2]['name'] = '{msg_shownamepane}';
pm_menu['view'][2]['link'] = 'core_switchview("namepane", -1)';
pm_menu['view'][2]['linktype'] = 'js';
pm_menu['view'][2]['selected'] = 0;

coreChkIntvl = window.setTimeout('core_check_quotas()', 5000);
core_quota = 'okay';
function core_check_quotas()
{
    window.clearTimeout(coreChkIntvl);
    coreChkIntvl = window.setTimeout('core_check_quotas()', 600000); // Once every 10 minutes, that's enough
    $.ajax(
        {url : '{checkquota_url}'
        ,dataType : 'json'
        ,success : function (next) {
            if (next['error']) {
                core_log_error(next['error']);
            }
            if (next['get_servertime']) {
                core_draw_quota(next['get_quota_state']);
                core_adjustclock(next['get_servertime']);
            }
        }
    })
}

function core_draw_quota(quota)
{
    $('#quotaicon_' + core_quota).hide();
    quota = quota * 1;
    if (quota < 0.34) {
        core_quota = 'okay';
    } else if (quota < 0.67) {
        core_quota = 'medium';
    } else {
        core_quota = 'bad';
    }
    $('#quotaicon_' + core_quota).show().attr('title', Math.round(quota * 100) + '%');
}

function core_adjustclock(servertime)
{
    var jetzt = new Date();
    var server = new Date(servertime);
    core_clockoffset = server.getTime() - jetzt.getTime();
}

function core_switchview(field, value)
{
    field = field.replace(/[^a-zA-Z0-9_]/, '');
    if (value == -1) {
        core_view[field] = 1-core_view[field];
        save_custom_size('core_vieww_' + field, core_view[field]);
    } else {
        core_view[field] = (value == 1) ? 1 : 0;
    }
    if (field == 'favourites') {
        $('#favfolderpane').css('display', core_view['favourites'] == 1 ? 'block' : 'none');
        window.setTimeout('adjust_height();', 250);
        pm_menu['view'][0]['selected'] = core_view['favourites'];
    }
    if (field == 'namepane') {
        $('#namepane').css('display', core_view['namepane'] == 1 ? 'block' : 'none');
        window.setTimeout('adjust_height();', 250);
        pm_menu['view'][2]['selected'] = core_view['namepane'];
    }
    if (field == 'folderlist') {
        FolderListWidthEffective = core_view['folderlist'] == 1 ? FolderListWidth : 0;
        $('#PHM_tl').css('display', core_view['folderlist'] == 1 ? 'block' : 'none');
        $('#middleresizer').css('display', core_view['folderlist'] == 1 ? 'block' : 'none');
        window.setTimeout('adjust_height();', 250);
        pm_menu['view'][1]['selected'] = core_view['folderlist'];
    }
}
$(document).ready(function () {
    $('#notify-container').notify({speed: 1000, expires: 5000});
    var loginmessage = '{loginmessage}';
    if (loginmessage.length) {
        $('#notify-container').notify('create', {'text' : loginmessage},{ expires: false });
    }

});<!-- START logincheckupdates -->

$(document).ready(function () {
    $.ajax({url: '{url_logincheckupdates}', dataType: 'text', success: show_hasupdates });
});

function show_hasupdates(next)
{
    // No updates or sth. else went wrong
    if (typeof next == 'undefined' || !next || next == 'no') return;

    next = next.split('|');
    var aboutText = '{about_update}'.replace('$build$', next[0]);
    aboutText = aboutText.replace('$relstatus$', next[1]);
    var updatesAvailableNotify = $('#notify-container').notify('create', {'title' : '{head_update}', 'text' : aboutText});
    core_icontray_add('updatesavailableicon', '{theme_path}/icons/notify_newupdate.png', '{title_update}', function () {
            updatesAvailableNotify.open();
    });
}
<!-- END logincheckupdates -->
//]]>
</script>