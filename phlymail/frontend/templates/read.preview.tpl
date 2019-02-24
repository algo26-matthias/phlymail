<script type="text/javascript">
/*<![CDATA[*/
inline_att = {};<!-- START showinline -->
inline_att[{id}] = {"type": '{type}', "name" : '{name}'};<!-- END showinline -->
ctxmen = [ {'status' : 1, 'link' : 'dl_attach()', 'name' : '{msg_save}', 'icon' : '{theme_path}/icons/save_ctx.gif'} ];<!-- START has_dlall -->
ctxmen.push({'status' : 1, 'link' : 'dl_all()', 'name' : '{msg_save_all}', 'icon' : '{theme_path}/icons/save_ctx.gif'});<!-- END has_dlall -->

ctxmen_id = false;
ctxadded = false;
hdl_ctx = [];
att_info = [];
ctxover = false;

function context_addhandler(icon, handler, msg)
{
    if (!ctxadded) {
        ctxmen.push({'status' : 2});
        ctxadded = true;
    }
    ctxmen.push({'status' : 3, 'link' : 'sendto("' + handler + '")', 'name' : msg, 'icon' : '{theme_path}/icons/' + icon});
    hdl_ctx[handler] = ctxmen.length-1;
}
<!-- START availhdls -->
context_addhandler('{icon}', '{handler}', '{msg}');<!-- END availhdls -->

function menuattach(attnum)
{
    for (var i = 0; i < ctxmen.length; ++i) {
        if (i < 3) continue;
        ctxmen[i]['status'] = 3;
    }
    for (i = 0; i < att_info[attnum]['hdls'].length; ++i) {
        ctxmen[hdl_ctx[att_info[attnum]['hdls'][i]]]['status'] = 1;
    }
    ctxover = attnum;
}

function dl_all()
{
    // Changes necessary before this can come into place:
    // - Clicking attachment selects it
    // - Ctrl+Click, Shift+Click for multiple selection
    // - Ctxmen then offers "Save selected"
    // - Doubleclick acts as Click does right now
    // - ZIP extension must be enabled
}

function dl_attach()
{
    if (ctxover == false) return;
    var ahref = document.getElementById('ahref_' + ctxover);
    if (ahref) {
        if (typeof ahref.target != 'undefiend' && ahref.target == '_blank')  {
            window.open(ahref.href);
        } else {
            self.location.href = ahref.href;
        }
    }
}

function sendto(hndlr)
{
    var resid = att_info[ctxover]['resid'];
    date = new Date();
    ctime = date.getTime();
    window.open
            ('{link_sendto}&handler=' + hndlr + '&resid=' + encodeURIComponent(resid)
            ,'_sendto_' + ctime
            ,'width=250,height=250,scrollbars=no,resizable=yes,location=no,menubar=no,status=no,toolbar=no,personalbar=no'
            );
}

function adjustMyHeight()
{
    // Get the available Window height
    if (window.innerHeight) {
        avail_height = window.innerHeight;
    } else if (document.documentElement.offsetHeight) {
        avail_height = document.documentElement.offsetHeight;
    } else if (document.body.offsetHeight) {
        avail_height = document.body.offsetHeight;
    } else {
        avail_height = 500;
    }
    mbody = document.getElementById('mbody_prev');
    mbody_height = mbody.offsetHeight;
    iframe_height = document.getElementById('mbody_iframe').offsetHeight;
    availbody = avail_height - (mbody.offsetTop);

    if (document.getElementById('attachments')) availbody = availbody - document.getElementById('attachments').offsetHeight;
    // A size < 0 doesn't make sense, does it?
    if (availbody > 0)  {
        mbody.style.height = availbody + 'px';
        document.getElementById('mbody_iframe').style.height = (iframe_height + (availbody - mbody_height)) + 'px';
    }
}

function view_inline()
{
    var hr, im, div;
    if (inline_att.length == 0) return;
    AppDoc = frames.mbody_iframe.document;
    if (typeof AppDoc == 'undefined') return;

    AppNode = AppDoc.getElementById('mailtext');
    for (var ID in inline_att) {
        if (inline_att[ID]['type'] != 'image' && inline_att[ID]['type'] != 'text') continue;

        hr = AppDoc.createElement('div');
        // We don't have a generic style sheet for HTML mails
        hr.style.borderBottom = '1px solid darkgray';
        hr.style.padding = '4px';
        hr.style.margin = '2px';
        hr.style.fontSize = '9pt';
        hr.style.fontFamily = 'Verdana, Arial, Helvetica, "Sans Serif"';
        hr.style.color = 'darkgray';
        hr.className = 'attachment_hr'
        hr.appendChild(AppDoc.createTextNode(inline_att[ID]['name']));
        AppNode.appendChild(hr);

        if (inline_att[ID]['type'] == 'image') {
            img = AppDoc.createElement('img');
            img.style.display = 'block';
            img.style.margin = 'auto';
            img.src = '{showinlineurl}' + ID;
            AppNode.appendChild(img);
        } else if (inline_att[ID]['type'] == 'text') {
            div = AppDoc.createElement('div');
            div.id = 'inline_div_' + ID;
            AppNode.appendChild(div);
            $.ajax(
                {url : '{showinlineurl}' + ID
                ,dataType: 'text'
                ,success: function (data) {
                    AppDoc.getElementById('inline_div_' + ID).innerHTML = '<pre>' + data + '</pre>';
                }});
        }
    }
}

<!-- START mdn -->
send_dsn = false;
if ('{dispomode}' == 'manual') {
    send_dsn = confirm('{msg_confirm_mdn}');
} else {
    send_dsn = true;
}
if (send_dsn) {
    $.ajax({url:'{send_url}'});
}
$.ajax({url:'{status_url}'});<!-- END mdn --><!-- START preview_blocked -->
parent.preview_blocked();<!-- END preview_blocked -->
parent.fill_preview_header('{from}', '{x_from}', '{to}', '{subject}', '{cc}', '{replyto}', '{date}', '{imgurl}');
$(document).ready(function () {
	adjustMyHeight();
});
$(window).resize(function () {
    adjustMyHeight();
});

/*]]>*/
</script>
<div id="mbody_prev" style="background:white;">
    <iframe src="{body_link}" width="100%" height="100%" id="mbody_iframe" name="mbody_iframe" frameborder="0"></iframe>
</div><!-- START attachblock -->
<div id="attachments">
 <div class="sendmenubut" id="attachmentdivider"></div>
 <div id="attachmentcontainer"><!-- START attachline -->
 <script type="text/javascript">/*<![CDATA[*/att_info[{att_num}] = {'resid' : '{resid}', 'hdls' : [{hdllist}]};/*]]>*/</script>
 <span onmouseover="ctxmen_activate_sensor(ctxmen)" onmouseout="ctxmen_disable_sensor();if(ctxmen_id==false){ctxover=false;}" oncontextmenu="menuattach('{att_num}')" style="white-space:nowrap;"><img src="{frontend_path}/filetypes/32/{att_icon}" width="32" height="32" alt="" title="{att_icon_alt}" />&nbsp;<a id="ahref_{att_num}" title="{att_size} {msg_att_type}: {att_type}" href="{link_target}"<!-- START inline --> target="_blank"<!-- END inline -->>{att_name}</a></span><!-- END attachline -->
 </div>
</div>
<!-- END attachblock -->