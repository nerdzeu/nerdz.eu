<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$core = new NERDZ\Core\Core();

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
    
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die($core->jsonResponse('error',$core->lang('ERROR').': token'));
    
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

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

$ret = $core->query(
    [
        'UPDATE users SET "timezone" = :timezone, "name" = :name,
        "surname" = :surname,"email" = :email,"gender" = :gender, "birth_date" = :date
        '.($updatedPassword ? ', "password" = ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\')' : '').' WHERE counter = :id', $params
    ],Db::FETCH_ERRSTR);

if($ret != Db::NO_ERRSTR)
    die($core->jsonDbResponse($ret));

if(!$core->login($core->getUsername(), $user['password'], isset($_COOKIE['nerdz_u']), $_SESSION['mark_offline'], !$updatedPassword))
    die($core->jsonResponse('error',$core->lang('ERROR').': Login'));

die($core->jsonResponse('error','OK'));
?>
