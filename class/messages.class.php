<?php
namespace NERDZ\Core;

use PDO;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Messages extends Core
{
    // regular expressions used to parse the [video] bbcode
    const YOUTUBE_REGEXP  = '#^https?://(?:(?:www|m)\.)?(?:youtube\.com/watch(?:\?v=|\?.+?&v=)|youtu\.be/)([a-z0-9_-]+)#i';
    const VIMEO_REGEXP    = '#^https?://(?:www\.)?vimeo\.com.+?(\d+)$#i';
    const DMOTION_REGEXP  = '#^https?://(?:www\.)?(?:dai\.ly/|dailymotion\.com/(?:.+?video=|(?:video|hub)/))([a-z0-9]+)#i';
    const FACEBOOK_REGEXP = '#^https?://(?:www\.)?facebook\.com/photo\.php(?:\?v=|\?.+?&v=)(\d+)#i';

    private $project;

    public function __construct()
    {
        parent::__construct();
        $this->project = new Project();
    }

    public function getCodes($str)
    {
        $epos = $key = $i = $codecounter = 0;
        $codes = $start = $end = $ret = [];
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

    public static function imgValidUrl($url, $domain, $sslEnabled)
    {
        $url = strip_tags(trim($url));
        if (!Core::isValidURL($url))
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
            return Config\CAMO_KEY == '' ?
                'https://i0.wp.com/' . preg_replace ('#^http://|^ftp://#i', '', strip_tags($url)) :
                $domain.'/secure/image/'.hash_hmac('sha1', $url, Config\CAMO_KEY).'?url='.urlencode($url);
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
        $domain = $ssl ? 'https://'.Config\SITE_HOST : Config\STATIC_DOMAIN;

        $validURL = function($m) {
            $m[1] = trim($m[1]);
            if(!Core::isValidURL($m[1]))
            {
                $m[1] = 'www.'.$m[1];
                if(!Core::isValidURL($m[1]))
                    return '<b>'.parent::lang('INVALID_URL').'</b>';
            }
            $url = preg_match('#^(http(s)?:\/\/)|(ftp:\/\/)#im',$m[1]) ? $m[1] : 'http://'.$m[1];
            return isset($m[2]) ? '<a href="'.Messages::stripTags($url).'" onclick="window.open(this.href); return false">'.$m[2].'</a>' : '<a href="'.Messages::stripTags($url).'" onclick="window.open(this.href); return false">'.$m[1].'</a>';
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
                return '<a href="/'.Core::userLink($m[1])."\">{$m[1]}</a>";
                },$str);
        $str = preg_replace_callback('#\[project\](.+?)\[/project\]#i',function($m) {
                return '<a href="/'.Core::projectLink($m[1])."\">{$m[1]}</a>";
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
            // The reason for the 'data-uuid' attribute is in the jclass.js file, in the loadTweet function.
            return '<img data-id="'.$m[1].'" data-uuid="'.mt_rand().'" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" onload="N.loadTweet(this)">';
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
                    $url = Messages::imgValidUrl($m[1], $domain, $ssl);
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
                    return '<img src="'.Messages::imgValidUrl($m[1],$domain,$ssl).'" alt="" style="max-width: 79%; max-height: 89%" onerror="N.imgErr(this)" />';
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

    public function countMessages($id, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if(!($o = parent::query(
            [
                'SELECT COALESCE( MAX("pid"), 0 ) AS cc FROM "'.$table.'" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 0;

        return $o->cc;
    }

    public function getMessage($hpid,$project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if(!($o = parent::query(
            [
                'SELECT p.*, EXTRACT(EPOCH FROM p."time") AS time FROM "'.$table.'" p WHERE p."hpid" = :hpid',
                    [
                        ':hpid' => $hpid
                    ]
            ],Db::FETCH_OBJ))
        )
            return new StdClass();

        return $o;
    }

    public function getMessages($id, $options = [])
    {
        extract($options);
        $limit        = !empty($limit)  ? $limit : 10;
        $lang         = !empty($lang)   ? $lang : false;
        $hpid         = !empty($hpid)   ? $hpid : false;
        $search       = !empty($search) ? $search : false;
        $project      = !empty($project);
        $inHome       = !empty($inHome);
        $onlyfollowed = !empty($onlyfollowed);

        $anyone       = $lang === '*';
        $search4Lang  = false;

        $table = ($project ? 'groups_' : '').'posts';

        $glue = $id ? ' "to" = :id ' : 'TRUE';
        var_dump($options);

        if($onlyfollowed) {
            $followed = array_merge(parent::getFollow($_SESSION['id']), (array)$_SESSION['id']);
            $glue    .= ' AND p."from" IN ('.implode(',',$followed).') ';
        } elseif($lang && !$anyone) {
            $languages = array_merge(parent::availableLanguages(), (array)'*');
            if(!in_array($lang,$languages))
                $lang = parent::isLogged() ? parent::getUserLanguage($_SESSION['id']) : 'en';

            $glue .= ' AND p.lang = :lang ';
            $search4Lang = true;
        }

        if($limit > 20 || $limit <= 0) // at most 20 posts
            $limit = 20;

        $blist = parent::getRealBlacklist();

        if(!empty($blist))
        {
            $imp_blist = implode(',',$blist);
            $glue .= ' AND p."from" NOT IN ('.$imp_blist.') ';
            if(!$project) {
                $glue .= ' AND p."to" NOT IN ('.$imp_blist.') ';
            }
        }

        $glue .= $search ? ' AND p.message ILIKE :like ' : '';
        $glue .= $hpid   ? ' AND p.hpid < :hpid ' : '';

        $join = '';
        if($project) {
            $join  = ' INNER JOIN "groups" g ON p.to = g.counter INNER JOIN "users"  u ON p."from" = u.counter ';
            $glue .= ' AND (g."visible" IS TRUE ';

            if(parent::isLogged())
                $glue .= ' OR (\''.$_SESSION['id'].'\' IN (
                            SELECT "user" FROM groups_members WHERE "group" = p."to"
                           )) OR \''.$_SESSION['id'].'\' = g.owner';
            $glue .= ') ';
        }

        var_dump($glue);

        if(!($result = parent::query(
            [
                'SELECT p.*, EXTRACT(EPOCH FROM p."time") AS time FROM "'.$table.'" p '.$join.' WHERE '.$glue.' ORDER BY "hpid" DESC LIMIT '.$limit,
                array_merge(
                    $id             ? [ ':id'   => $id ]             : [],
                    $search4Lang    ? [ ':lang' => $lang]            : [],
                    $hpid           ? [ ':hpid' => $hpid ]           : [],
                    $search         ? [ ':like' => '%'.$search.'%' ] : []
                )

            ],Db::FETCH_STMT))
          )
          return [];

        return $this->getPostsArray($result, $project, $inHome);
    }

    public function addMessage($to, $message, $options = [])
    {
        extract($options);
        $news = !empty($news);
        $project = !empty($project);

        $table = ($project ? 'groups_' : '').'posts';

        $retStr = parent::query(
            [
                'INSERT INTO "'.$table.'" ("from","to","message", "news") VALUES (:id,:to,:message, :news)',
                [
                    ':id'      => $_SESSION['id'],
                    ':to'      => $to,
                    ':message' => htmlspecialchars($message,ENT_QUOTES,'UTF-8'),
                    ':news'    => $news ? 'true' : 'false'
                ]
            ],Db::FETCH_ERRSTR);

        if($retStr != Db::NO_ERRSTR)
            return $retStr;

        if($project && $to == ISSUE_BOARD) {
            require_once 'vendor/autoload.php';
            $client = new \Github\Client();
            $client->authenticate(ISSUE_GIT_KEY, null, Github\client::AUTH_URL_TOKEN);
            $message = $this->stripTags($message);
            $client->api('issue')->create('nerdzeu','nerdz.eu',
                [
                    'title' => substr($message, 0, 128),
                     'body'  => parent::getUsername().': '.$message
                 ]
             );
        }
    }

    public function deleteMessage($hpid, $project = true)
    {
        $table = ($project ? 'groups_' : '').'posts';
        $obj = new StdClass();

        if(!($obj = parent::query(
            [
                'SELECT "from","to" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_OBJ)))
            return 'ERROR';

        return $this->canRemovePost([ 'from' => $obj->from, 'to' => $obj->to ], $project) &&
            Db::NO_ERRNO == parent::query(
                [
                    'DELETE FROM "'.$table.'" WHERE "hpid" = :hpid',
                    [
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_ERRNO);
    }

    public function editMessage($hpid,$message, $project = false)
    {
        $message = htmlspecialchars($message,ENT_QUOTES,'UTF-8');
        $table = ($project ? 'groups_' : '').'posts';
        $obj = new StdClass();

        if(!($obj = parent::query(
            [
                'SELECT "from","to","pid" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_OBJ)) ||
            !$this->canEditPost(['from' => $obj->from, 'to' => $obj->to], $project)
          )
              return 'ERROR';

        return parent::query(
            [
                'UPDATE "'.$table.'" SET "message" = :message WHERE "hpid" = :hpid',
                [
                    ':message' => $message,
                    ':hpid'    => $hpid
                ]
            ],Db::FETCH_ERRSTR);
    }
 
    public function canEditPost($post, $project = false)
    {
        return parent::isLogged() && (
            $project ? 
            in_array($_SESSION['id'],array_merge((array)$this->project->getMembers($post['to']),(array)$this->project->getOwner($post['to']),(array)$post['from']))
            : $_SESSION['id'] == $post['from']
        );
    }

    public function canRemovePost($post, $project = false)
    {
        return parent::isLogged() && (
            $project ?
                in_array($_SESSION['id'],array_merge((array)$this->project->getMembers($post['to']),(array)$this->project->getOwner($post['to']),(array)$post['from']))
            : in_array($_SESSION['id'], [ $post['to'], $post['from'] ] )
        );
    }

    public function canShowLockForPost($post, $project = false)
    {
        $table =  ($project ? 'groups_' : '').'comments';
        return parent::isLogged() && (
            (
                $project
                ? $_SESSION['id'] == $post['from']
                : in_array($_SESSION['id'],array($post['from'],$post['to']))
            ) ||
            parent::query(
                     [
                         'SELECT DISTINCT "from" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :id',
                         [
                             ':hpid' => $post['hpid'],
                             ':id' => $_SESSION['id']
                         ]
                     ], Db::ROW_COUNT) > 0
            );
    }

    public function hasLockedPost($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts_no_notify';
        return (
                parent::isLogged() &&
                parent::query(
                    [
                        'SELECT "hpid" FROM "'.$table.'" WHERE "hpid" = :hpid AND "user" = :id',
                        [
                            ':hpid' => $post['hpid'],
                            ':id'   => $_SESSION['id']
                        ]
                    ],Db::ROW_COUNT) > 0
               );
    }

    public function hasLurkedPost($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'lurkers';
        return (
                parent::isLogged() &&
                parent::query(
                    [
                        'SELECT "post" FROM "'.$table.'" WHERE "post" = :hpid AND "user" = :id',
                        [
                            ':hpid' => $post['hpid'],
                            ':id'   => $_SESSION['id']
                        ]
                    ],Db::ROW_COUNT) > 0
               );
    }

    public function hasBookmarkedPost($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'bookmarks';
        return (
                parent::isLogged() &&
                parent::query(
                    [
                        'SELECT "hpid" FROM "bookmarks" WHERE "hpid" = :hpid AND "from" = :id',
                        [
                            ':hpid' => $post['hpid'],
                            ':id'   => $_SESSION['id']
                        ]
                    ],Db::ROW_COUNT) > 0
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

    public function getThumbs($hpid, $project = false) {
        $table = ($project ? 'groups_' : ''). 'thumbs';

        $ret = parent::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hpid" = :hpid GROUP BY hpid',
                [
                  ':hpid' => $hpid
                ]

            ],
            Db::FETCH_OBJ
        );

        return isset($ret->sum) ? $ret->sum : 0;
    }

    public function getRevisionsNumber($hpid, $project = false) {
        $table = ($project ? 'groups_' : ''). 'posts_revisions';

        $ret = parent::query(
            [
                'SELECT COALESCE( MAX("rev_no"), 0 )  AS "rev_no" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                  ':hpid' => $hpid
                ]

            ],
            Db::FETCH_OBJ
        );

        return isset($ret->rev_no) ? $ret->rev_no : 0;
    }

    public function getRevision($hpid, $number,  $project = false) {
        $table = ($project ? 'groups_' : ''). 'posts_revisions';

        return parent::query(
            [
                'SELECT message, EXTRACT(EPOCH FROM "time") AS time FROM "'.$table.'" WHERE "hpid" = :hpid AND "rev_no" = :number',
                [

                    ':hpid' => $hpid,
                    ':number' => $number
                ]

            ],
            Db::FETCH_OBJ
        );
    }

    public function getUserThumb($hpid, $project = false) {
        if (!parent::isLogged()) {
          return 0;
        }
        $table = $project ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :from',
                [
                  ':hpid' => $hpid,
                  ':from' => $_SESSION['id']
                ]

            ],
            Db::FETCH_OBJ
        );

        if (isset($ret->vote)) {
           return $ret->vote;
        }

        return 0;
    }

    public function setThumbs($hpid, $vote, $project = false) {
        if (!parent::isLogged()) {
          return false;
        }

        $table = $project ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'INSERT INTO '.$table.'(hpid, "from", vote) VALUES(:hpid, :from, :vote)',
              [
                ':hpid' => (int) $hpid,
                ':from' => (int) $_SESSION['id'],
                ':vote' => (int) $vote
              ]
            ],
            Db::FETCH_ERRNO
        );

        return $ret == Db::NO_ERRNO;
    }

    public function getPostList($mess, $prj, $truncate = false) {
        require_once 'comments.class.php';
        $comments = new Comments();

        $count = count($mess);
        $ret = [];
        $logged = parent::isLogged();
        $toFunc = [ $this, 'get'.($prj ? 'ProjectName' : 'Username') ];
        $toFuncLink = [ $this, ($prj ? 'project' : 'user').'Link' ];

        for($i=0;$i<$count;++$i)
        {
            if(!($from = parent::getUsername($mess[$i]['from'])))
                $from = '';

            if(!($to = $toFunc($mess[$i]['to'])))
                $to =  '';

            $ret[$i]['thumbs_n'] = $this->getThumbs($mess[$i]['hpid'], $prj);
            $ret[$i]['revisions_n'] = $this->getRevisionsNumber($mess[$i]['hpid'], $prj);
            $ret[$i]['uthumb_n'] = $this->getUserThumb($mess[$i]['hpid'], $prj);
            $ret[$i]['pid_n'] = $mess[$i]['pid'];
            $ret[$i]['news_b'] = $mess[$i]['news'];
            $ret[$i]['from4link_n'] = \NERDZ\Core\Core::userLink($from);
            $ret[$i]['to4link_n'] = $toFuncLink($to);
            $ret[$i]['fromid_n'] = $mess[$i]['from'];
            $ret[$i]['toid_n'] = $mess[$i]['to'];
            $ret[$i]['from_n'] = $from;
            $ret[$i]['to_n'] = $to;
            $ret[$i]['datetime_n'] = $mess[$i]['datetime'];
            $ret[$i]['cmp_n'] = $mess[$i]['cmp'];

            $ret[$i]['canremovepost_b'] = $this->canRemovePost($mess[$i], $prj);

            $ret[$i]['caneditpost_b'] = $this->canEditPost($mess[$i], $prj);
            $ret[$i]['canshowlock_b'] = $this->canShowLockForPost($mess[$i], $prj);
            $ret[$i]['lock_b'] = $this->hasLockedPost($mess[$i], $prj);

            $ret[$i]['canshowlurk_b'] = $logged ? !$ret[$i]['canshowlock_b'] : false;
            $ret[$i]['lurk_b'] = $this->hasLurkedPost($mess[$i], $prj);
            
            $ret[$i]['canshowbookmark_b'] = $logged;
            $ret[$i]['bookmark_b'] = $this->hasBookmarkedPost($mess[$i], $prj);

            $ret[$i]['message_n'] = $this->bbcode($mess[$i]['message'],$truncate, $prj ? 'g' : 'u' ,$ret[$i]['pid_n'],$ret[$i]['toid_n']);
            $ret[$i]['postcomments_n'] = $comments->countComments($mess[$i]['hpid'], $prj);
            $ret[$i]['hpid_n'] = $mess[$i]['hpid'];
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

    private function getPostsArray($result,$project, $inHome = false)
    {
        $c = 0;
        $ret = [];
        while(($row = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$c]['news'] = $inHome
                ? ($project ? $row->to == PROJECTS_NEWS : $row->to == USERS_NEWS)
                : $row->news;
            $ret[$c]['hpid']     = $row->hpid;
            $ret[$c]['from']     = $row->from;
            $ret[$c]['to']       = $row->to;
            $ret[$c]['pid']      = $row->pid;
            $ret[$c]['message']  = $ret[$c]['news'] ? $this->parseNewsMessage($row->message) : $row->message;
            $ret[$c]['datetime'] = parent::getDateTime($row->time);
            $ret[$c]['cmp']      = $row->time;
            ++$c;
        }
        return $ret;
    }
}
?>
