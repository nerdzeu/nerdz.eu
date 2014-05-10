<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/db.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/raintpl.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/templatecfg.class.php';

//Per la condivisione delle sessioni (tramite redis) con node.js. L'inclusione ha session_start();
if(REDIS_ENABLED)
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/redisSessionHandler.class.php';
else
    session_start();

if(isset($_GET['id']) && !is_numeric($_GET['id']) && !is_array($_GET['id']))
    $_GET['id'] = (new phpCore())->getUserId(trim($_GET['id']));

class phpCore
{
    private $db;
    private $tpl;
    private $lang;
    private $tpl_no;
    private $templateCfg;
    
    public function __construct()
    {
        try
        {
            $this->db = db::getDB();
        }
        catch(PDOException $e)
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/databaseError.html';
            $this->dumpException($e);
            die();
        }

        $this->autoLogin(); //set nerdz_template value on autologin (according to mobile or destktop version)

        $this->mobileSplashScreen();

        $this->lang = $this->isLogged() ? $this->getBoardLanguage($_SESSION['nerdz_id']) : $this->getBrowserLanguage();

        $this->tpl = new RainTPL();
        $this->tpl->configure('tpl_dir',"{$_SERVER['DOCUMENT_ROOT']}/tpl/{$_SESSION['nerdz_template']}/");

        $this->tpl_no = $this->tpl->getActualTemplateNumber();

        $this->templateCfg = new templateCfg($this);

        if($this->isLogged() && (($motivation = $this->isInBanList($_SESSION['nerdz_id']))))
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/bannedUser.php';
            die();
        }

        $idiots = [];
        if(!empty($idiots) && $this->isLogged() && in_array($_SESSION['nerdz_id'], $idiots))
            $this->logout();
    }

    private function mobileSplashScreen() {
        $useragent = isset ($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $mobilebrowser = preg_match ('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
        if ($_SERVER['PHP_SELF'] != '/splash.php' && !preg_match('/json|html/',$_SERVER["PHP_SELF"]))
        {
            $is_mobile = $this->isMobile();
            if (isset ($_COOKIE['mobile-splash']))
            {
                if (!$is_mobile && $_COOKIE['mobile-splash'] == 'mobile')
                    die (header ('Location: http://' . MOBILE_HOST . $_SERVER['REQUEST_URI']));
                else if ($is_mobile && $_COOKIE['mobile-splash'] == 'desktop')
                    die (header ('Location: http://' . SITE_HOST   . $_SERVER['REQUEST_URI']));
            }
            else if (($mobilebrowser && !$is_mobile) || (!$mobilebrowser && $is_mobile))
                die (header ('Location: /splash.php?ref=' . rawurlencode ($_SERVER['REQUEST_URI'])));
        }
    }

    public function getTemplateCfg() {
        return $this->templateCfg;
    }

    public function lang($index,$page = null)
    {
        //non ci preoccupiamo delle modifiche ai file di lingua dato che devono accadere MOLTO di rado e si attende che la cache si purghi da sola
        $nullPage = !$page;
        $cache = "language-file-{$this->lang}-{$this->tpl_no}".SITE_HOST.'-'.( $nullPage ? 'default' : $page );
        if(apc_exists($cache))
            $_LANG = unserialize(apc_fetch($cache));
        else
        {
            $langFiles = $this->templateCfg->getTemplateVars($page)['langs'];

            $default = $langFiles['default'];
           
            $defaultLang = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/{$default}"),true);
            if($nullPage) {
                $_LANG = $defaultLang;
            }
            else {
                $page = isset($langFiles[$page]) ? $langFiles[$page] : null;
                if($page !== null) {
                    $pageLang = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/$page"),true);
                    $_LANG = array_merge($defaultLang, $pageLang);
                }
            }
            @apc_store($cache,serialize($_LANG),3600);
        }
        return nl2br(htmlspecialchars($_LANG[$index],ENT_QUOTES,'UTF-8'));
    }

    
    public function isMobile() 
    {
        return (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == MOBILE_HOST);
    }
    
    public function getSiteName()
    {
        return $this->isMobile() ? 'NERDZmobile' : 'NERDZ';
    }

    public function getSafeCookieDomainName()
    {
        // use a simple algorithm to determine the common parts between
        // MOBILE_HOST and SITE_HOST.
        $mobile_host = explode ('.', MOBILE_HOST);
        $site_host   = explode ('.', SITE_HOST);
        $chost       = [];
        for ($i = 0; $i < min (count ($site_host), count ($mobile_host)); $i++)
        {
            $sh_k = count ($site_host)   - $i;
            $mh_k = count ($mobile_host) - $i;
            if (isset ($site_host[--$sh_k]) && isset ($mobile_host[--$mh_k]) && $site_host[$sh_k] == $mobile_host[$mh_k])
                array_unshift ($chost, $site_host[$sh_k]);
            else
                break;
        }
        // accept at least a domain with one dot (x.y), because
        // chrome does not accept point-less (heh) domains for cookie usage.
        // this also handles localhost.
        return count ($chost) > 1 ? implode ('.', $chost) : null;
    }

    public function getDB()
    {
        return $this->db;
    }

    public function getTPL()
    {
        return $this->tpl;
    }
   
    public function dumpException($e, $moredata = false)
    {
        $this->dumpErrorString((($moredata != false) ? "{$moredata}: " : '').$e->getMessage());
    }
    
    public function dumpErrorString($string)
    {
        $path = $_SERVER['DOCUMENT_ROOT'].'/data/errlog.txt';
        file_put_contents($path,$string."\n", FILE_APPEND);
        chmod($path,0775);
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
            if(isset($_COOKIE['nerdz_id']))
                setcookie('nerdz_id', $_COOKIE['nerdz_id'], time()-3600, '/', $this->getSafeCookieDomainName(), false, true);
            if(isset($_COOKIE['nerdz_u']))
                setcookie('nerdz_u',  $_COOKIE['nerdz_u'],  time()-3600, '/', $this->getSafeCookieDomainName(), false, true);
            session_destroy();
        }
    }

    public function login($username, $pass, $cookie = null, $setOffline = null, $hashPassword = null)
    {
        $shaPass = $hashPassword ? $pass : sha1($pass);
        if(!($o = $this->query(
            [
                'SELECT "counter" FROM "users" WHERE LOWER("username") = LOWER(:user) AND "password" = :pass',
                 [
                     ':user' => $username,
                     ':pass' => $shaPass
                 ]
             ],db::FETCH_OBJ))
         )
            return false;

        if($cookie)
        {
            $exp_time = time() + 2592000;
            $chost    = $this->getSafeCookieDomainName();
            setcookie ('nerdz_id', $o->counter , $exp_time, '/', $chost, false, true);
            setcookie ('nerdz_u',  md5($shaPass), $exp_time, '/', $chost, false, true);
        }

        $_SESSION['nerdz_logged'] = true;
        $_SESSION['nerdz_id'] = $o->counter;
        $_SESSION['nerdz_username'] = $username;
        $_SESSION['nerdz_lang'] = $this->getUserLanguage($o->counter);
        $_SESSION['nerdz_board_lang'] = $this->getBoardLanguage($o->counter);
        $_SESSION['nerdz_template'] = $this->getTemplate($o->counter, (isset($_SERVER['HTTP_REFERER']) && parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == MOBILE_HOST));
        $_SESSION['nerdz_mark_offline'] = $setOffline;

        return true;
    }

    /**
     * Executes a query.
     * Its return value varies according to the $action parameter, which should 
     * be a constant member of db.
     * 
     * @param string $query
     * @param int $action
     * @return null|boolean|object 
     * 
     */
    public function query($query,$action = db::NO_RETURN, $all = false)
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
            $this->dumpException($e,$_SERVER['REQUEST_URI'].', '.$e->getTraceAsString());
            if($action == db::FETCH_ERRNO) {
                return $stmt->errorInfo()[1];
            }
            if($action == db::FETCH_ERRSTR) {
                return $stmt->errorInfo()[2];
            }

            $this->dumpException($e,$_SERVER['REQUEST_URI'].', '.$e->getTraceAsString());

            return null;
        }

        switch($action)
        {
            case db::FETCH_ERRNO:
                return db::NO_ERRNO;

            case db::FETCH_STMT:
                return $stmt;

            case db::FETCH_OBJ: {
                return ($all === false) ? $stmt->fetch(PDO::FETCH_OBJ) : $stmt->fetchAll(PDO::FETCH_OBJ);
            }

            case db::ROW_COUNT:
                return $stmt->rowCount();

            case db::NO_RETURN:
                return true;
        }

        return false;
    }

    private function isInBanList($user)
    {
        if(!($o = $this->query(array('SELECT "motivation" FROM "ban" WHERE "user" = :user',array(':user' => $user)),db::FETCH_OBJ)))
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
                $short = [];
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

            $a = $b = [];
            while(false !== ($row = fgetcsv($fp)))
            {
                $a[] = $row[0]; //encoding sarebbe inutile, sono due caratteri e sono ascii
                $b[$row[0]] = htmlspecialchars($row[1],ENT_QUOTES,'UTF-8');
            }
            fclose($fp);
            ksort($b);
            @apc_store($cache,serialize($b),3600);

            return $long ? $b : $a;
        }
    }


    private function getAcceptLanguagePreference()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $langs = [];
            $lang_parse = [];
            
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
            
        return $this->query(array('UPDATE "users" SET "board_lang" = :lang WHERE "counter" = :id',array(':lang' => $lang, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERRNO) == db::NO_ERRNO;
    }
    
    public function updateUserLanguage($lang)
    {
        if(!$this->isLogged())
            return false;
            
        return $this->query(array('UPDATE "users" SET "lang" = :lang WHERE "counter" = :id',array(':lang' => $lang, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERRNO) == db::NO_ERRNO;
    }
    
    public function getBoardLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['nerdz_id'])
        {
            if(empty($_SESSION['nerdz_board_lang']))
            {
                if(!($o = $this->query(array('SELECT "board_lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
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
        
        if(!($o = $this->query(array('SELECT "board_lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
            
        return empty($o->board_lang) ? $this->getBrowserLanguage() : $o->board_lang;
    }
    
    public function getUserLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['nerdz_id'])
        {
            if(empty($_SESSION['nerdz_lang']))
            {
                if(!($o = $this->query(array('SELECT "lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
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
        
        if(!($o = $this->query(array('SELECT "lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
            
        return empty($o->lang) ? $this->getBrowserLanguage() : $o->lang;
    }

    public function getFollow($id)
    {
        if(!($stmt = $this->query(array('SELECT "to" FROM "follow" WHERE "from" = :id',array(':id' => $id)),db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFriends($id) {
        if(!($stmt = $this->query(array('select "to" from (select "to" from follow where "from" = :id) as f inner join (select "from" from follow where "to" = :id) as e on f.to = e.from', array(':id' => $id)), db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isOnline($id)
    {
        if(!($o = $this->query(array('SELECT ("last" + INTERVAL \'300 SECONDS\') > NOW() AS online,"viewonline" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->viewonline && $o->online;
    }

    public function closedProfile($id)
    {
        return $this->query(array('SELECT "counter" FROM "closed_profiles" WHERE "counter" = :id',array(':id' => $id)),db::ROW_COUNT);
    }

    public function getBlacklist()
    {
        $ret = $blist = [];
        if((!$this->isLogged())||(!($r = $this->query(array('SELECT "to" FROM "blacklist" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT))))
            return $ret;

        $blist = $r->fetchAll(PDO::FETCH_COLUMN);

        if((!($r = $this->query(array('SELECT DISTINCT "from" FROM "blacklist" WHERE "to" = :id' ,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT))))
            return $ret;

        return array_merge($blist, $r->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isInBlacklist($cattivo,$buono)
    {
        if(!($stmt = $this->query(array('SELECT "to" FROM "blacklist" WHERE "from" = :from',array(':from' => $buono)),db::FETCH_STMT)))
            return false;

        return in_array($cattivo,$stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getWhitelist($id)
    {
        if(!($stmt = $this->query(array('SELECT "to" FROM "whitelist" WHERE "from" = :id',array(':id' => $id)),db::FETCH_STMT)))
            return false;

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isLogged()
    {     
        return (isset($_SESSION['nerdz_logged']) && $_SESSION['nerdz_logged']);
    }

    public function getUserObject($id)
    {
        return $this->query(array('SELECT * FROM "users" u JOIN "profiles" p ON u.counter = p.counter WHERE p.counter = :id',array(':id' => $id)),db::FETCH_OBJ);
    }

    public function getProjectName($gid)
    {
        if(!($o = $this->query(array('SELECT "name" FROM "groups" WHERE "counter" = :gid',array(':gid' => $gid)),db::FETCH_OBJ)))
            return false;
        return $o->name;
    }
    
    public function getEmail($id)
    {
        if(!($o = $this->query(array('SELECT "email" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->email;
    }

    public function getUserName($id=null)
    {
        if($this->isLogged() && (($id===null) || $id == $_SESSION['nerdz_id']))
            return $_SESSION['nerdz_username'];

        if(!($o = $this->query(array('SELECT "username" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return false;
        return $o->username;
    }

    public function getUserId($username = null)
    {
        if($this->isLogged() && ($username === null))
            return $_SESSION['nerdz_id'];

        if(!($id = $this->query(array('SELECT "counter" FROM "users" WHERE LOWER("username") = LOWER(:username)',array(':username' => htmlspecialchars($username,ENT_QUOTES,'UTF-8'))),db::FETCH_OBJ)))
            return false;

        return $id->counter;
    }

    public function getAvailableTemplates()
    {
        $root = $_SERVER['DOCUMENT_ROOT'].'/tpl/';
        $templates = array_diff(scandir($root), array('.','..','index.html'));
        $ret = array();
        $i = 0;
        foreach($templates as $val) {
            $ret[$i]['number'] = $val;
            $ret[$i]['name'] = file_get_contents($root.$val.'/NAME');
            ++$i;
        }
        return $ret;
    }
    
    public function getMobileTemplate($id = null)
    {
        $logged = $this->isLogged();

        if(!$id && !$logged)
            return '1'; //default

        if(!$id && $logged)
        {
            if(!isset($_SESSION['nerdz_template']))
            {
                if(!($o = $this->query(array('SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                    return false;

                $_SESSION['nerdz_template'] = $o->mobile_template;
                return $_SESSION['nerdz_template'];
            }
            else
                return $_SESSION['nerdz_template'];
        }
        
        if(!($o = $this->query(array('SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
            return '1';

        return $o->mobile_template;
    }

    public function getTemplate($id = null, $forceMobile = null)
    {
        if($this->isMobile() || $forceMobile) {
            return $this->getMobileTemplate($id);
        }

        $logged = $this->isLogged();

        if(!$id && !$logged)
            return '0'; //default

        if(!$id && $logged)
        {
            if(!isset($_SESSION['nerdz_template']))
            {
                if(!($o = $this->query(array('SELECT "template" FROM "profiles" WHERE "counter" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                    return false;

                $_SESSION['nerdz_template'] = $o->template;
                return $_SESSION['nerdz_template'];
            }
            else
                return $_SESSION['nerdz_template'];
        }
        
        if(!($o = $this->query(array('SELECT "template" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
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

        if(!($o = $this->query(array('SELECT "timezone" FROM "users" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
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

        if(!($o = $this->query(array('SELECT "dateformat" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),db::FETCH_OBJ)))
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
        if($this->isLogged())
            return false;

        //This session variable MUST be defined either if logged or not
        $_SESSION['nerdz_template'] = $this->getTemplate();

        //If there are no cookie, no autologin
        if(!isset($_COOKIE['nerdz_u']) || !isset($_COOKIE['nerdz_id']) || !is_numeric($_COOKIE['nerdz_id']))
            return false;
        if(($obj = $this->query(array('SELECT "username","password" FROM "users" WHERE "counter" = :id',array(':id' => $_COOKIE['nerdz_id'])),db::FETCH_OBJ)) && md5($obj->password) === $_COOKIE['nerdz_u'])
            return $this->login($obj->username, $obj->password, true, false, true);

        return false;
    }

    public function refererControl()
    {
        //no needs to check if referrer is nerdz.eu since nerdz.eu is redirect by the server to SITE_HOST
        return isset($_SERVER['HTTP_REFERER']) && in_array(parse_url($_SERVER['HTTP_REFERER'])['host'],array(SITE_HOST,MOBILE_HOST));
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
            return $limit;

        if(!is_string($limit))
            return $n;

        $r = sscanf($limit,'%d,%d',$a,$b);

        if($r != 2 || ($r == 2 && $b > $n) )
            return $n;

        return "{$b} OFFSET {$a}";
    }

    public function setPush($id,$value) {
    
        if(!is_bool($value) || !is_numeric($id)) {
            return false;
        }

        return $this->query(['UPDATE "profiles" SET "push" = :val WHERE "counter" = :user',[':user' => $id, ':val' => $value]]) ? true : false;

    }

    public function wantsPush($id) {
        if (!($o = $this->query(['SELECT "push" FROM "profiles" WHERE "counter" = :user',[':user' => $id]],db::FETCH_OBJ))){
            return false;
        }
            
        return $o->push;
    }

    public function floodPushRegControl($id) {
        //If there has been a request in the last 5 seconds, return false. Always update timer to NOW to cut off flooders.
        if (!($o = $this->query(['SELECT EXTRACT(EPOCH FROM NOW() - "pushregtime") >= 3 AS valid FROM "profiles" WHERE "counter" = :user',[':user' => $id]],db::FETCH_OBJ))) {
            return false;
        }

        if (!$this->query(['UPDATE "profiles" SET "pushregtime" = NOW() WHERE "counter" = :user',[':user' => $id]])) {
            return false;
        }

        return $o->valid;

    }

    public static function isValidURL($url)
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

    public static function getVersion()
    {
        $cache = 'NERDZVersion' . SITE_HOST;
        if (apc_exists ($cache))
            return apc_fetch ($cache);
        if (!is_dir ($_SERVER['DOCUMENT_ROOT'] . '/.git') ||
            !file_exists ($_SERVER['DOCUMENT_ROOT'] . '/.git/refs/heads/master'))
            return 'null';
        $revision = substr (file_get_contents ($_SERVER['DOCUMENT_ROOT'] . '/.git/refs/heads/master'), 0, 7);
        if (strlen ($revision) != 7)
            return 'null';
        @apc_store ($cache, $revision, 5400); // store the version for 1.5 hours
        return $revision;
    }
}
?>
