<?php
if($core->isLogged())
    die(header('Location: /home.php'));

$core->getTPL()->draw('base/reset');
?>
