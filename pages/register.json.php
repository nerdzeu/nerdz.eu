<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\Captcha;

$user = new User();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MISSING').': '.$user->lang('CAPTCHA')));

if(!$cptcka->check($captcha))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php';

$ret = Db::query(
    [
        'INSERT INTO users ("username","password","name","surname","email","gender","birth_date","lang","board_lang","timezone","remote_addr", "http_user_agent")
        VALUES (:username,ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\'), :name, :surname, :email, :gender, :date, :lang, :lang, :timezone, :remote_addr, :http_user_agent)',
        [
              ':username'        => $userData['username'],
              ':password'        => $userData['password'],
              ':name'            => $userData['name'],
              ':surname'         => $userData['surname'],
              ':email'           => $userData['email'],
              ':gender'          => $userData['gender'],
              ':timezone'        => $userData['timezone'],
              ':date'            => $birth['date'],
              ':lang'            => $user->getBrowserLanguage(),
              ':remote_addr'     => $_SERVER['REMOTE_ADDR'],
              ':http_user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8')
         ]
     ], Db::FETCH_ERRSTR);

if($ret != Db::NO_ERRSTR)
    die(NERDZ\Core\Utils::jsonDbResponse($ret));

if(!$user->login($userData['username'], $userData['password']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Login'));

die(NERDZ\Core\Utils::jsonResponse('ok',$user->lang('LOGIN_OK')));

?>
