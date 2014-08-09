<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;
$core = new User();

if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));
if(!$core->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': referer'));

$from  = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : 0; // 0 = full post
$hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : 0;

if(!$hpid)
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    
$to = $_SESSION['id'];

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        if(!$from) // full post
        {
            $table = (isset($prj) ? 'groups_' : '').'posts_no_notify';
            if(Db::NO_ERRNO != Db::query(
                    [
                        'INSERT INTO "'.$table.'"("user","hpid")
                         SELECT :to, :hpid
                         WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "user" = :to AND "hpid" = :hpid)',
                        [
                            ':to'   => $to,
                            ':hpid' => $hpid
                        ]
                     ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
        }
        else // user
        {
            $table = (isset($prj) ? 'groups_' : '').'comments_no_notify';
            if(Db::NO_ERRNO != Db::query(
                    [
                        'INSERT INTO "'.$table.'"("from","to","hpid")
                         SELECT :from, :to, :hpid
                         WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid)',
                        [
                            ':from' => $from,
                            ':to'   => $to,
                            ':hpid' => $hpid
                        ]
                    ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
        }
    break;
    case 'del':
        if(!$from) // full post
        {
            if(Db::NO_ERRNO != Db::query(
                    [
                        'DELETE FROM "'.(isset($prj) ? 'groups_' : '').'posts_no_notify" WHERE "user" = :to AND "hpid" = :hpid',
                        [
                            ':to'   => $to,
                            ':hpid' => $hpid
                        ]
                    ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
        }
        else // user
            if(Db::NO_ERRNO != Db::query(
                    [
                        'DELETE FROM "'.(isset($prj) ? 'groups_' : '').'comments_no_notify" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid',
                        [
                            ':from' => $from,
                            ':to'   => $to,
                            ':hpid' => $hpid
                        ]
                    ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
