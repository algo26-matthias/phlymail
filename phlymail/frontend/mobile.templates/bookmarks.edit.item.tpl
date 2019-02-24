<form data-ajax="false" action="{save_url}" target="_self" method="post">
    <div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">    
        <div data-role="header" data-position="fixed">
            <h1>{pageTitle}</h1>
            <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content">
            <div data-role="fieldcontain">
                <label for="sel_group">{msg_group}</label>
                <select size="1" name="group" id="sel_group">
                    <option value="">{msg_root}</option><!-- START group_sel -->
                    <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END group_sel -->
                </select>
            </div>
            <div data-role="fieldcontain">
                <label for="inp_url">{msg_url}</label>
                <input type="text" name="url" id="inp_url" value="{url}" maxlength="1024">
            </div>
            <div data-role="fieldcontain">
                <label for="inp_name">{msg_name}</label>
                <input type="text" name="name" id="inp_name" value="{name}">
            </div>
            <div data-role="fieldcontain">
                <input type="checkbox" name="is_favourite" id="chk_favourite" value="1"<!-- START is_favourite --> checked="checked"<!-- END is_favourite --> />
                <label for="chk_favourite">{msg_is_favourite}</label>
            </div>
            <div data-role="fieldcontain">
                <label for="ta_desc">{msg_desc}</label>
                <textarea name="desc" id="ta_desc" rows="5" cols="80">{desc}</textarea>
            </div>
        </div>

        <div data-role="footer" class="ui-bar" data-position="fixed">
            <button type="submit" data-icon="check">%h%save%</button>
        </div>
    </div>
</form>