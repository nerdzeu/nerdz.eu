<?php
//Template: OK
require_once $_SERVER['DOCUMENT_ROOT'].'class/comments.class.php';
$core = new comments();

if(!$core->isLogged() || empty($_GET['message']))
	die($core->lang('ERROR'));

$vals = array();
$vals['message_n'] = $core->parseCommentQuotes($core->bbcode(htmlentities($_GET['message'],ENT_QUOTES,'UTF-8')));
$tpl->assign($vals);
$tpl->draw('base/preview');
?>
