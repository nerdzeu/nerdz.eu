<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();

if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0))
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
if($core->isLogged())
    die($core->jsonResponse('error',$core->lang('ALREADY_LOGGED')));

$user = isset($_POST['username']) ? htmlentities(trim($_POST['username']),ENT_QUOTES,'UTF-8') : false;
$pass = isset($_POST['password']) ? sha1($_POST['password']) : false;

if(!$user || !$pass)
    die($core->jsonResponse('error',$core->lang('INSERT_USER_PASS')));

if(!($result = $core->query(array('SELECT "counter","username" FROM users WHERE LOWER("username") = LOWER(:user) AND "password" = :pass',array(':user' => $user,':pass' => $pass)),db::FETCH_STMT)))
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
$ok = false;
if($result->rowCount() == 1)
{
    $obj = $result->fetch(PDO::FETCH_OBJ);
    if(isset($_POST['setcookie']))
    {
        if($_SERVER['SERVER_NAME'] == 'mobile.nerdz.eu')
        {
            setcookie('nerdz_id',$obj->counter,time()+60*60*24*30,'/','.mobile.nerdz.eu',false,true);
            setcookie('nerdz_u',md5($pass),time()+60*60*24*30,'/','.mobile.nerdz.eu',false,true);
        }
        else
        {
            setcookie('nerdz_id',$obj->counter,time()+60*60*24*30,'/','.'.SITE_HOST,false,true);
            setcookie('nerdz_u',md5($pass),time()+60*60*24*30,'/','.'.SITE_HOST,false,true);
        }
    }
    $_SESSION['nerdz_logged'] = true;
    $_SESSION['nerdz_id'] = $obj->counter;
    $_SESSION['nerdz_username'] = $obj->username;
    $_SESSION['nerdz_lang'] = $core->getUserLanguage($obj->counter);
    $_SESSION['nerdz_board_lang'] = $core->getBoardLanguage($obj->counter);
    $ok = true;

    $_SESSION['nerdz_mark_offline'] = isset($_POST['offline']);
}

die (
     $ok ? $core->jsonResponse('ok',$core->lang('LOGIN_OK')) : $core->jsonResponse('error',$core->lang('WRONG_USER_OR_PASSWORD'))
    );

?>
