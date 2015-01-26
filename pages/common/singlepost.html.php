<?php
if(empty($hpid))
    die('$hpid required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

use NERDZ\Core\Messages;

$prj = isset($prj);
$messages = new Messages();
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$user->getTPL()->assign($messages->getPost($hpid, ['project' => $prj ]));

if(isset($draw))
    $user->getTPL()->draw(($prj ? 'project' : 'profile').'/post');
else
    return $user->getTPL()->draw(($prj ? 'project' : 'profile').'/post', true);
