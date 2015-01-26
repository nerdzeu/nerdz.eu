<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;

$user = new User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MISSING')."\n".$user->lang('CAPTCHA')));
if(!$cptcka->check($captcha))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

$create = true; //required by validateproject.php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';
try
{
    Db::getDb()->beginTransaction();
    Db::query(
        [
            'INSERT INTO groups ("description","name") VALUES (:description,:name)',
            [
                ':description' => $projectData['description'],
                ':name'        => $projectData['name']
            ]
        ],Db::NO_RETURN);

    $o = Db::query(
        [
            'SELECT counter FROM groups WHERE name = :name',
            [
                ':name' => $projectData['name']
            ]
         ], Db::FETCH_OBJ);

    Db::query(
        [
            'INSERT INTO groups_owners("from", "to")  VALUES(:owner, :group)',
            [
                ':owner'  => $projectData['owner'],
                ':group'  => $o->counter
            ]
        ], Db::NO_RETURN);
        
    Db::getDb()->commit();
} catch(\PDOException $e) {
    Db::getDb()->rollBack();
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}

die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
