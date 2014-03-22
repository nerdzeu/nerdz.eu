<?php
/*
 * Classe per la gestione dei posts
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class messages extends phpCore
{
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

        $str = preg_replace_callback('#\[url=&quot;(.+?)&quot;\](.+?)\[/url\]#im',function($m) use ($validURL) {
                   return $validURL($m);
            },$str);
        $str = preg_replace_callback('#\[url=(.+?)\](.+?)\[/url\]#im',function($m) use ($validURL) {
                return $validURL($m);
            },$str);

        $str = preg_replace_callback('#\[url\](.+?)\[/url\]#im',function($m) use ($validURL) {
                return $validURL($m);
            },$str);

        $str = preg_replace('#\[i\](.+?)\[/i\]#im','<span style="font-style:italic">$1</span>',$str);
        $str = preg_replace('#\[cur\](.+?)\[/cur\]#im','<span style="font-style:italic">$1</span>',$str);
        $str = preg_replace('#\[gist\]([0-9a-zA-Z]+)\[/gist\]#','<div class="gistLoad" data-id="$1" id="gist-$1">'.parent::lang('LOADING').'...</div>',$str);
        $str = preg_replace('#\[b\](.+?)\[/b\]#im','<span style="font-weight:bold">$1</span>',$str);
        $str = preg_replace('#\[del\](.+?)\[/del\]#im','<del>$1</del>',$str);
        $str = preg_replace('#\[u\](.+?)\[/u\]#im','<u>$1</u>',$str);
        $str = preg_replace('#\[hr\]#im','<hr style="clear:both" />',$str);
        $str = preg_replace('#\[small\](.+?)\[/small\]#im','<span style="font-size:7pt">$1</span>',$str);
        $str = preg_replace('#\[big\](.+?)\[/big\]#im','<span style="font-size:14pt">$1</span>',$str);
        $str = preg_replace('#\[wat\]#im','<span style="font-size:22pt">WAT</span>',$str); //easter egg [never change]

        $str = preg_replace_callback('#\[user\](.+?)\[/user\]#im',function($m) {
                return '<a href="/'.phpCore::userLink($m[1])."\">{$m[1]}</a>";
                },$str);
        $str = preg_replace_callback('#\[project\](.+?)\[/project\]#im',function($m) {
                return '<a href="/'.phpCore::projectLink($m[1])."\">{$m[1]}</a>";
                },$str);
        $str = preg_replace_callback('#\[wiki=([a-z]{2})\](.+?)\[/wiki\]#im',function($m) {
                return '<a href="http://'.$m[1].'.wikipedia.org/wiki/'.urlencode(str_replace(' ','_',html_entity_decode($m[2],ENT_QUOTES,'UTF-8')))."\" onclick=\"window.open(this.href); return false\">{$m[2]} @Wikipedia - {$m[1]}</a>";
                },$str);
        $str = preg_replace_callback("#(\[math\]|\[m\])(.+?)(\[/math\]|\[/m\])#im",function($m) {
                return $m[1].strip_tags($m[2]).$m[3];
                },$str);

        $str = preg_replace_callback('#\[list\](.+?)\[\/list\]#im',function($m) {

                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[1]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ul>';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ul>';

                return $ret;
                },$str,20); //ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#im', function($m) {

                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[2]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol type="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                },$str,10); //ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#im',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[2]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
                },$str,10);//ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#im',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[3]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[1].'" type="'.$m[2].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
                },$str,10);//ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#im',function($m) {
                $arr = array_filter(explode('[*]',trim(str_replace('<br />','',$m[3]))));
                if(empty($arr))
                    return $m[0];

                $ret = '<ol start="'.$m[2].'" type="'.$m[1].'">';
                foreach($arr as $v)
                    $ret .= '<li>'.trim($v).'</li>';
                $ret .= '</ol>';

                return $ret;
                
                },$str,10);

        while(preg_match('#\[quote=(.+?)\](.+?)\[/quote]#im',$str))
            $str = preg_replace_callback('#\[quote=(.+?)\](.+?)\[/quote]#im',function($m) use($domain) {
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

        while(preg_match('#\[quote\](.+?)\[/quote]#im',$str))
            $str = preg_replace_callback('#\[quote\](.+?)\[/quote]#im',function($m) use($domain) {
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

        // Quote in comments, new version
        while(preg_match('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#im', $str))
            $str = preg_replace_callback('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#im', function($m) {
                    return '<div class="qu_main"><div class="qu_user">'.$m[1].'</div>'.$m[2].'</div>';
                }, $str, 1);

        while(preg_match('#\[spoiler\](.+?)\[/spoiler]#im',$str))
            $str = preg_replace('#\[spoiler\](.+?)\[/spoiler]#im',
            '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">SPOILER:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$1</div>
            </div>',$str,1);

        while(preg_match('#\[spoiler=(.+?)\](.+?)\[/spoiler]#im',$str))
            $str = preg_replace('#\[spoiler=(.+?)\](.+?)\[/spoiler]#im',
            '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">$1:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$2</div>
            </div>',$str,1);

        $imgValidUrl = function($m,$domain,$ssl) {
            $m[1] = trim($m[1]);
            return 
                (!phpCore::isValidURL($m[1]) ?
                $domain.'/static/images/invalidImgUrl.php' :
                (
                    $ssl ? 
                        (
                          preg_match('#^https://#i',$m[1]) ?
                          strip_tags($m[1]) :
                          'https://i0.wp.com/'.
                          (
                            preg_match("#^http://(i\.)?imgur\.com#i", $m[1]) ?
                            'www.'.preg_replace('#^http://|^ftp://#i','', strip_tags($m[1])) :
                            preg_replace('#^http://|^ftp://#i','',strip_tags($m[1]))
                          )
                        )
                        :
                        strip_tags($m[1])
                )
            );
        };

        if($truncate)
        {
            $callBack2Param = function($m) use($ssl) {
                $qsvar = array();
                parse_str(html_entity_decode($m[2],ENT_QUOTES,'UTF-8'),$qsvar);
                if(empty($qsvar['v']) || !preg_match('#^[\w+\-]{11}(\#.+?)?$#',$qsvar['v']))
                    return $m[0];

                return '<a class="yt_frame" data-vid="'.$qsvar['v'].'">
                          <span>'.parent::lang('VIDEO').'</span>
                          <img src="http'.($ssl ? 's': '').'://i1.ytimg.com/vi/'.$qsvar['v'].'/hqdefault.jpg" alt="" width="130" height="130" style="float: left; margin-right:4px; " />
                        </a>';
            };

            $str = preg_replace_callback('#\[youtube\](.+?)youtube.com\/watch\?(.+?)\[\/youtube\]#im', $callBack2Param,$str,10);
            $str = preg_replace_callback('#\[yt\](.+?)youtube.com\/watch\?(.+?)\[\/yt\]#im', $callBack2Param,$str,10);

            $callBack1Param = function($m) use($ssl) {
                return '<a class="yt_frame" data-vid="'.$m[1].'">
                            <span>'.parent::lang('VIDEO').'</span>
                            <img src="http'.($ssl ? 's': '').'://i1.ytimg.com/vi/'.$m[1].'/hqdefault.jpg" alt="" width="130" height="130" style="float: left; margin-right:4px; " />
                        </a>';
            };

            $str = preg_replace_callback('#\[youtube\]http:\/\/youtu.be\/(.{11})\[\/youtube\]#im',$callBack1Param,$str,10);
            $str = preg_replace_callback('#\[yt\]http:\/\/youtu.be\/(.{11})\[\/yt\]#im',$callBack1Param,$str,10);

            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#im',function($m) use($domain,$ssl,$imgValidUrl) {
                    $url = $imgValidUrl($m, $domain, $ssl);
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
            $callBack2Param = function($m) use($ssl) {
                $qsvar = array();
                parse_str(html_entity_decode($m[2],ENT_QUOTES,'UTF-8'),$qsvar);
                if(empty($qsvar['v']) || !preg_match('#^[\w+\-]{11}(\#.+?)?$#',$qsvar['v']))
                    return $m[0];
                return '<div style="width:80%; margin: auto;text-align:center">
                            <br /><iframe title="YouTube video" style="width:560px; height:340px; border:0px" src="http'.($ssl ? 's': '').'://www.youtube.com/embed/'.$qsvar['v'].'?wmode=opaque"></iframe>
                        </div>';
            };

            $str = preg_replace_callback('#\[youtube\](.+?)youtube.com\/watch\?(.+?)\[\/youtube\]#im',$callBack2Param,$str);
            $str = preg_replace_callback('#\[yt\](.+?)youtube.com\/watch\?(.+?)\[\/yt\]#im',$callBack2Param,$str);

            $callBack1Param = function($m) use($ssl) {
                return '<div style="width:80%; margin: auto;text-align:center">
                            <br /><iframe style="border:0px; width:560px; height:340px" title="YouTube video" src="http'.($ssl ? 's': '').'://www.youtube.com/embed/'.$m[1].'?wmode=opaque"></iframe>
                        </div>';
            };

            $str = preg_replace_callback('#\[youtube\]http:\/\/youtu.be\/(.{11})\[\/youtube\]#im',$callBack1Param,$str);
            $str = preg_replace_callback('#\[yt\]http:\/\/youtu.be\/(.{11})\[\/yt\]#im',$callBack1Param,$str);

            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#im',function($m) use($domain,$ssl,$imgValidUrl) {
                    return '<img src="'.$imgValidUrl($m,$domain,$ssl).'" alt="" style="max-width: 79%; max-height: 89%" onerror="N.imgErr(this)" />';
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
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
        if(!(new flood())->profilePost())
            return 0;
            
        if(isset($message[65534]))
            return false;

        if(parent::closedProfile($to) && ($to != $_SESSION['nerdz_id'] && !in_array($_SESSION['nerdz_id'],parent::getWhitelist($to))))
            return false;

        if(in_array($to,parent::getBlacklist()))
            return false;

        $lastpid = $this->countMessages($to) + 1;
        $not = $_SESSION['nerdz_id'] != $to ? '1' : '0';

        $message = htmlentities($message,ENT_QUOTES,'UTF-8'); //fixed empty entities

        return !empty($message) && db::NO_ERR == parent::query(array('INSERT INTO "posts" ("from","to","pid","message","notify", "time") VALUES (:id,:to,:lastpid,:message,:not,NOW())',array(':id' => $_SESSION['nerdz_id'],':to' => $to,':lastpid' => $lastpid,':message' => $message,':not' => $not)),db::FETCH_ERR);
    }

    public function deleteMessage($hpid)
    {
        if(
            !($obj = parent::query(array('SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) ||
            !$this->canRemovePost(array('from' => $obj->from,'to' => $obj->to)) ||
            db::NO_ERR != parent::query(array('DELETE FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_ERR)//il trigger "before_delete_post" gestisce elimimo comments, comments_notify
          )
            return false;

        //NON POSSO GESTIRE L'AGGIORNAMENTO QUI SOTTO VIA TRIGGER MYSQL A CAUSA DI UNA SUA LIMITAZIONE
        return db::NO_ERR == parent::query(array('UPDATE "posts" SET "pid" = "pid" -1 WHERE "pid" > :pid AND "to" = :to',array(':pid' => $obj->pid, ':to' => $obj->to)),db::FETCH_ERR);
    }

    public function editMessage($hpid,$message)
    {
        $message = htmlentities($message,ENT_QUOTES,'UTF-8'); //fixed empty entities
        return !(
            empty($message) ||
            !($obj = parent::query(array('SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) ||
            !$this->canEditPost(array('from' => $obj->from, 'to' => $obj->to)) ||
            empty($_SESSION['nerdz_editpid']) || $_SESSION['nerdz_editpid'] != $obj->pid ||
            empty($message) || isset($message[65534]) ||
            db::NO_ERR != parent::query(array('UPDATE "posts" SET "from" = :from, "to" = :to, "pid" = :pid, "message" = :message WHERE "hpid" = :hpid',array(':from' => $obj->from, ':to' => $obj->to, ':pid' => $obj->pid, ':message' => $message, ':hpid' => $hpid)),db::FETCH_ERR)
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
        'SELECT groups.visible, groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" INNER JOIN "groups" ON groups_posts.to = groups.counter INNER JOIN users ON groups_posts."from" = users.counter WHERE '.$glue.' AND "visible" = TRUE ORDER BY groups_posts.hpid DESC LIMIT '.$limit :
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
        array('SELECT groups.visible, groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" INNER JOIN "groups" ON groups_posts.to = groups.counter INNER JOIN users ON groups_posts."from" = users.counter WHERE '.$glue.' AND "visible" = TRUE AND "hpid" < :hpid ORDER BY groups_posts.hpid DESC LIMIT '.$N,array(':hpid' => $hpid)) :
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
        if(parent::isLogged())
            return ($_SESSION['nerdz_id'] == $post['to']) || ($_SESSION['nerdz_id'] == $post['from']);
    }

    public function canShowLockForPost($post)
    {
        if(
            parent::isLogged() &&
            (
                in_array($_SESSION['nerdz_id'],array($post['from'],$post['to'])) ||
                parent::query(array('SELECT DISTINCT "from" FROM "comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
            )
          )
            return true;
        return false;
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
                str_ireplace('[quote=','',$message))))))))))))))))))))))))))))))))))))))))))))));
    } 

    public function getThumbs($hpid, $prj = false) {
        $table = $prj ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hpid" = :hpid GROUP BY hpid',
                [
                  ':hpid' => $hpid
                ]

            ],
            db::FETCH_OBJ
        );

        if (isset($ret->sum)) {
           return $ret->sum;
        }

        return 0;
    }

    public function getUserThumb($hpid, $prj = false) {
        if (!parent::isLogged()) {
          return 0;
        }
        $table = $prj ? "groups_thumbs" : "thumbs";

        $ret = parent::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hpid" = :hpid AND "user" = :user',
                [
                  ':hpid' => $hpid,
                  ':user' => $_SESSION['nerdz_id']
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
              'WITH new_values (hpid, "user", vote) AS ( VALUES(CAST(:hpid AS int8), CAST(:user AS int8), CAST(:vote AS int8))),
              upsert AS ( 
                  UPDATE '.$table.' AS m 
                  SET vote = nv.vote
                  FROM new_values AS nv
                  WHERE m.hpid = nv.hpid
                    AND m.user = nv.user
                  RETURNING m.*
              )
              INSERT INTO '.$table.' (hpid, "user", vote)
              SELECT hpid, "user", vote
              FROM new_values
              WHERE NOT EXISTS (SELECT 1 
                                FROM upsert AS up 
                                WHERE up.hpid = new_values.hpid 
                                  AND up.user = new_values.user)',
              [
                ':hpid' => (int) $hpid,
                ':user' => (int) $_SESSION['nerdz_id'],
                ':vote' => (int) $vote
              ]
            ],
            db::FETCH_ERR
        );

        return $ret == db::NO_ERR;
    }
}
?>
