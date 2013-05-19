$(document).ready(function() {
	//tutti gli eventi ajax che evvengono in plist sono nel formato pilst.on(evento,[selettore],function(...){...});
	var plist = $("#postlist");
	var loading = $("#loadtxt").data('loading'); //il div è nell'header
	var operaMini = navigator.userAgent.match(/Opera Mini/i);
	plist.html('<h1>'+loading+'...</h1>');

	var fixHeights = function() {
		plist.find(".nerdz_message").each(function() {
			var el = $(this).find('div:first');
			if(el.height() > 200 && !el.attr('data-parsed')) {
				el.height(200);
				var n = el.parent().find('div:last-child');
				n.append('<div class="more">...</div>');
				n.css('background-color','#FFFDD0');
				n.css('color','#000');
			}
			el.attr('data-parsed','1');
		});
	};

	var hideHidden = function() {
		if(!operaMini) {
			var hidden = localStorage.getItem('hid');

			if(hidden != null)
			{
				var pids = hidden.split("|");
				for(var i in pids)
				{
					var el = plist.find("#"+pids[i]);
					if(el)
					{
						el.hide();
					}
				}
			}
		}
		fixHeights();
	};

	plist.on('click','.more',function() {
		var par = $(this).parent();
		par.parent().find('div:first').height('100%');
		par.css('background-color','#000');
		par.css('color','#FFF');
		$(this).remove();
	});

	plist.on('click',".hide",function() {
		var pid = $(this).data('postid');
		$("#"+pid).hide();
		if(!operaMini) {
			var hidden = localStorage.getItem('hid');
			if(hidden == null) {
				localStorage.setItem('hid',pid);
			}
			else
			{
				hidden += "|"+pid;
				localStorage.setItem('hid',hidden);
			}
		}
	});

	$("#profilePostList").on('click',function() {
		plist.html('<h1>'+loading+'...</h1>');
		$("#fast_nerdz").show();
		$("#nerdzlist").hide();
		$(".selectlang").css('color','');
		N.html.profile.getHomePostList(0,function(data) {
			plist.html(data);
			plist.data('limit',0);
			plist.data('type','profile');
			plist.data('mode','std');
			hideHidden();
		});
	});
	
	$("#projectPostList").on('click',function() {
		plist.html('<h1>'+loading+'...</h1>');
		$("#fast_nerdz").hide();
		$("#projlist").hide();
		$(".projlang").css('color','');
		N.html.project.getHomePostList(0,function(data) {
			plist.html(data);
			plist.data('limit',0);
			plist.data('type','project');
			plist.data('mode','std');
			hideHidden();
		});
	});

	$("#nerdzselect").on('click',function() {
		$("#nerdzlist").toggle();
	});

	$("#projselect").on('click',function() {
		$("#projlist").toggle();
	});

	var lang = null; /* globale dato che la uso anche altrove */

	$(".selectlang").on('click',function() {
		plist.html('<h1>'+loading+'...</h1>');
		lang = $(this).data('lang');
		$(".selectlang").css('color','');
		$(this).css('color','#2370B6');
		if(lang == 'usersifollow')
		{
			$("#fast_nerdz").show();
			N.html.profile.getFollowedHomePostList(0,function(data) {
				plist.html(data);
				plist.data('limit',0);
				plist.data('type','profile');
				plist.data('mode','followed');
				hideHidden();
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

			N.html.profile.getByLangHomePostList(0,lang,function(data) {
				plist.html(data);
				plist.data('limit',0);
				plist.data('type','profile');
				plist.data('mode','language');
				hideHidden();
			});
		}
	});

	$(".projlang").on('click',function() {
		$("#fast_nerdz").hide();
		plist.html('<h1>'+loading+'...</h1>');
		lang = $(this).data('lang');
		$(".projlang").css('color','');
		$(this).css('color','#2370B6');
		if(lang == 'usersifollow')
		{
			N.html.project.getFollowedHomePostList(0,function(data) {
				plist.html(data);
				plist.data('limit',0);
				plist.data('type','project');
				plist.data('mode','followed');
				hideHidden();
			});
		}
		else
		{
			N.html.project.getByLangHomePostList(0,lang,function(data) {
				plist.html(data);
				plist.data('limit',0);
				plist.data('type','project');
				plist.data('mode','language');
				hideHidden();
			});
		}
	});
	
	$("#stdfrm").on('submit',function(e) {
		e.preventDefault();
		$("#pmessage").html(loading+'...');
		N.json.profile.newPost({message: $("#frmtxt").val(), to: 0 },function(data) {
			if(data.status == 'ok') {
				$("#frmtxt").val('');
				if(lang == '*') {
					N.html.profile.getByLangHomePostList(0,lang,function(data) {
						plist.html(data);
						plist.data('limit',0);
						plist.data('type','profile');
						plist.data('mode','language');
						hideHidden();
					});
				}
				else if(lang == 'usersifollow') {
					N.html.profile.getFollowedHomePostList(0,function(data) {
						plist.html(data);
						plist.data('limit',0);
						plist.data('type','profile');
						plist.data('mode','followed');
						hideHidden();
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

	plist.data('location','home');
	N.html.profile.getHomePostList(0,function(data) {
		plist.html(data);
		hideHidden();
		});
	

	/* Autoload vecchi post allo scrolldown */
	var load = true;
	$(window).scroll(function() {
		if($(this).scrollTop()+200 >= ( $(document).height() - $(this).height() ) )
		{
			var manageResponse = function(data) {
				$("#mainloadtxt").remove();
				if(data.length > 0) {
					plist.append(data);
					plist.data('limit',limit);
					load = true;
					hideHidden();
				}
			};

			if(load)
			{
				load = false;
				var limit = plist.data('limit')+10;
				var mode = plist.data('mode');
				plist.append('<h3 id="mainloadtxt">'+loading+'...</h3>');
				
				if( plist.data('type') == 'project')
				{
					if(mode == 'std') {
						N.html.project.getHomePostList(limit,manageResponse);
					}
					else if(mode == 'followed') {
						N.html.project.getFollowedHomePostList(limit,manageResponse);
					}
					else if(mode == 'language') {
						N.html.project.getByLangHomePostList(limit, lang, manageResponse);
					}
					else
					{
						N.html.search.globalProjectPosts( { q: $("#footersearch input[name=q]").val(), limit: limit }, manageResponse);
					}
				}
				else
				{
					if(mode == 'std') {
						N.html.profile.getHomePostList(limit,manageResponse);
					}
					else if(mode == 'followed') {
						N.html.profile.getFollowedHomePostList(limit,manageResponse);
					}
					else if(mode == 'language') {
						N.html.profile.getByLangHomePostList(limit, lang, manageResponse);
					}
					else
					{
						N.html.search.globalProfilePosts( { q: $("#footersearch input[name=q]").val(), limit: limit},manageResponse);
					}
				}
			}
		}
	});
});
