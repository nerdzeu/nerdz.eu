<?php
/*
 * Classe per la gestione degli intervalli di flooding tra i posts e i commenti
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class flood extends phpCore
{
	public function __construct()
	{
		parent::__construct();
	}

	public function pm()
	{
		if(!parent::isLogged())
			return false;
			
		if(!isset($_SESSION['nerdz_MPflood']))
		{
			if(($r = parent::query(array('SELECT MAX("time") AS cc FROM "pms" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)))
			{
				if(!$r->rowCount()) //first pm
				{
					$_SESSION['nerdz_MPflood'] = time();
				}
				else
				{
					$o = $r->fetch(PDO::FETCH_OBJ);
					if(($o->cc +10) >= time())
						return false;
					else
						$_SESSION['nerdz_MPflood'] = time();
				}
			}
			else
			{
				$_SESSION['nerdz_MPflood'] = time(); //?
				return true;
			}
		}
		else
		{
			if(($_SESSION['nerdz_MPflood']+10) >= time())
				return false;
			$_SESSION['nerdz_MPflood'] = time();
		}
		
		return true;
	}
	
	public function profilePost()
    {
		if(!parent::isLogged())
			return false;

		if(!isset($_SESSION['nerdz_ProfileFlood']))
		{
			if(($r = parent::query(array('SELECT MAX("time") as cc FROM "posts" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)))
			{
				if(!$r->rowCount()) //first post
				{
					$_SESSION['nerdz_ProfileFlood'] = time();
					return true;
				}
				
				$o = $r->fetch(PDO::FETCH_OBJ);
				
				if(($o->cc + 30) >= time())
					return false;
					
				$_SESSION['nerdz_ProfileFlood'] = time();
				return true;
			}
			return false;
		}
		if(($_SESSION['nerdz_ProfileFlood']+30) >= time())
			return false;
			
		$_SESSION['nerdz_ProfileFlood'] = time();
		return true;
	}
	
	public function projectPost()
	{
		if(!parent::isLogged())
			return false;

		if(!isset($_SESSION['nerdz_ProjectFlood']))
		{
			if(($r = parent::query(array('SELECT MAX("time") as cc FROM "groups_posts" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)))
			{
				if(!$r->rowCount()) //first post
				{
					$_SESSION['nerdz_ProjectFlood'] = time();
					return true;
				}
				
				$o = $r->fetch(PDO::FETCH_OBJ);
				
				if(($o->cc +30) >= time())
					return false;
					
				$_SESSION['nerdz_ProjectFlood'] = time();
				return true;
			}
			return false;
		}
		if(($_SESSION['nerdz_ProjectFlood']+30) >= time())
			return false;
		$_SESSION['nerdz_ProjectFlood'] = time();
		return true;
	}
	
	public function postComment()
	{
		if(!parent::isLogged())
			return false;
			
		if(!isset($_SESSION['nerdz_PostCommentFlood']))
		{
			if(($r = parent::query(array('SELECT MAX("time") as cc FROM "comments" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)))
			{
				if(!$r->rowCount()) //first comment
				{
					$_SESSION['nerdz_PostCommentFlood'] = time();
					return true;
				}
				
				$o = $r->fetch(PDO::FETCH_OBJ);
				
				if(($o->cc +8) >= time())
					return false;
					
				$_SESSION['nerdz_PostCommentFlood'] = time();
				return true;
			}
			return false;
		}
		
		if(($_SESSION['nerdz_PostCommentFlood']+8) >= time())
			return false;
			
		$_SESSION['nerdz_PostCommentFlood'] = time();
		return true;
	}
	
	public function projectComment()
	{
		if(!parent::isLogged())
			return false;
	
		if(!isset($_SESSION['nerdz_ProjectCommentFlood']))
		{
			if(($r = parent::query(array('SELECT MAX("time") as cc FROM "groups_comments" WHERE "from" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)))
			{
				if(!$r->rowCount()) //first comment
				{
					$_SESSION['nerdz_ProjectCommentFlood'] = time();
					return true;
				}
				
				$o = $r->fetch(PDO::FETCH_OBJ);
				
				if(($o->cc +8) >= time())
					return false;
				$_SESSION['nerdz_ProjectCommentFlood'] = time();
				return true;
			}
			return false;
		}
		
		if(($_SESSION['nerdz_ProjectCommentFlood']+8) >= time())
			return false;
			
		$_SESSION['nerdz_ProjectCommentFlood'] = time();
		return true;	
	}
}
?>
