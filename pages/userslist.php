<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$validFields = [ 'username', 'name', 'surname', 'birth_date', 'last', 'counter', 'registration_time' ];

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$order   = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';
$q       = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');
$orderby = isset($_GET['orderby']) ? NERDZ\Core\Security::fieldControl($_GET['orderby'], $validFields, 'username') : 'username';

$query = empty($q)
    ? "SELECT counter
      FROM users
      ORDER BY {$orderby} {$order} LIMIT {$limit}"
    : [
          "SELECT counter
           FROM users
           WHERE CAST({$orderby} AS TEXT) ILIKE ?
           ORDER BY {$orderby} {$order} LIMIT {$limit}",
           [
                "%{$q}%"
           ]
      ];

$vals = [];
$users = !($stmt = Db::query($query,Db::FETCH_STMT))
    ? []
    : $stmt->fetchAll(PDO::FETCH_COLUMN);

$type = 'list';
$dateExtractor = function($friendId, $registrationDate) {
    return $registrationDate;
};
// Fetch total users number (from cache if present)
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/stats.php';
// assign $vals['totusers_n'] to $total, required by userslist.html.php
$total =  $vals['totusers_n'];
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
$user->getTPL()->draw('base/userslist');
