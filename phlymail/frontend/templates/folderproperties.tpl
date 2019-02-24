<script type="text/javascript">
// <![CDATA[
function submit_rename()
{<!-- START js_norename -->
    return false;<!-- END js_norename -->
    return false;
}

function submit_icon() // This will fade in a list with possible icons for selecting one
{<!-- START js_noicon -->
    return false;<!-- END js_noicon -->
    return false;
}

function on_view_default()
{
    $('#show_fields_area').css('display', $('#view_default').is(':checked') ? 'none' : 'block');
}

function open_export()
{
    window.open
            ('{exportwinurl}'
            ,'export_' + new Date().getTime()
            ,'top=200,left=200,width=460,height=200,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            );
}

function open_subscribe()
{
    window.open
            ('{subscribewinurl}'
            ,'subscribe_' + new Date().getTime()
            ,'top=200,left=200,width=460,height=600,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            );
}

function open_hidefolders()
{
    window.open
            ('{hidefolderswinurl}'
            ,'hidefolders_' + new Date().getTime()
            ,'top=200,left=200,width=460,height=600,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no'
            );
}

function draw_bar(bid, klimit, usage, keep)
{
    var g, r;
    var cont = document.getElementById(bid + '_cont');
    var bar = document.getElementById(bid + '_bar');
    if (((usage == '' || klimit == '' || usage == 0 || klimit == 0) && keep != 1)
            || (usage == 0 && klimit == 0 && keep == 1)) {
        bar.style.visibility = 'hidden';
        cont.title = '0%';
        return;
    }
    // Since MSIE fails to read cont.offsetWidth I had to set the value from the style.width setting manually
    var fullwidth = 40; // cont.offsetWidth;

    klimit = klimit * 1;
    usage  = usage * 1;
    if (usage >= klimit) {
        bar.style.width = fullwidth + 'px';
        bar.style.height = '12px';
        bar.style.backgroundColor = '#FF0000';
        cont.title = '>= 100%';
    } else {
        var prozent = Math.round(100 * usage / klimit);
        bar.style.width = (fullwidth * prozent / 100) + 'px';
        bar.style.height = '12px';
        g = r = 100;
        if (prozent < 50) {
            r = prozent * 2;
        } else if (prozent > 50) {
            g = (100-prozent) * 2;
        }
        bar.style.backgroundColor = 'rgb(' + r + '%,' + g + '%,0%)';
        cont.title = prozent + '%';
    }
}

$(document).ready(function() {
    addsel = document.getElementById('addselect');
    addform = document.getElementById('selected_form');
    on_view_default();
    $('#showfields_sortable').sortable().disableSelection();
    $('.accordion').accordion();
    if ($('#type_row select').length > 0) {
        $('#type_row span').remove();
    }
    
    adjust_height();
})
// ]]>
</script><!-- START has_global_message -->
<div id="pagewide-messagebox">{message}</div><!-- END has_global_message -->
<div class="l accordion">
    <h3><a href="#">{msg_properties}</a></h3>
	<div>
        <form action="{form_target}" method="post">
            <input type="hidden" name="formname" value="basic_settings" />
            <div style="float:left;padding:10px;margin-right:10px;">
                <img src="{big_icon}" alt="{foldername}" style="display:block;" />
            </div>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td class="l t">{msg_name}:&nbsp;</td>
                    <td class="l t">{foldername}</td>
                </tr>
                <tr>
                    <td class="l t">{msg_type}:&nbsp;</td>
                    <td class="l t" id="type_row"><span>{type}</span><!-- START has_type_select -->
                        <select size="1" name="type"><!-- START line -->
                            <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END line -->
                        </select><!-- END has_type_select -->
                    </td>
                </tr>
                <tr>
                    <td class="l" colspan="2">{msg_has_folders}: {has_folders}</td>
                </tr>
                <tr>
                    <td class="l" colspan="2">{msg_has_items}: {has_items}</td>
                </tr><!-- START has_last_update -->
                <tr>
                    <td class="l" colspan="2">{msg_last_update}: {last_update}</td>
                </tr><!-- END has_last_update --><!-- START has_show_in_root -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="show_in_root" id="show_in_root" value="1"<!-- START show_in_root --> checked="checked"<!-- END show_in_root --> />
                        <label for="show_in_root">{msg_show_in_root}</label>
                    </td>
                </tr><!-- END has_show_in_root --><!-- START has_show_in_sync -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="show_in_sync" id="show_in_sync" value="1"<!-- START show_in_sync --> checked="checked"<!-- END show_in_sync --> />
                        <label for="show_in_sync">{msg_show_in_sync}</label>
                    </td>
                </tr><!-- END has_show_in_sync --><!-- START has_show_in_pinboard -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="show_in_pinboard" id="show_in_pinboard" value="1"<!-- START show_in_pinboard --> checked="checked"<!-- END show_in_pinboard --> />
                        <label for="show_in_pinboard">{msg_show_in_pinboard}</label>
                    </td>
                </tr><!-- END has_show_in_pinboard --><!-- START has_autoarchive -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="autoarchive" value="1" id="lbl_autoarchive"<!-- START autoarchive --> checked="checked"<!-- END autoarchive --> />
                        <label for="lbl_autoarchive">&nbsp;{msg_autoarchive_olderthan}</label>

                        <input type="number" min="0" name="autoarchive_age_inp" value="{autoarchive_age}" max="99999">
                        <select size="1" name="autoarchive_age_drop"><!-- START autoarchive_age_drop -->
                            <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END autoarchive_age_drop -->
                        </select>
                    </td>
                </tr><!-- END has_autoarchive --><!-- START has_autodelete -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="checkbox" name="autodelete" value="1" id="lbl_autodelete"<!-- START autodelete --> checked="checked"<!-- END autodelete --> />
                        <label for="lbl_autodelete">&nbsp;{msg_autodelete_olderthan}</label>

                        <input type="number" min="0" name="autodelete_age_inp" value="{autodelete_age}" max="99999">
                        <select size="1" name="autodelete_age_drop"><!-- START autodelete_age_drop -->
                            <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END autodelete_age_drop -->
                        </select>
                    </td>
                </tr><!-- END has_autodelete --><!-- START has_store_basic_settings -->
                <tr>
                    <td class="l" colspan="2">
                        <input type="submit" value="{msg_save}" />
                    </td>
                </tr><!-- END has_store_basic_settings -->
            </table>
            <br />
        </form><!-- START colour -->
        <br />
        <fieldset>
            <legend>{leg_colour}</legend>
            <form action="{form_target}" method="post" name="colourform" class="foldercolour_container">
                <div class="item">
                    <input type="radio" name="foldercolour" id="rad_folder_colour_" value="" checked="checked" />
                    <label for="rad_folder_colour_">-</label>
                </div><!-- START sel_foldercolour -->
                <div class="item">
                    <input type="radio" name="foldercolour" id="rad_folder_colour_{hex}" value="{hex}"<!-- START sel --> checked="checked"<!-- END sel --> />
                    <label for="rad_folder_colour_{hex}" class="sendmenubut" style="background-color:#{hex};">&nbsp;</label>
                </div><!-- END sel_foldercolour -->
                <div style="clear:both;"><br /></div>
                <input type="submit" value="{msg_save}" />
            </form>
        </fieldset>
        <br /><!-- END colour --><!-- START externalsource -->
        <br />
        <fieldset>
            <legend>{leg_externalsource}</legend>
            <form action="{form_target}" method="post" name="exturiform">
                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="l t">URI:</td>
                        <td class="l t">
                            <input type="text" name="uri" value="{uri}" size="32" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_username}:</td>
                        <td class="l t">
                            <input type="text" name="username" autocomplete="off" value="{username}" size="16" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_passwod}:</td>
                        <td class="l t">
                            <input type="password" name="password" autocomplete="off" value="{password}" size="16" />
                        </td>
                    </tr>
                    <tr>
                        <td class="l t">{msg_checkevery}:</td>
                        <td class="l t">
                            <input type="text" value="{checkevery_value}" name="checkevery_value" size="4" />
                            <select size="1" name="checkevery_range">
                                <option value="m"<!-- START s_w_m --> selected="selected"<!-- END s_w_m -->>{msg_minutes}</option>
                                <option value="h"<!-- START s_w_h --> selected="selected"<!-- END s_w_h -->>{msg_hours}</option>
                                <option value="d"<!-- START s_w_d --> selected="selected"<!-- END s_w_d -->>{msg_days}</option>
                                <option value="w"<!-- START s_w_w --> selected="selected"<!-- END s_w_w -->>{msg_weeks}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="l t"><input type="submit" value="{msg_save}" /></td>
                    </tr>
                </table>
            </form>
        </fieldset>
        <br /><!-- END externalsource -->
        <div><!-- START has_export -->
            <button type="button" onclick="open_export();">{msg_export}</button><!-- END has_export --><!-- START has_subscribe -->
            <button type="button" onclick="open_subscribe();">{msg_subscribe}</button><!-- END has_subscribe --><!-- START has_hidefolders -->
            <button type="button" onclick="open_hidefolders();">{msg_hidefolders}</button><!-- END has_hidefolders -->
        </div>
    </div><!-- START plugin --><!-- END plugin --><!-- START display -->
    <h3><a href="#">{leg_display}</a></h3>
	<div>
        <form action="{form_target}" method="post" name="fieldsform">
            <div id="preview"<!-- START nopreview --> style="display:none;"<!-- END nopreview -->>
                <input type="checkbox" name="show_preview" value="1" id="chk_preview"<!-- START show_preview --> checked="checked"<!-- END show_preview --> />
                <label for="chk_preview">{msg_use_preview}</label>
                <br />
            </div>
            <div id="cnt_default"<!-- START noviewdefault --> style="display:none;"<!-- END noviewdefault -->>
                <input type="checkbox" name="view_default" onclick="on_view_default();" value="1" id="view_default"<!-- START view_default --> checked="checked"<!-- END view_default --> />
                <label for="view_default" onclick="on_view_default();">{msg_use_default}</label><br />
            </div>
            <div id="show_fields_area">
                <ul id="showfields_sortable" class="l sendmenubut sendmenuborder"><!-- START dbline -->
                    <li id="cont_{id}">
                        <span class="ui-icon"></span>
                        <input type="checkbox" name="show_field[{id}]" value="1" id="show_field_{id}"<!-- START checked --> checked="checked"<!-- END checked --> />
                        <label id="lbl_sf_{id}" for="show_field_{id}"><strong>{value}</strong></label>
                    </li><!-- END dbline -->
                </ul><!-- START has_set_as_default -->
                <div id="cont_set_as_default">
                    <input type="checkbox" name="set_as_default" value="1" id="set_as_default" />
                    <label for="set_as_default">{msg_set_as_default}</label>
                </div><!-- END has_set_as_default -->
            </div><!-- START has_orderby -->
            <div style="padding:4px 0;">
                {msg_orderby}:
                <select size="1" name="orderby"><!-- START orderline -->
                    <option value="{val}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END orderline -->
                </select>
                <select size="1" name="orderdir">
                    <option value="ASC"<!-- START selasc --> selected="selected"<!-- END selasc -->>{msg_asc}</option>
                    <option value="DESC"<!-- START seldesc --> selected="selected"<!-- END seldesc -->>{msg_desc}</option>
                </select>
            </div><!-- END has_orderby --><!-- START has_groupby -->
            <div style="padding:4px 0;">
                {msg_groupby}:
                <select size="1" name="groupby"><!-- START groupline -->
                    <option value="{val}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END groupline -->
                </select>
            </div><!-- END has_groupby -->
            <br />
            <input type="submit" value="{msg_save}" />
            <br />
        </form>
    </div><!-- END display --><!-- START quotas -->
    <h3><a href="#">{leg_quotas}</a></h3>
	<div>
        <table border="0" cellpadding="2" cellspacing="0"><!-- START quotaline -->
            <tr>
                <td>{msg_crit}</td>
                <td>{msg_use}</td>
                <td> / </td>
                <td>{msg_limit}</td>
                <td>
                    <div id="{crit_id}_cont" class="quotabar_cont"><div id="{crit_id}_bar"></div></div>
                    <script type="text/javascript">/*<![CDATA[*/draw_bar("{crit_id}", {limit}, {use});/*]]>*/</script>
                </td>
            </tr><!-- END quotaline -->
        </table>
    </fieldset>
    <br /><!-- END quotas --><!-- START webapi -->
    <h3><a href="#">{leg_api}</a></h3>
	<div>
        {about_webapi}<br />
        <br />
        <strong>{title_http}</strong><br />
        <input type="text" name="" value="{url}" readonly="readonly" size="64" style="width:95%" /><br />
        <br />
        <strong>{title_xna}</strong><br />
        {about_webapi_xna} <a href="{generate_xna_url}">{xna_submit_value}</a><br />
        <br />
        <input type="text" name="" value="{url_xna}" readonly="readonly" size="64" style="width:95%;" />
    </div><!-- END webapi -->
</div>