<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$user = new NERDZ\Core\Comments();

if(!$user->isLogged() || empty($_GET['message']))
    $_GET['message'] = $user->lang('ERROR');

$vals = [];
$vals['message_n'] =$user->bbcode($user->parseCommentQuotes(htmlspecialchars($_GET['message'],ENT_QUOTES,'UTF-8')));
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/preview');
?>
