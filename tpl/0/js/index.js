$(document).ready(function() {
    $("#regfrm").on('submit',function(event) {
        event.preventDefault();
        N.json.register($("#regfrm").serialize(),function(obj) {
            
            if(obj.status == 'error')
            {
                $("#error").html(obj.message.replace(/\n/g,"<br />"));
                $("#cptxt").html('');
                N.reloadCaptcha();
            }
            else if(obj.status == 'ok')
            {
                $("#error").hide();
                $("#done").html(obj.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        });
    });
});
