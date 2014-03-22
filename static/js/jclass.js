/**
 * N, default JS API for NERDZ - TEMPLATE, JS class
 */
function N() /* THE FATHER of God (class/object/function)*/
{
    this.json = function(){}; /*namespace json */
    this.html = function(){}; /*namespace html */
    this.tmp;
    
    this.reloadCaptcha = function()
    {
        var v = $("#captcha");
        if(v.length)
            v.attr("src","/static/images/captcha.php?a"+Math.random()+'b');
    };
    
    this.yt = function(a,vid)
    {
        a.removeClass("yt_frame");
        a.html('<div style="width:80%; margin: auto;text-align:center"><br /><iframe style="border:0px;width:560px; height:340px" title="YouTube video" style="width:460px; height:340px" src="http'+('https:' == document.location.protocol ? 's' : '')+'://www.youtube.com/embed/'+vid+'?wmode=opaque"></iframe></div>');
        a.css('cursor','default');
    };
    
    this.imgErr = function(obj) {
      $(obj).attr("src","/static/images/onErrorImg.php"); 
    }
    
    this.imgLoad = function(obj) {
      src = obj.src;
      if(/onErrorImg\.php/i.test(src)) {
        $(obj).prev().remove();
        p = $(obj).parent().removeClass().removeAttr("onclick");
      } else {
        m = (117-$(obj).height())/2;
        if (m>1)
          $(obj).css("margin-top", m)
      }
    }

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
    }
};

N = new N();

N.json = function()
{
    this.pm = function(){};
    this.project = function(){};
    this.profile = function(){};
        
    this.post = function(path,param,done)
    {
        $.ajax({
            type: 'POST',
            url: path,
            data: param,
            dataType: 'json'
        }).done(function(data) { done(data); });
    };
    
    /**
    * User login
    * @parameter: { username, password, setcookie, tok[ ,offline] }
    * offline: if is set don't mark the user as online for this session
    */
    this.login = function(jObj,done)
    {
        this.post('/pages/profile/login.json.php',jObj,function(d) {
            done(d);
        });
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
                if (typeof window.PR.prettyPrint == 'function')
                    window.PR.prettyPrint (
                        (typeof N.getStaticData().prettyPrintCallbackName !== 'undefined' &&
                        typeof window[N.getStaticData().prettyPrintCallbackName] === 'function')
                            ? window[N.getStaticData().prettyPrintCallbackName]
                            : undefined
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
        var s = '', n = $("#notifycounter"), p = $("#pmcounter"), go = false;
        if(n.length) {
            var val = parseInt(n.text());
            if(!isNaN(val)) {
                if(val != 0 && val != nval) {
                    document.title = document.title.replace(/\([0-9]+\)/g,'');
                    s+="(" + val + ") ";
                    go = true;
                    nval = val;
                }
                else if(val == 0) {
                    document.title = document.title.replace(/\([0-9]+\)/g,'');
                }
            }
        }
        
        if(p.length) {
            var val = parseInt(p.text());
            if(!isNaN(val)) {
                if(val != 0 && val != pval ) {
                    document.title = document.title.replace(/\[[0-9]+\]/g,'');
                    s+="["+ val + "] ";
                    go = true;
                    pval = val;
                }
                else if(val == 0) {
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
