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

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();

ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));
?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
    <title><?=$user->lang('TERMS') ?> - <?=NERDZ\Core\Utils::getSiteName(); ?></title>
<?php
$headers = $tplcfg->getTemplateVars('terms');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/terms.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
