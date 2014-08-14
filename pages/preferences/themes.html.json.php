<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Db;

$user = new User();
if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!$user->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$theme = isset($_POST['theme']) && is_string($_POST['theme']) ? trim($_POST['theme']) : '';
$shorts = [];
$templates = $user->getAvailableTemplates();
foreach($templates as $val) {
    $shorts[] = $val['number'];
}

if(!in_array($theme,$shorts))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$column = (Config\MOBILE_HOST == $_SERVER['HTTP_HOST'] ? 'mobile_' : '').'template';

if(Db::NO_ERRNO != Db::query(array('UPDATE "profiles" SET "'.$column.'" = :theme WHERE "counter" = :id',array(':theme' => $theme, ':id' => $_SESSION['id'])),Db::FETCH_ERRNO))
    die(NERDZ\Core\Utils::jsonResponse('error','Update: ' . $user->lang('ERROR')));

$_SESSION['template'] = $theme;

die(NERDZ\Core\Utils::jsonResponse('ok','OK'));

?>
