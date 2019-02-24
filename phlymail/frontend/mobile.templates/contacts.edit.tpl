<script type="text/javascript">
//<![CDATA[
function save_contact()
{
    if ($('masterform').attr('action') === '#') {
        return false;
    }
    return true;
}
//]]>
</script>
<div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">
    <form name="formular" id="masterform" data-ajax="false" data-deletelink="{delete_link}" action="<!-- START may_edit -->{form_target}&amp;noajax=1<!-- END may_edit --><!-- START no_edit -->#<!-- END no_edit -->" target="_self" method="post" onsubmit="return save_contact();" enctype="multipart/form-data">
        <div data-role="header" data-position="fixed">
            <h1>{pageTitle}</h1>
            <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
        <div data-role="content">
            <h3>%h%General%</h3>
            <div data-role="fieldcontain">
                <label for="nick">%h%nick%</label>
                <input type="text" size="32" name="nick" id="nick" value="{nick}">
            </div>
            <div data-role="fieldcontain">
                <label for="title">%h%Title%</label>
                <input type="text" size="32" name="title" id="title" value="{title}">
            </div>
            <div data-role="fieldcontain">
                <label for="firstname">%h%fnam%</label>
                <input type="text" size="32" name="firstname" id="firstname" value="{firstname}">
            </div>
            <div data-role="fieldcontain">
                <label for="thirdname">%h%ThirdNames%</label>
                <input type="text" size="32" name="thirdname" id="thirdname" value="{thirdname}">
            </div>
            <div data-role="fieldcontain">
                <label for="lastname">%h%snam%</label>
                <input type="text" size="32" name="lastname" id="lastname" value="{lastname}">
            </div>
            <div data-role="fieldcontain">
                <label for="birthday_fulldate">%h%bday%</label>
                <input type="date" name="birthday_fulldate" id="birthday_fulldate" value="{birthday_fulldate}">
            </div>
            <div data-role="fieldcontain">
                <label for="comments">%h%cmnt%</label>
                <textarea rows="6" cols="28" name="comments" id="comments">{comments}</textarea>
            </div>

            <hr>

            <h3>%h%Personal%</h3>
            <div data-role="fieldcontain">
                <label for="address">%h%address%</label>
                <input type="text" size="32" name="address" id="address" value="{address}">
            </div>
            <div data-role="fieldcontain">
                <label for="address2">&nbsp;</label>
                <input type="text" size="32" name="address2" id="address2" value="{address2}">
            </div>
            <div data-role="fieldcontain">
                <label for="street">%h%street%</label>
                <input type="text" size="32" name="street" id="street" value="{street}">
            </div>
            <div data-role="fieldcontain">
                <label for="zip">%h%zip%</label>
                <input type="text" size="14" name="zip" id="zip" value="{zip}">
            </div>
            <div data-role="fieldcontain">
                <label for="location">%h%location%</label>
                <input type="text" size="14" name="location" id="location" value="{location}">
            </div>
            <div data-role="fieldcontain">
                <label for="region">%h%state%</label>
                <input type="text" size="14" name="region" id="region" value="{region}">
            </div>
            <div data-role="fieldcontain">
                <label for="country">%h%country%</label>
                <input type="text" size="14" name="country" id="country" value="{country}">
            </div>
            <div data-role="fieldcontain">
                <label for="tel_private">%h%fon%</label>
                <input type="tel" size="32" name="tel_private" id="tel_private" value="{tel_private}">
            </div>
            <div data-role="fieldcontain">
                <label for="fax">%h%fax%</label>
                <input type="tel" size="32" name="fax" id="fax" value="{fax}">
            </div>
            <div data-role="fieldcontain">
                <label for="cellular">%h%cell%</label>
                <input type="tel" size="32" name="cellular" id="cellular" value="{cellular}">
            </div>
            <div data-role="fieldcontain">
                <label for="email1">%h%emai1%</label>
                <input type="email" size="32" name="email1" id="email1" value="{email1}">
            </div>
            <div data-role="fieldcontain">
                <label for="www">%h%www%</label>
                <input type="url" size="32" name="www" id="www" value="{www}">
            </div>

            <hr>

            <h3>%h%Business%</h3><!-- START has_customer_number -->
            <div data-role="fieldcontain">
                <label for="customer_number">%h%CustomerNumber%</label>
                <input type="text" size="32" name="customer_number" id="customer_number" value="{customer_number}">
            </div><!-- END has_customer_number -->
            <div data-role="fieldcontain">
                <label for="company">%h%company%</label>
                <input type="text" size="32" name="company" id="company" value="{company}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_dep">%h%comp_dep%</label>
                <input type="text" size="32" name="comp_dep" id="comp_dep" value="{comp_dep}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_role">%h%Role%</label>
                <input type="text" size="32" name="comp_role" id="comp_role" value="{comp_role}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_address">%h%address%</label>
                <input type="text" size="32" name="comp_address" id="comp_address" value="{comp_address}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_address2">&nbsp;</label>
                <input type="text" size="32" name="comp_address2" id="comp_address2" value="{comp_address2}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_street">%h%street%</label>
                <input type="text" size="32" name="comp_street" id="comp_street" value="{comp_street}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_zip">%h%zip%</label>
                <input type="text" size="14" name="comp_zip" id="comp_zip" value="{comp_zip}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_location">%h%location%</label>
                <input type="text" size="14" name="comp_location" id="comp_location" value="{comp_location}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_region">%h%state%</label>
                <input type="text" size="14" name="comp_region" id="comp_region" value="{comp_region}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_country">%h%country%</label>
                <input type="text" size="14" name="comp_country" id="comp_country" value="{comp_country}">
            </div>
            <div data-role="fieldcontain">
                <label for="tel_business">%h%fon2%</label>
                <input type="tel" size="32" name="tel_business" id="tel_business" value="{tel_business}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_fax">%h%fax%</label>
                <input type="tel" size="32" name="comp_fax" id="comp_fax" value="{comp_fax}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_cellular">%h%cell%</label>
                <input type="tel" size="32" name="comp_cellular" id="comp_cellular" value="{comp_cellular}">
            </div>
            <div data-role="fieldcontain">
                <label for="email2">%h%emai1%</label>
                <input type="email" size="32" name="email2" id="email2" value="{email2}">
            </div>
            <div data-role="fieldcontain">
                <label for="comp_www">%h%www%</label>
                <input type="url" size="32" name="comp_www" id="comp_www" value="{comp_www}">
            </div>

            <hr>

            <h3>%h%LegendImage%</h3>
            %h%ImgUpload%:<br />
            <input type="file" name="image" id="image" size="16" /><!-- START delimage -->
            <input type="checkbox" name="delimage" id="delimage" value="1" />
            <label for="delimage">%h%ImgDelImage%</label><!-- END delimage --><br /><!-- START ifimage -->
            <img src="{imgurl}" alt="" style="display:block;width:{imgw}px;height:{imgh}px;margin-top:4px;" /><!-- END ifimage -->

            <!-- START has_groupsel -->
            <hr>
            <h3>%h%group%</h3>
            <div data-role="controlgroup"><!-- START groupline -->
                <input type="checkbox" name="gid[]" id="chk_gid_{id}" value="{id}"<!-- START selected --> checked<!-- END selected -->>
                <label for="chk_gid_{id}">{name}</label><!-- END groupline -->
            </div><!-- END has_groupsel -->

            <!-- START has_freefields -->
            <hr>
            <h3>%h%FreeFieldsShort%</h3><!-- END has_freefields --><!-- START freefield -->

            <div data-role="fieldcontain">
                <label for="free_{id}">{name}</label><!-- START type_text -->
                <input type="text" id="free_{id}" size="32" name="free[{id}]" value="{value}" /><!-- END type_text --><!-- START type_textarea -->
                <textarea rows="6" id="free_{id}" cols="28" name="free[{id}]">{value}</textarea><!-- END type_textarea -->
            </div><!-- END freefield -->


            <!-- START has_attachments -->
            <hr>

            <h3>%h%Attachments%</h3><!-- END has_attachments -->


        </div>
        <div data-role="footer" class="ui-bar" data-position="fixed"><!-- START save_button -->
            <button type="submit" data-icon="check">%h%save%</button><!-- END save_button --><!-- START delete_button -->
            <button type="button" onclick="contactsDeleteContact('%h%AskDelAdr%')" data-icon="dustbin">%h%DelAdr%</button><!-- END delete_button -->
        </div>
    </form>
</div>