<?php
/*
 * Classe per la gestione dei template in senso stretto.
 * Si occupa di ottenere le variabili obbligatorie e facoltative dei template.
 * In sostanza, si occupa del parsing dei files /tpl/<tpl no>/template.values
 * Inoltre, gestisce la minimizzazione e la compressione dei file css e javascript che i template useranno
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/class/raintpl.class.php';

final class templateCfg
{
    private $tpl_no;
    private $lang;
    private $tpl;
    private $phpCore;

    public function __construct($phpCore)
    {
        $this->phpCore = $phpCore;
        $this->tpl = $this->phpCore->getTPL();
        $this->lang = $this->phpCore->isLogged() ? $this->phpCore->getBoardLanguage($_SESSION['nerdz_id']) : $this->phpCore->getBrowserLanguage();
        $this->tpl_no = $this->tpl->getActualTemplateNumber();
    }

    public function getTemplateVars($page = null)
    {
        $templatepath = $_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/template.values";
        $cachename = "tpl-{$this->tpl_no}-vars".SITE_HOST;
        $cachevaluestime = "tpl-{$this->tpl_no}-values-time".SITE_HOST;

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
                return array();
            // thanks for the following regexp to 1franck (http://là.pw/pb)
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
                foreach($arr as $nestedID => &$path)
                {
                    if(is_array($path)) //Now is possible to use array to include more than 1 file (eg "default": "js/default.js", "http://cdn.jslibrary", "js/otherdefile.js"
                    {
                        foreach($path as &$value)
                        {
                            $this->validatePath($value);
                            array_push($ret[$id],$value);
                        }
                        unset($ret[$id][$nestedID]);
                    }
                    else
                        $this->validatePath($value);
                }
            else //id == langs
                foreach($arr as &$langFile)
                    $langFile = str_replace('%lang%',$this->lang,$langFile);
        
        return $ret;
    }

    private function validatePath(&$path)
    {
        if(!phpCore::isValidURL($path))
        {
            $userfile = "{$_SERVER['DOCUMENT_ROOT']}/tpl/{$this->tpl_no}/{$path}";
            if(!file_exists($userfile))
            {
                unset($path);
                return;
            }
            if (!MINIFICATION_ENABLED)
                return;
            
            $userfiletime = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.md5($path).$this->tpl_no.$id;
            $ext = "min.{$id}";

            if(!file_exists ($userfiletime) || // intval won't get called if the file doesn't exist
               intval(file_get_contents($userfiletime)) < filemtime($userfile))
            {
                require_once $_SERVER['DOCUMENT_ROOT'].'/class/minification.class.php';
                Minification::minifyTemplateFile ($userfiletime, $userfile, $userfile . $ext, $id);
            }
            
            $path.=$ext; // append min.ext to file path
        }

    }
}

?>
