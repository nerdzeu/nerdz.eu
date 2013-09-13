<?php
//TEMPLATE: OK
$vals = array();
$vals['logged_b'] = $core->isLogged();
$vals['register'] = $core->lang('REGISTER');
$vals['terms'] = $core->lang('TERMS');
$vals['stats'] = $core->lang('STATS');
$vals['informations'] = $core->lang('INFORMATIONS');
$vals['bugtitle'] = $core->lang('BUG_TITLE');
$vals['search'] = $core->lang('SEARCH');

$tpl->assign($vals);
$tpl->draw('base/footer');

$fl = in_array($_SERVER['SCRIPT_NAME'], array('/profile.php','/project.php'));
$ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';

if($fl)
    echo '<script type="application/javascript">
      (function() {
        var po = document.createElement(\'script\'); po.type = \'application/javascript\'; po.async = true;
        po.src = \'https://apis.google.com/js/plusone.js\';
        var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
     })();
         </script>';
?>
<script type="application/javascript">
(function() {
    var gi = document.createElement('script'); gi.type = 'application/javascript'; gi.async = true;
    gi.src = '<?php echo ($ssl ? '' : STATIC_DOMAIN). '/static/js/gistBlogger.jsmin.js';?>';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(gi,s);
})();
</script>
