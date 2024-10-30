/**
 * Created by rothy on 12/1/14.
 */
(function ($) {
    $.fn.chartabel = function (options) {

        var settings = $.extend({
            colors: {},
            labels: {},
            titles: {},
            total: ''
        }, options);
        //console.log(this);
        var text     = '';
        for (i = 0; i < settings.labels.length; i++) {
            //'<li style="list-style-type: none;color: '+graph_color+'"><div><div style="height: 20px;width: 20px;display: inline-block;background-color: '+graph_color+'"></div><span title="(Votes :'+data.result[key]+')" style="margin-left:10px;display: inline-block;">'+data.answers[key]+'</span></div></li>';
            if (typeof (settings.colors[i]) === 'undefined') settings.colors[i] = '#e4e4e4';
            if (typeof (settings.titles[i]) === 'undefined') settings.titles[i] = 'No Label Found';
            text += '<li class="cbsmartresult-anslabels-item"><span style="display:inline-block;margin-right:5px;width:20px;height:20px;background-color: ' + settings.colors[i] + '" class="cbsmartresult-anslabels-item-lb cbsmartresult-anslabels-item-lb-box"></span><span title="' + settings.titles[i] + '" class="cbsmartresult-anslabels-item-lb cbsmartresult-anslabels-item-lb-text">' + settings.labels[i] + '</span></li>';

        }
        this.html(text);
        return this;
    };
}(jQuery));