<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Browser;
use NERDZ\Core\Config;
use NERDZ\Core\Db;
use NERDZ\Core\Utils;
use NERDZ\Core\System;
use NERDZ\Core\User;
// Displays the stuff contained in the <head> tag.
// Disable DNS prefetching to avoid tracking issues
?>
<meta http-equiv="x-dns-prefetch-control" content="off">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<?php
$logged = $user->isLogged();
$uagdata = (new Browser(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''))->getArray();
$tno = $user->getTemplate();
/* BEGIN MOBILE_META_TAGS */
if (User::isOnMobileHost()) { ?>
	<meta name="theme-color" content="#1D1B1B">
<?php
} /* END MOBILE_META_TAGS */
$static_domain = System::getResourceDomain();
/* BEGIN WINDOWS_META_TAGS */
if ($uagdata['platform'] == 'Windows' && (float)$uagdata['version'] >= 10) {
?>
    <meta name="application-name" content="NERDZ" /> 
    <meta name="msapplication-TileColor" content="#1D1B1B" /> 
    <meta name="msapplication-TileImage" content="/static/images/winicon.png" />
<?php
} /* END WINDOWS_META_TAGS */
/* BEGIN FAVICON */
if (User::isOnMobileHost()) { ?>
    <link rel="manifest" href="<?php echo $static_domain;?>/static/webapp/manifest.json" />
    <?php } else { ?>
    <link rel="icon" type="image/x-icon" href="<?php echo $static_domain;?>/static/images/favicon.ico" />
<?php
} /* END FAVICON */
?>
<link rel="image_src" href="<?php echo $static_domain;?>/static/images/N.png">
<link rel="stylesheet" type="text/css" href="<?php echo $static_domain;?>/static/css/pgwmodal.min.css" />
<?php
/* BEGIN STYLESHEETS */
foreach ($headers['css'] as $var) {
    if (filter_var ($var,FILTER_VALIDATE_URL,FILTER_FLAG_PATH_REQUIRED))
        echo '<link rel="stylesheet" type="text/css" href="',$var,'" />';
    else
        echo '<link rel="stylesheet" type="text/css" href="',$static_domain,'/tpl/',$tno,'/',$var,'" />';
}
/* END STYLESHEETS */
/* BEGIN JQUERY */
?>
<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="<?php echo $static_domain;?>/static/js/pgwmodal.min.js"></script>
<?php
/* END JQUERY */
foreach($headers['js'] as $var) {
    if (is_array ($var)) continue;
    if (filter_var ($var,FILTER_VALIDATE_URL,FILTER_FLAG_PATH_REQUIRED))
        echo '<script src="',$var,'"></script>';
    else
        echo '<script src="',$static_domain,'/tpl/',$tno,'/',$var,'"></script>';
}
?>
<script src="<?php echo $static_domain;?>/static/js/api.php"></script>
<script src="<?php echo $static_domain;?>/static/js/nerdzcrush.min.js" async></script>
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
extensions: ["tex2jax.js"],
    jax: ["input/TeX", "output/HTML-CSS"],
    tex2jax: {
    inlineMath: [ ['[m]','[/m]'] ],
        displayMath: [ ['[math]','[/math]'] ],
        processEscapes: false
        },
        "HTML-CSS": { availableFonts: ["TeX"], linebreaks: { automatic: true, width: "container" } }
    });
    </script>
    <script src="//cdn.mathjax.org/mathjax/latest/MathJax.js" async></script>
    <script>
<?php
$trackingCacheKey = 'tracking_js'.NERDZ\Core\Config\SITE_HOST;
if(!($tracking = Utils::apc_get($trackingCacheKey)))
    $tracking = Utils::apc_set($trackingCacheKey, function() {
        $trjs = $_SERVER['DOCUMENT_ROOT'].'/data/tracking.js';
        return is_readable($trjs) ? file_get_contents($trjs) : '';
    }, 3600);
echo $tracking;

/* BEGIN SSL_VARIABLES (used by the JS API) */
?>
    var Nssl = {
        login: <?=Config\LOGIN_SSL_ONLY ? 'true' : 'false'?>,
        domain: "<?=Config\HTTPS_DOMAIN?>"
    };
<?php
/* END SSL_VARIABLES */
/* BEGIN NERDZ_VERSION */
if (isset ($headers['js']['staticData']['outputVersion']) && $headers['js']['staticData']['outputVersion'] === true) {
    unset($headers['js']['staticData']['outputVersion']);
?>
    var Nversion = '<?=System::getVersion()?>';
<?php
} /* END NERDZ_VERSION */
/* BEGIN NERDZ_STATIC_DATA */
?>
var Nstatic = <?=json_encode(isset($headers['js']['staticData']) ? $headers['js']['staticData'] : [], JSON_HEX_TAG)?>;
<?php
/* END NERDZ_STATIC_DATA */
/* BEGIN BLACKLIST_STUFF */
if ($logged) {
    $jsonIdiots = [];
    if(($blist = $user->getBlacklist()))
    {
        $blistcss = '<style type="text/css">';
        foreach ($blist as $b_id) {
            $blistcss .= ".bluser{$b_id},";
            $jsonIdiots[] = User::getUsername($b_id);
        }
   }
?>
    N.idiots=<?=json_encode($jsonIdiots)?>,
    N.tplVars=<?=$user->getTemplateVariables()?>;
<?php
}
?>
</script>
<?php
if($logged && isset($blistcss)) echo substr ($blistcss, 0, -1), '{border:1px solid #FF0000}</style>';
/* END BLACKLIST_STUFF */
if ($logged && (($o = Db::query(array('SELECT "userscript" FROM "profiles" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ))) && !empty($o->userscript))
    echo '<script src="',html_entity_decode($o->userscript,ENT_QUOTES,'UTF-8'),'"></script>';
