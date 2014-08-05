<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
if(!($o = (new Core())->query('SELECT "username" FROM "users" ORDER BY "counter" DESC',Db::FETCH_OBJ)))
    die('Db error');

die(header('Location: /'.NERDZ\Core\Core::userLink($o->username)));
?>
