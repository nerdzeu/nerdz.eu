<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();

if(!$user->isLogged() || empty($_POST['id']) || !is_numeric($_POST['id']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('LOGIN')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
case 'del':
    if(Db::NO_ERRNO != Db::query(
        [
            'DELETE FROM "blacklist" WHERE "from" = :me AND "to" = :to',
            [
                ':me' => $_SESSION['id'],
                ':to' => $_POST['id']
            ]
        ],Db::FETCH_ERRNO))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;

case 'add':
    $motivation = empty($_POST['motivation']) ? '' : htmlspecialchars(trim($_POST['motivation']),ENT_QUOTES,'UTF-8');
    if(!($user->hasInBlacklist($_POST['id'])))
    {
        if(Db::NO_ERRNO != Db::query(
            [
                'INSERT INTO "blacklist"("from","to","motivation") VALUES (:me,:to,:motivation)',
                    [
                        ':me'         => $_SESSION['id'],
                        ':to'         => $_POST['id'],
                        ':motivation' => $motivation
                    ]
                ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
        }
        else
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'1'));
    break;

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'2'));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
