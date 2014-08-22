<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
$validFields = [ 'username', 'name', 'surname', 'birth_date', 'last', 'counter', 'registration_time' ];

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$order   = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';
$q       = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');
$orderby = isset($_GET['orderby']) ? NERDZ\Core\Security::fieldControl($_GET['orderby'], $validFields, 'username') : 'username';

$query = empty($q)
    ? "SELECT name,surname,username, counter, birth_date, EXTRACT(EPOCH FROM last) AS last
      FROM users
      ORDER BY {$orderby} {$order} LIMIT {$limit}"
    : [
          "SELECT name,surname,username, counter,birth_date,EXTRACT(EPOCH FROM last) AS last
           FROM users
           WHERE CAST({$orderby} AS TEXT) ILIKE ?
           ORDER BY {$orderby} {$order} LIMIT {$limit}",
           [
                "%{$q}%"
           ]
      ];

$vals = [];
$vals['list_a'] = [];

if(($r = Db::query($query,Db::FETCH_STMT)))
{
    $i = 0;
    while($o = $r->fetch(PDO::FETCH_OBJ))
        $vals['list_a'][$i++] = $user->getBasicInfo($o->counter);
}

\NERDZ\Core\Security::setNextAndPrevURLs($vals, $limit,
    [
        'order' => $order,
        'query' => $q,
        'field' => empty($_GET['orderby']) ? '' : $_GET['orderby'],
        'validFields' => $validFields
    ]);

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$vals['type_n'] = 'list';
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/userslist');
?>
