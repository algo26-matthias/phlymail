<div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <div data-role="header" data-position="fixed">
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" data-count-theme="e" class="x32"><!-- START line --><!-- START divider -->
            <li data-role="list-divider"><!-- START dicon -->
                <img src="{src}" alt="{alt}" class="ui-li-icon"><!-- END dicon -->
                {title}
            </li><!-- END divider --><!-- START notarget -->
            <li data-props="{proplist}" class="fldlvl_{level}"><!-- START nticon -->
                <img src="{src}" alt="{alt}" class="ui-li-icon"><!-- END nticon -->
                {title}
            </li><!-- END notarget --><!-- START target -->
            <li data-props="{proplist}" class="fldlvl_{level}">
                <a href="{link}"><!-- START ticon -->
                    <img src="{src}" alt="{alt}" class="ui-li-icon"><!-- END ticon -->
                    {title}<!-- START count -->
                    <span class="ui-li-count">{count}</span><!-- END count -->
                </a>
            </li><!-- END target --><!-- END line -->
        </ul>
    </div>
</div>