<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Messages;
use NERDZ\Core\Utils;

$core = new Messages();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
if(!$core->refererControl())
    die($core->jsonResponse('error','No SPAM/BOT'));

$url     = empty($_POST['url'])     ? false : trim($_POST['url']);
$comment = empty($_POST['comment']) ? false : trim($_POST['comment']);
$to      = empty($_POST['to'])      ? false : trim($_POST['to']);

if(!$url || !Utils::isValidURL($url))
    die($core->jsonResponse('error',$core->lang('INVALID_URL')));

if($to)
{
    if(!$core->getUsername($to))
        die($core->jsonResponse('error',$core->lang('USER_NOT_FOUND')));
}
else
    $to = $_SESSION['id'];

if($_SESSION['id'] != $to)
{
    if($core->closedProfile($to))
        die($core->jsonResponse('error',$core->lang('CLOSED_PROFILE_DESCR')));
}
    
$share = function($to,$url,$message = NULL) use($core)
{
    if(!preg_match('#(^http:\/\/|^https:\/\/|^ftp:\/\/)#i',$url))
        $url = "http://{$url}";
        
    if(preg_match('#(.*)youtube.com\/watch\?v=(.{11})#Usim',$url)|| preg_match('#http:\/\/youtu.be\/(.{11})#Usim',$url))
    {
        $message = "[youtube]{$url}[/youtube] ".$message;
        return $core->addMessage($to,$message);
    }
    
    if(preg_match('#http://sprunge.us/([a-z0-9\.]+)\?(.+?)#i',$url,$res))
    {
        $file = file_get_contents('http://sprunge.us/'.$res[1]);
        $message = "[code={$res[2]}]{$file}[/code]".$message;
        return $core->addMessage($to,$message);
    }

    $h = @get_headers($url,Db::FETCH_OBJ);
    if(false === $h)
        return false;

    foreach((array)$h['Content-Type'] as $ct)
    {
        if(preg_match('#(image)#i',$ct))
        {
            $message = "[img]{$url}[/img]".$message;
            return $core->addMessage($to,$message);
        }
        
        if(preg_match('#(htm)#i',$ct))
        {
            $file = file_get_contents($url);
            $arr = explode('<img src="',$file);
            $flag = false;
            if(!empty($arr[0]))
                foreach($arr as $val)
                {
                    $img = trim(strstr($val,'"',true));
                    $img = str_replace('"','',$img);
                    if(filter_var($img,FILTER_VALIDATE_URL))
                    {
                        $flag = true;
                        break;
                    }
                }
            $message = $flag ? "[url={$url}][img]{$img}[/img][/url]".$message : "[url]{$url}[/url] ".$message;
            return $core->addMessage($to,$message);
        }
    }
};

if($share($to,$url,$comment))
    die($core->jsonResponse('ok','OK'));

die($core->jsonResponse('error',$core->lang('ERROR')));
?>
