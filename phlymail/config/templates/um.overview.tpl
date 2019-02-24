<script type="text/javascript">
//<![CDATA[
function open_profiles(fuerwen)
{
    window.open
            ('{link_edpf}' + fuerwen
            ,'prof_editor'
            ,'width=770,height=410,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no,personalbar=no'
            )
}
//]]>
</script>
<div>{head_text}<br /></div>
 <!-- START return --><strong>{WP_return}</strong><br /><!-- END return -->
 <br />
 <table border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td align="right">{regusers}:</td>
   <td align="right">{users_all}</td>
   <td align="left"><!-- START search_all -->
    <a href="{link_search_all}"><img src="{confpath}/icons/search.png" alt="" title="{searchcrit}" /></a><!-- END search_all -->
   </td>
  </tr>
  <tr>
   <td align="right">{maxlicence}:</td>
   <td align="right">{users_max}</td>
   <td align="left">&nbsp;</td>
  </tr>
  <tr>
   <td align="right">{msg_active}:</td>
   <td align="right">{users_active}</td>
   <td align="left"><!-- START search_active -->
    <a href="{link_search_active}"><img src="{confpath}/icons/search.png" alt="" title="{searchcrit}" /></a><!-- END search_active -->
   </td>
  </tr>
  <tr>
   <td align="right">{msg_inactive}:</td>
   <td align="right">{users_inactive}</td>
   <td align="left"><!-- START search_inactive -->
    <a href="{link_search_inactive}"><img src="{confpath}/icons/search.png" alt="" title="{searchcrit}" /></a><!-- END search_inactive -->
   </td>
  </tr>
  <tr>
   <td align="right">{msg_locked}:</td>
   <td align="right">{users_locked}</td>
   <td align="left"><!-- START search_locked -->
    <a href="{link_search_locked}"><img src="{confpath}/icons/search.png" alt="" title="{searchcrit}" /></a><!-- END search_locked -->
   </td>
  </tr>
 </table><br />
<form action="{search_target}" method="post">
<div>
 {msg_finduser}:&nbsp;
 <input type="text" name="search" value="{search}" title="{msg_title}" size="16" maxlength="32" />
 &nbsp;
 <select name="criteria" size="1">
  <option <!-- START sel_crit_all -->selected="selected" <!-- END sel_crit_all -->value="all">{msg_all}</option>
  <option <!-- START sel_crit_active -->selected="selected" <!-- END sel_crit_active -->value="active">{msg_active}</option>
  <option <!-- START sel_crit_inactive -->selected="selected" <!-- END sel_crit_inactive -->value="inactive">{msg_inactive}</option>
  <option <!-- START sel_crit_locked -->selected="selected" <!-- END sel_crit_locked -->value="locked">{msg_locked}</option>
 </select>
 &nbsp;
 <input type="submit" value="{msg_find}" /><br />
</div>
</form>
<br />
<table border="0" cellpadding="0" cellspacing="0" width="400"><!-- START menu -->
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
   <a href="{link_edit}"><img src="{confpath}/icons/user_edit.png" alt="" title="{msg_edit}" /></a>&nbsp;
   <a href="{link_dele}"><img src="{confpath}/icons/user_dele.png" alt="" title="{msg_dele}" /></a>&nbsp;
   <a href="javascript:open_profiles('{uid}');"><img src="{confpath}/icons/proto_edit.gif" alt="" title="{msg_edpf}" /></a>&nbsp;
  </td>
 </tr><!-- END menuline --><!-- END menu --><!-- START nomenu --> <tr>
  <td colspan="4" align="center" class="conttd">
   <br />
   <br />
   {msg_nomatch}
  </td>
 </tr><!-- END nomenu -->
</table>
<br /><!-- START adduser -->
<div>
 <a href="{link_adduser}"><img src="{confpath}/icons/user_add.png" alt="" title="{msg_adduser}" /></a>&nbsp;
 <a href="{link_adduser}">{msg_adduser}</a>
</div><!-- END adduser -->