<script type="text/javascript">
//<![CDATA[
function disable_warn(active) { $('#warndisable').css('display', (active) ? 'block' : 'none'); }
function onSlider()
{
    var sval = $('#wd_slider').slider('option', 'values');
    $('#wd_start').val(sval[0]/50);
    $('#wd_end').val(sval[1]/50);
    $('#wd_slider_human').html(slider2hrtime(sval[0]) + ' - ' + slider2hrtime(sval[1]));
}

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

$(document).ready(function() {
    adjust_height();
    disable_warn((document.getElementById('warn').checked) ? true : false);
    window.setTimeout('$("#result_message").css("visibility","hidden");', 7000);
    $('#wd_slider').slider(
            {range: true, minValue: 0, maxValue: 2350, steps: 50, min:0, max:2350, step:50, values: [{wd_start}, {wd_end}], slide: onSlider, change: onSlider
            });
    onSlider();
});
//]]>
</script>
<form action="{target_link}" method="post">
    <input type="hidden" name="wd_start" id="wd_start" value="" />
    <input type="hidden" name="wd_end" id="wd_end" value="" />
    <div class="t l">
        <fieldset>
            <legend>%h%CalWorkingDays%</legend>
            %h%CalAboutWorkingDays%<br />
            <br />
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td>&nbsp;</td><!-- START wd_head -->
                    <td class="t c" title="{daytitle}">{day}</td><!-- END wd_head -->
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="l"><strong>%h%CalWorkingDays%:</strong></td><!-- START wd_box -->
                    <td class="t c">
                        <input type="checkbox" name="wd[{id}]" value="1"<!-- START sel --> checked="checked"<!-- END sel --> />
                    </td><!-- END wd_box -->
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="l"><strong>%h%CalWorkingTime%:</strong></td>
                    <td colspan="7" class="l">
                        <div id="wd_slider"></div>
                    </td>
                    <td id="wd_slider_human">
                   </td>
                </tr>
            </table>
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%CalDefaultView%</legend>
            %h%CalAboutDefaultView%<br />
            <br />
            %h%CalDefaultView%:
            <select size="1" name="viewmode"><!-- START viewmode -->
                <option value="{mode}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END viewmode -->
            </select>
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%CalDefAlert%</legend>
            %h%CalAboutDefAlert%<br />
            <input type="checkbox" id="warn" name="warn" onchange="disable_warn((this.checked) ? true : false)" onclick="disable_warn((this.checked) ? true : false)" value="1"<!-- START warn --> checked="checked"<!-- END warn --> />
            <label for="warn">%h%CalWarnMe%</label><br />
            <div id="warndisable">
                <input type="text" name="warn_time" value="{warn_time}" size="6" maxlength="6" />
                <select size="1" name="warn_range">
                    <option value="m"<!-- START s_w_m --> selected="selected"<!-- END s_w_m -->>%h%CalMinutes%</option>
                    <option value="h"<!-- START s_w_h --> selected="selected"<!-- END s_w_h -->>%h%CalHours%</option>
                    <option value="d"<!-- START s_w_d --> selected="selected"<!-- END s_w_d -->>%h%CalDays%</option>
                    <option value="w"<!-- START s_w_w --> selected="selected"<!-- END s_w_w -->>%h%CalWeeks%</option>
                </select>
                <select size="1" name="warn_mode">
                    <option value="s"<!-- START s_w_s --> selected="selected"<!-- END s_w_s -->>%h%CalWarnBeforeStart%</option>
                    <option value="e"<!-- START s_w_e --> selected="selected"<!-- END s_w_e -->>%h%CalWarnBeforeEnd%</option>
                </select><br />
                %h%CalAdditionalAlert%:<br />
                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="l">%h%CalViaMailTo%</td>
                        <td class="l">
                            <input type="text" name="warn_mail" id="warnmail_txt" value="{warn_mail}" size="32" maxlength="255" />
                            <img src="{theme_path}/icons/combobox_activator.gif" id="warnmail_btn" alt="" style="vertical-align:bottom;cursor:pointer;" onclick="combo_active('warnmail_txt','warnmail_sel','warnmail_btn');" />
                            <select size="1" id="warnmail_sel" style="display:none;"><!-- START warnmail_profiles -->
                                <option>{email}</option><!-- END warnmail_profiles -->
                            </select>
                        </td>
                    </tr><!-- START external_alerting -->
                    <tr>
                        <td class="l">%h%CalViaSMSTo%</td>
                        <td class="l">
                            <input type="text" name="warn_sms" id="warnsms_txt" value="{warn_sms}" size="32" maxlength="255" />
                            <img src="{theme_path}/icons/combobox_activator.gif" id="warnsms_btn" alt="" style="vertical-align:bottom;cursor:pointer;" onclick="combo_active('warnsms_txt','warnsms_sel','warnsms_btn');" />
                            <select size="1" id="warnsms_sel" style="display:none;"><!-- START warnsms_profiles -->
                                <option>{sms}</option><!-- END warnsms_profiles -->
                            </select>
                        </td>
                    </tr><!-- END external_alerting -->
                </table>
                <br />
            </div>
        </fieldset>
        <br />
        <div style="float:right;padding:4px;">
            <strong id="result_message">{WP_return}</strong>&nbsp;&nbsp;
            <input type="submit" value="%h%save%" />
        </div>
    </div>
</form>