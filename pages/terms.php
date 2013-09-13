<?php
//TEMPLATE: OK
if($core->isLogged())
    $l = $core->getUserLanguage($_SESSION['nerdz_id'],db::FETCH_OBJ);
else
    $l = $core->getBrowserLanguage();
$f = false;
$lcon = $l.'.txt';
$arr = glob($_SERVER['DOCUMENT_ROOT'].'/data/terms/*.html');
foreach($arr as $lang)
    if($lcon == $lang)
    {
        $f = true;
        break;
    }
if(!($txt = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/terms/'.($f ? $lcon : 'it.html'))))
    die($core->lang('ERROR'));

$txt = nl2br($txt);
$vals = array();
$vals['terms_n'] = $txt;
$tpl->assign($vals);
$tpl->draw('base/terms');

?>
