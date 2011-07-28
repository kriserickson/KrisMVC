(function() {
    var lastVisible = false;


    function debugLoaded()
    {
        $(document).ready(function(){
            $('#krisMvcWebBar').slideDown('fast');

            //hide toolbar and make visible the 'show' button
            $('span.downarr a').click(function() {
                $('#krisMvcWebBar').slideToggle('fast');
                $('#krisMvcDebugDataHolder').hide();
                $('#krisMvcWebBarButton').fadeIn('slow');
            });

            //show toolbar and hide the 'show' button
            $('span.showbar a').click(function() {
                $('#krisMvcWebBar').slideToggle('fast');
                $('#krisMvcWebBarButton').fadeOut();
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
                        /** @namespace dbLog */
                        /** @namespace timeLog */
                        /** @namespace memoryLog */
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
                        case 'logs' : text = '<h2>Log Info</h2>Not yet implemented...';
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