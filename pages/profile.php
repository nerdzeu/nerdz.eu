<?php

use NERDZ\Core\Banners;
use NERDZ\Core\Browser;
use NERDZ\Core\Config;
use NERDZ\Core\Db;
use NERDZ\Core\Gravatar;
use NERDZ\Core\Messages;
use NERDZ\Core\Stuff;

$vals = [];
$vals['logged_b'] = $core->isLogged();
$vals['id_n'] = $info->counter;

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

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
    $vals['canifollow_b'] = 0 == $core->query(
        [
            'SELECT "to" FROM "follow" WHERE "from" = :me AND "to" = :id',
            [
                ':me' => $_SESSION['id'],
                ':id' => $info->counter
            ]
        ],Db::ROW_COUNT);

    $vals['caniblacklist_b'] = 0 == $core->query(
        [
            'SELECT "to" FROM "blacklist" WHERE "from" = :me AND "to" = :id',
            [
                ':me' => $_SESSION['id'],
                ':id' => $info->counter
            ]
        ], db::ROW_COUNT);
}

$vals['privateprofile_b'] = !$info->private;
$enter = (!$vals['privateprofile_b'] && $vals['logged_b']) || ($vals['privateprofile_b']);

if($enter)
{
    $vals['gravatarurl_n'] = (new Gravatar())->getURL($info->counter);

    $vals['onerrorimgurl_n'] = Config\STATIC_DOMAIN.'/static/images/onErrorImg.php';
    $vals['website_n'] = $vals['website4link_n'] = empty($info->website) ? 'http://'.Config\SITE_HOST : $info->website;

    if(!preg_match('#(^http:\/\/|^https:\/\/|^ftp:\/\/)#i',$vals['website4link_n']))
        $vals['website4link_n'] = 'http://'.$vals['website4link_n'];

    $ida = [ ':id' => $info->counter ];

    if(!($o = $core->query(
        [
            'SELECT EXTRACT(EPOCH FROM "registration_time") AS registration_time from "users" WHERE "counter" = :id',
            $ida
        ],Db::FETCH_OBJ)))
            die($core->lang('ERROR'));

    $vals['registrationtime_n'] = $core->getDateTime($o->registration_time);
    $vals['username_n'] = $info->username;
    $vals['username4link_n'] = \NERDZ\Core\Core::userLink($info->username);
    $vals['lang_n'] = $core->getUserLanguage($info->counter);
    $vals['online_b'] = $core->isOnline($info->counter);

    $vals['name_n'] = ucfirst($info->name);
    $vals['surname_n'] = ucfirst($info->surname);

    list($year, $month, $day) = explode('-',$info->birth_date);
    $vals['birthdate_n'] = $day.'/'.$month.'/'.$year;

    $apc_name = 'count_comments_'.$info->counter.Config\SITE_HOST;

    if(!apc_exists($apc_name))
    {

        if(!($o = $core->query(
            [
                'SELECT COUNT("hcid") AS cc FROM "comments" WHERE "from" = :id',
                $ida
            ],Db::FETCH_OBJ)
        ))
            die($core->lang('ERROR'));

        $n = $o->cc;

        if(!($o = $core->query(
            [
                'SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "from" = :id',
                $ida
            ],Db::FETCH_OBJ)
        ))
            die($core->lang('ERROR'));

        $n += $o->cc;
        $a = Stuff::stupid($n);
        $a['n'] = $n;

        @apc_store($apc_name,serialize($a),300);
    }
    else
        $a = unserialize(apc_fetch($apc_name));

    $vals['stupidstuffnow_n'] = $a['now'];
    $vals['stupidstuffnext_n'] = $a['next'];
    $vals['stupidstuffless_n'] = $a['less'];

    $vals['totalcomments_n'] = $a['n'];

    if(!($o = $core->query(
        [
            'SELECT EXTRACT(EPOCH FROM "last") AS last from "users" WHERE "counter" = :id',
            $ida
        ],Db::FETCH_OBJ)
    ))
        die($core->lang('ERROR'));

    $vals['lastvisit_n'] = $core->getDateTime($o->last);

    if(!$core->closedProfile($info->counter))
        $vals['canwrite_b'] = true;
    else
        $vals['canwrite_b'] = $vals['logged_b'] && ($info->counter == $_SESSION['id'] || in_array($_SESSION['id'],$core->getWhitelist($info->counter)));

    $vals['useragent_a'] = (new Browser($info->http_user_agent))->getArray();

    $f = $core->getFriends($info->counter);

    if(!empty($f))
    {
        function sortbyusername($a, $b)
        {
            return (strtolower($a['username_n']) < strtolower($b['username_n'])) ? -1 : 1;
        }

        $amigos = [];
        $c = 0;
        foreach($f as $val)
            if(($name = $core->getUserName($val)))
            {
                $amigos[$c]['username_n'] = $name;
                $amigos[$c]['username4link_n'] = \NERDZ\Core\Core::userLink($name);
                ++$c;
            }

        usort($amigos,'sortbyusername');
    }
    else
        $amigos = [];

    $vals['gender_n'] = $core->lang($info->gender == 1 ? 'MALE' : 'FEMALE');

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

    if(!($r = $core->query(
        [
            'SELECT "name" FROM "groups" WHERE "owner" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
        die($core->lang('ERROR'));

    $vals['ownerof_a'] = [];
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['ownerof_a'][$i]['name_n'] = $o->name;
        $vals['ownerof_a'][$i]['name4link_n'] = \NERDZ\Core\Core::projectLink($o->name);
        ++$i;
    }

    if(!($r = $core->query(
        [
            'SELECT "name" FROM "groups" INNER JOIN "groups_members" ON "groups"."counter" = "groups_members"."group" WHERE "user" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
        die($core->lang('ERROR'));

    $vals['memberof_a'] = [];
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['memberof_a'][$i]['name_n'] = $o->name;
        $vals['memberof_a'][$i]['name4link_n'] = \NERDZ\Core\Core::projectLink($o->name);
        ++$i;
    }

    if(!($r = $core->query(
        [
            'SELECT "name" FROM "groups" INNER JOIN "groups_followers" ON "groups"."counter" = "groups_followers"."group" WHERE "user" = :id',
            $ida
        ],Db::FETCH_STMT)
    ))
        die($core->lang('ERROR'));

    $vals['userof_a'] = [];
    $i = 0;
    while(($o =$r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['userof_a'][$i]['name_n'] = $o->name;
        $vals['userof_a'][$i]['name4link_n'] = \NERDZ\Core\Core::projectLink($o->name);
        ++$i;
    }


    $vals['github_n']   = $info->github;
    $vals['yahoo_n']    = $vals['logged_b'] ? $info->yahoo    : '';
    $vals['jabber_n']   = $vals['logged_b'] ? $info->jabber   : '';
    $vals['skype_n']    = $vals['logged_b'] ? $info->skype    : '';
    $vals['steam_n']    = $vals['logged_b'] ? $info->steam    : '';
    $vals['facebook_n'] = $vals['logged_b'] ? $info->facebook : '';
    $vals['twitter_n']  = $vals['logged_b'] ? $info->twitter  : '';
    $vals['id_n']       = $id;

    $vals['totalfriends_n'] = isset($c) ? $c : 0;
    $vals['friends_a'] = $amigos;
    $vals['singlepost_b'] = isset($pid) && isset($id) && is_numeric($pid);

    // single post like nessuno.1
    $found = false;
    if($vals['singlepost_b'])
    {
        if(!($post = $core->query(
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
            $core->getTPL()->assign('banners_a',$vals['banners_a']);
            $core->getTPL()->draw('profile/postnotfound');
        }
        else
        {
            // The require_once below needs this 3 variables
            $hpid = $post->hpid;
            $draw = false;
            $included = true; // avoid to call gzhandler
            require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/singlepost.html.php'; // here $vals has another name
            $vals['post_n'] = $singlepost;
            $found = true;
        }
    }
    if(($vals['singlepost_b'] && $found) || (!$vals['singlepost_b']))
    {
        $core->getTPL()->assign($vals);
        $core->getTPL()->draw('profile/layout');
    }
}
else
{
    $included = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/register.php';
    $vals['presentation_n'] = ''; //cancello la presentazione
    $core->getTPL()->assign($vals);
    $core->getTPL()->draw('profile/closed');
}
?>
