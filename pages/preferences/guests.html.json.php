<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));;

$id = $_SESSION['id'];

if(!($obj = Db::query(array('SELECT "private" FROM "users" WHERE "counter" = ?',array($id)),Db::FETCH_OBJ)))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
case 'public':
    if($obj->private == 1)
        if(Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "private" = FALSE WHERE "counter" = ?',array($id)),Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
case 'private':
    if(!$obj->private)
        if(Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "private" = TRUE WHERE "counter" = ?',array($id)),Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
