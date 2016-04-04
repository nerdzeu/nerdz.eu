<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

use PDO;

require_once __DIR__.'/Autoload.class.php';

class Messages
{
    // regular expressions used to parse the [video] bbcode
    const YOUTUBE_REGEXP = '#^https?://(?:(?:www|m)\.)?(?:youtube\.com/watch(?:\?v=|\?.+?&v=)|youtu\.be/)([a-z0-9_-]+)#i';
    const VIMEO_REGEXP = '#^https?://(?:www\.)?vimeo\.com.+?(\d+).*$#i';
    const DMOTION_REGEXP = '#^https?://(?:www\.)?(?:dai\.ly/|dailymotion\.com/(?:.+?video=|(?:video|hub)/))([a-z0-9]+)#i';
    const FACEBOOK_REGEXP = '#^https?://(?:www\.)?facebook\.com/(?:(?:photo|video)\.php(?:\?v=|\?.+?&v=)|[a-z0-9._-]+/videos/)(\d+)/?#i';
    const NERDZCRUSH_REGEXP = '#^https?://(?:cdn\.)?media\.nerdz\.eu/([a-z0-9_-]{12})(?:|\.[a-z0-9]{2,4})#i';
    const IMGUR_REGEXP = '#^https?://(?:www\.)?(?:i\.)?imgur\.com/([a-z0-9_-]+)\.(?:gifv|webm)$#i';
    const HASHTAG_MAXLEN = 44;

    protected $project;
    protected $user;

    public function __construct()
    {
        $this->user = new User();
        $this->project = new Project();
    }

    public function getCodes($str)
    {
        $codeTypes = [
            // tag => attributes
            'code' => [
                'start' => '[code=', // start code
                'end' => '[/code]', // end code
                'start_len' => 6,    // start code lenghth
                'end_len' => 7,     // end code lenght
            ],
            'c' => [
                'start' => '[c=', // start code
                'end' => '[/c]', // end code
                'start_len' => 3, // start code lenghth
                'end_len' => 4, ], // end code lenght
            ];

        $zzz = strtolower($str);
        $codecounter = 0;
        $ncod = 1;
        $ret = [];
        foreach ($codeTypes as $tag => $codeType) {
            $epos = $key = $i = 0;
            $codes = $start = $end = [];
            $start[$key] = strpos($zzz, $codeType['start'], 0);
            $end[$key] = strpos($zzz, $codeType['end'], 0);

            while ((false !== $start[$key]) && (false !== $end[$key])) {
                ++$key;
                $start[$key] = strpos($zzz, $codeType['start'], $end[$key - 1] + $codeType['start_len']);
                $end[$key] = strpos($zzz, $codeType['end'],  $end[$key - 1] + $codeType['end_len']);
            }

            while ($key > 0) {
                $codes[] = substr($str, $start[$i] + $codeType['start_len'],
                    $end[$i] - $start[$i] - $codeType['start_len']);
                ++$i;
                --$key;
            }
            $epos = $i;

            for ($i = 0;$i < $epos;++$i) {
                for ($x = 0;$x < static::HASHTAG_MAXLEN;++$x) {
                    if (isset($codes[$i][$x]) && $codes[$i][$x] == ']') {
                        $lang = substr($codes[$i], 0, $x);
                        $code = substr($codes[$i], $x + 1);
                        break;
                    }
                }
                if ($x < static::HASHTAG_MAXLEN && isset($code[1])) {
                    $ret[$codecounter]['lang'] = $lang;
                    $ret[$codecounter]['code'] = $code;
                    $ret[$codecounter]['tag'] = $tag;
                    ++$codecounter;
                }
            }
            //$key = $i = 0;
        }

        return $ret;
    }

    private static function hashtag(&$str)
    {
        return preg_replace_callback('/(?!\[(?:url(?:=)|c(?:ode)?=|video|yt|youtube|music|img|twitter)[^\]]*\])([\W]|^)(#(?!\d+[\W])[\w]{1,'.static::HASHTAG_MAXLEN.'})(?![^\[]*\[\/(?:url|code|c|video|yt|youtube|music|img|twitter)\])/iu', function ($m) {
            return $m[1].'<a href="/search.php?q='.urlencode($m[2]).'">'.$m[2].'</a>';
        }, $str);
    }

    public function bbcode($str, $truncate = null, $type = null, $pid = null, $id = null)
    {
        //evitare il parsing del bbcode nel tag code
        $codes = $this->getCodes($str);
        $index = 0;
        foreach ($codes as $code) {
            $totalcode = $code['code'];
            $lang = $code['lang'];
            $str = str_ireplace("[code={$lang}]{$totalcode}[/code]", ">>>{$index}<<<", $str);
            ++$index;
        }

        $domain = System::getResourceDomain();
        $str = static::hashtag($str);
        $str = str_replace("\n", '<br />', $str);

        $validURL = function ($m) {
            $m[1] = trim($m[1]);
            if (!Utils::isValidURL($m[1])) {
                $m[1] = 'http://'.$m[1];
                if (!Utils::isValidURL($m[1])) {
                    return '<b>'.$this->user->lang('INVALID_URL').'</b>';
                }
            }
            $url = preg_match('#^(?:https?|ftp):\/\/#i', $m[1]) ? $m[1] : 'http://'.$m[1];
            $host = parse_url($url)['host'];
            $local = Utils::endsWith($host, System::getSafeCookieDomainName());
            $url = Messages::stripTags($url);
            if (!$local) {
                $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
                $url = '/out.php?url='.urlencode($url).'&hmac='.Utils::getHMAC($url, Config\CAMO_KEY);
            }

            return isset($m[2])
                ? '<a href="'.$url.'" onclick="window.open(this.href); return false">'.$m[2].'</a>'
                : '<a href="'.$url.'" onclick="window.open(this.href); return false">'.$m[1].'</a>';
        };

        $str = preg_replace_callback('#\[url=&quot;(.+?)&quot;\](.+?)\[/url\]#i', function ($m) use ($validURL) {
            return $validURL($m);
        }, $str);
        $str = preg_replace_callback('#\[url=(.+?)\](.+?)\[/url\]#i', function ($m) use ($validURL) {
            return $validURL($m);
        }, $str);
        $str = preg_replace_callback('#\[url\](.+?)\[/url\]#i', function ($m) use ($validURL) {
            return $validURL($m);
        }, $str);

        $str = preg_replace('#\[i\](.+?)\[/i\]#i', '<span style="font-style:italic">$1</span>', $str);
        $str = preg_replace('#\[cur\](.+?)\[/cur\]#i', '<span style="font-style:italic">$1</span>', $str);
        $str = preg_replace('#\[gist\]([0-9a-z]+)\[/gist\]#i', '<div class="gistLoad" data-id="$1" id="gist-$1">'.$this->user->lang('LOADING').'...</div>', $str);
        $str = preg_replace('#\[b\](.+?)\[/b\]#i', '<span style="font-weight:bold">$1</span>', $str);
        $str = preg_replace('#\[del\](.+?)\[/del\]#i', '<del>$1</del>', $str);
        $str = preg_replace('#\[u\](.+?)\[/u\]#i', '<u>$1</u>', $str);
        $str = preg_replace('#\[hr\]#i', '<hr style="clear:both" />', $str);
        $str = preg_replace('#\[small\](.+?)\[/small\]#i', '<span style="font-size:7pt">$1</span>', $str);
        $str = preg_replace('#\[big\](.+?)\[/big\]#i', '<span style="font-size:14pt">$1</span>', $str);
        $str = preg_replace('#\[wat\]#i', '<span style="font-size:22pt">WAT</span>', $str);

        $str = preg_replace_callback('#\[user\](.+?)\[/user\]#i', function ($m) {
            return '<a href="/'.Utils::userLink($m[1])."\">{$m[1]}</a>";
        }, $str);
        $str = preg_replace_callback('#\[project\](.+?)\[/project\]#i', function ($m) {
            return '<a href="/'.Utils::projectLink($m[1])."\">{$m[1]}</a>";
        }, $str);
        $str = preg_replace_callback('#\[wiki=([a-z]{2})\](.+?)\[/wiki\]#i', function ($m) {
            return '<a href="http://'.$m[1].'.wikipedia.org/wiki/'.urlencode(str_replace(' ', '_', html_entity_decode($m[2], ENT_QUOTES, 'UTF-8')))."\" onclick=\"window.open(this.href); return false\">{$m[2]} @Wikipedia - {$m[1]}</a>";
        }, $str);
        $str = preg_replace_callback("#(\[math\]|\[m\])(.+?)(\[/math\]|\[/m\])#i", function ($m) {
            return $m[1].strip_tags($m[2]).$m[3];
        }, $str);

        $str = preg_replace_callback('#\[list\](.+?)\[\/list\]#i', function ($m) {
            $arr = array_filter(explode('[*]', trim(trim($m[1]), '<br />')));
            if (empty($arr)) {
                return $m[0];
            }

            $ret = '<ul>';
            foreach ($arr as $v) {
                $ret .= '<li>'.trim($v).'</li>';
            }
            $ret .= '</ul>';

            return $ret;
        }, $str, 20); //ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#i', function ($m) {
            $arr = array_filter(explode('[*]', trim(trim($m[2]), '<br />')));
            if (empty($arr)) {
                return $m[0];
            }

            $ret = '<ol type="'.$m[1].'">';
            foreach ($arr as $v) {
                $ret .= '<li>'.trim($v).'</li>';
            }
            $ret .= '</ol>';

            return $ret;
        }, $str, 10); //ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#i', function ($m) {
            $arr = array_filter(explode('[*]', trim(trim($m[2]), '<br />')));
            if (empty($arr)) {
                return $m[0];
            }

            $ret = '<ol start="'.$m[1].'">';
            foreach ($arr as $v) {
                $ret .= '<li>'.trim($v).'</li>';
            }
            $ret .= '</ol>';

            return $ret;

        }, $str, 10);//ok

        $str = preg_replace_callback('#\[list[\s]+start=&quot;(\-?\d+)&quot;[\s]+type=&quot;(1|a|i)&quot;\](.+?)\[\/list\]#i', function ($m) {
            $arr = array_filter(explode('[*]', trim(trim($m[3]), '<br />')));
            if (empty($arr)) {
                return $m[0];
            }

            $ret = '<ol start="'.$m[1].'" type="'.$m[2].'">';
            foreach ($arr as $v) {
                $ret .= '<li>'.trim($v).'</li>';
            }
            $ret .= '</ol>';

            return $ret;

        }, $str, 10);//ok

        $str = preg_replace_callback('#\[list[\s]+type=&quot;(1|a|i)&quot;[\s]+start=&quot;(\-?\d+)&quot;\](.+?)\[\/list\]#i', function ($m) {
            $arr = array_filter(explode('[*]', trim(trim($m[3]), '<br />')));
            if (empty($arr)) {
                return $m[0];
            }

            $ret = '<ol start="'.$m[2].'" type="'.$m[1].'">';
            foreach ($arr as $v) {
                $ret .= '<li>'.trim($v).'</li>';
            }
            $ret .= '</ol>';

            return $ret;

        }, $str, 10);

        // Quote in comments, new version
        while (preg_match('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#i', $str)) {
            $str = preg_replace_callback('#\[commentquote=(.+?)\](.+?)\[/commentquote\]#i', function ($m) {
                return '<div class="qu_main"><div class="qu_user">'.$m[1].'</div>'.$m[2].'</div>';
            }, $str, 1);
        }

        while (preg_match('#\[quote=(.+?)\](.+?)\[/quote\]#i', $str)) {
            $str = preg_replace_callback('#\[quote=(.+?)\](.+?)\[/quote\]#i', function ($m) use ($domain) {
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
            }, $str, 1);
        }

        while (preg_match('#\[quote\](.+?)\[/quote\]#i', $str)) {
            $str = preg_replace_callback('#\[quote\](.+?)\[/quote\]#i', function ($m) use ($domain) {
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
            }, $str, 1);
        }

        while (preg_match('#\[spoiler\](.+?)\[/spoiler\]#i', $str)) {
            $str = preg_replace('#\[spoiler\](.+?)\[/spoiler]#i',
                '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">SPOILER:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$1</div>
                </div>', $str, 1);
        }

        while (preg_match('#\[spoiler=(.+?)\](.+?)\[/spoiler\]#i', $str)) {
            $str = preg_replace('#\[spoiler=(.+?)\](.+?)\[/spoiler]#i',
                '<div class="spoiler" onclick="var c = $(this).children(\'div\'); c.toggle(\'fast\'); c.on(\'click\',function(e) {e.stopPropagation();});">
                <span style="font-weight: bold; cursor:pointer">$1:</span>
                <div style="display:none"><hr /></div>
                <div style="display:none; margin-left:3%;overflow:hidden">$2</div>
                </div>', $str, 1);
        }

        $str = preg_replace_callback('#\[music\]\s*(.+?)\s*\[/music\]#i', function ($m) use ($truncate) {
            $uri = strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            if (stripos($uri, 'spotify') !== false) {
                // TODO: use a single regexp

                if (preg_match('#^(?:spotify:track:[\d\w]+)|(?:spotify:user:[\w\d]+:playlist:[\w\d]+)$#i', $uri)) {
                    $ID = $uri;
                } elseif (preg_match('#^https?://(?:open|play)\.spotify\.com/track/[\w\d]+$#i', $uri)) {
                    $ID = 'spotify:track:'.basename($uri);
                } elseif (preg_match('#^https?://(?:open|play)\.spotify\.com/user/([\w\d]+)/playlist/[\w\d]+#i', $uri, $matches)) {
                    $ID = "spotify:user:{$matches[1]}:playlist:".basename($uri);
                } else {
                    return $m[0];
                }

                return '<iframe src="https://embed.spotify.com/?uri='.$ID.'" width="300" height="80" frameborder="0" allowtransparency="true"></iframe>';
            } elseif (preg_match('#^https?://soundcloud\.com/\S+/\S+$#i', $uri)) {
                return '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url='.rawurlencode($uri).'"></iframe>';
            } elseif (preg_match('#^https?://(?:www\.)?deezer\.com/(track|album|playlist)/(\d+)$#', $uri, $match)) {
                $a_type = $match[1].($match[1] == 'track' ? 's' : '');
                $a_height = $truncate ? '80' : '240';

                return "<iframe src='//www.deezer.com/plugins/player?height={$a_height}&type={$a_type}&id={$match[2]}' width='100%' height='{$a_height}' scrolling='no' frameborder='no'></iframe>";
            } elseif (filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
                return '<audio preload="none" controls src="'.htmlspecialchars($uri, ENT_QUOTES, 'UTF-8').'"></audio>';
            } else {
                return $m[0];
            }
        }, $str, 10);

        $str = preg_replace_callback('#\[twitter\]\s*(.+?)\s*\[/twitter\]#i', function ($m) use ($truncate) {
            // The reason for the 'data-uuid' attribute is in the jclass.js file, in the loadTweet function.
            // with a fixed height (220px - when truncate is true - js trimmer can handle post size
            if (!(is_numeric($m[1]) || Utils::isValidURL($m[1]))) {
                return $m[0];
            }

            return '<img data-id="'.htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8').'" data-uuid="'.mt_rand().'" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" onload="N.loadTweet(this)"'.($truncate ? ' height="220"' : '').'>';
        }, $str, 10);

        if ($truncate) {
            $videoCallback = function ($m) {
                $v_url = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                $output = [];
                if (preg_match(static::YOUTUBE_REGEXP,   $v_url, $match)) {
                    $output = ['youtube', $match[1], '//i1.ytimg.com/vi/'.$match[1].'/hqdefault.jpg', 130];
                } elseif (preg_match(static::VIMEO_REGEXP,     $v_url, $match)) {
                    $output = ['vimeo', $match[1], 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 130, 'N.vimeoThumbnail(this)'];
                } elseif (preg_match(static::DMOTION_REGEXP,   $v_url, $match)) {
                    $output = ['dailymotion', $match[1], 'https://www.dailymotion.com/thumbnail/video/'.$match[1], 100];
                } elseif (preg_match(static::FACEBOOK_REGEXP,  $v_url, $match)) {
                    $output = ['facebook', $match[1], 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 100, 'N.facebookThumbnail(this)'];
                } elseif (preg_match(static::NERDZCRUSH_REGEXP, $v_url, $match)) {
                    $output = ['nerdzcrush', $match[1], 'https://media.nerdz.eu/'.$match[1].'.jpg', 130];
                } elseif (preg_match(static::IMGUR_REGEXP, $v_url, $match)) {
                    $output = ['imgur', $match[1], 'https://i.imgur.com/'.$match[1].'b.gifv', 130];
                } else {
                    return $m[0];
                }

                return '<a class="yt_frame" data-vid="'.$output[1].'" data-host="'.$output[0].'">'.
                    '<span>'.$this->user->lang('VIDEO').'</span>'.
                    '<img src="'.$output[2].'" alt="" width="130" height="'.$output[3].'" style="float:left;margin-right:4px"'.(isset($output[4]) ? 'onload="'.$output[4].'"' : '').' />'.
                    '</a>';
            };
            $str = preg_replace_callback('#\[video\]\s*(https?:\/\/[\S]+)\s*\[\/video\]#i', $videoCallback, $str, 10);
            // don't break older posts and preserve the [yt] and [youtube] tags.
            $str = preg_replace_callback('#\[yt\]\s*(https?:\/\/[\S]+)\s*\[\/yt\]#i', $videoCallback, $str, 10);
            $str = preg_replace_callback('#\[youtube\]\s*(https?:\/\/[\S]+)\s*\[\/youtube\]#i', $videoCallback, $str, 10);

            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#i', function ($m) {
                $url = Utils::getValidImageURL($m[1]);

                return '<a href="'.$url.'" target="_blank" class="img_frame" onclick="$(this).toggleClass(\'img_frame-extended\'); return false;">
                    <span>
                    '.$this->user->lang('IMAGES').'
                    </span>
                    <img src="'.$url.'" alt="" onload="N.imgLoad(this)" onerror="N.imgErr(this)" />
                    </a>';
            }, $str, 10);
        } else {
            $videoCallback = function ($m) {
                $v_url = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                $iframe_code = '';
                if (preg_match(static::YOUTUBE_REGEXP,    $v_url, $match)) {
                    $iframe_code = '<iframe title="YouTube video" style="width:560px; height:340px; border:0px; margin: auto;" src="//www.youtube.com/embed/'.$match[1].'?wmode=opaque" allowfullscreen></iframe>';
                } elseif (preg_match(static::VIMEO_REGEXP,      $v_url, $match)) {
                    $iframe_code = '<iframe src="//player.vimeo.com/video/'.$match[1].'?badge=0&amp;color=ffffff" width="500" height="281" style="margin: auto" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
                } elseif (preg_match(static::DMOTION_REGEXP,    $v_url, $match)) {
                    $iframe_code = '<iframe frameborder="0" style="margin: auto" width="480" height="270" src="//www.dailymotion.com/embed/video/'.$match[1].'" allowfullscreen></iframe>';
                } elseif (preg_match(static::FACEBOOK_REGEXP,   $v_url, $match)) {
                    $iframe_code = '<iframe style="margin: auto" src="https://www.facebook.com/video/embed?video_id='.$match[1].'" frameborder="0"></iframe>';
                } elseif (preg_match(static::NERDZCRUSH_REGEXP, $v_url, $match)) {
                    $iframe_code = '<div class="nerdzcrush" data-media="'.$match[1].'#noautoplay,noloop"></div>';
                } elseif (preg_match(static::IMGUR_REGEXP, $v_url, $match)) {
                    $iframe_code = '<video src="https://i.imgur.com/'.$match[1].'.webm" controls></video>';
                } else {
                    return $m[0];
                }

                return '<div style="width:100%; text-align:center"><br />'.$iframe_code.'</div>';
            };

            $str = preg_replace_callback('#\[video\]\s*(https?:\/\/[\S]+)\s*\[\/video\]#i', $videoCallback, $str, 10);
            $str = preg_replace_callback('#\[yt\]\s*(https?:\/\/[\S]+)\s*\[\/yt\]#i', $videoCallback, $str, 10);
            $str = preg_replace_callback('#\[youtube\]\s*(https?:\/\/[\S]+)\s*\[\/youtube\]#i', $videoCallback, $str, 10);

            $str = preg_replace_callback('#\[img\](.+?)\[/img\]#i', function ($m) {
                return '<img src="'.Utils::getValidImageURL($m[1]).'" alt="" style="max-width: 79%; max-height: 89%" onerror="N.imgErr(this)" />';
            }, $str);
        }

        while ($index > 0) {
            --$index;
            $lang = $codes[$index]['lang'];
            $totalcode = $codes[$index]['code'];
            $tag = $codes[$index]['tag'];
            $str = str_ireplace(">>>{$index}<<<", "[{$tag}={$lang}]{$totalcode}[/{$tag}]", $str);
        }

        return $this->parseCode($codes, $str, $type, $pid, $id);
    }

    public function parseNews($message)
    {
        return str_replace('%%12now is34%%', $this->user->lang('NOW_IS'), $message);
    }

    public static function getMessage($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if (!is_numeric($hpid) || !($o = Db::query(
            [
                'SELECT message FROM "'.$table.'" p WHERE p."hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ))
        ) {
            return '';
        }

        return $o->message;
    }

    public static function getTags($hpid, $project = false)
    {
        $field = ($project ? 'g' : 'u').'_hpid';
        if (!is_numeric($hpid) || !($all = Db::query(
            [
                "SELECT tag FROM posts_classification WHERE {$field} = :hpid",
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ, $all = true)
        )) {
            return [];
        }

        return $all;
    }

    public function getHome($options)
    {
        if (!$this->user->isLogged()) {
            return [];
        }
        extract($options);
        $limit = !empty($limit)  ? Security::limitControl($limit, 20)  : 10;
        $lang = !empty($lang)   ? $lang   : false;
        $hpid = !empty($hpid)   ? $hpid   : false;
        $search = !empty($search) ? $search : false;
        $project = !empty($project);
        $onlyfollowed = !empty($onlyfollowed);
        $truncate = !empty($truncate);

        $anyone = $lang === '*';
        $search4Lang = false;

        $glue = 'TRUE ';

        if ($onlyfollowed) {
            $glue .= 'AND p.from IN (SELECT :id UNION ALL SELECT "to" FROM "followers" WHERE "from" = :id) ';
        } elseif ($lang && !$anyone) {
            if (!in_array($lang, System::getAvailableLanguages())) {
                $lang = $this->user->isLogged() ? $this->user->getLanguage($_SESSION['id']) : 'en';
            }

            $glue .= ' AND p.lang = :lang ';
            $search4Lang = true;
        }
        if ($search) {
            if (preg_match('/^#[\w]{1,44}$/iu', $search)) {
                return (new Search())->topic($search, $limit, $hpid);
            } else {
                // TODO: replace with full text search
                $glue .= ' AND p.message ILIKE :like ';
            }
        }

        $blist = '(SELECT * FROM blist)';
        $glue .= "AND p.\"from\" NOT IN {$blist} AND
            CASE p.type
            WHEN 1 THEN  p.\"to\" NOT IN {$blist}
            ELSE ( -- groups conditions
                    TRUE IN (SELECT visible FROM \"groups\" g WHERE g.counter = p.\"to\")
                    OR
                    (:id IN (
                        SELECT \"from\" FROM groups_members gm WHERE gm.\"to\" = p.\"to\"
                        UNION ALL
                        SELECT \"from\" FROM groups_owners go  WHERE go.\"to\" = p.\"to\")
                    )
                 )
            END ";

        if ($hpid) {
            $realTable = ($project ? 'groups_' : '').'posts';
            $glue .= 'AND p.time <= (SELECT time FROM '.$realTable.' WHERE hpid = :hpid) AND p.hpid <> :hpid ';
        }

        $query = 'with blist as (select "to" from blacklist where "from" = :id) SELECT '.
                ' p.*, EXTRACT(EPOCH FROM p."time") AS time FROM "messages" p WHERE '.
                $glue.
                ' ORDER BY p.time DESC'.
                ' LIMIT '.$limit;

        if (!($result = Db::query(
            [
                $query,
                array_merge(
                    [':id' => $_SESSION['id']],
                    $search4Lang     ? [':lang' => $lang]           : [],
                    $hpid            ? [':hpid' => $hpid]           : [],
                    $search          ? [':like' => '%'.str_replace('%', '\%', $search).'%'] : []
                ),

            ], Db::FETCH_STMT))
        ) {
            return [];
        }

        $c = 0;
        $ret = [];
        while (($row = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$c] = $this->getPost($row,
                [
                    'inHome' => true,
                    'project' => $row->type === 0,
                    'truncate' => $truncate,
                ]);

            $ret[$c]['news_b'] = $row->type === 0
                ? $row->to == Config\PROJECTS_NEWS
                : $row->to == Config\USERS_NEWS;

            ++$c;
        }

        return $ret;
    }

    public function getPosts($options = [])
    {
        extract($options);
        $id = !empty($id)     ? $id     : false;
        $limit = !empty($limit)  ? Security::limitControl($limit, 10)  : 10;
        $lang = !empty($lang)   ? $lang   : false;
        $hpid = !empty($hpid)   ? $hpid   : false;
        $search = !empty($search) ? $search : false;
        $project = !empty($project);
        $truncate = !empty($truncate);

        $anyone = $lang === '*';
        $search4Lang = false;

        $table = ($project ? 'groups_' : '').'posts';

        $glue = $id ? ' p."to" = :id ' : 'TRUE';

        if ($lang && !$anyone) {
            $languages = array_merge(System::getAvailableLanguages(), (array) '*');
            if (!in_array($lang, $languages)) {
                $lang = $this->user->isLogged() ? $this->user->getLanguage($_SESSION['id']) : 'en';
            }

            $glue .= ' AND p.lang = :lang ';
            $search4Lang = true;
        }

        $join = '';

        if ($search) {
            if (preg_match('/^#[\w]{1,44}$/iu', $search)) {
                return (new Search())->topic($search, $limit, $hpid);
            } else {
                $glue .= ' AND p.message ILIKE :like ';
            }
        }

        $blist = '(select * from blist)';

        $glue .= " AND p.\"from\" NOT IN {$blist}";
        if (!$project) {
            $glue .= " AND p.\"to\" NOT IN {$blist}";
        }

        $glue .= $hpid ? ' AND p.hpid < :hpid ' : '';

        if ($project) {
            $join .= ' INNER JOIN "groups" g ON p.to = g.counter
                INNER JOIN "users" u ON p."from" = u.counter
                INNER JOIN "groups_owners" gu ON gu."to" = p.to';
            $glue .= ' AND (g."visible" IS TRUE ';

            if ($this->user->isLogged()) {
                $glue .= ' OR (\''.$_SESSION['id'].'\' IN (
                    SELECT "from" FROM groups_members gm WHERE gm."to" = p."to"
                )) OR \''.$_SESSION['id'].'\' = gu.from';
            }
            $glue .= ') ';
        }

        $query = 'with blist as (select "to" from blacklist where "from" = '.$_SESSION['id'].') SELECT p.*, EXTRACT(EPOCH FROM p."time") AS time FROM "'.
                $table.'" p '.$join.' WHERE '.
                $glue.
                ' ORDER BY p.time DESC'.
                ' LIMIT '.$limit;

        if (!($result = Db::query(
            [
                $query,
                array_merge(
                    $id              ? [':id' => $id]             : [],
                    $search4Lang     ? [':lang' => $lang]            : [],
                    $hpid            ? [':hpid' => $hpid]           : [],
                    $search          ? [':like' => '%'.str_replace('%', '\%', $search).'%'] : []
                ),

            ], Db::FETCH_STMT))
        ) {
            return [];
        }

        $c = 0;
        $ret = [];
        while (($row = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$c] = $this->getPost($row,
                [
                    'project' => $project,
                    'truncate' => $truncate,
                ]);
            ++$c;
        }

        return $ret;
    }

    public function add($to, $message, $options = [])
    {
        extract($options);
        $news = !empty($news);
        $project = !empty($project);
        $issue = !empty($issue);
        $language = !empty($language) ? $language : false;

        if ($language) {
            if (!in_array($language, System::getAvailableLanguages())) {
                return 'error: INVALID_LANGUAGE';
            }
        } else {
            $language = $this->user->getLanguage();
        }

        $table = ($project ? 'groups_' : '').'posts';

        $retStr = Db::query(
            [
                'INSERT INTO "'.$table.'" ("from","to","message","news","lang") VALUES (:id,:to,:message, :news, :language)',
                [
                    ':id' => $_SESSION['id'],
                    ':to' => $to,
                    ':message' => Comments::parseQuote(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')),
                    ':news' => $news ? 'true' : 'false',
                    ':language' => $language,
                ],
            ], Db::FETCH_ERRSTR);

        if ($retStr != Db::NO_ERRSTR) {
            return $retStr;
        }

        if ($project && $issue && $to == Config\ISSUE_BOARD) {
            require_once __DIR__.'/vendor/Autoload.class.php';
            $client = new \Github\Client();
            $client->authenticate(Config\ISSUE_GIT_KEY, null, \Github\client::AUTH_URL_TOKEN);
            $message = static::stripTags($message);
            try {
                $client->api('issue')->create('nerdzeu', 'nerdz.eu',
                    [
                        'title' => substr($message, 0, 128),
                        'body' => User::getUsername().': '.$message,
                    ]
                );
            } catch (\Github\Exception\RuntimeException $exception) {
                System::dumpError('GitHub API: '.$exception->getMessage());
                System::dumpError('GitHub API: '.$exception->getPrevious());
            }
        }

        return $retStr;
    }

    public function reOpen($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if (!($obj = Db::query(
            [
                'SELECT "from","to","pid" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ)) ||
            !$this->canClose(['from' => $obj->from, 'to' => $obj->to], $project)
        ) {
            return 'ERROR';
        }

        return Db::query(
            [
                'UPDATE "'.$table.'" SET closed = FALSE WHERE hpid = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_ERRSTR);
    }

    public function close($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if (!($obj = Db::query(
            [
                'SELECT "from","to","pid" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ)) ||
            !$this->canClose(['from' => $obj->from, 'to' => $obj->to], $project)
        ) {
            return 'ERROR';
        }

        return Db::query(
            [
                'UPDATE "'.$table.'" SET closed = TRUE WHERE hpid = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_ERRSTR);
    }

    public function delete($hpid, $project = true)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if (!($obj = Db::query(
            [
                'SELECT "from","to" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ))) {
            return 'ERROR';
        }

        return $this->canRemove(['from' => $obj->from, 'to' => $obj->to], $project) &&
            Db::NO_ERRNO == Db::query(
                [
                    'DELETE FROM "'.$table.'" WHERE "hpid" = :hpid',
                    [
                        ':hpid' => $hpid,
                    ],
                ], Db::FETCH_ERRNO);
    }

    public function edit($hpid, $message, $project = false)
    {
        $message = Comments::parseQuote(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $table = ($project ? 'groups_' : '').'posts';

        if (!($obj = Db::query(
            [
                'SELECT "from","to","pid" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_OBJ)) ||
            !$this->canEdit(['from' => $obj->from, 'to' => $obj->to], $project)
        ) {
            return 'ERROR';
        }

        return Db::query(
            [
                'UPDATE "'.$table.'" SET "message" = :message WHERE "hpid" = :hpid',
                [
                    ':message' => $message,
                    ':hpid' => $hpid,
                ],
            ], Db::FETCH_ERRSTR);
    }

    public function canClose($post, $project = false)
    {
        return $this->canRemove($post, $project);
    }

    public function canEdit($post, $project = false)
    {
        return $this->user->isLogged() && (
            $project ?
            in_array($_SESSION['id'], array_merge((array) $this->project->getMembers($post['to']), (array) $this->project->getOwner($post['to']), (array) $post['from']))
            : $_SESSION['id'] == $post['from']
        );
    }

    public function canRemove($post, $project = false)
    {
        return $this->user->isLogged() && (
            $project
            ? in_array($_SESSION['id'], array_merge(
                (array) $this->project->getMembers($post['to']),
                (array) $this->project->getOwner($post['to']),
                (array) $post['from'])
            )
            : in_array($_SESSION['id'], [$post['to'], $post['from']])
        );
    }

    public function canShowLock($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'comments';

        return $this->user->isLogged() && (
            (
                $project
                ? $_SESSION['id'] == $post['from']
                : in_array($_SESSION['id'], array($post['from'], $post['to']))
            ) ||
            Db::query(
                [
                    'SELECT DISTINCT "from" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :id',
                    [
                        ':hpid' => $post['hpid'],
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::ROW_COUNT) > 0
            );
    }

    public static function stripTags($message)
    {
        return str_replace('[', '', str_ireplace('[url=&quot;', '',
            str_ireplace('[url=', '',
            str_replace('&quot;]', ' ',
            str_replace(']', ' ',
            str_ireplace('[url]', '',
            str_ireplace('[twitter]', '',
            str_ireplace('[/twitter]', '',
            str_ireplace('[video]', '',
            str_ireplace('[/video]', '',
            str_ireplace('[music]', '',
            str_ireplace('[/music]', '',
            str_ireplace('[img]', '',
            str_ireplace('[/img]', '',
            str_ireplace('[/url]', '',
            str_ireplace('[youtube]', '',
            str_ireplace('[/youtube]', '',
            str_ireplace('[yt]', '',
            str_ireplace('[/yt]', '',
            str_ireplace('[i]', '',
            str_ireplace('[/i]', '',
            str_ireplace('[b]', '',
            str_ireplace('[/b]', '',
            str_ireplace('[code=', '',
            str_ireplace('[c=', '',
            str_ireplace('[/c]', '',
            str_ireplace('[/code]', '',
            str_ireplace('[cur]', '',
            str_ireplace('[/cur]', '',
            str_ireplace('[list]', '',
            str_ireplace('[/list]', '',
            str_ireplace('[gist]', '',
            str_replace('[*]', '',
            str_ireplace('[quote]', '',
            str_ireplace('[user]', '',
            str_ireplace('[/user]', '',
            str_ireplace('[project]', '',
            str_ireplace('[/project]', '',
            str_ireplace('[spoiler]', '',
            str_ireplace('[spoiler=', '',
            str_ireplace('[/spoiler]', '',
            str_ireplace('[small]', '',
            str_ireplace('[/small]', '',
            str_ireplace('[m]', '',
            str_ireplace('[/m]', '',
            str_ireplace('[math]', '',
            str_ireplace('[/math]', '',
            str_ireplace('[wiki=', '',
            str_ireplace('[/wiki]', '',
            str_ireplace('[u]', '',
            str_ireplace('[big]', '',
            str_ireplace('[/u]', '',
            str_ireplace('[/big]', '',
            str_ireplace('[hr]', '',
            str_ireplace('[wat]', '',
            str_ireplace('[quote=', '', $message))))))))))))))))))))))))))))))))))))))))))))))))))))))));
    }

    public function getThumbs($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'thumbs';

        $ret = Db::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hpid" = :hpid GROUP BY hpid',
                [
                    ':hpid' => $hpid,
                ],

            ],
            Db::FETCH_OBJ
        );

        return isset($ret->sum) ? $ret->sum : 0;
    }

    public function getRevisionsNumber($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts_revisions';

        $ret = Db::query(
            [
                'SELECT COALESCE( MAX("rev_no"), 0 )  AS "rev_no" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid,
                ],

            ],
            Db::FETCH_OBJ
        );

        return isset($ret->rev_no) ? $ret->rev_no : 0;
    }

    public function getRevision($hpid, $number,  $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts_revisions';

        return Db::query(
            [
                'SELECT message, EXTRACT(EPOCH FROM "time") AS time FROM "'.$table.'" WHERE "hpid" = :hpid AND "rev_no" = :number',
                [

                    ':hpid' => $hpid,
                    ':number' => $number,
                ],

            ],
            Db::FETCH_OBJ
        );
    }

    public function getUserThumb($hpid, $project = false)
    {
        if (!$this->user->isLogged()) {
            return 0;
        }
        $table = $project ? 'groups_thumbs' : 'thumbs';

        $ret = Db::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :from',
                [
                    ':hpid' => $hpid,
                    ':from' => $_SESSION['id'],
                ],

            ],
            Db::FETCH_OBJ
        );

        if (isset($ret->vote)) {
            return $ret->vote;
        }

        return 0;
    }

    public function setThumbs($hpid, $vote, $project = false)
    {
        if (!$this->user->isLogged()) {
            return Utils::$REGISTER_DB_MESSAGE;
        }

        $table = ($project ? 'groups_' : '').'thumbs';

        return Db::query(
            [
                'INSERT INTO '.$table.'(hpid, "from", vote) VALUES(:hpid, :from, :vote)',
                [
                    ':hpid' => (int) $hpid,
                    ':from' => (int) $_SESSION['id'],
                    ':vote' => (int) $vote,
                ],
            ],
            Db::FETCH_ERRSTR
        );
    }

    private function parseCode(&$codes, $str, $type = null, $pid = null, $id = null)
    {
        $i = 1;
        foreach ($codes as $code) {
            $totalcode = $code['code'];
            $lang = $code['lang'];
            $tag = $code['tag'];
            $codeurl = '';

            if ($pid && $id) {
                if (isset($type)) {
                    $codeurl = '/getcode.php?';
                    if ($type == 'g') { //gid, group, project
                        $codeurl .= 'g';
                    } elseif ($type == 'pc') { //pcid, ppstccomment
                        $codeurl .= 'pc';
                    } elseif ($type == 'gc') {
                        //gcid group comment
                        $codeurl .= 'gc';
                    }
                    //else il nulla, id, profile
                    $codeurl .= "id={$id}&amp;".(in_array($type, array('pc', 'gc')) ? '' : "pid={$pid}&amp;")."ncode={$i}";
                }
            } else {
                $pid = $id = 0;
            }

            if ($tag == 'c') { // short
                $str = str_ireplace("[c={$lang}]{$totalcode}[/c]",
                    '<span class="nerdz-code-wrapper" title="'.$lang.'"><code class="prettyprint lang-'.$lang.'" style="border:0px; word-wrap: break-word">'.
                    str_replace("\n", '<br />',
                        str_replace(' ', '&nbsp;',
                        str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $totalcode))).'</code></span>', $str);
            } else { // long
                $str = str_ireplace("[code={$lang}]{$totalcode}[/code]",
                    '<div class="nerdz-code-wrapper">
                    <div class="nerdz-code-title">'.$lang.':'.(empty($codeurl)
                    ? ''
                    : '<a href="'.$codeurl.'" onclick="window.open(this.href); return false" class="nerdz-code-text-version">'.
                    $this->user->lang('TEXT_VERSION').'</a>'
                ).
                '</div><code class="prettyprint lang-'.$lang.'" style="border:0px; overflow-x:auto; word-wrap: normal; display:block">'.
                str_replace("\n", '<br />',
                    str_replace(' ', '&nbsp;',
                    str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $totalcode))).'</code></div>', $str);
                ++$i;
            }
        }

        return $str;
    }

    public function getPost($dbPost, $options = [])
    {
        extract($options);
        $project = !empty($project);
        $truncate = !empty($truncate);
        $inHome = !empty($inHome);

        if (is_object($dbPost)) {
            $dbPost = (array) $dbPost;
        } elseif (is_numeric($dbPost)) {
            //hpid

            if ($inHome) {
                $table = 'messages';
            } else {
                $table = ($project ? 'groups_' : '').'posts';
            }

            if (!($o = Db::query(
                [
                    'SELECT p.*, EXTRACT(EPOCH FROM p."time") AS time FROM "'.$table.'" p WHERE p."hpid" = :hpid'.($inHome ? ' AND p.type = '.($project ? '0' : '1') : ''),
                    [
                        ':hpid' => $dbPost,
                    ],
                ], Db::FETCH_OBJ))
            ) {
                return new \StdClass();
            }
            $dbPost = (array) $o;
        }

        $logged = $this->user->isLogged();

        if (!($from = User::getUsername($dbPost['from']))) {
            $from = '';
        }

        $toFunc = $project
            ? [__NAMESPACE__.'\\Project', 'getName']
            : [__NAMESPACE__.'\\User', 'getUsername'];

        $toFuncLink = [__NAMESPACE__.'\\Utils', ($project ? 'project' : 'user').'Link'];

        if (!($to = $toFunc($dbPost['to']))) {
            $to = '';
        }

        $ret = [];
        $ret['thumbs_n'] = $this->getThumbs($dbPost['hpid'], $project);
        $ret['revisions_n'] = $this->getRevisionsNumber($dbPost['hpid'], $project);
        $ret['uthumb_n'] = $this->getUserThumb($dbPost['hpid'], $project);
        $ret['pid_n'] = $dbPost['pid'];
        $ret['news_b'] = $dbPost['news'];
        $ret['language_n'] = $dbPost['lang'];
        $ret['from4link_n'] = Utils::userLink($from);
        $ret['to4link_n'] = $toFuncLink($to);
        $ret['fromid_n'] = $dbPost['from'];
        $ret['toid_n'] = $dbPost['to'];
        $ret['fromgravatarurl_n'] = $this->user->getGravatar($dbPost['from']);
        $ret['togravatarurl_n'] = $this->user->getGravatar($dbPost['to']);
        $ret['from_n'] = $from;
        $ret['to_n'] = $to;
        $ret['date_n'] = $this->user->getDate($dbPost['time']);
        $ret['time_n'] = $this->user->getTime($dbPost['time']);
        $ret['timestamp_n'] = $dbPost['time'];

        $ret['canclosepost_b'] = $this->canClose($dbPost, $project);
        $ret['closed_b'] = $dbPost['closed'];
        $ret['canremovepost_b'] = $this->canRemove($dbPost, $project);
        $ret['caneditpost_b'] = $this->canEdit($dbPost, $project);
        $ret['canshowlock_b'] = $this->canShowLock($dbPost, $project);
        $ret['lock_b'] = $this->user->hasLocked($dbPost, $project);

        $ret['canshowlurk_b'] = $logged ? !$ret['canshowlock_b'] : false;
        $ret['lurk_b'] = $this->user->hasLurked($dbPost, $project);

        $ret['canshowbookmark_b'] = $logged;
        $ret['bookmark_b'] = $this->user->hasBookmarked($dbPost, $project);

        $ret['message_n'] = $this->bbcode($dbPost['message'], $truncate, $project ? 'g' : 'u', $ret['pid_n'], $ret['toid_n']);
        if (!$project && $dbPost['to'] == Config\USERS_NEWS) {
            $ret['message_n'] = $this->parseNews($ret['message_n']);
        }
        $ret['postcomments_n'] = $this->countComments($dbPost['hpid'], $project);
        $ret['hpid_n'] = $dbPost['hpid'];
        $ret['type_n'] = $inHome ? ($dbPost['type'] == '0' ? 'project' : 'profile') : ($project ? 'project' : 'profile');

        return $ret;
    }

    public function countComments($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'comments';
        if ($this->user->isLogged()) {
            if (!($o = Db::query(
                [
                    'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id)'.
                    (
                        $project
                        ? ''
                        : ' AND "to" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id)'
                    ),
                    [
                        ':hpid' => $hpid,
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_OBJ))
            ) {
                return 0;
            }
        } else {
            if (!($o = Db::query(
                [
                    'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid',
                    [
                        ':hpid' => $hpid,
                    ],
                ], Db::FETCH_OBJ))
            ) {
                return 0;
            }
        }

        return $o->cc;
    }
}
