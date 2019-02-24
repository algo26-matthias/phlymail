<div id="page_email_read" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <div data-role="header" data-position="fixed">
        <a href="javascript:history.back();" class="ui-btn-left ui-btn-back" data-icon="arrow-l" rel="external">%h%CoreBack%</a>
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        <a href="{hlink_levelup}" id="a_skim_levelup" style="display:none" data-icon="arrow-u" data-iconpos="notext" data-direction="reverse">Up</a>
    </div>
    <div data-role="content" class="ui-body">
        <div id="email_read_icons"><!-- START priority_icon -->
            <img src="{src}" alt="{alt}"><!-- END priority_icon --><!-- START has_attach -->
            <span id="taptoatt" class="ui-icon ui-icon-custom ui-icon-attachment">&nbsp;</span><!-- END has_attach --><!-- START has_colour -->
            <span class="cmark_square cmark_{colour}"></span><!-- END has_colour -->
        </div>
        <div id="kopfzeilen">
            <div class="table"><!-- START headerlines -->
                <div class="tr hl_{hl_name}">
                    <div class="td t l nowrap">{hl_key}:</div>
                    <div class="td t l {hl_add}">{hl_val}</div>
                </div><!-- END headerlines -->
            </div>
        </div>
        <div id="kopfzeilen_kompakt">
        </div>
        <hr>
        <div id="mailbody">
            {mbody}
        </div><!-- START attachblock -->
        <ul data-role="listview" data-inset="true" id="attachlist">
            <li data-role="list-divider">
                %h%attachs%
            </li><!-- START attachline -->
            <li><!-- START inline --><!-- END inline -->
                <a class="attachlink" href="#page_email_read_attach_context" data-rel="dialog" data-transition="pop" data-hdllist="{hdllist_js}" data-resid="{resid}" data-ref="{link_target}">
                    <img src="{frontend_path}/filetypes/32/{att_icon}" alt="{att_icon_alt}" class="ui-li-icon">
                    <h3>{att_name}</h3>
                    <p><strong>{att_size} {msg_att_type}: {att_type}</strong></p>
                </a>
            </li><!-- END attachline -->
        </ul><!-- END attachblock -->
    </div>
    <div data-role="footer" data-position="fixed">
        <div data-role="navbar" id="mailactions">
            <ul><!-- START skim_next -->
                <li>
                    <a href="{link_next}" id="a_skim_next" data-icon="arrow-l" rel="external" data-iconpos="top" data-direction="slideup">%h%EmailNext%</a>
                </li><!-- END skim_next -->
                <li>
                    <a href="{hlink_mailops}delete" id="page_email_read_action_delete" data-icon="dustbin" data-iconpos="top">%h%del%</a>
                </li>
                <li>
                    <a href="#page_email_read_action_context" data-icon="grid" data-rel="dialog" data-iconpos="top">%h%CoreMore%</a>
                </li><!-- START skim_prev -->
                <li>
                    <a href="{link_previous}" id="a_skim_prev" data-icon="arrow-r" rel="external" data-iconpos="top" data-direction="slidedown">%h%EmailPrev%</a>
                </li><!-- END skim_prev -->
            </ul>
        </div>
    </div>
</div>
<div id="page_email_read_attach_context" data-role="page">
    <ul data-role="listview">
        <li>
            <a id="page_email_read_attach_context_save_link" href="#" data-ajax="false">
                <img src="{theme_path}/icons/save.png" alt="%h%save%" class="ui-li-icon">
                %h%save%
            </a>
        </li><!-- START availhdls -->
        <li class="sendto_link handler_{handler}">
            <a href="{link_sendto}&amp;handler={handler}&amp;resid=" data-ajax="false">
                <img src="{theme_path}/icons/{icon}" alt="" class="ui-li-icon">
                {msg}
            </a>
        </li><!-- END availhdls -->
        <li data-icon="false">
            <a href="#page_email_read" data-rel="back">
                <span class="ui-icon ui-icon-custom ui-icon-arrow-l ui-icon-shadow"></span>
                %h%CoreBack%
            </a>
        </li>
    </ul>
</div>

<div id="page_email_read_action_context" data-role="page">
    <ul data-role="listview">
        <li data-role="list-divider">
            %h%selection%
        </li>
        <li>
            <a href="{hlink_send}answer">
                <span class="ui-icon ui-icon-custom ui-icon-mail-reply ui-icon-shadow"></span>
                %h%answer%
            </a>
        </li>
        <li>
            <a href="{hlink_send}answerAll">
                <span class="ui-icon ui-icon-custom ui-icon-mail-replyall ui-icon-shadow"></span>
                %h%answerAll%
            </a>
        </li>
        <li>
            <a href="{hlink_send}forward">
                <span class="ui-icon ui-icon-custom ui-icon-mail-forward ui-icon-shadow"></span>
                %h%forward%
            </a>
        </li>
        <li>
            <a href="#bounce_window" data-href="{hlink_send}bounce" data-rel="dialog">
                <span class="ui-icon ui-icon-custom ui-icon-mail-reroute ui-icon-shadow"></span>
                %h%bounce%
            </a>
        </li>
        <li>
            <a href="{hlink_send}template">
                <span class="ui-icon ui-icon-custom ui-icon-mail-asnew ui-icon-shadow"></span>
                %h%EditAsNew%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="archive" href="#" data-href="{hlink_mailops}archive">
                <span class="ui-icon ui-icon-custom ui-icon-mail-archive ui-icon-shadow"></span>
                %h%EmailSendToArchive%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="mark" href="#" data-href="{hlink_mailops}mark">
                <span class="ui-icon ui-icon-custom ui-icon-mail-markread ui-icon-shadow"></span>
                %h%markread_set%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="unmark" href="#" data-href="{hlink_mailops}unmark">
                <span class="ui-icon ui-icon-custom ui-icon-mail-markunread ui-icon-shadow"></span>
                %h%markread_unset%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="spam" href="#" data-href="{hlink_mailops}spam">
                <span class="ui-icon ui-icon-custom ui-icon-mail-markjunk ui-icon-shadow"></span>
                %h%markmailSPAM%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="unspam" href="#" data-href="{hlink_mailops}unspam">
                <span class="ui-icon ui-icon-custom ui-icon-mail-marknotjunk ui-icon-shadow"></span>
                %h%markmailHAM%
            </a>
        </li>
        <li>
            <a href="#page_email_read_select_colour" data-rel="dialog">
                <span class="ui-icon ui-icon-custom ui-icon-mail-colourmark ui-icon-shadow"></span>
                %h%markmailColour%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="copy" href="#page_email_read_select_folder" data-href="{hlink_mailops}copy" data-rel="dialog">
                <span class="ui-icon ui-icon-custom ui-icon-mail-copyto ui-icon-shadow"></span>
                %h%copytofolder%
            </a>
        </li>
        <li>
            <a class="mailop" data-op="move" href="#page_email_read_select_folder" data-href="{hlink_mailops}move" data-rel="dialog">
                <span class="ui-icon ui-icon-custom ui-icon-mail-moveto ui-icon-shadow"></span>
                %h%movetofolder%
            </a>
        </li>

        <li data-icon="false">
            <a href="#page_email_read" data-rel="back">
                <span class="ui-icon ui-icon-custom ui-icon-arrow-l ui-icon-shadow"></span>
                %h%CoreBack%
            </a>
        </li>
    </ul>
</div>

<div id="page_email_read_select_colour" data-role="page">
    <ul data-role="listview">
        <li data-role="list-divider">
            %h%selection%
        </li><!-- START sel_colourmark -->
        <li<!-- START sel --> data-icon="check"<!-- END sel -->>
            <a class="mailop" data-op="colour" href="#" data-href="{hlink_mailops}colourmark&amp;colour={colour}">
                <span class="cmark_square cmark_{colour}"></span>
                &nbsp;<!-- Think about a textual type assigned to each colour -->
            </a>
        </li><!-- END sel_colourmark -->
        <li>
            <a class="mailop" data-op="colour" href="#" data-href="{hlink_mailops}colourmark&amp;colour=none">
                <span class="ui-icon ui-icon-custom ui-icon-delete"></span>
                %h%ColourmarkRemove%
            </a>
        </li>
    </ul>
</div>

<div id="page_email_read_select_folder" data-role="page">
</div>
