<script type="text/javascript">
/*<![CDATA[*/
switched_on = false;
function newmail_playsound(filename)
{
    if (!filename) filename = 'default_newmail.mp3';
    var ph = document.getElementById('preview_holder');
    ph.innerHTML = '';
    ph.innerHTML = '<object id="preview_player" width="8" height="8" uiMode="none"'
            + ' type="application/x-shockwave-flash"'
            + ' data="{frontend_path}/js/bgsoundplay.swf?file={frontend_path}/sounds/' + filename + '"'
            + '><param name="movie" value="{frontend_path}/js/bgsoundplay.swf?file={frontend_path}/sounds/' + filename + '" /><'
            + 'embed src="{frontend_path}/js/bgsoundplay.swf?file={frontend_path}/sounds/' + filename + '" width="1" height="1" /><'
            + '/object>';
}

function change_preview()
{
    newmail_playsound($('#sound_for_preview').val());
}

function switch_on(token)
{
    if (switched_on != false) {
        $('#win_' + switched_on).hide();
        $('#men_' + switched_on).removeClass('marked');
    }
    switched_on = token;
    $('#win_' + switched_on).show();
    $('#men_' + switched_on).addClass('marked');
    $('#hid_initial_tabulator').val(token);
}

$(document).ready(function (e) {
    adjust_height();
    var initTab = $('#hid_initial_tabulator').val();
    if (initTab.length == 0) {
        initTab = 'general';
    }
    switch_on(initTab);

    window.setTimeout('$("#result_message").css("visibility", "hidden");', 7000);
});
/*]]>*/
</script>
<form action="{target_link}" method="post">
    <input type="hidden" id="hid_initial_tabulator" name="init_tab" value="{initital_tabulator}" />
    <div id="general_setup_tabs" class="sendmenuborder inboxline">
        <div id="men_general" class="menuline" onclick="switch_on('general')">
            <img src="{theme_path}/images/setup_general.png" alt="%h%SetupGeneral%" title="%h%SetupGeneral%" />
        </div>
        <div id="men_reading" class="menuline" onclick="switch_on('reading')">
            <img src="{theme_path}/images/setup_reading.png" alt="%h%SetupReading%" title="%h%SetupReading%" />
        </div>
        <div id="men_composing" class="menuline" onclick="switch_on('composing')">
            <img src="{theme_path}/images/setup_composing.png" alt="%h%SetupComposing%" title="%h%SetupComposing%" />
        </div>
        <div id="men_archive" class="menuline" onclick="switch_on('archive')">
            <img src="{theme_path}/images/setup_archive.png" alt="%h%SetupArchive%" title="%h%SetupArchive%" />
        </div>
        <div id="men_2fa" class="menuline" onclick="switch_on('2fa')">
            <img src="{theme_path}/images/setup_2fa.png" alt="%h%Setup2FA%" title="%h%Setup2FA%" />
        </div>
    </div>
    <div id="general_setup_container" class="sendmenuborder">

        <div id="win_general">
            <table cellspacing="0" cellpadding="2" border="0"><!-- START has_themes -->
                <tr>
                    <td>%h%opttheme%</td>
                    <td>
                        <select name="theme" size="1"><!-- START skinline -->
                            <option value="{skinname}"<!-- START sel --> selected="selected"<!-- END sel -->>{skinname}</option><!-- END skinline -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>%h%MobileTheme%</td>
                    <td>
                        <select name="mobile_theme" size="1"><!-- START mobiletheme -->
                            <option value="{name}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END mobiletheme -->
                        </select>
                    </td>
                </tr><!-- END has_themes -->
                <tr>
                    <td>%h%optlang%</td>
                    <td>
                        <select name="lang" size="1"><!-- START langline -->
                            <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{langname}</option><!-- END langline -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>%h%TimeZone%</td>
                    <td>
                        <select size="1" name="timezone"><!-- START timezone -->
                            <option value="{zone}"<!-- START sel --> selected="selected"<!-- END sel -->>{zonename}</option><!-- END timezone -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>%h%optpagesize%</td>
                    <td>
                        <input type="text" name="pagesize" value="{pagesize}" size="3" maxlength="6" />
                    </td>
                </tr>
                <tr>
                     <td>%h%sysextemail%</td>
                     <td><input type="text" name="externalemail" size="24" maxlength="255" value="{externalemail}" /></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                     <td>%h%sysnewpass%</td>
                     <td><input type="password" autocomplete="off" name="pw" size="24" maxlength="32" /></td>
                </tr>
                <tr>
                    <td>%h%syspass2%</td>
                    <td><input type="password" autocomplete="off" name="pw2" size="24" maxlength="32" /></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>%h%onLogin%</strong></td>
                </tr>
                <tr>
                    <td>%h%LoginFolder%</td>
                    <td>
                        <select size="1" name="loginfolder">
                            <option value="core::root">%h%CorePinboard%</option><!-- START loginfolder -->
                            <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END loginfolder -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><strong>%h%onLogout%</strong></td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;<input type="checkbox" name="logout_showprompt" value="1" id="lbl_lo_showprompt"<!-- START logoutshowprompt --> checked="checked"<!-- END logoutshowprompt --> />
                        <label for="lbl_lo_showprompt">&nbsp;%h%onLogoutShowPrompt%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;<input type="checkbox" name="emptytrash" value="1" id="lbl_trash"<!-- START emptytrash --> checked="checked"<!-- END emptytrash --> />
                        <label for="lbl_trash">&nbsp;%h%ActionEmptyTrash%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;<input type="checkbox" name="emptyjunk" value="1" id="lbl_junk"<!-- START emptyjunk --> checked="checked"<!-- END emptyjunk --> />
                        <label for="lbl_junk">&nbsp;%h%ActionEmptyJunk%</label>
                    </td>
                </tr>
            </table>
        </div>
        <div id="win_reading">
            <table cellspacing="0" cellpadding="2" border="0">
                <tr>
                    <td>%h%PlaintextFontstyle%</td>
                    <td>
                        <select size="1" name="fontface"><!-- START fontface -->
                            <option value="{face}"<!-- START sel --> selected="selected"<!-- END sel -->>{face}</option><!-- END fontface -->
                        </select>&nbsp;
                        <input type="text" name="fontsize" value="{fontsize}" size="2" maxlength="3" />&nbsp;pt
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <input type="checkbox" name="teletype" value="1" id="lbl_teletype"<!-- START teletype --> checked="checked"<!-- END teletype --> />
                        <label for="lbl_teletype">&nbsp;%h%TeletypeFont%</label>
                    </td>
                </tr>
                <tr>
                    <td>%h%optmdnbehaviour%</td>
                    <td>
                        <select name="mdn_behaviour" size="1"><!-- START mdnline -->
                            <option value="{behaviour}"<!-- START sel --> selected="selected"<!-- END sel -->>{behaviourname}</option><!-- END mdnline -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>%h%optpreferred%</td>
                    <td>
                        <select name="email_preferred_part" size="1"><!-- START mailpreferredpart -->
                            <option value="{part}"<!-- START sel --> selected="selected"<!-- END sel -->>{partname}</option><!-- END mailpreferredpart -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="parsesmileys" value="1" id="lbl_parsesmileys"<!-- START parsesmileys --> checked="checked"<!-- END parsesmileys --> />
                        <label for="lbl_parsesmileys">&nbsp;%h%ParseSmileys%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="parseformat" value="1" id="lbl_parseformat"<!-- START parseformat --> checked="checked"<!-- END parseformat --> />
                        <label for="lbl_parseformat">&nbsp;%t%ParsePlaintextFormats%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="collapse_threads" value="1" id="lbl_collapse_threads"<!-- START collapse_threads --> checked="checked"<!-- END collapse_threads --> />
                        <label for="lbl_collapse_threads">&nbsp;%h%EmailCollapseThreads%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="automarkread" value="1" id="lbl_automark"<!-- START automarkread --> checked="checked"<!-- END automarkread --> />
                        <label for="lbl_automark">&nbsp;%h%AutoMarkRead%</label>
                        <input type="text" name="automarkread_time" value="{automarkread_time}" size="3" maxlength="6" />&nbsp;%h%AutoMarkRead2%
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="usepreview" value="1" id="lbl_preview"<!-- START preview --> checked="checked"<!-- END preview --> />
                        <label for="lbl_preview">&nbsp;%h%folders_use_preview%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="showattachmentinline" value="1" id="lbl_showinline"<!-- START showattachmentinline --> checked="checked"<!-- END showattachmentinline --> />
                        <label for="lbl_showinline">&nbsp;%h%showattachmentinline%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>%h%newmail_showalert%</strong></td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;<input type="checkbox" name="alertmail" value="1" id="lbl_alert"<!-- START alertmail --> checked="checked"<!-- END alertmail --> />
                        <label for="lbl_alert">&nbsp;%h%newmail_alertpopup%</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;<input type="checkbox" name="soundmail" value="1" id="lbl_sound"<!-- START soundmail --> checked="checked"<!-- END soundmail --> />
                        <label for="lbl_sound">&nbsp;%h%newmail_alertsound%</label>
                        <select size="1" id="sound_for_preview" name="soundname">
                            <option value="">%h%Standard%</option><!-- START soundnames -->
                            <option value="{name}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END soundnames -->
                        </select>
                        <img src="{theme_path}/icons/play_sound.gif" onclick="change_preview()" alt="" style="cursor:pointer;" />
                        <div id="preview_holder" style="width:0;height:0;border:0;"></div>
                    </td>
                </tr>
            </table>
        </div>
        <div id="win_composing">
            <table cellspacing="0" cellpadding="2" border="0">
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="receiptout" value="1" id="lbl_receipt"<!-- START receipt --> checked="checked"<!-- END receipt --> />
                        <label for="lbl_receipt">&nbsp;%h%optreceipt%</label>
                        <br />
                        <input type="checkbox" name="sendhtml" value="1" id="lbl_html"<!-- START sendhtml --> checked="checked"<!-- END sendhtml --> />
                        <label for="lbl_html">&nbsp;%h%EmailSendHTML%</label>
                        <br />
                        <input type="checkbox" name="answersamewin" value="1" id="lbl_answersamewin"<!-- START answersamewin --> checked="checked"<!-- END answersamewin --> />
                        <label for="lbl_answersamewin">&nbsp;%h%ReplyInSameWin%</label>
                        <br />
                        <input type="checkbox" name="reply_dontcutsignatures" value="1" id="lbl_reply_dontcutsignatures"<!-- START reply_dontcutsignatures --> checked="checked"<!-- END reply_dontcutsignatures --> />
                        <label for="lbl_reply_dontcutsignatures">&nbsp;%h%ReplyDoNotCutSignatures%</label>
                        <br />
                        <input type="checkbox" name="replysamefolder" value="1" id="lbl_replysamefolder"<!-- START replysamefolder --> checked="checked"<!-- END replysamefolder --> />
                        <label for="lbl_replysamefolder">&nbsp;%h%StoreReplySameFolder%</label>
                        <br />
                        <input type="checkbox" name="email_delete_markread" value="1" id="lbl_email_delete_markread"<!-- START email_delete_markread --> checked="checked"<!-- END email_delete_markread --> />
                        <label for="lbl_email_delete_markread">&nbsp;%h%MarkReadBeforeDelete%</label>
                    </td>
                </tr>
                <tr>
                    <td>%h%SigPos%</td>
                    <td>
                        <select size="1" name="answer_style">
                            <option value="default"<!-- START answer_style_default --> selected="selected"<!-- END answer_style_default -->>%h%SigBottom%</option>
                            <option value="tofu"<!-- START answer_style_tofu --> selected="selected"<!-- END answer_style_tofu -->>%h%SigTop%</option>
                        </select>
                    </td>
                </tr><!-- START smssender -->
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                     <td>%h%SMSSender%</td>
                     <td><input type="text" name="smssender" size="24" maxlength="24" value="{sms_sender}" /></td>
                </tr><!-- END smssender -->
                <tr>
                    <td>%h%CoreSentFolderSMS%</td>
                    <td>
                        <select size="1" name="sentfolder_sms">
                            <option value="0">%h%Standard%</option><!-- START sentfolder_sms -->
                            <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END sentfolder_sms -->
                        </select>
                    </td>
                </tr><!-- START faxsender -->
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                     <td>%h%FaxSender%</td>
                     <td>
                        <input type="text" name="faxsender" size="24" maxlength="24" value="{fax_sender}" />
                     </td>
                </tr>
                <tr>
                     <td>%h%FaxSenderName%</td>
                     <td>
                        <input type="text" name="faxsendername" size="24" maxlength="24" value="{fax_sender_name}" />
                     </td>
                </tr>
                <tr>
                     <td>%h%FaxStatusEmailTo%</td>
                     <td>
                        <input type="text" name="faxstatusemail" size="24" maxlength="255" value="{fax_status_email}" />
                     </td>
                </tr><!-- END faxsender -->
                <tr>
                    <td>%h%CoreSentFolderFax%</td>
                    <td>
                        <select size="1" name="sentfolder_fax">
                            <option value="0">%h%Standard%</option><!-- START sentfolder_fax -->
                            <option value="{id}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END sentfolder_fax -->
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div id="win_archive">
            <p>
                <input type="checkbox" name="archive_override_delete" value="1" id="lbl_archive_override_delete"<!-- START archive_override_delete --> checked="checked"<!-- END archive_override_delete --> />
                <label for="lbl_archive_override_delete">&nbsp;%h%ArchiveOverrideDelete%</label>
                <br />
                %h%AboutArchiveOverrideDelete%
            </p>
            <p>
                <input type="checkbox" name="archive_mimic_foldertree" value="1" id="lbl_archive_mimic_foldertree"<!-- START archive_mimic_foldertree --> checked="checked"<!-- END archive_mimic_foldertree --> />
                <label for="lbl_archive_mimic_foldertree">&nbsp;%h%ArchiveMimicFolderTree%</label>
            </p>
            <p>
                <input type="checkbox" name="archive_partition_by_year" value="1" id="lbl_archive_partition_by_year"<!-- START archive_partition_by_year --> checked="checked"<!-- END archive_partition_by_year --> />
                <label for="lbl_archive_partition_by_year">&nbsp;%h%ArchivePartitionByYear%</label>
            </p>
            <p>
                <strong>%h%HeadAutoArchive%</strong><br />
                %h%AboutAutoArchive%
            </p>
            <p>
                <input type="checkbox" name="archive_email_autoarchive" value="1" id="lbl_archive_email_autoarchive"<!-- START archive_email_autoarchive --> checked="checked"<!-- END archive_email_autoarchive --> />
                <label for="lbl_archive_email_autoarchive">&nbsp;%h%AutoArchiveMailsOlderThan%</label>

                <input type="number" min="0" name="archive_email_autoarchive_age_inp" value="{archive_email_autoarchive_age}" max="99999">
                <select size="1" name="archive_email_autoarchive_age_drop"><!-- START archive_email_autoarchive_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_email_autoarchive_age_drop -->
                </select>
                <br />

                <input type="checkbox" name="archive_email_autodelete" value="1" id="lbl_archive_email_autodelete"<!-- START archive_email_autodelete --> checked="checked"<!-- END archive_email_autodelete --> />
                <label for="lbl_archive_email_autodelete">&nbsp;%h%AutoDeleteMailsOlderThan%</label>

                <input type="number" min="0" name="archive_email_autodelete_age_inp" value="{archive_email_autodelete_age}" max="99999">
                <select size="1" name="archive_email_autodelete_age_drop"><!-- START archive_email_autodelete_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_email_autodelete_age_drop -->
                </select>
                <br />

                <input type="checkbox" name="archive_calendar_autoarchive" value="1" id="lbl_archive_calendar_autoarchive"<!-- START archive_calendar_autoarchive --> checked="checked"<!-- END archive_calendar_autoarchive --> />
                <label for="lbl_archive_calendar_autoarchive">&nbsp;%h%AutoArchiveEventsOlderThan%</label>

                <input type="number" min="0" name="archive_calendar_autoarchive_age_inp" value="{archive_calendar_autoarchive_age}" max="99999">
                <select size="1" name="archive_calendar_autoarchive_age_drop"><!-- START archive_calendar_autoarchive_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_calendar_autoarchive_age_drop -->
                </select>
                <br />

                <input type="checkbox" name="archive_calendar_autodelete" value="1" id="lbl_archive_calendar_autodelete"<!-- START archive_calendar_autodelete --> checked="checked"<!-- END archive_calendar_autodelete --> />
                <label for="lbl_archive_calendar_autodelete">&nbsp;%h%AutoDeleteEventsOlderThan%</label>

                <input type="number" min="0" name="archive_calendar_autodelete_age_inp" value="{archive_calendar_autodelete_age}" max="99999">
                <select size="1" name="archive_calendar_autodelete_age_drop"><!-- START archive_calendar_autodelete_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_calendar_autodelete_age_drop -->
                </select>
            </p>
        </div>

        <div id="win_2fa">
            <p>%h%2FaAbout%</p>
            <div>
                <label>
                    <input type="radio" name="2fa_mode" value="none"<!-- START 2fa_mode_none --> checked<!-- END 2fa_mode_none -->> <strong>%h%2FaNone%</strong>
                </label>
            </div>
            <div<!-- START 2fa_no_sms --> style="display:none;"<!-- END 2fa_no_sms -->>
                <hr>
                <div>
                    <label>
                        <input type="radio" name="2fa_mode" value="sms"<!-- START 2fa_mode_sms --> checked<!-- END 2fa_mode_sms -->> <strong>%h%2FaModeSms%</strong>
                    </label>
                </div>
                <div>%h%2FaModeSmsAbout%</div><!-- START 2fa_register_sms -->
                <div>
                    %h%2FaModeSmsRegister% <input type="text" name="2fa_sms_register" size="16" maxlength="18" value="{2fa_sms_register}">
                </div><!-- END 2fa_register_sms --><!-- START 2fa_verify_sms -->
                <div>
                    %h%2FaModeSmsEnterCode% <input type="text" name="2fa_sms_verify" size="8" maxlength="12" value="" autocomplete="off">
                    <label><input type="checkbox" name="2fa_sms_resendverify" value="1"> %h%2FaModeSmsResendCode%</label>
                </div><!-- END 2fa_verify_sms --><!-- START 2fa_registered_sms -->
                <div>
                    %h%2FaModeSmsSendTo% <em>{2fa_sms_to}</em>
                    <label><input type="checkbox" name="2fa_sms_unregister" value="1"> %h%2FaModeSmsUnregister%</label>
                </div><!-- END 2fa_registered_sms -->
            </div>
            <div<!-- START 2fa_no_u2f --> style="display:none;"<!-- END 2fa_no_u2f -->>
                <hr>
                <div>
                    <label>
                        <input type="radio" name="2fa_mode" value="u2f"<!-- START 2fa_mode_u2f --> checked<!-- END 2fa_mode_u2f -->> <strong>%h%2FaModeYubikey%</strong>
                    </label>
                </div>
                <div>%h%2FaModeYubikeyAbout%</div><!-- START 2fa_register_u2f -->
                <div>
                    %h%2FaModeYubikeyRegister% <input type="text" name="2fa_u2f_register" value="" autocomplete="off">
                </div><!-- END 2fa_register_u2f --><!-- START 2fa_registered_u2f -->
                <div>
                    %h%2FaModeYubikeyRegistered% <em>{2fa_u2f_serial}</em>
                </div><!-- END 2fa_registered_u2f -->
            </div>
        </div>
    </div>

    <div style="float:right;padding:4px;">
        <strong id="result_message">{WP_return}</strong>&nbsp;&nbsp;
        <input type="submit" value="%h%save%" />
    </div>
</form>