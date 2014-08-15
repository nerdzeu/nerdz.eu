<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\System;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

$vals = [];
$vals['tok_n'] = $user->getCsrfToken('edit');
$longlangs  = System::getAvailableLanguages(1);

$vals['langs_a'] = [];
$i = 0;
foreach($longlangs as $id => $val)
{
    $vals['langs_a'][$i]['longlang_n'] = $val;
    $vals['langs_a'][$i]['shortlang_n'] = $id;
    ++$i;
}
$vals['mylang_n'] = $user->getLanguage($_SESSION['id']);
$vals['myboardlang_n'] = $user->getBoardLanguage($_SESSION['id']);

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/language');
?>
