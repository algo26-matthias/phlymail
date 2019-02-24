thinedit =
    {teobj: null
    ,interactive: 0 // Do not use this, it will break the UI
    ,cpos: 0
    ,actlen : 0
    ,lastlen : 0
    ,linelength: 72
    ,forcewrap: 0
    ,wordwrap:  function (e) {
        if (thinedit.interactive == 1) {
            thinedit.actlen = thinedit.teobj.value.length;
            if (thinedit.actlen == thinedit.lastlen) return;
            if (!thinedit.getcursor()) return;
        }
        var val = thinedit.teobj.value.split("\n");
        var out = '';
        var vlen = val.length;
        var le = String.fromCharCode(10);
        var spc = String.fromCharCode(32);
        for (var i = 0; i < vlen; ++i) {
            if (i == vlen-1) le = '';
            if (val[i].substr(0, 1) == '>') {
                out += val[i] + le;
                continue;
            }
            if (val[i].length < thinedit.linelength) {
                out += val[i] + le;
                continue;
            }
            var wrd = val[i].split(" ");
            var wlen = wrd.length;
            var wout = '';
            for (var j = 0; j < wlen; ++j) {
                if (wrd[j].length >= thinedit.linelength) {
                    if (wout.length == 0) {
                        out += wrd[j] + le;
                        continue;
                    } else {
                        out += wout + le + wrd[j] + le;
                        wout = '';
                        continue;
                    }
                }
                if (wout.length + wrd[j].length >= thinedit.linelength) {
                    out += wout + le;
                    wout = wrd[j];
                    continue;
                }
                wout += (wout.length) ? spc + wrd[j] : wrd[j];
            }
            if (wout) out += wout + le;
        }
        thinedit.teobj.value = out;
        if (thinedit.interactive == 1) {
            thinedit.cpos += (thinedit.teobj.value.length) - thinedit.actlen;
            thinedit.lastlen = thinedit.teobj.value.length;
            thinedit.setcursor();
        }
    }
    ,inserttext: function (text) {
        thinedit.getcursor();
        if (thinedit.cpos) {
            var before = thinedit.teobj.value.substr(0, thinedit.cpos);
            var after  = thinedit.teobj.value.substr(thinedit.cpos);
            thinedit.teobj.value = before + text + after;
            thinedit.cpos += (text.length);
            thinedit.lastlen = thinedit.teobj.value.length;
            thinedit.setcursor();
        } else {
            thinedit.teobj.value = text + thinedit.teobj.value;
            thinedit.lastlen = thinedit.teobj.value.length;
        }
    }
    ,setcursor: function (e) {
        if (typeof thinedit.teobj.selectionStart != 'undefined') {
            thinedit.teobj.selectionStart = thinedit.cpos;
            thinedit.teobj.selectionEnd = thinedit.cpos;
            return true;
        } else if (typeof document.selectionStart != 'undefined') {
            document.selectionStart = thinedit.cpos;
            document.selectionEnd = thinedit.cpos;
            return true;
        } else if (typeof document.selection != 'undefined') {
            return false;
        }
    }
    ,getcursor: function (e) {
         if (typeof thinedit.teobj.selectionEnd != 'undefined') {
            thinedit.cpos = thinedit.teobj.selectionStart;
            return true;
        } else if (typeof document.selectionEnd != 'undefined') {
            thinedit.cpos = document.selectionStart;
            return true;
        } else if (typeof document.selection != 'undefined') {
            return false;
        }
    }
    ,start: function (who) {
        thinedit.teobj = who;
        thinedit.lastlen = thinedit.teobj.value.length;
        thinedit.teobj.focus();
        thinedit.setcursor();
        if (thinedit.interactive == 1) {
            thinedit.getcursor();
            thinedit.teobj.onkeyup = thinedit.wordwrap;
        }
    }
};