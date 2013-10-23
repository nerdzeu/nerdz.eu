<?php
//TEMPLATE: OK
$mo = empty($_GET['top']);
$un_ti = ' AND ("time" + INTERVAL \'28 days\') > NOW()';
$path = $_SERVER['DOCUMENT_ROOT'].'/pages/cache/'.($mo ? 'rank.json' : 'r_month.json');

$vals['position'] = $core->lang('POSITION');
$vals['username'] = $core->lang('USERNAME');
$vals['comments'] = $core->lang('COMMENTS');
$vals['updown'] = $core->lang('UPDOWN');
$vals['stupidstuff'] = '5tUp1d stUfF!1';

if(!file_exists($path) || (filemtime($path)+3600)<time())
{
    $res = $core->query('SELECT COUNT("hcid") AS cc,"from" FROM "comments" WHERE "from" <> '.DELETED_USERS.(!$mo ? $un_ti : '').' GROUP BY "from" ORDER BY cc DESC LIMIT 100',db::FETCH_STMT);
    $rank = array();
    
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/stuff.class.php';
    while(($o = $res->fetch(PDO::FETCH_OBJ)))
    {
        $gc = $core->query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "from" = :from '.(!$mo ? $un_ti : ''),array(':from' => $o->from)),db::FETCH_OBJ);
        $us = $core->getUserName($o->from);
        $n = $o->cc + $gc->cc;
        $rank[$us] = $n;
        $stupid = stuff::stupid($n);
        $ss[$us] = $stupid['now'];
    }
    
    asort($rank);
    $rank = array_reverse($rank,true);

    $i = 0;
    $ret = array();
    
    foreach($rank as $username => $val)
    {
        $ret[$i]['position_n'] = $i+1;
        $ret[$i]['username4link_n'] =  phpCore::userLink($username);
        $ret[$i]['username_n'] = $username;
        $ret[$i]['comments_n'] = $val;
        $ret[$i]['stupidstuff_n'] = $ss[$username];
        ++$i;
    }
    file_put_contents($path,json_encode($ret));
    chmod($path,0775);
}
else
    $ret = json_decode(file_get_contents($path),true);
$vals['list_a'] = $ret;
$vals['monthly_b'] = !$mo;

$vals['lastupdate'] = $core->lang('LAST_UPDATE');
$vals['lastupdate_n'] = $core->getDateTime(filemtime($path));

$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');

$core->getTPL()->assign($vals);

$core->getTPL()->draw('base/rank');
?>
