<?php
/*
 * Classe per la gestione dei PM, i nomi sono esplicativi.
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';

final class pm extends messages
{
	const MAX_CONVERSATIONS = 20;

	public function __construct()
	{
		parent::__construct();
	}

	public function send($to,$message)
	{
		require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
		if(!(new flood())->pm())
			return null;
		
		if(
				isset($message[65534]) ||
				!($stmt = parent::query(array('SELECT `from`, `pmid`,`message` AS oldmessage FROM `pms` WHERE `pmid` = (SELECT MAX(`pmid`) FROM `pms` WHERE (`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?))',
												  array($_SESSION['nerdz_id'],$to,$to,$_SESSION['nerdz_id'])),db::FETCH_STMT)
				 )
		  )
			return false;

		$o = $stmt->fetch(PDO::FETCH_OBJ);

		if($o && $o->from == $_SESSION['nerdz_id'])
			return $this->appendMessage($message,$o->oldmessage,$o->pmid);
	
		return db::NO_ERR == parent::query(array('INSERT INTO `pms` (`from`,`to`,`message`,`time`,`read`) VALUES (:id,:to,:message,UNIX_TIMESTAMP(),1)',array(':id' => $_SESSION['nerdz_id'],':to' => $to,':message' => $message)),db::FETCH_ERR);
    }

	private function appendMessage($message,$oldMessage,$pmid)
	{
		$newmsg = $oldMessage.'[hr]'.$message;

		if(isset($newmsg[65534]))
			return false;

		return db::NO_ERR == parent::query(array('UPDATE `pms` SET `message` = :message, `read` = 1, `time` = UNIX_TIMESTAMP() WHERE `pmid` = :pmid',array(':message' => $newmsg,':pmid' => $pmid)),db::FETCH_ERR);
	}
    
    
    public function getList()
    {
		do
		{
			if(!($rs = parent::query(array('SELECT * FROM ((SELECT DISTINCT time as lasttime, `from`,`read` FROM pms where `read` = 1 and `to` = ?)  UNION (SELECT MAX(`time`) AS lasttime, `from`, `read` FROM pms WHERE `to` = ? GROUP BY `from`)) AS tmp ORDER BY `read` DESC, `lasttime` DESC',array($_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_STMT)))
				return false;
	
			$times = $res = array();
			$c = 0;
			while(($o = $rs->fetch(PDO::FETCH_OBJ)))
			{
				$from = $this->getUserName($o->from);
				$res[$c]['from4link_n'] = phpCore::userLink($from);
				$res[$c]['from_n'] = $from;
				$res[$c]['datetime_n'] = parent::getDateTime($o->lasttime);
				$res[$c]['fromid_n'] = $o->from;
				$res[$c]['toid_n'] = $_SESSION['nerdz_id'];
				$times[$c] = $o->lasttime;
				++$c;
			}

			$res = array_unique($res,SORT_REGULAR); //fix for new duplicate pm
			$c = count($res);
			
			$redo = false;
			if($c >= self::MAX_CONVERSATIONS)
			{
				sort($times,SORT_NUMERIC);
				// probably I have to fix something about replacing time with ids here, but that query is just WAT
				if(db::NO_ERR !=  parent::query(array('DELETE FROM `pms` WHERE (`time` BETWEEN ? AND ?) AND (`to` = ? OR `from` = ?)',array($times[0],$times[1],$_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_ERR))
					return false;
				$redo = true;
			}
		}
		while($redo);
		
		return $res;
	}
	
	public function read($fromid,$toid,$time,$pmid)
	{
		$ret = array();
			
		if(
				!is_numeric($fromid) || !is_numeric($toid) || !is_numeric ($pmid) || !in_array($_SESSION['nerdz_id'],array($fromid,$toid)) ||
				!($res = parent::query(array('SELECT `message`,`read` FROM `pms` WHERE `from` = :from AND `to` = :to AND `pmid` = :pmid',array(':from' => $fromid, ':to' => $toid, ':pmid' => $pmid)),db::FETCH_STMT))
		  )
			return false;

		if(($o = $res->fetch(PDO::FETCH_OBJ)))
		{
			$from = $this->getUserName($fromid);
			$ret['from4link_n'] = phpCore::userLink($from);
			$ret['from_n'] = $from;
			$ret['datetime_n'] = parent::getDateTime($time);
			$ret['fromid_n'] = $fromid;
			$ret['toid_n'] = $toid;
			$ret['message_n'] = parent::bbcode($o->message);
			$ret['read_b'] = $o->read;
			$ret['pmid_n'] = $pmid;
			//$ret['realto_n'] = $fromid != $_SESSION['nerdz_id'] ? $from : $this->getUserName ($toid);
		}
		
		return $ret;
	}
	
	public function countNew()
	{
		if(!($o = parent::query(array('SELECT COUNT(DISTINCT `from`) as cc FROM (SELECT `from` FROM `pms` WHERE `to` = :id AND `read` = 1) AS tmp1',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
			return -1;
		return $o->cc;
	}
    	
	public function deleteConversation($from, $to)
   {
		return is_numeric($from) && is_numeric($to) && in_array($_SESSION['nerdz_id'],array($from,$to)) && 
			db::NO_ERR == parent::query(array('DELETE FROM `pms` WHERE (`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)',array($from,$to,$to,$from)),db::FETCH_ERR);
	}
    
    public function readConversation($from, $to, $afterPmId = null, $num = null, $start = 0)
    {
		$ret = array();
		
		if(!is_numeric($from) || !is_numeric($to) || (is_numeric ($num) && is_numeric ($start) && ($start < 0 || $start > 200 || $num < 0 || $num > 10)) /*|| !in_array($_SESSION['nerdz_id'],array($from,$to))*/)
			return $ret;
		$__enableLimit = is_numeric ($num) && is_numeric ($start);
		$query = $__enableLimit ?
		                //"SELECT q.from, q.to, q.time FROM (SELECT `from`, `to`, `time` FROM `pms` WHERE ((`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)) ORDER BY `pmid`"
		                'SELECT q.from, q.to, q.time, q.pmid FROM (SELECT `from`, `to`, `time`, `pmid` FROM `pms` WHERE ((`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)) ORDER BY `pmid` DESC LIMIT ?, ?) AS q ORDER BY q.pmid ASC' :
		                'SELECT `from`, `to`, `time`, `pmid` FROM `pms` WHERE '.($afterPmId ? '`pmid` > ? AND ' : '').' ((`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)) ORDER BY `pmid` ASC';
		if (!($res = parent::query (array ($query, ($__enableLimit ? array ($from, $to, $to, $from, $start * $num, $num) : ( $afterPmId ? array ($afterPmId, $from, $to, $to, $from) : array ($from, $to, $to, $from)))), db::FETCH_STMT)))
			  	/*!($res = parent::query(
					array('SELECT `from`,`to`,`time` FROM `pms` WHERE '.($afterPmId ? '`pmid` > ? AND ' : '').' ((`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)) ORDER BY `pmid` ASC',
					$afterPmId ?
						array($afterPmId,$from, $to,$to,$from) :
						array($from, $to,$to,$from)
					),db::FETCH_STMT)))*/
			return $ret;

		$ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));

		//se l'ultimo l'ho inviato io e ora voglio vedere presumibilmente l'append, mostro il nuovo commento se non ne sono stati aggiunti di nuovi dall'altro prima
		if($afterPmId && empty($ret))
		{
			if(!($res = parent::query(
					array('SELECT `from`,`to`,`time`,`pmid` FROM `pms` WHERE `pmid` = ? AND ((`from` = ? AND `to` = ?) OR (`from` = ? AND `to` = ?)) ORDER BY `pmid` ASC',array($afterPmId,$from, $to,$to,$from)
						  ),db::FETCH_STMT)))
				return $ret;
			$ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));
		}
		if(db::NO_ERR != parent::query(array('UPDATE `pms` SET `read` = 0 WHERE `from` = :from AND `to` = :id',array(':from' => $from, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
			return false;
		
		return $ret;
	}
}
?>
