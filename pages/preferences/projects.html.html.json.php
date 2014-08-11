<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use \PDO;

$user    = new User();
$project = new Project();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$id = $_POST['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? trim($_POST['id']) : false;

if($_SESSION['id'] != $project->getOwner($id) || !$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        $capt = new Captcha();

        if(!($capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : '')))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': '.$user->lang('CAPTCHA')));

        if(Db::NO_ERRNO != Db::query(
            [
                'DELETE FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
    
    case 'update':
        //validate fields
        require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';

        // Members
        $_POST['members'] = isset($_POST['members']) ? $_POST['members'] : '';

        $oldmem = $project->getMembers($id);

        $m = array_filter(array_unique(explode("\n",$_POST['members'])));
        $newmem = [];
        $userMap = [];
        foreach($m as $v)
        {
            $username = trim($v);
            $uid = $user->getId($username);
            if(is_numeric($uid) && $uid > 0) {
                $newmem[] = $uid;
                $userMap[$uid] = $username;
            }
            else
                die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Invalid member - '.$v));
        }

        //members to add
        $toadd = array_diff($newmem, $oldmem);
        foreach($toadd as $uid) {
            $ret = Db::query(
                    [
                        'INSERT INTO "groups_members"("to","from") VALUES(:project,:user)',
                        [
                            ':project' => $id,
                            ':user'    => $uid
                        ]
                    ],Db::FETCH_ERRSTR);

            if($ret != Db::NO_ERRSTR)
                die(NERDZ\Core\Utils::jsonDbResponse($ret, $userMap[$uid]));
        }

        // members to remove
        $toremove = array_diff($oldmem, $newmem);
        foreach($toremove as $val)
            if(Db::NO_ERRNO != Db::query(
                    [
                        'DELETE FROM groups_members WHERE "to" = :project AND "from" = :user',
                        [
                            ':project' => $id,
                            ':user'    => $val
                        ]
                    ],Db::FETCH_ERRNO))

                die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'4'));
      
        if(Db::NO_ERRNO != Db::query(
            [
                'UPDATE "groups" SET "description" = :desc, "website" = :website, "photo" = :photo,
                "private" = :private, "open" = :open, "goal" = :goal, "visible" = :visible WHERE "counter" = :id',
                [
                    ':desc'    => $projectData['description'],
                    ':website' => $projectData['website'],
                    ':photo'   => $projectData['photo'],
                    ':private' => $projectData['private'],
                    ':open'    => $projectData['open'],
                    ':goal'    => $projectData['goal'],
                    ':visible' => $projectData['visible'],
                    ':id'      => $id
                ]
            ],Db::FETCH_ERRNO)
        )
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
