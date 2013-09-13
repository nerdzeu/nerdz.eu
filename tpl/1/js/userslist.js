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

        $("#id, #name, #description").on('click', function() {
            location.replace(fixURL(location.href,$(this).attr('id')));
        });
        
        $("#search").on('submit', function(event) {
            event.preventDefault();
            
            var queryString = "";

            $.each($(this).serializeArray(), function(i, field) {
                if(field.value != "") {
                    queryString += (queryString == "" ? "" : ",") + field.name + "=" + field.value;
                    /* uso , (comma) come separatore per i parametri della query (?q=name=a,surname=b,...), nella pagina php sono da strippare in base alla posizione della ,
                    e ogni virgola significa AND */
                }
            });
        });
});