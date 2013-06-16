$(document).ready(function() {
	var loading = $("#loadtxt").data('loading'); //il div è nell'header

	//elementi singoli
	$("iframe").attr('scrolling','no'); //dato che il validatore non li vuole e con i css overflow:hidden non funge
	$("body").append($('<br />')); //per fare funzionare infinte scrolling sempre

	$("#notifycounter").on('click',function(e) {
		e.preventDefault();
		var list = $("#notify_list"), old = $(this).html();
		var nold = parseInt(old);
		if(list.length) {
			if(isNaN(nold) || nold == 0)
			{
				list.remove();
			}
			else if(nold > 0) {
				list.prepend('<div id="pr_lo">'+loading+'</div>');
				N.html.getNotifications(function(d) {
					$("#pr_lo").remove();
                	list.prepend(d);
	             });
			}
		}
		else {
			var l = $(document.createElement("div"));
			l.attr('id',"notify_list");
			l.html(loading);
			$("body").append(l);
			N.html.getNotifications(function(d) {
				l.html(d);
			});
	
			$("#notify_list").on('click','.notref',function(e) {
				e.preventDefault();
				var href = $(this).attr('href');
				if(e.ctrlKey)
				{
					window.open(href);
					return false;
				}
				if(href == window.location.pathname + window.location.hash) {
					location.reload();
				}
				else {
					location.href = href;
				}
			});

		}
		$(this).html(isNaN(nold) ? old : '0');
	});

	/* il footersearch si mostra solo in alcune pagine */
	var wrongPages = [ '/bbcode.php','/terms.php','/faq.php','/stats.php','/rank.php','/preferences.php', '/informations.php' ];
	   if($.inArray(location.pathname,wrongPages) != -1) {
		   $("#footersearch").hide();
	   };

	$("#footersearch").on('submit',function(e) {
		e.preventDefault();
		var plist = $("#postlist");
		var qs =  $.trim($("#footersearch input[name=q]").val());
		var num = 10; //TODO: numero di posts, parametro?

		if(qs == '') {
			return false;
		}

		var manageResponse = function(d)
		{
			plist.html(d);
			//VARIABILE BOOLEANA MESSA COME STRINGA DATO CHE NEL DOM POSSO SALVARE SOLO STRINGHE, DEVO COMPARARARE COME STRINGA
			//CAPS LOCK DAY
			sessionStorage.setItem('searchLoad', "1"); //è la variabile load di search, dato che queste azioni sono in questo file js ma sono condivise da tutte le pagine, la variabile di caricamento dev'essere nota a tutte
		};

		if(plist.data('type') == 'project')
		{
			if(plist.data('location') == 'home')
			{
				N.html.search.globalProjectPosts(num, qs, manageResponse);
			}
			else
			{
				if(plist.data('location') == 'project')
				{
					N.html.search.specificProjectPosts(num, qs, plist.data('projectid'),manageResponse);
				}
			}
		}
		else
		{
			if(plist.data('location') == 'home')
			{
				N.html.search.globalProfilePosts(num, qs, manageResponse);
			}
			else
			{
				if(plist.data('location') == 'profile')
				{
					N.html.search.specificProfilePosts(num, qs, plist.data('profileid'),manageResponse);
				}
			}
		}
		plist.data('mode','search');
	});

	$("#logout").on('click',function(event) {
		event.preventDefault();
		var t = $("#title_right");
		N.json.logout( { tok: $(this).data('tok') }, function(r) {
			var tmp = t.html();
			if(r.status == 'ok')
			{
				t.html(r.message);
				setTimeout(function() {
					document.location.href = "/";
					},1500);
			}
			else
			{
				t.html('<h2>'+ r.message + '</h2>');
				setTimeout(function() {
					t.html(tmp);
				},1500);
			}
		});
	});

	$("#gotopm").on('click',function(e) {
			e.preventDefault();

			var href = $(this).attr('href');

			if($('#pmcounter').html() != '0') {

				if(href == window.location.pathname ) {
					location.hash = "new";
					location.reload();
				}
				else {
					location.href='/pm.php#new';
				}
			}
			else
			{
				location.href = href;
			}
	});

	$(".preview").on('click',function(){
		var txt = $($(this).data('refto')).val();
		if(undefined != txt && txt != '') {
			window.open('/preview.php?message='+encodeURIComponent(txt));
		}
	});
	
	$("textarea").on('keydown', function(e) {
		if( e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13) ) {
			$(this).parent().trigger('submit');
		}
	});

	//begin plist into events (common to: homepage, projects, profiles)
	var plist = $("#postlist");

	plist.on('click','.preview',function(){
		var txtarea = $($(this).data('refto'));
		txtarea.val(txtarea.val()+' '); //workaround
		var txt = txtarea.val();
		txtarea.val($.trim(txtarea.val()));
		if(undefined != txt && $.trim(txt) != '') {
			window.open('/preview.php?message='+encodeURIComponent(txt));
		}
	});

	plist.on('keydown',"textarea", function(e) {
		if( e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13) ) {
			$(this).parent().trigger('submit');
		}
	});

	plist.on('click',".delcomment",function() {
		var refto = $('#' + $(this).data('refto'));
		refto.html(loading+'...');

		if(plist.data('type') == 'profile') {
			N.json.profile.delComment({ hcid: $(this).data('hcid') },function(d) {
				if(d.status == 'ok')
				{
					refto.remove();
				}
				else
				{
					refto.html(d.message);
				}
			});
		}
		else
		{
			N.json.project.delComment({ hcid: $(this).data('hcid') },function(d) {
				if(d.status == 'ok')
				{
					refto.remove();
				}
				else
				{
					refto.html(d.message);
				}
			});
		}
	});

	plist.on('submit','.frmcomment',function(e) {
		e.preventDefault();
		var last, hcid,
			hpid     = $(this).data ('hpid'),
			refto    = $('#commentlist' + hpid),
			error    = $(this).find ('.error').eq (0),
			pattern  = 'div[id^="c"]',
			comments = refto.find (pattern);
		if(comments.length)
		{
			// Uses the second-last element instead of the last one (if available)
			// to fix the append bug reported by nessuno.
			last = comments.length > 1 ? comments.eq (comments.length - 2) : null;
			hcid = last ? last.data('hcid') : 0;
		}
		error.html (loading);
		N.json[plist.data('type')].addComment ({ hpid: hpid, message: $(this).find('textarea').eq(0).val() }, function(d) {
			if(d.status == 'ok')
			{
				if(hcid && last)
				{
					N.html[plist.data('type')].getCommentsAfterHcid ({ hpid: hpid, hcid: hcid }, function(d) {
						var form = refto.find ('form.frmcomment').eq (0),
							pushBefore = form.parent(),
							newComments = $('<div>' + d + '</div>').find (pattern),
							internalLengthPointer = comments.length,
							lastComment = comments.last();
						// if available, delete the secondlast comment
						if (comments.length > 1) {
							comments.eq (comments.length - 1).remove();
							internalLengthPointer--;
						}
						// then, check the hcid of the last comment
						// delete it if it matches
						if (lastComment.data ('hcid') == newComments.last().data ('hcid')) {
							lastComment.remove();
							internalLengthPointer--;
						}
						// wait until we reach 10 comments (except if the user pressed more)
						// TODO: replace this with comments.slice (0, n).remove()
						// TODO: add logic to show again the 'more' button if we deleted
						// enough comments
						while ((internalLengthPointer + newComments.length) > (((comments.parent().find ('more_btn').data ('morecount') || 0) + 1) * 10)) {
							comments.first().remove();
							// reassign the variable, otherwise .first() won't work
							// anymore with .remove().
							comments = refto.find (pattern);
							internalLengthPointer--;
						}
						// append newComments
						pushBefore.before (d);
						form.find ('textarea').val ('');
						error.html('');
					});
				}
				else
				{
					N.html[plist.data('type')].getComments( { hpid: hpid, start: 0, num: 10 },function(d) {
						refto.html(d);
						error.html('');
					});
				}
			}
			else
			{
				error.html(d.message);
			}
		});
	});

	plist.on('click',".showcomments",function() {
		var refto = $('#' + $(this).data('refto'));
		if(refto.html() == '')
		{
			refto.html(loading+'...');
			N.html[plist.data ('type')].getComments ({
				hpid: $(this).data ('hpid'),
				start: 0,
				num: 10
			}, function (res) {
				refto.html (res);
				if (window.location.hash == '#last')
					refto.find ('.frmcomment textarea[name=message]').focus();
			});
		}
		else
		{
			refto.html('');
		}
	});

	plist.on ('click', '.more_btn', function() {
		var moreBtn     = $(this),
			commentList = moreBtn.parents ("div[id^=\"commentlist\"]"),
			hpid        = /^post(\d+)$/.exec (commentList.parents ("div[id^=\"post\"]").attr ("id"))[1],
			intCounter  = moreBtn.data ("morecount") || 0;
		if (moreBtn.data ("inprogress") === "1") return;
		moreBtn.data ("inprogress", "1").text (loading + "...");
		N.html[plist.data ('type')].getComments ({ hpid: hpid, start: intCounter + 1, num: 10 }, function (r) {
			moreBtn.data ("inprogress", "0").data ("morecount", ++intCounter).text (moreBtn.data ('localization'));
			var _ref = $(r);
			// Lesson learned: don't use .parent() after a .hide()
			_ref.insertAfter (moreBtn.parent());
			if (intCounter == 1)
				moreBtn.parent().find (".scroll_bottom_hidden").show();
			if ($.trim (r) == "" || _ref.find (".nerdz_from").length < 10)
				moreBtn.hide().parent().find (".scroll_bottom_separator").hide();//html (moreBtn.parent().html().replace (/\s\|\s/, ""));
		});
	});

	plist.on ('click', '.scroll_bottom_btn', function() {
		// thanks to stackoverflow for .eq(x) and for the scroll hack
		var cForm = $(this).parents().eq (2).find (".frmcomment");
		$("html, body").animate ({ scrollTop: cForm.offset().top }, function() {
			cForm.find ("textarea").focus();
		});
	});

	plist.on('click',".qu_ico",function() {
		var area = $("#"+$(this).data('refto'));
		area.val(area.val()+"[quote="+ $(this).data('hcid') +"|"+$(this).data('type')+"]");
		area.focus();
	});

	plist.on('click',".delpost",function(e) {
		e.preventDefault();
		var refto = $('#' + $(this).data('refto'));
		var post = refto.html();
		var hpid = $(this).data('hpid');
		if(plist.data('type') == 'profile')
		{
			N.json.profile.delPostConfirm({ hpid: hpid },function(m) {
				if(m.status == 'ok') {
					refto.html('<div style="text-align:center">' + m.message + '<br /><span id="delPostOk' + hpid +'" style="cursor:pointer">YES</span>|<span id="delPostNo'+hpid+'" style="cursor:pointer">NO</span></div>');
					refto.on('click','#delPostOk'+hpid,function() {
						N.json.profile.delPost({ hpid: hpid	},function(j) {
							if(j.status == 'ok') {
								refto.hide();
							}
							else {
								refto.html(j.message);
							}
						});
					});

					refto.on('click','#delPostNo'+hpid,function() {
						refto.html(post);
					});
				}
			});
		}
		else
		{
			N.json.project.delPostConfirm({ hpid: hpid },function(m) {
				if(m.status == 'ok') {
					refto.html('<div style="text-align:center">' + m.message + '<br /><span id="delPostOk' + hpid +'" style="cursor:pointer">YES</span>|<span id="delPostNo'+hpid+'" style="cursor:pointer">NO</span></div>');
					refto.on('click','#delPostOk'+hpid,function() {
						N.json.project.delPost({ hpid: hpid	},function(j) {
							if(j.status == 'ok') {
								refto.hide();
							}
							else {
								refto.html(j.message);
							}
						});
					});

					refto.on('click','#delPostNo'+hpid,function() {
						refto.html(post);
					});
				}
			});
		}
	});

	plist.on('click',".editpost",function(e) {
		e.preventDefault();
		var refto = $('#' + $(this).data('refto')), hpid = $(this).data('hpid');
		var editlang = $(this).html();
		var form = function(fid,hpid,message,edlang,prev) {
					return 	'<form style="margin-bottom:40px" id="' +fid+ '" data-hpid="'+hpid+'">' +
							   '<textarea id="'+fid+'abc" autofocus style="width:99%; height:125px">' +message+ '</textarea><br />' +
							   '<input type="submit" value="' + edlang +'" style="float: right; margin-top:5px" />' +
	 						   '<button type="button" style="float:right; margin-top: 5px" class="preview" data-refto="#'+fid+'abc">'+prev+'</button>'+
							   '<button type="button" style="float:left; margin-top:5px" onclick="window.open(\'/bbcode.php\')">BBCode</button>' +
						   '</form>';
					};

		if(plist.data('type') == 'profile') {
			N.json.profile.getPost({hpid: hpid},function(d) {
				var fid = refto.attr('id') + 'editform';
				refto.html(form(fid,hpid,d.message,editlang,$(".preview").html()));

				$('#'+fid).on('submit',function(e) {
					e.preventDefault();
					N.json.profile.editPost(
						{
							hpid: $(this).data('hpid'),
							message: $(this).children('textarea').val()
						},function(d)
						{
							if(d.status == 'ok')
							{
								refto.slideToggle("slow");
								N.html.profile.getPost({hpid: hpid}, function(o) {
									refto.html(o);
									refto.slideToggle("slow");
								});
							}
							else {
								alert(d.message);
							}
					});
				});
			});
		}
		else
		{
			N.json.project.getPost({hpid: hpid},function(d) {
				var fid = refto.attr('id') + 'editform';
				refto.html(form(fid,hpid,d.message,editlang,$(".preview").html()));
				$('#'+fid).on('submit',function(e) {
					e.preventDefault();
					N.json.project.editPost(
						{
							hpid: $(this).data('hpid'),
							message: $(this).children('textarea').val()
						},function(d)
						{
							if(d.status == 'ok')
							{
								refto.slideToggle('slow');
								N.html.project.getPost({hpid: hpid}, function(o) {
									refto.html(o);
									refto.slideToggle('slow');
								});
							}
							else {
								alert(d.message);
							}
					});
				});
			});
		}
	});

	plist.on('click',".imglocked",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','imgunlocked');
				me.attr('src',newsrc.replace('/lock.png','/unlock.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			if($(this).data('silent')) { //nei commenti
				N.json.profile.reNotifyFromUserInPost({ hpid: $(this).data('hpid'), from: $(this).data('silent') },function(d) {tog(d);});
			}
			else {
				N.json.profile.reNotifyForThisPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
			}
		}
		else
		{
			if($(this).data('silent')) {
				N.json.project.reNotifyFromUserInPost({ hpid: $(this).data('hpid'), from: $(this).data('silent') },function(d) {tog(d);});
			}
			else {
				N.json.project.reNotifyForThisPost({hpid: $(this).data('hpid') },function(d) { tog(d);});
			}
		}
	});

	plist.on('click',".imgunlocked",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','imglocked');
				me.attr('src',newsrc.replace('/unlock.png','/lock.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			if($(this).data('silent')) {
				N.json.profile.noNotifyFromUserInPost({ hpid: $(this).data('hpid'), from: $(this).data('silent') },function(d) {tog(d);});
			}
			else {
				N.json.profile.noNotifyForThisPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
			}
		}
		else
		{
			if($(this).data('silent')) {
				N.json.project.noNotifyFromUserInPost({ hpid: $(this).data('hpid'), from: $(this).data('silent') },function(d) {tog(d);});
			}
			else {
				N.json.project.noNotifyForThisPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
			}
		}
	});

	plist.on('click',".lurk",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','unlurk');
				me.attr('src',newsrc.replace('/lurk.png','/unlurk.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			N.json.profile.lurkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
		else
		{
			N.json.project.lurkPost({hpid: $(this).data('hpid') },function(d) { tog(d);});
		}
	});

	plist.on('click',".unlurk",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','lurk');
				me.attr('src',newsrc.replace('/unlurk.png','/lurk.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			N.json.profile.unlurkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
		else
		{
			N.json.project.unlurkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
	});

	plist.on('click',".bookmark",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','unbookmark');
				me.attr('src',newsrc.replace('/bookmark.png','/unbookmark.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			N.json.profile.bookmarkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
		else
		{
			N.json.project.bookmarkPost({hpid: $(this).data('hpid') },function(d) { tog(d);});
		}
	});

	plist.on('click',".unbookmark",function() {
		var me = $(this);
		var tog = function(d) {
			if(d.status == 'ok') {
				var newsrc = me.attr('src');
				me.attr('class','bookmark');
				me.attr('src',newsrc.replace('/unbookmark.png','/bookmark.png'));
				me.attr('title',d.message);
			}
		}

		if(plist.data('type') == 'profile')
		{
			N.json.profile.unbookmarkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
		else
		{
			N.json.project.unbookmarkPost({hpid: $(this).data('hpid') },function(d) {tog(d);});
		}
	});
	// EASTER EGG! :O
	// NOTE: If you alreay tried/discovered this easter egg, then feel free
	// to read the code. Otherwise don't be a bad guy and try to find it by yourself.
	var code = [ 38, 38, 40, 40, 37, 39, 37, 39, 66, 65 ], pressed = [];
	window._NERDZ_NICK = $.trim (/,(.+)/.exec ($("nav div").text())[1]);
	$(window).keydown (function dEv (e) {
		pressed.push (e.keyCode);
		while (pressed.length > code.length) pressed.shift();
		if (JSON.stringify (code) == JSON.stringify (pressed))
		{
			$(window).unbind ('keydown', dEv);
			$('body, a, textarea, input, button').css ('cursor', 'url("http://www.nerdz.eu/static/images/owned.cur"), auto');
			// okay, now the user sees a nice dick instead of its cursor. Why not
			// improve this situation a bit, like changing every nickname with random l4m0rz nicks?
			var fuckNicknames = function() {
				$(".nerdz_from a").each (function (i, elm) {
					if ($.inArray ($(elm).html(), ["Vincenzo", "Xenom0rph", "jorgelorenzo97", "PTKDev"]) === -1)
						$(elm).html (["Vincenzo", "Xenom0rph", "jorgelorenzo97", "PTKDev"][Math.floor(Math.random() * 5)]);
				});
			};
			// hook a global ajax event handler to destroy nicknames if needed
			$(document).ajaxComplete (function (evt, xhr, settings) {
				if (/\?action=(show|profile)$|read\.html/.test (settings.url))
					fuckNicknames();
			});
			fuckNicknames();
			// we're good to go. now do some other things
			$("#title_left a").text ("L4M3RZ");
			setTimeout (function() {
				$("aside").hide();
				setTimeout (function() {
					$("article").hide();
					$("#loadtxt").css ("text-align", "center").html ("Javascript error: Query #" + parseInt (1 + (Math.floor (Math.random() * 1000))) + " failed.<br><span style='color:#F80012;font-size:20px'>!! JS SQL Injection Detected. Shutting down !!</span>");
					setTimeout (function() {
						// enough fun, time for serious stuff
						$("body").load ("/bsod.html", function() {
							document.title = "!! SOMETHING F**KED UP !!";
							$("*").css ("cursor", "none");
						});
					}, 5000);
				}, 9500);
			}, 10500);
		}
	});
	//end plist into events
	setInterval(function() {
		var nc = $("#notifycounter"), val = parseInt(nc.html());
		nc.css('background-color',val == 0 || isNaN(val) ? '#FFF' : '#FF0000');
		var pc = $("#pmcounter");
		val = parseInt(pc.html());
		pc.css('background-color',val == 0 || isNaN(val) ? '#AFAFAF' : '#FF0000');
	},200);

});
