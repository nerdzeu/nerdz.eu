<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/utils.class.php';
$utils = new utils();
$ret = [];
$vals = [];

$cache = 'nerdz_stats'.SITE_HOST;

if(apc_exists('nerdz_stats'))
    $ret = unserialize(apc_fetch('nerdz_stats'));
else
{
    exec($_SERVER['DOCUMENT_ROOT'].'/pages/executables/site_stats.ccgi',$ret); //ret[0,6]
    @apc_store($cache,serialize($ret),300); //5 min
}

$vals['totusers_n'] = $ret[0];
$vals['totpostsprofiles_n'] = $ret[1];
$vals['totcommentsprofiles_n'] = $ret[2];
$vals['totprojects_n'] = $ret[3];
$vals['totpostsprojects_n'] = $ret[4];
$vals['totcommentsprojects_n'] = $ret[5];
$vals['totonlineusers_n'] = $ret[6];
$vals['tothiddenusers_n'] = $ret[7];
$vals['lastupdate_n'] = $core->getDateTime($utils->apc_getLastModified($cache));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/stats');
?>
