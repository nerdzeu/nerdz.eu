<?php
/*
 * Classe per la gestione dei banners
 */

final class banners
{
	private $banners;

	public function __construct()
	{
		$this->banners = array();
		
		$cache = 'bannerarray'.SITE_HOST;
		if(apc_exists($cache))
		   $this->banners = unserialize(apc_fetch($cache));
		else
		{	
			if(($arr = file ($_SERVER['DOCUMENT_ROOT'].'/data/banner.list', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
			{
				foreach ($arr as $line)
				{
					$elms = explode ('.', $line, 3);
					$this->banners[] = array ($elms[0], $elms[1], $elms[2]);
				}
				apc_store($cache,serialize($this->banners),7200);
			}
		}
	}

	public function getBanners($formato = null,$fornitore = null,$limit = 0)
	{
		$ret = array();

		if(is_string($formato))
		{
			foreach($this->banners as $a)
				if($a[1] == $formato)
					$ret[] = $a;
		}
		elseif(is_string($fornitore))
		{
			foreach($this->banners as $a)
				if($a[0] == $fornitore)
					$ret[] = $a;
		}
		else
			$ret = $this->banners;

		if($limit)
		{
			$c = count($ret);
			while($c>$limit)
				unset($ret[--$c]);
		}

		return $ret;
	}
}
?>
