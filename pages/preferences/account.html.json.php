<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');

$core = new messages();

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
    
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die($core->jsonResponse('error',$core->lang('ERROR').': token'));
    
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$l = "\x00\t\n\r\x0B \x7F\x81\x8D\x8F\x90\x9D\xA0\xAD";

$user['username'] = isset($_POST['username']) ? trim($_POST['username'],$l) : false;
$user['name']      = isset($_POST['name'])     ? trim($_POST['name'],$l)        : false;
$user['surname']  = isset($_POST['surname'])  ? trim($_POST['surname'],$l)  : false;
$user['email']      = isset($_POST['email'])    ? trim($_POST['email'],$l)    : false;
$user['timezone'] = isset($_POST['timezone']) ? trim($_POST['timezone'],$l)    : false;
$user['gender']      = (isset($_POST['gender']) && is_numeric($_POST['gender'])) && $_POST['gender']<=2 ? $_POST['gender']  : false;

$birth['birth_day']        = (isset($_POST['birth_day'])   && is_numeric($_POST['birth_day'])      && $_POST['birth_day']>0)    ? $_POST['birth_day']   : false;
$birth['birth_month']    = (isset($_POST['birth_month']) && is_numeric($_POST['birth_month']) && $_POST['birth_month']>0)  ? $_POST['birth_month'] : false;
$birth['birth_year']    = (isset($_POST['birth_year'])  && is_numeric($_POST['birth_year'])  && $_POST['birth_year']>0)   ? $_POST['birth_year']  : false;

$user_flag  = !in_array(false,$user);
$birth_flag = !in_array(false,$birth);

if(!$user_flag || !$birth_flag)
{
    $msg = $core->lang('MUST_COMPLETE_FORM')."\n\n".$core->lang('MISSING').':';

    if(!$user_flag)
        foreach($user as $id => $val)
            if(!$val)
            {
                $msg.= "\n";
                switch($id)
                {
                    case 'username':
                        $msg.=$core->lang('USERNAME');
                    break;
                    case 'name':
                        $msg.=$core->lang('NAME');
                    break;
                    case 'surname':
                        $msg.=$core->lang('SURNAME');
                    break;
                    case 'gender':
                        $msg.=$core->lang('GENDER');
                    break;
                    case 'email':
                        $msg.=$core->lang('EMAIL');
                    break;
                    case 'timezone':
                        $msg.='Time zone';
                    break;
                }
            }

    if(!$birth_flag)
        foreach($birth as $id => $val)
            if(!$val)
            {
                $msg.= "\n";
                switch($id)
                {
                    case 'birth_day':
                        $msg.=$core->lang('DAY');
                    break;
                    case 'birth_month':
                        $msg.=$core->lang('MONTH');
                    break;
                    case 'birth_year':
                        $msg.=$core->lang('YEAR');
                    break;
                }
            }

    die($core->jsonResponse('error',$msg));
}


if(is_numeric($user['username']))
    die($core->jsonResponse('error',$core->lang('USERNAME_NUMBER')));

if(isset($user['email'][350]))
    die($core->jsonResponse('error',$core->lang('MAIL_NOT_VALID')));
    
if(isset($user['name'][60]))
    die($core->jsonResponse('error',$core->lang('NAME_LONG')));
    
if(isset($user['surname'][60]))
    die($core->jsonResponse('error',$core->lang('SURNAME_LONG')));

if(isset($user['username'][30]))
    die($core->jsonResponse('error',$core->lang('USERNAME_LONG')));

if(!in_array($user['timezone'],DateTimeZone::listIdentifiers()))
    die($core->jsonResponse('error',$core->lang('ERROR').': Time zone'));
    
if(preg_match('#^~#',$user['username']))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')));

if(is_numeric(strpos($user['username'],'#')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': #'));

if(is_numeric(strpos($user['username'],'+')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': +'));

if(is_numeric(strpos($user['username'],'&')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': &'));

if(is_numeric(strpos($user['username'],'%')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': %'));
    
if(!checkdate($birth['birth_month'],$birth['birth_day'],$birth['birth_year']))
    die($core->jsonResponse('error',$core->lang('DATE_NOT_VALID')));

$birth['date'] = $birth['birth_year'].'/'.$birth['birth_month'].'/'.$birth['birth_day'];

if(false === ($obj = $core->getUserObject($_SESSION['nerdz_id'])))
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(empty($_POST['password']))
{
    $user['password'] = $obj->password;
    $control = false;
}
else
{
    $user['password'] = $_POST['password'];
    if(isset($user['password'][300]))
        die($core->jsonResponse('error',$core->lang('PASSWORD_LONG')));
    $control = true;
}

$usernamechanged = false;
if(html_entity_decode($obj->username,ENT_QUOTES,'UTF-8') != $user['username'])
{
    if(false !== $core->getUserId($user['username']))
        die($core->jsonResponse('error',$core->lang('USERNAME_EXISTS')));
    $usernamechanged = true;
}

foreach($user as $id => $value)
    if($id == 'password')
        $user[$id] = $value;
    else
        $user[$id] = htmlentities($value,ENT_QUOTES,'UTF-8');

//htmlentities empty return values FIX
if(count(array_filter($user)) != count($user))
    die($core->jsonResponse('error',$core->lang('ERROR').': INVALID UTF-8'));

if(($ut = $core->query("SELECT counter FROM users WHERE email = '{$user['email']}'",db::FETCH_OBJ)))
    if($ut->counter != $obj->counter)
        die($core->jsonResponse('error',$core->lang('MAIL_EXITS')));

if(!isset($user['username'][MIN_LENGTH_USER]))
    die($core->jsonResponse('error',$core->lang('USERNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_USER +1)));

if(!isset($user['password'][MIN_LENGTH_PASS]))
    die($core->jsonResponse('error',$core->lang('PASSWORD_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_PASS +1)));

if(!isset($user['name'][MIN_LENGTH_NAME]))
    die($core->jsonResponse('error',$core->lang('NAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_NAME +1)));
    
if(!isset($user['surname'][MIN_LENGTH_SURNAME]))
    die($core->jsonResponse('error',$core->lang('SURNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_SURNAME +1)));

if(!filter_var($user['email'],FILTER_VALIDATE_EMAIL))
    die($core->jsonResponse('error',$core->lang('MAIL_NOT_VALID')));

$par = array( ':username' => $user['username'],
              ':timezone' => $user['timezone'],
              ':name' => $user['name'],
              ':surname' => $user['surname'],
              ':email' => $user['email'],
              ':gender' => $user['gender'],
              ':date' => $birth['date'],
              ':id' => $obj->counter
            );

if($control)
    $par[':pass'] = $user['password'];

if($usernamechanged)
{
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
    //aggiungo un nuovo post all'utente users news (USERS_NEWS) per notificare il tutto

    if(!(new flood())->profilePost())
        die($core->jsonResponse('error','Flood!'));
}

if(
    db::NO_ERR != $core->query(array('UPDATE users SET "username" = :username, "timezone" = :timezone, "name" = :name, "surname" = :surname,"email" = :email,"gender" = :gender,
    "birth_date" = :date'. ($control ? ",password = ENCODE(DIGEST(:pass, \'SHA1\'), \'HEX\')" : '') . ' WHERE counter = :id',$par),db::FETCH_ERR)
  )
    die($core->jsonResponse('error',$core->lang('ERROR')));

if($control)
    setcookie('nerdz_u',md5(sha1($user['password'])),time()+60*60*24*30,'/','.'.SITE_HOST);

if($usernamechanged)
{
    $lastpid = $core->countMessages(USERS_NEWS) + 1;
    $message = "{$obj->username} %%12now is34%%: [user]{$user['username']}[/user].";
    if(db::NO_ERR != $core->query(array('INSERT INTO "posts" ("from","to","pid","message","notify", "time") VALUE ('.USERS_NEWS.','.USERS_NEWS.",{$lastpid}, :msg ,0,NOW())",array($message)),db::FETCH_ERR))
        die($core->jsonResponse('error',$core->lang('ERROR')));
        
    $_SESSION['nerdz_username'] = $user['username'];
}
die($core->jsonResponse('error','OK'));
?>
