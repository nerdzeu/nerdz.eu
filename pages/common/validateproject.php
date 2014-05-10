<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
$core = new project();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

foreach($_POST as &$val)
    $val = trim($val);


if(empty($_POST['description']) || ! is_string($_POST['description'])) //always required
    die($core->jsonResponse('error',$core->lang('MUST_COMPLETE_FORM')."\n\n".$core->lang('MISSING').":\n".$core->lang('DESCRIPTION')));

$group['description'] = $_POST['description'];
$group['owner']       = $_SESSION['nerdz_id'];

//required for creation
if(isset($create))
{
    if(empty($_POST['name']) || !is_string($_POST['name']))
        die($core->jsonResponse('error',$core->lang('MUST_COMPLETE_FORM')."\n\n".$core->lang('MISSING').":\n".$core->lang('NAME')));

    $group['name'] = $_POST['name'];

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

    if(mb_strlen($group['name'],'UTF-8') < MIN_LENGTH_USER)
        die($core->jsonResponse('error',$core->lang('USERNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.MIN_LENGTH_USER));
}

foreach($group as &$value)
    $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');

//htmlspecialchars empty return values FIX
if(count(array_filter($group)) != count($group))
    die($core->jsonResponse('error',$core->lang('ERROR').': INVALID UTF-8'));

if(isset($create)) {
    if(mb_strlen($group['name'],'UTF-8') >= 30)
        die($core->jsonResponse('error',$core->lang('USERNAME_LONG')));
}

if(!isset($_POST['goal']))
    $_POST['goal'] = '';

if(!isset($_POST['website']))
    $_POST['website'] = '';
       
if(!empty($_POST['website']) && !phpCore::isValidURL($_POST['website']))
    die($core->jsonResponse('error',$core->lang('WEBSITE').': '.$core->lang('INVALID_URL')));
    
if(!empty($_POST['photo']))
{
    if(!phpCore::isValidURL($_POST['photo']))
        die($core->jsonResponse('error',$core->lang('PHOTO').': '.$core->lang('INVALID_URL')));
        
    if(!($head = get_headers($_POST['photo'],db::FETCH_OBJ)) || !isset($head['Content-Type']))
        die($core->jsonResponse('error','Something wrong with your project image'));
        
    if(false === strpos($head['Content-Type'],'image'))
        die($core->jsonResponse('error','Your project image, is not a photo or is protected, change it'));
}
else
    $_POST['photo'] = '';

$group['photo']   = $_POST['photo'];
$group['website'] = $_POST['website'];
$group['goal'] = $_POST['goal'];
$group['visible'] = isset($_POST['visible']) && $_POST['visible'] == 1 ? '1' : '0';
$group['open']    = isset($_POST['open'])    && $_POST['open']    == 1 ? '1' : '0';
$group['private'] = isset($_POST['private']) && $_POST['private'] == 1 ? '1' : '0';

?>
