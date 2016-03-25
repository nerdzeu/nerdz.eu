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
/*
 * Classe per la gestione dei Banners
 */

namespace NERDZ\Core;

final class Banners
{
    private $banners;

    public function __construct()
    {
        $cache = 'bannerarray'.Config\SITE_HOST;

        if(!($this->banners = Utils::apc_get($cache)))
            $this->banners = Utils::apc_set($cache, function() {
                $path = $_SERVER['DOCUMENT_ROOT'].'/data/banner.list';
                $banners = [];

                if(file_exists($path) && ($arr = file ($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
                {
                    foreach ($arr as $line)
                    {
                        $elms = explode ('.', $line, 3);
                        $banners[] = array ($elms[0], $elms[1], $elms[2]);
                    }
                }

                return $banners;
            }, 7200);
    }

    public function getBanners($formato = null,$fornitore = null,$limit = 0)
    {
        $ret = [];

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
