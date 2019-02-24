<div align="left" style="padding: 8px;"><!-- START return -->
<div align="center">
 <br />
 <span align="left"><strong>{return}</strong></span>
 <br />
</div><!-- END return -->
 <form action="{target}" method="POST" enctype="multipart/form-data" onsubmit="pleasewait_on();">
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
   <td align="left">{msg_format}:</td>
   <td align="left">
    <select name="imform" size="1">
     <option value="">--- {msg_select} ---</option><!-- START imoption -->
     <option value="{value}">{name}</option><!-- END imoption -->
    </select>
    <input type=hidden name="do" value="import">
    {passthrough}
   </td>
  </tr>
  <tr>
   <td align="left">{msg_file}:</td>
   <td align="left">
    <input type="file" name="imfile" size=32>
   </td>
  </tr>
  <tr>
   <td></td>
   <td align="left">
    <input type="submit" value="Go!">
   </td>
  </tr>
  </table>
 </fieldset>
 </form><br />
 <br /><!-- START export -->
 <form action="{target}" method="POST">
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
   <td align="left">{msg_format}:</td>
   <td align="left">
    <select name="exform" size=1>
     <option value="">--- {msg_select} ---</option><!-- START exoption -->
     <option value="{value}">{name}</option><!-- END exoption -->
    </select><br />
    <input type=hidden name="do" value="export">
    {passthrough}
   </td>
  </tr>
  <tr>
   <td></td>
   <td align="left">
    <input type="submit" value="Go!">
   </td>
  </tr>
  </table>
 </fieldset>
 </form><!-- END export -->
</div>