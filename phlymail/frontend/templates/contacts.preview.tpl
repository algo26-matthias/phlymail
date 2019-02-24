<script type="text/javascript">
/*<![CDATA[*/
function print()
{
    window.open('{print_url}', 'printcontact_{id}', 'width=660,height=650,left=100,top=100,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
}

function edit()
{
    window.open('{edit_url}', 'editcontact_{id}', 'width=510,height=470,left=100,top=100,scrollbars=no,resizable=yes,location=no,menubar=no,status=yes,toolbar=no');
}

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

/*]]>*/
</script>
<div style="width:49%;float:left;">
    <div style="padding:4px;text-align:left">
        <div style="text-align:left; font-size: 1.5em; margin:4px; position:relative;"><!-- START ifimage -->
            <div style="float:right;width:{imgw}px;height:{imgh}px;">
                <img src="{imgurl}" alt="" style="display:block"/>
            </div><!-- END ifimage -->
            <strong>{displayname}</strong><br />
            <span style="font-style:italic;">{group}</span>
        </div>
        <br style="clear:both"/>
        <fieldset>
            <legend>%h%General%</legend>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td class="l t"><strong>%h%nick%</strong></td><td class="l t">{nick}</td>
                </tr>
                <tr>
                   <td class="l t"><strong>%h%Title%</strong></td><td class="l t">{title}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%fnam%</strong></td><td class="l t">{fname}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%ThirdNames%</strong></td><td class="l t">{thirdname}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%snam%</strong></td><td class="l t">{lname}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%bday%</strong></td><td class="l t">{bday}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%cmnt%</strong></td><td class="l t">{comment}</td>
                </tr>
            </table>
        </fieldset>
        <br />
        <br />
        <fieldset>
            <legend>%h%Personal%</legend>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td class="l t"><strong>%h%address%</strong></td><td class="l t">{addr}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%street%</strong></td><td class="l t">{street}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%zip% / %h%location%</strong></td><td class="l t">{zip_location}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%state% / %h%country%</strong></td><td class="l t">{region_country}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%fon%</strong></td><td class="l t">{fon_private}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%fax%</strong></td><td class="l t field_fax">{fax}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%cell%</strong></td><td class="l t field_sms">{cellular}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%emai1%</strong></td><td class="l t field_email">{email1}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%www%</strong></td><td class="l t field_www">{www}</td>
                </tr>
            </table>
        </fieldset>
    </div>
</div>
<div style="width:49%;float:right;">
    <div style="text-align:right;padding:2px;">
        <button type="button" onclick="print();">%h%prnt%</button><!-- START may_edit -->
        &nbsp;
        <button type="button" onclick="edit();">%h%Edit%</button><!-- END may_edit -->
    </div>
    <div style="padding:4px;text-align:left">
        <fieldset>
            <legend>%h%Business%</legend>
            <table border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <td class="l t"><strong>%h%CustomerNumber%</strong></td><td class="l t">{customer_number}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%company%</strong></td><td class="l t">{company}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%comp_dep%</strong></td><td class="l t">{department}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%Role%</strong></td><td class="l t">{comp_role}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%address%</strong></td><td class="l t">{comp_addr}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%street%</strong></td><td class="l t">{comp_street}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%zip% / %h%location%</strong></td><td class="l t">{comp_zip_location}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%state% / %h%country%</strong></td><td class="l t">{comp_region_country}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%fon2%</strong></td><td class="l t">{fon_business}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%fax%</strong></td><td class="l t field_fax">{comp_fax}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%cell%</strong></td><td class="l t field_sms">{comp_cellular}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%emai1%</strong></td><td class="l t field_email">{email2}</td>
                </tr>
                <tr>
                    <td class="l t"><strong>%h%www%</strong></td><td class="l t field_www">{comp_www}</td>
                </tr>
            </table>
        </fieldset><!-- START has_freefields -->
        <br />
        <br />
        <fieldset>
            <legend>%h%FreeFields%</legend>
            <table border="0" cellpadding="2" cellspacing="0"><!-- START freefield -->
                <tr>
                    <td class="l t"><strong>{name}</strong></td><td class="l t">{value}</td>
                </tr><!-- END freefield -->
            </table>
        </fieldset><!-- END has_freefields -->
    </div>
</div>