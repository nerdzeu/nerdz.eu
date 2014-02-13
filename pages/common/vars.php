<?php
//Variables avaiable in every page present in the root of nerdz (/home.php, /profile.php and so on)
$vals['tok_n'] = $core->getCsrfToken();
$vals['myusername_n'] = $core->getUserName();
$vals['myusername4link_n'] = phpCore::userLink($vals['myusername_n']);
?>
