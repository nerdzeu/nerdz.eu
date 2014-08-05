<?php
// require_once $prj, $truncate, $path variables
if(!isset($prj, $truncate, $path))
    die('$prj, $truncate, $path required');

ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

ob_start(array('Core','minifyHtml'));

$core     = new Core();
$Messages = new Messages();
$comments = new Comments();

$logged   = $core->isLogged();
$id       = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

// boards
$limit      = isset($_POST['limit']) ? $core->limitControl($_POST['limit'],10)     : 10;
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

//search
$specific = isset($_GET['specific']);
$action   = isset($_GET['action']) && $_GET['action'] === 'profile' ? 'profile' : 'project';
$search   = !empty($_POST['q']) ? trim(htmlspecialchars($_POST['q'], ENT_QUOTES,'UTF-8')) : false;
//rewrite $path if searching not in home
if($specific) {
    $path = $action;
    $prj = $action == 'project';
}

$mess = $Messages->getMessages($id,
    array_merge(
        [ 'project' => $prj ],
        $limit          ? [ 'limit'        => $limit ]         : [],
        $beforeHpid     ? [ 'hpid'         => $beforeHpid ]    : [],
        $onlyfollowed   ? [ 'onlyfollowed' => $onlyfollowed ]  : [],
        $lang           ? [ 'lang'         => $lang ]          : [],
        $search         ? [ 'search'       => $search ]        : []
    ));

if(!$mess || (!$logged && $beforeHpid))
    die(''); //empty so javascript client code stop making requsts

$vals = [];
$vals['count_n'] = count($mess);
$vals['list_a'] = $Messages->getPostList($mess, $prj, $truncate);
$core->getTPL()->assign($vals);
$core->getTPL()->draw($path.'/postlist');
?>
