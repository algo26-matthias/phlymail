<script type="text/javascript">
/*<![CDATA[*/
function done()
{
    self.close();
}<!-- START done -->
done();<!-- END done -->
/*]]>*/
</script>
<form action="{baseurl}" method="post">
    <div class="l">
        <div style="height:164px;text-align:center;vertical-align:middle;">
            <img src="{big_icon}" alt="{mimetype}" title="{mimetype}" style="margin:4px;" /><br />
            <input type="text" name="override_name" size="64" value="{name}" style="width:95%;" />
        </div>
        <div style="width:95%;margin:auto;">
            {msg_filetofolder}<br />
            <select size="1" id="destfolder" name="destfolder"><!-- START destfolder -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END destfolder -->
            </select>
            <br />
            <br />
            <button class="error" style="float:right;" id="browse_submit" type="button" onclick="done();">{msg_cancel}</button>
            <input type="submit" class="ok" value="{msg_ok}" />
        </div>
    </div>
</form>
