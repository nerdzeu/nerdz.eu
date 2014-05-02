<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();
    
if($core->isLogged())
    die($core->jsonResponse('error',$core->lang('ALREADY_LOGGED')));

$user = isset($_POST['username']) ? htmlentities(trim($_POST['username']),ENT_QUOTES,'UTF-8') : false;
$pass = isset($_POST['password']) ? sha1($_POST['password']) : false;

if(!$user || !$pass)
    die($core->jsonResponse('error',$core->lang('INSERT_USER_PASS')));

if(is_numeric($user)) {
    if(!($result = $core->query(array('SELECT "counter","username" FROM users WHERE counter = :user AND "password" = :pass',array(':user' => (int) $user,':pass' => $pass)),db::FETCH_STMT)))
        die($core->jsonResponse('error',$core->lang('ERROR')));
} else {
    if(!($result = $core->query(array('SELECT "counter","username" FROM users WHERE LOWER("username") = LOWER(:user) AND "password" = :pass',array(':user' => $user,':pass' => $pass)),db::FETCH_STMT)))
        die($core->jsonResponse('error',$core->lang('ERROR')));
}    

if($result->rowCount() == 1)
{
    $obj = $result->fetch(PDO::FETCH_OBJ);
    if(isset($_POST['setcookie']))
    {
        $exp_time = time() + 2592000;
        $chost    = $core->getSafeCookieDomainName();
        setcookie ('nerdz_id', $obj->counter, $exp_time, '/', $chost, false, true);
        setcookie ('nerdz_u',  md5 ($pass),   $exp_time, '/', $chost, false, true);
    }
    $_SESSION['nerdz_logged'] = true;
    $_SESSION['nerdz_id'] = $obj->counter;
    $_SESSION['nerdz_username'] = $obj->username;
    $_SESSION['nerdz_lang'] = $core->getUserLanguage($obj->counter);
    $_SESSION['nerdz_board_lang'] = $core->getBoardLanguage($obj->counter);
    $_SESSION['nerdz_template'] = $core->getTemplate($obj->counter, (isset ($_SERVER['HTTP_REFERER']) && parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == MOBILE_HOST));
    $_SESSION['nerdz_mark_offline'] = isset($_POST['offline']);
    die($core->jsonResponse('ok',$core->lang('LOGIN_OK')));
}

die($core->jsonResponse('error',$core->lang('WRONG_USER_OR_PASSWORD')));
?>
