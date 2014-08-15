<?php
namespace NERDZ\Core;
/*
 * Classe per la gestione dei template in senso stretto.
 * Si occupa di ottenere le variabili obbligatorie e facoltative dei template.
 * In sostanza, si occupa del parsing dei files /tpl/<tpl no>/template.values
 * Inoltre, gestisce la minimizzazione e la compressione dei file css e javascript che i template useranno
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

final class TemplateConfig
{
    private $tpl_no;
    private $lang;
    private $tpl;
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
        $this->tpl = $this->user->getTPL();
        $this->lang = $this->user->getBoardLanguage();
        $this->tpl_no = $this->tpl->getActualTemplateNumber();
    }

    public function getTemplateVars($page = null)
    {
        $templatepath = $_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/template.values";
        $cachename = "tpl-{$this->tpl_no}-vars".Config\SITE_HOST;
        $cachevaluestime = "tpl-{$this->tpl_no}-values-time".Config\SITE_HOST;

        $control = false;

        if(apc_exists($cachevaluestime))
        {
            $valuestime = unserialize(apc_fetch($cachevaluestime));
            $control = true;
        }
        else
        {
            $valuestime = filemtime($templatepath);
            @apc_store($cachevaluestime,serialize($valuestime),3600);
        }

        if($control)
        {
            $newtime = filemtime($templatepath);
            if($newtime != $valuestime)
            {
                apc_delete($cachename);
                @apc_store($cachevaluestime,serialize($newtime),3600);
            }
        }

        if(apc_exists($cachename))
            $ret = unserialize(apc_fetch($cachename));
        else
        {
            if(!($txt = file_get_contents($templatepath)))
                return [];
            // thanks for the following regexp to 1franck (http://it2.php.net/manual/en/function.json-decode.php#111551)
            $ret = json_decode (preg_replace ('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#', '', $txt), true);
            if (!is_array ($ret))
                $ret = array ( 'js' => [], 'css' => [] , 'langs' => []);
            @apc_store($cachename,serialize($ret),3600); //1h
        }

        if($page != null)
            foreach($ret as $pid => &$ff)
                foreach($ff as $id => &$val)
                    if(($id != $page) && ($id != 'default'))
                        unset($ret[$pid][$id]);

        if(count($ret)<3)
            if(isset($ret['js']))
                $ret['css'] = [];
            elseif(isset($ret['css']))
                $ret['js'] = [];
            elseif(isset($ret['langs']))
                $ret['langs'] = [];
            else
                $ret['js'] = $ret['css'] = $ret['langs'] = [];

        //control for css/js file modification and substitution of %lang% with selected language
        foreach($ret as $id => &$arr)
            if($id != 'langs')
            {
                $workArr = $arr;
                foreach($workArr as $nestedID => &$path)
                {
                    if(is_array ($path)) //Now is possible to use array to include more than 1 file (eg "default": ["js/default.js", "http://cdn.jslibrary", "js/otherdefile.js"]
                    {
                        foreach($path as &$value)
                        {
                            if (!is_array ($value))
                            {
                                $this->validatePath($value,$id);
                                if (isset ($value))
                                    $ret[$id][] = $value;
                            }
                            else
                            {
                                if (isset ($ret[$id]['staticData']))
                                    $ret[$id]['staticData'] = array_merge_recursive ($ret[$id]['staticData'], $value);
                                else
                                    $ret[$id]['staticData'] = $value;
                            }
                        }
                        unset ($ret[$id][$nestedID]);
                    }
                    else
                    {
                        $this->validatePath($path,$id);
                        if (isset ($path))
                            $ret[$id][$nestedID] = $path;
                    }
                }
                // this checks if there is $ret[$id]['staticData'] AND if we are not being
                // called from the lang() function. this IS necessary because if the language cache
                // is being rebuilt by calling getTemplateVars this is going to cause an infinite loop
                // and will cause explosions. To get the calling function I used debug_backtrace
                // which returns the latest callers of any function. This should NOT be slow
                // since it is called once per section and ONLY when there is a staticData section.
                // Practically this is called ONE time on each invocation.
                if (isset ($ret[$id]['staticData']['lang']) && is_array ($ret[$id]['staticData']['lang']))
                {
                    $Dbg = debug_backtrace (DEBUG_BACKTRACE_IGNORE_ARGS);
                    if (isset ($Dbg[1]['function']) && $Dbg[1]['function'] === 'lang') continue;
                    foreach ($ret[$id]['staticData']['lang'] as $key => $entry)
                    {
                        if (!is_numeric ($key)) continue;
                        $ret[$id]['staticData']['lang'][$entry] = $this->user->lang ($entry);
                        unset ($ret[$id]['staticData']['lang'][$key]);
                    }
                }
                //print_r($ret[$id]); // * debug *
            }
            else //id == langs
                foreach($arr as &$langFile)
                    $langFile = str_replace('%lang%',$this->lang,$langFile);
        return $ret;
    }

    private function validatePath(&$path,$id)
    {
        if(!Utils::isValidURL($path))
        {
            $userfile = "{$_SERVER['DOCUMENT_ROOT']}/tpl/{$this->tpl_no}/{$path}";
            if(!file_exists($userfile))
            {
                unset($path);
                return;
            }
            if (!Config\MINIFICATION_ENABLED)
            {
                $path .= '?'.filemtime($userfile); //force cache refresh if file is changed
                return;
            }

            $userfiletime = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.md5($path).$this->tpl_no.$id;
            $ext = "min.{$id}";

            $updateTime = 0;

            if(!file_exists ($userfiletime) || // intval won't get called if the file doesn't exist
                intval(file_get_contents($userfiletime)) < ($updateTime = filemtime($userfile)))
            {
                Minification::minifyTemplateFile ($userfiletime, $userfile, $userfile . $ext, $id);
                $updateTime = time();
            }

            $path.=$ext."?$updateTime"; // append min.ext to file path, and add ?time, to force cache refresh if file is changed
        }

    }
}

?>
