<?php
namespace NERDZ\Core;

use PDO, PDOException;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

if(isset($_GET['id']) && !is_numeric($_GET['id']) && !is_array($_GET['id']))
    $_GET['id'] = (new Core())->getUserId(trim($_GET['id']));

class Core
{
    private $db;
    private $tpl;
    private $lang;
    private $tpl_no;
    private $templateConfig;
    private $browser;

    public function __construct()
    {
        try
        {
            $this->db = Db::getDB();
        }
        catch(PDOException $e)
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/databaseError.html';
            Db::dumpException($e);
            die();
        }

        $this->browser = new Browser(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        if($this->browser->isMobile() && isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== Config\MOBILE_HOST)
            die(header('Location: http://'.Config\MOBILE_HOST.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')));

        $this->autoLogin(); //set nerdz_template value on autologin (according to mobile or destktop version)
        $this->lang = $this->isLogged() ? $this->getBoardLanguage($_SESSION['id']) : $this->getBrowserLanguage();

        $this->tpl = new RainTPL();
        $this->tpl->configure('tpl_dir',"{$_SERVER['DOCUMENT_ROOT']}/tpl/{$_SESSION['template']}/");

        $this->tpl_no = $this->tpl->getActualTemplateNumber();

        $this->templateConfig = new TemplateConfig($this);

        if($this->isLogged() && (($motivation = $this->isInBanList($_SESSION['id']))))
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/data/bannedUser.php';
            die();
        }

        $idiots = [];
        if(!empty($idiots) && $this->isLogged() && in_array($_SESSION['id'], $idiots))
            $this->logout();
    }

    public function getTemplateCfg() {
        return $this->templateConfig;
    }

    public function lang($index,$page = null)
    {
        // we don't worrie about language file modifications, since this ones shouldn't occur often
        $nullPage = !$page;
        $cache = "language-file-{$this->lang}-{$this->tpl_no}".Config\SITE_HOST.'-'.( $nullPage ? 'default' : $page );
        if(apc_exists($cache))
            $_LANG = unserialize(apc_fetch($cache));
        else
        {
            $langFiles = $this->templateConfig->getTemplateVars($page)['langs'];

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
        return isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == Config\MOBILE_HOST;
    }

    public function getSiteName()
    {
        return 'NERDZ'.( $this->isMobile() ? 'Mobile' : '' );
    }

    public function getSafeCookieDomainName()
    {
        // use a simple algorithm to determine the common parts between
        // Config\MOBILE_HOST and Config\SITE_HOST.
        $mobile_host = explode ('.', Config\MOBILE_HOST);
        $site_host   = explode ('.', Config\SITE_HOST);
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

    public function getTPL()
    {
        return $this->tpl;
    }

    public function toJsonResponse($status, $message)
    {
        $ret = is_array($status) ? $status : ['status' => $status, 'message' => $message];
        return json_encode($ret,JSON_FORCE_OBJECT);
    }
   
    public function jsonResponse($status, $message = '')
    {
        header('Content-type: application/json; charset=utf-8');
        return $this->toJsonResponse($status, $message);
    }

    public function logout()
    {
        if($this->isLogged())
        {
            $chost = $this->getSafeCookieDomainName();
            if(isset($_COOKIE['nerdz_id']))
                setcookie('nerdz_id', $_COOKIE['nerdz_id'], time()-3600, '/', $chost, false, true);
            if(isset($_COOKIE['nerdz_u']))
                setcookie('nerdz_u',  $_COOKIE['nerdz_u'],  time()-3600, '/', $chost, false, true);
            session_destroy();
        }
    }

    public function login($username, $pass, $cookie = null, $setOffline = null, $hashPassword = null)
    {
        $shaPass = $hashPassword ? $pass : sha1($pass);
        if(!($o = Db::query(
            [
                'SELECT "counter", "username" FROM "users" WHERE LOWER("username") = LOWER(:user) AND "password" = :pass',
                 [
                     ':user' => $username,
                     ':pass' => $shaPass
                 ]
             ],Db::FETCH_OBJ))
         )
            return false;

        if($cookie)
        {
            $exp_time = time() + 2592000;
            $chost    = $this->getSafeCookieDomainName();
            setcookie ('nerdz_id', $o->counter , $exp_time, '/', $chost, false, true);
            setcookie ('nerdz_u',  md5($shaPass), $exp_time, '/', $chost, false, true);
        }

        $_SESSION['logged'] = true;
        $_SESSION['id'] = $o->counter;
        $_SESSION['username'] = $o->username;
        $_SESSION['lang'] = $this->getUserLanguage($o->counter);
        $_SESSION['board_lang'] = $this->getBoardLanguage($o->counter);
        $_SESSION['template'] = $this->getTemplate($o->counter);
        $_SESSION['mark_offline'] = $setOffline;

        return true;
    }

    private function isInBanList($user)
    {
        if(!($o = Db::query(array('SELECT "motivation" FROM "ban" WHERE "user" = :user',array(':user' => $user)),Db::FETCH_OBJ)))
            return false;
        return $o->motivation;
    }

    public function availableLanguages($long = null)
    {
        //qui non ci imteressiamo se il file delle lingue è stato modificato, in tal caso si attende che scada la cache affinché si aggiorni, non si forza
        $cache = 'AvailableLanguages'.Config\SITE_HOST;
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

        return Db::query(array('UPDATE "users" SET "board_lang" = :lang WHERE "counter" = :id',array(':lang' => $lang, ':id' => $_SESSION['id'])),Db::FETCH_ERRNO) == Db::NO_ERRNO;
    }

    public function updateUserLanguage($lang)
    {
        if(!$this->isLogged())
            return false;

        return Db::query(array('UPDATE "users" SET "lang" = :lang WHERE "counter" = :id',array(':lang' => $lang, ':id' => $_SESSION['id'])),Db::FETCH_ERRNO) == Db::NO_ERRNO;
    }

    public function getBoardLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['id'])
        {
            if(empty($_SESSION['board_lang']))
            {
                if(!($o = Db::query(array('SELECT "board_lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
                    return false;

                if(empty($o->board_lang))
                {
                    $_SESSION['board_lang'] = $this->getBrowserLanguage();
                    if(!$this->updateBoardLanguage($_SESSION['board_lang']))
                        return false;
                }
                else
                    $_SESSION['board_lang'] = $o->board_lang;
            }
            return $_SESSION['board_lang'];
        }

        if(!($o = Db::query(array('SELECT "board_lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return false;

        return empty($o->board_lang) ? $this->getBrowserLanguage() : $o->board_lang;
    }

    public function getUserLanguage($id)
    {
        if($this->isLogged() && $id == $_SESSION['id'])
        {
            if(empty($_SESSION['lang']))
            {
                if(!($o = Db::query(array('SELECT "lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
                    return false;

                if(empty($o->lang))
                {
                    $_SESSION['lang'] = $this->getBrowserLanguage();
                    if(!$this->updateUserLanguage($_SESSION['lang']))
                        return false;
                }
                else
                    $_SESSION['lang'] = $o->lang;
            }
            return $_SESSION['lang'];
        }

        if(!($o = Db::query(array('SELECT "lang" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return false;

        return empty($o->lang) ? $this->getBrowserLanguage() : $o->lang;
    }

    public function getFollow($id)
    {
        if(!($stmt = Db::query(array('SELECT "to" FROM "followers" WHERE "from" = :id',array(':id' => $id)),Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFriends($id) {
        if(!($stmt = Db::query(array('select "to" from (select "to" from followers where "from" = :id) as f inner join (select "from" from followers where "to" = :id) as e on f.to = e.from', array(':id' => $id)), Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isOnline($id)
    {
        if(!($o = Db::query(array('SELECT ("last" + INTERVAL \'300 SECONDS\') > NOW() AS online,"viewonline" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return false;
        return $o->viewonline && $o->online;
    }

    public function closedProfile($id)
    {
        if(! ( $o = Db::query(array('SELECT "closed" FROM "profiles" WHERE "counter" = ?',array($id)),Db::FETCH_OBJ)))
            return false;
        return $o->closed;
    }

    public function getRealBlacklist()
    {
        if((!$this->isLogged())||(!($r = Db::query(array('SELECT "to" FROM "blacklist" WHERE "from" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_STMT))))
            return [];

        return $r->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getBlacklist()
    {
        $ret = $blist = [];
        if((!$this->isLogged())||(!($r = Db::query(array('SELECT "to" FROM "blacklist" WHERE "from" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_STMT))))
            return $ret;

        $blist = $r->fetchAll(PDO::FETCH_COLUMN);

        if((!($r = Db::query(array('SELECT DISTINCT "from" FROM "blacklist" WHERE "to" = :id' ,array(':id' => $_SESSION['id'])),Db::FETCH_STMT))))
            return $ret;

        return array_merge($blist, $r->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isInBlacklist($cattivo,$buono)
    {
        if(!($stmt = Db::query(array('SELECT "to" FROM "blacklist" WHERE "from" = :from',array(':from' => $buono)),Db::FETCH_STMT)))
            return false;

        return in_array($cattivo,$stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getWhitelist($id)
    {
        if(!($stmt = Db::query(array('SELECT "to" FROM "whitelist" WHERE "from" = :id',array(':id' => $id)),Db::FETCH_STMT)))
            return false;

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isLogged()
    {
        return (isset($_SESSION['logged']) && $_SESSION['logged']);
    }

    public function getUserObject($id)
    {
        return Db::query(array('SELECT * FROM "users" u JOIN "profiles" p ON u.counter = p.counter WHERE p.counter = :id',array(':id' => $id)),Db::FETCH_OBJ);
    }

    public function getProjectName($gid)
    {
        if(!($o = Db::query(array('SELECT "name" FROM "groups" WHERE "counter" = :gid',array(':gid' => $gid)),Db::FETCH_OBJ)))
            return false;
        return $o->name;
    }

    public function getEmail($id)
    {
        if(!($o = Db::query(array('SELECT "email" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return false;
        return $o->email;
    }

    public function getUsername($id=null)
    {
        if($this->isLogged() && (($id===null) || $id == $_SESSION['id']))
            return $_SESSION['username'];

        if(!($o = Db::query(array('SELECT "username" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return false;
        return $o->username;
    }

    public function getUserId($username = null)
    {
        if($this->isLogged() && ($username === null))
            return $_SESSION['id'];

        if(!($id = Db::query(array('SELECT "counter" FROM "users" WHERE LOWER("username") = LOWER(:username)',array(':username' => htmlspecialchars($username,ENT_QUOTES,'UTF-8'))),Db::FETCH_OBJ)))
            return false;

        return $id->counter;
    }

    public function getAvailableTemplates()
    {
        $root = $_SERVER['DOCUMENT_ROOT'].'/tpl/';
        $templates = array_diff(scandir($root), array('.','..','index.html'));
        $ret = [];
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
            if(!isset($_SESSION['template']))
            {
                if(!($o = Db::query(array('SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_OBJ)))
                    return false;

                $_SESSION['template'] = $o->mobile_template;
                return $_SESSION['template'];
            }
            else
                return $_SESSION['template'];
        }

        if(!($o = Db::query(array('SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return '1';

        return $o->mobile_template;
    }

    public function getTemplate($id = null)
    {
        if($this->isMobile()) {
            return $this->getMobileTemplate($id);
        }

        $logged = $this->isLogged();

        if(!$id && !$logged)
            return '0'; //default

        if(!$id && $logged)
        {
            if(!isset($_SESSION['template']))
            {
                if(!($o = Db::query(array('SELECT "template" FROM "profiles" WHERE "counter" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_OBJ)))
                    return false;

                $_SESSION['template'] = $o->template;
                return $_SESSION['template'];
            }
            else
                return $_SESSION['template'];
        }

        if(!($o = Db::query(array('SELECT "template" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return '0';

        return $o->template;
    }

    private function getUserTimezone($id = null)
    {
        if(!$this->isLogged())
            return  'UTC';

        if(!$id && isset($_SESSION['timezone']))
            return $_SESSION['timezone'];

        if(!$id)
            $id = $_SESSION['id'];

        if(!($o = Db::query(array('SELECT "timezone" FROM "users" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return 'UTC';

        if($id ==  $_SESSION['id'])
             $_SESSION['timezone'] = $o->timezone;

        return $o->timezone;
    }

    private function getUserDateFormat($id = null)
    {
        if(!$this->isLogged())
            return  'Y/m/d, H:i';

        if(!$id && isset($_SESSION['dateformat']))
            return $_SESSION['dateformat'];

        if(!$id)
            $id = $_SESSION['id'];

        if($id ==  $_SESSION['id'] && isset($_SESSION['dateformat']))
             return $_SESSION['dateformat'];

        if(!($o = Db::query(array('SELECT "dateformat" FROM "profiles" WHERE "counter" = :id',array(':id' => $id)),Db::FETCH_OBJ)))
            return 'Y/m/d, H:i';

        $_SESSION['dateformat'] = $o->dateformat;

        return $o->dateformat;
    }

    public function getDateTime($timestamp)
    {
        $timezone = $this->getUserTimezone($this->isLogged() ? $_SESSION['id'] : 0);

        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimeZone(new \DateTimezone($timezone));

        $today = new \DateTime('now');
        $today->setTimezone(new \DateTimezone($timezone));

        $yesterday = new \DateTime();
        $yesterday->setTimestamp($today->getTimestamp() - 86400);

        $format4compare = 'Y-m-d';
        $tmp = $date->format($format4compare);

        if($tmp == $today->format($format4compare))
            return $date->format('H:i');

        if($tmp == $yesterday->format($format4compare))
            return $this->lang('YESTERDAY').' - '.$date->format('H:i');

        return $date->format( $this->getUserDateformat($this->isLogged() ? $_SESSION['id'] : 0) );
    }

    private function autoLogin()
    {
        if($this->isLogged())
            return false;

        //This session variable MUST be defined either if logged or not
        $_SESSION['template'] = $this->getTemplate();

        //If there are no cookie, no autologin
        if(!isset($_COOKIE['nerdz_u']) || !isset($_COOKIE['nerdz_id']) || !is_numeric($_COOKIE['nerdz_id']))
            return false;
        if(($obj = Db::query(array('SELECT "username","password" FROM "users" WHERE "counter" = :id',array(':id' => $_COOKIE['nerdz_id'])),Db::FETCH_OBJ)) && md5($obj->password) === $_COOKIE['nerdz_u'])
            return $this->login($obj->username, $obj->password, true, false, true);

        return false;
    }

    public function refererControl()
    {
        //no needs to check if referrer is nerdz.eu since nerdz.eu is redirect by the server to Config\SITE_HOST
        return isset($_SERVER['HTTP_REFERER']) && in_array(parse_url($_SERVER['HTTP_REFERER'])['host'],array(Config\SITE_HOST,Config\MOBILE_HOST));
    }

    public function getCsrfToken($n = '')
    {
        $_SESSION['tok_'.$n] = isset($_SESSION['tok_'.$n]) ? $_SESSION['tok_'.$n] : md5(uniqid(rand(7,21)));
        return $_SESSION['tok_'.$n];
    }

    public function csrfControl($tok,$n = '')
    {
        if(empty($_SESSION['tok_'.$n]))
            return false;
        return $_SESSION['tok_'.$n] === $tok;
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

    public function parseDbMessage($msg, $otherInfo = '')
    {
        $msg = trim($msg);
        if($otherInfo != '')
            $otherInfo = ': '.$otherInfo;

        $okRet = ['ok', 'OK'];
        if(Db::NO_ERRSTR == $msg)
            return $okRet;

        if(strpos($msg, '~') !== false) { // flood with time
            $exp = explode('~',$msg);
            return ['error', $this->lang('WAIT').' '.trim($exp[1])];
        }

        $matches = [];
        preg_match("#error:\s*(.*)#i", $msg, $matches);
        $match = isset($matches[1]);
        if($match) {
            if($matches[1] == 'FLOOD') { // flood without time. Translation is useless
                return [ 'error', 'Flood'.$otherInfo ];
            } else if(stripos($matches[1], 'unique') !== false) {
                if(preg_match("#detail:\s+key\s+\((.+?)\)#i", $msg, $matches)) {
                    return [ 'error', $this->lang('email' == trim(strtolower($matches[1])) ? 'MAIL_EXISTS' : 'USERNAME_EXISTS') ];
                }
            }
        }

        return ['error', htmlspecialchars($this->lang( $match ? $matches[1] : 'ERROR'), ENT_QUOTES, 'UTF-8').$otherInfo ];
    }

    public function jsonDbResponse($msg, $otherInfo = '')
    {
        $res = $this->parseDbMessage($msg, $otherInfo);
        return $this->jsonResponse($res[0], $res[1]);
    }

    public function setPush($id,$value) {

        if(!is_bool($value) || !is_numeric($id)) {
            return false;
        }

        return Db::query(['UPDATE "profiles" SET "push" = :val WHERE "counter" = :user',[':user' => $id, ':val' => $value]]) ? true : false;

    }

    public function wantsPush($id) {
        if (!($o = Db::query(['SELECT "push" FROM "profiles" WHERE "counter" = :user',[':user' => $id]],Db::FETCH_OBJ))){
            return false;
        }

        return $o->push;
    }

    public function floodPushRegControl($id) {
        //If there has been a request in the last 5 seconds, return false. Always update timer to NOW to cut off flooders.
        if (!($o = Db::query(['SELECT EXTRACT(EPOCH FROM NOW() - "pushregtime") >= 3 AS valid FROM "profiles" WHERE "counter" = :user',[':user' => $id]],Db::FETCH_OBJ))) {
            return false;
        }

        if (!Db::query(['UPDATE "profiles" SET "pushregtime" = NOW() WHERE "counter" = :user',[':user' => $id]])) {
            return false;
        }

        return $o->valid;

    }
}
?>
