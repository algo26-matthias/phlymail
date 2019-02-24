<script type="text/javascript" src="{frontend_path}/js/mp3player.js?{current_build}"></script>
<script type="text/javascript">
//<![CDATA[
pathSWF = '{frontend_path}/js/mp3player.swf';
pathMP3 = '{file_url}';
cookieName = '{cookieName}';
MP3autoPlay = 'yes';
//]]>
</script>
<style type="text/css">
.audioplayer {
    width:360px;
    margin:auto;
    margin-top:20px;
    background-color:white;
    border:0 solid #648CB4;
    border-color:#1A5896;
    color:#B9D7E6;
}
</style>
<div id="phMAudioPlayer" class="audioplayer"></div>
<script type="text/javascript">/*<![CDATA[*/mp3_include('phMAudioPlayer', '{id}');/*]]>*/</script>