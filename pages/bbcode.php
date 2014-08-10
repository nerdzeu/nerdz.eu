<?php
$lang = $user->isLogged() ? $user->getLanguage($_SESSION['id']) : $user->getBrowserLanguage();

$vals = [];
$vals['bbcode_n'] = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/tpl/{$user->getTemplate()}/langs/{$lang}/bbcode.html");

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/bbcode');
?>
