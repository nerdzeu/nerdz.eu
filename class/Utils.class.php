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

class Utils
{
    public static $REGISTER_DB_MESSAGE = ['error', 'REGISTER'];
    public static $ERROR_DB_MESSAGE = ['error', 'ERRROR'];

    public static function apcu_getLastModified($key)
    {
        $cache = apcu_cache_info('user');

        if (isset($cache['start_time'])) {
            return $cache['start_time'];
        }

        return 0;
    }

    public static function apcu_get($key)
    {
        if (apcu_exists($key)) {
            return unserialize(apcu_fetch($key));
        }

        return;
    }

    public static function apcu_set($key, callable $setter, $ttl)
    {
        $ret = $setter();
        if (Config\MINIFICATION_ENABLED) {
            @apcu_store($key, serialize($ret), $ttl);
        }

        return $ret;
    }

    public static function isValidURL($url)
    {
        // FILTER_VALIDATE_URL is not protocol agnostic
        if (strpos($url, '//') === 0) {
            $value = 'http:'.$url;
        } else {
            $value = $url;
        }

        return (strpos($value, "http://") === 0 || strpos($value, "https://") === 0) &&
            filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public static function getValidImageURL($url)
    {
        $url = strip_tags(trim($url));
        $domain = System::getResourceDomain();

        if (!static::isValidURL($url)) {
            return $domain.'/static/images/invalidImgUrl.php';
        }

        // Proxy every image that's not in data\trusted-host.json
        $cache = 'nerdz_trusted'.Config\SITE_HOST;
        if (!($trusted_hosts = self::apcu_get($cache))) {
            $trusted_hosts = self::apcu_set($cache, function () {
                $txt = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/trusted-hosts.json');

                return json_decode(preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#', '', $txt), true);
            }, 86400);
        }

        // Avoid IP address (and other user info) spoofing
        $urlInfo = parse_url($url);
        foreach ($trusted_hosts as $host) {
            if (preg_match($host['regex'], $urlInfo['host'])) {
                if ($urlInfo['scheme'] !== 'https') {
                    $count = 1; // str_replace wants the count parameter passed by referece (mfw)
                    return str_replace('http', 'https', $url, $count);
                }

                return $url;
            }
        }
        // If here, host is not a trusted host
        return Config\CAMO_KEY === '' || Config\MEDIA_HOST === ''
            ? 'https://i0.wp.com/'.preg_replace('#^https?://|^ftp://#i', '', $url)
            : 'https://'.Config\MEDIA_HOST.'/camo/'.static::getHMAC($url, Config\CAMO_KEY).'?url='.urlencode($url);
    }

    public static function getHMAC($message, $key)
    {
        return hash_hmac('sha1', $message, $key);
    }

    private static function getLink($name)
    {
        return str_replace(' ', '+', urlencode(html_entity_decode($name, ENT_QUOTES, 'UTF-8')));
    }

    public static function userLink($user)
    {
        return self::getLink($user).'.';
    }

    public static function projectLink($name)
    {
        return self::getLink($name).':';
    }

    public static function minifyHTML($str)
    {
        return Config\MINIFICATION_ENABLED
            ? preg_replace('#>\s+<#', '> <', preg_replace('#^\s+|\s+$|\n#m', '', $str))
            : $str;
    }

    public static function toJsonResponse($status, $message = '')
    {
        $ret = is_array($status) ? $status : ['status' => $status, 'message' => $message];

        return json_encode($ret);
    }

    public static function JSONResponse($status, $message = '')
    {
        header('Content-type: application/json; charset=utf-8');

        return static::toJsonResponse($status, $message);
    }

    public static function jsonDbResponse($msg, $otherInfo = '')
    {
        $user = new User();
        $res = $user->parseDbMessage($msg, $otherInfo);

        return static::JSONResponse($res[0], $res[1]);
    }

    public static function getSiteName()
    {
        return Config\SITE_NAME.(User::isOnMobileHost() ? 'Mobile' : '');
    }

    public static function sortByUsername($a, $b)
    {
        return (strtolower($a['username_n']) < strtolower($b['username_n'])) ? -1 : 1;
    }

    public static function actionValidator($action)
    {
        return in_array($action, ['friends', 'followers', 'following', 'interactions', 'members'])
            ? $action
            : false;
    }

    public static function in_arrayi($needle, $haystack)
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

    // for startsWith & endsWith thanks https://stackoverflow.com/a/10473026/2891324

    public static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}
