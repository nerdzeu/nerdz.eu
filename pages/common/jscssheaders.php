<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
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
if (User::isOnMobileHost()) {
    ?>
	<meta name="theme-color" content="#1D1B1B">
<?php
} /* END MOBILE_META_TAGS */
$static_domain = System::getResourceDomain();
/* BEGIN WINDOWS_META_TAGS */
if ($uagdata['platform'] == 'Windows' && (float) $uagdata['version'] >= 10) {
    ?>
    <meta name="application-name" content="NERDZ" /> 
    <meta name="msapplication-TileColor" content="#1D1B1B" /> 
    <meta name="msapplication-TileImage" content="/static/images/winicon.png" />
<?php
} /* END WINDOWS_META_TAGS */
/* BEGIN FAVICON */
if (User::isOnMobileHost()) {
    ?>
    <link rel="manifest" href="<?php echo $static_domain; ?>/static/webapp/manifest.json" />
    <?php
} else {
        ?>
    <link rel="icon" type="image/x-icon" href="<?php echo $static_domain; ?>/static/images/favicon.ico" />
<?php
    } /* END FAVICON */
?>
<link rel="image_src" href="<?php echo $static_domain;?>/static/images/N.png">
<link rel="stylesheet" type="text/css" href="<?php echo $static_domain;?>/static/css/pgwmodal.min.css" />
<?php
/* BEGIN STYLESHEETS */
foreach ($headers['css'] as $var) {
    if (filter_var($var, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        echo '<link rel="stylesheet" type="text/css" href="',$var,'" />';
    } else {
        echo '<link rel="stylesheet" type="text/css" href="',$static_domain,'/tpl/',$tno,'/',$var,'" />';
    }
}
/* END STYLESHEETS */
/* BEGIN JQUERY */
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>
<script src="<?php echo $static_domain;?>/static/js/pgwmodal.min.js"></script>
<?php
/* END JQUERY */
foreach ($headers['js'] as $var) {
    if (is_array($var)) {
        continue;
    }
    if (filter_var($var, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        echo '<script src="',$var,'"></script>';
    } else {
        echo '<script src="',$static_domain,'/tpl/',$tno,'/',$var,'"></script>';
    }
}
?>
<script src="<?php echo $static_domain;?>/static/js/api.php"></script>
<script src="<?php echo $static_domain;?>/static/js/nerdzcrush.min.js" async></script>
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
extensions: ["tex2jax.js", "Safe.js"],
    jax: ["input/TeX", "output/HTML-CSS"],
    tex2jax: {
    inlineMath: [ ['[m]','[/m]'] ],
        displayMath: [ ['[math]','[/math]'] ],
        processEscapes: false
        },
        "HTML-CSS": { availableFonts: ["TeX"], linebreaks: { automatic: true, width: "container" } }
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-AMS_HTML" async></script>
    <script>
<?php
$trackingCacheKey = 'tracking_js'.NERDZ\Core\Config\SITE_HOST;
if (!($tracking = Utils::apcu_get($trackingCacheKey))) {
    $tracking = Utils::apcu_set($trackingCacheKey, function () {
        $trjs = $_SERVER['DOCUMENT_ROOT'].'/data/tracking.js';
        return is_readable($trjs) ? file_get_contents($trjs) : '';
    }, 3600);
}
echo $tracking;
/* BEGIN NERDZ_STATIC_DATA */
?>
N.static = <?=json_encode(isset($headers['js']['staticData']) ? $headers['js']['staticData'] : [], JSON_HEX_TAG)?>;
<?php
/* END NERDZ_STATIC_DATA */
/* BEGIN BLACKLIST_STUFF */
if ($logged) {
    $jsonIdiots = [];
    if (($blist = $user->getBlacklist())) {
        $blistcss = '<style>';
        foreach ($blist as $b_id) {
            $blistcss .= ".bluser{$b_id},";
            $jsonIdiots[] = User::getUsername($b_id);
        }
    } ?>
    N.idiots=<?=json_encode($jsonIdiots)?>,
    N.tplVars=<?=$user->getTemplateVariables()?>;
<?php
}
?>
</script>
<?php
if ($logged && isset($blistcss)) {
    echo substr($blistcss, 0, -1), '{border:1px solid #FF0000}</style>';
}
/* END BLACKLIST_STUFF */
if ($logged && (($o = Db::query(array('SELECT "userscript" FROM "profiles" WHERE "counter" = ?', array($_SESSION['id'])), Db::FETCH_OBJ))) && !empty($o->userscript)) {
    echo '<script src="',html_entity_decode($o->userscript, ENT_QUOTES, 'UTF-8'),'"></script>';
}
