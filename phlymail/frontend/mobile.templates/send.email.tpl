<div id="page_email_compose" data-answer-style="{answer_style}" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <form action="#" data-action="{sendtarget}" method="post" id="compose_email_sendform" data-confirm-no-subject="%h%confirm_no_subject%" data-error-no-rcpt="%h%confirm_no_receiver%"
            data-sending-mail="%h%EmailCreatingMail%" data-notsentsave="%h%nomailsent_savedraft%">
        <input type="hidden" name="WP_send[references]" value="{head_references}">
        <input type="hidden" name="WP_send[inreply]" value="{message_id}">
        <div data-role="header" data-position="fixed">
            <h1>{pageTitle}</h1>
            <a href="{PHP_SELF}?{passthru}" id="page_email_compose_go_home" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content" class="ui-body">
            <div data-role="fieldcontain"><!-- START on_account -->
                <select name="WP_send[from_profile]" id="compose_email_sel_fromprofile" size="1" style="width: 99%"><!-- START accmenu -->
                    <option class="vcf_{vcf}" value="{counter}"<!-- START selected --> selected<!-- END selected -->>{profilenm}</option><!-- END accmenu -->
                </select><!-- END on_account --><!-- START one_account -->
                %h%from% {from}&nbsp;({address}) <input type="hidden" name="WP_send[from_profile]" value="{profile}"><!-- END one_account -->
            </div>
            <ul data-role="listview" data-inset="true" id="email_compose_recipients_container"><!-- START predef_recipients -->
                <li>
                    <input type="hidden" name="WP_send[{target}][]" value="{address}">
                    <a href="#page_email_compose_editrecipient_menu" data-rel="popup" id="{uniqid}" data-target="{target}" data-address="{address}">
                        {target_txt} {address}
                    </a>
                </li><!-- END predef_recipients -->
            </ul>
            <div data-role="fieldcontain">
                <input type="text" id="email_compose_recipients_selector" name="WP_send[recipients]" value="" autocomplete="off" placeholder="%h%to% / CC / BCC">
            </div>
            <ul data-role="listview" data-inset="true" id="email_compose_recipients_autocomplete">
            </ul>
            <div data-role="fieldcontain">
                <input type="text" name="WP_send[subj]" id="compose_email_subject" value="{subject}" placeholder="%h%subject%">
            </div>
            <textarea id="compose_email_mbody" name="WP_send[body]" rows="14" cols="70" class="borderless_mbody">{body}</textarea>
            <div data-role="fieldcontain">
                <label for="compose_email_sel_sendvcf">{msg_sendvcf}</label>
                <select size="1" name="WP_send[sendvcf]" id="compose_email_sel_sendvcf">
                    <option value="none"<!-- START vcf_none --> selected<!-- END vcf_none -->>{msg_vcf_none}</option>
                    <option value="priv"<!-- START vcf_priv --> selected<!-- END vcf_priv -->>{msg_vcf_priv}</option>
                    <option value="busi"<!-- START vcf_busi --> selected<!-- END vcf_busi -->>{msg_vcf_busi}</option>
                    <option value="all"<!-- START vcf_all --> selected<!-- END vcf_all -->>{msg_vcf_all}</option>
                </select>
            </div>
            <div data-role="fieldcontain">
                <label for="compose_email_sel_prio">%h%prio%</label>
                <select size="1" name="WP_send[prio]" id="compose_email_sel_prio"><!-- START priomen -->
                    <option value="{prioval}"<!-- START priosel --> selected<!-- END priosel -->>{priotxt}</option><!-- END priomen -->
                </select>
            </div>

            <ul data-role="listview" data-inset="true" id="compose_email_attachcontainer">
                <li data-role="list-divider">%h%attachs%</li>
                <li>
                    <a href="#page_email_compose_upload_menu" data-future-href="#page_email_compose_addattach_menu" data-rel="popup" class="">%h%AddAttachment%</a>
                </li><!-- START origattachs --><!-- START attline -->
                <li data-icon="false">
                    <a href="#" class="attachlink" data-mimetype="{mimetype}" data-src="orig" data-filename="{filename}">
                        <img src="{small_icon}" alt="" class="ui-li-icon atticon">
                        <span class="name">{name}</span>
                    </a>
                </li><!-- END attline --><!-- START hdlattline -->
                <li data-icon="false">
                    <a href="#" class="attachlink" data-mimetype="{mimetype}" data-src="user" data-filename="{filename}">
                        <img src="{small_icon}" alt="" class="ui-li-icon atticon">
                        <span class="name">{name}</span>
                    </a>
                </li><!-- END hdlattline --><!-- END origattachs -->
                <li data-icon="false" style="display:none;" id="email_compose_attachment_tenplate">
                    <a href="#" data-src="user" data-mimetype="application/octet-stream" data-filename="noname">
                        <img src="{frontend_path}/filetypes/16/__.png" alt="" class="ui-li-icon atticon">
                        <span class="name">%h%undeffile%</span>
                    </a>
                </li>
            </ul>

        </div>

        <div data-role="footer" data-position="fixed">
            <div data-role="navbar">
                <ul>
                    <li>
                        <a href="#" id="compose_email_a_send_mail" data-is-draft="0" data-icon="send-mail" data-iconpos="top">%h%send%</a>
                    </li>
                    <li>
                        <a href="#" id="compose_email_a_save_as_draft" data-is-draft="1" data-icon="save" data-iconpos="top">%h%EmailSaveAsDraftShort%</a>
                    </li>
                    <li>
                        <a href="#" id="compose_email_a_save_as_template" data-is-draft="2" data-icon="save" data-iconpos="top">%h%EmailSaveAsTemplateShort%</a>
                    </li>
                </ul>
            </div>
        </div>
    </form>

    <div id="page_email_compose_addrecipient_menu" data-role="popup">
        <ul data-role="listview">
            <li>
                <a href="#" data-target="to" class="ohkeh">%h%to%</a>
            </li>
            <li>
                <a href="#" data-target="cc" class="ohkeh">CC</a>
            </li>
            <li>
                <a href="#" data-target="bcc" class="ohkeh">BCC</a>
            </li>
            <li data-icon="false">
                <a href="#" data-rel="back">
                    <span class="ui-icon ui-icon-custom ui-icon-arrow-l ui-icon-shadow"></span>
                    %h%CoreBack%
                </a>
            </li>
        </ul>
    </div>

    <div id="page_email_compose_editrecipient_menu" data-role="popup">
        <ul data-role="listview">
            <li>
                <a href="#" data-target="to" class="ohkeh">%h%to%</a>
            </li>
            <li>
                <a href="#" data-target="cc" class="ohkeh">CC</a>
            </li>
            <li>
                <a href="#" data-target="bcc" class="ohkeh">BCC</a>
            </li>
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

    <div id="page_email_compose_addattach_menu" data-role="popup">
        <ul data-role="listview">
            <li>
                <a href="#page_email_compose_upload_menu" data-source="upload" class="ohkeh" data-rel="popup">
                    <img src="{theme_path}/icons/files_upload.gif" class="ui-li-icon">
                    {msg_upload}
                </a>
            </li><!-- START attachreceiver -->
            <li>
                <a href="#" data-source="handler" class="ohkeh">
                    <img src="{theme_path}/icons/files_sendto.gif" class="ui-li-icon">
                    {msg_name}
                </a>
            </li><!-- END attachreceiver -->
            <li data-icon="false">
                <a href="#" data-rel="back">
                    <span class="ui-icon ui-icon-custom ui-icon-arrow-l ui-icon-shadow"></span>
                    %h%CoreBack%
                </a>
            </li>
        </ul>
    </div>

    <div id="page_email_compose_upload_menu" data-role="popup">
        <div data-role="content" class="ui-body">
            <iframe src="about:blank" id="email_compose_upload_iframe" name="email_compose_upload_iframe" width="1" height="1" frameborder="0" style="float:right;"></iframe>
            <form id="email_compose_upload_form" action="{upload_form_action}" accept-charset="utf-8" enctype="multipart/form-data" method="post" target="email_compose_upload_iframe">
                <div data-role="fieldcontain">
                    %h%MobileUploadSelectFiles%. {msg_maxfilesize}
                </div>
                <div data-role="fieldcontain">
                    <input id="email_compose_upload_upload" name="file[]" type="file" multiple directory webkitdirectory mozdirectory><!-- START maxfilesize -->
                    <input type="hidden" name="MAX_FILE_SIZE" value="{maxfilesize}" /><!-- END maxfilesize -->
                </div>
                <div data-role="fieldcontain">
                    <input data-inline="true" data-theme="b" type="submit" value="OK">
                    <a href="#page_email_compose" data-role="button" data-icon="arrow-l" data-inline="true">%h%CoreBack%</a>
                </div>
            </form>
        </div>
    </div>


</div>
