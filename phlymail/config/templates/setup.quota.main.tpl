<form action="{target_link}" method="post">
<div align="left"><!-- START error -->
 <br />
 <span class="texterror">{error}</span><br /><!-- END error -->
 <br />
 {about_quota}<br />
 <br /><!-- START handlerline -->
 <fieldset>
  <legend><strong>{handler}</strong></legend>
  <table border="0" cellpadding="2" cellspacing="0">
   <thead>
    <tr>
     <th>{head_crit}</th>
     <th colspan="2">{head_limit}</th>
    </tr>
   </thead>
   <tbody><!-- START critline -->
    <tr class="conttd">
     <td>{msg_crit}</td>
     <td><input type="text" name="{input}" class="r" value="{crit_limit}" size="8" maxlength="32" /></td>
     <td class="r">{crit_unit}</td>
    </tr>
    <tr class="conttd">
     <td colspan="3">
      <table border="0" cellpadding="2" cellspacing="0">
       <tr>
        <td>{msg_avguser}</td>
        <td>{crit_avg}</td>
        <td></td>
       </tr>
       <tr>
        <td>{msg_maxuser}</td>
        <td>{crit_max}</td>
        <td><a href="{link_maxuser}">{msg_showuser}</a></td>
       </tr>
      </table>
     </td>
    </tr>
    <tr>
     <td colspan="3">&nbsp;</td>
    </tr>
    <!-- END critline -->
   </tbody>
  </table>
 </fieldset><br />
 <br /><!-- END handlerline -->
 <input type="submit" value="{msg_save}" />
 </div>
 </form>