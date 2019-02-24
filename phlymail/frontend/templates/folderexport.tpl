<script type="text/javascript">
/*<![CDATA[*/
idlist = [{id_list}];

function init()
{
    fetcher_url = '{exporturl}';
    mail_curr = 0;
    mail_all = idlist.length;
    p_prof = document.getElementById('fetcher_inner');
    o_prof = document.getElementById('fetcher_outer');
    p_prof.style.width = '0px';
    if (typeof profwidth == 'undefined') profwidth  = p_prof.offsetWidth;
    document.getElementById('current').innerHTML = mail_curr;
    document.getElementById('sum').innerHTML = mail_all;
    if (mail_all == 0) {
        document.getElementById('machzu').style.display = 'block';
    } else {
        fetch();
    }
}

function fetch()
{
    if (idlist.length == 0) {
        window.location.href = '{downloadurl}';
        document.getElementById('machzu').style.display = 'block';
        return;
    }
    var mymail = idlist.shift();
    mail_curr++;
    document.getElementById('current').innerHTML = mail_curr;
    p_prof.style.width = parseInt(mail_curr / mail_all * 100) + '%';
    call(fetcher_url + '&mail=' + mymail);
}

function call(url)
{
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
        text = null;
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
        text = false;
    } else {
        req = false;
    }
    if (req) {
        req.onreadystatechange = function () {
            if (req.readyState != 4) return;
            if (req.status == 200) {
                process(req.responseText);
            } else {
                alert('HTTP ' + req.status + ' / ' + reg.statusText);
            }
        }
        req.open("GET", url, true); // Specify the third paramater as true for async mode
        req.send(text);
    }
}

function process(response)
{
    nresponse = response.match(/{.+}/);
    if (!nresponse.length) {
        alert(response);
    } else {
        response = nresponse[0];
    }
    eval('next = ' + response);
    if (next['error']) {
        gna = confirm(next['error']);
        if (!gna) idlist = [];
        fetch();
    }
    if (next['got_mail']) {
        fetch();
    }
}
window.onload = init;
/*]]>*/
</script>
<div style="text-align:left;">
<br />
{about_export}<br />
<br />
</div>
<div id="bars" style="width:90%;margin:auto;padding:8px;text-align:center;">
 <div id="fetcher_outer" class="prgr_outer">
  <div id="fetcher_inner" class="prgr_inner"></div>
 </div>
 <span id="current"></span> / <span id="sum"></span>
</div>
<br />
<div id="machzu" style="display:none;">
 <button type="button" onclick="window.close();">{msg_close}</button>
</div>