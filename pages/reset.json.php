<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';

$core = new NERDZ\Core\Core();
$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;
if(!$captcha)
    die($core->jsonResponse('error',$core->lang('MISSING').': '.$core->lang('CAPTCHA')));
if(!$cptcka->check($captcha))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

$email = isset($_POST['email']) ? trim($_POST['email']) : false;
if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL))
    die($core->jsonResponse('error',$core->lang('MAIL_NOT_VALID')));

if(!($obj = $core->query(array('SELECT "username","counter" FROM "users" WHERE "email" = :email',array(':email' => $email)),Db::FETCH_OBJ)))
    die($core->jsonResponse('error',$core->lang('USER_NOT_FOUND')));

$pass = Captcha::randomString(MIN_LENGTH_PASS);

if(Db::NO_ERRNO != $core->query(array('UPDATE "users" SET "password" = ENCODE(DIGEST(:pass, \'SHA1\'), \'HEX\') WHERE "counter" = :id',array(':pass' => $pass, ':id' => $obj->counter)),Db::FETCH_ERRNO))
    die($core->jsonResponse('error',$core->lang('ERROR').': retry'));

$subject = 'NERDZ PASSWORD';
$msg = '<a href="http://'.SITE_HOST.'">NERDZ</a><br /><br />';
$msg.= $core->lang('USERNAME').': '.$obj->username.'<br />';
$msg.= $core->lang('PASSWORD').': '.$pass.'<br />';
$msg.= "IP: {$_SERVER['REMOTE_ADDR']}";

require_once $_SERVER['DOCUMENT_ROOT'].'/class/vendor/autoload.php';

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true;
#$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
$mail->SMTPSecure = "tls";
$mail->Host = SMTP_SERVER;
$mail->Port = SMTP_PORT;
$mail->Username = SMTP_USER;
$mail->Password = SMTP_PASS;
$mail->SetFrom(SMTP_USER,'NERDZ Recovery [No reply]');
$mail->Subject = $subject;
$mail->MsgHTML($msg);
$mail->AddAddress($email);
if($mail->Send())
    die($core->jsonResponse('ok','OK'));

die($core->jsonResponse('error',$core->lang('ERROR').': contact support@nerdz.eu or retry'));
?>
