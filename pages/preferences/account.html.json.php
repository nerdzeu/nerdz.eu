<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

$core = new phpCore();

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
     ':id'       => $_SESSION['nerdz_id']
];

if($updatedPassword) {
    $params[':password'] = $user['password'];
}

if(db::NO_ERRNO != $core->query(
    [
        'UPDATE users SET "timezone" = :timezone, "name" = :name,
        "surname" = :surname,"email" = :email,"gender" = :gender, "birth_date" = :date
        '.($updatedPassword ? ', "password" = ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\')' : '').' WHERE counter = :id', $params
    ],db::FETCH_ERRNO)
)
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(!$core->login($core->getUserName(), $user['password'], isset($_COOKIE['nerdz_u']), $_SESSION['nerdz_mark_offline'], !$updatedPassword))
    die($core->jsonResponse('error',$core->lang('ERROR').': Login'));

die($core->jsonResponse('error','OK'));
?>
