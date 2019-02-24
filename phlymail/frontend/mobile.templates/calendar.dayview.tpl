<div id="calendar_dayview" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <div data-role="header" data-position="fixed">
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <div class="dayhead">
            <a class="calendar_skim prev" href="{oneback}">&lsaquo;</a>
            <a class="calendar_skim next" href="{oneforward}">&rsaquo;</a>
            {subTitle}
        </div>
        <div class="ui-content pad">
            <ul data-role="listview"><!-- START eventline -->
                <li>
                    <a href="{link}">
                        <h3><!-- START primary_icon -->
                            <img src="{src}" alt="{alt}"><!-- END primary_icon --><!-- START priority_icon -->
                            <img class="priority" src="{src}" alt="{alt}"><!-- END priority_icon --><!-- START has_attach -->
                            <span class="ui-icon ui-icon-custom ui-icon-attachment">&nbsp;</span><!-- END has_attach --><!-- START has_colour -->
                            <span class="cmark_square cmark_{colour}"></span><!-- END has_colour -->
                            {primary}
                        </h3>
                        <p><!-- START secondary --><strong>{secondary}</strong><!-- END secondary --><div class="completionbar" data-completion="{completion}"></div>
                        </p><!-- START tertiary -->
                        <p>{tertiary}</p><!-- END tertiary --><!-- START aside -->
                        <p class="ui-li-aside"><strong>{aside}</strong></p><!-- END aside -->
                    </a>
                </li><!-- END eventline --><!-- START noevents -->
                <li>
                    %h%none%
                </li><!-- END noevents -->
            </ul>
        </div>
    </div>
</div>