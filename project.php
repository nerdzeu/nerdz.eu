<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Messages;
use NERDZ\Core\Project;
use NERDZ\Core\Db;
use NERDZ\Core\Config;

$user = new User();
$project = new Project();
$messages = new Messages();
$tplcfg = $user->getTemplateCfg();

$gid = isset($_GET['gid']) && is_numeric($_GET['gid']) ? $_GET['gid'] : false;
$pid = isset($_GET['pid']) && is_numeric($_GET['pid']) ? $_GET['pid'] : false;
$action = NERDZ\Core\Utils::actionValidator(!empty($_GET['action']) && is_string ($_GET['action']) ? $_GET['action'] : false);

$create = !$gid;
$found = false;
$post = new stdClass();
$post->message = '';
if($gid)
{
    if(false === ($info = $project->getObject($gid)))
        $name = $user->lang('PROJECT_NOT_FOUND');
    else
    {
        $found = true;
        $name = $info->name;
        if($pid && !$info->private && $info->visible)
        {
            if(!($post = Db::query(
                [
                    'SELECT "message","from" FROM "groups_posts" WHERE "pid" = :pid AND "to" = :gid',
                    [
                        ':pid' => $pid,
                        ':gid' => $gid
                    ]
                ],Db::FETCH_OBJ))
                || $user->hasInBlacklist($post->from) //fake post not found
            )
            {
                $post = new stdClass();
                $post->message = '';
            }
        }
    }
    /*else abbiamo la variabili $info con tutti i dati del gruppo in un oggetto */
}
else
    $name = 'Create';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$a = explode(' ',$messages->parseNews($messages->stripTags(str_replace("\n",' ',$post->message))));

$i = 25;
while((isset($a[$i])))
    unset($a[$i++]);

$description = implode(' ',$a);

$i = 12;
while((isset($a[$i])))
    unset($a[$i++]);
$title = implode(' ',$a);

?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming, projects, group" />
    <meta name="description" content="
<?php
if($pid)
    echo $description;
echo ($pid ? ' ' : ''), $name, ' @ NERDZ';
if($pid)
    echo ' #',$pid;
?>" />
    <meta name="robots" content="index,follow" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>
<?php
if(!empty($title))
    echo $title, '... ⇒ ',$name;
else
    echo $name;
if($pid)
    echo ' #', $pid;
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
if($create)
{
    if($user->isLogged())
        require($_SERVER['DOCUMENT_ROOT'].'/pages/project/create.php');
    else die(header('Location: /'));
}
elseif(!$found)
    echo $user->lang('PROJECT_NOT_FOUND');
else
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project.php';

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
