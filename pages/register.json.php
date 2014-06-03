<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

$core = new phpCore();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die($core->jsonResponse('error',$core->lang('MISSING').': '.$core->lang('CAPTCHA')));

if(!$cptcka->check($captcha))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php';

$ret = $core->query(
    [
        'INSERT INTO users ("username","password","name","surname","email","gender","birth_date","lang","board_lang","timezone","remote_addr", "http_user_agent")
        VALUES (:username,ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\'), :name, :surname, :email, :gender, :date, :lang, :lang, :timezone, :remote_addr, :http_user_agent)',
        [
              ':username'        => $user['username'],
              ':password'        => $user['password'],
              ':name'            => $user['name'],
              ':surname'         => $user['surname'],
              ':email'           => $user['email'],
              ':gender'          => $user['gender'],
              ':timezone'        => $user['timezone'],
              ':date'            => $birth['date'],
              ':lang'            => $core->getBrowserLanguage(),
              ':remote_addr'     => $_SERVER['REMOTE_ADDR'],
              ':http_user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8')
         ]
     ], db::FETCH_ERRSTR);

if($ret != db::NO_ERRSTR)
    die($core->jsonDbResponse($ret));

if(!$core->login($user['username'], $user['password']))
    die($core->jsonResponse('error',$core->lang('ERROR').': Login'));

die($core->jsonResponse('ok',$core->lang('LOGIN_OK')));

?>
