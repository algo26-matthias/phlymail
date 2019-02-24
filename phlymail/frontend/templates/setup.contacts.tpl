<script type="text/javascript">
$(document).ready(function() {
    adjust_height();

    $('#freefields-add-field').on('click', function (evt) {
        evt.preventDefault();
        evt.stopImmediatePropagation();

        $('#freefields-master-form').append('<input type="hidden" name="addfield" value="1"/>').submit();

        return false;
    });

    $('.freefields-delete-link').on('click', function (evt) {
        evt.preventDefault();
        evt.stopImmediatePropagation();
        var $this = $(this);
        if (!confirm('%j%FreeFieldReallyDeleteType%')) {
            return false;
        }

        $('#freefields-master-form').append('<input type="hidden" name="deletefield" value="' + $this.attr('data-id') + '"/>').submit();

        return false;
    });
});
//]]>
</script>
<form action="{target_link}" method="post" id="freefields-master-form">
    <div class="t l">
        <fieldset>
            <legend>%h%SetupFreeFields%</legend>
            <p>
                %h%AdbAboutSetupFreefields%
            </p>
            <p>
                <a href="#" id="freefields-add-field">%h%FreeFieldAddField%</a>
            </p>
            <table border="0" cellpadding="2" cellspacing="0">
                <thead>
                    <tr>
                        <th>%h%FreeFieldName%</th>
                        <th>%h%FreeFieldType%</th>
                        <th>%h%FreeFieldToken%</th>
                        <td>&nbsp;</td>
                    </tr>
                </thead>
                <tbody><!-- START freefieldline -->
                    <tr>
                        <td>
                            <input type="text" name="free[{id}][name]" value="{name}" size="24" maxlength="255" required>
                        </td>
                        <td>
                            <select size="1" name="free[{id}][type]">
                                <option value="text"{selected_text}>%h%FreeFieldTypeText%</option>
                                <option value="textarea"{selected_textarea}>%h%FreeFieldTypeTextarea%</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="free[{id}][token]" value="{token}" size="24" maxlength="255" pattern="^[a-z0-9]([a-z0-9-]+)$" required>
                        </td>
                        <td>
                            <a class="freefields-delete-link" href="#" data-id="{id}">%h%del%</a>
                        </td>
                    </tr><!-- END freefieldline -->
                </tbody>
            </table>
        </fieldset>
        <div style="float:right;padding:4px;">
            <strong id="result_message">{WP_return}</strong>&nbsp;&nbsp;
            <input type="submit" value="%h%save%" />
        </div>
    </div>
</form>