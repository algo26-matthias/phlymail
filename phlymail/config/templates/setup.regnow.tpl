<div align="left">
    <form action="{target_link}" method="POST">
        {head_text}<br />
        <br />
        <strong>{WP_return}</strong><br />
        <br />
        <fieldset>
            <legend>{LegRegNow}</legend>
            <input type="checkbox" name="WP_registershow" id="lbl_regshow" value="1"<!-- START regshow --> checked<!-- END regshow --> />
            <label for="lbl_regshow">&nbsp;{msg_registershow}</label><br />
            <br />
            {msg_regsystmail}<br />
            <input type="text" size="48" name="WP_systememail" value="{systememail}"><br />
            <br />
            {msg_defaultgroups}<br />
            <select size="5" multiple="multiple" name="groups[]"><!-- START groupline -->
                <option value="{gid}"<!-- START sel --> selected="selected"<!-- END sel -->>{gname}</option><!-- END groupline -->
            </select>
        </fieldset>
        <br />
        <fieldset>
            <legend>{LegMailUser}</legend>
            <input type="checkbox" name="WP_mailuser" id="lbl_mailuser" value="1"<!-- START mailuser --> checked<!-- END mailuser --> />
            <label for="lbl_mailuser">&nbsp;{msg_regmailuser}</label><br />
            <br />
            {msg_subject}:&nbsp;<input type="text" size="48" name="WP_mailusersubj" value="{reg_mailuser_subj}" /><br />
            <br />
            {msg_regmailusertext}<br />
            <textarea name="WP_regmailusertext" rows="4" cols="64" >{regmailusertext}</textarea>
        </fieldset>
        <br />
        <fieldset>
            <legend>{LegMailAdm}</legend>
            <input type="checkbox" name="WP_mailadm" id="lbl_mailadm" value="1"<!-- START mailadm --> checked<!-- END mailadm --> />
            <label for="lbl_mailadm">&nbsp;{msg_regmailadm}</label><br />
            <br />
            {msg_subject}:&nbsp;<input type="text" size="48" name="WP_mailadmsubj" value="{reg_mailadm_subj}" /><br />
            <br />
            {msg_mailadmtext}<br />
            <textarea name="WP_regmailadmtext" rows="4" cols="64">{regmailadmtext}</textarea>
        </fieldset>
        <br />
        <input type="submit" value="{msg_save}">
    </form>
</div>