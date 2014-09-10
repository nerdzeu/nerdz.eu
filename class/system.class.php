<?php
namespace NERDZ\Core;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class System
{
    public static function getSafeCookieDomainName()
    {
        // use a simple algorithm to determine the common parts between
        // Config\MOBILE_HOST and Config\SITE_HOST.
        $mobile_host = explode ('.', Config\MOBILE_HOST);
        $site_host   = explode ('.', Config\SITE_HOST);
        $chost       = [];
        for ($i = 0; $i < min (count ($site_host), count ($mobile_host)); $i++)
        {
            $sh_k = count ($site_host)   - $i;
            $mh_k = count ($mobile_host) - $i;
            if (isset ($site_host[--$sh_k]) && isset ($mobile_host[--$mh_k]) && $site_host[$sh_k] == $mobile_host[$mh_k])
                array_unshift ($chost, $site_host[$sh_k]);
            else
                break;
        }
        // accept at least a domain with one dot (x.y), because
        // chrome does not accept point-less (heh) domains for cookie usage.
        // this also handles localhost.
        return count ($chost) > 1 ? implode ('.', $chost) : null;
    }

    public static function getResourceDomain()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'
            ? 'https://'.Config\SITE_HOST
            : Config\STATIC_DOMAIN;
    }

    public static function getAvailableLanguages($long = null)
    {
        $cache = 'AvailableLanguages'.Config\SITE_HOST;
        if(!($ret = Utils::apc_get($cache)))
            $ret = Utils::apc_set($cache, function() {
                //on error return en
                if(!($fp = fopen($_SERVER['DOCUMENT_ROOT'].'/data/languages.csv','r')))
                    return [ 'en' => 'English' ];

                $ret = [];
                while(false !== ($row = fgetcsv($fp)))
                    $ret[$row[0]] = htmlspecialchars($row[1],ENT_QUOTES,'UTF-8');
                
                fclose($fp);
                ksort($ret);
                return $ret;
            }, 3600);

        return $long ? $ret : array_keys($ret);
    }

    private static function getAcceptLanguagePreference()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $langs = [];
            $lang_parse = [];

            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

            if (!empty($lang_parse[1]))
            {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val)
                    if (empty($val))
                        $langs[$lang] = 1;
             }
            // sort list based on value
            arsort($langs, SORT_NUMERIC);
            return $langs;
        }

        return ['en' => 1]; //english on error/default
    }

    public static function getBrowserLanguage()
    {
        $langpref = static::getAcceptLanguagePreference();
        $avail    = static::getAvailableLanguages();

        foreach($langpref as $lang => $val)
            foreach($avail as $av)
                if(strpos($lang,$av) !== false)
                    return $av;

        return 'en'; // should never reach this line
    }

    public static function getAvailableTemplates()
    {
        $root = $_SERVER['DOCUMENT_ROOT'].'/tpl/';
        $templates = array_diff(scandir($root), [ '.','..','index.html' ]);
        $ret = [];
        $i = 0;
        foreach($templates as $val) {
            $ret[$i]['number'] = $val;
            $ret[$i]['name'] = file_get_contents($root.$val.'/NAME');
            ++$i;
        }
        return $ret;
    }

    public static function dumpError($string)
    {
        $path = $_SERVER['DOCUMENT_ROOT'].'/data/error.log';
        file_put_contents($path,date('d-m-Y H:i').": {$string}\n", FILE_APPEND);
        chmod($path,0755);
    }
}
