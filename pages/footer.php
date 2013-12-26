<?php
$vals = array();
$vals['logged_b'] = $core->isLogged();

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/footer');

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
