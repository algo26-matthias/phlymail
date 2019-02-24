/**
 * Include the MP3 player in the given place,
 * automatically adapting colors as necessary.
 * @copyright 2008-2009 phlyLabs Berlin, http://phlylabs.de
 * @author Matthias Sommerfeld
 * @version 0.2.1 2009-02-18
 */
var pathSWF = '/frontend/js/mp3player.swf';
var pathMP3 = '/frontend/sounds/';
var MP3autoPlay = 'no';
/**
 * Call this function for including a MP3 player where you need it
 * @param string  file name (relative URI or absolute URL)
 * @param string  caption (i.e. Artist and Song Name) to show in the player
 * @param mixed  Either the ID of the container element as a string or the elemnt itself as object
 * @since 0.0.1
 */
function mp3_include(container, fileName)
{
          /*,bg: 'E5E5E5'
            ,leftbg: 'CCCCCC'          // Speaker icon/Volume control background
            ,lefticon: '333333'        // Speaker icon
            ,voltrack: 'F2F2F2'        // Volume track
            ,volslider: '666666'       // Volume slider
            ,rightbg: 'B4B4B4'         // Play/Pause button background
            ,rightbghover: '999999'    // Play/Pause button background (hover state)
            ,righticon: '333333'       // Play/Pause icon
            ,righticonhover: 'FFFFFF'  // Play/Pause icon (hover state)
            ,loader: '009900'          // Loading bar
            ,track: 'FFFFFF'           // Loading/Progress bar track backgrounds
            ,tracker: 'DDDDDD'         // Progress track
            ,border: 'CCCCCC'          // Progress bar border
            ,skip: '666666'            // Previous/Next skip buttons
            ,text: '333333'            // Text*/

    var finalURL, bgColor, txtColor, barBgColor, posBarColor;

    if (typeof(container) != 'object') {
        container = document.getElementById(container);
        if (typeof(container) != 'object') return; // Element not found
    }

    finalURL = pathSWF + '?autostart=' + MP3autoPlay + '&amp;animation=no&amp;width=360&amp;initialvolume=100'
            + '&amp;transparentpagebg=yes&amp;soundFile=';
    finalURL += encodeURIComponent(pathMP3 + fileName);

    bgColor = getStyle(container, 'background-color');
    if (!bgColor) bgColor = getStyle(container, 'background');
    txtColor = getStyle(container, 'color');
    barBgColor = getStyle(container, 'border-top-color');
    if (!barBgColor) barBgColor = getStyle(container, 'border-color');

    finalURL += '&amp;bg=' + mp3_rgbHex(txtColor, true);
    finalURL += '&amp;leftbg=' + mp3_rgbHex(barBgColor, true);
    finalURL += '&amp;rightbg=' + mp3_rgbHex(barBgColor, true);
    finalURL += '&amp;rightbghover=' + mp3_rgbHex(barBgColor, true);
    finalURL += '&amp;voltrack=' + mp3_rgbHex(bgColor, true);
    finalURL += '&amp;volslider=' + mp3_rgbHex(txtColor, true);
    finalURL += '&amp;lefticon=' + mp3_rgbHex(txtColor, true);
    finalURL += '&amp;righticon=' + mp3_rgbHex(txtColor, true);
    finalURL += '&amp;righticonhover=' + mp3_rgbHex(txtColor, true);
    finalURL += '&amp;loader=' + mp3_rgbHex(barBgColor, true);

    container.innerHTML = '<obje' + 'ct classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"'
            + ' codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="360" height="26"'
            + ' id="' +container.id + '_mp3" align="middle">'
            + '<param name="allowScriptAccess" value="sameDomain" />'
            + '<param name="movie" value="' + finalURL + '" />'
            + '<param name="quality" value="high" />'
            + '<param name="bgcolor" value="' + mp3_rgbHex(bgColor) + '" />'
            + '<emb' + 'ed src="' + finalURL + '" quality="high" bgcolor="' + mp3_rgbHex(bgColor) + '" width="360" height="26" name="mp3" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />'
            + '</object>';
}

/**
 * Converts CSS rgb(x,y,z) to classic #xxyyzz notation
 * @param string  CSS rgb(x,y,z) setting
 *[@param bool  Whether to cut the # in front; Default: false (leave it in)]
 * @return  string  either #xxyyzz or xxyyzz (if param 2 is set to true)
 */
function mp3_rgbHex(rgb, flash)
{
    if (rgb.match(/^rgb/)) {
        var tmp = /^rgb\((\d+),\s?(\d+),\s?(\d+)\)/;
        tmp.exec(rgb);
        rgb = '#' + mp3_decHex(RegExp.$1) + mp3_decHex(RegExp.$2) + mp3_decHex(RegExp.$3);
    }
    if (flash && rgb.substr(0,1) == '#') rgb = rgb.substr(1);
    return rgb;
}

/**
 * Simple dec -> hex converter
 * @param  int  decimal value; range: 0 ... 255
 * @return  string  hex value. has always two digits!
 */
function mp3_decHex(dez)
{
    dez = parseInt(dez);
    var tbl = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F'];
    var H1, H2;
    H1 = parseInt(dez/16);
    H2 = dez - (H1*16);
    return tbl[H1].toString() + tbl[H2].toString();
}

/**
 * Third party code below
 */

// Ermittle Style eines Elements
function getStyle(element, property)
{
    // Beim IE kann man (über currentStyle - IE5+) keine Eigenschaften
    // abfragen, die kombinierte Werte enthalten (border, borderBottom, ...)
    // Andererseits gibt es zu clip hier auch clipTop, clipRight, clipBottom & ClipLeft.
    var result = '';

    obj = getObject(element);

    if (obj) {
        if (window.getComputedStyle) {
            result = window.getComputedStyle(obj, '').getPropertyValue(property);
        } else if (obj.currentStyle) {
            result = obj.currentStyle[propertyToStyle(property)];
        } else if (obj.style) {
            result = obj.style[propertyToStyle(property)];
        }
    }
    return result;
}

// Style-Schreibweise von CSS auf JS ändern
function propertyToStyle(property)
{
    // 1. Eigenschaften mit reserviertem Bezeichner: Unterscheidung nach JScript- bzw. JavaScript-Syntax
    if (property == 'float') {
        property = ((typeof(window.cssFloat) == 'undefined') ? 'style' : 'css') + property.charAt(0).toUpperCase()+property.substring(1);
    } else if (property.indexOf("-") >= 0) { // 2. Eigenschaften mit Bindestrich
        syntax = property.split("-");               // CSS-Syntax am "-" auftrennen, ...
        property = syntax[0];                       // ... ersten Teil übernehmen und ...
        for(var i = 1; i < syntax.length; i++) {    // ... folgende Teile mit großem Anfangsbuchstaben
            property += syntax[i].charAt(0).toUpperCase()+syntax[i].substring(1);
        }
    }
    return property;
}

// Ermittle Objekt (veränderbare Priorität: object/id/name/tagname) 050708
function getObject(element, number)
{
    var obj = false, lastParam, type, types, i;
    if (element) {
        // Letzten, optionalen Parameter ermitteln (type)
        lastParam = getObject.arguments[getObject.arguments.length-1];
        // Erwünschten Abfragetyp sichern: object/id/name/tagname (voreingestellte Typen und Reihenfolge der Abfrage)
        type = (typeof(lastParam) == 'string' && getObject.arguments.length>1)
                ? lastParam.toLowerCase().replace(',', '\/')
                : 'object/id/name/tagname';
        // Wenn element bereits Objekt ist und auch dieser Typ sein darf
        if (typeof(element) == 'object' && type.indexOf('object') >= 0) {
            obj = element;
        } else if (document.getElementById) {
            number = (typeof(number) == 'number')?number:0;
            types = type.split('/');
            for (i in types) {
                if (types[i] == 'id' && document.getElementById(element)) {
                    obj = document.getElementById(element); break;
                } else if (types[i] == 'name' && document.getElementsByName(element) && document.getElementsByName(element)[number]) {
                    obj = document.getElementsByName(element)[number]; break;
                } else if (types[i] == 'tagname' && document.getElementsByTagName(element) && document.getElementsByTagName(element)[number]) {
                    obj = document.getElementsByTagName(element)[number]; break;
                }
            }
        }
    }
    return obj;
}