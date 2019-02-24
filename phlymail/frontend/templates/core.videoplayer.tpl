<script type="text/javascript" src="{frontend_path}/js/AC_RunActiveContent.js?{current_build}"></script>
<script type="text/javascript">
// <![CDATA[
var FileURL = encodeURIComponent('{file_url}{id}');
AC_FL_RunContent('codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0'
        , 'width', '750', 'height', '600'
        , 'src', ((!DetectFlashVer(9, 0, 0) && DetectFlashVer(8, 0, 0)) ? '{frontend_path}/js/osflvplayer8' : '{frontend_path}/js/osflvplayer')
        , 'pluginspage', 'http://www.macromedia.com/go/getflashplayer'
        , 'id', 'flvPlayer_{id}'
        , 'allowFullScreen', 'true'
        , 'movie', ((!DetectFlashVer(9, 0, 0) && DetectFlashVer(8, 0, 0)) ? '{frontend_path}/js/osflvplayer8' : '{frontend_path}/js/osflvplayer')
        , 'FlashVars', 'bgcolor=0x051615&fgcolor=0xFC3204&volume=100&autoplay=on&autoload=on&autorewind=on&clickurl=&clicktarget=&movie=' + FileURL
        );
// ]]>
</script>