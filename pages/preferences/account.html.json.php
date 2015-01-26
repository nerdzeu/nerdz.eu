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
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php'; //include $updatedPassword
$params = [
    ':timezone' => $userData['timezone'],
    ':name'     => $userData['name'],
    ':surname'  => $userData['surname'],
    ':email'    => $userData['email'],
    ':gender'   => $userData['gender'],
    ':date'     => $birth['date'],
    ':id'       => $_SESSION['id']
];

if($updatedPassword) {
    $params[':password'] = $userData['password'];
}

$ret = Db::query(
    [
        'UPDATE users SET "timezone" = :timezone, "name" = :name,
        "surname" = :surname,"email" = :email,"gender" = :gender, "birth_date" = :date
        '.($updatedPassword ? ', "password" = crypt(:password, gen_salt(\'bf\', 7))' : '').' WHERE counter = :id', $params
    ],Db::FETCH_ERRSTR);

if($ret != Db::NO_ERRSTR)
    die(NERDZ\Core\Utils::jsonDbResponse($ret));

if($updatedPassword && ($cookie = isset($_COOKIE['nerdz_u']))) {
	if(!$user->login(User::getUsername(), $userData['password'], $cookie, $_SESSION['mark_offline']))
		die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Login'));
}

die(NERDZ\Core\Utils::jsonResponse('error','OK'));
