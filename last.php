<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
if(!($o = (new Core())->query('SELECT "username" FROM "users" ORDER BY "counter" DESC',Db::FETCH_OBJ)))
    die('Db error');

die(header('Location: /'.Core::userLink($o->username)));
?>
