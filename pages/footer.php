<?php
$vals = [];
$vals['logged_b'] = $user->isLogged();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/footer');

$ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';

if(in_array($_SERVER['SCRIPT_NAME'], array('/profile.php','/project.php'))) { ?>
<script type="application/javascript">
(function() {
    var po = document.createElement('script'); po.type = 'application/javascript'; po.async = true; po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
 })();
</script>
<?php
} // end G+ js (only in profiles and projects)
?>
<script type="application/javascript">
(function() {
    var gi = document.createElement('script'); gi.type = 'application/javascript'; gi.async = true;
    gi.src = '<?php echo ($ssl ? '' : NERDZ\Core\Config\STATIC_DOMAIN). '/static/js/gistBlogger.min.js';?>';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(gi,s);
})();
</script>
