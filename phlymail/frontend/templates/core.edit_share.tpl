<div id="core_edit_shares">
    <form action="{form_url}" method="post">
        <div id="tabpane" class="ui-tabpane" style="height:450px;">
            <ul>
                <li><a href="#groups"><span>%h%Groups%</span></a></li>
                <li><a href="#users"><span>%h%Users%</span></a></li>
            </ul>
            <div id="groups" style="max-height:420px;overflow:auto;-moz-box-sizing:border-box;">
                <p>%h%AboutGroupShares%</p>
                <table border="0" cellpadding="2" cellspacing="0" style="width:99%;">
                    <thead>
                        <tr>
                            <th class="l">%h%Group%</th>
                            <th><span title="%h%PermMayAll%" class="perm_icon mayall"></span></th>
                            <th><span title="%h%PermMayRead%" class="perm_icon mayread"></span></th>
                            <th><span title="%h%PermMayWrite%" class="perm_icon maywrite"></span></th>
                            <th><span title="%h%PermMayDelete%" class="perm_icon maydelete"></span></th>
                            <!-- th><span title="%h%PermMayAddChild%" class="perm_icon mayaddchild forfolder"></span></th -->
                            <!-- th><span title="%h%PermMayDeleteChild%" class="perm_icon maydelchild forfolder"></span></th -->
                        </tr>
                    </thead>
                    <tbody><!-- START groupline -->
                        <tr>
                            <td>{groupname}</td>
                            <td><input type="checkbox" name="gshare[{gid}][all]" class="mayall" value="1"<!-- START chk_all --> checked="checked"<!-- END chk_all --> /></td>
                            <td><input type="checkbox" name="gshare[{gid}][read]" class="mayread" value="1"<!-- START chk_read --> checked="checked"<!-- END chk_read --> /></td>
                            <td><input type="checkbox" name="gshare[{gid}][write]" class="maywrite" value="1"<!-- START chk_write --> checked="checked"<!-- END chk_write --> /></td>
                            <td><input type="checkbox" name="gshare[{gid}][delete]" class="maydelete" value="1"<!-- START chk_delete --> checked="checked"<!-- END chk_delete --> /></td>
                            <!-- td><input type="checkbox" name="gshare[{gid}][newfolder]" class="mayaddchild" value="1"<!-- START chk_addchild --> checked="checked"<!-- END chk_addchild --> /></td -->
                            <!-- td><input type="checkbox" name="gshare[{gid}][deleitems]" class="maydelchild" value="1"<!-- START chk_delchild --> checked="checked"<!-- END chk_delchild --> /></td -->
                        </tr><!-- END groupline -->
                    </tbody>
                </table>
            </div>
            <div id="users" style="max-height:420px;overflow:auto;-moz-box-sizing:border-box;">
                <p>%h%AboutUserShares%</p>
                <table border="0" cellpadding="2" cellspacing="0" style="width:99%;">
                    <thead>
                        <tr>
                            <th class="l">%h%User%</th>
                            <th><span title="%h%PermMayAll%" class="perm_icon mayall"></span></th>
                            <th><span title="%h%PermMayRead%" class="perm_icon mayread"></span></th>
                            <th><span title="%h%PermMayWrite%" class="perm_icon maywrite"></span></th>
                            <th><span title="%h%PermMayDelete%" class="perm_icon maydelete"></span></th>
                            <!-- th><span title="%h%PermMayAddChild%" class="perm_icon mayaddchild forfolder"></span></th -->
                            <!-- th><span title="%h%PermMayDeleteChild%" class="perm_icon maydelchild forfolder"></span></th -->
                        </tr>
                    </thead>
                    <tbody><!-- START userline -->
                        <tr>
                            <td>{username}</td>
                            <td><input type="checkbox" name="ushare[{uid}][all]" class="mayall" value="1"<!-- START chk_all --> checked="checked"<!-- END chk_all --> /></td>
                            <td><input type="checkbox" name="ushare[{uid}][read]" class="mayread" value="1"<!-- START chk_read --> checked="checked"<!-- END chk_read --> /></td>
                            <td><input type="checkbox" name="ushare[{uid}][write]" class="maywrite" value="1"<!-- START chk_write --> checked="checked"<!-- END chk_write --> /></td>
                            <td><input type="checkbox" name="ushare[{uid}][delete]" class="maydelete" value="1"<!-- START chk_delete --> checked="checked"<!-- END chk_delete --> /></td>
                            <!-- td><input type="checkbox" name="ushare[{uid}][newfolder]" class="mayaddchild" value="1"<!-- START chk_addchild --> checked="checked"<!-- END chk_addchild --> /></td -->
                            <!-- td><input type="checkbox" name="ushare[{uid}][deleitems]" class="maydelchild" value="1"<!-- START chk_delchild --> checked="checked"<!-- END chk_delchild --> /></td -->
                        </tr><!-- END userline -->
                    </tbody>
                </table>
            </div>
        </div>
        <p />
        <div id="footerbar">
            <button type="button" class="error" id="cancel">%h%cancel%</button>
            <button type="submit" class="ok">%h%save%</button>
        </div>
    </form>
</div>
<script type="text/javascript">
//<![CDATA[
$('#tabpane').tabs().tabs('select', 0);
$('input.mayall').on('click change', function() {
    var $this = $(this);
    var $tr = $this.closest('tr');
	if ($this.is(':checked')) {
        $('input:checkbox', $tr).each(function () {
            if ($(this).is(':checked')) {
				return true;
            }
            if ($(this).hasClass('mayall')) {
                return true;
            }
            $(this).trigger('click');
        });
	} else {
	    $('input:checkbox:checked', $tr).each(function () {
            if ($(this).hasClass('mayall')) {
                return true;
            }
            $(this).trigger('click');
        });
    }
});
// ]]>
</script>