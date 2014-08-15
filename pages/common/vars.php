<?php
//Variables avaiable in every page present in the root of nerdz (/home.php, /profile.php and so on)
$vals['tok_n'] = NERDZ\Core\Security::getCsrfToken();
$vals['myusername_n'] = NERDZ\Core\User::getUsername();
$vals['myusername4link_n'] = \NERDZ\Core\Utils::userLink($vals['myusername_n']);
?>
