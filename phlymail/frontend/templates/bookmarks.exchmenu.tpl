<script type="text/javascript">
//<![CDATA[
function pleasewait_on()
{
    document.getElementById('pleasewait').style.display = 'block';
}

function pleasewait_off()
{
    document.getElementById('pleasewait').style.display = 'none';
}
// ]]>
</script>
<div class="l" style="padding: 8px;"><!-- START return -->
<div class="c t">
 <br />
 <span class="l"><strong>{return}</strong></span>
 <br />
</div><!-- END return --><!-- START import -->
<form action="{target}" method="post" enctype="multipart/form-data" onsubmit="pleasewait_on();">
 <fieldset style="width:400px;">
  <legend>{leg_import}</legend>
  {about_import}<br />
  <br />
  <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td>{msg_group}:</td>
   <td>
    <select name="imgroup" size="1">
     <option value="0"> --- </option><!-- START imgroup -->
     <option value="{id}">{name}</option><!-- END imgroup -->
    </select>
   </td>
  </tr>
  <tr>
   <td class="l">{msg_format}:</td>
   <td class="l">
    <select name="imform" size="1">
     <option value="">--- {msg_select} ---</option><!-- START imoption -->
     <option value="{value}">{name}</option><!-- END imoption -->
    </select>
    <input type="hidden" name="do" value="import" />
    {passthrough}
   </td>
  </tr>
  <tr>
   <td class="l">{msg_file}:</td>
   <td class="l">
    <input type="file" name="imfile" size="32" />
   </td>
  </tr>
  <tr>
   <td></td>
   <td class="l">
    <input type="submit" value="Go!" />
   </td>
  </tr>
  </table>
 </fieldset>
</form><br />
<br /><!-- END import --><!-- START export -->
<form action="{target}" method="post">
 <fieldset style="width:400px;">
  <legend>{leg_export}</legend>
  {about_export}<br />
  <br />
  <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td>{msg_group}:</td>
   <td>
    <select name="imgroup" size="1">
     <option value="0"> --- </option><!-- START exgroup -->
     <option value="{id}">{name}</option><!-- END exgroup -->
    </select>
   </td>
  </tr>
  <tr>
   <td class="l">{msg_format}:</td>
   <td class="l">
    <select name="exform" size=1>
     <option value="">--- {msg_select} ---</option><!-- START exoption -->
     <option value="{value}">{name}</option><!-- END exoption -->
    </select><br />
    <input type="hidden" name="do" value="export" />
    {passthrough}
   </td>
  </tr>
  <tr>
   <td></td>
   <td class="l">
    <input type="submit" value="Go!" />
   </td>
  </tr>
  </table>
 </fieldset>
</form><!-- END export -->
</div>
<div id="pleasewait" style="display:none;position:absolute;top:50px;width:100%;">
    <img src="{theme_path}/images/pleasewait.gif" style="display:block;margin:auto;padding:10px;z-index:200;" alt="..." />
</div>
</div>