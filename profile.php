<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/Messages.class.php';
    
    $core = new Messages();
    $tplcfg = $core->getTemplateCfg();
    
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : false;
    $pid = isset($_GET['pid']) && is_numeric($_GET['pid']) ? $_GET['pid'] : false;
    $found = true;
    if($id)
    {
        if(false === ($info = $core->getUserObject($id))) /* false se l'id richiesto non esiste*/
        {
            $username = $core->lang('USER_NOT_FOUND');
            $found = false;
            $post = new stdClass();
            $post->message = '';
        }
        else
        {
            $username = $info->username;
            if($pid)
            {
                if((!$core->isLogged() && $info->private) || !($post = $core->query(array('SELECT "message" FROM "posts" WHERE "pid" = :pid AND "to" = :id',array($pid,$id)),Db::FETCH_OBJ)))
                {
                    $post = new stdClass();
                    $post->message = '';
                }
            }
            else
            {
                $post = new stdClass();
                $post->message = '';
            }
        }
        /*else abbiamo la variabili $info con tutti i dati dell'utente in un oggetto */
    }
    else
        die(header('Location: /index.php'));

    ob_start(array('NERDZ\\Core\\Core','minifyHtml'));

    $a = explode(' ',$core->parseNewsMessage($core->stripTags(str_replace("\n",' ',$post->message))));

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
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
    <meta name="description" content="
<?php
    if($pid)
           echo $description;
    echo ($pid ? ' ' : ''), $username, ' @ NERDZ';
    if($pid)
        echo ' #',$pid;
?>" />
    <meta name="robots" content="index,follow" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>
<?php
    if(!empty($title))
        echo $title, '... =&gt; ',$username;
    else
        echo $username;
    if($pid)
        echo ' #', $pid;
    echo ' @ '.$core->getSiteName();
?></title>
        <link rel="alternate" type="application/atom+xml" title="<?php echo $username; ?>" href="http://<?php echo SITE_HOST; ?>/feed.php?id=<?php echo $id; ?>" />
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

if($found)
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile.php';
else
    echo $core->lang('USER_NOT_FOUND');

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
</body>
</html>
