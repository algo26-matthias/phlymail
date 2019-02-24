<script type="text/javascript"><!-- START success -->
$(document).ready(function() {
    window.close();
});<!-- END success -->
</script>
<div class="l">
    <form action="{save_url}" method="post">
        <table border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td class="t l">
                    <label for="sel_group">{msg_group}</label>
                </td>
                <td class="t l">
                    <select size="1" name="group" id="sel_group">
                        <option value="">{msg_root}</option><!-- START group_sel -->
                        <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END group_sel -->
                    </select>
                </td>
            </tr>
            <tr>
                <td class="t l">
                    <label for="inp_url">{msg_url}</label>
                </td>
                <td class="t l">
                    <input type="text" name="url" id="inp_url" value="{url}" size="64" maxlength="1024" style="width:95%" />
                </td>
            </tr>
            <tr>
                <td class="t l">
                    <label for="inp_name">{msg_name}</label>
                </td>
                <td class="t l">
                    <input type="text" name="name" id="inp_name" value="{name}" size="64" maxlength="255" style="width:95%" />
                </td>
            </tr>
            <tr>
                <td class="t l">&nbsp;</td>
                <td class="t l">
                    <input type="checkbox" name="is_favourite" id="chk_favourite" value="1"<!-- START is_favourite --> checked="checked"<!-- END is_favourite --> />
                    <label for="chk_favourite">{msg_is_favourite}</label>
                </td>
            </tr>
            <tr>
                <td class="t l">
                    <label for="ta_desc">{msg_desc}</label><br />
                </td>
                <td class="t l">
                    <textarea name="desc" id="ta_desc" rows="5" cols="80" style="width:95%">{desc}</textarea>
                </td>
            </tr>
            <tr>
                <td class="t l">&nbsp;</td>
                <td class="t l">
                    <input type="submit" value="{msg_save}" />
                </td>
            </tr>
        </table>
    </form>
</div>