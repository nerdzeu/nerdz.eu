<?php
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
?>
