<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Project;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use \PDO;

$core = new Project();

if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));

$id = $_POST['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? trim($_POST['id']) : false;

if($_SESSION['id'] != $core->getOwner($id) || !$core->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        $capt = new Captcha();
        var_dump($_SESSION);
        
        if(!($capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : '')))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': '.$core->lang('CAPTCHA')));

        if(Db::NO_ERRNO != Db::query(array('DELETE FROM "groups" WHERE "counter" = ?',array($id)),Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    
    case 'update':
        //validate fields
        require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';

        // Members
        $_POST['members'] = isset($_POST['members']) ? $_POST['members'] : '';

        if(!($res = Db::query(array('SELECT "from" FROM groups_members where "to" = ?',array($id)),Db::FETCH_STMT)))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').'2'));

        $oldmem = $res->fetchAll(PDO::FETCH_COLUMN);

        $m = array_filter(array_unique(explode("\n",$_POST['members'])));
        $newmem = [];
        $userMap = [];
        foreach($m as $v)
        {
            $username = trim($v);
            $uid = $core->getUserId($username);
            if(is_numeric($uid)) {
                $newmem[] = $uid;
                $userMap[$uid] = $username;
            }
            else
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': Invalid member - '.$v));
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

                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').'4'));
      
        if(Db::NO_ERRNO != Db::query(
            [
                'UPDATE "groups" SET "description" = :desc, "website" = :website, "photo" = :photo,
                "private" = :private, "open" = :open, "goal" = :goal, "visible" = :visible WHERE "counter" = :id',
                [
                    ':desc'    => $group['description'],
                    ':website' => $group['website'],
                    ':photo'   => $group['photo'],
                    ':private' => $group['private'],
                    ':open'    => $group['open'],
                    ':goal'    => $group['goal'],
                    ':visible' => $group['visible'],
                    ':id'      => $id
                ]
            ],Db::FETCH_ERRNO)
        )
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
