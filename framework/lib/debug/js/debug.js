/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.cookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};


(function() {
    var lastVisible = false;


    function debugLoaded()
    {
        $(document).ready(function()
        {
            if ($.cookie('krismvc_debug_tb') === null)
            {
                $('#krisMvcWebBar').slideDown('fast');
            }
            else
            {
                $('#krisMvcWebBarButton').fadeIn('slow');
            }

            //hide toolbar and make visible the 'show' button
            $('span.downarr a').click(function()
            {
                $('#krisMvcWebBar').slideToggle('fast');
                $('#krisMvcDebugDataHolder').hide();
                $('#krisMvcWebBarButton').fadeIn('slow');
                $.cookie('krismvc_debug_tb', true);
            });

            //show toolbar and hide the 'show' button
            $('span.showbar a').click(function()
            {
                $('#krisMvcWebBar').slideToggle('fast');
                $('#krisMvcWebBarButton').fadeOut();
                $.cookie('krismvc_debug_tb', null);
            });

            $('.debugList').click(function()
            {
                var debugWhat = $(this).attr('id'), holder = $('#krisMvcDebugDataHolder'), visible = holder.is(':visible'), text = '';

                if (lastVisible == debugWhat && visible)
                {
                    holder.slideToggle('fast');
                    lastVisible = false;
                }
                else
                {

                    switch (debugWhat)
                    {
                        /** @namespace dbLog */ /** @namespace timeLog */ /** @namespace memoryLog */ /** @namespace debugLog */
                        case 'database' :
                                text += '<h2>Database Log</h2><ol>';
                                for (var i = 0; i < dbLog.length; i++)
                                {

                                    text += '<li>' + dbLog[i].func + ' [' + dbLog[i].milliseconds + ' milliseconds]: ' + dbLog[i].query + '</li>';
                                }
                                text += '</ol>';
                            break;
                        case 'time' : text = timeLog;
                            break;
                        case 'memory' : text = memoryLog;
                            break;
                        case 'logs' : text = debugLog;
                            break;
                        case 'config' : text = '<h2>Config Info</h2>Not yet implemented...';
                            break;
                        default: text = '';
                    }

                    if (text.length > 0)
                    {
                        if (visible)
                        {
                            holder.slideToggle('fast');
                        }
                        $('#krisMvcDebugData').html(text);
                        holder.slideToggle('fast');
                    }

                    lastVisible = debugWhat;
                }
            });
        });
    }

    // If jquery isn't on the page, load jquery....
    if (typeof jQuery == 'undefined') {
        var script = document.createElement('script');
        script.setAttribute('type', 'text/javascript');
        script.onload = debugLoaded;
        script.setAttribute('src', 'http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js');
        document.getElementsByTagName('head')[0].appendChild(script);
    }
    else
    {
        debugLoaded();
    }
})();