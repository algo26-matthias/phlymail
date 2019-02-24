<script type="text/javascript">
//<[CDATA[
open_mask    = false;
currplate    = 0;
currfolder   = false;
platetype    = false;
_editor_url  = '{frontend_path}/js/ckeditor/'
_editor_lang = '{user_lang}';

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
    if (next['error']) {
        alert(next['error']);
        return;
    }
    if (next['platesaved'] || next['groupsaved']) {
        window.location.reload();
    }
    if (next['plate']) {
        fillfields(next['plate']);
    }
    if (next['platelist']) {
        gotplatelist(next['platelist']);
    }
}

function selectfolder()
{
    if (currfolder !== false) $('#flist_' + currfolder).removeClass('marked');
    currfolder = this.id.replace(/^flist_/, '');
    $(this).addClass('marked');
    $('#showbutton_subgroup').css('visibility', (currfolder > 0) ? 'visible' : 'hidden');
    AJAX_call('{getplatelisturl}' + currfolder);
}

function gotplatelist(data)
{
    var html = '';
    $.each(data, function (id, line) {
        html += '<tr id="prof_' + line.id + '" class="menuline" style="cursor:pointer;">';
        html += '<td class="l t"><img src="{theme_path}/icons/';
        if (line.type == 'text') {
            html += 'boilerplate_men.gif';
        } else {
            html += 'boilerplate2_men.gif';
        }
        html += '" alt="" /></td><td class="l t">' + line.name + '</td></tr>';
    });
    $('#bplatelist').html(html).find('tr').click(loadplate);
}

function loadplate()
{
    var id = this.id.replace(/^prof_/, '');
    // unmark current plate in list
    if (currplate != 0) {
        $('#prof_' + currplate).removeClass('marked');
    }
    currplate = id;
    AJAX_call('{editlink}' + id, 'get');
}

function fillfields(data)
{
    // mark current plate in list
    $('#prof_' + currplate).addClass('marked');
    emptyfields();
    $('#platename').val(data['name']);
    $('#platebody').val(data['body']);
    $('#plategroup').val(data['gid']);
    platetype = data['type'];
    $('#delebutton').css('display', 'inline');
    $('#generic').show();
    show_wysiwyg();
}

function emptyfields()
{
    $('#platename,#platebody,#plategroup').val('');
    drop_wysiwyg();
}

function addplate(plate_type)
{
    emptyfields();
    currplate = 0;
    platetype = plate_type;
    $('#generic').show();
    $('#delebutton, #onhtml, #ontext').hide();
    $('#plategroup').val(currfolder);
    show_wysiwyg();
}

function show_wysiwyg()
{
    if (platetype != 'html') {
        $('#ontext').show();
        return;
    }
    $('#onhtml').show();
    $('#htmlname').val($('#platename').val());
    $('#htmlgroup').val($('#plategroup').val());
    if (typeof CKEDITOR.instances.htmlbody != 'undefined') {
        CKEDITOR.instances.htmlbody.setData($('#platebody').val());
        return;
    }
    $('#htmlbody').val($('#platebody').val());
    CKEDITOR.replace
            ('htmlbody'
            ,{ baseHref : _editor_url, language : _editor_lang, uiColor : themeBaseColour, toolbarStartupExpanded : true, toolbar : 'Basic', height: 270}
            );
}

function drop_wysiwyg()
{
    if (platetype != 'html') {
        $('#ontext').hide();
        return;
    }
    $('#onhtml').hide();
}

function saveplate()
{
    form = document.forms.mainform;
    if (platetype == 'html') {
        $('#platebody').val(CKEDITOR.instances.htmlbody.getData());
        $('#platename').val($('#htmlname').val());
        $('#plategroup').val($('#htmlgroup').val());
    }
    data = 'type=' + encodeURIComponent(platetype)
            + '&name=' + encodeURIComponent($('#platename').val())
            + '&body=' + encodeURIComponent($('#platebody').val())
            + '&gid=' + encodeURIComponent($('#plategroup').val());
    AJAX_call('{savelink}' + ((currplate != 0) ? 'saveold&id=' + currplate : 'savenew'), 'post', data);
}

function deleplate()
{
    if (!currplate) return;
    if (confirm('{kill_request}')) {
        AJAX_call('{delelink}' + currplate, 'get');
    }
}

function addgroup(where)
{
    var newName = prompt('{msg_foldername}');
    if (newName.length == 0 || false === newName) {
        return;
    }
    AJAX_call('{savelink}newgroup&childof='
            + (where == 'main' ? 0 : currfolder)
            + '&name=' + encodeURIComponent(newName));
}

function editgroup()
{
    var ID = this.parentNode.id.replace(/^flist_/, '');
    var oldName = $('#flist_' + ID).attr('title');
    var newName = prompt('{msg_foldername}', oldName);
    if (newName.length == 0 || false === newName) {
        return;
    }
    if (newName == oldName) {
        return;
    }
    AJAX_call('{savelink}oldgroup&id=' + ID + '&name=' + encodeURIComponent(newName));
}

function delgroup()
{
    var ID = this.parentNode.id.replace(/^flist_/, '');
    if (confirm('{qdelfolder}'.replace(/\$1/, $('#flist_' + ID).attr('title')))) {
        AJAX_call('{savelink}delgroup&id=' + ID);
    }
}

$(document).ready(function () {
    $('#flist_container .foldername').click(selectfolder)
            .find('.delgrp').click(delgroup).end().find('.editgrp').click(editgroup);
});
// ]]>
</script>
<script type="text/javascript" src="{frontend_path}/js/ckeditor/ckeditor.js?{current_build}"></script>
<table border="0" cellpadding="2" cellspacing="0" width="1024">
<colgroup>
    <col width="200" />
    <col width="200" />
    <col width="*" />
</colgroup>
<tbody>
    <tr>
        <td class="l t">
            <div id="flist_container" class="sendmenuborder inboxline" style="height:415px;overflow:auto;background:white;"><!-- START listfolder -->
                <div class="foldername" id="flist_{id}" title="{name}">
                    <div class="folderlevel" style="margin-left:{spacer}px;"></div><!-- START edit -->
                    <img class="folderinlineedit delgrp" src="{theme_path}/icons/deletefolder_ctx.gif" title="{msg_del}" alt="" />
                    <img class="folderinlineedit editgrp" src="{theme_path}/icons/renamefolder_ctx.gif" title="{msg_ren}" alt="" /><!-- END edit -->
                    <img class="foldericon" src="{icon}" alt="" />
                    <span class="name">{name}</span>
                </div><!-- END listfolder -->
            </div>
        </td>
        <td class="l t">
            <div class="sendmenuborder inboxline" style="height:415px;overflow:auto;background:white;">
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                    <colgroup>
                        <col width="17" />
                        <col width="*" />
                    </colgroup>
                    <tbody id="bplatelist">
                    </tbody>
                </table>
            </div>
        </td>
        <td class="l t">
            <form name="mainform" onsubmit="saveplate();" action="#" method="get">
                <div id="generic" style="display:none;height:376px;">
                    <div class="sendmenubut" id="ontext" style="display:none;">
                        <strong>{msg_platename}</strong><br />

                        <select size="1" name="plategroup" id="plategroup" style="width:295px;float:right;"><!-- START selectfolder -->
                            <option value="{id}">{name}</option><!-- END selectfolder -->
                        </select>

                        <input type="text" name="platename" id="platename" size="24" value="" maxlength="32" style="width:295px;" /><br />
                        <br />
                        <strong>{msg_platebody}</strong><br />
                        <textarea name="platebody" id="platebody" rows="20" cols="40" style="width:600px;height:320px;"></textarea>
                    </div>
                    <div class="sendmenubut" id="onhtml" style="display:none;">
                        <strong>{msg_platename}</strong><br />

                        <select size="1" name="htmlgroup" id="htmlgroup" style="width:295px;float:right;">{selectfolder}
                        </select>

                        <input type="text" name="htmlname" id="htmlname" size="24" value="" maxlength="32" style="width:295px;" /><br />
                        <br />
                        <strong>{msg_platebody}</strong><br />
                        <textarea name="htmlbody" id="htmlbody" rows="20" cols="40" style="width:600px;height:320px;"></textarea>
                    </div>
                    <div style="text-align:right;margin-top:4px;">
                        <button class="error" style="display:none;" id="delebutton" type="button" onclick="deleplate()">{msg_dele}</button>
                        <button class="ok" type="button" onclick="saveplate()">{msg_save}</button>
                    </div>
                </div>
            </form>
        </td>
    </tr>
    <tr class="middlemenu">
        <td colspan="3" class="l">
            <button type="button" onclick="addgroup('main');">{msg_add_mgroup}</button>&nbsp;
            <div id="showbutton_subgroup" style="display:inline;visibility:hidden;">
                <button type="button" onclick="addgroup('here');">{msg_add_sgroup}</button>&nbsp;
            </div>
            <button type="button" onclick="addplate('text');">{msg_add_text}</button>&nbsp;
            <button type="button" onclick="addplate('html');">{msg_add_html}</button>
        </td>
    </tr>
</tbody>
</table>