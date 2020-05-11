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

    this.yt = function(a, vid)
    {
        a.removeClass ("yt_frame");
        var iframe;
        switch (a.attr ("data-host"))
        {
            case "youtube":
                iframe = '<iframe style="border:0px;width:560px; height:340px; margin: auto" title="YouTube video" style="width:460px; height:340px" src="https://www.youtube.com/embed/'+vid+'?wmode=opaque" allowfullscreen></iframe>';
            break;
            case "vimeo":
                iframe = '<iframe style="margin: auto" src="https://player.vimeo.com/video/'+vid+'?badge=0&amp;color=ffffff" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
            break;
            case "dailymotion":
                iframe = '<iframe style="margin: auto" frameborder="0" width="480" height="270" src="https://www.dailymotion.com/embed/video/'+vid+'" allowfullscreen></iframe>';
            break;
            case "facebook":
                iframe = '<iframe style="margin: auto" src="https://www.facebook.com/video/embed?video_id='+vid+'" frameborder="0"></iframe>';
            break;
            case "nerdzcrush":
                iframe = '<div class="nerdzcrush" data-media="'+vid+'#noautoplay,noloop"></div>';
            break;
            case "imgur":
                iframe = '<video src="https://i.imgur.com/'+vid+'.webm" controls autoplay></video>';
            break;
        }
        a.html ('<div style="width:100%; text-align:center"><br />'+iframe+'</div>');
        a.css ('cursor','default');
        if(a.attr ("data-host") == "nerdzcrush") {
            NERDZCrush.renderAll();
        }
    };

    this.vimeoThumbnail = function(img) {
        var video_id = $(img).parent().attr ("data-vid");
        $.ajax ({
            type:     "GET",
            url:      "https://vimeo.com/api/v2/video/" + video_id + ".json",
            jsonp:    "callback",
            dataType: "jsonp",
            success:  function (data) {
                img.src = data[0].thumbnail_large;
                img.onload = null;
            }
        });
    };

    this.facebookThumbnail = function(img) {
        var video_id = $(img).parent().attr ("data-vid");
        img.src = "https://graph.facebook.com/"+video_id+"/picture";
        img.onload = null;
    };

    this.loadTweet = function (img) {
        var $img = $(img);
        if (!("processedTweets" in this)) this.processedTweets = [];
        // FIXME: this UUID stuff is used to circumvent a stupid (and unknown)
        // bug in something which calls the .onload() handler more than once.
        // Setting a flag like .attr ("data-processed", true) does not work, and for
        // some reason the object differs in the second onload call. If someone
        // is able to fix this, then please do it.
        // NOTE: using the twitter ID as a UUID is not a viable choice, because
        // multiple [twitter] tags with the same ID will fail.
        if ($.inArray ($img.attr ("data-uuid"), this.processedTweets) !== -1) return;
        this.processedTweets.push ($img.attr ("data-uuid"));
        var id = $img.attr ("data-id"), failHandler = function (msg) {
            img.onload = null;
            $img.attr ({
                src:   "/static/images/twitter_fail.png",
                title: msg
            });
        };
        // Potential bug fixer: you may use the following line to check if you
        // have fixed the issue.
        //console.log ("Processing tweet: " + id);
        // sanitize the tweet id
        if (id.indexOf ("/") !== -1)
            id = id.split ("/").pop();
        if (!/^\d+$/.test (id))
            return failHandler ("Invalid ID");

        if (!window.__twttrlr) {
            document.body.appendChild ($(document.createElement ("script")).attr ({
                type: "application/javascript",
                src:  "https://platform.twitter.com/widgets.js",
                async: true
            })[0]);
            window.__twttrlr = 1;
        }

        $.ajax ({
            type:     "POST",
            url:      "/embed_tweet.php",
            dataType: "json",
            data:     { id: id },
            cache:    true,
            success:  function (json) {
                if (json.errors)
                    return failHandler (json.errors[0].message);
                var $div = $(document.createElement ("div")).html (json.html);
                $div.insertBefore ($img);
                $img.remove();
                if (typeof twttr !== "undefined")
                    twttr.widgets.load();
            }
        });
    };

    this.imgErr = function (obj) {
        $(obj).unbind("error").attr("src", "/static/images/onErrorImg.php");
    };

    this.imgLoad = function(obj) {
        var $obj = $(obj);
        if (/onErrorImg\.php/i.test ($obj.attr ("src")))
        {
            $obj.prev().remove();
            $obj.parent().removeClass()[0].onclick = null;
        }
        else
        {
            var dad = $obj.parent();
            // "boxed" image
            if (dad.hasClass("img_frame")) {
                var post = dad.parent();
                var w = $obj[0].naturalWidth, h = $obj[0].naturalHeight;

                if (w < post.width() && h < 512) {
                    $obj.prev().remove();
                    dad[0].onclick = null;
                    dad.removeClass("img_frame").addClass("img_frame_extended");
                    $obj.css('max-width', '100%');
                } else {
                    // center into the box
                    var m = (117 - $obj.height()) / 2;
                    if (m > 1) {
                        $obj.css ("margin-top", m);
                    }
                }
            }
        }
    };

    /**
     * getStaticData
     * Description: returns the array of static stuff in the header.
     */
    this.getStaticData = function() {
        if (typeof window.N.static !== 'object')
            return {};
        return window.N.static;
    };

    /**
     * getLangData
     * Description: returns getStaticData().lang if available
     */
    this.getLangData = function() {
        if (typeof window.N.static === 'object' && typeof window.N.static.lang === 'object')
            return this.getStaticData().lang;
        return {};
    };

    /**
     * isLoggedIn
     * Description: returns true if the user is logged in.
     */
    this.isLoggedIn = function() {
        return $("#pmcounter").length > 0;
    };

    /**
     * getTemplateVars
     * Description: returns the template variables
     */
    this.getTemplateVars = function() {
        return N.tplVars;
    };
}

var N = new N();

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
    * @parameters: { username, password, setcookie, tok[ ,offline][, query_string] }
    * offline: if is set don't mark the user as online for this session
    * query_string: to append an additional query string as get parameters
    */
    this.login = function(jObj,done)
    {
        var qs = /query_string=([^&|]+)/g.exec(jObj);
        // OAuth2 parameter check
        if(qs !== null && qs[1].includes("client_id") && qs[1].includes("response_type")) {
            qs = '?' + decodeURIComponent(qs[1]) + '&redirect=' + encodeURIComponent('/oauth2/authorize.php')
        } else {
            qs = '';
        }
        this.post ('/pages/profile/login.json.php' + qs, jObj, function(d) {
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
     * @parameters: {captcha, email} or {reset-token, new-password}
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

    /**
     * Create a new application
     * @paramters: {name, description, redirect_uri, scope (array), captcha}
     */
    this.createApplication = function(jObj,done)
    {
        N.json.post('/pages/application/create.json.php',jObj,function(d) {
            done(d);
        });
    };

    /**
     * Set template variables
     * @parameters: {vars: designer defined object, tok}
     */
    this.setTemplateVars = function(jObj, done)
    {
        N.json.post('/pages/preferences/themes.html.json.php?action=vars',{"vars": jObj.vars, "tok": jObj.tok},function(d) {
            N.tplVars = jObj.vars;
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

    /**
    * Get comment from profile post (to put in a textarea, before editing)
    * (in json namespace is not parsed)
    * @Parameters: { hcid }
    */
    this.getComment = function(jObj,done)
    {
        this.post('comments.json.php?action=get',jObj,function(d) {
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
    * edit profile post commment
    * (in json namespace is not parsed)
    * @Parameters: { hcid, message }
    */
    this.editComment = function(jObj,done)
    {
        this.post('comments.json.php?action=edit',jObj,function(d) {
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
     * close that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.closePost = function(jObj,done)
    {
        this.post('post.json.php?action=close',jObj,function(d) {
            done(d);
        });
    };

    /**
     * open that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.openPost = function(jObj,done)
    {
        this.post('post.json.php?action=open',jObj,function(d) {
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

    /**
     * get revison for post
     * @parameters; {hpid, revNo}
     * hpid: hidden post id
     * revNo: revision number
     */
    this.getRevision = function(jObj, done)
    {
        this.post('revisions.json.php', jObj, function(d) {
            done(d);
        });
    };

    /**
     * get revison for comment
     * @parameters; {hcid, revNo}
     * hcid: hidden comment id
     * revNo: revision number
     */
    this.getCommentRevision = function(jObj, done)
    {
        this.post('revisions.json.php', $.extend(jObj, {comment:true}), function(d) {
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
    * @Parameters: { message, to [, news, issue] }
    * to: project id
    * news:  optional. If present: 1 if news 0 otherwise
    * issue: optional. Open issue on GitHub. If present: 1 if issue 0 otherwise
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
    * Get comment from profile post (to put in a textarea, before editing)
    * (in json namespace is not parsed)
    * @Parameters: { hcid }
    */
    this.getComment = function(jObj,done)
    {
        this.post('comments.json.php?action=get',jObj,function(d) {
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
    * Get comment from profile post (to put in a textarea, before editing)
    * (in json namespace is not parsed)
    * @Parameters: { hcid, message }
    */
    this.editComment = function(jObj,done)
    {
        this.post('comments.json.php?action=edit',jObj,function(d) {
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
     * close that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.closePost = function(jObj,done)
    {
        this.post('post.json.php?action=close',jObj,function(d) {
            done(d);
        });
    };

    /**
     * open that post
     * @parameters: {hpid}
     * hpid: hidden post id
     */
    this.openPost = function(jObj,done)
    {
        this.post('post.json.php?action=open',jObj,function(d) {
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

    /**
     * get revison for post
     * @parameters; {hpid, revNo}
     * hpid: hidden post id
     * revNo: revision number
     */
    this.getRevision = function(jObj, done)
    {
        this.post('revisions.json.php',jObj, function(d) {
            done(d);
        });
    };

    /**
     * get revison for comment
     * @parameters; {hcid, revNo}
     * hcid: hidden comment id
     * revNo: revision number
     */
    this.getCommentRevision = function(jObj, done)
    {
        this.post('revisions.json.php',$.extend(jObj, {comment:true}), function(d) {
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
        // trigger the notification change event in case someone clicks on the counter
        if (!doNotDelete)
            $(document).trigger ('nerdz_internal:set_count', [ 'notification', 0 ]);
    };

};

N.html = new N.html();

N.html.home = function() {
    var pp = '/pages/home/';
 
    this.post = function(path, jObj,done)
    {
        N.html.post(path,jObj,done);
    };

    /**
     * Return the homepage with only posts made by follwed users (10 post), starting from post number: lim
     * @parameters lim
     */
    this.getFollowedPostList = function(lim, done) {
        this.post(pp + 'home.html.php', {limit: !lim ? '0' : lim+",10", onlyfollowed: '1'}, function(d) {
            done(d);
        });
    };

    /**
     * Return the homepage with only posts made by follwed users (lim posts), starting from post with id = hpid
     * @parameters lim,hpid, done
     * hpid = {hpid, type = hpid type [project or profile]}
     */
    this.getFollowedPostListBeforeHpid = function(lim, hpid, type, done)
    {
        this.post(pp + 'home.html.php',{limit: lim ? lim : "10", onlyfollowed: '1', hpid: hpid, type: type}, function(d) {
            done(d);
        });
    };
};

N.html.home = new N.html.home();

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

    /**
    * Get comment from hcid (useful to show after edit complete!)
    * parsed
    * @Parameters: { hcid }
    */
    this.getComment = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=get',jObj,function(d) {
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
    * Get post from project (useful to show after edit complete!)
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
    * Get comment from hcid (useful to show after edit complete!)
    * parsed
    * @Parameters: { hcid }
    */
    this.getComment = function(jObj,done)
    {
        this.post(pp + 'comments.html.php?action=get',jObj,function(d) {
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
        // trigger the pm count change if a conversation is being fetched
        if (jObj.start === 0)
            $(document).trigger ('nerdz_internal:set_count', [ 'pm', 0 ]);
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
     * get the list of new Pmss
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
    this.globalPosts = function(num, q, done) {
        this.post('posts.html.php',{q:q, limit: num},function(d) {
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
    this.globalPostsBeforeHpid = function(num, q, hpid, done)
    {
        this.post('posts.html.php',{q: q, hpid: hpid, limit: num},function(d) {
                done(d);
        });
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
     * @Parameters: num, q, hpid, id
     * q:query string
     * id: project id
     * num: number of posts
     * hpid: hidden post id
     * return n <= limit posts matching q on project id with id < hpid
    */
    this.specificProjectPostsBeforeHpid = function(num, q, id, hpid, done)
    {
        this.post('posts.html.php?action=project&specific=1',{q: q, hpid: hpid, limit: num, id:id},function(d) {
            done(d);
        });
    };
};

N.html.search = new N.html.search();

$(document).ready(function() {
    N.reloadCaptcha();
    if (typeof initGist == 'function')
            initGist();

    // if we're not logged in, we are done.
    if (!N.isLoggedIn())
        return;

    // useful stuff for the notifications and pms
    var lastCounters = { pm: 0, notification: 0 },
        handleUpdate = function (context, value) {
            var parsed = parseInt (value, 10);
            if (!isNaN (value))
            {
                if (lastCounters[context] !== parsed)
                    $(document).trigger ('nerdz:' + context, [ parsed, lastCounters[context] ]);
                lastCounters[context] = parsed;
            }
        };

    // register the DOM event handlers that will handle new notifications and PMs
    var updateTitle = function (schar, echar, counter, prev) {
        var target = schar + counter + echar + ' ';
        if (counter > 0 && prev === 0)
            // give priority to the PMs
            if (schar === '(' && lastCounters.pm !== 0)
            {
                var pos = document.title.indexOf (']') + 1; // + 1 gets the space too
                document.title = document.title.substr (0, pos + 1) + target + document.title.substr (pos);
            }
            else
                document.title = target + document.title;
        else
            document.title = document.title.replace (
                new RegExp ('\\' + schar + '\\d+\\' + echar + '\\s'),
                counter > 0 ? target : ''
            );
    };

    $(document).on ('nerdz:notification.nerdz', function (e, counter, prev) {
        updateTitle ('(', ')', counter, prev);
    }).on ('nerdz:pm.nerdz', function (e, counter, prev) {
        updateTitle ('[', ']', counter, prev);
    }).on ('nerdz_internal:set_count.nerdz', function (e, context, target) {
        // internal event, should not be used outside of this class.
        handleUpdate (context, target);
    });


    var $pmCounter = $('#pmcounter'), $notifyCounter = $('#notifycounter');

    // If the browser supports SSE
    /* disabled. php-fpm locks everything.
     * I really need to enable this thing only when an API will be ready
     * and a server will be created for this purpose
     *
    if(typeof(EventSource) !== "undefined") { /
        var notificationSource = new EventSource("/pages/profile/notifyEvent.json.php");
        notificationSource.addEventListener("pm", function(e) {
            var obj = JSON.parse(e.data);
            console.log(obj);
            var sval = obj.status === 'ok' ? obj.message : '0';
            $pmCounter.html (sval);
            handleUpdate ('pm', sval);
        });

        notificationSource.addEventListener("notification", function(e) {
            var obj = JSON.parse(e.data);
            console.log(obj);
            $notifyCounter.html (obj.message);
            handleUpdate ('notification', obj.message);
        });

    } else { // use old legacy polling
    */
        // runs every 60 seconds, sets the user online
        var updateOnlineStatus = function() {
            N.json.post ('/pages/profile/online.json.php', {}, function(){});
        };
        updateOnlineStatus();
        setInterval (updateOnlineStatus, 60000);

        // runs every 16 seconds, updates the PM counter
        var updatePmCounter = function() {
                N.json.post ('/pages/pm/notify.json.php', {}, function (obj) {
                    var sval = obj.status === 'ok' ? obj.message : '0';
                    $pmCounter.html (sval);
                    handleUpdate ('pm', sval);
                });
            };
        updatePmCounter();
        setInterval (updatePmCounter, 16000);

        // runs every 12 seconds, updates the notifications counter
        var updateNotifyCounter = function() {
                N.json.post ('/pages/profile/notify.json.php', {}, function (obj) {
                    $notifyCounter.html (obj.message);
                    handleUpdate ('notification', obj.message);
                });
            };
        updateNotifyCounter();
        setInterval (updateNotifyCounter, 12000);
    //} // else of if SSE
});
