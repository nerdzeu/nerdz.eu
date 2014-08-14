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

if(Db::NO_ERRNO != Db::query(
    [
        'INSERT INTO groups ("description","owner","name") VALUES (:description,:owner,:name)',
            [
                ':description' => $projectData['description'],
                ':owner'       => $projectData['owner'],
                ':name'        => $projectData['name']
            ]
        ],Db::FETCH_ERRNO)
    )
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
