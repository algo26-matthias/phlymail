<script type="text/javascript">
// <![CDATA[
function upload_progress()
{
    $.ajax({url: '{upload_progress_url_js}{UL_ID}', success: AjaxProcess, dataType: 'json'});
}

function AjaxProcess(next)
{
    if (next['upload_stats']) upload_progress_draw(next['upload_stats']);
}

function upload_progress_draw(data)
{
    if (data['bytes_uploaded'] > 0 && data['bytes_total'] > 0) {
        if (data['bytes_uploaded'] >= data['bytes_total']) {
            document.getElementById('busy_fetching').style.visibility = 'hidden';
            document.getElementById('prgr_inner_busy').style.width = '100%';
            return;
        }
        pleasewait_off();
        data['speed_average'] = parseInt(data['speed_average']/1024*10)/10;
        var Hs = parseInt(data['est_sec']/3600);
        var Ms = parseInt((data['est_sec']+Hours*3600)/60);
        var Ss = data['est_sec']-(Mins*60 + Hours*3600);
        data['est_sec'] = Hs + ':' + (Ms < 10 ? '0' : '') + Ms + ':' + (Ss < 10 ? '0' : '') + Ss;
        document.getElementById('busy_fetching').style.visibility = 'visible';
        document.getElementById('prgr_inner_busy').style.width = (100*data['bytes_uploaded']/data['bytes_total']) + '%';
        document.getElementById('fetch_curr').innerHTML = parseInt(data['bytes_uploaded']/1024);
        document.getElementById('fetch_all').innerHTML = parseInt(data['bytes_total']/1024);
        document.getElementById('kb_p_sec').innerHTML = data['speed_average'];
        document.getElementById('time_remain').innerHTML = data['est_sec'];
    } else {
        pleasewait_on();
    }
    window.setTimeout('upload_progress();', 5000);
}

function pleasewait_on()
{
    $('#pleasewait').show();
}
function pleasewait_off()
{
    $('#pleasewait').hide();
}

function done()
{
    $('#onupload').hide();
    $('#default').show();

    if (opener.parent.parent.frames && opener.parent.parent.frames.PHM_tr) {
        try {
            opener.parent.parent.frames.PHM_tr.refreshlist();
        } catch (e) {
            opener.parent.parent.frames.PHM_tr.location.reload();
        }
    } else if (opener.frames && opener.frames.PHM_tr) {
        try {
            opener.frames.PHM_tr.refreshlist();
        } catch (e) {
            opener.frames.PHM_tr.location.reload();
        }
    } else {
        try { opener.refreshlist(); } catch (e) { opener.location.reload(); }
    }

    resizeMe();
}

function resizeMe()
{
    var wh = $(window).height();
    var dh = $(document).height();
    var ww = $(window).width();
    var dw = $(document).width();
    window.resizeBy(ww < dw ? dw-ww : 0, wh < dh ? dh-wh : 0);
}

$(document).ready(function() {
    resizeMe();
});
// ]]>
</script>

<div id="onupload" class="l"<!-- START default --> style="display:none;"<!-- END default -->>
    <br />
    {about_done}<br />
    <br />
    <div style="height:100px; text-align:center; vertical-align:middle;">
        <img src="{big_icon}" alt="{mimetype}" title="{mimetype}" style="margin:4px;" /><br />
        {name}<br />
        <button type="button" onclick="done();" />OK</button>
    </div>
</div>

<div style="width:370px;<!-- START onupload -->display:none;"<!-- END onupload -->" id="default" class="l">
    <form id="form" action="{action}" accept-charset="utf-8" enctype="multipart/form-data" method="post" onsubmit="pleasewait_on();">
    <fieldset>
        <legend>{leg_localfolder}</legend>
        {msg_filetofolder}<br />
        <div style="margin-top:4px;">
            <select size="1" id="destfolder" name="folder"><!-- START destfolder -->
                <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END destfolder -->
            </select>
        </div>
    </fieldset>
    <br />
    <fieldset>
        <legend>{leg_enterurl}</legend>
        {msg_select}:<br />
        <br />
        <input type="hidden" name="{UL_ID_NAME}" value="{UL_ID}" />
        <input size="48" type="text" name="get_from_url" /><br />
        <div id="busy_fetching" style="visibility:hidden;width:300px;margin:auto;">
            <div class="sendmenuborder" style="height:10px;">
                <div id="prgr_inner_busy" class="prgr_inner_busy"></div>
            </div>
            <span id="fetch_curr">0</span>/<span id="fetch_all">0</span>KB, <span id="kb_p_sec">0</span>KB/s,
            <span id="time_remain">0:00</span> ETA
        </div>
    </fieldset>
    <br />
    <fieldset>
        <legend>{leg_choosefile}</legend>
        <div>{about_choosefile}. {msg_maxfilesize}</div>
        <div style="margin-top:4px;">
            <input id="upload" name="file[]" type="file" multiple="multiple" /><!-- START maxfilesize -->
            <input type="hidden" name="MAX_FILE_SIZE" value="{maxfilesize}" /><!-- END maxfilesize -->
        </div>
    </fieldset>
    <br />
    <input type="submit" value="{msg_upload}" />
    </form>
    <div id="pleasewait" style="display:none;position:absolute;top:50px;width:100%;">
        <img src="{theme_path}/images/pleasewait.gif" style="display:block;margin:auto;padding:10px;z-index:200;" alt="..." />
    </div>
</div>