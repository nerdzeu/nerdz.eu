<?php
/* 
 * Classe statica che gestisce la "roba" di nerdz.
 * Le cose imbecilli che variano al variare di alcuni semplici parametri, saranno incluse qui
 */

final class stuff
{
    public static function stupid($n)
    {
        switch(intval($n))
        {
            case 0:
            case ($n<1000):
                $r = 'n0rm41-&uuml;s3r';
                $less = 1000-$n;
                $next=  '3m0tiC0n';
            break;
            case ($n<3000):
                $r = '3m0tiC0n';
                $less = (3000-$n);
                $next = 'p0w4';
            break;
            case ($n<5000):
                $r = 'p0w4';
                $less = 5000-$n;
                $next = 'u1tr4-p0w4';
            break;
            case ($n<7000):
                $r = 'u1tr4-p0w4 -';
                $less = 7000-$n;
                $next = 'g0k&ugrave;!!11';
            break;
            case ($n<10000):
                $r = 'g0k&ugrave;!!11';
                $less = 10000-$n;
                $next = 'sup3r-s4y4n';
            break;
            case ($n<15000):
                $r = 'sup3r-s4y4n';
                $less = 15000-$n;
                $next = 'd0rk';
            break;
            case ($n<20000):
                $r = 'd0rk';
                $less = 20000-$n;
                $next = 'l33t';
            break;
            case ($n<30000):
                $r = 'l33t';
                $less = 30000-$n;
                $next = 'h4x0r!11!&ugrave;';
            break;
            case ($n<50000):
                $r = 'h4x0r!11!&ugrave;';
                $less = 50000-$n;
                $next = 'h4x0r-m3g4pOw4h!';
            break;
            case ($n<100000):
                $r = 'h4x0r-m3g4pOw4h!';
                $less = 100000-$n;
                $next = 'g0d';
            break;
            case ($n<200000):
                $r = 'g0d';
                $less = 200000-$n;
                $next = 'n3ssUn0';
            break;
            case ($n<300000):
                $r = 'n3ssUn0';
                $less = 300000-$n;
                $next = '&Uuml;B3r-N3rD';
            break;
            case ($n<424242):
                $r = '&Uuml;B3r-N3rD';
                $less = 424242-$n;
                $next = '42.';
            break;
            default:
                $r = $next = '42.';
                $less = 0;
            break;
        }
        
        return array('now' => $r, 'less' => $less, 'next' => $next);
    }
}

?>
