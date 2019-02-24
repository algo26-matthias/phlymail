<div style="padding:8px;text-align:left;"><!-- START return -->
    <div style="text-align:center;padding:0 0 8px 0;"><strong>{return}</strong></div><!-- END return --><!-- START import -->
    <form action="{target}" method="post" enctype="multipart/form-data">
        <fieldset style="width:400px;">
            <legend>{leg_import}</legend>
            {about_import}<br />
            <br />
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td>{msg_group}:</td>
                    <td>
                        <select name="imgroup" size="1">
                            <option value="0">&lt; {msg_none} &gt;</option><!-- START imgroup -->
                            <option value="{id}">{name}</option><!-- END imgroup -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="tdl">{msg_format}:</td>
                    <td class="tdl">
                        <select name="imform" size="1">
                            <option value="">--- {msg_select} ---</option><!-- START imoption -->
                            <option value="{value}">{name}</option><!-- END imoption -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="tdl">{msg_file}:</td>
                    <td class="tdl">
                        <input type="file" name="imfile" size="32" />
                    </td>
                </tr>
                <tr>
                    <td class="l">URL:</td>
                    <td class="l">
                        <input type="text" name="imurl" size="32" />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td class="tdl">
                        <fieldset>
                            <legend>{msg_csv_only}</legend>
                            <input type="checkbox" name="fieldnames" id="im_fieldnames" value="1" />
                            <label for="im_fieldnames">{msg_fieldnames}</label><br />
                            <input type="checkbox" name="is_quoted" id="im_quoted" value="1" />
                            <label for="im_quoted">{msg_csv_quoted}</label><br />
                            {msg_field_delimiter}:&nbsp;<input type="text" name="delimiter" value=";" size="1" maxlength="1" /><br />
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td class="l">
                        <input type="checkbox" id="chk_truncate" name="truncate" value="1" />
                        <label for="chk_truncate">{msg_truncate}</label>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td class="tdl">
                        <input type="submit" value="{msg_go}" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
    <br />
    <br /><!-- END import --><!-- START export -->
    <form action="{target}" method="post">
        <fieldset style="width:400px;">
            <legend>{leg_export}</legend>
            {about_export}<br />
            <br />
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td>{msg_group}:</td>
                    <td>
                        <select name="exgroup" size="1">
                            <option value="0">&lt; {msg_none} &gt;</option><!-- START exgroup -->
                            <option value="{id}">{name}</option><!-- END exgroup -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="tdl">{msg_format}:</td>
                    <td class="tdl">
                        <select name="exform" size=1>
                            <option value="">--- {msg_select} ---</option><!-- START exoption -->
                            <option value="{value}">{name}</option><!-- END exoption -->
                        </select><br />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td class="tdl">
                        <fieldset>
                            <legend>{msg_csv_only}</legend>
                            <input type="checkbox" name="fieldnames" id="ex_fieldnames" value="1" />
                            <label for="ex_fieldnames">{msg_fieldnames}</label><br />
                            <input type="checkbox" name="is_quoted" id="ex_quoted" value="1" />
                            <label for="ex_quoted">{msg_csv_quoted}</label><br />
                            {msg_field_delimiter}:&nbsp;<input type="text" name="delimiter" value=";" size="1" maxlength="1" /><br />
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td class="tdl">
                        <input type="submit" value="{msg_go}" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form><!-- END export -->
</div>