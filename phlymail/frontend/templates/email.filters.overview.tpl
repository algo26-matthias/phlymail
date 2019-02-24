<script type="text/javascript">
/*<![CDATA[*/
marked = 0;

function select(id)
{
    if (marked) {
        if (marked == id) return;
        $('#f_' + marked).removeClass('marked');
    }
    marked = id;
    $('#f_' + marked).addClass('marked');

    betroffen = document.getElementsByName('onsel');
    for (i = 0; i < betroffen.length; i++) {
        betroffen[i].disabled = false;
    }
}

function edit(id)
{
    if (id == 0) id = '';
    window.open
            ('{edit_url}' + id
            ,'editfilter_{id}'
            ,'width=545;,height=580,left=' + 200 + ',top=' + 200
                    + ',scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no'
            );
}

function dele(id) { self.location.href = '{delete_url}' + id; }

function reorder(id, updown)
{
    updown = (updown == 'up') ? 'up' : 'down';
    self.location.href = '{reorder_url}' + id + '&dir=' + updown + '&selected=' + marked;
}

function activate(id) { self.location.href = '{activate_url}' + id + '&selected=' + marked; }
/*]]>*/
</script>
<div class="t l">
<div style="height:150px;float:right;">
<button type="button" onclick="edit(0)">{msg_new} ...</button><br />
<br />
<button type="button" name="onsel" onclick="edit(marked)" disabled="disabled">{msg_edit}</button><br />
<button type="button" name="onsel" onclick="dele(marked)" disabled="disabled">{msg_delete}</button><br />
<br />
<br />
<button type="button" name="onsel" onclick="reorder(marked, 'up')" disabled="disabled">{msg_up}</button><br />
<button type="button" name="onsel" onclick="reorder(marked, 'down')" disabled="disabled">{msg_down}</button><br />
</div>
<div class="sendmenuborder" style="height:150px;width:300px;float:left;overflow:auto;text-align:left;vertical-align:top;padding:4px;"><!-- START filterline -->
<div class="menuline" id="f_{id}" onclick="select('{id}')" ondblclick="edit({id})" style="cursor:pointer;">
<input type="checkbox" name="filter_1"<!-- START active --> checked="checked" title="{msg_active}"<!-- END active --><!-- START inactive --> title="{msg_inactive}"<!-- END inactive -->value="1" onclick="select({id}); activate({id})" />
{name}
</div><!-- END filterline -->
</div>
</div><!-- START ifselected -->
<script type="text/javascript">/*<![CDATA[*/select({selected});/*]]>*/</script><!-- END ifselected -->