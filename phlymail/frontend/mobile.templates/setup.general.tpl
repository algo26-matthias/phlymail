<div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <form action="{target_link}" method="post" data-ajax="false">
        <div data-role="header" data-position="fixed">
            <h1>%h%SetupProgrammeLong%</h1>
            <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content">
            <h1><img class="h1-icon" src="{theme_path}/images/setup_general.png" alt="" title="">%h%SetupGeneral%</h1>

            <div id="general"><!-- START has_themes -->
                <div data-role="fieldcontain">
                    <label for="sel_theme">%h%opttheme%</label>
                    <select name="theme" size="1" id="sel_theme"><!-- START skinline -->
                        <option value="{skinname}"<!-- START sel --> selected<!-- END sel -->>{skinname}</option><!-- END skinline -->
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="sel_mobile_theme">%h%MobileTheme%</label>
                    <select name="mobile_theme" id="sel_mobile_theme" size="1"><!-- START mobiletheme -->
                        <option value="{name}"<!-- START sel --> selected<!-- END sel -->>{name}</option><!-- END mobiletheme -->
                    </select>
                </div><!-- END has_themes -->
                <div data-role="fieldcontain">
                    <label for="sel_lang">%h%optlang%</label>
                    <select name="lang" id="sel_lang" size="1"><!-- START langline -->
                        <option value="{id}"<!-- START sel --> selected<!-- END sel -->>{langname}</option><!-- END langline -->
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="sel_timezone">%h%TimeZone%</label>
                    <select size="1" name="timezone" id="sel_timezone"><!-- START timezone -->
                        <option value="{zone}"<!-- START sel --> selected<!-- END sel -->>{zonename}</option><!-- END timezone -->
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_pagesize">%h%optpagesize%</label>
                    <input type="text" id="inp_pagesize" name="pagesize" value="{pagesize}" size="3" maxlength="6">
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_externalemail">%h%sysextemail%</label>
                    <input type="text" id="inp_externalemail" name="externalemail" size="24" maxlength="255" value="{externalemail}">
                </div>

                <hr>

                <div data-role="fieldcontain">
                    <label for="inp_passwd_1">%h%sysnewpass%</label>
                    <input type="password" id="inp_passwd_1" autocomplete="off" name="pw" size="24" maxlength="32">
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_passwd_2">%h%syspass2%</label>
                    <input type="password" id="inp_passwd_2" autocomplete="off" name="pw2" size="24" maxlength="32">
                </div>

                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <legend>%h%onLogout%</legend>
                        <input type="checkbox" name="logout_showprompt" value="1" id="lbl_lo_showprompt"<!-- START logoutshowprompt --> checked<!-- END logoutshowprompt -->>
                        <label for="lbl_lo_showprompt">&nbsp;%h%onLogoutShowPrompt%</label>
                        <input type="checkbox" name="emptytrash" value="1" id="lbl_trash"<!-- START emptytrash --> checked<!-- END emptytrash -->>
                        <label for="lbl_trash">&nbsp;%h%ActionEmptyTrash%</label>
                        <input type="checkbox" name="emptyjunk" value="1" id="lbl_junk"<!-- START emptyjunk --> checked<!-- END emptyjunk -->>
                        <label for="lbl_junk">&nbsp;%h%ActionEmptyJunk%</label>
                    </fieldset>
                </div>
            </div>

            <h1><img class="h1-icon" src="{theme_path}/images/setup_reading.png" alt="" title="">%h%SetupReading%</h1>

            <div id="reading">
                <div data-role="fieldcontain">
                    <label for="sel_mdn_behaviour">%h%optmdnbehaviour%</label>
                    <select name="mdn_behaviour" id="sel_mdn_behaviour" size="1"><!-- START mdnline -->
                        <option value="{behaviour}"<!-- START sel --> selected<!-- END sel -->>{behaviourname}</option><!-- END mdnline -->
                    </select>
                </div>
                <div data-role="fieldcontain">
                    <label for="sel_email_preferred_part">%h%optpreferred%</label>
                    <select name="email_preferred_part" id="sel_email_preferred_part" size="1"><!-- START mailpreferredpart -->
                        <option value="{part}"<!-- START sel --> selected<!-- END sel -->>{partname}</option><!-- END mailpreferredpart -->
                    </select>
                </div>
                <input type="checkbox" name="parsesmileys" value="1" id="lbl_parsesmileys"<!-- START parsesmileys --> checked<!-- END parsesmileys -->>
                <label for="lbl_parsesmileys">&nbsp;%h%ParseSmileys%</label>

                <input type="checkbox" name="parseformat" value="1" id="lbl_parseformat"<!-- START parseformat --> checked<!-- END parseformat -->>
                <label for="lbl_parseformat">&nbsp;%t%ParsePlaintextFormats%</label>

                <input type="checkbox" name="collapse_threads" value="1" id="lbl_collapse_threads"<!-- START collapse_threads --> checked<!-- END collapse_threads -->>
                <label for="lbl_collapse_threads">&nbsp;%h%EmailCollapseThreads%</label>
            </div>

            <h1><img class="h1-icon" src="{theme_path}/images/setup_composing.png" alt="" title="">%h%SetupComposing%</h1>


            <div id="composing">
                <input type="checkbox" name="receiptout" value="1" id="lbl_receipt"<!-- START receipt --> checked<!-- END receipt -->>
                <label for="lbl_receipt">&nbsp;%h%optreceipt%</label>
                <input type="checkbox" name="reply_dontcutsignatures" value="1" id="lbl_reply_dontcutsignatures"<!-- START reply_dontcutsignatures --> checked<!-- END reply_dontcutsignatures -->>
                <label for="lbl_reply_dontcutsignatures">&nbsp;%h%ReplyDoNotCutSignatures%</label>
                <input type="checkbox" name="replysamefolder" value="1" id="lbl_replysamefolder"<!-- START replysamefolder --> checked<!-- END replysamefolder -->>
                <label for="lbl_replysamefolder">&nbsp;%h%StoreReplySameFolder%</label>
                <input type="checkbox" name="email_delete_markread" value="1" id="lbl_email_delete_markread"<!-- START email_delete_markread --> checked<!-- END email_delete_markread -->>
                <label for="lbl_email_delete_markread">&nbsp;%h%MarkReadBeforeDelete%</label>

                <div data-role="fieldcontain">
                    <label for="sel_answer_style">%h%SigPos%</label>
                    <select size="1" name="answer_style" id="sel_answer_style">
                        <option value="default"<!-- START answer_style_default --> selected<!-- END answer_style_default -->>%h%SigBottom%</option>
                        <option value="tofu"<!-- START answer_style_tofu --> selected<!-- END answer_style_tofu -->>%h%SigTop%</option>
                    </select>
                </div><!-- START smssender -->

                <hr>
                <div data-role="fieldcontain">
                    <label for="inp_smssender">%h%SMSSender%</label>
                    <input type="text" name="smssender" id="inp_smssender" size="24" maxlength="24" value="{sms_sender}">
                </div><!-- END smssender -->
                <div data-role="fieldcontain">
                    <label for="sel_sentfolder_sms">%h%CoreSentFolderSMS%</label>
                    <select size="1" name="sentfolder_sms" id="sel_sentfolder_sms">
                        <option value="0">%h%Standard%</option><!-- START sentfolder_sms -->
                        <option value="{id}"<!-- START sel --> selected<!-- END sel -->>{name}</option><!-- END sentfolder_sms -->
                    </select>
                </div><!-- START faxsender -->
                <hr>
                <div data-role="fieldcontain">
                    <label for="inp_faxsender">%h%FaxSender%</label>
                    <input type="text" name="faxsender" id="inp_faxsender" size="24" maxlength="24" value="{fax_sender}">
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_faxsendername">%h%FaxSenderName%</label>
                    <input type="text" name="faxsendername" id="inp_faxsendername" size="24" maxlength="24" value="{fax_sender_name}">
                </div>
                <div data-role="fieldcontain">
                    <label for="inp_faxstatusemail">%h%FaxStatusEmailTo%</label>
                    <input type="text" name="faxstatusemail" id="inp_faxstatusemail" size="24" maxlength="255" value="{fax_status_email}">
                </div><!-- END faxsender -->
                <div data-role="fieldcontain">
                    <label for="sel_sentfolder_fax">%h%CoreSentFolderFax%</label>
                    <select size="1" name="sentfolder_fax" id="sel_sentfolder_fax">
                        <option value="0">%h%Standard%</option><!-- START sentfolder_fax -->
                        <option value="{id}"<!-- START sel --> selected<!-- END sel -->>{name}</option><!-- END sentfolder_fax -->
                    </select>
                </div>
            </div>

            <h1><img class="h1-icon" src="{theme_path}/images/setup_archive.png" alt="" title="">%h%SetupArchive%</h1>

            <div id="archive">
                <input type="checkbox" name="archive_override_delete" value="1" id="lbl_archive_override_delete"<!-- START archive_override_delete --> checked<!-- END archive_override_delete -->>
                <label for="lbl_archive_override_delete">&nbsp;%h%ArchiveOverrideDelete%</label>
                <hr>
                %h%AboutArchiveOverrideDelete%
                <hr>
                <input type="checkbox" name="archive_mimic_foldertree" value="1" id="lbl_archive_mimic_foldertree"<!-- START archive_mimic_foldertree --> checked<!-- END archive_mimic_foldertree -->>
                <label for="lbl_archive_mimic_foldertree">&nbsp;%h%ArchiveMimicFolderTree%</label>
                <input type="checkbox" name="archive_partition_by_year" value="1" id="lbl_archive_partition_by_year"<!-- START archive_partition_by_year --> checked<!-- END archive_partition_by_year -->>
                <label for="lbl_archive_partition_by_year">&nbsp;%h%ArchivePartitionByYear%</label>

                <hr>
                <h4>%h%HeadAutoArchive%</h4>
                 %h%AboutAutoArchive%
                <br>
                <input type="checkbox" name="archive_email_autoarchive" value="1" id="lbl_archive_email_autoarchive"<!-- START archive_email_autoarchive --> checked="checked"<!-- END archive_email_autoarchive --> />
                <label for="lbl_archive_email_autoarchive">&nbsp;%h%AutoArchiveMailsOlderThan%</label>

                <input type="number" min="0" name="archive_email_autoarchive_age_inp" value="{archive_email_autoarchive_age}" max="99999">
                <select size="1" name="archive_email_autoarchive_age_drop"><!-- START archive_email_autoarchive_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_email_autoarchive_age_drop -->
                </select>
                <br>

                <input type="checkbox" name="archive_email_autodelete" value="1" id="lbl_archive_email_autodelete"<!-- START archive_email_autodelete --> checked="checked"<!-- END archive_email_autodelete --> />
                <label for="lbl_archive_email_autodelete">&nbsp;%h%AutoDeleteMailsOlderThan%</label>

                <input type="number" min="0" name="archive_email_autodelete_age_inp" value="{archive_email_autodelete_age}" max="99999">
                <select size="1" name="archive_email_autodelete_age_drop"><!-- START archive_email_autodelete_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_email_autodelete_age_drop -->
                </select>
                <br>

                <input type="checkbox" name="archive_calendar_autoarchive" value="1" id="lbl_archive_calendar_autoarchive"<!-- START archive_calendar_autoarchive --> checked="checked"<!-- END archive_calendar_autoarchive --> />
                <label for="lbl_archive_calendar_autoarchive">&nbsp;%h%AutoArchiveEventsOlderThan%</label>

                <input type="number" min="0" name="archive_calendar_autoarchive_age_inp" value="{archive_calendar_autoarchive_age}" max="99999">
                <select size="1" name="archive_calendar_autoarchive_age_drop"><!-- START archive_calendar_autoarchive_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_calendar_autoarchive_age_drop -->
                </select>
                <br>

                <input type="checkbox" name="archive_calendar_autodelete" value="1" id="lbl_archive_calendar_autodelete"<!-- START archive_calendar_autodelete --> checked="checked"<!-- END archive_calendar_autodelete --> />
                <label for="lbl_archive_calendar_autodelete">&nbsp;%h%AutoDeleteEventsOlderThan%</label>

                <input type="number" min="0" name="archive_calendar_autodelete_age_inp" value="{archive_calendar_autodelete_age}" max="99999">
                <select size="1" name="archive_calendar_autodelete_age_drop"><!-- START archive_calendar_autodelete_age_drop -->
                    <option value="{unit}"<!-- START sel --> selected="selected"<!-- END sel -->>{name}</option><!-- END archive_calendar_autodelete_age_drop -->
                </select>
            </div>

            <h1><img class="h1-icon" src="{theme_path}/images/setup_2fa.png" alt="" title="">%h%Setup2FA%</h1>

            <div id="2fa">
                <p>%h%2FaAbout%</p>
                <div data-role="fieldcontain">
                    <label>
                        <input type="radio" name="2fa_mode" value="none"<!-- START 2fa_mode_none --> checked<!-- END 2fa_mode_none -->> <strong>%h%2FaNone%</strong>
                    </label>
                </div>
                <div<!-- START 2fa_no_sms --> style="display:none;"<!-- END 2fa_no_sms -->>
                    <hr>
                    <div data-role="fieldcontain">
                        <label>
                            <input type="radio" name="2fa_mode" value="sms"<!-- START 2fa_mode_sms --> checked<!-- END 2fa_mode_sms -->> <strong>%h%2FaModeSms%</strong>
                        </label>
                    </div>
                    <div>%h%2FaModeSmsAbout%</div><!-- START 2fa_register_sms -->
                    <div data-role="fieldcontain">
                        %h%2FaModeSmsRegister% <input type="text" name="2fa_sms_register" size="16" maxlength="18" value="{2fa_sms_register}">
                    </div><!-- END 2fa_register_sms --><!-- START 2fa_verify_sms -->
                    <div data-role="fieldcontain">
                        %h%2FaModeSmsEnterCode% <input type="text" name="2fa_sms_verify" size="8" maxlength="12" value="" autocomplete="off">
                        <label><input type="checkbox" name="2fa_sms_resendverify" value="1"> %h%2FaModeSmsResendCode%</label>
                    </div><!-- END 2fa_verify_sms --><!-- START 2fa_registered_sms -->
                    <div data-role="fieldcontain">
                        %h%2FaModeSmsSendTo% <em>{2fa_sms_to}</em>
                        <label><input type="checkbox" name="2fa_sms_unregister" value="1"> %h%2FaModeSmsUnregister%</label>
                    </div><!-- END 2fa_registered_sms -->
                </div>
                <div<!-- START 2fa_no_u2f --> style="display:none;"<!-- END 2fa_no_u2f -->>
                    <hr>
                    <div data-role="fieldcontain">
                        <label>
                            <input type="radio" name="2fa_mode" value="u2f"<!-- START 2fa_mode_u2f --> checked<!-- END 2fa_mode_u2f -->> <strong>%h%2FaModeYubikey%</strong>
                        </label>
                    </div>
                    <div>%h%2FaModeYubikeyAbout%</div><!-- START 2fa_register_u2f -->
                    <div data-role="fieldcontain">
                        %h%2FaModeYubikeyRegister% <input type="text" name="2fa_u2f_register" value="" autocomplete="off">
                    </div><!-- END 2fa_register_u2f --><!-- START 2fa_registered_u2f -->
                    <div data-role="fieldcontain">
                        %h%2FaModeYubikeyRegistered% <em>{2fa_u2f_serial}</em>
                    </div><!-- END 2fa_registered_u2f -->
                </div>
            </div>

        </div>

        <div data-role="footer" class="ui-bar" data-position="fixed">
            <button type="submit" data-icon="check">%h%save%</button>
        </div>
    </form>
</div>