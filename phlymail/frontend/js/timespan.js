/**
* @method timeSpan
* @param fromDate {Date} The starting date.
* @param toDate {Date} The ending date.
* @param format {String} A comma separated list of the formats to return, from greatest to least (years > months > weeks > days > hours > minutes > seconds > milliseconds).
* @sample timeSpan(new Date(2009,12,12), new Date(2008,1,12), "months,weeks,days");
* @author Stephen Rushing
**/
function timeSpan(fromDate, toDate, format)
{
    if (format == null) format = "milliseconds";
    var formatsMS = {
        milliseconds: 1,
        seconds: 1000,
        minutes: 1000*60,
        hours: 1000*60*60,
        days: 1000*60*60*24,
        weeks: 1000*60*60*24*7,
        months: function(m) {
            var ms = this.days,
            daysPer = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];
            return ms*daysPer[m];
        },
        years: function(y) {
            var ms = 1000*60*60*24*365;
            //add a day for leap years
            if( (y % 4 == 0 && y % 100 == 0) || y % 400 == 0 )
            ms += this.days;
            return ms;
        }
    },
    // get the time difference in milliseconds
    ms = toDate.getTime() - fromDate.getTime(),
    reqFormats = format.split(","),
    isYearReq = (format.indexOf("years") > -1),
    isMonthReq = (format.indexOf("months") > -1),
    result = {};

    if (isYearReq){
        result["years"] = 0;
        for (var y = fromDate.getFullYear(); y <= toDate.getFullYear(); y++) {
            var yearMS = formatsMS.years(y);
            if (ms >= yearMS) {
                ms -= yearMS;
                result["years"] += 1;
            }
        }
        // use "to" year for calculating decimal
        formatsMS.years = formatsMS.years(toDate.getFullYear());
    }
    if (isMonthReq) {
        result["months"] = 0;
        var month = fromDate.getMonth(),
        year = (result["years"] > 0) ? fromDate.getFullYear() + result["years"] : fromDate.getFullYear();
        for (month; month <= 11; month++) {
            var monthMS = formatsMS.months(month);
            if (month == toDate.getMonth() && year == toDate.getFullYear()) {
                break;
            } else if (ms >= monthMS) {
                ms -= monthMS;
                result["months"] += 1;
            }
            if (month == 12 && year < toDate.getFullYear()) {
                month = 0;
                year++;
            }
        }
        // use "to" month for decimal
        formatsMS.months = formatsMS.months(toDate.getMonth());
    }
    // handle the remaining milliseconds
    for (var f = 0; f < reqFormats.length && reqFormats[0] != ''; f++) {
        var res = (f < reqFormats.length-1) ? Math.floor(ms/formatsMS[reqFormats[f]]) : ms/formatsMS[reqFormats[f]];
        if (ms > 0) {
            result[reqFormats[f]] = (result[reqFormats[f]] >= 0) ? result[reqFormats[f]] += res : res;
        } else {
            result[reqFormats[f]] = 0;
        }
        ms -= res * formatsMS[reqFormats[f]];
    }
    return result;
}