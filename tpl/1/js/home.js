$(document).ready(function() {
      // append version information
     $("#left_col .title").eq (0).html ("NERDZ<small>mobile</small> <span style='font-weight: normal'><a href='/Mobile+Nerdz:' style='color: #000 !important'>[" + _mobileVersion + "]</a></span>");

    //tutti gli eventi ajax che evvengono in plist sono nel formato pilst.on(evento,[selettore],function(...){...});
    var plist = $("#postlist");
    var loading = $("#loadtxt").data('loading'); //il div è nell'header
    var lang = null; /* globale dato che la uso anche altrove */
    var load = false; //gestisce i caricamenti ed evita sovrapposizioni. Dichiarata qui che è il foglio che viene incluso di default ovunque e per primo
    plist.html('<h1>'+loading+'...</h1>');
    
    var fixHeights = function() {
        plist.find(".nerdz_message").each (function() {
            var el = $(this).find('div:first');
            if ((el.height() >= 200 || el.find ('.gistLoad').length > 0) && !el.attr('data-parsed'))
            {
                el.addClass("compressed");
                var n = el.next();
                n.prepend ('<p class="more">&gt;&gt;' + n.data ('expand') + '&lt;&lt;</p>');
            }
            el.attr('data-parsed','1');
        });
    };

    var hideHidden = function() {
        var hidden = localStorage.getItem('hid');

        if(hidden != null)
        {
            var pids = hidden.split("|");
            for(var i in pids)
            {
                var el = plist.find("#"+pids[i]);
                if(el)
                    el.hide();
            }
        }
        fixHeights();
    };

    plist.on('click','.more',function() {
        var me = $(this), par = me.parent(), jenk = par.prev();
        par.removeClass("shadowed");
        jenk.removeClass("compressed")
        me.slideUp ('slow', function() {
            me.remove();
        });
    });

    plist.on('click',".hide",function() {
        var pid = $(this).data('postid');
        $("#"+pid).hide();
        var hidden = localStorage.getItem('hid');
        if(hidden == null) {
            localStorage.setItem('hid',pid);
        }
        else
        {
            hidden += "|"+pid;
            localStorage.setItem('hid',hidden);
        }
        //auto lock
        var lock = $("#post"+pid).find('img.imgunlocked');
        if(lock.length)
        {
            lock.eq(0).click();
        }
    });
    
    plist.on('click', ".yt_frame", function(e) {
      if( navigator.userAgent.match(/Android|iPhone|iPad|iPod|Blackberry|BB10; Touch|Mobi|Opera Mini|IEMobile/i) ) {
        $(this).attr("href","http"+('https:' == document.location.protocol ? 's' : '')+"://m.youtube.com/watch?w="+$(this).data("vid"));
      } else {
        N.yt($(this), $(this).data("vid"))
      }
      return true;
    })
    
    $collapse = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAUJJREFUeNrsl8FtwjAYhWPuGaALdAHWKAN0AXIH7rj30nsYIB0AxigLZIEOwADps/QOVmWTBvKLVLxPspzYif2eY/92ikIIIYQQQgjxqDirhruuK5Gtebtzzp3/jQGKr5GeWdQiVRYmZgbig+gmEl/wumHddA1A4Jwj/5SoDmU1n5meAQhbUHx54bGSJhaTWgMQtES2TFS10RT6zR5rYn93AxDvkb1kxFe8rjMmjjDh72IgEWkuCuszem2EcjdEGj90avRMNY/3WnMDFJ9brG8QcfjDYt8mqs78Eq2ZAXa+SogPnW/Q+WlAuH3PtPPRNwhXGUCnr9HRIOY7lA8dOX7JXWbPCEePz9EMWC3AoYFg7I3seOv5hu9WbMsWjFbYQb+YvEH7Pmq/NjlhIjVjHgNSQYJ9lPrTEUIIIYQQQvTxI8AA3Z2n70qhoAYAAAAASUVORK5CYII=";
    
    $expand = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAVxJREFUeNrsl9ENgjAQhsF3BnABFnANHMAF4F19F9/V9zKADqBj6AIu4AAMgNfkTBpyBQoticnf5FLShrv/63EHLKI/HwsAAAAAAAAAAAAAAAAAAADBAZqmSciuZOtQYrRvjpGEcK7InmxlAP+l4V95zQALXhlLGQdMPAhP2H9mLK+GHtKUGtAB1RQIvle1xDuN2CHYhqadsPXR63Ecvx3FpzSdyZbC9pn83bwC/IqMpi1Z+9Rrsj0FfQ30ox/Hk8XPhfzcvWegdXJKCK7HsS84H8JB2NLiC9dMOtcAByjIpEAHEph3iM8t4t9jxI/KgFCAqbD9IDGl0MmyDvF10CLuabFWYXw9GHR2AOPRyC0QkUV8ReKrqbG9APQUZzSm2GcH6GmPo9rt7F+jLKzgl5v0wit8iveegY4ONanTzA5gQOyMT4M6wsAvJQAAAAAAAAAAAAAAAADgcXwFGAB1L66rldX2mQAAAABJRU5ErkJggg==";

    $("#profilePostList").on('click',function() {
        plist.html('<h1>'+loading+'...</h1>');
        $("#fast_nerdz").show();
        $("#nerdzlist").hide();
        $(".active-lang").removeClass('active-lang');
        localStorage.removeItem("autolang");
        load = false;
        N.html.profile.getHomePostList(0,function(data) {
            plist.html(data);
            plist.data('type','profile');
            plist.data('mode','std');
            hideHidden();
            console.log($expand);
            $("#nerdzselect").attr("src",$expand);
            load = true;
        });
    });
    
    $("#projectPostList").on('click',function() {
        plist.html('<h1>'+loading+'...</h1>');
        $("#fast_nerdz").hide();
        $("#projlist").hide();
        $("#projselect").attr("src",$expand);
        $(".active-plang").removeClass('active-plang');
        load = false;
        N.html.project.getHomePostList(0,function(data) {
            plist.html(data);
            plist.data('type','project');
            plist.data('mode','std');
            hideHidden();
            load = true;
        });
    });

    $("#nerdzselect").on('click',function() {
		$(this).attr("src") != $collapse ? $(this).attr("src",$collapse) : $(this).attr("src",$expand);
        $("#nerdzlist").slideToggle();
    });

    $("#projselect").on('click',function() {
		$(this).attr("src") != $collapse ? $(this).attr("src",$collapse) : $(this).attr("src",$expand);
        $("#projlist").slideToggle();
    });

    $(".selectlang").on('click',function() {
        plist.html('<h1>'+loading+'...</h1>');
        lang = $(this).data('lang');
        localStorage.setItem("autolang",lang);
        $(".active-lang").removeClass('active-lang');
        $(this).addClass('active-lang');
        load = false;
        if(lang == 'usersifollow')
        {
            $("#fast_nerdz").show();
            N.html.profile.getFollowedHomePostList(0,function(data) {
                plist.html(data);
                plist.data('type','profile');
                plist.data('mode','followed');
                hideHidden();
                load = true;
            });
        }
        else
        {
            if(lang == '*') {
                $("#fast_nerdz").show();
            }
            else {
                $("#fast_nerdz").hide();
            }

            load = false;
            N.html.profile.getByLangHomePostList(0,lang,function(data) {
                plist.html(data);
                plist.data('mode','language');
                plist.data('type','profile');
                hideHidden();
                load = true;
            });
        }
    });

    $(".projlang").on('click',function() {
        $("#fast_nerdz").hide();
        plist.html('<h1>'+loading+'...</h1>');
        lang = $(this).data('lang');
        $(".active-plang").removeClass("active-plang")
        $(this).addClass('active-plang');
        load = false;
        if(lang == 'usersifollow')
        {
                N.html.project.getFollowedHomePostList(0,function(data) {
                plist.html(data);
                plist.data('type','project');
                plist.data('mode','followed');
                hideHidden();
                load = true;
            });
        }
        else
        {
            N.html.project.getByLangHomePostList(0,lang,function(data) {
                plist.html(data);
                plist.data('type','project');
                plist.data('mode','language');
                hideHidden();
                load = true;
            });
        }
    });
    
    $("#stdfrm").on('submit',function(e) {
        e.preventDefault();
        $("#pmessage").html(loading+'...');
        N.json.profile.newPost({message: $("#frmtxt").val(), to: 0 },function(data) {
            if(data.status == 'ok') {
                $("#frmtxt").val('');
                load = false;
                if(lang == '*') {
                    N.html.profile.getByLangHomePostList(0,lang,function(data) {
                        plist.html(data);
                        plist.data('type','profile');
                        plist.data('mode','language');
                        hideHidden();
                        load = true;
                    });
                }
                else if(lang == 'usersifollow') {
                    N.html.profile.getFollowedHomePostList(0,function(data) {
                        plist.html(data);
                        plist.data('type','profile');
                        plist.data('mode','followed');
                        hideHidden();
                        load = true;
                    });
                }
                else {
                    $("#profilePostList").click();
                }
            }
            
            $("#pmessage").html(data.message);

            setTimeout(function() {
                        $("#pmessage").html('');
                        },5000);
        });
    });

    //default profile posts
    if(localStorage.getItem("autolang"))
    {
        $("#nerdzselect").click();
        var el = $("#nerdzlist").find("ul").find("[data-lang='"+localStorage.getItem("autolang")+"']");
        el.click();
        el.addClass("active-lang");
    }
    else
    {
        plist.data('location','home');
        load = false;
        N.html.profile.getHomePostList(0,function(data) {
            plist.html(data);
            hideHidden();
            plist.data('type','profile');
            plist.data('mode','std');
            load = true;
        });
    }

    /* Autoload vecchi post allo scrolldown */
        //questo serve per search, che avendo l'azione iniziale nel file default.js, non condivide la variabile load. Uso sessionStorage per ovviare
    var sl = 'searchLoad'; /*search label */
    sessionStorage.setItem(sl,"0");
    var tmpDivId = "scrtxt";
    var manageScrollResponse = function(data) {
        $("#"+tmpDivId).remove();
        if(data.length > 0) {
            plist.append(data);
            hideHidden();
            load = true;
            sessionStorage.setItem(sl, "0"); // se sono entrato qui, sicuramente non cerco
        }
    };

    var manageScrollSearchResponse = function(data) {
        $("#"+tmpDivId).remove();
        if(data.length > 0) {
            plist.append(data);
            hideHidden();
            sessionStorage.setItem(sl, "1");
            load = false; // se sono entrato qui, sicuramente stavo cercando
        }
    };

    $(window).scroll(function() {
        if($(this).scrollTop()+200 >= ( $(document).height() - $(this).height() ))
        {
            var num = 10; //TODO: numero di posts, parametro?
            var hpid = plist.find("div[id^='post']").last().data('hpid');
            var mode = plist.data('mode');
            var type = plist.data('type');
            var append = '<h3 id="'+tmpDivId+'">'+loading+'...</h3>';

            if((load || ("1" == sessionStorage.getItem(sl))) && !$("#"+tmpDivId).length)
            {
                plist.append(append);
            }

            if(load)
            {
                load = false;
                if(mode == 'std') {
                    N.html[type].getHomePostListBeforeHpid(num,hpid,manageScrollResponse);
                }
                else if(mode == 'followed') {
                    N.html[type].getFollowedHomePostListBeforeHpid(num,hpid,manageScrollResponse);
                }
                else if(mode == 'language') {
                    N.html[type].getByLangHomePostListBeforeHpid(num,lang,hpid, manageScrollResponse);
                }
            }
            //a true ci va in default.js, dopo il primo search
            if(sessionStorage.getItem(sl) == "1")
            {
                sessionStorage.setItem(sl, "0");
                if(type == 'project' && mode == 'search')
                {
                    N.html.search.globalProjectPostsBeforeHpid(num,$("#footersearch input[name=q]").val(), hpid, manageScrollSearchResponse);
                }
                else if(type == 'profile' && mode == 'search')
                {
                    N.html.search.globalProfilePostsBeforeHpid(num, $("#footersearch input[name=q]").val(), hpid, manageScrollSearchResponse);
                }
            }
        }
    });
});
