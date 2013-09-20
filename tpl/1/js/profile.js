$(document).ready(function() {
    var loading = $("#loadtxt").data('loading'); //il div è nell'header

    $("#stdfrm").on('submit',function(event) {
        event.preventDefault();
         $("#pmessage").html(loading+'...');
        N.json.profile.newPost({message: $("#frmtxt").val(), to: $(this).data('to') },function(data) {
            if(data.status == 'ok') {
                $("#showpostlist").click();
                $("#frmtxt").val('');
            }
            
            $("#pmessage").html(data.message);

            setTimeout(function() {
                        $("#pmessage").html('');
                        },5000);
        });
    });

    var oldPlist = "";
    $("#follow").click(function() {
        var me = $(this);
        me.html('...');
        N.json.profile.follow({id: $(this).data('id')},function(d) {
            me.html(d.message);
            me.off('click');
        });
    });

    $("#unfollow").click(function() {
        var me = $(this);
        me.html('...');
        N.json.profile.unfollow({id: $(this).data('id')},function(d) {
            me.html(d.message);
            me.off('click');
        });
    });

    $("#blacklist").click(function() {
        var me = $(this);
        var plist = $("#postlist");
        oldPlist = plist.html();
        plist.html('<form id="blfrm">Motivation: <textarea style="width:100%; height:60px" id="blmot"></textarea><br /><input type="submit" value="Blacklist" /></form>');
        plist.on('submit','#blfrm',function(event) {
            event.preventDefault();
            me.html('...');
            N.json.profile.blacklist({
                    id: me.data('id'),
                    motivation: $("#blmot").val()
                },function(d) {
                    me.html(d.message);
                    plist.html(oldPlist);
                    me.off('click');
            });
        });
    });

    $("#unblacklist").click(function() {
        var me = $(this);
        me.html('...');
        N.json.profile.unblacklist({id: $(this).data('id')},function(d) {
            me.html(d.message);
            me.off('click');
        });
    });

    $("#profilepm").on('click',function() {
        var me = $(this), txt = me.html();
        if(oldPlist == "") {
            me.html('...');
            N.html.pm.getForm(function(data) {
                oldPlist = $("#postlist").html();
                $("#postlist").html(data);
                $("#to").val($("#username").html());
                $("#fast_nerdz").hide();
            });
        }
        else
        {
            me.html(txt);
            $("#fast_nerdz").show();
            $("#postlist").html(oldPlist);
            oldPlist = "";
        }
    });

    $("#postlist").on('submit',"#convfrm",function(e) { //per i pm
        e.preventDefault();
        $("#res").html('...');
        N.json.pm.send({
            tok: $(this).data('tok'),
            to: $("#to").val(),
            message: $("#message").val(),
            },function(d) {
                $('#res').html(d.message);
                if(d.status == 'ok') {
                    setTimeout(function() {
                        $("#fast_nerdz").show();
                        $("#postlist").html(oldPlist);
                    },500);
                }
        });
    });
});
