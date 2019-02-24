<script type="text/javascript">
//<!--
function draw_bar(bid, klimit, usage, keep)
{
    var cont  = document.getElementById(bid + '_cont');
    var bar   = document.getElementById(bid + '_bar');
    if ((usage == '' || klimit == '' || usage == 0 || klimit == 0) && keep != 1
            || (usage == 0 && klimit == 0 && keep == 1)) {
        bar.style.visibility = 'hidden';
        cont.title = '0%';
        return;
    }
    // Since MSIE fails to read cont.offsetWidth I had to set the value from the style.width setting manually
    var fullwidth = 40; // cont.offsetWidth;

    klimit = klimit * 1;
    usage  = usage * 1;
    if (usage >= klimit) {
        bar.style.width = fullwidth + 'px';
        bar.style.height = '12px';
        bar.style.backgroundColor = '#FF0000';
        cont.title = '>= 100%';
    } else {
        var prozent = Math.round(100 * usage / klimit);
        bar.style.width = (fullwidth * prozent / 100) + 'px';
        bar.style.height = '12px';
        if (prozent < 50) {
            var r = prozent * 2;
            var g = 100;
        } else if (prozent == 50) {
            var r = 100
            var g = 100;
        } else {
            var r = 100;
            var g = (100-prozent) * 2;
        }
        bar.style.backgroundColor = 'rgb(' + r + '%,' + g + '%,0%)';
        cont.title = prozent + '%';
    }
}
// -->
</script>
<a href="{link_um}">{where_um}</a>&nbsp;/&nbsp;<a href="{link_user}">{where_user}</a>&nbsp;/&nbsp;{where_setquota}<br />
<form action="{target_link}" method="post">
<div align="left"><!-- START error -->
 <br />
 <span class="texterror">{error}</span><br /><!-- END error -->
 <br />
 {about_quota}<br />
 <br /><!-- START handlerline -->
 <fieldset>
  <legend>{handler}</legend>
  <table border="0" cellpadding="2" cellspacing="0">
   <thead>
    <tr>
     <th>{head_crit}</th>
     <th colspan="2">{head_limit}</th>
     <th colspan="2">{head_usage}</th>
    </tr>
   </thead>
   <tbody><!-- START critline -->
    <tr>
     <td>{msg_crit}</td>
     <td><input type="text" name="{input}" class="r" value="{crit_limit}" size="8" maxlength="32" /></td>
     <td class="r">{crit_unit}</td>
     <td class="r">{crit_use}</td>
     <td><div id="{crit}_cont" class="quotabar_cont"><div id="{crit}_bar"></div></div>
     <script type="text/javascript">draw_bar("{crit}", '{crit_limit}', '{crit_use}', '{crit_keep}');</script>
     </td>
    </tr><!-- END critline -->
   </tbody>
  </table>
 </fieldset><br />
 <br /><!-- END handlerline -->
 <input type="submit" value="{msg_save}" />
 </div>
 </form>