<?php
namespace NERDZ\Core;

class Utils
{
    public static $REGISTER_DB_MESSAGE = [ 'error', 'REGISTER' ];
    public static $ERROR_DB_MESSAGE    = [ 'error', 'ERRROR' ];

    public static function apc_getLastModified($key)
    {
        $cache = apc_cache_info('user');

        if (empty($cache['cache_list']))
            return false;

        foreach($cache['cache_list'] as $entry)
        {
            if($entry['info'] != $key)
                continue;

            return $entry['creation_time'];
        }
    }

    public static function apc_get($key)
    {
        if(apc_exists($key))
            return unserialize(apc_fetch($key));
        return null;
    }

    public static function apc_set($key, callable $setter, $ttl)
    {
        $ret = $setter();
        @apc_store ($key, serialize($ret), $ttl);
        return $ret;
    }

    public static function isValidURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function getValidImageURL($url)
    {
        $url        = strip_tags(trim($url));
        $sslEnabled = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        $domain     = System::getResourceDomain();

        if (!static::isValidURL($url))
            return $domain.'/static/images/invalidImgUrl.php';

        if($sslEnabled) {
            // valid ssl url
            if(preg_match('#^https://#i',$url))
                return $url;

            // imgur without ssl
            if(preg_match("#^http://(www\.)?(i\.)?imgur\.com/[a-z0-9]+\..{3}$#i",$url)) {
                return preg_replace_callback("#^http://(?:www\.)?(?:i\.)?imgur\.com/([a-z0-9]+\..{3})$#i", function($matches) {
                    return 'https://i.imgur.com/'.$matches[1];
                },$url);
            }

            // url hosted on a non ssl host - use camo or our trusted proxy
            return Config\CAMO_KEY == ''
                ? 'https://i0.wp.com/' . preg_replace ('#^http://|^ftp://#i', '', $url)
                : $domain.'/secure/image/'.hash_hmac('sha1', $url, Config\CAMO_KEY).'?url='.urlencode($url);
        }
        return $url;
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
        return preg_replace('#>\s+<#','><',preg_replace('#^\s+|\s+$|\n#m','',$str));
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
        return in_array($action, [ 'friends', 'followers', 'following', 'interactions', 'members' ])
            ? $action
            : false;
    }

    public static function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
}
