<?php
if($user->isLogged())
    die(header('Location: /home.php'));

$user->getTPL()->draw('base/reset');
?>
