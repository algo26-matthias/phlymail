$(document).ready(function () {
    $('.draw-usage-bar').each(function () {
        var $this = $(this),
                $val = (typeof $this.attr('data-usage-val') !== 'undefined') ? $this.data('usage-val') : 0,
                $max = (typeof $this.attr('data-usage-max') !== 'undefined') ? $this.data('usage-max') : 1, // no div. by zero
                $class = 'usage',
                $css = 'width:calc(100% * ' + parseInt($val) + ' / ' + parseInt($max) + ');',
                $percentage = Math.round(parseInt($val) * 100 / parseInt($max)),
                $title = $percentage.toString() + '%';
        if (typeof $this.attr('data-extra') !== 'undefined') {
            $class += ' ' + $this.data('extra').toString();
        }
        if ($percentage > 80) {
            $class += ' warning';
        }
        $this.append('<div title="' + $title + '" class="usage-bar"><div class="' + $class + '" style="' + $css + '"></div></div>');
    });
});