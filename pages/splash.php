<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$vals["mobile_b"] = $core->isMobile();
if(!$core->isMobile()) {
  $vals["yes_link_n"] = "mobile";
  $vals["nop_link_n"] = "desktop";
} else {
  $vals["yes_link_n"] = "desktop";
  $vals["nop_link_n"] = "mobile";
}
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/splash');
?>
