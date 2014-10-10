<?php
use NERDZ\Core\Trend;
use NERDZ\Core\Utils;
use NERDZ\Core\Config;
use NERDZ\Core\User;
use NERDZ\Core\Project;

$vals = [];
$vals['querystring_n'] = $q;
$vals['type_n'] = !preg_match('/^#[a-z][a-z0-9]{0,33}$/i', $q) && isset($_GET['type'])
    ? (
        $_GET['type'] == 'profile'
        ? 'profile'
        : 'project'
    ) : 'tag';

if($vals['type_n'] == 'tag') {
    $vals['where_n'] = 'home';
    $vals['toid_n'] = $vals['to_n'] = $vals['to4link_n'] = '';
}
else
{
    $prj =  $vals['type_n'] == 'project';

    $vals['where_n'] = isset($_GET['location'])
        ? (
            $_GET['location'] == 'home'
            ? 'home'
            : (
                $_GET['location'] == 'profile'
                ? 'profile'
                : 'project'
            )
        ) : 'home';

    $vals['toid_n'] = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : false;
    if($vals['toid_n']) {
        if($prj) {
            $vals['to_n'] = Project::getName($vals['toid_n']);
            $vals['to4link_n'] = Utils::projectLink($vals['to_n']);
        } else {
            $vals['to_n'] = User::getUsername($vals['toid_n']);
            $vals['to4link_n'] = Utils::userLink($vals['to_n']);
        }
    } else {
        $vals['toid_n'] = $vals['to_n'] = $vals['to4link_n'] = '';
    }
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/trends.html.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('search/layout');
?>
