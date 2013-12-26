<?php
if(!$core->isLogged())
    die(header('Location: /'));
$vals = array();
$vals['description_n'] = $core->lang('PREFERENCES_DESCR');

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/layout');
?>
