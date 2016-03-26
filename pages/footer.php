<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

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
use NERDZ\Core\System;

$vals = [];
$vals['logged_b'] = $user->isLogged();

if (!$vals['logged_b']) {
    System::upsertGuest();
}

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/footer');

if (in_array($_SERVER['SCRIPT_NAME'], array('/profile.php', '/project.php'))) {
    ?>
<script>
(function() {
    var po = document.createElement('script'); po.type = 'application/javascript'; po.async = true; po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
 })();
</script>
<?php

} // end G+ js (only in profiles and projects)
?>
<script>
(function() {
    var gi = document.createElement('script'); gi.type = 'application/javascript'; gi.async = true;
    gi.src = '<?php echo System::getResourceDomain().'/static/js/gistBlogger.min.js';?>';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(gi,s);
})();
</script>
<?php
if (!isset($_COOKIE['stupid_and_useless_cookielaw'])) {
    ?>
<div id="cookieChoiceInfo" style="position: fixed; width: 100%; border-top-width: 1px; border-top-style: solid; border-top-color: rgb(204, 204, 204); color: rgb(119, 119, 119); font-size: 12px; margin: 0px; left: 0px; bottom: 0px; padding: 10px 0px; z-index: 1000; text-align: center; background-color: rgb(230, 230, 230);">
    <span><?php echo $user->lang('COOKIE_LAW_NOTICE') ?></span>
    <a href="/terms.php#cookiePolicy" target="_blank" style="color: rgb(119, 119, 119); text-decoration: underline; margin-left: 20px;"><?php echo $user->lang('INFORMATIONS') ?></a>
    <a id="cookieChoiceDismiss" href="#" style="color: rgb(255, 255, 255); padding: 3px; margin-left: 20px; background-color: rgb(255, 102, 0);">OK</a>
</div>
    <script>
    $("#cookieChoiceDismiss").on('click',function(e) {
        e.preventDefault();
        $("#cookieChoiceInfo").remove();
        document.cookie = "stupid_and_useless_cookielaw=true; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/; domain=<?php echo System::getSafeCookieDomainName() ?>";
    });
    </script>
<?php 
} ?>
