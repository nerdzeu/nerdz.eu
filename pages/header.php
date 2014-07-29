<?php
$vals = [];

$vals['logged_b'] = $core->isLogged();
if($vals['logged_b'])
{
    $vals['myusername_n'] = $core->getUsername();
    $vals['myusername4link_n'] = Core::userLink($vals['myusername_n']);
}
$vals['tok_n'] = $core->getCsrfToken();

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/header');
?>
