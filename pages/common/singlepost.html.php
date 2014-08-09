<?php
if(!isset($hpid))
    die('$hpid required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

use NERDZ\Core\Messages;

$prj = isset($prj);
$messages = new Messages();

if( empty($hpid) || !($o = $messages->getMessage($hpid, $prj)) )
    die($core->lang('ERROR'));

$core->getTPL()->assign($messages->getPost($o, ['project' => $prj ]));
    
if(isset($draw))
    $core->getTPL()->draw(($prj ? 'project' : 'profile').'/post');
else
    return $core->getTPL()->draw(($prj ? 'project' : 'profile').'/post', true);
?>
