<?php
//Variables avaiable in every page compiled by rainTPL
if(!isset($user))
    die('$user required');
// use function to create variable scope and avoid conflicts
$func = function() use ($user) {
    $commonvars = [];
    $commonvars['tok_n'] = NERDZ\Core\Security::getCsrfToken();
    if($user->isLogged()) {
        $commonvars['logged_b'] = true;
        $commonvars['myusername_n'] = NERDZ\Core\User::getUsername();
        $commonvars['myusername4link_n'] = \NERDZ\Core\Utils::userLink($commonvars['myusername_n']);
        $commonvars['mygravatarurl_n'] = $user->getGravatar($_SESSION['id']);
    } else {
        $commonvars['logged_b'] = false;
        $commonvars['myusername_n'] = $commonvars['myusername4link_n'] = $commonvars['mygravatarurl_n'] = '';
    }
    $langKey = 'lang'. NERDZ\Core\Config\SITE_HOST;
    if(!($commonvars['langs_a'] = NERDZ\Core\Utils::apc_get($langKey)))
        $commonvars['langs_a'] = NERDZ\Core\Utils::apc_set($langKey,function() {
            $ret = [];
            $i = 0;
            $longlangs = NERDZ\Core\System::getAvailableLanguages(1);
            foreach($longlangs as $id => $val)
            {
                $ret[$i]['longlang_n'] = $val;
                $ret[$i]['shortlang_n'] = $id;
                ++$i;
            }
            return $ret;
        }, 3600);

    $commonvars['mylang_n']  = $user->getLanguage();
    $commonvars['flagdir_n'] = NERDZ\Core\System::getResourceDomain().'/static/images/flags/';

    $banners = (new NERDZ\Core\Banners())->getBanners();
    $commonvars['banners_a'] = [];
    shuffle($banners);
    foreach($banners as $ban)
        $commonvars['banners_a'][$ban[1]] = $ban[2];

    $user->getTPL()->assign($commonvars);
};

$func();

