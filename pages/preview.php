<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Comments;
use NERDZ\Core\User;
$user = new User();
$message = new Comments();

if(!$user->isLogged() || empty($_GET['message']))
    $_GET['message'] = $user->lang('ERROR');

$vals = [];
$vals['message_n'] = $message->bbcode(htmlspecialchars($message->parseQuote($_GET['message']),ENT_QUOTES,'UTF-8'));
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/preview');
