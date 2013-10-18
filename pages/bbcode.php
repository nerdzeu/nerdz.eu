<?php
//Template: OK
$l = $core->isLogged() ? $core->getUserLanguage($_SESSION['nerdz_id']) : $core->getBrowserLanguage();
$f = false;
$lcon = $_SERVER['DOCUMENT_ROOT'].'/data/bbcode/'.$l.'.txt';
foreach(glob($_SERVER['DOCUMENT_ROOT'].'/data/bbcode/*.txt') as $lang)
    if($lcon == $lang)
    {
        $f = true;
        break;
    }

$txt = file_get_contents($f ? $lcon : $_SERVER['DOCUMENT_ROOT'].'/data/bbcode/'.$l.'en.txt');
$vals = array();
$arr = explode("\n",$txt);
$vals['list_a'] = $arr;
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/bbcode');
?>
