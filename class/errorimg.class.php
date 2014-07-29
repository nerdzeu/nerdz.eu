<?php
namespace NERDZ\Core;
/*
 * Classe per la creazione di immagini di errore
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

final class ErrorImg extends Core
{
    public function __construct($langindex)
    {
        parent::__construct();

        ob_clean();
        header('Content-Type: image/png');
        $red = rand(100,255);
        $green = rand(100,250);
        $blue = rand(100,200);
        
        $string = is_array($langindex) ? parent::lang($langindex[0]).': '.parent::lang($langindex[1]) : parent::lang($langindex);
        $string = html_entity_decode($string,ENT_QUOTES,'UTF-8');

        $image = imagecreate(11*strlen($string),45);
        $background_color = imagecolorallocate($image,0,0,0);
        $textcolor = imagecolorallocate($image,$red,$green,$blue);
        imagestring($image,14,20,14,$string,$textcolor);
        imagepng($image);
        imagedestroy($image);
    }

}
?>
