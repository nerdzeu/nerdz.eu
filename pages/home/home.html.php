<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new Core();

if(!($logged = $core->isLogged()))
    die($core->lang('REGISTER'));

$prj      = isset($_GET['action']) && $_GET['action'] == 'project';
$truncate = true;
$path     = 'home';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.html.php';
?>
