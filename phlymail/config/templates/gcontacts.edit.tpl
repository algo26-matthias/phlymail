<div id="edit" align="left">
<script type="text/javascript">
<!--
function adjust_height()
{
    var availh = (document.getElementById('edit').offsetHeight) + 10;
    var availw = (document.getElementById('edit').offsetWidth);
    // Get the available Window height
    if (window.innerHeight) {
        window.innerHeight = availh;
    } else {
        window.resizeTo(availw, availh);
    }
}

function delete_event()
{
    state = confirm('{msg_askdele}');
    if (state == true) {
        document.getElementById('saveframe').src = '{delete_link}';
    }
}

function save_event()
{<!-- START may_edit -->
    url = '{form_target}';<!-- END may_edit --><!-- START no_edit -->
    url = '#';
    return false;<!-- END no_edit -->
    document.getElementById('busy').style.display = 'block';
    document.forms['formular'].action = url;
    document.forms['formular'].submit();
    return false;
}

function process(responseText)
{
    document.getElementById('busy').style.display = 'none';
    // alert(responseText);
    eval('next = ' + responseText);
    if (next['error']) {
        alert(next['error']);
    }
    if (next['done']) {
        done();
    }
}

function done()
{
    if (opener) {
        opener.location.reload();
    }
    self.close();
}

window.onload = adjust_height
// -->
</script>
<form name="formular" action="#" target="saveframe" method="post" onsubmit="return save_event();" enctype="multipart/form-data">
<div>
<div style="width:50%;float:left;">
<div style="padding:4px;text-align:left;">
<fieldset>
 <legend>{leg_general}</legend>
<table border="0" cellpadding="2" cellspacing="0">
   <tr>
    <td class="t l"><strong>{msg_group}:</strong></td>
    <td class="t l">
     <select size="3" name="gid[]" multiple="multiple"><!-- START groupline -->
      <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END groupline -->
     </select>
    </td>
   </tr>
 <tr>
  <td><strong>{msg_nick}</strong></td>
  <td><input type="text" size="32" name="nick" value="{nick}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_title}</strong></td>
  <td><input type="text" size="32" name="title" value="{title}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_fnam}</strong></td>
  <td><input type="text" size="32" name="firstname" value="{firstname}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_thirdname}</strong></td>
  <td><input type="text" size="32" name="thirdname" value="{thirdname}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_lnam}</strong></td>
  <td><input type="text" size="32" name="lastname" value="{lastname}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_email1}</strong></td>
  <td><input type="text" size="32" name="email1" value="{email1}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_email2}</strong></td>
  <td><input type="text" size="32" name="email2" value="{email2}" /></td>
 </tr>
 <tr>
  <td><strong>{msg_bday}</strong></td>
  <td>
   <select name="birthday_day" size="1"><!-- START bday_dayline -->
    <option value="{day}"<!-- START selected --> selected="selected"<!-- END selected -->>{day}</option><!-- END bday_dayline -->
   </select>&nbsp;
   <select name="birthday_month" size="1"><!-- START bday_monthline -->
    <option value="{month}"<!-- START selected --> selected="selected"<!-- END selected -->>{month}</option><!-- END bday_monthline -->
   </select>&nbsp;
   <input type="text" size="4" name="birthday_year" value="{birthday_year}" />
   &nbsp;{msg_bday_format}
   </td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>{msg_comment}</strong></td>
  <td><textarea rows="4" cols="28" name="comments">{comments}</textarea></td>
 </tr>
</table>
</fieldset><br />
<br />
 <fieldset>
  <legend>{leg_image}</legend>
  <div style="float:right;margin: 4px 4px; width:120px;"><!-- START ifimage -->
  <img src="{imgurl}" alt="" style="display:block;width:{imgw}px;height:{imgh}px;" /><!-- END ifimage --><!-- START delimage -->
  <br />
  <input type="checkbox" name="delimage" value="1" id="delimage" />
  <label for="delimage">{msg_delimage}</label><!-- END delimage -->
  </div>
  {msg_uploadimage}:<br />
  <input type="file" name="image" size="16" /><br />
  {msg_restrictions}<br />
 </fieldset>
</div>
</div>
<div style="width:50%;float:right;">
<div style="padding:4px;text-align:left;">
<fieldset>
 <legend>{leg_personal}</legend>
 <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td align="left" valign="top"><strong>{msg_addr}</strong></td>
   <td>
    <input type="text" size="32" name="address" value="{address}" /><br />
    <input type="text" size="32" name="address2" value="{address2}" style="margin-top: 2px" />
   </td>
  </tr>
  <tr>
   <td><strong>{msg_street}</strong></td>
   <td><input type="text" size="32" name="street" value="{street}" /></td>
  </tr>
  <tr>
   <td><strong>{msg_zip} / {msg_location}</strong></td>
   <td>
      <input type="text" size="14" name="zip" value="{zip}" />
    / <input type="text" size="14" name="location" value="{location}" />
   </td>
  </tr>
  <tr>
   <td><strong>{msg_region} / {msg_country}</strong></td>
   <td>
      <input type="text" size="14" name="region" value="{region}" />
    / <input type="text" size="14" name="country" value="{country}" />
   </td>
  </tr>
   <tr>
    <td><strong>{msg_fon}</strong></td>
    <td><input type="text" size="32" name="tel_private" value="{tel_private}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_fax}</strong></td>
    <td><input type="text" size="32" name="fax" value="{fax}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_cell}</strong></td>
    <td><input type="text" size="32" name="cellular" value="{cellular}" /></td>
   </tr>
 <tr>
  <td><strong>{msg_www}</strong></td>
  <td><input type="text" size="32" name="www" value="{www}" /></td>
 </tr>
 </table>
</fieldset><br />
<br />
<fieldset>
 <legend>{leg_business}</legend>
  <table border="0" cellpadding="2" cellspacing="0">
   <tr>
    <td><strong>{msg_CustomerNumber}</strong></td>
    <td><input type="text" size="32" name="customer_number" value="{customer_number}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_company}</strong></td>
    <td><input type="text" size="32" name="company" value="{company}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_department}</strong></td>
    <td><input type="text" size="32" name="comp_dep" value="{comp_dep}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_role}</strong></td>
    <td><input type="text" size="32" name="comp_role" value="{comp_role}" /></td>
   </tr>
   <tr>
    <td class="t l"><strong>{msg_addr}</strong></td>
    <td>
     <input type="text" size="32" name="comp_address" value="{comp_address}" /><br />
     <input type="text" size="32" name="comp_address2" value="{comp_address2}" style="margin-top: 2px" />
    </td>
   </tr>
   <tr>
    <td><strong>{msg_street}</strong></td>
    <td><input type="text" size="32" name="comp_street" value="{comp_street}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_zip} / {msg_location}</strong></td>
    <td>
      <input type="text" size="14" name="comp_zip" value="{comp_zip}" />
     / <input type="text" size="14" name="comp_location" value="{comp_location}" />
    </td>
   </tr>
   <tr>
    <td><strong>{msg_region} / {msg_country}</strong></td>
    <td>
      <input type="text" size="14" name="comp_region" value="{comp_region}" />
     / <input type="text" size="14" name="comp_country" value="{comp_country}" />
    </td>
   </tr>
   <tr>
    <td><strong>{msg_fon2}</strong></td>
    <td><input type="text" size="32" name="tel_business" value="{tel_business}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_fax}</strong></td>
    <td><input type="text" size="32" name="comp_fax" value="{comp_fax}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_cell}</strong></td>
    <td><input type="text" size="32" name="comp_cellular" value="{comp_cellular}" /></td>
   </tr>
   <tr>
    <td><strong>{msg_www}</strong></td>
    <td><input type="text" size="32" name="comp_www" value="{comp_www}" /></td>
   </tr>
  </table>
</fieldset>
</div>
</div>
</div>
<div style="clear:both;padding:8px;">
<div style="width:50%;float:left;text-align:left"><!-- START save_button -->
 <input type="submit" value="{msg_save}" />&nbsp;
 <img id="busy" src="{skin_path}/icons/busy.gif" style="visibility:hidden" alt="" title="Please wait" /><!-- END save_button -->
</div>
<div style="width:50%;float:right;text-align:right"><!-- START delete_button -->
   <input type="button" onclick="delete_event()" class="error" value="{msg_dele}" /><!-- END delete_button -->
</div>
</div>
</form>
<iframe src="" id="saveframe" name="saveframe" style="visibility:hidden;width:1px;height:1px"></iframe><br />
</div>