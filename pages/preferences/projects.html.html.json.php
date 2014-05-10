<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';

$core = new project();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$id = $_POST['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? trim($_POST['id']) : false;

if($_SESSION['nerdz_id'] != $core->getOwnerByGid($id) || !$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        $capt = new Captcha();
        
        if(!($capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : '')))
            die($core->jsonResponse('error',$core->lang('ERROR').': '.$core->lang('CAPTCHA')));

        if(db::NO_ERRNO != $core->query(array('DELETE FROM "groups" WHERE "counter" = ?',array($id)),db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    
    case 'update':
        //validate fields
        require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';

        // Members
        $_POST['members'] = isset($_POST['members']) ? $_POST['members'] : '';

        if(!($res = $core->query(array('SELECT "user" FROM groups_members where "group" = ?',array($id)),db::FETCH_STMT)))
            die($core->jsonResponse('error',$core->lang('ERROR').'2'));

        $oldmem = $res->fetchAll(PDO::FETCH_COLUMN);

        $m = array_filter(array_unique(explode("\n",$_POST['members'])));
        $newmem = array();
        foreach($m as $v)
        {
            $uid = $core->getUserId(trim($v));
            if(is_numeric($uid))
                $newmem[] = $uid;
            else
                die($core->jsonResponse('error',$core->lang('ERROR').': Invalid member - '.$v));
        }

        //members to add
        $toadd = array_diff($newmem, $oldmem);
        foreach($toadd as $uid)
            if(db::NO_ERRNO != $core->query(array('INSERT INTO "groups_members"("group","user") VALUES(:id,:uid)',array(':id' => $id,':uid' => $uid)),db::FETCH_ERRNO))
                die($core->jsonResponse('error',$core->lang('ERROR').'1'));

        // members to remove
        $toremove = array_diff($oldmem, $newmem);
        foreach($toremove as $val)
            if(db::NO_ERRNO != $core->query(array('DELETE FROM groups_members WHERE "group" = :id AND "user" = :val',array(':id' => $id,':val' => $val)),db::FETCH_ERRNO))
                die($core->jsonResponse('error',$core->lang('ERROR').'4'));
      
        if(db::NO_ERRNO != $core->query(
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
            ],db::FETCH_ERRNO)
        )
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
