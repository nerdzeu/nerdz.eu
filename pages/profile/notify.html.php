<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Notification;
use NERDZ\Core\User;
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$user         = new User();
$notification = new Notification(); // group notification by default

if($user->isLogged())
{
    $vals = [];
    $vals['list_a'] = $notification->show('all', !isset ($_POST['doNotDelete']));

    if(!count($vals['list_a']))
        $vals['list_a'] = $notification->story();
    else
        $notification->updateStory($vals['list_a']);

    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('profile/notify');
}
else
    echo $user->lang('REGISTER');
?>
