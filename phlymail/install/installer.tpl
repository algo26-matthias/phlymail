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
    <script type="text/javascript" src="frontend/js/jquery/jquery-min.js"></script>
</head>
<body>
    <div id="root">
        <div id="header">
            <div id="logo">&nbsp;</div>
            <div id="langsel">
                <a href="{link_german}">%h%German%</a>&nbsp;|&nbsp;<a href="{link_english}">%h%English%</a>
            </div>
            <h2>%h%HeadInstall%</h2>
        </div>
        <hr><!-- START access -->
        <p id="problems">{Probs}</p>
        <hr><!-- END access -->
        <form enctype="multipart/form-data" action="{form_target}" method="post" id="masterform">
            <p>
                %h%Greeting%
            </p>
            <hr>
            <h3>%h%HeadStep1%</h3><!-- START generic_error -->
            <p class="errorbox">{error}</p><!-- END generic_error -->
            <p>
                %h%AboutLang%
            </p>
            <p>
                %h%Language%:
                <select name="language" size="1"><!-- START langline -->
                    <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{langname}</option><!-- END langline -->
                </select>
            </p>
            <p>
                %h%LanguageConfig%:
                <select name="language_conf" size="1"><!-- START langconfline -->
                    <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{langname}</option><!-- END langconfline -->
                </select>
            </p>
            <p>
                %h%AboutSkin%
            </p>
            <p>
                %h%Skin%:
                <select name="skin" size="1"><!-- START skinline -->
                    <option value="{skinname}"<!-- START sel --> selected="selected"<!-- END sel -->>{skinname}</option><!-- END skinline -->
                </select>
            </p>
            
            <hr>
            <h3>%h%HeadStep2%</h3>
            <p> 
                %h%AboutDriver%
            </p>
            <p>
                %h%Driver%:
                <select name="database" size="1"><!-- START driverline -->
                    <option value="{key}"<!-- START sel --> selected="selected"<!-- END sel -->>{drivername}</option><!-- END driverline -->
                </select>
            </p>
            <div id="db_driver_specific">
                {db_driver_specific}
            </div>
            <hr>
            <h3>%h%Administrator%</h3>
            <p>%h%AboutAdmin%</p>
            <table>
                <tr>
                    <td>%h%Username%</td>
                    <td><input type="text" required name="admin_name" value="{admin_name}" size="32"></td>
                </tr>
                <tr>
                    <td>%h%Password%</td>
                    <td><input type="password" required name="admin_pw_1" id="admin_pw_1" value="{admin_pw_1}" size="32"></td>
                </tr>
                <tr>
                    <td>%h%Repeat%</td>
                    <td><input type="password" required name="admin_pw_2" id="admin_pw_2" value="{admin_pw_2}" size="32"></td>
                </tr>
            </table>
            <hr>
            <p style="text-align:center;">
                <button type="submit" id="start">%h%StartInstall%</button>
            </p>
        </form>
    </div>
    <script type="text/javascript">
    // <![CDATA[
    $('#masterform').on('submit', function () {
        var formIsGo = true;
        $('input[required]').each(function () {
            if ($(this).val() == '') {
                formIsGo = false;
                return false;
            }
        });
        if (formIsGo === false) {
            alert('%j%ECompleteForm%');
            return false;
        }

        if ($('#admin_pw_1').val() != $('#admin_pw_2').val()) {
            alert('%j%PWsDoNotMatch%');
            return false;
        }
        
    });
    // ]]>
    </script>
</body>
</html>