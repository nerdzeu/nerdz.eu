<?php
/*
 * Classe per la creazione e il controllo di un semplice captcha
 */
namespace NERDZ\Core;

require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

final class Captcha
{
    private function generate()
    {
        $_SESSION['captcha'] = $this->randomString(CAPTCHA_LEVEL);
    }

    public function show()
    {
        if (count(ob_list_handlers()) > 0) {
             ob_clean();
        }
        header('Content-Type: image/png');
        $this->generate();
        $red = rand(200,255);
        $green = rand(200,250);
        $blue = rand(200,200);
        $image = imagecreate(90,30);
        $background_color = imagecolorallocate($image,0,0,0);
        $textcolor = imagecolorallocate($image,$red,$green,$blue);
        imagestring($image,5,18,8,$_SESSION['captcha'],$textcolor);
        for($i=0;$i<20;++$i)
        {
            $x1 = rand(1,80);
               $y1 = rand(1,25);
            $x2 = $x1+4;
            $y2 = $y1+4;
            $color = imagecolorallocate($image,~$green,~$blue,~$red);
            imageline($image,$x1,$y1,$x2,$y2,$color);
        }
        imagepng($image);
        imagedestroy($image);
    }

    public function check($var)
    {
        if(!isset($_SESSION['captcha']))
            return false;
        $c = $var == $_SESSION['captcha'];
        return $c;
    }

    public static function randomString($len)
    {
        $casual = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz12345678890';
        $i=0;
        $ret = '';
        while($i++<=$len)
            $ret.=$casual[rand(0,62)];
    
        return trim($ret);
    }
}
?>
