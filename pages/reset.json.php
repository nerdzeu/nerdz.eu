<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use NERDZ\Core\Security;

$user = new User();
$cptcka = new Captcha();

$captcha  = isset($_POST['captcha'])  ? $_POST['captcha']     : false;
$email    = isset($_POST['email'])    ? trim($_POST['email']) : false;
$password = isset($_POST['password']) ? $_POST['password']    : false;
$token    = isset($_POST['token'])    ? $_POST['token']       : false;
$key      = isset($_POST['key']) && is_numeric($_POST['key']) ? $_POST['key'] : false;

if($email !== false && $captcha !== false) { // 1st step
    if(!$captcha)
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MISSING').': '.$user->lang('CAPTCHA')));
    if(!$cptcka->check($captcha))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

    if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MAIL_NOT_VALID')));

    if(!($obj = Db::query(
        [
            'SELECT "username","counter" FROM "users" WHERE "email" = :email',
            [
                ':email' => $email
            ]
        ],Db::FETCH_OBJ)))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USER_NOT_FOUND')));

    $vals = [];
    $vals['username_n'] = $obj->username;
    $vals['usernamelink_n'] = 'http://'.Config\SITE_HOST.'/'.\NERDZ\Core\Utils::userLink($obj->username);
    $vals['account_n'] = "{$obj->username} - ID: {$obj->counter}";
    $vals['ip_n'] = $_SERVER['REMOTE_ADDR'];
    $token = md5(openssl_random_pseudo_bytes(rand(7,21)));
    if(Db::NO_ERRNO != Db::query(
        [
            'INSERT INTO reset_requests(remote_addr,token,"to") VALUES(:remote_addr,:token,:to)',
                [
                    ':remote_addr' => $_SERVER['REMOTE_ADDR'],
                    ':token'       => $token,
                    ':to'          => $obj->counter
                ]
            ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'(1): '.$user->lang('TRY_LATER')));

    if(!($key = Db::query(
        [
            'SELECT counter FROM reset_requests WHERE token = :token AND "to" = :to AND remote_addr = :remote_addr',
            [
                ':remote_addr' => $_SERVER['REMOTE_ADDR'],
                ':token'       => $token,
                ':to'          => $obj->counter
            ]
        ],Db::FETCH_OBJ)))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'(4): '.$user->lang('TRY_LATER')));

    $vals['reseturl_n'] = 'http://'.Config\SITE_HOST.'/reset.php?tok='.$token.'&amp;id='.$key->counter;

    require_once $_SERVER['DOCUMENT_ROOT'].'/class/vendor/autoload.php';
    try
    {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = 'tls://'.trim(Config\SMTP_SERVER).':'.trim(Config\SMTP_PORT);
        $mail->Username = Config\SMTP_USER;
        $mail->Password = Config\SMTP_PASS;
        $mail->SetFrom(Config\SMTP_USER, Config\SITE_NAME);
        $mail->Subject = $user->lang('RESET_YOUR_PASSWORD');
        $user->getTPL()->assign($vals);
        $mail->MsgHTML($user->getTPL()->draw("langs/{$user->getLanguage()}/reset-mail", true));
        $mail->AddAddress($email);
        if($mail->Send())
            die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': '.$mail->ErrorInfo));
    }
    catch(phpmailerException $e) {
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': '.$e->errorMessage()."\n contact support@nerdz.eu or retry"));
    }

    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': contact support@nerdz.eu or retry'));

} else if($password !== false && $token !== false && $key !== false) {//3rd step
    switch( Security::passwordControl($password) ) {
    case 'PASSWORD_SHORT':
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('PASSWORD_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_PASS));
    case 'PASSWORD_LONG':
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('PASSWORD_LONG')));
    }

    if(!($obj = Db::query(
        [
            'SELECT r.*, u.username FROM reset_requests r JOIN users u ON r.to = u.counter WHERE r.counter = :key',
            [
                ':key' => $key
            ]
        ], Db::FETCH_OBJ)))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'(2): '.$user->lang('TRY_LATER')));

    if($obj->token !== $token)
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Token'));

    if(Db::NO_ERRNO != Db::query(
        [
            'DELETE FROM reset_requests WHERE "to" = :to AND counter <= :key',
            [
                ':to'  => $obj->to,
                ':key' => $key
            ]
        ], Db::FETCH_ERRNO))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'(3): '.$user->lang('TRY_LATER')));

    if(Db::NO_ERRNO != Db::query(
        [
            'UPDATE "users" SET "password" = crypt(:pass, gen_salt(\'bf\', 7)) WHERE "counter" = :id',
                [
                    ':pass' => $password,
                    ':id'   => $obj->to
                ]
            ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': retry'));

    if(!$user->login($obj->username, $password, $setCookie = true))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Login'));

    die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
}
