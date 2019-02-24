var $globalPageTitle = null;

function cancel_event(e)
{
    var evt = e || window.event;
    if (window.event) evt.cancelBubble = true;
    if (evt.preventDefault) evt.preventDefault();
    if (evt.stopPropagation) evt.stopPropagation();
    evt.returnValue = false;
    return false;
}

function adjust_height()
{
    var wh = $(window).height();
    var dh = $(document).height();
    var ww = $(window).width();
    var dw = $(document).width();
    window.resizeBy(ww < dw ? dw-ww : 0, wh < dh ? dh-wh : 0);
}

function setlinks(anzahl)
{
    var singleclass = 'disabledbut';
    var notnullclass = 'disabledbut';
    if (1 == anzahl) {
        singleclass = 'activebut';
        notnullclass = 'activebut';
    } else if (anzahl > 1) {
        singleclass = 'disabledbut';
        notnullclass = 'activebut';
    }
    $('.single').removeClass('activebut disabledbut').addClass(singleclass);
    $('.notnull').removeClass('activebut disabledbut').addClass(notnullclass);
    $('.noaction').removeClass('activebut disabledbut').addClass('disabledbut');
}

function pageTitleNotification(msg)
{
    if ($globalPageTitle === null) {
        return false;
    }
    if (msg === 0 || msg === '') {
        $globalPageTitle.text($globalPageTitle.data('orig-val'));
    } else if (typeof msg === 'string'
             || typeof msg === 'number') {
         $globalPageTitle.text(msg + ' · ' + $globalPageTitle.data('orig-val'));
    }
}

function stringRepeat(pattern, count)
{
    if (count < 1) {
        return '';
    }
    var result = '';
    while (count > 1) {
        if (count & 1) {
            result += pattern;
        }
        count >>= 1;
        pattern += pattern;
    }
    return result + pattern;
}

$(document).ready(function () {
    $globalPageTitle = $('head title');
    $globalPageTitle.data('orig-val', $globalPageTitle.text());

    $('.datepicker,.datetimepicker,.timepicker,.duration').attr('autocomplete', 'off');
    $('.datepicker').datepicker({ changeMonth: true, changeYear: true, selectOtherMonths: true, showOtherMonths: true, showWeek: true });
    $('.datetimepicker').datetimepicker({ autoSize: false, constrainInput: false, changeMonth: true, changeYear: true, selectOtherMonths: true, showOtherMonths: true, showWeek: true });
    $('.timepicker').timepicker();
    $('#pagewide-messagebox').delay(5000).fadeOut('slow', function () {$('#pagewide-messagebox').remove(); });
});