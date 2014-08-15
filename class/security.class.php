<?php
namespace NERDZ\Core;

class Security
{
    public static function refererControl()
    {
        return isset($_SERVER['HTTP_REFERER']) && in_array(parse_url($_SERVER['HTTP_REFERER'])['host'],[ Config\SITE_HOST,Config\MOBILE_HOST ] );
    }

    public static function getCsrfToken($n = '')
    {
        $_SESSION['tok_'.$n] = isset($_SESSION['tok_'.$n]) ? $_SESSION['tok_'.$n] : md5(uniqid(rand(7,21)));
        return $_SESSION['tok_'.$n];
    }

    public static function csrfControl($tok,$n = '')
    {
        if(empty($_SESSION['tok_'.$n]))
            return false;
        return $_SESSION['tok_'.$n] === $tok;
    }

    public static function limitControl($limit,$n)
    {
        if(is_numeric($limit) && $limit < $n && $limit > 0)
            return $limit;

        if(!is_string($limit))
            return $n;

        $r = sscanf($limit,'%d,%d',$a,$b);

        if($r != 2 || ($r == 2 && $b > $n) )
            return $n;

        return "{$b} OFFSET {$a}";
    }
}
?>
