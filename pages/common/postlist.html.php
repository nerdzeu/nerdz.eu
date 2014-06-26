<?php
// require $prj, $truncate, $path variables
if(!isset($prj, $truncate, $path))
    die('$prj, $truncate, $path required');

ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

ob_start(array('phpCore','minifyHtml'));

$core     = new phpCore();
$messages = new messages();
$comments = new comments();

$logged   = $core->isLogged();
$id       = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

if($logged && is_numeric(strpos($_SERVER['REQUEST_URI'],'refresh.html.php')) && (true === $core->isInBlacklist($_SESSION['nerdz_id'],$id)))
    die('Hax0r c4n\'t fuck nerdz pr00tectionz');

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
    if(($lang = isset($_POST['lang']) ? $_POST['lang'] : false))
    {
        $languages = $core->availableLanguages();
        $languages[] = '*'; //any language
        if(!in_array($lang,$languages))
            $lang = false;
    }
    $onlyfollowed = false;
}

//search
$specific = isset($_GET['specific']);
$action   = isset($_GET['action']) && $_GET['action'] === 'profile' ? 'profile' : 'project';
$search   = !empty($_POST['q']) && $specific && $id ? trim(htmlspecialchars($_POST['q'], ENT_QUOTES,'UTF-8')) : false;
//rewrite $path if searching not in home
if($specific) {
    $path = $action;
    $prj = $action == 'project';
}

$mess = $messages->getMessages($id, $limit,
        array_merge(
            [ 'project' => $prj ],
            $beforeHpid     ? [ 'hpid'         => $beforeHpid ]    : [],
            $onlyfollowed   ? [ 'onlyfollowed' => $onlyfollowed ]  : [],
            $lang           ? [ 'lang'         => $lang ]          : [],
            $search         ? [ 'search'       => $search ]        : []
        ));

if(!$mess || (!$logged && $beforeHpid))
    die(''); //empty so javascript client code stop making requsts

$vals = [];
$vals['count_n'] = count($mess);
$vals['list_a'] = $messages->getPostList($mess, $prj, $truncate);
$core->getTPL()->assign($vals);
$core->getTPL()->draw($path.'/postlist');
?>
