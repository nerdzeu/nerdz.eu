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
use NERDZ\Core\Messages;
use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\Config;

$messages = new Messages();
$user = new User();
$tplcfg = $user->getTemplateCfg();

$id = isset($_GET['id'])      && is_numeric($_GET['id'])     ? $_GET['id']     : false; // intval below
$pid = isset($_GET['pid'])     && is_numeric($_GET['pid'])    ? intval($_GET['pid'])    : false;
$action = NERDZ\Core\Utils::actionValidator(!empty($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : false);

$found = true;
if ($id) {
    $id = intval($id); //intval here, so we can display the user not found message
    if (false === ($info = $user->getObject($id))) {/* false se l'id richiesto non esiste*/
        $username = $user->lang('USER_NOT_FOUND');
        $found = false;
        $post = new stdClass();
        $post->message = '';
    } else {
        $username = $info->username;
        if ($pid && !$user->hasInBlacklist($id)) {
            // fake post not found

            if ((!$user->isLogged() && $info->private) || !($post = Db::query(['SELECT "message" FROM "posts" WHERE "pid" = :pid AND "to" = :id', [':pid' => $pid, ':id' => $id]], Db::FETCH_OBJ))) {
                $post = new stdClass();
                $post->message = '';
            }
        } else {
            $post = new stdClass();
            $post->message = '';
        }
    }
    /*else abbiamo la variabili $info con tutti i dati dell'utente in un oggetto */
} else {
    die(header('Location: /index.php'));
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
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
    <meta name="description" content="
<?php
if ($pid) {
    echo $description;
}
echo($pid ? ' ' : ''), $username, ' @ NERDZ';
if ($pid) {
    echo ' #',$pid;
}
if ($action) {
    echo ' - ', $action;
}
?>" />
    <meta name="robots" content="index,follow" />
    <title>
<?php
if (!empty($title)) {
    echo $title, '... ⇒ ',$username;
} else {
    echo $username;
}
if ($pid) {
    echo ' #', $pid;
}

if ($action) {
    echo ' - ', $action;
}

echo ' @ '.NERDZ\Core\Utils::getSiteName();
?></title>
    <link rel="alternate" type="application/atom+xml" title="<?php echo $username; ?>" href="http://<?php echo Config\SITE_HOST; ?>/feed.php?id=<?php echo $id; ?>" />
<?php
$headers = $tplcfg->getTemplateVars('profile');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';

if ($found) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile.php';
} else {
    echo $user->lang('USER_NOT_FOUND');
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
