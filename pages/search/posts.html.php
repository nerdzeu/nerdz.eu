<?php
$prj = isset($_GET['action']) && $_GET['action'] == 'project';
$path = 'home';
$id = null;
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.html.php';
