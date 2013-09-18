<?php
//TEMPLATE: OK
$ret = array();
$vals = array();

$cache = 'nerdz_stats'.SITE_HOST;

if(apc_exists('nerdz_stats'))
    $ret = unserialize(apc_fetch('nerdz_stats'));
else
{
    exec($_SERVER['DOCUMENT_ROOT'].'/pages/executables/site_stats.ccgi',$ret); //ret[0,6]
    apc_store($cache,serialize($ret),300); //5 min
}

function apc_last_modified($key)
{
    $cache = apc_cache_info('user');

    if (empty($cache['cache_list']))
        return false;
    
    foreach($cache['cache_list'] as $entry)
    {
        if($entry['info'] != $key)
            continue;

        return $entry['creation_time'];
    }
}

$vals['totusers_n'] = $ret[0];
$vals['totpostsprofiles_n'] = $ret[1];
$vals['totcommentsprofiles_n'] = $ret[2];
$vals['totprojects_n'] = $ret[3];
$vals['totpostsprojects_n'] = $ret[4];
$vals['totcommentsprojects_n'] = $ret[5];
$vals['totonlineusers_n'] = $ret[6];
$vals['tothiddenusers_n'] = $ret[7];

$vals['totalusers'] = $core->lang('TOTAL_USERS');
$vals['totalpostsprofiles'] = $core->lang('TOTAL_POSTS_PROFILES');
$vals['totalcommentsprofiles'] = $core->lang('TOTAL_COMMENTS_PROFILES');
$vals['totalprojects'] = $core->lang('TOTAL_PROJECTS');
$vals['totalpostsprojects'] = $core->lang('TOTAL_POSTS_PROJECTS');
$vals['totalcommentsprojects'] = $core->lang('TOTAL_COMMENTS_PROJECTS');
$vals['totalonlineusers'] = $core->lang('TOTAL_ONLINE_USERS');
$vals['tothiddenusers'] = $core->lang('HIDDEN_USERS');

$tpl->assign($vals);
$tpl->draw('base/stats');
?>
