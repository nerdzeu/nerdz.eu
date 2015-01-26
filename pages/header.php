<?php
$vals = [];

$vals['logged_b'] = $user->isLogged();
if($vals['logged_b'])
{
    $vals['myusername_n'] = NERDZ\Core\User::getUsername();
    $vals['myusername4link_n'] = \NERDZ\Core\Utils::userLink($vals['myusername_n']);
}
$vals['tok_n'] = NERDZ\Core\Security::getCsrfToken();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/header');
