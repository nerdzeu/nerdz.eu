<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();

if(!($logged = $core->isLogged()))
    die($core->lang('REGISTER'));

$prj = isset($_GET['action']) && $_GET['action'] == 'project';
var_dump($prj);
$truncate = true;
$path = 'home';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.html.php';
?>
