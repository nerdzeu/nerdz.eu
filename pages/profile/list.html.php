<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();

$_POST['limit'] = isset($_POST['limit'])       && is_string($_POST['limit'])   ? $_POST['limit'] : 20;
$_POST['orderby'] = isset($_POST['orderby'])  && is_string($_POST['orderby']) ? $_POST['orderby'] : 'counter';

if(!$core->limitControl($_POST['limit'],20))
    die($core->lang('ERROR'));

$s = array();
$s['counter']   = isset($_POST['counter'])  && is_numeric($_POST['counter']) ? $_POST['counter'] : false;
$s['name']      = isset($_POST['name'])     && is_string($_POST['name'])      ? htmlentities($_POST['name'],ENT_QUOTES,'UTF-8') : false;
$s['username']  = isset($_POST['username']) && is_string($_POST['username']) ? htmlentities($_POST['username'],ENT_QUOTES,'UTF-8') : false;
$s['surname']   = isset($_POST['surname'])  && is_string($_POST['surname'])  ? htmlentities($_POST['surname'],ENT_QUOTES,'UTF-8') : false;

$imp = array();
foreach($s as $val)
    if($val !== false)
        $imp[] = "\"{$fid}\" LIKE '%{$val}%'";

$orderby = in_array(strtolower($_GET['orderby']),array('counter','username','name','surname','birth_date')) ? $_GET['orderby'] : 'counter';
        
$query = empty($imp) ? 
        "SELECT counter,username,name,surname,birth_date FROM users ORDER BY \"{$orderby}\" LIMIT {$_POST['limit']}" :
        'SELECT "counter","username","name","surname","birth_date" FROM "users" WHERE ('.implode(' AND ',$imp).") ORDER BY counter LIMIT {$_POST['limit']}";

if(!($r = $core->query($query,db::FETCH_STMT)))
    die($core->lang('ERROR'));

$i = 0;
$vals = array();
$vals['list_a'] = array();
while(($o = $r->fetch(PDO::FETCH_OBJ)))
{
    $vals['list_a'][$i]['counter_n'] = $o->counter;
    $vals['list_a'][$i]['username4link_n'] = phpCore::userLink($o->username);
    $vals['list_a'][$i]['username_n'] = $o->username;
    $vals['list_a'][$i]['name_n'] = $o->name;
    $vals['list_a'][$i]['surname_n'] = $o->surname;
    list($year, $month, $day) = explode('-',$o->birth_date);
    $vals['list_a'][$i]['birthdate_n'] = $day.'/'.$month.'/'.$year;
    ++$i;
}

$core->getTPL()->assign($vals);
$core->getTPL()->draw('profile/list');
?>
