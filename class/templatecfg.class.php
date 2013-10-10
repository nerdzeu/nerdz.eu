<?php
/*
 * Classe per la gestione dei template in senso stretto.
 * Si occupa di ottenere le variabili obbligatorie e facoltative dei template.
 * In sostanza, si occupa del parsing dei files /tpl/<tpl no>/(mandatory|template).values
 * Inoltre, gestisce la minimizzazione e la compressione dei file css e javascript che i template useranno
 * (file che sono in template.values)
 * 
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

final class templateCfg extends phpCore
{
    private $tpl_no;

    public function __construct()
    {
        parent::__construct();
        $this->tpl_no = parent::getTemplate();
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
            //suppress warning because sometimes, acp_store raise a warning only to say how long the value spent n cache
            //according to stackoverflow: [ http://stackoverflow.com/questions/6937528/apc-how-to-handle-gc-cache-warnings ] this can be safetly be ignored
            @apc_store($cachevaluestime,serialize($valuestime),3600);
        }

        if($control)
        {
            $newtime = filemtime($templatepath);
            if($newtime != $valuestime)
            {
                apc_delete($cachename);
                //suppress warning because sometimes, acp_store raise a warning only to say how long the value spent n cache
                //according to stackoverflow: [ http://stackoverflow.com/questions/6937528/apc-how-to-handle-gc-cache-warnings ] this can be safetly be ignored
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
                $ret = array ( 'js' => array(), 'css' => array() );
            //suppress warning because sometimes, acp_store raise a warning only to say how long the value spent n cache
            //according to stackoverflow: [ http://stackoverflow.com/questions/6937528/apc-how-to-handle-gc-cache-warnings ] this can be safetly be ignored
            @apc_store($cachename,serialize($ret),3600); //1h
        }

        if($page != null)
            foreach($ret as $pid => &$ff)
                foreach($ff as $id => &$val)
                    if(($id != $page) && ($id != 'default'))
                        unset($ret[$pid][$id]);
                        
        if(count($ret)<2)
            if(isset($ret['js']))
                $ret['css'] = array();
            elseif(isset($ret['css']))
                $ret['js'] = array();
            else
                $ret['js'] = $ret['css'] = array();
            
        //controllo per le modifiche ai file
        foreach($ret as $id => &$arr)
            foreach($arr as &$path)
                if(!parent::isValidURL($path))
                {
                    $userfile = "{$_SERVER['DOCUMENT_ROOT']}/tpl/{$this->tpl_no}/{$path}";
                    if(!file_exists($userfile))
                    {
                        unset($path);
                        continue;
                    }
                    if (!MINIFICATION_ENABLED)
                        continue;
                    
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
        return $ret;
    }
}

?>
