<?php
if(isset($_COOKIE['SEXYID']) && !preg_match('#^[a-z0-9\-,]{32}$#i',$_COOKIE['SEXYID']))
    unset($_COOKIE['SEXYID']);

require_once $_SERVER['DOCUMENT_ROOT'].'/class/db.class.php';

//Per la condivisione delle sessioni (tramite redis) con node.js. L'inclusione ha session_start();
if(REDIS_ENABLED)
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/redisSessionHandler.class.php';
else
    session_start();

//l'uso delle sessioni, devono essere dopo il session start
if(isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'mobile.nerdz.eu')
    $_SESSION['nerdz_template'] = '1'; //mobile

require_once $_SERVER['DOCUMENT_ROOT'].'/class/raintpl.class.php';

if(isset($_GET['id']) && !is_numeric($_GET['id']) && !is_array($_GET['id']))
    $_GET['id'] = (new phpCore())->getUserId(trim($_GET['id']));
    
$tpl = new RainTPL();

$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/'.(empty($_SESSION['nerdz_template']) ? '0' : $_SESSION['nerdz_template']).'/');

class phpCore
{
    private $db;

    public function __construct()
    {
        try
        {
            $this->db = db::getDB();
        }
        catch(PDOException $e)
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/databaseError.html';
            $path = $_SERVER['DOCUMENT_ROOT'].'/data/errlog.txt';
            file_put_contents($path,$e->getMessage());
            chmod($path,0775);
            die();
        }

        $this->autoLogin();

        if($this->isLogged() && (($motivation = $this->isInBanList($_SESSION['nerdz_id']))))
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/bannedUser.php';
            die();
        }

        $idiots = array();
        if(!empty($idiots) && $this->isLogged() && in_array($_SESSION['nerdz_id'], $idiots))
            $this->logout();
    }

    public function getDB()
    {
        return $this->db;
    }
   
    public function jsonResponse($status, $message)
    {
        header('Content-type: application/json');
        return json_encode(array('status' => $status, 'message' => $message),JSON_FORCE_OBJECT);
    }
    
    public function logout()
    {
        if($this->isLogged())
        {
            if($_SERVER['SERVER_NAME'] == 'mobile.nerdz.eu')
            {
                if(isset($_COOKIE['nerdz_id']))
                    setcookie('nerdz_id','',time()-3600,'/','mobile.nerdz.eu');
                if(isset($_COOKIE['nerdz_u']))
                    setcookie('nerdz_u','',time()-3600,'/','mobile.nerdz.eu');
            }
            else
            {
                if(isset($_COOKIE['nerdz_id']))
                    setcookie('nerdz_id','',time()-3600,'/',SITE_HOST);
                if(isset($_COOKIE['nerdz_u']))
                    setcookie('nerdz_u','',time()-3600,'/',SITE_HOST);
            }
            session_destroy();
        }
    }

    public function query($query,$action)
    {
        $stmt = null; //PDO statement

        try
        {
            if(is_string($query))
                $stmt = $this->db->query($query);
            else
            {
                $stmt = $this->db->prepare($query[0]);
                $stmt->execute($query[1]);
            }
        }
        catch(PDOException $e)
        {
            if($action == db::FETCH_ERR)
                return $stmt->errorInfo()[1];

            return null;
        }

        switch($action)
        {
            case db::FETCH_ERR:
                return db::NO_ERR;

            case db::FETCH_STMT:
                return $stmt;

            case db::FETCH_OBJ:
                return $stmt->fetch(PDO::FETCH_OBJ);

            case db::ROW_COUNT:
                return $stmt->rowCount();

            case db::NO_RETURN:
                return true;
        }

        return false;
    }

    private function isInBanList($user)
    {
        if(!($o = $this->query(array('SELECT `motivation` FROM `ban` WHERE `user` = :user',array(':user' => $user)),db::FETCH_OBJ)))
            return false;
        return $o->motivation;
    }
 
    public function availableLanguages($long = null)
    {
        //qui non ci imteressiamo se il file delle lingue è stato modificato, in tal caso si attende che scada la cache affinché si aggiorni, non si forza
        $cache = 'AvailableLanguages'.SITE_HOST;
        if(apc_exists($cache))
        {
            $ret = unserialize(apc_fetch($cache));
            if($long)
                return $ret;
            else
            {
                $short = array();
                foreach($ret as $id => $val)
                    $short[] = $id;
                sort($short);
                return $short;
            }
        }
        else
        {
            //on error return en
            if(!($fp = fopen($_SERVER['DOCUMENT_ROOT'].'/data/languages.csv','r')))
                return $long ? 'English' : 'en';

            $a = $b = array();
            while(false !== ($row = fgetcsv($fp)))
            {
                $a[] = $row[0]; //encoding sarebbe inutile, sono due caratteri e sono ascii
                $b[$row[0]] = htmlentities($row[1],ENT_QUOTES,'UTF-8');
            }
            fclose($fp);
            ksort($b);
            apc_store($cache,serialize($b),3600);

            return $long ? $b : $a;
        }
    }


    private function getAcceptLanguagePreference()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $langs = array();
            $lang_parse = array();
            
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
            
            if (!empty($lang_parse[1]))
            {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                        
                 // set default to 1 for any without q factor
                foreach ($langs as $lang => $val)
                    if (empty($val))
                        $langs[$lang] = 1;
             }
            // sort list based on value    
            arsort($langs, SORT_NUMERIC);
            return $langs;
        }
        return array('en' => 1); //english on error/default
    }

    public function getBrowserLanguage()
    {
        $langpref = $this->getAcceptLanguagePreference();
        $avail = $this->availableLanguages();

        foreach($langpref as $lang => $val)
            foreach($avail as $av)
                if(strpos($lang,$av) !== false)
                    return $av;
        return 'en'; //non dovremmo mai arrivare qui
    }
    
    public function updateBoardLanguage($lang)
    {
        if(!$this->isLogged())
            return false;
            
        return $this->query(array('UPDATE `users` SET `board_lang` = :lang WHERE `counter` = :id',array(':lang' => $lang, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR) == db::NO_ERR;
    }
    
    public function updateUserLanguage($lang)
    {
        if(!$this->isLogged())
            return false;
            
        return $this->query(array('UPDATE `users` SET `lang` = :lang WHERE `counter` = :id',array(':lang' => $lang, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR) == db::NO_ERR;
    }
    
    public function getBoardLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['nerdz_id'])
        {
            if(empty($_SESSION['nerdz_board_lang']))
            {
                if(!($o = $this->query(array('SELECT `board_lang` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
                    return false;
                    
                if(empty($o->board_lang))
                {
                    $_SESSION['nerdz_board_lang'] = $this->getBrowserLanguage();
                    if(!$this->updateBoardLanguage($_SESSION['nerdz_board_lang']))
                        return false;
                }
                else
                    $_SESSION['nerdz_board_lang'] = $o->board_lang;
            }
            return $_SESSION['nerdz_board_lang'];
        }
        
        if(!($o = $this->query(array('SELECT `board_lang` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
            
        return empty($o->board_lang) ? $this->getBrowserLanguage() : $o->board_lang;
    }
    
    public function getUserLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['nerdz_id'])
        {
            if(empty($_SESSION['nerdz_lang']))
            {
                if(!($o = $this->query(array('SELECT `lang` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
                    return false;
                    
                if(empty($o->lang))
                {
                    $_SESSION['nerdz_lang'] = $this->getBrowserLanguage();
                    if(!$this->updateUserLanguage($_SESSION['nerdz_lang']))
                        return false;
                }
                else
                    $_SESSION['nerdz_lang'] = $o->lang;
            }
            return $_SESSION['nerdz_lang'];
        }
        
        if(!($o = $this->query(array('SELECT `lang` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
            
        return empty($o->lang) ? $this->getBrowserLanguage() : $o->lang;
    }

    public function lang($index)
    {
        $lang = $this->isLogged() ? $this->getBoardLanguage($_SESSION['nerdz_id']) : $this->getBrowserLanguage();
        if(empty($lang))
            $lang = 'en'; //non succede, ma se succede sono protetto
        //non ci preoccupiamo delle modifiche ai file di lingua dato che devono accadere MOLTO di rado e si attende che la cache si purghi da sola
        $cache = "language-file-{$lang}".SITE_HOST;
        if(apc_exists($cache))
            $_LANG = unserialize(apc_fetch($cache));
        else
        {
            if (!isset ($_LANG))
                require $_SERVER['DOCUMENT_ROOT']."/languages/{$lang}.php";
            apc_store($cache,serialize($_LANG),3600);
        }
        return str_replace("\n",'<br />',htmlentities($_LANG[$index],ENT_QUOTES,'UTF-8'));
    }

    public function getFollow($id)
    {
        if(!($stmt = $this->query(array('SELECT `to` FROM `follow` WHERE `from` = :id',array(':id' => $id)),db::FETCH_STMT)))
            return array();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isOnline($id)
    {
        if(!($o = $this->query(array('SELECT `last`,`viewonline` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->viewonline && (($o->last+300) > time());
    }

    public function areFriends($uno,$due)
    {
        $q = 'SELECT `from` FROM `follow` WHERE `from` = :from AND `to` = :to';
        return $this->query(array($q,array(':from' => $uno, ':to' => $due)),db::ROW_COUNT) && $this->query(array($q,array(':from' => $due, ':to' => $uno)),db::ROW_COUNT);
     }
    
    public function closedProfile($id)
    {
        return $this->query(array('SELECT `counter` FROM `closed_profiles` WHERE `counter` = :id',array(':id' => $id)),db::ROW_COUNT);
       }

    public function hasGravatarEnabled($id)
   {
        if($this->isLogged() && $id == $_SESSION['nerdz_id'] && isset($_SESSION['nerdz_gravatar']))
            return $_SESSION['nerdz_gravatar'];

        $_SESSION['nerdz_gravatar'] = $this->query(array('SELECT `counter` FROM `gravatar_profiles` WHERE `counter` = :id',array(':id' => $id)),db::ROW_COUNT);
        return $_SESSION['nerdz_gravatar'];
    }

    public function getBlacklist()
    {
        $ret = $blist = array();
        if((!$this->isLogged())||(!($r = $this->query(array('SELECT `to` FROM `blacklist` WHERE `from` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT))))
            return $ret;

        $blist = $r->fetchAll(PDO::FETCH_COLUMN);

        if((!($r = $this->query(array('SELECT DISTINCT `from` FROM `blacklist` WHERE `to` = :id' ,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT))))
            return $ret;

        return array_merge($blist, $r->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isInBlacklist($cattivo,$buono)
    {
        if(!($stmt = $this->query(array('SELECT `to` FROM `blacklist` WHERE `from` = :from',array(':from' => $buono)),db::FETCH_STMT)))
            return false;

        return in_array($cattivo,$stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getWhitelist($id)
    {
        if(!($stmt = $this->query(array('SELECT `to` FROM `whitelist` WHERE `from` = :id',array(':id' => $id)),db::FETCH_STMT)))
            return false;

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isLogged()
    {
        return (isset($_SESSION['nerdz_logged']) && $_SESSION['nerdz_logged']);
    }

    public function getUserObject($id)
    {
        return $this->query(array('SELECT * FROM `users` u JOIN `profiles` p ON u.counter = p.counter WHERE p.counter = :id',array(':id' => $id)),db::FETCH_OBJ);
    }

    public function getProjectName($gid)
    {
        if(!($o = $this->query(array('SELECT `name` FROM `groups` WHERE `counter` = :gid',array(':gid' => $gid)),db::FETCH_OBJ)))
            return false;
        return $o->name;
    }
    
    public function getEmail($id)
    {
        if(!($o = $this->query(array('SELECT `email` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->email;
    }

    public function getUserName($id=null)
    {
        if($this->isLogged() && (($id===null) || $id == $_SESSION['nerdz_id']))
            return $_SESSION['nerdz_username'];

        if(!($o = $this->query(array('SELECT `username` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->username;
    }

    public function getUserId($username = null)
    {
        if($this->isLogged() && ($username === null))
            return $_SESSION['nerdz_id'];

        if(!($id = $this->query(array('SELECT `counter` FROM `users` WHERE `username` = :username',array(':username' => htmlentities($username,ENT_QUOTES,'UTF-8'))),db::FETCH_OBJ)))
            return false;

        return $id->counter;
    }

    public function getTemplate($id = null)
    {
        $logged = $this->isLogged();

        if(!$id && !$logged)
            return '0'; //default

        if(!$id && $logged)
            if(empty($_SESSION['nerdz_template']))
            {
                if(!($o = $this->query(array('SELECT `template` FROM `profiles` WHERE `counter` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                    return false;

                $_SESSION['nerdz_template'] = $o->template;
                return $_SESSION['nerdz_template'];
            }
            else
                return $_SESSION['nerdz_template'];
        
        if(!($o = $this->query(array('SELECT `template` FROM `profiles` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return '0';

        return $o->template;
    }

    private function getUserTimezone($id = null)
    {
        if(!$this->isLogged())
            return  'UTC';

        if(!$id && isset($_SESSION['nerdz_timezone']))
            return $_SESSION['nerdz_timezone'];

        if(!$id)
            $id = $_SESSION['nerdz_id'];

        if(!($o = $this->query(array('SELECT `timezone` FROM `users` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return 'UTC';

        if($id ==  $_SESSION['nerdz_id'])
             $_SESSION['nerdz_timezone'] = $o->timezone;

        return $o->timezone;
    }

    private function getUserDateFormat($id = null)
    {
        if(!$this->isLogged())
            return  'Y/m/d, H:i';

        if(!$id && isset($_SESSION['nerdz_dateformat']))
            return $_SESSION['nerdz_dateformat'];

        if(!$id)
            $id = $_SESSION['nerdz_id'];

        if($id ==  $_SESSION['nerdz_id'] && isset($_SESSION['nerdz_dateformat']))
             return $_SESSION['nerdz_dateformat'];

        if(!($o = $this->query(array('SELECT `dateformat` FROM `profiles` WHERE `counter` = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return 'Y/m/d, H:i';

        $_SESSION['nerdz_dateformat'] = $o->dateformat;

        return $o->dateformat;
    }

    public function getDateTime($timestamp)
    {
        $timezone = $this->getUserTimezone($this->isLogged() ? $_SESSION['nerdz_id'] : 0);

        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimeZone(new DateTimezone($timezone));

        $today = new DateTime('now');
        $today->setTimezone(new DateTimezone($timezone));

        $yesterday = new DateTime();
        $yesterday->setTimestamp($today->getTimestamp() - 86400);

        $format4compare = 'Y-m-d';
        $tmp = $date->format($format4compare);

        if($tmp == $today->format($format4compare))
            return $date->format('H:i');

        if($tmp == $yesterday->format($format4compare))
            return $this->lang('YESTERDAY').' - '.$date->format('H:i');

        return $date->format( $this->getUserDateformat($this->isLogged() ? $_SESSION['nerdz_id'] : 0) );
    }

    private function autoLogin()
    {
        if($this->isLogged() || !isset($_COOKIE['nerdz_u']) || !isset($_COOKIE['nerdz_id']) || !is_numeric($_COOKIE['nerdz_id']))
            return false;
        if(($obj = $this->query(array('SELECT `username`,`password` FROM `users` WHERE `counter` = :id',array(':id' => $_COOKIE['nerdz_id'])),db::FETCH_OBJ)) && md5($obj->password) === $_COOKIE['nerdz_u'])
        {
            $_SESSION['nerdz_logged'] = true;
            $_SESSION['nerdz_id']     = $_COOKIE['nerdz_id'];
            $_SESSION['nerdz_username'] = $obj->username;
            $_SESSION['nerdz_lang'] = $this->getUserLanguage($_SESSION['nerdz_id']);
            $_SESSION['nerdz_board_lang'] = $this->getBoardLanguage($_SESSION['nerdz_id']);
            $_SESSION['nerdz_template'] = $this->getTemplate($_SESSION['nerdz_id']);
            return true;
        }
        return false;
    }

    public function refererControl()
    {
         return isset($_SERVER['HTTP_REFERER']) && in_array(parse_url($_SERVER['HTTP_REFERER'])['host'],array(SITE_HOST,'nerdz.eu','mobile.nerdz.eu'));
    }

    public function getCsrfToken($n = '')
    {
        $_SESSION['nerdz_tok_'.$n] = isset($_SESSION['nerdz_tok_'.$n]) ? $_SESSION['nerdz_tok_'.$n] : md5(uniqid(rand(7,21)));
        return $_SESSION['nerdz_tok_'.$n];
    }

    public function csrfControl($tok,$n = '')
    {
        if(empty($_SESSION['nerdz_tok_'.$n]))
            return false;
        return $_SESSION['nerdz_tok_'.$n] === $tok;
    }

    public function limitControl($limit,$n)
    {
        if(is_numeric($limit) && $limit < $n && $limit > 0)
            return true;

        $r = sscanf($limit,'%d,%d',$a,$b);

        if($r != 2 || ($r == 2 && $b > $n) )
            return false;
        
        return true;
    }

    public function isValidURL($url)
    {
        return preg_match("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#im",$url);
    }

    public static function userLink($user)
    {
        return str_replace(' ','+',urlencode(html_entity_decode($user,ENT_QUOTES,'UTF-8'))).'.';
    }

    public static function projectLink($name)
    {
        return str_replace(' ','+',urlencode(html_entity_decode($name,ENT_QUOTES,'UTF-8'))).':';
    }

    public static function minifyHtml($str)
    {
        $str = explode("\n",$str);
        foreach($str as &$val)
           $val = trim(str_replace("\t",'',$val));
        
        return implode('',$str);
    }
}
?>
