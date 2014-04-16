<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new project();

if(!$core->isLogged())
    die(header('Location: /'));

$limit = isset($_POST['limit']) ? $core->limitControl($_POST['limit'],10) : 10;

if( empty($_POST['q']) || (!empty($_GET['specific']) && empty($_POST['id'])) )
    die();

if(isset($_POST['id']) && !is_numeric($_POST['id']))
    die('2');

$txt = trim(htmlentities($_POST['q'],ENT_QUOTES,'UTF-8'));

$blist = $core->getBlacklist();
$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

$vals = array();
$group = false;
$specific = isset($_GET['specific']);
$query_param = array_merge(array(':like' => '%'.$txt.'%') ,$specific ? array(':to' => $_POST['id']) : array(), $beforeHpid ? array (':hpid' => $beforeHpid) : array());

switch(isset($_GET['action']) ? trim(strtolower($_GET['action'])) : '')
{
    case 'profile':
        if(empty($blist))
            $glue = '';
        else
        {
            $imp_blist = implode(',',$blist);
            $glue = 'AND "posts"."from" NOT IN ('.$imp_blist.') AND "posts"."to" NOT IN ('.$imp_blist.')';
        }

        if(!($k = $core->query(
                    array('SELECT "from","to","pid","message",EXTRACT(EPOCH FROM "time") AS time,"hpid" FROM "posts" WHERE "message" ILIKE :like '.$glue.($specific ? ' AND "to" = :to' : '').($beforeHpid ? ' AND "hpid" < :hpid' : '').' ORDER BY "hpid" DESC LIMIT '.$limit,
                        $query_param
                ),db::FETCH_STMT))
            )
            die($core->lang('ERROR'));
    break;
    
    case 'project':
        $group = true;
        $glue = ' AND "groups_posts"."to" NOT IN (SELECT "counter" FROM "groups" WHERE "visible" IS FALSE) ';
        if(!empty($blist))
        {
            $imp_blist = implode(',',$blist);
            $glue .= ' AND "groups_posts"."from" NOT IN ('.$imp_blist.') ';
        }

        if(!($k = $core->query(
                    array('SELECT "from","to","pid","message",EXTRACT(EPOCH FROM "time") AS time,"hpid" FROM "groups_posts" WHERE "message" ILIKE :like '.$glue.($specific ? ' AND "to" = :to' : '').($beforeHpid ? ' AND "hpid" < :hpid' : '').' ORDER BY "hpid" DESC LIMIT '.$limit,
                    $query_param
                ),db::FETCH_STMT))
            )
            die($core->lang('ERROR'));
    break;
    default:
        die($core->lang('ERROR'));
    break;
}

//variabile $mess necessaria per le pagine sotto
$mess = $core->getPostsArray($k,$group);

//per le miniature!
$miniature = true;
if($group)
    //come sotto
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';
else
    //fa tutto il loop e assegna tutte le variabili corrette in $vals, comprese quelle di lingua comuni a entrabbi i loops
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('home/postlist');
?>
