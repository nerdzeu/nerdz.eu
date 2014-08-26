<?php
use NERDZ\Core\Db;
use \PDO;

if(!($stmt = Db::query('SELECT counter, username, motivation ,EXTRACT(EPOCH FROM "time") AS time FROM "deleted_users" ORDER BY "time" DESC', Db::FETCH_STMT)))
    echo $user->lang('ERROR');
else
{
    $i = 0;
    $ret = [];
    while(($o = $stmt->fetch(PDO::FETCH_OBJ)))
    {
        $ret[$i]['id_n']         = $o->counter;
        $ret[$i]['username_n']   = $o->username;
        $ret[$i]['datetime_n']   = $user->getDateTime($o->time);
        $ret[$i]['motivation_n'] = $o->motivation;
        ++$i;
    }

    $user->getTPL()->assign('list_a', $ret);
    $user->getTPL()->draw('base/deleted');
}
?>
