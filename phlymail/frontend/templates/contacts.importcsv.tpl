<script type="text/javascript">
/*<![CDATA[*/
function choose()
{
    // Get selected items from both forms
    sel_csv = document.getElementsByName('csv_fields')[0].selectedIndex;
    sel_db = document.getElementsByName('db_fields')[0].selectedIndex;
    // Nothing selected: Do nothing
    if (sel_csv == -1 || sel_db == -1) return;
    // The real values of the options
    sel_csv_val = document.getElementsByName('csv_fields')[0].options[sel_csv].getAttribute('value');
    sel_db_val = document.getElementsByName('db_fields')[0].options[sel_db].getAttribute('value');
    // Create new table rows showing the selections in clear text
    newInp = document.createElement('input');
    newInp.setAttribute('type', 'hidden');
    newInp.setAttribute('name', 'selected_fields[' + sel_db_val + ']');
    newInp.setAttribute('value', sel_csv_val);
    addform.appendChild(newInp);

    newTr = document.createElement('tr');

    newTd = document.createElement('td');
    newTd.setAttribute('align', 'left');
    TdVal = document.createTextNode(document.getElementsByName('csv_fields')[0].options[sel_csv].firstChild.nodeValue);
    newTd.appendChild(TdVal);
    newTr.appendChild(newTd);

    newTd = document.createElement('td');
    newTd.setAttribute('align', 'left');
    newTr.appendChild(newTd);

    newTd = document.createElement('td');
    newTd.setAttribute('align', 'left');
    TdVal = document.createTextNode(document.getElementsByName('db_fields')[0].options[sel_db].firstChild.nodeValue);
    newTd.appendChild(TdVal);
    newTr.appendChild(newTd);
    addtable.appendChild(newTr);

    // Remove selected items to prevent further selection
    document.getElementsByName('db_fields')[0].removeChild(document.getElementsByName('db_fields')[0].options[sel_db]);
    document.getElementsByName('csv_fields')[0].removeChild(document.getElementsByName('csv_fields')[0].options[sel_csv]);
}
window.onload = function(e) {
    addtable = document.getElementById('addtable');
    addform = document.getElementById('selected_form');
}
/*]]>*/
</script>
<div class="l">
 {about_selection}<br />
 <br />
 <fieldset>
  <legend>{legend_source}</legend>
  <div style="vertical-align:top;">
   <select name="csv_fields" size="15"><!-- START csvline -->
    <option value="{id}">{value}</option><!-- END csvline -->
   </select>
   =&gt;
   <select name="db_fields" size="15"><!-- START dbline -->
    <option value="{id}">{value}</option><!-- END dbline -->
   </select>
   <button type="button" onclick="choose();">{msg_select}</button>
  </div>
 </fieldset>
 <br />
 <form action="{form_action}" id="selected_form" method="post">
  <fieldset>
   <legend>{legend_selection}</legend>
   <table border="0" cellpadding="2" cellspacing="0">
   <thead>
    <tr>
     <td class="l"><strong>{msg_from_csv}</strong></td>
     <td class="l"><strong> =&gt; </strong></td>
     <td class="l"><strong>{msg_in_db}</strong></td>
    </tr>
   </thead>
   <tbody id="addtable">
   </tbody>
   </table>
   <br /><!-- START if_fieldnames -->
   <input type="hidden" name="fieldnames" value="1" /><!-- END if_fieldnames --><!-- START if_quoted -->
   <input type="hidden" name="is_quoted" value="1" /><!-- END if_quoted -->
   <input type="hidden" name="delimiter" value="{delimiter}" />
   <input type="submit" value="{msg_save}" />
  </fieldset>
 </form><br />
 <br />
 <div class="r" style="padding:8px;">
  <a href="{link_back}">{msg_back}</a>
 </div>
</div>