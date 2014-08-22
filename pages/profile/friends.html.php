<?php
if(!isset($id, $user))
    die('$id & user required');

$users = $user->getFriends($id);
$type = 'friends';
return require $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
?>
