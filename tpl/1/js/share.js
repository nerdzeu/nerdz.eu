$(document).ready(function() {
    $("#tshare").submit(function(event) {
        event.preventDefault();
        N.json.profile.share($(this).serialize(),function(d) {
            if(d.status == 'ok')
            {
                $(this).html('<h1>' + d.message + '</h1>');
            }
            else
            {
                $("#errore").html(d.message);
            }
        });
    });
});
