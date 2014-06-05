<?php
/*
 * Classe per la gestione dei posts
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class messages extends phpCore
{
    // regular expressions used to parse the [video] bbcode
    const YOUTUBE_REGEXP  = '#^https?://(?:www\.)?(?:youtube\.com/watch(?:\?v=|\?.+?&v=)|youtu\.be/)([a-z0-9_-]+)#i';
    const VIMEO_REGEXP    = '#^https?://(?:www\.)?vimeo\.com.+?(\d+)$#i';
    const DMOTION_REGEXP  = '#^https?://(?:www\.)?(?:dai\.ly/|dailymotion\.com/(?:.+?video=|(?:video|hub)/))([a-z0-9]+)#i';
    const FACEBOOK_REGEXP = '#^https?://(?:www\.)?facebook\.com/photo\.php(?:\?v=|\?.+?&v=)(\d+)#i';
    public function __construct()
    {
        parent::__construct();
    }

    public function getCodes($str)
    {
        $epos = $key = $i = $codecounter = 0;
        $codes = $start = $end = $ret = array();
        $ncod = 1;

        $zzz = strtolower($str);

        $start[$key] = strpos($zzz,'[code=',0);
        $end[$key] = strpos($zzz,'[/code]',0);

        while((false !== $start[$key]) && (false !== $end[$key]))
        {
            ++$key;
            $start[$key] = strpos($zzz,'[code=',$end[$key-1]+6);
            $end[$key] = strpos($zzz,'[/code]',$end[$key-1]+7);
        }
        while($key-->0)
        {
            $codes[] = substr($str,$start[$i]+6,$end[$i]-$start[$i]-6);
            ++$i;
        }
        $epos = $i;

        for($i=0;$i<$epos;++$i)
        {
            for($x=0;$x<30;++$x)
                if(isset($codes[$i][$x]) && $codes[$i][$x] == ']')
                {
                    $lang = substr($codes[$i],0,$x);
                    $code = substr($codes[$i],$x+1);
                    break;
                }
            if($x<30 && isset($code[1]))
            {
                $ret[$codecounter]['lang'] = $lang;
                $ret[$codecounter]['code'] = $code;
                ++$codecounter;
            }
        }
        return $ret;
    }


    private function parseCode($str,$type = NULL,$pid = NULL,$id = NULL)
    {
        $codes = $this->getCodes($str);

        $i = 1;
        foreach($codes as $code)
        {
            $totalcode = $code['code'];
            $lang = $code['lang'];
            $codeurl = '';
            
            if($pid && $id)
            {
                if(isset($type))
                {
                    $codeurl = '/getcode.php?';
                    if($type == 'g') //gid, group, project
                        $codeurl.='g';
                    elseif($type == 'pc') //pcid, ppstccomment
                        $codeurl.='pc';
                    elseif($type == 'gc')//gcid group comment
                        $codeurl.='gc';
                    //else il nulla, id, profile
                    $codeurl.="id={$id}&amp;".(in_array($type,array('pc','gc')) ? '' : "pid={$pid}&amp;")."ncode={$i}";
                }
            }
            else
                $pid = $id = 0;
            
            $str = str_ireplace("[code={$lang}]{$totalcode}[/code]",
                               '<div class="nerdz-code-wrapper">
                                    <div class="nerdz-code-title">'.$lang.':</div><pre class="prettyprint lang-' . $lang . '" style="border:0px; overflow-x:auto; word-wrap: normal">'.str_replace("\t",'&#09;',$totalcode).'</pre>'.
                                        (
                                         empty($codeurl) ? '' :
                                         '<a href="'.$codeurl.'" onclick="window.open(this.href); return false">'.parent::lang('TEXT_VERSION').'</a>'
                                        ).'</div>',
                                $str);
            ++$i;
        }
        return $str;
    }

    public static function imgValidUrl($url, $domain, $sslEnabled)
    {
        $url = strip_tags(trim($url));
        if (!phpCore::isValidURL($url))
            return $domain.'/static/images/invalidImgUrl.php';

        if($sslEnabled) {
            // valid ssl url
            if(preg_match('#^https://#i',$url))
                return strip_tags($url);

            // imgur without ssl
            if(preg_match("#^http://(www\.)?(i\.)?imgur\.com/[a-z0-9]+\..{3}$#i",$url)) {
                return preg_replace_callback("#^http://(?:www\.)?(?:i\.)?imgur\.com/([a-z0-9]+\..{3})$#i", function($matches) {
                    return 'https://i.imgur.com/'.$matches[1];
                },$url);
            }

            // url hosted on a non ssl host - use camo or our trusted proxy
            return CAMO_KEY == '' ?
                'https://i0.wp.com/' . preg_replace ('#^http://|^ftp://#i', '', strip_tags($url)) :
                $domain.'/secure/image/'.hash_hmac('sha1', $url, CAMO_KEY).'?url='.urlencode($url);
        }
        return strip_tags($url);
    }

    public function bbcode($str,$truncate = null, $type = NULL,$pid = NULL,$id = NULL)
    {
        $str = str_replace("\n",'<br />',$str);
        //evitare il parsing del bbcode nel tag code
        $codes = $this->getCodes($str);
        $index = 0;
        foreach($codes as $code)
        {
            $totalcode = $code['code'];
            $lang = $code['lang'];
            $str = str_ireplace("[code={$lang}]{$totalcode}[/code]",">>>{$index}<<<",$str);
            ++$index;
        }

        $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        $domain = $ssl ? 'https://'.SITE_HOST : STATIC_DOMAIN;

        $validURL = function($m) {
            $m[1] = trim($m[1]);
            if(!phpCore::isValidURL($m[1]))
            {
                $m[1] = 'www.'.$m[1];
                if(!phpCore::isValidURL($m[1]))
                    return 'http://www.nerdz.eu';
            }
            $url = preg_match('#^(http(s)?:\/\/)|(ftp:\/\/)#im',$m[1]) ? $m[1] : 'http://'.$m[1];
            return isset($m[2]) ? '<a href="'.messages::stripTags($url).'" onclick="window.open(this.href); return false">'.$m[2].'</a>' : '<a href="'.messages::stripTags($url).'" onclick="window.open(this.href); return false">'.$m[1].'</a>';
        };

        $str = preg_replace_callback('#\[url=&quot;(.+?)&quot;\](.+?)\[/url\]#i',function($m) use ($validURL) {
                   return $validURL($m);
            },$str);
        $str = preg_replace_callback('#\[url=(.+?)\](.+?)\[/url\]#i',function($m) use ($validURL) {
                return $validURL($m);
            },$str);
        $str = preg_replace_callback('#\[url\](.+?)\[/url\]#i',function($m) use ($validURL) {
                return $validURL($m);
            },$str);

        $str = preg_replace('#\[i\](.+?)\[/i\]#i','<span style="font-style:italic">$1</span>',$str);
        $str = preg_replace('#\[cur\](.+?)\[/cur\]#i','<span style="font-style:italic">$1</span>',$str);
        $str = preg_replace('#\[gist\]([0-9a-z]+)\[/gist\]#i','<div class="gistLoad" data-id="$1" id="gist-$1">'.parent::lang('LOADING').'...</div>',$str);
        $str = preg_replace('#\[b\](.+?)\[/b\]#i','<span style="font-weight:bold">$1</span>',$str);
        $str = preg_replace('#\[del\](.+?)\[/del\]#i','<del>$1</del>',$str);
        $str = preg_replace('#\[u\](.+?)\[/u\]#i','<u>$1</u>',$str);
        $str = preg_replace('#\[hr\]#i','<hr style="clear:both" />',$str);
        $str = preg_replace('#\[small\](.+?)\[/small\]#i','<span style="font-size:7pt">$1</span>',$str);
        $str = preg_replace('#\[big\](.+?)\[/big\]#i','<span style="font-size:14pt">$1</span>',$str);
        $str = preg_replace('#\[wat\]#i','<span style="font-size:22pt">WAT</span>',$str); //easter egg [never change]

        $str = preg_replace_callback('#\[user\](.+?)\[/user\]#i',function($m) {
                return '<a href="/'.phpCore::userLink($m[1])."\">{$m[1]}</a>";
                },$str);
        $str = preg_replace_callback('#\[project\](.+?)\[/project\]#i',function($m) {
                return '<a href="/'.phpCore::projectLink($m[1])."\">{$m[1]}</a>";
                },$str);
        $str = preg_replace_callback('#\[wiki=([a-z]{2})\](.+?)\[/wiki\]#i',function($m) {
                return '<a href="http://'.$m[1].'.wikipedia.org/wiki/'.urlencode(str_replace(' ','_',html_entity_decode($m[2],ENT_QUOTES,'UTF-8')))."\" onclick=\"window.open(this.href); return false\">{$m[2]} @Wikipedia - {$m[1]}</a>";
                },$str);
        $str = preg_replace_callback("#(\[math\]|\[m\])(.+?)(\[/math\]|\[/m\])#i",function($m) {
                return $m[1].strip_tags($m[2]).$m[3];
                },$str);

        $str = preg_replace_callback('#\[list\](.+?)\[\/list\]#i',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[1]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ul>';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ul>';

                return $ret;
                },$str,20); //ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#i', function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[2]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol type="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                },$str,10); //ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#i',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[2]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
                },$str,10);//ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#i',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[3]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[1].'" type="'.$m[2].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
                },$str,10);//ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#i',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[3]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[2].'" type="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
        },$str,10);

        // Quote in comments, new version
        while(preg_match('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#i', $str))
            $str = preg_replace_callback('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#im', function($m) {
                    return '<div class="qu_main"><div class="qu_user">'.$m[1].'</div>'.$m[2].'</div>';
                }, $str, 1);

        while(preg_match('#\[quote=(.+?)\](.+?)\[/quote\]#i',$str))
            $str = preg_replace_callback('#\[quote=(.+?)\](.+?)\[/quote\]#im',function($m) use($domain) {
                return '<div class="quote">
                    <div style="font-weight: bold">'.$m[1].':</div>
                    <span style="float: left; margin-top: 5px">
                        <img src="'.$domain.'/static/images/oquotes.gif" alt="quote" width="20" height="11" />
                    </span>
                    <div style="font-style:italic">
                        <blockquote style="margin-left: 3%">'.trim($m[2]).'</blockquote>
                    </div>
                    <span style="float: right">
                        <img src="'.$domain.'/static/images/cquotes.gif" alt="cquote" width="20" height="11" />
                    </span>
                </div>';
            },$str,1);

        while(preg_match('#\[quote\](.+?)\[/quote\]#i',$str))
            $str = preg_replace_callback('#\[quote\](.+?)\[/quote\]#im',function($m) use($domain) {
                return '<div class="quote">
                    <span style="float: left; margin-top: 5px">
                        <img src="'.$domain.'/static/images/oquotes.gif" alt="quote" width="20" height="11" />
                    </span>
                    <div style="font-style:italic">
                        <blockquote style="margin-left: 3%">'.trim($m[1]).'</blockquote>
                    </div>
                    <span style="float: right">
                        <img src="'.$domain.'/static/images/cquotes.gif" alt="cquote" width="20" height="11" />
                    </span>
                </div>';
            },$str,1);

        while(preg_match('#\[spoiler\](.+?)\[/spoiler\]#i',$str))
            $str = preg_replace('#\[spoiler\](.+?)\[/spoiler]#im',
            '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">SPOILER:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$1</div>
            </div>',$str,1);

        while(preg_match('#\[spoiler=(.+?)\](.+?)\[/spoiler\]#i',$str))
            $str = preg_replace('#\[spoiler=(.+?)\](.+?)\[/spoiler]#im',
            '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">$1:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$2</div>
            </div>',$str,1);

        $str = preg_replace_callback('#\[music\]\s*(.+?)\s*\[/music\]#i',function($m) use($ssl, $truncate) {
            $uri = strip_tags(html_entity_decode($m[1],ENT_QUOTES,'UTF-8'));
            if (stripos ($uri, 'spotify') !== false) // TODO: use a single regexp
            {
                if (preg_match ('#^spotify:track:[\d\w]+$#i', $uri))
                    $ID = $uri;
                else if (preg_match('#^https?://(?:open|play)\.spotify\.com/track/[\w\d]+$#i',$uri))
                    $ID = 'spotify:track:' . basename ($uri);
                else
                    return $m[0];
                return '<iframe src="https://embed.spotify.com/?uri='.$ID.'" width="300" height="80" frameborder="0" allowtransparency="true"></iframe>';
            }
            else if (preg_match ('#^https?://soundcloud\.com/\S+/\S+$#i',$uri))
                return '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=' . rawurlencode($uri).'"></iframe>';
            else if (preg_match ('#^https?://(?:www\.)?deezer\.com/(track|album|playlist)/(\d+)$#', $uri, $match)) {
                $a_type   = $match[1] . ($match[1] == 'track' ? 's' : '');
                $a_height = $truncate ? '80': '240';
                return "<iframe src='//www.deezer.com/plugins/player?height={$a_height}&type={$a_type}&id={$match[2]}' width='100%' height='{$a_height}' scrolling='no' frameborder='no'></iframe>";
            }
            else if (filter_var ($uri, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED))
                return '<audio preload="none" controls src="'.htmlspecialchars ($uri, ENT_QUOTES, 'UTF-8').'"></audio>';
            else 
                return $m[0];
        },$str,10);
        
        $str = preg_replace_callback('#\[twitter\]\s*(.+?)\s*\[/twitter\]#i',function($m) use($ssl) {
            return '<img data-id="'.$m[1].'" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" onload="N.loadTweet(this)">';
        },$str,10);
        
        if($truncate)
        {
            $videoCallback = function($m) use($ssl) {
                $v_url  = html_entity_decode ($m[1],ENT_QUOTES,'UTF-8');
                $output = [];
                if      (preg_match (self::YOUTUBE_REGEXP,  $v_url, $match))
                    $output = [ 'youtube', $match[1], '//i1.ytimg.com/vi/' . $match[1] . '/hqdefault.jpg', 130 ];
                else if (preg_match (self::VIMEO_REGEXP,    $v_url, $match))
                    $output = [ 'vimeo', $match[1], 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 130, 'N.vimeoThumbnail(this)' ];
                else if (preg_match (self::DMOTION_REGEXP,  $v_url, $match))
                    $output = [ 'dailymotion', $match[1], 'https://www.dailymotion.com/thumbnail/video/' . $match[1], 100 ];
                else if (preg_match (self::FACEBOOK_REGEXP, $v_url, $match))
                    $output = [ 'facebook', $match[1], 'https://graph.facebook.com/' . $match[1] . '/picture', 100 ];
                else
                    return $m[0];
                return '<a class="yt_frame" data-vid="' . $output[1] . '" data-host="' . $output[0] . '">' .
                       '<span>' . parent::lang ('VIDEO') . '</span>' .
                       '<img src="' . $output[2] . '" alt="" width="130" height="' . $output[3] . '" style="float:left;margin-right:4px"' . (isset ($output[4]) ? 'onload="' . $output[4] . '"' : '') . ' />' .
                       '</a>';
            };
            $str = preg_replace_callback('#\[video\]\s*(https?:\/\/[\S]+)\s*\[\/video\]#im',$videoCallback,$str,10);
            // don't break older posts and preserve the [yt] and [youtube] tags.
            $str = preg_replace_callback('#\[yt\]\s*(https?:\/\/[\S]+)\s*\[\/yt\]#im',$videoCallback,$str,10);
            $str = preg_replace_callback('#\[youtube\]\s*(https?:\/\/[\S]+)\s*\[\/youtube\]#im',$videoCallback,$str,10);

            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#im',function($m) use($domain,$ssl) {
                    $url = messages::imgValidUrl($m[1], $domain, $ssl);
                    return     '<a href="'.$url.'" target="_blank" class="img_frame" onclick="$(this).toggleClass(\'img_frame-extended\'); return false;">
                                    <span>
                                        '.parent::lang('IMAGES').'
                                    </span>
                                    <img src="'.$url.'" alt="" onload="N.imgLoad(this)" onerror="N.imgErr(this)" />
                                </a>';
                    },$str,10);
        }
        else
        {
            $videoCallback = function($m) use($ssl) {
                $v_url       = html_entity_decode ($m[1], ENT_QUOTES, 'UTF-8');
                $iframe_code = '';
                if      (preg_match (self::YOUTUBE_REGEXP,  $v_url, $match))
                    $iframe_code = '<iframe title="YouTube video" style="width:560px; height:340px; border:0px; margin: auto;" src="//www.youtube.com/embed/'.$match[1].'?wmode=opaque"></iframe>';
                else if (preg_match (self::VIMEO_REGEXP,    $v_url, $match))
                    $iframe_code = '<iframe src="//player.vimeo.com/video/'.$match[1].'?badge=0&amp;color=ffffff" width="500" height="281" style="margin: auto" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
                else if (preg_match (self::DMOTION_REGEXP,  $v_url, $match))
                    $iframe_code = '<iframe frameborder="0" style="margin: auto" width="480" height="270" src="//www.dailymotion.com/embed/video/'.$match[1].'" allowfullscreen></iframe>';
                else if (preg_match (self::FACEBOOK_REGEXP, $v_url, $match))
                    $iframe_code = '<iframe style="margin: auto" src="https://www.facebook.com/video/embed?video_id='.$match[1].'" width="540" height="420" frameborder="0"></iframe>';
                else
                    return $m[0];
                return '<div style="width:100%; text-align:center"><br />' . $iframe_code . '</div>';
            };

            $str = preg_replace_callback('#\[video\]\s*(https?:\/\/[\S]+)\s*\[\/video\]#im',$videoCallback,$str,10);
            $str = preg_replace_callback('#\[yt\]\s*(https?:\/\/[\S]+)\s*\[\/yt\]#im',$videoCallback,$str,10);
            $str = preg_replace_callback('#\[youtube\]\s*(https?:\/\/[\S]+)\s*\[\/youtube\]#im',$videoCallback,$str,10);
            
            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#im',function($m) use($domain,$ssl) {
                    return '<img src="'.messages::imgValidUrl($m[1],$domain,$ssl).'" alt="" style="max-width: 79%; max-height: 89%" onerror="N.imgErr(this)" />';
            },$str);
        }

        while($index > 0)
        {
            --$index;
            $lang = $codes[$index]['lang'];
            $totalcode = $codes[$index]['code'];
            $str = str_ireplace(">>>{$index}<<<","[code={$lang}]{$totalcode}[/code]",$str);
        }
        $str = $this->parseCode($str,$type,$pid,$id);

        return $str;
    }

    public function parseNewsMessage($message)
    {
        return str_replace('%%12now is34%%',$this->lang('NOW_IS'),$message);
    }

    public function countMessages($id)
    {
        if(!($o = parent::query(array('SELECT MAX("pid") AS cc FROM "posts" WHERE "to" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->cc === null ? 0 : $o->cc;
    }

    public function getMessage($hpid,$edit = false)
    {
        if(!($o = parent::query(array('SELECT "hpid", "from", "to", "pid", "message", "notify", EXTRACT(EPOCH FROM "time") AS time FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        if($edit)
            $_SESSION['nerdz_editpid'] = $o->pid;
        return $o;
    }

    public function getMessages($id,$limit)
    {
        $blist = parent::getBlacklist();

        if(empty($blist))
            $glue = '';
        else
        {
            $imp_blist = implode(',',$blist);
            $glue = 'AND "posts"."from" NOT IN ('.$imp_blist.') AND "posts"."to" NOT IN ('.$imp_blist.')';
        }

        if(!($result = parent::query(array('SELECT "hpid", "from", "to", "pid", "message", "notify", EXTRACT(EPOCH FROM "time") AS time FROM "posts" WHERE "to" = :id '.$glue.' ORDER BY "hpid" DESC LIMIT '.$limit,array(':id' => $id)),db::FETCH_STMT)))
            return false;
        return $this->getPostsArray($result,false);
    }

    public function getNMessagesBeforeHpid($N,$hpid,$id)
    {
        $blist = parent::getBlacklist();

        if($N > 20 || $N <= 0) //massimo 20 posts, defaults
            $N = 20;

        if(empty($blist))
            $glue = '';
        else
        {
            $imp_blist = implode(',',$blist);
            $glue = 'AND "posts"."from" NOT IN ('.$imp_blist.') AND "posts"."to" NOT IN ('.$imp_blist.')';
        }

        if(!($result = parent::query(array('SELECT "hpid", "from", "to", "pid", "message", "notify", EXTRACT(EPOCH FROM "time") AS time FROM "posts" WHERE "hpid" < :hpid AND "to" = :id '.$glue.' ORDER BY "hpid" DESC LIMIT '.$N,array(':id' => $id,':hpid' => $hpid)),db::FETCH_STMT)))
            return false;

        return $this->getPostsArray($result,false);
    }

    public function addMessage($to,$message)
    {
        return parent::query(
            [
                'INSERT INTO "posts" ("from","to","message") VALUES (:id,:to,:message)',
                [
                    ':id' => $_SESSION['nerdz_id'],
                    ':to' => $to,
                    ':message' => htmlspecialchars($message,ENT_QUOTES,'UTF-8')
                ]
            ],db::FETCH_ERRSTR);
    }

    public function deleteMessage($hpid)
    {
        return (
            ($obj = parent::query(array('SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) &&
            $this->canRemovePost(array('from' => $obj->from,'to' => $obj->to)) &&
            db::NO_ERRNO == parent::query(array('DELETE FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_ERRNO) // triggers fix the rest
          );
    }

    public function editMessage($hpid,$message)
    {
        $message = htmlspecialchars($message,ENT_QUOTES,'UTF-8'); //fixed empty entities
        return !(
            empty($message) ||
            !($obj = parent::query(array('SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) ||
            !$this->canEditPost(array('from' => $obj->from, 'to' => $obj->to)) ||
            empty($_SESSION['nerdz_editpid']) || $_SESSION['nerdz_editpid'] != $obj->pid ||
            empty($message) ||
            db::NO_ERRNO != parent::query(array('UPDATE "posts" SET "from" = :from, "to" = :to, "pid" = :pid, "message" = :message WHERE "hpid" = :hpid',array(':from' => $obj->from, ':to' => $obj->to, ':pid' => $obj->pid, ':message' => $message, ':hpid' => $hpid)),db::FETCH_ERRNO)
          );
    }

    public function getLatests($limit,$prj = null,$onlyfollowed = false,$lang = false)
    {
        $ret = array();
        $blist = parent::getBlacklist();

        if(($lang && !$onlyfollowed) || (!$lang && !$onlyfollowed))
        {
            $lang = $lang ? $lang : parent::getUserLanguage($_SESSION['nerdz_id']);
            $glue = $lang == '*' ? 'TRUE' : "\"lang\" = '{$lang}'";
        }
        elseif($onlyfollowed)
        {
            $followed = parent::getFollow($_SESSION['nerdz_id']);
            $followed[] = $_SESSION['nerdz_id'];
            $glue = ($prj ? 'groups_posts.from' : 'posts.from').' IN ('.implode(',',$followed).')';
        }

        if(!empty($blist))
        {
            $imp_blist = implode(',',$blist);
            $glue.= ' AND '.($prj ? 'groups_posts.from' : 'posts.from')." NOT IN ({$imp_blist})".($prj ? '' : " AND posts.to NOT IN ({$imp_blist})");
        }

        $q = $prj ?
        'SELECT groups.visible, groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" INNER JOIN "groups" ON groups_posts.to = groups.counter INNER JOIN users ON groups_posts."from" = users.counter WHERE '.$glue.' AND ("visible" = TRUE OR (\''.$_SESSION['nerdz_id'].'\' IN (SELECT "user" FROM groups_members WHERE "group" = groups_posts."to")) OR \''.$_SESSION['nerdz_id'].'\' = groups.owner ) ORDER BY groups_posts.hpid DESC LIMIT '.$limit :
        'SELECT posts.hpid, posts.from, posts.to, posts.pid, posts.message, posts.notify, EXTRACT(EPOCH FROM posts.time) AS time,users.lang FROM "posts" INNER JOIN "users" ON users.counter = posts.to WHERE '.$glue.' ORDER BY posts.hpid DESC LIMIT '.$limit;

        if(!($result = parent::query($q,db::FETCH_STMT)))
            return $ret;

        return $this->getPostsArray($result,$prj);
    }

    public function getNLatestBeforeHpid($N,$hpid,$prj = null,$onlyfollowed = false,$lang = false)
    {
        $ret = array();
        $blist = parent::getBlacklist();
        $glue = '';

        if($N > 20 || $N <= 0) //massimo 20 posts, defaults
            $N = 20;

        if(($lang && !$onlyfollowed) || (!$lang && !$onlyfollowed))
        {
            $lang = $lang ? $lang : parent::getUserLanguage($_SESSION['nerdz_id']);
            $glue = $lang == '*' ? 'TRUE' : "\"lang\" = '{$lang}'";
        }
        elseif($onlyfollowed)
        {
            $followed = parent::getFollow($_SESSION['nerdz_id']);
            $followed[] = $_SESSION['nerdz_id'];
            $glue = ($prj ? 'groups_posts.from' : 'posts.from').' IN ('.implode(',',$followed).')';
        }

        if(!empty($blist))
        {
            $imp_blist = implode(',',$blist);
            $glue.= ' AND '.($prj ? 'groups_posts.from' : 'posts.from')." NOT IN ({$imp_blist})".($prj ? '' : " AND posts.to NOT IN ({$imp_blist})");
        }

        $q = $prj ?
        array('SELECT groups.visible, groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" INNER JOIN "groups" ON groups_posts.to = groups.counter INNER JOIN users ON groups_posts."from" = users.counter WHERE '.$glue.' AND ("visible" = TRUE OR (\''.$_SESSION['nerdz_id'].'\' IN (SELECT "user" FROM groups_members WHERE "group" = groups_posts."to")) OR \''.$_SESSION['nerdz_id'].'\' = groups.owner ) AND "hpid" < :hpid ORDER BY groups_posts.hpid DESC LIMIT '.$N,array(':hpid' => $hpid)) :
        array('SELECT posts.hpid, posts.from, posts.to, posts.pid, posts.message, posts.notify, EXTRACT(EPOCH FROM posts.time) AS time,users.lang FROM "posts" INNER JOIN "users" ON users.counter = posts.to WHERE '.(empty($glue) ? '' : "{$glue} AND ").' "hpid" < :hpid ORDER BY posts.hpid DESC LIMIT '.$N,array(':hpid' => $hpid));

        if(!($result = parent::query($q,db::FETCH_STMT)))
            return $ret;

        return $this->getPostsArray($result,$prj);
    }

    public function getPostsArray($result,$prj,$inList = null) /* In list is a parameter used for projects only. To disaplay news in project board, if posted as news */
    {
        $c=0;
        $ret = array();
        while(($row = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$c]['news'] = (!$prj && ($row->from == USERS_NEWS)) || ($prj && ( $row->to == 1 /*hompage*/ || ($inList && $row->news) )); // per i progetti, le news sono nerdz
            $ret[$c]['hpid'] = $row->hpid;
            $ret[$c]['from'] = $row->from;
            $ret[$c]['to'] = $row->to;
            $ret[$c]['pid'] = $row->pid;
            $ret[$c]['message'] = $ret[$c]['news'] ? $this->parseNewsMessage($row->message) : $row->message;
            $ret[$c]['datetime'] = parent::getDateTime($row->time);
            $ret[$c]['cmp'] = $row->time;
            ++$c;
        }
        return $ret;
    }

    public function canEditPost($post) 
    {
        if(parent::isLogged())
            if(($_SESSION['nerdz_id'] == $post['to']) && ($_SESSION['nerdz_id'] == $post['from']))
                return true;
            else
            {
                if($_SESSION['nerdz_id'] == $post['from'])
                    return true;
                elseif($_SESSION['nerdz_id'] == $post['to'])
                    return false;
            }
        return false;
    }

    public function canRemovePost($post)
    {
        return parent::isLogged() && (($_SESSION['nerdz_id'] == $post['to']) || ($_SESSION['nerdz_id'] == $post['from']));
    }

    public function canShowLockForPost($post)
    {
        return parent::isLogged() && (
                in_array($_SESSION['nerdz_id'],array($post['from'],$post['to'])) ||
                parent::query(array('SELECT DISTINCT "from" FROM "comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
            );
    }

    public function hasLockedPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "hpid" FROM "posts_no_notify" WHERE "hpid" = :hpid AND "user" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }

    public function hasLurkedPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "post" FROM "lurkers" WHERE "post" = :hpid AND "user" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }

    public function hasBookmarkedPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "hpid" FROM "bookmarks" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }

    public function stripTags($message)
    {
        return  str_ireplace('[url="','',
                str_ireplace('[url=','',
                str_replace('"]',' ',
                str_replace(']',' ',
                str_ireplace('[url]','',
                str_ireplace('[twitter]','',
                str_ireplace('[/twitter]','',
                str_ireplace('[video]','',
                str_ireplace('[music]','',
                str_ireplace('[img]','',
                str_ireplace('[/img]','',
                str_ireplace('[/url]','',
                str_ireplace('[youtube]','',
                str_ireplace('[/youtube]','',
                str_ireplace('[yt]','',
                str_ireplace('[/yt]','',
                str_ireplace('[i]','',
                str_ireplace('[/i]','',
                str_ireplace('[b]','',
                str_ireplace('[/b]','',
                str_ireplace('[code=','',
                str_ireplace('[/code]','',
                str_ireplace('[cur]','',
                str_ireplace('[/cur]','',
                str_ireplace('[list]','',
                str_ireplace('[/list]','',
                str_ireplace('[gist]','',
                str_replace('[*]','',
                str_ireplace('[quote]','',
                str_ireplace('[user]','',
                str_ireplace('[/user]','',
                str_ireplace('[project]','',
                str_ireplace('[/project]','',
                str_ireplace('[spoiler]','',
                str_ireplace('[/spoiler]','',
                str_ireplace('[small]','',
                str_ireplace('[/small]','',
                str_ireplace('[m]','',
                str_ireplace('[/m]','',
                str_ireplace('[math]','',
                str_ireplace('[/math]','',
                str_ireplace('[wiki=','',
                str_ireplace('[/wiki]','',
                str_ireplace('[u]','',
                str_ireplace('[big]','',
                str_ireplace('[/u]','',
                str_ireplace('[/big]','',
                str_ireplace('[hr]','',
                str_ireplace('[wat]','',
                str_ireplace('[quote=','',$message))))))))))))))))))))))))))))))))))))))))))))))))));
    } 

    public function getThumbs($hpid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'thumbs';

        $ret = parent::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hpid" = :hpid GROUP BY hpid',
                [
                  ':hpid' => $hpid
                ]

            ],
            db::FETCH_OBJ
        );

        return isset($ret->sum) ? $ret->sum : 0;
    }

    public function getRevisionsNumber($hpid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'posts_revisions';

        $ret = parent::query(
            [
                'SELECT COALESCE( MAX("rev_no"), 0 )  AS "rev_no" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                  ':hpid' => $hpid
                ]

            ],
            db::FETCH_OBJ
        );

        return isset($ret->rev_no) ? $ret->rev_no : 0;
    }

    public function getRevision($hpid, $number,  $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'posts_revisions';

        return parent::query(
            [
                'SELECT message, EXTRACT(EPOCH FROM "time") AS time FROM "'.$table.'" WHERE "hpid" = :hpid AND "rev_no" = :number',
                [

                    ':hpid' => $hpid,
                    ':number' => $number
                ]

            ],
            db::FETCH_OBJ
        );
    }

    public function getUserThumb($hpid, $prj = false) {
        if (!parent::isLogged()) {
          return 0;
        }
        $table = $prj ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :from',
                [
                  ':hpid' => $hpid,
                  ':from' => $_SESSION['nerdz_id']
                ]

            ],
            db::FETCH_OBJ
        );

        if (isset($ret->vote)) {
           return $ret->vote;
        }

        return 0;
    }

    public function setThumbs($hpid, $vote, $prj = false) {
        if (!parent::isLogged()) {
          return false;
        }

        $table = $prj ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'INSERT INTO '.$table.'(hpid, "from", vote) VALUES(:hpid, :from, :vote)',
              [
                ':hpid' => (int) $hpid,
                ':from' => (int) $_SESSION['nerdz_id'],
                ':vote' => (int) $vote
              ]
            ],
            db::FETCH_ERRNO
        );

        return $ret == db::NO_ERRNO;
    }
}
?>
