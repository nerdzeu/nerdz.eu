<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();

$_POST['limit']   = isset($_POST['limit'])    && is_string($_POST['limit'])   ? $_POST['limit'] : 20;
$_POST['orderby'] = isset($_POST['orderby'])  && is_string($_POST['orderby']) ? $_POST['orderby'] : 'counter';

if(!$core->limitControl($_POST['limit'],20))
    die($core->lang('ERROR'));

$s = array();
$s['description']    = isset($_POST['description'])   && is_string($_POST['description']) ? htmlentities($_POST['description'],ENT_QUOTES,'UTF-8') : false;
$s['name']           = isset($_POST['name'])          && is_string($_POST['name'])          ? htmlentities($_POST['name'],ENT_QUOTES,'UTF-8')          : false;

$imp = array();
foreach($s as $val)
    if($val !== false)
        $imp[] = "\"{$fid}\" LIKE '%{$val}%'";

$query = empty($imp) ?
        "SELECT description,name FROM groups ORDER BY \"{$_POST['orderby']}\" LIMIT {$_POST['limit']}" :
        'SELECT "description","name" FROM "groups" WHERE ('.implode(' AND ',$imp).') ORDER BY "'.$_POST['orderby'].'" LIMIT '.$_POST['limit'];

if(!($r = $core->query($query,db::FETCH_STMT)))
    die($core->lang('ERROR'));
    
$vals = array();
$vals['list_a'] = array();
$i=0;
while(($o = $r->fetch(PDO::FETCH_OBJ)))
{
    $vals['list_a'][$i]['project4link_n'] = phpCore::projectLink($o->name);
    $vals['list_a'][$i]['project_n'] = $o->name;
    $vals['list_a'][$i]['description_n'] = $o->description;
    ++$i;
}

$vals['search'] = $core->lang('SEARCH');
$vals['description'] = $core->lang('DESCRIPTION');
$vals['name'] = $core->lang('NAME');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('project/list');
?>
