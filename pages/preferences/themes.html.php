<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Config;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));
    
$vals = [];
$vals['tok_n'] = $user->getCsrfToken('edit');

$vals['themes_a'] = [];
$i = 0;
$templates = $user->getAvailableTemplates();

foreach($templates as $val)
{
    $vals['themes_a'][$i]['tplno_n'] = $val['number'];
    $vals['themes_a'][$i]['tplname_n'] = $val['name'];
    ++$i;
}
$vals['mytplno_n'] = $user->getTemplate($_SESSION['id']);
$vals['mobile_b'] = $_SERVER['HTTP_HOST'] == Config\MOBILE_HOST;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/themes');
?>
