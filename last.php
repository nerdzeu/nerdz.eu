<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Core;

if(!($o = (new Core())->query('SELECT "username" FROM "users" ORDER BY "counter" DESC',Db::FETCH_OBJ)))
    die('Db error');

die(header('Location: /'.NERDZ\Core\Utils::userLink($o->username)));
?>
