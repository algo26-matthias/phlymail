/**
 * Combobox implementation through JavaScript
 * As opposed to a standard combobox this one uses a input type="text" field as the default input method and
 * allows to display a dropdown for selecting predefined values.
 * The <select> is activated by a button. Clicking elsewhere or selecting sth. from the <select> hides it again
 * Usage: define both the text and teh select where the select has display:none first.
 *
 * @version 0.0.1
 * @package phlyMail Nahariya 4.0+
 * @subpackage frontend
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 */

/**
 * Actually activates the select box after clicking the button
 *
 * @param string ID of the <input type=text>
 * @param string ID of the <select>
 * @param string ID of the button which is used to activate the select
 * @since 0.0.1
 */
function combo_active(text, select, knopf)
{
    var text = document.getElementById(text);
    var select = document.getElementById(select);
    var knopf = document.getElementById(knopf);

    select.onchange = function () {
        var selection = select.value;
        select.style.display = 'none';
        text.style.display = '';
        knopf.style.display = '';
        if (selection) {
            text.value = selection;
        }
    }
    select.onblur = function () {
        select.style.display = 'none';
        text.style.display = '';
        knopf.style.display = '';
    }
    select.style.display = '';
    select.focus();
    select.selectedIndex = -1;
    text.style.display = 'none';
    knopf.style.display = 'none';
}

/**
 * Adds a nice touch by hiding the button, if there's nothing to comboselect
 *
 * @param string ID of the <select>
 * @param string ID of the button which is used to activate the select
 * @since 0.0.1
 */
function combo_disable(select, knopf)
{
    var select = document.getElementById(select);
    var knopf = document.getElementById(knopf);
    if (select.options.length == 0) knopf.style.display = 'none';
}
