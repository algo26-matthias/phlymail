<script type="text/javascript">
/*<![CDATA[*/
function upload_progress()
{
    $.ajax({url:'{upload_progress_url_js}{UL_ID}', success: AjaxProcess, dataType:'json'});
}
function AjaxProcess(next)
{
    if (next['upload_stats']) upload_progress_draw(next['upload_stats']);
}
function upload_progress_draw(data)
{
    if (data['bytes_uploaded'] > 0 && data['bytes_total'] > 0) {
        if (data['bytes_uploaded'] >= data['bytes_total']) {
            $('#busy_fetching').css('visibility', 'hidden');
            $('#prgr_inner_busy').css('width', '100%');
            return;
        }
        pleasewait_off();
        data['speed_average'] = parseInt(data['speed_average']/1024*10)/10;
        var Hs = parseInt(data['est_sec']/3600);
        var Ms = parseInt((data['est_sec']+Hours*3600)/60);
        var Ss = data['est_sec']-(Mins*60 + Hours*3600);
        data['est_sec'] = Hs + ':' + (Ms < 10 ? '0' : '') + Ms + ':' + (Ss < 10 ? '0' : '') + Ss;
        $('#busy_fetching').css('visibility', 'visible');
        $('#prgr_inner_busy').css('width', (100*data['bytes_uploaded']/data['bytes_total']) + '%');
        $('#fetch_curr').text(parseInt(data['bytes_uploaded']/1024));
        $('#fetch_all').text(parseInt(data['bytes_total']/1024));
        $('#kb_p_sec').text(data['speed_average']);
        $('#time_remain').text(data['est_sec']);
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
/*]]>*/
</script>
<div>
 <form action="{action}&amp;opener={opener}" method="post" enctype="multipart/form-data" onsubmit="pleasewait_on();upload_progress();">
 <div id="onupload" class="l"<!-- START default --> style="display: none;"<!-- END default -->>
 <script type="text/javascript">
 /*<![CDATA[*/<!-- START jsforparent -->
 window.parent.addattach('{name}', '{filename}', '{small_icon}', 'user', '{mimetype}');<!-- END jsforparent -->
 function done()
 {
     $('#onupload').hide();
     $('#default').show();
 }
 /*]]>*/
 </script>
 <br />
 {about_done}<br />
 <br />
 <div style="height:100px;text-align:center;vertical-align:middle;">
 <img src="{big_icon}" alt="{mimetype}" title="{mimetype}" style="margin:4px;" /><br />
 {name}<br />
 <button type="button" onclick="done();" />OK</button>
 </div>
</div>
<div id="default" class="l"<!-- START onupload --> style="display:none;"<!-- END onupload -->>
 <br />
 {msg_select}:<br />
 <br />
 <input type="hidden" name="{UL_ID_NAME}" value="{UL_ID}" /><!-- START maxfilesize -->
 <input type="hidden" name="MAX_FILE_SIZE" value="{maxfilesize}" /><!-- END maxfilesize -->
 <input size="32" type="file" name="file[]" multiple="multiple" /><br />
 <div>{msg_maxfilesize}</div>
  <div id="busy_fetching" style="visibility:hidden;width:300px;margin:auto;">
   <div class="sendmenuborder" style="height:10px;">
    <div id="prgr_inner_busy" class="prgr_inner_busy"></div>
   </div>
   <span id="fetch_curr">0</span>/<span id="fetch_all">0</span>KB, <span id="kb_p_sec">0</span>KB/s,
   <span id="time_remain">0:00</span> ETA
  </div>
 <br />
 <input type="submit" value="{msg_upload}" />
 </div>
 </form>
 <div id="pleasewait" style="display:none;position:absolute;top:50px;width:100%;">
    <img src="{theme_path}/images/pleasewait.gif" style="display:block;margin:auto;padding:10px;z-index:200;" alt="..." />
 </div>
</div>