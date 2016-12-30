<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
//Variables avaiable in every page compiled by rainTPL
if (!isset($user)) {
    die('$user required');
}
// use function to create variable scope and avoid conflicts
$func = function () use ($user) {
    $commonvars = [];
    $commonvars['tok_n'] = NERDZ\Core\Security::getCsrfToken();
    $commonvars['captchaurl_n'] = '/static/images/captcha.php';

    if ($user->isLogged()) {
        $commonvars['logged_b'] = true;
        $commonvars['myusername_n'] = NERDZ\Core\User::getUsername();
        $commonvars['myusername4link_n'] = \NERDZ\Core\Utils::userLink($commonvars['myusername_n']);
        $commonvars['mygravatarurl_n'] = $user->getGravatar($_SESSION['id']);
    } else {
        $commonvars['logged_b'] = false;
        $commonvars['myusername_n'] = $commonvars['myusername4link_n'] = $commonvars['mygravatarurl_n'] = '';
    }
    $langKey = 'lang'.NERDZ\Core\Config\SITE_HOST;
    if (!($commonvars['langs_a'] = NERDZ\Core\Utils::apcu_get($langKey))) {
        $commonvars['langs_a'] = NERDZ\Core\Utils::apcu_set($langKey, function () {
            $ret = [];
            $i = 0;
            $longlangs = NERDZ\Core\System::getAvailableLanguages(1);
            foreach ($longlangs as $id => $val) {
                $ret[$i]['longlang_n'] = $val;
                $ret[$i]['shortlang_n'] = $id;
                ++$i;
            }

            return $ret;
        }, 3600);
    }

    $commonvars['mylang_n'] = $user->getLanguage();
    $commonvars['flagdir_n'] = NERDZ\Core\System::getResourceDomain().'/static/images/flags/';

    $banners = (new NERDZ\Core\Banners())->getBanners();
    $commonvars['banners_a'] = [];
    shuffle($banners);
    foreach ($banners as $ban) {
        $commonvars['banners_a'][$ban[1]] = $ban[2];
    }

    // querystring_n contains the current GET query string
    // usefull to handle OAuth2 Authorization redirect
    // and to have the GET querystring parameters urlencoded in a key-value map
    $commonvars['querystring_n'] = isset($_GET) ? urlencode(implode('&', array_map(function ($k, $v) {
        return "{$k}={$v}";
    }, array_keys($_GET), array_values($_GET)))) : '';

    $user->getTPL()->assign($commonvars);
};

$func();
