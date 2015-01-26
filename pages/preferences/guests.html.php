<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

if(!($o = Db::query(
    [
        'SELECT "private" FROM "users" WHERE "counter" = :id',
        [
            ':id' => $_SESSION['id']
        ]
    ],Db::FETCH_OBJ)))
    die($user->lang('ERROR'));

$vals['private_b'] = $o->private;
$vals['tok_n']     = NERDZ\Core\Security::getCsrfToken('edit');

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/guests');
