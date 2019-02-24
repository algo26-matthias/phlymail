<div id="page_setup_calendar" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <form action="{target_link}" method="post" data-ajax="false">
        <div data-role="header" data-position="fixed">
            <h1>%h%SetupCalendarLong%</h1>
            <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content">

            <input type="hidden" name="wd_start" id="wd_start" value="" />
            <input type="hidden" name="wd_end" id="wd_end" value="" />
            <h3>%h%CalWorkingDays%</h3>
            <div>%h%CalAboutWorkingDays%</div>

            <div data-role="fieldcontain">
                <fieldset data-role="controlgroup">
                    <legend>%h%CalWorkingDays%</legend><!-- START wd_checkbox -->
                    <label for="setup_calendar_weekday_{id}">{daytitle}</label>
                    <input type="checkbox" id="setup_calendar_weekday_{id}" name="wd[{id}]" value="1"<!-- START sel --> checked="checked"<!-- END sel -->><!-- END wd_checkbox -->
                </fieldset>
            </div>
            <div class="rangeslider">
                <label for="setup_calendar_workingtime_min">%h%CalWorkingTime%: <span id="setup_calendar_workingtime_human"> </span></label>
                <input type="range" name="buying_slider_min" id="setup_calendar_workingtime_min" class="rangeslider_min" value="{wd_start}" min="0" max="2350" step="50" />
                <input type="range" name="buying_slider_max" id="setup_calendar_workingtime_max" class="rangeslider_max" value="{wd_end}" min="0" max="2350" step="50" data-track-theme="b"/>
            </div>

            <hr>
            <h3>%h%CalDefaultView%</h3>
            <div>%h%CalAboutDefaultView%</div>

            <div data-role="fieldcontain">
                <label for="setup_calendar_viewmode">%h%CalDefaultView%</label>
                <select size="1" name="viewmode" id="setup_calendar_viewmode"><!-- START viewmode -->
                    <option value="{mode}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END viewmode -->
                </select>
            </div>

            <hr>
            <h3>%h%CalDefAlert%</h3>
            <div>%h%CalAboutDefAlert%</div>

            <input type="checkbox" id="setup_calendar_chk_warn" name="warn" value="1"<!-- START warn --> checked<!-- END warn --> />
                   <label for="setup_calendar_chk_warn">%h%CalWarnMe%</label>
            <div id="setup_calendar_warndisable">
                <input type="number" name="warn_time" value="{warn_time}" size="6" maxlength="6" />

                <input type="radio" name="warn_range" id="setup_calendar_chk_warn_range_m" value="m"<!-- START s_w_m --> checked<!-- END s_w_m -->>
                       <label for="setup_calendar_chk_warn_range_m">%h%CalMinutes%</label>
                <input type="radio" name="warn_range" id="setup_calendar_chk_warn_range_h" value="h"<!-- START s_w_h --> checked<!-- END s_w_h -->>
                       <label for="setup_calendar_chk_warn_range_h">%h%CalHours%</label>
                <input type="radio" name="warn_range" id="setup_calendar_chk_warn_range_d" value="d"<!-- START s_w_d --> checked<!-- END s_w_d -->>
                       <label for="setup_calendar_chk_warn_range_d">%h%CalDays%</label>
                <input type="radio" name="warn_range" id="setup_calendar_chk_warn_range_w" value="w"<!-- START s_w_w --> checked<!-- END s_w_w -->>
                       <label for="setup_calendar_chk_warn_range_w">%h%CalWeeks%</label>

                <input type="radio" name="warn_mode" id="setup_calendar_chk_warn_mode_s" value="s"<!-- START s_w_s --> checked<!-- END s_w_s -->>
                       <label for="setup_calendar_chk_warn_mode_s">%h%CalWarnBeforeStart%</label>
                <input type="radio" name="warn_mode" id="setup_calendar_chk_warn_mode_e" value="e"<!-- START s_w_e --> checked<!-- END s_w_e -->>
                       <label for="setup_calendar_chk_warn_mode_e">%h%CalWarnBeforeEnd%</label>

                <h5>%h%CalAdditionalAlert%</h5>

                <label for="setup_calendar_inp_warnmail">%h%CalViaMailTo%</label>
                <input type="email" name="warn_mail" id="setup_calendar_inp_warnmail" value="{warn_mail}" size="32" maxlength="255" />
                <select size="1" id="setup_calendar_sel_warnmail"><!-- START warnmail_profiles -->
                    <option>{email}</option><!-- END warnmail_profiles -->
                </select><!-- START external_alerting -->

                <label for="setup_calendar_inp_warnsms">%h%CalViaSMSTo%</label>
                <input type="tel" name="warn_sms" id="setup_calendar_inp_warnsms" value="{warn_sms}" size="32" maxlength="255" />
                <select size="1" id="setup_calendar_sel_warnsms"><!-- START warnsms_profiles -->
                    <option>{sms}</option><!-- END warnsms_profiles -->
                </select><!-- END external_alerting -->
            </div>

        </div>
        <div data-role="footer" class="ui-bar" data-position="fixed">
            <button type="submit" data-icon="check">%h%save%</button>
        </div>
    </form>
</div>