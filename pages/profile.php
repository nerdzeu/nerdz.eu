<?php

use NERDZ\Core\Banners;
use NERDZ\Core\Browser;
use NERDZ\Core\Config;
use NERDZ\Core\System;
use NERDZ\Core\Db;
use NERDZ\Core\Gravatar;
use NERDZ\Core\Messages;
use NERDZ\Core\Stuff;
use NERDZ\Core\Utils;

$vals = [];
$vals['logged_b'] = $user->isLogged();
$vals['id_n'] = $info->counter;

$vals['canwriteissue_b'] = false;
$vals['canwritenews_b']  = $user->isLogged() && $info->counter == $_SESSION['id'];

$banners = (new Banners())->getBanners();
$vals['banners_a'] = [];
shuffle($banners);
foreach($banners as $ban)
    $vals['banners_a'][$ban[1]] = $ban[2];

$vals['canshowmenu_b'] = $vals['logged_b'] && ($_SESSION['id'] != $info->counter);

$vals['canifollow_b'] = false;
$vals['caniblacklist_b'] = false;

if($vals['logged_b'])
{
    $vals['canifollow_b']    = !$user->isFollowing($info->counter);
    $vals['caniblacklist_b'] = !$user->hasInBlacklist($info->counter);
}

$vals['privateprofile_b'] = !$info->private;
$vals['singlepost_b']    = isset($pid)    && isset($id) && is_numeric($pid);
$vals['friends_b']       = isset($action) && $action == 'friends';
$vals['followers_b']     = isset($action) && $action == 'followers';
$vals['following_b']     = isset($action) && $action == 'following';
$vals['interactions_b']  = isset($action) && $action == 'interactions';

$enter = $vals['interactions_b'] && !$vals['logged_b']
    ? false
    : (!$vals['privateprofile_b'] && $vals['logged_b']) || $vals['privateprofile_b'];

if($enter)
{
    $vals['gravatarurl_n'] = $user->getGravatar($info->counter);

    $vals['onerrorimgurl_n'] = System::getResourceDomain().'/static/images/onErrorImg.php';
    $vals['website_n'] = $vals['website4link_n'] = empty($info->website) ? 'http://'.Config\SITE_HOST : $info->website;

    if(!preg_match('#(^http:\/\/|^https:\/\/|^ftp:\/\/)#i',$vals['website4link_n']))
        $vals['website4link_n'] = 'http://'.$vals['website4link_n'];

    $ida = [ ':id' => $info->counter ];

    if(!($o = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM "registration_time") AS registration_time from "users" WHERE "counter" = :id',
                $ida
            ],Db::FETCH_OBJ)))
            die($user->lang('ERROR'));

    $userTpl = $user->getTemplate($info->counter);
    $templates = System::getAvailableTemplates();
    $vals['template_n'] = '';

    foreach($templates as $pair) {
        if($pair['number'] == $userTpl) {
            $vals['template_n'] = $pair['name'];
            break;
        }
    }


    $vals['registrationtime_n'] = $user->getDateTime($o->registration_time);
    $vals['username_n'] = $info->username;
    $vals['username4link_n'] = Utils::userLink($info->username);
    $vals['lang_n'] = $user->getLanguage($info->counter);
    $vals['online_b'] = $user->isOnline($info->counter);

    $vals['name_n'] = ucfirst($info->name);
    $vals['surname_n'] = ucfirst($info->surname);

    list($year, $month, $day) = explode('-',$info->birth_date);
    $vals['birthdate_n'] = $day.'/'.$month.'/'.$year;

    $apc_name = 'userstuff'.$info->counter.Config\SITE_HOST;
    if(!($stuff = Utils::apc_get($apc_name))) {
        $stuff = Utils::apc_set($apc_name, function() use($user, $ida) {
            if(!($o = Db::query(
                [
                    'SELECT COUNT("hcid") AS cc FROM "comments" WHERE "from" = :id',
                        $ida
                    ],Db::FETCH_OBJ)
                ))
                die($user->lang('ERROR'));

            $n = $o->cc;

            if(!($o = Db::query(
                [
                    'SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "from" = :id',
                        $ida
                    ],Db::FETCH_OBJ)
                ))
                die($user->lang('ERROR'));

            $n += $o->cc;
            $a = Stuff::stupid($n);
            $a['n'] = $n;
            return $a;
        }, 300);
    }

    $vals['stupidstuffnow_n']  = $stuff['now'];
    $vals['stupidstuffnext_n'] = $stuff['next'];
    $vals['stupidstuffless_n'] = $stuff['less'];
    $vals['totalcomments_n']   = $stuff['n'];

    if(!($o = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM "last") AS last from "users" WHERE "counter" = :id',
                $ida
            ],Db::FETCH_OBJ)
        ))
        die($user->lang('ERROR'));

    $vals['following_n'] = $user->getFollowingCount($info->counter);
    $vals['followers_n'] = $user->getFollowersCount($info->counter);
    $vals['friends_n']   = $user->getFriendsCount($info->counter);

    $vals['lastvisit_n'] = $user->getDateTime($o->last);

    if(!$vals['singlepost_b'] && !$vals['friends_b'] && !$vals['followers_b'] && !$vals['following_b'] && !$vals['interactions_b'])
    {
        if(!$user->hasClosedProfile($info->counter))
            $vals['canwrite_b'] = true;
        else
            $vals['canwrite_b'] = $vals['logged_b'] && ($info->counter == $_SESSION['id'] || in_array($_SESSION['id'],$user->getWhitelist($info->counter)));
    } else {
        $vals['canwrite_b'] = false; // don't show textarea when in a singlepost
    }

    $vals['useragent_a'] = (new Browser($info->http_user_agent))->getArray();

    $vals['gender_n'] = $user->lang($info->gender == 1 ? 'MALE' : 'FEMALE');

    $vals['karmaposts_n']    = $user->getKarma('post', $info->counter);
    $vals['karmacomments_n'] = $user->getKarma('comment', $info->counter);

    $vals['biography_n'] = (new Messages())->bbcode($info->biography,1);
    $vals['quotes_a'] = explode("\n",trim($info->quotes));
    if(count($vals['quotes_a']) == 1 && empty($vals['quotes_a'][0]))
        $vals['quotes_a'] = [];
    else
        foreach($vals['quotes_a'] as $qid => $val)
        {
            $vals['quotes_a'][$qid] = trim($val);
            if(empty($vals['quotes_a'][$qid]))
                unset($vals['quotes_a'][$qid]);
        }

    $vals['interests_a'] = explode("\n",$info->interests);
    if(count($vals['interests_a']) == 1 && empty($vals['interests_a'][0]))
        $vals['interests_a'] = [];
    else
        foreach($vals['interests_a'] as $qid => $val)
        {
            $vals['interests_a'][$qid] = trim($val);
            if(empty($vals['interests_a'][$qid]))
                unset($vals['interests_a'][$qid]);
        }

    if(!($r = Db::query(
        [
            'SELECT "name"
            FROM "groups" g INNER JOIN "groups_owners" go
            ON go."to" = g.counter
            WHERE go."from" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
    die($user->lang('ERROR'));

    $vals['ownerof_a'] = [];
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['ownerof_a'][$i]['name_n'] = $o->name;
        $vals['ownerof_a'][$i]['username_n'] = $o->name;
        $vals['ownerof_a'][$i]['name4link_n'] = Utils::projectLink($o->name);
        ++$i;
    }
    usort($vals['ownerof_a'],'\\NERDZ\\Core\\Utils::sortByUsername');

    if(!($r = Db::query(
        [
            'SELECT "name" FROM "groups" INNER JOIN "groups_members" ON "groups"."counter" = "groups_members"."to" WHERE "from" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
    die($user->lang('ERROR'));

    $vals['memberof_a'] = [];
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['memberof_a'][$i]['name_n'] = $o->name;
        $vals['memberof_a'][$i]['username_n'] = $o->name;
        $vals['memberof_a'][$i]['name4link_n'] = Utils::projectLink($o->name);
        ++$i;
    }

    usort($vals['memberof_a'],'\\NERDZ\\Core\\Utils::sortByUsername');

    if(!($r = Db::query(
        [
            'SELECT "name" FROM "groups" INNER JOIN "groups_followers" ON "groups"."counter" = "groups_followers"."to" WHERE "from" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
    die($user->lang('ERROR'));

    $vals['userof_a'] = [];
    $i = 0;
    while(($o =$r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['userof_a'][$i]['name_n'] = $o->name;
        $vals['userof_a'][$i]['username_n'] = $o->name;
        $vals['userof_a'][$i]['name4link_n'] = Utils::projectLink($o->name);
        ++$i;
    }

    usort($vals['userof_a'],'\\NERDZ\\Core\\Utils::sortByUsername');

    $vals['github_n']   = $info->github;
    $vals['yahoo_n']    = $vals['logged_b'] ? $info->yahoo    : '';
    $vals['jabber_n']   = $vals['logged_b'] ? $info->jabber   : '';
    $vals['skype_n']    = $vals['logged_b'] ? $info->skype    : '';
    $vals['steam_n']    = $vals['logged_b'] ? $info->steam    : '';
    $vals['facebook_n'] = $vals['logged_b'] ? $info->facebook : '';
    $vals['twitter_n']  = $vals['logged_b'] ? $info->twitter  : '';
    $vals['id_n']       = $id;

    // single post like nessuno.1
    $found = false;
    if($vals['singlepost_b'])
    {
        if($user->hasInBlacklist($id)) //fake post not found [ same trick in the header ]
        {
            $user->getTPL()->assign('banners_a',$vals['banners_a']);
            require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
            $user->getTPL()->draw('profile/postnotfound');
        }
        elseif(!($post = Db::query(
            [
                'SELECT "hpid" FROM "posts" WHERE "pid" = :pid AND "to" = :id',
                array_merge(
                    [ ':pid' => $pid ],
                    $ida
                )
            ]
            ,Db::FETCH_OBJ)
        ))
        {
            $user->getTPL()->assign('banners_a',$vals['banners_a']);
            require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
            $user->getTPL()->draw('profile/postnotfound');
        }
        else
        {
            // required for singlepost
            $hpid = $post->hpid;
            $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/profile/singlepost.html.php';
            $found = true;
        }
    } elseif($vals['friends_b']) {
        $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/profile/friends.html.php';
    } elseif($vals['following_b']) {
        $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/profile/following.html.php';
    } elseif($vals['followers_b']) {
        $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/profile/followers.html.php';
    } elseif($vals['interactions_b']) {
         $vals['post_n'] = require $_SERVER['DOCUMENT_ROOT'].'/pages/profile/interactions.html.php';
    }

    if(($vals['singlepost_b'] && $found) || !$vals['singlepost_b'])
    {
        $user->getTPL()->assign($vals);
        require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
        $user->getTPL()->draw('profile/layout');
    }
}
else
{
    $included = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/register.php';
    $vals['presentation_n'] = ''; // delete the presentation
    $user->getTPL()->assign($vals);
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
    $user->getTPL()->draw('profile/closed');
}
