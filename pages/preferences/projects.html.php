<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!NERDZ\Core\Security::refererControl())
    die($user->lang('ERROR'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

$vals = [];
$vals['tok_n'] = NERDZ\Core\Security::getCsrfToken('edit');

if(!($r = Db::query(
    [
        'SELECT g."name", g.counter FROM "groups" g INNER JOIN "groups_owners" go
        ON go."to" = g.counter
        WHERE go."from" = :id',
        [
            ':id' => $_SESSION['id']
        ]
    ],Db::FETCH_STMT)))
    $vals['myprojects_a'] = [];
else
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['myprojects_a'][$i]['name_n'] = $o->name;
        $vals['myprojects_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
        $vals['myprojects_a'][$i]['id_n'] = $o->counter;
        ++$i;
    }
}
$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/projects');
