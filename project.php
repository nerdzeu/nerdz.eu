<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/templatecfg.class.php';
    
    $core = new project();
    $tplcfg = new templateCfg();
    
    $gid = isset($_GET['gid']) && is_numeric($_GET['gid']) ? $_GET['gid'] : false;
    $pid = isset($_GET['pid']) && is_numeric($_GET['pid']) ? $_GET['pid'] : false;
    if(!$gid)
        $create = true;
    else
        $create = false;
    $found = false;
    $post = new stdClass();
    $post->message = '';
    if($gid)
    {
        if(false === ($info = $core->getProjectObject($gid)))
            $name = $core->lang('PROJECT_NOT_FOUND');
        else
        {
            $found = true;
            $name = $info->name;
            if($pid && !$info->private && $info->visible)
                if(!($post = $core->query(array('SELECT "message" FROM "groups_posts" WHERE "pid" = :pid AND "to" = :gid',array(':pid' => $pid, ':gid' => $gid)),db::FETCH_OBJ)))
                {
                    $post = new stdClass();
                    $post->message = '';
                }
        }
        /*else abbiamo la variabili $info con tutti i dati del gruppo in un oggetto */
    }
    else
        $name = 'Create';
    ob_start(array('phpCore','minifyHtml'));

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
            echo $title, '... =&gt; ',$name;
        else
            echo $name;
        if($pid)
            echo ' #', $pid;
        echo ' @ NERDZ';
    ?></title>
        <link rel="alternate" type="application/atom+xml" title="<?php echo $name; ?>" href="http://<?php echo SITE_HOST; ?>/feed.php?id=<?php echo $gid; ?>&amp;project=1" />
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
        if($core->isLogged())
            require_once($_SERVER['DOCUMENT_ROOT'].'/pages/project/create.php');
        else die(header('Location: /'));
    }
    elseif(!$found)
        echo $core->lang('PROJECT_NOT_FOUND');
    else
        require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project.php';
    
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
</body>
</html>
