<?php
if(!isset($id))
    die('$id required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Db;
$prj    = isset($prj);
$entity = $prj ? new Project() : new User();
$limit  = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$user   = new User();

$myvals = [];
$myvals['me_n']   = $_SESSION['id'];
$myvals['list_a'] = $entity->getInteractions($id, $limit);

$user->getTPL()->assign($myvals);
return $user->getTPL()->draw(($prj ? 'project' : 'profile').'/interactions', true);

?>
