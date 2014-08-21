<?php
namespace NERDZ\Core;

class Utils
{
    public static function apc_getLastModified($key)
    {
        $cache = apc_cache_info('user');

        if (empty($cache['cache_list']))
            return false;

        foreach($cache['cache_list'] as $entry)
        {
            if($entry['key'] != $key)
                continue;

            return $entry['ctime'];
        }
    }

    public static function isValidURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function getValidImageURL($url, $domain, $sslEnabled)
    {
        $url = strip_tags(trim($url));
        if (!static::isValidURL($url))
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


    public static function userLink($user)
    {
        return str_replace(' ','+',urlencode(html_entity_decode($user,ENT_QUOTES,'UTF-8'))).'.';
    }

    public static function projectLink($name)
    {
        return str_replace(' ','+',urlencode(html_entity_decode($name,ENT_QUOTES,'UTF-8'))).':';
    }

    public static function minifyHTML($str)
    {
        $str = explode("\n",$str);
        foreach($str as &$val)
            $val = trim(str_replace("\t",'',$val));

        return implode('',$str);
    }

    public static function getVersion()
    {
        $cache = 'NERDZVersion'.Config\SITE_HOST;

        if (apc_exists ($cache))
            return apc_fetch ($cache);

        if (!is_dir ($_SERVER['DOCUMENT_ROOT'] . '/.git') ||
            !file_exists ($_SERVER['DOCUMENT_ROOT'] . '/.git/refs/heads/master'))
            return 'null';

        $revision = substr (file_get_contents ($_SERVER['DOCUMENT_ROOT'] . '/.git/refs/heads/master'), 0, 7);
        if (strlen ($revision) != 7)
            return 'null';

        @apc_store ($cache, $revision, 5400); // store the version for 1.5 hours
        return $revision;
    }

    public static function toJsonResponse($status, $message)
    {
        $ret = is_array($status) ? $status : ['status' => $status, 'message' => $message];
        return json_encode($ret,JSON_FORCE_OBJECT);
    }

    public static function jsonResponse($status, $message = '')
    {
        header('Content-type: application/json; charset=utf-8');
        return static::toJsonResponse($status, $message);
    }

    public static function jsonDbResponse($msg, $otherInfo = '')
    {
        $user = new User();
        $res = $user->parseDbMessage($msg, $otherInfo);
        return static::jsonResponse($res[0], $res[1]);
    }

    public static function getSiteName()
    {
        return Config\SITE_NAME.( User::isOnMobileHost() ? 'Mobile' : '' );
    }

    public static function sortByUsername($a, $b)
    {
        return (strtolower($a['username_n']) < strtolower($b['username_n'])) ? -1 : 1;
    }

    public static function actionValidator($action)
    {
        return in_array($action, [ 'friends', 'followers', 'following', 'interactions' ])
            ? $action
            : false;
    }
}
?>
