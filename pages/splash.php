<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
if(!$core->isMobile()) {
  $vals["splash_n"] = "NERDZ ha anche una versione mobile, vuoi provarla?";
  $vals["yessa_n"] = "Vai alla versione mobile";
  $vals["nope_n"] = "Rimani sul sito desktop";
  $vals["yes_link_n"] = "mobile";
  $vals["nop_link_n"] = "desktop";
} else {
  $vals["splash_n"] = "Vuoi passare alla versione desktop?";
  $vals["yessa_n"] = "Si";
  $vals["nope_n"] = "No";
  $vals["yes_link_n"] = "desktop";
  $vals["nop_link_n"] = "mobile";
}
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/splash');
?>
