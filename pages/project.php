<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';//ok qui
$core = new project();

$vals = [];
$enter = true;

$vals['logged_b'] = $core->isLogged();

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

if(($info->private && !$vals['logged_b']) || (!$info->visible && !$vals['logged_b']))
{
    $included = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/register.php';
    $core->getTPL()->assign($vals);
    $core->getTPL()->draw('project/private');
}
else
{
    $mem = $core->getMembers($info->counter);
    $icansee = true;
    if($vals['logged_b'] && !$info->visible)
        $icansee = $_SESSION['nerdz_id'] == $info->owner || in_array($_SESSION['nerdz_id'],$mem);

    if(!$icansee)
    {
        $core->getTPL()->assign($vals);
        $core->getTPL()->draw('project/invisible');
    }
    else
    {
        function sortbyusername($a, $b)
        {
            return (strtolower($a['username_n']) < strtolower($b['username_n'])) ? -1 : 1;
        }

        $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        $domain = $ssl ? '' : STATIC_DOMAIN;
        $vals['photo_n'] = messages::imgValidUrl($info->photo, $domain, $ssl);
        $vals['onerrorimgurl_n'] = $domain.'/static/images/onErrorImg.php';
        $vals['id_n'] = $info->counter;

        $vals['name_n'] = $info->name;
        $vals['name4link_n'] =  phpCore::projectLink($info->name);

        $vals['members_n'] = count($mem);
        $vals['members_a'] = [];
        $i = 0;
        foreach($mem as $uid)
        {
            if(!($uname = $core->getUsername($uid)))
                continue;
            $vals['members_a'][$i]['username_n'] = $uname;
            $vals['members_a'][$i]['username4link_n'] =  phpCore::userLink($uname);
            ++$i;
        }

        usort($vals['members_a'],'sortbyusername');


        $fol = $core->getFollowers($info->counter);
        $vals['users_n'] = count($fol);
        $vals['users_a'] = [];
        $i = 0;
        foreach($fol as $uid)
        {
            if(!($uname = $core->getUsername($uid)))
                continue;
            $vals['users_a'][$i]['username_n'] = $uname;
            $vals['users_a'][$i]['username4link_n'] = phpCore::userLink($uname);
            ++$i;
        }
        usort($vals['users_a'],'sortbyusername');

        $vals['owner_n'] = $core->getUsername($info->owner);
        $vals['owner4link_n'] =  phpCore::userLink($vals['owner_n']);

        $vals['description_n'] = $messages->bbcode($info->description);

        $vals['goal_n'] = $messages->bbcode($info->goal);

        $vals['website_n'] = $vals['website4link_n'] = empty($info->website) ? 'http://www.nerdz.eu/' : $info->website;

        $vals['openproject_b'] = $core->isOpen($info->counter);
        
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/banners.class.php';
        $banners = (new banners())->getBanners();
        $vals['banners_a'] = [];
        shuffle($banners);
        foreach($banners as $ban)
            $vals['banners_a'][$ban[1]] = $ban[2];

        $vals['canifollow_b'] = $vals['logged_b'] && !in_array($_SESSION['nerdz_id'],array_merge($mem,$fol));

        $vals['canshowmenu_b'] = $vals['logged_b'] && ($_SESSION['nerdz_id'] != $info->owner);

        $vals['singlepost_b'] = isset($pid) && isset($gid) && is_numeric($pid);

        $vals['canwrite_b'] = $vals['logged_b'] && ($core->isOpen($gid) || in_array($_SESSION['nerdz_id'],$mem) || ($_SESSION['nerdz_id'] == $info->owner));
        $vals['canwritenews_b'] = $vals['logged_b'] && (in_array($_SESSION['nerdz_id'],$mem) || ($_SESSION['nerdz_id'] == $info->owner));

        // solo qui ci sarà la pagina statica, per i posts singoli
        // per il profilo intero, è inutile anche perché si aggiorna sempr
        $found = false;
        if($vals['singlepost_b'])
        {
            if(!($post = $core->query(array('SELECT "hpid" FROM "groups_posts" WHERE "pid" = :pid AND "to" = :gid',array(':pid' => $pid, ':gid' => $gid)),db::FETCH_OBJ)))
            {
                $core->getTPL()->assign('banners_a',$vals['banners_a']);
                $core->getTPL()->draw('project/postnotfound');
            }
            else
            {
                $hpid = $post->hpid; //IL REQUIRE QUI SOTTO NECESSITA DA QUESTO
                $draw = false; // e di questo
                $included = true; //che evita che venga ciamato gzhandler di nuovo
                require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/singlepost.html.php';//qui vals ha un altro nome
                $vals['post_n'] = $singlepost;
                $found = true;
            }
        }
        if(($vals['singlepost_b'] && $found) || (!$vals['singlepost_b']))
        {
            $core->getTPL()->assign($vals);
            $core->getTPL()->draw('project/layout');
        }
    }
}
