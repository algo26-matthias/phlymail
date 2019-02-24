<!-- START returnblock --><div align="left" class="returnbox"><strong>{return}</strong></div><br />
<!-- END returnblock --><!-- START nocontactsblock -->
<div class="emptymailbox">{nocontacts}</div><!-- END nocontactsblock --><!-- START contactblock --><div align="left" id="topmen">
<script type="text/javascript">
<!--
var anzahl = 0;
markedcontacts = new Array();
ctxmen_id = false;

function setBoxes(anaus)
{
    var betroffen = document.getElementById('contactline_tbody').childNodes.length;
    for (var i = 0; i < betroffen; ++i) {
    	var child = document.getElementById('contactline_tbody').childNodes[i];
    	if (child.nodeName != 'TR') continue;
        var lineid = child.id;
        if (typeof(markedcontacts[lineid]) != 'undefined') {
            if (anaus == 1 && markedcontacts[lineid] == null) {
                markline(lineid);
            } else if (anaus == 0 && markedcontacts[lineid] == 1) {
                markline(lineid);
            } else if (anaus == -1) {
                markline(lineid);
            }
        } else {
            if (anaus == 1 || anaus == -1) {
                markline(lineid);
            }
        }
    }
}

function markline(lineid)
{
    var rowchilds = document.getElementById(lineid).childNodes.length;
    if (markedcontacts[lineid] == 1) {
        // unset mark
        markedcontacts[lineid] = null;
        anzahl--;
        for (var i = 0; i < rowchilds; i++) {
            var child = document.getElementById(lineid).childNodes[i];
            if (1 == child.nodeType) {
                var classe = child.getAttributeNode("class");
                classe.nodeValue = "conttd";
            }
        }
    } else {
        // set mark
        markedcontacts[lineid] = 1;
        anzahl++;
        for (var i = 0; i < rowchilds; i++) {
            var child = document.getElementById(lineid).childNodes[i];
            if (1 == child.nodeType) {
                var classe = child.getAttributeNode("class");
                classe.nodeValue = "conttdsel";
            }
        }
    }
    if (0 == anzahl) {
        document.getElementById('delbut').disabled = true;
    } else {
        document.getElementById('delbut').disabled = false;
    }
}

function disable_jump()
{
    if (2 > document.getElementById('maxpage').firstChild.data) {
       document.getElementById('submit_jump').disabled = 1;
    }
    document.getElementById('delbut').disabled = true;
}

function dele_contacts()
{
    if (anzahl == 0) return;
    if (!confirm('{msg_really_del}')) return;
     
    url = '{delelink}';
    for (var ID in markedcontacts) {
        if (1 == markedcontacts[ID]) {            
            url += '&id[]=' + document.getElementById(ID).getAttribute('name');
        }
    }
    self.location.href = url;
}


window.onload = disable_jump;
// -->
</script>
<table border="0" cellpadding="1" cellspacing="0" width="100%" class="sendmenubut">
 <tr>
  <td align="left" valign="middle" title="{rawallsize}">{neueingang} {plural}&nbsp;</td>
  <td align="left" valign="middle">
  <form action="#" method="post" style="display:inline">
  <input type="hidden" name="action" value="{action}" />
  {msg_page}&nbsp;{page}/<span id="maxpage">{boxsize}</span>&nbsp;&nbsp;
  <input type="text" size="{size}" maxlength="{maxlen}" name="WP_core_jumppage" value="{page}" />&nbsp;
  <input type="submit" id="submit_jump" value="{go}" />
  {passthrough_2}
  </form>
  </td>
  <td align="left">&nbsp;
   {contacts} {displaystart} - {displayend}</td>
  <td align="right"><!-- START blstblk -->
   <a href="{link_last}">
    <img src="{skin_path}/icons/nav_left.png" alt="" title="{but_last}" border="0" />
   </a>&nbsp;<!-- END blstblk --><!-- START bnxtblk -->
   <a href="{link_next}">
    <img src="{skin_path}/icons/nav_right.png" alt="" title="{but_next}" border="0" />
   </a><!-- END bnxtblk -->
  </td>
 </tr>
</table>
</div>
<div style="overflow: auto; vertical-align: top; text-align: left; width: 100%; height:250px;" id="contactlines">
<table border="0" cellpadding="1" cellspacing="0" width="100%">
 <tr>
  <td class="contthleft" align="left" style="cursor:pointer" onclick="window.location='{nickordurl}';"><!-- START nickordupico --><img src="{skin_path}/icons/nav_up.png" /><!-- END nickordupico --><!-- START nickorddownico --><img src="{skin_path}/icons/nav_down.png" /><!-- END nickorddownico -->{msg_nick}</td>
  <td class="contthmiddle" align="left" style="cursor:pointer" onclick="window.location='{lnamordurl}';"><!-- START lnamordupico --><img src="{skin_path}/icons/nav_up.png" /><!-- END lnamordupico --><!-- START lnamorddownico --><img src="{skin_path}/icons/nav_down.png" /><!-- END lnamorddownico -->{msg_lname}</td>
  <td class="contthmiddle" align="left" style="cursor:pointer" onclick="window.location='{fnamordurl}';"><!-- START fnamordupico --><img src="{skin_path}/icons/nav_up.png" /><!-- END fnamordupico --><!-- START fnamorddownico --><img src="{skin_path}/icons/nav_down.png" /><!-- END fnamorddownico -->{msg_fname}</td>
  <td class="contthright" align="left" style="cursor:pointer" onclick="window.location='{mailordurl}';"><!-- START mailordupico --><img src="{skin_path}/icons/nav_up.png" /><!-- END mailordupico --><!-- START mailorddownico --><img src="{skin_path}/icons/nav_down.png" /><!-- END mailorddownico -->{msg_email}</td>
 </tr>
 <tbody id="contactline_tbody"><!-- START contactlines -->
 <tr name="{id}" style="cursor:pointer" ondblclick="javascript:window.open('{editlink}','contact_{id}','width=850,height=375,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no');" onclick="javascript:markline('line_{id}');" id="line_{id}">
 <td class="conttd" align="left" title="{nick_title}">{nick}</td>
 <td class="conttd" align="left" title="{lname_title}">{lname}</td>
 <td class="conttd" align="left" title="{fname_title}">{fname}</td>
 <td class="conttd" align="left" title="{email_title}">{email}</td>
</tr><!-- END contactlines -->
</tbody>
</table>
</div>
<div class="conttdb">
 <button type="button" onclick="setBoxes(1)">{msg_all}</button>
 <button type="button" onclick="setBoxes(0)">{msg_none}</button>
 <button type="button" onclick="setBoxes(-1)">{msg_rev}</button>&nbsp;&nbsp;
 <button type="button" id="delbut" onclick="dele_contacts()">{msg_dele}</button>
</div><!-- END contactblock -->
<button type="button" onclick="window.open('{newlink}','contact','width=850,height=375,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no')">{msg_add}</button>