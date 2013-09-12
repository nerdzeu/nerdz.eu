$(document).ready(function() {
    var c_from, c_to;

    $("#content").on('submit',"#convfrm",function(event) {
        event.preventDefault();
        $("#res").html('...');
        N.json.pm.send({
            to: $("#to").val(),
            message: $("#message").val(),
            subject: $("#subject").val()
            },function(d) {
                $('#res').html(d.message);
                if(d.status == 'ok') {
                    $("#message").val('');
                    N.html.pm.getConversation({ from: c_from, to: c_to, subject: c_subject},function(data) {
                        $("#conversation").html(data);
                    });
                }
        });
    });

    var c = $("#content");
    var newpm = false;

    $("#form").click(function() {
        c.html($("#loadtxt").data('loading')+'...');
        N.html.pm.getForm(function(data) {
            c.html(data);
        });
    });
    $("#inbox").click(function() {
        c.html($("#loadtxt").data('loading')+'...');
        N.html.pm.getInbox(function(data) {
            c.html(data);
            if(newpm)
            {
                setTimeout(function() { c.find('.getconv:first').click();},500);
                newpm = false;
                var count = $("#pmcounter");
                var cval = parseInt(count.html());
                if(!isNaN(cval) && cval != 0) {
                    count.html(cval -1);
                }
            }

        });
    });

    c.on('click',".delete",function(e) {
        var did = $($(this).data('id'));
        did.html($("#loadtxt").data('loading'));
        e.preventDefault();
        N.json.pm.delConversation({ from: $(this).data('from'), to: $(this).data('to'), time: $(this).data('time') },function(data) {
            if(data.status == 'ok') {
                $(did).hide();
            }
            else {
                $(did).html(data.message);
            }
        });
    });
        
    c.on('click',".getconv",function(e) {
        var conv = $("#conversation");
        conv.html($("#loadtxt").data('loading'));
        e.preventDefault();
        /*variabili esterne che richiamo per far mostrare il pm appena inviato */
        c_from =  $(this).data('from');
        c_to = $(this).data('to');
        c_subject = $(this).html();
        N.html.pm.getConversation({ from: c_from, to: c_to, subject: c_subject },function(data) {
            conv.html(data);
        });
    });

    c.on('click','.preview',function(){
        var txtarea = $($(this).data('refto'));
        txtarea.val(txtarea.val()+' '); //workaround
        var txt = txtarea.val();
        txtarea.val($.trim(txtarea.val()));
        if(undefined != txt && $.trim(txt) != '') {
            window.open('/preview.php?message='+encodeURIComponent(txt));
        }
    });

    setTimeout(function() {
        if(window.location.hash == '#new') {
            window.location.hash = '';
            newpm = true;
            $("#inbox").click();
        }
    },1000);

});
