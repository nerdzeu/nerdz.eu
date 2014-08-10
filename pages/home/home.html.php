<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$user = new NERDZ\Core\User();

if(!($logged = $user->isLogged()))
    die($user->lang('REGISTER'));

$prj      = isset($_GET['action']) && $_GET['action'] == 'project';
$truncate = true;
$path     = 'home';

require $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.html.php';
?>
