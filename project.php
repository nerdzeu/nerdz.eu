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
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\User;
use NERDZ\Core\Messages;
use NERDZ\Core\Project;
use NERDZ\Core\Db;
use NERDZ\Core\Config;

$user = new User();
$project = new Project();
$messages = new Messages();
$tplcfg = $user->getTemplateCfg();

$gid = isset($_GET['gid']) && is_numeric($_GET['gid']) ? $_GET['gid'] : false; //intval below
$pid = isset($_GET['pid']) && is_numeric($_GET['pid']) ? intval($_GET['pid']) : false;
$action = NERDZ\Core\Utils::actionValidator(!empty($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : false);

$found = false;
$post = new stdClass();
$post->message = '';
if ($gid) {
    $gid = intval($gid);
    if (false === ($info = $project->getObject($gid))) {
        $name = $user->lang('PROJECT_NOT_FOUND');
    } else {
        $found = true;
        $name = $info->name;
        if ($pid && !$info->private && $info->visible) {
            if (!($post = Db::query(
                [
                    'SELECT "message","from" FROM "groups_posts" WHERE "pid" = :pid AND "to" = :gid',
                    [
                        ':pid' => $pid,
                        ':gid' => $gid,
                    ],
                ],
                Db::FETCH_OBJ
            ))
                || $user->hasInBlacklist($post->from) //fake post not found
            ) {
                $post = new stdClass();
                $post->message = '';
            }
        }
    }
    /*else abbiamo la variabili $info con tutti i dati del gruppo in un oggetto */
} else {
    $name = 'Create';
}
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

$a = explode(' ', $messages->parseNews(Messages::stripTags(str_replace("\n", ' ', $post->message))));

$i = 25;
while ((isset($a[$i]))) {
    unset($a[$i++]);
}

$description = implode(' ', $a);

$i = 12;
while ((isset($a[$i]))) {
    unset($a[$i++]);
}
$title = implode(' ', $a);

?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming, projects, group" />
    <meta name="description" content="
<?php
if ($pid) {
    echo $description;
}
echo($pid ? ' ' : ''), $name, ' @ NERDZ';
if ($pid) {
    echo ' #',$pid;
}
?>" />
    <meta name="robots" content="index,follow" />
    <title>
<?php
if (!empty($title)) {
    echo $title, '... ⇒ ',$name;
} else {
    echo $name;
}
if ($pid) {
    echo ' #', $pid;
}
echo ' @ '.NERDZ\Core\Utils::getSiteName();
?></title>
    <link rel="alternate" type="application/atom+xml" title="<?php echo $name; ?>" href="http://<?php echo Config\SITE_HOST; ?>/feed.php?id=<?php echo $gid; ?>&amp;project=1" />
<?php
$headers = $tplcfg->getTemplateVars('project');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
if (!$found) {
    echo $user->lang('PROJECT_NOT_FOUND');
} else {
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project.php';
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
