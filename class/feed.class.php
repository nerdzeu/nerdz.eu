<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';

class feed extends project
{
    private $ssl, $baseurl;
 
    public function __construct()
    {
        parent::__construct();

        $this->baseurl = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://'.SITE_HOST.'/';
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

    private function xmlentity($str)
    {
        $str = html_entity_decode($str,ENT_QUOTES,'UTF-8');
        return str_replace('<','&lt;',
               str_replace('>','&gt;',
               str_replace("'",'&apos;',
               str_replace('"','&quot;',
               str_replace('&','&amp;',$str)))));
    }

    private function getValidFeedMessage($message) //40 "parole"
    {
        $m = explode(' ',$message);
        $i = 40;
        if(count($m) > $i)
            while(isset($m[$i]))
                unset($m[$i++]);
        return $this->xmlentity(implode(' ',$m)).'...';
    }

    private function getProfileItem($post)
    {
        $from = $this->xmlentity(parent::getUserName($post['from']));
        $to = $this->xmlentity(parent::getUserName($post['to']));

        $url = $this->baseurl.parent::userLink($to).$post['pid'];

        return "<item>
                    <title>{$from} =&gt; {$to} - {$post['pid']}</title>
                    <description><![CDATA[".parent::bbcode($this->getValidFeedMessage($post['message']))."]]></description>
                    <link>{$url}</link>
                    <pubDate>".date('r',$post['cmp'])."</pubDate>
                    <guid>{$url}</guid>
                </item>";
    }

    private function getProjectItem($post)
    {
        $from = $this->xmlentity(parent::getUserName($post['from']));
        $to = $this->xmlentity(parent::getProjectName($post['to']));

        $url = $this->baseurl.parent::projectLink($to).$post['pid'];

        return "<item>
                    <title>{$from} =&gt; {$to} - {$post['pid']}</title>
                    <description><![CDATA[".parent::bbcode($this->getValidFeedMessage($post['message']))."]]></description>
                    <link>{$url}</link>
                    <pubDate>".date('r',$post['cmp'])."</pubDate>
                    <guid>{$url}</guid>
                </item>";    
    }

    public function getHomeProfileFeed()
    {
        if(!parent::isLogged())
            return $this->error('Please login');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <atom:link href="http'.$this->baseurl.'feed.php" rel="self" type="application/rss+xml" />
                <title>Homepage [Users] - NERDZ RSS</title>
                <description>Homepage [Users] - NERDZ RSS</description>
                <link>'.$this->baseurl.'/home.php</link>';

        if(($m = parent::getLatests(15)))
            foreach($m as $post)
                $xml.= $this->getProfileItem($post);
        else
            return $this->error('Empty homepage');

        return $xml.'</channel></rss>';
    }

    public function getHomeProjectFeed()
    {
        if(!parent::isLogged())
            return $this->error('Please login');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <atom:link href="http'.$this->baseurl.'feed.php" rel="self" type="application/rss+xml" />
                <title>Homepage [Projects] - NERDZ RSS</title>
                <description>Homepage [Projects] - NERDZ RSS</description>
                <link>'.$this->baseurl.'/home.php?project=1</link>';

        if(($m = parent::getLatests(15,true)))
            foreach($m as $post)
                $xml.= $this->getProjectItem($post);
        else
            return $this->error('Empty homepage');

        return $xml.'</channel></rss>';
    }

    public function getProfileFeed($id)
    {
        if(!($us = parent::getUserName($id)))
            return $this->error('Invalid user ID');

        $urluser = phpCore::userLink($us);
        $us = $this->xmlentity($us);
    
        if(!parent::isLogged() && (!($p = parent::query(array('SELECT "private" FROM "users" WHERE "counter" = ?',array($id)),db::FETCH_OBJ)) || $p->private))
                return $this->error('Private profile OR undefined error');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <atom:link href="http'.$this->baseurl.'feed.php?id='.$id.'" rel="self" type="application/rss+xml" />
                <title>'.$us.'</title>
                <description>'.$us.' NERDZ RSS</description>
                <link>'.$this->baseurl.$urluser.'</link>';

        if(($m = parent::getMessages($id,15)))
            foreach($m as $post)
                $xml .= $this->getProfileItem($post);
        else
            return $this->error('Empty profile');
        
        return $xml.'</channel></rss>';
    }

    public function getProjectFeed($id)
    {
        if(!($us = parent::getProjectName($id)))
            return $this->error('Invalid project ID');

        $urlprj = phpCore::projectLink($us);
        $us = $this->xmlentity($us);
    
        if(!($p = parent::query(array('SELECT "private","owner" FROM "groups" WHERE "counter" = ?',array($id)),db::FETCH_OBJ)))
            return $this->error('Undefined error');

        if($p->private && (!parent::isLogged() || (!in_array($_SESSION['nerdz_id'], parent::getMembers($id)) && $_SESSION['nerdz_id'] != $p->owner)))
            return $this->error('Closed project');

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <atom:link href="http'.$this->baseurl.'feed.php?id='.$id.'&amp;project=1" rel="self" type="application/rss+xml" />
                <title>'.$us.'</title>
                <description>'.$us.' NERDZ RSS</description>
                <link>'.$this->baseurl.$urlprj.'</link>';

        if(($m = parent::getProjectMessages($id,15)))
            foreach($m as $post)
                $xml .= $this->getProjectItem($post);
        else
            return $this->error('Empty project');
        
        return $xml.'</channel></rss>';
    }
}

