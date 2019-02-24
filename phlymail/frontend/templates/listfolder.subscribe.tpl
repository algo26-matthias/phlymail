<script type="text/javascript">
<!--
function resize_elements()
{
    var avail_screen;
    if (window.innerHeight) {
        avail_screen = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_screen = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_screen = document.body.offsetHeight;
    } else {
        avail_screen = 500;
    }
    var toph = document.getElementById('top').offsetHeight;
    var botth = document.getElementById('bottom').offsetHeight;
    var midh = avail_screen - (toph + botth);
    document.getElementById('container').style.height = midh + 'px';
}

function init_page()
{
    resize_elements();
    window.onresize = resize_elements;
}

if (window.addEventListener) {
    window.addEventListener('load', init_page, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', init_page);
}

function savelist()
{
    var frm = document.subscriptions;
    var lgth = frm.elements.length;
    var i, ele, data;
    for (i = 0; i < lgth; i++) {
        ele = frm.elements[i];
        if (!ele.type || !ele.name) continue;
        if (ele.type == 'checkbox') {
            data += '&' + encodeURIComponent(ele.name) + '=' + ele.value
                    + '&' + encodeURIComponent('stat_' + ele.name) + '=' + (ele.checked ? 1 : 0);
        }
    }
    AJAX_call(frm.action, 'post', data.substr(1));
    return false;
}

function AJAX_call(url, method, data)
{
    if (!method || method == 'get') method = 'GET';
    if (method == 'post') method = 'POST';
    var payload = {'url': url, 'type': method, 'success': AJAX_process};
    if (method == 'POST' && data) {
        payload.data = data;
    }
    $.ajax(payload);
}

function AJAX_process(next)
{
    if (next['done']) done();
}

function done()
{
    try { opener.opener.parent.PHM_tl.location.reload(); } catch (e) {}
    self.close();
}

// -->
</script>
<form name="subscriptions" action="{subscribetarget}" method="post" onsubmit="return savelist();">
<div id="top">
 <div class="l" style="padding:16px;">{head_select}</div>
</div>
<div id="container" style="border:1px solid black;overflow:auto;text-align:left;vertical-align:top;margin:0px 16px 0px 16px;">
<div style="text-align:left; vertical-align:top;">
<!-- START line --><div class="listfolderline" name="{level}">
<input type="checkbox" name="sub[{id}]" value="{fullpath}"<!-- START subbed --> checked="checked"<!-- END subbed --><!-- START nonselect --> style="visibility:hidden"<!-- END nonselect --> /><!-- START bars --><!-- START vbar --><img src="{theme_path}/icons/corn2.png" alt="" align="middle" /><!-- END vbar --><!-- START novbar --><img src="{theme_path}/icons/corn3.png" alt="" align="middle" /><!-- END novbar --><!-- END bars --><!-- START aufzu --><img src="{theme_path}/icons/minus.png" alt="" align="middle" /><!-- END aufzu --><!-- START cornplus --><img src="{theme_path}/icons/corn1.png" alt="" align="middle" /><!-- END cornplus --><!-- START corn --><img src="{theme_path}/icons/corn0.png" alt="" align="middle" /><!-- END corn --><!-- START rootline --><img src="{theme_path}/icons/root.png" alt="" align="middle" /><!-- END rootline --><img id="img_{folder_path}" src="{icon}" alt="" title="{foldername}" align="middle" />&nbsp;<span class="foldername" id="txt_{Rfolder_path}" title="{foldername}">{foldername}</span><br /></div>
<!-- END line -->
</div>
</div>
<div id="bottom">
 <div class="l" style="padding:16px;">
  <input type="submit" value="{msg_save}" />
 </div>
</div>
</form>