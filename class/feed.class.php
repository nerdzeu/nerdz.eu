<?php
namespace NERDZ\Core;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Feed extends Messages
{
    private $ssl, $baseurl;

    public function __construct()
    {
        parent::__construct();
        $this->baseurl = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://'.Config\SITE_HOST.'/';
    }

    public function error($desc)
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
            <atom:link href="'.$this->baseurl.'error.php" rel="self" type="application/rss+xml" />
            <title>RSS Error</title>
            <description>'.$desc.'</description>
            <link></link>
            </channel>
            </rss>';
    }

    private function getValidFeedMessage($message) //40 words
    {
        $m = explode(' ',$message);
        $i = 40;
        if(count($m) > $i)
            while(isset($m[$i]))
                unset($m[$i++]);
        return implode(' ',$m).'...';
    }

    private function getProfileItem($post)
    {
        $from = $post['from_n'];
        $to   = $post['to_n'];

        $url = $this->baseurl.$post['to4link_n'].$post['pid_n'];

        return "<item>
            <title>{$from} =&gt; {$to} - {$post['pid_n']}</title>
            <description><![CDATA[".$this->getValidFeedMessage($post['message_n'])."]]></description>
            <link>{$url}</link>
            <pubDate>".date('r',$post['timestamp_n'])."</pubDate>
            <guid>{$url}</guid>
            </item>";
    }

    private function getProjectItem($post)
    {
        $from = $post['from_n'];
        $to   = $post['to_n'];

        $url = $this->baseurl.$post['to4link_n'].$post['pid_n'];

        return "<item>
            <title>{$from} =&gt; {$to} - {$post['pid_n']}</title>
            <description><![CDATA[".$this->getValidFeedMessage($post['message_n'])."]]></description>
            <link>{$url}</link>
            <pubDate>".date('r',$post['timestamp_n'])."</pubDate>
            <guid>{$url}</guid>
            </item>";    
    }

    public function getHomeProfileFeed()
    {
        if(!$this->user->isLogged())
            return $this->error('Please login');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
            <atom:link href="http'.$this->baseurl.'feed.php" rel="self" type="application/rss+xml" />
            <title>Homepage [Users] - '.Config\SITE_NAME.' RSS</title>
            <description>Homepage [Users] - '.Config\SITE_NAME.' RSS</description>
            <link>'.$this->baseurl.'/home.php</link>';

        if(($m = parent::getPosts(null, [ 'limit' => 15 ])))
            foreach($m as $post)
                $xml.= $this->getProfileItem($post);
        else
            return $this->error('Empty homepage');

        return $xml.'</channel></rss>';
    }

    public function getHomeProjectFeed()
    {
        if(!$this->user->isLogged())
            return $this->error('Please login');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
            <atom:link href="http'.$this->baseurl.'feed.php" rel="self" type="application/rss+xml" />
            <title>Homepage [Projects] - '.Config\SITE_NAME.' RSS</title>
            <description>Homepage [Projects] - '.Config\SITE_NAME.' RSS</description>
            <link>'.$this->baseurl.'/home.php?project=1</link>';

        if(($m = parent::getPosts(null, [ 'project' => true, 'limit' => 15 ] )))
            foreach($m as $post)
                $xml.= $this->getProjectItem($post);
        else
            return $this->error('Empty homepage');

        return $xml.'</channel></rss>';
    }

    public function getProfileFeed($id)
    {
        if(!($us = User::getUsername($id)))
            return $this->error('Invalid user ID');

        $urluser = Utils::userLink($us);

        if(!$this->user->isLogged() && (!($p = Db::query(
            [
                'SELECT "private" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)) || $p->private))
            return $this->error('Private profile OR undefined error');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
            <atom:link href="http'.$this->baseurl.'feed.php?id='.$id.'" rel="self" type="application/rss+xml" />
            <title>'.$us.'</title>
            <description>'.$us.' '.Config\SITE_NAME.' RSS</description>
            <link>'.$this->baseurl.$urluser.'</link>';

        if(($m = parent::getPosts($id,[ 'limit' => 15 ])))
            foreach($m as $post)
                $xml .= $this->getProfileItem($post);
        else
            return $this->error('Empty profile');

        return $xml.'</channel></rss>';
    }

    public function getProjectFeed($id)
    {
        if(!($us = Project::getName($id)))
            return $this->error('Invalid project ID');

        $urlprj = Utils::projectLink($us);

        if(!($p = Db::query(
            [
                'SELECT "private" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return $this->error('Undefined error');

        if($p->private && (!$this->user->isLogged() || (!in_array($_SESSION['id'], $this->project->getMembers($id)) && $_SESSION['id'] != $this->project->getOwner())))
            return $this->error('Closed project');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
            <atom:link href="http'.$this->baseurl.'feed.php?id='.$id.'&amp;project=1" rel="self" type="application/rss+xml" />
            <title>'.$us.'</title>
            <description>'.$us.' '.Config\SITE_NAME.' RSS</description>
            <link>'.$this->baseurl.$urlprj.'</link>';

        if(($m = parent::getPosts($id, ['project' => true, 'limit' => 15 ]) ))
            foreach($m as $post)
                $xml .= $this->getProjectItem($post);
        else
            return $this->error('Empty project');

        return $xml.'</channel></rss>';
    }
}

