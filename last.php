<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
if(!($o = (new phpCore())->query('SELECT "username" FROM "users" ORDER BY "counter" DESC',db::FETCH_OBJ)))
    die('db error');

die(header('Location: /'.phpCore::userLink($o->username)));
?>
