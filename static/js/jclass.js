/**
 * N, default JS API for NERDZ - TEMPLATE, JS class
 */
function N() /* THE FATHER of God (class/object/function)*/
{
    this.json = function(){}; /*namespace json */
    this.html = function(){}; /*namespace html */
    this.tmp = "";
    
    this.reloadCaptcha = function()
    {
        var v = $("#captcha");
        if(v.length)
            v.attr("src","/static/images/captcha.php?a"+Math.random()+'b');
    };
    
    this.yt = function(a,vid)
    {
        a.removeClass("yt_frame");
        var iframe;
        switch(a.data("host")) {
          case "youtube":
            iframe = '<iframe style="border:0px;width:560px; height:340px; margin: auto" title="YouTube video" style="width:460px; height:340px" src="http'+('https:' == document.location.protocol ? 's' : '')+'://www.youtube.com/embed/'+vid+'?wmode=opaque"></iframe>';
            break;
          case "vimeo":
            iframe = '<iframe style="margin: auto" src="//player.vimeo.com/video/'+vid+'?badge=0&amp;color=ffffff" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
            break;
          case "dailymotion":
            iframe = '<iframe style="margin: auto" frameborder="0" width="480" height="270" src="//www.dailymotion.com/embed/video/'+vid+'" allowfullscreen></iframe>';
            break;
          case "facebook":
            iframe = '<iframe style="margin: auto" src="https://www.facebook.com/video/embed?video_id='+vid+'" width="540" height="420" frameborder="0"></iframe>';
            break;
          default:
            break;
        }
        a.html('<div style="width:100%; text-align:center"><br />'+iframe+'</div>');
        a.css('cursor','default');
    };
    
    this.vimeoThumbnail = function(img) {
        var video_id = $(img).parent().data("vid");
        $.ajax({
            type:'GET',
            url: 'http://vimeo.com/api/v2/video/' + video_id + '.json',
            jsonp: 'callback',
            dataType: 'jsonp',
            success: function(data){
                img.src = data[0].thumbnail_large;
            }
        });
    };
    
    this.loadTweet = function(img) {
      var id = $(img).data("id");
      $.ajax({
        type: "GET",
        url: "/twitter_embed.php?twit="+id,
        dataType: 'json',
        success: function(json) {
          if(json.errors) {
            img.src="data:image/png;base64,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";
            img.title=json.errors[0].message;
            return false;
          }
          var div = $("<div/>").html(json.html);
          div.find("script").remove();
          div.insertBefore($(img));
          $(img).remove();
          if(!window.__twttrlr) {
            var script = document.createElement( 'script' );
            script.type = 'text/javascript';
            script.src = "https://platform.twitter.com/widgets.js";
            document.body.appendChild(script);
          }
          else
            twttr.widgets.load();
        }
      });
    }
    
    this.imgErr = function(obj) {
      $(obj).attr("src","/static/images/onErrorImg.php"); 
    };
    
    this.imgLoad = function(obj) {
      src = obj.src;
      if(/onErrorImg\.php/i.test(src)) {
        $(obj).prev().remove();
        p = $(obj).parent().removeClass().removeAttr("onclick");
      } else {
        m = (117-$(obj).height())/2;
        if (m>1)
          $(obj).css("margin-top", m);
      }
    };

    /**
     * getStaticData
     * Description: returns the array of static stuff in the header.
     */
    this.getStaticData = function() {
        if (typeof window.Nstatic !== 'object')
            return {};
        return window.Nstatic;
    };

    /**
     * getLangData
     * Description: returns getStaticData().lang if available
     */
    this.getLangData = function() {
        if (typeof window.Nstatic === 'object' && typeof window.Nstatic.lang === 'object')
            return this.getStaticData().lang;
        return {};
    };
}

N = new N();

N.json = function()
{
    this.pm = function(){};
    this.project = function(){};
    this.profile = function(){};
        
    this.post = function(path,param,done,_corsCookies)
    {
        $.ajax({
            type: 'POST',
            url: path,
            data: param,
            dataType: 'json',
            xhrFields: {
                withCredentials: _corsCookies === true ? true : false
            }
        }).done(function(data) { done(data); });
    };
    
    /**
    * User login
    * @parameters: { username, password, setcookie, tok[ ,offline] }
    * offline: if is set don't mark the user as online for this session
    */
    this.login = function(jObj,done)
    {
        var forceSSL = location.protocol !== 'https:' && 
            typeof Nssl !== 'undefined' && Nssl.login === true;
        this.post ((forceSSL ? 'https://' + (Nssl.domain || document.location.host) : '') + '/pages/profile/login.json.php', jObj, function(d) {
            done (d);
        }, true);
    };

    /**
     * Logout user
     * @parameter: { tok }
     */
    this.logout = function(jObj,done)
    {
        this.post('/pages/profile/logout.json.php',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Reset userpassword
     * @parameters: {captcha, email}
     */
    this.resetPassword = function(jObj,done)
    {
        this.post('/pages/reset.json.php',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Register to nerdz
     * @parameters: { name, surname, username, password, captcha, birth_day, birth_year, birth_month, email }
     */
    this.register = function(jObj, done)
    {
        N.json.post('/pages/register.json.php',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Create project
     * @paramters: {name, description, captcha}
     */
    this.createProject = function(jObj,done)
    {
        N.json.post('/pages/project/create.json.php',jObj,function(d) {
            done(d);
        });
    };
};

N.json = new N.json();

N.json.profile = function()
{
    var pp = "/pages/profile/";
    
    this.post = function(path, jObj,done)
    {
        N.json.post(pp + path,jObj,done);
    };
    
    /**
     * New post in profile
     * @Parameters: { message, to }
     */
    this.newPost = function(jObj,done)
    {
        this.post('board.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Get post from profile (to put in a textarea, before editing)
    * (in json namespace is not parsed)
    * @Parameters: { hpid }
    */
    this.getPost = function(jObj,done)
    {
        this.post('board.json.php?action=get',jObj,function(d) {
            done(d);
        });
    };
    
    /** create a nerdz post sharing the content of a url
     * @parameters: {to, comment, url}
     * to: optional, receipt id (default myself)
     * comment: optional, text content to add
     * url: a valid url
     */    
    this.share = function(jObj,done)
    {
        this.post('share.json.php',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Delete post from profile
    * *** you MUST call before delPostConfim({hpid: hpid}), to get a "are you sure?" message and make delete of post possible
    * @Parameters: { hpid }
    */
    this.delPost = function(jObj,done)
    {
        this.post('board.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Make possible to delete a post, and get a message of confirmation
    * @Parameters: { hpid }
    */
    this.delPostConfirm = function(jObj,done)
    {
        this.post('board.json.php?action=delconfirm',jObj,function(d) {
            done(d);
        });
    };
        
    /**
     * edit post in profile
     * @Parameters: { hpid, message }
     */
    this.editPost = function(jObj,done)
    {
        this.post('board.json.php?action=edit',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Add comment on profile post
    * @Parameters: { hpid, message }
    * hpid: hidden post id (post which comment refer to)
    */    
    this.addComment = function(jObj,done)
    {
        this.post('comments.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Delete comment in profile post
    * @parameters: { hcid }
    * hcid: hidden comment id
    */
    this.delComment = function(jObj,done)
    {
        this.post('comments.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
        
    /**
     * Follow the user (id)
     * @parameters: { id }
     */    
    this.follow = function(jObj,done)
    {
        this.post('follow.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Unfollow the user (id)
     * @parameters: {id}
     */    
    this.unfollow = function(jObj,done)
    {
        this.post('follow.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Blacklist the user (id)
     * @parameters: { id,motivation }
     * motivation: a valid motivation (not required)
     */    
    this.blacklist = function(jObj,done)
    {
        this.post('blacklist.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Remove from blacklist the user (id)
     * @parameters: { id }
     */
    this.unblacklist = function(jObj,done)
    {
        this.post('blacklist.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Don't receive notifications from comments of a user, in that post
     * @parameters: {from, hpid}
     * from: fromid (users to "silent" notification)
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.noNotifyFromUserInPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Don't receive notifications from comments of a user, in that post
     * @parameters: {hpid}
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.noNotifyForThisPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Restart to receive notifications from comments of a user, in that post
     * @parameters: {from, hpid}
     * from: fromid (users to "silent" notification)
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.reNotifyFromUserInPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Restart to receive notifications in this post
     * @parameters: {hpid}
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.reNotifyForThisPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Lurk that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.lurkPost = function(jObj,done)
    {
        this.post('lurke.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
     * unlurke that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.unlurkPost = function(jObj,done)
    {
        this.post('lurke.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Bookmark that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.bookmarkPost = function(jObj,done)
    {
        this.post('bookmark.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
     * unbookmark that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.unbookmarkPost = function(jObj,done)
    {
        this.post('bookmark.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * set thumbs for post
     * @parameters;  {hpid, thumb}
     * hpid: hidden post id
     * vote: vote (-1,0,1)
     */
    this.thumbs = function(jObj, done) 
    {
        this.post('thumbs.json.php',jObj, function(d) {
            done(d);
        });
    };
    
    /**
     * set thumbs for comments
     * @parameters;  {hcid, thumb}
     * hpid: hidden comment id
     * vote: vote (-1,0,1)
     */
    this.cthumbs = function(jObj, done) 
    {
        this.post('thumbs.json.php',$.extend(jObj,{comment:true}), function(d) {
            done(d);
        });
    };

};

N.json.profile = new N.json.profile();

N.json.project = function()
{
    var pp = '/pages/project/';
    this.post = function(path, jObj,done)
    {
        N.json.post(pp + path,jObj,done);
    };

    /**
    * New post in project
    * @Parameters: { message, to [, news] }
    * to: project id
    * news: optional. If present: 1 if news 0 else
    */
    this.newPost = function(jObj,done)
    {
        this.post('board.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Get post from project(to put in a textarea, before editing)
    * not parsed
    * @Parameters: { hpid }
    */    
    this.getPost = function(jObj,done)
    {
        this.post('board.json.php?action=get',jObj,function(d) {
            done(d);
        });
    };
    
    /**
    * edit post in project
    * @Parameters: { hpid, message }
    */
    this.editPost = function(jObj,done)
    {
        this.post('board.json.php?action=edit',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Delete post from project
    * *** you MUST call before delPostConfim({hpid: hpid}), to get a "are you sure?" message and make delete of post possible
    * @Parameters: { hpid }
    */    
    this.delPost = function(jObj,done)
    {
        this.post('board.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Make possible to delete a post, and get a message of confirmation
    * @Parameters: { hpid }
    */
    this.delPostConfirm = function(jObj,done)
    {
        this.post('board.json.php?action=delconfirm',jObj,function(d) {
            done(d);
        });
    };
    
    /**
    * Add comment on profile post
    * @Parameters: { hpid, message }
    * hpid: hidden post id (post which comment refer to)
    */
    this.addComment = function(jObj,done)
    {
        this.post('comments.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
    * Delete comment in project post
    * @parameters: { hcid }
    * hcid: hidden comment id
    */    
    this.delComment = function(jObj,done)
    {
        this.post('comments.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Follow the project (id)
     * @parameters: {id }
     */
    this.follow = function(jObj,done)
    {
        this.post('follow.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Unfollow the project (id)
     * @parameters: {id}
     */
    this.unfollow = function(jObj,done)
    {
        this.post('follow.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Don't receive notifications from comments of a user, in that post
     * @parameters: {from, hpid}
     * from: fromid (users to "silent" notification)
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.noNotifyFromUserInPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Don't receive notifications from comments of a user, in that post
     * @parameters: {hpid}
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.noNotifyForThisPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Restart to receive notifications from comments of a user, in that post
     * @parameters: {from, hpid}
     * from: fromid (users to "silent" notification)
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.reNotifyFromUserInPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Restart to receive notifications in this post
     * @parameters: {hpid}
     * hpid: hidden post id (POST, NOT COMMENT)
     */
    this.reNotifyForThisPost = function(jObj,done)
    {
        this.post('nonotify.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Lurk that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.lurkPost = function(jObj,done)
    {
        this.post('lurke.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
     * unlurk that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.unlurkPost = function(jObj,done)
    {
        this.post('lurke.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Bookmark that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.bookmarkPost = function(jObj,done)
    {
        this.post('bookmark.json.php?action=add',jObj,function(d) {
            done(d);
        });
    };

    /**
     * unbookmark that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.unbookmarkPost = function(jObj,done)
    {
        this.post('bookmark.json.php?action=del',jObj,function(d) {
            done(d);
        });
    };

    /**
     * set thumbs for post
     * @parameters;  {hpid, thumb}
     * hpid: hidden post id
     * vote: vote (-1,0,1)
     */
    this.thumbs = function(jObj, done) 
    {
        this.post('thumbs.json.php',jObj, function(d) {
            done(d);
        });
    };

    /**
     * set thumbs for comments
     * @parameters;  {hcid, thumb}
     * hcid: hidden comment id
     * vote: vote (-1,0,1)
     */
    this.cthumbs = function(jObj, done) 
    {
        this.post('thumbs.json.php',$.extend(jObj,{comment:true}), function(d) {
            done(d);
        });
    };
};

N.json.project = new N.json.project();

N.json.pm = function()
{
    var pp = '/pages/pm/';
    
    this.post = function(path, jObj,done)
    {
        N.json.post(pp + path,jObj,done);
    };
    
    /**
     * Send pm to user
     * @parameters: { to, message, subject}
     * to: username recipient
     * message: the message
     */
    this.send = function(jObj,done)
    {
        this.post('send.json.php',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * Delete a conversation
     * @parameters: { to, from}
     * to: toid
     * from: fromid
     */
    this.delConversation = function(jObj,done)
    {
        this.post('delete.json.php',jObj,function(d) {
            done(d);
        });
    };
};

N.json.pm = new N.json.pm();
    
N.html = function()
{
    this.pm = function(){};
    this.profile = function(){};
    this.project = function(){};
    this.search = function(){};
    
    this.eval = function(text)
    {
        var p = document.createElement('p');
        p.innerHTML = text;
        var scripts = p.getElementsByTagName('script');
        var code = '';
        for (var i = 0; i < scripts.length; i++)
        {
            code +=  scripts[i].innerHTML;
        }

        try{ eval(code); } catch(e){}
    };
        
    this.post = function(path,param,done)
    {
        $.ajax({
            type: 'POST',
            url: path,
            data: param,
            dataType: 'html'
        }).done(function(data) { 
                done(data);
                N.reloadCaptcha();
                MathJax.Hub.Queue(['Typeset',MathJax.Hub,'body']);
                N.html.eval(data);
                if (typeof initGist == 'function')
                    initGist();
                if (('PR' in window) && typeof window.PR.prettyPrint == 'function')
                    window.PR.prettyPrint (
                        (typeof N.getStaticData().prettyPrintCallbackName !== 'undefined' &&
                        typeof window[N.getStaticData().prettyPrintCallbackName] === 'function') ?
                            window[N.getStaticData().prettyPrintCallbackName] :
                            undefined
                    );
            });
    };

    /**
     * Gets the HTML list of notifications.
     * Set the 'doNotDelete' param to true if don't wanna reset
     * the counter.
     */
    this.getNotifications = function(done, doNotDelete)
    {
        var datJson = ( typeof doNotDelete !== 'undefined' && doNotDelete === true ) ? { doNotDelete: true } : {};
        this.post('/pages/profile/notify.html.php', datJson, function(d) {
            done(d);
        });
    };

};

N.html = new N.html();

N.html.profile = function()
{
    var pp = '/pages/profile/';
    
    this.post = function(path, jObj,done)
    {
        N.html.post(path,jObj,done);
    };
    
    /**
    * Get html code of comments (as defined in template)
    * @parameters: { hpid, start, num }
    * hpid: hidden post id
    */    
    this.getComments = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=show',jObj,function(d) { 
            done(d);
        });
    };

    /**
    * Get html code of comments (as defined in template)
    * @parameters: { hpid, hcid }
    * hcid: hidden comment id
    * hpid: hidden post id
    */    
    this.getCommentsAfterHcid = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=show',jObj,function(d) { 
            done(d);
        });
    };
    
    /**
     * Return the homepage post list (10 post), starting from post number: lim
     * @parameters lim
     */
    this.getHomePostList = function(lim, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: !lim ? '0' : lim+",10"}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by follwed users (10 post), starting from post number: lim
     * @parameters lim
     */
    this.getFollowedHomePostList = function(lim, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: !lim ? '0' : lim+",10", onlyfollowed: '1'}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by users with lang = lang (2 letters or *), starting from post number: lim
     * @parameters lim, lang
     */
    this.getByLangHomePostList = function(lim, lang, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: !lim ? '0' : lim+",10", lang: lang}, function(d) {
            done(d);
        });
    };

    /**
     * Return lim posts, after posts with id = hpid
     * @parameters lim, hpid
     */
    this.getHomePostListBeforeHpid = function(lim, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: lim ? lim : "10",hpid: hpid}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by follwed users (lim posts), starting from post with id = hpid
     * @parameters lim,hpid
     */
    this.getFollowedHomePostListBeforeHpid = function(lim, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: lim ? lim : "10", onlyfollowed: '1',hpid:hpid}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by users with lang = lang (2 letters or *), starting from post with id= hpid, and get lim posts
     * @parameters lim, lang, hpid
     */
    this.getByLangHomePostListBeforeHpid = function(lim, lang, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=profile',{limit: lim ? lim : "10", lang: lang, hpid:hpid}, function(d) {
            done(d);
        });
    };
    
    /**
     * Get lim posts from profile id
     * @parameters: lim, id
     * id: user id
     * lim: number of posts
     */
    this.getPostList = function(lim, id, done)
    {
        this.post(pp + 'refresh.html.php',{limit: lim, id: id},function(d) {
            done(d);
        });
    };

    /**
     * Get lim posts from profile id with id < hpid
     * @parameters: lim, id
     * id: user id
     * lim: number of posts
     * hpid: hidden post id
     */
    this.getPostListBeforeHpid = function(lim, id, hpid, done)
    {
        this.post(pp + 'refresh.html.php',{limit: lim, id: id, hpid:hpid},function(d) {
            done(d);
        });
    };

    /**
    * Get post from profile (useful to show after edit complete!)
    * parsed
    * @Parameters: { hpid }
    */
    this.getPost = function(jObj,done)
    {
        this.post(pp + 'board.html.php?action=get',jObj,function(d) {
            done(d);
        });
    };
};

N.html.profile = new N.html.profile();

N.html.project = function()
{
    var pp = '/pages/project/';
    
    this.post = function(path, jObj,done)
    {
        N.html.post(path,jObj,done);
    };
    
    /**
    * Get html code of comments (as defined in template)
    * @parameters: { hpid, start, num }
    * hpid: hidden post id
    */
    this.getComments = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=show',jObj,function(d) {
            done(d);
        });
    };
    
    /**
    * Get html code of comments (as defined in template), newest after hcid
    * @parameters: { hpid, hcid }
    * hpid: hidden post id
    * hcid: comment hidden id
    */
    this.getCommentsAfterHcid = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=show',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage post list (10 post), starting from post number: lim
     * @parameters: lim
     */
    this.getHomePostList = function(lim, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: !lim ? '0' : lim+",10"}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by follwed users (10 post), starting from post number: lim
     * @parameters lim
     */
    this.getFollowedHomePostList = function(lim, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: !lim ? '0' : lim+",10", onlyfollowed: '1'}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by users with lang = lang (2 letters or *), starting from post number: lim
     * @parameters lim, lang
     */
    this.getByLangHomePostList = function(lim, lang, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: !lim ? '0' : lim+",10", lang: lang}, function(d) {
            done(d);
        });
    };

    /**
     * Return the project homepage post list (lim posts), starting from post with id = hpid
     * @parameters: lim, hpid
     */
    this.getHomePostListBeforeHpid = function(lim, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: lim ? lim : "10", hpid:hpid}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by follwed users (lim posts), starting from post with id = hpid
     * @parameters lim, hpid
     */
    this.getFollowedHomePostListBeforeHpid = function(lim, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: lim ? lim : "10", onlyfollowed: '1', hpid:hpid}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by users with lang = lang (2 letters or *), starting from post with id=hpid, gets lim posts
     * @parameters lim, lang, hpid
     */
    this.getByLangHomePostListBeforeHpid = function(lim, lang, hpid, done)
    {
        this.post('/pages/home/home.html.php?action=project',{limit: lim ? lim : "10", lang: lang, hpid:hpid}, function(d) {
            done(d);
        });
    };

    /**
    * Get post from profile (useful to show after edit complete!)
    * parsed
    * @Parameters: { hpid }
    */
    this.getPost = function(jObj,done)
    {
        this.post(pp + 'board.html.php?action=get',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Get lim posts from project id
     * @parameters: lim, id
     * id: project id
     * lim: number of posts
     */
    this.getPostList = function(lim, id, done)
    {
        this.post(pp + 'refresh.html.php',{limit: lim, id:id},function(d) {
            done(d);
        });
    };

    /**
     * Get lim posts from project id with id < hpid
     * @parameters: lim, id
     * id: project id
     * lim: number of posts
     * hpid: hidden post id
     */
    this.getPostListBeforeHpid = function(lim, id, hpid, done)
    {
        this.post(pp + 'refresh.html.php',{limit: lim, id:id, hpid:hpid},function(d) {
            done(d);
        });
    };
};

N.html.project = new N.html.project();

N.html.pm = function()
{
    var pp = '/pages/pm/';
    
    this.post = function(path, jObj,done)
    {
        N.html.post(pp + path,jObj,done);
    };
    
    /**
     * get conversation from and to ID
     * @parameters: { from, to, start, num }
     * to: toid
     * from: fromid
     */
    this.getConversation = function(jObj,done)
    {
        this.post('read.html.php?action=conversation',jObj,function(d) {
            done(d);
        });
    };

    /**
     * get conversation after pmid from and to IDs
     * @parameters: { from, to, pmid }
     * to: toid
     * from: fromid
     * pmid: id of last read pm
     */
    this.getConversationAfterPmid = function(jObj,done)
    {
        this.post('read.html.php?action=conversation',jObj,function(d) {
            done(d);
        });
    };
    
    /**
     * get the list of new pms
     */
    this.getNotifications = function(done)
    {
        this.post('notify.html.php',{},function(d) {
            done(d);
        });
    };
    
    /**
     * get inbox
     */
    this.getInbox = function(done)
    {
        this.post('inbox.html.php',{},function(d) {
            done(d);
        });
    };
    
    /**
     * get send form of pm
     */
    this.getForm = function(done)
    {
        this.post('form.html.php',{},function(d) {
            done(d);
        });
    };
};

N.html.pm = new N.html.pm();

N.html.search = function()
{
    var pp = '/pages/search/';
    
    this.post = function(path, jObj,done)
    {
        N.html.post(pp + path,jObj,done);
    };

    /**
     * @Parameters: num, q
     * q:query string
     * num: number of posts
     * returns n <= num posts matching q
     */
    this.globalProfilePosts = function(num, q, done)
    {
        this.post('posts.html.php?action=profile',{q:q, limit: num},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, hpid
     * q:query string
     * num: number of posts
     * hpid: hidden post id
     * returns n <= num posts matching q with id < hpid
     */
    this.globalProfilePostsBeforeHpid = function(num, q, hpid, done)
    {
        this.post('posts.html.php?action=profile',{q: q, hpid: hpid, limit: num},function(d) {
                done(d);
        });
    };

    /**
     * @Parameters: num, q
     * q:query string
     * num: number of posts
     * returns n <= num posts matching q
     */
    this.globalProjectPosts = function(num, q ,done)
    {
        this.post('posts.html.php?action=project',{q:q, limit: num},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, hpid
     * q:query string
     * num: number of posts
     * hpid: hidden post id
     * returns n <= num posts matching q with id < hpid
     */
    this.globalProjectPostsBeforeHpid = function(num, q, hpid, done)
    {
        this.post('posts.html.php?action=project', {q: q, hpid: hpid, limit: num},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, id
     * q:query string
     * id: project id
     * num: number of posts
     * return n <= limit posts matching q on profile id
     */
    this.specificProfilePosts = function(num, q, id, done)
    {
        this.post('posts.html.php?action=profile&specific=1',{q: q, limit: num, id:id},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, id, hpid
     * q:query string
     * id: profile id
     * num: number of posts
     * hpid: hidden post id
     * return n <= limit posts matching q on profile id with id < hpid
    */
    this.specificProfilePostsBeforeHpid = function(num, q, id, hpid, done)
    {
        this.post('posts.html.php?action=profile&specific=1',{q: q, hpid: hpid, limit: num, id:id},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, id
     * q:query string
     * id: project id
     * num: number of posts
     * return n <= limit posts matching q on project id
     */
    this.specificProjectPosts = function(num, q, id,done)
    {
        this.post('posts.html.php?action=project&specific=1',{q: q, limit: num, id:id},function(d) {
            done(d);
        });
    };

    /**
     * @Parameters: num, q, hpi8d, id
     * q:query string
     * id: project id
     * num: number of posts
     * hpid: hidden post id
     * return n <= limit posts matching q on project id with id < hpid
    */
    this.specificPrjectPostsBeforeHpid = function(num, q, id, hpid, done)
    {
        this.post('posts.html.php?action=project&specific=1',{q: q, hpid: hpid, limit: num, id:id},function(d) {
            done(d);
        });
    };
};

N.html.search = new N.html.search();

/*
 * Azioni di compiere in ogni template
*/
$(document).ready(function() {
    var logged = true;
    /* Se esiste img#captcha, gli da la corretta path per l'immagine */
    N.reloadCaptcha(); 
    if(typeof initGist == 'function') {
            initGist();
    }
    /*Aggiorna timestamp per status online, ogni minuto */
    var timeupdate = function() {
        if(logged) {
            N.json.post('/pages/profile/online.json.php',{},function(d) {
                if(d.status == 'error') {
                logged = false;
              }
            });
        }
    };
    timeupdate();
    setInterval(timeupdate, 60000);
    
    /*Aggiorna #pmcounter (se esiste) ogni 16 secondi se ci sono nuovi pm */
    var pmcount = function() {
        var v = $("#pmcounter");
        if(v.length) {
            N.json.post('/pages/pm/notify.json.php',{}, function(obj) {
                v.html(obj.status == 'ok' ? obj.message : '0');
            });
        }
    };
    pmcount();
    setInterval(pmcount, 16000);
    
    /*Aggiorna #notifycounter (se esiste) ogni 12 secondi se ci sono nuove notifiche */
    var notifycount = function() {
        if(logged) {
            var v = $("#notifycounter");
            if(v.length) {
                N.json.post('/pages/profile/notify.json.php',{}, function(obj) {
                    v.html(obj.message);
                    if(obj.status == 'error') {
                        logged = false;
                    }

                });
            }
        }
    };
    notifycount();
    setInterval(notifycount, 12000);
    
    var pval = 0, nval = 0;
    var updateTitle = function() {
        var s = '', n = $("#notifycounter"), p = $("#pmcounter"), go = false, val = 0;
        if(n.length) {
            val = parseInt(n.text());
            if(!isNaN(val)) {
                if(val !== 0 && val != nval) {
                    document.title = document.title.replace(/\([0-9]+\)/g,'');
                    s+="(" + val + ") ";
                    go = true;
                    nval = val;
                }
                else if(val === 0) {
                    document.title = document.title.replace(/\([0-9]+\)/g,'');
                }
            }
        }
        
        if(p.length) {
            val = parseInt(p.text());
            if(!isNaN(val)) {
                if(val !== 0 && val != pval ) {
                    document.title = document.title.replace(/\[[0-9]+\]/g,'');
                    s+="["+ val + "] ";
                    go = true;
                    pval = val;
                }
                else if(val === 0) {
                    document.title = document.title.replace(/\[[0-9]+\]/g,'');
                }
            }
        }
        
        if(go) {
            document.title = s + document.title;
            go = false;
        }
    };
    
    setInterval(updateTitle,1000);
});
