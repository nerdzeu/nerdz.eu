$(document).ready(function() {
    var c_from, c_to;
    var loadtxt = $("#loadtxt").data('loading')+'...';

    $("#content").on('submit',"#convfrm",function(e) {
        e.preventDefault();
        $("#res").html(loadtxt);
        var pattern = "div[id^='pm']";
        var mess = $("#conversation").find(pattern);
        var last = null;
        var pmid = 0;

        if(mess.length) {
            last = mess.length > 1 ? mess.eq (mess.length - 2) : null; // request every message if cnum < 2.
            pmid = last ? last.data('pmid') : 0;
        }
        N.json.pm.send({ to: $("#to").val(), message: $("#message").val() },function(d) {
                $('#res').html(d.message);
                if(d.status == 'ok') {
                    $("#message").val('');
                    if(pmid) {
                        N.html.pm.getConversationAfterPmid({ from: c_from, to: c_to, pmid: pmid }, function(d) {
                            var newPms = $('<div>' + d + '</div>').find (pattern), internalCounter = mess.length, lastPm = mess.last();
                            // This implementation is almost the same as the one in tpl/x/js/default.js.
                            // For explanations see that file.
                            // NOTE: it appears that mess.eq (mess.length - 1) isn't properly selecting
                            // the secondlast element. But.. it works. Yeah, I really can't explain that.
                            // But until it works, let's keep it like that. I left debugging stuff here for that.
                            //console.log ("DBG: secondlast? " + mess.eq(mess.length-1).data('pmid'));
                            if (mess.length > 1) {
                                mess.eq (mess.length - 1).remove();
                                internalCounter--;
                            }
                            if (lastPm.data ('pmid') == newPms.last().data ('pmid')) {
                                lastPm.remove();
                                internalCounter--;
                            }
                            //console.log ("Reduce to: " + ((($(".more_btn").data ('counter') || 0) + 1) * 10));
                            //console.log ("counter: " + internalCounter + ", newPmLength: " + newPms.length);
                            while ((internalCounter + newPms.length) > ((($(".more_btn").data ('counter') || 0) + 1) * 10))
                            {
                                mess.first().remove();
                                mess = $("#conversation").find (pattern);
                                internalCounter--;
                            }
                            //console.log ("counter: " + internalCounter);
                            $("#convfrm").before (d);
                        });
                    }
                    else {
                        N.html.pm.getConversation({ from: c_from, to: c_to, start: 0, num: 10 },function(data) {
                            $("#conversation").html(data);
                        });
                    }
                }
        });
    });

    var c = $("#content");
    var newpm = false;

    $("#form").click(function() {
        c.html(loadtxt);
        N.html.pm.getForm(function(data) {
            c.html(data);
        });
    });
    $("#inbox").click(function() {
        c.html(loadtxt);
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
        did.html(loadtxt);
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
        conv.html(loadtxt);
        e.preventDefault();
        /*variabili esterne che richiamo per far mostrare il pm appena inviato */
        c_from =  $(this).data('from');
        c_to = $(this).data('to');
        N.html.pm.getConversation({ from: c_from, to: c_to, start: 0, num: 10 },function(data) {
            conv.html(data);
            window.location.hash = 'message';
            $("#message").focus();
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
    
    c.on('keydown',"textarea", function(e) {
        if( e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13) ) {
            $(this).parent().trigger('submit');
        }
    });

    c.on ('click', '.more_btn', function() {
        var thisBtn = $(this),
            internalPointer = thisBtn.data ('counter') || 0;
        if (thisBtn.data ('in-progress') === '1') return;
        thisBtn.data ('in-progress', '1').text (loadtxt + '...');
        N.html.pm.getConversation ({ from: c_from, to: c_to, start: internalPointer + 1, num: 10 }, function (data) {
            thisBtn.data ('in-progress', '0').data ('counter', ++internalPointer).text (thisBtn.data ('localization'));
            var parsedData = $(data);
            parsedData.insertAfter (thisBtn.parent());
            if (internalPointer == 1)
                thisBtn.parent().find ('.scroll_bottom_hidden').show();
            if ($.trim (data) == ''|| parsedData.find ('.nerdz_from').length < 10 || (10 * (internalPointer + 1)) == thisBtn.data ('count'))
            {
                var btnDb = thisBtn.hide().parent();
                btnDb.find (".scroll_bottom_separator").hide();
                btnDb.find (".all_msgs_hidden").hide();
            }
        });
    });

    c.on ('click', '.scroll_bottom_btn', function() {
        $("html, body").animate ({ scrollTop: $("#convfrm").offset().top }, function() {
            $("#message").focus();
        })
    });

    c.on ('click', '.all_msgs_btn', function() {
        var btn     = $(this),
            btnDb   = btn.parent().parent(),
            moreBtn = btnDb.find (".more_btn");
        if (btn.data ("working") === "1" || moreBtn.data ("in-progress") === "1") return;
        btn.data ("working", "1").text (loadtxt + "...");
        moreBtn.data ("in-progress", "1");
        N.html.pm.getConversation ({ from: c_from, to: c_to, forceNoForm: true }, function (data) {
            btn.data ("working", "0").text (btn.data ("localization")).parent().hide();
            btnDb.find (".scroll_bottom_hidden").show().find (".scroll_bottom_separator").hide();
            var parsedData = $("<div>" + data + "</div>"), push = $("#conversation");
            moreBtn.hide().data ("counter", Math.ceil (parsedData.find (".nerdz_from").length / 10));
            push.find ("div[id^=\"pm\"]").remove();
            $("#convfrm").before (data);
        });
    });

    setTimeout(function() {
        if(window.location.hash == '#new') {
            window.location.hash = '';
            newpm = true;
            $("#inbox").click();
        }
    },500);

});
