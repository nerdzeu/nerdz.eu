<?php
/*
 * Classe per la gestione delle notifiche
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class notify extends phpCore
{
	private $rag, $cachekey;
	
	public function __construct()
	{
		parent::__construct();
		$this->cachekey = parent::isLogged() ? "{$_SESSION['nerdz_id']}notifystory".SITE_HOST : '';
	}
	
	public function count($what = null,$rag = null)
	{
		$c = -1;
		$this->rag = $rag;
		
		if(empty($what))
			$what = 'all';
		
		switch(trim(strtolower($what)))
		{
			case 'users':
				$c = $this->countUsers();
			break;
			case 'posts':
				$c = $this->countPosts();
			break;
			case 'projects':
				$c = $this->countProjects();
			break;
			case 'projects_news':
				$c = $this->countProjectsNews();
			break;
			case 'follow':
				$c = $this->countFollow();
			break;
			case 'all':
				$c = (($a = $this->countUsers())<=0 ? 0 : $a) + (($a = $this->countPosts())<=0 ? 0 : $a) + (($a = $this->countProjects())<=0 ? 0 : $a) + (($a = $this->countProjectsNews())<=0 ? 0 : $a) + (($a = $this->countFollow())<=0 ? 0 : $a);
			break;
		}
		return $c;
	}

	private function countUsers()
	{
		$q = $this->rag ? 'SELECT COUNT(`to`) AS cc FROM (SELECT `to` FROM `comments_notify` WHERE `to` = :id GROUP BY `hpid`) AS c' :
						  'SELECT COUNT(`to`) AS cc FROM `comments_notify` WHERE `to` = :id';
		
		if(!($o = parent::query(array($q,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
		
		return $o->cc;
	}

	private function countPosts()
	{
		if(!($o = parent::query(array('SELECT COUNT(`hpid`) AS cc FROM `posts` WHERE `notify` = 1 AND `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
		
		return $o->cc;
	}

	private function countProjects()
	{
		$q = $this->rag ? 'SELECT COUNT(`to`) AS cc FROM (SELECT `to` FROM groups_comments_notify WHERE `to` = :id GROUP BY `hpid`) AS c' :
						  'SELECT COUNT(`to`) AS cc FROM `groups_comments_notify` WHERE `to` = :id';
			
		if(!($o = parent::query(array($q,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
		
		return $o->cc;
	}

	private function countProjectsNews()
	{
		if(!($o = parent::query(array('SELECT COUNT(DISTINCT `group`) AS cc FROM `groups_notify` WHERE `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
		return $o->cc;
	}

	private function countFollow()
	{
		if(!($o = parent::query(array('SELECT COUNT(`to`) AS cc FROM `follow` WHERE `to` = :id AND `notified` = 1',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
	
		return $o->cc;
	}

	public function show($what = null,$del = true)
	{
		$ret = array();
		if(empty($what))
			$what = 'all';
			
		switch(trim(strtolower($what)))
		{
			case 'users':
				$ret = $this->getUsers($del);
			break;
			case 'posts':
				$ret = $this->getPosts($del);
			break;
			case 'projects':
				$ret = $this->getProjects($del);
			break;
			case 'projects_news':
				$ret = $this->getProjectsNews($del);
			break;
			case 'follows':
				$ret = $this->getFollows($del);
			break;
			case 'all':
				$ret = array_merge($this->getUsers($del),$this->getPosts($del),$this->getProjects($del),$this->getProjectsNews($del),$this->getFollows($del));
			break;
		}
		return $ret;
	}

	private function getUsers($del)
	{
		$ret = array();
		$i = 0;
		$result = parent::query(array('SELECT `from`,`hpid`,`time` FROM `comments_notify` WHERE `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
		
		while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = parent::query(array('SELECT `from`,`to`,`pid` FROM `posts` WHERE `hpid` = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ)))
		{
			$ret[$i]['from'] = $o->from;
			$ret[$i]['from_user'] = parent::getUserName($o->from);
			$ret[$i]['to'] = $p->to;
			$ret[$i]['to_user'] = parent::getUserName($p->to);
			$ret[$i]['post_from_user'] = parent::getUserName($p->from);
			$ret[$i]['post_from'] = $p->from;
			$ret[$i]['pid'] = $p->pid;
			$ret[$i]['datetime'] = parent::getDateTime($o->time);
			$ret[$i]['cmp'] = $o->time;
			$ret[$i]['board'] = false;
			$ret[$i]['project'] = false;
			++$i;
		}

		if($del && (db::NO_ERR != parent::query(array('DELETE FROM `comments_notify` WHERE `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR)))
			return array();
		return $ret;
	}

	private function getPosts($del)
	{
		$ret = array();
		$i = 0;
		$result = parent::query(array('SELECT `pid`,`hpid`,`from`,`time` FROM `posts` WHERE `notify` = 1 AND `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
		$to = parent::getUserName($_SESSION['nerdz_id']);

		while(($o = $result->fetch(PDO::FETCH_OBJ)))
		{
			$ret[$i]['from'] = $o->from;
			$ret[$i]['from_user'] = parent::getUserName($o->from);
			$ret[$i]['to'] = $_SESSION['nerdz_id'];
			$ret[$i]['to_user'] = $to;
			$ret[$i]['pid'] = $o->pid;
			$ret[$i]['datetime'] = parent::getDateTime($o->time);
			$ret[$i]['cmp'] = $o->time;
			$ret[$i]['board'] = true;
			$ret[$i]['project'] = false;
			if($del && (db::NO_ERR != parent::query(array('UPDATE `posts` SET `notify` = 0 WHERE `hpid` = :hpid',array(':hpid' => $o->hpid)),db::FETCH_ERR)))
				return array();
			++$i;
		}
		return $ret;
	}

	private function getProjects($del)
	{
		$ret = array();
		$i = 0;
		$result = parent::query(array('SELECT `from`,`hpid`,`time` FROM `groups_comments_notify` WHERE `to` = :id', array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);

		while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = parent::query(array('SELECT `from`,`to`,`pid` FROM `groups_posts` WHERE `hpid` = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ)))
		{
			$ret[$i]['from'] = $o->from;
			$ret[$i]['from_user'] = parent::getUserName($o->from);
			$ret[$i]['to'] = $p->to;
			$ret[$i]['to_project'] = parent::getProjectName($p->to);
			$ret[$i]['post_from_user'] = parent::getUserName($p->from);
			$ret[$i]['post_from'] = $p->from;
			$ret[$i]['pid'] = $p->pid;
			$ret[$i]['datetime'] = parent::getDateTime($o->time);
			$ret[$i]['cmp'] = $o->time;
			$ret[$i]['board'] = false;
			$ret[$i]['project'] = true;
			++$i;
		}

		if($del && (db::NO_ERR != parent::query(array('DELETE FROM `groups_comments_notify` WHERE `to` = :id', array(':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR)))
			return array();
		return $ret;
	}

	private function getProjectsNews($del)
	{
		$ret = array();
		$i = 0;
		$result = parent::query(array('SELECT DISTINCT `group`,`time` FROM `groups_notify` WHERE `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
		while(($o = $result->fetch(PDO::FETCH_OBJ)))
		{
			$ret[$i]['project'] = true;
			$ret[$i]['to'] = $o->group;
			$ret[$i]['to_project'] = parent::getProjectName($o->group);
			$ret[$i]['datetime'] = parent::getDateTime($o->time);
			$ret[$i]['cmp'] = $o->time;
			$ret[$i]['news'] = true;
			++$i;
		}
		if($del && (db::NO_ERR != parent::query(array('DELETE FROM `groups_notify` WHERE `to` = :id', array(':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR)))
			return array();
		return $ret;
	}

	private function getFollows($del)
	{
		$ret = array();
		$i = 0;
		$result = parent::query(array('SELECT `from`,`time` FROM `follow` WHERE `to` = :id AND `notified` = 1',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
		while(($o = $result->fetch(PDO::FETCH_OBJ)))
		{
			$ret[$i]['follow'] = true;
			$ret[$i]['from'] = $o->from;
			$ret[$i]['from_user'] = parent::getUserName($o->from);
			$ret[$i]['datetime'] = parent::getDateTime($o->time);
			$ret[$i]['cmp'] = $o->time;
			++$i;
		}
		
		if($del && (db::NO_ERR != parent::query(array('UPDATE `follow` SET `notified` = 0 WHERE `to` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR)))
			return array();
		return $ret;
	}

	public function story()
	{
		if(apc_exists($this->cachekey))
			return unserialize(apc_fetch($this->cachekey));
		else
		{
			if(!($o = parent::query(array('SELECT `notify_story` FROM `users` WHERE `counter` = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
				return array();
		
			$ret = json_decode($o->notify_story,true);
			apc_store($this->cachekey,serialize($ret),300); //5 secondi
			return $ret;
		}
	}

	public function updateStory($new)
	{
		$old = $this->story();
		if(empty($old))
		{
			if(db::NO_ERR != parent::query(array('UPDATE `users` SET `notify_story` = :story WHERE `counter` = :id',array(':story' => json_encode($new,JSON_FORCE_OBJECT),':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
				return false;
		}
		else
		{
			if(($c = count($old))>15)
			{
				for($i=15;$i<$c;++$i)
					unset($old[$i]);
				$c = count($new);
				for($i=0;$i<$c;++$i)
					array_unshift($old,$new[$i]);
			}
			else				
				for($i=0, $c = count($new);$i<$c;++$i)
					array_unshift($old,$new[$i]);

			if(db::NO_ERR != parent::query(array('UPDATE `users` SET `notify_story` = :story WHERE `counter` = :id',array(':story' => json_encode($old,JSON_FORCE_OBJECT),':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
				return false;
		}
		apc_delete($this->cachekey);
		return true;
	}

	public static function echoSort($a,$b) //callback
	{
		return $a[1] == $b[1] ? 0 : $a[1] > $b[1] ? -1 : 1;
	}
}
?>
