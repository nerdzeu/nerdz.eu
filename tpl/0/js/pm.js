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
			last = mess.last();
			console.log(last.data('pmid'));
			pmid = last.data('pmid');
		}

		N.json.pm.send({ to: $("#to").val(), message: $("#message").val() },function(d) {
				$('#res').html(d.message);
				if(d.status == 'ok') {
					$("#message").val('');
					if(pmid) {
						N.html.pm.getConversationAfterPmid({ from: c_from, to: c_to, pmid: pmid },function(d) {
							var tmp = $(document.createElement('div'));
							tmp.html(d);

							var recv = tmp.find(pattern);
							console.log(recv.eq(0));
							
							if(recv.length == 1 && recv.eq(0).data('pmid') == pmid) {
								last.remove();
							}
						  
							$("#convfrm").before(d);
						});
					}
					else {
						N.html.pm.getConversation({ from: c_from, to: c_to},function(data) {
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
		N.html.pm.getConversation({ from: c_from, to: c_to },function(data) {
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

	setTimeout(function() {
		if(window.location.hash == '#new') {
			window.location.hash = '';
			newpm = true;
			$("#inbox").click();
		}
	},500);

});
