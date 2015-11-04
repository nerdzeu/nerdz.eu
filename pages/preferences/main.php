<?php
if(!$user->isLogged())
    die(header('Location: /'));

$vals = [];
$vals['description_n'] = $user->lang('PREFERENCES_DESCR');
$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/layout');
