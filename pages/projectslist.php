<?php

function parseLim($lim) {

    $r = sscanf($lim,'%d,%d',$a,$b);

    if($r != 2)
        return false;
        
    return "$b OFFSET $a";
}


$limit = $core->limitControl(isset($_GET['lim']) ? $_GET['lim'] : 0 ,20) ? $_GET['lim'] : 20;

//WTF? sscanf? strstr? What a shitty language! However, postgresql fix.
if (strstr($limit, ',') != false)
    $newlim = parseLim($limit);
else
    $newlim = $limit;

switch(isset($_GET['orderby']) ? trim(strtolower($_GET['orderby'])) : '')
{
	case 'name':
		$orderby = 'name';
	break;

	case 'description':
		$orderby = 'description';
	break;

	case 'id':
	default:
		$orderby = 'counter';
	break;
}

$order = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';

$vals = array();

$vals['name'] = $core->lang('NAME');
$vals['id'] = 'ID';
$vals['description'] = $core->lang('DESCRIPTION');

$q = empty($_GET['q']) ? '' : htmlentities($_GET['q'],ENT_QUOTES,'UTF-8');

$query = empty($q) ?
		 "SELECT name, description,counter FROM groups ORDER BY {$orderby} {$order} LIMIT {$newlim}" :
		 array("SELECT name,description, counter FROM groups WHERE {$orderby} LIKE ? ORDER BY {$orderby} {$order} LIMIT {$newlim}",array("%{$q}%"));

$vals['list_a'] = array();

if(($r = $core->query($query,db::FETCH_STMT)))
{
	$i = 0;
	while(($o = $r->fetch(PDO::FETCH_OBJ)))
	{
		$vals['list_a'][$i]['id_n'] = $o->counter;
		$vals['list_a'][$i]['name_n'] = $o->name;
		$vals['list_a'][$i]['description_n'] = $o->description;
		$vals['list_a'][$i]['name4link_n'] = phpCore::projectLink($o->name);
		++$i;
	}
}

$desc = $order == 'DESC' ? '1' : '0';
$url = "?orderby={$orderby}&amp;desc={$desc}&amp;q={$q}";
if(is_numeric($limit))
{
	$vals['prev_url'] = '';
	$vals['next_url'] = count($vals['list_a']) == 20 ? $url.'&amp;lim=20,20' : '';
}
else
{
    if(2 == sscanf($limit,"%d,%d",$a,$b))
    {
		$next =  $a+20;
		$prev = $a-20;
		$limitnext = "{$next},20";
		$limitprev = $prev >0 ? "{$prev},20" : '20';
    }

	$vals['next_url'] = count($vals['list_a']) == 20 ? $url."&amp;lim={$limitnext}" : '';
	$vals['prev_url'] = $url."&amp;lim={$limitprev}";
}
$tpl->assign($vals);
$tpl->draw('base/projectslist');
?>
