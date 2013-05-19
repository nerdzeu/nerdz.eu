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
			if(($txt = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/banner.list')))
			{
				$a = explode("\n",$txt);
				$tot = count($a);
				$tmp = array();
				for($i=0;$i<$tot;++$i)
				{
					if(empty($a[$i]))
						continue;

					$tmp = preg_split('#([a-z0-9\.]+) =(.+?)#',$a[$i],null,PREG_SPLIT_DELIM_CAPTURE);

					list($fornitore, $formato) = explode('.',$tmp[1]);
					$banner = $tmp[3];
					$this->banners[] = array($fornitore,$formato,$banner);
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
