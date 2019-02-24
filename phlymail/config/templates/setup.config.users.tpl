<!-- START overview -->
<div align="left">{head_text}<br /></div>
<!-- START return --><strong>{WP_return}</strong><br /><!-- END return -->
<br />
<table border="0" cellpadding="2" cellspacing="0">
 <tr>
  <td align="right">{regadmins}:</td>
  <td align="right">{users_all}</td>
  <td align="left"><!-- START search_all --><a href="{link_search_all}"><img src="{confpath}/icons/search.png" border="0" alt="" title="{searchcrit}" /></a><!-- END search_all --></td>
 </tr>
 <tr>
  <td align="right">{msg_active}:</td>
  <td align="right">{users_active}</td>
  <td align="left"><!-- START search_active --><a href="{link_search_active}"><img src="{confpath}/icons/search.png" border="0" alt="" title="{searchcrit}" /></a><!-- END search_active --></td>
 </tr>
 <tr>
  <td align="right">{msg_inactive}:</td>
  <td align="right">{users_inactive}</td>
  <td align="left"><!-- START search_inactive --><a href="{link_search_inactive}"><img src="{confpath}/icons/search.png" border="0" alt="" title="{searchcrit}" /></a><!-- END search_inactive --></td>
 </tr>
 <tr>
  <td align="right">{msg_locked}:</td>
  <td align="right">{users_locked}</td>
  <td align="left"><!-- START search_locked --><a href="{link_search_locked}"><img src="{confpath}/icons/search.png" border="0" alt="" title="{searchcrit}" /></a><!-- END search_locked --></td>
 </tr>
</table><br />
<form action="{search_target}" method="post">
<div align="left">
 {msg_finduser}:&nbsp;
 <input type="text" name="search" value="{search}" title="{msg_title}" size=16 maxlength=32 />
 &nbsp;
 <select name="criteria" size="1">
  <option <!-- START sel_crit_all -->selected <!-- END sel_crit_all -->value="all">{msg_all}</option>
  <option <!-- START sel_crit_active -->selected <!-- END sel_crit_active -->value="active">{msg_active}</option>
  <option <!-- START sel_crit_inactive -->selected <!-- END sel_crit_inactive -->value="inactive">{msg_inactive}</option>
  <option <!-- START sel_crit_locked -->selected <!-- END sel_crit_locked -->value="locked">{msg_locked}</option>
 </select>
 &nbsp;
 <input type="submit" value="{msg_find}" /><br />
</div>
</form>
<br />
<table border="0" cellpadding="0" cellspacing="0" width="400"><!-- START menu_ow -->
<tr>
 <td align="left" class="contthleft"><strong>ID</strong></td>
 <td align="left" class="contthmiddle"><strong>{username}</strong></td>
 <td align="left" class="contthmiddle"><strong>{active}</strong></td>
 <td class="contthright">&nbsp;</td>
</tr><!-- START menuline -->
<tr>
 <td align="right" class="conttd">{uid}&nbsp;</td>
 <td align="left" class="conttd">{username}&nbsp;</td>
 <td align="left" class="conttd"><a href="{link_active}">{active}</a>&nbsp;</td>
 <td align="left" class="conttd">
  <a href="{link_edit}"><img src="{confpath}/icons/user_edit.png" border="0" alt="" title="{msg_edit}" /></a>&nbsp;
  <a href="{link_dele}"><img src="{confpath}/icons/user_dele.png" border="0" alt="" title="{msg_dele}" /></a>
 </td>
</tr><!-- END menuline --><!-- END menu_ow --><!-- START nomenu -->
<tr>
 <td colspan="4" class="conttd" align="center">
  <br />
  <br />
  {msg_nomatch}
 </td>
</tr><!-- END nomenu -->
</table>
<br /><!-- START adduser -->
<div align="left">
 <a href="{link_adduser}"><img src="{confpath}/icons/user_add.png" border="0" alt="" title="{msg_adduser}" /></a>&nbsp;
 <a href="{link_adduser}">{msg_adduser}</a>
</div><!-- END adduser -->
<!-- END overview -->
<!-- START editarea -->
<!-- START error --><strong>{error}</strong><br /><!-- END error -->
<form action="{edit_target}" method="POST" name="SetForm">
<fieldset>
 <legend>{leg_basic}</legend>
 <table cellpadding="2" cellsapcing="0" border="0">
  <tr>
   <td align="left">{msg_sysuser}</td>
   <td align="left"><!-- START adduser -->
    <input type="text" size="16" maxlength="32" name="PHM[username]" value="{name}" /><!-- END adduser --><!-- START edituser -->
    {name}<input type="hidden" name="uid" value="{uid}" /><!-- END edituser -->
   </td>
  </tr>
  <tr>
   <td align="left">{msg_syspass}</td>
   <td align="left">
    <input type="password" autocomplete="off" size=16 maxlength=32 name="PHM[password]" value="{password}" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_syspass2}</td>
   <td align="left">
    <input type="password" autocomplete="off" size="16" maxlength="32" name="PHM[password2]" value="{password2}" />
   </td>
  </tr>
  <tr>
   <td align="left">{msg_email}</td>
   <td align="left" colspan=2>
    <input type="text" size="32" maxlength="255" name="PHM[email]" value="{email}">
   </td>
  </tr>
  <tr>
   <td align="left">{msg_active}</td>
   <td align="left">
    <select name="PHM[active]" size="1">
     <option value="0"<!-- START selno --> selected="selected"<!-- END selno -->>{msg_no}</option>
     <option value="1"<!-- START selyes --> selected="selected"<!-- END selyes -->>{msg_yes}</option>
    </select>
   </td>
  </tr>
 </table>
 </fieldset>
 <br /><!-- START forSA -->
 <fieldset>
 <legend>{leg_permissions}</legend>
 {msg_perm}:<br />
 <br />
 <input type="checkbox" name="WPnewsuperadmin" value="1"<!-- START selsupadm --> checked="checked"<!-- END selsupadm --> id="lbl_superadmin" />
 <label for="lbl_superadmin">&nbsp;<strong>{msg_superadmin}</strong></label><br />
 {about_superadmin}
 <br />
 <br />
 {msg_modperms}:<br />
 <br />
 <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td align="left">{msg_module}</td>
   <td align="left">{msg_read}</td>
   <td align="left">{msg_write}</td>
  </tr><!-- START permline -->
  <tr><!-- START perm -->
   <td align="left">{menu}</td>
   <td>
    <input type="checkbox" name="read[]" value="{action}_{screen}" id="read_{action}_{screen}"<!-- START optread --> checked="checked"<!-- END optread --> OnChange="read_dependency('{action}_{screen}')" />
   </td>
   <td>
    <input type="checkbox" name="write[]" value="{action}_{screen}" id="write_{action}_{screen}"<!-- START optwrite --> checked="checked"<!-- END optwrite --> OnChange="write_dependency('{action}_{screen}')" />
   </td><!-- END perm --><!-- START heading -->
   <td align="left" colspan="3"><strong>{menu}</strong></td><!-- END heading -->
  </tr><!-- END permline -->
  <tr>
   <td>&nbsp;</td>
   <td align="left">
    <script type="text/javascript">
     <!--
     document.write('<input type="button" value="{msg_all}" onClick="setBoxes(\'SetForm\',\'read[]\',1)" /><br />');
     document.write('<input type="button" value="{msg_none}" onClick="setBoxes(\'SetForm\',\'read[]\',0)" /><br />');
    // -->
   </script>
  </td>
  <td align="left">
    <script type="text/javascript">
     <!--
     document.write('<input type="button" value="{msg_all}" onClick="setBoxes(\'SetForm\',\'write[]\',1)" /><br />');
     document.write('<input type="button" value="{msg_none}" onClick="setBoxes(\'SetForm\',\'write[]\',0)" /><br />');
    // -->
   </script>
  </td>
  </tr>
 </table>
 </fieldset><!-- END forSA -->
 <br />
 <input type="submit" value="{msg_save}" />
</form><br />
<br />
  <!-- START loginfail -->
<fieldset>
<legend>{leg_stat}</legend>
 <table cellpadding="2" cellsapcing="0" border="0">
  <tr>
   <td align="left">{msg_lastlogin}</td>
   <td align="left">{lastlogin}</td>
  </tr>
  <tr>
   <td align="left">{msg_lastlogout}</td>
   <td align="left">{lastlogout}</td>
  </tr>
  <tr>
   <td align="left">{msg_loginfail}</td>
   <td align="left">
    {loginfail} <!-- START resetfail --><a href="{link_resetfail}">{msg_resetfail}<!-- END resetfail -->
   </td>
  </tr>
 </table>
</fieldset><!-- END loginfail -->
 <br clear="all" />
 <br />
 <div align="right"><a href="{link_base}">{msg_cancel}</a></div>
<script type="text/javascript">
<!--
function setBoxes(formular, gruppe, anaus)
{
    var moep = document.forms[formular].elements[gruppe];
    var betroffen = (typeof(moep.length) != 'undefined') ? moep.length : 0;
    if(betroffen) {
        for(var i=0; i<betroffen; i++) {
            if(anaus == -1) {
                moep[i].checked = 1 - moep[i].checked;
            } else {
                moep[i].checked = anaus;
                // Abh�ngigkeit Schreiben - Lesen
                if (gruppe == 'write[]' && anaus == 1) {
                    document.forms[formular].elements['read[]'][i].checked = 1;
                }
                if (gruppe == 'read[]' && anaus == 0) {
                    document.forms[formular].elements['write[]'][i].checked = 0;
                }
            }
        }
    } else {
        if(anaus == -1) {
            moep.checked = 1 - moep.checked;
        } else {
            moep.checked = anaus;
        }
    }
    return true;
}
function write_dependency(affected)
{
    if (document.getElementById('write_' + affected).checked == 1) {
        document.getElementById('read_' + affected).checked = 1;
    }
}
function read_dependency(affected)
{
    if (document.getElementById('read_' + affected).checked == 0) {
        document.getElementById('write_' + affected).checked = 0;
    }
}
// -->
</script>
<!-- END editarea -->