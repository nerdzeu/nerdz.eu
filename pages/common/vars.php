<?php
//Variables avaiable in every page present in the root of nerdz (/home.php, /profile.php and so on)
if(!isset($user))
    die('$user required');
$commonvars = [];
$commonvars['tok_n'] = NERDZ\Core\Security::getCsrfToken();
$commonvars['myusername_n'] = NERDZ\Core\User::getUsername();
$commonvars['myusername4link_n'] = \NERDZ\Core\Utils::userLink($commonvars['myusername_n']);
$langKey = 'lang'. NERDZ\Core\Config\SITE_HOST;
$commonvars['langs_a'] = [];
if(!apc_exists($langKey))
{
    $longlangs  = NERDZ\Core\System::getAvailableLanguages(1);
    $i = 0;
    foreach($longlangs as $id => $val)
    {
        $commonvars['langs_a'][$i]['longlang_n'] = $val;
        $commonvars['langs_a'][$i]['shortlang_n'] = $id;
        ++$i;
    }

    @apc_store($langKey, serialize($commonvars['langs_a']),3600);
} else {
    $commonvars['langs_a'] = unserialize(apc_fetch($langKey));
}

$commonvars['mylang_n'] = $user->getLanguage();
$commonvars['flagdir_n'] = NERDZ\Core\System::getResourceDomain().'/static/images/flags/';

$user->getTPL()->assign($commonvars);

?>
