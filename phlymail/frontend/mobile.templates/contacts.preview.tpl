<script type="text/javascript">
//<![CDATA[
$(document).ready(function () {
    $('.field_email').each(function() {
        if ($(this).text().length > 0) {
            $(this).html('<a href="{composemail_url}' + $(this).text() + '" target="_blank">' + $(this).text() + '</a>');
        }
    });
    $('.field_sms').each(function() {
        if ($(this).text().length > 0) {
            $(this).html('<a href="{composesms_url}' + $(this).text() + '" target="_blank">' + $(this).text() + '</a>');
        }
    });
    $('.field_fax').each(function() {
        if ($(this).text().length > 0) {
            $(this).html('<a href="{composefax_url}' + $(this).text() + '" target="_blank">' + $(this).text() + '</a>');
        }
    });
    $('.field_www').each(function() {
        if ($(this).text().length > 0) {
            var URL = $(this).text();
            if (!URL.match(/^([a-zA-Z])+\:(\/\/)?/)) {
                URL = 'http://' + URL;
            }
            $(this).html('<a href="' + URL + '" target="_blank">' + $(this).text() + '</a>');
        }
    });
});
//]]>
</script>
<div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <div data-role="header" data-position="fixed">
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">

        <div class="contactsheet-headline ui-helper-clearfix">
            <div class="contactphoto-placeholder-medium"><!-- START ifimage -->
                <img src="{imgurl}" alt=""><!-- END ifimage -->
            </div>
            <h3>{displayname}</h3>
            <em>{group}</em>
        </div>
        <hr>
        <h3>%h%General%</h3>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%nick%</div><div class="ui-block-b l t">{nick}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%Title%</div><div class="ui-block-b l t">{title}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%fnam%</div><div class="ui-block-b l t">{fname}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%ThirdNames%</div><div class="ui-block-b l t">{thirdname}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%snam%</div><div class="ui-block-b l t">{lname}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%bday%</div><div class="ui-block-b l t">{bday}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%cmnt%</div><div class="ui-block-b l t">{comment}</div>
        </div>
        <hr>
        <h3>%h%Personal%</h3>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%address%</div><div class="ui-block-b l t">{addr}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%street%</div><div class="ui-block-b l t">{street}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%zip% / %h%location%</div><div class="ui-block-b l t">{zip_location}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%state% / %h%country%</div><div class="ui-block-b l t">{region_country}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%fon%</div><div class="ui-block-b l t">{fon_private}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%fax%</div><div class="ui-block-b l t field_fax">{fax}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%cell%</div><div class="ui-block-b l t field_sms">{cellular}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%emai1%</div><div class="ui-block-b l t field_email">{email1}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%www%</div><div class="ui-block-b l t field_www">{www}</div>
        </div>
        <hr>
        <h3>%h%Business%</h3>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%CustomerNumber%</div><div class="ui-block-b l t">{customer_number}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%company%</div><div class="ui-block-b l t">{company}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%comp_dep%</div><div class="ui-block-b l t">{department}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%Role%</div><div class="ui-block-b l t">{comp_role}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%address%</div><div class="ui-block-b l t">{comp_addr}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%street%</div><div class="ui-block-b l t">{comp_street}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%zip% / %h%location%</div><div class="ui-block-b l t">{comp_zip_location}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%state% / %h%country%</div><div class="ui-block-b l t">{comp_region_country}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%fon2%</div><div class="ui-block-b l t">{fon_business}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%fax%</div><div class="ui-block-b l t field_fax">{comp_fax}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%cell%</div><div class="ui-block-b l t field_sms">{comp_cellular}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%emai1%</div><div class="ui-block-b l t field_email">{email2}</div>
        </div>
        <div class="ui-grid-a">
            <div class="ui-block-a">%h%www%</div><div class="ui-block-b l t field_www">{comp_www}</div>
        </div><!-- START has_freefields -->
        <hr>
        <h3>%h%FreeFields%</h3><!-- START freefield -->
        <div class="ui-grid-a">
            <div class="ui-block-a">{name}</div><div class="ui-block-b l t">{value}</div>
        </div><!-- END freefield --><!-- END has_freefields --><!-- START may_edit -->
    </div>
    <div data-role="footer" class="ui-bar" data-position="fixed">
        <a data-role="button" data-inline="true" href="{edit_url_h}">%h%Edit%</a>
    </div><!-- END may_edit -->
</div>