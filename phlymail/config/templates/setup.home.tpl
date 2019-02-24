<div align="left">
    {head_text}
    <br /><!-- START importlk -->
    <fieldset>
        <legend>{leg_import}</legend>
        <form action="{url_importlk}" method="post" enctype="multipart/form-data">
            {import}<br />
            <br />
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td align="left" valign="top"><strong>{msg_firstname}</strong></td>
                    <td align="left" valign="top"><input type="text" name="fname" value="{firstname}" size="32" /></td>
                </tr>
                <tr>
                    <td align="left" valign="top"><strong>{msg_surname}</strong></td>
                    <td align="left" valign="top"><input type="text" name="sname" value="{surname}" size="32" /></td>
                </tr>
                <tr>
                    <td align="left" valign="top"><strong>{msg_customer}</strong></td>
                    <td align="left" valign="top"><input type="text" name="customer" value="{customer}" size="32" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td align="left" valign="top">
                        <input type="file" name="licence_key2" />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td align="left" valign="top">
                        <textarea name="licence_key" rows="3" cols="24">{licence_key}</textarea>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td align="left"><input type="submit" value="{msg_save}" /></td>
                </tr>
            </table><br />
        </form>
    </fieldset>
    <br/><!-- END importlk --><!-- START checks -->
    <fieldset class="texterror">
        <legend>{leg_check}</legend>
        {msg_foundprob}<br />
        <ul><!-- START probline -->
            <li>{msg_descr}<br />
                <br />
                <strong>{msg_module}:</strong> <a href="{link_module}">{module}</a><br />
                <br />
            </li><!-- END probline -->
        </ul>
    </fieldset>
    <br /><!-- END checks -->
    <fieldset>
        <legend>{msg_general}</legend>
        {msg_currbuild}: <a href="{link_AU}" title="{msg_checkupd}">{curr_build}</a><br />
        <br />
        {msg_frontendis}: <a href="{link_fe_onoff}" title="{msg_chgstatus}">{msg_fe_active}</a><br />
    </fieldset>
    <br />
    <fieldset>
        <legend>{msg_users}</legend>
        <table border="0" cellpadding="2" cellspacing="0" width="100%">
            <colgroup>
                <col width="50%">
                <col width="20%">
                <col width="30%">
            </colgroup>
            <tr>
                <td align="right">{regusers}:</td>
                <td align="right">{users_all}</td>
                <td align="left"><!-- START search_all --><a href="{link_search_all}"><img src="{confpath}/icons/search.png" border="0" alt="{searchcrit}" /></a><!-- END search_all --></td>
            </tr>
            <tr>
                <td align="right">{msg_active}:</td>
                <td align="right">{users_active}</td>
                <td align="left"><!-- START search_active --><a href="{link_search_active}"><img src="{confpath}/icons/search.png" border="0" alt="{searchcrit}" /></a><!-- END search_active --></td>
            </tr>
            <tr>
                <td align="right">{msg_inactive}:</td>
                <td align="right">{users_inactive}</td>
                <td align="left"><!-- START search_inactive --><a href="{link_search_inactive}"><img src="{confpath}/icons/search.png" border="0" alt="{searchcrit}" /></a><!-- END search_inactive --></td>
            </tr>
            <tr>
                <td align="right">{msg_locked}:</td>
                <td align="right">{users_locked}</td>
                <td align="left"><!-- START search_locked --><a href="{link_search_locked}"><img src="{confpath}/icons/search.png" border="0" alt="{searchcrit}" /></a><!-- END search_locked --></td>
            </tr>
            <tr>
                <td align="right">{maxlicence}:</td>
                <td align="right">{users_max}</td>
                <td align="left" class="draw-usage-bar" data-usage-val="{users_all_raw}" data-usage-max="{users_max_raw}">
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <hr />
                </td>
            </tr>
            <tr>
                <td align="right">{msg_validto}:</td>
                <td align="right">{valid_to}</td>
                <td align="left">&nbsp;</td>
            </tr><!-- START has_provisioning -->
            <tr>
                <td colspan="3">
                    <hr />
                </td>
            </tr>
            <tr>
                <td align="right">%h%ProvisionedStorage%</td>
                <td align="right">{provisioned_storage}</td>
                <td align="left">&nbsp;</td>
            </tr>
            <tr>
                <td align="right">%h%UsageStorage%</td>
                <td align="right">{usage_storage}</td>
                <td align="left" class="draw-usage-bar" data-usage-val="{usage_storage_raw}" data-usage-max="{provisioned_storage_raw}">
                </td>
            </tr><!-- END has_provisioning -->
        </table>
        <br />
    </fieldset>
</div>