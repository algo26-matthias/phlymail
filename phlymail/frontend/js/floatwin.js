/**
 * These functions offer a "floating" window, created via DOM functions. This window can contain fairly
 * everything. It is draggable around the screen and can be closed via an [x] icon.
 * Before just using these functions, you should replace the ID of the "application area"
 * @version 0.7.4 2007-07-30
 * @author  Matthias Sommerfeld <mso@phlylabs.de>
 */
popupnum = 0;          // All windows are numbered for uniqueness
popup_zidx = 10;       // Start zIndex for popups
openlist = [];         // Listing all windows open
max_zidx = popup_zidx; // current maximum zIndex
canvasleft = 0;        // Left border of the visible application area;

/**
 * Creates the floating window
 * @param  string  ID of the content div
 * @param  string  Name (title) of the window, used as heading
 * @param  int  width of the window
 * @param  int  height of the window
 *[@param  bool  Set to true, if duplicates are allowed, otherwise no new window will be created if one
 *     of the same ID already exists]
 *[@param  string  ID of the newly created window - useful for unique window names]
 * @since  0.0.1
 */
function float_window(id, winname, winx, winy, duplicate, new_id)
{
    if (!duplicate && openlist[id] != null) return;

    var opener_offset = (popupnum%10)*25; // incrementing the window pos with each opening
    max_zidx++; // Incrementing the current top layer

    // The window scrolled -> make sure top open the popup in sight
    if (typeof(window.pageYOffset) != 'undefined') {
        var y_offset = window.pageYOffset;
        var x_offset = window.pageXOffset
    } else {
        var y_offset = document.body.scrollTop;
        var x_offset = document.body.scrollLeft;
    }
    var left = canvasleft + 100 + opener_offset + x_offset;
    var top = 100 + opener_offset + y_offset;

    popupnum++;

    var nid = (new_id) ? new_id : popupnum + '_' + id;

    openlist[id] = nid;

    var src = document.getElementById('float_win_src');
    var out = src.cloneNode(true);
    out.setAttribute('id', nid);
    out.style.position = 'absolute';
    out.style.left = left + 'px';
    out.style.top = top + 'px';
    out.style.width = winx + 'px';
    out.style.height = winy + 'px';
    out.style.zIndex = max_zidx;;
    out.style.display = 'block';

    // Setting the name of the window title
    out.firstChild.firstChild.firstChild.firstChild.appendChild(document.createTextNode(winname));

    // Defining the close link
    var link = 'javascript:float_close("' + nid + '", "' + id + '");';
    out.firstChild.firstChild.firstChild.firstChild.nextSibling.firstChild.setAttribute('href', link);

    // Cloning the payload into the place
    var cont_cell = out.firstChild.firstChild.firstChild.nextSibling.firstChild;
    content = document.getElementById(id).cloneNode(true);
    content.style.display = 'block';
    cont_cell.appendChild(content);

    // Make the new window draggable by touching its title bar
    out.firstChild.firstChild.firstChild.firstChild.setAttribute('onMouseDown', 'float_drag("' + nid + '")');

    document.body.appendChild(out);
}

/**
 * Just an internal function used by the floating window itself
 * @since 0.0.1
 */
function float_close(id, oid)
{
    var me = document.getElementById(id);
    me.style.display = 'none';
    openlist[oid] = null;
    me.parentNode.removeChild(me);
}

/**
 * Use this function from other JS routines to close a floating window
 * This will only work, if you have NOT opened this window with
 * parameter 4 (duplicate) set to true
 * @since 0.7.2
 * @param  string  Identifier (name) you opened the floatwin with
 */
function float_close_script(id)
{
    if (!openlist[id]) return false;
    float_close(openlist[id], id);
}

/**
 * Just an internal function used by the floating window itself
 * @since 0.0.1
 */
function float_drag(id, msie)
{
    var cell = (id) ? document.getElementById(id) : msie.parentNode.parentNode.parentNode.parentNode;
    dragme.start(cell);
}

/**
 * Allows the floating window to be dragged aroud on the screen
 * @since 0.0.1
 */
dragme = {
    dragobj: null,
    ox: 0,
    oy: 0,
    mx: 0,
    my: 0,
    init: function() {
        document.onmousemove = dragme.drag;
        document.onmouseup = dragme.stop;
    },
    start: function(o) {
        if (o.style.zIndex != max_zidx) max_zidx++;
        o.style.zIndex = max_zidx;
        dragme.dragobj = o;
        dragme.ox = dragme.mx - dragme.dragobj.offsetLeft;
        dragme.oy = dragme.my - dragme.dragobj.offsetTop;
    },
    drag: function(e) {
        dragme.mx = document.all ? window.event.clientX : e.pageX;
        dragme.my = document.all ? window.event.clientY : e.pageY;
        // Object is given, pointer does not leave screen on top and left
        if(dragme.dragobj != null && dragme.mx >= 0 && dragme.my >= 0) {
            dragme.dragobj.style.left = (dragme.mx - dragme.ox) + "px";
            dragme.dragobj.style.top = (dragme.my - dragme.oy) + "px";
        }
    },
    stop: function() {
        dragme.dragobj = null;
    }
}