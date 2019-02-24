<script type="text/javascript">
//<![CDATA[
function delete_contact()
{
    state = confirm('%h%AskDelAdr%');
    if (state == true) document.getElementById('saveframe').src = '{delete_link}';
}

function print()
{
    window.open('{print_url}', 'printcontact_{id}', 'width=650,height=600,left=100,top=100'
                    + ',scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no');
}

function save_contact()
{
    if ($('masterform').attr('action') == '#') {
        return false;
    }
    return true;
}

function process(response)
{
    document.getElementById('busy').style.display = 'none';
    var regx = /\{.+\}/;
    var nresponse = regx.exec(response);
    if (!nresponse.length) {
    	alert(response);
    } else {
    	response = nresponse[0];
    }
    eval('next = ' + response);
    if (next['error']) {
        alert(next['error']);
    }
    if (next['done']) {
        done();
    }
}

function done()
{
    try { opener.parent.frames.PHM_tr.refreshlist(); } catch (e) { }
    try { opener.frames.PHM_tr.refreshlist(); } catch (e) { }
    self.close();
}
$(document).ready(function () {
    adjust_height();
    $('#tabpane').tabs().tabs('select', 0);
});
//]]>
</script>
<div id="edit" class="t l">
    <form name="formular" action="<!-- START may_edit -->{form_target}&amp;noajax=1<!-- END may_edit --><!-- START no_edit -->#<!-- END no_edit -->" method="post" onsubmit="return save_contact();" enctype="multipart/form-data">

        <div id="tabpane" class="ui-tabpane" style="height:400px;margin-top:4px;">
            <ul>
                <li><a href="#generic"><span>%h%General%</span></a></li>
                <li><a href="#personal"><span>%h%Personal%</span></a></li>
                <li><a href="#business"><span>%h%Business%</span></a></li><!-- START has_freefields -->
                <li><a href="#freefields"><span>%h%FreeFieldsShort%</span></a></li><!-- END has_freefields -->
                <li><a href="#image"><span>%h%LegendImage%</span></a></li><!-- START has_attachments -->
                <li><a href="#attachments"><span>%h%Attachments%</span></a></li><!-- END has_attachments -->
            </ul>
            <div id="generic">
                <table border="0" cellpadding="2" cellspacing="0"><!-- START has_groupsel -->
                    <tr>
                        <td class="t l"><strong>%h%group%:</strong></td>
                        <td class="t l">
                            <select size="3" name="gid[]" multiple="multiple"><!-- START groupline -->
                                <option value="{id}"<!-- START selected --> selected="selected"<!-- END selected -->>{name}</option><!-- END groupline -->
                            </select>
                        </td>
                    </tr><!-- END has_groupsel -->
                    <tr>
                        <td><strong>%h%nick%</strong></td>
                        <td><input type="text" size="32" name="nick" value="{nick}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%Title%</strong></td>
                        <td><input type="text" size="32" name="title" value="{title}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%fnam%</strong></td>
                        <td><input type="text" size="32" name="firstname" value="{firstname}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%ThirdNames%</strong></td>
                        <td><input type="text" size="32" name="thirdname" value="{thirdname}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%snam%</strong></td>
                        <td><input type="text" size="32" name="lastname" value="{lastname}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%bday%</strong></td>
                        <td>
                            <input type="text" size="10" maxlength="10" class="datepicker" name="birthday_fulldate" value="{birthday_fulldate}" />
                        </td>
                    </tr>
                    <tr>
                        <td class="t l"><strong>%h%cmnt%</strong></td>
                        <td><textarea rows="6" cols="28" name="comments">{comments}</textarea></td>
                    </tr>
                </table>
            </div>

            <div id="personal">
                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="t l"><strong>%h%address%</strong></td>
                        <td>
                            <input type="text" size="32" name="address" value="{address}" /><br />
                            <input type="text" size="32" name="address2" value="{address2}" style="margin-top: 2px" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%street%</strong></td>
                        <td><input type="text" size="32" name="street" value="{street}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%zip% / %h%location%</strong></td>
                        <td>
                            <input type="text" size="14" name="zip" value="{zip}" /> / <input type="text" size="14" name="location" value="{location}" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%state% / %h%country%</strong></td>
                        <td>
                            <input type="text" size="14" name="region" value="{region}" /> / <input type="text" size="14" name="country" value="{country}" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%fon%</strong></td>
                        <td><input type="text" size="32" name="tel_private" value="{tel_private}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%fax%</strong></td>
                        <td><input type="text" size="32" name="fax" value="{fax}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%cell%</strong></td>
                        <td><input type="text" size="32" name="cellular" value="{cellular}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%emai1%</strong></td>
                        <td><input type="text" size="32" name="email1" value="{email1}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%www%</strong></td>
                        <td><input type="text" size="32" name="www" value="{www}" /></td>
                    </tr>
                </table>
            </div>

            <div id="business">
                <table border="0" cellpadding="2" cellspacing="0"><!-- START has_customer_number -->
                    <tr>
                        <td><strong>%h%CustomerNumber%</strong></td>
                        <td><input type="text" size="32" name="customer_number" value="{customer_number}" /></td>
                    </tr><!-- END has_customer_number -->
                    <tr>
                        <td><strong>%h%company%</strong></td>
                        <td><input type="text" size="32" name="company" value="{company}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%comp_dep%</strong></td>
                        <td><input type="text" size="32" name="comp_dep" value="{comp_dep}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%Role%</strong></td>
                        <td><input type="text" size="32" name="comp_role" value="{comp_role}" /></td>
                    </tr>
                    <tr>
                        <td class="t l"><strong>%h%address%</strong></td>
                        <td>
                            <input type="text" size="32" name="comp_address" value="{comp_address}" /><br />
                            <input type="text" size="32" name="comp_address2" value="{comp_address2}" style="margin-top: 2px" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%street%</strong></td>
                        <td><input type="text" size="32" name="comp_street" value="{comp_street}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%zip% / %h%location%</strong></td>
                        <td>
                            <input type="text" size="14" name="comp_zip" value="{comp_zip}" />
                            / <input type="text" size="14" name="comp_location" value="{comp_location}" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%state% / %h%country%</strong></td>
                        <td>
                            <input type="text" size="14" name="comp_region" value="{comp_region}" />
                            / <input type="text" size="14" name="comp_country" value="{comp_country}" />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>%h%fon2%</strong></td>
                        <td><input type="text" size="32" name="tel_business" value="{tel_business}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%fax%</strong></td>
                        <td><input type="text" size="32" name="comp_fax" value="{comp_fax}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%cell%</strong></td>
                        <td><input type="text" size="32" name="comp_cellular" value="{comp_cellular}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%emai1%</strong></td>
                        <td><input type="text" size="32" name="email2" value="{email2}" /></td>
                    </tr>
                    <tr>
                        <td><strong>%h%www%</strong></td>
                        <td><input type="text" size="32" name="comp_www" value="{comp_www}" /></td>
                    </tr>
                </table>
            </div>

            <div id="freefields">
                <table border="0" cellpadding="2" cellspacing="0"><!-- START freefield -->
                    <tr>
                        <td class="l t"><strong>{name}</strong></td>
                        <td class="l t"><!-- START type_text -->
                            <input type="text" size="32" name="free[{id}]" value="{value}" /><!-- END type_text --><!-- START type_textarea -->
                            <textarea rows="6" cols="28" name="free[{id}]">{value}</textarea><!-- END type_textarea -->
                        </td>
                    </tr><!-- END freefield -->
                </table>
            </div>

            <div id="image">
                %h%ImgUpload%:<br />
                <input type="file" name="image" size="16" /><!-- START delimage -->
                <input type="checkbox" name="delimage" value="1" id="delimage" />
                <label for="delimage">%h%ImgDelImage%</label><!-- END delimage --><br /><!-- START ifimage -->
                <img src="{imgurl}" alt="" style="display:block;width:{imgw}px;height:{imgh}px;margin-top:4px;" /><!-- END ifimage -->
            </div>

        </div>

        <div style="clear:both;padding:8px;">
            <div style="width:50%;float:left;text-align:left;"><!-- START save_button -->
                <input type="submit" value="%h%save%" />
                <img id="busy" src="{theme_path}/images/busy.gif" style="visibility:hidden;" alt="" title="Please wait" />&nbsp;<!-- END save_button --><!-- START print_button -->
                <button type="button" onclick="print();">%h%prnt%</button><!-- END print_button -->
            </div>
            <div style="width:50%;float:right;text-align:right;"><!-- START delete_button -->
                <button type="button" onclick="delete_contact()" class="error">%h%DelAdr%</button><!-- END delete_button -->
            </div>
        </div>
    </form>
    <iframe src="about:blank" id="saveframe" name="saveframe" style="width:1px;height:1px;"></iframe><br />
</div>