$(document).ready(function() {
        var fixURL = function(url,order) {
            if(url.search(/orderby=[a-z]+/i) != -1)
            {
                url = url.replace(/orderby=[a-z]+/i,'orderby='+order);
            }
            else
            {
                url+= (location.search == '' ? '?' : '&') + 'orderby='+order;
            }

            if(url.search(/desc=[0-1]/i) != -1)
            {
                url = url.replace(/desc=[0-1]/i,'desc=' + (    url.match(/desc=([0-1])/i)[1] == '1' ? '0' : '1'));
            }
            else
            {
                url+= '&desc=0';
            }
            return url;
        };

        $("#id, #username, #name, #surname, #birthdate").click(function() {
            location.replace(fixURL(location.href,$(this).attr('id')));
        });

    $("#footersearch").on('submit',function(e) {
        e.preventDefault();
        var url = location.href, order = '';

        url.replace(/orderby=([a-z]+)/i,function(match,str) {
            order = str;
        });

        if(order == '')
        {
            order = 'username';
        }

        url = fixURL(url,order);
        var q = $(this).find('input[name=q]').val();

        if(url.search(/q=[a-z]+/i) != -1)
        {
            url = url.replace(/q=[a-z]+/i,'q='+q);
        }
        else
        {
            url = url+'&q='+q;
        }

        window.location = url;
    });
});
