<?php
if(!isset($gid))
    die('$gid required');
$id  = $gid;
$prj = true;
return require $_SERVER['DOCUMENT_ROOT'].'/pages/common/interactions.html.php';
?>
