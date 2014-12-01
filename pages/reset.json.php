<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;

$user = new User();
$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;
if(!$captcha)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MISSING').': '.$user->lang('CAPTCHA')));
if(!$cptcka->check($captcha))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

$email = isset($_POST['email']) ? trim($_POST['email']) : false;
if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MAIL_NOT_VALID')));

if(!($obj = Db::query(array('SELECT "username","counter" FROM "users" WHERE "email" = :email',array(':email' => $email)),Db::FETCH_OBJ)))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USER_NOT_FOUND')));

$pass = Captcha::randomString(Config\MIN_LENGTH_PASS);

if(Db::NO_ERRNO != Db::query(
    [
        'UPDATE "users" SET "password" = crypt(:pass, gen_satl(\'bf\', 7)) WHERE "counter" = :id',
        [
            ':pass' => $pass,
            ':id'   => $obj->counter
        ]
    ],Db::FETCH_ERRNO))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': retry'));

$subject = Config\SITE_NAME.' Password';
$msg = '<a href="http://'.Config\SITE_HOST.'">NERDZ</a><br /><br />';
$msg.= $user->lang('USERNAME').': '.$obj->username.'<br />';
$msg.= $user->lang('PASSWORD').': '.$pass.'<br />';
$msg.= "IP: {$_SERVER['REMOTE_ADDR']}";

require_once $_SERVER['DOCUMENT_ROOT'].'/class/vendor/autoload.php';

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true;
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
#$mail->SMTPSecure = "tls";
$mail->Host = Config\SMTP_SERVER;
$mail->Port = Config\SMTP_PORT;
$mail->Username = Config\SMTP_USER;
$mail->Password = Config\SMTP_PASS;
$mail->SetFrom(Config\SMTP_USER,'['. Config\SITE_NAME .'] Password recovery [No reply]');
$mail->Subject = $subject;
$mail->MsgHTML($msg);
$mail->AddAddress($email);
if($mail->Send())
    die(NERDZ\Core\Utils::jsonResponse('ok','OK'));

die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': contact support@nerdz.eu or retry'));
?>
