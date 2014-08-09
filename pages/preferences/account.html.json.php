<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$core = new User();

if(!$core->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': referer'));
    
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': token'));
    
if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php'; //include $updatedPassword
$params = [
     ':timezone' => $user['timezone'],
     ':name'     => $user['name'],
     ':surname'  => $user['surname'],
     ':email'    => $user['email'],
     ':gender'   => $user['gender'],
     ':date'     => $birth['date'],
     ':id'       => $_SESSION['id']
];

if($updatedPassword) {
    $params[':password'] = $user['password'];
}

$ret = Db::query(
    [
        'UPDATE users SET "timezone" = :timezone, "name" = :name,
        "surname" = :surname,"email" = :email,"gender" = :gender, "birth_date" = :date
        '.($updatedPassword ? ', "password" = ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\')' : '').' WHERE counter = :id', $params
    ],Db::FETCH_ERRSTR);

if($ret != Db::NO_ERRSTR)
    die(NERDZ\Core\Utils::jsonDbResponse($ret));

if(!$core->login(User::getUsername(), $user['password'], isset($_COOKIE['nerdz_u']), $_SESSION['mark_offline'], !$updatedPassword))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': Login'));

die(NERDZ\Core\Utils::jsonResponse('error','OK'));
?>
