<script type="text/javascript">
/*<![CDATA[*/
function discard_event()
{
    parent.calendar_discardevent('{reminder_id}');
    parent.float_close('alertevent_' + {reminder_id}, 'alertevent');
}

function edit_event()
{
    parent.window.open
            ('{edit_url}'
            ,'editevent_{event_id}'
            ,'width=350,height=420,left=100,top=100,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
    parent.float_close('alertevent_' + {reminder_id}, 'alertevent');
}

function repeat_alert()
{
    parent.calendar_repeatevent('{reminder_id}');
    parent.float_close('alertevent_' + {reminder_id}, 'alertevent');
}
/*]]>*/
</script>
<div id="outer" style="text-align:left;vertical-align:top;">
 <div style="float:right;vertical-align:top;margin-left:4px;height:100%;">
  <button type="button" onclick="discard_event()">{msg_close}</button><br />
  <br />
  <button type="button" onclick="edit_event()">{msg_edit}</button><br />
  <br />
  <button type="button" onclick="repeat_alert()">{msg_reschedule}</button><br />
  <br />
 </div>
 <strong>{title}</strong><br />
 {start_end}<br />
 {location}<br />
 <strong>{reminder_text}</strong><br />
 {description}<br />
 <br />
</div>