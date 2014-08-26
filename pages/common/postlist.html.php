<?php
// require_once $prj, $truncate, $path variables
if(!isset($prj, $truncate, $path))
    die('$prj, $truncate, $path required');

ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core;

ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$user     = new NERDZ\Core\User();
$messages = new NERDZ\Core\Messages();

$logged   = $user->isLogged();

// boards
$id         = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;
$limit      = isset($_POST['limit']) ? NERDZ\Core\Security::limitControl($_POST['limit'],10)     : 10;
$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

// homepage
if(isset($_POST['onlyfollowed']))
{
    $lang = false;
    $onlyfollowed = true;
}
else
{
    $lang = isset($_POST['lang']) ? $_POST['lang'] : false;
    $onlyfollowed = false;
}

$vote    = isset($_POST['vote']) && is_string($_POST['vote']) ? trim($_POST['vote']) : false;

//search
$specific = isset($_GET['specific']);
$action   = isset($_GET['action']) && $_GET['action'] === 'profile' ? 'profile' : 'project';
$search   = !empty($_POST['q']) ? trim(htmlspecialchars($_POST['q'], ENT_QUOTES,'UTF-8')) : false;
//rewrite $path if searching not in home
if($specific) {
    $path = $action;
    $prj = $action == 'project';
}

$vals = [];

$vals['list_a'] = $messages->getPosts($id,
    array_merge(
        [ 'project'  => $prj ],
        [ 'truncate' => true ], // always truncate in postlist
        [ 'inHome'   => !$id ],
        [ 'vote'     => $vote],
        $limit          ? [ 'limit'        => $limit ]         : [],
        $beforeHpid     ? [ 'hpid'         => $beforeHpid ]    : [],
        $onlyfollowed   ? [ 'onlyfollowed' => $onlyfollowed ]  : [],
        $lang           ? [ 'lang'         => $lang ]          : [],
        $search         ? [ 'search'       => $search ]        : []
    ));

if(empty($vals['list_a']) || (!$logged && $beforeHpid))
    die(''); //empty so javascript client code stop making requsts

$vals['count_n'] = count($vals['list_a']);

$user->getTPL()->assign($vals);
$user->getTPL()->draw($path.'/postlist');
?>
