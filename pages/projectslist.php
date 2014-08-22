<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$validFields = [ 'name', 'description' ];

$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'],20) : 20;
$order = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';
$q     = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');
$orderby = isset($_GET['orderby']) ? NERDZ\Core\Security::fieldControl($_GET['orderby'], $validFields, 'name') : 'name';

$vals = [];

$query = empty($q)
    ? "SELECT name, description,counter
      FROM groups
      ORDER BY {$orderby} {$order} LIMIT {$limit}"
    : [
          "SELECT name,description, counter
           FROM groups WHERE CAST({$orderby} AS TEXT) ILIKE ?
           ORDER BY {$orderby} {$order} LIMIT {$limit}",
           [
               "%{$q}%"
           ]
      ];

$vals['list_a'] = [];

if(($r = Db::query($query,Db::FETCH_STMT)))
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['list_a'][$i]['id_n'] = $o->counter;
        $vals['list_a'][$i]['name_n'] = $o->name;
        $vals['list_a'][$i]['description_n'] = $o->description;
        $vals['list_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
        ++$i;
    }
}

\NERDZ\Core\Security::setNextAndPrevURLs($vals, $limit,
    [
        'order' => $order,
        'query' => $q,
        'field' => empty($_GET['orderby']) ? '' : $_GET['orderby'],
        'validFields' => $validFields
    ]);

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/projectslist');
?>
