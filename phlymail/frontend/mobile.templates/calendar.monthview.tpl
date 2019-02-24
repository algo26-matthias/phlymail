<div id="calendar_monthview" data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <div data-role="header" data-position="fixed">
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <div class="monthtable">
            <table>
                <thead>
                    <tr class="title">
                        <th colspan="8">
                                <a class="calendar_skim prev" href="{oneback}">&lsaquo;</a>
                                <a class="calendar_skim next" href="{oneforward}">&rsaquo;</a>
                                {subTitle}
                        </th>
                    </tr>
                    <tr class="daynames">
                        <th class="cal_mnth_label" abbr="%h%weekday_0%">&nbsp;</th>
                        <th class="cal_mnth_label{label_monday}" abbr="%h%weekday_0%">%h%wday2_0%</th>
                        <th class="cal_mnth_label{label_tuesday}" abbr="%h%weekday_1%">%h%wday2_1%</th>
                        <th class="cal_mnth_label{label_wednesday}" abbr="%h%weekday_2%">%h%wday2_2%</th>
                        <th class="cal_mnth_label{label_thursday}" abbr="%h%weekday_3%">%h%wday2_3%</th>
                        <th class="cal_mnth_label{label_friday}" abbr="%h%weekday_4%">%h%wday2_4%</th>
                        <th class="cal_mnth_label{label_saturday}" abbr="%h%weekday_5%">%h%wday2_5%</th>
                        <th class="cal_mnth_label{label_sunday}" abbr="%h%weekday_6%">%h%wday2_6%</th>
                    </tr>
                </thead>
                <tbody><!-- START mnth_weekline -->
                    <tr class="monthline">
                        <td class="cal_cw">{kw}</td><!-- START mnth_daycell -->
                        <td class="{dayclass}" title="{title}" id="td_{datelong}">
                            <a class="whole" href="{daylink}"><!-- START li_holiday -->
                                <span class="cal_mnth_txt_holiday">{holiday}</span><!-- END li_holiday -->
                                <span class="cal_mnth_date" id="draw_{day}">{date}</span>
                                <span class="evtsquare_container"><!-- START evt_colour -->
                                    <span class="cmark_square cmark_{colour}"></span><!-- END evt_colour -->
                                </span>
                            </a> 
                        </td><!-- END mnth_daycell -->
                    </tr><!-- END mnth_weekline -->
                </tbody>
            </table>
        </div>
        <div class="ui-content pad">
            <ul data-role="listview">
                <li data-role="list-divider">
                    %h%TskMyName%
                </li><!-- START taskline -->
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
                </li><!-- END taskline --><!-- START notasks -->
                <li>
                    %h%none%
                </li><!-- END notasks -->
            </ul>
        </div>
    </div>
</div>