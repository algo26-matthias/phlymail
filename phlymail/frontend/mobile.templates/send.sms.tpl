<!-- START nosender -->
<div id="page_sms_error">
    <p class="emptymailbox">{msg_nosender}</p>
    <p><a href="{link_setup}">{msg_setup}</a></p>
</div><!-- END nosender --><!-- START normal -->
<div id="page_sms_compose" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <form action="{form_action}" method="post" id="compose_sms_sendform" data-smsmaxlen="{max_len}" data-error-norcpt="{err_norcpt}" data-error-notxt="{err_notxt}" data-error-toolong="{err_toolong}" data-txt-sending="{msg_sendmail}">
        <div data-role="header" data-position="fixed">
            <h1>{pageTitle}</h1>
            <a id="page_sms_compose_go_home" href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content" class="ui-body">            
            <div data-role="fieldcontain"> 
                %h%from% {from}
            </div>
            <ul data-role="listview" id="sms_compose_recipients_container">            
            </ul>
            <div data-role="fieldcontain">
                <input type="text" id="sms_compose_recipients_selector" name="recipients" value="" autocomplete="off" placeholder="%h%to%">
            </div>
            <ul data-role="listview" id="sms_compose_recipients_autocomplete">            
            </ul>
            <textarea id="sms_compose_mbody" name="body" rows="14" cols="70">{body}</textarea>
            <div>
                <span id="compose_sms_count_chars"></span> / {max_len} {msg_charsleft}&nbsp;|&nbsp;
                <span id="compose_sms_count_sms"></span> SMS
            </div>
            
            <div data-role="fieldcontain">
                <label for="sel_sendpause">{msg_sendpause}</label>
                <select size="1" name="sendpause" id="sel_sendpause">
                    <option value="0">0s</option>
                    <option value="2">2s</option>
                    <option value="5">5s</option>
                    <option value="10">10s</option>
                </select>            
            </div>
            
            <div data-role="fieldcontain">
                <label for="sel_savefolder">{msg_savecopy}</label>
                <select size="1" name="savefolder" id="sel_savefolder"><!-- START savefolder -->
                    <option value="{id}">{name}</option><!-- END savefolder -->
                </select>            
            </div><!-- START answerchoice -->
            
            <div data-role="fieldcontain">
                <label for="">{msg_answervia}</label>
                <input type="radio" name="answer" value="sms" id="answer_sms" checked="checked">
                <label for="answer_sms">{msg_sms}</label>
                <input type="radio" name="answer" value="email" id="answer_email">
                <label for="answer_email">{msg_email}</label>            
            </div><!-- END answerchoice -->
            
            <input type="submit" data-inline="true" data-icon="send-mail" data-theme="b" value="%h%send%">            
        </div>
    </form>                
</div>
    
<div id="page_sms_compose_editrecipient_menu" data-role="dialog">
    <ul data-role="listview">
        <li>                
            <a href="#" data-target="remove" class="ohkeh">
                <span class="ui-icon ui-icon-custom ui-icon-dustbin ui-icon-shadow"></span>
                %h%del%
            </a>
        </li>
        <li data-icon="false">
            <a href="#" data-rel="back">
                <span class="ui-icon ui-icon-custom ui-icon-arrow-l ui-icon-shadow"></span>
                %h%CoreBack%
            </a>
        </li>
    </ul>
</div>
<!-- END normal -->