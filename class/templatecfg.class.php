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

	//template values, not mandatory constants (sections)
	const CSS_SECTION = 'css:';
	const JS_SECTION  = 'javascript:';

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
			apc_store($cachevaluestime,serialize($valuestime),3600);
		}

		if($control)
		{
			$newtime = filemtime($templatepath);
			if($newtime != $valuestime)
			{
				apc_delete($cachename);
				apc_store($cachevaluestime,serialize($newtime),3600);
			}
		}

		if(apc_exists($cachename))
			$ret = unserialize(apc_fetch($cachename));
		else
		{
			if(!($txt = file_get_contents($templatepath)))
				return array();

			$a = explode("\n",$txt);
			$tot = count($a);

			$ret = array();
			$i = 0;
			$sections = array(self::JS_SECTION,self::CSS_SECTION);

			while(!in_array($a[$i],$sections) && $i++ < $tot)
				;

			if($i == $tot)
				return array();

			$js = $css = array();

			if($a[$i] == self::JS_SECTION)
			{
				while($i < $tot && $a[$i] != self::CSS_SECTION)
				{
					++$i;
					if(!empty($a[$i]) && $a[$i][0] != '#')
						$js[] = $a[$i];
					else
						++$i;
				}
				
				while($i < $tot)
				{
					++$i;
					if(!empty($a[$i]) && $a[$i][0] != '#')
						$css[] = $a[$i];
					else
						++$i;
				}
			}

			if($a[$i] == self::CSS_SECTION)
			{
				
				while($i<$tot && $a[$i] != self::JS_SECTION)
				{
					++$i;
					if(!empty($a[$i]) && $a[$i][0] != '#')
						$css[] = $a[$i];
					else
						++$i;
				}
				
				while($i<$tot)
				{
					++$i;
					if(!empty($a[$i]) && $a[$i][0] != '#')
						$js[] = $a[$i];
					else
						++$i;
				}
			}

			$list = array($js,$css);
			foreach($list as $type)
			{
				$id = $type == $js ? 'js' : 'css';

				foreach($type as $j)
				{
					$tmp = explode(':',trim($j),2);
					foreach($tmp as &$val)
						$val = trim($val);
					
					if(empty($tmp[1]))
						continue;
		
					$c = 0;
					while(isset($tmp[1][$c]) && ($tmp[1][$c] != ';'))
						++$c;
		
					if($c == (strlen($tmp[1])-1))
						$ret[$id][$tmp[0]] = substr($tmp[1],0,$c);
					else
						if(isset($tmp[1][$c]) && ($tmp[1][$c] == ';'))
							$ret[$id][$tmp[0]] = substr($tmp[1],0,$c);
				}
			}

			apc_store($cachename,serialize($ret),3600); //1h

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
					$userfile = $_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/{$path}";
					if(!file_exists($userfile))
					{
						unset($path);
						continue;
					}
					
					$userfiletime = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.md5($path).$this->tpl_no.$id;
					$ext = "min.{$id}";
			
					if(!file_exists($userfiletime))
					{
						require_once $_SERVER['DOCUMENT_ROOT'].'/class/javascript.class.php';
						require_once $_SERVER['DOCUMENT_ROOT'].'/class/css.class.php';
						file_put_contents($userfiletime,filemtime($userfile));
						chmod($userfiletime,0775);
						$realfile = $userfile.$ext;
						file_put_contents($realfile,$id == 'js' ? Javascript::optimize($userfile) : Css::optimize($userfile));
						chmod($realfile,0775);
					}
					else
						if(intval(file_get_contents($userfiletime)) < filemtime($userfile))
						{
							require_once $_SERVER['DOCUMENT_ROOT'].'/class/javascript.class.php';
							require_once $_SERVER['DOCUMENT_ROOT'].'/class/css.class.php';
							$realfile = $userfile.$ext;
							file_put_contents($realfile,$id == 'js' ? Javascript::optimize($userfile) : Css::optimize($userfile));
							chmod($realfile,0775);
							file_put_contents($userfiletime,filemtime($userfile));
							chmod($userfiletime,0775);
						}
					
					$path.=$ext;
				}
		return $ret;
	}
}

?>
