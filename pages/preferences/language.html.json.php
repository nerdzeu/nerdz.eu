<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$core = new NERDZ\Core\User();
if(!$core->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': referer'));
    
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': token'));
    
if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));
    
$lang = isset($_POST['lang']) && is_string($_POST['lang']) ? trim($_POST['lang']) : '';
        
if(!in_array($lang,$core->availableLanguages()))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'userlang':            
        if(!$core->updateUserLanguage($lang))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
        
        $_SESSION['lang'] = $lang;
    break;
    
    case 'boardlang':
        if(!$core->updateBoardLanguage($lang))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));

        $_SESSION['board_lang'] = $lang;
    break;
    
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));

?>
