<script type="text/javascript">
/*<![CDATA[*/
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
/*]]>*/
</script>
<div id="folderbrowser">
    <div id="top">
        <div class="l">{head_select}</div>
    </div>
    <div id="container">
        {folderlist}
    </div>
    <div id="bottom">
        <div class="l">
            <button type="button" id="browse_submit" disabled="disabled" onclick="submit_folder();">{msg_select}</button>
        </div>
    </div>
</div>