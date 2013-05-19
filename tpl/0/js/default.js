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
		var hpid = $(this).data('hpid');
		var me = $(this);
		var refto = $('#commentlist' + $(this).data('hpid'));
		var error = me.find('.error').eq(0);

		/* se ci sono altri commenti, prendo quelli successivi. Altriment uso getCommetns */
		var pattern = "div[id^='c']";
		var comments = refto.find(pattern);
		var hcid = 0;
		var last = null;
		if(comments.length)
		{
			last = comments.last();
			hcid = last.data('hcid');
		}

		var handleAfter = function(d) {
			var form = refto.find("form.frmcomment").eq(0);

			var tmp = $(document.createElement('div'));
			tmp.html(d);
			var recv = tmp.find(pattern);
			if(recv.length == 1 && recv.eq(0).data('hcid') == hcid) {
				last.remove();
			}

			form.parent().before(d);
			form.find('textarea').val('');
			error.html('');
		};

		error.html(loading);
		if(plist.data('type') == 'profile') {
			N.json.profile.addComment({ hpid: hpid,  message: $(this).find('textarea').eq(0).val() },function(d) {
				if(d.status == 'ok')
				{
					if(hcid && last) {
						N.html.profile.getCommentsAfterHcid( { hpid: hpid, hcid:hcid },handleAfter);
					}
					else
					{
						N.html.profile.getComments( { hpid: hpid },function(d) {
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
		}
		else
		{
			N.json.project.addComment({ hpid: hpid,  message: $(this).find('textarea').eq(0).val() },function(d) {
				if(d.status == 'ok')
				{
					if(hcid) {
						N.html.project.getCommentsAfterHcid( { hpid: hpid, hcid:hcid },handleAfter);
					}
					else {
						N.html.project.getComments( { hpid: hpid },function(d) {
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
		}

	});

	plist.on('click',".showcomments",function() {
		var refto = $('#' + $(this).data('refto'));
		if(refto.html() == '')
		{
			refto.html(loading+'...');
			
			if(plist.data('type') == 'profile') {
				N.html.profile.getComments( { hpid: $(this).data('hpid')},function(d) {
					refto.html(d);
					if(window.location.hash == '#last') { //notifiche
						refto.find(".frmcomment textarea[name=message]").focus();
					}
				});
			}
			else
			{
				N.html.project.getComments( { hpid: $(this).data('hpid') },function(d) {
					refto.html(d);
					if(window.location.hash == '#last') { //notifiche
						refto.find(".frmcomment textarea[name=message]").focus();
					}
				});
			}
		}
		else
		{
			refto.html('');
		}
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

	//end plist into events
	setInterval(function() {
		var nc = $("#notifycounter"), val = parseInt(nc.html());
		nc.css('background-color',val == 0 || isNaN(val) ? '#FFF' : '#FF0000');
		var pc = $("#pmcounter");
		val = parseInt(pc.html());
		pc.css('background-color',val == 0 || isNaN(val) ? '#AFAFAF' : '#FF0000');
	},200);

});
