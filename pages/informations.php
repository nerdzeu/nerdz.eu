<?php
//TEMPLATE: OK
$l = $core->isLogged() ? $core->getUserLanguage($_SESSION['nerdz_id']) : $core->getBrowserLanguage();
$f = false;
$lcon = $_SERVER['DOCUMENT_ROOT'].'/data/informations/'.$l.'.txt';
foreach(glob($_SERVER['DOCUMENT_ROOT'].'/data/informations/*.txt') as $lang)
	if($lcon == $lang)
	{
		$f = true;
		break;
	}
if(!$f)
	$lcon = $_SERVER['DOCUMENT_ROOT'].'/data/informations/en.txt';

$txt = file_get_contents($lcon);
$exp = explode("\n",$txt);
$vals = array();
$vals['informations_n'] = $txt;
$tpl->assign($vals);
$tpl->draw('base/informations');
?>
