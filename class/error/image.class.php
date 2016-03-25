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
namespace NERDZ\Core\Error;

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;

final class Image
{
    private $user;
    
    public function __construct($langindex)
    {
        $this->user = new User();

        ob_clean();
        header('Content-Type: image/png');
        $red = rand(100,255);
        $green = rand(100,250);
        $blue = rand(100,200);

        $string = is_array($langindex) ? $this->user->lang($langindex[0]).': '.$this->user->lang($langindex[1]) : $this->user->lang($langindex);
        $string = html_entity_decode($string,ENT_QUOTES,'UTF-8');

        $image = imagecreate(11*strlen($string),45);
        $background_color = imagecolorallocate($image,0,0,0);
        $textcolor = imagecolorallocate($image,$red,$green,$blue);

        imagettftext ( $image , 14 ,0 , 14 , 30 , $textcolor , __DIR__ . '/fonts/DejaVuSans.ttf' ,$string );
        imagepng($image);
        imagedestroy($image);
    }

}
