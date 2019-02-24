<p>
    %h%SuHeadDB%
</p><!-- START error --><br>
<p>
    <strong>{error}</strong>
</p><!-- END error -->
<form action="{target_link}" method="post">
    <strong>%h%SuDBCurrDrvr%</strong>&nbsp;<!-- START one_no_driver -->{output}
    <!-- END one_no_driver --><!-- START drivermenu -->
    <select class="input" name="new_driver" size=1><!-- START menuline -->
        <option value="{key}"<!-- START selected --> selected="selected"<!-- END selected -->>{drivername}</option><!-- END menuline -->
    </select>&nbsp;<input class="input" type=submit value="{msg_save}"><br /><!-- END drivermenu -->
</form>
<hr />
<form action="{link_base}driver&amp;save=1" method="post">
    <fieldset>
        <legend><strong>%h%settings%</strong></legend>
        {conf_output}
        <p>
            <input type="submit" value="%h%save%" />
        </p>
    </fieldset>
</form>