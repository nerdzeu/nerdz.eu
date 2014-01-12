<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/notify.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new notify();

if($core->isLogged())
{
    $e = $vals = array();
    $a = $core->show(null, !isset ($_POST['doNotDelete']));
    $y = count($a);
    $f = true;
    if(!$y)
    {
        $f = false;
        $a = $core->story();
        $y = count($a);
    }
    for($i=0;$i<$y;++$i)
    {
        if(empty($a[$i]['board']) && empty($a[$i]['project']) && !isset($a[$i]['follow']))
        {
            $e[$i][2] = $a[$i]['to'];
            $e[$i][4] = 'c';
            $e[$i][5] = $a[$i]['to_user'];
        }
        elseif(empty($a[$i]['project']) && !empty($a[$i]['board']))
        {
            $e[$i][2] = $a[$i]['to'];
            $e[$i][4] = 'mb';
            $e[$i][5] = $a[$i]['to_user'];
            $e[$i][8] = $a[$i]['from_user'];
        }
        elseif(empty($a[$i]['board']) && !empty($a[$i]['project']) && empty($a[$i]['news']))
        {
            $e[$i][2] = $a[$i]['to'];
            $e[$i][4] = 'pc';
            $e[$i][5] = $a[$i]['to_project'];
        }
        elseif(isset($a[$i]['follow']))
        {
            $e[$i][8] = $a[$i]['from_user'];
            $e[$i][4] = 'f';
        }
        else
        {
            $e[$i][2] = $a[$i]['to'];
            $e[$i][4] = 'np';
            $e[$i][5] = $a[$i]['to_project'];
        }
        $e[$i][1] = $a[$i]['cmp'];
        $e[$i][2] = isset($a[$i]['to']) ? $a[$i]['to'] : $_SESSION['nerdz_id'];
        $e[$i][3] = isset($a[$i]['pid']) ? $a[$i]['pid'] : false;
        $e[$i][9] = isset($a[$i]['from']) ? $a[$i]['from'] : 0;
    }
    
    if($f && !$core->updateStory($a))
        die($core->lang('ERROR'));
        
    usort($e,array('notify','echoSort'));
    $raggr = 1; //set variable via POST to decide if we have to raggrupate notifys or not
    if($raggr)
    {
        $x = $str = array();
        $c = 0;
        
        for($i=0;$i<$y;++$i)
        {
            $ss = $e[$i][2].'-'.(is_numeric($e[$i][3]) ? $e[$i][3] : '0');
            if($e[$i][4] == 'f')
            {
                //user ti sta seguendo
                $str[$c]['type_n'] = 'new_follower';
                $str[$c]['datetime_n'] = $core->getDateTime($e[$i][1]);
                $str[$c]['from_n'] = $e[$i][8];
                $str[$c]['from4link_n'] = phpCore::userLink($e[$i][8]);
                ++$c;
            }
            else
                if(!in_array($ss,$x))
                {
                    $x[] = $ss;
                    $str[$c]['datetime_n'] = $core->getDateTime($e[$i][1]);
                    
                    if($e[$i][4] == 'c')
                    {
                        //ci sono nuovi commenti sul profilo xxx.yyy 
                        $str[$c]['type_n'] = 'profile_comments';
                        $str[$c]['to4link_n'] = phpCore::userLink($e[$i][5]).$e[$i][3];
                        $str[$c]['to_n'] = $e[$i][5];
                        $str[$c]['pid_n'] = $e[$i][3];
                    }
                    elseif($e[$i][4] == 'mb')
                    {
                        // xxx ha postato qualcosa sulla tua board (from* è sempre se stesso)
                        $str[$c]['type_n'] = 'new_post_on_profile';
                        
                        $str[$c]['from4link_n'] = phpCore::userLink($e[$i][8]);
                        $str[$c]['from_n'] = $e[$i][8];
                        $str[$c]['to4link_n'] = phpCore::userLink($e[$i][5]).$e[$i][3];
                        $str[$c]['to_n'] = $e[$i][5];
                        $str[$c]['pid_n'] = $e[$i][3];
                    }
                    elseif($e[$i][4] == 'pc')
                    {
                        //ci sono nuovi commenti sul progetto xxx:yyy
                        $str[$c]['type_n'] = 'project_comments';
                        $str[$c]['to4link_n'] = phpCore::projectLink($e[$i][5]).$e[$i][3];
                        $str[$c]['to_n'] = $e[$i][5];
                        $str[$c]['pid_n'] = $e[$i][3];
                    }
                    elseif($e[$i][4] == 'np')
                    {
                        //novità sul progetto xxx:
                        $str[$c]['type_n'] = 'news_project';
                        $str[$c]['to4link_n'] = phpCore::projectLink($e[$i][5]);
                        $str[$c]['to_n'] = $e[$i][5];
                    }
                    ++$c;
                }
        }
        
        $vals['list_a'] = $str;
        
        $core->getTPL()->assign($vals);
        $core->getTPL()->draw('profile/notify');
    }
    else
        for($i=0;$i<$y;++$i)
            echo $e[$i][0];
}
else
    echo $core->lang('REGISTER');
?>
