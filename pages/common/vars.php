<?php
//Variables avaiable in every page present in the root of nerdz (/home.php, /profile.php and so on)
$vals['tok_n'] = $core->getCsrfToken();
$vals['myusername_n'] = $core->getUsername();
$vals['myusername4link_n'] = \NERDZ\Core\Core::userLink($vals['myusername_n']);
?>
