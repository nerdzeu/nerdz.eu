<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Project;
use NERDZ\Core\Db;

$core = new Project();

if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));

if(empty($_POST['id'])||!is_numeric($_POST['id']))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));

$prj = isset($prj);

$table = ($prj ? 'groups_' : '').'followers';

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        if(Db::NO_ERRNO != Db::query(
            [
                'DELETE FROM "'.$table.'" WHERE "to" = :id AND "from" = :me',
                [
                    ':id' => $_POST['id'],
                    ':me' => $_SESSION['id']
                ],Db::FETCH_ERRNO
            ])
        )
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    case 'add':
        if(Db::NO_ERRNO != Db::query(
            [
                'INSERT INTO "'.$table.'"("to","from")
                 SELECT :id, :me
                 WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "to" = :id AND "from" = :me)',
                 [
                     ':id' => $_POST['id'],
                     ':me' => $_SESSION['id']
                 ]
             ],Db::FETCH_ERRNO)
         )
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
}

die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
