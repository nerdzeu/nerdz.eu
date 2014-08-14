<?php
/*
 * Classe per la gestione dei Banners
 */

namespace NERDZ\Core;

final class Banners
{
    private $Banners;

    public function __construct()
    {
        $this->Banners = [];

        $cache = 'bannerarray'.Config\SITE_HOST;
        if(apc_exists($cache))
            $this->Banners = unserialize(apc_fetch($cache));
        else
        {
            $path = $_SERVER['DOCUMENT_ROOT'].'/data/banner.list';
            if(file_exists($path) && ($arr = file ($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
            {
                foreach ($arr as $line)
                {
                    $elms = explode ('.', $line, 3);
                    $this->Banners[] = array ($elms[0], $elms[1], $elms[2]);
                }
                @apc_store($cache,serialize($this->Banners),7200);
            }
        }
    }

    public function getBanners($formato = null,$fornitore = null,$limit = 0)
    {
        $ret = [];

        if(is_string($formato))
        {
            foreach($this->Banners as $a)
                if($a[1] == $formato)
                    $ret[] = $a;
        }
        elseif(is_string($fornitore))
        {
            foreach($this->Banners as $a)
                if($a[0] == $fornitore)
                    $ret[] = $a;
        }
        else
            $ret = $this->Banners;

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
