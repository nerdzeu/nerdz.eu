<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
$core = new comments();

if(!$core->isLogged() || empty($_GET['message']))
    $_GET['message'] = $core->lang('ERROR');

$vals = [];
$vals['message_n'] =$core->bbcode($core->parseCommentQuotes(htmlspecialchars($_GET['message'],ENT_QUOTES,'UTF-8')));
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/preview');
?>
