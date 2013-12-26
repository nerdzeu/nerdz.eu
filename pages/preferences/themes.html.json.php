<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

$core = new phpCore();
if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
    
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die($core->jsonResponse('error',$core->lang('ERROR').': token'));
    
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
$theme = isset($_POST['theme']) && is_string($_POST['theme']) ? trim($_POST['theme']) : '';
$shorts = array();
$templates = $core->getAvailableTemplates();
foreach($templates as $val) {
    $shorts[] = $val['number'];
}
        
if(!in_array($theme,$shorts))
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(db::NO_ERR != $core->query(array('UPDATE "profiles" SET "template" = :theme WHERE "counter" = :id',array(':theme' => $theme, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
    die($core->jsonResponse('error','Update: ' . $core->lang('ERROR')));

$_SESSION['nerdz_template'] = $theme;

die($core->jsonResponse('ok','OK'));

?>
