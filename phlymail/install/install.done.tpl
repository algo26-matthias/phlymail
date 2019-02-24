<!DOCTYPE html>
<html>
<head>
 <title>%h%HeadInstall%</title>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8">
 <style type="text/css"><!--
 body, p, li, td, option, div, input, button, select { font-family:Helvetica,Arial,Verdana; font-weight:normal; font-style:normal; font-size:14px; color:black; }
 td { text-align:left; vertical-align:top; padding:2px; }
 form, body { margin:0; }
 body { background-color:#EEEEEE; }
 a { text-decoration:none;font-weight:bold;color:#000040; }
 a:hover { text-decoration:underline; }
 #root { width:800px; margin:16px auto; background:white; border-radius:16px; box-shadow:2px 2px 4px darkgray; padding:8px; }
 #logo { width:266px; height:63px; display:block; float:left; background:transparent url(install/phlymail.png) 0 0 no-repeat; background-size:266px 63px; margin:0 16px 8px 0; }
 #header { height:80px; clear:both; }
 #header h2 { font-size:32px; line-height:61px; margin:0; }
 hr { border: 0; height: 1px; background:lightgray; }
 #langsel { float:right; margin:0 0 8px 16px; }
 h1, h2, h3, h4, h5 { color:rgb(4, 54, 88); font-weight: 500; }
 h3 { font-size: 20px; margin: 20px 0 4px; }
 .errorbox { color:darkred;font-weight:bold; }
 .returnbox { color:darkgreen;font-weight:normal;font-style:normal; }
 #problems, #problems ul li { color:#880000; }
 #start { border:none; border-radius:6px; box-shadow: 1px 1px 4px gray; color: white; font-weight: bold; padding: 6px 16px; background:rgb(4, 54, 88); cursor:pointer; }
 --></style>
</head>
<body>
    <div id="root">
        <div id="header">
            <div id="logo">&nbsp;</div>
            <h2>%h%HeadInstall%</h2>
        </div>
        <hr>
        <h3>%h%HeadCleanup%</h3>
        <p>
            %h%AboutFinal%
        </p>
        <p>
            %h%RemoveDir% ... <!-- START dirfail -->
            <span style="color:darkred">%h%Failed%</span><!-- END dirfail --><!-- START dirfine -->
            <span style="color:darkgreen">%h%Success%</span><!-- END dirfine -->
        </p>
        <p>
            %h%RemoveMe% ... <!-- START myfail -->
            <span style="color:darkred">%h%Failed%</span><!-- END myfail --><!-- START myfine -->
            <span style="color:darkgreen">%h%Success%</span><!-- END myfine -->
        </p><!-- START removemanually -->
        <p>
            %h%CompleteManually%
        </p><!-- END removemanually -->
        <hr>
        <h3>%h%HeadLinks%</h3>
        <p>
            %t%ExtraLinks%
        </p>
        <hr>
        <h3>%h%HeadConfig%</h3>
        <p>
            %h%InstComplete%
        </p>
        <p style="text-align:center;">
            <a href="./config.{file_ext}">%h%GoConfig%</a>
        </p>
    </div>
</body>
</html>