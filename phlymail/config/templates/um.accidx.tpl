<a href="{link_um}">{where_um}</a>&nbsp;/&nbsp;<a href="{link_user}">{where_user}</a>&nbsp;/&nbsp;{where_profsel}<br />
<br />
<form action="{target_link}" method="POST">
<div align="left"><!-- START return -->
 <strong>{WP_return}</strong><br /><br /><!-- END return -->
 <strong>{profname}</strong>&nbsp;<!-- START profmenu -->
 <select name="accid" size="1"><!-- START menuline -->
  <option value="{key}">{value}</option><!-- END menuline -->
 </select>&nbsp;<input type="submit" value="{msg_edit}" /><!-- END profmenu --><!-- START nomenu -->
 {msg_noprof}<!-- END nomenu --><br />
 <br />
 {msg_add}: <a href="{link_add}&acctype=pop3">POP3</a><!-- START has_imap -->&nbsp;&middot;&nbsp;<a href="{link_add}&acctype=imap">IMAP</a><!-- END has_imap -->
</div>
</form>