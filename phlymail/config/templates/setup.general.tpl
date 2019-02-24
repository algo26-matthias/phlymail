    <form action="{target_link}" method="post"><!-- START return -->
        <strong>{WP_return}</strong><br />
        <br /><!-- END return -->
        %h%SuHeadGen%<br />
        <br />
        <fieldset>
            <legend>%h%general%</legend>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td align="left">%h%optlang%:</td>
                    <td align="left">
                        <select name="WP_newlang"><!-- START langline -->
                            <option value="{key}"<!-- START sel --> selected<!-- END sel -->>{langname}</option><!-- END langline -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="left">%h%Timezone%:</td>
                    <td align="left">
                        <select name="timezone"><!-- START timezone -->
                            <option value="{key}"<!-- START sel --> selected<!-- END sel -->>{val}</option><!-- END timezone -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="left">%h%optskin%:</td>
                    <td align="left">
                        <select name="WP_newskin"><!-- START skinline -->
                            <option value="{key}"<!-- START sel --> selected<!-- END sel -->>{skinname}</option><!-- END skinline -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="left">%h%MobileTheme%:</td>
                    <td align="left">
                        <select name="mobile_theme"><!-- START mobskinline -->
                            <option value="{key}"<!-- START sel --> selected<!-- END sel -->>{skinname}</option><!-- END mobskinline -->
                        </select>
                    </td>
                </tr>
            </table><br />
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%LegendMobile%</legend>
            <div>
                <label>
                    <input type="checkbox" name="mobile_advertise" value="1"<!-- START mobile_advertise --> checked<!-- END mobile_advertise --> />
                    %h%AdvertiseMobile%
                </label>
            </div>            
            <div>
                <label>
                    <input type="checkbox" name="mobile_autodetect" value="1"<!-- START mobile_autodetect --> checked<!-- END mobile_autodetect --> />
                    %h%AutoDetectMobile%
                </label>
            </div>            
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%LegReceipt%</legend>
            <div>
                <label>
                    <input type="checkbox" name="WP_newreceiptout" value="1"<!-- START receipt --> checked<!-- END receipt --> />
                    %h%optreceipt%
                </label>
            </div>
            <p>
                %h%AboutReceipt%
            </p>
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%LegWrap%</legend>
            <div>
                <label>
                    <input type="checkbox" name="WP_newsendwordwrap" value="1"<!-- START wordwrap --> checked<!-- END wordwrap --> />
                    %h%optwrap%
                </label>
            </div>
            <p>
                %h%AboutWrap%
            </p>
        </fieldset>
        <br />
        <fieldset>
            <legend>%h%LegURIs%</legend>
            <p>
                %h%AboutURIs%
            </p>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td align="left">%h%URIlogout%:</td>
                    <td align="left">
                        <input type="text" size="48" maxlength="1024" name="logout_uri" value="{logout_uri}" />
                    </td>
                </tr>
                <tr>
                    <td align="left">%h%URIfailed%:</td>
                    <td align="left">
                        <input type="text" size="48" maxlength="1024" name="failed_uri" value="{failed_uri}" />
                    </td>
                </tr>
            </table>
            <br />
        </fieldset>
        <br />
        <input type="submit" value="%h%save%">
    </form>