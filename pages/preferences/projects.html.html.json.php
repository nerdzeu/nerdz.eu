<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');

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

        if(db::NO_ERR != $core->query(array('DELETE FROM "groups" WHERE "counter" = ?',array($id)),db::FETCH_ERR))//il trigger fa il resto
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    
    case 'update':
    
        foreach($_POST as &$val)
            $val = trim($val);
        
        if(!empty($_POST['website']))
        {
            if(!$core->isValidURL($_POST['website']))
                die($core->jsonResponse('error',$core->lang('WEBSITE').': '.$core->lang('INVALID_URL')));
        }
        else
            $_POST['website'] = '';
            
        if(!empty($_POST['photo']))
        {
            if(!$core->isValidURL($_POST['photo']))
                die($core->jsonResponse('error',$core->lang('PHOTO').': '.$core->lang('INVALID_URL')));
                
            if(!($head = get_headers($_POST['photo'],db::FETCH_OBJ)) || !isset($head['Content-Type']))
                die($core->jsonResponse('error','Something wrong with your project image'));
                
            if(false === strpos($head['Content-Type'],'image'))
                die($core->jsonResponse('error','Your group image, is not a photo or is protected, change it'));
        }
        else
            $_POST['photo'] = '';


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
            {
                if(!in_array($core->query(array('INSERT INTO "groups_members"("group","user") VALUES(:id,:uid)',array(':id' => $id,':uid' => $uid)),db::FETCH_ERR),array(-1,POSTGRESQL_DUP_KEY)))
                    die($core->jsonResponse('error',$core->lang('ERROR').'1'));
                $newmem[] = $uid;
            }
            else
                die($core->jsonResponse('error',$core->lang('ERROR').': Invalid member - '.$v));
        }

        $toremove = array();
        foreach($oldmem as $val)
            if(!in_array($val,$newmem))
                $toremove[] = $val;

         foreach($toremove as $val)
             if(db::NO_ERR != $core->query(array('DELETE FROM groups_members WHERE "group" = :id AND user = :val',array(':id' => $id,':val' => $val)),db::FETCH_ERR))
                die($core->jsonResponse('error',$core->lang('ERROR').'4'));
        
        foreach($_POST as &$val)
            $val = htmlentities($val,ENT_QUOTES,'UTF-8');
                
        $_POST['visible'] = isset($_POST['visible']) && $_POST['visible'] == 1 ? '1' : '0';
        $_POST['open']       =    isset($_POST['open'])      && $_POST['open']       == 1 ? '1' : '0';
        $_POST['private'] = isset($_POST['private']) && $_POST['private'] == 1 ? '1' : '0';
            
        if(!isset($_POST['description']))
            $_POST['description'] = '';

        if(!isset($_POST['goal']))
            $_POST['goal'] = '';

        $par = array(':desc' => $_POST['description'], ':website' => $_POST['website'], ':photo' => $_POST['photo'], ':private' => $_POST['private'], ':open' => $_POST['open'], ':goal' => $_POST['goal'], ':visible' => $_POST['visible'], ':id' => $id);
        
        if(db::NO_ERR != $core->query(array('UPDATE "groups" SET "description" = :desc, "website" = :website, "photo" = :photo, "private" = :private, "open" = :open, "goal" = :goal, "visible" = :visible WHERE "counter" = :id',$par),db::FETCH_ERR))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
