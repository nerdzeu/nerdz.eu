<?php
//Template: OK
$code = isset($_GET['code']) ? $_GET['code'] : false;
$errmsg[400] = 'Bad Request';
$errmsg[401] = 'Authorization required';
$errmsg[403] = 'Forbidden';
$errmsg[404] = 'Page not found';
$errmsg[500] = 'Internal server error';
$errmsg[501] = 'Not Implemented';
$errmsg[502] = 'Bad Gateway';
$vals = array();
if($code)
{
    if(isset($errmsg[$code]))
        $vals['error_n'] = $errmsg[$code];    
    else
        $vals['error_n'] = 'Undefined error';
    $vals['errorcode_n']  = $code;
    $vals['ip_n'] = $_SERVER['REMOTE_ADDR'];
    $vals['useragent_n'] = isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8') : '';
    $vals['referrer_n'] = isset($_SERVER['HTTP_REFERRER']) ? htmlentities($_SERVER['HTTP_REFERRER'],ENT_QUOTES,'UTF-8') : 'Direct';
}
else
    $vals['error_n'] = $vals['errorcode_n'] = $vals['ip_n'] = $vals['useragent_n'] = $vals['referrer_n'] = 'Undefined Error';
    
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/error');
?>
