<?php
//Template: OK
$l = $core->isLogged() ? $core->getUserLanguage($_SESSION['nerdz_id']) : $core->getBrowserLanguage();
$f = false;
$lcon = $l.'.txt';
foreach(glob($_SERVER['DOCUMENT_ROOT'].'/data/faq/*.txt') as $lang)
    if(is_numeric(strpos($lang,$lcon)))
    {
        $f = true;
        break;
    }
$exp = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/faq/'.($f ? $lcon : 'it.txt')));

$vals = array();
$c = 0;
$questions = 0;
foreach($exp as $v)
{
    $vals['list_a'][$c]['title_b'] = isset($v[0]) && ($v[0] == 'Q');
    $vals['list_a'][$c]['questionid_n'] = $vals['list_a'][$c]['title_b'] ? ++$questions : 0;
    $vals['list_a'][$c]['line_n'] = htmlentities($v,ENT_QUOTES,'UTF-8');
    ++$c;
}
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/faq');
?>
