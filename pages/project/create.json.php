<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Project;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;

$core = new Project();
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die($core->jsonResponse('error',$core->lang('MISSING')."\n".$core->lang('CAPTCHA')));
if(!$cptcka->check($captcha))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

$create = true; //required by validateproject.php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';

if(Db::NO_ERRNO != Db::query(
    [
        'INSERT INTO groups ("description","owner","name") VALUES (:description,:owner,:name)',
         [
             ':description' => $group['description'],
             ':owner' => $group['owner'],
             ':name' => $group['name']
         ]
     ],Db::FETCH_ERRNO)
 )
    die($core->jsonResponse('error',$core->lang('ERROR')));
        
die($core->jsonResponse('ok','OK'));
?>
