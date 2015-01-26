<?php
if(!$user->isLogged())
    die(header('Location: /'));
$vals = [];
$vals['description_n'] = $user->lang('PREFERENCES_DESCR');

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/layout');
