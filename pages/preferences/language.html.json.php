<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\System;

$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$lang = isset($_POST['lang']) && is_string($_POST['lang']) ? trim($_POST['lang']) : '';

if(!in_array($lang,System::getAvailableLanguages()))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
case 'userlang':            
    if(!$user->setLanguage($lang))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

    $_SESSION['lang'] = $lang;
    break;

case 'boardlang':
    if(!$user->setBoardLanguage($lang))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

    $_SESSION['board_lang'] = $lang;
    break;

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));

