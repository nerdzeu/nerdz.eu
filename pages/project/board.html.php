<?php
ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

use NERDZ\Core\User;
$user = new User();

if(!$user->isLogged())
    die($user->lang('REGISTER'));

if(!NERDZ\Core\Security::refererControl())
    die($user->lang('ERROR'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
case 'get':
    //fa tutto lei compresa la gestione di $_POST[hpid]
    $hpid = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
    $draw = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/singlepost.html.php';
    break;

default:
    die($user->lang('ERROR'));
    break;
}
?>
