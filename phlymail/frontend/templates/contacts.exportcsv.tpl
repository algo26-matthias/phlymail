<script type="text/javascript">
/*<![CDATA[*/
function choose()
{
    // Get selected item
    sel_db = document.getElementsByName('db_fields')[0].selectedIndex;
    // Nothing selected: Do nothing
    if (sel_db == -1) return;
    // The real values of the options
    sel_db_val = document.getElementsByName('db_fields')[0].options[sel_db].getAttribute('value');
    // Create new option showing the selection in clear text
    newOpt = document.createElement('option');
    newOpt.setAttribute('value', sel_db_val);
    OptVal = document.createTextNode(document.getElementsByName('db_fields')[0].options[sel_db].firstChild.nodeValue);
    newOpt.appendChild(OptVal);
    addsel.appendChild(newOpt);

    newInp = document.createElement('input');
    newInp.setAttribute('type', 'hidden');
    newInp.setAttribute('name', 'selected_fields[]');
    newInp.setAttribute('value', sel_db_val);
    addform.appendChild(newInp);

    // Remove selected item to prevent further selection
    document.getElementsByName('db_fields')[0].removeChild(document.getElementsByName('db_fields')[0].options[sel_db]);
}

function add_space()
{
    // Create new option showing the selection in clear text
    newOpt = document.createElement('option');
    newOpt.setAttribute('value', -1);
    OptVal = document.createTextNode('- {msg_space} -');
    newOpt.appendChild(OptVal);
    addsel.appendChild(newOpt);

    newInp = document.createElement('input');
    newInp.setAttribute('type', 'hidden');
    newInp.setAttribute('name', 'selected_fields[]');
    newInp.setAttribute('value', -1);
    addform.appendChild(newInp);
}
window.onload = function (e) {
    addsel = document.getElementById('addselect');
    addform = document.getElementById('selected_form');
}
/*]]>*/
</script>
<div class="l">
 {about_selection}<br />
 <br />
 <form action="{form_action}" id="selected_form" method="post">
  <div style="float:left;">
   <strong>{msg_from_db}</strong><br />
   <select name="db_fields" size="{sel_size}"><!-- START dbline -->
    <option value="{id}">{value}</option><!-- END dbline -->
   </select>
   <button type="button" onclick="choose();">{msg_select} -&gt;</button>
  </div>
  <div style="float:right;">
   <strong>{msg_in_csv}</strong><br />
   <select name="csv_fields" size="{sel_size}" id="addselect">
   </select>
   <button type="button" onclick="add_space();">&lt;- {msg_add_space}</button>
  </div>
  <br style="clear:both" /><!-- START if_fieldnames -->
  <input type="hidden" name="fieldnames" value="1" /><!-- END if_fieldnames --><!-- START if_quoted -->
  <input type="hidden" name="is_quoted" value="1" /><!-- END if_quoted -->
  <input type="hidden" name="delimiter" value="{delimiter}" />
  <input type="submit" value="{msg_save}" />
 </form><br />
 <br />
 <div class="r" style="padding:8px;">
  <a href="{link_back}">{msg_back}</a>
 </div>
</div>