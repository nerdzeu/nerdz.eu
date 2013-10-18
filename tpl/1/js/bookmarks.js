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

            if(url.search(/asc=[0-1]/i) != -1)
            {
                url = url.replace(/asc=[0-1]/i,'asc=' + (    url.match(/asc=([0-1])/i)[1] == '1' ? '0' : '1'));
            }
            else
            {
                url+= '&asc=0';
            }
            return url;
        };

        $("#time, #preview").click(function() {
            location.replace(fixURL(location.href,$(this).attr('id')));
        });

    $("#center_col").on('click',".unbookmark",function() {
        var me = $(this);
        var tog = function(d) {
            if(d.status == 'ok') {
                $("#"+me.data('refto')).hide();
            }
        }
          
          N.json[$("#center_col").data('type')].unbookmarkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});

    });

    $("#footersearch").on('submit',function(e) {
        e.preventDefault();
        var url = location.href, order = '';

        url.replace(/orderby=([a-z]+)/i,function(match,str) {
            order = str;
        });

        if(order == '')
        {
            order = 'preview';
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
