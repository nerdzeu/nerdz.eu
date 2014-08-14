<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Config;
use NERDZ\Core\User;

$user = new User();

$l = "\x00\t\n\r\x0B \x7F\x81\x8D\x8F\x90\x9D\xA0\xAD";

$userData = [];
$userData['name']     = isset($_POST['name'])     ? trim($_POST['name'],$l)     : false;
$userData['surname']  = isset($_POST['surname'])  ? trim($_POST['surname'],$l)  : false;
$userData['email']    = isset($_POST['email'])    ? trim($_POST['email'],$l)    : false;
$userData['timezone'] = isset($_POST['timezone']) ? trim($_POST['timezone'],$l) : false;
if($user->isLogged())
{
    $updatedPassword = false;
    if(empty($_POST['password']))
    {
        if(!($obj = Db::query(
            [
                'SELECT "password" FROM "users" WHERE counter = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ], Db::FETCH_OBJ)
        ))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

        $userData['password'] = $obj->password; //saved hashed password
    }
    else
    {
        $userData['password'] = $_POST['password'];
        $updatedPassword = true;
    }
}
else
{
    $userData['password'] = isset($_POST['password']) ? $_POST['password'] : false;
    $userData['username'] = isset($_POST['username']) ? trim($_POST['username'],$l) : false;
}

$userData['gender']       = isset($_POST['gender']) && is_numeric($_POST['gender']) && $_POST['gender'] >0 && $_POST['gender'] <= 2      ? $_POST['gender'] : false;
$birth['birth_day']   = isset($_POST['birth_day'])    && is_numeric($_POST['birth_day'])   && $_POST['birth_day']  >0 ? $_POST['birth_day']             : false;
$birth['birth_month'] = isset($_POST['birth_month'])  && is_numeric($_POST['birth_month']) && $_POST['birth_month']>0 ? $_POST['birth_month']           : false;
$birth['birth_year']  = isset($_POST['birth_year'])   && is_numeric($_POST['birth_year'])  && $_POST['birth_year'] >0 ? $_POST['birth_year']            : false;

$user_flag  = !in_array(false,$userData);
$birth_flag = !in_array(false,$birth);

if(!$user_flag||!$birth_flag)
{
    $msg = $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').':';

    if(!$user_flag)
        foreach($userData as $id => $val)
            if(!$val)
            {
                $msg.= "\n";
                switch($id)
                {
                case 'username':
                    $msg.=$user->lang('USERNAME');
                    break;
                case 'name':
                    $msg.=$user->lang('NAME');
                    break;
                case 'surname':
                    $msg.=$user->lang('SURNAME');
                    break;
                case 'password':
                    $msg.=$user->lang('PASSWORD');
                    break;
                case 'gender':
                    $msg.=$user->lang('GENDER');
                    break;
                case 'email':
                    $msg.=$user->lang('EMAIL');
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
                    $msg.=$user->lang('DAY');
                    break;
                case 'birth_month':
                    $msg.=$user->lang('MONTH');
                    break;
                case 'birth_year':
                    $msg.=$user->lang('YEAR');
                    break;
                }
            }

    die(NERDZ\Core\Utils::jsonResponse('error',$msg));
}

if(!$user->isLogged()) //username field
{
    if(mb_strlen($userData['username'],'UTF-8') < Config\MIN_LENGTH_USER)
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USERNAME_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_USER));

    if(is_numeric($userData['username']))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USERNAME_NUMBER')));

    if(preg_match('#^~#',$userData['username']))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USERNAME')));

    if(is_numeric(strpos(html_entity_decode($userData['username'],ENT_QUOTES,'UTF-8'),'#')))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USERNAME').": {$userData['username']}\n".$user->lang('CHAR_NOT_ALLOWED').': #'));

    if(is_numeric(strpos($userData['username'],'+')))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': +'));

    if(is_numeric(strpos($userData['username'],'&amp;')))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': &'));

    if(is_numeric(strpos($userData['username'],'%')))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': %'));
}

if(mb_strlen($userData['password'],'UTF-8') < Config\MIN_LENGTH_PASS)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('PASSWORD_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_PASS));

if(mb_strlen($userData['name'],'UTF-8') < Config\MIN_LENGTH_NAME)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('NAME_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_NAME));

if(mb_strlen($userData['surname'],'UTF-8') < Config\MIN_LENGTH_SURNAME)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SURNAME_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_SURNAME));


if(false === filter_var($userData['email'],FILTER_VALIDATE_EMAIL))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MAIL_NOT_VALID')));

foreach($userData as $id => $value)
    $userData[$id] = $id == 'password' ? $value : htmlspecialchars($value,ENT_QUOTES,'UTF-8');

//htmlspecialchars empty return values FIX
if(count(array_filter($userData)) != count($userData))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': INVALID UTF-8'));

if(!$user->isLogged() && mb_strlen($userData['username'],'UTF-8') >= 90) //Username with convertited entities is too long for Db field
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USERNAME_LONG')));

if(isset($userData['email'][350]))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MAIL_NOT_VALID')));

if(isset($userData['name'][60]))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('NAME_LONG')));

if(isset($userData['surname'][60]))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SURNAME_LONG')));

if((!$user->isLogged() || $updatedPassword) && isset($userData['password'][40]))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('PASSWORD_LONG')));

if(!in_array($userData['timezone'],DateTimeZone::listIdentifiers()))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Time zone'));

if(!checkdate($birth['birth_month'],$birth['birth_day'],$birth['birth_year']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('DATE_NOT_VALID')));

$birth['date'] = $birth['birth_year'].'/'.$birth['birth_month'].'/'.$birth['birth_day'];

$userData['gender'] = intval($userData['gender']) == 1 ? 'true' : 'false'; //true = male, false = woman

// if here, user fields are ok
?>
