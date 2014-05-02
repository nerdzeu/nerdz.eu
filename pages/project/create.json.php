<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';

$core = new project();
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die($core->jsonResponse('error',$core->lang('MISSING')."\n".$core->lang('CAPTCHA')));
if(!$cptcka->check($captcha))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

$group['name']          = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : false;
$group['description'] = !empty($_POST['description']) ? trim($_POST['description']) : false;
$group['owner']       = $_SESSION['nerdz_id'];
$user_flag  = !in_array(false,$group);

if(!$user_flag)
{
    $msg = $core->lang('MUST_COMPLETE_FORM')."\n\n".$core->lang('MISSING').':';
    foreach($group as $id => $val)
        if(!$val)
        {
            $msg.="\n";
            switch($id)
            {
                case 'name':
                    $msg.= $core->lang('NAME');
                break;
                case 'description':
                    $msg.= $core->lang('DESCRIPTION');
                break;
            }
        }
                
    die($core->jsonResponse('error',$msg));
}

if(is_numeric($core->getGid($group['name'])))
    die($core->jsonResponse('error',$core->lang('USERNAME_EXISTS')));

if(is_numeric($group['name']))
    die($core->jsonResponse('error',$core->lang('USERNAME_NUMBER')));

if(preg_match('#^~#',$group['name']))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')));

if(is_numeric(strpos($group['name'],'#')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': #'));

if(is_numeric(strpos($group['name'],'+')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': +'));

if(is_numeric(strpos($group['name'],'&')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': &'));

if(is_numeric(strpos($group['name'],'%')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': %'));

if(!isset($group['name'][MIN_LENGTH_USER + 1]))
    die($core->jsonResponse('error',$core->lang('USERNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.MIN_LENGTH_USER));

if(isset($group['name'][30]))
    die($core->jsonResponse('error',$core->lang('NAME_LONG')));

foreach($group as &$value)
    $value = htmlentities($value,ENT_QUOTES,'UTF-8');

//htmlentities empty return values FIX
if(count(array_filter($group)) != count($group))
    die($core->jsonResponse('error',$core->lang('ERROR').': INVALID UTF-8'));

if(db::NO_ERRNO != $core->query(array('INSERT INTO groups ("description","owner","name") VALUES (:description,:owner,:name)',array(':description' => $group['description'],':owner' => $group['owner'],':name' => $group['name'])),db::FETCH_ERRNO))
    die($core->jsonResponse('error',$core->lang('ERROR')));
        
die($core->jsonResponse('ok','OK'));
?>
