<?php
$vals = [];
$vals['querystring_n'] = $q;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('search/layout');
?>
