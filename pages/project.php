<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Banners;
use NERDZ\Core\Db;
use NERDZ\Core\Project;
use NERDZ\Core\Utils;
use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Messages;

$project  = new Project();
$messages = new Messages();
$user     = new User();

$vals = [];
$enter = true;

$vals['logged_b'] = $user->isLogged();

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

if(($info->private && !$vals['logged_b']) || (!$info->visible && !$vals['logged_b']))
{
    $included = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/register.php';
    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('project/private');
}
else
{
    $mem = $project->getMembers($info->counter);
    $icansee = true;
    if($vals['logged_b'] && !$info->visible)
        $icansee = $_SESSION['id'] == $info->owner || in_array($_SESSION['id'],$mem);

    if(!$icansee)
    {
        $user->getTPL()->assign($vals);
        $user->getTPL()->draw('project/invisible');
    }
    else
    {
        $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        $domain = $ssl ? '' : Config\STATIC_DOMAIN;
        $vals['photo_n'] = Utils::getValidImageURL($info->photo, $domain, $ssl);
        $vals['onerrorimgurl_n'] = $domain.'/static/images/onErrorImg.php';
        $vals['id_n'] = $info->counter;

        $vals['name_n'] = $info->name;
        $vals['name4link_n'] =  \NERDZ\Core\Utils::projectLink($info->name);

        $vals['members_n'] = count($mem);
        $vals['members_a'] = [];
        $i = 0;
        foreach($mem as $uid)
        {
            if(!($uname = User::getUsername($uid)))
                continue;
            $vals['members_a'][$i]['username_n'] = $uname;
            $vals['members_a'][$i]['username4link_n'] = \NERDZ\Core\Utils::userLink($uname);
            ++$i;
        }
        usort($vals['members_a'],'NERDZ\\Core\\Utils::sortByUsername');

        $fol = $project->getFollowers($info->counter);
        $vals['users_n'] = count($fol);
        $vals['users_a'] = [];
        $i = 0;
        foreach($fol as $uid)
        {
            if(!($uname = User::getUsername($uid)))
                continue;
            $vals['users_a'][$i]['username_n'] = $uname;
            $vals['users_a'][$i]['username4link_n'] = \NERDZ\Core\Utils::userLink($uname);
            ++$i;
        }
        usort($vals['users_a'],'NERDZ\\Core\\Utils::sortByUsername');

        $vals['owner_n'] = User::getUsername($info->owner);
        $vals['owner4link_n'] =  \NERDZ\Core\Utils::userLink($vals['owner_n']);

        $vals['description_n'] = $messages->bbcode($info->description);

        $vals['goal_n'] = $messages->bbcode($info->goal);

        $vals['website_n'] = $vals['website4link_n'] = empty($info->website) ? 'http://'.Config\SITE_HOST.'/' : $info->website;

        $vals['openproject_b'] = $project->isOpen($info->counter);

        $banners = (new Banners())->getBanners();
        $vals['banners_a'] = [];
        shuffle($banners);
        foreach($banners as $ban)
            $vals['banners_a'][$ban[1]] = $ban[2];

        $vals['canifollow_b'] = $vals['logged_b'] && !in_array($_SESSION['id'],array_merge($mem,$fol));

        $vals['canshowmenu_b'] = $vals['logged_b'] && ($_SESSION['id'] != $info->owner);

        $vals['singlepost_b'] = isset($pid) && isset($gid) && is_numeric($pid);
        $vals['followers_b']     = isset($action) && $action == 'followers';
        $vals['interactions_b']  = isset($action) && $action == 'interactions';

        if(!$vals['singlepost_b'] && !$vals['followers_b'] && !$vals['interactions_b'])
        {
            $vals['canwrite_b']      = $vals['logged_b'] && ($project->isOpen($gid) || in_array($_SESSION['id'],$mem) || ($_SESSION['id'] == $info->owner));
            $vals['canwriteissue_b'] = $vals['logged_b'] && ($info->counter == Config\ISSUE_BOARD);

            $vals['canwritenews_b']  = !$vals['canwriteissue_b'] && $vals['logged_b'] && (in_array($_SESSION['id'],$mem) || ($_SESSION['id'] == $info->owner));

        }
        else
        {
            // don't show textarea when in a singlepost
            $vals['canwritenews_b'] = $vals['canwrite_b'] = $vals['canwriteissue_b'] = false;
        }

        // single post handling
        $found = false;
        if($vals['singlepost_b'])
        {
            if(!($post = Db::query(
                [
                    'SELECT "hpid","from" FROM "groups_posts" WHERE "pid" = :pid AND "to" = :gid',
                    [
                        ':pid' => $pid,
                        ':gid' => $gid
                    ]
                ],Db::FETCH_OBJ))
                || $user->hasInBlacklist($post->from) // fake post not found
            )
            {
                $user->getTPL()->assign('banners_a',$vals['banners_a']);
                $user->getTPL()->draw('project/postnotfound');
            }
            else
            {
                // requiired by singlepost
                $hpid = $post->hpid;
                $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/project/singlepost.html.php';
                $found = true;
            }
        }
        elseif($vals['followers_b']) {
            $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/project/followers.html.php';
        } elseif($vals['interactions_b']) {
            $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/project/interactions.html.php';
        }

        if(($vals['singlepost_b'] && $found) || (!$vals['singlepost_b']))
        {
            $user->getTPL()->assign($vals);
            $user->getTPL()->draw('project/layout');
        }
    }
}
