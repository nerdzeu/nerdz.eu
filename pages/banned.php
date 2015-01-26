<?php
use NERDZ\Core\Db;
use \PDO;

if(!($stmt = Db::query('SELECT u.username, b.user, b.motivation ,EXTRACT(EPOCH FROM "time") AS time FROM "ban" b JOIN "users" u ON u.counter = b.user ORDER BY "time" DESC', Db::FETCH_STMT)))
    echo $user->lang('ERROR');
else
{
    $i = 0;
    $ret = [];
    while(($o = $stmt->fetch(PDO::FETCH_OBJ)))
    {
        $ret[$i]['id_n']         = $o->user;
        $ret[$i]['username_n']   = $o->username;
        $ret[$i]['datetime_n']   = $user->getDateTime($o->time);
        $ret[$i]['motivation_n'] = $o->motivation;
        ++$i;
    }

    $user->getTPL()->assign('list_a', $ret);
    $user->getTPL()->draw('base/banned');
}
