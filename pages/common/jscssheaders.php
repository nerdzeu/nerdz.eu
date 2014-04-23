<?php
// Displays the stuff contained in the <head> tag.
$logged = $core->isLogged();
require_once $_SERVER['DOCUMENT_ROOT'].'/class/browser.class.php';
$uagdata = (new Browser(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''))->getArray();
$tno = $core->getTemplate();
/* BEGIN MOBILE_META_TAGS */
if ($core->isMobile()) { ?>
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <meta name="mobile-web-app-capable" content="yes">
<?php
} /* END MOBILE_META_TAGS */
$is_ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
$static_domain = $is_ssl ? '' : STATIC_DOMAIN;
/* BEGIN WINDOWS_META_TAGS */
if ($uagdata['platform'] == 'Windows' && (float)$uagdata['version'] >= 10) {
?>
    <meta name="application-name" content="NERDZ" /> 
    <meta name="msapplication-TileColor" content="#1D1B1B" /> 
    <meta name="msapplication-TileImage" content="/static/images/winicon.png" />
<?php
} /* END WINDOWS_META_TAGS */
/* BEGIN FAVICON */
if ($core->isMobile()) { ?>
    <link rel="shortcut icon" sizes="196x196" href="<?php echo $static_domain;?>/static/images/droidico.png">
<?php } else { ?>
    <link rel="icon" type="image/x-icon" href="<?php echo $static_domain;?>/static/images/favicon.ico" />
<?php
} /* END FAVICON */
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
    <script type="application/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<?php
/* END JQUERY */
foreach($headers['js'] as $var) {
    if (is_array ($var)) continue;
    if (filter_var ($var,FILTER_VALIDATE_URL,FILTER_FLAG_PATH_REQUIRED))
        echo '<script type="application/javascript" src="',$var,'"></script>';
    else
        echo '<script type="application/javascript" src="',$static_domain,'/tpl/',$tno,'/',$var,'"></script>';
}
?>
    <script type="application/javascript" src="<?php echo $static_domain;?>/static/js/api.php"></script>
    <script type="text/x-mathjax-config">
    MathJax.Hub.Config({
        extensions: ["tex2jax.js"],
        jax: ["input/TeX", "output/HTML-CSS"],
        tex2jax: {
          inlineMath: [ ['[m]','[/m]'] ],
          displayMath: [ ['[math]','[/math]'] ],
          processEscapes: true
        },
        "HTML-CSS": { availableFonts: ["TeX"], linebreaks: { automatic: true, width: "container" } }
    });
    </script>
    <script type="application/javascript" src="http<?php echo $is_ssl ? 's://c328740.ssl.cf1.rackcdn.com' : '://cdn.mathjax.org' ?>/mathjax/latest/MathJax.js"></script>
    <script type="application/javascript">
<?php

    if(!apc_exists('tracking_js')) {
        $trjs = $_SERVER['DOCUMENT_ROOT'].'/data/tracking.js';           
        @apc_store('tracking_js', serialize(file_exists($trjs) ? file_get_contents($trjs) : ''));
    }
    echo unserialize(apc_fetch('tracking_js'));

/* BEGIN SSL_LOGIN: Variabled used by the javascript API to control if login must be only via ssl */
    echo 'var SSLLogin = '. (LOGIN_SSL_ONLY ? 'true' : 'false').', sessionID="'.session_name().'", SSLDomain = "'. HTTPS_DOMAIN.'";';
/* END SSL_LOGIN */
/* BEGIN NERDZ_VERSION */
if (isset ($headers['js']['staticData']['outputVersion']) && $headers['js']['staticData']['outputVersion'] === true) {
    unset($headers['js']['staticData']['outputVersion']);
?>
    var Nversion = '<?=$core->getVersion()?>';
<?php
} /* END NERDZ_VERSION */
/* BEGIN NERDZ_STATIC_DATA */
?>
        var Nstatic = <?=json_encode(isset($headers['js']['staticData'])?$headers['js']['staticData']:array(),JSON_HEX_TAG)?>;
<?php
/* END NERDZ_STATIC_DATA */
/* BEGIN BLACKLIST_STUFF */
// this also closes the main <script> tag opened before.
if ($logged && ($blist = $core->getBlacklist()))
{
    $jsonObj = [];
    $blistcss = '<style type="text/css">';
    foreach ($blist as $b_id) {
        $blistcss .= ".bluser{$b_id},";
        $jsonObj[] = $core->getUsername($b_id);
    }
?>
        var idiots = <?=json_encode($jsonObj)?>;
    </script>
    <?=substr ($blistcss, 0, -1)?>{border:1px solid #FF0000}</style>
<?php
}
else { ?> </script> <?php }
/* END BLACKLIST_STUFF */
if ($logged && (($o = $core->query(array('SELECT "userscript" FROM "profiles" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_OBJ))) && !empty($o->userscript))
    echo '<script type="application/javascript" src="',html_entity_decode($o->userscript,ENT_QUOTES,'UTF-8'),'"></script>';
?>
