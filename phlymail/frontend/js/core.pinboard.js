var Frame, perBox, perRow = 2, boxCount = 0;
function pinboard_dimensions()
{
    Frame = parent.$('#PHM_tr');
    $('body').width(Frame.width()).height(Frame.height());
}

function pinboard_draw_boxes()
{
    $('#core_pinboard').empty();

    var HTML, thisRow = 0, allDrawn = 0;
    if (Frame.width() < 601 || boxCount == 1) {
        perRow = 1;
    } else if (Frame.width() > 1600 && boxCount > 2) {
        perRow = 3;
    }
    perBox = (Math.floor(Frame.width()/perRow)-8).toString(); // Margins!

    $.each(boxinfo, function (box, data) {
        if (thisRow == 0) $('#core_pinboard').append('<div class="pinboard_row">');
        thisRow++;
        allDrawn++;
        HTML = '<div class="pinboard_box" id="pinboard_box_' + box + '" style="width:' + perBox + 'px;"><div class="flist_hhead" id="pinboard_head_' + box + '">';
        if (htmlBiDi == 'ltr') {
            HTML += '<img class="flist_hhead_icon" src="' + urlThemePath + '/icons/' + data['stats']['icon'] + '" alt="" />'
                    + '<img class="flist_hhead_refresh" src="' + urlThemePath + '/icons/folderlist_refresh.png" alt="" title="' + msgRefresh + '" id="pinboard_refresh_' + box + '" />';
        } else {
            HTML += '<img class="flist_hhead_refresh" src="' + urlThemePath + '/icons/folderlist_refresh.png" alt="" title="' + msgRefresh + '" id="pinboard_refresh_' + box + '" />'
                    + '<img class="flist_hhead_icon" src="' + urlThemePath + '/icons/' + data['stats']['icon'] + '" alt="" />';
        }
        HTML += data['stats']['headline'] + '</div>'
                + '<div class="flist_cont loading" id="pinboard_cont_' + box + '"></div></div>';
        $('#core_pinboard').append(HTML);
        if (thisRow >= perRow || allDrawn == boxCount) {
            $('#core_pinboard').append('</div>');
            thisRow = 0;
        }
        pinboard_fill_box(box);
    });
    $('#core_pinboard .flist_hhead_refresh').click(pinboard_refresh_ico);
}

function pinboard_fill_box(box)
{
    var availwidth = perBox-12, verteilcount = 0, verteilfields = [];
    $.each(boxinfo[box]['cols'], function (k, v) {
        if (v.w*1 == 0) {
            verteilcount++;
            verteilfields.push(k);
        } else {
            availwidth -= v.w;
            boxinfo[box]['cols'][k].cw = v.w; // Copy: cw == ComputedWidth
        }
    });
    // Evenly distribute avail width to flexwidth fields
    if (verteilcount > 0) {
        availwidth /= verteilcount;
        for (var i in verteilfields) {
            if (typeof (verteilfields[i]) !== 'string') {
                continue;
            }
            boxinfo[box]['cols'][verteilfields[i]].cw = Math.floor(availwidth);
        }
    }
    HTML = '';
    try {
        $.each(boxinfo[box]['rows'], function (k, v) {
            HTML += '<div class="inboxline" data-id="' + v.id + '" id="' + box + '_item_' + v.id + '">';
            $.each(boxinfo[box]['cols'], function (k2, v2) {
                if (k2 === 'id') {
                    return true; // continue
                }
                if (typeof v[k2]['css'] === 'undefined') {
                    v[k2]['css'] = '';
                }
                HTML += '<div class="inboxfield" title="' + v[k2]['t'] + '" style="width:' + (v2.cw-4) + 'px;' + v[k2]['css'] + '">' + v[k2]['v'] + '</div>';
            });
            HTML += '</div>';
        });
    } catch (e) {}
    $('#pinboard_cont_' + box).removeClass('loading').html(HTML).css('height', 'auto')
        .find('.inboxline').click(function () {
            window.setTimeout('parent.' + boxinfo[box]['stats']['action'] + '("' + this.data('id') + '")', 1);
        });
    pinboard_resize_rows();
}

function pinboard_refresh_ico()
{
    pinboard_refresh_box(this.id.replace(/^pinboard_refresh_/, ''));
}

function pinboard_refresh_box(box)
{
    $('#pinboard_cont_' + box).css('height', $('#pinboard_cont_' + box).height() + 'px').empty().addClass('loading');
    $.ajax({url: urlRefreshBox + box, success: pinboard_boxdata });
}

function pinboard_boxdata(data)
{
    boxinfo[data['box']]['rows'] = data['rows'];
    pinboard_fill_box(data['box']);
}

function pinboard_resize_rows()
{
    $('.pinboard_row').each(function() {
        var me = $(this);
        me.find('.pinboard_box').each(function () {
            if (me.height() < $(this).height()+8) me.height($(this).height()+8);
        })
    });
}

$(document).ready(function () {
    pinboard_dimensions();
    pinboard_draw_boxes();
});
$(window).resize(function () {
    pinboard_dimensions();
    pinboard_draw_boxes();
});