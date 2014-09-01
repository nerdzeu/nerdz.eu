<?php
namespace NERDZ\Core;

use PDO, PDOException;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

if(isset($_GET['id']) && !is_numeric($_GET['id']) && !is_array($_GET['id']))
    $_GET['id'] = (new User())->getId(trim($_GET['id']));

class User
{
    private $tpl;
    private $lang;
    private $tpl_no;
    private $templateConfig;
    private $browser;

    public function __construct()
    {
        $this->browser = new Browser(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        if($this->isFromMobileDevice() && !static::isOnMobileHost())
            die(header('Location: http://'.Config\MOBILE_HOST.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')));

        $this->autoLogin(); //set template value on autologin (according to mobile or destktop version)
        $this->lang = $this->getBoardLanguage();

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

    public function lang($index)
    {
        // we don't worrie about language file modifications, since this ones shouldn't occur often
        $cache = "language-file-{$this->lang}-{$this->tpl_no}".Config\SITE_HOST;
        if(apc_exists($cache))
            $_LANG = unserialize(apc_fetch($cache));
        else
        {
            // first load default language film
            $defaultLang = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/data/langs/{$this->lang}/default.json"), true);

            // then we add eventually merge template additions
            $tplFile = $_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/langs/{$this->lang}/json/default.json";
            if(is_readable($tplFile))
                $defaultLang = array_merge($defaultLang, json_decode(file_get_contents($tplFile), true));

            $_LANG = $defaultLang;
            @apc_store($cache,serialize($_LANG),3600);
        }
        return nl2br(htmlspecialchars($_LANG[$index],ENT_QUOTES,'UTF-8'));
    }


    public function isFromMobileDevice()
    {
        return $this->browser->isMobile();
    }

    public static function isOnMobileHost()
    {
        return isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == Config\MOBILE_HOST;
    }

    public function getTPL()
    {
        return $this->tpl;
    }

    public function logout()
    {
        if($this->isLogged())
        {
            $chost = System::getSafeCookieDomainName();
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
            $chost    = System::getSafeCookieDomainName();
            setcookie ('nerdz_id', $o->counter , $exp_time, '/', $chost, false, true);
            setcookie ('nerdz_u',  md5($shaPass), $exp_time, '/', $chost, false, true);
        }

        $_SESSION['logged']       = true;
        $_SESSION['id']           = $o->counter;
        $_SESSION['username']     = $o->username;
        $_SESSION['lang']         = $this->getLanguage($o->counter);
        $_SESSION['board_lang']   = $this->getBoardLanguage($o->counter);
        $_SESSION['template']     = $this->getTemplate($o->counter);
        $_SESSION['mark_offline'] = $setOffline;

        return true;
    }

    private function isInBanList($id)
    {
        if(!($o = Db::query(
            [
                'SELECT "motivation" FROM "ban" WHERE "user" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;
        return $o->motivation;
    }

    public function setBoardLanguage($lang)
    {
        if(!$this->isLogged())
            return false;

        return Db::query(
            [
                'UPDATE "users" SET "board_lang" = :lang WHERE "counter" = :id',
                [
                    ':lang' => $lang,
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_ERRNO) == Db::NO_ERRNO;
    }

    public function setLanguage($lang)
    {
        if(!$this->isLogged())
            return false;

        return Db::query(
            [
                'UPDATE "users" SET "lang" = :lang WHERE "counter" = :id',
                [
                    ':lang' => $lang,
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_ERRNO) == Db::NO_ERRNO;
    }

    public function getBasicInfo($id = null)
    {
        if($this->isLogged() && !$id)
            $id = $_SESSION['id'];

        if(empty($id))
            return [];

        $o = $this->getObject($id);
        $ret = [];
        $ret['username_n']      = $o->username;
        $ret['username4link_n'] = Utils::userLink($o->username);
        $ret['id_n']            = $id;
        $ret['name_n']          = $o->name;
        $ret['surname_n']       = $o->surname;
        $ret['gravatarurl_n']   = $this->getGravatar($id);
        $ret['canshowfollow_b'] = $this->isLogged() && $id !== $_SESSION['id'];
        $ret['canifollow_b']    = !$this->isFollowing($id);
        $ret['birthdate_n']     = $this->getDateTime(strtotime($o->birth_date));
        $ret['since_n']         = $this->getDateTime(strtotime($o->registration_time));

        return $ret;
    }

    public function getBoardLanguage($id = null)
    {
        $logged = $this->isLogged();

        if(!$logged && !$id)
            return System::getBrowserLanguage();

        if($logged && ($id == $_SESSION['id'] || !$id))
        {
            if(empty($_SESSION['board_lang']))
            {
                if(!($o = Db::query(
                    [
                        'SELECT "board_lang" FROM "users" WHERE "counter" = :id',
                        [
                            ':id' => $id
                        ]
                    ],Db::FETCH_OBJ)))
                    return System::getBrowserLanguage();

                if(empty($o->board_lang))
                {
                    $_SESSION['board_lang'] = System::getBrowserLanguage();
                    $this->setBoardLanguage($_SESSION['board_lang']);
                }
                else
                    $_SESSION['board_lang'] = $o->board_lang;
            }
            return $_SESSION['board_lang'];
        }

        if(!($o = Db::query(
            [
                'SELECT "board_lang" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return System::getBrowserLanguage();

        return empty($o->board_lang) ? System::getBrowserLanguage() : $o->board_lang;
    }

    public function getLanguage($id = null)
    {
        $logged = $this->isLogged();

        if(!$id && !$logged)
            return System::getBrowserLanguage();

        if($logged && ($id == $_SESSION['id'] || !$id))
        {
            if(empty($_SESSION['lang']))
            {
                if(!($o = Db::query(
                    [
                        'SELECT "lang" FROM "users" WHERE "counter" = :id',
                        [
                            ':id' => $id
                        ]
                    ],Db::FETCH_OBJ)))
                    return System::getBrowserLanguage();

                if(empty($o->lang))
                {
                    $_SESSION['lang'] = System::getBrowserLanguage();
                    $this->setLanguage($_SESSION['lang']);
                }
                else
                    $_SESSION['lang'] = $o->lang;
            }
            return $_SESSION['lang'];
        }

        if(!($o = Db::query(
            [
                'SELECT "lang" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return System::getBrowserLanguage();

        return empty($o->lang) ? System::getBrowserLanguage() : $o->lang;
    }

    public function getInteractions($id, $limit = 0)
    {
        if(!$this->isLogged())
            return [];

        if($limit)
            $limit = Security::limitControl($limit, 20);

        $objs = [];
        if(!($objs = Db::query(
            [
                'SELECT "type", "from", "to", extract(epoch from time) as time, pid, post_to
                FROM user_interactions(:me, :id) AS
                f("type" text, "from" int8, "to" int8, "time" timestamp with time zone, pid int8, post_to int8)
                ORDER BY f.time DESC'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':me' => $_SESSION['id'],
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ, true)))
            return [];

        $ret = [];
        for($i=0, $count = count($objs); $i<$count; ++$i) {
            $ret[$i]['type_n']      = $objs[$i]->type;
            $ret[$i]['fromid_n']    = $objs[$i]->from;
            $ret[$i]['from_n']      = static::getUsername($objs[$i]->from);
            $ret[$i]['from4link_n'] = Utils::userLink($ret[$i]['from_n']);
            $ret[$i]['toid_n']      = $objs[$i]->to;
            $ret[$i]['to_n']        = static::getUsername($objs[$i]->to);
            $ret[$i]['to4link_n']   = Utils::userLink($ret[$i]['to_n']);
            $ret[$i]['datetime_n']  = $this->getDateTime($objs[$i]->time);
            $ret[$i]['pid_n']       = $objs[$i]->pid;
            $ret[$i]['postto_n']    = static::getUsername($objs[$i]->post_to);
            $ret[$i]['link_n']      = Utils::userLink($ret[$i]['postto_n']).$objs[$i]->pid;
        }

        return $ret;
    }

    public function getFollowing($id, $limit = 0)
    {
        if($limit)
            $limit = Security::limitControl($limit, 20);

        if(!($stmt = Db::query(
            [
                'SELECT "to" FROM "followers" f JOIN "users" u ON f.to = u.counter WHERE "from" = :id ORDER BY u.username'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowingCount($id)
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "followers" WHERE "from" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->cc;
    }

    public function getFollowers($id, $limit = 0)
    {
        if($limit)
            $limit = Security::limitControl($limit, 20);

        if(!($stmt = Db::query(
            [
                'SELECT "from" FROM "followers" f JOIN "users" u ON f.from = u.counter WHERE "to" = :id ORDER BY u.username'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowersCount($id)
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "followers" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->cc;
    }

    public function getFriends($id, $limit = 0) {
        if($limit)
            $limit = Security::limitControl($limit, 20);

        if(!($stmt = Db::query(
            [
                'select "to" from (
                    select "to" from followers where "from" = :id) as f
                    inner join 
                    (select "from" from followers where "to" = :id) as e
                    on f.to = e.from
                    inner join users u on u.counter = f.to order by username'.($limit != 0 ? ' LIMIT '.$limit : ''),
                [
                    ':id' => $id
                ]
            ], Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFriendsCount($id) {
        if(!($o = Db::query(
            [
                'select COUNT("to") AS cc from (
                    select "to" from followers where "from" = :id) as f
                    inner join 
                    (select "from" from followers where "to" = :id) as e
                    on f.to = e.from',
                [
                    ':id' => $id
                ]
            ], Db::FETCH_OBJ)))
            return 0;

        return $o->cc;
    }

    public function follow($id, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'followers';
        return Db::query(
            [
                'INSERT INTO "'.$table.'"("to","from")
                SELECT :id, :me
                WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "to" = :id AND "from" = :me)',
                    [
                        ':id' => $id,
                        ':me' => $_SESSION['id']
                    ]
                ],Db::FETCH_ERRSTR);
    }

    public function defollow($id, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'followers';
        return Db::query(
            [
                'DELETE FROM "'.$table.'" WHERE "to" = :id AND "from" = :me',
                [
                    ':id' => $id,
                    ':me' => $_SESSION['id']
                ]
            ],Db::FETCH_ERRSTR);
    }

    public function bookmark($hpid, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'bookmarks';
        return Db::query(
            [
                'INSERT INTO "'.$table.'"("from","hpid")
                SELECT :from, :hpid
                WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid)',
                [
                    ':from' => $_SESSION['id'],
                    ':hpid' => $hpid
                ]
            ], Db::FETCH_ERRSTR);
    }

    public function unbookmark($hpid, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'bookmarks';
        return Db::query(
            [
                'DELETE FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid',
                [
                    ':from' => $_SESSION['id'],
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_ERRSTR);
    }

    public function dontNotify($options = [], $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        extract($options);
        $hpid = !empty($hpid) ? $hpid : 0;
        if($hpid == 0)
            return Utils::$ERROR_DB_MESSAGE;

        $from = isset($from) ? $from : 0;

        $table = ($prj ? 'groups_' : '');
        if($from)
        {
            $table .= 'comments_no_notify';
            return Db::query(
                    [
                    'INSERT INTO "'.$table.'"("from", "to", "hpid")
                    SELECT :from, :to, :hpid
                    WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid)',
                        [
                            ':from' => $from,
                            ':to'    => $_SESSION['id'],
                            ':hpid' => $hpid
                        ]
                    ], Db::FETCH_ERRSTR);
        }

        $table .= 'posts_no_notify';
        return Db::query(
            [
               'INSERT INTO "'.$table.'"("user", "hpid")
                SELECT :user, :hpid
                WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "user" = :user AND "hpid" = :hpid)',
                [
                    ':user' => $_SESSION['id'],
                    ':hpid' => $hpid
                ]
            ], Db::FETCH_ERRSTR);
    }

    public function reNotify($options = [], $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        extract($options);
        $hpid = !empty($hpid) ? $hpid : 0;
        if($hpid == 0)
             return Utils::$ERROR_DB_MESSAGE;

        $from = isset($from) ? $from : 0;

        $table = ($prj ? 'groups_' : '');
        if($from)
        {
            $table .= 'comments_no_notify';
            return Db::query(
                    [
                       'DELETE FROM "'.$table.'" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid',
                        [
                            ':from' => $from,
                            ':to'   => $_SESSION['id'],
                            ':hpid' => $hpid
                        ]
                    ],Db::FETCH_ERRSTR);
        }

        $table .= 'posts_no_notify';
        return Db::query(
                [
                    'DELETE FROM "'.$table.'" WHERE "user" = :user AND "hpid" = :hpid',
                    [
                        ':hpid' => $hpid,
                        ':user' => $_SESSION['id']
                    ]
                ],Db::FETCH_ERRSTR);
    }

    public function lurk($hpid, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'lurkers';
        return Db::query(
            [
                'INSERT INTO "'.$table.'"("from","hpid")
                SELECT :from, :hpid
                WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid)',
                [
                    ':from' => $_SESSION['id'],
                    ':hpid' => $hpid
                ]
            ], Db::FETCH_ERRSTR);
    }

    public function unlurk($hpid, $prj = false)
    {
        if(!$this->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($prj ? 'groups_' : '').'lurkers';
        return Db::query(
            [
                'DELETE FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid',
                [
                    ':from' => $_SESSION['id'],
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_ERRSTR);
    }

    public function isOnline($id)
    {
        if(!($o = Db::query(
            [
                'SELECT ("last" + INTERVAL \'300 SECONDS\') > NOW() AS online,"viewonline"
                FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;
        return $o->viewonline && $o->online;
    }

    public function hasClosedProfile($id)
    {
        if(!($o = Db::query(
            [
                'SELECT "closed" FROM "profiles" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;
        return $o->closed;
    }

    public function getBlacklist()
    {
        if((!$this->isLogged())||(!($r = Db::query(
            [
                'SELECT "to" FROM "blacklist" WHERE "from" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT))))
            return [];

        return $r->fetchAll(PDO::FETCH_COLUMN);
    }

    public function hasInBlacklist($other)
    {
        if(!$this->isLogged())
            return false;

        return $stmt = Db::query(
            [
                'SELECT 1 FROM "blacklist" WHERE "from" = :from AND "to" = :other',
                [
                    ':from'  => $_SESSION['id'],
                    ':other' => $other
                ]
            ],Db::ROW_COUNT);
    }

    public function isFollowing($id, $project = false)
    {
        if(!$this->isLogged())
            return false;

        $table = ($project ? 'groups_' : '').'followers';

        return $stmt = Db::query(
            [
                'SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "to" = :other',
                [
                    ':from'  => $_SESSION['id'],
                    ':other' => $id
                ]
            ],Db::ROW_COUNT);
    }

    public function hasLocked($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts_no_notify';
        return (
            $this->isLogged() &&
            Db::query(
                [
                    'SELECT "hpid" FROM "'.$table.'" WHERE "hpid" = :hpid AND "user" = :id',
                    [
                        ':hpid' => $post['hpid'],
                        ':id'   => $_SESSION['id']
                    ]
                ],Db::ROW_COUNT) > 0
            );
    }

    public function hasLurked($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'lurkers';
        return (
            $this->isLogged() &&
            Db::query(
                [
                    'SELECT "hpid" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :id',
                    [
                        ':hpid' => $post['hpid'],
                        ':id'   => $_SESSION['id']
                    ]
                ],Db::ROW_COUNT) > 0
            );
    }

    public function hasBookmarked($post, $project = false)
    {
        $table = ($project ? 'groups_' : '').'bookmarks';
        return (
            $this->isLogged() &&
            Db::query(
                [
                    'SELECT "hpid" FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" = :id',
                    [
                        ':hpid' => $post['hpid'],
                        ':id'   => $_SESSION['id']
                    ]
                ],Db::ROW_COUNT) > 0
            );
    }

    public function getWhitelist($id)
    {
        if(!($stmt = Db::query(
            [
                'SELECT "to" FROM "whitelist" WHERE "from" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return false;

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function isLogged()
    {
        return isset($_SESSION['logged']) && $_SESSION['logged'];
    }

    public function getObject($id)
    {
        return Db::query(
            [
                'SELECT * FROM "users" u JOIN "profiles" p ON u.counter = p.counter WHERE p.counter = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ);
    }

    public function getEmail($id)
    {
        if(!($o = Db::query(
            [
                'SELECT "email" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;
        return $o->email;
    }

    public function getGravatar($id)
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower($this->getEmail($id)));
    }

    public function getId($username = null)
    {
        if($this->isLogged() && ($username === null))
            return $_SESSION['id'];

        if(!($id = Db::query(
            [
                'SELECT "counter" FROM "users" WHERE LOWER("username") = LOWER(:username)',
                    [
                        ':username' => htmlspecialchars($username,ENT_QUOTES,'UTF-8')
                    ]
                ],Db::FETCH_OBJ)))
                return 0;

        return $id->counter;
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
                if(!($o = Db::query(
                    [
                        'SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',
                        [
                            ':id' => $_SESSION['id']
                        ]
                    ],Db::FETCH_OBJ)))
                    return false;

                $_SESSION['template'] = $o->mobile_template;
                return $_SESSION['template'];
            }
            else
                return $_SESSION['template'];
        }

        if(!($o = Db::query(
            [
                'SELECT "mobile_template" FROM "profiles" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return '1';

        return $o->mobile_template;
    }

    public function getTemplate($id = null)
    {
        if(static::isOnMobileHost()) {
            return $this->getMobileTemplate($id);
        }

        $logged = $this->isLogged();

        if(!$id && !$logged)
            return '0'; //default

        if(!$id && $logged)
        {
            if(!isset($_SESSION['template']))
            {
                if(!($o = Db::query(
                    [
                        'SELECT "template" FROM "profiles" WHERE "counter" = :id',
                        [
                            ':id' => $_SESSION['id']
                        ]
                    ],Db::FETCH_OBJ)))
                    return false;

                $_SESSION['template'] = $o->template;
                return $_SESSION['template'];
            }
            else
                return $_SESSION['template'];
        }

        if(!($o = Db::query(
            [
                'SELECT "template" FROM "profiles" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return '0';

        return $o->template;
    }

    private function getTimezone($id = null)
    {
        if(!$this->isLogged())
            return  'UTC';

        if(!$id && isset($_SESSION['timezone']))
            return $_SESSION['timezone'];

        if(!$id)
            $id = $_SESSION['id'];

        if(!($o = Db::query(
            [
                'SELECT "timezone" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 'UTC';

        if($id ==  $_SESSION['id'])
            $_SESSION['timezone'] = $o->timezone;

        return $o->timezone;
    }

    private function getDateFormat($id = null)
    {
        if(!$this->isLogged())
            return  'Y/m/d, H:i';

        if(!$id && isset($_SESSION['dateformat']))
            return $_SESSION['dateformat'];

        if(!$id)
            $id = $_SESSION['id'];

        if($id ==  $_SESSION['id'] && isset($_SESSION['dateformat']))
            return $_SESSION['dateformat'];

        if(!($o = Db::query(
            [
                'SELECT "dateformat" FROM "profiles" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 'Y/m/d, H:i';

        $_SESSION['dateformat'] = $o->dateformat;

        return $o->dateformat;
    }

    public function getDateTime($timestamp)
    {
        $timezone = $this->getTimezone($this->isLogged() ? $_SESSION['id'] : 0);

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

        return $date->format( $this->getDateFormat($this->isLogged() ? $_SESSION['id'] : 0) );
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

        if(($obj = Db::query(
            [
                'SELECT "username","password" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $_COOKIE['nerdz_id']
                ]
            ],Db::FETCH_OBJ)) && md5($obj->password) === $_COOKIE['nerdz_u']
        )
        return $this->login($obj->username, $obj->password, true, false, true);

        return false;
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

    public static function getUsername($id=null)
    {
        if(isset($_SESSION['logged']) && $_SESSION['logged'] && (($id===null) || $id == $_SESSION['id']))
            return $_SESSION['username'];

        $field = is_numeric($id) ? 'counter' : 'email';

        if(!($o = Db::query(
            [
                'SELECT "username" FROM "users" WHERE "'.$field.'" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;

        return $o->username;
    }
}
?>
